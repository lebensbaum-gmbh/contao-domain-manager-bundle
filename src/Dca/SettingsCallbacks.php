<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Dca;

final class SettingsCallbacks
{
    public function normalizeOptionalTimestamp(mixed $value): mixed
    {
        return (int) $value > 0 ? $value : '';
    }

    public function translateAutoSyncStatus(mixed $value): string
    {
        $status = trim((string) $value);

        if ('' === $status) {
            return '';
        }

        $labels = $GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_statuses'] ?? [];

        return (string) ($labels[$status] ?? $status);
    }

    public function normalizeAutoSyncStatusForStorage(mixed $value): string
    {
        $submitted = trim((string) $value);

        if ('' === $submitted) {
            return '';
        }

        $labels = $GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_statuses'] ?? [];

        foreach ($labels as $status => $label) {
            if ($submitted === (string) $label) {
                return (string) $status;
            }
        }

        return $submitted;
    }
}
