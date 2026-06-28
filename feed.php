<?php
// Endpoint JSON per il caricamento incrementale della galleria.
// Parametri: offset (default 0), limit (default 20, max 60), w (filtro serie/tecnica opzionale)
require_once __DIR__ . '/include/data.php';

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = min(60, max(1, (int)($_GET['limit'] ?? 20)));
$w      = isset($_GET['w']) ? (string)$_GET['w'] : '';
$src    = isset($_GET['src']) ? (string)$_GET['src'] : '';
$seed   = (int)($_GET['seed'] ?? 0);   // ordinamento casuale stabile (stesso seed = stesso ordine tra le pagine)

if ($src === 'all') {
    // Tutte le opere (anche oscurate) — solo per utenti gallerie loggati
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['gallery_ok'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['total' => 0, 'items' => []]);
        exit;
    }
    $all   = works_all_any();
    $slice = array_slice($all, $offset, $limit);
    $cur_uid = (int)($_SESSION['gallery_uid'] ?? 0);
    $gu = [];
    foreach (db_fetch_all("SELECT id,nome,cognome,codice FROM gallery_users") as $u) {
        $gu[(int)$u['id']] = $u;
    }
    $items = array_map(function ($r) use ($cur_uid, $gu) {
        $h = (int)$r['altezza']; $wd = (int)$r['larghezza'];
        $rh = ($h > 0 && $wd > 0) ? round($h / $wd, 3) : 1;
        $parts = array_filter([w_tech($r), w_dimensions($r), w_year($r)], fn($x) => $x !== '');
        $owner = null;
        $oid = (int)($r['owner'] ?? 0);
        if ($oid > 0 && isset($gu[$oid])) {
            $u = $gu[$oid];
            $archive = trim((string)($r['archivio'] ?? ''));
            if ($oid === $cur_uid) {
                $owner = ['mine' => true, 'name' => trim(($u['cognome'] ?? '') . ' ' . ($u['nome'] ?? '')), 'archive' => $archive];
            } else {
                $owner = ['mine' => false, 'code' => (string)($u['codice'] ?? ''), 'archive' => $archive];
            }
        }
        return [
            'id'    => (int)$r['id'],
            'title' => w_title($r),
            'thumb' => w_img($r['foto']),
            'big'   => w_img($r['foto_big'] ?: $r['foto']),
            'meta'  => implode(', ', $parts),
            'owner' => $owner,
            'rh'    => $rh,
            'extra' => trim(w_clean((string)($r['info'] ?? ''))),
            'extra_link' => w_link((string)($r['info_link'] ?? '')),
            'project' => work_project_links($r),
        ];
    }, $slice);
} elseif ($src === 'atelier') {
    $all   = atelier_load_all();                         // già ordinate data DESC
    if ($seed) { mt_srand($seed); shuffle($all); }       // ordinamento casuale stabile
    $slice = array_slice($all, $offset, $limit);
    $items = array_map(function ($r) {
        $rel = ltrim((string)$r['foto'], '/');
        $img = ATELIER_BASE . $rel;
        $thumb = IS_LOCAL ? $img : ('thumb.php?p=' . rawurlencode('studio/imm/' . $rel) . '&w=600');
        $big   = IS_LOCAL ? $img : ('thumb.php?p=' . rawurlencode('studio/imm/' . $rel) . '&w=1600');
        return [
            'id'    => (int)$r['id'],
            'title' => '',
            'thumb' => $thumb,
            'big'   => $big,
            'meta'  => '',
            'date'  => $r['data'] ? date('j M Y', strtotime((string)$r['data'])) : '',
            'extra' => trim((string)($r['nota'] ?? '')),
            'nolink'=> true,
        ];
    }, $slice);
} else {
    $all   = $w !== '' ? works_by($w) : works_load_all(); // ordinate anno DESC
    $slice = array_slice($all, $offset, $limit);
    $items = array_map(function ($r) {
        $h = (int)$r['altezza']; $wd = (int)$r['larghezza'];
        $rh = ($h > 0 && $wd > 0) ? round($h / $wd, 3) : 1;
        $parts = array_filter([w_tech($r), w_dimensions($r), w_year($r)], fn($x) => $x !== '');
        return [
            'id'    => (int)$r['id'],
            'title' => w_title($r),
            'thumb' => w_img($r['foto']),
            'big'   => w_img($r['foto_big'] ?: $r['foto']),
            'meta'  => implode(', ', $parts),
            'rh'    => $rh,
            'extra' => trim(w_clean((string)($r['info'] ?? ''))),
            'extra_link' => w_link((string)($r['info_link'] ?? '')),
            'project' => work_project_links($r),
        ];
    }, $slice);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total'  => count($all),
    'offset' => $offset,
    'limit'  => $limit,
    'items'  => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
