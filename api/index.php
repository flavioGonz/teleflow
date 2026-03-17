<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action     = $_GET['action'] ?? '';
$avatar_dir = '../uploads/avatars/';
if (file_exists(__DIR__ . '/../config.php')) {
    include __DIR__ . '/../config.php';
} else {
    $DB_PASS = ''; // Fallback if config.php is missing
}

// ─── AUTH ───────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    try {
        $db   = new SQLite3('/var/www/db/acl.db');
        $stmt = $db->prepare('SELECT name,md5_password FROM acl_user WHERE name=:u');
        $stmt->bindValue(':u', $user);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ($row && md5($pass) === $row['md5_password']) {
            $_SESSION['tf_user'] = $row['name'];
            echo json_encode(['status' => 'success', 'user' => $row['name']]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

if (!isset($_SESSION['tf_user']) && !in_array($action, ['get_agents_data', 'upload_avatar'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($action === 'set_sip_debug') {
    $level = $_POST['level'] ?? 'off'; 
    $ext   = $_POST['ext'] ?? ''; // Opcional: filtrar por interno en el logger
    
    if ($level === 'on') {
        shell_exec("/usr/sbin/asterisk -rx 'pjsip set logger on'");
        if ($ext) shell_exec("/usr/sbin/asterisk -rx 'pjsip set logger host $ext'");
        shell_exec("/usr/sbin/asterisk -rx 'core set verbose 6'");
        shell_exec("/usr/sbin/asterisk -rx 'core set debug 5'");
        echo json_encode(['success' => true, 'msg' => 'PJSIP Logger Activado (Verbose 6 + Debug 5)']);
    } else {
        shell_exec("/usr/sbin/asterisk -rx 'pjsip set logger off'");
        shell_exec("/usr/sbin/asterisk -rx 'core set verbose 3'");
        shell_exec("/usr/sbin/asterisk -rx 'core set debug 0'");
        echo json_encode(['success' => true, 'msg' => 'PJSIP Logger Desactivado (Verbose 3)']);
    }
    exit;
}

if ($action === 'get_sip_debug') {
    $log_path = '/var/log/asterisk/full';
    if (!file_exists($log_path)) {
        echo json_encode(['success' => true, 'log' => "Archivo de log no encontrado en $log_path"]);
        exit;
    }
    
    // Verificamos si el logger está activo (opcional, pero útil para el frontend)
    $status_out = shell_exec("/usr/sbin/asterisk -rx 'pjsip show history' 2>&1");
    $is_active = (strpos($status_out, 'enabled') !== false || strpos($status_out, 'History') !== false);

    $tail = file_exists('/usr/bin/tail') ? '/usr/bin/tail' : 'tail';
    $grep = file_exists('/usr/bin/grep') ? '/usr/bin/grep' : 'grep';
    
    // Ampliamos el filtro para ver TODO lo relacionado con la negociación media y errores
    $patterns = 'pjsip|sip|reg|auth|fail|error|rtp|ice|stun|turn|sdp|re-invite|ack|bye|cancel';
    $cmd = "$tail -n 1200 $log_path | $grep -iaE '$patterns' | $tail -n 160";
    $output = shell_exec($cmd);
    
    $output = preg_replace('/\x1B\[[0-9;]*[mK]/', '', $output);
    
    echo json_encode([
        'success' => true, 
        'is_debug_active' => $is_active,
        'log' => $output ?: "Esperando eventos... (Asegúrese de activar el PJSIP Logger)"
    ]);
    exit;
}

// ─── HELPERS ────────────────────────────────────────────────────────────────
function mysql_pbx($db = 'asterisk') {
    global $DB_PASS;
    return new PDO("mysql:host=localhost;dbname=$db;charset=utf8", 'root', $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function ami_cmd($cmd) {
    return shell_exec("COLUMNS=200 /usr/sbin/asterisk -rx " . escapeshellarg($cmd) . " 2>/dev/null");
}

function get_all_endpoint_statuses() {
    $pjsip_e = ami_cmd('pjsip show endpoints');
    $pjsip_c = ami_cmd('pjsip show contacts');
    
    $statuses = [];
    $contacts = [];
    
    // Parse contacts for IP
    foreach (explode("\n", $pjsip_c) as $line) {
        if (preg_match('/^\s*Contact:\s+(\d+)\/(\S+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\S+)/i', $line, $m)) {
            $contacts[$m[1]] = $m[4]; // IP address
        }
    }

    // Parse endpoints for status
    foreach (explode("\n", $pjsip_e) as $line) {
        // Updated regex to catch modern Asterisk PJSIP output
        if (preg_match('/Endpoint:\s+([\w]+)(?:\/.*?)?\s+(.*?)\s+(\d+)\s+of/i', $line, $m)) {
            $ext = $m[1];
            $status_raw = trim($m[2]);
            $status = 'OFFLINE';
            if (strpos($status_raw, 'Not in use') !== false) $status = 'ONLINE';
            else if (strpos($status_raw, 'In use') !== false || strpos($status_raw, 'Busy') !== false) $status = 'BUSY';
            else if (strpos($status_raw, 'Ringing') !== false) $status = 'RINGING';
            
            $statuses[$ext] = [
                'status' => $status,
                'ip' => $contacts[$ext] ?? '---'
            ];
        }
    }
    return $statuses;
}

function reload_dialplan() {
    // Find fwconsole in common locations
    $paths = ['/var/lib/asterisk/bin/fwconsole','/usr/sbin/fwconsole','/usr/local/sbin/fwconsole'];
    $fw = '';
    foreach ($paths as $p) { if (file_exists($p)) { $fw=$p; break; } }
    if ($fw) {
        shell_exec("$fw reload --quiet >/dev/null 2>&1");
    } else {
        shell_exec("/var/lib/asterisk/bin/retrieve_conf >/dev/null 2>&1");
        shell_exec("/usr/sbin/asterisk -rx 'core reload' >/dev/null 2>&1");
    }
}

function apply_sip_settings($db, $ext, $name, $secret, $devType) {
    if (!$devType) {
         // Si no mandan devType, solo actualizamos secret y name si vienen
         if ($secret) {
             $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'secret', ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, $secret]);
         }
         if ($name) {
             $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'callerid', ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, "$name <$ext>"]);
         }
         return;
    }

    $sip_data = [
        'type'                            => 'friend',
        'host'                            => 'dynamic',
        'nat'                             => 'no',
        'port'                            => '5060',
        'qualify'                         => 'yes',
        'qualifyfreq'                     => '60',
        'dtmfmode'                        => 'rfc2833',
        'disallow'                        => 'all',
        'allow'                           => 'alaw,ulaw',
        'dial'                            => "PJSIP/$ext",
        'mailbox'                         => $ext,
        'context'                         => 'from-internal',
        'account'                         => $ext,
        'direct_media'                    => 'no',
        'max_contacts'                    => '1',
        'remove_existing'                 => 'yes',
        'ice_support'                     => 'no',
        'media_encryption'                => 'no',
        'dtls_verify'                     => 'no',
        'dtls_setup'                      => 'actpass',
        'media_use_received_transport'    => 'no',
        'allow_subscribe'                 => 'yes',
    ];

    if ($secret) $sip_data['secret'] = $secret;
    if ($name) $sip_data['callerid'] = "$name <$ext>";

    // CLEANUP: Evitar entradas duplicadas que Issabel genera por defecto y que pisan a las de WebRTC
    $db->prepare("DELETE FROM sip WHERE id=? AND keyword IN ('rtp_symmetric','rewrite_contact','force_rport','ice_support','use_avpf','rtcp_mux','media_encryption','webrtc','bundle','dtls_auto_generate_cert')")->execute([$ext]);

    if ($devType === 'webrtc') {
        $sip_data['allow'] = 'alaw,ulaw,opus,vp8,h264';
        $sip_data['webrtc'] = 'yes';
        $sip_data['use_avpf'] = 'yes';
        $sip_data['media_encryption'] = 'dtls';
        $sip_data['dtls_verify'] = 'fingerprint';
        $sip_data['dtls_setup'] = 'actpass';
        $sip_data['ice_support'] = 'yes';
        $sip_data['media_use_received_transport'] = 'yes';
        $sip_data['rtcp_mux'] = 'yes';
        $sip_data['bundle'] = 'yes';
        $sip_data['rewrite_contact'] = 'yes';
        $sip_data['rtp_symmetric'] = 'yes';
        $sip_data['force_rport'] = 'yes';
        $sip_data['dtls_auto_generate_cert'] = 'yes';
        $sip_data['rtp_keepalive'] = '5'; // Enviar paquetes RTP vacíos para mantener NAT abierto
    } else if ($devType === 'video') {
        $sip_data['allow'] = 'alaw,ulaw,h264,vp8';
    }

    $stmt = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (:id, :kw, :data, 0)
                          ON DUPLICATE KEY UPDATE data=VALUES(data)");
    foreach ($sip_data as $kw => $val) {
        $stmt->execute([':id' => $ext, ':kw' => $kw, ':data' => $val]);
    }

    // Actualizar AstDB para que el dialplan de FreePBX/Issabel lo reconozca inmediatamente
    $dialStr = $sip_data['dial'] ?? "PJSIP/$ext";
    shell_exec("/usr/sbin/asterisk -rx 'database put AMPUSER $ext/device $ext'");
    shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/user $ext'");
    shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/dial $dialStr'");
    shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/type fixed'");
    
    // Recargar PJSIP para aplicar cambios de MySQL (si es que PJSIP lee de ahí, si no retrieve_conf es necesario)
    // shell_exec("/usr/sbin/asterisk -rx 'module reload res_pjsip.so'");
}

// ─── GET AGENTS DATA (Lite version for Softphone Directory) ─────────────────
if ($action === 'get_agents_data') {
    $pjsip_e = ami_cmd('pjsip show endpoints');
    $exts = [];
    foreach (explode("\n", $pjsip_e) as $line) {
        if (preg_match('/^\s+Endpoint:\s+(\d+)\/(.+?)\s+(Not in use|Unavailable|In use|Busy|Ringing)\s+(\d+)/i', $line, $m)) {
            $ext  = $m[1];
            $name = trim($m[2]);
            $avatar = "uploads/avatars/$ext.jpg";
            if (!file_exists($avatar_dir . $ext . '.jpg')) $avatar = "";
            $st = strtoupper(trim($m[3]));
            $status = ($st==='NOT IN USE')?'ONLINE':($st==='UNAVAILABLE'?'OFFLINE':'BUSY');
            $exts[] = ['ext'=>$ext,'name'=>$name,'status'=>$status,'avatar'=>$avatar];
        }
    }
    // MySQL Fallback
    try {
        $db2 = mysql_pbx();
        $db_devs = $db2->query("SELECT d.id as ext, d.description as name FROM devices d WHERE d.tech IN ('pjsip','sip') ORDER BY CAST(d.id AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($db_devs as $dev) {
            $found = false;
            foreach($exts as $e) if($e['ext'] === $dev['ext']) { $found=true; break; }
            if(!$found) {
                $ext = $dev['ext'];
                $avatar = "uploads/avatars/$ext.jpg";
                if (!file_exists($avatar_dir . $ext . '.jpg')) $avatar = "";
                $exts[] = ['ext'=>$dev['ext'],'name'=>$dev['name'] ?: $dev['ext'],'status'=>'OFFLINE','avatar'=>$avatar];
            }
        }
    } catch (Exception $e) {}
    echo json_encode(['success' => true, 'agents' => $exts]);
    exit;
}


// ─── GET FULL DATA (dashboard + extensiones + grabaciones) ──────────────────
if ($action === 'get_full_data') {
    $load   = sys_getloadavg();
    $pjsip_e = ami_cmd('pjsip show endpoints');
    $pjsip_c = ami_cmd('pjsip show contacts');

    $exts = [];
    $lines = explode("\n", $pjsip_e);
    foreach ($lines as $line) {
        if (preg_match('/^\s+Endpoint:\s+(\d+)(?:\/.*?)?\s+(Not in use|Unavailable|In use|Busy|Ringing|Unknown)\s+(\d+)/i', $line, $m)) {
            $ext  = $m[1];
            $name = $ext;
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=714B67&color=fff&size=80';
            if (file_exists($avatar_dir . $ext . '.jpg')) $avatar = "uploads/avatars/$ext.jpg?" . time();
            $st = strtoupper(trim($m[2]));
            $status = ($st==='NOT IN USE')?'ONLINE':($st==='UNAVAILABLE'?'OFFLINE':'BUSY');
            $exts[$ext] = [
                'ext'=>$ext, 'name'=>$name, 'status'=>$status, 
                'ip'=>'—', 'rtt'=>'—', 'rtt_ms'=>999, 'mac'=>'—', 
                'avatar'=>$avatar, 'recording'=>'dontcare', 'device_type'=>'softphone'
            ];
        }
    }

    foreach (explode("\n", $pjsip_c) as $line) {
        if (preg_match('/Contact:\s+(\d+)\/sip:\S+@([\d\.]+):(\d+)\S*\s+\S+\s+Avail\s+([\d\.]+)/i', $line, $m)) {
            $ext = $m[1];
            if (isset($exts[$ext])) {
                if ($exts[$ext]['status'] !== 'BUSY') $exts[$ext]['status'] = 'ONLINE';
                $exts[$ext]['ip']  = $m[2];
                $ms = round((float)$m[4] * 1000); // Usually Asterisk returns seconds in "avail 0.002"
                if ($ms < 1) $ms = round((float)$m[4]); // Fallback if it's already ms
                $exts[$ext]['rtt'] = $ms . 'ms';
                $exts[$ext]['rtt_ms'] = $ms;
            }
        }
    }

    try {
        $db2 = mysql_pbx();
        $db_devs = $db2->query("SELECT d.id as ext, d.description as name FROM devices d WHERE d.tech IN ('pjsip','sip') ORDER BY CAST(d.id AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        
        $sip_transp = $db2->query("SELECT id, data FROM sip WHERE keyword='transport'")->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($db_devs as $dev) {
            $ext_v = $dev['ext'];
            if (!isset($exts[$ext_v])) {
                $n = $dev['name'] ?: $ext_v;
                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($n) . '&background=714B67&color=fff&size=80';
                $exts[$ext_v] = [
                   'ext'=>$ext_v, 'name'=>$n, 'status'=>'OFFLINE', 
                   'ip'=>'—', 'rtt'=>'—', 'rtt_ms'=>999, 'mac'=>'—', 
                   'avatar'=>$avatar, 'recording'=>'dontcare', 'device_type'=>'phone'
                ];
            } elseif (empty(trim($exts[$ext_v]['name'])) || $exts[$ext_v]['name'] === $ext_v) {
                $exts[$ext_v]['name'] = $dev['name'] ?: $exts[$ext_v]['name'];
            }
            
            // Detect device type
            $t = $sip_transp[$ext_v] ?? '';
            if (strpos($t, 'wss') !== false) $exts[$ext_v]['device_type'] = 'softphone';
            else $exts[$ext_v]['device_type'] = 'phone';
        }
        $rec_rows = $db2->query("SELECT extension as id, recording as data FROM users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rec_rows as $r) { if (isset($exts[$r['id']])) $exts[$r['id']]['recording'] = $r['data']; }
    } catch (Exception $e) {}

    $ch_raw = ami_cmd('core show channels verbose');
    preg_match_all('/^(PJSIP|SIP)\/((\d+)-\w+)/m', $ch_raw, $mc);
    foreach (($mc[3] ?? []) as $busy_ext) {
        if (isset($exts[$busy_ext])) $exts[$busy_ext]['status'] = 'BUSY';
    }

    $recordings = [];
    try {
        $db  = mysql_pbx('asteriskcdrdb');
        $recordings = $db->query("SELECT calldate,src,dst,duration,billsec,disposition,recordingfile,clid FROM cdr WHERE recordingfile!='' ORDER BY calldate DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $uptime = str_replace('up ', '', trim(shell_exec('uptime -p 2>/dev/null') ?: ''));
    $ram_raw = shell_exec("free -m | awk 'NR==2{print $3*100/$2 }'");
    $ram = round((float)$ram_raw);
    $disk_raw = shell_exec("df -h / | awk 'NR==2{print $5}'") ?: '0%';
    $disk = (int)str_replace('%', '', trim($disk_raw));
    $conn_raw = shell_exec("netstat -an | grep ESTABLISHED | wc -l");
    $conn = (int)trim($conn_raw);

    $live_calls = [];
    $lines = explode("\n", $ch_raw);
    foreach($lines as $line) {
        if (preg_match('/^((?:PJSIP|SIP)\/(\d+)-\w+)\s+\S+\s+(\S+)\s+(\S+)\s+(.*?)\s+(\S+)\s+(\d+:\d{2}:\d{2}|\d+:\d{2})/', $line, $m)) {
             $live_calls[] = [
                 'channel' => $m[1],
                 'ext' => $m[2], 
                 'state' => $m[3], 
                 'app' => $m[4],
                 'dest' => $m[5],
                 'duration' => $m[7],
                 'callerid' => $m[6]
             ];
        }
    }

    $queues = [];
    $ringgroups = [];
    $ivrs = [];
    try {
        $db2 = mysql_pbx();
        $queues = $db2->query("SELECT extension as id, descr as name FROM queues_config ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($queues as &$q) {
            $q['members'] = $db2->query("SELECT queue_pos, membername FROM queues_details WHERE id='{$q['id']}'")->fetchAll(PDO::FETCH_COLUMN, 1);
            $q_status = ami_cmd("queue show {$q['id']}");
            preg_match('/strategy\s+\w+\s+has\s+(\d+)\s+calls/i', $q_status, $mq);
            $q['calls_waiting'] = (int)($mq[1] ?? 0);
        }
        $ringgroups = $db2->query("SELECT grpnum as id, description as name, grplist as members FROM ringgroups")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ringgroups as &$rg) {
            $rg['members'] = explode('-', $rg['members']);
        }
        $ivrs = $db2->query("SELECT id, name FROM ivr_details")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'system' => ['cpu' => round($load[0] * 25), 'uptime' => $uptime, 'ram' => $ram, 'disk' => $disk, 'connections' => $conn],
        'pbx'    => [
            'extensions' => array_values($exts), 
            'recordings' => $recordings, 
            'live_calls' => $live_calls,
            'queues' => $queues,
            'ringgroups' => $ringgroups,
            'ivrs' => $ivrs
        ],
    ]);
    exit;
}



// ─── EXTENSIONES: GET DETAIL ─────────────────────────────────────────────────
if ($action === 'get_extension') {
    $ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
    if (!$ext) { echo json_encode(['success' => false]); exit; }
    try {
        $db   = mysql_pbx();
        $dev  = $db->query("SELECT * FROM devices WHERE id='$ext'")->fetch(PDO::FETCH_ASSOC);
        $usr  = $db->query("SELECT * FROM users WHERE extension='$ext'")->fetch(PDO::FETCH_ASSOC);
        
        $sip    = $db->query("SELECT data FROM sip WHERE id='$ext' AND keyword='secret' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $transp = $db->query("SELECT data FROM sip WHERE id='$ext' AND keyword='transport' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        $device_type = 'audio';
        if ($transp && $transp['data'] === 'transport-wss') $device_type = 'webrtc';
        
        echo json_encode([
            'success' => true, 
            'device' => $dev, 
            'user' => $usr, 
            'secret' => $sip['data'] ?? '',
            'device_type' => $device_type
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── EXTENSIONES: CREATE ─────────────────────────────────────────────────────
if ($action === 'create_extension') {
    $ext    = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    $name   = htmlspecialchars(trim($_POST['name'] ?? ''));
    $secret = trim($_POST['secret'] ?? '');
    $email  = trim($_POST['email'] ?? '');

    if (!$ext || !$name || !$secret) {
        echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']); exit;
    }
    if (strlen($ext) < 3 || strlen($ext) > 6) {
        echo json_encode(['success' => false, 'error' => 'El interno debe tener entre 3 y 6 dígitos']); exit;
    }

    try {
        $db = mysql_pbx();

        // Check if already exists
        $chk = $db->prepare("SELECT id FROM devices WHERE id=?");
        $chk->execute([$ext]);
        if ($chk->fetch()) { echo json_encode(['success' => false, 'error' => "El interno $ext ya existe"]); exit; }

        // 1. devices table
        $db->prepare("INSERT INTO devices (id, tech, dial, devicetype, user, description, emergency_cid) VALUES (?, ?, ?, 'fixed', ?, ?, '')")
           ->execute([$ext, 'pjsip', "PJSIP/$ext", $ext, $name]);

        // 2. users table (Set recording 'out=Always|in=Always' so new extensions record by default)
        $db->prepare("INSERT INTO users (extension, password, name, voicemail, ringtimer, noanswer, recording, outboundcid, mohclass) VALUES (?, ?, ?, 'novm', 0, '', 'out=Always|in=Always', '', 'default')")
           ->execute([$ext, $ext, $name]);

        // settings helper logic here now integrated into update too
        apply_sip_settings($db, $ext, $name, $secret, $_POST['device_type'] ?? 'webrtc');

        // 4. Reload dialplan
        reload_dialplan();

        echo json_encode(['success' => true, 'message' => "Extensión $ext creada correctamente"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── EXTENSIONES: UPDATE ─────────────────────────────────────────────────────
if ($action === 'update_extension') {
    $ext    = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    $name   = htmlspecialchars(trim($_POST['name'] ?? ''));
    $secret = trim($_POST['secret'] ?? '');

    if (!$ext) { echo json_encode(['success' => false, 'error' => 'Interno inválido']); exit; }

    try {
        $db = mysql_pbx();
        if ($name) {
            $db->prepare("UPDATE devices SET description=? WHERE id=?")->execute([$name,$ext]);
            $db->prepare("UPDATE users SET name=? WHERE extension=?")->execute([$name,$ext]);
        }
        
        $devType = $_POST['device_type'] ?? '';
        apply_sip_settings($db, $ext, $name, $secret, $devType);

        reload_dialplan();
        echo json_encode(['success' => true, 'message' => "Extensión $ext actualizada"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── EXTENSIONES: DELETE ─────────────────────────────────────────────────────
if ($action === 'delete_extension') {
    $ext = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    if (!$ext) { echo json_encode(['success' => false, 'error' => 'Interno inválido']); exit; }
    try {
        $db = mysql_pbx();
        $db->prepare("DELETE FROM devices WHERE id=?")->execute([$ext]);
        $db->prepare("DELETE FROM users WHERE extension=?")->execute([$ext]);
        $db->prepare("DELETE FROM sip WHERE id=?")->execute([$ext]);
        @unlink($GLOBALS['avatar_dir'] . "$ext.jpg");
        reload_dialplan();
        echo json_encode(['success' => true, 'message' => "Extensión $ext eliminada"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── GRABACIONES TOGGLE ───────────────────────────────────────────────────────
if ($action === 'set_recording') {
    $ext  = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    $mode = in_array($_POST['mode']??'', ['always','dontcare','never']) ? $_POST['mode'] : 'dontcare';
    if (!$ext) { echo json_encode(['success'=>false,'error'=>'Interno inválido']); exit; }
    try {
        $db = mysql_pbx();
        $db->prepare("UPDATE users SET recording=? WHERE extension=?")->execute([$mode,$ext]);
        reload_dialplan();
        echo json_encode(['success'=>true, 'message'=>"Grabación $mode configurada para $ext"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── AVATAR UPLOAD ────────────────────────────────────────────────────────────
if ($action === 'upload_avatar') {
    $ext = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    if (!$ext || !isset($_FILES['avatar'])) { echo json_encode(['success' => false]); exit; }
    $dest = $avatar_dir . "$ext.jpg";
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'url' => "uploads/avatars/$ext.jpg?" . time()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar']);
    }
    exit;
}

// ─── CDR ─────────────────────────────────────────────────────────────────────
if ($action === 'get_cdr') {
    $from  = $_GET['from']  ?? date('Y-m-d', strtotime('-7 days'));
    $to    = $_GET['to']    ?? date('Y-m-d');
    $src   = preg_replace('/\W/', '', $_GET['src']   ?? '');
    $dst   = preg_replace('/\W/', '', $_GET['dst']   ?? '');
    $disp  = $_GET['disp']  ?? '';
    $limit = min(500, intval($_GET['limit'] ?? 100));
    $page  = max(0, intval($_GET['page'] ?? 0));

    try {
        $db = mysql_pbx('asteriskcdrdb');
        $where = ["calldate BETWEEN '$from 00:00:00' AND '$to 23:59:59'"];
        if ($src)  $where[] = "(src LIKE '%$src%' OR dst LIKE '%$src%')";
        if ($disp) $where[] = "disposition=" . $db->quote($disp);
        $w = 'WHERE ' . implode(' AND ', $where);

        $total = $db->query("SELECT COUNT(*) FROM cdr $w")->fetchColumn();
        $rows  = $db->query("SELECT calldate,clid,src,dst,duration,billsec,disposition,recordingfile,channel,dstchannel FROM cdr $w ORDER BY calldate DESC LIMIT $limit OFFSET " . ($page * $limit))->fetchAll(PDO::FETCH_ASSOC);
        $stats = $db->query("SELECT COUNT(*) as total, SUM(disposition='ANSWERED') as answered, SUM(disposition='NO ANSWER') as no_answer, SUM(disposition='BUSY') as busy, SUM(disposition='FAILED') as failed, AVG(CASE WHEN disposition='ANSWERED' THEN billsec ELSE NULL END) as avg_duration, SUM(billsec) as total_seconds FROM cdr $w")->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'total' => $total, 'rows' => $rows, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── QUEUES (desde MySQL + estado en vivo AMI) ────────────────────────────────
if ($action === 'get_queues') {
    $queues = [];
    try {
        $db = mysql_pbx();
        $q_rows = $db->query("SELECT extension, descr FROM queues_config ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($q_rows as $q) {
            $qid   = $q['extension'];
            $det   = $db->prepare("SELECT keyword, data FROM queues_details WHERE id=? ORDER BY keyword");
            $det->execute([$qid]);
            $details = [];
            foreach ($det->fetchAll(PDO::FETCH_ASSOC) as $d) $details[$d['keyword']][] = $d['data'];

            $members = [];
            foreach (($details['member']??[]) as $m) {
                $parts = explode(',', $m);
                $ch = explode('/', $parts[0]);
                $members[] = ['tech'=>$ch[0]??'PJSIP','ext'=>$ch[1]??$m,'name'=>$parts[2]??'','status'=>'idle'];
            }

            // Live status from AMI
            $qa      = ami_cmd("queue show $qid");
            $waiting = 0;
            if (preg_match('/(\d+) calls waiting/i', $qa, $wm)) $waiting = intval($wm[1]);
            $processed = 0;
            if (preg_match('/processed (\d+)/i', $qa, $pm)) $processed = intval($pm[1]);

            // Parse max wait time from the calls list
            // Longest wait: 0:42
            $max_wait = 0;
            if (preg_match_all('/wait:\s+(\d+):(\d+)/i', $qa, $wait_matches, PREG_SET_ORDER)) {
                foreach ($wait_matches as $wm) {
                    $seconds = (intval($wm[1]) * 60) + intval($wm[2]);
                    if ($seconds > $max_wait) $max_wait = $seconds;
                }
            }

            $queues[] = [
                'id'            => $qid,
                'name'          => $q['descr'],
                'strategy'      => $details['strategy'][0] ?? 'ringall',
                'timeout'       => $details['timeout'][0]  ?? 15,
                'wrapuptime'    => $details['wrapuptime'][0] ?? 0,
                'calls_waiting' => $waiting,
                'calls_processed' => $processed,
                'max_wait'      => $max_wait,
                'members'       => $members,
            ];
        }

        // Cross-reference member status with actual PJSIP reachability
        $pjsip_statuses = get_all_endpoint_statuses();
        foreach ($queues as &$q) {
            foreach ($q['members'] as &$m) {
                if (isset($pjsip_statuses[$m['ext']])) {
                    $m['status'] = $pjsip_statuses[$m['ext']]['status'];
                    $m['ip'] = $pjsip_statuses[$m['ext']]['ip'] ?? '---';
                }
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit;
    }
    echo json_encode(['success' => true, 'queues' => $queues]);
    exit;
}

// ─── ACTIVE CALLS (VIVO) ─────────────────────────────────────────────────────
if ($action === 'get_active_calls') {
    // 1. Get channel info from core show channels verbose
    $raw_ch = ami_cmd('core show channels verbose');
    $ch_stats = ami_cmd('pjsip show channelstats');
    
    $stats_map = [];
    foreach (explode("\n", $ch_stats) as $line) {
        // PJSIP/1001-00000001              ulaw        0.005    0.00     0.005    0.00
        if (preg_match('/^((?:PJSIP|SIP)\/\S+)\s+(\S+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)/', $line, $sm)) {
            $stats_map[$sm[1]] = [
                'codec' => $sm[2],
                'tx_rtt' => round((float)$sm[3] * 1000) . 'ms',
                'tx_loss' => $sm[4] . '%',
                'rx_rtt' => round((float)$sm[5] * 1000) . 'ms',
                'rx_loss' => $sm[6] . '%'
            ];
        }
    }

    $calls = [];
    $lines = explode("\n", $raw_ch);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (strpos($line, 'Channel') !== false && strpos($line, 'Context') !== false) continue;
        if (preg_match('/^0 active|^\d+ active calls|^\d+ calls processed/', $line)) continue;

        // Try to match the verbose format:
        // Channel (1) Context (2) Extension (3) Prio (4) State (5) Application (6) Data (7) CallerID (8) Duration (9) Account (10)
        // Note: Field spacing varies, use regex to find columns
        // Example: PJSIP/1001-00000001 from-internal 2005 1 Up Dial PJSIP/2005,,Ttr 1001 00:00:10
        
        // This regex is a bit flexible to handle different spacing
        if (preg_match('/^((?:PJSIP|SIP)\/[\w\-]+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w+)\s+(\S+)\s+(.*?)\s+(\S+)\s+(\d+:\d{2}:\d{2}|\d+:\d{2})/', $line, $m)) {
            $chan    = $m[1];
            $ext_num = $m[8]; // CallerID field
            $dest    = $m[3]; // Extension field
            $state   = $m[5];
            $dur     = $m[9];
            $app     = $m[6];

            if ($state === 'Down') continue;
            
            // Check if recording is active on this channel
            $rec_active = (strpos(ami_cmd("core show channel $chan"), 'MixMonitor') !== false);

            $calls[] = [
                'channel'  => $chan,
                'context'  => $m[2],
                'ext'      => $ext_num,
                'dest'     => $dest,
                'state'    => $state,
                'app'      => $app,
                'duration' => $dur,
                'tech'     => $stats_map[$chan] ?? ['codec'=>'—','tx_rtt'=>'—','tx_loss'=>'—','rx_rtt'=>'—','rx_loss'=>'—'],
                'recording'=> $rec_active
            ];
        }
    }
    
    // De-duplicate if needed (sometimes legs show up twice)
    $unique_calls = [];
    $seen = [];
    foreach($calls as $c) {
        if (!isset($seen[$c['channel']])) {
            $unique_calls[] = $c;
            $seen[$c['channel']] = true;
        }
    }

    echo json_encode(['success' => true, 'calls' => $unique_calls, 'count' => count($unique_calls)]);
    exit;
}

// ─── CALL ACTIONS (HANGUP, SPY, WHISPER, BARGE) ──────────────────────────────
if ($action === 'call_action') {
    $sub_action = $_POST['type'] ?? ''; // hangup, spy, whisper, barge
    $channel    = $_POST['channel'] ?? '';
    $supervisor = $_POST['supervisor'] ?? ''; // extension of the supervisor
    
    if (!$channel) { echo json_encode(['success'=>false, 'error'=>'Canal no especificado']); exit; }

    switch ($sub_action) {
        case 'hangup':
            ami_cmd("channel request hangup $channel");
            echo json_encode(['success'=>true, 'message'=>'Petición de colgado enviada']);
            break;
            
        case 'spy':
        case 'whisper':
        case 'barge':
            if (!$supervisor) { echo json_encode(['success'=>false, 'error'=>'Debes especificar tu extensión de supervisor']); exit; }
            $opt = ($sub_action === 'whisper') ? 'w' : (($sub_action === 'barge') ? 'B' : '');
            // Originate a call to the supervisor and connect it to ChanSpy
            // ChanSpy(PJSIP/agent, options) -> we need the agent extension
            $target_ext = preg_replace('/^(?:PJSIP|SIP)\/(\d+)-.*$/', '$1', $channel);
            $cmd = "channel originate PJSIP/$supervisor application ChanSpy PJSIP/$target_ext,q$opt";
            ami_cmd($cmd);
            echo json_encode(['success'=>true, 'message'=>'Llamada de intervención iniciada a tu extensión (' . $supervisor . ')']);
            break;
            
        default:
            echo json_encode(['success'=>false, 'error'=>'Acción desconocida']);
    }
    exit;
}

// ─── RING GROUPS ─────────────────────────────────────────────────────────────
if ($action === 'get_ring_groups') {
    try {
        $db   = mysql_pbx();
        $rows = $db->query("SELECT grpnum, description, strategy, grptime, grplist, recording FROM ringgroups ORDER BY CAST(grpnum AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['members'] = array_values(array_filter(explode('-', $r['grplist']), fn($x) => trim($x) !== ''));
        }
        echo json_encode(['success' => true, 'groups' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── LIST EXTENSIONS ─────────────────────────────────────────────────────────
if ($action === 'list_extensions_db') {
    try {
        $db   = mysql_pbx();
        $rows = $db->query("SELECT d.id as ext, d.description as name FROM devices d WHERE d.tech IN ('pjsip','sip') ORDER BY CAST(d.id AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'extensions' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── QUEUE CRUD ──────────────────────────────────────────────────────────────
if ($action === 'create_queue') {
    $id = $_POST['extension'] ?? '';
    $descr = $_POST['descr'] ?? '';
    $strategy = $_POST['strategy'] ?? 'ringall';
    $timeout = $_POST['timeout'] ?? 15;
    $wrapuptime = $_POST['wrapuptime'] ?? 5;
    $members = explode(',', $_POST['members'] ?? '');
    
    try {
        $db = mysql_pbx();
        $db->beginTransaction();
        $db->query("INSERT IGNORE INTO queues_config (extension, descr) VALUES ('$id', '$descr')");
        $db->query("DELETE FROM queues_details WHERE id = '$id'");
        $details = [
            ['timeout', $timeout, 0],
            ['wrapuptime', $wrapuptime, 0],
            ['strategy', $strategy, 0],
            ['joinempty', 'yes', 0],
            ['leavewhenempty', 'no', 0],
            ['ringinuse', 'no', 0],
        ];
        $stmt = $db->prepare("INSERT INTO queues_details (id, keyword, data, flags) VALUES (?, ?, ?, ?)");
        foreach($details as $d) $stmt->execute([$id, $d[0], $d[1], $d[2]]);
        foreach($members as $m) {
            $m = trim($m);
            if(empty($m)) continue;
            $db->query("INSERT INTO queues_details (id, keyword, data, flags) VALUES ('$id', 'member', 'Local/$m@from-queue/n,0', 0)");
        }
        $db->commit();
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Cola $id creada"]);
    } catch(Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'update_queue') {
    $id = $_POST['extension'] ?? '';
    $descr = $_POST['descr'] ?? '';
    $strategy = $_POST['strategy'] ?? 'ringall';
    $timeout = $_POST['timeout'] ?? 15;
    $wrapuptime = $_POST['wrapuptime'] ?? 5;
    $members = explode(',', $_POST['members'] ?? '');
    
    try {
        $db = mysql_pbx();
        $db->beginTransaction();
        $db->query("UPDATE queues_config SET descr='$descr' WHERE extension='$id'");
        $db->query("DELETE FROM queues_details WHERE id = '$id'");
        $details = [
            ['timeout', $timeout, 0],
            ['wrapuptime', $wrapuptime, 0],
            ['strategy', $strategy, 0],
            ['joinempty', 'yes', 0],
            ['leavewhenempty', 'no', 0],
            ['ringinuse', 'no', 0],
        ];
        $stmt = $db->prepare("INSERT INTO queues_details (id, keyword, data, flags) VALUES (?, ?, ?, ?)");
        foreach($details as $d) $stmt->execute([$id, $d[0], $d[1], $d[2]]);
        foreach($members as $m) {
            $m = trim($m);
            if(empty($m)) continue;
            // Also accept direct extension format depending on system, but Local/... is typical for FreePBX
            $db->query("INSERT INTO queues_details (id, keyword, data, flags) VALUES ('$id', 'member', 'Local/$m@from-queue/n,0', 0)");
        }
        $db->commit();
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Cola $id actualizada"]);
    } catch(Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_queue') {
    $id = $_POST['extension'] ?? '';
    try {
        $db = mysql_pbx();
        $db->query("DELETE FROM queues_config WHERE extension='$id'");
        $db->query("DELETE FROM queues_details WHERE id='$id'");
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Cola $id eliminada"]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ─── RING GROUP CRUD ─────────────────────────────────────────────────────────
if ($action === 'create_ring_group') {
    $grpnum = $_POST['grpnum'] ?? '';
    $desc = $_POST['description'] ?? '';
    $strat = $_POST['strategy'] ?? 'ringall';
    $time = $_POST['grptime'] ?? 20;
    $list = $_POST['grplist'] ?? '';
    
    try {
        $db = mysql_pbx();
        $stmt = $db->prepare("INSERT INTO ringgroups (grpnum, description, strategy, grptime, grplist, recording) VALUES (?, ?, ?, ?, ?, 'dontcare')");
        $stmt->execute([$grpnum, $desc, $strat, $time, $list]);
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Grupo $grpnum creado"]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'update_ring_group') {
    $grpnum = $_POST['grpnum'] ?? '';
    $desc = $_POST['description'] ?? '';
    $strat = $_POST['strategy'] ?? 'ringall';
    $time = $_POST['grptime'] ?? 20;
    $list = $_POST['grplist'] ?? '';
    
    try {
        $db = mysql_pbx();
        $stmt = $db->prepare("UPDATE ringgroups SET description=?, strategy=?, grptime=?, grplist=? WHERE grpnum=?");
        $stmt->execute([$desc, $strat, $time, $list, $grpnum]);
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Grupo $grpnum actualizado"]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_ring_group') {
    $grpnum = $_POST['grpnum'] ?? '';
    try {
        $db = mysql_pbx();
        $stmt = $db->prepare("DELETE FROM ringgroups WHERE grpnum=?");
        $stmt->execute([$grpnum]);
        reload_dialplan();
        echo json_encode(['success'=>true,'message'=>"Grupo $grpnum eliminado"]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}


// ─── REPORTES ───────────────────────────────────────────────────────────────
if ($action === 'get_reports') {
    $start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
    $end = $_GET['end'] ?? date('Y-m-d');
    $queue = $_GET['queue'] ?? '';
    
    try {
        $db = mysql_pbx('asteriskcdrdb');
        
        $where = ["DATE(calldate) BETWEEN ? AND ?"];
        $params = [$start, $end];
        if ($queue) {
            $where[] = "dst = ?";
            $params[] = $queue;
        }
        $wStr = implode(" AND ", $where);

        // General stats
        $stmtStats = $db->prepare("SELECT 
            COUNT(*) as total, 
            SUM(CASE WHEN disposition='ANSWERED' THEN 1 ELSE 0 END) as answered, 
            SUM(CASE WHEN disposition='FAILED' THEN 1 ELSE 0 END) as failed, 
            SUM(CASE WHEN disposition='NO ANSWER' THEN 1 ELSE 0 END) as no_answer, 
            SUM(CASE WHEN disposition='BUSY' THEN 1 ELSE 0 END) as busy, 
            AVG(billsec) as avg_duration,
            AVG(duration - billsec) as avg_wait
            FROM cdr WHERE $wStr");
        $stmtStats->execute($params);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // Daily trend
        $stmtTrend = $db->prepare("SELECT DATE(calldate) as date, disposition, COUNT(*) as count FROM cdr WHERE $wStr GROUP BY date, disposition ORDER BY date ASC");
        $stmtTrend->execute($params);
        $trendRaw = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);
        
        $trend = [];
        foreach($trendRaw as $r) {
            $d = $r['date'];
            if(!isset($trend[$d])) $trend[$d] = ['ANSWERED'=>0,'NO ANSWER'=>0,'BUSY'=>0,'FAILED'=>0];
            $trend[$d][$r['disposition']] = $r['count'];
        }

        // Top origins
        $stmtOrigins = $db->prepare("SELECT src, COUNT(*) as count FROM cdr WHERE $wStr GROUP BY src ORDER BY count DESC LIMIT 5");
        $stmtOrigins->execute($params);
        $origins = $stmtOrigins->fetchAll(PDO::FETCH_ASSOC);

        // Top dests (only if not filtering by a single queue)
        $dests = [];
        if (!$queue) {
            $stmtDests = $db->prepare("SELECT dst, COUNT(*) as count FROM cdr WHERE $wStr GROUP BY dst ORDER BY count DESC LIMIT 5");
            $stmtDests->execute($params);
            $dests = $stmtDests->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success'=>true, 'stats'=>$stats, 'trend'=>$trend, 'origins'=>$origins, 'dests'=>$dests]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

// ─── IVR DESIGNER ENDPOINTS ───────────────────────────────────────────────────
if ($action === 'get_ivr_data') {
    $recordings = [];
    $queues = [];
    $ringgroups = [];
    $extensions = [];
    
    try {
        $dbAsterisk = mysql_pbx();
        $extensions = $dbAsterisk->query("SELECT d.id as ext, d.description as name FROM devices d WHERE d.tech IN ('pjsip','sip') ORDER BY CAST(d.id AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        $queues = $dbAsterisk->query("SELECT extension as ext, descr as name FROM queues_config")->fetchAll(PDO::FETCH_ASSOC);
        $ringgroups = $dbAsterisk->query("SELECT grpnum as ext, description as name FROM ringgroups")->fetchAll(PDO::FETCH_ASSOC);
        
        $rec_dir = '/var/lib/asterisk/sounds/custom/';
        if (file_exists($rec_dir) && is_dir($rec_dir)) {
            $files = scandir($rec_dir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                if (preg_match('/\.(wav|WAV|gsm|sln|mp3)$/i', $f)) {
                    $recordings[] = $f;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'extensions' => $extensions,
            'queues' => $queues,
            'ringgroups' => $ringgroups,
            'recordings' => $recordings
        ]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'upload_ivr_audio') {
    if (!isset($_FILES['audio'])) {
        echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo']);
        exit;
    }
    
    $file = $_FILES['audio'];
    $tmp = $file['tmp_name'];
    $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($file['name']));
    $target_dir = '/var/lib/asterisk/sounds/custom/';
    
    if (!file_exists($target_dir)) {
        @mkdir($target_dir, 0775, true);
    }
    
    $target = $target_dir . $name;
    
    if (move_uploaded_file($tmp, $target)) {
        // Enforce permissions for Asterisk
        shell_exec("chown asterisk:asterisk " . escapeshellarg($target));
        shell_exec("chmod 664 " . escapeshellarg($target));
        
        // Convert mp3 to wav if needed (for Asterisk compatibility)
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'mp3') {
            $wav_name = pathinfo($name, PATHINFO_FILENAME) . '.wav';
            $wav_target = $target_dir . $wav_name;
            shell_exec("sox " . escapeshellarg($target) . " -r 8000 -c 1 -e signed-integer " . escapeshellarg($wav_target));
            shell_exec("chown asterisk:asterisk " . escapeshellarg($wav_target));
            shell_exec("chmod 664 " . escapeshellarg($wav_target));
            $name = $wav_name; // return the wav name
        }

        echo json_encode(['success' => true, 'filename' => $name, 'message' => 'Audio subido exitosamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo mover el archivo subido al directorio de Issabel. Revisa los permisos de ' . $target_dir]);
    }
    exit;
}

if ($action === 'save_ivr_flow') {
    $data = file_get_contents('php://input');
    $file = __DIR__ . '/ivr_flow.json';
    if (file_put_contents($file, $data) !== false) {
        // Enforce permissions for Asterisk if needed
        @shell_exec("chmod 664 " . escapeshellarg($file));
        @shell_exec("chown asterisk:asterisk " . escapeshellarg($file));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo ivr_flow.json']);
    }
    exit;
}

if ($action === 'get_ivr_flow') {
    $file = __DIR__ . '/ivr_flow.json';
    if (file_exists($file)) {
        $data = file_get_contents($file);
        echo $data; // Already JSON
    } else {
        echo json_encode(['success' => false, 'error' => 'No hay flujo guardado']);
    }
    exit;
}

if ($action === 'apply_ivr_flow') {
    $file = __DIR__ . '/ivr_flow.json';
    if (!file_exists($file)) { echo json_encode(['success'=>false, 'error'=>'No hay flujo para aplicar']); exit; }
    
    $json = json_decode(file_get_contents($file), true);
    $nodes = $json['nodes'] ?? [];
    $edges = $json['edges'] ?? [];
    
    $conf = "; TeleFlow Auto-Generated IVR\n\n";
    
    // We append the start hook to from-internal-custom so it's dialable
    $conf .= "[from-internal-custom]\n";
    
    $startNodes = array_filter($nodes, function($n) { return $n['type'] === 'start'; });
    foreach ($startNodes as $start) {
        $ivrNum = $start['data']['ivrNumber'] ?? '7777';
        // Encontrar siguiente destino (conexión del start)
        $next = null;
        foreach ($edges as $e) { if ($e['source'] == $start['id']) { $next = $e['target']; break; } }
        
        if ($next) {
            $conf .= "exten => {$ivrNum},1,NoOp(TeleFlow IVR - Start)\n";
            $conf .= "exten => {$ivrNum},n,Goto(ivr-node-{$next},s,1)\n\n";
        }
    }
    
    // Process Menu Nodes
    $menuNodes = array_filter($nodes, function($n) { return $n['type'] === 'menu'; });
    foreach ($menuNodes as $menu) {
        $audio = $menu['data']['audio'] ?? '';
        $audioClean = preg_replace('/\.(wav|WAV|gsm|sln|mp3)$/i', '', $audio);
        // Use absolute path to bypass language prefixes
        $audioStr = $audioClean ? "custom/$audioClean" : "dir-intro";
        
        $conf .= "[ivr-node-{$menu['id']}]\n";
        $conf .= "exten => s,1,NoOp(IVR Menu {$menu['data']['label']})\n";
        $conf .= "exten => s,n,Answer()\n";
        $conf .= "exten => s,n,Wait(1)\n";
        $conf .= "exten => s,n(loop),Background({$audioStr})\n";
        $conf .= "exten => s,n,WaitExten(5)\n";
        
        $options = $menu['data']['options'] ?? [];
        foreach ($options as $opt) {
            $digit = $opt['digit'];
            // Detectar hades a dond va este dígito usando Edges
            $next = null;
            foreach ($edges as $e) { if ($e['source'] === $menu['id'] && $e['sourceHandle'] === "opt-{$digit}") { $next = $e['target']; break; } }
            
            if ($next) {
                $conf .= "exten => {$digit},1,Goto(ivr-node-{$next},s,1)\n";
            } else if (!empty($opt['destination'])) {
                // Hardcoded fallback destination
                $destParts = explode(':', $opt['destination']);
                $target = trim($destParts[1] ?? '');
                if ($target) $conf .= "exten => {$digit},1,Goto(from-internal,{$target},1)\n";
            }
        }
        $conf .= "exten => i,1,Playback(pbx-invalid)\n";
        $conf .= "exten => i,n,Goto(s,loop)\n";
        $conf .= "exten => t,1,Playback(pbx-invalid)\n";
        $conf .= "exten => t,n,Goto(s,loop)\n\n";
    }
    
    // Process Action/Dest Nodes
    $actionNodes = array_filter($nodes, function($n) { return $n['type'] === 'action'; });
    foreach ($actionNodes as $action) {
        $conf .= "[ivr-node-{$action['id']}]\n";
        $label = $action['data']['label'] ?? '';
        if ($label === 'Colgar Llamada') {
            $conf .= "exten => s,1,Hangup()\n\n";
        } else {
            $parts = explode(':', $label);
            $target = trim($parts[1] ?? '');
            if ($target) {
                $conf .= "exten => s,1,Goto(from-internal,{$target},1)\n\n";
            } else {
                $conf .= "exten => s,1,Hangup()\n\n";
            }
        }
    }
    
    // Escribir archivo
    $outputConf = '/etc/asterisk/extensions_teleflow_ivr.conf';
    file_put_contents($outputConf, $conf);
    @shell_exec('chown asterisk:asterisk ' . escapeshellarg($outputConf));
    @shell_exec('chmod 664 ' . escapeshellarg($outputConf));
    
    // Asegurar #include en custom
    $extCustom = '/etc/asterisk/extensions_custom.conf';
    if (file_exists($extCustom)) {
        $c = file_get_contents($extCustom);
        if (strpos($c, '#include extensions_teleflow_ivr.conf') === false) {
            file_put_contents($extCustom, "\n#include extensions_teleflow_ivr.conf\n", FILE_APPEND);
        }
    }
    
    // Reload Asterisk Dialplan
    shell_exec('/usr/sbin/asterisk -rx "dialplan reload"');
    
    echo json_encode(['success' => true]);
    exit;
}

// ─── AGENT HISTORY peek ──────────────────────────────────────────────────────
if ($action === 'get_agent_history') {
    $ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
    if (!$ext) { echo json_encode(['success' => false]); exit; }
    try {
        $db = mysql_pbx('asteriskcdrdb');
        $stmt = $db->prepare("SELECT calldate, src, dst, duration, disposition FROM cdr 
                              WHERE src=? OR dst=? ORDER BY calldate DESC LIMIT 5");
        $stmt->execute([$ext, $ext]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'history' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── REDIRECT CALL (Transfer) ────────────────────────────────────────────────
if ($action === 'redirect_call') {
    $channel = $_POST['channel'] ?? '';
    $ext     = $_POST['ext'] ?? '';
    if (!$channel || !$ext) { echo json_encode(['success' => false, 'error' => 'Canal o destino inválido']); exit; }
    
    // channel redirect <channel> <context>,<exten>,<priority>
    $cmd = "channel redirect $channel from-internal,$ext,1";
    ami_cmd($cmd);
    echo json_encode(['success' => true, 'message' => "Transferencia a $ext iniciada"]);
    exit;
}


echo json_encode(['status' => 'error', 'message' => 'Acción desconocida: ' . $action]);
