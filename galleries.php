<?php
require_once __DIR__ . '/include/data.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$page_title = 'Galleries & Collectors — Akira Zakamoto';
$active = 'galleries';

// --- Endpoint: genera/ritorna il codice registro di un'opera (solo loggati) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'gencode') {
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['gallery_ok'])) { echo json_encode(['ok' => false, 'error' => 'auth']); exit; }
    require_once __DIR__ . '/include/db.php';
    db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
    $id  = (int)($_POST['id'] ?? 0);
    $row = $id ? (db_fetch_all("SELECT archivio FROM work WHERE id = ?", [$id])[0] ?? null) : null;
    if (!$row) { echo json_encode(['ok' => false, 'error' => 'not found']); exit; }
    $code = trim((string)$row['archivio']);
    if ($code === '' || $code === '0') {
        $numbers = '1234567890';
        $letters = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
        $special = '--!=!@@#++%';
        for ($t = 0; $t < 25; $t++) {
            $code = substr(str_shuffle($numbers), 0, 2) . substr(str_shuffle($letters), 0, 5) . substr(str_shuffle($special), 0, 1) . ($id * 8);
            $dup = db_fetch_all("SELECT id FROM work WHERE archivio = ? AND id <> ?", [$code, $id]);
            if (!$dup) break;   // unico
        }
        db()->prepare("UPDATE work SET archivio = ? WHERE id = ?")->execute([$code, $id]);
    }
    echo json_encode(['ok' => true, 'code' => $code]); exit;
}

// --- Endpoint: richiesta "sono il proprietario" (notifica all'artista) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim') {
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['gallery_ok'])) { echo json_encode(['ok' => false, 'error' => 'auth']); exit; }
    require_once __DIR__ . '/include/db.php';
    db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
    $id = (int)($_POST['id'] ?? 0);
    $w  = $id ? (db_fetch_all("SELECT id,titolo,titolo_en,archivio,tecnica,larghezza,altezza,anno FROM work WHERE id = ?", [$id])[0] ?? null) : null;
    if (!$w) { echo json_encode(['ok' => false, 'error' => 'not found']); exit; }
    $uid = (int)($_SESSION['gallery_uid'] ?? 0);
    $u   = $uid ? (db_fetch_all("SELECT * FROM gallery_users WHERE id = ?", [$uid])[0] ?? []) : [];
    $uname  = trim((string)($u['cognome'] ?? '') . ' ' . (string)($u['nome'] ?? ''));
    $ulogin = (string)($_SESSION['gallery_login'] ?? ($u['login'] ?? ''));
    $ucode  = (string)($u['codice'] ?? '');
    $uemail = (string)($u['email'] ?? '');
    $title  = html_entity_decode(trim((string)($w['titolo_en'] ?: $w['titolo'])), ENT_QUOTES, 'UTF-8');
    $dim    = ((int)$w['larghezza'] > 0 && (int)$w['altezza'] > 0) ? ((int)$w['larghezza'] . ' × ' . (int)$w['altezza'] . ' cm') : '';
    require_once __DIR__ . '/include/PHPMailer/Exception.php';
    require_once __DIR__ . '/include/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/include/PHPMailer/SMTP.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST; $mail->SMTPAuth = true; $mail->Username = SMTP_USER; $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE; $mail->Port = SMTP_PORT; $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress(CONTACT_TO);
        if ($uemail && filter_var($uemail, FILTER_VALIDATE_EMAIL)) $mail->addReplyTo($uemail, $uname ?: $ulogin);
        $mail->Subject = "Richiesta proprietà — " . $title;
        $mail->Body =
            "Galleria/Collezionista: " . $ulogin . ($uname ? " ($uname)" : '') . ($ucode ? " — codice $ucode" : '') . ($uemail ? " — $uemail" : '') . "\n\n" .
            "dichiara di essere proprietario dell'opera:\n" .
            "Titolo: $title (#" . (int)$w['id'] . ")\n" .
            "Codice registro: " . trim((string)$w['archivio']) . "\n" .
            ($w['tecnica'] ? "Tecnica: " . html_entity_decode((string)$w['tecnica'], ENT_QUOTES, 'UTF-8') . "\n" : '') .
            ($dim ? "Dimensioni: $dim\n" : '') .
            ($w['anno'] ? "Anno: " . substr((string)$w['anno'], 0, 4) . "\n" : '') .
            "\nVerifica e assegna il proprietario nell'area admin.";
        $mail->send();
        echo json_encode(['ok' => true]); exit;
    } catch (Throwable $e) {
        error_log('Claim mail error: ' . $mail->ErrorInfo);
        echo json_encode(['ok' => false, 'error' => 'mail']); exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = trim((string)$_POST['login']);
    $pass  = (string)($_POST['pass'] ?? '');
    $uid = gallery_user_check($login, $pass);
    if ($uid) {
        $_SESSION['gallery_ok'] = true;
        $_SESSION['gallery_uid'] = $uid;
        $_SESSION['gallery_login'] = $login;
        header('Location: galleries.php');
        exit;
    }
    $error = 'Wrong login or password. Please try again.';
}
if (isset($_GET['logout'])) {
    unset($_SESSION['gallery_ok'], $_SESSION['gallery_login']);
    header('Location: galleries.php');
    exit;
}
$unlocked = !empty($_SESSION['gallery_ok']);

// --- Catalogo + opzioni dei filtri (solo se loggato) ---
$series_labels = [
    'Dreams' => 'Dreams', 'Life' => 'Life is a game', 'Robot' => 'Robot',
    'Megamix' => 'Megamix', 'deflorationis' => 'Deflorationis', 'Kitty' => 'Kitty die Katze',
    'Future' => 'Future', 'Zoo' => 'Zoolatry', 'Nudi' => 'Nudes',
];
$catalog = []; $dimsSet = []; $yearsSet = []; $seriesSet = [];
if ($unlocked) {
    foreach (works_all_any() as $w) {
        $dim  = w_dimensions($w);
        $year = w_year($w);
        $cat  = trim((string)($w['cat'] ?? ''));
        $rel  = ltrim((string)($w['foto_big'] ?: $w['foto']), '/');
        $img  = $rel === '' ? '' : (IS_LOCAL ? w_img($rel) : 'thumb.php?p=' . rawurlencode('MAT/' . $rel) . '&w=1000');
        $catalog[] = [
            'id'    => (int)$w['id'],
            'it'    => w_clean((string)($w['titolo'] ?? '')),
            'en'    => w_clean((string)($w['titolo_en'] ?? '')),
            'title' => w_title($w),
            'tech'  => w_tech($w),
            'dim'   => $dim,
            'year'  => $year,
            'cat'   => $cat,
            'serie' => $cat === '' ? '' : ($series_labels[$cat] ?? $cat),
            'code'  => trim((string)($w['archivio'] ?? '')),
            'img'   => $img,
        ];
        if ($dim !== '')  $dimsSet[$dim] = true;
        if ($year !== '') $yearsSet[$year] = true;
        if ($cat !== '')  $seriesSet[$cat] = ($series_labels[$cat] ?? $cat);
    }
    $dims = array_keys($dimsSet);  sort($dims, SORT_NATURAL);
    $years = array_keys($yearsSet); rsort($years);
    $series = [];
    foreach ($series_labels as $c => $l) if (isset($seriesSet[$c])) $series[$c] = $l;
    foreach ($seriesSet as $c => $l) if (!isset($series[$c])) $series[$c] = $l;
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<?php if (!$unlocked): ?>
<main class="page gate-page">
  <h1>Galleries &amp; Collectors</h1>
  <p class="gate-intro">Reserved area for galleries and collectors. Please log in to view the full catalogue.</p>
  <?php if ($error): ?><p class="form-err"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <form class="contact-form gate-form" method="post" action="galleries.php">
    <label>Login <span>*</span>
      <input type="text" name="login" required autofocus autocomplete="username">
    </label>
    <label>Password <span>*</span>
      <input type="password" name="pass" required autocomplete="current-password">
    </label>
    <button type="submit">Enter</button>
  </form>
</main>
<?php else: ?>
<div class="gate-bar">
  <span>Galleries &amp; Collectors — full catalogue codes</span>
  <a href="galleries.php?logout=1">Log out</a>
</div>
<main class="page catalog-page">
  <form class="cat-search" id="catSearch" onsubmit="return false">
    <div class="cs-field"><label>Titolo (IT)</label><input type="text" id="f_it" autocomplete="off"></div>
    <div class="cs-field"><label>Title (EN)</label><input type="text" id="f_en" autocomplete="off"></div>
    <div class="cs-field"><label>Codice registro</label><input type="text" id="f_code" autocomplete="off"></div>
    <div class="cs-field"><label>Dimensioni</label>
      <select id="f_dim"><option value="">tutte</option>
        <?php foreach ($dims as $d): ?><option value="<?php echo $h($d); ?>"><?php echo $h($d); ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="cs-field"><label>Anno</label>
      <select id="f_year"><option value="">tutti</option>
        <?php foreach ($years as $y): ?><option value="<?php echo $h($y); ?>"><?php echo $h($y); ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="cs-field"><label>Serie</label>
      <select id="f_serie"><option value="">tutte</option>
        <?php foreach ($series as $c => $l): ?><option value="<?php echo $h($c); ?>"><?php echo $h($l); ?></option><?php endforeach; ?>
      </select>
    </div>
  </form>

  <p class="cat-count" id="catCount"></p>
  <div class="cat-table-wrap">
    <table class="cat-table">
      <thead><tr><th>Titolo (IT)</th><th>Title (EN)</th><th>Tecnica</th><th>Dimensioni</th><th>Anno</th><th>Serie</th><th>Codice Registro</th><th></th></tr></thead>
      <tbody id="catBody"></tbody>
    </table>
  </div>
</main>

<div id="cprev" class="cprev" hidden>
  <div class="cprev-box">
    <button class="cprev-close" id="cprevClose" aria-label="Close">×</button>
    <img id="cprevImg" src="" alt="">
    <div class="cprev-info">
      <div id="cprevTitle"></div>
      <div id="cprevMeta"></div>
      <div id="cprevCode"></div>
      <button type="button" id="cprevClaim" class="cprev-claim">Sono il proprietario di quest'opera</button>
      <div id="cprevClaimMsg" class="cprev-claim-msg"></div>
    </div>
  </div>
</div>

<script>
(function () {
  var CAT = <?php echo json_encode($catalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
  var byId = function (i) { return document.getElementById(i); };
  var f_it = byId('f_it'), f_en = byId('f_en'), f_code = byId('f_code'),
      f_dim = byId('f_dim'), f_year = byId('f_year'), f_serie = byId('f_serie');
  var body = byId('catBody'), count = byId('catCount');
  var norm = function (s) { return (s || '').toString().toLowerCase(); };

  function td(text, cls) { var e = document.createElement('td'); if (cls) e.className = cls; e.textContent = (text === '' || text == null) ? '—' : text; return e; }

  function hasFilters() {
    return f_it.value.trim() || f_en.value.trim() || f_code.value.trim() || f_dim.value || f_year.value || f_serie.value;
  }

  function render() {
    body.innerHTML = '';
    if (!hasFilters()) { count.textContent = 'Usa la ricerca per trovare le opere.'; return; }
    var it = norm(f_it.value), en = norm(f_en.value), code = norm(f_code.value);
    var dim = f_dim.value, year = f_year.value, serie = f_serie.value;
    var frag = document.createDocumentFragment(), n = 0;
    for (var i = 0; i < CAT.length; i++) {
      var w = CAT[i];
      if (it && norm(w.it).indexOf(it) < 0) continue;
      if (en && norm(w.en).indexOf(en) < 0) continue;
      if (code && norm(w.code).indexOf(code) < 0) continue;
      if (dim && w.dim !== dim) continue;
      if (year && w.year !== year) continue;
      if (serie && w.cat !== serie) continue;
      var tr = document.createElement('tr');
      tr.setAttribute('data-i', i);
      tr.appendChild(td(w.it));
      tr.appendChild(td(w.en || w.title));
      tr.appendChild(td(w.tech));
      tr.appendChild(td(w.dim));
      tr.appendChild(td(w.year));
      tr.appendChild(td(w.serie));
      tr.appendChild(td(w.code, 'code-cell'));
      var act = document.createElement('td');
      var b = document.createElement('button'); b.className = 'prevbtn'; b.textContent = 'Anteprima'; b.setAttribute('data-i', i);
      act.appendChild(b); tr.appendChild(act);
      frag.appendChild(tr); n++;
    }
    body.appendChild(frag);
    count.textContent = n + (n === 1 ? ' opera' : ' opere');
  }

  // Anteprima
  var cprev = byId('cprev'), cprevImg = byId('cprevImg'),
      cprevTitle = byId('cprevTitle'), cprevMeta = byId('cprevMeta'), cprevCode = byId('cprevCode'),
      cprevClaim = byId('cprevClaim'), cprevClaimMsg = byId('cprevClaimMsg');
  var curId = 0;

  function updateRowCode(i, code) {
    var tr = body.querySelector('tr[data-i="' + i + '"]');
    if (tr) { var c = tr.querySelector('.code-cell'); if (c) c.textContent = code; }
  }

  function openPrev(i) {
    var w = CAT[i];
    curId = w.id;
    cprevClaim.disabled = false;
    cprevClaimMsg.textContent = ''; cprevClaimMsg.className = 'cprev-claim-msg';
    cprevImg.src = w.img || '';
    cprevTitle.textContent = w.title || w.it || w.en || '';
    cprevMeta.textContent = [w.tech, w.dim, w.year, w.serie].filter(Boolean).join(' · ');
    var code = w.code;
    if (!code || code === '0') {
      cprevCode.textContent = 'Codice: generazione…';
      var b = new URLSearchParams(); b.set('action', 'gencode'); b.set('id', w.id);
      fetch('galleries.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: b.toString(), credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (j.ok) { w.code = j.code; cprevCode.textContent = 'Codice registro: ' + j.code; updateRowCode(i, j.code); }
          else { cprevCode.textContent = 'Codice: errore'; }
        }).catch(function () { cprevCode.textContent = 'Codice: errore di rete'; });
    } else {
      cprevCode.textContent = 'Codice registro: ' + code;
    }
    cprev.hidden = false;
  }
  function closePrev() { cprev.hidden = true; cprevImg.src = ''; }

  body.addEventListener('click', function (e) {
    var b = e.target.closest('.prevbtn'); if (!b) return;
    openPrev(parseInt(b.getAttribute('data-i'), 10));
  });
  byId('cprevClose').addEventListener('click', closePrev);
  cprev.addEventListener('click', function (e) { if (e.target === cprev) closePrev(); });

  cprevClaim.addEventListener('click', function () {
    if (!curId) return;
    cprevClaim.disabled = true;
    cprevClaimMsg.textContent = 'Invio…'; cprevClaimMsg.className = 'cprev-claim-msg';
    var b = new URLSearchParams(); b.set('action', 'claim'); b.set('id', curId);
    fetch('galleries.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: b.toString(), credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok) { cprevClaimMsg.textContent = 'Richiesta inviata. L\'artista verificherà e ti contatterà.'; cprevClaimMsg.className = 'cprev-claim-msg ok'; }
        else { cprevClaim.disabled = false; cprevClaimMsg.textContent = 'Errore nell\'invio. Riprova.'; cprevClaimMsg.className = 'cprev-claim-msg err'; }
      })
      .catch(function () { cprevClaim.disabled = false; cprevClaimMsg.textContent = 'Errore di rete.'; cprevClaimMsg.className = 'cprev-claim-msg err'; });
  });

  [f_it, f_en, f_code].forEach(function (e) { e.addEventListener('input', render); });
  [f_dim, f_year, f_serie].forEach(function (e) { e.addEventListener('change', render); });
  render();   // default: nessun risultato finché non si cerca
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/include/footer.php'; ?>
