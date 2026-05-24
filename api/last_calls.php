<?php
/**
 * api/last_calls.php
 *
 * Devuelve el timestamp de la última llamada CONTESTADA para cada extensión
 * y para cada cola, para alimentar el badge "hace Xm/Xh" en el FloorMap.
 *
 * Action:
 *   ?action=summary
 *     → {success:true, exts:{ '1000':1779480000, ... }, queues:{ 'sales':1779479000, ... }, generated_at:1779480100, ttl:30}
 *
 * Cache 30s en file system (/tmp/tf_last_calls.json) para no martillar
 * el MySQL del PBX en cada hover/segundo. El frontend recalcula "hace X" client-side
 * con el delta de `generated_at`.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']); exit;
}

const CACHE_FILE = '/tmp/tf_last_calls.json';
const CACHE_TTL  = 30;

function read_cache(): ?array {
    if (!file_exists(CACHE_FILE)) return null;
    $age = time() - filemtime(CACHE_FILE);
    if ($age > CACHE_TTL) return null;
    $data = @json_decode(@file_get_contents(CACHE_FILE), true);
    return is_array($data) ? $data : null;
}

function write_cache(array $data): void {
    @file_put_contents(CACHE_FILE, json_encode($data), LOCK_EX);
    @chmod(CACHE_FILE, 0664);
}

function compute_last_calls(): array {
    global $DB_HOST, $DB_USER, $DB_PASS, $CDR_DB_NAME, $PBX_DB_NAME, $TF_DB_NAME;
    // IMPORTANTE: devolvemos sec_ago (delta) calculado DENTRO de MySQL con TIMESTAMPDIFF,
    // no epochs. Esto evita drift entre MySQL/PHP/browser y resuelve el bug de timers
    // negativos visto en producción (MySQL del PBX tenía offset de TZ respecto al clock real).
    $exts = []; $queues = [];

    try {
        $cdr = new PDO("mysql:host=$DB_HOST;dbname=$CDR_DB_NAME;charset=utf8", $DB_USER, $DB_PASS,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = "SELECT ext, MIN(sec_ago) AS sec_ago FROM (
                    SELECT src AS ext, TIMESTAMPDIFF(SECOND, calldate, NOW()) AS sec_ago
                    FROM cdr
                    WHERE disposition='ANSWERED'
                      AND calldate > DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND src REGEXP '^[0-9]{3,7}$'
                    UNION ALL
                    SELECT dst AS ext, TIMESTAMPDIFF(SECOND, calldate, NOW()) AS sec_ago
                    FROM cdr
                    WHERE disposition='ANSWERED'
                      AND calldate > DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND dst REGEXP '^[0-9]{3,7}$'
                ) u
                GROUP BY ext";
        foreach ($cdr->query($sql) as $row) {
            $sec = (int)$row['sec_ago'];
            $exts[$row['ext']] = $sec < 0 ? 0 : $sec;
        }
    } catch (Exception $e) { /* silencio */ }

    try {
        $cc = new PDO("mysql:host=$DB_HOST;dbname=asteriskcdrdb;charset=utf8", $DB_USER, $DB_PASS,
                      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = "SELECT queuename, MIN(TIMESTAMPDIFF(SECOND, time, NOW())) AS sec_ago
                FROM queue_log
                WHERE event IN ('COMPLETEAGENT','COMPLETECALLER')
                  AND time > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY queuename";
        foreach ($cc->query($sql) as $row) {
            $sec = (int)$row['sec_ago'];
            $queues[$row['queuename']] = $sec < 0 ? 0 : $sec;
        }
    } catch (Exception $e) { /* ignore */ }

    return [
        'success'      => true,
        'generated_at' => time(),
        'ttl'          => CACHE_TTL,
        'exts'         => $exts,        // ahora son sec_ago (segundos desde la última llamada)
        'queues'       => $queues,      // idem
        'format'       => 'sec_ago',    // discriminador para clientes nuevos
    ];
}

$action = $_GET['action'] ?? '';

if ($action === 'summary') {
    $cached = read_cache();
    if ($cached) {
        $cached['from_cache'] = true;
        echo json_encode($cached);
        exit;
    }
    $data = compute_last_calls();
    write_cache($data);
    $data['from_cache'] = false;
    echo json_encode($data);
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown action']);
