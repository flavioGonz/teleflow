<?php
/**
 * TeleFlow — API Reportes Avanzados v2 (Horizon)
 *
 * Acciones:
 *   summary, kpis, by_agent, by_queue, daily, hourly_heatmap,
 *   disposition_breakdown, calls, export_csv,
 *   agent_detail, agent_pauses, agent_sessions,
 *   queue_detail, queue_events, pause_stats
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

function pbx_db($db = 'asteriskcdrdb') {
    global $PBX_DB_HOST, $PBX_DB_USER, $PBX_DB_PASS;
    return new PDO("mysql:host=$PBX_DB_HOST;dbname=$db;charset=utf8mb4",
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

$action = $_GET['action'] ?? 'summary';

try {
    [$from, $to] = dt_range();

    if ($action === 'kpis' || $action === 'summary') {
        $cdr = pbx_db();
        $row = $cdr->prepare("SELECT COUNT(*) AS total, SUM(disposition='ANSWERED') AS answered, SUM(disposition='NO ANSWER') AS no_answer, SUM(disposition='BUSY') AS busy, SUM(disposition='FAILED') AS failed, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_billsec, ROUND(AVG(IF(disposition='ANSWERED', duration-billsec, NULL)),0) AS avg_wait, MAX(billsec) AS max_billsec, SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk_seconds FROM cdr WHERE calldate BETWEEN ? AND ?");
        $row->execute([$from, $to]);
        $k = $row->fetch(PDO::FETCH_ASSOC);
        $k['answer_rate'] = $k['total'] > 0 ? round($k['answered'] * 100.0 / $k['total'], 1) : 0;
        $k['abandon_rate'] = $k['total'] > 0 ? round(($k['no_answer'] + $k['failed']) * 100.0 / $k['total'], 1) : 0;
        if ($action === 'kpis') {
            echo json_encode(['status' => 'ok', 'kpis' => $k, 'period' => ['from' => $from, 'to' => $to]]);
            exit;
        }
        $tf = tf_db();
        $sess_row = $tf->prepare("SELECT COUNT(DISTINCT session_id) AS sessions, COUNT(DISTINCT agent_ext) AS unique_agents, SUM(IF(logout_time IS NOT NULL, TIMESTAMPDIFF(SECOND, login_time, logout_time), 0)) AS total_login_sec, SUM(total_calls) AS total_calls_sessions, SUM(total_talk_time) AS total_talk_sec_sessions, SUM(total_pause_time) AS total_pause_sec_sessions FROM agent_sessions WHERE login_time BETWEEN ? AND ?");
        $sess_row->execute([$from, $to]);
        $sess = $sess_row->fetch(PDO::FETCH_ASSOC);
        $pause_row = $tf->prepare("SELECT COUNT(*) AS total_pauses, SUM(IF(pause_end IS NOT NULL, duration_seconds, TIMESTAMPDIFF(SECOND, pause_start, NOW()))) AS total_pause_sec FROM agent_pauses WHERE pause_start BETWEEN ? AND ?");
        $pause_row->execute([$from, $to]);
        $pause = $pause_row->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'kpis' => $k, 'sessions' => $sess, 'pauses' => $pause, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'by_agent') {
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT src AS ext, COUNT(*) AS calls, SUM(disposition='ANSWERED') AS answered, SUM(disposition!='ANSWERED') AS not_answered, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht, SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk FROM cdr WHERE calldate BETWEEN ? AND ? AND src REGEXP '^[0-9]+\$' AND LENGTH(src) BETWEEN 2 AND 5 GROUP BY src ORDER BY calls DESC LIMIT 100");
        $st->execute([$from, $to]);
        $cdr_agents = $st->fetchAll(PDO::FETCH_ASSOC);
        $tf = tf_db();
        $names = [];
        try {
            $cc = pbx_db('call_center');
            $names = $cc->query("SELECT number, name FROM agent WHERE estatus='A'")->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {}
        $pauses_per_ext = $tf->prepare("SELECT agent_ext, COUNT(*) AS pause_count, SUM(IF(pause_end IS NOT NULL, duration_seconds, TIMESTAMPDIFF(SECOND, pause_start, NOW()))) AS pause_sec FROM agent_pauses WHERE pause_start BETWEEN ? AND ? GROUP BY agent_ext");
        $pauses_per_ext->execute([$from, $to]);
        $pauses_idx = [];
        foreach ($pauses_per_ext->fetchAll(PDO::FETCH_ASSOC) as $p) $pauses_idx[$p['agent_ext']] = $p;
        $sess_per_ext = $tf->prepare("SELECT agent_ext, agent_number, COUNT(DISTINCT session_id) AS session_count, SUM(IF(logout_time IS NOT NULL, TIMESTAMPDIFF(SECOND, login_time, logout_time), TIMESTAMPDIFF(SECOND, login_time, NOW()))) AS login_sec FROM agent_sessions WHERE login_time BETWEEN ? AND ? GROUP BY agent_ext, agent_number");
        $sess_per_ext->execute([$from, $to]);
        $sess_idx = [];
        foreach ($sess_per_ext->fetchAll(PDO::FETCH_ASSOC) as $s) $sess_idx[$s['agent_ext']] = $s;
        foreach ($cdr_agents as &$a) {
            $ext = $a['ext'];
            $a['name'] = $names[$ext] ?? null;
            $sess = $sess_idx[$ext] ?? null;
            $a['agent_number'] = $sess['agent_number'] ?? null;
            $a['login_sec'] = (int)($sess['login_sec'] ?? 0);
            $a['session_count'] = (int)($sess['session_count'] ?? 0);
            if ($a['agent_number'] && isset($names[$a['agent_number']])) $a['name'] = $names[$a['agent_number']];
            $p = $pauses_idx[$ext] ?? null;
            $a['pause_count'] = (int)($p['pause_count'] ?? 0);
            $a['pause_sec'] = (int)($p['pause_sec'] ?? 0);
            $a['productive_pct'] = $a['login_sec'] > 0 ? round(($a['login_sec'] - $a['pause_sec']) * 100.0 / $a['login_sec'], 1) : null;
        }
        unset($a);
        foreach ($sess_idx as $ext => $s) {
            $found = false;
            foreach ($cdr_agents as $a) if ($a['ext'] === $ext) { $found = true; break; }
            if ($found) continue;
            $p = $pauses_idx[$ext] ?? null;
            $cdr_agents[] = ['ext' => $ext, 'agent_number' => $s['agent_number'], 'name' => $names[$s['agent_number']] ?? $names[$ext] ?? null, 'calls' => 0, 'answered' => 0, 'not_answered' => 0, 'avg_aht' => null, 'total_talk' => 0, 'login_sec' => (int)$s['login_sec'], 'session_count' => (int)$s['session_count'], 'pause_count' => (int)($p['pause_count'] ?? 0), 'pause_sec' => (int)($p['pause_sec'] ?? 0), 'productive_pct' => $s['login_sec'] > 0 ? round(($s['login_sec'] - ($p['pause_sec'] ?? 0)) * 100.0 / $s['login_sec'], 1) : null];
        }
        echo json_encode(['status' => 'ok', 'agents' => $cdr_agents, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'by_queue') {
        $tf = tf_db();
        $sl = (int)($_GET['sl_threshold'] ?? 20);
        $st = $tf->prepare("SELECT queue_name AS queue, SUM(event_type='JOIN') AS offered, SUM(event_type='CONNECT') AS answered, SUM(event_type='ABANDON') AS abandoned, ROUND(AVG(IF(event_type='CONNECT', wait_time, NULL)),1) AS avg_wait, MAX(IF(event_type='CONNECT', wait_time, NULL)) AS max_wait, SUM(IF(event_type='CONNECT' AND wait_time <= ?, 1, 0)) AS answered_in_sl, ROUND(AVG(IF(event_type='COMPLETE', talk_time, NULL)),0) AS avg_talk, SUM(IF(event_type='COMPLETE', talk_time, 0)) AS total_talk FROM queue_events WHERE event_timestamp BETWEEN ? AND ? GROUP BY queue_name ORDER BY offered DESC");
        $st->execute([$sl, $from, $to]);
        $queues = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($queues as &$q) {
            $q['service_level'] = $q['answered'] > 0 ? round($q['answered_in_sl'] * 100.0 / $q['answered'], 1) : null;
            $q['abandon_rate'] = $q['offered'] > 0 ? round($q['abandoned'] * 100.0 / $q['offered'], 1) : 0;
            $q['answer_rate']  = $q['offered'] > 0 ? round($q['answered'] * 100.0 / $q['offered'], 1) : 0;
        }
        unset($q);
        if (empty($queues)) {
            $cdr = pbx_db();
            $st = $cdr->prepare("SELECT dst AS queue, COUNT(*) AS offered, SUM(disposition='ANSWERED') AS answered, SUM(disposition='NO ANSWER') AS abandoned, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_talk, ROUND(SUM(disposition='ANSWERED') * 100.0 / COUNT(*), 1) AS answer_rate FROM cdr WHERE calldate BETWEEN ? AND ? AND dst REGEXP '^[0-9]+\$' AND LENGTH(dst) BETWEEN 3 AND 6 GROUP BY dst HAVING offered >= 5 ORDER BY offered DESC LIMIT 50");
            $st->execute([$from, $to]);
            $queues = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        try {
            $pbx = pbx_db('asterisk');
            $descrs = $pbx->query("SELECT extension, descr FROM queues_config")->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($queues as &$q) $q['descr'] = $descrs[$q['queue']] ?? null;
            unset($q);
        } catch (Exception $e) {}
        echo json_encode(['status' => 'ok', 'queues' => $queues, 'sl_threshold' => $sl, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'daily') {
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT DATE(calldate) AS day, COUNT(*) AS total, SUM(disposition='ANSWERED') AS answered, SUM(disposition='NO ANSWER') AS abandoned, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht FROM cdr WHERE calldate BETWEEN ? AND ? GROUP BY DATE(calldate) ORDER BY day ASC");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'days' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'hourly_heatmap') {
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT DAYOFWEEK(calldate) - 1 AS dow, HOUR(calldate) AS hour, COUNT(*) AS calls FROM cdr WHERE calldate BETWEEN ? AND ? GROUP BY DAYOFWEEK(calldate), HOUR(calldate)");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'heatmap' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'disposition_breakdown') {
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT disposition, COUNT(*) AS count FROM cdr WHERE calldate BETWEEN ? AND ? GROUP BY disposition ORDER BY count DESC");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'dispositions' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'calls') {
        $limit = min(2000, max(1, (int)($_GET['limit'] ?? 200)));
        $disposition = $_GET['disposition'] ?? '';
        $src = $_GET['src'] ?? '';
        $dst = $_GET['dst'] ?? '';
        $ext = $_GET['ext'] ?? '';
        $min_dur = (int)($_GET['min_dur'] ?? 0);
        $where = "calldate BETWEEN ? AND ?";
        $params = [$from, $to];
        if ($disposition && in_array($disposition, ['ANSWERED','NO ANSWER','BUSY','FAILED'])) { $where .= " AND disposition = ?"; $params[] = $disposition; }
        if ($src && preg_match('/^[0-9]+$/', $src)) { $where .= " AND src = ?"; $params[] = $src; }
        if ($dst && preg_match('/^[0-9]+$/', $dst)) { $where .= " AND dst = ?"; $params[] = $dst; }
        if ($ext && preg_match('/^[0-9]+$/', $ext)) { $where .= " AND (src = ? OR dst = ?)"; $params[] = $ext; $params[] = $ext; }
        if ($min_dur > 0) { $where .= " AND billsec >= ?"; $params[] = $min_dur; }
        $cdr = pbx_db();
        // Cross-db join: resolver qué AGENTE estaba logueado en el ext de destino al momento de la llamada.
        // Esto es clave para reportes con failover entre colas: el dst es el ext que atendió,
        // pero queremos saber qué agent_number/name estaba en ese ext en ese instante.
        // Tabla teleflow.agent_sessions (login_time, logout_time, agent_ext, agent_number).
        $st = $cdr->prepare("SELECT
                cdr.calldate, UNIX_TIMESTAMP(cdr.calldate) AS call_epoch,
                cdr.src, cdr.dst, cdr.clid, cdr.disposition, cdr.duration, cdr.billsec,
                cdr.recordingfile, cdr.uniqueid, cdr.linkedid, cdr.did,
                asess.agent_number AS answered_by_agent_number,
                asess.agent_ext    AS answered_by_ext,
                a.name             AS answered_by_agent_name
            FROM cdr
            LEFT JOIN teleflow.agent_sessions asess
                ON asess.agent_ext = cdr.dst
                AND cdr.calldate >= asess.login_time
                AND (asess.logout_time IS NULL OR cdr.calldate <= asess.logout_time)
            LEFT JOIN call_center.agent a
                ON a.number = asess.agent_number
            WHERE $where
            ORDER BY cdr.calldate DESC
            LIMIT $limit");
        $st->execute($params);
        echo json_encode(['status' => 'ok', 'calls' => $st->fetchAll(PDO::FETCH_ASSOC), 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'agent_detail') {
        $agent = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        if (!$agent) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'falta agent']); exit; }
        $tf = tf_db();
        $info = ['agent_number' => $agent];
        try {
            $cc = pbx_db('call_center');
            $st = $cc->prepare("SELECT id, name, password, estatus FROM agent WHERE number = ?");
            $st->execute([$agent]);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            if ($r) $info += $r;
        } catch (Exception $e) {}
        $st = $tf->prepare("SELECT session_id, agent_ext, login_time, logout_time, status, TIMESTAMPDIFF(SECOND, login_time, IFNULL(logout_time, NOW())) AS duration_sec, total_calls, total_talk_time, total_pause_time, mood_flags FROM agent_sessions WHERE (agent_number = ? OR agent_ext = ?) AND login_time BETWEEN ? AND ? ORDER BY login_time DESC");
        $st->execute([$agent, $agent, $from, $to]);
        $sessions = $st->fetchAll(PDO::FETCH_ASSOC);
        $st = $tf->prepare("SELECT ap.id, ap.agent_ext, ap.pause_type_code, ap.pause_start, ap.pause_end, IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW())) AS duration_seconds, pt.label AS pause_label, pt.color AS pause_color FROM agent_pauses ap LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code WHERE (ap.agent_number = ? OR ap.agent_ext = ?) AND ap.pause_start BETWEEN ? AND ? ORDER BY ap.pause_start DESC");
        $st->execute([$agent, $agent, $from, $to]);
        $pauses = $st->fetchAll(PDO::FETCH_ASSOC);
        $exts_used = array_values(array_unique(array_filter(array_column($sessions, 'agent_ext'))));
        $kpi = ['sessions_count' => count($sessions), 'total_login_sec' => array_sum(array_column($sessions, 'duration_sec')), 'total_calls' => array_sum(array_column($sessions, 'total_calls')), 'total_talk_sec' => array_sum(array_column($sessions, 'total_talk_time')), 'pauses_count' => count($pauses), 'total_pause_sec' => array_sum(array_column($pauses, 'duration_seconds'))];
        $kpi['productive_sec'] = max(0, $kpi['total_login_sec'] - $kpi['total_pause_sec']);
        $kpi['productive_pct'] = $kpi['total_login_sec'] > 0 ? round($kpi['productive_sec'] * 100.0 / $kpi['total_login_sec'], 1) : null;
        $calls = [];
        if (!empty($exts_used)) {
            $cdr = pbx_db();
            $place = implode(',', array_fill(0, count($exts_used), '?'));
            $st = $cdr->prepare("SELECT calldate, src, dst, disposition, duration, billsec, clid, recordingfile, uniqueid FROM cdr WHERE calldate BETWEEN ? AND ? AND (src IN ($place) OR dst IN ($place)) ORDER BY calldate DESC LIMIT 500");
            $st->execute(array_merge([$from, $to], $exts_used, $exts_used));
            $calls = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        $pause_breakdown = [];
        foreach ($pauses as $p) {
            $code = $p['pause_type_code'];
            if (!isset($pause_breakdown[$code])) {
                $pause_breakdown[$code] = ['code' => $code, 'label' => $p['pause_label'] ?: $code, 'color' => $p['pause_color'], 'count' => 0, 'total_sec' => 0];
            }
            $pause_breakdown[$code]['count']++;
            $pause_breakdown[$code]['total_sec'] += $p['duration_seconds'];
        }
        $pause_breakdown = array_values($pause_breakdown);
        usort($pause_breakdown, fn($a,$b) => $b['total_sec'] - $a['total_sec']);
        echo json_encode(['status' => 'ok', 'agent' => $info, 'kpi' => $kpi, 'sessions' => $sessions, 'pauses' => $pauses, 'pause_breakdown' => $pause_breakdown, 'calls' => $calls, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'agent_pauses' || $action === 'pauses') {
        $agent = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        $tf = tf_db();
        $sql = "SELECT ap.id, ap.agent_ext, ap.agent_number, ap.pause_type_code, ap.pause_start, ap.pause_end, IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW())) AS duration_seconds, pt.label AS pause_label, pt.color AS pause_color FROM agent_pauses ap LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code WHERE ap.pause_start BETWEEN ? AND ?";
        $params = [$from, $to];
        if ($agent) { $sql .= " AND (ap.agent_number = ? OR ap.agent_ext = ?)"; $params[] = $agent; $params[] = $agent; }
        $sql .= " ORDER BY ap.pause_start DESC LIMIT 500";
        $st = $tf->prepare($sql); $st->execute($params);
        echo json_encode(['status' => 'ok', 'pauses' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'agent_sessions') {
        $agent = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        $tf = tf_db();
        $sql = "SELECT session_id, agent_ext, agent_number, login_time, UNIX_TIMESTAMP(login_time) AS login_epoch, logout_time, status, TIMESTAMPDIFF(SECOND, login_time, IFNULL(logout_time, NOW())) AS duration_sec, total_calls, total_talk_time, total_pause_time FROM agent_sessions WHERE login_time BETWEEN ? AND ?";
        $params = [$from, $to];
        if ($agent) { $sql .= " AND (agent_number = ? OR agent_ext = ?)"; $params[] = $agent; $params[] = $agent; }
        $sql .= " ORDER BY login_time DESC LIMIT 500";
        $st = $tf->prepare($sql); $st->execute($params);
        echo json_encode(['status' => 'ok', 'sessions' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'queue_detail') {
        $queue = preg_replace('/[^0-9]/', '', $_GET['queue'] ?? '');
        if (!$queue) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'falta queue']); exit; }
        $sl = (int)($_GET['sl_threshold'] ?? 20);
        $tf = tf_db();
        $st = $tf->prepare("SELECT SUM(event_type='JOIN') AS offered, SUM(event_type='CONNECT') AS answered, SUM(event_type='ABANDON') AS abandoned, ROUND(AVG(IF(event_type='CONNECT', wait_time, NULL)),1) AS avg_wait, MAX(IF(event_type='CONNECT', wait_time, NULL)) AS max_wait, SUM(IF(event_type='CONNECT' AND wait_time <= ?, 1, 0)) AS in_sl, ROUND(AVG(IF(event_type='COMPLETE', talk_time, NULL)),0) AS avg_talk FROM queue_events WHERE queue_name = ? AND event_timestamp BETWEEN ? AND ?");
        $st->execute([$sl, $queue, $from, $to]);
        $sum = $st->fetch(PDO::FETCH_ASSOC);
        $sum['service_level'] = $sum['answered'] > 0 ? round($sum['in_sl'] * 100.0 / $sum['answered'], 1) : null;
        $sum['abandon_rate']  = $sum['offered'] > 0 ? round($sum['abandoned'] * 100.0 / $sum['offered'], 1) : 0;
        $st = $tf->prepare("SELECT agent_ext, SUM(event_type='CONNECT') AS calls, ROUND(AVG(IF(event_type='COMPLETE', talk_time, NULL)),0) AS avg_talk, SUM(IF(event_type='COMPLETE', talk_time, 0)) AS total_talk FROM queue_events WHERE queue_name = ? AND event_timestamp BETWEEN ? AND ? AND agent_ext IS NOT NULL GROUP BY agent_ext ORDER BY calls DESC");
        $st->execute([$queue, $from, $to]);
        $by_agent = $st->fetchAll(PDO::FETCH_ASSOC);
        $st = $tf->prepare("SELECT HOUR(event_timestamp) AS hour, SUM(event_type='JOIN') AS offered, SUM(event_type='CONNECT') AS answered, SUM(event_type='ABANDON') AS abandoned FROM queue_events WHERE queue_name = ? AND event_timestamp BETWEEN ? AND ? GROUP BY HOUR(event_timestamp) ORDER BY hour");
        $st->execute([$queue, $from, $to]);
        $by_hour = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'queue' => $queue, 'sl_threshold' => $sl, 'summary' => $sum, 'by_agent' => $by_agent, 'by_hour' => $by_hour, 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'queue_events') {
        $queue = preg_replace('/[^0-9]/', '', $_GET['queue'] ?? '');
        $tf = tf_db();
        $sql = "SELECT * FROM queue_events WHERE event_timestamp BETWEEN ? AND ?";
        $params = [$from, $to];
        if ($queue) { $sql .= " AND queue_name = ?"; $params[] = $queue; }
        $sql .= " ORDER BY event_timestamp DESC LIMIT 500";
        $st = $tf->prepare($sql); $st->execute($params);
        echo json_encode(['status' => 'ok', 'events' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'pause_stats') {
        $tf = tf_db();
        $st = $tf->prepare("SELECT ap.pause_type_code AS code, IFNULL(pt.label, ap.pause_type_code) AS label, pt.color, COUNT(*) AS count, SUM(IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW()))) AS total_sec, ROUND(AVG(IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW()))),0) AS avg_sec FROM agent_pauses ap LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code WHERE ap.pause_start BETWEEN ? AND ? GROUP BY ap.pause_type_code, pt.label, pt.color ORDER BY count DESC");
        $st->execute([$from, $to]);
        echo json_encode(['status' => 'ok', 'pause_stats' => $st->fetchAll(PDO::FETCH_ASSOC), 'period' => ['from' => $from, 'to' => $to]]);
        exit;
    }

    if ($action === 'export_csv') {
        $type = $_GET['type'] ?? 'calls';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="teleflow_' . $type . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($type === 'calls') {
            $cdr = pbx_db();
            $st = $cdr->prepare("SELECT calldate, src, dst, disposition, duration, billsec FROM cdr WHERE calldate BETWEEN ? AND ? ORDER BY calldate DESC LIMIT 5000");
            $st->execute([$from, $to]);
            fputcsv($out, ['Fecha/Hora', 'Origen', 'Destino', 'Estado', 'Duración (s)', 'Hablado (s)']);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }

    http_response_code(400);
    // ─── FAILOVER CALLS (optimizada) ─────────────────────────────────
    if ($action === 'failover_calls') {
        $main_queue = preg_replace('/[^0-9]/', '', $_GET['main_queue'] ?? '');
        $agent_filter = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        $window_sec = intval($_GET['window'] ?? 300);
        $tf = tf_db();

        // 1) Self-join principal SOLO con info básica del journey + LEFT JOIN para CONNECT/COMPLETE/EXIT
        //    Mucho mas eficiente que subqueries correlacionadas
        $where_main = $main_queue ? "AND q1.queue_name = ?" : "";
        $params = [$from, $to];
        if ($main_queue) $params[] = $main_queue;

        $sql = "
            SELECT
                q1.event_timestamp AS journey_start,
                q1.caller_id,
                q1.queue_name AS source_queue,
                q2.queue_name AS failover_queue,
                q2.event_timestamp AS failover_at,
                qc.event_timestamp AS connect_at,
                qc.agent_ext AS answered_ext,
                qc.wait_time AS wait_sec,
                qcomp.talk_time AS talk_sec,
                qexit.event_type AS exit_reason
            FROM queue_events q1
            INNER JOIN queue_events q2
              ON q1.caller_id = q2.caller_id
              AND q1.queue_name <> q2.queue_name
              AND q1.event_type = 'JOIN'
              AND q2.event_type = 'JOIN'
              AND q2.event_timestamp > q1.event_timestamp
              AND q2.event_timestamp <= DATE_ADD(q1.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qc
              ON qc.caller_id = q1.caller_id
              AND qc.queue_name = q2.queue_name
              AND qc.event_type = 'CONNECT'
              AND qc.event_timestamp >= q2.event_timestamp
              AND qc.event_timestamp <= DATE_ADD(q2.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qcomp
              ON qcomp.caller_id = q1.caller_id
              AND qcomp.queue_name = q2.queue_name
              AND qcomp.event_type = 'COMPLETE'
              AND qcomp.event_timestamp >= q2.event_timestamp
              AND qcomp.event_timestamp <= DATE_ADD(q2.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qexit
              ON qexit.caller_id = q1.caller_id
              AND qexit.queue_name = q1.queue_name
              AND qexit.event_timestamp >= q1.event_timestamp
              AND qexit.event_timestamp <= q2.event_timestamp
              AND qexit.event_type IN ('ABANDON','EXITWITHTIMEOUT','EXITEMPTY')
            WHERE q1.event_timestamp BETWEEN ? AND ?
              $where_main
            ORDER BY q1.event_timestamp DESC
            LIMIT 500
        ";
        $st = $tf->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // 2) Dedup: misma journey (caller+src+failover+minuto) → quedarnos con la fila con info mas completa
        $dedup = [];
        foreach ($rows as $r) {
            $key = $r['caller_id'].'|'.$r['source_queue'].'|'.$r['failover_queue'].'|'.substr($r['journey_start'], 0, 16);
            if (!isset($dedup[$key])) { $dedup[$key] = $r; continue; }
            $prev = $dedup[$key];
            $cur_score = (int)!empty($r['connect_at']) + (int)!empty($r['answered_ext']) + (int)!empty($r['talk_sec']);
            $prev_score = (int)!empty($prev['connect_at']) + (int)!empty($prev['answered_ext']) + (int)!empty($prev['talk_sec']);
            if ($cur_score > $prev_score) $dedup[$key] = $r;
        }
        $rows = array_values($dedup);

        // 3) BATCH lookup de agent_sessions: una sola query con WHERE agent_ext IN (...)
        $exts = array_values(array_unique(array_filter(array_column($rows, 'answered_ext'))));
        $session_map = []; // key: ext|journey_start_minute → agent_number
        if (!empty($exts)) {
            $ph = implode(',', array_fill(0, count($exts), '?'));
            $st2 = $tf->prepare("SELECT agent_number, agent_ext, login_time, logout_time FROM agent_sessions WHERE agent_ext IN ($ph) AND login_time <= ? AND (logout_time IS NULL OR logout_time >= ?)");
            $params2 = array_merge($exts, [$to, $from]);
            $st2->execute($params2);
            $sessions = $st2->fetchAll(PDO::FETCH_ASSOC);
            // Index por ext, dentro guarda lista de (login, logout, agent_number)
            $sess_idx = [];
            foreach ($sessions as $s) $sess_idx[$s['agent_ext']][] = $s;
            // Para cada row, encontrar sesion que cubra el connect_at
            foreach ($rows as $r) {
                if (empty($r['answered_ext']) || empty($r['connect_at'])) continue;
                $ext = $r['answered_ext']; $at = $r['connect_at'];
                if (!isset($sess_idx[$ext])) continue;
                foreach ($sess_idx[$ext] as $s) {
                    if ($s['login_time'] <= $at && ($s['logout_time'] === null || $s['logout_time'] >= $at)) {
                        $session_map[$ext.'|'.$at] = $s['agent_number'];
                        break;
                    }
                }
            }
        }

        // 4) BATCH agent_pauses: una query sobre el período
        $pause_windows = []; // list de [start, end]
        try {
            $stp = $tf->prepare("SELECT pause_start, IFNULL(pause_end, NOW()) AS pause_end FROM agent_pauses WHERE pause_start <= ? AND (pause_end IS NULL OR pause_end >= ?)");
            $stp->execute([$to, $from]);
            $pause_windows = $stp->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // 5) Agent names batch
        $agent_names = [];
        $nums = array_values(array_unique(array_filter($session_map)));
        if (!empty($nums)) {
            try {
                $cc = new PDO("mysql:host=$DB_HOST;dbname=call_center;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
                $ph = implode(',', array_fill(0, count($nums), '?'));
                $st3 = $cc->prepare("SELECT number, name FROM agent WHERE number IN ($ph)");
                $st3->execute($nums);
                foreach ($st3->fetchAll(PDO::FETCH_ASSOC) as $a) $agent_names[$a['number']] = $a['name'];
            } catch (Exception $e) {}
        }

        // 6) Assemble result + filtros agente
        $result = [];
        foreach ($rows as $r) {
            $session_key = ($r['answered_ext'] ?? '').'|'.($r['connect_at'] ?? '');
            $agent_num = $session_map[$session_key] ?? null;
            if ($agent_filter && $agent_num != $agent_filter) continue;

            $reason_code = 'TIMEOUT';
            $reason_label = 'Timeout / nadie atendió';
            if ($r['exit_reason'] === 'ABANDON') {
                $reason_code = 'ABANDON_SRC';
                $reason_label = 'Abandono en cola origen';
            }
            // Check si hubo pausa activa en el momento del journey_start
            foreach ($pause_windows as $pw) {
                if ($pw['pause_start'] <= $r['journey_start'] && $pw['pause_end'] >= $r['journey_start']) {
                    $reason_code = 'AGENT_PAUSE';
                    $reason_label = 'Agente en pausa';
                    break;
                }
            }

            $result[] = [
                'journey_start'  => $r['journey_start'],
                'caller_id'      => $r['caller_id'],
                'source_queue'   => $r['source_queue'],
                'failover_queue' => $r['failover_queue'],
                'failover_at'    => $r['failover_at'],
                'connect_at'     => $r['connect_at'],
                'answered_ext'   => $r['answered_ext'],
                'agent_number'   => $agent_num,
                'agent_name'     => ($agent_num && isset($agent_names[$agent_num])) ? $agent_names[$agent_num] : null,
                'wait_sec'       => intval($r['wait_sec'] ?? 0),
                'talk_sec'       => intval($r['talk_sec'] ?? 0),
                'reason_code'    => $reason_code,
                'reason_label'   => $reason_label,
                'status'         => $r['connect_at'] ? 'answered' : 'unanswered',
            ];
        }

        echo json_encode([
            'status' => 'ok',
            'failovers' => $result,
            'total' => count($result),
            'period' => ['from' => $from, 'to' => $to],
        ]);
        exit;
    }



    echo json_encode(['status' => 'error', 'message' => 'Acción desconocida: ' . $action]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
