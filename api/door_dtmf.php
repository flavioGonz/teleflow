<?php
// TeleFlow — endpoint que registra aperturas remotas DTMF y opcionalmente las dispara via AMI Originate
ignore_user_abort(true); set_time_limit(15);
header('Content-Type: application/json');

require __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['tf_user']) && !isset($_SESSION['agent_user'])) {
    http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit;
}

$action = $_GET['action'] ?? '';
$ext    = preg_replace('/\D/', '', $_POST['ext'] ?? $_GET['ext'] ?? '');
$dtmf   = preg_replace('/[^*#0-9]/', '', $_POST['dtmf'] ?? $_GET['dtmf'] ?? '');

if (!$ext) { echo json_encode(['ok'=>false,'error'=>'ext_required']); exit; }

function tflog($m) { @file_put_contents('/tmp/teleflow_door.log', '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Crear tabla on-the-fly si no existe (idempotente)
    try {
        $tf->exec("CREATE TABLE IF NOT EXISTS door_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ext VARCHAR(8) NOT NULL,
            dtmf VARCHAR(8) NOT NULL,
            actor_user VARCHAR(80) NULL,
            actor_kind VARCHAR(20) NULL,
            occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ext (ext),
            INDEX idx_at (occurred_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $_) {}
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>'db_init_fail']); exit;
}

if ($action === 'log') {
    if (!$dtmf) $dtmf = '*9';
    $actor_user = $_SESSION['tf_user']['username'] ?? $_SESSION['agent_user']['name'] ?? 'unknown';
    $actor_kind = isset($_SESSION['tf_user']) ? 'admin' : 'agent';
    try {
        $tf->prepare("INSERT INTO door_events (ext, dtmf, actor_user, actor_kind) VALUES (?, ?, ?, ?)")
           ->execute([$ext, $dtmf, $actor_user, $actor_kind]);
        tflog("log ext=$ext dtmf=$dtmf actor=$actor_user kind=$actor_kind");
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>'db_insert: '.$e->getMessage()]); exit;
    }
    echo json_encode(['ok'=>true, 'status'=>'ok', 'ext'=>$ext, 'dtmf'=>$dtmf, 'actor'=>$actor_user]);
    exit;
}

if ($action === 'list') {
    $limit = (int)($_GET['limit'] ?? 50);
    if ($limit < 1) $limit = 50;
    if ($limit > 500) $limit = 500;
    $st = $tf->prepare("SELECT ext, dtmf, actor_user, actor_kind, occurred_at FROM door_events WHERE ext = ? ORDER BY occurred_at DESC LIMIT $limit");
    $st->execute([$ext]);
    echo json_encode(['ok'=>true, 'events'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
