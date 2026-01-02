# 🦥 TimeSloth

**Professional Time Tracking for Sloths.**
*Effizient faul sein – mit präziser Erfassung.*

TimeSloth ist ein spezialisiertes Zeiterfassungstool, optimiert für komplexe Gleitzeit-Modelle mit Home-Office-Quoten, SAP-Integration und strengen "Arzt-Regeln". Es ist als Docker-Container (speziell für Home Assistant Add-ons) konzipiert.

Es dient nur als Hilfe um die vor Ort Anwesenheit (Office Quota) zu kontrollieren und nicht als tatsächliche Zeiterfassung. 
Es soll kein SAP ablösen oder ersetzen.

---

## 🧠 Business Logic & Rechenregeln (WICHTIG FÜR AI)

Wenn du als AI diesen Code bearbeitest, beachte bitte zwingend folgende Logik-Regeln, die in diesem Projekt hart codiert sind:

### 1. SAP vs. CATS (Das zwei-Konten-Modell)
Das System unterscheidet strikt zwischen zwei Zeit-Typen:
* **SAP (Gleitzeit/Anwesenheit):** Die Zeit, die physisch oder digital "da" war. Relevant für das Gleitzeitkonto.
* **CATS (Verrechnung):** Die Zeit, die an Kunden verrechnet werden darf.
* *Regel:* `CATS = SAP - Arztbesuche`.

### 2. Die "Arzt-Regel" (Doctor Logic)
Arztbesuche sind ein Sonderfall.
* Sie zählen als **Arbeitszeit (SAP)**, aber **nicht** als verrechenbare Zeit (CATS = 0).
* **Wichtig:** Sie zählen NUR im fiktiven Normalarbeitszeit-Fenster von **08:00 bis 16:12 Uhr**.
* *Beispiel:* Ein Arztbesuch von 07:00 bis 09:00 Uhr zählt für SAP nur 1 Stunde (08:00-09:00). Die Zeit davor verpufft.

### 3. Büro-Quote (Office Quota)
Mitarbeiter müssen **40%** ihrer Arbeitszeit im Büro verbringen.
* **Ziel-Berechnung:** Das Monats-Soll ist dynamisch: `Anzahl Werktage (Mo-Fr) im Monat * Tagessoll * 0,40`.
* **Abzüge (Deduction):** Tage mit Status **F** (Feiertag), **U** (Urlaub) oder **K** (Krank) reduzieren das Soll-Ziel um den jeweiligen Tageswert (z.B. 3,08h bei Vollzeit).
* *Logik:* Wer krank ist, muss diese Zeit nicht im Büro nachholen.

### 4. Beschäftigungsausmaß (Smart Percentage)
Der User kann in den Settings sein Ausmaß einstellen (z.B. 100%, 50%).
* **Basis (100%):** 38,5h Woche / 7,70h Tag.
* Alle Berechnungen (Soll, Quoten-Abzug, Saldo) skalieren automatisch anhand dieses Prozentsatzes.

### 5. Pausen-Automatik
* Ab **6,01 Stunden** reiner Arbeitszeit (SAP) werden automatisch **30 Minuten** abgezogen, sofern keine Pause gestempelt wurde.
* Wenn man genau **6,00 Stunden** arbeitet, wird keine Pause abgezogen.

---

## 🛠 Tech Stack (Neu: PHP Edition)

Wir haben das Projekt von Python auf einen leichtgewichtigen, nativen PHP-Stack migriert, um die Performance zu steigern und die Image-Größe zu minimieren.

* **Server:** Nginx + PHP 8.4 (via PHP-FPM).
* **Backend:** Native PHP (kein Framework, Plain PDO für SQLite).
* **Frontend:** HTML5 + Vue.js 3 (via CDN, Standalone-Build ohne Webpack).
* **CSS:** Bootstrap 5 (mit Custom Dark Mode Theme).
* **Database:** SQLite (lokal im `/data` Ordner für Persistenz).
* **Container:** Docker (basiert auf Alpine Linux via Home Assistant Base Image).

### Besonderheiten im Code
* **Vue.js:** Nutzt die `[[ ]]` Delimiter statt `{{ }}`, um Konflikte mit serverseitigem Rendering (jetzt PHP, früher Jinja2) zu vermeiden.
* **API-Design:** Das Backend dient primär als JSON-API (`api.php`), das Frontend (`dashboard.php`) übernimmt die Rechenlogik client-seitig.
* **Daten-Struktur:** Zeiten werden als JSON-Blobs (`blocks`) in der SQLite-Datenbank gespeichert, um flexible Mischungen (Home, Office, Arzt an einem Tag) zu ermöglichen.

---

## 🚀 Features

* **Responsive Design:** "Mobile First" Ansatz mit Sticky Headers.
* **Dark Mode:** Vollständige Unterstützung mit modernem "Slate" Theme und Transparenzen ("Nextcloud Style").
* **Smart Input:** Unterstützt Eingaben wie `0800`, `8`, `08:00` und sogar Sekunden (werden kaufmännisch gerundet).
* **Live Prognose:** Zeigt basierend auf dem aktuellen Startzeitpunkt an, wann das Soll (7,7h) und die gesetzliche Höchstgrenze (10h) erreicht sind.
* **Admin Panel:** Verwaltung von Usern und globalen Feiertagen. 
* **Privacy by Design:** Admins können User verwalten, aber keine Zeitbuchungen anderer Personen einsehen.