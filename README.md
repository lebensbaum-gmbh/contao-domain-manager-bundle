# Contao Domain Manager Bundle

Zentrale Verwaltung, Synchronisation und Überwachung mehrerer Contao-Installationen.

## Version 1.1.0

Der Domain Manager ist ein eigenständiges Contao-Bundle für Contao 5.7. Er benötigt weder den Catalog Manager noch manuelle Laufzeitkonfiguration in Projektdateien.

### Eigene Datenbasis

- `tl_domain_manager_domain`
- `tl_domain_manager_installation`
- `tl_domain_manager_settings`

Die Backend-Verwaltung befindet sich unter **Domainverwaltung → Hauptdomains** und **Domainverwaltung → Einstellungen**.

## Funktionen

- Hauptdomains und zugehörige Contao-Installationen verwalten
- Contao- und PHP-Versionen über das System-Info-Bundle synchronisieren
- aktuelles Live-Ziel automatisch ermitteln
- Synchronisationsstatus, Fehlermeldungen und Zeitpunkte speichern
- verschlüsselte Secret-Speicherung in der Datenbank
- Verbindungstest direkt an einer Installation
- Frontend-Domainübersicht als eigenes Contao-Inhaltselement
- eigener Domainfilter
- Screenshots/Thumbnails je Hauptdomain
- Trakked-Status je Installation
- optionale, konfigurierbare Trakked-URL
- konfigurierbare Frontend-Mitgliedergruppen für die Synchronisation
- eigene Backend-Rechte für Bearbeitung, Verbindungstests, Secrets und Einstellungen

## Backend-Benutzerrechte

Die Sichtbarkeit der Backend-Module wird über Contaos Standardrecht **Erlaubte Module** gesteuert:

- **Domainverwaltung → Hauptdomains**
- **Domainverwaltung → Einstellungen**

Zusätzlich stellt das Bundle folgende Domain-Manager-Rechte bereit:

- Hauptdomains und Installationen bearbeiten
- Verbindungen testen
- Secrets ersetzen
- Domain-Manager-Einstellungen bearbeiten

Administratoren besitzen Vollzugriff.

Ohne **Hauptdomains und Installationen bearbeiten** bleiben Hauptdomains und Installationen für normale Backend-Benutzer lesbar, sofern das Modul freigegeben ist. Detailansichten bleiben verfügbar; Schreib-, Lösch-, Kopier- und Mehrfachbearbeitungsaktionen werden nicht angeboten und zusätzlich serverseitig über Data-Container-Voter abgesichert.

Für die Domain-Manager-Datensätze sind keine zusätzlichen klassischen Tabellen- oder Feldrechte erforderlich.

**Verbindungen testen** und **Secrets ersetzen** sind getrennte Rechte. Das Recht für **Domain-Manager-Einstellungen bearbeiten** steuert die Bearbeitung der Einstellungen unabhängig vom reinen Lesezugriff.

Für die Auswahl von Screenshots über das `fileTree`-Feld benötigt ein Backend-Benutzer Zugriff auf das Modul **Dateiverwaltung** sowie einen passenden Filemount. Dateioperationen können unabhängig davon eingeschränkt werden.

Die Frontend-Aktion **Systemdaten aktualisieren** ist von den Backend-Rechten getrennt und wird über die unter **Domainverwaltung → Einstellungen** gewählten Frontend-Mitgliedergruppen gesteuert.

## Keine manuelle Laufzeitkonfiguration

Für den regulären Betrieb werden keine manuellen Änderungen an folgenden Dateien benötigt:

- `composer.json`
- `auth.json`
- `.env.local`
- `domain-manager-secrets.json`

Installations-IDs und Secrets werden in der Backend-Verwaltung gepflegt. Secrets werden verschlüsselt gespeichert und nicht im Klartext ausgegeben.

## Installation

Nach einer Veröffentlichung als Composer-Paket ist der vorgesehene Ablauf:

1. Bundle über den Contao Manager installieren.
2. Datenbank aktualisieren.
3. Unter **Domainverwaltung → Einstellungen** Frontend-Mitgliedergruppen und optionale Links konfigurieren.
4. Hauptdomains und Installationen im Backend anlegen.
5. Auf den überwachten Installationen das zugehörige System-Info-Bundle installieren und dessen Zugangsdaten in den Installationsdatensatz übernehmen.
6. Im gewünschten Artikel die Inhaltselemente **Domainübersicht** und optional **Domainfilter** einrichten.

Für den regulären Anwenderbetrieb sind keine Terminalbefehle erforderlich.

## Frontend

Das Bundle enthält zwei Inhaltselemente:

- **Domainübersicht** (`domain_manager_overview`)
- **Domainfilter** (`domain_manager_filter`)

Die Domainübersicht liest ausschließlich aus den eigenen Domain-Manager-Tabellen. Der Filter arbeitet clientseitig auf den dort ausgegebenen Installationen.

Die Aktion **Systemdaten aktualisieren** wird nur Frontend-Mitgliedern angezeigt, die mindestens einer unter **Domainverwaltung → Einstellungen** ausgewählten Mitgliedergruppe angehören.

Ist in den Einstellungen eine Trakked-URL hinterlegt, wird der globale Button **Zu Trakked ↗** ausgegeben. Bei leerem Feld erscheint kein Button.

## Catalog Manager

Der Catalog Manager ist keine Abhängigkeit dieses Bundles und wird zur Laufzeit nicht verwendet.

Frühere Entwicklungsstände nutzten Catalog-Manager-Strukturen. Diese Übergangslogik ist vollständig entfernt. Alte Tabellen wie `domains`, `installations` oder `tl_catalog*` gehören nicht zum Domain Manager und können nach Sicherung und Prüfung separat entfernt werden, sofern keine andere Erweiterung sie benötigt.

## Sicherheit

- Secrets werden mit AES-256-GCM verschlüsselt gespeichert.
- Der Schlüssel wird aus dem Symfony-/Contao-`kernel.secret` abgeleitet.
- System-Info-Abfragen werden per HMAC-SHA256 signiert.
- Verbindungstests verwenden die gespeicherten, verschlüsselten Zugangsdaten.
- Backend-Aktionen werden über eigene Rechte und serverseitige Voter abgesichert.
- Die Frontend-Synchronisation ist auf konfigurierte Mitgliedergruppen beschränkt.

## Anforderungen

- PHP `^8.3`
- Contao `^5.7`
- OpenSSL-Erweiterung

## Aktuell geprüfter Funktionsstand

Erfolgreich getestet wurden:

- Anlegen und Bearbeiten von Hauptdomains und Installationen
- Nur-Lesen-Modus für Hauptdomains, Installationen und Einstellungen
- Detailansichten ohne Bearbeitungsrecht
- Ausblenden der Mehrfachbearbeitung im Nur-Lesen-Modus
- getrennte Rechte für Verbindungstests und Secret-Wechsel
- Screenshot-Auswahl mit Filemount
- Frontend-Ausgabe und Domainfilter
- Synchronisation von Contao-/PHP-Versionen und Statuswerten
- Live-Erkennung
- kontrollierter Verbindungsfehler und anschließende Wiederherstellung
- vollständiger Betrieb ohne Catalog Manager
- Installation des Bundles auf einer frischen Contao-5.7-Installation
- Zusammenspiel mit System-Info v1.1.0 auf einer frisch angebundenen Zielinstallation

## Veröffentlichung

Für eine öffentliche Composer-Verteilung sind organisatorisch noch zu klären:

- Lizenzentscheidung (derzeit `proprietary`)
- öffentliches Git-Repository
- Registrierung bei Packagist
- Veröffentlichung bzw. Verfügbarkeit des zugehörigen System-Info-Bundles
