<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Settings;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Throwable;

final class DomainManagerSettings
{
    private const TABLE = 'tl_domain_manager_settings';
    private const DEFAULT_STALE_SYNC_DAYS = 30;
    private const DEFAULT_AUTO_SYNC_INTERVAL_HOURS = 6;
    private const AUTO_SYNC_INTERVALS = [1, 6, 12, 24];

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return list<int> */
    public function getSyncMemberGroupIds(): array
    {
        $value = $this->getValue('sync_member_groups');

        if (null === $value || '' === trim((string) $value)) {
            return [];
        }

        $ids = [];

        foreach (StringUtil::deserialize($value, true) as $groupId) {
            if (!is_numeric($groupId)) {
                continue;
            }

            $groupId = (int) $groupId;

            if ($groupId > 0) {
                $ids[$groupId] = $groupId;
            }
        }

        return array_values($ids);
    }

    public function getStaleSyncDays(): int
    {
        $value = $this->getValue('stale_sync_days');

        if (!is_numeric($value)) {
            return self::DEFAULT_STALE_SYNC_DAYS;
        }

        $days = (int) $value;

        if ($days < 1 || $days > 3650) {
            return self::DEFAULT_STALE_SYNC_DAYS;
        }

        return $days;
    }

    public function isAutoSyncEnabled(): bool
    {
        $value = strtolower(trim((string) ($this->getValue('auto_sync_enabled') ?? '')));

        return in_array($value, ['1', 'true', 'yes', 'ja', 'on'], true);
    }

    public function getAutoSyncIntervalHours(): int
    {
        $value = $this->getValue('auto_sync_interval');
        $hours = is_numeric($value) ? (int) $value : self::DEFAULT_AUTO_SYNC_INTERVAL_HOURS;

        return in_array($hours, self::AUTO_SYNC_INTERVALS, true)
            ? $hours
            : self::DEFAULT_AUTO_SYNC_INTERVAL_HOURS;
    }

    public function getAutoSyncLastAttempt(): int
    {
        return $this->getPositiveInt('auto_sync_last_attempt');
    }

    public function getAutoSyncLastSuccess(): int
    {
        return $this->getPositiveInt('auto_sync_last_success');
    }

    public function getAutoSyncStatus(): string
    {
        return trim((string) ($this->getValue('auto_sync_status') ?? ''));
    }

    public function getAutoSyncMessage(): string
    {
        return trim((string) ($this->getValue('auto_sync_message') ?? ''));
    }

    public function isAutoSyncDue(?int $now = null): bool
    {
        if (!$this->isAutoSyncEnabled()) {
            return false;
        }

        $lastAttempt = $this->getAutoSyncLastAttempt();

        if ($lastAttempt < 1) {
            return true;
        }

        $now ??= time();

        return ($now - $lastAttempt) >= ($this->getAutoSyncIntervalHours() * 3600);
    }

    public function markAutoSyncAttempt(int $timestamp): void
    {
        $this->setValues([
            'tstamp' => time(),
            'auto_sync_last_attempt' => max(0, $timestamp),
            'auto_sync_status' => 'running',
            'auto_sync_message' => '',
        ]);
    }

    public function storeAutoSyncResult(string $status, string $message, int $attemptTimestamp): void
    {
        $values = [
            'tstamp' => time(),
            'auto_sync_last_attempt' => max(0, $attemptTimestamp),
            'auto_sync_status' => trim($status),
            'auto_sync_message' => trim($message),
        ];

        if ('success' === $status) {
            $values['auto_sync_last_success'] = max(0, $attemptTimestamp);
        }

        $this->setValues($values);
    }

    private function getPositiveInt(string $field): int
    {
        $value = $this->getValue($field);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    /** @param array<string, int|string|null> $values */
    private function setValues(array $values): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::TABLE])) {
                return;
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);
            $filtered = [];

            foreach ($values as $field => $value) {
                if (isset($columns[strtolower($field)])) {
                    $filtered[$field] = $value;
                }
            }

            if ([] === $filtered) {
                return;
            }

            $exists = $this->connection->fetchOne(
                'SELECT id FROM '.self::TABLE.' WHERE id = 1 LIMIT 1'
            );

            if (false === $exists) {
                return;
            }

            $this->connection->update(self::TABLE, $filtered, ['id' => 1]);
        } catch (Throwable) {
            // Settings access deliberately fails closed; synchronization itself must not crash here.
        }
    }

    private function getValue(string $field): mixed
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist([self::TABLE])) {
                return null;
            }

            $columns = array_change_key_case($schemaManager->listTableColumns(self::TABLE), CASE_LOWER);

            if (!isset($columns[strtolower($field)])) {
                return null;
            }

            $value = $this->connection->fetchOne(
                sprintf('SELECT `%s` FROM `%s` WHERE id = 1 LIMIT 1', $field, self::TABLE)
            );

            return false === $value ? null : $value;
        } catch (Throwable) {
            return null;
        }
    }
}
