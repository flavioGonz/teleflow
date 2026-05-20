<?php
ignore_user_abort(true);
set_time_limit(15);
header('Content-Type: application/json');

$agent_number = preg_replace('/\D/', '', $_GET['agent'] ?? '');
$pass         = $_GET['pass'] ?? '';
$callback_ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
// $queue puede venir como:
//   - número de cola directo: "8000"
//   - patrón de atajos via *7700*N: "1", "1*2", "1*2*3"
//   - múltiples colas crudas separadas: "8000*8001"
// Sanitizamos preservando * para luego parsear.
$queue_raw    = preg_replace('/[^0-9*]/', '', $_GET['queue'] ?? '');

if (!$agent_number || !$callback_ext) { http_response_code(400); echo '{"ok":false}'; exit; }

require __DIR__ . '/../config.php';
$logf = '/tmp/teleflow_agent_commit.log';
function tflog($m) { global $logf; @file_put_contents($logf, '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }
tflog("commit agent=$agent_number ext=$callback_ext queue_raw=$queue_raw");

try {
    $cc = new PDO("mysql:host=$DB_HOST;dbname=call_center;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4",   $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { tflog('db_fail'); echo '{"ok":false}'; exit; }

$st = $cc->prepare("SELECT id, password FROM agent WHERE number = ? AND estatus = 'A'");
$st->execute([$agent_number]);
$ag = $st->fetch(PDO::FETCH_ASSOC);
if (!$ag || $ag['password'] !== $pass) { tflog('auth_fail'); echo '{"ok":false}'; exit; }

$queues = [];

// ─── Resolución del parámetro queue (atajos *7700*N*M*K) ───
if ($queue_raw !== '') {
    // 1) Cargar mapeo de atajos (digit→queue) de la tabla queue_shortcut
    $shortcuts = [];
    try {
        $rs = $tf->query("SELECT digit, queue FROM queue_shortcut WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rs as $r) $shortcuts[(int)$r['digit']] = $r['queue'];
    } catch (Exception $e) { tflog('shortcut_load_fail: ' . $e->getMessage()); }

    // 2) Split por '*' y resolver cada token
    $tokens = array_filter(explode('*', $queue_raw), fn($x) => $x !== '');
    $resolved = [];
    foreach ($tokens as $t) {
        if (strlen($t) === 1 && ctype_digit($t) && isset($shortcuts[(int)$t])) {
            // Atajo: dígito único mapeado vía queue_shortcut
            $resolved[] = $shortcuts[(int)$t];
        } elseif (ctype_digit($t) && strlen($t) >= 2) {
            // Token largo: tratarlo como queue ID literal (compat con *7700*8000)
            $resolved[] = $t;
        } else {
            tflog("skip token: $t (no mapeado y no es queue válida)");
        }
    }
    $resolved = array_values(array_unique($resolved));
    foreach ($resolved as $q) $queues[] = ['queue' => $q, 'penalty' => 0];
    tflog("queue_raw='$queue_raw' resolved=[" . implode(',', $resolved) . "]");
}

// Si no vino queue o no se pudo resolver nada → fallback a prefs guardadas
if (empty($queues)) {
    try {
        $st = $tf->prepare("SELECT queue, penalty FROM agent_queue_pref WHERE agent_number = ?");
        $st->execute([$agent_number]);
        $queues = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    if (empty($queues)) {
        try {
            $rows = $cc->query("SELECT DISTINCT queue FROM queue_call_entry WHERE estatus='A'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $q) $queues[] = ['queue'=>$q,'penalty'=>0];
        } catch (Exception $e) {}
    }
}
if (empty($queues)) { tflog('no_queues'); echo '{"ok":false}'; exit; }

$ami = @fsockopen($AMI_HOST, $AMI_PORT, $en, $es, 3);
if (!$ami) { tflog('ami_fail'); echo '{"ok":false}'; exit; }
stream_set_timeout($ami, 4);
fgets($ami);
fwrite($ami, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
$st=microtime(true); while(microtime(true)-$st<2){$l=fgets($ami);if(!$l)break;if(strpos($l,'Authentication accepted')!==false)break;}

fwrite($ami, "Action: QueueStatus\r\n\r\n");
$members=[]; $cur=[]; $st=microtime(true);
while(microtime(true)-$st<5){$l=fgets($ami);if($l===false)break;$l=rtrim($l,"\r\n");
    if($l===''){if(!empty($cur)&&($cur['event']??'')==='QueueMember')$members[]=$cur;$cur=[];continue;}
    if(stripos($l,'QueueStatusComplete')!==false)break;
    if(preg_match('/^([A-Za-z]+):\s*(.*)$/',$l,$m))$cur[strtolower($m[1])]=$m[2];
}
$previous_queues = [];
foreach ($members as $m) {
    if (preg_match('/^(SIP|PJSIP)\/'.preg_quote($callback_ext,'/').'$/', $m['location']??'') ||
        ($m['name']??'') === "Agent/$agent_number") {
        $previous_queues[] = $m['queue'];
    }
}
foreach (array_unique($previous_queues) as $pq) {
    fwrite($ami, "Action: QueueRemove\r\nQueue: $pq\r\nInterface: SIP/$callback_ext\r\n\r\n"); usleep(80000);
}

$added = [];
foreach ($queues as $q) {
    $qid = $q['queue']; $pen = (int)$q['penalty'];
    $msg  = "Action: QueueAdd\r\nQueue: $qid\r\nInterface: SIP/$callback_ext\r\nMemberName: Agent/$agent_number\r\nStateInterface: SIP/$callback_ext\r\nPenalty: $pen\r\n\r\n";
    fwrite($ami, $msg);
    $st=microtime(true); $resp='';
    while(microtime(true)-$st<1){$l=fgets($ami);if($l===false)break;$resp.=$l;if(trim($l)==='')break;}
    if (strpos($resp,'Response: Success')!==false || strpos($resp,'Added')!==false) $added[] = $qid;
}
fwrite($ami, "Action: Logoff\r\n\r\n"); fclose($ami);

try {
    $sid = uniqid('s_',true); $today = date('Y-m-d');
    $tf->prepare("INSERT INTO agent_sessions (session_id, agent_ext, agent_number, login_time, shift_date, status) VALUES (?, ?, ?, NOW(), ?, 'ACTIVE')")
       ->execute([$sid, $callback_ext, $agent_number, $today]);
} catch (Exception $e) { tflog("session insert fail: ".$e->getMessage()); }

@unlink('/tmp/teleflow_queue_show.txt');
@unlink('/tmp/teleflow_queue_status.json');
@file_get_contents('http://127.0.0.1/api/notify.php?event=agent_login&agent='.urlencode($agent_number).'&ext='.urlencode($callback_ext).'&queues='.urlencode(implode(',',$added)));

tflog("commit OK agent=$agent_number ext=$callback_ext queues=".implode(',',$added));
echo json_encode(['ok'=>true,'queues'=>$added]);
