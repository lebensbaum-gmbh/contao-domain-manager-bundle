<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Health;

final class InstallationHealthEvaluator
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ERROR = 'error';

    /** @var array<string, array{active_until:string, security_until:string}> */
    private const CONTAO_SUPPORT = [
        '5.3' => [
            'active_until' => '2027-02-14 23:59:59 UTC',
            'security_until' => '2028-02-14 23:59:59 UTC',
        ],
        '5.7' => [
            'active_until' => '2029-02-14 23:59:59 UTC',
            'security_until' => '2030-02-14 23:59:59 UTC',
        ],
        '6.0' => [
            'active_until' => '2027-02-14 23:59:59 UTC',
            'security_until' => '2027-02-14 23:59:59 UTC',
        ],
    ];

    /** @var array<string, array{active_until:string, security_until:string}> */
    private const PHP_SUPPORT = [
        '8.2' => [
            'active_until' => '2024-12-31 23:59:59 UTC',
            'security_until' => '2026-12-31 23:59:59 UTC',
        ],
        '8.3' => [
            'active_until' => '2025-12-31 23:59:59 UTC',
            'security_until' => '2027-12-31 23:59:59 UTC',
        ],
        '8.4' => [
            'active_until' => '2026-12-31 23:59:59 UTC',
            'security_until' => '2028-12-31 23:59:59 UTC',
        ],
        '8.5' => [
            'active_until' => '2027-12-31 23:59:59 UTC',
            'security_until' => '2029-12-31 23:59:59 UTC',
        ],
    ];

    /**
     * @param array<string, mixed> $installation
     * @return array{status:string,label:string,messages:list<string>,issue_count:int}
     */
    public function evaluate(array $installation, int $staleSyncDays, ?int $now = null): array
    {
        $status = self::STATUS_OK;
        $messages = [];
        $now ??= time();
        $staleSyncDays = $staleSyncDays > 0 ? $staleSyncDays : 30;

        $syncStatus = strtolower(trim((string) ($installation['sync_status'] ?? '')));
        $connectionStatus = strtolower(trim((string) ($installation['connection_status'] ?? '')));
        $systemId = trim((string) ($installation['system_id'] ?? ''));
        $lastSync = (int) ($installation['last_sync'] ?? 0);
        $webroot = strtolower(str_replace('\\', '/', trim((string) ($installation['document_root'] ?? ''))));
        $contaoVersion = trim((string) ($installation['contao_version'] ?? ''));
        $phpVersion = trim((string) ($installation['php_version'] ?? ''));

        if ('error' === $syncStatus) {
            $this->addIssue($status, $messages, self::STATUS_ERROR, 'Die letzte Synchronisation ist fehlgeschlagen.');
        }

        if ('error' === $connectionStatus) {
            $this->addIssue($status, $messages, self::STATUS_ERROR, 'Der letzte Verbindungstest ist fehlgeschlagen.');
        } elseif ('untested' === $connectionStatus) {
            $this->addIssue($status, $messages, self::STATUS_WARNING, 'Die Verbindung wurde noch nicht getestet.');
        } elseif ('not_configured' === $connectionStatus) {
            $this->addIssue($status, $messages, self::STATUS_WARNING, 'Die Verbindung ist noch nicht vollständig konfiguriert.');
        }

        if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
            $this->addIssue($status, $messages, self::STATUS_WARNING, 'Es ist keine gültige System-Info-Installations-ID hinterlegt.');
        }

        if ($lastSync < 1) {
            $this->addIssue($status, $messages, self::STATUS_WARNING, 'Es wurde noch keine erfolgreiche Synchronisation durchgeführt.');
        } elseif (($now - $lastSync) > ($staleSyncDays * 86400)) {
            $this->addIssue(
                $status,
                $messages,
                self::STATUS_WARNING,
                sprintf('Die letzte erfolgreiche Synchronisation ist älter als %d Tage.', $staleSyncDays)
            );
        }

        if ('/web' === rtrim($webroot, '/')) {
            $this->addIssue($status, $messages, self::STATUS_WARNING, 'Der Webroot verwendet noch das veraltete Verzeichnis /web.');
        }

        $this->evaluateContaoVersion($contaoVersion, $now, $status, $messages);
        $this->evaluatePhpVersion($phpVersion, $now, $status, $messages);

        return [
            'status' => $status,
            'label' => $this->label($status),
            'messages' => $messages,
            'issue_count' => count($messages),
        ];
    }

    /**
     * @param list<array{status:string,label?:string,messages?:list<string>,issue_count?:int}> $healthStates
     * @return array{status:string,label:string,messages:list<string>,issue_count:int}
     */
    public function summarize(array $healthStates, bool $hasCurrentTarget): array
    {
        $status = self::STATUS_OK;
        $messages = [];
        $issueCount = 0;

        foreach ($healthStates as $health) {
            $healthStatus = (string) ($health['status'] ?? self::STATUS_OK);
            $issueCount += max(0, (int) ($health['issue_count'] ?? 0));
            $status = $this->worstStatus($status, $healthStatus);
        }

        if (!$hasCurrentTarget) {
            $status = $this->worstStatus($status, self::STATUS_WARNING);
            $messages[] = 'Für diese Hauptdomain ist kein aktuelles Ziel festgelegt.';
            ++$issueCount;
        }

        return [
            'status' => $status,
            'label' => $this->label($status),
            'messages' => $messages,
            'issue_count' => $issueCount,
        ];
    }

    /** @param list<string> $messages */
    private function evaluateContaoVersion(string $version, int $now, string &$status, array &$messages): void
    {
        $branch = $this->versionBranch($version);

        if (null === $branch) {
            return;
        }

        if (isset(self::CONTAO_SUPPORT[$branch])) {
            $support = self::CONTAO_SUPPORT[$branch];
            $activeUntil = strtotime($support['active_until']);
            $securityUntil = strtotime($support['security_until']);

            if (false !== $securityUntil && $now > $securityUntil) {
                $this->addIssue(
                    $status,
                    $messages,
                    self::STATUS_ERROR,
                    sprintf('Contao %s wird nicht mehr unterstützt.', $branch)
                );
            } elseif (false !== $activeUntil && $now > $activeUntil) {
                $this->addIssue(
                    $status,
                    $messages,
                    self::STATUS_WARNING,
                    sprintf(
                        'Contao %s erhält nur noch Sicherheitsupdates (bis %s).',
                        $branch,
                        date('d.m.Y', $securityUntil)
                    )
                );
            }

            return;
        }

        [$major, $minor] = array_map('intval', explode('.', $branch, 2));

        if ($major < 5 || (5 === $major && $minor < 7 && 3 !== $minor)) {
            $this->addIssue(
                $status,
                $messages,
                self::STATUS_ERROR,
                sprintf('Contao %s wird nicht mehr unterstützt.', $branch)
            );
        }
    }

    /** @param list<string> $messages */
    private function evaluatePhpVersion(string $version, int $now, string &$status, array &$messages): void
    {
        $branch = $this->versionBranch($version);

        if (null === $branch) {
            return;
        }

        if (isset(self::PHP_SUPPORT[$branch])) {
            $support = self::PHP_SUPPORT[$branch];
            $activeUntil = strtotime($support['active_until']);
            $securityUntil = strtotime($support['security_until']);

            if (false !== $securityUntil && $now > $securityUntil) {
                $this->addIssue(
                    $status,
                    $messages,
                    self::STATUS_ERROR,
                    sprintf('PHP %s wird nicht mehr unterstützt.', $branch)
                );
            } elseif (false !== $activeUntil && $now > $activeUntil) {
                $this->addIssue(
                    $status,
                    $messages,
                    self::STATUS_WARNING,
                    sprintf(
                        'PHP %s erhält nur noch Sicherheitsupdates (bis %s).',
                        $branch,
                        date('d.m.Y', $securityUntil)
                    )
                );
            }

            return;
        }

        [$major, $minor] = array_map('intval', explode('.', $branch, 2));

        if ($major < 8 || (8 === $major && $minor <= 1)) {
            $this->addIssue(
                $status,
                $messages,
                self::STATUS_ERROR,
                sprintf('PHP %s wird nicht mehr unterstützt.', $branch)
            );
        }
    }

    private function versionBranch(string $version): ?string
    {
        if (1 !== preg_match('/\A[vV]?(\d+)\.(\d+)/', trim($version), $matches)) {
            return null;
        }

        return $matches[1].'.'.$matches[2];
    }

    /** @param list<string> $messages */
    private function addIssue(string &$status, array &$messages, string $issueStatus, string $message): void
    {
        $status = $this->worstStatus($status, $issueStatus);
        $messages[] = $message;
    }

    private function worstStatus(string $left, string $right): string
    {
        $severity = [
            self::STATUS_OK => 0,
            self::STATUS_WARNING => 1,
            self::STATUS_ERROR => 2,
        ];

        return ($severity[$right] ?? 0) > ($severity[$left] ?? 0) ? $right : $left;
    }

    private function label(string $status): string
    {
        return match ($status) {
            self::STATUS_ERROR => 'Fehler',
            self::STATUS_WARNING => 'Hinweis',
            default => 'OK',
        };
    }
}
