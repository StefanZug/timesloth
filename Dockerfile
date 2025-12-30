# Wir nutzen explizit das 3.21 Image (das beinhaltet Alpine 3.21)
FROM ghcr.io/home-assistant/aarch64-base:3.21

# Pakete installieren: Nginx, PHP8.4 und Extensions
# Hinweis: Wir nutzen 'php84' Pakete.
RUN apk add --no-cache \
    nginx \
    php84 \
    php84-fpm \
    php84-sqlite3 \
    php84-session \
    php84-json \
    php84-ctype \
    php84-openssl \
    php84-curl \
    php84-mbstring \
    curl \
    jq

# Verlinkung erstellen, damit 'php' Befehl funktioniert (optional, aber praktisch)
RUN ln -sf /usr/bin/php84 /usr/bin/php

# Assets (Vue, Bootstrap) laden
RUN mkdir -p /app/public/static/js /app/public/static/css /app/public/static/fonts /app/public/static/img && \
    curl -L -o /app/public/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o /app/public/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o /app/public/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js && \
    curl -L -o /app/public/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css && \
    curl -L -o /app/public/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css && \
    curl -L -o /app/public/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

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

# Code kopieren
COPY app /app
COPY icon.png /app/public/static/img/favicon.png
COPY run.sh /run.sh
RUN chmod +x /run.sh

WORKDIR /app
CMD [ "/run.sh" ]