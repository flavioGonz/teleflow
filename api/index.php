<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// --- LOGIN BRIDGE ---
if ($action == 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $db = new SQLite3('/var/www/db/acl.db');
    $stmt = $db->prepare('SELECT name FROM acl_user WHERE name = :u AND md5_password = :p');
    $stmt->bindValue(':u', $user);
    $stmt->bindValue(':p', md5($pass));
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $_SESSION['tf_user'] = $row['name'];
        echo json_encode(['status' => 'success', 'user' => $row['name']]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas']);
    }
    exit;
}

if ($action == 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

// --- PROTECCIÓN DE DATOS ---
if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($action == 'get_full_data') {
    $load = sys_getloadavg();
    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    $queues_raw = shell_exec("/usr/sbin/asterisk -rx 'queue show'");
    
    // Extensiones
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $exts[$m[1]] = [
            'ext' => $m[1], 'name' => trim($m[2]), 'status' => 'OFFLINE', 
            'ip' => '---', 'rtt' => '---', 'avatar' => "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff"
        ];
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

    // Colas detalladas para CallCenter
    $queues = [];
    if (preg_match_all('/([\w\-]+)\s+has\s+(\d+)\s+calls.*?Strategy:\s+(\w+).*?Members:(.*?)No\s+Callers/s', $queues_raw, $m_q, PREG_SET_ORDER)) {
        foreach ($m_q as $q) {
            $queues[] = ['name' => $q[1], 'waiting' => (int)$q[2], 'strategy' => $q[3]];
        }
    }

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'calls' => $active_calls, 'queues' => $queues],
        'summary' => ['queue' => count($queues), 'wait' => '0:45', 'abandon' => '2.4%']
    ]);
    exit;
}
?>
