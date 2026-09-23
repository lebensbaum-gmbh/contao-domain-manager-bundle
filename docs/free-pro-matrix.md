# Contao Domain Manager – Free / Pro Matrix

## Produktprinzip

Die Free-Version ist vollständig ohne Server-Cronjob nutzbar. Systemdaten können jederzeit manuell pro Hauptdomain oder gesammelt aktualisiert werden.

Die Pro-Version erweitert die Free-Version um Automatisierung, Monitoring und Benachrichtigungen. Manuelle Aktualisierungen bleiben auch in Pro jederzeit verfügbar.

**Kurzform:**

- Free = auf Knopfdruck aktuell
- Pro = auf Knopfdruck oder automatisch aktuell

## Funktionsmatrix

| Funktion | Free | Pro |
| --- | :---: | :---: |
| Hauptdomains verwalten | ✓ | ✓ |
| Mehrere Contao-Installationen je Hauptdomain verwalten | ✓ | ✓ |
| System-Info-Bundle anbinden | ✓ | ✓ |
| Verbindung testen | ✓ | ✓ |
| Systemdaten einer Hauptdomain manuell aktualisieren | ✓ | ✓ |
| Alle Systemdaten manuell gesammelt aktualisieren | ✓ | ✓ |
| Contao-, PHP-, Datenbank- und Webroot-Informationen übernehmen | ✓ | ✓ |
| Aktuelle Ziel-/Live-Installation ermitteln | ✓ | ✓ |
| Statusbewertung OK / Hinweis / Fehler | ✓ | ✓ |
| Supportstatus von Contao- und PHP-Versionen bewerten | ✓ | ✓ |
| Warnung bei veralteten Systemdaten | ✓ | ✓ |
| Such- und Filterfunktionen | ✓ | ✓ |
| Externe Dienste verwalten und Installationen zuordnen | ✓ | ✓ |
| Notizen und Screenshots je Installation | ✓ | ✓ |
| Backend-Rechteverwaltung | ✓ | ✓ |
| Geschützte Frontend-Domainübersicht | ✓ | ✓ |
| Ersteinrichtungs-Assistent | ✓ | ✓ |
| Automatische Synchronisierung im Hintergrund | – | ✓ |
| Frei wählbares Auto-Sync-Intervall | – | ✓ |
| Integration in Contaos Cronjob-Framework | – | ✓ |
| Anzeige des empfohlenen Server-Cron-Kommandos | – | ✓ |
| Status des letzten automatischen Laufs | – | ✓ |
| Letzter erfolgreicher automatischer Lauf | – | ✓ |
| Hinweis bei ausbleibendem Cron-Lauf | – | ✓ |
| Verfügbarkeit der Website regelmäßig prüfen | – | ✓ |
| Ausfall und Wiederherstellung erkennen | – | ✓ |
| Contao-Updates innerhalb der verwendeten Versionslinie erkennen | – | ✓ |
| Contao-Updates zentral aus der Domainübersicht vorbereiten und ausführen | – | ✓ |
| PHP-Supportphase und Supportende überwachen | – | ✓ |
| SSL-Zertifikat und Ablaufdatum überwachen | – | ✓ |
| System-Info-Erreichbarkeit überwachen | – | geplant |
| Änderungen an Contao-, PHP- und Systemwerten erkennen | – | ✓ |
| Neue relevante Contao-Log-Ereignisse erkennen | – | ✓ |
| Wiederkehrende Log-Fehler nach Fehlerbild zusammenfassen | – | geplant |
| E-Mail-Benachrichtigungen bei relevanten Zustandsänderungen | – | ✓ |
| Zusammenfassungen, z. B. Wochenbericht | – | geplant |
| Status- und Ereignishistorie | – | ✓ |

## Contao-Update-Funktion

Die Pro-Version erkennt verfügbare Contao-Patchupdates innerhalb des installierten `major.minor`-Zweigs und kann sie bewusst aus der zentralen Updates-Oberfläche gestartet installieren.

Der abgesicherte Ablauf umfasst:

- Ermittlung der verfügbaren Zielversion
- nicht-destruktive Composer-Vorprüfung
- Unterstützung auch für exakt festgeschriebene Contao-Versionen in der `composer.json`
- vollständiges Sicherheitsbackup unmittelbar vor der Installation
- erneute Prüfung des vorbereiteten Composer-Paketplans vor dem eigentlichen Update
- Composer-Aktualisierung auf die vorbereitete Zielversion
- Verifizierung der tatsächlich installierten Contao-Version
- Ausführung notwendiger Contao-Datenbankmigrationen
- erneute Synchronisierung der Systemdaten
- sichtbare Fortschrittsphasen für Backup, Vorprüfung, Composer, Verifizierung, Datenbank und Abschluss

Ein automatisches, unbeaufsichtigtes Einspielen von Contao-Updates ist bewusst **nicht** vorgesehen. Der Updatevorgang wird durch den Benutzer gestartet und bleibt auf Patchupdates innerhalb desselben Contao-Versionszweigs beschränkt.

## Log-Monitoring – Zielbild

Das Log-Monitoring soll keine vollständigen Logdateien zentral kopieren. Das System-Info-Bundle auf der Zielinstallation liefert nur strukturierte Informationen zu relevanten neuen Ereignissen.

Vorgesehene Informationen:

- Zeitpunkt
- Schweregrad, insbesondere Warning, Error und Critical
- kurze Fehlermeldung
- betroffene URL, sofern verfügbar
- Exception-Klasse, sofern verfügbar
- Fingerprint zur Gruppierung identischer Fehlerbilder
- Anzahl der Vorkommen seit der letzten Prüfung

Beispiel in der Domainübersicht:

```text
2 neue Fehler · 1 neues Fehlerbild
Letzter Fehler: 25.08.2026, 08.42 Uhr
```

Identische wiederkehrende Fehler sollen zusammengefasst werden, z. B. „ImagesController: file does not exist – 27 Vorkommen“, statt 27 einzelne Meldungen anzuzeigen.

## Benachrichtigungsprinzip

Benachrichtigungen sollen ereignis- bzw. statusbasiert erfolgen und nicht bei jedem Cron-Lauf.

Beispiele:

- Website ist nach mehreren Prüfungen nicht erreichbar
- Website ist nach einem Ausfall wieder erreichbar
- neues Contao-Patch-Update in der verwendeten Versionslinie verfügbar
- PHP-Version wechselt in die Security-Supportphase oder erreicht EOL
- SSL-Zertifikat läuft in Kürze ab
- neue relevante Log-Fehler wurden erkannt
- automatische Synchronisierung ist wiederholt fehlgeschlagen

Optional kann später eine regelmäßige Zusammenfassung ergänzt werden, z. B. ein Wochenbericht mit verfügbaren Updates, Warnungen und Ausfällen.

## Technische Trennung

Das Free-Bundle bleibt der gemeinsame Core und enthält die komplette Domainverwaltung sowie die gesamte Synchronisationslogik.

Das spätere Pro-Bundle baut darauf auf und ergänzt zusätzliche Auslöser, Monitoring-Dienste und Benachrichtigungen. Dadurch wird die eigentliche Synchronisationslogik nicht dupliziert.

Geplante Paketstruktur:

```text
lebensbaum/contao-domain-manager-bundle
    Free / Core

lebensbaum/contao-domain-manager-pro-bundle
    Erweiterung des Core-Bundles
    benötigt contao-domain-manager-bundle
```

## Upgrade- und Versionsstrategie

Die veröffentlichte 1.x-Linie (zuletzt v1.6.0) enthält noch die automatische Cron-basierte Synchronisierung und bleibt unverändert und weiterhin nutzbar.

Die automatische Synchronisierung wird daher nicht innerhalb eines normalen Patch- oder Minor-Updates der 1.x-Serie entfernt. Der Free-/Pro-Schnitt wird mit der neuen Hauptversion **2.0.0** eingeführt.

Damit gilt:

- bestehende Installationen auf 1.x verlieren durch ein normales Update keine Funktion
- Composer-Anforderungen wie `^1.5` wechseln nicht automatisch auf 2.x
- die stabile 1.x-Linie bleibt als Referenzstand erhalten
- die 2.x-Free-Version startet mit einer klaren, bewusst vereinfachten Produktstruktur
- Automatik-spezifische Datenfelder werden beim ersten Umbau zunächst kompatibel erhalten, damit vorhandene Daten nicht unnötig zerstört werden

Der bereits unter MIT veröffentlichte 1.x-Code bleibt selbstverständlich weiterhin unter dieser Lizenz nutzbar.
