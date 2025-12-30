#!/bin/bash
set -e

CONFIG_PATH=/data/options.json

# Config aus HA lesen
if [ -f "$CONFIG_PATH" ]; then
    export DB_FOLDER=$(jq --raw-output '.db_folder // "/data"' $CONFIG_PATH)
else
    export DB_FOLDER="/data"
fi

# Sicherstellen, dass DB Ordner existiert und nobody gehört
mkdir -p "$DB_FOLDER"
echo "🔧 Setze Berechtigungen für $DB_FOLDER..."
chown -R nobody:nobody "$DB_FOLDER"

echo "🦥 TimeSloth (PHP 8.4 Edition) startet..."
echo "📂 Datenbank Pfad: $DB_FOLDER/timesloth.sqlite"

# PHP-FPM 8.4 konfigurieren
# 1. User auf nobody setzen (sicherheitshalber)
sed -i 's/user = nobody/user = nobody/g' /etc/php84/php-fpm.d/www.conf
sed -i 's/group = nobody/group = nobody/g' /etc/php84/php-fpm.d/www.conf

# 2. WICHTIG: Umgebungsvariablen behalten (sonst ist DB_FOLDER leer!)
sed -i 's/;clear_env = no/clear_env = no/g' /etc/php84/php-fpm.d/www.conf

# 3. WICHTIG: Fehler in den Docker-Log leiten (damit du 500er Fehler siehst)
sed -i 's/;catch_workers_output = yes/catch_workers_output = yes/g' /etc/php84/php-fpm.d/www.conf
sed -i 's/;decorate_workers_output = no/decorate_workers_output = no/g' /etc/php84/php-fpm.d/www.conf

# PHP-FPM starten
php-fpm84 -D

# Nginx starten
nginx -g "daemon off;"