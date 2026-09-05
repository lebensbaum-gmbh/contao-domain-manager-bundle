<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;

final class SystemInfoClient
{
    private const ENDPOINT_PATH = '/_domainverwaltung/systeminfo';
    private const SUPPORTED_API_VERSION = 1;

    public function __construct(private readonly SystemInfoApiClient $apiClient)
    {
    }

    public function fetch(string $baseUrl, string $systemId): array
    {
        $data = $this->apiClient->get($baseUrl, $systemId, self::ENDPOINT_PATH);

        if (array_key_exists('api_version', $data)) {
            if (!is_int($data['api_version']) || self::SUPPORTED_API_VERSION !== $data['api_version']) {
                throw new SystemInfoConnectionException(
                    'response',
                    'unsupported_api_version',
                    'Die System-Info-Schnittstelle der Zielinstallation verwendet eine nicht unterstützte API-Version.',
                    200,
                    'Endpoint: '.self::ENDPOINT_PATH,
                );
            }
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
                'Endpoint: '.self::ENDPOINT_PATH,
            );
        }

        foreach (['database_name', 'document_root', 'system_info_version'] as $optionalField) {
            if (array_key_exists($optionalField, $data) && !is_string($data[$optionalField])) {
                throw new SystemInfoConnectionException(
                    'response',
                    'invalid_optional_field',
                    'Die System-Info-Antwort enthält ungültige optionale Angaben.',
                    200,
                    'Endpoint: '.self::ENDPOINT_PATH,
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
}
