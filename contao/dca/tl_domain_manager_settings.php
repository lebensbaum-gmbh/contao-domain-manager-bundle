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
        'default' => '{access_legend},sync_member_groups;{health_legend},stale_sync_days',
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

        // Compatibility storage for automatic synchronization settings introduced in
        // v1.5. These fields intentionally remain hidden in Free so existing data is
        // preserved and can later be adopted by the Pro extension without a destructive
        // database migration.
        'auto_sync_enabled' => [
            'sql' => "char(1) NOT NULL default ''",
        ],
        'auto_sync_interval' => [
            'sql' => "int(10) unsigned NOT NULL default 6",
        ],
        'auto_sync_last_attempt' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'auto_sync_last_success' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'auto_sync_status' => [
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'auto_sync_message' => [
            'sql' => 'text NULL',
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
