<?php
// ------------------------------------------------------------------
// Accesso ai dati delle opere — astrae locale (JSON) vs produzione (MySQL).
// Tutte le funzioni restituiscono array di opere ordinate dal più recente.
// ------------------------------------------------------------------
require_once __DIR__ . '/../config.php';

function works_load_all(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/works.json');
        $rows = $raw ? (json_decode($raw, true) ?: []) : [];
        // in locale teniamo solo i visibili
        $rows = array_values(array_filter($rows, fn($r) => (int)($r['modo'] ?? 1) === 1));
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $rows = db_fetch_all(
            "SELECT id,titolo,titolo_en,cat,cat2,foto,foto_big,tecnica,altezza,larghezza,anno,info,info_link,home,motolese
             FROM work WHERE modo = 1"
        );
    }

    // Ordina per data di inserimento nel sito: id autoincrement, dal più recente
    usort($rows, function ($a, $b) {
        $c = strcmp((string)$b['anno'], (string)$a['anno']);   // anno DESC
        return $c !== 0 ? $c : ((int)$b['id'] <=> (int)$a['id']);
    });

    $cache = $rows;
    return $cache;
}

// Tutte le opere, comprese quelle oscurate (modo=0) — per l'area riservata gallerie.
function works_all_any(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/works.json');
        $rows = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $rows = db_fetch_all(
            "SELECT id,titolo,titolo_en,cat,cat2,foto,foto_big,tecnica,altezza,larghezza,anno,info,info_link,home,owner,archivio,motolese FROM work"
        );
    }
    usort($rows, function ($a, $b) {
        $c = strcmp((string)$b['anno'], (string)$a['anno']);   // anno DESC
        return $c !== 0 ? $c : ((int)$b['id'] <=> (int)$a['id']);
    });
    $cache = $rows;
    return $cache;
}

// Verifica credenziali utente gallerie (login + password)
// Ritorna l'id dell'utente gallerie se le credenziali sono valide, altrimenti 0.
function gallery_user_check(string $login, string $password): int
{
    if ($login === '' || $password === '') return 0;
    if (IS_LOCAL) return 0;
    require_once __DIR__ . '/db.php';
    db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
    $rows = db_fetch_all("SELECT id, pass FROM gallery_users WHERE login = ?", [$login]);
    if (!$rows) return 0;
    return password_verify($password, (string)$rows[0]['pass']) ? (int)$rows[0]['id'] : 0;
}

// Progetto/i su motolese.com associati alla serie (campo work.cat).
// Robot e Megamix possono appartenere a due progetti distinti.
function work_projects(string $cat): array
{
    static $map = [
        'life'          => [['Life is a game',    'https://www.motolese.com/en/lifeisagame.php']],
        'robot'         => [['Umano Disumano',    'https://www.motolese.com/en/umanodisumano.php'],
                            ['Cuore e Acciaio',   'https://www.motolese.com/en/cuoreeacciaio.php']],
        'megamix'       => [['Media-Mente Falso', 'https://www.motolese.com/en/mediamentefalso.php'],
                            ['Green Ass',         'https://www.motolese.com/en/greenass.php']],
        'deflorationis' => [['Deflorationis',     'https://www.motolese.com/en/deflorationis.php']],
        'kitty'         => [['Kitty die Katze',   'https://www.motolese.com/en/kitty.php']],
        'future'        => [['Giganti',           'https://www.motolese.com/en/giganti.php']],
        'zoo'           => [['Stop the Pigeon',   'https://www.motolese.com/en/stopthepigeon.php']],
        'nudi'          => [['Quarantine',        'https://www.motolese.com/en/quarantine.php']],
    ];
    $k = strtolower(trim($cat));
    return $map[$k] ?? [];
}

// Elenco pagine progetto su motolese.com (url => etichetta)
function motolese_pages(): array
{
    return [
        'https://www.motolese.com/en/lifeisagame.php'    => 'Life is a game',
        'https://www.motolese.com/en/umanodisumano.php'  => 'Umano Disumano',
        'https://www.motolese.com/en/cuoreeacciaio.php'  => 'Cuore e Acciaio',
        'https://www.motolese.com/en/mediamentefalso.php'=> 'Media-Mente Falso',
        'https://www.motolese.com/en/greenass.php'       => 'Green Ass',
        'https://www.motolese.com/en/deflorationis.php'  => 'Deflorationis',
        'https://www.motolese.com/en/kitty.php'          => 'Kitty die Katze',
        'https://www.motolese.com/en/giganti.php'        => 'Giganti',
        'https://www.motolese.com/en/stopthepigeon.php'  => 'Stop the Pigeon',
        'https://www.motolese.com/en/quarantine.php'     => 'Quarantine',
    ];
}

// Link progetto da mostrare per un'opera: override per-opera (work.motolese) se presente,
// altrimenti i default della categoria.
function work_project_links(array $r): array
{
    $m = trim((string)($r['motolese'] ?? ''));
    if ($m === 'none') return [];                       // nessun link (forzato)
    if ($m !== '') {
        $pages = motolese_pages();
        return [[$pages[$m] ?? $m, $m]];
    }
    return work_projects((string)($r['cat'] ?? ''));   // default della categoria
}

// Opere filtrate per serie (cat) o tecnica (cat2). $key vuoto = tutte.
function works_by(string $key = ''): array
{
    $all = works_load_all();
    if ($key === '') return $all;
    return array_values(array_filter($all, fn($r) =>
        strcasecmp((string)$r['cat'], $key) === 0 ||
        strcasecmp((string)$r['cat2'], $key) === 0
    ));
}

// Mostre / premi — stesso schema locale (JSON) vs produzione (MySQL).
function expo_load_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/expo.json');
        $c = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $c = db_fetch_all("SELECT titolo,sottotitolo,dove,data FROM expo WHERE modo=1");
    }
    usort($c, fn($a, $b) => strcmp((string)$b['data'], (string)$a['data']));
    return $c;
}

function prizes_load_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/prizes.json');
        $c = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $c = db_fetch_all("SELECT titolo,sottotitolo,dove,data FROM prizes WHERE modo=1");
    }
    usort($c, fn($a, $b) => strcmp((string)$b['data'], (string)$a['data']));
    return $c;
}

// Testi critici — locale (JSON) vs produzione (MySQL).
function critica_load_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/critica.json');
        $c = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $c = db_fetch_all("SELECT id,titolo,titolo_en,autore,testo,testo_en,link,data FROM critica WHERE modo=1");
    }
    usort($c, fn($a, $b) => strcmp((string)$b['data'], (string)$a['data']));
    return $c;
}

// Pulisce il corpo di un testo critico: converte i <br> in a-capo reali,
// elimina i "\n" letterali e i tag residui, decodifica le entità.
function crit_body(string $raw): string
{
    $t = preg_replace('#</?br\s*/?>#i', "\n", $raw);   // <br>, <br/>, </br> -> newline
    $t = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $t); // "\n" letterale -> newline
    $t = str_replace(["\r\n", "\r"], "\n", $t);         // CR reali -> newline
    $t = strip_tags($t);                                // rimuove altri tag
    $t = w_clean($t);                                   // decodifica entità + mojibake
    // collassa qualsiasi sequenza di a-capo (con spazi/NBSP in mezzo) in uno solo
    $t = preg_replace('/[ \t\x{00A0}]*\n[ \t\x{00A0}\n]*/u', "\n", $t);
    return trim($t);
}

// Media (video, book/art book, cover, altri) — locale (JSON) vs produzione (MySQL).
function media_load_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/media.json');
        $c = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        require_once __DIR__ . '/db.php';
        db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
        $c = db_fetch_all("SELECT id,cat,titolo,foto_big AS link,foto,anno FROM media WHERE modo=1");
    }
    usort($c, function ($a, $b) {
        $cmp = strcmp((string)$b['anno'], (string)$a['anno']);
        return $cmp !== 0 ? $cmp : ((int)$b['id'] <=> (int)$a['id']);
    });
    return $c;
}

function critica_find(int $id): ?array
{
    foreach (critica_load_all() as $r) {
        if ((int)$r['id'] === $id) return $r;
    }
    return null;
}

// Archivio Atelier (DB separato zkmgallerydb, tabella foto).
function atelier_load_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    if (IS_LOCAL) {
        $raw = @file_get_contents(__DIR__ . '/../data/atelier.json');
        $c = $raw ? (json_decode($raw, true) ?: []) : [];
    } else {
        $pdo = new PDO(STUDIO_DSN, STUDIO_USER, STUDIO_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $c = $pdo->query("SELECT id,foto,data,nota FROM foto")->fetchAll();
    }
    usort($c, function ($a, $b) {
        $cmp = strcmp((string)$b['data'], (string)$a['data']);
        return $cmp !== 0 ? $cmp : ((int)$b['id'] <=> (int)$a['id']);
    });
    return $c;
}

function work_find(int $id): ?array
{
    foreach (works_load_all() as $r) {
        if ((int)$r['id'] === $id) return $r;
    }
    return null;
}

// Helpers di presentazione --------------------------------------------------

// Ripara il testo doppiamente codificato (UTF-8 letto come Latin1/CP1252):
// es. "Benchà©" -> "Benché", "â€œ" -> "“".
function fix_mojibake(string $s): string
{
    static $map = [
        // virgolette/punteggiatura tipografica
        'â€œ' => '“', 'â€' => '”', 'â€™' => '’', 'â€˜' => '‘',
        'â€”' => '—', 'â€“' => '–', 'â€¦' => '…', 'â€¢' => '•',
        // vocali accentate italiane (minuscole/maiuscole)
        'Ã ' => 'à', 'Ã¨' => 'è', 'Ã©' => 'é', 'Ã¬' => 'ì', 'Ã²' => 'ò', 'Ã¹' => 'ù',
        'Ã€' => 'À', 'Ãˆ' => 'È', 'Ã‰' => 'É', 'ÃŒ' => 'Ì', 'Ã’' => 'Ò', 'Ã™' => 'Ù',
        'Ã¼' => 'ü', 'Ã¶' => 'ö', 'Ã¤' => 'ä', 'Ã±' => 'ñ', 'Ã§' => 'ç',
        // varianti già "scese" di un livello (à© ecc.)
        'à©' => 'é', 'à¨' => 'è', 'à ' => 'à', 'à¹' => 'ù', 'à²' => 'ò', 'à¬' => 'ì',
        // residui
        'Â«' => '«', 'Â»' => '»', 'Â°' => '°', 'Â ' => ' ',
    ];
    // Â isolato (NBSP fantasma) -> rimosso solo se seguito da spazio/fine
    $s = strtr($s, $map);
    $s = preg_replace('/Â(?=\s|$)/u', '', $s);
    return $s;
}

// Decodifica le entità HTML (anche doppie) e ripara il mojibake.
function w_clean(string $s): string
{
    $prev = null;
    // decodifica ripetuta finché la stringa cambia (max 3 passaggi)
    for ($i = 0; $i < 3 && $s !== $prev; $i++) {
        $prev = $s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $s = fix_mojibake($s);
    return trim($s);
}

function w_title(array $r): string
{
    // Preferisci il titolo inglese (mantenuto con la sua capitalizzazione)
    $en = w_clean((string)($r['titolo_en'] ?? ''));
    if ($en !== '') return $en;
    $t = w_clean((string)$r['titolo']);
    if ($t === '') return 'Untitled';
    $t = mb_strtolower($t, 'UTF-8');
    return mb_strtoupper(mb_substr($t, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($t, 1, null, 'UTF-8');
}

// Tecnica ripulita: decodifica entità ed elimina trattini/spazi iniziali.
function w_tech(array $r): string
{
    $t = w_clean((string)($r['tecnica'] ?? ''));
    $t = ltrim($t, "- \t");
    return $t;
}

function w_year(array $r): string
{
    return substr((string)$r['anno'], 0, 4);
}

function w_img(string $path): string
{
    return MAT_BASE . ltrim($path, '/');
}

// Normalizza un URL (aggiunge https:// se manca lo schema). Vuoto se non valido.
function w_link(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    return $url;
}

// "100 × 80 cm" se disponibili
function w_dimensions(array $r): string
{
    $h = trim((string)($r['altezza'] ?? ''));
    $l = trim((string)($r['larghezza'] ?? ''));
    if ($h !== '' && $h !== '0' && $l !== '' && $l !== '0') {
        return $h . ' × ' . $l . ' cm';
    }
    return '';
}
