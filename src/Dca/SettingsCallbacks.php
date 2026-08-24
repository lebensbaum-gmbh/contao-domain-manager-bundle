<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Dca;

use Contao\DataContainer;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;

final class SettingsCallbacks
{
    private const CRON_GRACE_SECONDS = 900;

    public function __construct(
        private readonly DomainManagerSettings $settings,
        private readonly string $projectDir,
    ) {
    }

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

    public function renderCronPanel(DataContainer $dc): string
    {
        $enabled = $this->settings->isAutoSyncEnabled();
        $intervalHours = $this->settings->getAutoSyncIntervalHours();
        $lastAttempt = $this->settings->getAutoSyncLastAttempt();
        $now = time();

        $panel = $GLOBALS['TL_LANG']['tl_domain_manager_settings']['cron_panel'] ?? [];
        $title = (string) ($panel['title'] ?? 'Server-Cronjob');
        $description = (string) ($panel['description'] ?? 'Für die zuverlässige automatische Synchronisation ist ein regelmäßig ausgeführter CLI-Cronjob erforderlich.');
        $commandLabel = (string) ($panel['command'] ?? 'Empfohlener Befehl');
        $schedule = (string) ($panel['schedule'] ?? 'Den Server-Cronjob möglichst jede Minute ausführen. Contao entscheidet selbst, welche Aufgaben tatsächlich fällig sind.');
        $hosterHint = (string) ($panel['hoster_hint'] ?? 'Den PHP-Pfad bzw. die PHP-Version legt der jeweilige Hoster fest.');

        if (!$enabled) {
            $statusClass = 'info';
            $statusText = (string) ($panel['disabled'] ?? 'Automatische Synchronisation ist deaktiviert.');
        } elseif ($lastAttempt < 1) {
            $statusClass = 'warning';
            $statusText = (string) ($panel['not_confirmed'] ?? 'Noch kein automatischer CLI-Lauf bestätigt. Bitte den Server-Cronjob einrichten und testen.');
        } else {
            $deadline = $lastAttempt + ($intervalHours * 3600) + self::CRON_GRACE_SECONDS;

            if ($now <= $deadline) {
                $statusClass = 'success';
                $statusText = sprintf(
                    (string) ($panel['confirmed'] ?? 'Automatische CLI-Ausführung zuletzt bestätigt: %s.'),
                    $this->formatTimestamp($lastAttempt)
                );
            } else {
                $statusClass = 'warning';
                $statusText = sprintf(
                    (string) ($panel['overdue'] ?? 'Kein fälliger automatischer Lauf seit %s erkannt. Bitte den Server-Cronjob prüfen.'),
                    $this->formatTimestamp($lastAttempt)
                );
            }
        }

        $command = 'php '.$this->projectDir.'/vendor/bin/contao-console contao:cron';
        $statusStyle = 'success' === $statusClass
            ? 'border-color:#8fc7a8;background:#edf8f1;color:#176b3a;'
            : ('warning' === $statusClass
                ? 'border-color:#e5c878;background:#fff8e5;color:#765500;'
                : 'border-color:#b8c4cc;background:#f4f7f9;color:#41515c;');

        return sprintf(
            '<div style="margin:8px 0 18px;padding:14px 16px;border:1px solid #cfd8de;border-radius:6px;background:#fff">'
            .'<strong style="display:block;margin-bottom:7px">%s</strong>'
            .'<p style="margin:0 0 10px">%s</p>'
            .'<div style="margin:0 0 12px;padding:9px 11px;border:1px solid;border-radius:5px;%s"><strong>%s</strong></div>'
            .'<div style="margin:0 0 7px"><strong>%s</strong></div>'
            .'<code style="display:block;overflow:auto;padding:9px 11px;border:1px solid #d8dfe3;border-radius:4px;background:#f7f9fa;white-space:nowrap">%s</code>'
            .'<p style="margin:9px 0 0">%s<br><span class="tl_help">%s</span></p>'
            .'</div>',
            $this->escape($title),
            $this->escape($description),
            $statusStyle,
            $this->escape($statusText),
            $this->escape($commandLabel),
            $this->escape($command),
            $this->escape($schedule),
            $this->escape($hosterHint),
        );
    }

    private function formatTimestamp(int $timestamp): string
    {
        if ($timestamp < 1) {
            return '–';
        }

        return (new \DateTimeImmutable('@'.$timestamp))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format('d.m.Y, H.i \\U\\h\\r');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
