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
