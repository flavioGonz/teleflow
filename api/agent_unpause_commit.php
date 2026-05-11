<?php
ignore_user_abort(true); set_time_limit(15);
header('Content-Type: application/json');
$ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
if (!$ext) { http_response_code(400); echo '{"ok":false}'; exit; }
require __DIR__ . '/../config.php';
$logf = '/tmp/teleflow_agent_commit.log';
function tflog($m) { global $logf; @file_put_contents($logf, '['.date('Y-m-d H:i:s').'] '.$m."\n", FILE_APPEND | LOCK_EX); }
tflog("unpause ext=$ext");

$ami = @fsockopen($AMI_HOST, $AMI_PORT, $en, $es, 3);
if (!$ami) { echo '{"ok":false}'; exit; }
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
$queues=[]; $agent_num=null;
foreach($members as $m){
    if(preg_match('/^(SIP|PJSIP)\/'.preg_quote($ext,'/').'$/',$m['location']??'')){
        $queues[]=$m['queue'];
        if(preg_match('/^Agent\/(\d+)$/',$m['name']??'',$am))$agent_num=$am[1];
    }
}

$unpaused = [];
foreach (array_unique($queues) as $q) {
    fwrite($ami, "Action: QueuePause\r\nInterface: SIP/$ext\r\nPaused: false\r\nQueue: $q\r\n\r\n");
    $st=microtime(true); $resp='';
    while(microtime(true)-$st<1){$l=fgets($ami);if($l===false)break;$resp.=$l;if(trim($l)==='')break;}
    if(strpos($resp,'Response: Success')!==false) $unpaused[]=$q;
}
fwrite($ami, "Action: Logoff\r\n\r\n"); fclose($ami);

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    // Update last open pause for this ext: set pause_end + duration
    $tf->prepare("UPDATE agent_pauses SET pause_end=NOW(), duration_seconds=TIMESTAMPDIFF(SECOND, pause_start, NOW()) WHERE agent_ext=? AND pause_end IS NULL")->execute([$ext]);
} catch (Exception $e) {}

@unlink('/tmp/teleflow_queue_show.txt');
@unlink('/tmp/teleflow_queue_status.json');
@file_get_contents('http://127.0.0.1/api/notify.php?event=agent_unpause&ext='.urlencode($ext).'&agent='.urlencode($agent_num??''));

tflog("unpause OK ext=$ext queues=".implode(',',$unpaused));
echo json_encode(['ok'=>true,'queues'=>$unpaused]);
