<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Lebensbaum\ContaoDomainManagerBundle\Security\BackendPermissionChecker;
use Lebensbaum\ContaoDomainManagerBundle\Security\DomainManagerPermissions;

/**
 * Applies Domain Manager permissions as soon as the DCA is loaded.
 *
 * This deliberately happens before DC_Table is instantiated. In particular,
 * DC_Table processes the POST request of the "select / multiple edit" action
 * before its onload_callback is executed. Therefore read-only restrictions
 * must already be present at DCA-load time.
 */
#[AsHook('loadDataContainer', priority: 300)]
final class DomainManagerDcaPermissionsListener
{
    private const DOMAIN_TABLE = 'tl_domain_manager_domain';
    private const INSTALLATION_TABLE = 'tl_domain_manager_installation';
    private const SETTINGS_TABLE = 'tl_domain_manager_settings';

    public function __construct(
        private readonly BackendPermissionChecker $permissionChecker,
    ) {
    }

    public function __invoke(string $table): void
    {
        if (!\in_array($table, [self::DOMAIN_TABLE, self::INSTALLATION_TABLE, self::SETTINGS_TABLE], true)) {
            return;
        }

        if (self::SETTINGS_TABLE === $table) {
            if (!$this->permissionChecker->isGranted(DomainManagerPermissions::EDIT_SETTINGS)) {
                $this->makeSettingsReadOnly($table);
            }

            return;
        }

        if (!$this->permissionChecker->isGranted(DomainManagerPermissions::EDIT_RECORDS)) {
            $this->makeReadOnly($table);
        }

        if (self::INSTALLATION_TABLE === $table) {
            $canUseConnectionAction = $this->permissionChecker->isGranted(DomainManagerPermissions::TEST_CONNECTIONS)
                || $this->permissionChecker->isGranted(DomainManagerPermissions::MANAGE_SECRETS);

            if (!$canUseConnectionAction) {
                unset($GLOBALS['TL_DCA'][$table]['list']['operations']['connection']);
            }
        }
    }

    private function makeSettingsReadOnly(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['config']['notEditable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notCreatable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notCopyable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notDeletable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notSortable'] = true;

        unset(
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['new'],
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['all'],
            $GLOBALS['TL_DCA'][$table]['list']['operations']['edit'],
        );
    }

    private function makeReadOnly(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['config']['notEditable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notCreatable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notCopyable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notDeletable'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['notSortable'] = true;

        unset(
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['dm_new'],
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['dm_all'],
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['all'],
        );

        if (self::DOMAIN_TABLE === $table) {
            unset(
                $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_edit'],
                $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_copy'],
                $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_delete'],
            );

            return;
        }

        unset(
            $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_edit'],
            $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_copy'],
            $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_cut'],
            $GLOBALS['TL_DCA'][$table]['list']['operations']['dm_delete'],
        );
    }
}
