<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_domain']['domain_legend'] = 'Hauptdomain';
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['status_legend'] = 'Status & Hinweise';
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['live_legend'] = 'Live-Erkennung';

$GLOBALS['TL_LANG']['tl_domain_manager_domain']['domain'] = ['Domain', 'Öffentliche Hauptdomain ohne Pfad.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['title'] = ['Bezeichnung', 'Optionale interne Bezeichnung.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['thumbnail'] = ['Screenshot / Thumbnail', 'Vorschaubild der Hauptdomain für die Frontend-Domainübersicht.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['status'] = ['Status', 'Optionaler Status der Hauptdomain.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['notes'] = ['Bemerkungen', 'Interne Hinweise zur Hauptdomain.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_status'] = ['Live-Status', 'Ergebnis der letzten automatischen Live-Erkennung.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_last_check'] = ['Letzte Live-Prüfung', 'Zeitstempel der letzten Live-Erkennung.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_installation_id'] = ['Erkannte Installation', 'Datensatz-ID der zuletzt erkannten Live-Installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_live_error'] = ['Live-Fehler', 'Fehlermeldung der letzten Live-Erkennung.'];

$GLOBALS['TL_LANG']['tl_domain_manager_domain']['new'] = ['Neue Hauptdomain', 'Eine neue Hauptdomain anlegen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['children'] = ['Installationen', 'Installationen dieser Hauptdomain bearbeiten.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['edit'] = ['Bearbeiten', 'Hauptdomain ID %s bearbeiten.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['copy'] = ['Duplizieren', 'Hauptdomain ID %s duplizieren.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['delete'] = ['Löschen', 'Hauptdomain ID %s löschen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['show'] = ['Details', 'Details der Hauptdomain ID %s anzeigen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['editAll'] = ['Mehrere bearbeiten', 'Mehrere Hauptdomains gleichzeitig bearbeiten.'];

// Domain-Manager-specific operation names bypass Contao's legacy table-right UI filtering.
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_new'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['new'] ?? ['dm_new', 'dm_new'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_edit'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['edit'] ?? ['dm_edit', 'dm_edit'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_copy'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['copy'] ?? ['dm_copy', 'dm_copy'];
$GLOBALS['TL_LANG']['tl_domain_manager_domain']['dm_delete'] = $GLOBALS['TL_LANG']['tl_domain_manager_domain']['delete'] ?? ['dm_delete', 'dm_delete'];
