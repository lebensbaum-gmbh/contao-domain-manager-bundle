# Contao Domain Manager – Pro Extension Architecture

## Ziel

Die Free-Version bleibt der gemeinsame technische Core. Das spätere Pro-Bundle ergänzt Automatisierung, Monitoring und Benachrichtigungen, ohne die bestehende Synchronisationslogik zu kopieren.

Geplante Paketstruktur:

```text
lebensbaum/contao-domain-manager-bundle
    Free / Core

lebensbaum/contao-domain-manager-pro-bundle
    Pro-Erweiterung
    benötigt contao-domain-manager-bundle
```

## Grundprinzipien

1. Free enthält die vollständige manuelle Domain- und Installationsverwaltung.
2. Free enthält die vollständige Synchronisationslogik.
3. Pro ruft vorhandene Free-Services auf, statt eigene Synchronizer zu implementieren.
4. Pro verwaltet nur Pro-spezifische Einstellungen, Monitoring-Zustände, Ereignisse und Benachrichtigungen.
5. Das System-Info-Bundle auf Zielinstallationen bleibt der geschützte technische Datenlieferant.
6. Bestehende Installationen aus der 1.x-Linie dürfen beim Wechsel auf die spätere 2.x-Struktur keine gespeicherten Daten verlieren.

## Bereits geeignete Core-Services

### `AllDomainsSynchronizer`

Der Service verarbeitet alle Hauptdomains und verwendet intern die gleiche Logik wie die manuelle Sammelsynchronisation. Das Pro-Bundle kann diesen Service direkt für einen automatischen Cron-Lauf verwenden.

Damit bleibt die Regel erhalten:

- Free: Benutzer löst die Synchronisation manuell aus.
- Pro: Benutzer kann manuell synchronisieren oder Pro löst dieselbe Core-Logik automatisch aus.

### `DomainSynchronizer`

Kann von Pro genutzt werden, wenn nur eine einzelne Hauptdomain automatisch oder gezielt verarbeitet werden soll.

### `InstallationSynchronizer`

Enthält bereits die eigentliche Aktualisierung einer Zielinstallation. Der Service kennt vor und nach der Synchronisation unter anderem die Contao- und PHP-Version und ist damit der geeignete Ort, um zukünftig generische Synchronisationsereignisse auszulösen.

### `SystemInfoClient`

Stellt die signierte Verbindung zum System-Info-Bundle her. Für zusätzliche Pro-Endpunkte wie Log-Monitoring sollte die Signatur-/Transportlogik nicht im Pro-Bundle dupliziert werden.

## Vorgesehene Extension Points

### 1. Synchronisationsereignisse im Free-Core

Der Core soll nach einer erfolgreichen Installation-Synchronisation ein neutrales Event auslösen, zum Beispiel:

```text
InstallationSynchronizedEvent
```

Das Event enthält mindestens:

- Installations-ID
- Domain
- alte Contao-Version
- neue Contao-Version
- alte PHP-Version
- neue PHP-Version
- Zeitpunkt

Pro kann dieses Event abonnieren und daraus Änderungsereignisse erzeugen, beispielsweise:

- Contao-Version geändert
- PHP-Version geändert
- Installation nach vorherigem Fehler wieder erreichbar

Der Free-Core selbst muss diese Ereignisse nicht historisieren oder per E-Mail melden.

Optional kann später zusätzlich ein Fehler-Event eingeführt werden, wenn dafür ein konkreter Pro-Anwendungsfall benötigt wird.

### 2. Wiederverwendbare signierte System-Info-API

Für Pro-Funktionen wie Log-Monitoring soll die HMAC-Signatur nicht ein zweites Mal implementiert werden.

Zielbild:

```text
SystemInfoApiClient
    -> signierter GET-Aufruf auf erlaubte System-Info-Endpunkte

SystemInfoClient
    -> nutzt SystemInfoApiClient für /_domainverwaltung/systeminfo

Pro Log Client
    -> nutzt SystemInfoApiClient für einen späteren Log-Endpunkt
```

Der generische Client gehört in den Free-Core, weil er die gemeinsame sichere Transportebene zwischen Domain Manager und System-Info-Bundle darstellt. Die Pro-spezifische Interpretation von Log-Daten bleibt im Pro-Bundle.

Der generische Client darf keine beliebigen externen URLs signieren. Zielpfade müssen kontrolliert und auf die bekannte Zielinstallation beschränkt bleiben.

### 3. Pro-eigene Datenspeicherung

Monitoring-Daten sollen nicht in die bestehenden Free-Tabellen gedrückt werden, wenn sie ausschließlich Pro betreffen.

Vorgesehene Pro-Tabellen können beispielsweise sein:

```text
tl_domain_manager_pro_settings
tl_domain_manager_monitor_state
tl_domain_manager_monitor_event
tl_domain_manager_notification_rule
```

Damit bleibt eine Deinstallation von Pro möglich, ohne die Free-Domainverwaltung zu beschädigen.

Die in v1.5.1 bereits vorhandenen Auto-Sync-Felder in `tl_domain_manager_settings` werden für den Übergang zunächst erhalten. Bei einer späteren Pro-Migration können vorhandene Werte übernommen werden.

## Automatische Synchronisation in Pro

Pro registriert einen eigenen Contao-Cronjob und injiziert den Free-Service `AllDomainsSynchronizer`.

Ablauf:

```text
Contao Cron
    -> Pro AutomaticSynchronizationCron
        -> Pro Settings prüfen
        -> AllDomainsSynchronizer aus Free aufrufen
        -> Pro-Laufstatus speichern
        -> Monitoring/Benachrichtigungen auswerten
```

Free benötigt dafür keinerlei Cron-Konfiguration.

## Monitoring-Architektur

Monitoring wird in einzelne Checks getrennt. Ein Check liefert einen strukturierten Zustand zurück, statt direkt E-Mails zu versenden.

Beispiele:

```text
AvailabilityCheck
SystemInfoReachabilityCheck
ContaoUpdateCheck
PhpSupportCheck
SslCertificateCheck
LogCheck
```

Der Monitoring-Runner sammelt Ergebnisse und vergleicht sie mit dem zuletzt gespeicherten Zustand.

Nur relevante Zustandsänderungen erzeugen Ereignisse:

```text
OK -> Fehler
Fehler -> OK
keine neuen Logfehler -> neue Logfehler
Contao aktuell -> Update verfügbar
SSL ausreichend gültig -> Ablaufwarnung
```

So wird verhindert, dass bei jedem Cron-Lauf dieselben Meldungen erneut erzeugt werden.

## Log-Monitoring

Das System-Info-Bundle liefert später ausschließlich strukturierte, relevante Log-Informationen. Es werden keine kompletten Logdateien oder vollständigen Stacktraces standardmäßig zentral kopiert.

Vorgesehene Felder pro Log-Ereignis:

- Zeitpunkt
- Schweregrad
- kurze Meldung
- Exception-Klasse, sofern vorhanden
- betroffene URL, sofern vorhanden
- Fingerprint zur Gruppierung identischer Fehler
- Anzahl der Vorkommen

Pro speichert einen Prüf-Cursor bzw. den letzten erfolgreichen Prüfzeitpunkt und kann dadurch unterscheiden zwischen:

- bereits bekannten Fehlern
- neuen Fehlern seit der letzten Prüfung
- wiederkehrenden identischen Fehlern

Beispielanzeige:

```text
weltenbegeher.de
2 neue Fehler · 1 Fehlerbild
```

oder bei Wiederholungen:

```text
ImagesController: file does not exist
27 Vorkommen seit 08.15 Uhr
```

## Benachrichtigungen

Die Benachrichtigungsschicht reagiert ausschließlich auf erzeugte Monitoring-Ereignisse.

Mögliche Kanäle zunächst:

- E-Mail bei Fehlerzustand
- E-Mail bei Wiederherstellung
- E-Mail bei neuen relevanten Logfehlern
- E-Mail bei Contao-/PHP-/SSL-Handlungsbedarf
- optionale tägliche oder wöchentliche Zusammenfassung

Ein normaler erfolgreicher Cron-Lauf erzeugt keine E-Mail.

## Nächste technische Schritte

1. Neutralen `InstallationSynchronizedEvent` im Free-Core einführen und testen.
2. Bestehenden `SystemInfoClient` so vorbereiten, dass die signierte Transportlogik später ohne Duplizierung für zusätzliche erlaubte Endpunkte verwendet werden kann.
3. Free erneut auf `install1` testen.
4. Danach Grundgerüst des separaten `contao-domain-manager-pro-bundle` anlegen.
5. Als erste Pro-Funktion die bisherige automatische Synchronisation aus v1.5.1 in das Pro-Bundle überführen.
6. Erst danach Monitoring schrittweise ergänzen; Log-Monitoring erfordert zusätzlich eine kompatible Erweiterung des System-Info-Bundles.
