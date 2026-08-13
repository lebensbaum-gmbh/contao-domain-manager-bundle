<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_domain']['domain_legend'] = 'Main domain';
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['status_legend'] = 'Status & notes';
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['live_legend'] = 'Live detection';
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['domain'] = ['Domain', 'Public main domain without path.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['title'] = ['Label', 'Optional internal label.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['thumbnail'] = ['Screenshot / thumbnail', 'Preview image of the main domain for the front end overview.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['status'] = ['Status', 'Optional status.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['notes'] = ['Notes', 'Internal notes.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_status'] = ['Live status', 'Result of the last live detection.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_last_check'] = ['Last live check', 'Timestamp of the last live detection.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_installation_id'] = ['Detected installation', 'Record ID of the detected live installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_error'] = ['Live error', 'Error from the last live detection.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['new'] = ['New main domain', 'Create a new main domain.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['children'] = ['Installations', 'Edit installations of this main domain.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['edit'] = ['Edit', 'Edit main domain ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['copy'] = ['Duplicate', 'Duplicate main domain ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['delete'] = ['Delete', 'Delete main domain ID %s.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['show'] = ['Details', 'Show details of main domain ID %s.'];

// Domain-Manager-specific operation names bypass Contao's legacy table-right UI filtering.
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_new'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['new'] ?? ['dm_new', 'dm_new'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_edit'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['edit'] ?? ['dm_edit', 'dm_edit'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_copy'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['copy'] ?? ['dm_copy', 'dm_copy'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_delete'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['delete'] ?? ['dm_delete', 'dm_delete'];
