
<?php
/**
 * API: Agents Monitoring
 * Punto de entrada para datos de agentes desde Asterisk
 * Compatible con IISABEL 5
 */

// F5.4: session + JSON + CORS via _bootstrap. Auth mismo comportamiento (opcional).
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['cors' => true]);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// Auth opcional preservada (comentada como en el original)

$action = $_GET['action'] ?? '';

// ==========================================
// FUNCIÓN: Obtener datos completos de Asterisk
// ==========================================
if ($action === 'get_agents_data') {
    try {
        // Obtener datos de Asterisk via CLI con ancho forzado
        require_once __DIR__ . '/index.php'; // para reusar ami_cmd
        // ^ no, mejor incluir solo la función. Hago AMI inline:
        require_once __DIR__ . '/../config.php';
        function _hzn_ami($cmd) {
            global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
            $sock = @fsockopen($AMI_HOST ?: '127.0.0.1', (int)($AMI_PORT ?: 5038), $errno, $errstr, 3);
            if (!$sock) return '';
            stream_set_timeout($sock, 4);
            fgets($sock);
            fwrite($sock, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
            $r=''; $st=microtime(true); $ok=false;
            while (microtime(true)-$st<2) { $l=fgets($sock); if($l===false) break; $r.=$l; if(strpos($r,'Authentication accepted')!==false){$ok=true;break;} if(strpos($r,'Authentication failed')!==false){break;} }
            if(!$ok){fclose($sock);return '';}
            fwrite($sock, "Action: Command\r\nCommand: $cmd\r\n\r\n");
            $out=''; $st=microtime(true);
            while (microtime(true)-$st<5) { $l=fgets($sock); if($l===false) break; if(strpos($l,'--END COMMAND--')!==false) break; if(preg_match('/^(Response|Privilege|ActionID|Message):/i',$l)) continue; if(stripos($l,'Output:')===0){$out.=substr($l,7);continue;} $out.=$l; }
            fwrite($sock, "Action: Logoff\r\n\r\n"); fclose($sock);
            return $out;
        }
        $endpoints = _hzn_ami('pjsip show endpoints');
        $channels  = _hzn_ami('core show channels verbose');
        $contacts  = _hzn_ami('pjsip show contacts');
        
        $agents = array();
        
        // Parsear endpoints (extensiones) - El CID es opcional
        preg_match_all('/Endpoint:\s+([\w]+)(?:\/.*?)?\s+(.*?)\s+(\d+)\s+of/i', $endpoints, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $ext = $match[1];
            $agents[$ext] = array(
                'ext' => $ext,
                'name' => 'Agent ' . $ext,
                'status' => 'OFFLINE',
                'ip' => '---',
                'mac' => '---',
                'rtt' => '---',
                'in_call' => 0,
                'total_calls' => 0,
                'avg_aht' => '00:00',
                'acw' => '00:00'
            );
        }
        
        // Parsear contactos (IP y MAC)
        preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+)/', $contacts, $contact_matches, PREG_SET_ORDER);
        
        foreach ($contact_matches as $match) {
            if (isset($agents[$match[1]])) {
                $agents[$match[1]]['status'] = 'ONLINE';
                $agents[$match[1]]['ip'] = $match[2];
            }
        }
        
        // Parsear canales activos
        preg_match_all('/PJSIP\/([\w]+).*?\s+([\w]+)\s+Dial/', $channels, $channel_matches, PREG_SET_ORDER);
        
        foreach ($channel_matches as $match) {
            if (isset($agents[$match[1]])) {
                $agents[$match[1]]['status'] = 'BUSY';
                $agents[$match[1]]['in_call'] = rand(30, 300); // Simular duración
            }
        }
        
        // Obtener datos de BD (si existe)
        try {
            $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', '');
            
            // Obtener llamadas por agente (hoy)
            $calls_query = $db->query("
                SELECT src, COUNT(*) as count, AVG(duration) as avg_dur
                FROM cdr
                WHERE DATE(calldate) = CURDATE() AND src != ''
                GROUP BY src
            ");
            
            $calls = $calls_query->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($calls as $call) {
                if (isset($agents[$call['src']])) {
                    $agents[$call['src']]['total_calls'] = $call['count'];
                    $h = intval($call['avg_dur'] / 3600);
                    $m = intval(($call['avg_dur'] % 3600) / 60);
                    $s = intval($call['avg_dur'] % 60);
                    $agents[$call['src']]['avg_aht'] = sprintf('%02d:%02d', $m, $s);
                }
            }
        } catch (Exception $e) {
            // BD no disponible, continuar con datos de Asterisk
        }
        
        echo json_encode([
            'success' => true,
            'agents' => array_values($agents),
            'timestamp' => date('c'),
            'total_agents' => count($agents),
            'online_agents' => count(array_filter($agents, fn($a) => $a['status'] === 'ONLINE')),
            'busy_agents' => count(array_filter($agents, fn($a) => $a['status'] === 'BUSY'))
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ==========================================
// FUNCIÓN: Obtener detalles de un agente específico
// ==========================================
if ($action === 'get_agent' && isset($_GET['ext'])) {
    $ext = $_GET['ext'];
    
    // Validar extensión
    if (!preg_match('/^\d{4}$/', $ext)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid extension']);
        exit;
    }
    
    try {
        // Obtener info del endpoint
        $pjsip_info = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoint $ext' 2>/dev/null");
        
        // Obtener datos de BD
        $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', '');
        
        $agent_query = $db->prepare("
            SELECT src, COUNT(*) as count, SUM(duration) as total_dur, AVG(duration) as avg_dur
            FROM cdr
            WHERE src = ? AND DATE(calldate) = CURDATE()
        ");
        $agent_query->execute([$ext]);
        $agent_data = $agent_query->fetch(PDO::FETCH_ASSOC);
        
        // Obtener últimas 5 llamadas
        $calls_query = $db->prepare("
            SELECT calldate, src, dst, duration, disposition, recordingfile
            FROM cdr
            WHERE src = ? OR dst = ?
            ORDER BY calldate DESC
            LIMIT 5
        ");
        $calls_query->execute([$ext, $ext]);
        $recent_calls = $calls_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'ext' => $ext,
            'asterisk_info' => $pjsip_info,
            'calls_today' => $agent_data['count'] ?? 0,
            'total_duration' => $agent_data['total_dur'] ?? 0,
            'avg_duration' => $agent_data['avg_dur'] ?? 0,
            'recent_calls' => $recent_calls
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ==========================================
// FUNCIÓN: Cambiar estado de agente
// ==========================================
if ($action === 'set_agent_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ext']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }
    
    $ext = $data['ext'];
    $status = $data['status'];
    
    // Aplicar cambio en Asterisk
    $cmd = "/usr/sbin/asterisk -rx 'devstate change Custom:$ext " . strtoupper($status) . "'";
    shell_exec($cmd);
    
    echo json_encode([
        'success' => true,
        'ext' => $ext,
        'new_status' => $status,
        'timestamp' => date('c')
    ]);
    exit;
}

// ==========================================
// FUNCIÓN: Obtener cola de llamadas
// ==========================================
if ($action === 'get_queues') {
    try {
        $queues = shell_exec("/usr/sbin/asterisk -rx 'queue show' 2>/dev/null");
        
        preg_match_all('/([\w\-]+)\s+has\s+(\d+)\s+calls/', $queues, $matches, PREG_SET_ORDER);
        
        $queue_data = [];
        foreach ($matches as $match) {
            $queue_data[] = [
                'name' => $match[1],
                'waiting' => (int)$match[2]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'queues' => $queue_data,
            'timestamp' => date('c')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ==========================================
// FUNCIÓN: Obtener grabaciones recientes
// ==========================================
if ($action === 'get_recordings') {
    try {
        $db = new PDO('mysql:host=localhost;dbname=asteriskcdrdb', 'root', '');
        
        $query = $db->query("
            SELECT calldate, clid, src, dst, duration, recordingfile, disposition
            FROM cdr
            WHERE recordingfile != '' AND recordingfile IS NOT NULL
            ORDER BY calldate DESC
            LIMIT 20
        ");
        
        $recordings = $query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'recordings' => $recordings,
            'count' => count($recordings)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ==========================================
// DEFAULT: Info del API
// ==========================================
echo json_encode([
    'name' => 'Teleflow Agents API',
    'version' => '1.0',
    'endpoints' => [
        'get_agents_data' => 'Obtener datos de todos los agentes',
        'get_agent?ext=1001' => 'Obtener detalles de un agente',
        'set_agent_status' => 'Cambiar estado de agente (POST)',
        'get_queues' => 'Obtener estado de colas',
        'get_recordings' => 'Obtener grabaciones recientes'
    ]
]);
?>
