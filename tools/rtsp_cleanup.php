<?php
/**
 * tools/rtsp_cleanup.php
 *
 * Recorre todas las rtsp_url con source='auto' en ext_meta y valida con ffprobe.
 * Si ffprobe NO puede leer el stream (auth required, vendor distinto, etc.),
 * borra la URL para que el cron pueda re-detectarla (o quedar vacía).
 *
 * Las URLs source='manual' NUNCA se tocan.
 *
 * Uso:
 *   php tools/rtsp_cleanup.php           # ejecuta
 *   php tools/rtsp_cleanup.php --dry-run # solo reporta qué borraría
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') { http_response_code(403); exit("CLI only\n"); }
require_once __DIR__ . '/../config.php';

$DRY = in_array('--dry-run', $argv, true);
$TIMEOUT = 4;

function ffprobe_ok(string $url, int $timeoutSec): bool {
    $u = escapeshellarg($url);
    $cmd = "timeout " . ($timeoutSec + 1) . " ffprobe -v error " .
           "-rtsp_transport tcp -timeout " . ($timeoutSec * 1000000) . " " .
           "-show_streams $u 2>&1 >/dev/null";
    exec($cmd, $out, $rc);
    return $rc === 0;
}

try {
    $tf = new PDO("mysql:host=$DB_HOST;dbname=$TF_DB_NAME;charset=utf8", $DB_USER, $DB_PASS,
                  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { fwrite(STDERR, "DB: " . $e->getMessage() . "\n"); exit(1); }

$rows = $tf->query("SELECT ext, rtsp_url FROM ext_meta WHERE rtsp_url_source='auto' AND rtsp_url IS NOT NULL ORDER BY ext")->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($rows) . " auto URLs to validate" . ($DRY ? " [DRY-RUN]" : "") . "\n\n";

$ok = $bad = 0;
$bad_exts = [];
foreach ($rows as $r) {
    $valid = ffprobe_ok($r['rtsp_url'], $TIMEOUT);
    if ($valid) {
        echo "  OK    ext {$r['ext']}: {$r['rtsp_url']}\n";
        $ok++;
    } else {
        echo "  BAD   ext {$r['ext']}: {$r['rtsp_url']}\n";
        $bad++; $bad_exts[] = $r['ext'];
        if (!$DRY) {
            $tf->prepare("UPDATE ext_meta SET rtsp_url=NULL, rtsp_label=NULL, rtsp_url_source=NULL, rtsp_auto_tried_at=NOW() WHERE ext=? AND rtsp_url_source='auto'")->execute([$r['ext']]);
        }
    }
}

echo "\n=== summary ===\n";
echo "OK:  $ok\n";
echo "BAD: $bad" . ($DRY ? " (dry-run, nothing changed)" : " (deleted from ext_meta)") . "\n";
