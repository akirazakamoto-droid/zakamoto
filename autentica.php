<?php
require_once __DIR__ . '/include/data.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Accesso consentito solo agli utenti loggati da galleries.php
if (empty($_SESSION['gallery_ok'])) {
    header('Location: galleries.php');
    exit;
}

require_once __DIR__ . '/include/db.php';
db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rows = $id ? db_fetch_all("SELECT * FROM work WHERE id = ?", [$id]) : [];
$w = $rows[0] ?? null;
if (!$w) { http_response_code(404); echo 'Opera non trovata.'; exit; }

// Codice d'archivio (work.archivio); generato e salvato se mancante
$code = trim((string)($w['archivio'] ?? ''));
if ($code === '' || $code === '0') {
    $numbers = '1234567890';
    $letters = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
    $special = '--!=!@@#++%';
    $code = substr(str_shuffle($numbers), 0, 2) . substr(str_shuffle($letters), 0, 5) . substr(str_shuffle($special), 0, 1) . ($id * 8);
    db()->prepare("UPDATE work SET archivio = ? WHERE id = ?")->execute([$code, $id]);
}

$w['archivio'] = $code;   // usa il codice finale (eventualmente appena generato)

// Impronta del record-registro (Fase 0): SHA-256 pronto per l'ancoraggio su NetworkM3
require_once __DIR__ . '/include/chain.php';
$chain = chain_anchor_local($w);

$title = w_title($w);
$tech  = w_tech($w);
$dim   = w_dimensions($w);
$date  = (string)$w['anno'];
$img   = w_img($w['foto_big'] ?: $w['foto']);
?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificato di autenticità — <?php echo htmlspecialchars($title); ?></title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; color:#222; background:#fff; margin:0; }
  .topbar { background:#333; color:#fff; text-align:center; padding:12px; }
  .topbar a { color:#fff; text-decoration:none; font-size:14px; letter-spacing:.04em; }
  .cert { max-width:820px; margin:30px auto; padding:0 24px 60px; }
  .cert img.opera { display:block; max-width:100%; height:auto; margin:0 auto 28px; }
  h1 { font-size:20px; text-transform:uppercase; letter-spacing:.08em; }
  .meta { color:#444; font-size:14px; margin:4px 0; }
  .body { font-size:15px; line-height:1.7; margin:22px 0; }
  .code { font-weight:bold; }
  .firma { margin-top:24px; }
  .firma img { width:200px; height:auto; }
  .studio { color:#555; font-size:13px; margin-top:18px; line-height:1.6; }
  .chain { margin:18px 0; padding:12px 14px; border:1px solid #e2e2e2; border-radius:6px; background:#fafafa; }
  .chain-hash { font-family:'Courier New',monospace; font-size:12px; word-break:break-all; color:#222; margin:0 0 8px; }
  .chain-note { font-size:12px; color:#666; margin:0; line-height:1.5; }
  @media print { .topbar { display:none; } .cert { margin-top:0; } }
</style>
</head>
<body>
  <div class="topbar"><a href="javascript:window.print()">🖨 Stampa l'autentica</a></div>
  <div class="cert">
    <img class="opera" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($title); ?>">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <?php if ($tech): ?><p class="meta">Tecnica: <?php echo htmlspecialchars($tech); ?></p><?php endif; ?>
    <?php if ($dim):  ?><p class="meta">Dimensioni: <?php echo htmlspecialchars($dim); ?></p><?php endif; ?>

    <p class="body">
      Con il presente certificato dichiaro che l'opera <strong><?php echo htmlspecialchars($title); ?></strong>
      è stata inserita nell'archivio in data <?php echo htmlspecialchars($date); ?>,
      <?php if ($dim || $tech): ?>con le seguenti caratteristiche tecniche:
        <?php echo htmlspecialchars(trim($dim . ($dim && $tech ? ' · ' : '') . $tech)); ?>.<?php endif; ?><br><br>
      Il codice d'archivio dell'opera è il seguente: <span class="code"><?php echo htmlspecialchars($code); ?></span>
    </p>

    <div class="chain">
      <p class="meta" style="margin-bottom:4px">Impronta del registro (SHA-256):</p>
      <p class="chain-hash"><?php echo htmlspecialchars($chain['hash']); ?></p>
      <p class="chain-note">
        <?php if (!empty($chain['row']['txid'])): ?>
          ✓ Registrata sul registro a catena di hash di <strong>NetworkM3</strong> — blocco
          <?php echo htmlspecialchars($chain['row']['txid']); ?><?php if (!empty($chain['row']['anchored_at'])): ?>, dal <?php echo htmlspecialchars($chain['row']['anchored_at']); ?><?php endif; ?>:
          dato immutabile e verificabile, in attesa dell'ancoraggio periodico sulla futura rete Hyperledger Fabric (org <em>zkm.gallery</em>).
        <?php else: ?>
          Impronta calcolata e registrata, pronta per il registro a catena di hash di <strong>NetworkM3</strong>.
        <?php endif; ?>
      </p>
    </div>

    <div class="firma">
      <p class="meta">Firma autentica dell'autore:</p>
      <img src="img/firmazaka.jpg" alt="Firma Akira Zakamoto">
    </div>

    <div class="studio">
      Studio d'Arte Zakamoto — Bottega Indaco<br>
      Via Belfiore, 20 — 10125 Torino<br>
      Telefono: 334.942.87.70 · akirazakamoto@gmail.com · www.zakamoto.com
    </div>
  </div>
</body>
</html>
