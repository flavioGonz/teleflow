<?php
/**
 * api/asterisk.php — inspector y control de Asterisk (config + AMI)
 *
 * Endpoints:
 *   GET  ?action=files                — lista archivos /etc/asterisk/ (read-only via SSH al PBX)
 *   GET  ?action=read_file&file=X     — contenido de un archivo de config
 *   POST ?action=ami_cmd              — ejecuta comando AMI arbitrario (whitelist)
 *
 * Whitelist AMI: solo comandos de consulta (show, status), nada destructivo
 *
 * HORIZON · Teleflow
 */
require_once __DIR__ . '/../config.php';
// F5.2: auth admin + session + JSON headers via _bootstrap.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// AMI helper — reusa la conexión que ya tiene api/index.php (mini-port)
function tf_ami_cmd($command) {
    global $AMI_HOST, $AMI_USER, $AMI_PASS;
    $h = isset($AMI_HOST) ? $AMI_HOST : '10.1.1.7';
    $u = isset($AMI_USER) ? $AMI_USER : 'admin';
    $p = isset($AMI_PASS) ? $AMI_PASS : '';
    $sock = @fsockopen($h, 5038, $errno, $errstr, 5);
    if (!$sock) return "ERROR: AMI connection failed ($errstr)";
    stream_set_timeout($sock, 10);
    fputs($sock, "Action: Login\r\nUsername: $u\r\nSecret: $p\r\nEvents: off\r\n\r\n");
    fputs($sock, "Action: Command\r\nCommand: $command\r\n\r\n");
    fputs($sock, "Action: Logoff\r\n\r\n");
    $out = '';
    $start = microtime(true);
    while (!feof($sock) && (microtime(true) - $start) < 8) {
        $line = fgets($sock, 4096);
        if ($line === false) break;
        $out .= $line;
        if (preg_match('/Response: Goodbye/i', $line)) break;
    }
    fclose($sock);
    // Strip AMI control headers
    $out = preg_replace('/^Response:.*?\r?\n(Message:.*?\r?\n)?/mi', '', $out);
    $out = preg_replace('/--END COMMAND--/i', '', $out);
    return trim($out);
}

// ─── action=files ─ lista archivos seguros para leer ──────────────
if ($action === 'files') {
    // Lista whitelist de archivos de config que mostramos
    $files = [
        ['name'=>'extensions.conf',           'desc'=>'Dialplan principal'],
        ['name'=>'extensions_additional.conf','desc'=>'Dialplan generado por FreePBX/Issabel'],
        ['name'=>'extensions_custom.conf',    'desc'=>'Dialplan custom (modificable)'],
        ['name'=>'pjsip.conf',                'desc'=>'PJSIP endpoints/aor/auth'],
        ['name'=>'sip.conf',                  'desc'=>'SIP legacy'],
        ['name'=>'queues.conf',               'desc'=>'Configuración de colas'],
        ['name'=>'queues_additional.conf',    'desc'=>'Colas generadas por FreePBX'],
        ['name'=>'manager.conf',              'desc'=>'AMI manager usuarios'],
        ['name'=>'manager_custom.conf',       'desc'=>'AMI usuarios custom'],
        ['name'=>'iax.conf',                  'desc'=>'IAX2 trunks'],
        ['name'=>'features.conf',             'desc'=>'Feature codes globales'],
        ['name'=>'rtp.conf',                  'desc'=>'RTP ports y settings'],
    ];
    echo json_encode(['status'=>'ok','files'=>$files]);
    exit;
}

// ─── action=read_file ─ contenido del archivo via SSH al PBX ──────
if ($action === 'read_file') {
    $file = basename($_GET['file'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]+\.conf$/', $file)) {
        echo json_encode(['status'=>'error','message'=>'Nombre de archivo inválido']); exit;
    }
    $remote = "/etc/asterisk/$file";
    $cmd = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=4 root@10.1.1.7 'cat " . escapeshellarg($remote) . " 2>/dev/null || echo NOTFOUND'";
    $out = trim(shell_exec($cmd) ?? '');
    if ($out === 'NOTFOUND' || $out === '') {
        echo json_encode(['status'=>'error','message'=>"Archivo no encontrado o inaccesible: $file"]); exit;
    }
    echo json_encode([
        'status'=>'ok',
        'file'=>$file,
        'content'=>$out,
        'size'=>strlen($out),
        'lines'=>substr_count($out, "\n")+1
    ]);
    exit;
}

// ─── action=ami_cmd ─ ejecuta comando AMI (whitelist) ──────────────
if ($action === 'ami_cmd') {
    $cmd = trim($_POST['cmd'] ?? '');
    if (!$cmd) { echo json_encode(['status'=>'error','message'=>'cmd requerido']); exit; }
    // Whitelist: solo comandos de consulta. Lista de prefijos permitidos
    $allowed_prefixes = [
        'core show', 'pjsip show', 'sip show', 'iax2 show', 'queue show',
        'channel originate', // NO permitido (queda para futuro)
        'manager show', 'module show', 'database show', 'dialplan show'
    ];
    $is_allowed = false;
    foreach ($allowed_prefixes as $pref) {
        if ($pref === 'channel originate') continue; // explicitly blocked
        if (stripos($cmd, $pref) === 0) { $is_allowed = true; break; }
    }
    if (!$is_allowed) {
        echo json_encode(['status'=>'error','message'=>"Comando no permitido. Solo: core/pjsip/sip/iax2/queue/manager/module/database/dialplan show"]); exit;
    }
    $out = tf_ami_cmd($cmd);
    echo json_encode(['status'=>'ok','cmd'=>$cmd,'output'=>$out,'ts'=>date('Y-m-d H:i:s')]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Acción desconocida']);
