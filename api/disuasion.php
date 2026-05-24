<?php
/**
 * api/disuasion.php
 *
 * Lectura unificada de grupos de voceo (paging) — FreePBX nativos + custom de Teleflow.
 * Y disparo de "vocear" via AMI Originate.
 *
 * Actions:
 *   ?action=list         → {success, groups: [{kind: 'freepbx'|'custom', page_code, name, members: [ext...], description, ...}]}
 *   ?action=originate    → POST page_code, from_ext → Originate via AMI al feature code (default *80)
 *                            o directamente al page_code si es de FreePBX
 *   ?action=custom_save  → POST id?, name, page_code, icon, color, description, members[]
 *   ?action=custom_del   → POST id
 *
 * AMI Originate usa el helper ami_cmd() del api/index.php (multi-line response).
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_ops_db.php';
ops_auth_or_403();

function ami_originate(string $fromExt, string $context, string $extension): array {
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client("tcp://$AMI_HOST:$AMI_PORT", $errno, $errstr, 4);
    if (!$fp) return ['ok' => false, 'error' => "AMI connect: $errstr"];
    stream_set_timeout($fp, 6);
    fwrite($fp, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
    // drain login
    while (!feof($fp)) { $l = fgets($fp, 1024); if ($l === false || trim($l) === '') break; }
    $aid = 'tfpage-' . bin2hex(random_bytes(4));
    $payload = "Action: Originate\r\n"
             . "Channel: Local/$fromExt@from-internal\r\n"
             . "Context: $context\r\n"
             . "Exten: $extension\r\n"
             . "Priority: 1\r\n"
             . "CallerID: \"Teleflow Page\" <$fromExt>\r\n"
             . "Async: true\r\n"
             . "ActionID: $aid\r\n\r\n";
    fwrite($fp, $payload);
    $buf = ''; $start = microtime(true);
    while (!feof($fp) && microtime(true) - $start < 5) {
        $c = fread($fp, 4096);
        if ($c === false || $c === '') { usleep(50000); continue; }
        $buf .= $c;
        if (strpos($buf, $aid) !== false && strpos($buf, "\r\n\r\n", strpos($buf, $aid)) !== false) break;
    }
    fwrite($fp, "Action: Logoff\r\n\r\n");
    fclose($fp);
    $ok = (strpos($buf, "Response: Success") !== false);
    return ['ok' => $ok, 'aid' => $aid, 'raw' => $buf];
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $groups = [];
    // FreePBX nativos
    try {
        $pbx = pbx_db_ro();
        $cfg = $pbx->query("SELECT page_group, description, force_page, duplex FROM paging_config ORDER BY page_group")
                   ->fetchAll(PDO::FETCH_ASSOC);
        $membersByGroup = [];
        foreach ($pbx->query("SELECT page_number, ext FROM paging_groups ORDER BY page_number, ext")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $membersByGroup[$r['page_number']][] = $r['ext'];
        }
        foreach ($cfg as $g) {
            $groups[] = [
                'kind'        => 'freepbx',
                'id'          => 'fpbx-' . $g['page_group'],
                'page_code'   => $g['page_group'],
                'name'        => $g['description'] ?: ('Page ' . $g['page_group']),
                'description' => $g['description'],
                'members'     => $membersByGroup[$g['page_group']] ?? [],
                'force_page'  => (int)$g['force_page'],
                'duplex'      => (int)$g['duplex'],
                'icon'        => 'campaign',
                'color'       => '#f59e0b',
            ];
        }
    } catch (Exception $e) { /* ignore — devolvemos al menos los custom */ }

    // Custom Teleflow
    try {
        $tf = ops_db();
        $cust = $tf->query("SELECT * FROM disuasion_groups ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cust as $g) {
            $groups[] = [
                'kind'        => 'custom',
                'id'          => 'cust-' . $g['id'],
                'custom_id'   => (int)$g['id'],
                'page_code'   => $g['page_code'],
                'name'        => $g['name'],
                'description' => $g['description'],
                'members'     => [],  // los miembros reales son los del paging_groups con ese page_code
                'icon'        => $g['icon'] ?: 'campaign',
                'color'       => $g['color'] ?: '#f59e0b',
            ];
        }
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'groups' => $groups]);
    exit;
}

if ($action === 'originate') {
    $pageCode = preg_replace('/[^A-Za-z0-9*#]/', '', $_POST['page_code'] ?? '');
    $fromExt  = preg_replace('/\D/', '', $_POST['from_ext'] ?? '');
    if (!$pageCode || !$fromExt) { echo json_encode(['success'=>false,'error'=>'page_code y from_ext requeridos']); exit; }
    // Page Code en FreePBX es directamente la extension dentro de from-internal
    $res = ami_originate($fromExt, 'from-internal', $pageCode);
    echo json_encode([
        'success' => $res['ok'],
        'page_code' => $pageCode,
        'from_ext' => $fromExt,
        'error'   => $res['ok'] ? null : 'AMI Originate failed',
        'aid'     => $res['aid'] ?? null,
    ]);
    exit;
}

if ($action === 'custom_save') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $code  = preg_replace('/[^A-Za-z0-9*#]/', '', $_POST['page_code'] ?? '');
    $icon  = trim($_POST['icon'] ?? '') ?: 'campaign';
    $color = trim($_POST['color'] ?? '') ?: '#f59e0b';
    $desc  = trim($_POST['description'] ?? '');
    if (!$name || !$code) { echo json_encode(['success'=>false,'error'=>'name y page_code requeridos']); exit; }
    try {
        $tf = ops_db();
        if ($id) {
            $tf->prepare("UPDATE disuasion_groups SET name=?, page_code=?, icon=?, color=?, description=? WHERE id=?")
               ->execute([$name, $code, $icon, $color, $desc ?: null, $id]);
        } else {
            $tf->prepare("INSERT INTO disuasion_groups (name, page_code, icon, color, description) VALUES (?,?,?,?,?)")
               ->execute([$name, $code, $icon, $color, $desc ?: null]);
            $id = (int)$tf->lastInsertId();
        }
        echo json_encode(['success'=>true, 'id'=>$id]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

if ($action === 'custom_del') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'id faltante']); exit; }
    try {
        ops_db()->prepare("DELETE FROM disuasion_groups WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

echo json_encode(['success'=>false, 'error'=>'unknown action']);
