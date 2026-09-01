<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\Backend;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Connection\InstallationConnectionTester;
use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;
use Lebensbaum\ContaoDomainManagerBundle\Security\BackendPermissionChecker;
use Lebensbaum\ContaoDomainManagerBundle\Security\DomainManagerPermissions;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route(
    path: '%contao.backend.route_prefix%/domain-manager/installation/{id}/connection',
    name: 'contao_domain_manager_installation_connection',
    requirements: ['id' => '\\d+'],
    defaults: ['_scope' => 'backend'],
    methods: ['GET', 'POST'],
)]
final class InstallationConnectionController extends AbstractBackendController
{
    private const SYSTEM_INFO_PATH = '/_domainverwaltung/systeminfo';

    public function __construct(
        private readonly Connection $connection,
        private readonly InstallationConnectionTester $connectionTester,
        private readonly SecretStore $secretStore,
        private readonly BackendPermissionChecker $permissionChecker,
        #[Autowire(param: 'contao.backend.route_prefix')]
        private readonly string $backendRoutePrefix,
    ) {
    }

    public function __invoke(int $id, Request $request): Response
    {
        $canTest = $this->permissionChecker->isGranted(DomainManagerPermissions::TEST_CONNECTIONS);
        $canManageSecret = $this->permissionChecker->isGranted(DomainManagerPermissions::MANAGE_SECRETS);

        if (!$this->permissionChecker->canAccessDomainsModule() || (!$canTest && !$canManageSecret)) {
            throw new AccessDeniedHttpException('Keine Berechtigung für die System-Info-Verbindung.');
        }

        $record = $this->loadRecord($id);

        if (null === $record) {
            throw new NotFoundHttpException('Installation nicht gefunden.');
        }

        $returnUrl = $this->resolveReturnUrl($request, $id, (int) $record['pid']);

        if ($request->isMethod('POST')) {
            $action = trim((string) $request->request->get('dm_action', ''));
            $feedback = [
                'success' => null,
                'error' => null,
                'technical_details' => null,
            ];

            if ('test' === $action) {
                if (!$canTest) {
                    throw new AccessDeniedHttpException('Keine Berechtigung zum Ausführen von Verbindungstests.');
                }

                try {
                    $result = $this->connectionTester->test($id);
                    $feedback['success'] = sprintf(
                        'Verbindung vollständig geprüft – 5 von 5 Stufen erfolgreich. „%s“: Contao %s, PHP %s.',
                        $result['domain'],
                        $result['contao_version'] ?? 'nicht ermittelbar',
                        $result['php_version']
                    );
                } catch (SystemInfoConnectionException $exception) {
                    $feedback['error'] = $exception->getMessage();

                    if (null !== $exception->getTechnicalDetails() && '' !== trim($exception->getTechnicalDetails())) {
                        $feedback['technical_details'] = $exception->getTechnicalDetails();
                    }
                } catch (Throwable $exception) {
                    $feedback['error'] = $exception->getMessage();
                }
            } elseif ('replace_secret' === $action) {
                if (!$canManageSecret) {
                    throw new AccessDeniedHttpException('Keine Berechtigung zum Ersetzen des Secrets.');
                }

                $secret = trim((string) $request->request->get('dm_secret_plaintext', ''));

                if ('' === $secret) {
                    $feedback['error'] = 'Bitte ein 64-stelliges Secret eingeben.';
                } else {
                    try {
                        $this->secretStore->storeSecretForInstallation($id, $secret);
                        $feedback['success'] = 'Das Secret wurde verschlüsselt gespeichert.';
                    } catch (Throwable $exception) {
                        $feedback['error'] = $exception->getMessage();
                    }
                }
            } else {
                $feedback['error'] = 'Unbekannte Aktion.';
            }

            $request->getSession()->set($this->feedbackSessionKey($id), $feedback);

            return new RedirectResponse(
                $request->getUriForPath($request->getPathInfo()),
                Response::HTTP_SEE_OTHER
            );
        }

        $feedback = $request->getSession()->remove($this->feedbackSessionKey($id));

        if (!is_array($feedback)) {
            $feedback = [];
        }

        $success = isset($feedback['success']) && is_string($feedback['success']) ? $feedback['success'] : null;
        $error = isset($feedback['error']) && is_string($feedback['error']) ? $feedback['error'] : null;
        $technicalDetails = isset($feedback['technical_details']) && is_string($feedback['technical_details'])
            ? $feedback['technical_details']
            : null;

        return $this->render('@ContaoDomainManager/backend/installation_connection.html.twig', [
            'title' => 'System-Info-Verbindung',
            'headline' => 'System-Info-Verbindung',
            'record' => $this->normalizeRecord($record),
            'can_test' => $canTest,
            'can_manage_secret' => $canManageSecret,
            'success' => $success,
            'connection_error' => $error,
            'technical_details' => $technicalDetails,
            'return_url' => $returnUrl,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function loadRecord(int $id): ?array
    {
        $record = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT
                    i.id,
                    i.pid,
                    i.domain,
                    i.system_id,
                    i.dm_encrypted_secret,
                    i.dm_secret_changed_at,
                    i.dm_connection_status,
                    i.dm_connection_stage,
                    i.dm_connection_error_code,
                    i.dm_connection_http_status,
                    i.dm_connection_message,
                    i.dm_last_connection_test,
                    i.dm_last_connection_success,
                    d.domain AS parent_domain
                FROM tl_domain_manager_installation i
                LEFT JOIN tl_domain_manager_domain d ON d.id = i.pid
                WHERE i.id = ?
                LIMIT 1
            SQL,
            [$id]
        );

        return false === $record ? null : $record;
    }

    /** @param array<string, mixed> $record
     *  @return array<string, mixed>
     */
    private function normalizeRecord(array $record): array
    {
        $status = trim((string) ($record['dm_connection_status'] ?? ''));
        $stage = trim((string) ($record['dm_connection_stage'] ?? ''));
        $errorCode = trim((string) ($record['dm_connection_error_code'] ?? ''));
        $httpStatus = (int) ($record['dm_connection_http_status'] ?? 0);
        $domain = trim((string) ($record['domain'] ?? ''));
        [$stageLabel, $stageStep] = $this->describeStage($stage);

        return [
            'id' => (int) ($record['id'] ?? 0),
            'domain' => $domain,
            'parent_domain' => trim((string) ($record['parent_domain'] ?? '')),
            'system_id' => trim((string) ($record['system_id'] ?? '')),
            'has_secret' => '' !== trim((string) ($record['dm_encrypted_secret'] ?? '')),
            'status' => $status,
            'status_label' => match ($status) {
                'success' => 'Erfolgreich',
                'error' => 'Fehler',
                'untested' => 'Noch nicht erneut getestet',
                'not_configured' => 'Nicht konfiguriert',
                default => 'Noch nicht getestet',
            },
            'stage' => $stage,
            'stage_label' => $stageLabel,
            'stage_step' => $stageStep,
            'error_code' => $errorCode,
            'http_status' => $httpStatus > 0 ? $httpStatus : null,
            'diagnosis_hint' => $this->diagnosisHint($errorCode),
            'endpoint_url' => $this->createEndpointUrlForDisplay($domain),
            'message' => trim((string) ($record['dm_connection_message'] ?? '')),
            'last_test' => $this->formatTimestamp((int) ($record['dm_last_connection_test'] ?? 0)),
            'last_success' => $this->formatTimestamp((int) ($record['dm_last_connection_success'] ?? 0)),
            'secret_changed' => $this->formatTimestamp((int) ($record['dm_secret_changed_at'] ?? 0)),
        ];
    }

    /** @return array{0:string,1:?int} */
    private function describeStage(string $stage): array
    {
        return match ($stage) {
            'configuration' => ['Konfiguration', 1],
            'transport' => ['Zielserver erreichen', 2],
            'endpoint' => ['System-Info-Endpunkt', 3],
            'authentication' => ['Authentifizierung', 4],
            'response' => ['API-Antwort', 4],
            'system_data' => ['Systemdaten', 5],
            'unknown' => ['Unbekannter Fehler', null],
            default => ['Noch nicht geprüft', null],
        };
    }

    private function diagnosisHint(string $errorCode): string
    {
        return match ($errorCode) {
            'target_unreachable' => 'Domain/DNS, TLS-Zertifikat und Server-Erreichbarkeit prüfen.',
            'endpoint_not_found' => 'Prüfen, ob das System-Info-Bundle auf der Zielinstallation installiert und aktiv ist.',
            'remote_service_not_configured' => 'System Info auf der Zielinstallation öffnen und Zugangsdaten/Konfiguration prüfen.',
            'unauthorized' => 'Installations-ID und Secret auf beiden Seiten vergleichen.',
            'clock_skew' => 'Serverzeit bzw. Zeitsynchronisation der beiden Systeme prüfen.',
            'remote_server_error', 'remote_system_info_unavailable' => 'Zuerst die PHP-Version der betroffenen Domain/Subdomain prüfen, danach Contao- und Server-Fehlerprotokolle.',
            'invalid_json', 'incomplete_response', 'invalid_optional_field' => 'System-Info-Endpunkt und installierte Bundle-Version prüfen.',
            'unsupported_api_version' => 'Core/Free und System-Info-Bundle auf kompatible Versionen aktualisieren.',
            'installation_id_mismatch' => 'Installations-ID und Zuordnung der Zielinstallation prüfen.',
            default => '',
        };
    }

    private function createEndpointUrlForDisplay(string $domain): string
    {
        if ('' === $domain) {
            return '';
        }

        $baseUrl = str_contains($domain, '://') ? $domain : 'https://'.$domain;
        $parts = parse_url($baseUrl);

        if (false === $parts || empty($parts['host'])) {
            return '';
        }

        $url = 'https://'.$parts['host'];

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        return $url.self::SYSTEM_INFO_PATH;
    }

    private function formatTimestamp(int $timestamp): string
    {
        return $timestamp > 0 ? date('d.m.Y, H:i', $timestamp).' Uhr' : '–';
    }

    private function feedbackSessionKey(int $id): string
    {
        return 'domain_manager_connection_feedback_'.$id;
    }

    private function resolveReturnUrl(Request $request, int $id, int $pid): string
    {
        $sessionKey = 'domain_manager_connection_return_'.$id;

        if ($request->isMethod('GET')) {
            $referer = $request->headers->get('referer');

            if (
                is_string($referer)
                && $this->isSafeBackendReferer($referer, $request)
                && !str_contains($referer, '/domain-manager/installation/')
            ) {
                $request->getSession()->set($sessionKey, $referer);
            }
        }

        $stored = $request->getSession()->get($sessionKey);

        if (is_string($stored) && '' !== $stored) {
            return $stored;
        }

        return $this->backendRoutePrefix.'?do=domain_manager_domains&table=tl_domain_manager_installation&id='.$pid;
    }

    private function isSafeBackendReferer(string $referer, Request $request): bool
    {
        $parts = parse_url($referer);

        if (false === $parts || !isset($parts['host']) || 0 !== strcasecmp((string) $parts['host'], $request->getHost())) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '');

        return str_starts_with($path, rtrim($this->backendRoutePrefix, '/').'/')
            || $path === rtrim($this->backendRoutePrefix, '/');
    }
}
