FROM python:3.9-slim

# System-Abhängigkeiten installieren
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Python Abhängigkeiten
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Code kopieren
COPY . .

# Environment Variablen
ENV PYTHONUNBUFFERED=1
ENV DB_FOLDER=/data

# Start-Script ausführbar machen
RUN chmod +x run.sh

# Port 8080 für Ingress/HTTP
EXPOSE 8080

CMD ["./run.sh"]