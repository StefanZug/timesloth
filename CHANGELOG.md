# Changelog

## 0.1.9 (The Refactoring 3.0)
**Architecture & Code Quality:**
- **Controller Pattern:** Einführung einer MVC-ähnlichen Struktur. Die Logik wurde aus der monolithischen `api.php` und `index.php` in dedizierte Controller (`ApiController`, `PageController`, `AdminController`, `AuthController`) verschoben.
- **Router:** Ein neuer `Router.php` übernimmt nun die zentrale Verteilung der Anfragen, was die `index.php` massiv entschlackt.
- **View Partials:** Das Dashboard-Template (`dashboard.php`) wurde in handliche Module zerlegt (`day_view.php`, `month_table.php`, `stats_sidebar.php`), um die Wartbarkeit zu erhöhen.

**Frontend & Design:**
- **CSS Cleanup:** Die `custom.css` wurde komplett neu strukturiert (Base, Components, Utilities) und von "Notfall-Fixes" bereinigt.
- **Inline-Styles entfernt:** Hunderte Zeilen Inline-CSS (`style="..."`) wurden aus den PHP-Templates entfernt und durch saubere CSS-Klassen ersetzt.
- **Konsistenz:** Vereinheitlichung von Abständen und Farben durch zentrale CSS-Variablen.

---


## 0.1.8 (The Notes Update)
**Features:**
- **Status-Notizen:** Bei Status F, U oder K kann nun eine spezifische Notiz hinterlegt werden (z.B. "Urlaub: Kroatien" oder "Krank: Grippe"). Diese Info wird im Kalender priorisiert angezeigt.
- **Markdown Support:** Tages-Notizen und Kommentare unterstützen nun Markdown-Formatierung (`**Fett**`, `* Kursiv`, `- Listen`, `### Überschriften`).
- **Erweiterte Tooltips:** Im Jahreskalender und der Monatsansicht werden Notizen beim Mouseover (Tooltip) nun detailliert angezeigt.
- **Offline-First:** Markdown-Bibliotheken (Marked & DOMPurify) werden nun lokal ausgeliefert statt via CDN, für besseren Datenschutz und Offline-Fähigkeit.

**UX & Design:**
- **Smarte Monatsansicht:** Das Notizfeld in der Tabelle zeigt nun im geschlossenen Zustand eine Vorschau an. Beim Klick öffnet sich nur noch der Editor (keine doppelte Vorschau mehr), was Platz spart.
- **Input-Feedback:** Textfelder in der Tabelle haben nun einen sichtbaren Rahmen beim Hovern, damit sie leichter als Eingabefelder erkennbar sind.
- **Jahresplaner:** Zeigt nun auch Status-Notizen an (z.B. "Urlaub (Kroatien)") statt nur pauschal "Urlaub".

**Technical & Database:**
- **Migration:** Automatische Erweiterung der `entries` Tabelle um die Spalte `status_note`.

---

## 0.1.7 (The Überstundenpauschale Update)
**Features:**
- **Überstundenpauschale:** Eine monatliche Pauschale (z.B. 10h) kann nun in den Settings hinterlegt werden. Überstunden füllen zuerst diesen "Topf", bevor sie das Gleitzeitkonto erhöhen.

**Technical & Database:**
- **Update Alpine 3.23 & PHP 8.5:** Base-Image wurde auf Alpine 3.23 aktualisiert und läuft nun mit PHP 8.5.
- **Refactoring:** Der Code wurde bereinigt. Inline-Styles wurden aus den PHP-Templates entfernt und in eine zentrale CSS-Datei ausgelagert.

**Performance & UX:**
- **Mobile Fixes:** Diverse Optimierungen für kleine Bildschirme (Sticky-Date Spalte, angepasste Input-Größen, bessere Lesbarkeit).

---

## 0.1.6 (The "Holiday" Update)
**Features:**
- **Jahres-Urlaubsplaner:** Ein neuer Kalender-Modal (erreichbar über das "Urlaubskonto" Widget) zeigt das komplette Jahr. Urlaubstage können dort direkt per Klick gesetzt oder entfernt werden.
- **Globale Feiertage:** Im Admin-Panel können nun zentrale Feiertage angelegt werden, die für alle User gelten.
- **Wochenend-Logik:** Der Urlaubs-Counter ignoriert nun korrekt Wochenenden. Ein Urlaub von Fr-Mo zählt nur noch als 2 Tage, nicht 4.

**Technical & Database:**
- **Neue Tabelle:** `global_holidays` wurde zur Datenbank hinzugefügt.
- **API Erweiterung:** Neuer Endpunkt `/api/get_year_stats` liefert Jahresdaten für den Kalender.

**Performance & UX:**
- **Optimistic UI Updates:** Status-Änderungen (F, U, K) werden nun sofort im Browser angewendet, noch bevor der Server antwortet. Das "Laggy"-Gefühl ist weg.
- **Input Design:** Die Zeiteingabefelder in der Monatsansicht wurden vergrößert und zeigen keine Sekunden mehr an (`HH:mm`), um das "gequetschte" Layout zu beheben.
- **Smart Formatting:** Sekunden werden im Frontend nun standardmäßig ausgeblendet, solange sie `00` sind.

**Fixes:**
- Fix: Layout-Probleme in der Monatsansicht bei schmalen Bildschirmen.

## 0.1.5 (The "Smooth Operator" Update)
**Dashboard & Usability:**
- **Robustes Mausrad-Scrollen:** Das Timepicker-Verhalten am Desktop wurde komplett neu geschrieben. Es nutzt nun Textfelder (`type="text"`) für präzise Kontrolle via Cursor-Position (Stunden/Minuten/Sekunden) ohne ungewolltes Springen.
- **Scroll-Bremse:** Eine intelligente Verzögerung verhindert, dass empfindliche Touchpads die Zahlen "durchdrehen" lassen.
- **Smart Input Fix:** Eingaben wie `0900` werden am PC nun wieder korrekt zu `09:00` formatiert.
- **Verzögertes Speichern:** Der Auto-Save wartet nun 1,5 Sekunden nach der letzten Eingabe, um Cursor-Sprünge während des Tippens/Scrollens zu verhindern.
- **Dauer-Anzeige:** In der Monatsliste wird nun neben jedem Zeitblock die berechnete Dauer (z.B. "4,5h") angezeigt.

**Gleitzeit & Logik:**
- **Erweiterte Saldo-Anzeige:** Im Tagesfazit wird nun unterschieden zwischen "Saldo Vortag" und "Saldo Aktuell" (inkl. heutiger Arbeitszeit).
- **Start-Saldo Edit:** Der Übertrag aus dem Vormonat (Start-Korrektur) kann nun direkt im Dashboard per Klick auf den Saldo bearbeitet werden.
- **Smartere Pausen:** Lücken zwischen Zeitblöcken werden nun intelligent als Pause angerechnet. Der automatische 30min-Abzug greift nur noch, wenn die echte Pause zu kurz war.
- **Kein Übertrag beim Scrollen:** Minuten-Scrollen ändert nicht mehr die Stunden (und Sekunden nicht mehr die Minuten), um versehentliche Änderungen zu vermeiden.

**Design & Fun:**
- **Sleepy Blink Animation:** Beim Theme-Wechsel (Hell/Dunkel) fallen dem TimeSloth nun "müde die Augen zu".
- **Logo Animation:** Das Faultier dreht sich nun langsamer und macht einen kleinen "Pop" (Belly-Flop) beim Anklicken.
- **UI Polish:** Die "Gehen" und "Max" Boxen im Dashboard wurden optisch an das restliche Design angepasst (graue Boxen).

**System & Fixes:**
- **Admin Stats:** Fehler behoben, durch den im Admin-Panel (und Log) oft "0 MB" Datenbankgröße angezeigt wurde.
- **Startup Log:** Die Datenbankgröße wird nun beim Start des Containers im Log ausgegeben.
- **Mobile Fixes:** Diverse Anpassungen, damit die Eingabefelder auf Mobilgeräten nativ bleiben, während sie am PC erweitert sind.

---

## 0.1.4 (The UI/UX Update)
**Design & UX:**
- **Neues Dashboard Layout:** Auf großen Bildschirmen (Desktop) wechselt TimeSloth nun automatisch in ein 3-Spalten-Grid ("Cockpit View").
- **Sticky Sidebar:** Die Statistik- und Quoten-Boxen bleiben am PC beim Scrollen sichtbar.
- **Dark Mode 2.0:** Komplettes Redesign des Darkmodes. Transparenzen wurden durch feste High-Contrast-Farben ersetzt (bessere Lesbarkeit, inspirieret von GitHub).
- **Settings Redesign:** Die Einstellungen wurden komplett überarbeitet und in übersichtliche Tabs (Berechnung, Interface, Account) gegliedert.
- **Widget-Look:** Einführung von "Widget Cards" für eine konsistentere Optik.

**Technical:**
- **Refactoring:** Die JavaScript-Logik der Einstellungsseite wurde von PHP getrennt und in `static/js/pages/settings.js` ausgelagert.
- **CSS Variablen:** Einführung zentraler CSS-Variablen für Status-Farben (Office, Home, Sick), die sich automatisch dem Theme anpassen.

---

## 0.1.3 (The Refactoring Release) (https://github.com/StefanZug/timesloth/issues/18)
**Technical Debt & Architecture:**
- **Backend:** Einführung einer Service-Architektur. Die monolithische `api.php` wurde in `EntryService`, `UserService` und `AdminService` aufgeteilt.
- **Frontend:** Trennung von Code und Design. Die Vue.js-App wurde aus `dashboard.php` in `static/js/pages/dashboard.js` ausgelagert.
- **Core Logic:** Die Berechnungslogik (SAP/CATS, Arzt-Regeln, Quoten) wurde zentral in `static/js/core/TimeLogic.js` isoliert. Das erleichtert Wartung und Tests.
- **Performance:** JavaScript-Dateien sind nun statisch und können vom Browser gecacht werden.

---

## 0.1.2.1
- Fix: SAP Zeitrechnung (https://github.com/StefanZug/timesloth/issues/15)
- Fix: Url in config.yaml
- Neu: Changelog.md

---

## 0.1.2.0
- Fix: Berechnung der Büro-Quote.
- Neu: Einstellungen für "Monatliche Korrektur" hinzugefügt.
- Fix: Issue mit Login-Session behoben.
- Siehe auch GitHub Issue [#5](https://github.com/StefanZug/timesloth/issues/5).

---

## 0.1.1.6
- Erstes Release der PHP-Version.
- Umstellung von Python auf Alpine/PHP 8.4.