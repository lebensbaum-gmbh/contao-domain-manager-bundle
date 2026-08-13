<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_domain_manager_settings'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'notCopyable' => true,
        'notDeletable' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
        ],
        'label' => [
            'fields' => ['id'],
            'format' => 'Globale Einstellungen',
        ],
        'global_operations' => [],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{access_legend},sync_member_groups;{links_legend},trakked_url',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sync_member_groups' => [
            'exclude' => false,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => [
                'multiple' => true,
                'tl_class' => 'clr',
            ],
            'sql' => 'blob NULL',
        ],
        'trakked_url' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 1024,
                'rgxp' => 'url',
                'decodeEntities' => true,
                'tl_class' => 'clr long',
            ],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
    ],
];

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_settings']['fields'] as $fieldName => &$fieldConfig) {
    if (!isset($fieldConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_settings'][$fieldName])) {
        $fieldConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_settings'][$fieldName];
    }
}
unset($fieldConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_settings']['list']['operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_settings'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_settings'][$operationName];
    }
}
unset($operationConfig);
