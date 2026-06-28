<?php
declare(strict_types=1);
// PDO wrapper minimale (usato solo in produzione).
$GLOBALS['__db_pdo'] = null;

function db_init(array $cfg): void
{
    if ($GLOBALS['__db_pdo'] instanceof PDO) return;
    $GLOBALS['__db_pdo'] = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

function db(): PDO
{
    if (!($GLOBALS['__db_pdo'] instanceof PDO)) {
        throw new RuntimeException('Database not initialized.');
    }
    return $GLOBALS['__db_pdo'];
}

function db_fetch_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
