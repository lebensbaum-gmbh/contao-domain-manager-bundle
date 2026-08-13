<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Sync;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

final class LiveInstallationDetector
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemInfoClient $systemInfoClient,
    ) {
    }

    public function detect(int $domainId): array
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

        $publicDomain = trim((string) $domainRecord['domain']);

        if ('' === $publicDomain) {
            throw new RuntimeException('Im Hauptdomain-Datensatz fehlt die Domain.');
        }

        $installations = $this->connection->fetchAllAssociative(
            'SELECT id, domain, system_id FROM '.self::INSTALLATION_TABLE.' WHERE pid = ? ORDER BY sorting, id',
            [$domainId]
        );

        $candidates = [];

        foreach ($installations as $installation) {
            $systemId = trim((string) $installation['system_id']);

            if (1 !== preg_match('/\A[a-f0-9]{32}\z/i', $systemId)) {
                continue;
            }

            $candidates[] = [
                'id' => (int) $installation['id'],
                'domain' => trim((string) $installation['domain']),
                'system_id' => $systemId,
            ];
        }

        if ([] === $candidates) {
            throw new RuntimeException(sprintf(
                'Für die Hauptdomain „%s“ gibt es keine Installation mit einer gültigen Installations-ID.',
                $publicDomain
            ));
        }

        foreach ($candidates as $candidate) {
            try {
                $systemInfo = $this->systemInfoClient->fetch($publicDomain, $candidate['system_id']);
            } catch (Throwable) {
                continue;
            }

            return [
                'domain_id' => $domainId,
                'public_domain' => $publicDomain,
                'installation_id' => $candidate['id'],
                'installation_domain' => $candidate['domain'],
                'system_id' => $systemInfo['system_id'],
            ];
        }

        throw new RuntimeException(sprintf(
            'Unter der Hauptdomain „%s“ konnte keine bekannte Installation identifiziert werden.',
            $publicDomain
        ));
    }
}
