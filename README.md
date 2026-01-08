# 🦥 TimeSloth

**Professional Time Tracking for Sloths.**
*Effizient faul sein – mit präziser Erfassung.*

TimeSloth ist ein spezialisiertes Zeiterfassungstool, optimiert für komplexe Gleitzeit-Modelle mit Home-Office-Quoten, SAP-Integration und strengen "Arzt-Regeln". Es ist als Docker-Container (speziell für Home Assistant Add-ons) konzipiert.

⚠️ **Zweck:** Es dient als Planungshilfe zur Kontrolle der Büro-Anwesenheit (Office Quota). Es ersetzt kein SAP, sondern hilft, das SAP-Ziel (Quote) zu erreichen.

---

## 🚀 Features

* **Responsive Design:** "Mobile First" für Unterwegs, plus mächtiges 3-Spalten-Cockpit für den Desktop.
* **Smart Input:** Unterstützt Eingaben wie 0800, 8, 08:00. Das UI wurde auf HH:mm optimiert.
* **Live Prognose:** Zeigt im Dashboard an, wann man gehen darf (Soll) und wann man gehen muss (10h Limit).
* **Quota-Rechner:** Berechnet, wie viele Tage man noch ins Büro muss, um das 40% Ziel zu erreichen.
* **Urlaubsplaner:** Interaktiver Jahreskalender inkl. Feiertags-Visualisierung.
* **Markdown Notizen:** Tages-Notizen und Status-Infos unterstützen Markdown-Formatierung.
* **Überstundenpauschale:** Automatische Verrechnung einer monatlichen Pauschale (z.B. 10h) vor dem Gleitzeit-Aufbau.
* **Admin Panel:** Verwaltung von Usern und globalen Feiertagen.

---

## 🧠 Business Logic & Rechenregeln

*Hinweis: Die Rechenlogik ist zentral in `app/public/static/js/core/TimeLogic.js` isoliert.*

### 1. SAP vs. CATS (Das Zwei-Konten-Modell)
Das System unterscheidet strikt zwischen zwei Zeit-Typen:
* **SAP (Gleitzeit/Anwesenheit):** Die Zeit, die physisch oder digital "da" war. Relevant für das Gleitzeitkonto.
* **CATS (Verrechnung):** Die Zeit, die an Kunden verrechnet werden darf.
* *Regel:* `CATS = SAP - Arztbesuche`.

### 2. Die "Arzt-Regel" (Doctor Logic)
Arztbesuche sind ein Sonderfall.
* Sie zählen als **Arbeitszeit (SAP)**, aber **nicht** als verrechenbare Zeit (CATS = 0).
* **Wichtig:** Sie zählen NUR im fiktiven Normalarbeitszeit-Fenster von **08:00 bis 16:12 Uhr** (bei Vollzeit).

### 3. Berechnung der Büro-Quote
Um Diskrepanzen zu SAP zu vermeiden, nutzen wir statistische Mittelwerte.
* **Quote (40%):** `Büro-Ziel = Monats-Soll * 0,40`
* **Abzüge:** Jeder Tag mit Status **F** (Feiertag), **U** (Urlaub) oder **K** (Krank) reduziert das *Büro-Ziel* sofort.

### 4. Pausen-Automatik
* Ab **6,01 Stunden** reiner Arbeitszeit (SAP) sind **30 Minuten** Pause Pflicht.
* **Intelligente Anrechnung:** TimeSloth erkennt Lücken zwischen Zeitblöcken automatisch als Pause an.

### 5. Überstundenpauschale (Flatrate)
User können eine monatliche Pauschale (z.B. 10h) hinterlegen.
* **Bucket-Prinzip:** Positive Tagessalden fließen *zuerst* in den Pauschalen-Topf.
* **Fairness-Regel:** An Tagen mit Status F/U/K wird automatisch der durchschnittliche Tagesanteil der Pauschale gutgeschrieben (`Monatspauschale / 22`), um Nachteile durch Abwesenheit zu vermeiden.

---

## 🛠 Tech Stack & Architektur

TimeSloth nutzt einen modernen, leichtgewichtigen PHP-Stack mit MVC-ähnlicher Architektur.

* **Server:** Nginx + PHP 8.5 (via PHP-FPM) auf Alpine Linux.
* **Backend:** PHP mit Controller/Service Pattern (kein Framework).
* **Frontend:** Vue.js 3 (CDN) + Bootstrap 5.
* **Datenbank:** SQLite (`/data/timesloth.sqlite`).

### Projektstruktur (v0.1.9+)

```text
/app
  /src
    /Controllers     # Steuerung (Auth, Api, Page, Admin)
    /Services        # Geschäftslogik & DB-Queries (EntryService, UserService...)
    /Router.php      # Zentraler Request-Verteiler
    /db.php          # Datenbank-Verbindung
  /templates         # PHP Views
    /partials        # Wiederverwendbare Komponenten (Dashboard Widgets)
  /public
    /static
      /js            # Vue.js Applikation & Core Logic
      /css           # Custom Styling (Variables, Theming)
    index.php        # Entry Point


### Datenfluss
1. **Request:** Alle Anfragen gehen zentral an `index.php`, welche den `Router.php` initialisiert.
2. **Routing:** Der Router analysiert die URL und ruft den passenden Controller (z.B. `ApiController`, `PageController`) auf.
3. **Logic:** Der Controller nutzt Services (z.B. `EntryService`), um Geschäftslogik auszuführen oder Datenbankabfragen zu tätigen.
4. **Response:** Der Controller gibt das Ergebnis zurück – entweder als JSON-Daten (API) oder als gerenderte HTML-View (Frontend).

---

## 💾 Datenbank Schema (SQLite)

**Table `users`**
* `id`, `username`, `password_hash`, `is_admin`, `is_active`
* `settings` (JSON) -> Enthält `percent`, `sollStunden`, `vacationDays`, `overtimeFlatrate` etc.
* `pw_last_changed` -> Zeitstempel der letzten Passwortänderung.

**Table `entries`**
* `user_id`, `date_str`
* `data` (JSON) -> Array von Zeitblöcken (Start, Ende, Typ)
* `status` ('F', 'U', 'K')
* `comment` (Tages-Notiz, Markdown support)
* `status_note` (Kurznotiz zum Status, z.B. "Urlaub: Kroatien")

**Table `global_holidays`**
* `id`, `date_str`, `name`
* Zentrale Feiertage, die im Admin-Panel verwaltet werden.

**Table `login_log`**
* `user_id`, `timestamp`, `ip_address`, `user_agent`
* Historie der letzten Logins zur Sicherheitsüberprüfung.

---

## 🎨 Credits
Logo & Design inspired by laziness.
Built with ❤️ , 🍺 and <img src="https://www.google.com/search?q=https://upload.wikimedia.org/wikipedia/commons/8/8a/Google_Gemini_logo.svg" height="16" style="vertical-align: middle;" alt="Gemini">