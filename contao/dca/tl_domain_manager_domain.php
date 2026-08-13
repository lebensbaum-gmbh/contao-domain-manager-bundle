<?php

declare(strict_types=1);

use Contao\DC_Table;
$GLOBALS['TL_DCA']['tl_domain_manager_domain'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_domain_manager_installation'],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'domain' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['domain'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['domain', 'dm_live_status'],
            'format' => '%s <span style="color:#999;padding-left:8px">[%s]</span>',
        ],
        'global_operations' => [
            'dm_new' => [
                'href' => 'act=create',
                'class' => 'header_new',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="n"',
            ],
        ],
        'operations' => [
            'children' => [
                'href' => 'table=tl_domain_manager_installation',
                'icon' => 'children.svg',
            ],
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
        'default' => '{domain_legend},domain,title,thumbnail;{status_legend},status,notes;{live_legend},dm_live_status,dm_live_last_check,dm_live_installation_id,dm_live_error',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
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
        'title' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'thumbnail' => [
            'exclude' => false,
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'fieldType' => 'radio',
                'extensions' => 'jpg,jpeg,png,webp,gif',
                'tl_class' => 'clr',
            ],
            'sql' => 'binary(16) NULL',
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
        'dm_live_status' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'dm_live_last_check' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'dm_live_installation_id' => [
            'exclude' => false,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'dm_live_error' => [
            'exclude' => false,
            'inputType' => 'textarea',
            'eval' => ['readonly' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
    ],
];

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_domain']['fields'] as $fieldName => &$fieldConfig) {
    if (!isset($fieldConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_domain'][$fieldName])) {
        $fieldConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain'][$fieldName];
    }
}
unset($fieldConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_domain']['list']['global_operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_domain'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain'][$operationName];
    }
}
unset($operationConfig);

foreach ($GLOBALS['TL_DCA']['tl_domain_manager_domain']['list']['operations'] as $operationName => &$operationConfig) {
    if (!isset($operationConfig['label']) && isset($GLOBALS['TL_LANG']['tl_domain_manager_domain'][$operationName])) {
        $operationConfig['label'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain'][$operationName];
    }
}
unset($operationConfig);
