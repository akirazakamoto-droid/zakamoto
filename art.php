<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'Art — Akira Zakamoto';
$active = 'art';
include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';

// Barra delle serie (campo work.cat). Mostro solo le serie effettivamente presenti.
$sel = isset($_GET['serie']) ? trim((string)$_GET['serie']) : '';
$series_order = [
    'Dreams'       => 'Dreams',
    'Life'         => 'Life is a game',
    'Robot'        => 'Robot',
    'Megamix'      => 'Megamix',
    'deflorationis'=> 'Deflorationis',
    'Kitty'        => 'Kitty die Katze',
    'Future'       => 'Future',
    'Zoo'          => 'Zoolatry',
    'Nudi'         => 'Nudes',
];
$always = ['Dreams'];   // categorie sempre mostrate anche se ancora senza opere
$counts = [];
foreach (works_load_all() as $r) {
    $c = trim((string)$r['cat']);
    if ($c !== '') $counts[$c] = ($counts[$c] ?? 0) + 1;
}
// Barra = categorie note (in ordine) presenti o "always" + eventuali nuove categorie (nome grezzo)
$bar = [];
foreach ($series_order as $cat => $label) { if (isset($counts[$cat]) || in_array($cat, $always, true)) $bar[$cat] = $label; }
foreach ($counts as $cat => $n) { if (!isset($series_order[$cat])) $bar[$cat] = $cat; }
?>
<nav class="series-bar" aria-label="Series">
  <button type="button" class="series-link<?php echo $sel === '' ? ' active' : ''; ?>" data-w="">All</button>
  <?php foreach ($bar as $cat => $label): ?>
  <button type="button" class="series-link<?php echo strcasecmp($sel, $cat) === 0 ? ' active' : ''; ?>" data-w="<?php echo htmlspecialchars($cat, ENT_QUOTES); ?>"><?php echo htmlspecialchars($label); ?></button>
  <?php endforeach; ?>
</nav>

<main class="gallery-wrap">
  <div class="masonry" id="masonry"></div>
  <div id="sentinel" aria-hidden="true"></div>
  <div id="loader" class="loader">Loading…</div>
</main>

<!-- Lightbox minimale -->
<div id="lightbox" class="lightbox" aria-hidden="true">
  <button class="lb-close" aria-label="Close">×</button>
  <button class="lb-nav lb-prev" id="lbPrev" aria-label="Previous">‹</button>
  <button class="lb-nav lb-next" id="lbNext" aria-label="Next">›</button>
  <figure>
    <img id="lb-img" src="" alt="">
    <figcaption id="lb-cap"><span id="lb-title"></span> <span id="lb-meta"></span></figcaption>
    <div class="lb-share-wrap">
      <button class="lb-share" id="lbShare" aria-label="Share">↗ Share</button>
      <div class="lb-share-menu" id="lbShareMenu" hidden>
        <a data-net="facebook" href="#" target="_blank" rel="noopener">Facebook</a>
        <a data-net="x" href="#" target="_blank" rel="noopener">X / Twitter</a>
        <a data-net="whatsapp" href="#" target="_blank" rel="noopener">WhatsApp</a>
        <button type="button" id="lbCopy">Copy link</button>
      </div>
    </div>
  </figure>
</div>

<?php include __DIR__ . '/include/footer.php'; ?>
<script src="assets/app.js?v=<?php echo ASSET_VER; ?>"></script>
