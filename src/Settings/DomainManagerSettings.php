<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Settings;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Throwable;

final class DomainManagerSettings
{
    private const TABLE = 'tl_domain_manager_settings';

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

    public function getTrakkedUrl(): string
    {
        $url = trim((string) ($this->getValue('trakked_url') ?? ''));

        if (
            '' === $url
            || false === filter_var($url, FILTER_VALIDATE_URL)
            || 1 !== preg_match('~^https?://~i', $url)
        ) {
            return '';
        }

        return $url;
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
