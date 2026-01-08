#!/bin/bash
set -e

CONFIG_PATH=/data/options.json

# 1. Config lesen
if [ -f "$CONFIG_PATH" ]; then
    export DB_FOLDER=$(jq --raw-output '.db_folder // "/data"' $CONFIG_PATH)
else
    export DB_FOLDER="/data"
fi

# 2. DB Setup
mkdir -p "$DB_FOLDER"
echo "🔧 Setze Berechtigungen für $DB_FOLDER..."
chown -R nobody:nobody "$DB_FOLDER"

# 3. SSL Setup
SSL=$(jq --raw-output '.ssl // false' $CONFIG_PATH)
CERTFILE=$(jq --raw-output '.certfile // "fullchain.pem"' $CONFIG_PATH)
KEYFILE=$(jq --raw-output '.keyfile // "privkey.pem"' $CONFIG_PATH)

if [ "$SSL" == "true" ]; then
    echo "🔒 SSL ist AKTIVIERT. Nutze Zertifikate aus /ssl/..."
    LISTEN_DIRECTIVE="listen 8080 default_server ssl;
    ssl_certificate /ssl/$CERTFILE;
    ssl_certificate_key /ssl/$KEYFILE;"
else
    echo "⚠️ SSL ist DEAKTIVIERT. Server läuft über HTTP."
    LISTEN_DIRECTIVE="listen 8080 default_server;"
fi

# 4. Nginx Config schreiben
echo "server {
    $LISTEN_DIRECTIVE
    root /app/public;
    index index.php;

    add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains\" always;
    add_header X-Content-Type-Options \"nosniff\" always;
    add_header X-Frame-Options \"SAMEORIGIN\" always;
    add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}" > /etc/nginx/http.d/default.conf

# 5. PHP AUTO-DETECTION (Der wichtige Teil!)
# Wir suchen den Config-Ordner. Alpine nutzt meist /etc/phpXY
PHP_CONF_DIR=$(find /etc -maxdepth 1 -type d -name "php*" | head -n 1)

if [ -z "$PHP_CONF_DIR" ]; then
    echo "❌ CRITICAL: Kein PHP Konfigurations-Ordner in /etc gefunden!"
    exit 1
fi

echo "🐘 PHP Config gefunden in: $PHP_CONF_DIR"

# PHP-FPM Konfigurieren (Dynamisch)
FPM_CONF="$PHP_CONF_DIR/php-fpm.d/www.conf"

if [ -f "$FPM_CONF" ]; then
    sed -i 's/user = nobody/user = nobody/g' "$FPM_CONF"
    sed -i 's/group = nobody/group = nobody/g' "$FPM_CONF"
    sed -i 's/;clear_env = no/clear_env = no/g' "$FPM_CONF"
    sed -i 's/;catch_workers_output = yes/catch_workers_output = yes/g' "$FPM_CONF"
    sed -i 's/;decorate_workers_output = no/decorate_workers_output = no/g' "$FPM_CONF"
else
    echo "❌ CRITICAL: $FPM_CONF nicht gefunden!"
    exit 1
fi

# Datenbank Info
if [ -f "$DB_FOLDER/timesloth.sqlite" ]; then
    DB_SIZE=$(du -h "$DB_FOLDER/timesloth.sqlite" | cut -f1)
    echo "📊 Aktuelle Datenbank-Größe: $DB_SIZE"
else
    echo "🆕 Datenbank wird beim ersten Zugriff erstellt."
fi

echo "🦥 TimeSloth startet..."

# PHP-FPM starten (Wir suchen das Binary passend zum Config Ordner namen, z.B. php-fpm83)
# Extrahiere Version aus Ordnernamen (z.B. /etc/php83 -> php-fpm83)
PHP_VER_NAME=$(basename "$PHP_CONF_DIR")
FPM_BIN="/usr/sbin/$PHP_VER_NAME-fpm"

# Fallback falls binary anders heißt (z.B. nur php-fpm)
if [ ! -x "$FPM_BIN" ]; then
    FPM_BIN="php-fpm"
fi

echo "🚀 Starte $FPM_BIN..."
$FPM_BIN -D

# Nginx starten
nginx -g "daemon off;"