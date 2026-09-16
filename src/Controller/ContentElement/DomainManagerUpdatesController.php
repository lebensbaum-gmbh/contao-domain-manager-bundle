<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Event\InstallationFrontendExtensionEvent;
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
            .'d.domain AS parent_domain, d.title AS parent_title '
            .'FROM '.self::INSTALLATION_TABLE.' i '
            .'LEFT JOIN '.self::DOMAIN_TABLE.' d ON d.id = i.pid '
            .'ORDER BY d.domain, i.sorting, i.id'
        );

        $installations = [];
        $proEnabled = false;
        $availableCount = 0;
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
                'environment' => $this->normalizeEnvironment((string) ($row['environment'] ?? '')),
                'system_id' => strtolower(trim((string) ($row['system_id'] ?? ''))),
                'contao_version' => trim((string) ($row['contao_version'] ?? '')),
                'php_version' => trim((string) ($row['php_version'] ?? '')),
                'last_sync' => (int) ($row['last_sync'] ?? 0),
                'status' => trim((string) ($row['status'] ?? '')),
            ];
            $installation['environment_label'] = $this->environmentLabel($installation['environment']);

            $frontendEvent = new InstallationFrontendExtensionEvent($installation);
            $this->eventDispatcher->dispatch($frontendEvent);
            $actions = $frontendEvent->getActions();

            $hasCheckAction = $this->hasAction($actions, 'update-check');
            $hasInstallAction = $this->hasAction($actions, 'update-install');
            $proEnabled = $proEnabled || $hasCheckAction;

            $updateState = $hasInstallAction ? 'available' : ($hasCheckAction ? 'unknown' : 'locked');

            if ($installation['id'] === $resultInstallationId && '' !== $checkStatus) {
                if ('ready' === $checkStatus) {
                    $updateState = 'available';
                } elseif ('up_to_date' === $checkStatus) {
                    $updateState = 'current';
                } elseif (in_array($checkStatus, ['blocked', 'error'], true)) {
                    $updateState = 'error';
                }
            }

            if ($installation['id'] === $resultInstallationId && '' !== $installStatus) {
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
            }

            $installation['frontend_actions'] = $actions;
            $installation['update_state'] = $updateState;
            $installation['update_state_label'] = $this->updateStateLabel($updateState);
            $installations[] = $installation;
        }

        $template->set('installations', $installations);
        $template->set('pro_enabled', $proEnabled);
        $template->set('available_count', $availableCount);
        $template->set('result_installation_id', $resultInstallationId);
        $template->set('check_status', $checkStatus);
        $template->set('install_status', $installStatus);
        $template->set('result_message', $this->resultMessage($checkStatus, $installStatus));

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
            'error' => 'Prüfung fehlgeschlagen',
            'warning' => 'Installiert · Synchronisation prüfen',
            'locked' => 'Pro-Funktion',
            default => 'Noch nicht geprüft',
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
