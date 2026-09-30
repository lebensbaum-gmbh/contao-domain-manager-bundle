<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInspector;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInstaller;
use Throwable;

final class UpgradeFrontendWorkspaceMigration extends AbstractMigration
{
    private const WORKSPACE_ITEMS = [
        'updates_page',
        'navigation_module',
        'overview_navigation_element',
        'updates_element',
        'updates_navigation_element',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly SetupInspector $setupInspector,
        private readonly SetupInstaller $setupInstaller,
    ) {
    }

    public function getName(): string
    {
        return 'Domain-Manager: Frontend-Arbeitsbereich aktualisieren';
    }

    public function shouldRun(): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!$schemaManager->tablesExist(['tl_page', 'tl_article', 'tl_content', 'tl_module', 'tl_theme'])) {
                return false;
            }

            $overviewExists = $this->connection->fetchOne(
                "SELECT c.id
                 FROM tl_content c
                 INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                 INNER JOIN tl_page p ON p.id = a.pid
                 WHERE c.type = 'domain_manager_overview' AND p.type = 'regular'
                 ORDER BY c.id LIMIT 1"
            );

            if (false === $overviewExists || (int) $overviewExists < 1) {
                return false;
            }

            $missing = $this->setupInspector->inspect()['missing'];

            foreach (self::WORKSPACE_ITEMS as $item) {
                if (in_array($item, $missing, true)) {
                    return true;
                }
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }

    public function run(): MigrationResult
    {
        try {
            $hostname = trim((string) $this->connection->fetchOne(
                "SELECT root.dns
                 FROM tl_content c
                 INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                 INNER JOIN tl_page p ON p.id = a.pid
                 INNER JOIN tl_page root ON root.id = p.pid AND root.type = 'root'
                 WHERE c.type = 'domain_manager_overview'
                 ORDER BY c.id LIMIT 1"
            ));

            $result = $this->setupInstaller->completeSetup('' !== $hostname ? $hostname : 'localhost');

            return $this->createResult(
                true,
                [] === $result['created']
                    ? 'Der Frontend-Arbeitsbereich war bereits vollständig.'
                    : sprintf(
                        'Der Frontend-Arbeitsbereich wurde automatisch ergänzt (%d Baustein%s).',
                        count($result['created']),
                        1 === count($result['created']) ? '' : 'e'
                    )
            );
        } catch (Throwable $exception) {
            return $this->createResult(
                false,
                'Der Frontend-Arbeitsbereich konnte nicht automatisch aktualisiert werden: '.$exception->getMessage()
            );
        }
    }
}
