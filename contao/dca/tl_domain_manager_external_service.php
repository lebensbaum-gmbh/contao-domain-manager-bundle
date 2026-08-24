<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_domain_manager_external_service'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['name'],
            'flag' => 1,
            'panelLayout' => 'search,limit',
        ],
        'label' => [
            'fields' => ['name', 'url'],
            'format' => '%s — %s',
        ],
        'global_operations' => [
            'dm_new' => [
                'href' => 'act=create',
                'class' => 'header_new',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="n"',
            ],
        ],
        'operations' => [
            'dm_edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'dm_copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'dm_delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'Wirklich löschen?\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{service_legend},name,url',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'name' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'url' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 1024,
                'rgxp' => 'url',
                'decodeEntities' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
    ],
];

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_external_service']['fields'] as $fieldName => &$fieldConfig) {
    if (!isset($fieldConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$fieldName])) {
        $fieldConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$fieldName];
    }
}
unset($fieldConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_external_service']['list']['global_operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$operationName];
    }
}
unset($operationConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_external_service']['list']['operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_external_service'][$operationName];
    }
}
unset($operationConfig);
