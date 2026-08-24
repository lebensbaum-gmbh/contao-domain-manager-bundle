<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Throwable;

final class MigrateExternalServicesMigration extends AbstractMigration
{
    private const SERVICE_TABLE = 'tl_domain_manager_external_service';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';
    private const SETTINGS_TABLE = 'tl_domain_manager_settings';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Domain-Manager: Trakked-Daten in externe Dienste migrieren';
    }

    public function shouldRun(): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::SERVICE_TABLE])) {
                return true;
            }

            if ($schemaManager->tablesExist([self::INSTALLATION_TABLE])) {
                $columns = array_change_key_case($schemaManager->listTableColumns(self::INSTALLATION_TABLE), CASE_LOWER);

                if (!isset($columns['external_services'])) {
                    return true;
                }
            }

            $legacyUrl = $this->getLegacyTrakkedUrl();
            $legacyAssignments = $this->countLegacyTrakkedAssignments();

            if ('' === $legacyUrl && 0 === $legacyAssignments) {
                return false;
            }

            return false === $this->connection->fetchOne(
                'SELECT id FROM '.self::SERVICE_TABLE.' WHERE name = ? LIMIT 1',
                ['Trakked']
            ) || $legacyAssignments > 0;
        } catch (Throwable) {
            return true;
        }
    }

    public function run(): MigrationResult
    {
        try {
            $this->ensureServiceTable();
            $this->ensureInstallationColumn();

            $legacyUrl = $this->getLegacyTrakkedUrl();
            $legacyAssignments = $this->countLegacyTrakkedAssignments();

            if ('' === $legacyUrl && 0 === $legacyAssignments) {
                return $this->createResult(true, 'Die Struktur für externe Dienste wurde angelegt.');
            }

            $serviceId = $this->getOrCreateTrakkedService($legacyUrl);
            $migrated = $this->migrateAssignments($serviceId);

            return $this->createResult(
                true,
                sprintf(
                    'Externe Dienste wurden eingerichtet. Trakked wurde übernommen; %d Installationszuordnung%s migriert.',
                    $migrated,
                    1 === $migrated ? ' wurde' : 'en wurden'
                )
            );
        } catch (Throwable $exception) {
            return $this->createResult(false, 'Die externen Dienste konnten nicht migriert werden: '.$exception->getMessage());
        }
    }

    private function ensureServiceTable(): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS `tl_domain_manager_external_service` (
                    `id` int(10) unsigned NOT NULL auto_increment,
                    `tstamp` int(10) unsigned NOT NULL default 0,
                    `name` varchar(128) NOT NULL default '',
                    `url` varchar(1024) NOT NULL default '',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    private function ensureInstallationColumn(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::INSTALLATION_TABLE])) {
            return;
        }

        $columns = array_change_key_case($schemaManager->listTableColumns(self::INSTALLATION_TABLE), CASE_LOWER);

        if (!isset($columns['external_services'])) {
            $this->connection->executeStatement(
                'ALTER TABLE '.self::INSTALLATION_TABLE.' ADD `external_services` blob NULL'
            );
        }
    }

    private function getOrCreateTrakkedService(string $legacyUrl): int
    {
        $serviceId = $this->connection->fetchOne(
            'SELECT id FROM '.self::SERVICE_TABLE.' WHERE name = ? LIMIT 1',
            ['Trakked']
        );

        if (false !== $serviceId) {
            $serviceId = (int) $serviceId;
            $currentUrl = trim((string) $this->connection->fetchOne(
                'SELECT url FROM '.self::SERVICE_TABLE.' WHERE id = ?',
                [$serviceId]
            ));

            if ('' === $currentUrl && '' !== $legacyUrl) {
                $this->connection->update(
                    self::SERVICE_TABLE,
                    ['url' => $legacyUrl, 'tstamp' => time()],
                    ['id' => $serviceId]
                );
            }

            return $serviceId;
        }

        $this->connection->insert(self::SERVICE_TABLE, [
            'tstamp' => time(),
            'name' => 'Trakked',
            'url' => $legacyUrl,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function migrateAssignments(int $serviceId): int
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::INSTALLATION_TABLE])) {
            return 0;
        }

        $columns = array_change_key_case($schemaManager->listTableColumns(self::INSTALLATION_TABLE), CASE_LOWER);

        if (!isset($columns['trakked'], $columns['external_services'])) {
            return 0;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, external_services FROM ".self::INSTALLATION_TABLE." WHERE trakked <> ''"
        );
        $migrated = 0;

        foreach ($rows as $row) {
            $installationId = (int) ($row['id'] ?? 0);

            if ($installationId < 1) {
                continue;
            }

            $serviceIds = [];
            foreach (StringUtil::deserialize($row['external_services'] ?? null, true) as $existingId) {
                if (is_numeric($existingId) && (int) $existingId > 0) {
                    $serviceIds[(int) $existingId] = (string) (int) $existingId;
                }
            }
            $serviceIds[$serviceId] = (string) $serviceId;

            $this->connection->update(
                self::INSTALLATION_TABLE,
                [
                    'external_services' => serialize(array_values($serviceIds)),
                    'trakked' => '',
                    'tstamp' => time(),
                ],
                ['id' => $installationId]
            );
            ++$migrated;
        }

        return $migrated;
    }

    private function getLegacyTrakkedUrl(): string
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::SETTINGS_TABLE])) {
                return '';
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::SETTINGS_TABLE), CASE_LOWER);

            if (!isset($columns['trakked_url'])) {
                return '';
            }

            $url = trim((string) $this->connection->fetchOne(
                'SELECT trakked_url FROM '.self::SETTINGS_TABLE.' WHERE id = 1 LIMIT 1'
            ));

            return $url;
        } catch (Throwable) {
            return '';
        }
    }

    private function countLegacyTrakkedAssignments(): int
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::INSTALLATION_TABLE])) {
                return 0;
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::INSTALLATION_TABLE), CASE_LOWER);

            if (!isset($columns['trakked'])) {
                return 0;
            }

            return (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM ".self::INSTALLATION_TABLE." WHERE trakked <> ''"
            );
        } catch (Throwable) {
            return 0;
        }
    }
}
