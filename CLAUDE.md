# BonsaiPress2 — Projektregeln

## Pflicht-Workflow bei jeder Änderung
1. `edit` — contenfiles / SCSS / Templates anpassen
2. `./bonsai static` — statische HTML neu generieren (NIE überspringen)
3. `./bonsai deploy` — nur geänderte Dateien hochladen

## Seiten IMMER über BonsaiPress bauen
- Kein statisches HTML direkt schreiben — immer `contenfiles/<id>.html` + `site_structure.xml`
- Template-Änderungen → `config/de/templates/`
- CSS/JS → `config/sass/main.scss` + `page_config/<id>.html`
- Preview: http://localhost:8081 (static mode), NICHT :8080

## Bootstrap
- Kommt aus `cms/bootstrap/` (Git-Submodul) — NIEMALS CDN
- Version steht in `current/config/bootstrap-version.txt`
- Nur verwendete Module importieren (speed first) — kein `@import "bootstrap"`
- CSS-Datei heißt immer `main.css`, nie `master.css`

## HTMX und andere JS-Libs
- Lokal in `static/_resources/js/` ablegen — kein CDN
- In `page_config/<id>.html` einbinden via `{_RESOURCES}js/htmx.min.js`

## Server-Struktur (Hetzner)
- `static/` → deployt nach `web/` (Docroot, HTTP-erreichbar)
- `include/` → deployt nach `include/` (NICHT per HTTP erreichbar)
- PHP-Endpunkte die per HTTP erreichbar sein müssen → `static/`
- Sensitive Dateien (db, Passwörter) → `include/`
- `ftp_path_to_publish_ = '/'` — nie ändern

## PHP-Handler-Pattern
- Logik → `include/lib/<Name>Handler.php` (testbar mit PHPUnit)
- HTTP-Wrapper → `static/<name>.php` (dünn, ruft nur Handler auf)

## JS in Templates (main.html, bs4_6menu.html)
- Template-Engine frisst `{foo}` — Regex `{[^\s].*}` matcht alles was direkt nach `{` kein Leerzeichen hat
- Fix: immer Leerzeichen nach `{` → `{ passive:true }`, `{ top:0,behavior:'smooth' }`
- `[^\s]` matcht kein Leerzeichen → Objekt bleibt erhalten

## Was NIEMALS passiert
- Htaccess anfassen — egal warum
- CDN-Links in irgendeiner Form
- `bonsai deploy` ohne vorheriges `bonsai static`
- Statisches HTML direkt schreiben statt BonsaiPress zu benutzen
