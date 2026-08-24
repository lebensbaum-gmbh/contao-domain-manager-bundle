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

    public function testAutomaticSynchronizationIsDisabledByDefault(): void
    {
        self::assertFalse($this->settings->isAutoSyncEnabled());
        self::assertFalse($this->settings->isAutoSyncDue(1_000_000));
        self::assertSame(6, $this->settings->getAutoSyncIntervalHours());
    }

    public function testFirstAutomaticSynchronizationIsImmediatelyDueAfterEnabling(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['auto_sync_enabled' => '1'],
            ['id' => 1]
        );

        self::assertTrue($this->settings->isAutoSyncEnabled());
        self::assertTrue($this->settings->isAutoSyncDue(1_000_000));
    }

    public function testConfiguredIntervalControlsWhenNextRunIsDue(): void
    {
        $lastAttempt = 1_000_000;

        $this->connection->update(
            'tl_domain_manager_settings',
            [
                'auto_sync_enabled' => '1',
                'auto_sync_interval' => 6,
                'auto_sync_last_attempt' => $lastAttempt,
            ],
            ['id' => 1]
        );

        self::assertFalse($this->settings->isAutoSyncDue($lastAttempt + (6 * 3600) - 1));
        self::assertTrue($this->settings->isAutoSyncDue($lastAttempt + (6 * 3600)));
    }

    public function testSuccessfulRunStoresAttemptSuccessStatusAndMessage(): void
    {
        $timestamp = 1_000_000;

        $this->settings->markAutoSyncAttempt($timestamp);

        self::assertSame($timestamp, $this->settings->getAutoSyncLastAttempt());
        self::assertSame('running', $this->settings->getAutoSyncStatus());

        $this->settings->storeAutoSyncResult('success', 'Alles aktuell.', $timestamp);

        self::assertSame($timestamp, $this->settings->getAutoSyncLastAttempt());
        self::assertSame($timestamp, $this->settings->getAutoSyncLastSuccess());
        self::assertSame('success', $this->settings->getAutoSyncStatus());
        self::assertSame('Alles aktuell.', $this->settings->getAutoSyncMessage());
    }

    public function testFailedRunDoesNotOverwriteLastSuccessfulRun(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['auto_sync_last_success' => 900_000],
            ['id' => 1]
        );

        $this->settings->storeAutoSyncResult('error', 'Fehlgeschlagen.', 1_000_000);

        self::assertSame(900_000, $this->settings->getAutoSyncLastSuccess());
        self::assertSame(1_000_000, $this->settings->getAutoSyncLastAttempt());
        self::assertSame('error', $this->settings->getAutoSyncStatus());
    }

    public function testInvalidIntervalFallsBackToSixHours(): void
    {
        $this->connection->update(
            'tl_domain_manager_settings',
            ['auto_sync_interval' => 5],
            ['id' => 1]
        );

        self::assertSame(6, $this->settings->getAutoSyncIntervalHours());
    }
}
