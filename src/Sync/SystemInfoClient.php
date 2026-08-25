<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use RuntimeException;

final class SystemInfoClient
{
    private const ENDPOINT_PATH = '/_domainverwaltung/systeminfo';

    public function __construct(private readonly SystemInfoApiClient $apiClient)
    {
    }

    public function fetch(string $baseUrl, string $systemId): array
    {
        $data = $this->apiClient->get($baseUrl, $systemId, self::ENDPOINT_PATH);

        if (
            !array_key_exists('contao_version', $data)
            || !isset($data['php_version'], $data['app_environment'], $data['generated_at'])
            || (null !== $data['contao_version'] && !is_string($data['contao_version']))
            || !is_string($data['php_version'])
            || !is_string($data['app_environment'])
            || !is_string($data['generated_at'])
        ) {
            throw new RuntimeException('In der Systeminfo-Antwort fehlen erforderliche Angaben.');
        }

        foreach (['database_name', 'document_root'] as $optionalField) {
            if (
                array_key_exists($optionalField, $data)
                && !is_string($data[$optionalField])
            ) {
                throw new RuntimeException('Die Systeminfo-Antwort enthält ungültige optionale Angaben.');
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
