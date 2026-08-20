<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Sync;

use Lebensbaum\ContaoDomainManagerBundle\Sync\AllDomainsSyncSummary;
use PHPUnit\Framework\TestCase;

final class AllDomainsSyncSummaryTest extends TestCase
{
    public function testSuccessfulAndPartialDomainsAreAggregated(): void
    {
        $summary = new AllDomainsSyncSummary(3);

        $summary->addResult([
            'synchronized' => [['id' => 1], ['id' => 2]],
            'skipped' => [['id' => 3]],
            'failed' => [],
            'live_error' => null,
        ]);

        $summary->addResult([
            'synchronized' => [['id' => 4]],
            'skipped' => [],
            'failed' => [['id' => 5]],
            'live_error' => 'Ziel konnte nicht ermittelt werden.',
        ]);

        $summary->addFailure();

        self::assertSame([
            'domains_total' => 3,
            'domains_success' => 1,
            'domains_partial' => 1,
            'domains_failed' => 1,
            'synchronized' => 3,
            'skipped' => 1,
            'failed' => 1,
            'live_errors' => 1,
        ], $summary->toArray());
    }

    public function testSkippedInstallationsDoNotMakeDomainPartial(): void
    {
        $summary = new AllDomainsSyncSummary(1);

        $summary->addResult([
            'synchronized' => [],
            'skipped' => [['id' => 1]],
            'failed' => [],
            'live_error' => null,
        ]);

        self::assertSame(1, $summary->toArray()['domains_success']);
        self::assertSame(0, $summary->toArray()['domains_partial']);
        self::assertSame(1, $summary->toArray()['skipped']);
    }
}
