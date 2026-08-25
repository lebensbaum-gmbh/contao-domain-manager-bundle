<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Event\InstallationHealthEvaluationEvent;
use Lebensbaum\ContaoDomainManagerBundle\Health\InstallationHealthEvaluator;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use Lebensbaum\ContaoDomainManagerBundle\Util\SystemValueNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

#[AsContentElement(
    type: 'domain_manager_overview',
    category: 'domain_manager',
)]
final class DomainManagerOverviewController extends AbstractContentElementController
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';
    private const SERVICE_TABLE = 'tl_domain_manager_external_service';

    public function __construct(
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $router,
        private readonly DomainManagerSettings $settings,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly InstallationHealthEvaluator $healthEvaluator,
        private readonly EventDispatcherInterface $eventDispatcher,
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
        $externalServices = $this->loadExternalServices();
        $staleSyncDays = $this->settings->getStaleSyncDays();
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
                $installation = $this->normalizeInstallation($installationRow, $externalServices);
                $installation['health'] = $this->healthEvaluator->evaluate($installation, $staleSyncDays);

                $healthEvent = new InstallationHealthEvaluationEvent($installation);
                $this->eventDispatcher->dispatch($healthEvent);
                $installation['health'] = $this->applyHealthExtensions(
                    $installation['health'],
                    $healthEvent->getIssues(),
                    $healthEvent->getInfoMessages()
                );

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

            $domainHealth = $this->healthEvaluator->summarize(
                array_map(
                    static fn (array $installation): array => $installation['health'],
                    $installations
                ),
                null !== $targetInstallation
            );

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
                foreach ($installation['external_services'] as $service) {
                    $searchTerms[] = $service['name'];
                }
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
                'health' => $domainHealth,
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
        $template->set('external_services', array_values($externalServices));
        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array{id: int, name: string, url: string}> $externalServices
     * @return array<string, mixed>
     */
    private function normalizeInstallation(array $row, array $externalServices): array
    {
        $domain = trim((string) ($row['domain'] ?? ''));
        $environment = $this->normalizeEnvironment((string) ($row['environment'] ?? ''));
        $contaoVersion = trim((string) ($row['contao_version'] ?? ''));
        $phpVersion = trim((string) ($row['php_version'] ?? ''));
        $backendUrl = $this->safeUrl((string) ($row['backend_url'] ?? ''));
        $managerUrl = $this->safeUrl((string) ($row['manager_url'] ?? ''));
        $externalServiceIds = [];

        foreach (StringUtil::deserialize($row['external_services'] ?? null, true) as $serviceId) {
            if (is_numeric($serviceId) && isset($externalServices[(int) $serviceId])) {
                $externalServiceIds[] = (int) $serviceId;
            }
        }

        $assignedServices = [];
        foreach (array_values(array_unique($externalServiceIds)) as $serviceId) {
            $assignedServices[] = $externalServices[$serviceId];
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'domain' => $domain,
            'frontend_url' => '' !== $domain ? 'https://'.$domain : '',
            'environment' => $environment,
            'environment_label' => $this->environmentLabel($environment),
            'system_id' => trim((string) ($row['system_id'] ?? '')),
            'document_root' => SystemValueNormalizer::webrootLabel((string) ($row['document_root'] ?? '')),
            'contao_version' => $contaoVersion,
            'php_version' => $phpVersion,
            'database_name' => trim((string) ($row['database_name'] ?? '')),
            'backend_url' => $backendUrl,
            'manager_url' => $managerUrl,
            'is_live' => $this->isChecked($row['is_live'] ?? ''),
            'external_service_ids' => $externalServiceIds,
            'external_services' => $assignedServices,
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

    /**
     * @param array{status:string,label:string,messages:list<string>,issue_count:int} $health
     * @param list<array{status:string,message:string}> $issues
     * @param list<string> $infoMessages
     * @return array{status:string,label:string,messages:list<string>,issue_count:int}
     */
    private function applyHealthExtensions(array $health, array $issues, array $infoMessages = []): array
    {
        $severity = [
            InstallationHealthEvaluator::STATUS_OK => 0,
            InstallationHealthEvaluator::STATUS_WARNING => 1,
            InstallationHealthEvaluator::STATUS_ERROR => 2,
        ];
        $status = (string) ($health['status'] ?? InstallationHealthEvaluator::STATUS_OK);
        $messages = $health['messages'] ?? [];
        $issueCount = max(0, (int) ($health['issue_count'] ?? count($messages)));

        foreach ($issues as $issue) {
            $issueStatus = (string) ($issue['status'] ?? InstallationHealthEvaluator::STATUS_OK);
            $message = trim((string) ($issue['message'] ?? ''));

            if ('' === $message) {
                continue;
            }

            if (($severity[$issueStatus] ?? 0) > ($severity[$status] ?? 0)) {
                $status = $issueStatus;
            }

            if (!in_array($message, $messages, true)) {
                $messages[] = $message;
                ++$issueCount;
            }
        }

        foreach ($infoMessages as $message) {
            $message = trim($message);

            if ('' !== $message && !in_array($message, $messages, true)) {
                $messages[] = $message;
            }
        }

        return [
            'status' => $status,
            'label' => match ($status) {
                InstallationHealthEvaluator::STATUS_ERROR => 'Fehler',
                InstallationHealthEvaluator::STATUS_WARNING => 'Hinweis',
                default => 'OK',
            },
            'messages' => $messages,
            'issue_count' => $issueCount,
        ];
    }

    /** @return array<int, array{id: int, name: string, url: string}> */
    private function loadExternalServices(): array
    {
        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, name, url FROM '.self::SERVICE_TABLE.' ORDER BY name, id'
            );
        } catch (Throwable) {
            return [];
        }

        $services = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));

            if ($id < 1 || '' === $name) {
                continue;
            }

            $services[$id] = [
                'id' => $id,
                'name' => $name,
                'url' => $this->safeUrl((string) ($row['url'] ?? '')),
            ];
        }

        return $services;
    }

    private function isChecked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'on'], true);
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
        } catch (Throwable) {
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
