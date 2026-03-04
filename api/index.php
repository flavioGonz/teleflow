<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_full_data') {
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
        if (file_exists("../uploads/avatars/$ext.jpg")) $avatar = "uploads/avatars/$ext.jpg?".time();
        $exts[$ext] = ['ext'=>$ext, 'name'=>trim($m[2]), 'status'=>'OFFLINE', 'ip'=>'---', 'rtt'=>'---', 'avatar'=>$avatar];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { 
        if(isset($exts[$m[1]])){ $exts[$m[1]]['status']='ONLINE'; $exts[$m[1]]['ip']=$m[2]; $exts[$m[1]]['rtt']=round($m[4]).'ms'; } 
    }

    $active_calls = [];
    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { $exts[$m[1]]['status'] = 'BUSY'; $exts[$m[1]]['live_time'] = $m[4]; }
        $active_calls[] = ['from' => $m[1], 'to' => $m[3], 'duration' => $m[4]];
    }

    // Colas
    $queues = [];
    preg_match_all('/([\w\-]+)\s+has\s+(\d+)\s+calls/', $queues_raw, $m_q, PREG_SET_ORDER);
    foreach ($m_q as $q) { $queues[] = ['name' => $q[1], 'waiting' => (int)$q[2]]; }

    // Grabaciones (Clon CDR moderno para CallCenter)
    $recordings = [];
    try {
        $db = new PDO('mysql:host=localhost;dbname=asteriskcdrdb', 'root', 'Sildan.1329');
        $recordings = $db->query("SELECT calldate, clid, src, dst, duration, recordingfile, disposition FROM cdr WHERE recordingfile != '' ORDER BY calldate DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'calls' => $active_calls, 'recordings' => $recordings, 'queues' => $queues],
        'summary' => ['queue' => count($queues), 'wait' => '0:45', 'abandon' => '2.4%']
    ]);
    exit;
}
?>
