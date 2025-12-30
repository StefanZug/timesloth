#!/bin/bash
set -e

CONFIG_PATH=/data/options.json

if ! command -v jq &> /dev/null
then
    echo "jq nicht gefunden, installiere es..."
    apk add --no-cache jq
fi

export SECRET_KEY=$(jq --raw-output '.secret_key' $CONFIG_PATH)
export DB_FOLDER=$(jq --raw-output '.db_folder' $CONFIG_PATH)

# FIX: Wir lesen nicht mehr aus der Config, sondern erzwingen Memory
export RATELIMIT_STORAGE_URL="memory://"

# --- Sicherheits-Check ---
if [ "$SECRET_KEY" == "CHANGE_ME_IN_ADDON_CONFIG" ]; then
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo "!! ACHTUNG: Bitte setze einen sicheren 'secret_key'!        !!"
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    exit 1
fi

echo "🦥 TimeSloth startet..."
exec gunicorn --bind 0.0.0.0:8080 --workers 4 --threads 2 "app:create_app()"