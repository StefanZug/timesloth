# Changelog

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