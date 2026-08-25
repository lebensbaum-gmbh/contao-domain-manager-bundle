<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Event\InstallationSynchronizedEvent;
use Lebensbaum\ContaoDomainManagerBundle\Util\SystemValueNormalizer;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class InstallationSynchronizer
{
    private const TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemInfoClient $systemInfoClient,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function synchronize(int $installationId): array
    {
        $installation = $this->connection->fetchAssociative(
            'SELECT id, domain, system_id, document_root, contao_version, CAST(php_version AS CHAR) AS php_version, CAST(php_version_full AS CHAR) AS php_version_full, database_name FROM '.self::TABLE.' WHERE id = ?',
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
                throw new RuntimeException('Im Installationsdatensatz fehlt die Domain.');
            }

            if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
                throw new RuntimeException(sprintf(
                    'Für die Installation „%s“ fehlt eine gültige Installations-ID.',
                    $domain
                ));
            }

            $systemInfo = $this->systemInfoClient->fetch($domain, $systemId);

            $contaoVersion = SystemValueNormalizer::contaoVersion($systemInfo['contao_version']);
            $phpVersion = SystemValueNormalizer::phpVersion($systemInfo['php_version']);
            $phpVersionFull = SystemValueNormalizer::phpVersionFull($systemInfo['php_version']);
            $documentRoot = trim((string) ($systemInfo['document_root'] ?? ''));
            $databaseName = trim((string) ($systemInfo['database_name'] ?? ''));
            $oldContaoVersion = trim((string) $installation['contao_version']);
            $oldPhpVersionFull = trim((string) ($installation['php_version_full'] ?? ''));

            $updateData = [
                'contao_version' => $contaoVersion,
                'php_version' => $phpVersion,
                'php_version_full' => $phpVersionFull,
                'last_sync' => $timestamp,
                'sync_status' => 'success',
                'sync_message' => 'Systeminformationen erfolgreich aktualisiert.',
                'dm_connection_status' => 'success',
                'dm_connection_message' => sprintf(
                    'Verbindung erfolgreich. Contao %s, PHP %s.',
                    $contaoVersion,
                    $phpVersionFull
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

            $this->eventDispatcher->dispatch(new InstallationSynchronizedEvent(
                $installationId,
                $domain,
                $oldContaoVersion,
                $contaoVersion,
                $oldPhpVersionFull,
                $phpVersionFull,
                $timestamp,
            ));

            return [
                'id' => $installationId,
                'domain' => $domain,
                'old_contao_version' => $oldContaoVersion,
                'new_contao_version' => $contaoVersion,
                'old_php_version' => $oldPhpVersionFull,
                'new_php_version' => $phpVersionFull,
            ];
        } catch (Throwable $exception) {
            try {
                $message = $this->normalizeErrorMessage($exception->getMessage());

                $this->connection->update(
                    self::TABLE,
                    [
                        'sync_status' => 'error',
                        'sync_message' => $message,
                        'dm_connection_status' => 'error',
                        'dm_connection_message' => $message,
                        'dm_last_connection_test' => $timestamp,
                        'tstamp' => $timestamp,
                    ],
                    ['id' => $installationId]
                );
            } catch (Throwable) {
            }

            throw $exception;
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
