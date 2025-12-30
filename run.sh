#!/bin/bash
set -e

CONFIG_PATH=/data/options.json

# Config aus HA lesen
if [ -f "$CONFIG_PATH" ]; then
    export DB_FOLDER=$(jq --raw-output '.db_folder // "/data"' $CONFIG_PATH)
else
    export DB_FOLDER="/data"
fi

# Sicherstellen, dass DB Ordner existiert
mkdir -p "$DB_FOLDER"

# Rechte anpassen (User 'nobody' für PHP)
echo "🔧 Setze Berechtigungen für $DB_FOLDER..."
chown -R nobody:nobody "$DB_FOLDER"

echo "🦥 TimeSloth (PHP 8.4 Edition) startet..."
echo "📂 Datenbank Pfad: $DB_FOLDER/timesloth.sqlite"

# PHP-FPM 8.4 konfigurieren
# Pfad ist jetzt /etc/php84/...
# Wir setzen User auf nobody (Standard), stellen aber sicher, dass es explizit drin steht
sed -i 's/user = nobody/user = nobody/g' /etc/php84/php-fpm.d/www.conf
sed -i 's/group = nobody/group = nobody/g' /etc/php84/php-fpm.d/www.conf

# PHP-FPM 8.4 starten
php-fpm84 -D

# Nginx starten
nginx -g "daemon off;"