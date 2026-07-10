<?php
/**
 * TeleFlow — Hotdesking & Call Center Agents API
 * Compatible con Issabel callcenter (BD call_center on PBX) + TeleFlow extensions
 *
 * Actions:
 *   GET  ?action=list                — lista todos los agentes
 *   GET  ?action=logged_in           — agentes actualmente logueados (desde teleflow.agent_sessions)
 *   POST ?action=create              — crear agent {number,name,password,eccp_password}
 *   POST ?action=update              — actualizar agent {id,...}
 *   POST ?action=delete              — borrar agent {id}
 *   POST ?action=login_agent         — login dinámico agent→extension {agent_number, extension}
 *   POST ?action=logout_agent        — logout {agent_number}
 *   POST ?action=add_to_queue        — añadir agente a queue {agent_number, queue, penalty}
 *   POST ?action=remove_from_queue   — quitar de queue {agent_number, queue}
 *   GET  ?action=queue_members       — lista miembros de queues con su status
 */

// F5.2: header consolidado. get_webrtc queda publico (chequeo previo a auth).
require_once __DIR__ . '/_bootstrap.php';
$currentAction = $_GET['action'] ?? '';
$publicActions = ['get_webrtc'];
if (in_array($currentAction, $publicActions)) {
    tf_bootstrap();  // sin auth
} else {
    tf_bootstrap(['auth' => 'any']);
}
$__IGNORED_1 = false; if ($__IGNORED_1) { http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'No autorizado']); exit;
}
@session_write_close();

require_once __DIR__ . '/../config.php';

function db_pbx($db = 'call_center') {
    global $DB_HOST, $DB_USER, $DB_PASS;
    return new PDO("mysql:host=$DB_HOST;dbname=$db;charset=utf8", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function ami_action($params, $timeout = 2.0) {
    // HORIZON: AMI Action nativa (más rápida que wrap con Action: Command)
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    static $s = null, $loggedIn = false;
    if ($s === null || !$loggedIn) {
        $s = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $en, $es, 3);
        if (!$s) { $s = null; return ['ok'=>false,'error'=>'connect']; }
        stream_set_timeout($s, 2);
        fgets($s);
        fwrite($s, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
        $r=''; $st=microtime(true);
        while (microtime(true)-$st<2) {
            $l=fgets($s); if($l===false) break; $r.=$l;
            if(strpos($r,'Authentication accepted')!==false) { $loggedIn=true; break; }
        }
        if (!$loggedIn) { @fclose($s); $s=null; return ['ok'=>false,'error'=>'auth']; }
    }
    $aid = uniqid('a');
    $msg = "ActionID: $aid\r\n";
    foreach ($params as $k=>$v) $msg .= "$k: $v\r\n";
    $msg .= "\r\n";
    fwrite($s, $msg);
    $resp=''; $done=false; $st=microtime(true);
    while (microtime(true)-$st < $timeout) {
        $l=fgets($s); if($l===false) break;
        $resp .= $l;
        if (strpos($l, $aid) !== false || trim($l) === '') {
            // Drain rest of message until empty line
            while (microtime(true)-$st < $timeout) {
                $l2 = fgets($s); if($l2===false) break;
                $resp .= $l2;
                if (trim($l2) === '') { $done=true; break; }
            }
            if ($done) break;
        }
    }
    return ['ok' => strpos($resp,'Response: Success')!==false, 'msg' => $resp];
}

function ami_send($cmd) {
    // HORIZON: socket persistente static dentro del mismo request
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    static $s = null, $loggedIn = false;
    if ($s === null || !$loggedIn) {
        $s = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $en, $es, 3);
        if (!$s) { $s = null; return ''; }
        stream_set_timeout($s, 3);
        fgets($s);
        fwrite($s, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
        $r=''; $st=microtime(true);
        while (microtime(true)-$st<2) {
            $l=fgets($s); if($l===false) break; $r.=$l;
            if(strpos($r,'Authentication accepted')!==false) { $loggedIn=true; break; }
            if(strpos($r,'Authentication failed')!==false) { fclose($s); $s=null; return ''; }
        }
        if (!$loggedIn) { try { fclose($s); } catch (Exception $e) {} $s=null; return ''; }
    }
    fwrite($s, "Action: Command\r\nCommand: $cmd\r\n\r\n");
    $out=''; $st=microtime(true);
    while (microtime(true)-$st<3) {
        $l=fgets($s); if($l===false) break;
        if (strpos($l,'--END COMMAND--')!==false) break;
        if (preg_match('/^(Response|Privilege|ActionID|Message):/i',$l)) continue;
        if (stripos($l,'Output:')===0) { $out.=substr($l,7); continue; }
        $out.=$l;
    }
    return $out;
}


function ami_queue_show_cached() {
    // Cache 5s para evitar saturar AMI
    $cache = '/tmp/teleflow_queue_show.txt';
    if (file_exists($cache) && (time() - filemtime($cache)) < 5) return file_get_contents($cache);
    $raw = ami_send('queue show');  // llamada real
    @file_put_contents($cache, $raw, LOCK_EX);
    @chmod($cache, 0666);
    return $raw;
}



// HORIZON: AMI Action: QueueStatus → array estructurado de miembros
function ami_queue_status_full() {
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    $sock = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $errno, $errstr, 3);
    if (!$sock) return [];
    stream_set_timeout($sock, 4);
    fgets($sock);
    fwrite($sock, "Action: Login\r\nUsername: " . ($AMI_USER ?: 'admin') . "\r\nSecret: " . ($AMI_PASS ?: '') . "\r\nEvents: off\r\n\r\n");
    $st = microtime(true);
    while (microtime(true) - $st < 2) { $l = fgets($sock); if (!$l) break; if (strpos($l,'Authentication accepted')!==false) break; }
    $aid = 'qs-' . uniqid();
    fwrite($sock, "Action: QueueStatus\r\nActionID: $aid\r\n\r\n");
    $members = [];
    $cur = [];
    $st = microtime(true);
    while (microtime(true) - $st < 5) {
        $line = fgets($sock);
        if ($line === false) break;
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            if (!empty($cur) && (($cur['event'] ?? '') === 'QueueMember')) $members[] = $cur;
            $cur = [];
            continue;
        }
        if (stripos($line, 'QueueStatusComplete') !== false) break;
        if (preg_match('/^([A-Za-z]+):\s*(.*)$/', $line, $m)) {
            $cur[strtolower($m[1])] = $m[2];
        }
    }
    fwrite($sock, "Action: Logoff\r\n\r\n");
    fclose($sock);
    return $members;
}

function parse_queue_show_all($raw) {
    $result = [];
    $current_queue = null;
    foreach (explode("\n", $raw) as $line) {
        // queue line: "8000 has X calls (max ...) in 'ringall' strategy ..."
        if (preg_match('/^\s*(\d+)\s+has\s+\d+\s+calls/', $line, $m)) {
            $current_queue = $m[1];
            $result[$current_queue] = [];
        }
        // member line: "Agent/200 (SIP/9006) (...) (...) ..."
        elseif ($current_queue && preg_match('/Agent\/(\d+)\s+\((SIP|PJSIP)\/(\d+)\)/', $line, $m)) {
            $result[$current_queue][] = ['agent_number'=>$m[1], 'tech'=>$m[2], 'extension'=>$m[3]];
        }
    }
    return $result;
}

$action = $_GET['action'] ?? '';

try {
    $cc = db_pbx('call_center');
    $tf = db_pbx('teleflow');

    switch ($action) {
        case 'list': {
            $rows = $cc->query("SELECT id, type, number, name, estatus FROM agent ORDER BY CAST(number AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
            // HORIZON: cruzar con AMI 'queue show' (single source of truth para login dinámico)
            // HORIZON v3: usar AMI Action: QueueStatus (eventos estructurados)
            // Mucho más robusto que parsear texto de "queue show"
            $by_agent_ext = [];
            $cache_qs = '/tmp/teleflow_queue_status.json';
            $qs_data = null;
            if (file_exists($cache_qs) && (time() - filemtime($cache_qs)) < 5) {
                $qs_data = json_decode(@file_get_contents($cache_qs), true);
            }
            if (!$qs_data) {
                try {
                    $qs_data = ami_queue_status_full();
                    @file_put_contents($cache_qs, json_encode($qs_data), LOCK_EX);
                    @chmod($cache_qs, 0666);
                } catch (Exception $e) { $qs_data = []; }
            }
            // Cargar mapa extension → último agent que logueó ahí (de agent_sessions activas)
            $ext2agent_session = [];
            try {
                $sess_rows = $tf->query("SELECT agent_ext, MAX(login_time) AS lt FROM agent_sessions WHERE logout_time IS NULL GROUP BY agent_ext")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sess_rows as $r) $ext2agent_session[$r['agent_ext']] = true;
            } catch (Exception $e) {}
            // Cargar lista de agent_numbers válidos
            $valid_agents = [];
            try {
                $st = $cc->query("SELECT number FROM agent WHERE estatus='A'");
                $valid_agents = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {}

            foreach ($qs_data as $member) {
                $queue = $member['queue'] ?? '';
                $name  = $member['name'] ?? '';     // MemberName: ej "Agent/200"
                $loc   = $member['location'] ?? ''; // Interface: ej "SIP/533"
                if (!$queue || !$loc) continue;

                $agent_num = null; $ext = null;
                // 1) Si Name es "Agent/N" → ese es el agente
                if (preg_match('/^Agent\/(\d+)$/', $name, $m)) $agent_num = $m[1];
                // 2) Sacar extension de Location
                if (preg_match('/^(SIP|PJSIP)\/(\d+)/', $loc, $m)) $ext = $m[2];
                // 3) Si no encontré agent_num pero la ext es VÁLIDA agent number, usarla
                if (!$agent_num && $ext && isset($valid_agents[$ext])) $agent_num = $ext;
                // 4) Si Name es solo "SIP/X", agent_num = X si es válido
                if (!$agent_num && preg_match('/^(SIP|PJSIP)\/(\d+)$/', $name, $m) && isset($valid_agents[$m[2]])) {
                    $agent_num = $m[2]; $ext = $m[2];
                }
                if (!$agent_num) continue;

                $by_agent_ext[$agent_num] = $by_agent_ext[$agent_num] ?? ['extension'=>$ext, 'queues'=>[]];
                if ($ext && !$by_agent_ext[$agent_num]['extension']) $by_agent_ext[$agent_num]['extension'] = $ext;
                if (!in_array($queue, $by_agent_ext[$agent_num]['queues'])) $by_agent_ext[$agent_num]['queues'][] = $queue;
            }
            // HORIZON: cargar pausas activas (con motivo y duracion) desde agent_pauses + pause_types
            $active_pauses = [];
            try {
                $st = $tf->query("
                    SELECT ap.agent_ext, ap.agent_number, ap.pause_type_code, ap.pause_start,
                           pt.label AS pause_label, pt.color AS pause_color,
                           TIMESTAMPDIFF(SECOND, ap.pause_start, NOW()) AS pause_seconds
                    FROM agent_pauses ap
                    LEFT JOIN pause_types pt ON pt.code = ap.pause_type_code
                    WHERE ap.pause_end IS NULL
                    ORDER BY ap.pause_start DESC
                ");
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
                    // Indexar por agent_number Y agent_ext (la pausa puede tener cualquiera)
                    $key = $p['agent_number'] ?: $p['agent_ext'];
                    if ($key && !isset($active_pauses[$key])) $active_pauses[$key] = $p;
                    if ($p['agent_ext'] && !isset($active_pauses[$p['agent_ext']])) $active_pauses[$p['agent_ext']] = $p;
                }
            } catch (Exception $e) {}

            foreach ($rows as &$r) {
                $info = $by_agent_ext[$r['number']] ?? null;
                $r['logged_in'] = !empty($info);
                $r['extension'] = $info['extension'] ?? null;
                $r['queues']    = $info['queues'] ?? [];

                // HORIZON: estado pausa — buscar por number, luego por extension
                $p = $active_pauses[$r['number']] ?? null;
                if (!$p && $r['extension']) $p = $active_pauses[$r['extension']] ?? null;
                if ($p) {
                    $r['paused']           = true;
                    $r['pause_type_code']  = $p['pause_type_code'];
                    $r['pause_label']      = $p['pause_label'] ?: $p['pause_type_code'];
                    $r['pause_color']      = $p['pause_color'] ?: '#f59e0b';
                    $r['pause_start']      = $p['pause_start'];
                    $r['pause_seconds']    = (int)$p['pause_seconds'];
                } else {
                    $r['paused'] = false;
                }
            }
            echo json_encode(['status'=>'ok','agents'=>$rows]);
            break;
        }

        case 'logged_in': {
            try {
                $rows = $tf->query("SELECT agent_ext as extension, login_time as login_at FROM agent_sessions WHERE logout_time IS NULL ORDER BY login_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $rows = []; }
            echo json_encode(['status'=>'ok','sessions'=>$rows]);
            break;
        }

        case 'create': {
            $num = preg_replace('/\D/', '', $_POST['number'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $pass = trim($_POST['password'] ?? '1234');
            $eccp = trim($_POST['eccp_password'] ?? $pass);
            $type = in_array($_POST['type'] ?? 'Agent', ['Agent','SIP','IAX2']) ? $_POST['type'] : 'Agent';
            if (!$num || !$name) { echo json_encode(['status'=>'error','message'=>'Faltan campos']); exit; }
            $stmt = $cc->prepare("INSERT INTO agent (type, number, name, password, eccp_password, estatus) VALUES (?, ?, ?, ?, ?, 'A')");
            $stmt->execute([$type, $num, $name, $pass, $eccp]);
            echo json_encode(['status'=>'ok','id'=>$cc->lastInsertId()]);
            break;
        }

        case 'update': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['status'=>'error','message'=>'id requerido']); exit; }
            $fields = []; $vals = [];
            foreach (['number','name','password','eccp_password','estatus','type'] as $f) {
                if (isset($_POST[$f])) { $fields[] = "$f=?"; $vals[] = $_POST[$f]; }
            }
            if (!$fields) { echo json_encode(['status'=>'error','message'=>'sin cambios']); exit; }
            $vals[] = $id;
            $stmt = $cc->prepare("UPDATE agent SET ".implode(',', $fields)." WHERE id=?");
            $stmt->execute($vals);
            echo json_encode(['status'=>'ok']);
            break;
        }

        case 'delete': {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['status'=>'error','message'=>'id requerido']); exit; }
            $cc->prepare("DELETE FROM agent WHERE id=?")->execute([$id]);
            echo json_encode(['status'=>'ok']);
            break;
        }

        case 'login_agent': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $ext   = preg_replace('/\D/', '', $_POST['extension'] ?? '');
            $has_queues_param = isset($_POST['queues']);  // true incluso si valor vacío
            $custom_queues = trim($_POST['queues'] ?? '');
            $penalty = (int)($_POST['penalty'] ?? 0);
            if (!$agent || !$ext) { echo json_encode(['status'=>'error','message'=>'agent_number y extension requeridos']); exit; }

            // Determinar queues:
            // a) Frontend mandó queues (aunque sea vacío) → respetar; vacío = error
            // b) Frontend NO mandó queues → caer a prefs guardados
            $queues = [];
            if ($has_queues_param) {
                if ($custom_queues === '') {
                    echo json_encode(['status'=>'error','message'=>'Marcá al menos una cola para loguear al agente.']);
                    exit;
                }
                foreach (explode(',', $custom_queues) as $q) { $q = trim($q); if ($q !== '') $queues[] = $q; }
                // Guardar como prefs nuevas
                try {
                    $tf->prepare("DELETE FROM agent_queue_pref WHERE agent_number = ?")->execute([$agent]);
                    $st = $tf->prepare("INSERT INTO agent_queue_pref (agent_number, queue, penalty) VALUES (?, ?, ?)");
                    foreach ($queues as $q) $st->execute([$agent, $q, $penalty]);
                } catch (Exception $e) {}
            } else {
                try {
                    $st = $tf->prepare("SELECT queue FROM agent_queue_pref WHERE agent_number = ?");
                    $st->execute([$agent]);
                    $queues = $st->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $e) {}
                if (empty($queues)) {
                    echo json_encode(['status'=>'error','message'=>'Este agente no tiene colas guardadas. Marcá al menos una en el modal.']);
                    exit;
                }
            }

            // HORIZON: 1) sweep — sacar al agente de TODAS las colas previas (limpia rastros de logins anteriores)
            $iface = "SIP/$ext";
            $member_name = "Agent/$agent";
            $sweep_raw = ami_queue_show_cached();
            @unlink('/tmp/teleflow_queue_show.txt');  // forzar fresh para sweep
            $sweep_raw = ami_send('queue show');
            $cur_q = null; $previous_queues = [];
            foreach (explode("\n", $sweep_raw) as $line) {
                if (preg_match('/^\s*(\d+)\s+has\s+/', $line, $m)) $cur_q = $m[1];
                elseif ($cur_q && (
                    strpos($line, "Agent/$agent ") !== false || 
                    strpos($line, $iface . ' ') !== false || strpos($line, $iface . ',') !== false ||
                    preg_match('/Agent\/' . preg_quote($agent,'/') . '\s+\(/', $line)
                )) {
                    $previous_queues[] = $cur_q;
                }
            }
            foreach (array_unique($previous_queues) as $pq) {
                // Probar ambos formatos para asegurarse de removerlo
                ami_action(['Action'=>'QueueRemove','Queue'=>$pq,'Interface'=>"Agent/$agent"], 0.4);
                ami_action(['Action'=>'QueueRemove','Queue'=>$pq,'Interface'=>$iface], 0.4);
            }

            // 2) QueueAdd con MemberName Agent/N — formato unificado
            $added = [];
            foreach ($queues as $q) {
                $r = ami_action([
                    'Action'=>'QueueAdd',
                    'Queue'=>$q,
                    'Interface'=>$iface,           // donde suena (SIP/533)
                    'MemberName'=>$member_name,    // Agent/200 — visible en queue show
                    'Penalty'=>$penalty,
                    'StateInterface'=>$iface,      // estado tomado del SIP
                ], 0.8);
                if ($r['ok']) $added[] = $q;
            }
            // HORIZON: invalidar caché de 'queue show' para que el list/get_full_data refleje login al instante
            @unlink('/tmp/teleflow_queue_show.txt');
            usleep(150000); // 150ms para que AMI propague el QueueAdd antes del próximo poll

            // Persistir session (tabla teleflow.agent_sessions: session_id, agent_ext, login_time, shift_date, status)
            try {
                $sid = uniqid('s_', true);
                $today = date('Y-m-d');
                $stmt = $tf->prepare("INSERT INTO agent_sessions (session_id, agent_ext, login_time, shift_date, status) VALUES (?, ?, NOW(), ?, 'ACTIVE')");
                $stmt->execute([$sid, $ext, $today]);
            } catch (Exception $e) { /* sigue, no bloquea */ }

            // Notificar a todos los clientes conectados via realtime hub
            @file_get_contents('http://127.0.0.1/api/notify.php?event=agent_login&agent='.urlencode($agent).'&ext='.urlencode($ext).'&queues='.urlencode(implode(',',$added)));
            echo json_encode(['status'=>'ok','queues_added'=>$added,'count'=>count($added),'iface'=>$iface]);
            break;
        }

        case 'spy_call': {
            // POST {channel, my_ext} — origina ChanSpy desde my_ext hacia channel
            // Modo whisper/spy: el supervisor escucha sin ser oído.
            $channel = trim($_POST['channel'] ?? $_GET['channel'] ?? '');
            $my_ext  = preg_replace('/\D/', '', $_POST['my_ext'] ?? $_GET['my_ext'] ?? '');
            $mode    = $_POST['mode'] ?? 'spy';  // spy | whisper | barge
            if (!$channel || !$my_ext) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'falta channel o my_ext']); exit; }

            // Validar mode
            $spy_opts = ['spy'=>'q', 'whisper'=>'qw', 'barge'=>'qB'];
            $opts = $spy_opts[$mode] ?? 'q';

            $s = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $en, $es, 3);
            if (!$s) { echo json_encode(['status'=>'error','message'=>'AMI no disponible']); exit; }
            stream_set_timeout($s, 3); fgets($s);
            fwrite($s, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
            $st=microtime(true); while(microtime(true)-$st<2){$l=fgets($s);if(!$l)break;if(strpos($l,'Authentication accepted')!==false)break;}

            // Extraer ext del canal target (ej. SIP/533-00001234 → 533)
            $target_ext = '';
            if (preg_match('/^(?:SIP|PJSIP)\/(\d+)/', $channel, $m)) $target_ext = $m[1];

            // AMI Originate hacia my_ext, que al contestar pasa a Application ChanSpy
            $action_id = uniqid('spy_');
            $payload = "Action: Originate\r\n";
            $payload .= "ActionID: $action_id\r\n";
            $payload .= "Channel: Local/$my_ext@from-internal\r\n";
            $payload .= "CallerID: <SPY-$target_ext>\r\n";
            $payload .= "Application: ChanSpy\r\n";
            $payload .= "Data: " . ($target_ext ? "SIP/$target_ext,$opts" : "$channel,$opts") . "\r\n";
            $payload .= "Timeout: 30000\r\n";
            $payload .= "Async: true\r\n\r\n";
            fwrite($s, $payload);

            $st=microtime(true); $resp=''; $ok=false;
            while(microtime(true)-$st<2){
                $l=fgets($s); if(!$l) break; $resp.=$l;
                if (strpos($l,'Response: Success')!==false) $ok = true;
                if (strpos($l,'Response: Error')!==false) break;
                if (trim($l)==='' && $ok) break;
            }
            fwrite($s, "Action: Logoff\r\n\r\n"); fclose($s);

            if ($ok) {
                echo json_encode(['status'=>'ok','message'=>"Originando spy a $my_ext (target $target_ext)",'mode'=>$mode]);
            } else {
                echo json_encode(['status'=>'error','message'=>'AMI Originate falló','raw'=>substr($resp,0,400)]);
            }
            break;
        }

        case 'hangup_call': {
            // POST {ext} — colgar la llamada actual de una extensión
            $ext = preg_replace('/\D/', '', $_POST['ext'] ?? $_GET['ext'] ?? '');
            if (!$ext) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'falta ext']); exit; }
            // Buscar canal activo de la extension
            $ch_raw = ami_action(['Action'=>'CoreShowChannels'], 3.0);
            $channels = [];
            // Hacer queries hasta obtener match
            $s = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $en, $es, 3);
            if (!$s) { echo json_encode(['status'=>'error','message'=>'AMI no disponible']); exit; }
            stream_set_timeout($s, 3); fgets($s);
            fwrite($s, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
            $st=microtime(true); while(microtime(true)-$st<2){$l=fgets($s);if(!$l)break;if(strpos($l,'Authentication accepted')!==false)break;}
            // core show channels concise
            fwrite($s, "Action: Command\r\nCommand: core show channels concise\r\n\r\n");
            $resp=''; $st=microtime(true);
            while(microtime(true)-$st<3){$l=fgets($s);if($l===false)break;$resp.=$l;if(strpos($l,'--END COMMAND--')!==false)break;}
            $hung = [];
            foreach (explode("\n", $resp) as $line) {
                if (preg_match('#^(SIP|PJSIP)/'.preg_quote($ext,'#').'[-!]#', $line)) {
                    $chan = explode('!', $line)[0];
                    fwrite($s, "Action: Hangup\r\nChannel: $chan\r\n\r\n");
                    $hung[] = $chan;
                    usleep(50000);
                }
            }
            fwrite($s, "Action: Logoff\r\n\r\n"); fclose($s);
            echo json_encode(['status'=>'ok','hung'=>$hung,'count'=>count($hung)]);
            break;
        }

        case 'logout_agent': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $ext_explicit = preg_replace('/\D/', '', $_POST['extension'] ?? '');
            if (!$agent && !$ext_explicit) { echo json_encode(['status'=>'error','message'=>'agent_number o extension requerido']); exit; }
            
            // HORIZON FIX: simple y robusto — encontrar ext del agente y QueueRemove de TODAS las colas activas
            $ext_now = $ext_explicit ?: '';
            if (!$ext_now && $agent) {
                try {
                    $raw = ami_queue_show_cached();
                    if (preg_match('/Agent\/' . preg_quote($agent, '/') . '\s+\((SIP|PJSIP)\/(\d+)\)/', $raw, $m)) {
                        $ext_now = $m[2];
                    }
                } catch (Exception $e) {}
            }
            if (!$ext_now) { echo json_encode(['status'=>'ok','message'=>'No hay sesión activa o extensión no detectada']); exit; }
            
            // QueueRemove de TODAS las colas activas vía Action: QueueRemove nativa (idempotente)
            $removed = [];
            try {
                $pbx_db = db_pbx('asterisk');
                $queues = $pbx_db->query("SELECT extension FROM queues_config")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($queues as $q) {
                    $r = ami_action(['Action'=>'QueueRemove','Queue'=>$q,'Interface'=>"SIP/$ext_now"], 0.8);
                    if ($r['ok']) $removed[] = $q;
                }
            } catch (Exception $e) {}
            
            // Cerrar agent_sessions activas
            try {
                if ($agent) {
                    $tf->prepare("UPDATE agent_sessions SET logout_time = NOW(), status = 'CLOSED' WHERE agent_ext = ? AND logout_time IS NULL")->execute([$ext_now]);
                }
            } catch (Exception $e) {}
            
            // Invalidar cache queue show
            @unlink('/tmp/teleflow_queue_show.txt');
            
            @file_get_contents('http://127.0.0.1/api/notify.php?event=agent_logout&ext='.urlencode($ext_now).'&queues='.urlencode(implode(',',$removed)).'&agent='.urlencode($agent));
            echo json_encode(['status'=>'ok', 'extension'=>$ext_now, 'queues_removed'=>$removed, 'count'=>count($removed)]);
            exit;
        }
        // Fin nuevo logout — el viejo se descarta abajo
        case 'logout_agent_OLD_DEPRECATED': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $ext_explicit = preg_replace('/\D/', '', $_POST['extension'] ?? '');
            if (!$agent && !$ext_explicit) { echo json_encode(['status'=>'error','message'=>'agent_number o extension requerido']); exit; }
            // Find current session — buscamos por extensión actual del agente vía AMI queue show
            $row = null;
            $ext_now = null;
            try {
                $raw = ami_queue_show_cached();
                if (preg_match('/Agent\/' . preg_quote($agent, '/') . '\s+\((SIP|PJSIP)\/(\d+)\)/', $raw, $m)) {
                    $ext_now = $m[2];
                }
            } catch (Exception $e) {}
            if ($ext_explicit) { $ext_now = $ext_explicit; }
            if (!$ext_now) {
                // Fallback: agent_sessions por agent_ext
                try {
                    $stmt = $tf->prepare("SELECT id, agent_ext as extension FROM agent_sessions WHERE logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) $ext_now = $row['extension'];
                } catch (Exception $e) {}
            } else {
                $row = ['extension' => $ext_now];
            }
            if (!$row) { echo json_encode(['status'=>'ok','message'=>'No hay sesion activa']); exit; }
            // QueueRemove from all queues activas
            try {
                $pbx_db = db_pbx('asterisk');
                $queues = $pbx_db->query("SELECT extension FROM queues_config")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($queues as $q) {
                    ami_send("queue remove member SIP/{$row['extension']} from $q");
                }
            } catch (Exception $e) {}
            // Close session
            try {
                if (!empty($ext_now)) {
                    $tf->prepare("UPDATE agent_sessions SET logout_time=NOW(), status='LOGGED_OUT' WHERE agent_ext=? AND logout_time IS NULL")->execute([$ext_now]);
                }
            } catch (Exception $e) {}
            echo json_encode(['status'=>'ok']);
            break;
        }

        case 'add_to_queue': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $queue = preg_replace('/\D/', '', $_POST['queue'] ?? '');
            $penalty = (int)($_POST['penalty'] ?? 0);
            if (!$agent || !$queue) { echo json_encode(['status'=>'error','message'=>'agent_number y queue requeridos']); exit; }
            // Insertar relación BD
            $stmt = $cc->prepare("INSERT IGNORE INTO queue_call_entry (id_agent, queue, penalty) VALUES ((SELECT id FROM agent WHERE number=?), ?, ?)");
            $stmt->execute([$agent, $queue, $penalty]);
            echo json_encode(['status'=>'ok']);
            break;
        }

        case 'remove_from_queue': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $queue = preg_replace('/\D/', '', $_POST['queue'] ?? '');
            if (!$agent || !$queue) { echo json_encode(['status'=>'error','message'=>'agent_number y queue requeridos']); exit; }
            $stmt = $cc->prepare("DELETE FROM queue_call_entry WHERE queue=? AND id_agent=(SELECT id FROM agent WHERE number=?)");
            $stmt->execute([$queue, $agent]);
            echo json_encode(['status'=>'ok']);
            break;
        }

        case 'queue_members': {
            // From AMI 'queue show' parsed roughly + DB join
            $rows = $cc->query("SELECT q.queue, a.number, a.name, q.penalty FROM queue_call_entry q LEFT JOIN agent a ON a.id = q.id_agent ORDER BY q.queue, CAST(a.number AS UNSIGNED)")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status'=>'ok','members'=>$rows]);
            break;
        }

        case 'get_agent_prefs': {
            // Devuelve colas preferidas del agente (de teleflow.agent_queue_pref)
            $agent = preg_replace('/\D/', '', $_GET['agent_number'] ?? $_POST['agent_number'] ?? '');
            if (!$agent) { echo json_encode(['status'=>'error','message'=>'agent_number requerido']); exit; }
            try {
                $stmt = $tf->prepare("SELECT queue, penalty FROM agent_queue_pref WHERE agent_number = ? ORDER BY queue");
                $stmt->execute([$agent]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['status'=>'ok', 'queues'=>$rows]);
            } catch (Exception $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); }
            exit;
        }
        case 'set_agent_prefs': {
            $agent = preg_replace('/\D/', '', $_POST['agent_number'] ?? '');
            $queues = $_POST['queues'] ?? '';
            $penalty = (int)($_POST['penalty'] ?? 0);
            if (!$agent) { echo json_encode(['status'=>'error','message'=>'agent_number requerido']); exit; }
            try {
                $tf->prepare("DELETE FROM agent_queue_pref WHERE agent_number = ?")->execute([$agent]);
                if ($queues) {
                    $stmt = $tf->prepare("INSERT INTO agent_queue_pref (agent_number, queue, penalty) VALUES (?, ?, ?)");
                    foreach (explode(',', $queues) as $q) {
                        $q = trim($q); if ($q) $stmt->execute([$agent, $q, $penalty]);
                    }
                }
                echo json_encode(['status'=>'ok']);
            } catch (Exception $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); }
            exit;
        }
                case 'pause_member': {
            $queue = preg_replace('/\D/', '', $_POST['queue'] ?? '');
            $iface = $_POST['interface'] ?? '';
            $paused = ($_POST['paused'] ?? '0') === '1';
            if (!$queue || !$iface) { echo json_encode(['status'=>'error','message'=>'queue+interface requeridos']); exit; }
            $r = ami_action(['Action'=>'QueuePause','Queue'=>$queue,'Interface'=>$iface,'Paused'=>$paused?'true':'false','Reason'=>'SUPERVISOR'], 1.0);
            if ($r['ok']) {
                echo json_encode(['status'=>'ok','msg'=>($paused?'Pausado':'Reanudado').' en Q'.$queue]);
            } else {
                echo json_encode(['status'=>'error','message'=>'AMI falló','detail'=>$r['msg']??'']);
            }
            exit;
        }
                case 'agent_queues': {
            // Devuelve mapping agent_number -> [queues] desde 'queue show'
            $raw = ami_queue_show_cached();
            $queue_members = parse_queue_show_all($raw);
            $by_agent = [];
            foreach ($queue_members as $q => $members) {
                foreach ($members as $m) {
                    $by_agent[$m['agent_number']] = $by_agent[$m['agent_number']] ?? [];
                    $by_agent[$m['agent_number']][] = ['queue'=>$q, 'extension'=>$m['extension']];
                }
            }
            echo json_encode(['status'=>'ok','by_agent'=>$by_agent,'by_queue'=>$queue_members]);
            break;
        }

        case 'list_webrtc_extensions': {
            // Devuelve extensiones SIP con transport que incluya "ws" (candidatas para WebRTC)
            try {
                $pbx = db_pbx('asterisk');
                $stmt = $pbx->query("SELECT DISTINCT s.id FROM sip s WHERE s.id IN (SELECT id FROM sip WHERE keyword='transport' AND data LIKE '%ws%') ORDER BY CAST(s.id AS UNSIGNED)");
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $result = [];
                if (!empty($ids)) {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $stmt2 = $pbx->prepare("SELECT id, description FROM devices WHERE id IN ($ph)");
                    $stmt2->execute($ids);
                    $names = [];
                    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) $names[$r['id']] = $r['description'];
                    foreach ($ids as $id) $result[] = ['ext' => $id, 'name' => $names[$id] ?? ''];
                }
                // Cuáles ya están asignados
                $tf = db_pbx('teleflow');
                $assigned = $tf->query("SELECT webrtc_ext, agent_number FROM agent_webrtc")->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($result as &$r) $r['assigned_to'] = $assigned[$r['ext']] ?? null;
                echo json_encode(['status'=>'ok', 'extensions'=>$result]);
            } catch (Exception $e) {
                echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
            }
            break;
        }

        case 'set_webrtc': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status'=>'error','message'=>'POST requerido']); break; }
            $agent = preg_replace('/[^0-9]/', '', $_POST['agent_number'] ?? '');
            $ext = preg_replace('/[^0-9]/', '', $_POST['webrtc_ext'] ?? '');
            if (!$agent) { echo json_encode(['status'=>'error','message'=>'agent_number requerido']); break; }
            try {
                $tf = db_pbx('teleflow');
                if (!$ext) {
                    $tf->prepare("DELETE FROM agent_webrtc WHERE agent_number=?")->execute([$agent]);
                    echo json_encode(['status'=>'ok', 'removed'=>true]);
                    break;
                }
                $pbx = db_pbx('asterisk');
                $stmt = $pbx->prepare("SELECT data FROM sip WHERE id=? AND keyword='secret'");
                $stmt->execute([$ext]);
                $secret = $stmt->fetchColumn();
                if (!$secret) { echo json_encode(['status'=>'error','message'=>'La ext '.$ext.' no existe o no tiene secret']); break; }
                $tf->prepare("INSERT INTO agent_webrtc (agent_number, webrtc_ext, webrtc_secret) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE webrtc_ext=VALUES(webrtc_ext), webrtc_secret=VALUES(webrtc_secret)")
                   ->execute([$agent, $ext, $secret]);
                echo json_encode(['status'=>'ok', 'ext'=>$ext]);
            } catch (Exception $e) {
                echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
            }
            break;
        }

        case 'get_webrtc': {
            $agent = preg_replace('/[^0-9]/', '', $_GET['agent_number'] ?? '');
            if (!$agent) { echo json_encode(['status'=>'error','message'=>'agent_number requerido']); break; }
            try {
                $tf = db_pbx('teleflow');
                $stmt = $tf->prepare("SELECT webrtc_ext FROM agent_webrtc WHERE agent_number=?");
                $stmt->execute([$agent]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['status'=>'ok', 'webrtc_ext' => $row['webrtc_ext'] ?? null]);
            } catch (Exception $e) {
                echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
            }
            break;
        }

        default:
            echo json_encode(['status'=>'error','message'=>'Acción desconocida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
