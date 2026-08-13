<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Lebensbaum\ContaoDomainManagerBundle\Security\DomainManagerPermissions;

$GLOBALS['TL_DCA']['tl_user_group']['fields'][DomainManagerPermissions::FIELD] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => [
        DomainManagerPermissions::EDIT_RECORDS,
        DomainManagerPermissions::TEST_CONNECTIONS,
        DomainManagerPermissions::MANAGE_SECRETS,
        DomainManagerPermissions::EDIT_SETTINGS,
    ],
    'reference' => &$GLOBALS['TL_LANG']['tl_user_group']['domain_manager_permissions_options'],
    'eval' => ['multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

PaletteManipulator::create()
    ->addLegend('domain_manager_legend', 'amg_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(DomainManagerPermissions::FIELD, 'domain_manager_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;
