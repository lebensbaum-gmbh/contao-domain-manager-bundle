<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['automatic_sync_legend'] = 'Automatic synchronization';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['access_legend'] = 'Front end access';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['health_legend'] = 'Status & warnings';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['links_legend'] = 'External services';

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_enabled'] = [
    'Enable automatic synchronization',
    'Automatically updates system data of configured installations through Contao\'s cron framework. A real CLI cron job running contao:cron is required for reliable execution.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_interval'] = [
    'Synchronization interval',
    'Time between two automatic synchronization attempts.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_intervals'] = [
    1 => 'Hourly',
    6 => 'Every 6 hours',
    12 => 'Every 12 hours',
    24 => 'Daily',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_last_attempt'] = [
    'Last automatic attempt',
    'Time of the last started automatic synchronization run.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_last_success'] = [
    'Last successful automatic synchronization',
    'Time of the last completely successful automatic synchronization run.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_status'] = [
    'Automatic synchronization status',
    'Result of the last automatic synchronization run.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_message'] = [
    'Automatic synchronization message',
    'Summary of the last automatic synchronization run.',
];
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
