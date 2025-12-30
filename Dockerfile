ARG BUILD_FROM
ARG TARGETARCH
ARG TARGETVARIANT

# Definiere die Standard-Basis-Images für jede Architektur.
# Dies wird verwendet, wenn der Build-Prozess (wie dieser GitHub Workflow)
# die Variable BUILD_FROM nicht explizit setzt.
ARG BASE_IMAGE_amd64="ghcr.io/home-assistant/amd64-base-python:3.12-alpine3.19"
ARG BASE_IMAGE_arm64="ghcr.io/home-assistant/aarch64-base-python:3.12-alpine3.19"
ARG BASE_IMAGE_armv7="ghcr.io/home-assistant/armv7-base-python:3.12-alpine3.19"

FROM ${BUILD_FROM:-${BASE_IMAGE_${TARGETARCH}${TARGETVARIANT}}}

# 1. Installiere notwendige Pakete
RUN apk add --no-cache python3 py3-pip curl

# Arbeitsverzeichnis
WORKDIR /app

# 2. Python Dependencies (ändern sich selten -> Cache nutzen)
COPY requirements.txt .
RUN pip3 install --no-cache-dir --break-system-packages -r requirements.txt

# --- 3. ASSETS DOWNLOAD (NEUER PLATZ!) ---
# Wir machen das VOR dem Code-Copy. Solange du diese Zeilen nicht änderst,
# nutzt Docker den Cache und lädt nichts neu herunter.
RUN mkdir -p app/static/js app/static/css app/static/fonts app/static/img

# JS & CSS laden
RUN curl -L -o app/static/js/vue.js https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js && \
    curl -L -o app/static/js/axios.js https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js && \
    curl -L -o app/static/js/bootstrap.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js && \
    curl -L -o app/static/css/bootstrap.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

# Icons laden
RUN curl -L -o app/static/css/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css && \
    curl -L -o app/static/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/fonts/bootstrap-icons.woff2

# 4. Jetzt erst den Code kopieren
COPY . .

# Favicon lokal kopieren (statt Download)
COPY icon.png app/static/img/favicon.png

# ENV & Berechtigungen
ENV DB_FOLDER=/data
RUN chmod +x run.sh

CMD [ "/app/run.sh" ]