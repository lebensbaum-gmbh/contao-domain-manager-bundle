<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_installation']['installation_legend'] = 'Installation';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['technical_legend'] = 'Technical data';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['links_legend'] = 'Direct links';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['status_legend'] = 'Status & notes';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_legend'] = 'Synchronization';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['connection_legend'] = 'System Info connection';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['domain'] = ['Domain / subdomain', 'Domain or subdomain where this installation is reachable.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['environment'] = ['Environment', 'Environment label, e.g. live, mirror or test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['system_id'] = ['Installation ID', '32 character System Info ID.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['document_root'] = ['DocumentRoot', 'DocumentRoot of this Contao installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['contao_version'] = ['Contao version', 'Last detected Contao version.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['php_version'] = ['PHP version', 'Last detected PHP version.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['database_name'] = ['Database', 'Database name.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['backend_url'] = ['Contao backend URL', 'Direct backend link.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['manager_url'] = ['Contao Manager URL', 'Direct Contao Manager link.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['is_live'] = ['Main domain points here', 'This installation is the currently detected live target.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['external_services'] = ['External services', 'Select optional external services associated with this installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['trakked'] = ['Trakked (legacy)', 'Legacy field from version 1.4; migrated automatically.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['status'] = ['Status', 'Optional internal status.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['notes'] = ['Notes', 'Internal notes.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['last_sync'] = ['Last successful synchronization', 'Timestamp of the last successful synchronization.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_status'] = ['Synchronization status', 'Status of the last synchronization.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_message'] = ['Synchronization message', 'Message of the last synchronization.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_panel'] = ['Connection status', 'Status of the protected connection to the monitored Contao installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_secret_editor'] = ['Secret', 'Enter a new secret; leave empty to keep the stored secret unchanged.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_secret_changed_at'] = ['Secret last changed', 'Timestamp of the last secret change.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_status'] = ['Connection status', 'Status of the last connection test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_message'] = ['Connection message', 'Message of the last connection test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_last_connection_test'] = ['Last test', 'Timestamp of the last connection test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_last_connection_success'] = ['Last successful test', 'Timestamp of the last successful connection test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['new'] = ['New installation', 'Create a new installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['edit'] = ['Edit', 'Edit installation ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['copy'] = ['Duplicate', 'Duplicate installation ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['cut'] = ['Move', 'Move installation ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['delete'] = ['Delete', 'Delete installation ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['show'] = ['Details', 'Show installation ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['connection'] = ['Connection', 'Check the system-info connection of installation ID %s.'];

// Domain-Manager-specific operation names bypass Contao's legacy table-right UI filtering.
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_new'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['new'] ?? ['dm_new', 'dm_new'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_edit'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['edit'] ?? ['dm_edit', 'dm_edit'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_copy'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['copy'] ?? ['dm_copy', 'dm_copy'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_cut'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['cut'] ?? ['dm_cut', 'dm_cut'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_delete'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['delete'] ?? ['dm_delete', 'dm_delete'];
