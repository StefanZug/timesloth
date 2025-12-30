# Wir nutzen direkt das Image für aarch64 (Raspberry Pi 64-bit)
# Kein ARG BUILD_FROM mehr nötig!
FROM ghcr.io/home-assistant/aarch64-base-python:3.12-alpine3.19

# 1. Installiere notwendige Pakete (jq für config parsing im run.sh wichtig)
RUN apk add --no-cache python3 py3-pip curl jq

# Arbeitsverzeichnis
WORKDIR /app

# 2. Python Dependencies
COPY requirements.txt .
RUN pip3 install --no-cache-dir --break-system-packages -r requirements.txt

# 3. ASSETS DOWNLOAD
# Hinweis: Wir lassen die Versionen (@3, @5.3.2) absichtlich drin,
# damit das Layout stabil bleibt und nicht durch Updates zerschossen wird.
RUN mkdir -p app/static/js app/static/css app/static/fonts app/static/img && \
    curl -L -o app/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o app/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o app/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js && \
    curl -L -o app/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css && \
    curl -L -o app/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css && \
    curl -L -o app/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# 4. Code kopieren
COPY . .

# Favicon an den richtigen Platz
COPY icon.png app/static/img/favicon.png

# ENV & Berechtigungen
ENV DB_FOLDER=/data
RUN chmod +x run.sh

CMD [ "/app/run.sh" ]