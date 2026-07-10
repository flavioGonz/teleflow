<?php
/**
 * api/users.php — gestión de usuarios admin + agentes
 *
 * Endpoints:
 *   GET  ?action=list                    — lista admins + agentes
 *   POST ?action=create_admin            — name, password
 *   POST ?action=update_admin            — name, [new_password]
 *   POST ?action=delete_admin            — name
 *   POST ?action=set_agent_password      — number, password (en call_center.agent)
 *
 * Storage admin: SQLite /var/www/db/acl.db, tabla acl_user (name TEXT PK, md5_password TEXT)
 * Storage agentes: MySQL call_center.agent (number, name, password)
 *
 * HORIZON · Teleflow
 */
require_once __DIR__ . '/../config.php';
// F5.4: session + JSON + auth admin via _bootstrap.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$acl_path = isset($ACL_DB_PATH) ? $ACL_DB_PATH : '/var/www/db/acl.db';

function acl_db() {
    global $acl_path;
    return new SQLite3($acl_path);
}

function mysql_cc() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=call_center;charset=utf8", $DB_USER, $DB_PASS);
}

// ─── action=list ────────────────────────────────────────────────
if ($action === 'list') {
    $admins = [];
    $agents = [];
    try {
        $db = acl_db();
        $rs = $db->query("SELECT name FROM acl_user ORDER BY name");
        while ($row = $rs->fetchArray(SQLITE3_ASSOC)) {
            $admins[] = ['name' => $row['name']];
        }
    } catch (Exception $e) {}
    try {
        $db = mysql_cc();
        $st = $db->query("SELECT id, number, name, IFNULL(estatus,'A') AS status FROM agent ORDER BY number");
        $agents = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    echo json_encode([
        'status'=>'ok',
        'admins' => $admins,
        'agents' => $agents,
        'me' => $_SESSION['tf_user']
    ]);
    exit;
}

// ─── action=create_admin ────────────────────────────────────────
if ($action === 'create_admin') {
    $name = trim($_POST['name'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9._-]{2,32}$/', $name) || strlen($pass) < 4) {
        echo json_encode(['status'=>'error','message'=>'name 2-32 alfanumérico, password >=4']); exit;
    }
    try {
        $db = acl_db();
        $st = $db->prepare('INSERT INTO acl_user (name, md5_password) VALUES (:n, :p)');
        $st->bindValue(':n', $name);
        $st->bindValue(':p', md5($pass));
        $st->execute();
        echo json_encode(['status'=>'ok','name'=>$name]);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

// ─── action=update_admin ────────────────────────────────────────
if ($action === 'update_admin') {
    $name = trim($_POST['name'] ?? '');
    $pass = $_POST['new_password'] ?? '';
    if (!$name) { echo json_encode(['status'=>'error','message'=>'name requerido']); exit; }
    if (strlen($pass) < 4) { echo json_encode(['status'=>'error','message'=>'password >=4']); exit; }
    try {
        $db = acl_db();
        $st = $db->prepare('UPDATE acl_user SET md5_password=:p WHERE name=:n');
        $st->bindValue(':n', $name);
        $st->bindValue(':p', md5($pass));
        $st->execute();
        echo json_encode(['status'=>'ok']);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

// ─── action=delete_admin ────────────────────────────────────────
if ($action === 'delete_admin') {
    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['status'=>'error','message'=>'name requerido']); exit; }
    if ($name === $_SESSION['tf_user']) {
        echo json_encode(['status'=>'error','message'=>'No podés eliminar tu propio usuario logueado']); exit;
    }
    try {
        $db = acl_db();
        $st = $db->prepare('DELETE FROM acl_user WHERE name=:n');
        $st->bindValue(':n', $name);
        $st->execute();
        echo json_encode(['status'=>'ok']);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

// ─── action=set_agent_password ──────────────────────────────────
if ($action === 'set_agent_password') {
    $number = preg_replace('/\D/', '', $_POST['number'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!$number) { echo json_encode(['status'=>'error','message'=>'number requerido']); exit; }
    if (strlen($pass) < 4) { echo json_encode(['status'=>'error','message'=>'password >=4']); exit; }
    try {
        $db = mysql_cc();
        $st = $db->prepare("UPDATE agent SET password=? WHERE number=?");
        $st->execute([$pass, $number]);
        echo json_encode(['status'=>'ok','rows_affected'=>$st->rowCount()]);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['status'=>'error','message'=>'Acción desconocida']);
