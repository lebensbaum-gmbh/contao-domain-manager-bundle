<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use Throwable;

final class AllDomainsSynchronizer
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';

    public function __construct(
        private readonly Connection $connection,
        private readonly DomainSynchronizer $domainSynchronizer,
    ) {
    }

    /**
     * @return array{
     *     domains_total: int,
     *     domains_success: int,
     *     domains_partial: int,
     *     domains_failed: int,
     *     synchronized: int,
     *     skipped: int,
     *     failed: int,
     *     live_errors: int
     * }
     */
    public function synchronize(): array
    {
        $domainIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM '.self::DOMAIN_TABLE.' ORDER BY domain, id'
        );

        $summary = [
            'domains_total' => count($domainIds),
            'domains_success' => 0,
            'domains_partial' => 0,
            'domains_failed' => 0,
            'synchronized' => 0,
            'skipped' => 0,
            'failed' => 0,
            'live_errors' => 0,
        ];

        foreach ($domainIds as $domainId) {
            try {
                $result = $this->domainSynchronizer->synchronize((int) $domainId);
            } catch (Throwable) {
                ++$summary['domains_failed'];
                continue;
            }

            $summary['synchronized'] += count($result['synchronized']);
            $summary['skipped'] += count($result['skipped']);
            $summary['failed'] += count($result['failed']);

            if (null !== $result['live_error']) {
                ++$summary['live_errors'];
            }

            if ([] === $result['failed'] && null === $result['live_error']) {
                ++$summary['domains_success'];
            } else {
                ++$summary['domains_partial'];
            }
        }

        return $summary;
    }
}
