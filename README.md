# zakamoto.com

Sito d'arte di Akira Zakamoto (Luca Motolese) — galleria minimalista in PHP/MySQL.

## Struttura
- `index.php` hero, `art.php` galleria opere (filtri per serie), `atelier.php`, `files.php` (testi critici), `about.php`, `expo.php`, `prizes.php`, `media.php`, `contact.php` (hCaptcha).
- `galleries.php` — area riservata Galleries & Collectors: motore di ricerca del catalogo + codici registro.
- `feed.php` — endpoint JSON per la galleria (infinite scroll). `thumb.php` — thumbnail on-demand (GD, cache).
- `dettaglio.php` — pagina opera con Open Graph. `autentica.php` — certificato di autenticità.
- `include/` — `data.php` (accesso dati + helper), `db.php` (PDO), `head/nav/footer`, `PHPMailer/`.
- `assets/` — `style.css`, `app.js` (masonry + lightbox), `menu.js`, `files.js`.

## Avvio locale (da GitHub, senza server di produzione)
Bastano PHP e questo repo — nessun database né disco esterno:
```bash
git clone https://github.com/akirazakamoto-droid/zakamoto.git
cd zakamoto
cp config.sample.php config.php   # in locale i valori DB possono restare placeholder
type nul > .local                 # Windows  (Linux/Mac: touch .local)
php -S localhost:8000
```
Apri http://localhost:8000

In **modalità locale** (presenza del file `.local`):
- i contenuti vengono letti da `data/*.json` (snapshot del catalogo);
- le immagini sono caricate dal sito live (`https://zakamoto.com/new/MAT/…`).

Funzionano in sola lettura le pagine pubbliche (`art`, `atelier`, `files`, `about`, `expo`, `prizes`, `media`).
Le funzioni che richiedono il database (area **Galleries & Collectors**, **contact**, **ADMIN**) sono attive solo in produzione con `config.php` reale.

## Configurazione produzione
Copia `config.sample.php` in `config.php` e inserisci DB, SMTP, hCaptcha (senza creare `.local`).

> `config.php`, `.local` e `_admin_tmp/` sono esclusi dal repo (credenziali / file di servizio).
