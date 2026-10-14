<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Event\InstallationFrontendExtensionEvent;
use Lebensbaum\ContaoDomainManagerBundle\Event\OverviewFrontendExtensionEvent;
use Lebensbaum\ContaoDomainManagerBundle\Frontend\FrontendWorkspaceNavigation;
use Lebensbaum\ContaoDomainManagerBundle\Frontend\WorkspacePresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsContentElement(
    type: 'domain_manager_updates',
    category: 'domain_manager',
)]
final class DomainManagerUpdatesController extends AbstractContentElementController
{
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';

    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FrontendWorkspaceNavigation $workspaceNavigation,
        private readonly WorkspacePresentation $workspacePresentation,
    ) {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ContentModel $model,
        Request $request,
    ): Response {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT i.id, i.pid, i.domain, i.environment, i.system_id, i.contao_version, '
            .'CAST(i.php_version AS CHAR) AS php_version, i.last_sync, i.status, '
            .'d.domain AS parent_domain, d.title AS parent_title, d.thumbnail AS parent_thumbnail '
            .'FROM '.self::INSTALLATION_TABLE.' i '
            .'LEFT JOIN '.self::DOMAIN_TABLE.' d ON d.id = i.pid '
            .'ORDER BY d.domain, i.sorting, i.id'
        );

        $installations = [];
        $featuresEvent = new OverviewFrontendExtensionEvent([]);
        $this->eventDispatcher->dispatch($featuresEvent);
        $workspace = $this->workspacePresentation->features($featuresEvent->getMetadata());
        $proEnabled = $workspace['pro_enabled'];
        $availableCount = 0;
        $currentCount = 0;
        $errorCount = 0;
        $checkFailureCount = 0;
        $totalCount = count($rows);
        $resultInstallationId = $request->query->getInt('dm_installation');
        $checkStatus = trim((string) $request->query->get('dm_update', ''));
        $installStatus = trim((string) $request->query->get('dm_update_install', ''));

        foreach ($rows as $row) {
            $installation = [
                'id' => (int) ($row['id'] ?? 0),
                'pid' => (int) ($row['pid'] ?? 0),
                'domain' => trim((string) ($row['domain'] ?? '')),
                'parent_domain' => trim((string) ($row['parent_domain'] ?? '')),
                'parent_title' => trim((string) ($row['parent_title'] ?? '')),
                'thumbnail_path' => $this->resolveThumbnailPath($row['parent_thumbnail'] ?? null),
                'environment' => $this->normalizeEnvironment((string) ($row['environment'] ?? '')),
                'system_id' => strtolower(trim((string) ($row['system_id'] ?? ''))),
                'contao_version' => trim((string) ($row['contao_version'] ?? '')),
                'php_version' => trim((string) ($row['php_version'] ?? '')),
                'last_sync' => (int) ($row['last_sync'] ?? 0),
                'status' => trim((string) ($row['status'] ?? '')),
                'frontend_workspace' => 'updates',
            ];
            $installation['environment_label'] = $this->environmentLabel($installation['environment']);

            $frontendEvent = new InstallationFrontendExtensionEvent($installation);
            $this->eventDispatcher->dispatch($frontendEvent);
            $actions = $frontendEvent->getActions();
            $frontendMetadata = $frontendEvent->getMetadata();
            $updateDetails = $frontendMetadata['update_details'] ?? null;
            $updateDetails = is_array($updateDetails) ? $updateDetails : null;
            $targetContaoVersion = trim((string) ($frontendMetadata['update_target_version'] ?? ''));

            if (1 !== preg_match('/\Av?\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/', $targetContaoVersion)) {
                $targetContaoVersion = '';
            }

            $hasCheckAction = $this->hasAction($actions, 'update-check');
            $hasInstallAction = $this->hasAction($actions, 'update-install');
            $proEnabled = $proEnabled || $hasCheckAction;

            $updateState = $hasInstallAction ? 'available' : ($hasCheckAction ? 'unknown' : 'locked');
            if ($hasCheckAction && true === ($updateDetails['relevant'] ?? false)) {
                if ('up_to_date' === ($updateDetails['check_status'] ?? '')) {
                    $updateState = 'current';
                }
                if ('running' === ($updateDetails['status'] ?? '')) {
                    $updateState = 'running';
                }
            }

            // A failed Composer check is not the same as a failed installation.
            // Both remain visible, but get different severity and counts.
            if ($hasCheckAction && null !== $updateDetails
                && true === ($updateDetails['relevant'] ?? false)
                && '' !== (string) ($updateDetails['error_heading'] ?? '')
            ) {
                $updateState = 'error' === ($updateDetails['status'] ?? '')
                    ? 'error'
                    : 'check_failed';
            }

            if ($hasCheckAction && $installation['id'] === $resultInstallationId && '' !== $checkStatus) {
                if ('ready' === $checkStatus) {
                    $updateState = 'available';
                } elseif ('up_to_date' === $checkStatus) {
                    $updateState = 'current';
                } elseif (in_array($checkStatus, ['blocked', 'error'], true)) {
                    $updateState = 'check_failed';
                }
            }

            if ($hasCheckAction && $installation['id'] === $resultInstallationId && '' !== $installStatus) {
                if ('success' === $installStatus) {
                    $updateState = 'current';
                } elseif ('success_sync_warning' === $installStatus) {
                    $updateState = 'warning';
                } elseif ('error' === $installStatus) {
                    $updateState = 'error';
                }
            }

            if ('available' === $updateState) {
                ++$availableCount;
            } elseif ('current' === $updateState) {
                ++$currentCount;
            } elseif ('error' === $updateState) {
                ++$errorCount;
            } elseif ('check_failed' === $updateState) {
                ++$checkFailureCount;
            }

            $installation['frontend_actions'] = $actions;
            $installation['frontend_metadata'] = $frontendMetadata;
            $installation['update_details'] = $updateDetails;
            $installation['target_contao_version'] = $targetContaoVersion;
            $installation['update_state'] = $updateState;
            $installation['update_state_label'] = $this->updateStateLabel($updateState);
            $gap = $frontendMetadata['update_release_gap'] ?? null;
            $installation['update_release_gap'] = is_int($gap) && $gap >= 0 ? $gap : null;
            if ('available' === $updateState && null !== $installation['update_release_gap'] && $gap >= 5) {
                $installation['update_state_label'] = sprintf('Wartungsrückstand · %d Versionen', $gap);
            }
            $installations[] = $installation;
        }

        $template->set('installations', $installations);
        $template->set('pro_enabled', $proEnabled);
        $template->set('workspace', $workspace);
        $template->set('workspace_counts', $this->workspacePresentation->summarize($installations));
        $template->set('workspace_groups', count(array_unique(array_column($installations, 'pid'))));
        $template->set('available_count', $availableCount);
        $template->set('current_count', $currentCount);
        $template->set('error_count', $errorCount);
        $template->set('check_failure_count', $checkFailureCount);
        $template->set('total_count', $totalCount);
        $template->set('result_installation_id', $resultInstallationId);
        $template->set('check_status', $checkStatus);
        $template->set('install_status', $installStatus);
        $template->set('result_message', $proEnabled ? $this->resultMessage($checkStatus, $installStatus) : '');
        $backupCreateStatus = trim((string) $request->query->get('dm_backup_create', ''));
        $backupRestoreStatus = trim((string) $request->query->get('dm_backup_restore', ''));
        $backupDeleteStatus = trim((string) $request->query->get('dm_backup_delete', ''));
        $template->set('backup_result_message', $proEnabled ? $this->backupResultMessage($backupCreateStatus, $backupRestoreStatus, $backupDeleteStatus) : '');
        $template->set('backup_result_error', in_array($backupCreateStatus, ['error'], true)
            || in_array($backupRestoreStatus, ['error', 'confirmation_invalid'], true)
            || in_array($backupDeleteStatus, ['error', 'confirmation_invalid'], true));
        $template->set('backup_result_installation_id', $request->query->getInt('dm_backup_installation'));
        $page = $request->attributes->get('pageModel');
        $pageId = $page instanceof PageModel ? (int) $page->id : (is_numeric($page) ? (int) $page : 0);
        $template->set('workspace_navigation', $this->workspaceNavigation->build($pageId));

        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /** @param list<array{name:string,label:string,url:string,method:string,variant:string,confirm:string}> $actions */
    private function hasAction(array $actions, string $name): bool
    {
        foreach ($actions as $action) {
            if (($action['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }

    private function normalizeEnvironment(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'live', 'production', 'prod' => 'live',
            'stage', 'staging' => 'staging',
            'dev', 'development' => 'development',
            'test', 'testing' => 'test',
            default => $value,
        };
    }

    private function environmentLabel(string $environment): string
    {
        return match ($environment) {
            'live' => 'Live',
            'staging' => 'Staging',
            'development' => 'Entwicklung',
            'test' => 'Test',
            '' => '—',
            default => ucfirst($environment),
        };
    }

    private function updateStateLabel(string $state): string
    {
        return match ($state) {
            'available' => 'Update verfügbar',
            'current' => 'Aktuell',
            'error' => 'Update fehlgeschlagen',
            'check_failed' => 'Prüfung fehlgeschlagen',
            'warning' => 'Installiert · Synchronisation prüfen',
            'locked' => 'Pro-Funktion',
            'running' => 'Wartung läuft',
            default => 'Noch nicht geprüft',
        };
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

    private function backupResultMessage(string $create, string $restore, string $delete): string
    {
        if ('' !== $delete) {
            return match ($delete) {
                'success' => 'Sicherung auf der Zielinstallation gelöscht und aus der Historie entfernt.',
                'confirmation_invalid' => 'Sicherung nicht gelöscht: Die Löschbestätigung fehlt.',
                'error' => 'Sicherung konnte nicht gelöscht werden. Die Dateien oder der Historieneintrag sind möglicherweise noch vorhanden.',
                default => '',
            };
        }

        if ('' !== $restore) {
            return match ($restore) {
                'success' => 'Wiederherstellung abgeschlossen. Die Installation wurde erneut synchronisiert.',
                'success_sync_warning' => 'Wiederherstellung abgeschlossen. Die anschließende Synchronisation muss noch geprüft werden.',
                'confirmation_invalid' => 'Wiederherstellung nicht gestartet: Domain-Bestätigung oder Sicherheitsbestätigung fehlt.',
                'error' => 'Wiederherstellung nicht erfolgreich bestätigt. Der Zielzustand ist möglicherweise unvollständig. Bitte keine weitere Aktion starten und die Protokolle sowie den manuellen Notfallweg prüfen.',
                default => '',
            };
        }

        return match ($create) {
            'success' => 'Sicherung erstellt und von der Zielinstallation bestätigt.',
            'error' => 'Sicherung konnte nicht erfolgreich bestätigt werden. Bitte den Backup-Status der Installation prüfen.',
            default => '',
        };
    }

    private function resultMessage(string $checkStatus, string $installStatus): string
    {
        if ('' !== $installStatus) {
            return match ($installStatus) {
                'success' => 'Update erfolgreich installiert, verifiziert und Systemdaten aktualisiert.',
                'success_sync_warning' => 'Update erfolgreich installiert und verifiziert. Die anschließende Synchronisation der Systemdaten ist jedoch fehlgeschlagen.',
                'error' => 'Die Update-Installation ist fehlgeschlagen. Details bleiben an der Installation erhalten.',
                default => 'Die Update-Installation wurde verarbeitet.',
            };
        }

        return match ($checkStatus) {
            'ready' => 'Prüfung abgeschlossen: Für die Installation ist ein Update verfügbar.',
            'up_to_date' => 'Prüfung abgeschlossen: Die Installation ist aktuell.',
            'blocked' => 'Die Update-Prüfung wurde durch die Update-Policy blockiert.',
            'error' => 'Die Update-Prüfung ist fehlgeschlagen.',
            'running' => 'Die Update-Prüfung läuft.',
            default => '',
        };
    }
}
