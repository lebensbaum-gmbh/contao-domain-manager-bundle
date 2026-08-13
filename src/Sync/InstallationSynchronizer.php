<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
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
            'SELECT id, domain, system_id, contao_version, CAST(php_version AS CHAR) AS php_version FROM '.self::TABLE.' WHERE id = ?',
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

            $contaoVersion = $this->normalizeContaoVersion($systemInfo['contao_version']);
            $phpVersion = $this->normalizePhpVersion($systemInfo['php_version']);
            $oldContaoVersion = trim((string) $installation['contao_version']);
            $oldPhpVersion = trim((string) $installation['php_version']);

            $this->connection->update(
                self::TABLE,
                [
                    'contao_version' => $contaoVersion,
                    'php_version' => $phpVersion,
                    'last_sync' => $timestamp,
                    'sync_status' => 'success',
                    'sync_message' => 'Systeminformationen erfolgreich aktualisiert.',
                    'dm_connection_status' => 'success',
                    'dm_connection_message' => sprintf(
                        'Verbindung erfolgreich. Contao %s, PHP %s.',
                        $contaoVersion,
                        $systemInfo['php_version']
                    ),
                    'dm_last_connection_test' => $timestamp,
                    'dm_last_connection_success' => $timestamp,
                    'tstamp' => $timestamp,
                ],
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

    private function normalizeContaoVersion(?string $version): string
    {
        $version = null !== $version ? trim($version) : '';

        if ('' === $version) {
            throw new RuntimeException('Die Contao-Version konnte nicht ermittelt werden.');
        }

        return preg_replace('/\Av(?=\d)/i', '', $version) ?? $version;
    }

    private function normalizePhpVersion(string $version): string
    {
        $version = trim($version);

        if (1 !== preg_match('/\A(\d+)\.(\d+)/', $version, $matches)) {
            throw new RuntimeException(sprintf(
                'Die PHP-Version „%s“ konnte nicht verarbeitet werden.',
                $version
            ));
        }

        return $matches[1].'.'.$matches[2];
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
