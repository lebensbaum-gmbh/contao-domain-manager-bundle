<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\FilesModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsContentElement(
    type: 'domain_manager_overview',
    category: 'domain_manager',
)]
final class DomainManagerOverviewController extends AbstractContentElementController
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $router,
        private readonly DomainManagerSettings $settings,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ContentModel $model,
        Request $request,
    ): Response {
        $domains = [];
        $allContaoVersions = [];
        $allPhpVersions = [];
        $allEnvironments = [];
        $syncMemberGroupIds = $this->settings->getSyncMemberGroupIds();
        $canSync = [] !== $syncMemberGroupIds
            && $this->authorizationChecker->isGranted(
                ContaoCorePermissions::MEMBER_IN_GROUPS,
                $syncMemberGroupIds
            );

        $domainRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM '.self::DOMAIN_TABLE.' ORDER BY domain, id'
        );

        foreach ($domainRows as $domainRow) {
            $domainId = (int) ($domainRow['id'] ?? 0);

            if ($domainId < 1) {
                continue;
            }

            $installationRows = $this->connection->fetchAllAssociative(
                'SELECT * FROM '.self::INSTALLATION_TABLE.' WHERE pid = ? ORDER BY sorting, id',
                [$domainId]
            );

            $installations = [];
            $targetInstallation = null;
            $liveInstallationId = (int) ($domainRow['dm_live_installation_id'] ?? 0);

            foreach ($installationRows as $installationRow) {
                $installation = $this->normalizeInstallation($installationRow);
                $installations[] = $installation;

                if ('' !== $installation['contao_version']) {
                    $allContaoVersions[$installation['contao_version']] = true;
                }
                if ('' !== $installation['php_version']) {
                    $allPhpVersions[$installation['php_version']] = true;
                }
                if ('' !== $installation['environment']) {
                    $allEnvironments[$installation['environment']] = $installation['environment_label'];
                }

                if (
                    null === $targetInstallation
                    && (
                        $installation['is_live']
                        || ($liveInstallationId > 0 && $installation['id'] === $liveInstallationId)
                    )
                ) {
                    $targetInstallation = $installation;
                }
            }

            $otherInstallations = array_values(array_filter(
                $installations,
                static fn (array $installation): bool => null === $targetInstallation
                    || $installation['id'] !== $targetInstallation['id']
            ));

            $syncResult = null;
            if ($request->query->getInt('dm_domain') === $domainId) {
                $syncStatus = trim((string) $request->query->get('dm_sync', ''));

                if ('' !== $syncStatus) {
                    $syncResult = [
                        'status' => $syncStatus,
                        'synced' => $request->query->getInt('dm_synced'),
                        'skipped' => $request->query->getInt('dm_skipped'),
                        'failed' => $request->query->getInt('dm_failed'),
                        'live_status' => trim((string) $request->query->get('dm_live', '')),
                        'live_domain' => trim((string) $request->query->get('dm_live_domain', '')),
                    ];
                }
            }

            $searchTerms = [trim((string) ($domainRow['domain'] ?? ''))];
            foreach ($installations as $installation) {
                $searchTerms[] = $installation['domain'];
                $searchTerms[] = $installation['environment_label'];
            }

            $domains[] = [
                'id' => $domainId,
                'domain' => trim((string) ($domainRow['domain'] ?? '')),
                'title' => trim((string) ($domainRow['title'] ?? '')),
                'status' => trim((string) ($domainRow['status'] ?? '')),
                'notes' => trim((string) ($domainRow['notes'] ?? '')),
                'thumbnail_path' => $this->resolveThumbnailPath($domainRow['thumbnail'] ?? null),
                'live_status' => trim((string) ($domainRow['dm_live_status'] ?? '')),
                'live_error' => trim((string) ($domainRow['dm_live_error'] ?? '')),
                'target' => $targetInstallation,
                'others' => $otherInstallations,
                'installations' => $installations,
                'search' => strtolower(implode(' ', array_filter($searchTerms))),
                'sync_url' => $this->router->generate(
                    'contao_domain_manager_sync_domain',
                    ['domainId' => $domainId]
                ),
                'sync_result' => $syncResult,
            ];
        }

        $contaoVersions = array_keys($allContaoVersions);
        $phpVersions = array_keys($allPhpVersions);
        natcasesort($contaoVersions);
        natcasesort($phpVersions);
        asort($allEnvironments, SORT_NATURAL | SORT_FLAG_CASE);

        $template->set('domains', $domains);
        $template->set('contao_versions', array_values($contaoVersions));
        $template->set('php_versions', array_values($phpVersions));
        $template->set('environments', $allEnvironments);
        $template->set('can_sync', $canSync);
        $template->set('trakked_url', $this->settings->getTrakkedUrl());
        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeInstallation(array $row): array
    {
        $domain = trim((string) ($row['domain'] ?? ''));
        $environment = trim((string) ($row['environment'] ?? ''));
        $contaoVersion = trim((string) ($row['contao_version'] ?? ''));
        $phpVersion = trim((string) ($row['php_version'] ?? ''));
        $backendUrl = $this->safeUrl((string) ($row['backend_url'] ?? ''));
        $managerUrl = $this->safeUrl((string) ($row['manager_url'] ?? ''));

        return [
            'id' => (int) ($row['id'] ?? 0),
            'domain' => $domain,
            'frontend_url' => '' !== $domain ? 'https://'.$domain : '',
            'environment' => $environment,
            'environment_label' => $this->environmentLabel($environment),
            'system_id' => trim((string) ($row['system_id'] ?? '')),
            'document_root' => $this->webrootLabel((string) ($row['document_root'] ?? '')),
            'contao_version' => $contaoVersion,
            'php_version' => $phpVersion,
            'database_name' => trim((string) ($row['database_name'] ?? '')),
            'backend_url' => $backendUrl,
            'manager_url' => $managerUrl,
            'is_live' => $this->isChecked($row['is_live'] ?? ''),
            'trakked' => $this->isChecked($row['trakked'] ?? ''),
            'status' => trim((string) ($row['status'] ?? '')),
            'notes' => trim((string) ($row['notes'] ?? '')),
            'last_sync' => (int) ($row['last_sync'] ?? 0),
            'last_sync_label' => $this->formatTimestamp((int) ($row['last_sync'] ?? 0)),
            'sync_status' => trim((string) ($row['sync_status'] ?? '')),
            'sync_message' => trim((string) ($row['sync_message'] ?? '')),
            'connection_status' => trim((string) ($row['dm_connection_status'] ?? '')),
            'connection_message' => trim((string) ($row['dm_connection_message'] ?? '')),
        ];
    }

    private function webrootLabel(string $documentRoot): string
    {
        $normalized = str_replace('\\', '/', trim($documentRoot));
        $normalized = rtrim($normalized, '/');

        if ('' === $normalized) {
            return '';
        }

        return basename($normalized);
    }

    private function isChecked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'on'], true);
    }

    private function environmentLabel(string $value): string
    {
        return match (strtolower($value)) {
            'live', 'production', 'prod' => 'Live',
            'mirror' => 'Mirror',
            'test', 'testing' => 'Test',
            'dev', 'development' => 'Entwicklung',
            default => '' !== $value ? ucfirst($value) : '',
        };
    }

    private function safeUrl(string $value): string
    {
        $url = trim($value);

        if (
            '' !== $url
            && false !== filter_var($url, FILTER_VALIDATE_URL)
            && 1 === preg_match('~^https?://~i', $url)
        ) {
            return $url;
        }

        return '';
    }

    private function resolveThumbnailPath(mixed $uuid): string
    {
        if (null === $uuid || '' === $uuid) {
            return '';
        }

        try {
            $file = FilesModel::findByUuid($uuid);

            return null !== $file ? trim((string) $file->path) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatTimestamp(int $timestamp): string
    {
        if ($timestamp < 1) {
            return '';
        }

        return (new \DateTimeImmutable('@'.$timestamp))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format('d.m.Y, H.i \\U\\h\\r');
    }
}
