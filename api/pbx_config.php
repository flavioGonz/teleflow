
<?php
/**
 * TeleFlow — API Configuración PBX
 * GET  ?action=list       → lista settings agrupados
 * POST ?action=save       → guarda cambios (body: {settings: [{key, value}, ...]})
 * POST ?action=test       → prueba MySQL + AMI con creds actuales
 */
// F5.3: session + JSON + auth admin via _bootstrap.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

function tf_db() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

$action = $_GET['action'] ?? 'list';

try {
    $db = tf_db();

    if ($action === 'list') {
        $rows = $db->query('SELECT config_key, config_value, description, config_group, is_secret, updated_at FROM pbx_config ORDER BY config_group, config_key')->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $r) {
            $g = $r['config_group'];
            if (!isset($grouped[$g])) $grouped[$g] = [];
            // ofuscar secrets (mostrar solo asteriscos a menos que se pida explicit)
            if ($r['is_secret']) {
                $r['_secret'] = true;
                $r['config_value'] = $r['config_value'] !== '' ? str_repeat('•', 8) : '';
            }
            $grouped[$g][] = $r;
        }
        echo json_encode(['status' => 'ok', 'groups' => $grouped]);
        exit;
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['settings']) || !is_array($body['settings'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Body inválido']);
            exit;
        }
        $stmt = $db->prepare('UPDATE pbx_config SET config_value = ? WHERE config_key = ?');
        $count = 0;
        foreach ($body['settings'] as $s) {
            if (!isset($s['key'])) continue;
            // Si vino como secreto ofuscado, no actualizar
            // HORIZON FIX: si viene SÓLO bullets U+2022 (placeholder ofuscado), no sobrescribir
            if (isset($s['value']) && $s['value'] !== '' && preg_match('/^[\xe2][\x80][\xa2]+$/', $s['value'])) continue;
            $stmt->execute([$s['value'] ?? '', $s['key']]);
            if ($stmt->rowCount() > 0) $count++;
        }
        echo json_encode(['status' => 'ok', 'updated' => $count]);
        exit;
    }

    if ($action === 'test') {
        $cfg = [];
        foreach ($db->query('SELECT config_key, config_value FROM pbx_config') as $r) {
            $cfg[$r['config_key']] = $r['config_value'];
        }
        $result = ['status' => 'ok', 'tests' => []];

        // Test MySQL
        try {
            $pdo = new PDO(
                "mysql:host={$cfg['MYSQL_HOST']};port={$cfg['MYSQL_PORT']};dbname={$cfg['MYSQL_DB_PBX']};charset=utf8mb4",
                $cfg['MYSQL_USER'], $cfg['MYSQL_PASS'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 4]
            );
            $row = $pdo->query("SELECT VERSION() AS v, COUNT(*) AS exts FROM devices")->fetch();
            $result['tests']['mysql'] = ['ok' => true, 'detail' => "MySQL {$row['v']}, {$row['exts']} extensiones en BD"];
        } catch (Exception $e) {
            $result['tests']['mysql'] = ['ok' => false, 'detail' => $e->getMessage()];
        }

        // Test AMI
        try {
            $sock = @fsockopen($cfg['AMI_HOST'], (int)$cfg['AMI_PORT'], $errno, $errstr, 3);
            if (!$sock) throw new Exception("AMI socket: $errstr ($errno)");
            stream_set_timeout($sock, 3);
            $greet = fgets($sock);
            fwrite($sock, "Action: Login\r\nUsername: {$cfg['AMI_USER']}\r\nSecret: {$cfg['AMI_PASS']}\r\nEvents: off\r\n\r\n");
            $resp = '';
            $start = microtime(true);
            while (microtime(true) - $start < 2) {
                $line = fgets($sock);
                if ($line === false) break;
                $resp .= $line;
                if (strpos($resp, 'Authentication accepted') !== false) break;
                if (strpos($resp, 'Authentication failed') !== false) break;
                if (trim($line) === '' && strpos($resp, 'Response:') !== false) break;
            }
            fwrite($sock, "Action: Logoff\r\n\r\n");
            fclose($sock);
            $ok = strpos($resp, 'Authentication accepted') !== false;
            $result['tests']['ami'] = [
                'ok' => $ok,
                'detail' => trim($greet) . ' — ' . ($ok ? 'Login OK' : 'Login FAIL'),
            ];
        } catch (Exception $e) {
            $result['tests']['ami'] = ['ok' => false, 'detail' => $e->getMessage()];
        }

        echo json_encode($result);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Acción desconocida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
