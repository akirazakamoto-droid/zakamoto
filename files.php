<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'Files — Akira Zakamoto';
$active = 'files';
$texts = critica_load_all();   // ordinati dal più recente

// Autore in forma "Title Case", titolo ripulito
function crit_author(array $r): string
{
    $a = w_clean((string)($r['autore'] ?? ''));
    return $a === '' ? '' : mb_convert_case(mb_strtolower($a, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}
function crit_title(array $r): string {
    $en = w_clean((string)($r['titolo_en'] ?? ''));
    return $en !== '' ? $en : w_clean((string)($r['titolo'] ?? ''));
}

$media = media_load_all();

// Tipo di icona dal campo cat
function media_type(string $cat): string
{
    $c = strtolower(trim($cat));
    if ($c === 'video') return 'video';
    if (in_array($c, ['book', 'artbook', 'catalogo'], true)) return 'book';
    if ($c === 'cover') return 'cover';
    return 'altri';
}

// Link esterno (assoluto); cover senza link
function media_url(array $r): string
{
    if (media_type((string)$r['cat']) === 'cover') return '';
    $l = trim((string)($r['link'] ?? ''));
    if ($l === '') return '';
    if (!preg_match('#^https?://#i', $l)) $l = 'https://' . $l;
    return $l;
}

// URL immagine del media (per le cover lo zoom)
function media_img(array $r): string
{
    $p = trim((string)($r['foto'] ?? ''));
    if ($p === '') return '';
    if (preg_match('#^https?://#i', $p)) return $p;
    if (strpos($p, 'img/') === 0 || strpos($p, 'MAT/') === 0) return NEW_BASE . $p;
    return MAT_BASE . $p;
}

// SVG per tipo (stroke #111, fill #fff: invertibili in dark)
function media_icon_svg(string $type): string
{
    $o = "<svg viewBox='0 0 48 60' width='48' height='60' xmlns='http://www.w3.org/2000/svg'>";
    $c = "</svg>";
    switch ($type) {
        case 'video':
            return $o . "<rect x='6' y='15' width='36' height='30' rx='3' fill='#fff' stroke='#111' stroke-width='2'/>"
                . "<path d='M21 24v12l11-6z' fill='none' stroke='#111' stroke-width='2' stroke-linejoin='round'/>" . $c;
        case 'book':
            return $o . "<path d='M24 18c-4-3-10-3-14-2v30c4-1 10-1 14 2 4-3 10-3 14-2V16c-4-1-10-1-14 2z' fill='#fff' stroke='#111' stroke-width='2' stroke-linejoin='round'/>"
                . "<line x1='24' y1='18' x2='24' y2='48' stroke='#111' stroke-width='2'/>" . $c;
        case 'cover':
            return $o . "<rect x='9' y='12' width='30' height='38' rx='2' fill='#fff' stroke='#111' stroke-width='2'/>"
                . "<circle cx='19' cy='23' r='3' fill='none' stroke='#111' stroke-width='2'/>"
                . "<path d='M13 45l9-10 6 6 4-4 5 6' fill='none' stroke='#111' stroke-width='2' stroke-linejoin='round'/>" . $c;
        default: // altri media -> globo
            return $o . "<circle cx='24' cy='30' r='15' fill='#fff' stroke='#111' stroke-width='2'/>"
                . "<path d='M9 30h30M24 15c5 5 5 25 0 30c-5-5-5-25 0-30' fill='none' stroke='#111' stroke-width='2'/>" . $c;
    }
}

// Lista unica testi + media, ordinata dal più recente
$items = [];
foreach ($texts as $t) $items[] = ['kind' => 'text',  'date' => (string)$t['data'], 'row' => $t];
foreach ($media as $m) $items[] = ['kind' => 'media', 'date' => (string)$m['anno'], 'row' => $m];
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));

$text_icon = '<svg viewBox="0 0 48 63" width="48" height="63" xmlns="http://www.w3.org/2000/svg">'
  . '<path d="M4 2h28l12 12v44a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="#fff" stroke="#111" stroke-width="2" stroke-linejoin="round"/>'
  . '<path d="M32 2v12h12" fill="none" stroke="#111" stroke-width="2" stroke-linejoin="round"/>'
  . '<line x1="11" y1="26" x2="37" y2="26" stroke="#111" stroke-width="2"/>'
  . '<line x1="11" y1="34" x2="37" y2="34" stroke="#111" stroke-width="2"/>'
  . '<line x1="11" y1="42" x2="29" y2="42" stroke="#111" stroke-width="2"/></svg>';

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="page files-page">
  <h1>Files</h1>
  <div class="file-grid">
    <?php foreach ($items as $it): ?>
      <?php if ($it['kind'] === 'text'): $r = $it['row']; $id = (int)$r['id']; ?>
        <a class="file-card" href="testo.php?id=<?php echo $id; ?>">
          <span class="file-ico" aria-hidden="true"><?php echo $text_icon; ?></span>
          <span class="file-title"><?php echo htmlspecialchars(crit_title($r)); ?></span>
          <?php if (crit_author($r) !== ''): ?>
            <span class="file-author"><?php echo htmlspecialchars(crit_author($r)); ?></span>
          <?php endif; ?>
        </a>
      <?php else:
        $m = $it['row'];
        $type   = media_type((string)$m['cat']);
        $url    = media_url($m);
        $mtitle = w_clean((string)$m['titolo']);
        $label  = $type === 'book' ? 'Book / Art Book' : ($type === 'altri' ? 'Media' : ucfirst($type));
      ?>
        <?php if ($type === 'cover'): $img = media_img($m); ?>
          <button type="button" class="file-card cover-zoom" data-img="<?php echo htmlspecialchars($img); ?>" data-title="<?php echo htmlspecialchars($mtitle); ?>">
            <span class="file-ico" aria-hidden="true"><?php echo media_icon_svg($type); ?></span>
            <span class="file-title"><?php echo htmlspecialchars($mtitle); ?></span>
            <span class="file-author"><?php echo htmlspecialchars($label); ?></span>
          </button>
        <?php elseif ($url !== ''): ?>
          <a class="file-card" href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener">
            <span class="file-ico" aria-hidden="true"><?php echo media_icon_svg($type); ?></span>
            <span class="file-title"><?php echo htmlspecialchars($mtitle); ?></span>
            <span class="file-author"><?php echo htmlspecialchars($label); ?></span>
          </a>
        <?php else: ?>
          <span class="file-card">
            <span class="file-ico" aria-hidden="true"><?php echo media_icon_svg($type); ?></span>
            <span class="file-title"><?php echo htmlspecialchars($mtitle); ?></span>
            <span class="file-author"><?php echo htmlspecialchars($label); ?></span>
          </span>
        <?php endif; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</main>

<!-- Lightbox cover -->
<div id="lightbox" class="lightbox" aria-hidden="true">
  <button class="lb-close" aria-label="Close">×</button>
  <div class="lb-share-wrap">
    <button class="lb-share" id="lbShare" aria-label="Share">↗ Share</button>
    <div class="lb-share-menu" id="lbShareMenu" hidden>
      <a data-net="facebook" href="#" target="_blank" rel="noopener">Facebook</a>
      <a data-net="x" href="#" target="_blank" rel="noopener">X / Twitter</a>
      <a data-net="whatsapp" href="#" target="_blank" rel="noopener">WhatsApp</a>
      <button type="button" id="lbCopy">Copy link</button>
    </div>
  </div>
  <figure>
    <img id="lb-img" src="" alt="">
    <figcaption id="lb-cap"><span id="lb-title"></span></figcaption>
  </figure>
</div>
<script src="assets/files.js?v=<?php echo ASSET_VER; ?>"></script>
<?php include __DIR__ . '/include/footer.php'; ?>
