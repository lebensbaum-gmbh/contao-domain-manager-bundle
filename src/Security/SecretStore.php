<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Security;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SecretStore
{
    private const TABLE = 'tl_domain_manager_installation';

    private ?bool $databaseStorageAvailable = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly SecretCipher $secretCipher,
    ) {
    }

    public function getSecret(string $systemId): ?string
    {
        $systemId = trim($systemId);

        if ('' === $systemId) {
            return null;
        }

        if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
            throw new RuntimeException('Die Installations-ID hat ein ungültiges Format.');
        }

        $this->assertStorageAvailable();

        $records = $this->connection->fetchAllAssociative(
            'SELECT id, dm_encrypted_secret FROM '.self::TABLE.' WHERE system_id = ? ORDER BY id LIMIT 2',
            [$systemId]
        );

        if (count($records) > 1) {
            throw new RuntimeException(sprintf(
                'Die Installations-ID "%s" ist mehreren Installationen zugeordnet.',
                $systemId
            ));
        }

        if ([] === $records) {
            return null;
        }

        $encryptedSecret = trim((string) ($records[0]['dm_encrypted_secret'] ?? ''));

        if ('' === $encryptedSecret) {
            return null;
        }

        $secret = $this->secretCipher->decrypt($encryptedSecret);
        $this->assertSecret($secret);

        return $secret;
    }

    public function storeSecretForInstallation(int $installationId, string $secret): void
    {
        if ($installationId < 1) {
            throw new RuntimeException('Die Installations-ID des Datensatzes ist ungültig.');
        }

        $secret = trim($secret);
        $this->assertSecret($secret);
        $this->assertStorageAvailable();
        $timestamp = time();

        $affected = $this->connection->update(
            self::TABLE,
            [
                'dm_encrypted_secret' => $this->secretCipher->encrypt($secret),
                'dm_secret_changed_at' => $timestamp,
                'dm_connection_status' => 'untested',
                'dm_connection_message' => 'Secret wurde geändert. Verbindung noch nicht erneut getestet.',
                'tstamp' => $timestamp,
            ],
            ['id' => $installationId]
        );

        if (0 === $affected) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM '.self::TABLE.' WHERE id = ?',
                [$installationId]
            );

            if (false === $exists) {
                throw new RuntimeException(sprintf(
                    'Die Installation mit der ID %d wurde nicht gefunden.',
                    $installationId
                ));
            }
        }
    }

    public function removeSecretForInstallation(int $installationId): void
    {
        if (!$this->isDatabaseStorageAvailable()) {
            return;
        }

        $timestamp = time();
        $this->connection->update(
            self::TABLE,
            [
                'dm_encrypted_secret' => null,
                'dm_secret_changed_at' => $timestamp,
                'dm_connection_status' => 'not_configured',
                'dm_connection_message' => 'Kein Secret gespeichert.',
                'tstamp' => $timestamp,
            ],
            ['id' => $installationId]
        );
    }

    public function isStoredInDatabase(string $systemId): bool
    {
        $systemId = trim($systemId);

        if (!$this->isDatabaseStorageAvailable() || '' === $systemId) {
            return false;
        }

        $value = $this->connection->fetchOne(
            'SELECT dm_encrypted_secret FROM '.self::TABLE.' WHERE system_id = ? ORDER BY id LIMIT 1',
            [$systemId]
        );

        return false !== $value && '' !== trim((string) $value);
    }

    private function assertStorageAvailable(): void
    {
        if (!$this->isDatabaseStorageAvailable()) {
            throw new RuntimeException(
                'Die neue Domain-Manager-Datenbasis für die verschlüsselte Secret-Speicherung ist noch nicht vorhanden. Bitte zuerst die Datenbank aktualisieren.'
            );
        }
    }

    private function isDatabaseStorageAvailable(): bool
    {
        if (null !== $this->databaseStorageAvailable) {
            return $this->databaseStorageAvailable;
        }

        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::TABLE])) {
                return $this->databaseStorageAvailable = false;
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);

            return $this->databaseStorageAvailable = isset(
                $columns['dm_encrypted_secret'],
                $columns['dm_secret_changed_at']
            );
        } catch (\Throwable) {
            return $this->databaseStorageAvailable = false;
        }
    }

    private function assertSecret(string $secret): void
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/i', $secret)) {
            throw new RuntimeException('Das Secret hat ein ungültiges Format.');
        }
    }
}
