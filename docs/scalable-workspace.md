# Domainübersicht und Updates für größere Bestände

Die Seitennamen bleiben **Domainübersicht** und **Updates**. Beide Ansichten bieten Liste und Kacheln mit den gespeicherten Website-Thumbnails, Suche und Seitennavigation. Die Ansichtsauswahl wird lokal je Arbeitsbereich gespeichert.

Die Domainübersicht fasst Installationen unter ihren Hauptdomains zusammen. Hinweise und Fehler bleiben in der geschlossenen Kopfzeile sichtbar. Updates startet mit Handlungsbedarf; „Alle Installationen“ fasst den Bestand in zunächst geschlossenen Hauptdomain-Gruppen zusammen. Suche öffnet passende Installationen. Eine Auswahl bleibt beim Filtern, Blättern und Ansichtswechsel erhalten. „Seite auswählen“ umfasst nur Installationen der aktuellen Seite; die Anzahl der Auswahl bleibt sichtbar.

Update-, Backup- und Restore-Aktionen verwenden die vorhandenen POST-Endpunkte und Bestätigungen. Während eines Wartungsauftrags bleibt die bestehende Fortschrittsanzeige oberhalb der Filter sichtbar; die Ansicht wird während des Laufs nicht umgebaut. Ergebnis- und Sicherungslinks öffnen die betroffene Hauptdomain und ihre Seite.

## Free und Pro

Liste, Kacheln, Suche, Seitennavigation und die manuelle Systemdatensynchronisierung bleiben in Free verfügbar. Pro-Aktionen sind in Free ausgegraut; ein Klick oder die Tastaturaktivierung öffnet einen Hinweis und den Link zum Abo-Modell. Free erhält keine Pro-POST-URLs oder Pro-CSRF-Tokens. Ist Pro installiert, fehlen aber Bedienrechte, erklärt der Hinweis die fehlende Berechtigung. Die serverseitige Autorisierung der Pro-Controller bleibt unverändert.

Der Ziel-Link ist zentral konfigurierbar, ohne eine Datenbankmigration:

```yaml
# config/config.yaml der Contao-Installation
parameters:
    maintend.pro_subscription_url: 'https://maintend.de/'
```

Der Standard verweist auf maintend.de. Sobald die endgültige URL des Abo-Modells veröffentlicht ist, wird sie an dieser Stelle eingetragen. Es wird kein unbestätigter Pfad wie `/preise` vorausgesetzt.

## Wartungsrückstand

Pro zählt tatsächlich veröffentlichte stabile Patch-Releases innerhalb des installierten Major-/Minor-Zweigs. Ab fünf neueren Releases wird Wartungsrückstand rot angezeigt. Eine fehlgeschlagene Versionsabfrage liefert „unbekannt“, keinen vermeintlich aktuellen Stand. Die rote Kennzeichnung blockiert eine ansonsten geprüfte Aktualisierung nicht. Eine bestehende Anzeige mit identischer Ausgangs- und Zielversion bleibt für andere Composer-Paketänderungen erhalten.

Der UI-Umbau ergänzt keine neue Erkennung von Sicherheitslücken; die vorhandenen Monitoring- und Composer-Ergebnisse bleiben maßgeblich. Die Lizenz-/Paddle-Anbindung bleibt Teil der späteren Produktfreigabe.

## Abnahme

Die Browser-Abnahme verwendet die tatsächliche Updates-Vorlage mit 80 Beispielinstallationen in Free und Pro. Geprüft werden initial geschlossene Gruppen, Suche über den gesamten Bestand, Auswahl über Ansichtswechsel hinweg, klickbare Free-Hinweise ohne Pro-Aktionsformulare sowie die Darstellung bei 1280, 736 und 375 Pixeln. PHP-Tests prüfen die Free-/Pro-Metadaten, sichere Abo-URLs, Zähler bei überlappenden Zuständen und die Grenze von vier zu fünf tatsächlich veröffentlichten Releases.
