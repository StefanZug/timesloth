# 🦥 TimeSloth

**Professional Time Tracking for Sloths.**
*Effizient faul sein – mit präziser Erfassung.*

TimeSloth ist ein spezialisiertes Zeiterfassungstool, optimiert für komplexe Gleitzeit-Modelle mit Home-Office-Quoten, SAP-Integration und strengen "Arzt-Regeln". Es ist als Docker-Container (speziell für Home Assistant Add-ons) konzipiert.

⚠️ **Zweck:** Es dient als Planungshilfe zur Kontrolle der Büro-Anwesenheit (Office Quota). Es ersetzt kein SAP, sondern hilft, das SAP-Ziel (Quote) zu erreichen.

---

## 🧠 Business Logic & Rechenregeln

*Hinweis: Seit v0.1.3.0 ist die Rechenlogik zentral in `app/public/static/js/core/TimeLogic.js` isoliert.*

### 1. SAP vs. CATS (Das Zwei-Konten-Modell)
Das System unterscheidet strikt zwischen zwei Zeit-Typen:
* **SAP (Gleitzeit/Anwesenheit):** Die Zeit, die physisch oder digital "da" war. Relevant für das Gleitzeitkonto.
* **CATS (Verrechnung):** Die Zeit, die an Kunden verrechnet werden darf.
* *Regel:* `CATS = SAP - Arztbesuche`.

### 2. Die "Arzt-Regel" (Doctor Logic)
Arztbesuche sind ein Sonderfall.
* Sie zählen als **Arbeitszeit (SAP)**, aber **nicht** als verrechenbare Zeit (CATS = 0).
* **Wichtig:** Sie zählen NUR im fiktiven Normalarbeitszeit-Fenster von **08:00 bis 16:12 Uhr** (bei Vollzeit).
* *Beispiel:* Ein Arztbesuch von 07:00 bis 09:00 Uhr zählt für SAP nur 1 Stunde (08:00-09:00). Die Zeit davor verpufft.

### 3. Berechnung der Büro-Quote (Statistical Average Logic)
Um Diskrepanzen zu SAP zu vermeiden, nutzen wir **nicht** die echten Kalendertage des Monats, sondern einen statistischen Mittelwert (wie SAP).

* **SAP Basis-Berechnung:** `Monats-Soll = (Wochenstunden * 4,33)`
* **Quote (40%):** `Büro-Ziel = Monats-Soll * 0,40`
* **Abzüge (Deduction):** Jeder Tag mit Status **F** (Feiertag), **U** (Urlaub) oder **K** (Krank) reduziert das *Büro-Ziel* sofort um den jeweiligen Tageswert.

### 4. Pausen-Automatik (Smart Logic)
Das System sorgt für die Einhaltung der gesetzlichen Ruhepausen, bestraft aber keine echten Pausen.
* Ab **6,01 Stunden** reiner Arbeitszeit (SAP) sind **30 Minuten** Pause Pflicht.
* **Intelligente Anrechnung:** TimeSloth erkennt Lücken zwischen eingetragenen Zeitblöcken automatisch als Pause an.
* *Beispiel:* Wer von 12:00 bis 12:30 Uhr nicht eingestempelt ist (Lücke), hat seine Pflichtpause erfüllt – es erfolgt **kein** zusätzlicher Abzug. Wer durcharbeitet, bekommt die fehlende Zeit automatisch abgezogen.

### 5. Monatliche Korrektur (Start-Saldo)
Da TimeSloth statistisch rechnet und nicht mit der SAP-Datenbank verbunden ist, muss der **Gleitzeit-Saldo** initial synchronisiert werden.
* Im Dashboard kann der **Start-Saldo** (Übertrag aus dem Vormonat laut SAP) direkt eingetragen werden.
* Dieser Wert dient als Basis für die laufende Hochrechnung des aktuellen Monats.

### 6. Urlaubszählung (Vacation Logic)
* Urlaubszählung: Wochenenden (Sa/So) werden bei der Berechnung der verbrauchten Urlaubstage automatisch ignoriert, auch wenn sie im Zeitraum liegen.

### 7. Überstundenpauschale (Optional)
User können in den Einstellungen eine monatliche Pauschale (z.B. 10h) hinterlegen.
* **Bucket-Prinzip:** Positive Tagessalden fließen *zuerst* in den Pauschalen-Topf. Erst wenn dieser für den Monat voll ist, wächst das Gleitzeitkonto.
* **Minusstunden:** Diese reduzieren das Gleitzeitkonto *sofort*. Die Pauschale schützt nicht vor Abzügen, sie "frisst" nur die Plusstunden.

---

## 🛠 Tech Stack & Architektur

Wir nutzen einen leichtgewichtigen PHP-Stack mit Service-Architektur.

* **Server:** Nginx + PHP 8.5 (via PHP-FPM) auf Alpine Linux.
* **Backend:** PHP mit Service-Klassen (`/app/src/Services/`), Plain PDO für SQLite.
* **Frontend:** Vue.js 3 (CDN) + Bootstrap 5. Die Logik ist vom View getrennt (`/static/js/pages/`).
* **Datenbank:** SQLite (`/data/timesloth.sqlite`) für Persistenz.

### Projektstruktur (v0.1.3+)

/app
  /src
    /Services        # PHP Geschäftslogik (EntryService, UserService...)
    /db.php          # Datenbank-Verbindung
    /auth.php        # Session-Handling
  /templates         # PHP Views (HTML Gerüst)
  /public
    /static
      /js
        /core        # Reine JS-Rechenlogik (TimeLogic.js)
        /pages       # Vue-App Logik pro Seite (dashboard.js)
      /css           # Custom Styling
    index.php        # Router & Controller (API)


### API & Datenfluss
1. **Frontend:** Vue.js lädt Daten via JSON von `/api/*`.
2. **Router:** `index.php` nimmt den Request entgegen.
3. **Services:** Die Logik (SQL, Validierung) wird von den Klassen unter `/src/Services` ausgeführt.
4. **Response:** JSON geht zurück an das Frontend.

### Datenbank Schema (SQLite)

**Table `users`**
* `id` (INT, PK), `username` (TEXT), `password_hash` (TEXT)
* `settings` (TEXT, JSON) -> Enthält `percent`, `sollStunden`, `correction` etc.
* `is_admin` (INT)

**Table `entries`**
* `user_id` (INT, FK)
* `date_str` (TEXT, "YYYY-MM-DD")
* `data` (TEXT, JSON) -> Array von Zeitblöcken
* `status` (TEXT) -> 'F', 'U', 'K' oder NULL
* `comment` (TEXT)

**Table `global_holidays`** (Neu in v0.1.6)
* `id` (INT, PK)
* `date_str` (TEXT, "YYYY-MM-DD", Unique)
* `name` (TEXT) -> Name des Feiertags (z.B. "Neujahr")

---

## 🚀 Features

* **Responsive Design:** "Mobile First" für Unterwegs, plus mächtiges 3-Spalten-Cockpit für den Desktop.
* **Smart Input:** Unterstützt Eingaben wie 0800, 8, 08:00. Das UI wurde auf HH:mm optimiert, um Platz zu sparen, speichert im Hintergrund aber sekundengenau
* **Live Prognose:** Zeigt im Dashboard an, wann man gehen darf (Soll) und wann man gehen muss (10h Limit).
* **Instant Feedback:** Optimierte Oberfläche reagiert sofort auf Eingaben (Optimistic UI).
* **Quota-Rechner:** Berechnet, wie viele Tage man noch ins Büro muss, um das 40% Ziel zu erreichen.
* **Admin Panel:** Verwaltung von Usern und globalen Feiertagen.
* **Urlaubsplaner:** Interaktive Jahresübersicht zur Planung.
* **Feiertags-Management:** Globale Feiertage via Admin-Panel.
* **Instant Feedback:** Optimierte Oberfläche reagiert sofort auf Eingaben (Optimistic UI).