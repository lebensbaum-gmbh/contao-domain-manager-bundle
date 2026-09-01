<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use JsonException;
use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use Throwable;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SystemInfoApiClient
{
    private const ENDPOINT_PREFIX = '/_domainverwaltung/';
    private const MAX_AUTH_TIME_DIFFERENCE = 300;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SecretStore $secretStore,
    ) {
    }

    public function get(string $baseUrl, string $systemId, string $endpointPath): array
    {
        $systemId = trim($systemId);
        $endpointPath = $this->normalizeEndpointPath($endpointPath);

        try {
            $secret = $this->secretStore->getSecret($systemId);
        } catch (Throwable $exception) {
            throw new SystemInfoConnectionException(
                'configuration',
                'local_secret_unavailable',
                'Das gespeicherte Secret konnte nicht gelesen werden. Bitte die System-Info-Zugangsdaten prüfen.',
                technicalDetails: $exception->getMessage(),
                previous: $exception,
            );
        }

        if (null === $secret) {
            throw new SystemInfoConnectionException(
                'configuration',
                'local_secret_missing',
                'Für diese Installations-ID ist im Domain Manager kein Secret gespeichert.'
            );
        }

        $endpointUrl = $this->createEndpointUrl($baseUrl, $endpointPath);
        $timestamp = (string) time();
        $signedContent = implode("\n", [$timestamp, 'GET', $endpointPath]);
        $signature = hash_hmac('sha256', $signedContent, $secret);

        try {
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
        } catch (TransportExceptionInterface $exception) {
            throw new SystemInfoConnectionException(
                'transport',
                'target_unreachable',
                'Der Zielserver konnte nicht erreicht werden. Bitte Domain, DNS, HTTPS/TLS und die Erreichbarkeit des Servers prüfen.',
                technicalDetails: $exception->getMessage(),
                previous: $exception,
            );
        } catch (SystemInfoConnectionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new SystemInfoConnectionException(
                'transport',
                'request_failed',
                'Die Anfrage an die Zielinstallation konnte nicht ausgeführt werden.',
                technicalDetails: $exception->getMessage(),
                previous: $exception,
            );
        }

        $data = $this->decodeJson($content);

        if (200 !== $statusCode) {
            $this->throwForHttpError($statusCode, $data, $endpointUrl);
        }

        if (null === $data) {
            throw new SystemInfoConnectionException(
                'response',
                'invalid_json',
                'Die Zielinstallation wurde erreicht, hat aber keine gültige JSON-Antwort geliefert.',
                200,
                'Endpoint: '.$endpointUrl,
            );
        }

        if (
            !isset($data['system_id'])
            || !is_string($data['system_id'])
            || !hash_equals($systemId, $data['system_id'])
        ) {
            throw new SystemInfoConnectionException(
                'authentication',
                'installation_id_mismatch',
                'Die antwortende Installation hat nicht die erwartete Installations-ID. Bitte Installations-ID und Zuordnung prüfen.',
                200,
                'Endpoint: '.$endpointUrl,
            );
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
            throw new SystemInfoConnectionException(
                'configuration',
                'invalid_endpoint_path',
                'Der angeforderte System-Info-Endpunkt ist nicht erlaubt.'
            );
        }

        return $endpointPath;
    }

    private function createEndpointUrl(string $baseUrl, string $endpointPath): string
    {
        $baseUrl = trim($baseUrl);

        if ('' === $baseUrl) {
            throw new SystemInfoConnectionException(
                'configuration',
                'domain_missing',
                'Für die Installation wurde keine Domain angegeben.'
            );
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
            throw new SystemInfoConnectionException(
                'configuration',
                'invalid_https_url',
                sprintf('Die URL „%s“ ist keine gültige HTTPS-Adresse.', $baseUrl)
            );
        }

        $endpointUrl = 'https://'.$parts['host'];

        if (isset($parts['port'])) {
            $endpointUrl .= ':'.$parts['port'];
        }

        return $endpointUrl.$endpointPath;
    }

    private function throwForHttpError(int $statusCode, ?array $data, string $endpointUrl): never
    {
        $error = isset($data['error']) && is_string($data['error']) ? trim($data['error']) : '';
        $technicalDetails = sprintf('HTTP %d; Endpoint: %s', $statusCode, $endpointUrl);

        if (401 === $statusCode) {
            $serverTime = $this->readServerTime($data);

            if (null !== $serverTime) {
                $difference = abs(time() - $serverTime);

                if ($difference > self::MAX_AUTH_TIME_DIFFERENCE) {
                    throw new SystemInfoConnectionException(
                        'authentication',
                        'clock_skew',
                        sprintf('Die Authentifizierung ist wegen einer Zeitabweichung fehlgeschlagen. Die Serveruhren unterscheiden sich um etwa %d Sekunden.', $difference),
                        401,
                        $technicalDetails,
                    );
                }
            }

            throw new SystemInfoConnectionException(
                'authentication',
                'unauthorized',
                'Die Zielinstallation hat die Anmeldung abgelehnt. Bitte Installations-ID und Secret prüfen.',
                401,
                $technicalDetails,
            );
        }

        if (403 === $statusCode) {
            throw new SystemInfoConnectionException(
                'authentication',
                'access_denied',
                'Der System-Info-Endpunkt ist erreichbar, der Zugriff wurde jedoch verweigert.',
                403,
                $technicalDetails,
            );
        }

        if (404 === $statusCode) {
            throw new SystemInfoConnectionException(
                'endpoint',
                'endpoint_not_found',
                'Der Zielserver ist erreichbar, aber der System-Info-Endpunkt wurde nicht gefunden. Bitte prüfen, ob das System-Info-Bundle installiert und aktiv ist.',
                404,
                $technicalDetails,
            );
        }

        if (503 === $statusCode && 'service_not_configured' === $error) {
            throw new SystemInfoConnectionException(
                'endpoint',
                'remote_service_not_configured',
                'Das System-Info-Bundle ist auf der Zielinstallation erreichbar, aber noch nicht vollständig konfiguriert.',
                503,
                $technicalDetails,
            );
        }

        if ($statusCode >= 500) {
            $code = 'system_info_unavailable' === $error
                ? 'remote_system_info_unavailable'
                : 'remote_server_error';

            throw new SystemInfoConnectionException(
                'endpoint',
                $code,
                'Der Zielserver wurde erreicht, System Info konnte dort jedoch nicht ausgeführt werden. Bitte zuerst die PHP-Version der betroffenen Domain/Subdomain sowie die Contao- und Server-Fehlerprotokolle prüfen.',
                $statusCode,
                $technicalDetails,
            );
        }

        throw new SystemInfoConnectionException(
            'endpoint',
            'unexpected_http_status',
            sprintf('Der System-Info-Endpunkt hat unerwartet mit HTTP-Status %d geantwortet.', $statusCode),
            $statusCode,
            $technicalDetails,
        );
    }

    private function decodeJson(string $content): ?array
    {
        if ('' === trim($content)) {
            return null;
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function readServerTime(?array $data): ?int
    {
        if (null === $data || !array_key_exists('server_time', $data)) {
            return null;
        }

        $value = $data['server_time'];

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
