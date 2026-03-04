<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';

if ($action == 'get_full_data') {
    // Sistema
    $load = sys_getloadavg();
    $mem_raw = shell_exec("free -m");
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $mem_raw, $m_mem);

    // Asterisk Core
    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    $trunks_raw = shell_exec("/usr/sbin/asterisk -rx 'pjsip show registrations'");
    
    // Parseo Extensiones
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $exts[$m[1]] = [
            'id' => $m[1], 'ext' => $m[1], 'name' => trim($m[2]), 'status' => 'OFFLINE', 
            'ip' => '---', 'rtt' => '---', 'type' => 'PJSIP', 'avatar' => "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff"
        ];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { if(isset($exts[$m[1]])){ $exts[$m[1]]['status']='ONLINE'; $exts[$m[1]]['ip']=$m[2]; $exts[$m[1]]['rtt']=round($m[4]).'ms'; } }

    // Parseo Troncales
    $trunks = [];
    preg_match_all('/([\w\-]+)\s+.*?\s+.*?\s+.*?\s+(\w+)/', $trunks_raw, $m_t, PREG_SET_ORDER);
    foreach ($m_t as $m) {
        $trunks[] = ['id' => $m[1], 'name' => $m[1], 'status' => strtolower($m[2])];
    }

    // Llamadas Activas
    $active_calls = [];
    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { $exts[$m[1]]['status'] = 'BUSY'; }
        $active_calls[] = ['from' => $m[1], 'to' => $m[3], 'duration' => $m[4]];
    }

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'ram' => round(($m_mem[2]/$m_mem[1])*100), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'trunks' => $trunks, 'calls' => $active_calls],
        'timestamp' => time()
    ]);
    exit;
}
?>
