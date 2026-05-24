<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_ops_db.php';
ops_auth_or_403();

$action = $_GET['action'] ?? '';
$db = ops_db();

if ($action === 'list') {
    // Devuelve clientes con conteos por kind
    $rows = $db->query("SELECT c.*,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='ext')    AS n_ext,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='paging') AS n_paging,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='nvr')    AS n_nvr,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='queue')  AS n_queue
        FROM clients c ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'clients'=>$rows]); exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    $c = $db->prepare("SELECT * FROM clients WHERE id=?"); $c->execute([$id]);
    $client = $c->fetch(PDO::FETCH_ASSOC);
    if (!$client) { echo json_encode(['success'=>false,'error'=>'no existe']); exit; }
    $assoc = $db->prepare("SELECT kind, ref_id FROM client_assoc WHERE client_id=? ORDER BY kind, ref_id");
    $assoc->execute([$id]);
    $byKind = ['ext'=>[], 'paging'=>[], 'nvr'=>[], 'queue'=>[]];
    foreach ($assoc->fetchAll(PDO::FETCH_ASSOC) as $r) $byKind[$r['kind']][] = $r['ref_id'];
    $client['assoc'] = $byKind;
    echo json_encode(['success'=>true, 'client'=>$client]); exit;
}

if ($action === 'save') {
    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'nombre requerido']); exit; }
    try {
        if ($id) {
            $st = $db->prepare("UPDATE clients SET name=?, address=?, contact=?, notes=? WHERE id=?");
            $st->execute([$name, $address ?: null, $contact ?: null, $notes ?: null, $id]);
        } else {
            $st = $db->prepare("INSERT INTO clients (name, address, contact, notes) VALUES (?,?,?,?)");
            $st->execute([$name, $address ?: null, $contact ?: null, $notes ?: null]);
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
        $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'assoc_set') {
    // Reemplaza TODAS las asociaciones de un kind para un cliente
    $id   = (int)($_POST['id'] ?? 0);
    $kind = $_POST['kind'] ?? '';
    if (!$id || !in_array($kind, ['ext','paging','nvr','queue'])) {
        echo json_encode(['success'=>false,'error'=>'inválido']); exit;
    }
    $refs = json_decode($_POST['refs'] ?? '[]', true);
    if (!is_array($refs)) $refs = [];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM client_assoc WHERE client_id=? AND kind=?")->execute([$id, $kind]);
        if ($refs) {
            $ins = $db->prepare("INSERT IGNORE INTO client_assoc (client_id, kind, ref_id) VALUES (?,?,?)");
            foreach ($refs as $r) {
                $r = trim((string)$r);
                if ($r !== '') $ins->execute([$id, $kind, $r]);
            }
        }
        $db->commit();
        echo json_encode(['success'=>true, 'count'=>count($refs)]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false, 'error'=>'unknown action']);
