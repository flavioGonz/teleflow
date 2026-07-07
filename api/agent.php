
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
session_start();
header('Content-Type: application/json');
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

        // 2) Abrir sesión TeleFlow (sin colas todavía — el agente elige en el paso siguiente)
        $sessionId = 'tf-' . date('Ymd') . '-' . $agentNum . '-' . substr(uniqid(), -6);
        $tf->prepare("INSERT INTO agent_sessions (session_id, agent_ext, shift_date, status) VALUES (?, ?, CURDATE(), 'ACTIVE')")
           ->execute([$sessionId, $agentNum]);

        // 3) Identificador del agente para AMI (Agent/N o SIP/N)
        $sAgente = $agent['type'] . '/' . $agent['number'];
        $iface = preg_match('|^\w+/\d+$|', $callbackExt) ? $callbackExt : "SIP/$callbackExt";

        // 4) Listado de colas activas + descripción para que el agente elija
        // (antes: auto-asignaba a todas. Ahora: devuelve disponibles, el cliente llama action=join_queues)
        $available_queues = [];
        try {
            $rows = $cc->query("SELECT DISTINCT queue FROM queue_call_entry WHERE estatus='A' ORDER BY queue")->fetchAll(PDO::FETCH_COLUMN);
            // Traer descripciones desde asterisk.queues_config
            $descMap = [];
            try {
                $pbxdb = new PDO("mysql:host=$PBX_DB_HOST;dbname=asterisk;charset=utf8mb4", $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $dr = $pbxdb->query("SELECT extension, descr FROM queues_config")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($dr as $row) $descMap[$row['extension']] = $row['descr'];
            } catch (Exception $e) {}
            foreach ($rows as $q) {
                $available_queues[] = ['queue' => $q, 'name' => $descMap[$q] ?? null];
            }
        } catch (Exception $e) {}

        // Prefs guardadas en agent_queue_pref (si las hay) para preselección
        $pref_queues = [];
        try {
            $st = $tf->prepare("SELECT queue FROM agent_queue_pref WHERE agent_number = ?");
            $st->execute([$agentNum]);
            $pref_queues = $st->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}

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
        // Sanitizar contra colas existentes en la PBX (no inventar)
        $cc = pbx_cc_db();
        $valid = $cc->query("SELECT DISTINCT queue FROM queue_call_entry WHERE estatus='A'")->fetchAll(PDO::FETCH_COLUMN);
        $queues = array_values(array_intersect($queues, $valid));

        $iface    = $a['callback_ext'];
        $sAgente  = $a['sAgente'];
        $addedQueues = [];
        foreach ($queues as $q) {
            try {
                $r = ami_action([
                    'Action' => 'QueueAdd',
                    'Queue' => $q,
                    'Interface' => $iface,
                    'Penalty' => 0,
                    'MemberName' => $sAgente,
                    'StateInterface' => $iface,
                ]);
                if (strpos($r, 'Response: Success') !== false || strpos($r, 'Added') !== false) {
                    $addedQueues[] = $q;
                }
            } catch (Exception $e) { /* continuar */ }
        }

        // Persistir prefs (para auto-preselección futura)
        try {
            $tf->prepare("DELETE FROM agent_queue_pref WHERE agent_number = ?")->execute([$a['agent_number']]);
            $ins = $tf->prepare("INSERT INTO agent_queue_pref (agent_number, queue, penalty) VALUES (?, ?, 0)");
            foreach ($addedQueues as $q) {
                try { $ins->execute([$a['agent_number'], $q]); } catch (Exception $e) {}
            }
        } catch (Exception $e) {}

        // Actualizar sesión
        $_SESSION['agent_user']['queues'] = $addedQueues;
        unset($_SESSION['agent_user']['pending_queue_selection']);

        // Notificar al hub realtime
        @file_get_contents('http://127.0.0.1/api/notify.php?event=agent_login&agent='.urlencode($a['agent_number']).'&ext='.urlencode(preg_replace('|^\w+/|', '', $iface)).'&queues='.urlencode(implode(',', $addedQueues)));

        echo json_encode([
            'status' => 'success',
            'joined_queues' => $addedQueues,
        ]);
        exit;
    }

    // ─── LOGOUT ─────────────────────────────────────────────────────────
    if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_SESSION['agent_user'] ?? null;
        if (!$a) { echo json_encode(['status' => 'ok']); exit; }

        // QueueRemove de cada cola
        foreach ($a['queues'] as $q) {
            try {
                ami_action([
                    'Action' => 'QueueRemove',
                    'Queue' => $q,
                    'Interface' => $a['callback_ext'],
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
            $stW->execute([$a['number'] ?? '']);
            $wRow = $stW->fetch(PDO::FETCH_ASSOC);
            if ($wRow) { $ext = $wRow['webrtc_ext']; $secretFromMap = $wRow['webrtc_secret']; }
        } catch (Exception $e) {}
        if (!$ext) {
            $ext = preg_replace('/^\w+\//', '', $a['callback'] ?? '');
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
