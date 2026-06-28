<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'Atelier — Akira Zakamoto';
$active = 'atelier';
include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="gallery-wrap">
  <div class="masonry" id="masonry" data-src="atelier"></div>
  <div id="sentinel" aria-hidden="true"></div>
  <div id="loader" class="loader">Loading…</div>
</main>

<!-- Lightbox -->
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
