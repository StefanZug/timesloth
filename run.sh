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

# SSL Konfiguration lesen
SSL=$(jq --raw-output '.ssl // false' $CONFIG_PATH)
CERTFILE=$(jq --raw-output '.certfile // "fullchain.pem"' $CONFIG_PATH)
KEYFILE=$(jq --raw-output '.keyfile // "privkey.pem"' $CONFIG_PATH)

# Nginx Server Block dynamisch erstellen
if [ "$SSL" == "true" ]; then
    echo "🔒 SSL ist AKTIVIERT. Nutze Zertifikate aus /ssl/..."
    LISTEN_DIRECTIVE="listen 8080 default_server ssl;
    ssl_certificate /ssl/$CERTFILE;
    ssl_certificate_key /ssl/$KEYFILE;"
else
    echo "⚠️ SSL ist DEAKTIVIERT. Server läuft über HTTP."
    LISTEN_DIRECTIVE="listen 8080 default_server;"
fi

# Config schreiben
echo "server {
    $LISTEN_DIRECTIVE
    root /app/public;
    index index.php;

    # Security Headers
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

# Datenbank Größe ermitteln (falls vorhanden)
if [ -f "$DB_FOLDER/timesloth.sqlite" ]; then
    # 'du -h' gibt menschenlesbare Größe aus (z.B. 1.2M)
    DB_SIZE=$(du -h "$DB_FOLDER/timesloth.sqlite" | cut -f1)
    echo "📊 Aktuelle Datenbank-Größe von $DB_FOLDER/timesloth.sqlite: $DB_SIZE"
else
    echo "🆕 Datenbank existiert noch nicht (wird beim ersten Zugriff erstellt)."
fi

# PHP-FPM 8.4 konfigurieren
# 1. User auf nobody setzen (sicherheitshalber)
sed -i 's/user = nobody/user = nobody/g' /etc/php84/php-fpm.d/www.conf
sed -i 's/group = nobody/group = nobody/g' /etc/php84/php-fpm.d/www.conf

# 2. WICHTIG: Umgebungsvariablen behalten (sonst ist DB_FOLDER leer!)
sed -i 's/;clear_env = no/clear_env = no/g' /etc/php84/php-fpm.d/www.conf

# 3. WICHTIG: Fehler in den Docker-Log leiten (damit du 500er Fehler siehst)
sed -i 's/;catch_workers_output = yes/catch_workers_output = yes/g' /etc/php84/php-fpm.d/www.conf
sed -i 's/;decorate_workers_output = no/decorate_workers_output = no/g' /etc/php84/php-fpm.d/www.conf

echo "🦥 TimeSloth startet..."

# PHP-FPM starten
php-fpm84 -D

# Nginx starten
nginx -g "daemon off;"