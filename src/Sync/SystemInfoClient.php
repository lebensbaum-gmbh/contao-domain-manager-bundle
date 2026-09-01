<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use JsonException;
use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use Throwable;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SystemInfoClient
{
    private const ENDPOINT_PATH = '/_domainverwaltung/systeminfo';
    private const MAX_AUTH_TIME_DIFFERENCE = 300;
    private const SUPPORTED_API_VERSION = 1;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SecretStore $secretStore,
    ) {
    }

    public function fetch(string $baseUrl, string $systemId): array
    {
        $systemId = trim($systemId);

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

        $endpointUrl = $this->createEndpointUrl($baseUrl);
        $timestamp = (string) time();
        $signedContent = implode("\n", [$timestamp, 'GET', self::ENDPOINT_PATH]);
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

            return $this->parseResponse($response, $systemId, $endpointUrl);
        } catch (SystemInfoConnectionException $exception) {
            throw $exception;
        } catch (TransportExceptionInterface $exception) {
            throw new SystemInfoConnectionException(
                'transport',
                'target_unreachable',
                'Der Zielserver konnte nicht erreicht werden. Bitte Domain, DNS, HTTPS/TLS und die Erreichbarkeit des Servers prüfen.',
                technicalDetails: $exception->getMessage(),
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new SystemInfoConnectionException(
                'transport',
                'request_failed',
                'Die Anfrage an die Zielinstallation konnte nicht ausgeführt werden.',
                technicalDetails: $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    private function createEndpointUrl(string $baseUrl): string
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

        return $endpointUrl.self::ENDPOINT_PATH;
    }

    private function parseResponse(ResponseInterface $response, string $expectedSystemId, string $endpointUrl): array
    {
        try {
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw new SystemInfoConnectionException(
                'transport',
                'target_unreachable',
                'Der Zielserver konnte nicht zuverlässig erreicht werden. Bitte DNS, HTTPS/TLS und die Server-Erreichbarkeit prüfen.',
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
                'Die Zielinstallation wurde erreicht, hat aber keine gültige System-Info-JSON-Antwort geliefert.',
                200,
                'Endpoint: '.$endpointUrl,
            );
        }

        if (array_key_exists('api_version', $data)) {
            if (!is_int($data['api_version']) || self::SUPPORTED_API_VERSION !== $data['api_version']) {
                throw new SystemInfoConnectionException(
                    'response',
                    'unsupported_api_version',
                    'Die System-Info-Schnittstelle der Zielinstallation verwendet eine nicht unterstützte API-Version.',
                    200,
                    'Endpoint: '.$endpointUrl,
                );
            }
        }

        if (
            !isset($data['system_id'])
            || !is_string($data['system_id'])
            || !hash_equals($expectedSystemId, $data['system_id'])
        ) {
            throw new SystemInfoConnectionException(
                'authentication',
                'installation_id_mismatch',
                'Die antwortende Installation hat nicht die erwartete Installations-ID. Bitte Installations-ID und Zuordnung prüfen.',
                200,
                'Endpoint: '.$endpointUrl,
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
            throw new SystemInfoConnectionException(
                'response',
                'incomplete_response',
                'Die System-Info-Antwort ist unvollständig oder hat ein unerwartetes Format.',
                200,
                'Endpoint: '.$endpointUrl,
            );
        }

        foreach (['database_name', 'document_root', 'system_info_version'] as $optionalField) {
            if (array_key_exists($optionalField, $data) && !is_string($data[$optionalField])) {
                throw new SystemInfoConnectionException(
                    'response',
                    'invalid_optional_field',
                    'Die System-Info-Antwort enthält ungültige optionale Angaben.',
                    200,
                    'Endpoint: '.$endpointUrl,
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
            'api_version' => isset($data['api_version']) && is_int($data['api_version']) ? $data['api_version'] : null,
            'system_info_version' => isset($data['system_info_version']) ? trim($data['system_info_version']) : '',
        ];
    }

    private function throwForHttpError(int $statusCode, ?array $data, string $endpointUrl): never
    {
        $error = isset($data['error']) && is_string($data['error'])
            ? trim($data['error'])
            : '';
        $technicalDetails = sprintf('HTTP %d; Endpoint: %s', $statusCode, $endpointUrl);

        if (401 === $statusCode) {
            $serverTime = $this->readServerTime($data);

            if (null !== $serverTime) {
                $difference = abs(time() - $serverTime);

                if ($difference > self::MAX_AUTH_TIME_DIFFERENCE) {
                    throw new SystemInfoConnectionException(
                        'authentication',
                        'clock_skew',
                        sprintf(
                            'Die Authentifizierung ist wegen einer Zeitabweichung fehlgeschlagen. Die Serveruhren unterscheiden sich um etwa %d Sekunden.',
                            $difference
                        ),
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
            $message = 'Der Zielserver wurde erreicht, System Info konnte dort jedoch nicht ausgeführt werden. Bitte zuerst die PHP-Version der betroffenen Domain/Subdomain sowie die Contao- und Server-Fehlerprotokolle prüfen.';
            $code = 'system_info_unavailable' === $error
                ? 'remote_system_info_unavailable'
                : 'remote_server_error';

            throw new SystemInfoConnectionException(
                'endpoint',
                $code,
                $message,
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
