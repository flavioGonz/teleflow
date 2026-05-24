<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_ops_db.php';
ops_auth_or_403();

$action = $_GET['action'] ?? '';
$db = ops_db();

if ($action === 'list') {
    // Devuelve clientes con conteos por kind
    $rows = $db->query("SELECT c.*,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='ext')    AS n_ext,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='paging') AS n_paging,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='nvr')    AS n_nvr,
        (SELECT COUNT(*) FROM client_assoc WHERE client_id=c.id AND kind='queue')  AS n_queue
        FROM clients c ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'clients'=>$rows]); exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    $c = $db->prepare("SELECT * FROM clients WHERE id=?"); $c->execute([$id]);
    $client = $c->fetch(PDO::FETCH_ASSOC);
    if (!$client) { echo json_encode(['success'=>false,'error'=>'no existe']); exit; }
    $assoc = $db->prepare("SELECT kind, ref_id FROM client_assoc WHERE client_id=? ORDER BY kind, ref_id");
    $assoc->execute([$id]);
    $byKind = ['ext'=>[], 'paging'=>[], 'nvr'=>[], 'queue'=>[]];
    foreach ($assoc->fetchAll(PDO::FETCH_ASSOC) as $r) $byKind[$r['kind']][] = $r['ref_id'];
    $client['assoc'] = $byKind;
    echo json_encode(['success'=>true, 'client'=>$client]); exit;
}

if ($action === 'save') {
    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'nombre requerido']); exit; }
    try {
        if ($id) {
            $st = $db->prepare("UPDATE clients SET name=?, address=?, contact=?, notes=? WHERE id=?");
            $st->execute([$name, $address ?: null, $contact ?: null, $notes ?: null, $id]);
        } else {
            $st = $db->prepare("INSERT INTO clients (name, address, contact, notes) VALUES (?,?,?,?)");
            $st->execute([$name, $address ?: null, $contact ?: null, $notes ?: null]);
            $id = (int)$db->lastInsertId();
        }
        echo json_encode(['success'=>true, 'id'=>$id]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    try {
        $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'assoc_set') {
    // Reemplaza TODAS las asociaciones de un kind para un cliente
    $id   = (int)($_POST['id'] ?? 0);
    $kind = $_POST['kind'] ?? '';
    if (!$id || !in_array($kind, ['ext','paging','nvr','queue'])) {
        echo json_encode(['success'=>false,'error'=>'inválido']); exit;
    }
    $refs = json_decode($_POST['refs'] ?? '[]', true);
    if (!is_array($refs)) $refs = [];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM client_assoc WHERE client_id=? AND kind=?")->execute([$id, $kind]);
        if ($refs) {
            $ins = $db->prepare("INSERT IGNORE INTO client_assoc (client_id, kind, ref_id) VALUES (?,?,?)");
            foreach ($refs as $r) {
                $r = trim((string)$r);
                if ($r !== '') $ins->execute([$id, $kind, $r]);
            }
        }
        $db->commit();
        echo json_encode(['success'=>true, 'count'=>count($refs)]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

/**
 * Bootstrap auto: agrupa todas las extensiones por "raíz" del name y crea
 * un cliente por cada raíz, asociando las exts correspondientes.
 *
 * Heurística de raíz: primera secuencia de letras antes de:
 *   - cualquier dígito
 *   - guión, paréntesis o coma
 *   - palabras genéricas (Videoportero, Hall, Garage, Barbacoa, AltoParlante, Principal, Secundario, etc.)
 * Trim de espacios y dejar máximo 3 palabras significativas.
 * Si el nombre completo es <20 chars y no matchea el patrón, usar el nombre tal cual.
 *
 * Parámetros:
 *   ?dry_run=1  → no escribe, devuelve preview {root → [exts...]}
 */
if ($action === 'bootstrap') {
    $dryRun = !empty($_GET['dry_run']) || !empty($_POST['dry_run']);
    try {
        $pbx = pbx_db_ro();
        $rows = $pbx->query("SELECT id AS ext, description AS name FROM devices WHERE description IS NOT NULL AND description != '' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>'No se pudo leer devices: '.$e->getMessage()]);
        exit;
    }

    // ── Extractor de raíz humana ──
    $stopWords = ['Videoportero','Videoportería','Hall','Garage','Barbacoa','AltoParlante','Altoparlante',
                  'Principal','Secundaria','Secundario','Sas','SAS','Bocina','Cámara','Camara',
                  'Portero','PPAL','Test','Testing','Demo','Backup','Failover','FailOver','Cola','Queue',
                  'Salida','Entrada','Acceso','Local'];
    $extractRoot = function(string $name) use ($stopWords): string {
        $name = trim($name);
        if ($name === '') return '';
        // Cortar en " - " primero (separador común)
        $parts = preg_split('/\s*[-–—]\s+/', $name, 2);
        $head = $parts[0];
        // Cortar en "(" (info extra entre paréntesis)
        $head = preg_split('/\s*\(/', $head, 2)[0];
        // Cortar antes del primer dígito (number suelto)
        $head = preg_split('/\s+\d/', $head, 2)[0];
        // Cortar antes de una stopword (al inicio de palabra)
        $stopRegex = '/\s+(' . implode('|', array_map('preg_quote', $stopWords)) . ')(\s|$)/i';
        $head = preg_split($stopRegex, $head, 2)[0];
        $head = trim($head);
        // Si quedó <2 chars, usar el name original truncado a 40
        if (mb_strlen($head) < 2) $head = mb_substr($name, 0, 40);
        // Limitar a 3 palabras
        $words = preg_split('/\s+/', $head);
        if (count($words) > 3) $head = implode(' ', array_slice($words, 0, 3));
        return $head;
    };

    $byRoot = [];
    foreach ($rows as $r) {
        $root = $extractRoot($r['name']);
        if (!$root) continue;
        $byRoot[$root][] = $r['ext'];
    }
    ksort($byRoot);

    $stats = ['groups' => count($byRoot), 'created' => 0, 'reused' => 0, 'assoc' => 0];
    $preview = [];
    foreach ($byRoot as $root => $exts) {
        $preview[$root] = $exts;
    }
    if ($dryRun) {
        echo json_encode(['success'=>true, 'dry_run'=>true, 'stats'=>$stats, 'preview'=>$preview]);
        exit;
    }

    $db = ops_db();
    foreach ($byRoot as $root => $exts) {
        // upsert cliente
        $get = $db->prepare("SELECT id FROM clients WHERE name=?");
        $get->execute([$root]);
        $cid = $get->fetchColumn();
        if (!$cid) {
            $ins = $db->prepare("INSERT INTO clients (name, notes) VALUES (?, ?)");
            $ins->execute([$root, 'Auto-bootstrap desde extensiones']);
            $cid = (int)$db->lastInsertId();
            $stats['created']++;
        } else {
            $stats['reused']++;
        }
        // upsert asociaciones (NO borra otras kinds)
        $ins2 = $db->prepare("INSERT IGNORE INTO client_assoc (client_id, kind, ref_id) VALUES (?, 'ext', ?)");
        foreach ($exts as $e) {
            $ins2->execute([$cid, $e]);
            $stats['assoc'] += $ins2->rowCount();
        }
    }
    echo json_encode(['success'=>true, 'stats'=>$stats, 'preview'=>$preview]);
    exit;
}

echo json_encode(['success'=>false, 'error'=>'unknown action']);
