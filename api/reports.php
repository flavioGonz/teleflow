
<?php
/**
 * TeleFlow — API Reportes Avanzados
 * GET ?action=kpis&from=YYYY-MM-DD&to=YYYY-MM-DD          → KPIs globales
 * GET ?action=by_agent&from=...&to=...                    → Performance por agente
 * GET ?action=by_queue&from=...&to=...                    → Performance por cola
 * GET ?action=daily&from=...&to=...                       → Trend diario
 * GET ?action=hourly_heatmap&from=...&to=...              → Heatmap horario
 * GET ?action=disposition_breakdown&from=...&to=...       → Distribución de cierres
 * GET ?action=calls&from=...&to=...&limit=100             → Drill-down de llamadas
 * GET ?action=export_csv&type=calls&...                   → Export CSV
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
@session_write_close();

require_once __DIR__ . '/../config.php';

// HORIZON: cache de reportes 60s — datos no cambian segundos a segundo
$rp_cache_key = '/tmp/teleflow_rp_' . md5($_SERVER['QUERY_STRING'] ?? '') . '.json';
if (file_exists($rp_cache_key) && (time() - filemtime($rp_cache_key)) < 60) {
    header('X-TF-RP-Cache: HIT');
    readfile($rp_cache_key);
    exit;
}
ob_start();
register_shutdown_function(function() use ($rp_cache_key) {
    $out = ob_get_contents();
    ob_end_flush();
    if ($out && strlen($out) < 5*1024*1024) {
        @file_put_contents($rp_cache_key, $out, LOCK_EX);
        @chmod($rp_cache_key, 0666);
    }
});

function pbx_db() {
    global $PBX_DB_HOST, $PBX_DB_USER, $PBX_DB_PASS;
    return new PDO("mysql:host=$PBX_DB_HOST;dbname=asteriskcdrdb;charset=utf8mb4",
        $PBX_DB_USER, $PBX_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
}

function tf_db() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function dt_range() {
    $today = date('Y-m-d');
    $from = $_GET['from'] ?? $today;
    $to = $_GET['to'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
    return [$from . ' 00:00:00', $to . ' 23:59:59'];
}

$action = $_GET['action'] ?? 'kpis';

try {
    [$from, $to] = dt_range();
    $cdr = pbx_db();

    if ($action === 'kpis') {
        $row = $cdr->prepare("
            SELECT
              COUNT(*) AS total,
              SUM(disposition='ANSWERED') AS answered,
              SUM(disposition='NO ANSWER') AS no_answer,
              SUM(disposition='BUSY') AS busy,
              SUM(disposition='FAILED') AS failed,
              ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_billsec,
              ROUND(AVG(IF(disposition='ANSWERED', duration-billsec, NULL)),0) AS avg_wait,
              MAX(billsec) AS max_billsec,
              SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk_seconds
            FROM cdr
            WHERE calldate BETWEEN ? AND ?
        ");
        $row->execute([$from, $to]);
        $k = $row->fetch(PDO::FETCH_ASSOC);
        $k['answer_rate'] = $k['total'] > 0 ? round($k['answered'] * 100.0 / $k['total'], 1) : 0;
        $k['abandon_rate'] = $k['total'] > 0 ? round(($k['no_answer'] + $k['failed']) * 100.0 / $k['total'], 1) : 0;
        echo json_encode(['status' => 'ok', 'kpis' => $k, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'by_agent') {
        $st = $cdr->prepare("
            SELECT
              src AS agent,
              COUNT(*) AS calls,
              SUM(disposition='ANSWERED') AS answered,
              ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht,
              SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk
            FROM cdr
            WHERE calldate BETWEEN ? AND ? AND src REGEXP '^[0-9]+$' AND LENGTH(src) BETWEEN 2 AND 5
            GROUP BY src
            ORDER BY calls DESC
            LIMIT 50
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'agents' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'by_queue') {
        $st = $cdr->prepare("
            SELECT
              dst AS queue,
              COUNT(*) AS calls,
              SUM(disposition='ANSWERED') AS answered,
              SUM(disposition='NO ANSWER') AS abandoned,
              ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht,
              ROUND(SUM(disposition='ANSWERED') * 100.0 / COUNT(*), 1) AS answer_rate
            FROM cdr
            WHERE calldate BETWEEN ? AND ? AND dst REGEXP '^[0-9]+$' AND LENGTH(dst) BETWEEN 3 AND 6
            GROUP BY dst
            HAVING calls >= 5
            ORDER BY calls DESC
            LIMIT 50
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'queues' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'daily') {
        $st = $cdr->prepare("
            SELECT
              DATE(calldate) AS day,
              COUNT(*) AS total,
              SUM(disposition='ANSWERED') AS answered,
              SUM(disposition='NO ANSWER') AS abandoned,
              ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht
            FROM cdr
            WHERE calldate BETWEEN ? AND ?
            GROUP BY DATE(calldate)
            ORDER BY day ASC
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'days' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'hourly_heatmap') {
        $st = $cdr->prepare("
            SELECT
              DAYOFWEEK(calldate) - 1 AS dow,
              HOUR(calldate) AS hour,
              COUNT(*) AS calls
            FROM cdr
            WHERE calldate BETWEEN ? AND ?
            GROUP BY DAYOFWEEK(calldate), HOUR(calldate)
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'heatmap' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'disposition_breakdown') {
        $st = $cdr->prepare("
            SELECT disposition, COUNT(*) AS count
            FROM cdr WHERE calldate BETWEEN ? AND ?
            GROUP BY disposition ORDER BY count DESC
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'dispositions' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'calls') {
        $limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));
        $st = $cdr->prepare("
            SELECT calldate, src, dst, disposition, duration, billsec, channel, dstchannel, uniqueid
            FROM cdr WHERE calldate BETWEEN ? AND ?
            ORDER BY calldate DESC LIMIT $limit
        ");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'calls' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'export_csv') {
        $type = $_GET['type'] ?? 'calls';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="teleflow_' . $type . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($type === 'calls') {
            $st = $cdr->prepare("SELECT calldate, src, dst, disposition, duration, billsec FROM cdr WHERE calldate BETWEEN ? AND ? ORDER BY calldate DESC LIMIT 5000");
            $st->execute([$from, $to]);
            fputcsv($out, ['Fecha/Hora', 'Origen', 'Destino', 'Estado', 'Duración (s)', 'Hablado (s)']);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Acción desconocida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
