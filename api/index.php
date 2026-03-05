<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action     = $_GET['action'] ?? '';
$avatar_dir = '../uploads/avatars/';
$DB_PASS    = 'Sildan.1329';

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

if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// ─── HELPERS ────────────────────────────────────────────────────────────────
function mysql_pbx($db = 'asterisk') {
    global $DB_PASS;
    return new PDO("mysql:host=localhost;dbname=$db;charset=utf8", 'root', $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function ami_cmd($cmd) {
    return shell_exec("/usr/sbin/asterisk -rx " . escapeshellarg($cmd) . " 2>/dev/null");
}

function reload_dialplan() {
    shell_exec('/usr/sbin/fwconsole reload --quiet >/dev/null 2>&1 &');
}

// ─── GET FULL DATA (dashboard + extensiones + grabaciones) ──────────────────
if ($action === 'get_full_data') {
    $load   = sys_getloadavg();
    $pjsip_e = ami_cmd('pjsip show endpoints');
    $pjsip_c = ami_cmd('pjsip show contacts');

    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $ext    = $m[1];
        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($m[2]) . '&background=714B67&color=fff&size=80';
        if (file_exists($avatar_dir . $ext . '.jpg')) $avatar = "uploads/avatars/$ext.jpg?" . time();
        $exts[$ext] = ['ext' => $ext, 'name' => trim($m[2]), 'status' => 'OFFLINE', 'ip' => '—', 'rtt' => '—', 'mac' => '—', 'avatar' => $avatar];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) {
        if (isset($exts[$m[1]])) {
            $exts[$m[1]]['status'] = 'ONLINE';
            $exts[$m[1]]['ip']     = $m[2];
            $exts[$m[1]]['rtt']    = round($m[4]) . 'ms';
        }
    }

    // Active calls → mark BUSY
    $ch_raw = ami_cmd('core show channels verbose');
    preg_match_all('/PJSIP\/([\d]+)-/', $ch_raw, $mc);
    foreach (($mc[1] ?? []) as $busy_ext) {
        if (isset($exts[$busy_ext])) $exts[$busy_ext]['status'] = 'BUSY';
    }

    // Recordings (last 20)
    $recordings = [];
    try {
        $db  = mysql_pbx('asteriskcdrdb');
        $recordings = $db->query(
            "SELECT calldate,src,dst,duration,billsec,disposition,recordingfile,clid
             FROM cdr WHERE recordingfile!='' ORDER BY calldate DESC LIMIT 20"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Uptime
    $uptime = trim(shell_exec('uptime -p 2>/dev/null') ?: '');

    echo json_encode([
        'system' => ['cpu' => round($load[0] * 25), 'mem' => round(trim(shell_exec('free | awk \'/Mem/{printf "%.0f", $3/$2*100}\''))), 'uptime' => $uptime],
        'pbx'    => ['extensions' => array_values($exts), 'recordings' => $recordings],
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
        // Get secret from sip table
        $sip  = $db->query("SELECT data FROM sip WHERE id='$ext' AND keyword='secret' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'device' => $dev, 'user' => $usr, 'secret' => $sip['data'] ?? '']);
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
        $exists = $db->query("SELECT id FROM devices WHERE id='$ext'")->fetch();
        if ($exists) { echo json_encode(['success' => false, 'error' => "El interno $ext ya existe"]); exit; }

        // 1. devices table
        $db->exec("INSERT INTO devices (id, tech, dial, devicetype, user, description, emergency_cid)
                   VALUES ('$ext','pjsip','PJSIP/$ext','fixed','$ext'," . $db->quote($name) . ",'')");

        // 2. users table
        $db->exec("INSERT INTO users (extension, password, name, voicemail, ringtimer, noanswer, recording, outboundcid, mohclass)
                   VALUES ('$ext','$ext'," . $db->quote($name) . ",'novm',0,'','','','default')");

        // 3. sip table (PJSIP settings via FreePBX sip table)
        $sip_data = [
            'secret'                          => $secret,
            'type'                            => 'friend',
            'host'                            => 'dynamic',
            'nat'                             => 'no',
            'port'                            => '5060',
            'qualify'                         => 'yes',
            'qualifyfreq'                     => '60',
            'callgroup'                       => '',
            'pickupgroup'                     => '',
            'dtmfmode'                        => 'rfc2833',
            'disallow'                        => '',
            'allow'                           => '',
            'dial'                            => "PJSIP/$ext",
            'mailbox'                         => $ext,
            'callerid'                        => "$name <$ext>",
            'context'                         => 'from-internal',
            'account'                         => $ext,
            'accountcode'                     => '',
            'deny'                            => '0.0.0.0/0.0.0.0',
            'permit'                          => '0.0.0.0/0.0.0.0',
            'direct_media'                    => 'no',
            'max_contacts'                    => '1',
            'message_context'                 => '',
            'authenticate_qualify'            => 'no',
            'outbound_proxy'                  => '',
            'ice_support'                     => 'no',
            'media_encryption'                => 'no',
            'dtls_verify'                     => 'no',
            'dtls_setup'                      => 'actpass',
            'dtls_cert_file'                  => '',
            'dtls_private_key'                => '',
            'dtls_ca_file'                    => '',
            'media_use_received_transport'    => 'no',
            'allow_subscribe'                 => 'yes',
            'qualify_timeout'                 => '3.0',
        ];
        $stmt = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (:id, :kw, :data, 0)
                              ON DUPLICATE KEY UPDATE data=VALUES(data)");
        foreach ($sip_data as $kw => $val) {
            $stmt->execute([':id' => $ext, ':kw' => $kw, ':data' => $val]);
        }

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
            $db->exec("UPDATE devices SET description=" . $db->quote($name) . " WHERE id='$ext'");
            $db->exec("UPDATE users SET name=" . $db->quote($name) . " WHERE extension='$ext'");
            $db->exec("UPDATE sip SET data=" . $db->quote("$name <$ext>") . " WHERE id='$ext' AND keyword='callerid'");
        }
        if ($secret) {
            $db->exec("UPDATE sip SET data=" . $db->quote($secret) . " WHERE id='$ext' AND keyword='secret'");
            if ($db->query("SELECT COUNT(*) FROM sip WHERE id='$ext' AND keyword='secret'")->fetchColumn() == 0) {
                $db->exec("INSERT INTO sip (id,keyword,data,flags) VALUES ('$ext','secret'," . $db->quote($secret) . ",0)");
            }
        }
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
        $db->exec("DELETE FROM devices WHERE id='$ext'");
        $db->exec("DELETE FROM users WHERE extension='$ext'");
        $db->exec("DELETE FROM sip WHERE id='$ext'");
        // Remove avatar
        @unlink($GLOBALS['avatar_dir'] . "$ext.jpg");
        reload_dialplan();
        echo json_encode(['success' => true, 'message' => "Extensión $ext eliminada"]);
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
        if ($src)  $where[] = "src LIKE '%$src%'";
        if ($dst)  $where[] = "dst LIKE '%$dst%'";
        if ($disp) $where[] = "disposition='$disp'";
        $w = 'WHERE ' . implode(' AND ', $where);

        $total = $db->query("SELECT COUNT(*) FROM cdr $w")->fetchColumn();

        $rows = $db->query(
            "SELECT calldate,clid,src,dst,duration,billsec,disposition,recordingfile,channel,dstchannel
             FROM cdr $w ORDER BY calldate DESC LIMIT $limit OFFSET " . ($page * $limit)
        )->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $stats = $db->query(
            "SELECT
                COUNT(*) as total,
                SUM(disposition='ANSWERED') as answered,
                SUM(disposition='NO ANSWER') as no_answer,
                SUM(disposition='BUSY') as busy,
                SUM(disposition='FAILED') as failed,
                AVG(CASE WHEN disposition='ANSWERED' THEN billsec ELSE NULL END) as avg_duration,
                SUM(billsec) as total_seconds
             FROM cdr $w"
        )->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'total' => $total, 'rows' => $rows, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── QUEUES ───────────────────────────────────────────────────────────────────
if ($action === 'get_queues') {
    $raw = ami_cmd('queue show');
    $queues = [];
    $current = null;

    foreach (explode("\n", $raw) as $line) {
        // Queue header: "support has 2 calls (max unlimited) in 'ringall' strategy...
        if (preg_match('/^(\S+)\s+has\s+(\d+)\s+call.*?strategy\s+\((\d+) calls processed\)/', $line, $m)) {
            if ($current) $queues[] = $current;
            $current = ['name' => $m[1], 'calls_waiting' => intval($m[2]), 'calls_processed' => intval($m[3]), 'strategy' => '', 'members' => []];
            // strategy from raw
            if (preg_match("/in '(\\S+)' strategy/", $line, $ms)) $current['strategy'] = $ms[1];
            if (preg_match('/\((\d+)s holdtime.*?(\d+)s talktime/', $line, $mh)) {
                $current['holdtime'] = intval($mh[1]);
                $current['talktime'] = intval($mh[2]);
            }
        }
        // Queue header v2: "support has 1 calls (max unlimited) in 'ringall' strategy, holdtime 0s, talktime 0s, processed 5 calls"
        if (preg_match('/^(\S+)\s+has\s+(\d+)\s+calls.*in\s+\'(\S+)\'\s+strategy.*processed\s+(\d+)/', $line, $m2) && !$current) {
            $current = ['name' => $m2[1], 'calls_waiting' => intval($m2[2]), 'strategy' => $m2[3], 'calls_processed' => intval($m2[4]), 'members' => []];
        }
        // Members: "   SIP/1001 (Local Agent) (Not in use) (ringinuse enabled) (skills: ) has taken 12 calls"
        if ($current && preg_match('/\s+(PJSIP|SIP)\/(\S+)\s+\((.+?)\)\s+\((.*?)\)/', $line, $mm)) {
            $current['members'][] = [
                'tech'   => $mm[1],
                'ext'    => rtrim($mm[2], ')'),
                'name'   => $mm[3],
                'status' => $mm[4],
            ];
        }
    }
    if ($current) $queues[] = $current;

    // También consultar desde MySQL para info extra
    try {
        $db = mysql_pbx();
        $q_config = $db->query("SELECT extension, descr FROM queues_config")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { $q_config = []; }

    echo json_encode(['success' => true, 'queues' => $queues]);
    exit;
}

// ─── ACTIVE CALLS (VIVO) ─────────────────────────────────────────────────────
if ($action === 'get_active_calls') {
    $raw  = ami_cmd('core show channels verbose');
    $calls = [];
    foreach (explode("\n", $raw) as $line) {
        // PJSIP/1001-00000001  s@from-internal  Up  AppDial  (Outgoing Line)  0:00:42
        if (preg_match('/^(PJSIP|SIP)\/(\S+)\s+(\S+)\s+(\w+)\s+(.+?)\s+(\d+:\d+:\d+|\d+:\d+)/', $line, $m)) {
            $calls[] = [
                'channel'  => $m[1] . '/' . $m[2],
                'ext'      => preg_replace('/-.*/', '', $m[2]),
                'dest'     => $m[3],
                'state'    => $m[4],
                'app'      => trim($m[5]),
                'duration' => $m[6],
            ];
        }
    }
    echo json_encode(['success' => true, 'calls' => $calls, 'count' => count($calls)]);
    exit;
}

// ─── EXTENSIONES: SEARCH (para autocompletar) ────────────────────────────────
if ($action === 'list_extensions_db') {
    try {
        $db   = mysql_pbx();
        $rows = $db->query("SELECT d.id as ext, d.description as name, u.extension FROM devices d LEFT JOIN users u ON d.id=u.extension WHERE d.tech IN ('pjsip','sip') ORDER BY CAST(d.id AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'extensions' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción desconocida: ' . $action]);
