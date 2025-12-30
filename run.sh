#!/bin/bash
set -e

# Lese Konfiguration aus der von Home Assistant bereitgestellten Datei
CONFIG_PATH=/data/options.json

# Installiere jq, falls es nicht vorhanden ist, um die JSON-Konfiguration zu lesen
if ! command -v jq &> /dev/null
then
    echo "jq nicht gefunden, installiere es..."
    apk add --no-cache jq
fi

export SECRET_KEY=$(jq --raw-output '.secret_key' $CONFIG_PATH)
export DB_FOLDER=$(jq --raw-output '.db_folder' $CONFIG_PATH)
export RATELIMIT_STORAGE_URL=$(jq --raw-output '.redis_url' $CONFIG_PATH)

# --- Sicherheits-Check ---
if [ "$SECRET_KEY" == "CHANGE_ME_IN_ADDON_CONFIG" ]; then
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo "!! ACHTUNG: Bitte setze einen sicheren 'secret_key' in der   !!"
    echo "!! Add-on Konfiguration, um die Anwendung abzusichern.      !!"
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    exit 1
fi

echo "🦥 TimeSloth startet..."
echo "📂 Datenbank-Pfad ist: $DB_FOLDER"

exec gunicorn --bind 0.0.0.0:8080 --workers 4 --threads 2 "app:create_app()"