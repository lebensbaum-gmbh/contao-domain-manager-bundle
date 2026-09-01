<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;
use Lebensbaum\ContaoDomainManagerBundle\Util\SystemValueNormalizer;
use RuntimeException;
use Throwable;

final class InstallationSynchronizer
{
    private const TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemInfoClient $systemInfoClient,
    ) {
    }

    public function synchronize(int $installationId): array
    {
        $installation = $this->connection->fetchAssociative(
            'SELECT id, domain, system_id, document_root, contao_version, CAST(php_version AS CHAR) AS php_version, database_name FROM '.self::TABLE.' WHERE id = ?',
            [$installationId]
        );

        if (false === $installation) {
            throw new RuntimeException(sprintf(
                'Die Installation mit der ID %d wurde in den neuen Domain-Manager-Tabellen nicht gefunden.',
                $installationId
            ));
        }

        $domain = trim((string) $installation['domain']);
        $systemId = trim((string) $installation['system_id']);
        $timestamp = time();

        try {
            if ('' === $domain) {
                throw new SystemInfoConnectionException(
                    'configuration',
                    'domain_missing',
                    'Im Installationsdatensatz fehlt die Domain.'
                );
            }

            if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
                throw new SystemInfoConnectionException(
                    'configuration',
                    'invalid_installation_id',
                    sprintf('Für die Installation „%s“ fehlt eine gültige Installations-ID.', $domain)
                );
            }

            $systemInfo = $this->systemInfoClient->fetch($domain, $systemId);

            $contaoVersion = SystemValueNormalizer::contaoVersion($systemInfo['contao_version']);
            $phpVersion = SystemValueNormalizer::phpVersion($systemInfo['php_version']);
            $documentRoot = trim((string) ($systemInfo['document_root'] ?? ''));
            $databaseName = trim((string) ($systemInfo['database_name'] ?? ''));
            $oldContaoVersion = trim((string) $installation['contao_version']);
            $oldPhpVersion = trim((string) $installation['php_version']);

            $updateData = [
                'contao_version' => $contaoVersion,
                'php_version' => $phpVersion,
                'last_sync' => $timestamp,
                'sync_status' => 'success',
                'sync_message' => 'Systeminformationen erfolgreich aktualisiert.',
                'dm_connection_status' => 'success',
                'dm_connection_stage' => 'system_data',
                'dm_connection_error_code' => '',
                'dm_connection_http_status' => 200,
                'dm_connection_message' => sprintf(
                    'Verbindung erfolgreich. Contao %s, PHP %s.',
                    $contaoVersion,
                    $systemInfo['php_version']
                ),
                'dm_last_connection_test' => $timestamp,
                'dm_last_connection_success' => $timestamp,
                'tstamp' => $timestamp,
            ];

            if ('' !== $documentRoot) {
                $updateData['document_root'] = $documentRoot;
            }

            if ('' !== $databaseName) {
                $updateData['database_name'] = $databaseName;
            }

            $this->connection->update(
                self::TABLE,
                $updateData,
                ['id' => $installationId]
            );

            return [
                'id' => $installationId,
                'domain' => $domain,
                'old_contao_version' => $oldContaoVersion,
                'new_contao_version' => $contaoVersion,
                'old_php_version' => $oldPhpVersion,
                'new_php_version' => $phpVersion,
            ];
        } catch (SystemInfoConnectionException $exception) {
            $this->storeFailure(
                $installationId,
                $timestamp,
                $exception->getStage(),
                $exception->getErrorCode(),
                $exception->getHttpStatus(),
                $exception->getMessage(),
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->storeFailure(
                $installationId,
                $timestamp,
                'unknown',
                'unexpected_error',
                null,
                $exception->getMessage(),
            );

            throw $exception;
        }
    }

    private function storeFailure(
        int $installationId,
        int $timestamp,
        string $stage,
        string $errorCode,
        ?int $httpStatus,
        string $message,
    ): void {
        try {
            $message = $this->normalizeErrorMessage($message);

            $this->connection->update(
                self::TABLE,
                [
                    'sync_status' => 'error',
                    'sync_message' => $message,
                    'dm_connection_status' => 'error',
                    'dm_connection_stage' => substr(trim($stage), 0, 32),
                    'dm_connection_error_code' => substr(trim($errorCode), 0, 64),
                    'dm_connection_http_status' => $httpStatus ?? 0,
                    'dm_connection_message' => $message,
                    'dm_last_connection_test' => $timestamp,
                    'tstamp' => $timestamp,
                ],
                ['id' => $installationId]
            );
        } catch (Throwable) {
        }
    }

    private function normalizeErrorMessage(string $message): string
    {
        $message = trim($message);

        if ('' === $message) {
            return 'Die Synchronisation ist aus unbekannter Ursache fehlgeschlagen.';
        }

        return substr($message, 0, 2000);
    }
}
