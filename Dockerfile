ARG BUILD_FROM
FROM $BUILD_FROM

# 1. Installiere notwendige Pakete (Python, Pip & Curl für Downloads)
RUN apk add --no-cache python3 py3-pip curl

# Arbeitsverzeichnis erstellen
WORKDIR /app

# Requirements kopieren
COPY requirements.txt .

# --- FIX FÜR PEP 668 ---
# Das Flag --break-system-packages ist zwingend nötig für neuere Alpine Versionen
RUN pip3 install --no-cache-dir --break-system-packages -r requirements.txt

# Den restlichen Code kopieren
COPY . .

# --- AUTOMATISCHER DOWNLOAD DER ASSETS (Proxy-Bypass) ---
# Ordnerstruktur erstellen
RUN mkdir -p app/static/js app/static/css app/static/fonts

# 1. JavaScript Libraries laden
RUN curl -L -o app/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o app/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o app/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

# 2. CSS laden (KORRIGIERT: Zeigt jetzt auf die richtige Bootstrap CSS Datei)
RUN curl -L -o app/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

# 3. Icons laden (CSS + Font Datei)
RUN curl -L -o app/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css
RUN curl -L -o app/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# Berechtigungen setzen
RUN chmod +x run.sh

CMD [ "/app/run.sh" ]