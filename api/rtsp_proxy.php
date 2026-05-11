<?php
/**
 * TeleFlow — Proxy RTSP a HLS via MediaMTX
 *
 * GET ?ext=<N>  → asegura que MediaMTX tenga configurado el path 'ext_<N>'
 *                 apuntando a la rtsp_url de esa extension. Retorna el HLS URL.
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['tf_user']) && !isset($_SESSION['agent_user'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'No autorizado']); exit;
}
@session_write_close();
require_once __DIR__ . '/../config.php';

$ext = preg_replace('/\D/', '', $_GET['ext'] ?? '');
if (!$ext) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'falta ext']); exit; }

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=teleflow;charset=utf8", $DB_USER, $DB_PASS);
    $st = $tf->prepare("SELECT rtsp_url, rtsp_label FROM ext_meta WHERE ext = ?");
    $st->execute([$ext]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error: '.$e->getMessage()]); exit;
}

if (!$row || !$row['rtsp_url']) {
    echo json_encode(['status'=>'error','message'=>'Extensión sin rtsp_url configurada']); exit;
}

$rtsp_url = $row['rtsp_url'];
$path_name = "ext_$ext";
$mtx_api = 'http://127.0.0.1:9997';

$ch = curl_init("$mtx_api/v3/config/paths/get/$path_name");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$exists = ($code == 200);
$current = $exists ? json_decode($resp, true) : null;
$needs_update = !$exists || ($current['source'] ?? null) !== $rtsp_url;

if ($needs_update) {
    $endpoint = $exists ? "/v3/config/paths/patch/$path_name" : "/v3/config/paths/add/$path_name";
    $payload = json_encode([
        'source' => $rtsp_url,
        'sourceOnDemand' => true,
        'sourceOnDemandStartTimeout' => '10s',
        'sourceOnDemandCloseAfter' => '30s',
    ]);
    $ch = curl_init("$mtx_api$endpoint");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $r2 = curl_exec($ch);
    $c2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($c2 !== 200 && $c2 !== 201) {
        echo json_encode(['status'=>'error','message'=>'MediaMTX API: '.$r2,'http'=>$c2]);
        exit;
    }
}

$host = $_SERVER['HTTP_HOST'] ?? '10.1.1.192';
$host_only = preg_replace('/:.*/', '', $host);

echo json_encode([
    'status'      => 'ok',
    'ext'         => $ext,
    'label'       => $row['rtsp_label'] ?: "Videoportero ext $ext",
    'rtsp_source' => $rtsp_url,
    'hls_url'     => "http://$host_only:8888/$path_name/index.m3u8",
    'webrtc_url'  => "http://$host_only:8889/$path_name",
    'path'        => $path_name,
]);
