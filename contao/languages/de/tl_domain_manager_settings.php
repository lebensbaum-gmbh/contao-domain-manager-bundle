<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['automatic_sync_legend'] = 'Automatische Synchronisation';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['access_legend'] = 'Frontend-Zugriff';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['health_legend'] = 'Status & Warnungen';
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['links_legend'] = 'Externe Dienste';

$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_enabled'] = [
    'Automatische Synchronisation aktivieren',
    'Aktualisiert die Systemdaten der hinterlegten Installationen automatisch über Contaos Cronjob-Framework. Für eine zuverlässige Ausführung ist ein echter CLI-Cronjob mit contao:cron erforderlich.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_interval'] = [
    'Synchronisationsintervall',
    'Zeitabstand zwischen zwei automatischen Synchronisationsversuchen.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_intervals'] = [
    1 => 'Stündlich',
    6 => 'Alle 6 Stunden',
    12 => 'Alle 12 Stunden',
    24 => 'Täglich',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_last_attempt'] = [
    'Letzter automatischer Versuch',
    'Zeitpunkt des letzten gestarteten automatischen Synchronisationslaufs.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_last_success'] = [
    'Letzte erfolgreiche automatische Synchronisation',
    'Zeitpunkt des letzten vollständig erfolgreichen automatischen Synchronisationslaufs.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_status'] = [
    'Status der automatischen Synchronisation',
    'Ergebnis des letzten automatischen Synchronisationslaufs.',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_statuses'] = [
    'success' => 'Erfolgreich',
    'partial' => 'Teilweise erfolgreich',
    'error' => 'Fehler',
];
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['auto_sync_message'] = [
    'Meldung der automatischen Synchronisation',
    'Zusammenfassung des letzten automatischen Synchronisationslaufs.',
];
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
