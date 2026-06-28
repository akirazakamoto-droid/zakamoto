<?php
// Oscura un'opera (modo=0). Usato da art_s.php.
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0 || IS_LOCAL) { http_response_code(400); echo json_encode(['ok' => false]); exit; }
try {
    require_once __DIR__ . '/include/db.php';
    db_init(['dsn' => DB_DSN, 'user' => DB_USER, 'pass' => DB_PASS]);
    db()->prepare("UPDATE work SET modo = 0 WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
