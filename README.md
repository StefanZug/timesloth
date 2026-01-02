# 🦥 TimeSloth

**Professional Time Tracking for Sloths.**
*Effizient faul sein – mit präziser Erfassung.*

TimeSloth ist ein spezialisiertes Zeiterfassungstool, optimiert für komplexe Gleitzeit-Modelle mit Home-Office-Quoten, SAP-Integration und strengen "Arzt-Regeln". Es ist als Docker-Container (speziell für Home Assistant Add-ons) konzipiert.

⚠️ **Zweck:** Es dient als Planungshilfe zur Kontrolle der Büro-Anwesenheit (Office Quota). Es ersetzt kein SAP, sondern hilft, das SAP-Ziel (Quote) zu erreichen.

---

## 🧠 Business Logic & Rechenregeln (WICHTIG FÜR AI)

Wenn du als AI diesen Code bearbeitest, beachte bitte zwingend folgende Logik-Regeln, die in diesem Projekt hart definiert sind:

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

* **Basis-Berechnung:** `Monats-Soll = (Wochenstunden * 52 Wochen) / 12 Monate`
* **Quote (40%):** `Büro-Ziel = Monats-Soll * 0,40`
* **Abzüge (Deduction):**
  Jeder Tag mit Status **F** (Feiertag), **U** (Urlaub) oder **K** (Krank) reduziert das *Büro-Ziel* sofort um den jeweiligen Tageswert.
* **Tageswert:**
  Es gibt keine Unterscheidung mehr zwischen Mo-Do und Fr. Jeder Tag zählt pauschal: `Wochenstunden / 5`.

*Warum?* Damit TimeSloth auch in langen Monaten (wie Januar) exakt das gleiche Ziel anzeigt wie SAP, welches mit Durchschnittswerten rechnet.

### 4. Beschäftigungsausmaß
Der User kann in den Settings sein Ausmaß einstellen (Slider 10-100%).
* **Basis (100%):** 38,5h Woche / 7,70h Tag.
* Alle Berechnungen (Soll, Quoten-Abzug, Statistik) skalieren automatisch linear anhand dieses Prozentsatzes.

### 5. Pausen-Automatik
* Ab **6,01 Stunden** reiner Arbeitszeit (SAP) werden automatisch **30 Minuten** abgezogen.
* Bei exakt **6,00 Stunden** oder weniger erfolgt kein Abzug.

---

## 🛠 Tech Stack & Architektur

Wir nutzen einen leichtgewichtigen PHP-Stack ohne großes Framework ("Keep it simple").

* **Server:** Nginx + PHP 8.4 (via PHP-FPM) auf Alpine Linux.
* **Backend:** Native PHP (`/app/src/`), keine Router-Libraries, Plain PDO für SQLite.
* **Frontend:** HTML5 + Vue.js 3 (`/app/templates/`), via CDN, Standalone-Build ohne Webpack/Build-Steps.
* **Styling:** Bootstrap 5 mit Custom CSS ("Nextcloud-Style", Dark Mode Support).
* **Datenbank:** SQLite (`/data/timesloth.sqlite`) für Persistenz.

### Besonderheiten im Code
* **Vue.js:** Nutzt die `[[ ]]` Delimiter statt `{{ }}`, um Konflikte mit PHP-Templating zu vermeiden.
* **API-Design:** Das Backend dient primär als JSON-API (`api.php`). Die Rechenlogik (Prognosen, Summen) liegt größtenteils im Frontend (`dashboard.php`) in Vue Computed Properties.
* **Speicherung:** Zeitblöcke werden als JSON-Blob in der Spalte `data` gespeichert.

### Datenbank Schema (SQLite)

**Table `users`**
* `id` (INT, PK)
* `username` (TEXT)
* `password_hash` (TEXT)
* `settings` (TEXT, JSON) -> Enthält `percent`, `sollStunden`, `correction` etc.
* `is_admin` (INT)

**Table `entries`**
* `user_id` (INT, FK)
* `date_str` (TEXT, "YYYY-MM-DD") -> Eindeutig pro User
* `data` (TEXT, JSON) -> Array von Blöcken: `[{type: 'office', start: '08:00', end: '16:00'}, ...]`
* `status` (TEXT) -> 'F', 'U', 'K' oder NULL
* `comment` (TEXT)

**Table `global_holidays`**
* `date_str` (TEXT, "YYYY-MM-DD")
* `name` (TEXT)

---

## 🚀 Wichtige Features für Debugging

* **Responsive Design:** "Mobile First" Ansatz mit Sticky Headers.
* **Smart Input:** Unterstützt Eingaben wie `0800`, `8`, `08:00` und Mausrad-Support.
* **Live Prognose:** Zeigt im Dashboard an, wann man gehen darf (Soll) und wann man gehen muss (10h Limit).
* **Admin Panel:** Verwaltung von Usern und globalen Feiertagen.