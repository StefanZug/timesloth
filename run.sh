#!/bin/sh
set -e

# 1. Datenbank-Pfad erzwingen (Überschreibt alles andere)
export DB_FOLDER=/data

# 2. Debugging-Info ausgeben
echo "🦥 TimeSloth startet..."
echo "📂 Datenbank-Pfad ist: $DB_FOLDER"

# 3. Sicherstellen, dass der Ordner existiert und beschreibbar ist
if [ ! -d "$DB_FOLDER" ]; then
  echo "⚠️  Ordner $DB_FOLDER existiert nicht. Erstelle ihn..."
  mkdir -p "$DB_FOLDER"
fi

# Rechte brachial setzen (SQLite braucht Schreibrechte im Ordner!)
chmod -R 777 "$DB_FOLDER"

# 4. Gunicorn starten
# Wir binden direkt an Port 8080
exec gunicorn --bind 0.0.0.0:8080 --workers 4 --threads 2 "app:create_app()"