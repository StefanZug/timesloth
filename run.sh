#!/bin/bash
echo "🦥 TimeSloth fährt hoch..."

# Sicherstellen, dass der Datenordner existiert
if [ -z "$DB_FOLDER" ]; then
    export DB_FOLDER="./data"
fi
mkdir -p "$DB_FOLDER"

# Starten mit Gunicorn (4 Worker für Performance)
# Bindet auf Port 8080
exec gunicorn --bind 0.0.0.0:8080 --workers 4 "app:create_app()"