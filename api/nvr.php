<?php
/**
 * api/nvr.php — CRUD NVR + canales + discos (lectura)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_ops_db.php';
ops_auth_or_403();

$action = $_GET['action'] ?? '';
$db = ops_db();

if ($action === 'list') {
    $rows = $db->query("SELECT n.*, c.name AS client_name,
        (SELECT COUNT(*) FROM nvr_channels WHERE nvr_id=n.id) AS n_ch,
        (SELECT COUNT(*) FROM nvr_disks    WHERE nvr_id=n.id) AS n_disks,
        (SELECT COUNT(*) FROM nvr_disks    WHERE nvr_id=n.id AND health='fail') AS n_fail
        FROM nvr_devices n LEFT JOIN clients c ON c.id=n.client_id
        ORDER BY n.name")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'nvrs'=>$rows]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    $st = $db->prepare("SELECT * FROM nvr_devices WHERE id=?"); $st->execute([$id]);
    $nvr = $st->fetch(PDO::FETCH_ASSOC);
    if (!$nvr) { echo json_encode(['success'=>false,'error'=>'no existe']); exit; }
    $ch = $db->prepare("SELECT * FROM nvr_channels WHERE nvr_id=? ORDER BY channel_no");
    $ch->execute([$id]);
    $dk = $db->prepare("SELECT * FROM nvr_disks WHERE nvr_id=? ORDER BY disk_no");
    $dk->execute([$id]);
    echo json_encode([
        'success'  => true,
        'nvr'      => $nvr,
        'channels' => $ch->fetchAll(PDO::FETCH_ASSOC),
        'disks'    => $dk->fetchAll(PDO::FETCH_ASSOC),
    ]);
    exit;
}

if ($action === 'save') {
    $id              = (int)($_POST['id'] ?? 0);
    $name            = trim($_POST['name'] ?? '');
    $model           = trim($_POST['model'] ?? '');
    $ip              = trim($_POST['ip'] ?? '');
    $port_http       = (int)($_POST['port_http'] ?? 80);
    $port_rtsp       = (int)($_POST['port_rtsp'] ?? 554);
    $channels_total  = (int)($_POST['channels_total'] ?? 0);
    $username        = trim($_POST['username'] ?? '');
    $password        = trim($_POST['password'] ?? '');
    $snmp_community  = trim($_POST['snmp_community'] ?? 'public');
    $snmp_version    = in_array($_POST['snmp_version'] ?? '', ['1','2c','3']) ? $_POST['snmp_version'] : '2c';
    $client_id       = (int)($_POST['client_id'] ?? 0) ?: null;
    $notes           = trim($_POST['notes'] ?? '');
    if (!$name || !$ip) { echo json_encode(['success'=>false,'error'=>'name e ip requeridos']); exit; }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) { echo json_encode(['success'=>false,'error'=>'IP inválida']); exit; }
    try {
        if ($id) {
            $st = $db->prepare("UPDATE nvr_devices SET name=?, model=?, ip=?, port_http=?, port_rtsp=?, channels_total=?, username=?, password=?, snmp_community=?, snmp_version=?, client_id=?, notes=? WHERE id=?");
            $st->execute([$name, $model ?: null, $ip, $port_http, $port_rtsp, $channels_total, $username ?: null, $password ?: null, $snmp_community, $snmp_version, $client_id, $notes ?: null, $id]);
        } else {
            $st = $db->prepare("INSERT INTO nvr_devices (name, model, ip, port_http, port_rtsp, channels_total, username, password, snmp_community, snmp_version, client_id, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([$name, $model ?: null, $ip, $port_http, $port_rtsp, $channels_total, $username ?: null, $password ?: null, $snmp_community, $snmp_version, $client_id, $notes ?: null]);
            $id = (int)$db->lastInsertId();
        }
        echo json_encode(['success'=>true, 'id'=>$id]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    try {
        $db->prepare("DELETE FROM nvr_devices WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'channel_save') {
    $nvr_id     = (int)($_POST['nvr_id'] ?? 0);
    $channel_no = (int)($_POST['channel_no'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $ext        = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    $rtsp_url   = trim($_POST['rtsp_url'] ?? '');
    $recording  = (int)($_POST['recording'] ?? 1);
    if (!$nvr_id || !$channel_no) { echo json_encode(['success'=>false,'error'=>'nvr_id y channel_no requeridos']); exit; }
    try {
        $db->prepare("INSERT INTO nvr_channels (nvr_id, channel_no, name, ext, rtsp_url, recording) VALUES (?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE name=VALUES(name), ext=VALUES(ext), rtsp_url=VALUES(rtsp_url), recording=VALUES(recording)")
           ->execute([$nvr_id, $channel_no, $name ?: null, $ext ?: null, $rtsp_url ?: null, $recording]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'channel_del') {
    $nvr_id     = (int)($_POST['nvr_id'] ?? 0);
    $channel_no = (int)($_POST['channel_no'] ?? 0);
    if (!$nvr_id || !$channel_no) { echo json_encode(['success'=>false,'error'=>'inválido']); exit; }
    try {
        $db->prepare("DELETE FROM nvr_channels WHERE nvr_id=? AND channel_no=?")->execute([$nvr_id, $channel_no]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

echo json_encode(['success'=>false, 'error'=>'unknown action']);
