<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_domain_manager_installation']['installation_legend'] = 'Installation';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['technical_legend'] = 'Technische Daten';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['links_legend'] = 'Direktlinks';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['status_legend'] = 'Status & Hinweise';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_legend'] = 'Synchronisation';
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['connection_legend'] = 'System-Info-Verbindung';

$GLOBALS['TL_LANG']['tl_domain_manager_installation']['domain'] = ['Domain / Subdomain', 'Domain bzw. Subdomain, unter der die Installation erreichbar ist.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['environment'] = ['Umgebung', 'Bezeichnung der Umgebung, z. B. Live, Mirror oder Test.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['system_id'] = ['Installations-ID', '32-stellige ID des System-Info-Bundles.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['document_root'] = ['DocumentRoot', 'DocumentRoot dieser Contao-Installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['contao_version'] = ['Contao-Version', 'Zuletzt ermittelte Contao-Version.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['php_version'] = ['PHP-Version', 'Zuletzt ermittelte PHP-Version.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['database_name'] = ['Datenbank', 'Name der verwendeten Datenbank.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['backend_url'] = ['Contao-Backend-URL', 'Direkter Link zum Backend.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['manager_url'] = ['Contao-Manager-URL', 'Direkter Link zum Contao Manager.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['is_live'] = ['Hauptdomain verweist hierhin', 'Diese Installation ist aktuell das erkannte Live-Ziel.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['trakked'] = ['Trakked', 'Diese Installation wird in Trakked geführt.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['status'] = ['Status', 'Optionaler interner Status.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['notes'] = ['Bemerkungen', 'Interne Hinweise zur Installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['last_sync'] = ['Letzte erfolgreiche Synchronisation', 'Zeitstempel der letzten erfolgreichen Synchronisation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_status'] = ['Synchronisationsstatus', 'Status der letzten Synchronisation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['sync_message'] = ['Synchronisationsmeldung', 'Meldung der letzten Synchronisation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_panel'] = ['Verbindungsstatus', 'Status der geschützten Verbindung zur überwachten Contao-Installation.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_secret_editor'] = ['Secret', 'Neues Secret eintragen; leer lassen, um das gespeicherte Secret unverändert zu lassen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_secret_changed_at'] = ['Secret zuletzt geändert', 'Zeitstempel der letzten Secret-Änderung.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_status'] = ['Verbindungsstatus', 'Status des letzten Verbindungstests.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_connection_message'] = ['Verbindungsmeldung', 'Meldung des letzten Verbindungstests.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_last_connection_test'] = ['Letzter Test', 'Zeitstempel des letzten Verbindungstests.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_last_connection_success'] = ['Letzter erfolgreicher Test', 'Zeitstempel des letzten erfolgreichen Verbindungstests.'];

$GLOBALS['TL_LANG']['tl_domain_manager_installation']['new'] = ['Neue Installation', 'Eine neue Installation anlegen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['edit'] = ['Bearbeiten', 'Installation ID %s bearbeiten.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['copy'] = ['Duplizieren', 'Installation ID %s duplizieren.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['cut'] = ['Verschieben', 'Installation ID %s verschieben.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['delete'] = ['Löschen', 'Installation ID %s löschen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['show'] = ['Details', 'Details der Installation ID %s anzeigen.'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['connection'] = ['Verbindung', 'System-Info-Verbindung der Installation ID %s prüfen.'];

// Domain-Manager-specific operation names bypass Contao's legacy table-right UI filtering.
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_new'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['new'] ?? ['dm_new', 'dm_new'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_edit'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['edit'] ?? ['dm_edit', 'dm_edit'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_copy'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['copy'] ?? ['dm_copy', 'dm_copy'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_cut'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['cut'] ?? ['dm_cut', 'dm_cut'];
$GLOBALS['TL_LANG']['tl_domain_manager_installation']['dm_delete'] = $GLOBALS['TL_LANG']['tl_domain_manager_installation']['delete'] ?? ['dm_delete', 'dm_delete'];
