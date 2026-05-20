<?php
// TeleFlow — CRUD del mapeo dígito → cola para atajos de *7700
ignore_user_abort(true); set_time_limit(15);
header('Content-Type: application/json');

require __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['tf_user'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    try {
        $tf->exec("CREATE TABLE IF NOT EXISTS queue_shortcut (
            digit TINYINT NOT NULL PRIMARY KEY,
            queue VARCHAR(32) NOT NULL,
            label VARCHAR(80) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $_) {}
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>'db_init_fail: ' . $e->getMessage()]); exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $rows = $tf->query("SELECT digit, queue, label, active FROM queue_shortcut ORDER BY digit")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok'=>true, 'shortcuts'=>$rows]);
    exit;
}

if ($action === 'set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $digit = (int)($_POST['digit'] ?? 0);
    $queue = preg_replace('/\D/', '', $_POST['queue'] ?? '');
    $label = trim($_POST['label'] ?? '');
    if ($digit < 1 || $digit > 9) { echo json_encode(['ok'=>false,'error'=>'digit must be 1..9']); exit; }
    if (!$queue)                  { echo json_encode(['ok'=>false,'error'=>'queue required']); exit; }
    try {
        $tf->prepare("INSERT INTO queue_shortcut (digit, queue, label, active) VALUES (?, ?, ?, 1)
                      ON DUPLICATE KEY UPDATE queue=VALUES(queue), label=VALUES(label), active=1")
           ->execute([$digit, $queue, $label]);
        echo json_encode(['ok'=>true]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $digit = (int)($_POST['digit'] ?? 0);
    if ($digit < 1 || $digit > 9) { echo json_encode(['ok'=>false,'error'=>'digit must be 1..9']); exit; }
    try {
        $tf->prepare("DELETE FROM queue_shortcut WHERE digit = ?")->execute([$digit]);
        echo json_encode(['ok'=>true]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
