<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'Akira Zakamoto';
$active = '';
$hide_wordmark = true;   // sulla home il nome grande è già nell'hero
$head_extra = '<style>html,body{height:100%;overflow:hidden}.hero-title{filter:drop-shadow(0 1px 6px rgba(0,0,0,.45))}</style>';  // niente scroll in home

// Orientamento dalle dimensioni dell'opera
function work_orient(array $w): string {
    $h = (int)$w['altezza']; $l = (int)$w['larghezza'];
    if ($l <= 0 || $h <= 0) return '';
    if ($l > $h) return 'landscape';
    if ($l < $h) return 'portrait';
    return 'square';
}

// Sorgente: opere flaggate per l'index (home=1); se nessuna, fallback alle recenti
$all_works = works_load_all();
$flagged   = array_values(array_filter($all_works, fn($w) => (int)($w['home'] ?? 0) === 1));
$hero_src  = $flagged ?: $all_works;

// Due set di opere: orizzontali e verticali+quadrate
$hero_landscape = [];
$hero_portrait  = [];
foreach ($hero_src as $w) {
    $o = work_orient($w);
    $img = w_img($w['foto_big'] ?: $w['foto']);
    if ($o === 'landscape') { if (count($hero_landscape) < 20) $hero_landscape[] = $img; }
    elseif ($o === 'portrait' || $o === 'square') { if (count($hero_portrait) < 20) $hero_portrait[] = $img; }
    if (count($hero_landscape) >= 20 && count($hero_portrait) >= 20) break;
}

// Immagini Atelier (inserite 1 ogni 3 opere) — ottimizzate via thumb.php (cache) per caricarle in tempo
$hero_atelier = [];
foreach (array_slice(atelier_load_all(), 0, 14) as $a) {
    $rel = ltrim((string)$a['foto'], '/');
    $hero_atelier[] = IS_LOCAL
        ? ATELIER_BASE . $rel
        : 'thumb.php?p=' . rawurlencode('studio/imm/' . $rel) . '&w=1400';
}

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<div class="hero">
  <div class="hero-bg" id="heroBg"></div>
  <svg class="hero-title" viewBox="0 0 1000 104" preserveAspectRatio="xMidYMax meet" xmlns="http://www.w3.org/2000/svg" aria-label="AKIRA ZAKAMOTO" role="img">
    <text x="0" y="94" textLength="1000" lengthAdjust="spacingAndGlyphs"
          fill="#fff" font-family="Inter, Helvetica, Arial, sans-serif" font-weight="700" font-size="96">AKIRA ZAKAMOTO</text>
  </svg>
</div>
<script src="assets/menu.js?v=<?php echo ASSET_VER; ?>"></script>
<script>
(function () {
  var SETS = {
    landscape: <?php echo json_encode($hero_landscape, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    portrait:  <?php echo json_encode($hero_portrait,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    atelier:   <?php echo json_encode($hero_atelier,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
  };
  var bg = document.getElementById('heroBg');
  if (!bg) return;

  // Altezza hero = viewport meno l'header reale (così la scritta in basso è sempre intera)
  function fitHero() {
    var hero = document.querySelector('.hero');
    var header = document.querySelector('.site-header');
    if (hero && header) hero.style.height = (window.innerHeight - header.offsetHeight) + 'px';
  }
  fitHero();
  window.addEventListener('resize', fitHero);
  var slides = [document.createElement('div'), document.createElement('div')];
  slides.forEach(function (s) { s.className = 'hero-slide'; bg.appendChild(s); });

  var timer = null, cur = 0, mode = '', pool = [], idx = 0;
  var ready = [], readySet = {};
  function shuffle(a) { a = a.slice(); for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; } return a; }
  function set(el, url) { el.style.backgroundImage = 'url("' + url + '")'; }

  // Set in base alle proporzioni della finestra (non al dispositivo)
  function pickMode() { return (window.innerHeight > window.innerWidth) ? 'portrait' : 'landscape'; }

  // Pool: quadri + foto Atelier mescolati completamente a caso (nessun ordine fisso)
  function buildPool(art) {
    return shuffle((art || []).concat(SETS.atelier || []));
  }

  function markReady(u) { if (!readySet[u]) { readySet[u] = 1; ready.push(u); } }
  function preloadAll(urls) { urls.forEach(function (u) { var im = new Image(); im.onload = function () { markReady(u); }; im.src = u; }); }

  // Mostra un'immagine (già caricata) con crossfade + zoom
  function activate(el, url) {
    set(el, url);
    el.classList.remove('zooming'); void el.offsetWidth;   // riavvia l'animazione zoom
    el.classList.add('active', 'zooming');
    slides.forEach(function (s) { if (s !== el) s.classList.remove('active'); });
  }

  function tick() {
    if (ready.length < 2) return;            // nulla da alternare ancora
    idx = (idx + 1) % ready.length;
    var incoming = slides[(cur + 1) % 2];
    activate(incoming, ready[idx]);          // solo immagini pronte -> niente buchi bianchi
    cur = (cur + 1) % 2;
  }

  function start() {
    var m = pickMode();
    if (m === mode) return;
    mode = m;
    var art = SETS[m];
    if (!art || !art.length) art = SETS.landscape.concat(SETS.portrait);
    pool = buildPool(art);
    ready = []; readySet = {}; idx = 0;
    if (timer) clearInterval(timer);
    slides.forEach(function (s) { s.classList.remove('active', 'zooming'); s.style.backgroundImage = ''; });

    // 1) carico una immagine random e la mostro appena pronta
    var first = pool[0];
    function begin() {
      preloadAll(pool.slice(1));             // 2) carico tutte le altre
      timer = setInterval(tick, 4500);       // 3) presentazione a rotazione
    }
    var im0 = new Image();
    im0.onload = function () { markReady(first); activate(slides[0], first); cur = 0; begin(); };
    im0.onerror = function () { begin(); };
    im0.src = first;
  }
  start();
  var rt;
  window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(start, 300); });
})();
</script>
</body>
</html>
