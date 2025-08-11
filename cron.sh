#!/bin/bash

set -e

# Suchverzeichnis definieren (aktuelles Verzeichnis)
SEARCH_DIR="/app"

# PDF-Dateien finden und mit pdftotext in unsichtbare Dateien konvertieren
find "$SEARCH_DIR" -type f -name "*.pdf" | while read -r pdf_file; do
    # Originalverzeichnis und Dateiname ermitteln
    dir_name="$(dirname "$pdf_file")"
    base_name="$(basename "$pdf_file" .pdf)"
    
    # Unsichtbare Datei (mit Präfix `.` und dem Zusatz `.txt`) erstellen
    invisible_txt_file="$dir_name/.$base_name.txt"

    # pdftotext aufrufen, falls die Datei noch nicht existiert
    if [ ! -f "$invisible_txt_file" ]; then
        echo "🔄 Konvertiere $pdf_file -> $invisible_txt_file"
        pdftotext "$pdf_file" "$invisible_txt_file"
    else
        echo "✔️ Datei $invisible_txt_file existiert bereits, übersprungen."
    fi
done

echo "✅ Verarbeitung abgeschlossen."