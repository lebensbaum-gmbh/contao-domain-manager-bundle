<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Connection;

use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Sync\SystemInfoClient;
use RuntimeException;
use Throwable;

final class InstallationConnectionTester
{
    private const TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemInfoClient $systemInfoClient,
    ) {
    }

    public function test(int $installationId): array
    {
        $installation = $this->connection->fetchAssociative(
            'SELECT id, domain, system_id FROM '.self::TABLE.' WHERE id = ?',
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
                throw new RuntimeException('Es ist keine gültige Installations-ID hinterlegt.');
            }

            $systemInfo = $this->systemInfoClient->fetch($domain, $systemId);

            $this->connection->update(
                self::TABLE,
                [
                    'dm_connection_status' => 'success',
                    'dm_connection_message' => sprintf(
                        'Verbindung erfolgreich. Contao %s, PHP %s.',
                        $systemInfo['contao_version'] ?? 'nicht ermittelbar',
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
                'contao_version' => $systemInfo['contao_version'],
                'php_version' => $systemInfo['php_version'],
            ];
        } catch (Throwable $exception) {
            try {
                $this->connection->update(
                    self::TABLE,
                    [
                        'dm_connection_status' => 'error',
                        'dm_connection_message' => $this->normalizeMessage($exception->getMessage()),
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

    private function normalizeMessage(string $message): string
    {
        $message = trim($message);

        return '' === $message
            ? 'Der Verbindungstest ist aus unbekannter Ursache fehlgeschlagen.'
            : substr($message, 0, 2000);
    }
}
