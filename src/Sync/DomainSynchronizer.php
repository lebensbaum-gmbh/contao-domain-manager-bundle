<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

final class DomainSynchronizer
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly InstallationSynchronizer $installationSynchronizer,
        private readonly LiveInstallationUpdater $liveInstallationUpdater,
    ) {
    }

    public function synchronize(int $domainId): array
    {
        $domainRecord = $this->connection->fetchAssociative(
            'SELECT id, domain FROM '.self::DOMAIN_TABLE.' WHERE id = ?',
            [$domainId]
        );

        if (false === $domainRecord) {
            throw new RuntimeException(sprintf(
                'Die Hauptdomain mit der ID %d wurde in den neuen Domain-Manager-Tabellen nicht gefunden.',
                $domainId
            ));
        }

        $domainName = trim((string) $domainRecord['domain']);
        $installations = $this->connection->fetchAllAssociative(
            'SELECT id, domain, system_id FROM '.self::INSTALLATION_TABLE.' WHERE pid = ? ORDER BY sorting, id',
            [$domainId]
        );

        if ([] === $installations) {
            throw new RuntimeException(sprintf(
                'Zur Hauptdomain „%s“ wurden keine Installationen gefunden.',
                $domainName
            ));
        }

        $synchronized = [];
        $skipped = [];
        $failed = [];

        foreach ($installations as $installation) {
            $installationId = (int) $installation['id'];
            $installationDomain = trim((string) $installation['domain']);
            $systemId = trim((string) $installation['system_id']);

            if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
                $reason = 'Keine gültige Installations-ID hinterlegt.';

                try {
                    $this->connection->update(
                        self::INSTALLATION_TABLE,
                        [
                            'sync_status' => 'not_configured',
                            'sync_message' => $reason,
                            'dm_connection_status' => 'not_configured',
                            'dm_connection_message' => $reason,
                            'tstamp' => time(),
                        ],
                        ['id' => $installationId]
                    );
                } catch (Throwable) {
                }

                $skipped[] = [
                    'id' => $installationId,
                    'domain' => $installationDomain,
                    'reason' => $reason,
                ];

                continue;
            }

            try {
                $synchronized[] = $this->installationSynchronizer->synchronize($installationId);
            } catch (Throwable $exception) {
                $failed[] = [
                    'id' => $installationId,
                    'domain' => $installationDomain,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $live = null;
        $liveError = null;

        try {
            $live = $this->liveInstallationUpdater->update($domainId);
        } catch (Throwable $exception) {
            $liveError = $exception->getMessage();
        }

        return [
            'domain_id' => $domainId,
            'domain' => $domainName,
            'synchronized' => $synchronized,
            'skipped' => $skipped,
            'failed' => $failed,
            'live' => $live,
            'live_error' => $liveError,
        ];
    }
}
