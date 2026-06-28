<?php
require_once __DIR__ . '/include/data.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$r  = $id ? critica_find($id) : null;

if (!$r) {
    http_response_code(404);
    $page_title = 'Not found — Akira Zakamoto';
    include __DIR__ . '/include/head.php';
    include __DIR__ . '/include/nav.php';
    echo '<main class="page"><p>Text not found. <a href="files.php">Back to Files</a></p></main>';
    include __DIR__ . '/include/footer.php';
    exit;
}

$title_it = w_clean((string)$r['titolo']);
$title_en = w_clean((string)($r['titolo_en'] ?? ''));
if ($title_en === '') $title_en = $title_it;
$author = w_clean((string)$r['autore']);
$author = $author === '' ? '' : mb_convert_case(mb_strtolower($author, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
$year   = substr((string)$r['data'], 0, 4);
$text_it = crit_body((string)$r['testo']);
$text_en = crit_body((string)($r['testo_en'] ?? ''));
$link    = trim((string)($r['link'] ?? ''));
$has_en  = ($text_en !== '' || w_clean((string)($r['titolo_en'] ?? '')) !== '');
// Default = inglese (fallback all'italiano se manca la traduzione)
$def_title = $title_en;
$def_body  = $text_en !== '' ? $text_en : $text_it;
$page_title = $def_title . ' — Akira Zakamoto';

$flag_it = "<svg viewBox='0 0 3 2'><rect width='1' height='2' x='0' fill='#009246'/><rect width='1' height='2' x='1' fill='#fff'/><rect width='1' height='2' x='2' fill='#ce2b37'/></svg>";
$flag_en = "<svg viewBox='0 0 60 40'><rect width='60' height='40' fill='#012169'/><path d='M0,0 60,40 M60,0 0,40' stroke='#fff' stroke-width='8'/><path d='M0,0 60,40 M60,0 0,40' stroke='#C8102E' stroke-width='4'/><path d='M30,0 30,40 M0,20 60,20' stroke='#fff' stroke-width='12'/><path d='M30,0 30,40 M0,20 60,20' stroke='#C8102E' stroke-width='7'/></svg>";

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="page text-page" lang="en">
  <p class="tp-back"><a href="files.php">← Files</a></p>
  <h1 class="tp-title" id="tp-title"><?php echo htmlspecialchars($def_title); ?></h1>
  <p class="tp-author">
    <?php if ($link !== ''): ?>
      <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($author); ?> <span class="tp-linkico" aria-hidden="true">↗</span></a>
    <?php else: ?>
      <?php echo htmlspecialchars($author); ?>
    <?php endif; ?><?php if ($year) echo ' · ' . htmlspecialchars($year); ?>
  </p>

  <?php if ($has_en): ?>
  <div class="poem-flags tp-flags">
    <button type="button" class="poem-flag active" data-lang="en" title="English" aria-label="English"><?php echo $flag_en; ?></button>
    <button type="button" class="poem-flag" data-lang="it" title="Italiano" aria-label="Italiano"><?php echo $flag_it; ?></button>
  </div>
  <?php endif; ?>

  <div class="tp-body" id="tp-body" lang="en"><?php echo nl2br(htmlspecialchars($def_body)); ?></div>

  <div id="tp-src" hidden>
    <div data-lang="it" data-title="<?php echo htmlspecialchars($title_it, ENT_QUOTES); ?>"><?php echo nl2br(htmlspecialchars($text_it)); ?></div>
    <div data-lang="en" data-title="<?php echo htmlspecialchars($title_en, ENT_QUOTES); ?>"><?php echo nl2br(htmlspecialchars($text_en !== '' ? $text_en : '(English version coming soon)')); ?></div>
  </div>
</main>
<?php if ($has_en): ?>
<script>
(function(){
  var src=document.getElementById('tp-src'), t=document.getElementById('tp-title'), b=document.getElementById('tp-body');
  document.querySelectorAll('.tp-flags .poem-flag').forEach(function(btn){
    btn.addEventListener('click',function(){
      var l=btn.getAttribute('data-lang'), el=src.querySelector('[data-lang="'+l+'"]');
      if(!el) return;
      t.textContent=el.getAttribute('data-title'); b.innerHTML=el.innerHTML;
      document.querySelector('.text-page').setAttribute('lang',l);
      document.querySelectorAll('.tp-flags .poem-flag').forEach(function(x){x.classList.toggle('active',x===btn);});
    });
  });
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/include/footer.php'; ?>
