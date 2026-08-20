# Contao Domain Manager

Der **Contao Domain Manager** ermöglicht die zentrale Verwaltung und Synchronisation mehrerer Contao-Installationen in einer eigenen Contao-Installation.

Hauptdomains und zugehörige Installationen können im Backend verwaltet, mit den jeweiligen Zielsystemen verbunden und deren Systeminformationen zentral aktualisiert werden. Für das Frontend stehen die eigenen Inhaltselemente **Domainübersicht** und **Domainfilter** zur Verfügung.

## Funktionen

- Verwaltung von Hauptdomains und zugehörigen Contao-Installationen
- Verbindungstest zu überwachten Installationen
- Synchronisation von Systeminformationen
- Sammelsynchronisation aller Hauptdomains
- automatische Übernahme von Contao-Version, PHP-Version, Datenbankname und DocumentRoot
- kompakte Anzeige des Webroots im Frontend, z. B. `/public` oder `/web`
- Statussystem mit **OK**, **Hinweis** und **Fehler**
- Hinweise bei veralteter Synchronisation, fehlender System-Info-Konfiguration und `/web` als Webroot
- automatische Bewertung der Supportphase von Contao- und PHP-Versionen
- Status- und Zeitinformationen zur letzten Synchronisation
- Statusfilter für **OK**, **Hinweis** und **Fehler**
- Notizen und Screenshots je Installation
- Rechteverwaltung für Backend-Benutzergruppen
- Frontend-Inhaltselement **Domainübersicht**
- Frontend-Inhaltselement **Domainfilter**
- mitgeliefertes responsives Standardlayout
- mitgeliefertes Seitentheme für Domainübersicht, Login und Fehlerseite
- anpassbare CSS-Variablen für Farben, Schrift, Breite und weitere Gestaltungswerte

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

Der folgende Aufbau wurde mit einer frischen Contao-Installation getestet.

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

Der vollständige DocumentRoot bleibt intern gespeichert. In der Frontend-Domainübersicht wird daraus bewusst nur der relevante Webroot angezeigt, zum Beispiel `/public` oder `/web`.

### 4. Verbindung testen

Über **Verbindung testen** kann geprüft werden, ob die zentrale Installation die Zielinstallation erreicht und die Zugangsdaten korrekt sind.

Der Verbindungstest prüft die Erreichbarkeit. Die technischen Systemdaten werden bei der späteren Synchronisation aktualisiert.

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

Nur angemeldete Frontend-Mitglieder aus mindestens einer dort ausgewählten Gruppe sehen die Aktionen **Systemdaten aktualisieren** und **Alle Systemdaten aktualisieren**.

Unter **Status & Warnungen** kann außerdem festgelegt werden, nach wie vielen Tagen eine nicht aktualisierte Installation als Hinweis markiert werden soll. Standard sind 30 Tage.

### 8. Theme und Seitenlayout anlegen

Eine vollständig neue Contao-Installation benötigt zunächst ein Theme und ein Seitenlayout.

1. Unter **Themes** ein Theme anlegen, zum Beispiel `Domainverwaltung`.
2. Darin ein Seitenlayout anlegen.
3. Im Seitenlayout unter **Eingebundene Elemente** das Contao-Modul **Artikel [Artikel]** der **Hauptspalte** zuweisen.
4. Beim **Startpunkt einer Website** die Option zur Layout-Zuweisung aktivieren und das neue Seitenlayout auswählen.

Das Login-Modul sollte nicht global im Seitenlayout eingebunden werden. Es wird gezielt als Inhaltselement vom Typ **Modul** in den jeweiligen Artikeln verwendet.

### 9. Empfohlene Seitenstruktur anlegen

Empfohlener Aufbau:

```text
Domainverwaltung           Startpunkt einer Website
├── Domainübersicht        Reguläre Seite, Alias: index, geschützt
├── Login                  Reguläre Seite, Alias: login, ungeschützt
├── 401 – Nicht authentifiziert
│                          Seitentyp: 401 Nicht authentifiziert
│                          Auto-Weiterleitung auf Login
└── 403 – Zugriff verweigert
                           Seitentyp: 403 Zugriff verweigert
                           eigene Hinweisseite
```

Für die Seite **Domainübersicht**:

- Alias `index`, wenn sie direkt unter der Hauptdomain erreichbar sein soll
- Seite schützen
- die gewünschte Frontend-Mitgliedergruppe freigeben
- CSS-Klasse `domainverwaltung-page`

Für die Seite **Login**:

- nicht schützen
- CSS-Klasse `domainverwaltung-login-page`

Für die Seite **401 – Nicht authentifiziert**:

- nicht schützen
- Auto-Weiterleitung auf die Login-Seite aktivieren
- eine eigene CSS-Klasse ist bei reiner Weiterleitung nicht erforderlich

Für die Seite **403 – Zugriff verweigert**:

- nicht schützen
- keine Auto-Weiterleitung erforderlich
- CSS-Klasse `domainverwaltung-error-page`
- optional einen Artikel mit einem kurzen Hinweis für angemeldete, aber nicht berechtigte Mitglieder anlegen

Damit ergibt sich der übliche Ablauf:

- nicht angemeldet → geschützte Domainübersicht → 401 → Login
- erfolgreiche Anmeldung → Domainübersicht
- angemeldet, aber nicht berechtigt → 403
- angemeldet und berechtigt → direkter Zugriff auf die Domainübersicht

### 10. Frontend-Login anlegen

Unter **Themes → Frontend-Module** ein Contao-Modul vom Typ **Login-Formular** anlegen.

Empfohlene Einstellungen:

- Weiterleitungsseite: **Domainübersicht**
- CSS-Klasse: `domainverwaltung-login`

Auf der Seite **Login** wird das Login-Modul über ein Inhaltselement vom Typ **Modul** in den Artikel eingebunden.

Dasselbe Login-Modul kann zusätzlich auf der geschützten Domainübersicht eingebunden werden. Im angemeldeten Zustand zeigt Contao dort die Login-Information sowie die Abmeldefunktion an. Das mitgelieferte Seitentheme formatiert diesen Bereich als kompakte Account-Leiste.

### 11. Domainübersicht und Domainfilter einfügen

Die beiden Domain-Manager-Elemente sind **eigene Contao-Inhaltselementtypen** und keine Textelemente.

Beim Anlegen eines neuen Inhaltselements steht die Gruppe **Domainverwaltung** zur Verfügung mit:

- **Domainübersicht** – technischer Typ `domain_manager_overview`
- **Domainfilter** – technischer Typ `domain_manager_filter`

Empfohlene Reihenfolge im Artikel der Domainübersicht:

1. Überschrift, z. B. `Domainübersicht`
2. Inhaltselement **Modul** mit dem Login-Modul
3. Inhaltselement **Domainfilter**
4. Inhaltselement **Domainübersicht**

Wenn Domainfilter und Domainübersicht auf derselben Seite vorhanden sind, platziert das mitgelieferte Layout den Filter auf größeren Bildschirmen rechts neben der Übersicht. Auf kleineren Ansichten werden die Elemente untereinander dargestellt.

### 12. Anmelden und synchronisieren

Melde dich im Frontend mit dem zuvor angelegten Mitglied an und öffne die Domainübersicht.

Bei korrekter Einrichtung erscheinen die Aktionen **Systemdaten aktualisieren** je Hauptdomain sowie **Alle Systemdaten aktualisieren** für die Sammelsynchronisation.

Nach erfolgreicher Synchronisation werden die von System-Info gelieferten technischen Informationen übernommen und der Synchronisationsstatus aktualisiert. Bei der Sammelsynchronisation werden alle Hauptdomains nacheinander verarbeitet; ein Fehler bei einer Installation oder Hauptdomain verhindert nicht die Aktualisierung der übrigen Einträge.

## Status & Warnungen

Der Domain Manager bewertet gespeicherte Installationen mit den Zuständen **OK**, **Hinweis** oder **Fehler**. Die Hauptdomain übernimmt dabei jeweils den schlechtesten Status ihrer Installationen.

Typische Bewertungen sind:

- **Fehler** bei fehlgeschlagener Synchronisation oder fehlgeschlagenem Verbindungstest
- **Hinweis** bei noch nicht getesteter bzw. unvollständig konfigurierter Verbindung
- **Hinweis** bei fehlender oder ungültiger System-Info-Installations-ID
- **Hinweis** bei noch nie erfolgter bzw. zu lange zurückliegender erfolgreicher Synchronisation
- **Hinweis** bei `/web` als veraltetem Webroot
- **Hinweis** während einer reinen Security-Supportphase einer Contao- oder PHP-Version
- **Fehler**, sobald eine bekannte Contao- oder PHP-Version ihr Supportende erreicht hat

Die Supportbewertung verwendet hinterlegte Supportzeiträume und wechselt automatisch zwischen aktivem Support, Security-Support und Supportende. Eine Installation muss dafür nicht neu gespeichert werden.

## Frontend-Ausgabe

### Domainübersicht

Über den Elementtyp **Domainübersicht** kann die zentrale Übersicht der gespeicherten Domains und Installationen im Frontend ausgegeben werden.

Die Hauptdomains werden als aufklappbare Einträge dargestellt. Das aktuelle Ziel, Contao-Version, PHP-Version und der jeweilige Status sind bereits in der kompakten Ansicht sichtbar. Weitere technische Informationen und konkrete Statushinweise erscheinen im aufgeklappten Bereich.

### Domainfilter

Der Elementtyp **Domainfilter** filtert die ausgegebenen Installationen unter anderem nach:

- Suchbegriff
- aktuellem Ziel
- Trakked-Status
- Status **OK**, **Hinweis** oder **Fehler**
- Contao-Version
- PHP-Version
- Umgebung

Mehrere Filter können kombiniert werden. Eine Hauptdomain bleibt sichtbar, sobald mindestens eine ihrer berücksichtigten Installationen zu den gewählten Filtern passt.

Beide Elemente können wie normale Contao-Inhaltselemente in Artikeln verwendet werden.

## Gestaltung / CSS

Der Domain Manager liefert mehrere Stylesheets mit:

- Komponenten-Styling für Domainübersicht und Domainfilter
- responsives Layout für Übersicht und Filter
- ein neutrales Seitentheme für Domainübersicht, Login und Fehlerseite

Das Seitentheme wird vom Bundle automatisch bereitgestellt. Es wirkt gezielt auf die dokumentierten CSS-Klassen und verändert nicht pauschal das Styling anderer Contao-Seiten.

### Empfohlene CSS-Klassen

```text
Domainübersicht-Seite:  domainverwaltung-page
Login-Seite:            domainverwaltung-login-page
403-Seite:              domainverwaltung-error-page
Login-Modul:            domainverwaltung-login
```

### Eigene Farben und Gestaltung

Die wichtigsten Gestaltungswerte sind über CSS-Custom-Properties definiert. Bundle-Dateien sollten nicht direkt geändert werden, da solche Änderungen bei einem Update überschrieben würden.

Lege stattdessen im eigenen Contao-Theme ein eigenes Stylesheet an und überschreibe dort nur die gewünschten Variablen.

Beispiel:

```css
body.domainverwaltung-page,
body.domainverwaltung-login-page,
body.domainverwaltung-error-page {
    --dm-theme-primary: #992228;
    --dm-theme-primary-hover: #741b20;
    --dm-theme-primary-soft: #f7ecee;

    --dm-theme-page: #f8f6f6;
    --dm-theme-surface: #ffffff;
    --dm-theme-surface-soft: #faf7f8;

    --dm-theme-text: #2b2b2b;
    --dm-theme-muted: #6b6b6b;

    --dm-theme-border: #ded7d9;
    --dm-theme-border-light: #eee8ea;

    --dm-theme-radius: 10px;
    --dm-theme-content-width: 1500px;
    --dm-theme-font-family: Arial, sans-serif;
}
```

Verfügbare Variablen umfassen unter anderem:

```text
--dm-theme-primary
--dm-theme-primary-hover
--dm-theme-primary-soft

--dm-theme-page
--dm-theme-surface
--dm-theme-surface-soft

--dm-theme-text
--dm-theme-muted

--dm-theme-border
--dm-theme-border-light

--dm-theme-success
--dm-theme-success-soft
--dm-theme-warning
--dm-theme-warning-soft
--dm-theme-error
--dm-theme-error-soft

--dm-theme-radius
--dm-theme-shadow
--dm-theme-content-width
--dm-theme-font-family
```

Dadurch kann die Oberfläche an ein vorhandenes Corporate Design angepasst werden, ohne die Bundle-Dateien selbst zu verändern.

## Rechteverwaltung

Für Backend-Benutzergruppen können getrennte Rechte vergeben werden, unter anderem für:

- Bearbeiten von Hauptdomains und Installationen
- Verbindungstests
- Ersetzen von Secrets

Zusätzlich gelten die üblichen Contao-Rechte, zum Beispiel für die Dateiverwaltung und Filemounts bei der Auswahl von Screenshots.

Die Frontend-Aktionen zur Einzel- und Sammelsynchronisation werden unabhängig von den Backend-Rechten über die unter **Domainverwaltung → Einstellungen** gewählten Frontend-Mitgliedergruppen gesteuert.

## Sicherheit

Die Kommunikation zwischen Domain Manager und System-Info erfolgt über die jeweilige Installations-ID und ein Secret. System-Info-Abfragen werden signiert übertragen.

Secrets sollten vertraulich behandelt und nur den Personen zugänglich gemacht werden, die die Verbindung zwischen den Installationen einrichten oder administrieren.

Ein Secret kann im System-Info-Bundle jederzeit neu erzeugt werden. Anschließend muss das neue Secret auch im Domain Manager hinterlegt werden.

Es werden keine Datenbank-Zugangsdaten wie Benutzername oder Passwort übertragen. Bei der Synchronisation wird lediglich der Datenbankname übernommen.

Der vollständige serverseitige DocumentRoot kann intern gespeichert werden. In der Frontend-Domainübersicht wird für die tägliche Arbeit nur der Webroot wie `/public` oder `/web` ausgegeben.

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

Weitergehende Funktionen wie automatische Benachrichtigungen, Historien, zeitgesteuerte Prüfungen oder zusätzliche Verwaltungsfunktionen können zukünftig separat ergänzt werden.
