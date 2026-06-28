# zakamoto.com

Sito d'arte di Akira Zakamoto (Luca Motolese) — galleria minimalista in PHP/MySQL.

## Struttura
- `index.php` hero, `art.php` galleria opere (filtri per serie), `atelier.php`, `files.php` (testi critici), `about.php`, `expo.php`, `prizes.php`, `media.php`, `contact.php` (hCaptcha).
- `galleries.php` — area riservata Galleries & Collectors: motore di ricerca del catalogo + codici registro.
- `feed.php` — endpoint JSON per la galleria (infinite scroll). `thumb.php` — thumbnail on-demand (GD, cache).
- `dettaglio.php` — pagina opera con Open Graph. `autentica.php` — certificato di autenticità.
- `include/` — `data.php` (accesso dati + helper), `db.php` (PDO), `head/nav/footer`, `PHPMailer/`.
- `assets/` — `style.css`, `app.js` (masonry + lightbox), `menu.js`, `files.js`.

## Configurazione
Copia `config.sample.php` in `config.php` e inserisci DB, SMTP, hCaptcha.
In sviluppo locale crea un file vuoto `.local` (usa i dati da `data/*.json` e le immagini remote).

> `config.php`, `.local` e `_admin_tmp/` sono esclusi dal repo (contengono credenziali).
