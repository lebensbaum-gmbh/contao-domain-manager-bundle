<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(
    type: 'domain_manager_filter',
    category: 'domain_manager',
)]
final class DomainManagerFilterController extends AbstractContentElementController
{
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ContentModel $model,
        Request $request,
    ): Response {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT contao_version, php_version, environment FROM '.self::INSTALLATION_TABLE.' ORDER BY id'
        );

        $contaoVersions = [];
        $phpVersions = [];
        $environments = [];

        foreach ($rows as $row) {
            $contaoVersion = trim((string) ($row['contao_version'] ?? ''));
            $phpVersion = trim((string) ($row['php_version'] ?? ''));
            $environment = $this->normalizeEnvironment((string) ($row['environment'] ?? ''));

            if ('' !== $contaoVersion) {
                $contaoVersions[$contaoVersion] = true;
            }

            if ('' !== $phpVersion) {
                $phpVersions[$phpVersion] = true;
            }

            if ('' !== $environment) {
                $environments[$environment] = $this->environmentLabel($environment);
            }
        }

        $contaoVersions = array_keys($contaoVersions);
        $phpVersions = array_keys($phpVersions);

        usort($contaoVersions, static fn (string $a, string $b): int => version_compare($b, $a));
        usort($phpVersions, static fn (string $a, string $b): int => version_compare($b, $a));
        uasort($environments, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

        $template->set('contao_versions', $contaoVersions);
        $template->set('php_versions', $phpVersions);
        $template->set('environments', $environments);

        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    private function normalizeEnvironment(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'production', 'prod', 'live' => 'live',
            'mirror' => 'mirror',
            'test', 'testing' => 'test',
            'dev', 'development' => 'dev',
            default => $normalized,
        };
    }

    private function environmentLabel(string $value): string
    {
        return match ($value) {
            'live' => 'Live',
            'mirror' => 'Mirror',
            'test' => 'Test',
            'dev' => 'Entwicklung',
            default => '' !== $value ? ucfirst($value) : '',
        };
    }
}
