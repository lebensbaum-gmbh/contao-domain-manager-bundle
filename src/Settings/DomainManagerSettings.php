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

    /**
     * Free never enables automatic synchronization. The legacy getters below are
     * temporarily retained so v1.5 data stays readable while the Pro extension is
     * split out without forcing a destructive migration.
     */
    public function isAutoSyncEnabled(): bool
    {
        return false;
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

    private function getPositiveInt(string $field): int
    {
        $value = $this->getValue($field);

        return is_numeric($value) ? max(0, (int) $value) : 0;
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
