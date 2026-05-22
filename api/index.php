
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
        $db   = new SQLite3(isset($ACL_DB_PATH) ? $ACL_DB_PATH : '/var/www/db/acl.db');
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
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

if (!isset($_SESSION['tf_user']) && !isset($_SESSION['agent_user']) && !in_array($action, ['get_agents_data', 'upload_avatar'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
// HORIZON: liberar lock de sesion para evitar serializacion en requests concurrentes
@session_write_close();

if ($action === 'set_sip_debug') {
    $level = $_POST['level'] ?? 'off';
    $ext   = $_POST['ext'] ?? '';
    if ($level === 'on') {
        ami_cmd('pjsip set logger on');
        ami_cmd('sip set debug on');
        if ($ext) ami_cmd("pjsip set logger host $ext");
        ami_cmd('core set verbose 6');
        ami_cmd('core set debug 5');
        echo json_encode(['success' => true, 'msg' => 'SIP+PJSIP Logger Activado (Verbose 6 + Debug 5)']);
    } else {
        ami_cmd('pjsip set logger off');
        ami_cmd('sip set debug off');
        ami_cmd('core set verbose 3');
        ami_cmd('core set debug 0');
        echo json_encode(['success' => true, 'msg' => 'SIP+PJSIP Logger Desactivado (Verbose 3)']);
    }
    exit;
}

if ($action === 'sip_debug_ext') {
    // Debug SIP filtrado por una extensión específica — refinado para chan_sip (PBX 10.1.1.7 = chan_sip)
    @set_time_limit(15);
    $ext = preg_replace('/[^0-9]/', '', $_GET['ext'] ?? '');
    if (!$ext) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'ext requerida']); exit; }

    // Cache 10s para evitar saturar el AMI con consultas seriales repetidas
    $cache = "/tmp/teleflow_sip_debug_$ext.json";
    if (file_exists($cache) && (time() - filemtime($cache)) < 10) {
        header('Content-Type: application/json');
        readfile($cache);
        exit;
    }

    $log_lines = [];
    // Comandos chan_sip (rápidos individualmente). Si no devuelven nada, se cae a PJSIP como fallback.
    $peer        = ami_cmd("sip show peer $ext");
    $registry    = ami_cmd('sip show registry');
    $sip_chans   = ami_cmd('sip show channels');
    $log_lines[] = "=== SIP PEER $ext (chan_sip) ===";
    if (trim($peer)) {
        $log_lines[] = trim($peer);
    } else {
        $log_lines[] = '(sin peer chan_sip — probando PJSIP)';
        $pjep = ami_cmd("pjsip show endpoint $ext");
        if (trim($pjep)) $log_lines[] = "(PJSIP fallback) " . trim($pjep);
        else $log_lines[] = '(la extensión tampoco existe como endpoint PJSIP)';
    }
    $log_lines[] = '';

    // SIP channels filtrados por la ext
    $log_lines[] = "=== SIP CHANNELS (filtrados por $ext) ===";
    $chl = [];
    foreach (explode("\n", $sip_chans) as $line) {
        if (strpos($line, "/$ext-") !== false ||
            preg_match('/\bSIP\/' . preg_quote($ext, '/') . '\b/', $line)) {
            $chl[] = $line;
        }
    }
    $log_lines[] = $chl ? implode("\n", $chl) : '(sin canales activos para esta ext)';
    $log_lines[] = '';

    // Registry filtrado (peers que registraron a un trunk externo)
    if (trim($registry)) {
        $log_lines[] = "=== SIP REGISTRY (filtrado por $ext) ===";
        $rl = [];
        foreach (explode("\n", $registry) as $line) {
            if (preg_match('/\b' . preg_quote($ext, '/') . '\b/', $line)) $rl[] = $line;
        }
        if ($rl) $log_lines[] = implode("\n", $rl);
        else $log_lines[] = '(sin registry — no aplica a peers locales)';
        $log_lines[] = '';
    }

    // Membresías en colas con esta ext (output más útil que pjsip history)
    $qstat = ami_cmd('queue show');
    $log_lines[] = "=== QUEUE MEMBERSHIPS (líneas con $ext) ===";
    $ql = [];
    $cur_q = null;
    foreach (explode("\n", $qstat) as $line) {
        if (preg_match('/^\s*(\d+)\s+has\s+/', $line, $m)) {
            $cur_q = $m[1];
            continue;
        }
        if ($cur_q && (
            strpos($line, "SIP/$ext") !== false ||
            strpos($line, "Local/$ext@") !== false ||
            preg_match('/\bAgent\/' . preg_quote($ext, '/') . '\b/', $line)
        )) {
            $ql[] = "[Q$cur_q] " . trim($line);
        }
    }
    $log_lines[] = $ql ? implode("\n", $ql) : '(no está como miembro de ninguna cola)';
    $log_lines[] = '';
    $log_lines[] = "=== TS: " . date('Y-m-d H:i:s') . " ===";

    $payload = json_encode([
        'success' => true,
        'ext'     => $ext,
        'log'     => implode("\n", $log_lines),
        'driver'  => trim($peer) ? 'chan_sip' : 'unknown',
        'ts'      => date('Y-m-d H:i:s')
    ]);
    @file_put_contents($cache, $payload);

    header('Content-Type: application/json');
    echo $payload;
    exit;
}

if ($action === 'get_sip_debug') {
    // HORIZON: en VM remota no hay /var/log/asterisk/full. Usar AMI para info de SIP en vivo.
    // Cache 15s para evitar saturar AMI con 4 commands seriales por cada poll
    @set_time_limit(30);
    $sd_cache = '/tmp/teleflow_sip_debug.json';
    if (file_exists($sd_cache) && (time() - filemtime($sd_cache)) < 15) {
        header('Content-Type: application/json');
        readfile($sd_cache);
        exit;
    }
    $verbose_out = ami_cmd('core show settings');
    $hist        = ami_cmd('pjsip show history');
    $sip_chans   = ami_cmd('sip show channels');
    $pjsip_chans = ami_cmd('pjsip show channels');
    $is_active = (strpos($verbose_out, 'verbose:') !== false && preg_match('/verbose:\s*(\d+)/i', $verbose_out, $vm) && (int)$vm[1] >= 6);
    $log_lines = [];
    $log_lines[] = '=== PJSIP HISTORY ===';
    $log_lines[] = trim($hist) ?: '(sin historia — activá el logger)';
    $log_lines[] = '';
    $log_lines[] = '=== SIP CHANNELS ===';
    $log_lines[] = trim($sip_chans) ?: '(sin canales activos)';
    $log_lines[] = '';
    $log_lines[] = '=== PJSIP CHANNELS ===';
    $log_lines[] = trim($pjsip_chans) ?: '(sin canales pjsip)';
    $sd_payload = json_encode([
        'success' => true,
        'is_debug_active' => $is_active,
        'log' => implode("\n", $log_lines)
    ]);
    @file_put_contents($sd_cache, $sd_payload, LOCK_EX);
    @chmod($sd_cache, 0666);
    header('Content-Type: application/json');
    echo $sd_payload;
    exit;
}

// ─── HELPERS ────────────────────────────────────────────────────────────────
function mysql_pbx($db = 'asterisk') {
    global $DB_PASS, $DB_HOST, $DB_USER;
    $h = $DB_HOST ?? '127.0.0.1';
    $u = $DB_USER ?? 'root';
    return new PDO("mysql:host=$h;dbname=$db;charset=utf8", $u, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function ami_cmd($cmd) {
    // HORIZON: AMI socket TCP en lugar de shell_exec local
    // Permite que TeleFlow corra fuera del server de Asterisk (LXC, VM, etc.)
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    static $sock = null;
    static $loggedIn = false;
    if ($sock === null || !$loggedIn) {
        $sock = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $errno, $errstr, 3);
        if (!$sock) return '';
        stream_set_timeout($sock, 4);
        fgets($sock); // greeting Asterisk Call Manager/...
        fwrite($sock, "Action: Login\r\nUsername: " . ($AMI_USER ?: 'admin') . "\r\nSecret: " . ($AMI_PASS ?: '') . "\r\nEvents: off\r\n\r\n");
        $r = ''; $start = microtime(true);
        while (microtime(true) - $start < 2) {
            $line = fgets($sock); if ($line === false) break;
            $r .= $line;
            if (strpos($r, 'Authentication accepted') !== false) { $loggedIn = true; break; }
            if (strpos($r, 'Authentication failed') !== false) { fclose($sock); $sock = null; return ''; }
        }
        if (!$loggedIn) { @fclose($sock); $sock = null; return ''; }
    }
    // Ejecutar el comando con un ActionID único
    $aid = 'tf-' . uniqid();
    fwrite($sock, "Action: Command\r\nCommand: $cmd\r\nActionID: $aid\r\n\r\n");
    $out = ''; $started = false; $start = microtime(true);
    while (microtime(true) - $start < 5) {
        $line = fgets($sock);
        if ($line === false) break;
        if (strpos($line, '--END COMMAND--') !== false) break;
        // AMI Command response prefija cada línea con "Output: "
        if (stripos($line, 'Output:') === 0) {
            $out .= substr($line, strlen('Output:') + (substr($line, 7, 1) === ' ' ? 1 : 0));
            $started = true;
            continue;
        }
        // Headers del response (Response:, Privilege:, ActionID:, Message:) → saltear hasta el primer Output:
        if (!$started && preg_match('/^(Response|Privilege|ActionID|Message):/i', $line)) continue;
        if ($started) $out .= $line;
    }
    return $out;
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

/**
 * Ejecuta un comando en la PBX Issabel (10.1.1.7) via SSH usando la clave del usuario www-data.
 * Devuelve la salida (stdout+stderr) o string vacío si falla.
 *
 * La web VM no tiene binario `asterisk` ni `retrieve_conf` (la PBX vive en 10.1.1.7).
 * Esta función reemplaza los viejos `shell_exec("/usr/sbin/asterisk -rx ...")` que silenciaban errores.
 */
function pbx_ssh_exec($cmd) {
    $key  = '/var/www/.ssh/id_ed25519';
    if (!is_readable($key)) return ''; // sin clave: noop (no romper instalaciones sin SSH setup)
    $opts = '-o BatchMode=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8';
    $esc  = escapeshellarg($cmd);
    return shell_exec("ssh $opts -i $key root@10.1.1.7 $esc 2>&1");
}

function reload_dialplan() {
    // La PBX es 10.1.1.7. Regenerar configs estáticos y recargar Asterisk via SSH.
    // retrieve_conf vuelca devices/users/sip a /etc/asterisk/*_additional.conf.
    pbx_ssh_exec("/var/lib/asterisk/bin/retrieve_conf >/dev/null 2>&1; /usr/sbin/asterisk -rx 'dialplan reload' >/dev/null 2>&1");
}

function apply_sip_settings($db, $ext, $name, $secret, $devType) {
    // ── SOFT UPDATE: sin devType solo refresca campos seguros y vuelve ──
    // Esto evita machacar el peer cuando el front guarda cosas no-SIP (avatar, ext_meta, etc.)
    if (!$devType) {
        if ($secret) {
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'secret', ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")
               ->execute([$ext, $secret]);
        }
        if ($name) {
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'callerid', ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")
               ->execute([$ext, "$name <$ext>"]);
        }
        return;
    }

    // ── PRESET chan_sip CLÁSICO (clonado del peer 1000 nativo de Issabel) ──
    // Todas las extensiones llevan video por default — los teléfonos sin video negocian audio-only en SDP.
    $sip_data = [
        'type'        => 'friend',
        'host'        => 'dynamic',
        'context'     => 'from-internal',
        'account'     => $ext,
        'mailbox'     => "$ext@device",        // formato FreePBX
        'dial'        => "SIP/$ext",            // chan_sip, NO pjsip
        'dtmfmode'    => 'rfc2833',
        'transport'   => 'udp',
        'port'        => '5060',
        'qualify'     => 'yes',
        'qualifyfreq' => '60',
        'canreinvite' => 'no',
        'trustrpid'   => 'yes',
        'sendrpid'    => 'no',
        'nat'         => 'yes',                 // default Issabel — atraviesa NAT
        'callgroup'   => '',
        'pickupgroup' => '',
        'deny'        => '0.0.0.0/0.0.0.0',
        'permit'      => '0.0.0.0/0.0.0.0',
        'accountcode' => '',
        // codecs con video por default (alaw/ulaw para audio + h264/vp8 para video + opus opcional)
        'disallow'    => 'all',
        'allow'       => 'alaw,ulaw,h264,vp8,opus',
        // flags chan_sip clásicos en estado base
        'avpf'        => 'no',
        'force_avp'   => 'no',
        'icesupport'  => 'no',
        'dtlsenable'  => 'no',
        'dtlsverify'  => 'no',
        'dtlssetup'   => 'actpass',
        'encryption'  => 'no',
        'rtcp_mux'    => 'no',
    ];
    if ($secret) $sip_data['secret']   = $secret;
    if ($name)   $sip_data['callerid'] = "$name <$ext>";

    if ($devType === 'webrtc') {
        // chan_sip WebRTC — clonando el peer 10010 que funciona en este Issabel
        $sip_data['transport']                    = 'ws,wss,udp';
        $sip_data['avpf']                         = 'yes';
        $sip_data['icesupport']                   = 'yes';
        $sip_data['encryption']                   = 'yes';
        $sip_data['rtcp_mux']                     = 'yes';
        // Keywords PJSIP-style que chan_sip de Issabel sí acepta (peer 10010 lo prueba):
        $sip_data['webrtc']                       = 'yes';
        $sip_data['use_avpf']                     = 'yes';
        $sip_data['bundle']                       = 'yes';
        $sip_data['media_encryption']             = 'dtls';
        $sip_data['dtls_verify']                  = 'fingerprint';
        $sip_data['dtls_setup']                   = 'actpass';
        $sip_data['dtls_auto_generate_cert']      = 'yes';
        $sip_data['rtp_symmetric']                = 'yes';
        $sip_data['force_rport']                  = 'yes';
        $sip_data['rewrite_contact']              = 'yes';
        $sip_data['rtp_keepalive']                = '5';
        $sip_data['ice_support']                  = 'yes';
        $sip_data['media_use_received_transport'] = 'yes';
    }
    // 'video' y 'sip' usan el preset base (que ya lleva video). El teléfono sin video negocia audio-only.

    // ── CLEANUP de keywords basura del esquema viejo (PJSIP que chan_sip ignora) ──
    $db->prepare("DELETE FROM sip WHERE id=? AND keyword IN (
        'max_contacts','remove_existing','direct_media','allow_subscribe'
    )")->execute([$ext]);

    // Si NO es WebRTC, limpiar flags WebRTC para que no queden residuales si la ext cambió de tipo
    if ($devType !== 'webrtc') {
        $db->prepare("DELETE FROM sip WHERE id=? AND keyword IN (
            'webrtc','use_avpf','bundle','media_encryption','dtls_verify','dtls_setup',
            'dtls_auto_generate_cert','rtp_symmetric','force_rport','rewrite_contact',
            'rtp_keepalive','ice_support','media_use_received_transport'
        )")->execute([$ext]);
    }

    // ── INSERT/UPDATE del peer ──
    $stmt = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (:id, :kw, :data, 0)
                          ON DUPLICATE KEY UPDATE data=VALUES(data)");
    foreach ($sip_data as $kw => $val) {
        $stmt->execute([':id' => $ext, ':kw' => $kw, ':data' => $val]);
    }

    // ── AstDB con SIP/, no PJSIP/ ── (vía SSH al PBX, la web VM no tiene `asterisk`)
    $dialStr = $sip_data['dial'];  // SIP/$ext
    $astdb = "/usr/sbin/asterisk -rx 'database put AMPUSER $ext/device $ext' >/dev/null 2>&1; "
           . "/usr/sbin/asterisk -rx 'database put DEVICE $ext/user $ext' >/dev/null 2>&1; "
           . "/usr/sbin/asterisk -rx 'database put DEVICE $ext/dial $dialStr' >/dev/null 2>&1; "
           . "/usr/sbin/asterisk -rx 'database put DEVICE $ext/type fixed' >/dev/null 2>&1";
    pbx_ssh_exec($astdb);

    // ── retrieve_conf materializa el peer en /etc/asterisk/sip_additional.conf ──
    // ── y sip reload hace que chan_sip lo cargue. Sin esto el peer existe en MySQL ──
    // ── pero Asterisk no lo conoce hasta que alguien haga Apply Config en Issabel UI. ──
    pbx_ssh_exec("/var/lib/asterisk/bin/retrieve_conf >/dev/null 2>&1; /usr/sbin/asterisk -rx 'sip reload' >/dev/null 2>&1");
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
    // HORIZON CACHE 2026-05-07 v6: cache simple con lock NO-bloqueante
    @set_time_limit(45);
    @ini_set('memory_limit', '256M');
    $tf_cache_file = '/tmp/teleflow_full_data.json';
    $tf_lock_file  = '/tmp/teleflow_full_data.lock';
    $tf_ttl = 4;
    $tf_max_age = 30; // sirve cache hasta 30s antes de bloquear esperando regen
    $tf_has_cache = file_exists($tf_cache_file);
    $tf_age = $tf_has_cache ? (time() - filemtime($tf_cache_file)) : 999999;
    $tf_fresh = $tf_age < $tf_ttl;

    // 1) Cache fresh: HIT instantaneo
    if ($tf_has_cache && $tf_fresh) {
        header("Content-Type: application/json");
        header("X-TF-Cache: HIT age=" . $tf_age . "s");
        readfile($tf_cache_file);
        exit;
    }

    $tf_response_sent = false;

    // 2) Cache stale: intentar tomar lock NO-bloqueante para regenerar
    $tf_lock = @fopen($tf_lock_file, "c");
    if (!$tf_lock) { http_response_code(503); echo "{}"; exit; }
    $got_lock = flock($tf_lock, LOCK_EX | LOCK_NB);

    // Si NO obtuvimos el lock (otro proceso regenera) Y tenemos cache, devolverlo (aunque stale)
    if (!$got_lock && $tf_has_cache && $tf_age < $tf_max_age) {
        header("Content-Type: application/json");
        header("X-TF-Cache: STALE-CONCURRENT age=" . $tf_age . "s");
        readfile($tf_cache_file);
        fclose($tf_lock);
        exit;
    }

    // Si no obtuvimos lock pero NO hay cache, esperar bloqueante con timeout corto
    if (!$got_lock) {
        $start = microtime(true);
        while (!flock($tf_lock, LOCK_EX | LOCK_NB)) {
            if (microtime(true) - $start > 6) {
                fclose($tf_lock);
                http_response_code(503); echo "{}";
                exit;
            }
            usleep(100000);
        }
        clearstatcache();
        if (file_exists($tf_cache_file)) {
            header("Content-Type: application/json");
            header("X-TF-Cache: HIT-AFTER-WAIT");
            readfile($tf_cache_file);
            flock($tf_lock, LOCK_UN); fclose($tf_lock);
            exit;
        }
    }

    // Llegamos aqui: tenemos el lock.
    // Si hay cache stale, servirlo YA al cliente y luego regenerar en background
    if ($tf_has_cache && $tf_age < $tf_max_age) {
        @ignore_user_abort(true);
        $tf_cached_payload = file_get_contents($tf_cache_file);
        header("Content-Type: application/json");
        header("Content-Length: " . strlen($tf_cached_payload));
        header("Connection: close");
        header("X-TF-Cache: STALE-WHILE-REVALIDATE age=" . $tf_age . "s");
        echo $tf_cached_payload;
        @ob_end_flush();
        @flush();
        $tf_response_sent = true;
        // Falls through to regenerate; client already has data
    }
    // Regenerar inline (si tf_response_sent=true, esto corre tras flush al cliente)
    // /HORIZON CACHE v6

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

    // HORIZON: detectar brand para skip chan_sip si UCM (solo PJSIP)
    $brand_cache = '/tmp/teleflow_pbx_brand.json';
    $skip_chan_sip = false;
    if (file_exists($brand_cache)) {
        $bd = @json_decode(file_get_contents($brand_cache), true);
        if (!empty($bd['features']) && empty($bd['features']['chan_sip'])) $skip_chan_sip = true;
    }
    // HORIZON FIX 2026-05-07: parsear chan_sip (Issabel usa SIP, no PJSIP)
    $sip_peers_raw = $skip_chan_sip ? '' : ami_cmd('sip show peers');
    foreach (explode("\n", $sip_peers_raw) as $line) {
        if (preg_match('/^\s*(\d+)(?:\/\S+)?\s+(\S+)\s+\S+\s+\S+\s+\S+\s+\S+\s+\d+\s+(OK|UNKNOWN|UNREACHABLE|LAGGED)(?:\s*\((\d+)\s*ms\))?/', $line, $m)) {
            $ext_v = $m[1]; $host = $m[2]; $st_word = strtoupper($m[3]); $rtt_ms = isset($m[4]) ? (int)$m[4] : null;
            if (!isset($exts[$ext_v])) {
                $name = $ext_v;
                $av = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=714B67&color=fff&size=80";
                if (file_exists($avatar_dir . $ext_v . ".jpg")) $av = "uploads/avatars/$ext_v.jpg?" . time();
                $exts[$ext_v] = ["ext"=>$ext_v, "name"=>$name, "status"=>"OFFLINE", "ip"=>"\xe2\x80\x94", "rtt"=>"\xe2\x80\x94", "rtt_ms"=>999, "mac"=>"\xe2\x80\x94", "avatar"=>$av, "recording"=>"dontcare", "device_type"=>"phone"];
            }
            if ($st_word === "OK") {
                if ($exts[$ext_v]["status"] !== "BUSY") $exts[$ext_v]["status"] = "ONLINE";
                if ($host && $host !== "(Unspecified)") $exts[$ext_v]["ip"] = $host;
                if ($rtt_ms !== null) { $exts[$ext_v]["rtt"] = $rtt_ms . "ms"; $exts[$ext_v]["rtt_ms"] = $rtt_ms; }
            } elseif ($st_word === "UNREACHABLE" || $st_word === "LAGGED") {
                if ($exts[$ext_v]["status"] !== "BUSY") $exts[$ext_v]["status"] = "OFFLINE";
            }
        }
    }
    // /HORIZON FIX

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
    $conn_raw = shell_exec("(ss -tan 2>/dev/null || netstat -an 2>/dev/null) | grep ESTAB | wc -l");
    $conn = (int)trim($conn_raw);

    // HORIZON 2026-05-07: usar 'core show channels concise' para dedup vía BridgeID
    $ch_concise = ami_cmd('core show channels concise');
    $by_bridge = []; // bridgeId => call
    $live_calls_raw = [];
    foreach (explode("\n", $ch_concise) as $line) {
        $line = trim($line);
        if (!$line || strpos($line, '!') === false) continue;
        $f = explode('!', $line);
        if (count($f) < 13) continue;
        $chan = $f[0];
        // Skip Local channels (dialplan helpers, no humanos)
        if (stripos($chan, 'Local/') === 0) continue;
        $context = $f[1] ?? '';
        $exten   = $f[2] ?? '';
        // HORIZON: Skip feature codes (*7700 login, *7702 pausa, *XXXX) — no son llamadas reales del agente
        if (substr($exten, 0, 1) === '*') continue;
        $state   = $f[4] ?? '';
        $app     = $f[5] ?? '';
        $data    = $f[6] ?? '';
        $cid     = $f[7] ?? '';
        $dur_sec = (int)($f[10] ?? $f[11] ?? 0);
        $bridge  = $f[count($f)-2] ?? ''; // penúltimo
        $uniqid  = $f[count($f)-1] ?? ''; // último
        // Extract ext from channel
        $ext_num = '';
        if (preg_match('/^(?:PJSIP|SIP|IAX2)\/(\d+)/', $chan, $em)) $ext_num = $em[1];
        // Compose duration HH:MM:SS
        $h = (int)floor($dur_sec/3600); $mn = (int)floor(($dur_sec%3600)/60); $sc = $dur_sec%60;
        $dur_str = sprintf('%d:%02d:%02d', $h, $mn, $sc);

        $entry = [
            'channel'  => $chan,
            'ext'      => $ext_num,
            'context'  => $context,
            'state'    => $state,
            'app'      => $app,
            'dest'     => $exten,
            'callerid' => $cid,
            'duration' => $dur_str,
            'bridge'   => $bridge,
            'uniqid'   => $uniqid
        ];
        $live_calls_raw[] = $entry;
    }
    // Dedup: agrupar por bridge, tomar 1 (preferir Up sobre Ringing)
    $live_calls = [];
    $bridges_seen = [];
    // Sort: Up primero
    usort($live_calls_raw, function($a, $b){
        $rank = ['Up'=>0,'Ringing'=>1,'Ring'=>2,'Dialing'=>3];
        return ($rank[$a['state']] ?? 9) - ($rank[$b['state']] ?? 9);
    });
    foreach ($live_calls_raw as $c) {
        $k = !empty($c['bridge']) ? $c['bridge'] : ('nobr_' . $c['uniqid']);
        if (isset($bridges_seen[$k])) {
            // Add as 'peer' info into existing
            $i = $bridges_seen[$k];
            if (empty($live_calls[$i]['peer_ext'])) $live_calls[$i]['peer_ext'] = $c['ext'];
            continue;
        }
        $bridges_seen[$k] = count($live_calls);
        $live_calls[] = $c;
    }

    $queues = [];
    $ringgroups = [];
    $ivrs = [];
    try {
        $db2 = mysql_pbx();
        $queues = $db2->query("SELECT extension as id, descr as name FROM queues_config ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
        // HORIZON: una sola llamada queue show + parse global
        $queue_show_all = ami_cmd('queue show');
        $members_by_q = []; $waiting_by_q = []; $cur_q = null;
        foreach (explode("\n", $queue_show_all) as $line) {
            if (preg_match('/^\s*(\d+)\s+has\s+(\d+)\s+calls.*?strategy/', $line, $mm)) {
                $cur_q = $mm[1]; $waiting_by_q[$cur_q] = (int)$mm[2]; $members_by_q[$cur_q] = [];
                continue;
            }
            if (!$cur_q) continue;

            // Patrón A — STATIC con nombre humano:
            //   "    Central Secundaria (Local/1001@from-queue/n from hint:1001@ext-local) (ringinuse enabled) (In use) has taken..."
            if (preg_match('/^\s+(.+?)\s+\(((?:SIP|PJSIP|Local|Agent)\/[^\s)]+)(?:\s+from\s+[^)]+)?\)/', $line, $mm)) {
                $name = trim($mm[1]);
                $iface = $mm[2];
                // Si el "name" parece otro Interface (ej la línea empieza directo con SIP/9999) cae al patrón B
                if (!preg_match('|^(SIP|PJSIP|Local|Agent)/|', $name)) {
                    // Skip rows que no son members: "(ringinuse enabled)" como name no se da porque el .+? es non-greedy
                    if (stripos($line, 'has taken') !== false || stripos($line, 'login was') !== false || stripos($line, 'last was') !== false || strpos($iface, '/') !== false) {
                        // Extraer ext del iface si es Local/N@... o SIP/N
                        $ext = null;
                        if (preg_match('|^(SIP|PJSIP|Agent)/(\d+)$|', $iface, $em)) $ext = $em[2];
                        elseif (preg_match('|^Local/(\d+)@|', $iface, $em)) $ext = $em[1];
                        $members_by_q[$cur_q][] = ['name'=>$name, 'iface'=>$iface, 'ext'=>$ext, 'kind'=>'static'];
                        continue;
                    }
                }
            }

            // Patrón B — DYNAMIC (interface es el "name"):
            //   "    SIP/9999 (ringinuse enabled) (dynamic) (Invalid) has taken no calls yet (login was 446728 secs ago)"
            //   "    PJSIP/1001 (dynamic) (Not in use) has taken 3 calls"
            if (preg_match('/^\s+((?:SIP|PJSIP|Local|Agent)\/[^\s(]+)(?:\s+\(|\s*$)/', $line, $mm)) {
                $iface = $mm[1];
                $ext = null;
                if (preg_match('|^(SIP|PJSIP|Agent)/(\d+)$|', $iface, $em)) $ext = $em[2];
                elseif (preg_match('|^Local/(\d+)@|', $iface, $em)) $ext = $em[1];
                $members_by_q[$cur_q][] = ['name'=>$iface, 'iface'=>$iface, 'ext'=>$ext, 'kind'=>'dynamic'];
            }
        }
        foreach ($queues as &$q) {
            $q['members'] = $members_by_q[$q['id']] ?? [];
            $q['calls_waiting'] = $waiting_by_q[$q['id']] ?? 0;
        }
        $ringgroups = $db2->query("SELECT grpnum as id, description as name, grplist as members FROM ringgroups")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ringgroups as &$rg) {
            // Split by hyphen, newline, or comma
            $rg['members'] = preg_split('/[-,\n\r]+/', $rg['members'], -1, PREG_SPLIT_NO_EMPTY);
        }
        $ivrs = $db2->query("SELECT id, name FROM ivr_details")->fetchAll(PDO::FETCH_ASSOC);
        $trunks = [];
        try { $trunks = $db2->query("SELECT trunkid as id, name FROM trunks WHERE disabled='off'")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $_e) { $trunks = []; }
    } catch(Exception $e) {}

    // HORIZON: incluir user para session restore en cliente
    $session_user = null;
    if (!empty($_SESSION['tf_user'])) {
        $session_user = ['name' => $_SESSION['tf_user'], 'role' => 'admin'];
    } elseif (!empty($_SESSION['agent_user'])) {
        $au = $_SESSION['agent_user'];
        $session_user = ['name' => $au['agent_name']??'Agent', 'role' => 'agent', 'agent' => $au];
    }
    $tf_payload = json_encode([
        'user'   => $session_user,
        'system' => ['cpu' => round($load[0] * 25), 'uptime' => $uptime, 'ram' => $ram, 'disk' => $disk, 'connections' => $conn],
        'pbx'    => [
            'extensions' => array_values($exts), 
            'recordings' => $recordings, 
            'live_calls' => $live_calls,
            'queues' => $queues,
            'ringgroups' => $ringgroups,
            'ivrs' => $ivrs,
            'trunks' => $trunks
        ],
    ]);
    // HORIZON CACHE v7: escribir cache + liberar lock; responder solo si no respondimos antes
    @file_put_contents($tf_cache_file, $tf_payload, LOCK_EX);
    @chmod($tf_cache_file, 0666);
    if (!empty($tf_lock)) { flock($tf_lock, LOCK_UN); fclose($tf_lock); }
    if (empty($tf_response_sent)) {
        header("Content-Type: application/json");
        header("X-TF-Cache: MISS");
        echo $tf_payload;
    }
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

        // 1. devices table — chan_sip, NO pjsip (Issabel usa chan_sip)
        $db->prepare("INSERT INTO devices (id, tech, dial, devicetype, user, description, emergency_cid) VALUES (?, ?, ?, 'fixed', ?, ?, '')")
           ->execute([$ext, 'sip', "SIP/$ext", $ext, $name]);

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

        // Borrado en cascada de las 3 tablas FreePBX
        $delDev  = $db->prepare("DELETE FROM devices WHERE id=?");  $delDev->execute([$ext]);
        $delUsr  = $db->prepare("DELETE FROM users WHERE extension=?"); $delUsr->execute([$ext]);
        $delSip  = $db->prepare("DELETE FROM sip WHERE id=?");      $delSip->execute([$ext]);

        // Si no se afectó ni una sola fila, la ext no existía en BD
        $totalDeleted = $delDev->rowCount() + $delUsr->rowCount() + $delSip->rowCount();
        if ($totalDeleted === 0) {
            echo json_encode(['success' => false, 'error' => "Extensión $ext no existe en la base de datos"]);
            exit;
        }

        // Limpiar avatar local
        @unlink($GLOBALS['avatar_dir'] . "$ext.jpg");

        // Limpiar ext_meta de Teleflow (RTSP url, bocina, dtmf, etc.)
        try {
            $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8", $DB_USER, $DB_PASS);
            $tf->prepare("DELETE FROM ext_meta WHERE ext=?")->execute([$ext]);
        } catch (Exception $_) {}

        // CRÍTICO: retrieve_conf regenera /etc/asterisk/sip_additional.conf SIN el peer,
        // y sip reload hace que chan_sip lo olvide en RAM. Sin esto el peer sigue
        // registrable aunque ya no esté en MySQL.
        pbx_ssh_exec("/var/lib/asterisk/bin/retrieve_conf >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'sip reload' >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'dialplan reload' >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'database del DEVICE $ext/user' >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'database del DEVICE $ext/dial' >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'database del DEVICE $ext/type' >/dev/null 2>&1; "
                   . "/usr/sbin/asterisk -rx 'database del AMPUSER $ext/device' >/dev/null 2>&1");

        echo json_encode(['success' => true, 'message' => "Extensión $ext eliminada", 'rows' => $totalDeleted]);
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


// ─── EXT META (tipo: cliente / horizon / vacío) ───────────────────────────────
if ($action === 'get_ext_meta') {
    try {
        $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8", $DB_USER, $DB_PASS);
        $rows = $tf->query("SELECT ext, tipo, notes, rtsp_url, rtsp_label, IFNULL(is_bocina,0) AS is_bocina, IFNULL(door_dtmf_code,'*9') AS door_dtmf_code, rtsp_url_source, rtsp_auto_tried_at FROM ext_meta")->fetchAll(PDO::FETCH_ASSOC);
        $map = []; foreach ($rows as $r) $map[$r['ext']] = $r;
        echo json_encode(['success'=>true, 'meta'=>$map]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}
if ($action === 'set_ext_meta') {
    $ext  = preg_replace('/\D/', '', $_POST['ext'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $rtsp_url = trim($_POST['rtsp_url'] ?? '');
    $rtsp_label = trim($_POST['rtsp_label'] ?? '');
    $is_bocina = !empty($_POST['is_bocina']) && $_POST['is_bocina'] !== 'false' && $_POST['is_bocina'] !== '0' ? 1 : 0;
    $door_dtmf_code = trim($_POST['door_dtmf_code'] ?? '');
    if ($door_dtmf_code) {
        $door_dtmf_code = preg_replace('/[^*#0-9]/', '', $door_dtmf_code);
        if (strlen($door_dtmf_code) > 8) $door_dtmf_code = substr($door_dtmf_code, 0, 8);
    }
    if (!in_array($tipo, ['cliente','horizon',''])) { echo json_encode(['success'=>false,'error'=>'tipo inválido']); exit; }
    if ($rtsp_url && !preg_match('#^(rtsp|rtsps|http|https)://#i', $rtsp_url)) {
        echo json_encode(['success'=>false,'error'=>'rtsp_url debe empezar con rtsp:// rtsps:// http:// o https://']); exit;
    }
    if (strlen($rtsp_url) > 500) { echo json_encode(['success'=>false,'error'=>'rtsp_url demasiado largo']); exit; }
    if (strlen($rtsp_label) > 80) $rtsp_label = substr($rtsp_label, 0, 80);
    try {
        $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8", $DB_USER, $DB_PASS);
        // Asegurar columnas (idempotentes)
        try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN is_bocina TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $_) {}
        try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN door_dtmf_code VARCHAR(8) NULL"); } catch(Exception $_) {}
        try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN rtsp_url_source ENUM('manual','auto') NULL DEFAULT NULL"); } catch(Exception $_) {}
        try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN rtsp_auto_tried_at DATETIME NULL DEFAULT NULL"); } catch(Exception $_) {}
        // Cuando viene desde la UI: si hay rtsp_url => marcar 'manual'. Si se vació => null.
        $src = $rtsp_url ? 'manual' : null;
        $stmt = $tf->prepare("INSERT INTO ext_meta (ext, tipo, notes, rtsp_url, rtsp_label, is_bocina, door_dtmf_code, rtsp_url_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE tipo=VALUES(tipo), notes=VALUES(notes), rtsp_url=VALUES(rtsp_url), rtsp_label=VALUES(rtsp_label), is_bocina=VALUES(is_bocina), door_dtmf_code=VALUES(door_dtmf_code), rtsp_url_source=VALUES(rtsp_url_source)");
        $stmt->execute([$ext, $tipo, $notes, $rtsp_url ?: null, $rtsp_label ?: null, $is_bocina, $door_dtmf_code ?: null, $src]);
        // Notificar al realtime hub para refrescar su cache RTSP (best-effort, non-blocking)
        @file_get_contents('http://127.0.0.1:9001/broadcast', false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json
X-TF-Notify: rtsp_meta_changed
",
                'content' => json_encode(['event'=>'rtsp_meta_changed','ext'=>$ext]),
                'timeout' => 1
            ]
        ]));
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}


// ─── DETECT PBX BRAND (Asterisk vanilla / Issabel / FreePBX / Grandstream UCM) ────
if ($action === 'detect_pbx') {
    $cache_file = '/tmp/teleflow_pbx_brand.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300) {
        readfile($cache_file);
        exit;
    }
    $version_out = ami_cmd('core show version');
    $settings_out = ami_cmd('core show settings');
    $modules_out = ami_cmd('module show');
    
    $brand = 'asterisk';
    $variant = 'vanilla';
    $features = ['chan_sip' => false, 'pjsip' => false, 'queue_log_realtime' => false];
    
    // Detección
    if (stripos($version_out, 'grandstream') !== false || stripos($settings_out, 'grandstream') !== false || stripos($modules_out, 'res_ucm') !== false) {
        $brand = 'grandstream';
        $variant = 'ucm';
        $features['pjsip'] = true;
        $features['chan_sip'] = false;
    } elseif (stripos($version_out, 'issabel') !== false || file_exists('/etc/issabel.conf') || stripos($modules_out, 'issabel') !== false) {
        $brand = 'asterisk';
        $variant = 'issabel';
        $features['chan_sip'] = (stripos($modules_out, 'chan_sip') !== false);
        $features['pjsip'] = (stripos($modules_out, 'res_pjsip') !== false || stripos($modules_out, 'chan_pjsip') !== false);
    } elseif (stripos($version_out, 'freepbx') !== false) {
        $brand = 'asterisk';
        $variant = 'freepbx';
        $features['chan_sip'] = (stripos($modules_out, 'chan_sip') !== false);
        $features['pjsip'] = (stripos($modules_out, 'res_pjsip') !== false);
    } else {
        // Asterisk vanilla — detectar pjsip vs chan_sip
        $features['chan_sip'] = (stripos($modules_out, 'chan_sip.so') !== false);
        $features['pjsip'] = (stripos($modules_out, 'res_pjsip.so') !== false || stripos($modules_out, 'chan_pjsip.so') !== false);
    }
    
    preg_match('/Asterisk\s+([\d.]+)/i', $version_out, $vm);
    $asterisk_version = $vm[1] ?? 'unknown';
    
    $payload = json_encode([
        'success' => true,
        'brand' => $brand,
        'variant' => $variant,
        'asterisk_version' => $asterisk_version,
        'features' => $features,
        'detected_at' => date('Y-m-d H:i:s'),
        'recommended_parser' => ($brand === 'grandstream' || (!$features['chan_sip'] && $features['pjsip'])) ? 'pjsip' : 'sip'
    ]);
    @file_put_contents($cache_file, $payload, LOCK_EX);
    @chmod($cache_file, 0666);
    header('Content-Type: application/json');
    echo $payload;
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
    // HORIZON: cache 2s para no saturar AMI con polling cada 2.5s
    @set_time_limit(30);
    $ac_cache = '/tmp/teleflow_active_calls.json';
    if (file_exists($ac_cache) && (time() - filemtime($ac_cache)) < 2) {
        header('Content-Type: application/json');
        readfile($ac_cache);
        exit;
    }
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

    $ac_payload = json_encode(['success' => true, 'calls' => $unique_calls, 'count' => count($unique_calls)]);
    @file_put_contents($ac_cache, $ac_payload, LOCK_EX); @chmod($ac_cache, 0666);
    header('Content-Type: application/json');
    echo $ac_payload;
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
            // ChanSpy: detectar tech del canal a espiar y del supervisor
            $target_ext = preg_replace('/^(?:PJSIP|SIP)\/(\d+)-.*$/', '$1', $channel);
            $tech = (strpos($channel, 'PJSIP/') === 0) ? 'PJSIP' : 'SIP';
            // El supervisor probablemente sea chan_sip (Issabel default)
            $sup_tech = 'SIP';
            $cmd = "channel originate $sup_tech/$supervisor application ChanSpy $tech/$target_ext,q$opt";
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
