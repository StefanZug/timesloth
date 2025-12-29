ARG BUILD_FROM
FROM $BUILD_FROM

# 1. Installiere notwendige Pakete (Python, Pip & Curl)
RUN apk add --no-cache python3 py3-pip curl

# Arbeitsverzeichnis erstellen
WORKDIR /app

# Requirements kopieren
COPY requirements.txt .

# PEP 668 Fix: Python Pakete installieren
RUN pip3 install --no-cache-dir --break-system-packages -r requirements.txt

# Den restlichen Code kopieren
COPY . .

# --- WICHTIG: DATENBANK PFAD SETZEN ---
# Damit weiß Python: Speicher die DB in /data (das ist der persistente HA-Ordner)
ENV DB_FOLDER=/data

# --- AUTOMATISCHER DOWNLOAD DER ASSETS ---
RUN mkdir -p app/static/js app/static/css app/static/fonts

# JS laden
RUN curl -L -o app/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o app/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o app/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

# CSS laden
RUN curl -L -o app/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

# Icons laden
RUN curl -L -o app/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css
RUN curl -L -o app/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# Berechtigungen setzen
RUN chmod +x run.sh

CMD [ "/app/run.sh" ]