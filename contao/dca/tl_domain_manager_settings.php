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
        'default' => '{automatic_sync_legend},auto_sync_enabled,auto_sync_interval,auto_sync_last_attempt,auto_sync_last_success,auto_sync_status,auto_sync_message;{access_legend},sync_member_groups;{health_legend},stale_sync_days',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'auto_sync_enabled' => [
            'exclude' => false,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
            ],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'auto_sync_interval' => [
            'exclude' => false,
            'inputType' => 'select',
            'options' => [1, 6, 12, 24],
            'reference' => &$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_intervals'],
            'default' => 6,
            'eval' => [
                'tl_class' => 'w50',
            ],
            'sql' => "int(10) unsigned NOT NULL default 6",
        ],
        'auto_sync_last_attempt' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'readonly' => true,
                'rgxp' => 'datim',
                'tl_class' => 'w50',
            ],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'auto_sync_last_success' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'readonly' => true,
                'rgxp' => 'datim',
                'tl_class' => 'w50',
            ],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'auto_sync_status' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => [
                'readonly' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'auto_sync_message' => [
            'exclude' => false,
            'inputType' => 'textarea',
            'eval' => [
                'readonly' => true,
                'tl_class' => 'clr',
            ],
            'sql' => 'text NULL',
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
        'stale_sync_days' => [
            'exclude' => false,
            'inputType' => 'text',
            'default' => 30,
            'eval' => [
                'rgxp' => 'digit',
                'minval' => 1,
                'maxval' => 3650,
                'tl_class' => 'w50',
            ],
            'sql' => "int(10) unsigned NOT NULL default 30",
        ],
        // Legacy field retained temporarily so existing v1.4 data can be migrated safely.
        'trakked_url' => [
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
