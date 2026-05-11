<?php
/**
 * TeleFlow — app_settings (branding + softphone defaults)
 *
 * GET  /api/app_settings.php          → { settings: { key: value, ... } }
 * POST /api/app_settings.php          → body: { key1: value1, key2: value2, ... }
 *                                       Upsert masivo.
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'No autorizado']); exit;
}
@session_write_close();
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    if ($method === 'GET') {
        $rows = $tf->query("SELECT `key`, value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['status'=>'ok', 'settings' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        // Aceptar JSON body o form-encoded
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) $body = $_POST;

        if (empty($body)) { echo json_encode(['status'=>'error','message'=>'sin payload']); exit; }

        $stmt = $tf->prepare("INSERT INTO app_settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
        $saved = [];
        foreach ($body as $k => $v) {
            // Whitelist de keys permitidos — solo brand_* y softphone_*
            if (!preg_match('/^(brand_|softphone_)[a-z_]+$/', $k)) continue;
            // Sanitizar value
            $v = is_scalar($v) ? (string)$v : json_encode($v);
            if (strlen($v) > 2000) $v = substr($v, 0, 2000);
            $stmt->execute([$k, $v]);
            $saved[] = $k;
        }
        echo json_encode(['status'=>'ok', 'saved' => $saved, 'count' => count($saved)]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'método no soportado']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
