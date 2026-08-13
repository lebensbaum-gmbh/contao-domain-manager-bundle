<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Throwable;

final class InitializeSettingsMigration extends AbstractMigration
{
    private const TABLE = 'tl_domain_manager_settings';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Domain-Manager: globale Einstellungen initialisieren';
    }

    public function shouldRun(): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::TABLE])) {
                return true;
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);

            if (!isset($columns['sync_member_groups'], $columns['trakked_url'])) {
                return true;
            }

            return false === $this->connection->fetchOne(
                'SELECT id FROM '.self::TABLE.' WHERE id = 1 LIMIT 1'
            );
        } catch (Throwable) {
            return true;
        }
    }

    public function run(): MigrationResult
    {
        try {
            $this->ensureTable();

            $exists = $this->connection->fetchOne(
                'SELECT id FROM '.self::TABLE.' WHERE id = 1 LIMIT 1'
            );

            if (false === $exists) {
                $this->connection->insert(self::TABLE, [
                    'id' => 1,
                    'tstamp' => time(),
                    'sync_member_groups' => null,
                    'trakked_url' => '',
                ]);
            }

            return $this->createResult(
                true,
                'Die globalen Einstellungen wurden angelegt. Bitte die berechtigten Frontend-Mitgliedergruppen und optionale Links im Backend festlegen.'
            );
        } catch (Throwable $exception) {
            return $this->createResult(false, 'Die Domain-Manager-Einstellungen konnten nicht initialisiert werden: '.$exception->getMessage());
        }
    }

    private function ensureTable(): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS `tl_domain_manager_settings` (
                    `id` int(10) unsigned NOT NULL auto_increment,
                    `tstamp` int(10) unsigned NOT NULL default 0,
                    `sync_member_groups` blob NULL,
                    `trakked_url` varchar(1024) NOT NULL default '',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB
            SQL
        );

        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);

        if (!isset($columns['sync_member_groups'])) {
            $this->connection->executeStatement('ALTER TABLE '.self::TABLE.' ADD `sync_member_groups` blob NULL');
        }

        if (!isset($columns['trakked_url'])) {
            $this->connection->executeStatement("ALTER TABLE ".self::TABLE." ADD `trakked_url` varchar(1024) NOT NULL default ''");
        }
    }
}
