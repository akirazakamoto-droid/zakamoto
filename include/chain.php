<?php
// Fase 0 — "ancoraggio" locale del record-registro di un'opera (impronta SHA-256),
// pronto per essere registrato on-chain su NetworkM3 (Hyperledger Fabric, org zkm.gallery).
// On-chain andranno SOLO hash + codici pseudonimi, mai dati personali.
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

// Calcola e memorizza l'impronta localmente (idempotente). Ritorna il record + la riga DB.
function chain_anchor_local(array $w): array
{
    chain_ensure_table();
    $rec = chain_build($w);
    $st = db()->prepare(
        "INSERT INTO work_chain (work_id, record_hash, owner_code, payload, img_rel, img_hash, updated_at)
         VALUES (?,?,?,?,?,?,NOW())
         ON DUPLICATE KEY UPDATE record_hash=VALUES(record_hash), owner_code=VALUES(owner_code),
           payload=VALUES(payload), img_rel=VALUES(img_rel), img_hash=VALUES(img_hash), updated_at=NOW()"
    );
    $st->execute([(int)$w['id'], $rec['hash'], $rec['owner'], $rec['payload'], $rec['img_rel'], $rec['img_hash']]);
    $rec['row'] = chain_row((int)$w['id']);
    return $rec;
}
