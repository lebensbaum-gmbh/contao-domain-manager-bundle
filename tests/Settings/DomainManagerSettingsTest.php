<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Settings;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use PHPUnit\Framework\TestCase;

final class DomainManagerSettingsTest extends TestCase
{
    private Connection $connection;
    private DomainManagerSettings $settings;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            <<<'SQL'
                CREATE TABLE tl_domain_manager_settings (
                    id INTEGER PRIMARY KEY,
                    tstamp INTEGER NOT NULL DEFAULT 0,
                    sync_member_groups BLOB NULL,
                    stale_sync_days INTEGER NOT NULL DEFAULT 30,
                    auto_sync_enabled VARCHAR(1) NOT NULL DEFAULT '',
                    auto_sync_interval INTEGER NOT NULL DEFAULT 6,
                    auto_sync_last_attempt INTEGER NOT NULL DEFAULT 0,
                    auto_sync_last_success INTEGER NOT NULL DEFAULT 0,
                    auto_sync_status VARCHAR(32) NOT NULL DEFAULT '',
                    auto_sync_message TEXT NULL
                )
            SQL
        );

        $this->connection->insert('tl_domain_manager_settings', [
            'id' => 1,
            'tstamp' => 0,
            'sync_member_groups' => null,
            'stale_sync_days' => 30,
            'auto_sync_enabled' => '',
            'auto_sync_interval' => 6,
            'auto_sync_last_attempt' => 0,
            'auto_sync_last_success' => 0,
            'auto_sync_status' => '',
        ]);

        $this->settings = new DomainManagerSettings($this->connection);
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testSyncMemberGroupsAreEmptyByDefault(): void
    {
        self::assertSame([], $this->settings->getSyncMemberGroupIds());
    }

    public function testSyncMemberGroupsAreNormalizedAndDeduplicated(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['sync_member_groups' => serialize(['2', '5', '2', 'invalid', 0])],
            ['id' => 1]
        );

        self::assertSame([2, 5], $this->settings->getSyncMemberGroupIds());
    }

    public function testStaleSyncDaysDefaultsToThirty(): void
    {
        self::assertSame(30, $this->settings->getStaleSyncDays());
    }

    public function testConfiguredStaleSyncDaysAreReturned(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['stale_sync_days' => 45],
            ['id' => 1]
        );

        self::assertSame(45, $this->settings->getStaleSyncDays());
    }

    public function testInvalidStaleSyncDaysFallBackToThirty(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['stale_sync_days' => 0],
            ['id' => 1]
        );

        self::assertSame(30, $this->settings->getStaleSyncDays());

        $this->connection->update(
            'tl_domain_manager_settings',
            ['stale_sync_days' => 4000],
            ['id' => 1]
        );

        self::assertSame(30, $this->settings->getStaleSyncDays());
    }

    public function testFreeNeverEnablesAutomaticSynchronization(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['auto_sync_enabled' => '1'],
            ['id' => 1]
        );

        self::assertFalse($this->settings->isAutoSyncEnabled());
    }
}
