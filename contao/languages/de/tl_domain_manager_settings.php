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
$GLOBALS['TL_LANG']['tl_domain_manager_settings']['cron_panel'] = [
    'title' => 'Server-Cronjob',
    'description' => 'Für die zuverlässige automatische Synchronisation muss das Contao-Cronjob-Framework regelmäßig über die Kommandozeile gestartet werden. Der Domain Manager kann diesen Server-Cronjob nicht selbst beim Hoster anlegen.',
    'command' => 'Empfohlener Befehl',
    'schedule' => 'Den Server-Cronjob möglichst jede Minute ausführen. Contao entscheidet selbst, welche Aufgaben tatsächlich fällig sind.',
    'hoster_hint' => 'Der passende PHP-Pfad bzw. die PHP-Version hängt vom Hoster ab. „php“ im Beispiel gegebenenfalls durch den dort vorgegebenen PHP-Befehl ersetzen.',
    'disabled' => 'Automatische Synchronisation ist deaktiviert.',
    'not_confirmed' => 'Noch kein automatischer CLI-Lauf bestätigt. Bitte den Server-Cronjob einrichten und testen.',
    'confirmed' => 'Automatische CLI-Ausführung zuletzt bestätigt: %s.',
    'overdue' => 'Kein fälliger automatischer Lauf seit %s erkannt. Bitte den Server-Cronjob prüfen.',
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
