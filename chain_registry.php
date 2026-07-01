<?php
require_once __DIR__ . '/include/data.php';
require_once __DIR__ . '/include/db.php';
db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
require_once __DIR__ . '/include/chain.php';

$verify = chain_ledger_verify();
$blocks = chain_ledger_list(200);

function ev_label(string $e): string
{
    return $e === 'transfer' ? 'Trasferimento' : 'Registrazione';
}
?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro a catena — NetworkM3</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; color:#222; background:#fff; margin:0; }
  .wrap { max-width:960px; margin:30px auto; padding:0 24px 60px; }
  h1 { font-size:20px; text-transform:uppercase; letter-spacing:.08em; }
  p.intro { color:#444; font-size:14px; line-height:1.6; }
  .status { padding:10px 14px; border-radius:6px; font-size:13px; margin:14px 0 22px; }
  .status.ok  { background:#eaf7ee; color:#1b6e34; border:1px solid #c9e9d3; }
  .status.bad { background:#fdeaea; color:#a02020; border:1px solid #f0c6c6; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid #eee; vertical-align:top; }
  th { color:#666; font-weight:normal; text-transform:uppercase; font-size:11px; letter-spacing:.04em; }
  .hash { font-family:'Courier New',monospace; font-size:11px; word-break:break-all; color:#333; }
  .ev { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; background:#eef1f5; }
</style>
</head>
<body>
  <div class="wrap">
    <h1>Registro a catena — NetworkM3</h1>
    <p class="intro">
      Ogni riga è un blocco: registra un evento (registrazione o trasferimento di un'opera) e referenzia
      l'hash del blocco precedente, rendendo la catena verificabile e a prova di manomissione.
      Nessun dato personale è presente — solo hash e codici pseudonimi.
    </p>

    <div class="status <?php echo $verify['ok'] ? 'ok' : 'bad'; ?>">
      <?php if ($verify['ok']): ?>
        ✓ Catena verificata: <?php echo (int)$verify['blocks']; ?> blocchi, nessuna incoerenza rilevata.
      <?php else: ?>
        ✗ Rilevate incoerenze nella catena: <?php echo htmlspecialchars(implode(' — ', $verify['errors'])); ?>
      <?php endif; ?>
    </div>

    <table>
      <thead>
        <tr><th>#</th><th>Evento</th><th>Data</th><th>Payload / hash blocco</th></tr>
      </thead>
      <tbody>
        <?php foreach ($blocks as $b): ?>
        <tr>
          <td><?php echo (int)$b['id']; ?></td>
          <td><span class="ev"><?php echo htmlspecialchars(ev_label($b['event_type'])); ?></span></td>
          <td><?php echo htmlspecialchars($b['created_at']); ?></td>
          <td>
            <div class="hash"><?php echo htmlspecialchars($b['payload']); ?></div>
            <div class="hash" style="margin-top:4px;color:#888">block: <?php echo htmlspecialchars($b['block_hash']); ?></div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$blocks): ?>
        <tr><td colspan="4" style="color:#888">Nessun blocco registrato ancora.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
