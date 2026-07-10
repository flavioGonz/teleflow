
<?php
/**
 * TeleFlow — Streaming de grabaciones del PBX
 *
 * GET /api/recording.php?file=<basename.gsm|wav|...>
 *   Busca la grabación en pbx-prod (vía SSH), la trae al LXC, convierte a WAV
 *   con ffmpeg si hace falta, cachea, y la sirve como audio/wav.
 *
 * GET /api/recording.php?file=<basename>&format=mp3
 *   Idem pero servido como audio/mpeg (más liviano para web).
 */
// F5.4: sesion via _bootstrap (SIN forzar JSON, porque este endpoint sirve audio).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>28800,'path'=>'/','samesite'=>'Lax','httponly'=>true]);
    session_start();
}
if (!isset($_SESSION['tf_user']) && !isset($_SESSION['agent_user'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'auth']);
    exit;
}

require_once __DIR__ . '/../config.php';

$file = basename($_GET['file'] ?? '');
$format = $_GET['format'] ?? 'wav';
if (!in_array($format, ['wav', 'mp3'])) $format = 'wav';
if (!$file) { http_response_code(400); exit; }
if (!preg_match('/^[A-Za-z0-9._\-]+$/', $file)) {
    http_response_code(400); echo "bad filename"; exit;
}

$cacheDir = '/var/cache/teleflow/recordings';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);

$cached = "$cacheDir/" . pathinfo($file, PATHINFO_FILENAME) . ".$format";
$gsm = "$cacheDir/" . pathinfo($file, PATHINFO_FILENAME) . ".gsm";

// Si ya está convertido en cache, servir directo
if (!file_exists($cached) || filesize($cached) === 0) {
    // Buscar el archivo en pbx-prod
    $find = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=4 root@10.1.1.7 "
        . escapeshellarg("find /var/spool/asterisk/monitor -name " . escapeshellarg($file) . " 2>/dev/null | head -1");
    $remotePath = trim(shell_exec($find) ?? '');
    if (!$remotePath) { http_response_code(404); echo "Recording not found"; exit; }

    // Bajar via SCP
    $scp = "scp -o StrictHostKeyChecking=no -o ConnectTimeout=5 root@10.1.1.7:" . escapeshellarg($remotePath) . " " . escapeshellarg($gsm) . " 2>&1";
    $r = shell_exec($scp);
    if (!file_exists($gsm) || filesize($gsm) === 0) {
        http_response_code(500); echo "Could not retrieve"; exit;
    }

    // Convertir con ffmpeg (soporta GSM nativo)
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'gsm' || $ext === 'wav' || $ext === 'mp3') {
        if ($format === 'mp3') {
            $cmd = "ffmpeg -y -i " . escapeshellarg($gsm) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($cached) . " 2>&1";
        } else {
            $cmd = "ffmpeg -y -i " . escapeshellarg($gsm) . " -ar 8000 -ac 1 " . escapeshellarg($cached) . " 2>&1";
        }
        shell_exec($cmd);
    }
    @unlink($gsm);
    if (!file_exists($cached) || filesize($cached) === 0) {
        http_response_code(500); echo "Conversion failed"; exit;
    }
}

if ($format === 'mp3') {
    header('Content-Type: audio/mpeg');
} else {
    header('Content-Type: audio/wav');
}
header('Content-Length: ' . filesize($cached));
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');
$_disp = isset($_GET['download']) ? 'attachment' : 'inline';
header('Content-Disposition: ' . $_disp . '; filename="' . pathinfo($file, PATHINFO_FILENAME) . '.' . $format . '"');
readfile($cached);
