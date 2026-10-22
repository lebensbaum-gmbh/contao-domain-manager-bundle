<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Assets;

use PHPUnit\Framework\TestCase;

final class DomainManagerBackupWorkspaceTest extends TestCase
{
    public function testBackupHistoryAndRestoreRequireExplicitConfirmation(): void
    {
        $template = file_get_contents(__DIR__.'/../../contao/templates/content_element/domain_manager_updates.html.twig');

        self::assertIsString($template);
        self::assertStringContainsString('data-dm-update-backups', $template);
        self::assertStringContainsString('backup_workspace.create_url', $template);
        self::assertStringContainsString('backup.restore_url', $template);
        self::assertStringContainsString('backup.delete_url', $template);
        self::assertStringContainsString('backup_workspace.total_size', $template);
        self::assertStringContainsString('backup_workspace.previous_url', $template);
        self::assertStringContainsString('backup_workspace.next_url', $template);
        self::assertStringContainsString('backup_workspace.page_first', $template);
        self::assertStringContainsString('backup_workspace.page_last', $template);
        self::assertStringContainsString('backup_workspace.is_selected', $template);
        self::assertStringContainsString('name="confirm_delete" value="1" required', $template);
        self::assertStringContainsString('name="confirm_domain" required', $template);
        self::assertStringContainsString('name="acknowledge_restore" value="1" required', $template);
        self::assertStringContainsString('name="maintend_csrf"', $template);
        self::assertStringContainsString('name="REQUEST_TOKEN"', $template);
        self::assertStringContainsString('Der normale Restore benötigt eine startfähige Contao-Installation.', $template);
    }

    public function testBackupProgressIsShownImmediatelyAndUpdatedFromConfirmedResponse(): void
    {
        $template = file_get_contents(__DIR__.'/../../contao/templates/content_element/domain_manager_updates.html.twig');
        $script = file_get_contents(__DIR__.'/../../public/js/domain-manager-updates.js');
        $styles = file_get_contents(__DIR__.'/../../public/css/domain-manager-updates.css');

        self::assertIsString($template);
        self::assertIsString($script);
        self::assertIsString($styles);
        self::assertStringContainsString('data-dm-backup-progress', $template);
        self::assertStringContainsString('data-dm-backup-result', $template);
        self::assertStringContainsString('dm-backup-restore-form', $template);
        self::assertStringContainsString('void restoreBackup(form, row)', $script);
        self::assertStringContainsString('data-dm-restore-progress-url', $template);
        self::assertStringContainsString('remoteStatusUrl', $script);
        self::assertStringContainsString('pollRestore()', $script);
        self::assertStringContainsString('restoreRunning = statusUnknown', $script);
        self::assertStringContainsString("responseUrl.searchParams.get('dm_backup_create')", $script);
        self::assertStringContainsString("resultUrl.searchParams.get('dm_backup_restore')", $script);
        self::assertStringContainsString("['success', 'success_sync_warning', 'error', 'confirmation_invalid']", $script);
        self::assertStringContainsString("event.returnValue = '';", $script);
        self::assertStringContainsString('Der Wiederherstellungsstatus ist unklar.', $script);
        self::assertStringContainsString('Keine Updates, Sicherungen oder andere Aktionen starten', $script);
        self::assertStringContainsString('window.location.assign(resultUrl.href)', $script);
        self::assertStringContainsString('.dm-backup-progress.is-running.is-restore', $styles);
        self::assertStringContainsString("backup_workspace.backup_count == 1 ? 'Sicherung' : 'Sicherungen'", $template);
        self::assertStringContainsString("button.textContent = 'Sicherung läuft …';", $script);
        self::assertStringContainsString("button.textContent = 'Sicherung wird gelöscht …';", $script);
        self::assertStringContainsString("backupRunning = true;", $script);
        self::assertStringContainsString("button.disabled = true;", $script);
        self::assertStringContainsString("window.clearInterval(timer);", $script);
        self::assertStringContainsString("progressElement.classList.add('is-running')", $script);
        self::assertStringContainsString("responseUrl.searchParams.get('dm_backup_create')", $script);
        self::assertStringContainsString("resultId !== installationId", $script);
        self::assertStringContainsString("panel.replaceWith(replacement)", $script);
        self::assertStringContainsString('Der Sicherungsstatus ist unklar.', $script);
        self::assertStringContainsString('.dm-backup-progress.is-running', $styles);
        self::assertStringContainsString('prefers-reduced-motion: reduce', $styles);
    }

    public function testControllerDistinguishesConfirmationFromRecoveryFailure(): void
    {
        $class = new \ReflectionClass(
            \Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement\DomainManagerUpdatesController::class
        );
        $controller = $class->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'backupResultMessage');

        self::assertStringContainsString('nicht gestartet', $method->invoke($controller, '', 'confirmation_invalid', ''));
        self::assertStringContainsString('Zielinstallation gelöscht', $method->invoke($controller, '', '', 'success'));
        self::assertStringContainsString('nicht gelöscht', $method->invoke($controller, '', '', 'confirmation_invalid'));
        self::assertStringContainsString('gerade erstellt', $method->invoke($controller, '', '', 'backup_running'));
        self::assertStringContainsString('für eine Wiederherstellung verwendet', $method->invoke($controller, '', '', 'backup_in_use'));
        self::assertStringContainsString('Wartungsaktion', $method->invoke($controller, '', '', 'maintenance_busy'));
        self::assertSame('', $method->invoke($controller, '', '', 'unknown_private_detail'));
        self::assertStringContainsString('möglicherweise unvollständig', $method->invoke($controller, '', 'error', ''));
        self::assertStringContainsString('Sicherung erstellt', $method->invoke($controller, 'success', '', ''));
    }
}
