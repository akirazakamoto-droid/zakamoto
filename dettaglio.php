<?php
require_once __DIR__ . '/include/data.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$w  = $id ? work_find($id) : null;

if (!$w) {
    http_response_code(404);
    $page_title = 'Not found — Akira Zakamoto';
    include __DIR__ . '/include/head.php';
    include __DIR__ . '/include/nav.php';
    echo '<main class="page"><p>Work not found. <a href="index.php">Back to home</a></p></main>';
    include __DIR__ . '/include/footer.php';
    exit;
}

$title = w_title($w);
$year  = w_year($w);
$tech  = w_tech($w);
$dim   = w_dimensions($w);
$big   = w_img($w['foto_big'] ?: $w['foto']);
$info  = w_clean((string)($w['info'] ?? ''));
$page_title = $title . ' — Akira Zakamoto';

// --- Open Graph / Twitter: immagine + titolo + link per la condivisione social ---
$fwd      = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$is_https = $fwd === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$scheme   = ($is_https || stripos($_SERVER['HTTP_HOST'] ?? '', 'zakamoto.com') !== false) ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'zakamoto.com';
$page_url = $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? ('/dettaglio.php?id=' . $id));
$img_abs  = (strpos($big, '//') === false) ? ($scheme . '://' . $host . $big) : $big;
$og_desc  = trim(($tech ?: '') . ($dim ? ($tech ? ', ' : '') . $dim : '') . ($year ? " ($year)" : ''));
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$head_extra =
    '<meta property="og:type" content="article">' .
    '<meta property="og:site_name" content="Akira Zakamoto">' .
    '<meta property="og:title" content="' . $h($title) . '">' .
    '<meta property="og:description" content="' . $h($og_desc) . '">' .
    '<meta property="og:url" content="' . $h($page_url) . '">' .
    '<meta property="og:image" content="' . $h($img_abs) . '">' .
    '<meta name="twitter:card" content="summary_large_image">' .
    '<meta name="twitter:title" content="' . $h($title) . '">' .
    '<meta name="twitter:description" content="' . $h($og_desc) . '">' .
    '<meta name="twitter:image" content="' . $h($img_abs) . '">';

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="detail">
  <div class="detail-img">
    <img src="<?php echo htmlspecialchars($big); ?>" alt="<?php echo htmlspecialchars($title); ?>" decoding="async">
  </div>
  <div class="detail-meta">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <?php if ($year): ?><p class="dm-year"><?php echo htmlspecialchars($year); ?></p><?php endif; ?>
    <?php if ($tech): ?><p class="dm-tech"><?php echo htmlspecialchars($tech); ?></p><?php endif; ?>
    <?php if ($dim):  ?><p class="dm-dim"><?php echo htmlspecialchars($dim); ?></p><?php endif; ?>
    <?php if ($info !== ''): ?><div class="dm-info"><?php echo nl2br(htmlspecialchars($info)); ?></div><?php endif; ?>
    <?php $projects = work_project_links($w); if ($projects): ?>
    <p class="dm-project">Project on motolese.com:
      <?php foreach ($projects as $i => $pl): ?><?php echo $i ? ' · ' : ' '; ?><a href="<?php echo htmlspecialchars($pl[1], ENT_QUOTES); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($pl[0]); ?></a><?php endforeach; ?>
    </p>
    <?php endif; ?>
    <p class="dm-back"><a href="art.php">← All works</a></p>
  </div>
</main>
<?php include __DIR__ . '/include/footer.php'; ?>
