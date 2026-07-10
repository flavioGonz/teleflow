<?php
// F5.3: session + JSON headers + auth via _bootstrap.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'any']);
@session_write_close();

require __DIR__ . '/../config.php';

$agent_number = preg_replace('/\D/', '', $_GET['agent'] ?? '');
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to   = $_GET['to']   ?? date('Y-m-d');

if (!$agent_number) { echo json_encode(['status'=>'error','message'=>'Falta agent']); exit; }

try {
    $cc = new PDO("mysql:host=$DB_HOST;dbname=call_center;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4",   $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $cdr = new PDO("mysql:host=$DB_HOST;dbname=asteriskcdrdb;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { echo json_encode(['status'=>'error','message'=>'db_fail: '.$e->getMessage()]); exit; }

// 1) Datos del agente
$st = $cc->prepare("SELECT id, type, number, name, estatus FROM agent WHERE number=?");
$st->execute([$agent_number]);
$agent = $st->fetch(PDO::FETCH_ASSOC);
if (!$agent) { echo json_encode(['status'=>'error','message'=>'Agente no encontrado']); exit; }

// 2) Sesiones — login/logout en el rango
$st = $tf->prepare("
    SELECT id, session_id, agent_ext, login_time, logout_time, shift_date, status,
           TIMESTAMPDIFF(SECOND, login_time, COALESCE(logout_time, NOW())) AS duration_seconds
    FROM agent_sessions
    WHERE agent_number = ?
      AND shift_date BETWEEN ? AND ?
    ORDER BY login_time DESC
");
$st->execute([$agent_number, $from, $to]);
$sessions = $st->fetchAll(PDO::FETCH_ASSOC);
$total_logged = 0;
$extensions_used = [];
foreach ($sessions as $s) {
    $total_logged += (int)$s['duration_seconds'];
    if ($s['agent_ext']) $extensions_used[$s['agent_ext']] = true;
}
$extensions_used = array_keys($extensions_used);

// 3) Pausas — con tipo
$st = $tf->prepare("
    SELECT p.id, p.agent_ext, p.pause_type_code, p.pause_start, p.pause_end, p.session_id,
           COALESCE(p.duration_seconds, TIMESTAMPDIFF(SECOND, p.pause_start, COALESCE(p.pause_end, NOW()))) AS dur,
           pt.label AS type_label, pt.is_paid, pt.color
    FROM agent_pauses p
    LEFT JOIN pause_types pt ON pt.code = p.pause_type_code
    WHERE p.agent_number = ?
      AND DATE(p.pause_start) BETWEEN ? AND ?
    ORDER BY p.pause_start DESC
");
$st->execute([$agent_number, $from, $to]);
$pauses = $st->fetchAll(PDO::FETCH_ASSOC);
$total_paused = 0;
$pauses_by_reason = [];
foreach ($pauses as $p) {
    $total_paused += (int)$p['dur'];
    $code = $p['pause_type_code'];
    if (!isset($pauses_by_reason[$code])) $pauses_by_reason[$code] = ['count'=>0, 'seconds'=>0, 'label'=>$p['type_label'] ?? $code];
    $pauses_by_reason[$code]['count']++;
    $pauses_by_reason[$code]['seconds'] += (int)$p['dur'];
}

// 4) Llamadas — buscar en CDR donde dstchannel matchea SIP/<ext> de las sesiones
$calls = [];
$total_calls = 0;
$total_talk = 0;
if (!empty($extensions_used)) {
    $like_clauses = [];
    $params = [];
    foreach ($extensions_used as $ext) {
        $like_clauses[] = "dstchannel LIKE ?";
        $params[] = "SIP/{$ext}-%";
    }
    $where_ext = '(' . implode(' OR ', $like_clauses) . ')';
    $sql = "
        SELECT calldate, UNIX_TIMESTAMP(calldate) AS call_epoch, src, dst, clid, duration, billsec, disposition, dstchannel, recordingfile
        FROM cdr
        WHERE DATE(calldate) BETWEEN ? AND ?
          AND $where_ext
        ORDER BY calldate DESC
        LIMIT 500
    ";
    $st = $cdr->prepare($sql);
    $st->execute(array_merge([$from, $to], $params));
    $calls = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($calls as $c) {
        $total_calls++;
        if ($c['disposition'] === 'ANSWERED') $total_talk += (int)$c['billsec'];
    }
}

// 5) Métricas agregadas
$answered = count(array_filter($calls, fn($c) => $c['disposition'] === 'ANSWERED'));
$aht = $answered > 0 ? round($total_talk / $answered) : 0;
// Available time = logueado - en pausa
$available = max(0, $total_logged - $total_paused);
// Occupancy = tiempo en llamada / tiempo disponible
$occupancy = $available > 0 ? round(($total_talk / $available) * 100, 1) : 0;

// 6) Tendencia diaria (sesiones agrupadas por día)
$daily = [];
foreach ($sessions as $s) {
    $d = $s['shift_date'];
    if (!isset($daily[$d])) $daily[$d] = ['date'=>$d, 'logged'=>0, 'paused'=>0, 'calls'=>0, 'talk'=>0];
    $daily[$d]['logged'] += (int)$s['duration_seconds'];
}
foreach ($pauses as $p) {
    $d = substr($p['pause_start'], 0, 10);
    if (!isset($daily[$d])) $daily[$d] = ['date'=>$d, 'logged'=>0, 'paused'=>0, 'calls'=>0, 'talk'=>0];
    $daily[$d]['paused'] += (int)$p['dur'];
}
foreach ($calls as $c) {
    $d = substr($c['calldate'], 0, 10);
    if (!isset($daily[$d])) $daily[$d] = ['date'=>$d, 'logged'=>0, 'paused'=>0, 'calls'=>0, 'talk'=>0];
    $daily[$d]['calls']++;
    if ($c['disposition'] === 'ANSWERED') $daily[$d]['talk'] += (int)$c['billsec'];
}
ksort($daily);

echo json_encode([
    'status' => 'ok',
    'agent' => $agent,
    'period' => ['from'=>$from, 'to'=>$to],
    'summary' => [
        'total_logged_seconds' => $total_logged,
        'total_paused_seconds' => $total_paused,
        'total_pauses' => count($pauses),
        'pauses_by_reason' => $pauses_by_reason,
        'total_calls' => $total_calls,
        'answered_calls' => $answered,
        'total_talk_seconds' => $total_talk,
        'aht_seconds' => $aht,
        'available_seconds' => $available,
        'occupancy_pct' => $occupancy,
        'extensions_used' => $extensions_used,
    ],
    'sessions' => $sessions,
    'pauses' => $pauses,
    'calls' => $calls,
    'daily' => array_values($daily),
]);
