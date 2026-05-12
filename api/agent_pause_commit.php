<?php
ignore_user_abort(true); set_time_limit(15);
header('Content-Type: application/json');

$ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
$reason_code = preg_replace('/\D/', '', $_GET['reason'] ?? '');
if (!$ext) { http_response_code(400); echo '{"ok":false}'; exit; }

$reason_map = ['1'=>'DESCANSO','2'=>'BATHROOM','3'=>'MEETING','4'=>'TRAINING','0'=>'PERSONAL'];
$reason_label = $reason_map[$reason_code] ?? 'PERSONAL';

require __DIR__ . '/../config.php';
$logf = '/tmp/teleflow_agent_commit.log';
function tflog($m) { global $logf; @file_put_contents($logf, '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }
tflog("pause ext=$ext reason=$reason_label");

$ami = @fsockopen($AMI_HOST, $AMI_PORT, $en, $es, 3);
if (!$ami) { echo '{"ok":false,"error":"ami"}'; exit; }
stream_set_timeout($ami, 4); fgets($ami);
fwrite($ami, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
$st=microtime(true); while(microtime(true)-$st<2){$l=fgets($ami);if(!$l)break;if(strpos($l,'Authentication accepted')!==false)break;}

fwrite($ami, "Action: QueueStatus\r\n\r\n");
$members=[]; $cur=[]; $st=microtime(true);
while(microtime(true)-$st<5){$l=fgets($ami);if($l===false)break;$l=rtrim($l,"\r\n");
    if($l===''){if(!empty($cur)&&($cur['event']??'')==='QueueMember')$members[]=$cur;$cur=[];continue;}
    if(stripos($l,'QueueStatusComplete')!==false)break;
    if(preg_match('/^([A-Za-z]+):\s*(.*)$/',$l,$m))$cur[strtolower($m[1])]=$m[2];
}
$queues=[]; $agent_num=null; $found=false;
foreach($members as $m){
    if(preg_match('/^(SIP|PJSIP)\/'.preg_quote($ext,'/').'$/',$m['location']??'')){
        $queues[]=$m['queue']; $found=true;
        if(preg_match('/^Agent\/(\d+)$/',$m['name']??'',$am))$agent_num=$am[1];
    }
}

if (!$found) { tflog("not_logged_in"); echo '{"ok":false,"error":"not_logged_in"}'; exit; }

$paused = [];
foreach (array_unique($queues) as $q) {
    fwrite($ami, "Action: QueuePause\r\nInterface: SIP/$ext\r\nPaused: true\r\nQueue: $q\r\nReason: $reason_label\r\n\r\n");
    $st=microtime(true); $resp='';
    while(microtime(true)-$st<1){$l=fgets($ami);if($l===false)break;$resp.=$l;if(trim($l)==='')break;}
    if(strpos($resp,'Response: Success')!==false||strpos($resp,'paused')!==false) $paused[]=$q;
}
fwrite($ami, "Action: Logoff\r\n\r\n"); fclose($ami);

// Pesistir con columnas CORRECTAS del schema
try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    // Get session_id activo del ext
    $sid = null;
    try {
        $st = $tf->prepare("SELECT session_id FROM agent_sessions WHERE agent_ext=? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
        $st->execute([$ext]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r) $sid = $r['session_id'];
    } catch (Exception $e) {}
    $tf->prepare("INSERT INTO agent_pauses (agent_ext, agent_number, pause_type_code, pause_start, session_id) VALUES (?, ?, ?, NOW(), ?)")
       ->execute([$ext, $agent_num, $reason_label, $sid]);
} catch (Exception $e) { tflog("db pause insert fail: ".$e->getMessage()); }

@unlink('/tmp/teleflow_queue_show.txt');
@unlink('/tmp/teleflow_queue_status.json');
@file_get_contents('http://127.0.0.1/api/notify.php?event=agent_pause&ext='.urlencode($ext).'&reason='.urlencode($reason_label).'&agent='.urlencode($agent_num??''));

tflog("pause OK ext=$ext queues=".implode(',',$paused)." reason=$reason_label");
echo json_encode(['ok'=>true,'reason'=>$reason_label,'queues'=>$paused]);
