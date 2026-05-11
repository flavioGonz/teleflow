<?php
/**
 * api/rtsp_snapshot.php — captura y gestión de snapshots RTSP por extensión
 *
 * Endpoints:
 *   POST ?action=capture   ext=<n>    — toma snapshot ahora (ffmpeg → JPG)
 *   GET  ?action=list      ext=<n>    — lista capturas últimos 30 días
 *   GET  ?action=image     file=<n>   — sirve la imagen (con session check)
 *   POST ?action=cleanup              — borra snapshots > 30 días (cron-friendly)
 *
 * Storage: uploads/rtsp_snapshots/{ext}/{YYYYMMDD_HHMMSS}.jpg
 * Cleanup automático al hacer list (best effort).
 *
 * HORIZON · Teleflow
 */
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

// Authorization: admin OR el propio agente
$is_admin = !empty($_SESSION['tf_user']);
$is_agent = !empty($_SESSION['agent_user']);
if (!$is_admin && !$is_agent) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$STORE_ROOT = __DIR__ . '/../uploads/rtsp_snapshots';
$MAX_AGE_DAYS = 30;

// Asegurar carpeta
if (!is_dir($STORE_ROOT)) {
    @mkdir($STORE_ROOT, 0775, true);
}

function get_ext_rtsp_url($ext) {
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/../db/acl.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Usar PBX MySQL para ext_meta
        $pdo = mysql_pbx();
        $st = $pdo->prepare("SELECT rtsp_url, rtsp_label FROM ext_meta WHERE ext = ?");
        $st->execute([$ext]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ? ['url' => $r['rtsp_url'], 'label' => $r['rtsp_label']] : null;
    } catch (Exception $e) {
        return null;
    }
}

function safe_ext($ext) {
    if (!preg_match('/^\d{1,8}$/', $ext)) return null;
    return $ext;
}

function snapshot_dir($store, $ext) {
    $d = $store . '/' . $ext;
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}

function cleanup_old($dir, $max_age_days) {
    if (!is_dir($dir)) return 0;
    $deleted = 0;
    $cutoff = time() - ($max_age_days * 86400);
    foreach (glob($dir . '/*.jpg') as $f) {
        if (filemtime($f) < $cutoff) {
            @unlink($f);
            $deleted++;
        }
    }
    return $deleted;
}

// ─── action=list ──────────────────────────────────────────────────────────
if ($action === 'list') {
    $ext = safe_ext($_GET['ext'] ?? '');
    if (!$ext) { echo json_encode(['status'=>'error','message'=>'ext inválida']); exit; }
    $dir = snapshot_dir($STORE_ROOT, $ext);
    cleanup_old($dir, $MAX_AGE_DAYS);  // best-effort
    $files = glob($dir . '/*.jpg') ?: [];
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
    $snapshots = [];
    foreach ($files as $f) {
        $name = basename($f);
        $mt = filemtime($f);
        // Filename = YYYYMMDD_HHMMSS.jpg
        $ts = preg_match('/^(\d{8})_(\d{6})/', $name, $m)
            ? sprintf('%s-%s-%s %s:%s:%s', substr($m[1],0,4), substr($m[1],4,2), substr($m[1],6,2),
                                            substr($m[2],0,2), substr($m[2],2,2), substr($m[2],4,2))
            : date('Y-m-d H:i:s', $mt);
        $snapshots[] = [
            'filename'  => $name,
            'timestamp' => $ts,
            'mtime'     => $mt,
            'size'      => filesize($f),
            'url'       => "api/rtsp_snapshot.php?action=image&ext={$ext}&file=" . urlencode($name),
        ];
    }
    echo json_encode(['status'=>'ok','ext'=>$ext,'snapshots'=>$snapshots,'count'=>count($snapshots)]);
    exit;
}

// ─── action=image ─ servir snapshot ────────────────────────────────────────
if ($action === 'image') {
    $ext = safe_ext($_GET['ext'] ?? '');
    $file = basename($_GET['file'] ?? '');
    if (!$ext || !preg_match('/^[\w\-]+\.jpg$/i', $file)) {
        http_response_code(400); exit;
    }
    $path = $STORE_ROOT . '/' . $ext . '/' . $file;
    if (!file_exists($path)) {
        http_response_code(404); exit;
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: private, max-age=300');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ─── action=capture ─ toma snapshot ahora ──────────────────────────────────
if ($action === 'capture') {
    $ext = safe_ext($_POST['ext'] ?? $_GET['ext'] ?? '');
    if (!$ext) { echo json_encode(['status'=>'error','message'=>'ext inválida']); exit; }
    $meta = get_ext_rtsp_url($ext);
    if (!$meta || empty($meta['url'])) {
        echo json_encode(['status'=>'error','message'=>'Extensión sin RTSP configurado']);
        exit;
    }
    $url = $meta['url'];
    $ts = date('Ymd_His');
    $dir = snapshot_dir($STORE_ROOT, $ext);
    $out = $dir . '/' . $ts . '.jpg';

    // ffmpeg: 1 frame, calidad 4, timeout 8s. Compatible con rtsp/http/hls
    $cmd_url = escapeshellarg($url);
    $cmd_out = escapeshellarg($out);
    $cmd = "timeout 10 ffmpeg -y -loglevel error -rtsp_transport tcp -i {$cmd_url} -frames:v 1 -q:v 4 {$cmd_out} 2>&1";
    $output = [];
    $rc = 0;
    @exec($cmd, $output, $rc);

    if ($rc !== 0 || !file_exists($out) || filesize($out) < 1024) {
        @unlink($out);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Falló la captura. ¿ffmpeg instalado? ¿RTSP accesible?',
            'rc'      => $rc,
            'out'     => implode("\n", array_slice($output, -5))
        ]);
        exit;
    }
    @chmod($out, 0664);

    echo json_encode([
        'status'    => 'ok',
        'ext'       => $ext,
        'filename'  => $ts . '.jpg',
        'timestamp' => date('Y-m-d H:i:s'),
        'url'       => "api/rtsp_snapshot.php?action=image&ext={$ext}&file={$ts}.jpg",
        'size'      => filesize($out),
    ]);
    exit;
}

// ─── action=cleanup ─ borra > 30 días en TODAS las extensiones ─────────────
if ($action === 'cleanup') {
    if (!$is_admin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'admin only']); exit; }
    $total = 0;
    foreach (glob($STORE_ROOT . '/*', GLOB_ONLYDIR) as $d) {
        $total += cleanup_old($d, $MAX_AGE_DAYS);
    }
    echo json_encode(['status'=>'ok','deleted'=>$total]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Acción desconocida']);
