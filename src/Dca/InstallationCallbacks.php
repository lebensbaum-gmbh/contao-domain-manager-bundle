<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Dca;

use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Lebensbaum\ContaoDomainManagerBundle\Connection\InstallationConnectionTester;
use Lebensbaum\ContaoDomainManagerBundle\Security\BackendPermissionChecker;
use Lebensbaum\ContaoDomainManagerBundle\Security\DomainManagerPermissions;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use Throwable;

final class InstallationCallbacks
{
    private const TABLE = 'tl_domain_manager_installation';

    public function __construct(
        private readonly Connection $connection,
        private readonly InstallationConnectionTester $connectionTester,
        private readonly SecretStore $secretStore,
        private readonly BackendPermissionChecker $permissionChecker,
    ) {
    }

    public function renderConnectionPanel(DataContainer $dc): string
    {
        $record = $this->loadRecord((int) $dc->id);

        if (null === $record) {
            return '<div class="tl_help">Die Verbindungsdaten stehen nach dem ersten Speichern der Installation zur Verfügung.</div>';
        }

        $domain = trim((string) ($record['domain'] ?? ''));
        $systemId = trim((string) ($record['system_id'] ?? ''));
        $encryptedSecret = trim((string) ($record['dm_encrypted_secret'] ?? ''));
        $status = trim((string) ($record['dm_connection_status'] ?? ''));
        $message = trim((string) ($record['dm_connection_message'] ?? ''));
        $lastTest = (int) ($record['dm_last_connection_test'] ?? 0);
        $lastSuccess = (int) ($record['dm_last_connection_success'] ?? 0);
        $secretChanged = (int) ($record['dm_secret_changed_at'] ?? 0);
        $hasTestPermission = $this->permissionChecker->isGranted(DomainManagerPermissions::TEST_CONNECTIONS);
        $canTest = $hasTestPermission && '' !== $encryptedSecret && 1 === preg_match('/\A[a-f0-9]{32}\z/i', $systemId);

        $statusLabel = match ($status) {
            'success' => 'Erfolgreich',
            'error' => 'Fehler',
            'untested' => 'Noch nicht erneut getestet',
            'not_configured' => 'Nicht konfiguriert',
            default => 'Noch nicht getestet',
        };

        $rows = [
            ['Installation', '' !== $domain ? $this->escape($domain) : '–'],
            ['Installations-ID', '' !== $systemId ? '<code>'.$this->escape($systemId).'</code>' : '<strong>nicht hinterlegt</strong>'],
            ['Secret', '' !== $encryptedSecret ? 'verschlüsselt gespeichert' : '<strong>nicht gespeichert</strong>'],
            ['Verbindungsstatus', $this->escape($statusLabel)],
            ['Letzter Test', $this->formatTimestamp($lastTest)],
            ['Letzter erfolgreicher Test', $this->formatTimestamp($lastSuccess)],
            ['Secret zuletzt geändert', $this->formatTimestamp($secretChanged)],
        ];

        if ('' !== $message) {
            $rows[] = ['Meldung', nl2br($this->escape($message))];
        }

        $html = '<div class="dm-connection-panel"><table class="tl_listing" style="width:100%;max-width:900px;margin:0 0 10px"><tbody>';

        foreach ($rows as [$label, $value]) {
            $html .= '<tr><td style="width:210px;padding:4px 10px 4px 0"><strong>'.$this->escape($label).'</strong></td><td style="padding:4px 0">'.$value.'</td></tr>';
        }

        $html .= '</tbody></table>';

        if ($hasTestPermission) {
            $html .= '<button type="submit" name="dm_test_connection" value="1" class="tl_submit"'.($canTest ? '' : ' disabled').'>Verbindung testen</button>';

            if (!$canTest) {
                $html .= '<p class="tl_help">Für den Verbindungstest werden eine gültige Installations-ID und ein gespeichertes Secret benötigt.</p>';
            }
        } else {
            $html .= '<p class="tl_help">Keine Berechtigung zum Ausführen von Verbindungstests.</p>';
        }

        return $html.'</div>';
    }

    public function renderSecretEditor(DataContainer $dc): string
    {
        if (!$this->permissionChecker->isGranted(DomainManagerPermissions::MANAGE_SECRETS)) {
            return '<p class="tl_help">Keine Berechtigung zum Ersetzen des Secrets.</p>';
        }

        $record = $this->loadRecord((int) $dc->id);
        $hasSecret = null !== $record && '' !== trim((string) ($record['dm_encrypted_secret'] ?? ''));
        $hint = $hasSecret
            ? 'Es ist bereits ein Secret gespeichert. Das Feld leer lassen, um es unverändert zu behalten.'
            : 'Noch kein Secret gespeichert. Das 64-stellige Secret der überwachten Installation eintragen.';

        return sprintf(
            '<div class="widget"><input type="password" name="dm_secret_plaintext" id="ctrl_dm_secret_plaintext" class="tl_text" value="" maxlength="64" autocomplete="new-password" placeholder="64-stelliges Secret"><p class="tl_help">%s</p></div>',
            $this->escape($hint)
        );
    }

    public function handleSubmit(DataContainer $dc): void
    {
        $installationId = (int) $dc->id;

        if ($installationId < 1) {
            return;
        }

        $secret = trim((string) Input::post('dm_secret_plaintext'));
        $testRequested = '1' === (string) Input::post('dm_test_connection');

        if ('' !== $secret) {
            if (!$this->permissionChecker->isGranted(DomainManagerPermissions::MANAGE_SECRETS)) {
                Message::addError('Keine Berechtigung zum Ersetzen des Secrets.');

                return;
            }

            try {
                $this->secretStore->storeSecretForInstallation($installationId, $secret);
                Message::addConfirmation('Das Secret wurde verschlüsselt gespeichert.');
            } catch (Throwable $exception) {
                Message::addError($exception->getMessage());

                return;
            }
        }

        if (!$testRequested) {
            return;
        }

        if (!$this->permissionChecker->isGranted(DomainManagerPermissions::TEST_CONNECTIONS)) {
            Message::addError('Keine Berechtigung zum Ausführen von Verbindungstests.');

            return;
        }

        try {
            $result = $this->connectionTester->test($installationId);
            Message::addConfirmation(sprintf(
                'Verbindung zu „%s“ erfolgreich: Contao %s, PHP %s.',
                $result['domain'],
                $result['contao_version'] ?? 'nicht ermittelbar',
                $result['php_version']
            ));
        } catch (Throwable $exception) {
            Message::addError($exception->getMessage());
        }
    }

    /** @return array<string, mixed>|null */
    private function loadRecord(int $installationId): ?array
    {
        if ($installationId < 1) {
            return null;
        }

        try {
            $record = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT
                        id,
                        domain,
                        system_id,
                        dm_encrypted_secret,
                        dm_secret_changed_at,
                        dm_connection_status,
                        dm_connection_message,
                        dm_last_connection_test,
                        dm_last_connection_success
                    FROM tl_domain_manager_installation
                    WHERE id = ?
                    LIMIT 1
                SQL,
                [$installationId]
            );

            return false === $record ? null : $record;
        } catch (Throwable) {
            return null;
        }
    }

    private function formatTimestamp(int $timestamp): string
    {
        return $timestamp > 0 ? date('d.m.Y, H:i', $timestamp).' Uhr' : '–';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
