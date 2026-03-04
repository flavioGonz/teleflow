<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';
$avatar_dir = "../uploads/avatars/";

if ($action === 'get_full_data') {
    // Sistema y Asterisk
    $load = sys_getloadavg();
    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    $queues_raw = shell_exec("/usr/sbin/asterisk -rx 'queue show'");
    
    // Extensiones
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $ext = $m[1];
        $avatar = "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff";
        if (file_exists($avatar_dir.$ext.".jpg")) $avatar = "uploads/avatars/$ext.jpg?".time();
        $exts[$ext] = ['ext'=>$ext, 'name'=>trim($m[2]), 'status'=>'OFFLINE', 'ip'=>'---', 'rtt'=>'---', 'avatar'=>$avatar];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { 
        if(isset($exts[$m[1]])){ $exts[$m[1]]['status']='ONLINE'; $exts[$m[1]]['ip']=$m[2]; $exts[$m[1]]['rtt']=round($m[4]).'ms'; } 
    }

    // Llamadas Activas
    $active_calls = [];
    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { $exts[$m[1]]['status'] = 'BUSY'; }
        $active_calls[] = ['from' => $m[1], 'to' => $m[3], 'duration' => $m[4]];
    }

    // Procesar Colas de Llamadas
    $queues = [];
    $q_blocks = explode("\n\n", $queues_raw);
    foreach ($q_blocks as $block) {
        if (preg_match('/^([\w\-]+)\s+has\s+(\d+)\s+calls.*?Strategy:\s+(\w+)/s', $block, $m_q)) {
            $members = [];
            if (preg_match_all('/\s+(.*?)\s+\((.*?)\)\s+\((.*?)\)\s+has\s+taken\s+(\d+)\s+calls/', $block, $m_m, PREG_SET_ORDER)) {
                foreach ($m_m as $m) {
                    $members[] = ['name' => $m[1], 'tech' => $m[2], 'status' => $m[3], 'calls' => $m[4]];
                }
            }
            $queues[] = ['name' => $m_q[1], 'waiting' => (int)$m_q[2], 'strategy' => $m_q[3], 'members' => $members];
        }
    }

    // Grabaciones (CDR)
    $recordings = [];
    try {
        $db = new PDO('mysql:host=localhost;dbname=asteriskcdrdb', 'root', 'Sildan.1329');
        $stmt = $db->query("SELECT calldate, clid, src, dst, duration, recordingfile FROM cdr WHERE recordingfile != '' ORDER BY calldate DESC LIMIT 15");
        $recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'calls' => $active_calls, 'queues' => $queues, 'recordings' => $recordings],
        'summary' => ['queue' => count($queues), 'wait' => '0:45', 'abandon' => '2.4%']
    ]);
    exit;
}
?>
