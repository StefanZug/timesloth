FROM ghcr.io/home-assistant/aarch64-base:3.21

# Pakete installieren
RUN apk add --no-cache \
    nginx \
    php84 \
    php84-fpm \
    php84-pdo \
    php84-pdo_sqlite \
    php84-sqlite3 \
    php84-session \
    php84-json \
    php84-ctype \
    php84-openssl \
    php84-curl \
    php84-mbstring \
    curl \
    jq

# PHP Verlinkung
RUN ln -sf /usr/bin/php84 /usr/bin/php

# LOGGING FIX: Nginx Logs auf stdout/stderr umleiten für Home Assistant
RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

# Assets laden
RUN mkdir -p /app/public/static/js /app/public/static/css/fonts /app/public/static/img && \
    curl -f -L -o /app/public/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js && \
    curl -f -L -o /app/public/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -f -L -o /app/public/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js && \
    curl -f -L -o /app/public/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css && \
    curl -f -L -o /app/public/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css && \
    curl -f -L -o /app/public/static/css/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2 && \
    curl -f -L -o /app/public/static/css/fonts/bootstrap-icons.woff https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff

# Nginx Config
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

COPY app /app
COPY icon.png /app/public/static/img/favicon.png
COPY logo.png /app/public/static/img/logo.png
COPY run.sh /run.sh
RUN chmod +x /run.sh

WORKDIR /app
CMD [ "/run.sh" ]