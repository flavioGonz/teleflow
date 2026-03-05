<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$avatar_dir = "../uploads/avatars/";

if ($action == 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $db = new SQLite3('/var/www/db/acl.db');
    $stmt = $db->prepare('SELECT name, md5_password FROM acl_user WHERE name = :u');
    $stmt->bindValue(':u', $user);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    if ($row && md5($pass) === $row['md5_password']) {
        $_SESSION['tf_user'] = $row['name'];
        echo json_encode(['status' => 'success', 'user' => $row['name']]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas']);
    }
    exit;
}

if ($action == 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

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
    
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $ext = $m[1];
        $avatar = "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff";
        if (file_exists($avatar_dir.$ext.".jpg")) $avatar = "uploads/avatars/$ext.jpg?".time();
        $exts[$ext] = ['ext'=>$ext, 'name'=>trim($m[2]), 'status'=>'OFFLINE', 'ip'=>'---', 'rtt'=>'---', 'avatar'=>$avatar];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { if(isset($exts[$m[1]])){ $exts[$m[1]]['status']='ONLINE'; $exts[$m[1]]['ip']=$m[2]; $exts[$m[1]]['rtt']=round($m[4]).'ms'; } }

    $recordings = [];
    try {
        $db = new PDO('mysql:host=localhost;dbname=asteriskcdrdb', 'root', 'Sildan.1329');
        $recordings = $db->query("SELECT calldate, src, dst, duration, recordingfile, disposition FROM cdr WHERE recordingfile != '' ORDER BY calldate DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'uptime' => shell_exec("uptime -p")],
        'pbx' => ['extensions' => array_values($exts), 'calls' => [], 'recordings' => $recordings, 'queues' => []]
    ]);
    exit;
}
?>
