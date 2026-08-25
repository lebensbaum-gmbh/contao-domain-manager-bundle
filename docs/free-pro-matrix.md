# Contao Domain Manager – Free / Pro Matrix

## Produktprinzip

Die Free-Version ist vollständig ohne Server-Cronjob nutzbar. Systemdaten können jederzeit manuell pro Hauptdomain oder gesammelt aktualisiert werden.

Die Pro-Version erweitert die Free-Version um Automatisierung. Manuelle Aktualisierungen bleiben auch in Pro jederzeit verfügbar.

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
| Zukünftige Monitoring-/Benachrichtigungsfunktionen | – | geplant |

## Technische Trennung

Das Free-Bundle bleibt der gemeinsame Core und enthält die komplette Domainverwaltung sowie die gesamte Synchronisationslogik.

Das spätere Pro-Bundle baut darauf auf und ergänzt nur zusätzliche Auslöser und Komfortfunktionen, insbesondere die automatische Synchronisierung. Dadurch wird die eigentliche Synchronisationslogik nicht dupliziert.

Geplante Paketstruktur:

```text
lebensbaum/contao-domain-manager-bundle
    Free / Core

lebensbaum/contao-domain-manager-pro-bundle
    Erweiterung des Core-Bundles
    benötigt contao-domain-manager-bundle
```

## Upgrade-Grundsatz

Die Umstellung von der bisherigen v1.5.1 auf die spätere Free-/Pro-Struktur soll bestehende Installationen und gespeicherte Daten nicht unnötig zerstören. Automatik-spezifische Datenfelder werden beim ersten Umbau daher zunächst kompatibel erhalten, auch wenn sie in Free nicht mehr in der Oberfläche angeboten werden.

Die endgültige Versionsstrategie wird erst nach erfolgreicher technischer Trennung festgelegt.
