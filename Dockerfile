ARG BUILD_FROM
FROM $BUILD_FROM

# 1. Installiere notwendige Pakete
RUN apk add --no-cache python3 py3-pip curl

# Arbeitsverzeichnis
WORKDIR /app

# Requirements
COPY requirements.txt .

# PEP 668 Fix
RUN pip3 install --no-cache-dir --break-system-packages -r requirements.txt

# Code kopieren
COPY . .

# --- WICHTIG: ENV VAR SETZEN ---
ENV DB_FOLDER=/data

# --- ASSETS DOWNLOAD (Proxy-Bypass & Favicon) ---
# Ordnerstruktur erstellen (inkl. img Ordner für Favicon)
RUN mkdir -p app/static/js app/static/css app/static/fonts app/static/img

# JS laden
RUN curl -L -o app/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o app/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o app/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

# CSS laden
RUN curl -L -o app/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

# Icons laden
RUN curl -L -o app/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css
RUN curl -L -o app/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# --- FAVICON LADEN ---
# Wir laden ein Faultier-Icon
RUN curl -L -o app/static/img/favicon.png https://www.emoji.family/api/emojis/1f9a5/noto/svg.svg

# Berechtigungen
RUN chmod +x run.sh

CMD [ "/app/run.sh" ]