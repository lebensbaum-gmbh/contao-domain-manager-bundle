<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Frontend;

final class WorkspacePresentation
{
    public function __construct(private readonly string $proSubscriptionUrl = 'https://maintend.de/')
    {
    }

    /** @param array<string, mixed> $features
     *  @return array{pro_available:bool,pro_enabled:bool,pro_url:string}
     */
    public function features(array $features): array
    {
        $url = trim($this->proSubscriptionUrl);
        if (false === filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true)
        ) {
            $url = 'https://maintend.de/';
        }

        return [
            'pro_available' => true === ($features['pro_available'] ?? false),
            'pro_enabled' => true === ($features['pro_authorized'] ?? false),
            'pro_url' => $url,
        ];
    }

    /** @param list<array<string, mixed>> $installations
     *  @return array{total:int,attention:int,updates:int,problems:int,running:int}
     */
    public function summarize(array $installations): array
    {
        $counts = ['total' => count($installations), 'attention' => 0, 'updates' => 0, 'problems' => 0, 'running' => 0];
        foreach ($installations as $installation) {
            $state = (string) ($installation['update_state'] ?? '');
            $health = (string) ($installation['health']['status'] ?? 'ok');
            $actions = $installation['frontend_actions'] ?? [];
            $hasUpdate = 'available' === $state;
            foreach ($actions as $action) {
                $hasUpdate = $hasUpdate || 'update-install' === ($action['name'] ?? '');
            }
            $problem = 'error' === $health || in_array($state, ['error', 'check_failed', 'warning'], true);
            $running = 'running' === $state
                || 'running' === ($installation['frontend_metadata']['update_details']['status'] ?? '');
            $backlog = (int) ($installation['update_release_gap'] ?? $installation['frontend_metadata']['update_release_gap'] ?? 0) >= 5;
            $attention = $hasUpdate || $problem || $running || $backlog || 'warning' === $health || 'unknown' === $state;
            $counts['updates'] += (int) $hasUpdate;
            $counts['problems'] += (int) $problem;
            $counts['running'] += (int) $running;
            $counts['attention'] += (int) $attention;
        }

        return $counts;
    }
}
