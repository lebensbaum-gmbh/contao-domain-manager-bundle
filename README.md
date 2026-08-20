# Contao Domain Manager

Der **Contao Domain Manager** ermöglicht die zentrale Verwaltung, Synchronisation und Überwachung mehrerer Contao-Installationen in einer eigenen Contao-Installation.

Hauptdomains und zugehörige Installationen können im Backend verwaltet, mit den jeweiligen Zielsystemen verbunden und deren Systeminformationen zentral aktualisiert werden. Für das Frontend stehen eine geschützte Domainübersicht und umfangreiche Filter zur Verfügung.

## Funktionen

- Verwaltung von Hauptdomains und zugehörigen Contao-Installationen
- Verbindungstest zu überwachten Installationen
- Einzel- und Sammelsynchronisation von Systeminformationen
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
- responsives Standardlayout und Seitentheme
- anpassbare CSS-Custom-Properties
- **Ersteinrichtungs-Assistent**, der Theme, Seitenlayout, Seitenstruktur, Login, Fehlerseiten, Mitgliedergruppe und Inhaltselemente automatisch anlegt

## Voraussetzungen

- Contao `^5.7`
- PHP `^8.3`
- PHP-Erweiterung `openssl`

Für jede überwachte Zielinstallation wird zusätzlich benötigt:

`lebensbaum/contao-system-info-bundle`

## Empfohlener Aufbau

Der Domain Manager sollte bevorzugt in einer **eigenen, separaten Contao-Installation** betrieben werden. Dadurch bleibt die Verwaltungsoberfläche von normalen Websites getrennt und der Ersteinrichtungs-Assistent kann eine dafür optimierte Seitenstruktur anlegen.

Beispiel für eine eigene Subdomain:

```text
domainverwaltung.<ihre-domain.tld>
```

Der DocumentRoot der Contao-Installation sollte auf `/public` zeigen.

## Installation des Domain Managers

### 1. Frisches Contao installieren

Installiere eine normale Contao-5.7-Installation, verbinde die Datenbank und lege einen Backend-Administrator an.

Es ist **nicht erforderlich**, vorher ein Theme, Seitenlayout, Seitenbaum oder Frontend-Modul anzulegen.

### 2. Domain Manager installieren

Installiere über den **Contao Manager**:

```text
lebensbaum/contao-domain-manager-bundle
```

Führe anschließend die von Contao angebotene Datenbankmigration aus.

### 3. Ersteinrichtung starten

Öffne im Backend:

**Domainverwaltung → Ersteinrichtung**

Der Assistent prüft zunächst, welche Bausteine bereits vorhanden sind. Fehlende Bausteine werden beim Klick auf **Einrichtung vervollständigen** in einer Transaktion angelegt. Bei einem Fehler wird der Vorgang vollständig zurückgerollt.

Bei einer frischen Installation legt der Assistent automatisch an:

- Frontend-Mitgliedergruppe **Domainverwaltung**
- Theme **Domainverwaltung**
- Seitenlayout **Domainverwaltung**
- Artikel-Modul in der Hauptspalte
- Startpunkt einer Website
- geschützte Seite **Domainübersicht**, Alias `index`
- ungeschützte Seite **Login**, Alias `login`
- Seite **401 – Nicht authentifiziert** mit Weiterleitung auf Login
- Seite **403 – Zugriff verweigert** mit Standardhinweis
- Frontend-Modul **Domainverwaltung – Login**
- Artikel und Überschriften für Domainübersicht und Login
- Inhaltselement **Domainfilter**
- Inhaltselement **Domainübersicht**
- Freigabe der Mitgliedergruppe in den Domain-Manager-Einstellungen
- Standardwert von 30 Tagen für die Synchronisationswarnung

Vorhandene passende Bausteine werden wiederverwendet. Ein erneuter Aufruf soll keine Duplikate erzeugen.

### 4. Frontend-Mitglied anlegen

Benutzername und Passwort werden bewusst **nicht automatisch erzeugt**.

Lege unter **Benutzerverwaltung → Mitglieder** mindestens ein Frontend-Mitglied an:

- Anmeldung aktivieren
- Benutzername und Passwort vergeben
- Mitgliedergruppe **Domainverwaltung** zuweisen

Die Ersteinrichtung prüft diesen Schritt. Solange kein aktives Mitglied der Gruppe vorhanden ist, erscheint ein entsprechender Hinweis. Sobald ein geeignetes Mitglied existiert, wird der Frontend-Zugang als vorbereitet angezeigt.

Damit ist die zentrale Domainverwaltung grundsätzlich einsatzbereit.

## System-Info auf Zielinstallationen

Installiere auf jeder Contao-Installation, die überwacht oder synchronisiert werden soll, über den **Contao Manager**:

```text
lebensbaum/contao-system-info-bundle
```

Nach der Installation erscheint im Backend unter **System → System-Info** die Verbindungskonfiguration.

Das System-Info-Bundle erzeugt automatisch:

- eine eindeutige Installations-ID
- ein zufälliges Secret
- den geschützten System-Info-Endpunkt

Es müssen keine `.env`-, Composer- oder sonstigen Konfigurationsdateien manuell bearbeitet werden.

## Erste Zielinstallation hinzufügen

### 1. Zugangsdaten übernehmen

Auf der Zielinstallation unter **System → System-Info**:

- Installations-ID kopieren
- Secret anzeigen und kopieren

### 2. Hauptdomain anlegen

In der zentralen Installation unter **Domainverwaltung → Hauptdomains** eine Hauptdomain anlegen.

### 3. Installation hinzufügen

Unter der Hauptdomain eine Installation anlegen und mindestens eintragen:

- Domain der Installation
- Installations-ID
- Secret

Technische Daten wie Contao-Version, PHP-Version, Datenbankname und DocumentRoot werden bei der Synchronisation automatisch übernommen.

Der vollständige DocumentRoot bleibt intern gespeichert. Im Frontend wird bewusst nur der relevante Webroot angezeigt, z. B. `/public` oder `/web`.

### 4. Verbindung testen

Über **Verbindung testen** wird geprüft, ob die Zielinstallation erreichbar ist und die Zugangsdaten gültig sind.

### 5. Systemdaten synchronisieren

Im Frontend stehen je Hauptdomain **Systemdaten aktualisieren** sowie global **Alle Systemdaten aktualisieren** zur Verfügung.

Bei der Sammelsynchronisation werden alle Hauptdomains nacheinander verarbeitet. Ein Fehler bei einer Installation verhindert nicht die Aktualisierung der übrigen Einträge.

## Automatisch erzeugte Seitenstruktur

Der Ersteinrichtungs-Assistent erzeugt standardmäßig:

```text
Domainverwaltung           Startpunkt einer Website
├── Domainübersicht        Reguläre Seite, Alias index, geschützt
├── Login                  Reguläre Seite, Alias login, ungeschützt
├── 401 – Nicht authentifiziert
│                          Weiterleitung auf Login
└── 403 – Zugriff verweigert
                           eigene Hinweisseite
```

Der typische Ablauf ist damit:

- nicht angemeldet → geschützte Domainübersicht → 401 → Login
- erfolgreiche Anmeldung → Domainübersicht
- angemeldet, aber nicht berechtigt → 403
- angemeldet und berechtigt → direkter Zugriff auf die Domainübersicht

## Ersteinrichtung als Prüfer und Reparaturassistent

Die Ersteinrichtung ist nicht nur für neue Installationen gedacht. Sie erkennt auch auf bestehenden Installationen vorhandene Bestandteile und zeigt an, was fehlt.

Geprüft werden derzeit:

1. Frontend-Mitgliedergruppe
2. Theme
3. Seitenlayout
4. Startpunkt
5. Seite Domainübersicht
6. Seite Login
7. 401-Seite
8. 403-Seite
9. Login-Modul
10. Inhaltselement Domainfilter
11. Inhaltselement Domainübersicht

Zusätzlich wird geprüft, ob mindestens ein aktives Frontend-Mitglied mit erlaubter Anmeldung der Mitgliedergruppe **Domainverwaltung** zugeordnet ist.

## Status & Warnungen

Der Domain Manager bewertet Installationen mit **OK**, **Hinweis** oder **Fehler**. Die Hauptdomain übernimmt jeweils den schlechtesten Status ihrer Installationen.

Typische Bewertungen:

- **Fehler** bei fehlgeschlagener Synchronisation oder fehlgeschlagenem Verbindungstest
- **Hinweis** bei noch nicht getesteter bzw. unvollständig konfigurierter Verbindung
- **Hinweis** bei fehlender oder ungültiger System-Info-Installations-ID
- **Hinweis** bei noch nie erfolgter bzw. zu lange zurückliegender erfolgreicher Synchronisation
- **Hinweis** bei `/web` als veraltetem Webroot
- **Hinweis** während einer reinen Security-Supportphase einer Contao- oder PHP-Version
- **Fehler**, sobald eine bekannte Contao- oder PHP-Version ihr Supportende erreicht hat

Die Warnschwelle für veraltete Synchronisationen kann unter **Domainverwaltung → Einstellungen** angepasst werden. Standard sind 30 Tage.

## Frontend-Ausgabe

### Domainübersicht

Die Hauptdomains werden als aufklappbare Einträge dargestellt. Bereits in der kompakten Ansicht sichtbar sind unter anderem:

- aktuelle Zielinstallation
- Contao-Version
- PHP-Version
- Status

Im aufgeklappten Bereich erscheinen zusätzliche technische Daten und konkrete Statushinweise.

### Domainfilter

Der Filter unterstützt unter anderem:

- Suchbegriff
- aktuelles Ziel
- Trakked-Status
- Status **OK**, **Hinweis**, **Fehler**
- Contao-Version
- PHP-Version
- Umgebung

Mehrere Filter können kombiniert werden. Eine Hauptdomain bleibt sichtbar, sobald mindestens eine ihrer Installationen den gewählten Kriterien entspricht.

## Gestaltung / CSS

Der Domain Manager liefert mehrere Stylesheets mit:

- Komponenten-Styling für Domainübersicht und Domainfilter
- responsivem Layout
- Seitentheme für Domainübersicht, Login und Fehlerseite

Die automatisch angelegte Seitenstruktur verwendet die vorgesehenen Klassen:

```text
Domainübersicht-Seite:  domainverwaltung-page
Login-Seite:            domainverwaltung-login-page
403-Seite:              domainverwaltung-error-page
Login-Modul:            domainverwaltung-login
```

### Eigene Farben und Gestaltung

Bundle-Dateien sollten nicht direkt geändert werden. Die wichtigsten Werte können über CSS-Custom-Properties überschrieben werden.

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
    --dm-theme-text: #2b2b2b;
    --dm-theme-muted: #6b6b6b;
    --dm-theme-border: #ded7d9;
    --dm-theme-radius: 10px;
    --dm-theme-content-width: 1500px;
    --dm-theme-font-family: Arial, sans-serif;
}
```

Weitere verfügbare Variablen umfassen unter anderem:

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

## Rechteverwaltung

Für Backend-Benutzergruppen können getrennte Rechte vergeben werden, unter anderem für:

- Bearbeiten von Hauptdomains und Installationen
- Verbindungstests
- Ersetzen von Secrets

Die **Ersteinrichtung** steht ausschließlich Administratoren zur Verfügung.

Frontend-Aktionen zur Einzel- und Sammelsynchronisation werden unabhängig von den Backend-Rechten über die unter **Domainverwaltung → Einstellungen** freigegebenen Frontend-Mitgliedergruppen kontrolliert.

## Sicherheit

- Secrets der Zielinstallationen werden verschlüsselt gespeichert.
- Der System-Info-Endpunkt verwendet eine signierte Authentifizierung.
- Die Domainübersicht ist standardmäßig als geschützte Frontend-Seite vorgesehen.
- Die automatische Ersteinrichtung ist nur für Backend-Administratoren verfügbar.
- Der Assistent arbeitet beim Anlegen mehrerer Bausteine innerhalb einer Datenbanktransaktion.

## Updates

Stabile Versionen werden über Packagist veröffentlicht. Empfohlen ist eine normale Composer-Versionsanforderung, z. B.:

```text
^1.4
```

Entwicklungsstände wie `dev-main` sind ausschließlich für Tests gedacht.

## Lizenz

MIT

## Support / Issues

Fehlerberichte und Verbesserungsvorschläge können über die GitHub-Issues des Projekts gemeldet werden.
