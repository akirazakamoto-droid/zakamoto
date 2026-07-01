<?php
// Registro a catena di hash (stesso modello "Fase 1 Beta" di NetworkM3/Buon Talento):
// ogni evento (registrazione, trasferimento) diventa un blocco append-only che referenzia
// l'hash del blocco precedente. Nessun nodo Fabric reale è coinvolto: è un ledger locale,
// verificabile e a prova di manomissione, pronto per un futuro ancoraggio periodico su
// NetworkM3 quando la rete Hyperledger Fabric multi-nodo (org zkm.gallery) sarà operativa.
// Sulla catena vanno SOLO hash e codici pseudonimi, mai dati personali.
require_once __DIR__ . '/db.php';

function chain_ensure_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        "CREATE TABLE IF NOT EXISTS work_chain (
            work_id INT PRIMARY KEY,
            record_hash CHAR(64) NOT NULL DEFAULT '',
            owner_code  VARCHAR(40) NOT NULL DEFAULT '',
            payload     VARCHAR(255) NOT NULL DEFAULT '',
            img_rel     VARCHAR(255) NOT NULL DEFAULT '',
            img_hash    CHAR(64) NOT NULL DEFAULT '',
            txid        VARCHAR(160) NOT NULL DEFAULT '',
            chain       VARCHAR(40) NOT NULL DEFAULT '',
            anchored_at DATETIME NULL,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $done = true;
}

function chain_row(int $workId): ?array
{
    $r = db_fetch_all("SELECT * FROM work_chain WHERE work_id = ?", [$workId]);
    return $r[0] ?? null;
}

function chain_owner_code(array $w): string
{
    $oid = (int)($w['owner'] ?? 0);
    if ($oid <= 0) return '';
    $r = db_fetch_all("SELECT codice FROM gallery_users WHERE id = ?", [$oid]);
    return $r ? trim((string)($r[0]['codice'] ?? '')) : '';
}

// SHA-256 del file immagine, con cache in work_chain (evita di rileggere file grandi).
function chain_image_hash(int $workId, string $rel): string
{
    $rel = ltrim($rel, '/');
    $row = chain_row($workId);
    if ($row && !empty($row['img_hash']) && (string)($row['img_rel'] ?? '') === $rel) return (string)$row['img_hash'];
    $path = '/var/www/html/new/MAT/' . $rel;
    if ($rel === '' || !is_file($path)) return '';
    $h = @hash_file('sha256', $path);
    return $h ?: '';
}

// Record canonico: SOLO id, codice registro, codice proprietario (pseudonimo), anno, hash immagine.
function chain_build(array $w): array
{
    $id    = (int)$w['id'];
    $reg   = trim((string)($w['archivio'] ?? ''));
    $owner = chain_owner_code($w);
    $year  = substr((string)($w['anno'] ?? ''), 0, 4);
    $rel   = ltrim((string)(($w['foto_big'] ?? '') ?: ($w['foto'] ?? '')), '/');
    $img   = chain_image_hash($id, $rel);
    $payload = "v1|id:$id|reg:$reg|owner:$owner|date:$year|img:$img";
    return ['payload' => $payload, 'hash' => hash('sha256', $payload), 'owner' => $owner, 'img_rel' => $rel, 'img_hash' => $img];
}

function chain_ledger_ensure_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        "CREATE TABLE IF NOT EXISTS chain_ledger (
            id              INT PRIMARY KEY AUTO_INCREMENT,
            work_id         INT NOT NULL,
            event_type      VARCHAR(20) NOT NULL,
            payload         TEXT NOT NULL,
            prev_block_hash CHAR(64) NOT NULL,
            block_hash      CHAR(64) NOT NULL,
            created_at      DATETIME NOT NULL,
            INDEX idx_work (work_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $done = true;
}

function chain_ledger_last_hash(): string
{
    $r = db_fetch_all("SELECT block_hash FROM chain_ledger ORDER BY id DESC LIMIT 1");
    return $r ? (string)$r[0]['block_hash'] : str_repeat('0', 64);
}

// Aggiunge un blocco alla catena (register|transfer) e sincronizza work_chain (idempotente
// per il testo mostrato in autentica.php: txid = riferimento al blocco). Ritorna hash/riga
// nello stesso formato già usato da autentica.php (['hash'=>..., 'row'=>...]).
function chain_ledger_append(array $w, string $eventType): array
{
    chain_ledger_ensure_table();
    $id  = (int)$w['id'];
    $rec = chain_build($w);

    $payload = [
        'event'         => $eventType,
        'work_id'       => $id,
        'registry_code' => trim((string)($w['archivio'] ?? '')),
        'owner_code'    => $rec['owner'],
        'titolo'        => (string)($w['titolo'] ?? ''),
        'tecnica'       => (string)($w['tecnica'] ?? ''),
        'altezza'       => (string)($w['altezza'] ?? ''),
        'larghezza'     => (string)($w['larghezza'] ?? ''),
        'anno'          => substr((string)($w['anno'] ?? ''), 0, 4),
        'img_hash'      => $rec['img_hash'],
        'record_hash'   => $rec['hash'],
    ];
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $prevHash    = chain_ledger_last_hash();
    $createdAt   = date('Y-m-d H:i:s');
    $blockHash   = hash('sha256', $prevHash . '|' . $payloadJson . '|' . $createdAt);

    db()->prepare(
        "INSERT INTO chain_ledger (work_id, event_type, payload, prev_block_hash, block_hash, created_at)
         VALUES (?,?,?,?,?,?)"
    )->execute([$id, $eventType, $payloadJson, $prevHash, $blockHash, $createdAt]);
    $blockId = (int)db()->lastInsertId();

    chain_ensure_table();
    db()->prepare(
        "INSERT INTO work_chain (work_id, record_hash, owner_code, payload, img_rel, img_hash, txid, chain, anchored_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,NOW())
         ON DUPLICATE KEY UPDATE record_hash=VALUES(record_hash), owner_code=VALUES(owner_code),
           payload=VALUES(payload), img_rel=VALUES(img_rel), img_hash=VALUES(img_hash),
           txid=VALUES(txid), chain=VALUES(chain), anchored_at=VALUES(anchored_at), updated_at=NOW()"
    )->execute([$id, $rec['hash'], $rec['owner'], $rec['payload'], $rec['img_rel'], $rec['img_hash'], 'LEDGER-' . $blockId, 'networkm3-ledger', $createdAt]);

    $rec['block_id']         = $blockId;
    $rec['block_hash']       = $blockHash;
    $rec['prev_block_hash']  = $prevHash;
    $rec['row']              = chain_row($id);
    return $rec;
}

// Registra l'opera sulla catena la prima volta che viene vista (evento "register");
// alle visite successive si limita a ricalcolare l'impronta corrente senza duplicare il blocco.
function chain_anchor_local(array $w): array
{
    chain_ensure_table();
    $existing = chain_row((int)$w['id']);
    if ($existing && !empty($existing['txid'])) {
        $rec = chain_build($w);
        $rec['row'] = $existing;
        return $rec;
    }
    return chain_ledger_append($w, 'register');
}

// Da richiamare quando cambia il proprietario di un'opera (work.owner): aggiunge un
// blocco "transfer" alla catena con il nuovo codice proprietario pseudonimo.
function chain_register_transfer(array $w): array
{
    return chain_ledger_append($w, 'transfer');
}

// Verifica l'integrità dell'intera catena ricalcolando ogni hash: usata dall'explorer
// pubblico e da eventuali audit automatizzati.
function chain_ledger_verify(): array
{
    chain_ledger_ensure_table();
    $rows   = db_fetch_all("SELECT * FROM chain_ledger ORDER BY id ASC");
    $prev   = str_repeat('0', 64);
    $errors = [];
    foreach ($rows as $r) {
        if ((string)$r['prev_block_hash'] !== $prev) {
            $errors[] = "Blocco #{$r['id']}: prev_block_hash non combacia con il blocco precedente";
        }
        $expected = hash('sha256', $r['prev_block_hash'] . '|' . $r['payload'] . '|' . $r['created_at']);
        if ($expected !== $r['block_hash']) {
            $errors[] = "Blocco #{$r['id']}: hash non corrisponde ai dati (possibile manomissione)";
        }
        $prev = (string)$r['block_hash'];
    }
    return ['ok' => empty($errors), 'errors' => $errors, 'blocks' => count($rows)];
}

function chain_ledger_list(int $limit = 200): array
{
    chain_ledger_ensure_table();
    return db_fetch_all("SELECT * FROM chain_ledger ORDER BY id DESC LIMIT " . max(1, min(1000, $limit)));
}
