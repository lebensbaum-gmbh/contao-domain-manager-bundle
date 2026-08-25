<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['access_legend'] = 'Front end access';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['health_legend'] = 'Status & warnings';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['links_legend'] = 'External services';

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['sync_member_groups'] = [
    'Allowed member groups',
    'Only authenticated front end members in at least one of these groups may run system data synchronization. With no group selected, front end synchronization is disabled.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['stale_sync_days'] = [
    'Synchronization warning after days',
    'Show a warning when the last successful synchronization is older than this number of days. Default: 30 days.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['trakked_url'] = [
    'Trakked URL (legacy)',
    'Legacy field from version 1.4; migrated automatically to an external service.',
];

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['edit'] = ['Edit', 'Edit global Domain Manager settings.'];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['show'] = ['Details', 'Show global Domain Manager settings.'];
