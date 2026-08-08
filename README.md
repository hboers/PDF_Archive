# PDF-Archiv

Lokale Weboberfläche zum Durchsuchen und Anzeigen eines nach Jahren geordneten PDF-Archivs. Die Anwendung zeigt alle PDFs eines ausgewählten Jahres an, filtert nach Dateinamen und durchsucht den extrahierbaren PDF-Text über einen lokalen SQLite-Cache.

## Funktionen

- Auswahl eines Archivjahres
- Sofortfilter für Dateinamen
- Volltextsuche innerhalb der PDFs des ausgewählten Jahres
- PDF-Vorschau direkt im Browser
- Automatische Aktualisierung des Suchindex bei neuen, geänderten oder gelöschten Dateien
- Rekursive Erkennung von PDFs in Unterverzeichnissen eines Jahres

## Voraussetzungen

- PHP mit aktivierter `exec()`-Funktion
- Python 3
- SQLite-Unterstützung für Python (normalerweise in Python enthalten)
- Poppler beziehungsweise `pdftotext`
- Ein Browser mit integrierter PDF-Anzeige
- Internetzugriff für das über ein CDN geladene Bootstrap-Stylesheet

Unter Debian oder Ubuntu lassen sich die wesentlichen Abhängigkeiten beispielsweise so installieren:

```bash
sudo apt install php-cli python3 poppler-utils
```

Für das Komfortskript `serv.sh` werden zusätzlich `curl` und LibreWolf benötigt.

## Verzeichnisstruktur

PDFs werden in vierstelligen Jahresverzeichnissen abgelegt:

```text
PDF_Archive/
├── 2025/
│   ├── Rechnung.pdf
│   └── Versicherungen/
│       └── Vertrag.pdf
├── 2026/
│   └── Dokument.pdf
├── index.php
├── search_cache.py
└── serv.sh
```

Nur Verzeichnisse mit einem Namen im Format `JJJJ` erscheinen in der Jahresauswahl. Unterordner innerhalb eines Jahres sind erlaubt.

## Starten

Im Projektverzeichnis kann die Anwendung direkt mit dem integrierten PHP-Server gestartet werden:

```bash
php -S 127.0.0.1:8001
```

Anschließend ist das Archiv unter [http://127.0.0.1:8001](http://127.0.0.1:8001) erreichbar.

Alternativ startet das vorhandene Skript den Server und öffnet die Seite automatisch in LibreWolf:

```bash
./serv.sh
```

Das Skript beendet den PHP-Server wieder, sobald es mit `Ctrl+C` gestoppt wird.

## Bedienung

1. Das gewünschte Jahr auswählen.
2. Optional einen Teil des Dateinamens in das linke Suchfeld eingeben. Die angezeigte Liste wird unmittelbar gefiltert.
3. Für eine Inhaltssuche einen Begriff in das Feld **PDF-Inhalt durchsuchen ...** eingeben und **Suchen** wählen.
4. Eine Datei in der Ergebnisliste anklicken, um sie rechts in der PDF-Vorschau zu öffnen.

Der Dateinamenfilter bleibt innerhalb der aktuellen Browser-Sitzung erhalten. Beide Sucharten unterscheiden Groß- und Kleinschreibung.

## Volltextindex

Beim ersten Aufruf einer Volltextsuche liest `search_cache.py` die PDFs des ausgewählten Jahres mit `pdftotext` ein und speichert den Text in `search.sqlite`. Weitere Suchen verwenden den Cache. Eine Datei wird erneut eingelesen, wenn sich ihre Größe oder ihr Änderungszeitpunkt geändert hat; nicht mehr vorhandene Dateien werden für das gerade durchsuchte Jahr aus dem Index entfernt.

Ein manueller Indexaufbau ist nicht erforderlich. Soll der gesamte Index neu erzeugt werden, kann bei beendetem Webserver die ignorierte Datei `search.sqlite` gelöscht werden. Die nächste Volltextsuche legt sie wieder an.

## Einschränkungen

- Die Volltextsuche arbeitet immer nur im ausgewählten Jahr.
- Reine Bild-Scans enthalten ohne vorherige OCR keinen durchsuchbaren Text.
- Verschlüsselte oder beschädigte PDFs können unter Umständen nicht indiziert werden.
- Die Anwendung besitzt keine Anmeldung oder Rechteverwaltung. Der mitgelieferte Startbefehl bindet den Server deshalb ausschließlich an `127.0.0.1`.
- Archivdokumente und der erzeugte SQLite-Index sind durch `.gitignore` vom Git-Repository ausgeschlossen.

## Dateien

- `index.php` – Weboberfläche, Jahresauswahl, Dateifilter und PDF-Vorschau
- `search_cache.py` – Textextraktion, Pflege des SQLite-Caches und Volltextsuche
- `serv.sh` – lokaler Start des PHP-Servers mit automatischem Browseraufruf
- `pdf2txt.sh` – älteres Hilfsskript zur stapelweisen Textextraktion neben den PDF-Dateien
- `search.sqlite` – automatisch erzeugter, nicht versionierter Volltextcache
