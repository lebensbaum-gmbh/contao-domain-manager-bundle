# Contao Domain Manager Pro – Monitoring Roadmap

## Ziel

Die Pro-Erweiterung soll aus dem Domain Manager kein vollwertiges externes Monitoring-System machen, sondern ein auf Contao zugeschnittenes Betriebs-Dashboard für Agenturen und Betreiber mehrerer Installationen.

Der gemeinsame Free-Core bleibt für Verwaltung, System-Info-Anbindung und manuelle Synchronisierung zuständig. Pro ergänzt automatische Prüfungen, Zustandsvergleich, Ereignisse und Benachrichtigungen.

## 1. Automatische Synchronisierung

Erste Pro-Funktion und direkter Nachfolger der in v1.5.1 enthaltenen Cron-Automatik.

- automatische Sammelsynchronisierung über Contaos Cronjob-Framework
- konfigurierbares Intervall
- letzter Versuch
- letzter erfolgreicher Lauf
- Status des letzten Laufs
- Hinweis bei ausbleibendem CLI-Cron-Lauf
- manueller Sync bleibt jederzeit zusätzlich möglich

## 2. Verfügbarkeitsprüfung

Der zentrale Domain Manager prüft die öffentliche URL einer Installation unabhängig vom System-Info-Endpunkt.

Vorgesehene Werte:

- erreichbar / nicht erreichbar
- HTTP-Statuscode
- Zeitpunkt der letzten erfolgreichen Prüfung
- Zeitpunkt des ersten Fehlers
- Wiederherstellung nach einem Ausfall
- optional einfache Antwortzeit

Ein einzelner kurzfristiger Fehler soll nicht sofort eine Ausfallmeldung auslösen. Für Benachrichtigungen ist eine kleine Bestätigungsschwelle vorgesehen, z. B. mehrere aufeinanderfolgende Fehlversuche.

## 3. Contao-Update-Monitoring

Ziel ist nicht nur die bestehende Supportbewertung, sondern die Erkennung konkreter verfügbarer Updates.

Beispiel:

```text
Installiert: 5.7.8
Verfügbar:   5.7.11
Status:      Patch-Update verfügbar
```

Updates sollen zunächst innerhalb der verwendeten Versionslinie bewertet werden. Eine unterstützte LTS-Installation soll nicht allein deshalb als problematisch markiert werden, weil eine neue Major-Version existiert.

## 4. PHP-Support-Monitoring

Die bestehende Supportbewertung des Free-Cores wird automatisch beobachtet.

Relevante Zustandswechsel:

- vollständig unterstützt → nur Security Support
- Security Support → End of Life
- optional Vorwarnung vor einem bevorstehenden Supportende

## 5. SSL-Zertifikat

Regelmäßige Prüfung des öffentlichen Zertifikats:

- gültig / ungültig
- Ablaufdatum
- Restlaufzeit
- Warnschwellen, z. B. 30, 14 und 7 Tage

## 6. System-Info-Erreichbarkeit

Die öffentliche Website kann erreichbar sein, obwohl die technische Verbindung zum System-Info-Bundle gestört ist.

Deshalb wird dieser Zustand getrennt betrachtet:

- Website erreichbar
- System-Info erreichbar und authentifiziert
- System-Info nicht erreichbar
- Zugangsdaten ungültig
- System-Info-Bundle fehlt oder antwortet nicht kompatibel

## 7. Änderungsmonitoring

Bei automatischen Synchronisationen werden relevante Zustandsänderungen erkannt, z. B.:

- Contao-Version geändert
- PHP-Version geändert
- DocumentRoot/Webroot geändert
- Datenbankname geändert
- Live-/Zielinstallation gewechselt
- Verbindung nach Fehler wiederhergestellt

Nicht jede Änderung muss eine E-Mail erzeugen. Sie kann zunächst als Ereignis gespeichert und je nach Benachrichtigungsregel bewertet werden.

## 8. Log-Monitoring

### Grundsatz

Es werden keine vollständigen Logdateien in den Domain Manager kopiert. Das System-Info-Bundle auf der Zielinstallation bereitet nur relevante neue Ereignisse strukturiert auf.

Vorgesehene Felder pro Fehlerbild:

- `first_seen`
- `last_seen`
- `level`
- `message`
- `exception_class` optional
- `request_uri` optional
- `fingerprint`
- `occurrences`

Der Fingerprint gruppiert wiederkehrende identische Fehler. Statt 27 Einzelmeldungen kann die Übersicht beispielsweise anzeigen:

```text
1 neues Fehlerbild · 27 Vorkommen
ImagesController: file does not exist
Letztes Vorkommen: 25.08.2026, 08.42 Uhr
```

### Welche Levels?

Für den ersten Schritt sollen vor allem relevante Fehler berücksichtigt werden:

- Warning
- Error
- Critical
- Alert
- Emergency

Reine Info-/Debug-Einträge werden nicht als Störung gezählt.

### „Neu“ statt „vorhanden“

Der Domain Manager merkt sich den letzten verarbeiteten Stand. Bereits bekannte Fehler werden beim nächsten Lauf nicht erneut als neu gemeldet.

Neue Vorkommen eines bekannten Fehlerbildes können dessen Zähler erhöhen, ohne jedes Mal eine neue Benachrichtigung auszulösen.

## 9. Benachrichtigungen

Benachrichtigungen erfolgen ereignis- bzw. statusbasiert und nicht bei jedem Cron-Lauf.

Mögliche Ereignisse:

- Website ausgefallen
- Website wieder erreichbar
- neues Contao-Update verfügbar
- PHP-Supportstatus verschlechtert
- SSL-Zertifikat läuft bald ab
- System-Info-Verbindung ausgefallen
- neue relevante Log-Fehler erkannt
- automatische Synchronisierung wiederholt fehlgeschlagen

### E-Mail

Erster vorgesehener Benachrichtigungskanal ist E-Mail.

Konfigurierbar sollen später mindestens sein:

- Empfänger
- welche Ereignistypen gemeldet werden
- nur Fehler oder auch Hinweise
- sofortige Meldung oder Zusammenfassung

## 10. Zusammenfassungen

Zusätzlich zu Einzelmeldungen ist ein regelmäßiger Bericht sinnvoll, beispielsweise wöchentlich:

```text
Domain Manager – Wochenübersicht

3 Contao-Updates verfügbar
1 PHP-Version nur noch im Security Support
2 neue Log-Fehlerbilder
0 aktuelle Ausfälle
1 SSL-Zertifikat läuft in weniger als 30 Tagen ab
```

## 11. Ereignishistorie

Später kann Pro relevante Zustandswechsel historisieren:

- Beginn und Ende eines Ausfalls
- Versionsänderungen
- Supportstatus-Wechsel
- neue Fehlerbilder
- Wiederherstellungen
- Benachrichtigungen

Eine unbegrenzte Rohdatenhistorie ist nicht Ziel. Aufbewahrungsfristen und Verdichtung müssen vor der Umsetzung festgelegt werden.

## Technische Verantwortlichkeiten

### Free / Core

- Domain- und Installationsmodelle
- System-Info-Client
- manuelle Synchronisierung
- zentrale Synchronizer
- Statusbewertung gespeicherter Systemdaten
- Frontend-Übersicht und Filter

### Pro

- Cron-Auslöser
- Monitoring-Prüfungen
- Zustandsvergleich
- Ereignisspeicher
- Benachrichtigungsregeln
- E-Mail-Versand
- zusätzliche Pro-Ausgabe in der Übersicht

### System-Info-Bundle

Das System-Info-Bundle bleibt der schlanke Agent auf der jeweiligen Zielinstallation. Für das spätere Log-Monitoring wird seine geschützte Schnittstelle erweitert, damit strukturierte lokale Fehlerdaten abgefragt werden können.

Vollständige Stacktraces oder komplette Logdateien sollen standardmäßig nicht übertragen werden.

## Reihenfolge für die Umsetzung

1. Free-/Pro-Schnitt technisch abschließen
2. Pro-Grundgerüst als eigenes Bundle
3. Cron-Automatik aus v1.5.1 im Pro-Bundle neu anbinden
4. Ereignismodell definieren
5. Verfügbarkeit und System-Info-Erreichbarkeit
6. Contao-/PHP-/SSL-Monitoring
7. E-Mail-Benachrichtigungen
8. System-Info-Schnittstelle für Log-Monitoring erweitern
9. Log-Monitoring und Gruppierung
10. Historie und Zusammenfassungen
