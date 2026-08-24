<?php

declare(strict_types=1);

use Contao\DC_Table;
use Lebensbaum\ContaoDomainManagerBundle\Dca\InstallationCallbacks;

$GLOBALS['TL_DCA']['tl_domain_manager_installation'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_domain_manager_domain',
        'enableVersioning' => true,
        'onsubmit_callback' => [[InstallationCallbacks::class, 'handleSubmit']],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'system_id' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['domain', 'dm_live_status'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['domain', 'contao_version', 'php_version', 'is_live'],
            'format' => '%s — Contao %s / PHP %s %s',
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
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
            ],
            'dm_cut' => [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
            ],
            'dm_delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'Wirklich löschen?\'))return false;Backend.getScrollOffset()"',
            ],
            'connection' => [
                'route' => 'contao_domain_manager_installation_connection',
                'icon' => 'show.svg',
                'primary' => true,
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{installation_legend},domain,environment;{technical_legend},system_id,document_root,contao_version,php_version,database_name;{links_legend},backend_url,manager_url;{status_legend},is_live,external_services,status,notes;{sync_legend},last_sync,sync_status,sync_message;{connection_legend},dm_connection_panel,dm_secret_editor,dm_connection_status,dm_last_connection_test,dm_last_connection_success,dm_secret_changed_at,dm_connection_message',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_domain_manager_domain.domain',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'domain' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'environment' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'system_id' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 32, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'document_root' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 1024, 'tl_class' => 'w50'],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
        'contao_version' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'php_version' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'database_name' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'backend_url' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 1024, 'tl_class' => 'w50'],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
        'manager_url' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 1024, 'tl_class' => 'w50'],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
        'is_live' => [
            'exclude' => false,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'external_services' => [
            'exclude' => false,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_domain_manager_external_service.name',
            'eval' => [
                'multiple' => true,
                'tl_class' => 'clr',
            ],
            'sql' => 'blob NULL',
        ],
        // Legacy field retained temporarily so existing v1.4 data can be migrated safely.
        'trakked' => [
            'sql' => "char(1) NOT NULL default ''",
        ],
        'status' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'notes' => [
            'exclude' => false,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'last_sync' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sync_status' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'sync_message' => [
            'exclude' => false,
            'inputType' => 'textarea',
            'eval' => ['readonly' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'dm_connection_panel' => [
            'exclude' => false,
            'input_field_callback' => [InstallationCallbacks::class, 'renderConnectionPanel'],
            'eval' => ['tl_class' => 'clr'],
        ],
        'dm_secret_editor' => [
            'exclude' => false,
            'input_field_callback' => [InstallationCallbacks::class, 'renderSecretEditor'],
            'eval' => ['tl_class' => 'clr'],
        ],
        'dm_encrypted_secret' => [
            'sql' => 'text NULL',
        ],
        'dm_secret_changed_at' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'dm_connection_status' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'dm_connection_message' => [
            'exclude' => false,
            'inputType' => 'textarea',
            'eval' => ['readonly' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'dm_last_connection_test' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'dm_last_connection_success' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
    ],
];

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_installation']['fields'] as $fieldName => &$fieldConfig) {
    if (!isset($fieldConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_installation'][$fieldName])) {
        $fieldConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation'][$fieldName];
    }
}
unset($fieldConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_installation']['list']['global_operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_installation'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation'][$operationName];
    }
}
unset($operationConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_installation']['list']['operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_installation'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation'][$operationName];
    }
}
unset($operationConfig);