# KORREKTUR: Der Name ist "aarch64-base" (das ist bereits Alpine basierend)
# Wir nutzen den Tag :3.19 für Stabilität (oder :latest)
FROM ghcr.io/home-assistant/aarch64-base:3.19

# Pakete installieren: Nginx, PHP8.3 und notwendige Extensions
# Hinweis: In den HA Base Images sind oft schon viele Tools drin, 
# aber wir stellen sicher, dass wir alles haben.
RUN apk add --no-cache \
    nginx \
    php83 \
    php83-fpm \
    php83-sqlite3 \
    php83-session \
    php83-json \
    php83-ctype \
    php83-openssl \
    curl \
    jq

# Assets (Vue, Bootstrap) laden
RUN mkdir -p /app/public/static/js /app/public/static/css /app/public/static/fonts /app/public/static/img && \
    curl -L -o /app/public/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o /app/public/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o /app/public/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js && \
    curl -L -o /app/public/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css && \
    curl -L -o /app/public/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css && \
    curl -L -o /app/public/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# Nginx Config für PHP Weiterleitung erstellen
# Wir nutzen default.conf in /etc/nginx/http.d/ (Standard in Alpine)
RUN echo 'server { \
    listen 8080 default_server; \
    root /app/public; \
    index index.php; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/http.d/default.conf

# App Code kopieren
COPY app /app
COPY icon.png /app/public/static/img/favicon.png
COPY run.sh /run.sh

RUN chmod +x /run.sh

# Arbeitsverzeichnis
WORKDIR /app

CMD [ "/run.sh" ]