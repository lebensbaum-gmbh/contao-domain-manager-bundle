<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

final class AllDomainsSyncSummary
{
    /** @var array<string, int> */
    private array $summary;

    public function __construct(int $domainsTotal)
    {
        $this->summary = [
            'domains_total' => $domainsTotal,
            'domains_success' => 0,
            'domains_partial' => 0,
            'domains_failed' => 0,
            'synchronized' => 0,
            'skipped' => 0,
            'failed' => 0,
            'live_errors' => 0,
        ];
    }

    /** @param array<string, mixed> $result */
    public function addResult(array $result): void
    {
        $failed = is_array($result['failed'] ?? null) ? $result['failed'] : [];
        $skipped = is_array($result['skipped'] ?? null) ? $result['skipped'] : [];
        $synchronized = is_array($result['synchronized'] ?? null) ? $result['synchronized'] : [];
        $liveError = $result['live_error'] ?? null;

        $this->summary['synchronized'] += count($synchronized);
        $this->summary['skipped'] += count($skipped);
        $this->summary['failed'] += count($failed);

        if (null !== $liveError) {
            ++$this->summary['live_errors'];
        }

        if ([] === $failed && null === $liveError) {
            ++$this->summary['domains_success'];
        } else {
            ++$this->summary['domains_partial'];
        }
    }

    public function addFailure(): void
    {
        ++$this->summary['domains_failed'];
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return $this->summary;
    }
}
