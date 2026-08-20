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

    /** @return array<string, int> */
    public function synchronize(): array
    {
        $domainIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM '.self::DOMAIN_TABLE.' ORDER BY domain, id'
        );

        $summary = new AllDomainsSyncSummary(count($domainIds));

        foreach ($domainIds as $domainId) {
            try {
                $summary->addResult(
                    $this->domainSynchronizer->synchronize((int) $domainId)
                );
            } catch (Throwable) {
                $summary->addFailure();
            }
        }

        return $summary->toArray();
    }
}
