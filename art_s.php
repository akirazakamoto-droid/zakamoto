<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'Art (hide) — Akira Zakamoto';
$active = 'art';
include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<div class="gate-bar">
  <span>Modalità oscura — clicca un'opera per nasconderla da Art (modo=0)</span>
  <a href="art.php">torna ad Art</a>
</div>
<main class="gallery-wrap">
  <div class="masonry" id="masonry" data-mode="hide"></div>
  <div id="sentinel" aria-hidden="true"></div>
  <div id="loader" class="loader">Loading…</div>
</main>
<?php include __DIR__ . '/include/footer.php'; ?>
<script src="assets/app.js?v=<?php echo ASSET_VER; ?>"></script>
