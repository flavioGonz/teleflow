<?php
// TeleFlow — endpoint para aperturas remotas DTMF + auto-snapshot RTSP
// F5.3: session + JSON + auth any (admin OR agent) via _bootstrap.
ignore_user_abort(true); set_time_limit(15);
require __DIR__ . '/../config.php';
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'any']);

$action = $_GET['action'] ?? '';
$ext    = preg_replace('/\D/', '', $_POST['ext'] ?? $_GET['ext'] ?? '');
$dtmf   = preg_replace('/[^*#0-9]/', '', $_POST['dtmf'] ?? $_GET['dtmf'] ?? '');

if (!$ext) { echo json_encode(['ok'=>false,'error'=>'ext_required']); exit; }

function tflog($m) { @file_put_contents('/tmp/teleflow_door.log', '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    try {
        $tf->exec("CREATE TABLE IF NOT EXISTS door_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ext VARCHAR(8) NOT NULL,
            dtmf VARCHAR(8) NOT NULL,
            actor_user VARCHAR(80) NULL,
            actor_kind VARCHAR(20) NULL,
            snapshot VARCHAR(255) NULL,
            occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ext (ext),
            INDEX idx_at (occurred_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $tf->exec("ALTER TABLE door_events ADD COLUMN snapshot VARCHAR(255) NULL"); } catch (Exception $_) {}
    } catch (Exception $_) {}
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>'db_init_fail']); exit;
}

if ($action === 'log') {
    if (!$dtmf) $dtmf = '*9';
    $actor_user = $_SESSION['tf_user']['username'] ?? $_SESSION['agent_user']['agent_name'] ?? 'unknown';
    $actor_kind = isset($_SESSION['tf_user']) ? 'admin' : 'agent';

    // ─── Auto-snapshot RTSP en el momento de la apertura ───
    $snapshot_filename = null;
    try {
        $ch = curl_init('http://127.0.0.1/api/rtsp_snapshot.php?action=capture');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['ext' => $ext]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        if (!empty($_COOKIE['PHPSESSID'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $_COOKIE['PHPSESSID']);
        }
        $resp = curl_exec($ch);
        curl_close($ch);
        if ($resp) {
            $j = json_decode($resp, true);
            if (!empty($j['filename'])) $snapshot_filename = $j['filename'];
        }
    } catch (Exception $e) { tflog('snapshot fail: ' . $e->getMessage()); }

    try {
        $tf->prepare("INSERT INTO door_events (ext, dtmf, actor_user, actor_kind, snapshot) VALUES (?, ?, ?, ?, ?)")
           ->execute([$ext, $dtmf, $actor_user, $actor_kind, $snapshot_filename]);
        tflog("log ext=$ext dtmf=$dtmf actor=$actor_user kind=$actor_kind snap=" . ($snapshot_filename ?: '(none)'));
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>'db_insert: '.$e->getMessage()]); exit;
    }
    echo json_encode([
        'ok' => true, 'status' => 'ok',
        'ext' => $ext, 'dtmf' => $dtmf,
        'actor' => $actor_user,
        'snapshot' => $snapshot_filename,
    ]);
    exit;
}

if ($action === 'list') {
    $limit = (int)($_GET['limit'] ?? 50);
    if ($limit < 1) $limit = 50;
    if ($limit > 500) $limit = 500;
    $st = $tf->prepare("SELECT ext, dtmf, actor_user, actor_kind, snapshot, occurred_at FROM door_events WHERE ext = ? ORDER BY occurred_at DESC LIMIT $limit");
    $st->execute([$ext]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    // Enriquecer con URL completa del snapshot
    foreach ($rows as &$r) {
        if (!empty($r['snapshot'])) {
            $r['snapshot_url'] = 'api/rtsp_snapshot.php?action=image&ext=' . urlencode($ext) . '&file=' . urlencode($r['snapshot']);
        }
    }
    echo json_encode(['ok'=>true, 'events'=>$rows]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown_action']);
