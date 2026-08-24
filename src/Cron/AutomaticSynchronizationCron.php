<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Cron;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use Lebensbaum\ContaoDomainManagerBundle\Sync\AllDomainsSynchronizer;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsCronJob('hourly')]
final class AutomaticSynchronizationCron
{
    public function __construct(
        private readonly DomainManagerSettings $settings,
        private readonly AllDomainsSynchronizer $synchronizer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $scope): void
    {
        if (!$this->settings->isAutoSyncEnabled()) {
            return;
        }

        $now = time();

        if (!$this->settings->isAutoSyncDue($now)) {
            return;
        }

        if (Cron::SCOPE_WEB === $scope) {
            throw new CronExecutionSkippedException();
        }

        $this->settings->markAutoSyncAttempt($now);

        try {
            $summary = $this->synchronizer->synchronize();
            $status = 0 === ($summary['domains_failed'] ?? 0)
                && 0 === ($summary['domains_partial'] ?? 0)
                ? 'success'
                : 'partial';
            $message = $this->formatSummary($summary);

            $this->settings->storeAutoSyncResult($status, $message, $now);

            if ('partial' === $status) {
                $this->logger->warning(
                    'Die automatische Domain-Manager-Synchronisation wurde nur teilweise abgeschlossen.',
                    ['summary' => $summary]
                );
            }
        } catch (Throwable $exception) {
            $this->settings->storeAutoSyncResult(
                'error',
                'Die automatische Synchronisation ist fehlgeschlagen.',
                $now
            );

            $this->logger->error(
                'Die automatische Domain-Manager-Synchronisation ist fehlgeschlagen.',
                ['exception' => $exception]
            );
        }
    }

    /** @param array<string, int> $summary */
    private function formatSummary(array $summary): string
    {
        $domains = (int) ($summary['domains_total'] ?? 0);
        $successfulDomains = (int) ($summary['domains_success'] ?? 0);
        $partialDomains = (int) ($summary['domains_partial'] ?? 0);
        $failedDomains = (int) ($summary['domains_failed'] ?? 0);
        $synchronized = (int) ($summary['synchronized'] ?? 0);
        $skipped = (int) ($summary['skipped'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        $liveErrors = (int) ($summary['live_errors'] ?? 0);

        return sprintf(
            '%d Hauptdomain%s verarbeitet: %d erfolgreich, %d teilweise, %d fehlgeschlagen. %d Installation%s synchronisiert, %d übersprungen, %d fehlgeschlagen.%s',
            $domains,
            1 === $domains ? '' : 's',
            $successfulDomains,
            $partialDomains,
            $failedDomains,
            $synchronized,
            1 === $synchronized ? '' : 'en',
            $skipped,
            $failed,
            $liveErrors > 0 ? sprintf(' %d Zielermittlung%s fehlgeschlagen.', $liveErrors, 1 === $liveErrors ? '' : 'en') : ''
        );
    }
}
