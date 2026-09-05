<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['access_legend'] = 'Frontend-Zugriff';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['health_legend'] = 'Status & Warnungen';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['links_legend'] = 'Externe Dienste';

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['sync_member_groups'] = [
    'Berechtigte Mitgliedergruppen',
    'Nur angemeldete Frontend-Mitglieder aus mindestens einer dieser Gruppen dürfen „Systemdaten aktualisieren“ ausführen. Ohne Auswahl ist die Synchronisation im Frontend deaktiviert.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['stale_sync_days'] = [
    'Synchronisationswarnung nach Tagen',
    'Zeigt einen Hinweis an, wenn die letzte erfolgreiche Synchronisation länger als diese Anzahl von Tagen zurückliegt. Standard: 30 Tage.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['trakked_url'] = [
    'Trakked-URL (Legacy)',
    'Altes Feld aus Version 1.4; wird bei der Migration automatisch in einen externen Dienst übernommen.',
];

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['edit'] = ['Bearbeiten', 'Globale Domain-Manager-Einstellungen bearbeiten.'];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['show'] = ['Details', 'Globale Domain-Manager-Einstellungen anzeigen.'];
