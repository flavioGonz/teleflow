<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';
$avatar_dir = "../uploads/avatars/";

if ($action == 'login') {
    $user = $_POST['username'] ?? ''; $pass = $_POST['password'] ?? '';
    $db = new SQLite3('/var/www/db/acl.db');
    $stmt = $db->prepare('SELECT name FROM acl_user WHERE name = :u AND md5_password = :p');
    $stmt->bindValue(':u', $user); $stmt->bindValue(':p', md5($pass));
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row) { $_SESSION['tf_user'] = $row['name']; echo json_encode(['status' => 'success']); }
    else { http_response_code(401); echo json_encode(['status' => 'error']); }
    exit;
}

// --- ACTUALIZAR EXTENSIÓN ---
if ($action == 'update_extension') {
    $ext = $_POST['ext'] ?? '';
    $new_name = $_POST['name'] ?? '';
    
    if ($ext && $new_name) {
        // En Issabel, el nombre de la extensión se guarda en la base de datos asterisk (MySQL)
        // y también en la base de datos interna de Asterisk (AstDB)
        try {
            $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
            $stmt = $db->prepare("UPDATE users SET name = :n WHERE extension = :e");
            $stmt->execute([':n' => $new_name, ':e' => $ext]);
            
            // Sincronizar con Asterisk DB para que el cambio sea inmediato en los teléfonos
            shell_exec("/usr/sbin/asterisk -rx 'database put cidname $ext \"$new_name\"'");
            
            // Flag de cambios pendientes
            touch('/tmp/tf_pending');
            
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    exit;
}

if ($action == 'get_full_data') {
    $load = sys_getloadavg();
    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    $queues_raw = shell_exec("/usr/sbin/asterisk -rx 'queue show'");
    
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

    $active_calls = [];
    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { $exts[$m[1]]['status'] = 'BUSY'; }
        $active_calls[] = ['from' => $m[1], 'to' => $m[3], 'duration' => $m[4]];
    }

    $queues = [];
    preg_match_all('/([\w\-]+)\s+has\s+(\d+)\s+calls/', $queues_raw, $m_q, PREG_SET_ORDER);
    foreach ($m_q as $q) { $queues[] = ['name' => $q[1], 'waiting' => (int)$q[2]]; }

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'calls' => $active_calls, 'queues' => $queues],
        'pending' => file_exists('/tmp/tf_pending')
    ]);
    exit;
}
?>
