# Changelog

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