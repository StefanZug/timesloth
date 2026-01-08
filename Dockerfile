FROM ghcr.io/home-assistant/aarch64-base:3.23

ARG PHP_PKG="php85"

RUN apk add --no-cache \
    nginx \
    ${PHP_PKG} \
    ${PHP_PKG}-fpm \
    ${PHP_PKG}-pdo \
    ${PHP_PKG}-pdo_sqlite \
    ${PHP_PKG}-sqlite3 \
    ${PHP_PKG}-session \
    ${PHP_PKG}-json \
    ${PHP_PKG}-ctype \
    ${PHP_PKG}-openssl \
    ${PHP_PKG}-curl \
    ${PHP_PKG}-mbstring \
    curl \
    jq \
    tzdata

ENV TZ=Europe/Vienna

# PHP Verlinkung
RUN ln -sf /usr/bin/${PHP_PKG} /usr/bin/php

# Logs umleiten
RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

COPY app /app
COPY icon.png /app/public/static/img/favicon.png
COPY logo.png /app/public/static/img/logo.png
COPY run.sh /run.sh
RUN chmod +x /run.sh

WORKDIR /app
CMD [ "/run.sh" ]