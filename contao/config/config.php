<?php

declare(strict_types=1);

$GLOBALS['TL_PERMISSIONS'][] = 'domain_manager_permissions';

$GLOBALS['BE_MOD']['domain_manager'] ??= [];
$GLOBALS['BE_MOD']['domain_manager']['domain_manager_domains'] = [
    'tables' => [
        'tl_domain_manager_domain',
        'tl_domain_manager_installation',
    ],
];

$GLOBALS['BE_MOD']['domain_manager']['domain_manager_settings'] = [
    'tables' => [
        'tl_domain_manager_settings',
    ],
];
