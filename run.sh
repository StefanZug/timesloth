#!/bin/bash
set -e

CONFIG_PATH=/data/options.json

# Config aus HA lesen (falls vorhanden)
if [ -f "$CONFIG_PATH" ]; then
    export DB_FOLDER=$(jq --raw-output '.db_folder // "/data"' $CONFIG_PATH)
else
    export DB_FOLDER="/data"
fi

# Sicherstellen, dass DB Ordner existiert
mkdir -p "$DB_FOLDER"
echo "🦥 TimeSloth (PHP Edition) startet..."
echo "📂 Datenbank Pfad: $DB_FOLDER/timesloth.sqlite"

# PHP-FPM konfigurieren (User www-data -> root oder anpassen für HA Container)
# Im HA Container laufen wir oft als root, PHP-FPM meckert da standardmäßig.
# Wir erlauben root execution für simplify.
sed -i 's/user = nobody/user = root/g' /etc/php83/php-fpm.d/www.conf
sed -i 's/group = nobody/group = root/g' /etc/php83/php-fpm.d/www.conf

# PHP-FPM im Hintergrund starten
php-fpm83 -D

# Nginx im Vordergrund starten
nginx -g "daemon off;"