
<?php
/**
 * TeleFlow — API del Agente (Call Center)
 *
 * POST ?action=login          { agent_number, password, callback_extension }
 * POST ?action=logout
 * POST ?action=pause          { pause_type_code }
 * POST ?action=unpause
 * GET  ?action=status         → estado actual del agente logueado
 * GET  ?action=pause_types    → catálogo de tipos de pausa
 * GET  ?action=my_queues      → colas asignadas al agente actual
 */
// F5.1: header consolidado via api/_bootstrap.php.
// TTL 8h + SameSite + JSON headers ahora se setean centralmente.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap();
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

function tf_db() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
function pbx_cc_db() {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=call_center;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/**
 * AMI socket helper — abre conexión, login, ejecuta acción, devuelve respuesta
 */
function ami_action(array $params) {
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    $sock = @fsockopen($AMI_HOST, (int)$AMI_PORT, $errno, $errstr, 4);
    if (!$sock) throw new Exception("No conecta AMI: $errstr ($errno)");
    stream_set_timeout($sock, 4);
    fgets($sock); // greeting
    fwrite($sock, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
    $resp = '';
    $start = microtime(true);
    while (microtime(true)-$start < 2) {
        $l = fgets($sock); if ($l === false) break;
        $resp .= $l;
        if (trim($l) === '' && strpos($resp, 'Response:') !== false) break;
    }
    if (strpos($resp, 'Authentication accepted') === false) {
        fclose($sock);
        throw new Exception("AMI auth failed");
    }
    $msg = '';
    foreach ($params as $k => $v) $msg .= "$k: $v\r\n";
    $msg .= "ActionID: tf-" . uniqid() . "\r\n\r\n";
    fwrite($sock, $msg);
    $r = '';
    $start = microtime(true);
    while (microtime(true)-$start < 3) {
        $l = fgets($sock); if ($l === false) break;
        $r .= $l;
        if (trim($l) === '' && strpos($r, 'Response:') !== false) break;
    }
    fwrite($sock, "Action: Logoff\r\n\r\n");
    fclose($sock);
    return $r;
}

try {
    $tf = tf_db();

    // ─── LOGIN AGENTE ───────────────────────────────────────────────────
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $agentNum = trim($_POST['agent_number'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $callbackExt = trim($_POST['callback_extension'] ?? '');
        $useFallback = !empty($_POST['use_fallback']) && $_POST['use_fallback'] !== 'false';
        if (!$agentNum || !$pass) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Falta agent_number o password']);
            exit;
        }

        // Si NO se usa fallback fisico, buscar la ext WebRTC 1:1 asignada al agente
        if (!$useFallback || !$callbackExt) {
            try {
                $stW = $tf->prepare("SELECT webrtc_ext FROM agent_webrtc WHERE agent_number = ?");
                $stW->execute([$agentNum]);
                $webrtcExt = $stW->fetchColumn();
                if ($webrtcExt) {
                    $callbackExt = $webrtcExt;  // usar WebRTC como callback interno
                }
            } catch (Exception $e) {}
        }

        if (!$callbackExt) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Este agente no tiene interno WebRTC asignado. Debe activar el fallback físico o pedirle al admin que le asigne un interno WebRTC.']);
            exit;
        }

        // 1) Validar contra BD remota call_center.agent
        $cc = pbx_cc_db();
        $st = $cc->prepare("SELECT id, type, number, name, password FROM agent WHERE number = ? AND estatus = 'A'");
        $st->execute([$agentNum]);
        $agent = $st->fetch(PDO::FETCH_ASSOC);
        if (!$agent || $agent['password'] !== $pass) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas o agente inactivo']);
            exit;
        }

        // 2) Cerrar cualquier sesión agent_sessions ZOMBIE del mismo agente (sin logout_time)
        //    para que el timer arriba refleje el login actual y no acumule tiempo viejo.
        $tf->prepare("UPDATE agent_sessions SET logout_time=NOW(), status='CLOSED' WHERE agent_ext=? AND logout_time IS NULL")
           ->execute([$agentNum]);
        // También destruir sesión PHP anterior para forzar timer nuevo desde 0
        session_regenerate_id(true);
        unset($_SESSION["agent_user"]);
        // Abrir NUEVA sesión TeleFlow (sin colas todavía)
        $sessionId = 'tf-' . date('Ymd') . '-' . $agentNum . '-' . substr(uniqid(), -6);
        $tf->prepare("INSERT INTO agent_sessions (session_id, agent_ext, login_time, shift_date, status) VALUES (?, ?, NOW(), CURDATE(), 'ACTIVE')")
           ->execute([$sessionId, $agentNum]);

        // 3) Identificador del agente para AMI (Agent/N o SIP/N)
        $sAgente = $agent['type'] . '/' . $agent['number'];
        $iface = preg_match('|^\w+/\d+$|', $callbackExt) ? $callbackExt : "SIP/$callbackExt";

        // 4) Listado de colas del sistema (source of truth = asterisk.queues_config)
        //    + preselección por membership real (queues_details) o preferencias guardadas.
        //    Antes: filtraba por queue_call_entry (módulo call_center) — se perdían colas
        //    que existían en queues_config pero no tenían entries en ese módulo.
        $available_queues = [];
        $memberships = [];   // colas donde el callback_ext YA es member
        try {
            $pbxdb = new PDO("mysql:host=$PBX_DB_HOST;dbname=asterisk;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $rows = $pbxdb->query("SELECT extension, descr FROM queues_config ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $available_queues[] = ["queue" => $r["extension"], "name" => $r["descr"] ?: null];
            }
            // Membership real: buscar en queues_details donde data LIKE 'Local/{ext}@%'
            $extNum = preg_replace('|^\w+/|', '', $iface);
            $stm = $pbxdb->prepare("SELECT DISTINCT id FROM queues_details WHERE keyword='member' AND data LIKE ?");
            $stm->execute(["Local/{$extNum}@from-queue/%"]);
            $memberships = $stm->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}

        // Prefs guardadas en agent_queue_pref (si las hay) — sino usa membership real
        $pref_queues = [];
        try {
            $st = $tf->prepare("SELECT queue FROM agent_queue_pref WHERE agent_number = ?");
            $st->execute([$agentNum]);
            $pref_queues = $st->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}
        if (empty($pref_queues) && !empty($memberships)) $pref_queues = $memberships;

        $_SESSION['agent_user'] = [
            'session_id' => $sessionId,
            'agent_id' => $agent['id'],
            'agent_number' => $agentNum,
            'agent_name' => $agent['name'],
            'sAgente' => $sAgente,
            'callback_ext' => $iface,
            'queues' => [],   // se completa con action=join_queues
            'login_time' => date('Y-m-d H:i:s'),
            'pending_queue_selection' => true,
        ];

        echo json_encode([
            'status' => 'success',
            'agent' => [
                'number' => $agentNum,
                'name' => $agent['name'],
                'type' => $agent['type'],
                'callback' => $iface,
                'queues' => [],
                'session_id' => $sessionId,
                'available_queues' => $available_queues,
                'pref_queues' => $pref_queues,
                'pending_queue_selection' => true,
            ],
        ]);
        exit;
    }

    // ─── JOIN QUEUES (paso 2 del login: agente elige qué colas atender) ────
    if ($action === 'join_queues' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Sin sesión activa']);
            exit;
        }
        // Aceptar queues como CSV o como array
        $raw = $_POST['queues'] ?? '';
        $queues = is_array($raw) ? $raw : array_filter(array_map('trim', explode(',', (string)$raw)));
        // Sanitizar contra las colas REALES del PBX (asterisk.queues_config es source of truth,
        // no queue_call_entry que solo tiene las del módulo call_center activo).
        try {
            $pbxdb = new PDO("mysql:host=$PBX_DB_HOST;dbname=asterisk;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $valid = $pbxdb->query("SELECT extension FROM queues_config")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) { $valid = []; }
        // Match como strings para evitar mismatch tipo int vs string
        $validStr = array_map('strval', $valid);
        $queues = array_values(array_intersect(array_map('strval', $queues), $validStr));

        $iface    = $a['callback_ext'];       // SIP/505 (state interface real)
        $sAgente  = $a['sAgente'];
        // Extraer ext numerico para construir el Local channel del member
        $extNum   = preg_replace('|^\w+/|', '', $iface);
        $memberIface = "Local/{$extNum}@from-queue/n";  // canal Local siempre idle (fix Status:5 UNREACHABLE en chan_sip+WebRTC)
        $addedQueues = [];
        $joinLog = [];
        foreach ($queues as $q) {
            try {
                $r = ami_action([
                    'Action' => 'QueueAdd',
                    'Queue' => $q,
                    'Interface' => $memberIface,   // Local/{ext}@from-queue/n
                    'Penalty' => 0,
                    'MemberName' => $sAgente,
                    // NO StateInterface: Asterisk usa status del Local channel = idle siempre
                    // (evita bug chan_sip+WSS+qualify que marca UNREACHABLE)
                ]);
                $ok = (strpos($r, 'Response: Success') !== false || strpos($r, 'Added') !== false || strpos($r, 'Already there') !== false);
                if ($ok) $addedQueues[] = $q;
                // Extract response reason
                $reason = "";
                if (preg_match('/Message: (.+)/', $r, $m)) $reason = trim($m[1]);
                $joinLog[] = ["queue"=>$q, "ami_ok"=>$ok, "reason"=>$reason];
                @file_put_contents('/tmp/tf_join.log', '[' . date('H:i:s') . "] agent=$sAgente iface=$iface queue=$q ok=" . ($ok?'Y':'N') . " reason=$reason
", FILE_APPEND | LOCK_EX);
            } catch (Exception $e) {
                $joinLog[] = ["queue"=>$q, "ami_ok"=>false, "reason"=>$e->getMessage()];
            }
        }

        // Persistir prefs (para auto-preselección futura)
        try {
            $tf->prepare("DELETE FROM agent_queue_pref WHERE agent_number = ?")->execute([$a['agent_number']]);
            $ins = $tf->prepare("INSERT INTO agent_queue_pref (agent_number, queue, penalty) VALUES (?, ?, 0)");
            foreach ($addedQueues as $q) {
                try { $ins->execute([$a['agent_number'], $q]); } catch (Exception $e) {}
            }
        } catch (Exception $e) {}

        // Guardar TODAS las colas elegidas por el agente (fuente de verdad para UI).
        // AMI puede rechazar QueueAdd si el driver/interface no matchea — igualmente
        // la UI debe reflejar la selección y el agente puede recibir llamadas via failover.
        $_SESSION['agent_user']['queues'] = $queues;                     // elegidas
        $_SESSION['agent_user']['queues_ami_ok'] = $addedQueues;        // aceptadas por AMI
        unset($_SESSION['agent_user']['pending_queue_selection']);

        // Notificar al hub realtime con las AMI OK (para que sepa a qué colas alertar)
        @file_get_contents('http://127.0.0.1/api/notify.php?event=agent_login&agent='.urlencode($a['agent_number']).'&ext='.urlencode(preg_replace('|^\w+/|', '', $iface)).'&queues='.urlencode(implode(',', $addedQueues)));

        echo json_encode([
            'status' => 'success',
            'joined_queues' => $queues,
            'ami_accepted' => $addedQueues,
            'ami_log' => $joinLog,               // debug detallado por cola
            'iface' => $iface,                    // el Interface real usado en QueueAdd
        ]);
        exit;
    }

    // ─── LOGOUT ─────────────────────────────────────────────────────────
    if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { echo json_encode(['status' => 'ok']); exit; }

        // QueueRemove de cada cola — usar Local/N@from-queue/n (mismo Interface del join)
        $extNum = preg_replace('|^\w+/|', '', $a['callback_ext']);
        $memberIface = "Local/{$extNum}@from-queue/n";
        foreach ($a['queues'] as $q) {
            try {
                ami_action([
                    'Action' => 'QueueRemove',
                    'Queue' => $q,
                    'Interface' => $memberIface,
                ]);
            } catch (Exception $e) { /* continuar */ }
        }

        // Cerrar sesión TF
        $tf->prepare("UPDATE agent_sessions SET logout_time=NOW(), status='CLOSED' WHERE session_id=?")
           ->execute([$a['session_id']]);

        // Cerrar pausa abierta si hay
        $tf->prepare("UPDATE agent_pauses SET pause_end=NOW(), duration_seconds=TIMESTAMPDIFF(SECOND, pause_start, NOW()) WHERE session_id=? AND pause_end IS NULL")
           ->execute([$a['session_id']]);

        unset($_SESSION['agent_user']);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // ─── PAUSE ──────────────────────────────────────────────────────────
    if ($action === 'pause' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { http_response_code(403); echo json_encode(['status' => 'error', 'message' => 'No logueado']); exit; }
        $code = trim($_POST['pause_type_code'] ?? 'BREAK');

        // Validar tipo de pausa
        $st = $tf->prepare("SELECT label FROM pause_types WHERE code = ? AND active=1");
        $st->execute([$code]);
        $pt = $st->fetch(PDO::FETCH_ASSOC);
        if (!$pt) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Tipo de pausa inválido']); exit; }

        // QueuePause via AMI (en todas las colas)
        foreach ($a['queues'] as $q) {
            try {
                ami_action([
                    'Action' => 'QueuePause',
                    'Queue' => $q,
                    'Interface' => $a['callback_ext'],
                    'Paused' => 'true',
                    'Reason' => $pt['label'],
                ]);
            } catch (Exception $e) { /* continuar */ }
        }

        // Registrar pausa
        $tf->prepare("INSERT INTO agent_pauses (agent_ext, pause_type_code, session_id) VALUES (?, ?, ?)")
           ->execute([$a['agent_number'], $code, $a['session_id']]);

        echo json_encode(['status' => 'ok', 'pause' => $pt['label']]);
        exit;
    }

    if ($action === 'unpause' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { http_response_code(403); echo json_encode(['status' => 'error']); exit; }

        foreach ($a['queues'] as $q) {
            try {
                ami_action([
                    'Action' => 'QueuePause',
                    'Queue' => $q,
                    'Interface' => $a['callback_ext'],
                    'Paused' => 'false',
                ]);
            } catch (Exception $e) { /* continuar */ }
        }

        $tf->prepare("UPDATE agent_pauses SET pause_end=NOW(), duration_seconds=TIMESTAMPDIFF(SECOND, pause_start, NOW()) WHERE session_id=? AND pause_end IS NULL")
           ->execute([$a['session_id']]);

        echo json_encode(['status' => 'ok']);
        exit;
    }

    // ─── STATUS ─────────────────────────────────────────────────────────
    if ($action === 'status') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { echo json_encode(['status' => 'logged_out']); exit; }
        $stmt = $tf->prepare("SELECT pt.label, pt.color, ap.pause_start FROM agent_pauses ap JOIN pause_types pt ON pt.code=ap.pause_type_code WHERE ap.session_id=? AND ap.pause_end IS NULL");
        $stmt->execute([$a['session_id']]);
        $currentPause = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $tf->prepare("SELECT login_time, total_calls, total_talk_time FROM agent_sessions WHERE session_id=?");
        $stmt->execute([$a['session_id']]);
        $sess = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => $currentPause ? 'paused' : 'available',
            'agent' => $a,
            'session' => $sess,
            'pause' => $currentPause,
        ]);
        exit;
    }

    if ($action === 'softphone_creds') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { echo json_encode(['status'=>'error','message'=>'sin_sesion']); exit; }
        // 1) PRIORIDAD: si el agente tiene webrtc_ext asignado en teleflow.agent_webrtc, usar ese
        // 2) Fallback: extension del callback (config vieja)
        $ext = null; $secretFromMap = null;
        try {
            $stW = $tf->prepare("SELECT webrtc_ext, webrtc_secret FROM agent_webrtc WHERE agent_number = ?");
            $stW->execute([$a['agent_number'] ?? '']);
            $wRow = $stW->fetch(PDO::FETCH_ASSOC);
            if ($wRow) { $ext = $wRow['webrtc_ext']; $secretFromMap = $wRow['webrtc_secret']; }
        } catch (Exception $e) {}
        if (!$ext) {
            $ext = preg_replace('/^\w+\//', '', $a['callback_ext'] ?? '');
        }
        if (!$ext) { echo json_encode(['status'=>'error','message'=>'sin_ext_webrtc_ni_callback']); exit; }
        try {
            require_once __DIR__ . '/../config.php';
            $db = new PDO("mysql:host=$PBX_DB_HOST;dbname=asterisk;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $stmt = $db->prepare("SELECT keyword, data FROM sip WHERE id=?");
            $stmt->execute([$ext]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $creds = ['ext' => $ext, 'secret' => $secretFromMap, 'transport' => 'udp', 'has_ws' => false];
            foreach ($rows as $r) {
                if ($r['keyword'] === 'secret') $creds['secret'] = $r['data'];
                if ($r['keyword'] === 'transport') { $creds['transport'] = $r['data']; $creds['has_ws'] = strpos($r['data'], 'ws') !== false; }
            }
            $creds['status'] = 'ok';
            $creds['wss_url'] = 'wss://pbx-prod.horizonseguridad.com:8089/ws';
            $creds['domain'] = 'pbx-prod.horizonseguridad.com';
            $creds['ws_ready'] = $creds['has_ws'] && !empty($creds['secret']);
            $creds['warning'] = $creds['ws_ready'] ? null : 'La extension no tiene transport=ws configurado. Editar en Issabel: Extensiones -> ' . $ext . ' -> transport=ws,udp + encryption=yes + avpf=yes';
            echo json_encode($creds);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'agent_data') {
        // Endpoint focalizado del agente — devuelve extensions + queues sin requerir admin.
        // El panel del agente necesita esto para el buscador de internos y Mis colas.
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'sin_sesion']); exit; }
        try {
            $db = new PDO("mysql:host=$PBX_DB_HOST;dbname=asterisk;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            // Extensions (users) — para el buscador de internos
            $rows = $db->query("SELECT extension AS ext, name FROM users ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
            // Enrichir con last-seen del peer si el hub lo persistió (opcional)
            $exts = array_map(function($r){ return ['ext'=>$r['ext'], 'name'=>$r['name'], 'status'=>null]; }, $rows);
            // Queues — para Mis colas (enriquecidas con timeout, failover, strategy)
            $qs = $db->query("SELECT qc.extension AS id, qc.descr AS name, qc.dest AS failover, 
                                     (SELECT data FROM queues_details WHERE id=qc.extension AND keyword='timeout' LIMIT 1) AS timeout,
                                     (SELECT data FROM queues_details WHERE id=qc.extension AND keyword='strategy' LIMIT 1) AS strategy
                              FROM queues_config qc ORDER BY qc.extension")->fetchAll(PDO::FETCH_ASSOC);
            // Resolver failover a nombre legible ("ext-queues,9101,1" → "9101", "app-blackhole,hangup,1" → "hangup")
            foreach ($qs as &$q) {
                if (!empty($q["failover"])) {
                    if (preg_match("/ext-queues,(\d+),/", $q["failover"], $m)) $q["failover_to"] = $m[1];
                    elseif (strpos($q["failover"], "blackhole") !== false) $q["failover_to"] = "hangup";
                    else $q["failover_to"] = null;
                }
            }
            unset($q);
            // Members reales — UNA sola llamada AMI QueueStatus sin queue param (más rápido)
            $agentQueueIds = array_map('strval', $_SESSION["agent_user"]["queues"] ?? []);
            $queueMembers = [];
            foreach ($agentQueueIds as $qid) $queueMembers[$qid] = [];
            try {
                global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
                // AMI QueueStatus devuelve MUCHOS bloques — no usar ami_action() genérico
                $sock = @fsockopen($AMI_HOST, (int)$AMI_PORT, $errno, $errstr, 4);
                $ami_resp = "";
                if ($sock) {
                    stream_set_timeout($sock, 3);
                    fread($sock, 4096);
                    fwrite($sock, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
                    $end = microtime(true)+1;
                    while (microtime(true)<$end) { fread($sock, 4096); usleep(30000); }
                    fwrite($sock, "Action: QueueStatus\r\nActionID: tf-qm\r\n\r\n");
                    $end = microtime(true)+3;
                    while (microtime(true) < $end) {
                        $r = fread($sock, 8192);
                        if ($r) $ami_resp .= $r;
                        if (strpos($ami_resp, "QueueStatusComplete") !== false) break;
                        usleep(30000);
                    }
                    fwrite($sock, "Action: Logoff\r\n\r\n");
                    fclose($sock);
                }
                // Parsear cada QueueMember event
                $lines = explode("
", $ami_resp);
                $current = [];
                $eventType = null;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === "") {
                        if ($eventType === "QueueMember" && !empty($current["Queue"]) && !empty($current["Location"])) {
                            $q = trim($current["Queue"]);
                            if (in_array($q, $agentQueueIds)) {
                                $loc = $current["Location"];
                                $ext = preg_replace("|Local/(\d+)@.*|", "$1", $loc);
                                $ext = preg_replace("|^\w+/|", "", $ext);
                                $queueMembers[$q][] = [
                                    "ext" => $ext,
                                    "name" => $current["MemberName"] ?? $ext,
                                    "paused" => !empty($current["Paused"]) && $current["Paused"] === "1",
                                    "status" => isset($current["Status"]) ? intval($current["Status"]) : 0,
                                ];
                            }
                        }
                        $current = [];
                        $eventType = null;
                    } elseif (preg_match("/^Event: (.+)/", $line, $m)) {
                        $eventType = trim($m[1]);
                    } elseif (preg_match("/^([A-Za-z]+): (.*)/", $line, $m)) {
                        $current[$m[1]] = $m[2];
                    }
                }
            } catch (Exception $e) {
                error_log("QueueStatus AMI fail: " . $e->getMessage());
            }
            // Agents — para columna Agentes
            $agents = [];
            try {
                $cc = pbx_cc_db();
                $aRows = $cc->query("SELECT id, number, name FROM agent WHERE estatus='A' ORDER BY CAST(number AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
                $sess_rows = $tf->query("SELECT agent_ext, MAX(login_time) AS lt FROM agent_sessions WHERE logout_time IS NULL GROUP BY agent_ext")->fetchAll(PDO::FETCH_ASSOC);
                $sess_by_ext = [];
                foreach ($sess_rows as $r) $sess_by_ext[$r['agent_ext']] = $r['lt'];
                foreach ($aRows as $aa) {
                    $num = $aa['number'];
                    $agents[] = [
                        'number' => $num,
                        'name' => $aa['name'],
                        'logged_in' => isset($sess_by_ext[$num]),
                        'login_time' => $sess_by_ext[$num] ?? null,
                    ];
                }
            } catch (Exception $e) { $agents = []; }
            echo json_encode(['status'=>'ok', 'pbx'=>['extensions'=>$exts, 'queues'=>$qs, 'agents'=>$agents, 'queue_members'=>$queueMembers]]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'pickup_queue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { http_response_code(401); echo json_encode(['status'=>'error','error'=>'sin_sesion']); exit; }
        $queue = preg_replace('/[^0-9]/', '', $_POST['queue'] ?? '');
        if (!$queue) { echo json_encode(['status'=>'error','error'=>'missing queue']); exit; }
        // CRITICO: solo permitir pickup si el agente ESTA LOGUEADO en esta cola
        $agentQueues = array_map('strval', $a['queues'] ?? []);
        if (!in_array(strval($queue), $agentQueues, true)) {
            echo json_encode(['status'=>'error','error'=>'not_your_queue', 'queue'=>$queue, 'your_queues'=>$agentQueues]);
            exit;
        }
        $iface = $a['callback_ext']; // SIP/505
        $ext = preg_replace('|^\w+/|', '', $iface);
        try {
            $r = ami_action(['Action'=>'QueueStatus','Queue'=>$queue]);
            $callerChan = null;
            $blocks = explode("Event: QueueEntry", $r);
            foreach ($blocks as $b) {
                if (preg_match('/Channel: (\S+)/', $b, $m)) {
                    $callerChan = trim($m[1]); break;
                }
            }
            if (!$callerChan) { echo json_encode(['status'=>'error','error'=>'no_calls_in_queue']); exit; }
            $rr = ami_action([
                'Action' => 'Redirect',
                'Channel' => $callerChan,
                'Exten' => $ext,
                'Context' => 'from-internal',
                'Priority' => 1,
            ]);
            $ok = strpos($rr, 'Response: Success') !== false;
            echo json_encode(['status'=>$ok ? 'ok' : 'error', 'channel'=>$callerChan, 'ext'=>$ext, 'raw'=>substr($rr,0,300)]);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
        }
        exit;
    }

        if ($action === 'peer_status') {
        $ext = preg_replace('/[^0-9]/', '', $_GET['ext'] ?? '');
        if (!$ext) { echo json_encode(['status'=>'error','error'=>'missing ext']); exit; }
        try {
            $r = ami_action(['Action'=>'SIPshowpeer','Peer'=>$ext]);
            $status = 'unknown'; $addr = null; $ua = null;
            foreach (explode("
", $r) as $l) {
                if (preg_match('/^Status: (.+)/i', $l, $m)) $status = trim($m[1]);
                if (preg_match('/^Address-IP: (.+)/i', $l, $m)) $addr = trim($m[1]);
                if (preg_match('/^SIP-Useragent: (.+)/i', $l, $m)) $ua = trim($m[1]);
            }
            $reachable = (stripos($status, 'OK') !== false || stripos($status, 'Unmonitored') !== false);
            echo json_encode(['status'=>'ok', 'ext'=>$ext, 'sip_status'=>$status, 'reachable'=>$reachable, 'addr'=>$addr, 'ua'=>$ua]);
        } catch (Exception $e) { echo json_encode(['status'=>'error','error'=>$e->getMessage()]); }
        exit;
    }

        if ($action === 'resolve_target') {
        // Traduce un dialpadTarget del softphone a ext SIP real
        //  - si es 2XX (agent_number), busca webrtc_ext en agent_webrtc
        //  - si es 3-5 digitos y existe como peer SIP, lo devuelve tal cual
        //  - devuelve {status,resolved,target,label,error}
        $target = preg_replace('/[^0-9*#]/', '', $_GET['target'] ?? '');
        if (!$target) { echo json_encode(['status'=>'error','message'=>'missing target']); exit; }
        try {
            $tf = tf_db();
            // 1) si target es 2XX (agent_number 3 digitos empezando en 2)
            if (preg_match('/^2\d{2}$/', $target)) {
                $q = $tf->prepare("SELECT webrtc_ext FROM agent_webrtc WHERE agent_number=?");
                $q->execute([$target]);
                $r = $q->fetch(PDO::FETCH_ASSOC);
                if ($r && $r['webrtc_ext']) {
                    // Buscar nombre del agente (tabla call_center.agent)
                    $cc = pbx_cc_db();
                    $name = null;
                    try {
                        $qn = $cc->prepare("SELECT CONCAT_WS(' ', first_name, last_name) AS full_name FROM cc_agent WHERE agent = ? LIMIT 1");
                        $qn->execute([$target]);
                        $u = $qn->fetch(PDO::FETCH_ASSOC);
                        if ($u) $name = trim($u['full_name']);
                    } catch (Exception $_) {}
                    echo json_encode(['status'=>'ok', 'resolved'=>true, 'target'=>$r['webrtc_ext'], 'original'=>$target, 'label'=>($name ?: "Agente $target") . " (ext {$r['webrtc_ext']})"]);
                    exit;
                }
                // Agente sin webrtc asignado
                echo json_encode(['status'=>'error', 'resolved'=>false, 'target'=>$target, 'error'=>"El agente $target no tiene softphone WebRTC asignado. Pedile al admin que le asigne uno en Config → Agentes."]);
                exit;
            }
            // 2) target no es agent_number → devolverlo tal cual (extension SIP directa)
            echo json_encode(['status'=>'ok', 'resolved'=>false, 'target'=>$target]);
        } catch (Exception $e) {
            echo json_encode(['status'=>'error', 'error'=>'db_error: '.$e->getMessage()]);
        }
        exit;
    }

        if ($action === 'client_dossier') {
        $ext = $_GET['ext'] ?? '';
        if (!$ext) { echo json_encode(['status'=>'error','message'=>'missing ext']); exit; }
        $authorized = [];
        try {
            $pdo = tf_db();
            $t = $pdo->query("SHOW TABLES LIKE 'clients_authorized_persons'")->fetchAll();
            if ($t) {
                $q = $pdo->prepare("SELECT p.name, COALESCE(p.document,'') AS document, COALESCE(p.role,'') AS role FROM clients_authorized_persons p JOIN clients c ON c.id=p.client_id LEFT JOIN client_extensions ce ON ce.client_id=c.id WHERE ce.ext=? LIMIT 50");
                $q->execute([$ext]);
                $authorized = $q->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
        echo json_encode(['status'=>'ok', 'ext'=>$ext, 'authorized'=>$authorized]);
        exit;
    }

        if ($action === 'pause_types') {
        $rows = $tf->query("SELECT code, label, is_paid, max_duration_min, color FROM pause_types WHERE active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'types' => $rows]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Acción desconocida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
