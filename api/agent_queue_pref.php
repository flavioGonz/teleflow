<?php
// api/agent_queue_pref.php — gestión de preferencias colas por agente
//
// Endpoints:
//   GET  ?action=list_all       → [{agent_number, name, queues:[9101,...]}, ...]
//   POST ?action=set            → {agent_number, queues:[9101,9102], penalty?}
//                                 borra prefs previas y setea las nuevas
//
// Schema: teleflow.agent_queue_pref (agent_number VARCHAR, queue VARCHAR, penalty INT)

require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);
require_once __DIR__ . '/../config.php';

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8mb4",
        $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Asegurar tabla
    $tf->exec("CREATE TABLE IF NOT EXISTS agent_queue_pref (
        agent_number VARCHAR(16) NOT NULL,
        queue VARCHAR(32) NOT NULL,
        penalty INT NOT NULL DEFAULT 0,
        PRIMARY KEY (agent_number, queue)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $cc = new PDO("mysql:host=$PBX_DB_HOST;dbname=call_center;charset=utf8mb4",
        $PBX_DB_USER, $PBX_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    tf_json_out(['ok' => false, 'error' => 'db: ' . $e->getMessage()], 500);
}

$action = $_GET['action'] ?? '';

if ($action === 'list_all') {
    // Todos los agentes activos + sus prefs
    $agents = $cc->query("SELECT number AS agent_number, name FROM agent WHERE estatus='A' ORDER BY number")
                 ->fetchAll(PDO::FETCH_ASSOC);
    $prefs = $tf->query("SELECT agent_number, queue FROM agent_queue_pref ORDER BY agent_number, queue")
                ->fetchAll(PDO::FETCH_ASSOC);
    $byAgent = [];
    foreach ($prefs as $p) {
        $byAgent[$p['agent_number']][] = $p['queue'];
    }
    foreach ($agents as &$a) {
        $a['queues'] = $byAgent[$a['agent_number']] ?? [];
    }
    tf_json_out(['ok' => true, 'agents' => $agents]);
}

if ($action === 'set') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $agent = preg_replace('/[^0-9]/', '', $body['agent_number'] ?? '');
    $queues = is_array($body['queues'] ?? null) ? $body['queues'] : [];
    $penalty = (int)($body['penalty'] ?? 0);
    if (!$agent) tf_json_out(['ok' => false, 'error' => 'agent_number requerido'], 400);
    try {
        $tf->beginTransaction();
        $tf->prepare("DELETE FROM agent_queue_pref WHERE agent_number = ?")->execute([$agent]);
        $ins = $tf->prepare("INSERT INTO agent_queue_pref (agent_number, queue, penalty) VALUES (?, ?, ?)");
        foreach ($queues as $q) {
            $q = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$q);
            if ($q) $ins->execute([$agent, $q, $penalty]);
        }
        $tf->commit();
        tf_json_out(['ok' => true, 'agent_number' => $agent, 'queues' => $queues]);
    } catch (Exception $e) {
        $tf->rollBack();
        tf_json_out(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

tf_json_out(['ok' => false, 'error' => 'action inválida'], 400);
