# ZAKAMOTO.COM — Project Overview / Handoff

> Documento di handoff per riprendere il progetto in un'altra sessione/progetto Claude.
> **Nessuna password è contenuta qui**: tutti i segreti stanno in `config.php` (escluso dal
> repo via `.gitignore`). Per i valori reali fare riferimento a `config.php` in produzione,
> oppure partire da `config.sample.php`.

---

## 1. Cos'è e a cosa serve

Sito d'arte di **Akira Zakamoto** (Luca Motolese) — galleria minimalista in **PHP/MySQL**,
senza framework. Mostra il catalogo opere con filtri per serie, testi critici, mostre/premi,
media, atelier, form contatti e un'**area riservata "Galleries & Collectors"** con motore di
ricerca del catalogo e codici registro. Include un **certificato di autenticità** stampabile
e le fondamenta di un **registro di provenienza su blockchain** (Hyperledger Fabric).

Lingua del sito: prevalentemente inglese (titoli EN preferiti ai titoli IT quando presenti).

---

## 2. Posizioni / dove vive il codice

| Cosa | Dove |
|------|------|
| Sorgente di sviluppo (Windows) | `C:\SERVER3\zakamoto` (più handoff in `C:\Users\akira\Documents\SERVER4\`) |
| Repository GitHub | `akirazakamoto-droid/zakamoto` |
| Produzione | server `77.42.25.226`, document root `/var/www/html` |
| Immagini opere (produzione) | `/var/www/html/new/MAT/...` (web: `/new/MAT/...`) |
| Immagini opere (locale) | servite dal sito live `https://zakamoto.com/new/MAT/...` |

In **modalità locale** (presenza del file `.local`) i contenuti vengono letti dagli snapshot
JSON in `data/`, e le immagini vengono caricate dal sito live. Senza `.local` (produzione) si
usa MySQL e i percorsi locali del disco.

---

## 3. Server e accesso

- Host produzione: `77.42.25.226`, web server con document root `/var/www/html`.
- Le credenziali SSH/SFTP, DB e SMTP **non sono in questo documento**: vedere `config.php` /
  gestore delle credenziali personale.
- **Trappola harness**: in ambienti che usano PowerShell/SFTP fare attenzione a quoting dei
  percorsi Windows e ai trasferimenti binari (immagini) — usare modalità binaria, evitare
  conversioni di newline sui file caricati.

---

## 4. Stack & layout dei file

PHP puro + MySQL (PDO). Niente build step lato server; gli asset hanno cache-busting via
`ASSET_VER` (`config.php`).

### Pagine pubbliche
- `index.php` — hero / home.
- `art.php` — galleria opere con filtri per serie (usa `feed.php` per l'infinite scroll).
- `atelier.php` — archivio atelier (DB separato STUDIO).
- `files.php` — testi critici (`critica`).
- `about.php`, `expo.php`, `prizes.php`, `media.php`, `critica.php`, `testo.php`, `art_s.php`.
- `dettaglio.php` — pagina singola opera con Open Graph.
- `contact.php` — form contatti con **hCaptcha** (fallback captcha aritmetica) + honeypot.

### Area riservata
- `galleries.php` — login Galleries & Collectors; motore di ricerca catalogo; endpoint AJAX
  `gencode` (genera/ritorna codice registro `work.archivio`) e `claim` (notifica via email
  "sono il proprietario").
- `autentica.php` — **certificato di autenticità** stampabile (solo utenti loggati); genera il
  codice d'archivio se mancante e calcola l'impronta SHA-256 del record-registro.

### Endpoint / utilità
- `feed.php` — endpoint JSON per la galleria (infinite scroll).
- `thumb.php` — thumbnail on-demand (GD, con cache).
- `hide_work.php` — gestione visibilità opera.
- `404.php`.

### Include
- `include/data.php` — **accesso dati + helper** (astrae locale JSON vs produzione MySQL):
  `works_load_all()`, `works_all_any()` (include opere oscurate), `works_by()`,
  `gallery_user_check()`, `work_project_links()` / `work_projects()` / `motolese_pages()`
  (link ai progetti su motolese.com), `expo/prizes/critica/media/atelier_load_all()`, e helper
  di presentazione (`w_title`, `w_tech`, `w_year`, `w_img`, `w_dimensions`, `w_clean`,
  `fix_mojibake` per riparare testi doppiamente codificati).
- `include/db.php` — wrapper PDO minimale (`db_init`, `db`, `db_fetch_all`).
- `include/chain.php` — Fase 0 blockchain (vedi §9).
- `include/head.php`, `include/nav.php`, `include/footer.php` — layout.
- `include/PHPMailer/` — invio email SMTP (contact + claim/inviti).

### Asset
- `assets/style.css` — stile (con dark mode via `localStorage 'zk-theme'`).
- `assets/app.js` — masonry + lightbox della galleria.
- `assets/menu.js` — menu mobile + theme toggle.
- `assets/files.js` — UI testi critici.

### Costanti chiave (`config.php`)
- `IS_LOCAL` = esiste il file `.local`.
- `MAT_BASE` / `NEW_BASE` / `ATELIER_BASE` — base URL immagini (live in locale, `/new/...` in prod).
- `ASSET_VER` — cache-busting CSS/JS.
- `GALLERIES_PASS` — (legacy) password area riservata; l'autenticazione attuale usa la tabella
  `gallery_users` con `password_verify`.

---

## 5. Database

Due connessioni: **DB principale** (`DB_DSN/DB_USER/DB_PASS`) e **DB STUDIO/ATELIER**
(`STUDIO_DSN/...`, host con porta dedicata). Charset `utf8mb4`.

### Tabelle principali (DB principale)
- **`work`** — opere. Colonne usate: `id`, `titolo`, `titolo_en`, `cat` (serie), `cat2`
  (tecnica/supporto), `foto`, `foto_big`, `tecnica`, `altezza`, `larghezza`, `anno` (data),
  `info`, `info_link`, `home`, `motolese` (override link progetto; `none` = nessun link),
  `modo` (1 = visibile, 0 = oscurata), `owner` (id collezionista), `archivio` (codice registro).
- **`gallery_users`** — utenti area riservata: `id`, `login`, `pass` (hash bcrypt),
  `nome`, `cognome`, `email`, `codice` (pseudonimo proprietario, es. `ZKM-XXXX`).
- **`work_chain`** — ancoraggio impronte (vedi §9): `work_id` (PK), `record_hash`, `owner_code`,
  `payload`, `img_rel`, `img_hash`, `txid`, `chain`, `anchored_at`, `updated_at`.
- **`expo`**, **`prizes`** — mostre/premi: `titolo`, `sottotitolo`, `dove`, `data`, `modo`.
- **`critica`** — testi critici: `id`, `titolo`, `titolo_en`, `autore`, `testo`, `testo_en`,
  `link`, `data`, `modo`.
- **`media`** — video/book/cover: `id`, `cat`, `titolo`, `foto_big`, `foto`, `anno`, `modo`.

### DB STUDIO/ATELIER
- **`foto`** — archivio atelier: `id`, `foto`, `data`, `nota`.

> Gli snapshot pubblici (solo colonne pubbliche) sono in `data/*.json` per l'avvio locale:
> `works.json`, `critica.json`, `expo.json`, `prizes.json`, `media.json`, `atelier.json`.

---

## 6. Funzionalità

- **Serie / filtri**: `art.php` filtra per `cat` (serie) o `cat2` (tecnica). Le serie sono
  mappate a etichette leggibili (Dreams, Life is a game, Robot, Megamix, Deflorationis,
  Kitty die Katze, Future, Zoolatry, Nudes).
- **Link motolese.com**: ogni serie può puntare a uno o più progetti su motolese.com
  (`work_projects`), con override per-opera tramite `work.motolese`.
- **Motore di ricerca collezionisti** (`galleries.php`): tabella catalogo completo (incluse
  opere oscurate) con filtri client-side per titolo IT/EN, codice registro, dimensioni, anno,
  serie; anteprima opera + pulsante "Sono il proprietario" (claim via email).
- **Certificato di autenticità** (`autentica.php`): immagine + dati tecnici + codice
  d'archivio + impronta SHA-256 del registro + firma + dati studio; stampabile.
- **Contact** (`contact.php`): invio via PHPMailer/SMTP, protezione hCaptcha (se configurato),
  altrimenti captcha aritmetica, più honeypot anti-bot.

---

## 7. ADMIN (in produzione, fuori dal repo pubblico)

Gli strumenti di amministrazione (gestione opere, categorie, crop immagini, inviti
collezionisti, editing) vivono in produzione e **non sono pubblicati su GitHub**
(`_admin_tmp/` è in `.gitignore`). Concetti chiave:
- gestione categorie/serie (`cat_tool` / mappa `CATEGORIES`);
- crop/ridimensionamento immagini caricate;
- creazione utenti `gallery_users` e invio inviti collezionisti (SMTP);
- editing opere (`work_edit`) e assegnazione `owner`/`archivio`.

> Quando si lavora sull'admin, ricordare che `gencode`/`autentica` generano `archivio` se
> mancante; l'assegnazione del proprietario (`work.owner`) è manuale lato admin dopo un claim.

---

## 8. Sicurezza

- `config.php`, `.local`, `_admin_tmp/`, `cache/`, `*.bak`, `*.log` esclusi dal repo.
- Autenticazione area riservata via `gallery_users` + `password_verify` (hash bcrypt); sessione
  PHP (`$_SESSION['gallery_ok']`, `gallery_uid`, `gallery_login`).
- `autentica.php` accessibile solo se loggati.
- Query parametrizzate (PDO prepared, `ATTR_EMULATE_PREPARES => false`).
- Output con `htmlspecialchars` / `ENT_QUOTES`.
- Contact: hCaptcha + honeypot + validazione email.
- (Produzione) regole `.htaccess` per proteggere file di servizio e directory admin.

---

## 9. Blockchain provenienza (Hyperledger Fabric — NetworkM3)

Obiettivo: registro **immutabile** di proprietà/provenienza delle opere. On-chain vanno
**solo dati non personali** (codice registro, id opera, codice proprietario pseudonimo,
impronta SHA-256 del record) — **mai dati personali**.

- **Fase 0 — FATTA**: `include/chain.php` calcola e memorizza localmente l'impronta del
  record-registro in `work_chain` (idempotente). Record canonico:
  `v1|id:<id>|reg:<archivio>|owner:<codice>|date:<anno>|img:<sha256 immagine>`, poi `sha256`
  del payload. L'hash è mostrato sul certificato (`autentica.php`), pronto per l'ancoraggio.
- **Fase 1 — FATTA**: chaincode Go **`artregistry`** in `chaincode/artregistry/`
  (Fabric 2.5; `fabric-contract-api-go` 1.2.2). Funzioni: `Register`, `Transfer`, `Get`,
  `Verify`, `History` (provenienza = `GetHistoryForKey`). `Register` è una tantum (errore se
  già presente); `Transfer` aggiorna owner+hash e incrementa `Version`, lasciando lo storico
  immutabile. Unit test presente (`artregistry_test.go`) — `go build` e `go test` OK.
- **Fase 2 — DA FARE**: deploy del chaincode sull'org `zkm.gallery` di NetworkM3 e
  collegamento PHP → chain (popolare `work_chain.txid`/`chain`/`anchored_at` dopo l'ancoraggio;
  `autentica.php` mostra già lo stato "registrata on-chain" quando `txid` è presente).

---

## 10. Avvio locale (da GitHub, senza server di produzione)

Bastano PHP e il repo — nessun DB né disco esterno:

```bash
git clone https://github.com/akirazakamoto-droid/zakamoto.git
cd zakamoto
cp config.sample.php config.php   # in locale i valori DB possono restare placeholder
touch .local                      # Windows: type nul > .local
php -S localhost:8000
```

Apri http://localhost:8000. In modalità locale funzionano in sola lettura le pagine pubbliche
(art, atelier, files, about, expo, prizes, media); le funzioni che richiedono DB (Galleries &
Collectors, contact, ADMIN) sono attive solo in produzione con `config.php` reale.

Chaincode:
```bash
cd chaincode/artregistry
go build ./...
go test ./...
```

---

## 11. Workflow di deploy

1. Sviluppo in locale (`.local` presente) leggendo i JSON in `data/`.
2. Commit su GitHub (`akirazakamoto-droid/zakamoto`).
3. Deploy in produzione su `77.42.25.226:/var/www/html` (upload binario per le immagini);
   `config.php` resta solo in produzione, **mai** committato.
4. Bump di `ASSET_VER` in `config.php` quando cambiano CSS/JS (cache-busting).
5. Refresh degli snapshot `data/*.json` (solo colonne pubbliche) quando cambia il catalogo.

---

## 12. TODO aperti

- **Blockchain Fase 2**: deploy `artregistry` su NetworkM3 (org `zkm.gallery`) e wiring
  PHP → chain (Register/Transfer + salvataggio `txid` in `work_chain`).
- Pubblicare/sincronizzare gli strumenti ADMIN in modo sicuro (restano fuori dal repo).
- Eventuale verifica pubblica del certificato (endpoint `Verify` esposto in lettura).

---

> 🔒 Promemoria: questo file è condivisibile/importabile senza rischi perché **non contiene
> credenziali**. Per una versione con anche le credenziali (uso strettamente personale),
> tenerla **fuori da Git**.
