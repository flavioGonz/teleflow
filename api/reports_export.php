<?php
/**
 * TeleFlow — Export de reportes a PDF / XLSX (server-side)
 *
 * GET ?type=<summary|by_agent|by_queue|calls|agent_detail|pauses>&format=<pdf|xlsx>
 *     &from=YYYY-MM-DD&to=YYYY-MM-DD
 *     [&agent=N] [&queue=Q] [&disposition=X] etc. (mismos filtros que reports.php)
 */
session_start();
if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo 'No autorizado'; exit;
}
@session_write_close();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

function pbx_db($db = 'asteriskcdrdb') {
    global $PBX_DB_HOST, $PBX_DB_USER, $PBX_DB_PASS;
    return new PDO("mysql:host=$PBX_DB_HOST;dbname=$db;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
}
function tf_db() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
function fmt_secs($s) {
    $s = (int)$s;
    if ($s <= 0) return '0s';
    $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); $r = $s % 60;
    return $h ? sprintf('%dh %02dm', $h, $m) : ($m ? sprintf('%dm %02ds', $m, $r) : "{$r}s");
}

function col_letter($col) {
    $letter = '';
    while ($col > 0) {
        $col--; $letter = chr(65 + ($col % 26)) . $letter; $col = intdiv($col, 26);
    }
    return $letter;
}
function dt_range() {
    $today = date('Y-m-d');
    $from = $_GET['from'] ?? $today;
    $to = $_GET['to'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
    return [$from . ' 00:00:00', $to . ' 23:59:59', $from, $to];
}

$type    = $_GET['type'] ?? 'summary';
$format  = $_GET['format'] ?? 'pdf';
[$from, $to, $from_d, $to_d] = dt_range();

// ─── Recolectar datos según tipo ──────────────────────────────────────
function fetch_data($type, $from, $to) {
    $tf = tf_db();
    if ($type === 'summary') {
        $cdr = pbx_db();
        $r = $cdr->prepare("SELECT COUNT(*) AS total, SUM(disposition='ANSWERED') AS answered, SUM(disposition='NO ANSWER') AS no_answer, SUM(disposition='BUSY') AS busy, SUM(disposition='FAILED') AS failed, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_billsec, ROUND(AVG(IF(disposition='ANSWERED', duration-billsec, NULL)),0) AS avg_wait, SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk_seconds FROM cdr WHERE calldate BETWEEN ? AND ?");
        $r->execute([$from, $to]);
        $kpis = $r->fetch(PDO::FETCH_ASSOC);
        $kpis['answer_rate'] = $kpis['total'] > 0 ? round($kpis['answered'] * 100.0 / $kpis['total'], 1) : 0;
        $kpis['abandon_rate'] = $kpis['total'] > 0 ? round(($kpis['no_answer'] + $kpis['failed']) * 100.0 / $kpis['total'], 1) : 0;
        $ss = $tf->prepare("SELECT COUNT(DISTINCT session_id) AS sessions, COUNT(DISTINCT agent_ext) AS unique_agents, SUM(IF(logout_time IS NOT NULL, TIMESTAMPDIFF(SECOND, login_time, logout_time), 0)) AS total_login_sec FROM agent_sessions WHERE login_time BETWEEN ? AND ?");
        $ss->execute([$from, $to]); $sess = $ss->fetch(PDO::FETCH_ASSOC);
        $ps = $tf->prepare("SELECT COUNT(*) AS total_pauses, SUM(IF(pause_end IS NOT NULL, duration_seconds, TIMESTAMPDIFF(SECOND, pause_start, NOW()))) AS total_pause_sec FROM agent_pauses WHERE pause_start BETWEEN ? AND ?");
        $ps->execute([$from, $to]); $pause = $ps->fetch(PDO::FETCH_ASSOC);
        return ['kpis' => $kpis, 'sessions' => $sess, 'pauses' => $pause];
    }
    if ($type === 'by_agent') {
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT src AS ext, COUNT(*) AS calls, SUM(disposition='ANSWERED') AS answered, ROUND(AVG(IF(disposition='ANSWERED', billsec, NULL)),0) AS avg_aht, SUM(IF(disposition='ANSWERED', billsec, 0)) AS total_talk FROM cdr WHERE calldate BETWEEN ? AND ? AND src REGEXP '^[0-9]+\$' AND LENGTH(src) BETWEEN 2 AND 5 GROUP BY src ORDER BY calls DESC LIMIT 200");
        $st->execute([$from, $to]); $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $names = []; try { $cc = pbx_db('call_center'); $names = $cc->query("SELECT number, name FROM agent")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Exception $e) {}
        $px = $tf->prepare("SELECT agent_ext, COUNT(*) AS pause_count, SUM(IF(pause_end IS NOT NULL, duration_seconds, TIMESTAMPDIFF(SECOND, pause_start, NOW()))) AS pause_sec FROM agent_pauses WHERE pause_start BETWEEN ? AND ? GROUP BY agent_ext");
        $px->execute([$from, $to]); $pauses_idx = [];
        foreach ($px->fetchAll(PDO::FETCH_ASSOC) as $p) $pauses_idx[$p['agent_ext']] = $p;
        $sx = $tf->prepare("SELECT agent_ext, agent_number, COUNT(DISTINCT session_id) AS session_count, SUM(IF(logout_time IS NOT NULL, TIMESTAMPDIFF(SECOND, login_time, logout_time), TIMESTAMPDIFF(SECOND, login_time, NOW()))) AS login_sec FROM agent_sessions WHERE login_time BETWEEN ? AND ? GROUP BY agent_ext, agent_number");
        $sx->execute([$from, $to]); $sess_idx = [];
        foreach ($sx->fetchAll(PDO::FETCH_ASSOC) as $s) $sess_idx[$s['agent_ext']] = $s;
        foreach ($rows as &$r) {
            $ext = $r['ext'];
            $s = $sess_idx[$ext] ?? null;
            $r['agent_number'] = $s['agent_number'] ?? null;
            $r['name'] = $names[$r['agent_number'] ?? ''] ?? $names[$ext] ?? null;
            $r['login_sec'] = (int)($s['login_sec'] ?? 0);
            $r['session_count'] = (int)($s['session_count'] ?? 0);
            $p = $pauses_idx[$ext] ?? null;
            $r['pause_count'] = (int)($p['pause_count'] ?? 0);
            $r['pause_sec'] = (int)($p['pause_sec'] ?? 0);
            $r['productive_pct'] = $r['login_sec'] > 0 ? round(($r['login_sec'] - $r['pause_sec']) * 100.0 / $r['login_sec'], 1) : null;
        }
        return ['agents' => $rows];
    }
    if ($type === 'by_queue') {
        $sl = (int)($_GET['sl_threshold'] ?? 20);
        $st = $tf->prepare("SELECT queue_name AS queue, SUM(event_type='JOIN') AS offered, SUM(event_type='CONNECT') AS answered, SUM(event_type='ABANDON') AS abandoned, ROUND(AVG(IF(event_type='CONNECT', wait_time, NULL)),1) AS avg_wait, MAX(IF(event_type='CONNECT', wait_time, NULL)) AS max_wait, SUM(IF(event_type='CONNECT' AND wait_time <= ?, 1, 0)) AS in_sl, ROUND(AVG(IF(event_type='COMPLETE', talk_time, NULL)),0) AS avg_talk FROM queue_events WHERE event_timestamp BETWEEN ? AND ? GROUP BY queue_name ORDER BY offered DESC");
        $st->execute([$sl, $from, $to]); $queues = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($queues as &$q) {
            $q['service_level'] = $q['answered'] > 0 ? round($q['in_sl'] * 100.0 / $q['answered'], 1) : null;
            $q['abandon_rate'] = $q['offered'] > 0 ? round($q['abandoned'] * 100.0 / $q['offered'], 1) : 0;
        }
        try { $pbx = pbx_db('asterisk'); $descrs = $pbx->query("SELECT extension, descr FROM queues_config")->fetchAll(PDO::FETCH_KEY_PAIR); foreach ($queues as &$q) $q['descr'] = $descrs[$q['queue']] ?? null; } catch (Exception $e) {}
        return ['queues' => $queues, 'sl_threshold' => $sl];
    }
    if ($type === 'calls') {
        $disposition = $_GET['disposition'] ?? '';
        $ext = preg_replace('/[^0-9]/', '', $_GET['ext'] ?? '');
        $where = "calldate BETWEEN ? AND ?"; $params = [$from, $to];
        if ($disposition) { $where .= " AND disposition = ?"; $params[] = $disposition; }
        if ($ext)         { $where .= " AND (src = ? OR dst = ?)"; $params[] = $ext; $params[] = $ext; }
        $cdr = pbx_db();
        $st = $cdr->prepare("SELECT calldate, src, dst, clid, disposition, duration, billsec, recordingfile FROM cdr WHERE $where ORDER BY calldate DESC LIMIT 5000");
        $st->execute($params);
        return ['calls' => $st->fetchAll(PDO::FETCH_ASSOC)];
    }
    if ($type === 'pauses') {
        $st = $tf->prepare("SELECT ap.agent_ext, ap.agent_number, ap.pause_type_code, IFNULL(pt.label, ap.pause_type_code) AS pause_label, ap.pause_start, ap.pause_end, IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW())) AS duration_seconds FROM agent_pauses ap LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code WHERE ap.pause_start BETWEEN ? AND ? ORDER BY ap.pause_start DESC LIMIT 5000");
        $st->execute([$from, $to]);
        return ['pauses' => $st->fetchAll(PDO::FETCH_ASSOC)];
    }
    if ($type === 'agent_detail') {
        $agent = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        if (!$agent) return null;
        $info = ['agent_number' => $agent];
        try { $cc = pbx_db('call_center'); $st = $cc->prepare("SELECT id, name, password, estatus FROM agent WHERE number = ?"); $st->execute([$agent]); if ($r = $st->fetch(PDO::FETCH_ASSOC)) $info += $r; } catch (Exception $e) {}
        $st = $tf->prepare("SELECT session_id, agent_ext, login_time, logout_time, status, TIMESTAMPDIFF(SECOND, login_time, IFNULL(logout_time, NOW())) AS duration_sec, total_calls, total_talk_time, total_pause_time FROM agent_sessions WHERE (agent_number = ? OR agent_ext = ?) AND login_time BETWEEN ? AND ? ORDER BY login_time DESC");
        $st->execute([$agent, $agent, $from, $to]); $sessions = $st->fetchAll(PDO::FETCH_ASSOC);
        $st = $tf->prepare("SELECT ap.agent_ext, ap.pause_type_code, ap.pause_start, ap.pause_end, IFNULL(ap.duration_seconds, TIMESTAMPDIFF(SECOND, ap.pause_start, NOW())) AS duration_seconds, IFNULL(pt.label, ap.pause_type_code) AS pause_label FROM agent_pauses ap LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code WHERE (ap.agent_number = ? OR ap.agent_ext = ?) AND ap.pause_start BETWEEN ? AND ? ORDER BY ap.pause_start DESC");
        $st->execute([$agent, $agent, $from, $to]); $pauses = $st->fetchAll(PDO::FETCH_ASSOC);
        $kpi = ['sessions_count' => count($sessions), 'total_login_sec' => array_sum(array_column($sessions, 'duration_sec')), 'total_calls' => array_sum(array_column($sessions, 'total_calls')), 'pauses_count' => count($pauses), 'total_pause_sec' => array_sum(array_column($pauses, 'duration_seconds'))];
        return ['info' => $info, 'kpi' => $kpi, 'sessions' => $sessions, 'pauses' => $pauses];
    }
    if ($type === 'failover_calls') {
        $main_queue = preg_replace('/[^0-9]/', '', $_GET['main_queue'] ?? '');
        $agent_filter = preg_replace('/[^0-9]/', '', $_GET['agent'] ?? '');
        $window_sec = 300;
        $where_main = $main_queue ? "AND q1.queue_name = ?" : "";
        $params = [$from, $to]; if ($main_queue) $params[] = $main_queue;
        $sql = "
            SELECT q1.event_timestamp AS journey_start, q1.caller_id, q1.queue_name AS source_queue,
                   q2.queue_name AS failover_queue, q2.event_timestamp AS failover_at,
                   qc.event_timestamp AS connect_at, qc.agent_ext AS answered_ext, qc.wait_time AS wait_sec,
                   qcomp.talk_time AS talk_sec, qexit.event_type AS exit_reason
            FROM queue_events q1
            INNER JOIN queue_events q2 ON q1.caller_id=q2.caller_id AND q1.queue_name<>q2.queue_name
              AND q1.event_type='JOIN' AND q2.event_type='JOIN'
              AND q2.event_timestamp>q1.event_timestamp
              AND q2.event_timestamp<=DATE_ADD(q1.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qc ON qc.caller_id=q1.caller_id AND qc.queue_name=q2.queue_name
              AND qc.event_type='CONNECT' AND qc.event_timestamp>=q2.event_timestamp
              AND qc.event_timestamp<=DATE_ADD(q2.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qcomp ON qcomp.caller_id=q1.caller_id AND qcomp.queue_name=q2.queue_name
              AND qcomp.event_type='COMPLETE' AND qcomp.event_timestamp>=q2.event_timestamp
              AND qcomp.event_timestamp<=DATE_ADD(q2.event_timestamp, INTERVAL $window_sec SECOND)
            LEFT JOIN queue_events qexit ON qexit.caller_id=q1.caller_id AND qexit.queue_name=q1.queue_name
              AND qexit.event_timestamp>=q1.event_timestamp AND qexit.event_timestamp<=q2.event_timestamp
              AND qexit.event_type IN ('ABANDON','EXITWITHTIMEOUT','EXITEMPTY')
            WHERE q1.event_timestamp BETWEEN ? AND ? $where_main
            ORDER BY q1.event_timestamp DESC LIMIT 5000
        ";
        $st = $tf->prepare($sql); $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        // Dedup
        $dedup = [];
        foreach ($rows as $r) {
            $key = $r['caller_id'].'|'.$r['source_queue'].'|'.$r['failover_queue'].'|'.substr($r['journey_start'],0,16);
            if (!isset($dedup[$key])) { $dedup[$key] = $r; continue; }
            $cur = (int)!empty($r['connect_at'])+(int)!empty($r['answered_ext'])+(int)!empty($r['talk_sec']);
            $prv = (int)!empty($dedup[$key]['connect_at'])+(int)!empty($dedup[$key]['answered_ext'])+(int)!empty($dedup[$key]['talk_sec']);
            if ($cur > $prv) $dedup[$key] = $r;
        }
        $rows = array_values($dedup);
        // Batch agent_sessions
        $exts = array_values(array_unique(array_filter(array_column($rows, 'answered_ext'))));
        $session_map = [];
        if (!empty($exts)) {
            $ph = implode(',', array_fill(0, count($exts), '?'));
            $st2 = $tf->prepare("SELECT agent_number, agent_ext, login_time, logout_time FROM agent_sessions WHERE agent_ext IN ($ph) AND login_time<=? AND (logout_time IS NULL OR logout_time>=?)");
            $st2->execute(array_merge($exts, [$to, $from]));
            $sess_idx = [];
            foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $s) $sess_idx[$s['agent_ext']][] = $s;
            foreach ($rows as $r) {
                if (empty($r['answered_ext']) || empty($r['connect_at'])) continue;
                $at = $r['connect_at'];
                foreach (($sess_idx[$r['answered_ext']] ?? []) as $s) {
                    if ($s['login_time'] <= $at && ($s['logout_time'] === null || $s['logout_time'] >= $at)) {
                        $session_map[$r['answered_ext'].'|'.$at] = $s['agent_number']; break;
                    }
                }
            }
        }
        // Pauses for reason detection
        $pauses_win = [];
        try {
            $stp = $tf->prepare("SELECT pause_start, IFNULL(pause_end, NOW()) AS pause_end FROM agent_pauses WHERE pause_start<=? AND (pause_end IS NULL OR pause_end>=?)");
            $stp->execute([$to, $from]); $pauses_win = $stp->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        // Agent names
        $agent_names = [];
        $nums = array_values(array_unique(array_filter($session_map)));
        if (!empty($nums)) {
            try {
                $cc = pbx_db('call_center');
                $ph = implode(',', array_fill(0, count($nums), '?'));
                $st3 = $cc->prepare("SELECT number, name FROM agent WHERE number IN ($ph)");
                $st3->execute($nums);
                foreach ($st3->fetchAll(PDO::FETCH_ASSOC) as $a) $agent_names[$a['number']] = $a['name'];
            } catch (Exception $e) {}
        }
        // Compose
        $result = [];
        foreach ($rows as $r) {
            $skey = ($r['answered_ext'] ?? '').'|'.($r['connect_at'] ?? '');
            $agent_num = $session_map[$skey] ?? null;
            if ($agent_filter && $agent_num != $agent_filter) continue;
            $reason = 'Timeout / nadie atendió';
            if ($r['exit_reason'] === 'ABANDON') $reason = 'Abandono en cola origen';
            foreach ($pauses_win as $pw) {
                if ($pw['pause_start'] <= $r['journey_start'] && $pw['pause_end'] >= $r['journey_start']) { $reason = 'Agente en pausa'; break; }
            }
            $result[] = [
                'journey_start'=>$r['journey_start'], 'caller_id'=>$r['caller_id'],
                'source_queue'=>$r['source_queue'], 'failover_queue'=>$r['failover_queue'],
                'connect_at'=>$r['connect_at'], 'answered_ext'=>$r['answered_ext'],
                'agent_number'=>$agent_num, 'agent_name'=>($agent_num && isset($agent_names[$agent_num])) ? $agent_names[$agent_num] : null,
                'wait_sec'=>intval($r['wait_sec'] ?? 0), 'talk_sec'=>intval($r['talk_sec'] ?? 0),
                'reason'=>$reason, 'status'=>$r['connect_at'] ? 'Atendida' : 'No atendida',
            ];
        }
        return ['failovers' => $result];
    }
    return null;
}

$data = fetch_data($type, $from, $to);
if (!$data) { http_response_code(400); echo 'Tipo inválido'; exit; }

// ─── XLSX ──────────────────────────────────────────────────────────────
if ($format === 'xlsx') {
    $ss = new Spreadsheet();
    $sh = $ss->getActiveSheet();

    $headStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>11], 'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'11B328']], 'alignment'=>['vertical'=>Alignment::VERTICAL_CENTER,'horizontal'=>Alignment::HORIZONTAL_CENTER], 'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'0E8F20']]]];
    $titleStyle = ['font'=>['bold'=>true,'size'=>16,'color'=>['rgb'=>'11B328']]];
    $subTitleStyle = ['font'=>['italic'=>true,'size'=>10,'color'=>['rgb'=>'6B7280']]];

    $sh->setCellValue('A1', 'TeleFlow — Reporte ' . strtoupper($type));
    $sh->getStyle('A1')->applyFromArray($titleStyle);
    $sh->mergeCells('A1:H1');
    $sh->setCellValue('A2', "Período: $from_d → $to_d  ·  Generado: " . date('Y-m-d H:i'));
    $sh->getStyle('A2')->applyFromArray($subTitleStyle);
    $sh->mergeCells('A2:H2');

    $row = 4;
    if ($type === 'summary') {
        $kpi = $data['kpis']; $sess = $data['sessions']; $p = $data['pauses'];
        $rows = [
            ['Total llamadas', $kpi['total']],
            ['Contestadas', $kpi['answered']],
            ['Sin respuesta', $kpi['no_answer']],
            ['Ocupado', $kpi['busy']],
            ['Falladas', $kpi['failed']],
            ['Tasa de respuesta %', $kpi['answer_rate']],
            ['Tasa de abandono %', $kpi['abandon_rate']],
            ['Espera promedio (s)', $kpi['avg_wait']],
            ['Duración promedio (s)', $kpi['avg_billsec']],
            ['Talk time total', fmt_secs($kpi['total_talk_seconds'])],
            ['', ''],
            ['Sesiones de agentes', $sess['sessions']],
            ['Agentes únicos', $sess['unique_agents']],
            ['Tiempo total logueado', fmt_secs($sess['total_login_sec'])],
            ['', ''],
            ['Pausas totales', $p['total_pauses']],
            ['Tiempo total en pausa', fmt_secs($p['total_pause_sec'])],
        ];
        $sh->setCellValue('A4', 'INDICADOR'); $sh->setCellValue('B4', 'VALOR');
        $sh->getStyle('A4:B4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($rows as $r) { $sh->setCellValue('A'.$row, $r[0]); $sh->setCellValue('B'.$row, $r[1]); $row++; }
        $sh->getColumnDimension('A')->setWidth(30); $sh->getColumnDimension('B')->setWidth(22);
        $ss->getActiveSheet()->setTitle('Resumen');
    } elseif ($type === 'by_agent') {
        $headers = ['Ext','Agente','Nombre','Sesiones','Login total','Pausas','Tiempo pausa','% Productivo','Llamadas','Contestadas','AHT (s)','Talk total'];
        foreach ($headers as $i => $h) $sh->setCellValue(col_letter($i+1) . 4, $h);
        $sh->getStyle('A4:L4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($data['agents'] as $a) {
            $cells = [$a['ext'], $a['agent_number'], $a['name'], $a['session_count'], fmt_secs($a['login_sec']), $a['pause_count'], fmt_secs($a['pause_sec']), $a['productive_pct'] !== null ? $a['productive_pct'].'%' : '—', $a['calls'], $a['answered'], $a['avg_aht'], fmt_secs($a['total_talk'])];
            foreach ($cells as $i => $v) $sh->setCellValue(col_letter($i+1) . $row, $v);
            $row++;
        }
        foreach (range('A','L') as $i => $c) $sh->getColumnDimension($c)->setWidth($i < 3 ? 14 : 12);
        $ss->getActiveSheet()->setTitle('Por agente');
    } elseif ($type === 'by_queue') {
        $headers = ['Cola','Descripción','Ofrecidas','Contestadas','Abandonadas','Tasa Aband.%','SL (≤'.$data['sl_threshold'].'s)%','Espera prom.','Máx. espera','AHT'];
        foreach ($headers as $i => $h) $sh->setCellValue(col_letter($i+1) . 4, $h);
        $sh->getStyle('A4:J4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($data['queues'] as $q) {
            $cells = [$q['queue'], $q['descr'] ?? '', $q['offered'], $q['answered'], $q['abandoned'], $q['abandon_rate'].'%', $q['service_level'] !== null ? $q['service_level'].'%' : '—', $q['avg_wait'] ?: '—', $q['max_wait'] ?: '—', $q['avg_talk'] ? fmt_secs($q['avg_talk']) : '—'];
            foreach ($cells as $i => $v) $sh->setCellValue(col_letter($i+1) . $row, $v);
            $row++;
        }
        foreach (range('A','J') as $i => $c) $sh->getColumnDimension($c)->setWidth($i < 2 ? 16 : 13);
        $ss->getActiveSheet()->setTitle('Por cola');
    } elseif ($type === 'calls') {
        $headers = ['Fecha/Hora','Origen','Destino','CallerID','Estado','Duración (s)','Hablado (s)','Grabación'];
        foreach ($headers as $i => $h) $sh->setCellValue(col_letter($i+1) . 4, $h);
        $sh->getStyle('A4:H4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($data['calls'] as $c) {
            $cells = [$c['calldate'], $c['src'], $c['dst'], $c['clid'], $c['disposition'], $c['duration'], $c['billsec'], $c['recordingfile']];
            foreach ($cells as $i => $v) $sh->setCellValue(col_letter($i+1) . $row, $v);
            $row++;
        }
        foreach (range('A','H') as $i => $c) $sh->getColumnDimension($c)->setWidth($i === 0 ? 18 : ($i === 3 ? 22 : ($i === 7 ? 30 : 12)));
        $ss->getActiveSheet()->setTitle('Llamadas');
    } elseif ($type === 'pauses') {
        $headers = ['Agente','Ext','Motivo','Inicio','Fin','Duración'];
        foreach ($headers as $i => $h) $sh->setCellValue(col_letter($i+1) . 4, $h);
        $sh->getStyle('A4:F4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($data['pauses'] as $p) {
            $cells = [$p['agent_number'] ?: '—', $p['agent_ext'], $p['pause_label'], $p['pause_start'], $p['pause_end'] ?: '(activa)', fmt_secs($p['duration_seconds'])];
            foreach ($cells as $i => $v) $sh->setCellValue(col_letter($i+1) . $row, $v);
            $row++;
        }
        foreach (range('A','F') as $i => $c) $sh->getColumnDimension($c)->setWidth($i === 0 ? 10 : ($i === 2 ? 18 : 18));
        $ss->getActiveSheet()->setTitle('Pausas');
    } elseif ($type === 'agent_detail') {
        $info = $data['info']; $k = $data['kpi'];
        $sh->setCellValue('A1', 'Agente '.$info['agent_number'].' — '.($info['name'] ?? '—'));
        $sh->getStyle('A1')->applyFromArray($titleStyle);
        $sh->mergeCells('A1:F1');
        // KPI box
        $sh->setCellValue('A4', 'INDICADOR'); $sh->setCellValue('B4', 'VALOR');
        $sh->getStyle('A4:B4')->applyFromArray($headStyle);
        $rows = [['Sesiones', $k['sessions_count']], ['Login total', fmt_secs($k['total_login_sec'])], ['Pausas', $k['pauses_count']], ['Tiempo pausa', fmt_secs($k['total_pause_sec'])], ['Tiempo productivo', fmt_secs(max(0,$k['total_login_sec']-$k['total_pause_sec']))], ['% Productivo', $k['total_login_sec'] > 0 ? round((max(0,$k['total_login_sec']-$k['total_pause_sec'])) * 100.0 / $k['total_login_sec'], 1).'%' : '—']];
        $row = 5;
        foreach ($rows as $r) { $sh->setCellValue('A'.$row, $r[0]); $sh->setCellValue('B'.$row, $r[1]); $row++; }
        // Sessions sheet
        $sheet2 = $ss->createSheet(); $sheet2->setTitle('Sesiones');
        $hdr = ['Session ID','Ext','Login','Logout','Duración','Llamadas','Talk time','Pause time'];
        foreach ($hdr as $i => $h) $sheet2->setCellValue(col_letter($i+1) . 1, $h);
        $sheet2->getStyle('A1:H1')->applyFromArray($headStyle);
        $r2 = 2;
        foreach ($data['sessions'] as $s) {
            $cells = [$s['session_id'], $s['agent_ext'], $s['login_time'], $s['logout_time'] ?: '(activa)', fmt_secs($s['duration_sec']), $s['total_calls'], fmt_secs($s['total_talk_time']), fmt_secs($s['total_pause_time'])];
            foreach ($cells as $i => $v) $sheet2->setCellValue(col_letter($i+1) . $r2, $v);
            $r2++;
        }
        foreach (range('A','H') as $c) $sheet2->getColumnDimension($c)->setWidth(18);
        // Pauses sheet
        $sheet3 = $ss->createSheet(); $sheet3->setTitle('Pausas');
        $hdr3 = ['Motivo','Inicio','Fin','Duración'];
        foreach ($hdr3 as $i => $h) $sheet3->setCellValue(col_letter($i+1) . 1, $h);
        $sheet3->getStyle('A1:D1')->applyFromArray($headStyle);
        $r3 = 2;
        foreach ($data['pauses'] as $p) {
            $cells = [$p['pause_label'], $p['pause_start'], $p['pause_end'] ?: '(activa)', fmt_secs($p['duration_seconds'])];
            foreach ($cells as $i => $v) $sheet3->setCellValue(col_letter($i+1) . $r3, $v);
            $r3++;
        }
        foreach (range('A','D') as $c) $sheet3->getColumnDimension($c)->setWidth(20);
        $ss->setActiveSheetIndex(0); $sh->setTitle('Resumen');
    } elseif ($type === 'failover_calls') {
        $headers = ['Inicio','Llamante','Cola origen','Cola failover','Motivo','Agente #','Agente nombre','Ext','Espera','Conversación','Estado'];
        foreach ($headers as $i => $h) $sh->setCellValue(col_letter($i+1) . 4, $h);
        $sh->getStyle('A4:K4')->applyFromArray($headStyle);
        $row = 5;
        foreach ($data['failovers'] as $f) {
            $cells = [
                $f['journey_start'], $f['caller_id'] ?: '—',
                $f['source_queue'], $f['failover_queue'], $f['reason'],
                $f['agent_number'] ?: '—', $f['agent_name'] ?: '—', $f['answered_ext'] ?: '—',
                fmt_secs($f['wait_sec']), fmt_secs($f['talk_sec']), $f['status'],
            ];
            foreach ($cells as $i => $v) $sh->setCellValue(col_letter($i+1) . $row, $v);
            $row++;
        }
        $widths = [18, 12, 12, 12, 24, 10, 22, 8, 12, 14, 14];
        foreach ($widths as $i => $w) $sh->getColumnDimension(col_letter($i+1))->setWidth($w);
        $ss->getActiveSheet()->setTitle('Failover');
    }

    $fname = "teleflow_{$type}_{$from_d}_{$to_d}.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    header('Cache-Control: max-age=0');
    $w = new Xlsx($ss);
    $w->save('php://output');
    exit;
}

// ─── PDF ───────────────────────────────────────────────────────────────
if ($format === 'pdf') {
    // Usar TCPDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('TeleFlow Horizon'); $pdf->SetAuthor('TeleFlow Horizon');
    $pdf->SetTitle("TeleFlow Horizon — $type");
    // Header/Footer custom con paleta Horizon
    $pdf->SetHeaderData('', 0, 'TeleFlow Horizon', (['summary'=>'Resumen general','by_agent'=>'Por agente','by_queue'=>'Por cola','calls'=>'Llamadas','pauses'=>'Pausas','failover_calls'=>'Llamadas con failover','agent_detail'=>'Detalle de agente'][$type] ?? ucfirst($type)) ?? ucfirst($type) . "  ·  $from_d → $to_d", [17, 179, 40], [17, 179, 40]);
    $pdf->setHeaderFont(['helvetica', 'B', 10]);
    $pdf->setHeaderMargin(8);
    $pdf->SetFooterData([17, 179, 40], [17, 179, 40]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 15);
    // ─── PÁGINA DE PORTADA (branding Horizon) ────────────────────────
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0); $pdf->SetAutoPageBreak(false);
    $pdf->AddPage('P', 'A4'); // Portada en portrait
    // Fondo: blanco. Banner verde top.
    $pageW = $pdf->getPageWidth(); $pageH = $pdf->getPageHeight();
    // Banner verde Horizon (top)
    $pdf->SetFillColor(17, 179, 40);
    $pdf->Rect(0, 0, $pageW, 90, 'F');
    // Banner negro Horizon (bottom thin)
    $pdf->SetFillColor(26, 26, 26);
    $pdf->Rect(0, $pageH - 14, $pageW, 14, 'F');

    // Logo circular en blanco con TF
    $logoCx = $pageW / 2; $logoCy = 50; $logoR = 22;
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Circle($logoCx, $logoCy, $logoR, 0, 360, 'F');
    $pdf->SetTextColor(17, 179, 40);
    $pdf->SetFont('helvetica', 'B', 28);
    $pdf->SetXY($logoCx - 25, $logoCy - 7);
    $pdf->Cell(50, 14, 'TF', 0, 0, 'C');

    // Titulo
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 32);
    $pdf->SetXY(0, 88);
    $pdf->Cell($pageW, 14, 'TeleFlow', 0, 0, 'C');

    // Subtitulo
    $pdf->SetTextColor(220, 220, 220);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(0, 105);
    $pdf->Cell($pageW, 8, 'Horizon  ·  PBX Control', 0, 0, 'C');

    // Card central
    $cardW = 140; $cardX = ($pageW - $cardW) / 2; $cardY = 135; $cardH = 90;
    $pdf->SetFillColor(248, 248, 248);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->RoundedRect($cardX, $cardY, $cardW, $cardH, 4, '1111', 'DF');

    // Tipo reporte
    $titles = [
        'summary'        => 'Resumen general',
        'by_agent'       => 'Por agente',
        'by_queue'       => 'Por cola',
        'calls'          => 'Llamadas',
        'pauses'         => 'Pausas',
        'failover_calls' => 'Llamadas con failover',
        'agent_detail'   => 'Detalle de agente',
    ];
    $titleLabel = $titles[$type] ?? ucfirst($type);

    $pdf->SetTextColor(17, 179, 40);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY($cardX, $cardY + 8);
    $pdf->Cell($cardW, 10, 'REPORTE', 0, 0, 'C');
    $pdf->SetTextColor(20, 20, 20);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->SetXY($cardX, $cardY + 22);
    $pdf->Cell($cardW, 12, $titleLabel, 0, 0, 'C');

    // Separator line
    $pdf->SetDrawColor(17, 179, 40);
    $pdf->Line($cardX + 30, $cardY + 42, $cardX + $cardW - 30, $cardY + 42);

    // Periodo
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY($cardX, $cardY + 50);
    $pdf->Cell($cardW, 6, 'PERÍODO', 0, 0, 'C');
    $pdf->SetTextColor(20, 20, 20);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY($cardX, $cardY + 58);
    $pdf->Cell($cardW, 7, $from_d . '   →   ' . $to_d, 0, 0, 'C');

    // Generado
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY($cardX, $cardY + 72);
    $pdf->Cell($cardW, 6, 'Generado: ' . date('Y-m-d H:i'), 0, 0, 'C');

    // Footer
    $pdf->SetTextColor(180, 180, 180);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(0, $pageH - 10);
    $pdf->Cell($pageW, 6, 'Horizon Seguridad  ·  TeleFlow PBX Control', 0, 0, 'C');

    // Restaurar margenes para el contenido
    $pdf->setPrintHeader(true); $pdf->setPrintFooter(true);
    $pdf->SetMargins(10, 20, 10); $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Header morado
    $pdf->SetFillColor(139, 92, 246);
    $pdf->Rect(0, 0, 297, 22, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY(10, 5);
    $pdf->Cell(0, 8, 'TeleFlow', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(10);
    $pdf->Cell(0, 5, 'Reporte ' . strtoupper($type) . '  ·  ' . $from_d . ' → ' . $to_d . '  ·  Generado ' . date('Y-m-d H:i'), 0, 1);

    $pdf->SetTextColor(20, 20, 20); $pdf->Ln(8);
    $pdf->SetFont('helvetica', '', 9);

    if ($type === 'summary') {
        $k = $data['kpis']; $sess = $data['sessions']; $p = $data['pauses'];
        // KPI cards
        $cards = [
            ['Total', number_format((int)$k['total']), [17,179,40]],
            ['Contestadas', number_format((int)$k['answered']) . ' ('.$k['answer_rate'].'%)', [34,197,94]],
            ['Abandono', $k['abandon_rate'].'%', [239,68,68]],
            ['AHT', $k['avg_billsec'].'s', [59,130,246]],
            ['Talk total', fmt_secs($k['total_talk_seconds']), [236,72,153]],
            ['Pausa total', fmt_secs($p['total_pause_sec']), [245,158,11]],
        ];
        $cw = 45; $x = 10; $y = 30;
        foreach ($cards as $c) {
            $pdf->SetFillColor($c[2][0], $c[2][1], $c[2][2]);
            $pdf->RoundedRect($x, $y, $cw, 18, 2, '1111', 'F');
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($x+2, $y+2); $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($cw-4, 4, strtoupper($c[0]), 0, 1);
            $pdf->SetXY($x+2, $y+8); $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell($cw-4, 8, $c[1], 0, 1);
            $x += $cw + 2;
        }
        $pdf->SetTextColor(20, 20, 20); $pdf->SetY(56);

        // Tabla con tooltip de actividad
        $pdf->SetFont('helvetica', 'B', 11); $pdf->Cell(0, 6, 'Indicadores adicionales', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $rows = [
            ['Sesiones de agentes', $sess['sessions']],
            ['Agentes únicos', $sess['unique_agents']],
            ['Tiempo total logueado', fmt_secs($sess['total_login_sec'])],
            ['Pausas totales', $p['total_pauses']],
            ['Sin respuesta / Ocupado / Falladas', $k['no_answer'] . ' / ' . $k['busy'] . ' / ' . $k['failed']],
            ['Espera promedio', $k['avg_wait'] . 's'],
        ];
        foreach ($rows as $r) {
            $pdf->SetFillColor(243, 244, 246);
            $pdf->Cell(70, 6, $r[0], 1, 0, '', true);
            $pdf->Cell(50, 6, $r[1], 1, 1);
        }
    } elseif ($type === 'by_agent') {
        $headers = [['Ext',12],['Agente',14],['Nombre',45],['Ses.',10],['Login',18],['Pausas',12],['T.Pausa',18],['Prod.%',12],['Llam.',12],['Contest',14],['AHT',10],['Talk',16]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($headers as $h) $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8);
        $alt = false;
        foreach ($data['agents'] as $a) {
            $alt = !$alt;
            $pdf->SetFillColor($alt ? 243 : 255, $alt ? 244 : 255, $alt ? 246 : 255);
            $vals = [$a['ext'], $a['agent_number'] ?: '—', mb_substr($a['name'] ?: '—', 0, 32), $a['session_count'], fmt_secs($a['login_sec']), $a['pause_count'], fmt_secs($a['pause_sec']), $a['productive_pct'] !== null ? $a['productive_pct'].'%' : '—', $a['calls'], $a['answered'], $a['avg_aht'] ?? '—', fmt_secs($a['total_talk'])];
            foreach ($vals as $i => $v) $pdf->Cell($headers[$i][1], 6, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
    } elseif ($type === 'by_queue') {
        $headers = [['Cola',16],['Descripción',58],['Ofrec.',14],['Contest.',16],['Aband.',14],['Aband.%',14],['SL%',12],['Esp.prom.',18],['Máx.',14],['AHT',14]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($headers as $h) $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8);
        $alt = false;
        foreach ($data['queues'] as $q) {
            $alt = !$alt;
            $vals = [$q['queue'], mb_substr($q['descr'] ?? '—', 0, 42), $q['offered'], $q['answered'], $q['abandoned'], $q['abandon_rate'].'%', $q['service_level'] !== null ? $q['service_level'].'%' : '—', $q['avg_wait'] ? $q['avg_wait'].'s' : '—', $q['max_wait'] ? $q['max_wait'].'s' : '—', $q['avg_talk'] ? fmt_secs($q['avg_talk']) : '—'];
            foreach ($vals as $i => $v) $pdf->Cell($headers[$i][1], 6, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
    } elseif ($type === 'calls') {
        $headers = [['Fecha/Hora',32],['Origen',20],['Destino',20],['CallerID',58],['Estado',24],['Dur.',16],['Hablado',18],['Grabación',8]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($headers as $h) $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8);
        $alt = false;
        // Limitamos PDF a 1000 filas para no hacer gigantes
        foreach (array_slice($data['calls'], 0, 1000) as $c) {
            $alt = !$alt;
            $vals = [$c['calldate'], $c['src'], $c['dst'], mb_substr($c['clid'] ?? '', 0, 42), $c['disposition'], $c['duration'].'s', $c['billsec'].'s', $c['recordingfile'] ? '✓' : '—'];
            foreach ($vals as $i => $v) $pdf->Cell($headers[$i][1], 5, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
        if (count($data['calls']) > 1000) {
            $pdf->Ln(3); $pdf->SetFont('helvetica','I',8);
            $pdf->Cell(0, 6, "Mostradas las 1000 primeras filas de " . count($data['calls']) . " totales. Usá Excel para el listado completo.", 0, 1);
        }
    } elseif ($type === 'pauses') {
        $headers = [['Agente',16],['Ext',16],['Motivo',32],['Inicio',38],['Fin',38],['Duración',24]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($headers as $h) $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8);
        $alt = false;
        foreach (array_slice($data['pauses'], 0, 1000) as $p) {
            $alt = !$alt;
            $vals = [$p['agent_number'] ?: '—', $p['agent_ext'], $p['pause_label'], $p['pause_start'], $p['pause_end'] ?: '(activa)', fmt_secs($p['duration_seconds'])];
            foreach ($vals as $i => $v) $pdf->Cell($headers[$i][1], 6, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
    } elseif ($type === 'agent_detail') {
        $info = $data['info']; $k = $data['kpi'];
        $pdf->SetFont('helvetica','B',14);
        $pdf->Cell(0, 8, 'Agente ' . $info['agent_number'] . ' — ' . ($info['name'] ?? '—'), 0, 1);
        $pdf->SetFont('helvetica','',9); $pdf->Ln(2);

        $pmins = $k['total_login_sec'] > 0 ? round(max(0, $k['total_login_sec']-$k['total_pause_sec']) * 100.0 / $k['total_login_sec'], 1) : null;
        $cards = [
            ['Sesiones', $k['sessions_count'], [17,179,40]],
            ['Login total', fmt_secs($k['total_login_sec']), [59,130,246]],
            ['Pausas', $k['pauses_count'], [245,158,11]],
            ['T. Pausa', fmt_secs($k['total_pause_sec']), [239,68,68]],
            ['Productivo', $pmins !== null ? $pmins.'%' : '—', [34,197,94]],
            ['Llamadas', $k['total_calls'], [236,72,153]],
        ];
        $cw = 45; $x = 10; $y = $pdf->GetY();
        foreach ($cards as $c) {
            $pdf->SetFillColor($c[2][0], $c[2][1], $c[2][2]);
            $pdf->RoundedRect($x, $y, $cw, 18, 2, '1111', 'F');
            $pdf->SetTextColor(255,255,255);
            $pdf->SetXY($x+2, $y+2); $pdf->SetFont('helvetica','B',8);
            $pdf->Cell($cw-4, 4, strtoupper($c[0]), 0, 1);
            $pdf->SetXY($x+2, $y+8); $pdf->SetFont('helvetica','B',12);
            $pdf->Cell($cw-4, 8, (string)$c[1], 0, 1);
            $x += $cw + 2;
        }
        $pdf->SetTextColor(20,20,20); $pdf->SetY($y + 22); $pdf->Ln(2);

        // Tabla de sesiones
        $pdf->SetFont('helvetica','B',10); $pdf->Cell(0, 6, 'Sesiones', 0, 1);
        $hs = [['Login',38],['Logout',38],['Ext',16],['Estado',22],['Duración',24],['Llam.',20],['Talk',24]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($hs as $h) $pdf->Cell($h[1], 6, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8); $alt = false;
        foreach ($data['sessions'] as $s) {
            $alt = !$alt;
            $vals = [$s['login_time'], $s['logout_time'] ?: '(activa)', $s['agent_ext'], $s['status'], fmt_secs($s['duration_sec']), $s['total_calls'], fmt_secs($s['total_talk_time'])];
            foreach ($vals as $i => $v) $pdf->Cell($hs[$i][1], 5, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }

        $pdf->Ln(4);
        $pdf->SetFont('helvetica','B',10); $pdf->Cell(0, 6, 'Pausas', 0, 1);
        $hp = [['Motivo',32],['Inicio',38],['Fin',38],['Duración',24]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($hp as $h) $pdf->Cell($h[1], 6, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',8); $alt = false;
        foreach ($data['pauses'] as $p) {
            $alt = !$alt;
            $vals = [$p['pause_label'], $p['pause_start'], $p['pause_end'] ?: '(activa)', fmt_secs($p['duration_seconds'])];
            foreach ($vals as $i => $v) $pdf->Cell($hp[$i][1], 5, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
    } elseif ($type === 'failover_calls') {
        $pdf->SetFont('helvetica','B',12);
        $pdf->Cell(0, 8, 'Llamadas con failover', 0, 1);
        $pdf->SetFont('helvetica','',8);
        $headers = [['Inicio',32],['Llamante',20],['Origen',16],['Failover',16],['Motivo',38],['Agente',26],['Ext',12],['Espera',16],['Convers.',20],['Estado',22]];
        $pdf->SetFillColor(17,179,40); $pdf->SetTextColor(255,255,255); $pdf->SetFont('helvetica','B',8);
        foreach ($headers as $h) $pdf->Cell($h[1], 7, $h[0], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('helvetica','',7);
        $alt = false;
        foreach (array_slice($data['failovers'], 0, 2000) as $f) {
            $alt = !$alt;
            $ag = $f['agent_number'] ? ('#'.$f['agent_number'].' '.($f['agent_name'] ?? '')) : '—';
            $vals = [
                substr($f['journey_start'], 0, 19),
                $f['caller_id'] ?: '—',
                $f['source_queue'], $f['failover_queue'], $f['reason'],
                $ag, $f['answered_ext'] ?: '—',
                fmt_secs($f['wait_sec']), fmt_secs($f['talk_sec']), $f['status'],
            ];
            foreach ($vals as $i => $v) $pdf->Cell($headers[$i][1], 6, (string)$v, 1, 0, 'C', $alt);
            $pdf->Ln();
        }
    }

    $fname = "teleflow_{$type}_{$from_d}_{$to_d}.pdf";
    $pdf->Output($fname, 'D');
    exit;
}

http_response_code(400);
echo 'Formato inválido';
