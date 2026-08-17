<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use JsonException;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SystemInfoClient
{
    private const ENDPOINT_PATH = '/_domainverwaltung/systeminfo';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SecretStore $secretStore,
    ) {
    }

    public function fetch(string $baseUrl, string $systemId): array
    {
        $systemId = trim($systemId);
        $secret = $this->secretStore->getSecret($systemId);

        if (null === $secret) {
            throw new RuntimeException(sprintf(
                'Für die Installations-ID "%s" wurde kein Secret gefunden.',
                $systemId
            ));
        }

        $endpointUrl = $this->createEndpointUrl($baseUrl);
        $timestamp = (string) time();
        $signedContent = implode("\n", [$timestamp, 'GET', self::ENDPOINT_PATH]);
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

        return $this->parseResponse($response, $systemId);
    }

    private function createEndpointUrl(string $baseUrl): string
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

        return $endpointUrl.self::ENDPOINT_PATH;
    }

    private function parseResponse(ResponseInterface $response, string $expectedSystemId): array
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $data = null;

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

            throw new RuntimeException(
                'Die Installation hat keine gültige JSON-Antwort geliefert.'
            );
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
            || !hash_equals($expectedSystemId, $data['system_id'])
        ) {
            throw new RuntimeException(
                'Die antwortende Installation hat nicht die erwartete Installations-ID.'
            );
        }

        if (
            !array_key_exists('contao_version', $data)
            || !isset($data['php_version'], $data['app_environment'], $data['generated_at'])
            || (null !== $data['contao_version'] && !is_string($data['contao_version']))
            || !is_string($data['php_version'])
            || !is_string($data['app_environment'])
            || !is_string($data['generated_at'])
        ) {
            throw new RuntimeException(
                'In der Systeminfo-Antwort fehlen erforderliche Angaben.'
            );
        }

        foreach (['database_name', 'document_root'] as $optionalField) {
            if (
                array_key_exists($optionalField, $data)
                && !is_string($data[$optionalField])
            ) {
                throw new RuntimeException(
                    'Die Systeminfo-Antwort enthält ungültige optionale Angaben.'
                );
            }
        }

        return [
            'system_id' => $data['system_id'],
            'contao_version' => $data['contao_version'],
            'php_version' => $data['php_version'],
            'database_name' => isset($data['database_name']) ? trim($data['database_name']) : '',
            'document_root' => isset($data['document_root']) ? trim($data['document_root']) : '',
            'app_environment' => $data['app_environment'],
            'generated_at' => $data['generated_at'],
        ];
    }
}
