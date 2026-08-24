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

    /** @var list<string> */
    private const REQUIRED_COLUMNS = [
        'sync_member_groups',
        'stale_sync_days',
        'trakked_url',
        'auto_sync_enabled',
        'auto_sync_interval',
        'auto_sync_last_attempt',
        'auto_sync_last_success',
        'auto_sync_status',
        'auto_sync_message',
    ];

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

            foreach (self::REQUIRED_COLUMNS as $column) {
                if (!isset($columns[$column])) {
                    return true;
                }
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
                    'stale_sync_days' => 30,
                    'trakked_url' => '',
                    'auto_sync_enabled' => '',
                    'auto_sync_interval' => 6,
                    'auto_sync_last_attempt' => 0,
                    'auto_sync_last_success' => 0,
                    'auto_sync_status' => '',
                    'auto_sync_message' => null,
                ]);
            }

            return $this->createResult(
                true,
                'Die globalen Einstellungen wurden angelegt bzw. auf den aktuellen Stand gebracht.'
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
                    `stale_sync_days` int(10) unsigned NOT NULL default 30,
                    `trakked_url` varchar(1024) NOT NULL default '',
                    `auto_sync_enabled` char(1) NOT NULL default '',
                    `auto_sync_interval` int(10) unsigned NOT NULL default 6,
                    `auto_sync_last_attempt` int(10) unsigned NOT NULL default 0,
                    `auto_sync_last_success` int(10) unsigned NOT NULL default 0,
                    `auto_sync_status` varchar(32) NOT NULL default '',
                    `auto_sync_message` text NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB
            SQL
        );

        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);

        $definitions = [
            'sync_member_groups' => 'blob NULL',
            'stale_sync_days' => 'int(10) unsigned NOT NULL default 30',
            'trakked_url' => "varchar(1024) NOT NULL default ''",
            'auto_sync_enabled' => "char(1) NOT NULL default ''",
            'auto_sync_interval' => 'int(10) unsigned NOT NULL default 6',
            'auto_sync_last_attempt' => 'int(10) unsigned NOT NULL default 0',
            'auto_sync_last_success' => 'int(10) unsigned NOT NULL default 0',
            'auto_sync_status' => "varchar(32) NOT NULL default ''",
            'auto_sync_message' => 'text NULL',
        ];

        foreach ($definitions as $column => $definition) {
            if (!isset($columns[$column])) {
                $this->connection->executeStatement(
                    sprintf('ALTER TABLE %s ADD `%s` %s', self::TABLE, $column, $definition)
                );
            }
        }
    }
}
