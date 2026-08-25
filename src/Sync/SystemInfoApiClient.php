<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use JsonException;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SystemInfoApiClient
{
    private const ENDPOINT_PREFIX = '/_domainverwaltung/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SecretStore $secretStore,
    ) {
    }

    public function get(string $baseUrl, string $systemId, string $endpointPath): array
    {
        $systemId = trim($systemId);
        $endpointPath = $this->normalizeEndpointPath($endpointPath);
        $secret = $this->secretStore->getSecret($systemId);

        if (null === $secret) {
            throw new RuntimeException(sprintf(
                'Für die Installations-ID "%s" wurde kein Secret gefunden.',
                $systemId
            ));
        }

        $endpointUrl = $this->createEndpointUrl($baseUrl, $endpointPath);
        $timestamp = (string) time();
        $signedContent = implode("\n", [$timestamp, 'GET', $endpointPath]);
        $signature = hash_hmac('sha256', $signedContent, $secret);

        $response = $this->httpClient->request('GET', $endpointUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'X-Domain-Manager-Timestamp' => $timestamp,
                'X-Domain-Manager-Signature' => $signature,
            ],
            'timeout' => 10,
            'max_duration' => 15,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $data = is_array($decoded) ? $decoded : null;
        } catch (JsonException) {
            if (200 !== $statusCode) {
                throw new RuntimeException(sprintf(
                    'Der Systeminfo-Abruf ist mit HTTP-Status %d fehlgeschlagen und hat keine gültige JSON-Antwort geliefert.',
                    $statusCode
                ));
            }

            throw new RuntimeException('Die Installation hat keine gültige JSON-Antwort geliefert.');
        }

        if (null === $data) {
            throw new RuntimeException('Die Systeminfo-Antwort hat ein ungültiges Format.');
        }

        if (200 !== $statusCode) {
            $error = isset($data['error']) && is_string($data['error'])
                ? $data['error']
                : 'unknown_error';

            throw new RuntimeException(sprintf(
                'Der Systeminfo-Abruf ist mit HTTP-Status %d fehlgeschlagen: %s',
                $statusCode,
                $error
            ));
        }

        if (
            !isset($data['system_id'])
            || !is_string($data['system_id'])
            || !hash_equals($systemId, $data['system_id'])
        ) {
            throw new RuntimeException('Die antwortende Installation hat nicht die erwartete Installations-ID.');
        }

        return $data;
    }

    private function normalizeEndpointPath(string $endpointPath): string
    {
        $endpointPath = trim($endpointPath);

        if (
            !str_starts_with($endpointPath, self::ENDPOINT_PREFIX)
            || str_contains($endpointPath, '..')
            || str_contains($endpointPath, '?')
            || str_contains($endpointPath, '#')
            || 1 !== preg_match('~\A/_domainverwaltung/[A-Za-z0-9/_-]+\z~', $endpointPath)
        ) {
            throw new RuntimeException('Der angeforderte System-Info-Endpunkt ist nicht erlaubt.');
        }

        return $endpointPath;
    }

    private function createEndpointUrl(string $baseUrl, string $endpointPath): string
    {
        $baseUrl = trim($baseUrl);

        if ('' === $baseUrl) {
            throw new RuntimeException('Für die Installation wurde keine URL angegeben.');
        }

        if (!str_contains($baseUrl, '://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $parts = parse_url($baseUrl);

        if (
            false === $parts
            || 'https' !== strtolower((string) ($parts['scheme'] ?? ''))
            || empty($parts['host'])
        ) {
            throw new RuntimeException(sprintf(
                'Die URL "%s" ist keine gültige HTTPS-Adresse.',
                $baseUrl
            ));
        }

        $endpointUrl = 'https://'.$parts['host'];

        if (isset($parts['port'])) {
            $endpointUrl .= ':'.$parts['port'];
        }

        return $endpointUrl.$endpointPath;
    }
}
