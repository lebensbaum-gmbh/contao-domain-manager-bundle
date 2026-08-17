# Contao Domain Manager

Der **Contao Domain Manager** ermöglicht die zentrale Verwaltung und Synchronisation mehrerer Contao-Installationen in einer eigenen Contao-Installation.

Hauptdomains und zugehörige Installationen können im Backend verwaltet, mit den jeweiligen Zielsystemen verbunden und deren Systeminformationen zentral aktualisiert werden. Für das Frontend stehen die Inhaltselemente **Domainübersicht** und **Domainfilter** zur Verfügung.

## Funktionen

- Verwaltung von Hauptdomains und zugehörigen Contao-Installationen
- Verbindungstest zu überwachten Installationen
- Synchronisation von Systeminformationen
- automatische Übernahme von Contao-Version, PHP-Version, Datenbankname und DocumentRoot
- Status- und Zeitinformationen zur letzten Synchronisation
- Notizen und Screenshots je Installation
- Rechteverwaltung für Backend-Benutzergruppen
- Frontend-Inhaltselement **Domainübersicht**
- Frontend-Inhaltselement **Domainfilter**
- mitgeliefertes, anpassbares Standard-CSS

## Voraussetzungen

- Contao `^5.7`
- PHP `^8.3`
- PHP-Erweiterung `openssl`

Für jede Installation, deren Systeminformationen synchronisiert werden sollen, wird zusätzlich das Paket

`lebensbaum/contao-system-info-bundle`

benötigt.

## Installation

### 1. Domain Manager installieren

Installiere auf der zentralen Contao-Installation über den **Contao Manager**:

`lebensbaum/contao-domain-manager-bundle`

Führe anschließend die von Contao angebotene Datenbankmigration aus.

Nach erfolgreicher Installation stehen die Backend-Funktionen des Domain Managers automatisch zur Verfügung.

### 2. System-Info auf den Zielinstallationen installieren

Installiere auf jeder Contao-Installation, die überwacht bzw. synchronisiert werden soll, über den **Contao Manager**:

`lebensbaum/contao-system-info-bundle`

Nach der Installation erscheint im Backend unter **System → System-Info** die Verbindungskonfiguration.

Das System-Info-Bundle erzeugt automatisch:

- eine eindeutige Installations-ID
- ein zufälliges Secret
- den System-Info-Endpunkt

Es müssen keine `.env`-Dateien, Composer-Dateien oder sonstigen Konfigurationsdateien manuell bearbeitet werden.

## Schnellstart: Von der Installation zur fertigen Domainübersicht

### 1. Zugangsdaten der Zielinstallation übernehmen

Öffne auf der Zielinstallation:

**System → System-Info**

Kopiere dort:

- Installations-ID
- Secret

### 2. Hauptdomain anlegen

Öffne auf der zentralen Installation den Bereich **Domainverwaltung** und lege eine Hauptdomain an.

### 3. Installation anlegen

Lege unter der Hauptdomain eine Installation an und trage mindestens ein:

- Domain der Installation
- Installations-ID
- Secret

Technische Angaben wie Contao-Version, PHP-Version, Datenbankname und DocumentRoot werden bei der Synchronisation automatisch übernommen, sofern die Zielinstallation diese Informationen bereitstellt.

### 4. Verbindung testen

Über **Verbindung testen** kann geprüft werden, ob die zentrale Installation die Zielinstallation erreicht und die Zugangsdaten korrekt sind.

### 5. Frontend-Mitgliedergruppe anlegen

Die Frontend-Synchronisation verwendet Contaos Mitgliederverwaltung. Backend-Benutzergruppen und Frontend-Mitgliedergruppen sind voneinander getrennt.

Lege unter **Benutzerverwaltung → Mitgliedergruppen** eine Mitgliedergruppe an, zum Beispiel:

`Domainverwaltung`

### 6. Frontend-Mitglied anlegen

Lege unter **Benutzerverwaltung → Mitglieder** ein Mitglied an.

- Login erlauben
- Benutzername und Passwort vergeben
- die zuvor angelegte Mitgliedergruppe zuweisen

### 7. Mitgliedergruppe im Domain Manager freigeben

Öffne **Domainverwaltung → Einstellungen** und wähle unter **Berechtigte Mitgliedergruppen** die gewünschte Frontend-Mitgliedergruppe aus.

Nur angemeldete Frontend-Mitglieder aus mindestens einer dort ausgewählten Gruppe sehen die Aktion **Systemdaten aktualisieren**.

### 8. Frontend-Login anlegen

Der Domain Manager bringt kein eigenes Login-System mit. Verwendet wird das Contao-Standardmodul für die Frontend-Anmeldung.

1. Unter **Themes → Frontend-Module** ein Login-Modul anlegen.
2. Eine Login-Seite in der Seitenstruktur anlegen.
3. Das Login-Modul über einen Artikel bzw. ein Modul-Inhaltselement auf dieser Seite einbinden.
4. Optional als Weiterleitungsseite die Seite mit der Domainübersicht festlegen.

### 9. Domainübersicht anlegen

Lege die gewünschte Frontend-Seite an und füge dort das Inhaltselement **Domainübersicht** ein.

Optional kann zusätzlich das Inhaltselement **Domainfilter** verwendet werden.

### 10. Anmelden und synchronisieren

Melde dich im Frontend mit dem zuvor angelegten Mitglied an und öffne die Domainübersicht.

Bei korrekter Einrichtung erscheint die Aktion **Systemdaten aktualisieren**.

Nach erfolgreicher Synchronisation werden die von System-Info gelieferten technischen Informationen übernommen und der Synchronisationsstatus aktualisiert.

## Frontend-Ausgabe

Nach der Installation stehen in Contao zusätzliche Inhaltselemente zur Verfügung.

### Domainübersicht

Über den Elementtyp **Domainübersicht** kann die zentrale Übersicht der gespeicherten Domains und Installationen im Frontend ausgegeben werden.

### Domainfilter

Optional kann zusätzlich der Elementtyp **Domainfilter** eingesetzt werden, um die ausgegebenen Installationen im Frontend zu filtern.

Beide Elemente können wie normale Contao-Inhaltselemente in Artikeln verwendet werden.

## Gestaltung / CSS

Der Domain Manager liefert ein neutrales Standard-Stylesheet mit.

Die Darstellung kann im eigenen Theme angepasst oder vollständig überschrieben werden.

Die wichtigsten Gestaltungswerte sind über CSS-Variablen definiert. Dadurch lassen sich Farben, Abstände und weitere Eigenschaften ohne Änderung der Bundle-Dateien anpassen.

Änderungen sollten immer im eigenen Contao-Theme erfolgen, damit sie bei späteren Updates des Domain Managers erhalten bleiben.

## Rechteverwaltung

Für Backend-Benutzergruppen können getrennte Rechte vergeben werden, unter anderem für:

- Bearbeiten von Hauptdomains und Installationen
- Verbindungstests
- Ersetzen von Secrets

Zusätzlich gelten die üblichen Contao-Rechte, zum Beispiel für die Dateiverwaltung und Filemounts bei der Auswahl von Screenshots.

Die Frontend-Aktion **Systemdaten aktualisieren** wird unabhängig von den Backend-Rechten über die unter **Domainverwaltung → Einstellungen** gewählten Frontend-Mitgliedergruppen gesteuert.

## Sicherheit

Die Kommunikation zwischen Domain Manager und System-Info erfolgt über die jeweilige Installations-ID und ein Secret. System-Info-Abfragen werden signiert übertragen.

Secrets sollten vertraulich behandelt und nur den Personen zugänglich gemacht werden, die die Verbindung zwischen den Installationen einrichten oder administrieren.

Ein Secret kann im System-Info-Bundle jederzeit neu erzeugt werden. Anschließend muss das neue Secret auch im Domain Manager hinterlegt werden.

Es werden keine Datenbank-Zugangsdaten wie Benutzername oder Passwort übertragen. Bei der Synchronisation wird lediglich der Datenbankname übernommen.

## Lizenz

Dieses Projekt ist unter der **MIT License** veröffentlicht.

Copyright (c) 2026 Lebensbaum GmbH

Siehe [LICENSE](LICENSE).

## Support und Fehlerberichte

Fehler und technische Probleme können über die GitHub-Issues des Projekts gemeldet werden:

https://github.com/lebensbaum-gmbh/contao-domain-manager-bundle/issues

Quellcode:

https://github.com/lebensbaum-gmbh/contao-domain-manager-bundle

## Weiterentwicklung

Die kostenlose Version des Contao Domain Managers soll eine vollständig nutzbare Basis für die zentrale Verwaltung mehrerer Contao-Installationen bieten.

Weitergehende Funktionen wie automatisiertes Monitoring, Benachrichtigungen, Historien oder zusätzliche Verwaltungsfunktionen können zukünftig separat ergänzt werden.
