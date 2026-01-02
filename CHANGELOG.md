# Changelog

## 0.1.4.0 (The UI/UX Update)

**Design & UX:**
- **Neues Dashboard Layout:** Auf großen Bildschirmen (Desktop) wechselt TimeSloth nun automatisch in ein 3-Spalten-Grid ("Cockpit View").
- **Sticky Sidebar:** Die Statistik- und Quoten-Boxen bleiben am PC beim Scrollen sichtbar.
- **Dark Mode 2.0:** Komplettes Redesign des Darkmodes. Transparenzen wurden durch feste High-Contrast-Farben ersetzt (bessere Lesbarkeit, inspirieret von GitHub).
- **Settings Redesign:** Die Einstellungen wurden komplett überarbeitet und in übersichtliche Tabs (Berechnung, Interface, Account) gegliedert.
- **Widget-Look:** Einführung von "Widget Cards" für eine konsistentere Optik.

**Technical:**
- **Refactoring:** Die JavaScript-Logik der Einstellungsseite wurde von PHP getrennt und in `static/js/pages/settings.js` ausgelagert.
- **CSS Variablen:** Einführung zentraler CSS-Variablen für Status-Farben (Office, Home, Sick), die sich automatisch dem Theme anpassen.

## 0.1.3.0 (The Refactoring Release) (https://github.com/StefanZug/timesloth/issues/18)

**Technical Debt & Architecture:**
- **Backend:** Einführung einer Service-Architektur. Die monolithische `api.php` wurde in `EntryService`, `UserService` und `AdminService` aufgeteilt.
- **Frontend:** Trennung von Code und Design. Die Vue.js-App wurde aus `dashboard.php` in `static/js/pages/dashboard.js` ausgelagert.
- **Core Logic:** Die Berechnungslogik (SAP/CATS, Arzt-Regeln, Quoten) wurde zentral in `static/js/core/TimeLogic.js` isoliert. Das erleichtert Wartung und Tests.
- **Performance:** JavaScript-Dateien sind nun statisch und können vom Browser gecacht werden.

## 0.1.2.1
- Fix: SAP Zeitrechnung (https://github.com/StefanZug/timesloth/issues/15)
- Fix: Url in config.yaml
- Neu: Changelog.md

## 0.1.2.0

- Fix: Berechnung der Büro-Quote.
- Neu: Einstellungen für "Monatliche Korrektur" hinzugefügt.
- Fix: Issue mit Login-Session behoben.
- Siehe auch GitHub Issue [#5](https://github.com/StefanZug/timesloth/issues/5).

## 0.1.1.6

- Erstes Release der PHP-Version.
- Umstellung von Python auf Alpine/PHP 8.4.