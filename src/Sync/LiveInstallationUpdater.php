<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use Throwable;

final class LiveInstallationUpdater
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly LiveInstallationDetector $detector,
    ) {
    }

    public function update(int $domainId): array
    {
        $timestamp = time();

        try {
            $detected = $this->detector->detect($domainId);
            $detectedInstallationId = $detected['installation_id'];

            $previousLiveIds = array_map(
                'intval',
                $this->connection->fetchFirstColumn(
                    'SELECT id FROM '.self::INSTALLATION_TABLE." WHERE pid = ? AND CAST(is_live AS CHAR) = '1' ORDER BY id",
                    [$domainId]
                )
            );

            $previousInstallationId = $previousLiveIds[0] ?? null;
            $isAlreadyClean = 1 === count($previousLiveIds)
                && $previousLiveIds[0] === $detectedInstallationId;

            if (!$isAlreadyClean) {
                $this->connection->beginTransaction();

                try {
                    $this->connection->executeStatement(
                        'UPDATE '.self::INSTALLATION_TABLE." SET is_live = '', tstamp = ? WHERE pid = ? AND CAST(is_live AS CHAR) = '1'",
                        [$timestamp, $domainId]
                    );

                    $this->connection->update(
                        self::INSTALLATION_TABLE,
                        ['is_live' => '1', 'tstamp' => $timestamp],
                        ['id' => $detectedInstallationId, 'pid' => $domainId]
                    );

                    $this->connection->commit();
                } catch (Throwable $exception) {
                    $this->connection->rollBack();
                    throw $exception;
                }
            }

            $this->storeLiveStatus($domainId, 'success', '', $detectedInstallationId, $timestamp);

            return [
                'domain_id' => $domainId,
                'public_domain' => $detected['public_domain'],
                'installation_id' => $detectedInstallationId,
                'installation_domain' => $detected['installation_domain'],
                'previous_installation_id' => $previousInstallationId,
                'changed' => !$isAlreadyClean,
            ];
        } catch (Throwable $exception) {
            $this->storeLiveStatus($domainId, 'error', $exception->getMessage(), 0, $timestamp);
            throw $exception;
        }
    }

    private function storeLiveStatus(int $domainId, string $status, string $error, int $installationId, int $timestamp): void
    {
        $this->connection->update(
            self::DOMAIN_TABLE,
            [
                'dm_live_status' => $status,
                'dm_live_last_check' => $timestamp,
                'dm_live_error' => '' === trim($error) ? null : substr(trim($error), 0, 2000),
                'dm_live_installation_id' => $installationId,
                'tstamp' => $timestamp,
            ],
            ['id' => $domainId]
        );
    }
}
