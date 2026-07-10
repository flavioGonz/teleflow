<?php
// TeleFlow — CRUD de agentes (call_center.agent en PBX 10.1.1.7)
// F5.3: session + JSON + auth admin via _bootstrap.
ignore_user_abort(true); set_time_limit(15);
require __DIR__ . '/../config.php';
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);

function db_cc() {
    global $PBX_DB_HOST, $PBX_DB_USER, $PBX_DB_PASS;
    return new PDO("mysql:host=$PBX_DB_HOST;dbname=call_center;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

$action = $_GET['action'] ?? '';
try {
    $cc = db_cc();
    if ($action === 'list') {
        $rows = $cc->query("SELECT id, type, number, name, password, estatus FROM agent ORDER BY number ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok'=>true, 'agents'=>$rows]);
        exit;
    }
    if ($action === 'create') {
        $type   = trim($_POST['type'] ?? 'agent');
        $number = preg_replace('/\D/', '', $_POST['number'] ?? '');
        $name   = trim($_POST['name'] ?? '');
        $pass   = preg_replace('/\D/', '', $_POST['password'] ?? '');
        if (!$number || !$name || !$pass) { echo json_encode(['ok'=>false,'error'=>'fields_required']); exit; }
        // unique number
        $st = $cc->prepare("SELECT id FROM agent WHERE number = ?");
        $st->execute([$number]);
        if ($st->fetch()) { echo json_encode(['ok'=>false,'error'=>'number_exists']); exit; }
        $cc->prepare("INSERT INTO agent (type, number, name, password, estatus) VALUES (?, ?, ?, ?, 'A')")
           ->execute([$type, $number, $name, $pass]);
        echo json_encode(['ok'=>true, 'id'=>$cc->lastInsertId()]);
        exit;
    }
    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $pass   = preg_replace('/\D/', '', $_POST['password'] ?? '');
        $estatus = $_POST['estatus'] ?? null; // 'A'/'I'
        if (!$id) { echo json_encode(['ok'=>false,'error'=>'id_required']); exit; }
        $sets = []; $vals = [];
        if ($name)   { $sets[] = "name = ?";     $vals[] = $name; }
        if ($pass)   { $sets[] = "password = ?"; $vals[] = $pass; }
        if ($estatus) { $sets[] = "estatus = ?"; $vals[] = ($estatus === 'A' ? 'A' : 'I'); }
        if (!$sets) { echo json_encode(['ok'=>false,'error'=>'nothing_to_update']); exit; }
        $vals[] = $id;
        $cc->prepare("UPDATE agent SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if ($action === 'delete') {
        // Soft delete: marca estatus = 'I' para no romper FK de históricos
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['ok'=>false,'error'=>'id_required']); exit; }
        $cc->prepare("UPDATE agent SET estatus='I' WHERE id = ?")->execute([$id]);
        echo json_encode(['ok'=>true, 'mode'=>'soft_delete']);
        exit;
    }
    if ($action === 'hard_delete') {
        // Hard delete: borra row sólo si no tiene históricos referenciados (chequeo opcional)
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['ok'=>false,'error'=>'id_required']); exit; }
        $cc->prepare("DELETE FROM agent WHERE id = ?")->execute([$id]);
        echo json_encode(['ok'=>true, 'mode'=>'hard_delete']);
        exit;
    }
    echo json_encode(['ok'=>false,'error'=>'unknown_action']);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
