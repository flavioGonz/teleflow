<?php
/**
 * api/floor_map.php
 *
 * Backend para el mapa del callcenter (Dashboard) — soporta markers de
 * extensiones SIP y de colas (queues).
 *
 * Schema:
 *  - floor_map_marker (kind ENUM('ext','queue'), id VARCHAR(16), x_pct, y_pct)
 *    PK = (kind, id) para que la misma id (e.g. '1000') pueda ser ext y cola.
 *  - floor_map_config (k VARCHAR(64) PRIMARY KEY, v TEXT)
 *  - Imágenes en /var/www/teleflow/uploads/floor_maps/
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']); exit;
}

function tf_db(): PDO {
    global $DB_HOST, $DB_USER, $DB_PASS;
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8", $DB_USER, $DB_PASS,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Crear tabla si no existe, con la versión nueva
    $pdo->exec("CREATE TABLE IF NOT EXISTS floor_map_marker (
        ext VARCHAR(8) NOT NULL PRIMARY KEY,
        x_pct DECIMAL(5,2) NOT NULL,
        y_pct DECIMAL(5,2) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    $pdo->exec("CREATE TABLE IF NOT EXISTS floor_map_config (
        k VARCHAR(64) NOT NULL PRIMARY KEY,
        v TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

    // Migración a nuevo schema (kind + id), idempotente
    try { $pdo->exec("ALTER TABLE floor_map_marker ADD COLUMN kind ENUM('ext','queue') NOT NULL DEFAULT 'ext'"); } catch (Exception $_) {}
    try { $pdo->exec("ALTER TABLE floor_map_marker ADD COLUMN id VARCHAR(16) NULL"); } catch (Exception $_) {}
    // Backfill: copiar ext → id donde id IS NULL
    try { $pdo->exec("UPDATE floor_map_marker SET id = ext WHERE id IS NULL"); } catch (Exception $_) {}
    // Cambiar PK a (kind, id) si todavía está en ext
    $hasNewPk = false;
    try {
        $r = $pdo->query("SHOW KEYS FROM floor_map_marker WHERE Key_name='PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_map(fn($x) => $x['Column_name'], $r);
        $hasNewPk = in_array('kind', $cols) && in_array('id', $cols);
    } catch (Exception $_) {}
    if (!$hasNewPk) {
        try {
            $pdo->exec("ALTER TABLE floor_map_marker DROP PRIMARY KEY");
            $pdo->exec("ALTER TABLE floor_map_marker MODIFY COLUMN id VARCHAR(16) NOT NULL");
            $pdo->exec("ALTER TABLE floor_map_marker ADD PRIMARY KEY (kind, id)");
        } catch (Exception $_) {}
    }

    return $pdo;
}

function tf_cfg_get(PDO $db, string $k, ?string $default = null): ?string {
    $st = $db->prepare("SELECT v FROM floor_map_config WHERE k=?");
    $st->execute([$k]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['v'] : $default;
}
function tf_cfg_set(PDO $db, string $k, string $v): void {
    $db->prepare("INSERT INTO floor_map_config (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")
       ->execute([$k, $v]);
}

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    try {
        $db = tf_db();
        $imageUrl = tf_cfg_get($db, 'image_url') ?: '';
        // Devolver tanto kind/id como ext (alias retro-compat para clientes que aún esperan 'ext')
        $rows = $db->query("SELECT kind, id, COALESCE(id, ext) AS ext, x_pct, y_pct FROM floor_map_marker ORDER BY kind, id")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'image_url' => $imageUrl, 'markers' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'upload_image') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'no file uploaded (or upload error)']);
        exit;
    }
    $f = $_FILES['image'];
    if ($f['size'] > 4 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'image too large (max 4MB)']);
        exit;
    }
    $mime = function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'error' => "tipo no permitido ($mime). Acepta jpg/png/webp"]);
        exit;
    }
    $dir = __DIR__ . '/../uploads/floor_maps';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = 'plan-' . date('YmdHis') . '.' . $allowed[$mime];
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => 'cannot save file']);
        exit;
    }
    @chmod($target, 0644);
    $publicUrl = 'uploads/floor_maps/' . $name;
    try {
        $db = tf_db();
        tf_cfg_set($db, 'image_url', $publicUrl);
        echo json_encode(['success' => true, 'image_url' => $publicUrl]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'set_marker') {
    $kind = ($_POST['kind'] ?? 'ext') === 'queue' ? 'queue' : 'ext';
    $rawId = $_POST['id'] ?? ($_POST['ext'] ?? '');  // retro-compat con clientes que mandan 'ext'
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$rawId);
    $x = isset($_POST['x_pct']) ? (float)$_POST['x_pct'] : -1;
    $y = isset($_POST['y_pct']) ? (float)$_POST['y_pct'] : -1;
    if (!$id || $x < 0 || $x > 100 || $y < 0 || $y > 100) {
        echo json_encode(['success' => false, 'error' => 'inválido (id / coords 0-100)']); exit;
    }
    try {
        $db = tf_db();
        $db->prepare("INSERT INTO floor_map_marker (kind, id, ext, x_pct, y_pct) VALUES (?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE x_pct=VALUES(x_pct), y_pct=VALUES(y_pct), ext=VALUES(ext)")
           ->execute([$kind, $id, $id, $x, $y]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_marker') {
    $kind = ($_POST['kind'] ?? 'ext') === 'queue' ? 'queue' : 'ext';
    $rawId = $_POST['id'] ?? ($_POST['ext'] ?? '');
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$rawId);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'id faltante']); exit; }
    try {
        $db = tf_db();
        $st = $db->prepare("DELETE FROM floor_map_marker WHERE kind=? AND id=?");
        $st->execute([$kind, $id]);
        echo json_encode(['success' => true, 'deleted' => $st->rowCount()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown action']);
