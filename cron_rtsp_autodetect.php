<?php
/**
 * cron_rtsp_autodetect.php
 *
 * Job batch que recorre todas las extensiones SIP registradas y, si NO tienen
 * rtsp_url manual definida en ext_meta, intenta detectar la URL RTSP del
 * dispositivo probando paths comunes de Akuvox, Hikvision y Dahua.
 *
 * Diseño:
 *  - Solo se ejecutan probes sobre internos con peer registrado en Asterisk.
 *  - rtsp_url con source='manual' NUNCA se pisa.
 *  - rtsp_url con source='auto' se mantiene si todavía responde; si dejó de
 *    responder por más de 1h se reintenta.
 *  - rtsp_auto_tried_at evita martillar: throttle 6h por intento sin éxito.
 *  - DESCRIBE RTSP considera 200 OK *o* 401 Unauthorized como "URL existe".
 *
 * Cron sugerido (systemd timer): cada 10 min.
 *
 * Uso manual:
 *   php /var/www/teleflow/cron_rtsp_autodetect.php           # corrida normal
 *   php /var/www/teleflow/cron_rtsp_autodetect.php --dry-run # no escribe DB
 *   php /var/www/teleflow/cron_rtsp_autodetect.php --verbose # log detallado
 *   php /var/www/teleflow/cron_rtsp_autodetect.php --ext NNN # probar solo NNN
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/config.php';

$DRY_RUN  = in_array('--dry-run', $argv, true);
$VERBOSE  = in_array('--verbose', $argv, true) || in_array('-v', $argv, true);
$ONLY_EXT = null;
foreach ($argv as $i => $a) {
    if ($a === '--ext' && isset($argv[$i + 1])) {
        $ONLY_EXT = preg_replace('/\D/', '', $argv[$i + 1]);
    }
}

// ── Paths a probar (en orden de prioridad) ──
$PROBE_PATHS = [
    // Akuvox (lo que pidió Mauricio explícitamente)
    ['vendor' => 'akuvox',    'path' => '/live/ch00_1'],   // HD
    ['vendor' => 'akuvox',    'path' => '/live/ch00_0'],   // SD fallback
    // Hikvision
    ['vendor' => 'hikvision', 'path' => '/Streaming/Channels/101'],
    ['vendor' => 'hikvision', 'path' => '/Streaming/Channels/102'],
    // Dahua
    ['vendor' => 'dahua',     'path' => '/cam/realmonitor?channel=1&subtype=0'],
    ['vendor' => 'dahua',     'path' => '/cam/realmonitor?channel=1&subtype=1'],
];

const REPROBE_AUTO_AFTER_SECONDS = 3600;   // rechequeo URL auto: 1h
const RETRY_THROTTLE_SECONDS     = 21600;  // intento fallido: 6h
const PROBE_TIMEOUT_SECONDS      = 2;

function log_v(string $msg): void {
    global $VERBOSE;
    if ($VERBOSE) fwrite(STDOUT, "[v] $msg\n");
}
function log_i(string $msg): void { fwrite(STDOUT, "[i] $msg\n"); }
function log_e(string $msg): void { fwrite(STDERR, "[!] $msg\n"); }

/**
 * DESCRIBE RTSP a host:554 path. Devuelve [code, reason] o null si la conexión falla.
 */
function rtsp_probe(string $host, string $path, int $timeoutSec = 2): ?array {
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client(
        "tcp://$host:554",
        $errno, $errstr,
        $timeoutSec,
        STREAM_CLIENT_CONNECT
    );
    if (!$fp) {
        log_v("probe $host$path: connect failed ($errstr)");
        return null;
    }
    stream_set_timeout($fp, $timeoutSec);

    $url = "rtsp://$host$path";
    $req = "DESCRIBE $url RTSP/1.0\r\n" .
           "CSeq: 1\r\n" .
           "User-Agent: TeleflowAutoDetect/1.0\r\n" .
           "Accept: application/sdp\r\n\r\n";
    @fwrite($fp, $req);

    $line = @fgets($fp, 1024);
    @fclose($fp);
    if (!$line) {
        log_v("probe $host$path: empty response");
        return null;
    }
    if (!preg_match('#^RTSP/\d\.\d\s+(\d+)\s+(.*?)\r?\n#', $line, $m)) {
        log_v("probe $host$path: malformed status: " . trim($line));
        return null;
    }
    return [(int)$m[1], trim($m[2])];
}

/** 200 OK o 401 Unauthorized = URL existe (404/454 = no existe ese path). */
function rtsp_url_is_valid(?array $resp): bool {
    if (!$resp) return false;
    return $resp[0] === 200 || $resp[0] === 401;
}

/**
 * AMI `sip show peers` → [ext => ip] solo peers OK/Reachable con IP válida.
 */
function get_registered_peer_ips(): array {
    global $AMI_HOST, $AMI_PORT, $AMI_USER, $AMI_PASS;
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client("tcp://$AMI_HOST:$AMI_PORT", $errno, $errstr, 4);
    if (!$fp) { log_e("AMI connect failed: $errstr"); return []; }
    stream_set_timeout($fp, 5);
    fwrite($fp, "Action: Login\r\nUsername: $AMI_USER\r\nSecret: $AMI_PASS\r\nEvents: off\r\n\r\n");
    while (!feof($fp)) {
        $l = fgets($fp, 1024);
        if ($l === false || trim($l) === '') break;
    }
    fwrite($fp, "Action: Command\r\nCommand: sip show peers\r\n\r\n");
    $buf = '';
    $start = microtime(true);
    while (!feof($fp) && microtime(true) - $start < 6) {
        $chunk = fread($fp, 4096);
        if ($chunk === false || $chunk === '') {
            usleep(50000);
            continue;
        }
        $buf .= $chunk;
        if (strpos($buf, '--END COMMAND--') !== false) break;
    }
    fwrite($fp, "Action: Logoff\r\n\r\n");
    fclose($fp);

    $map = [];
    foreach (explode("\n", $buf) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, 'Name/username') !== false) continue;
        if (!preg_match('/^(\d{3,7})(?:\/[\w-]+)?\s+(\S+)\s/', $line, $m)) continue;
        $ext = $m[1];
        $ip  = $m[2];
        if (!filter_var($ip, FILTER_VALIDATE_IP)) continue;
        if (strpos($line, 'OK (') === false && strpos($line, 'Reachable') === false) continue;
        $map[$ext] = $ip;
    }
    return $map;
}

// ── MAIN ─────────────────────────────────────────────────────────────────
$started = microtime(true);
log_v(($DRY_RUN ? '[DRY-RUN] ' : '') . "RTSP autodetect run starting");

try {
    $tf = new PDO(
        "mysql:host=$DB_HOST;dbname=$TF_DB_NAME;charset=utf8",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    log_e("DB connect failed: " . $e->getMessage());
    exit(1);
}

try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN rtsp_url_source ENUM('manual','auto') NULL DEFAULT NULL"); } catch (Exception $_) {}
try { $tf->exec("ALTER TABLE ext_meta ADD COLUMN rtsp_auto_tried_at DATETIME NULL DEFAULT NULL"); } catch (Exception $_) {}

$peers = get_registered_peer_ips();
if (!$peers) { log_e("no registered peers found"); exit(0); }
log_v("registered peers with valid IP: " . count($peers));

if ($ONLY_EXT) {
    if (!isset($peers[$ONLY_EXT])) {
        log_e("ext $ONLY_EXT not registered (no IP). Aborting.");
        exit(1);
    }
    $peers = [$ONLY_EXT => $peers[$ONLY_EXT]];
}

$placeholders = implode(',', array_fill(0, count($peers), '?'));
$st = $tf->prepare("SELECT ext, rtsp_url, rtsp_url_source, rtsp_auto_tried_at FROM ext_meta WHERE ext IN ($placeholders)");
$st->execute(array_keys($peers));
$meta = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $meta[$r['ext']] = $r;

$stats = ['skip_manual' => 0, 'skip_throttled' => 0, 'still_ok' => 0, 'detected' => 0, 'no_match' => 0];

foreach ($peers as $ext => $ip) {
    $row = $meta[$ext] ?? null;
    $src = $row['rtsp_url_source'] ?? null;
    $url = $row['rtsp_url'] ?? null;
    $tried = $row['rtsp_auto_tried_at'] ?? null;
    $triedTs = $tried ? strtotime($tried) : 0;
    $age = time() - $triedTs;

    // 1. Manual: NO TOCAR JAMÁS
    if ($src === 'manual' && $url) {
        log_v("ext $ext: manual URL set, skipping");
        $stats['skip_manual']++;
        continue;
    }

    // 2. Auto existente todavía fresca: dejar como está
    if ($src === 'auto' && $url && $age < REPROBE_AUTO_AFTER_SECONDS) {
        log_v("ext $ext: auto URL still fresh ($age s ago)");
        $stats['still_ok']++;
        continue;
    }

    // 3. Throttle de intentos fallidos
    if (!$url && $age < RETRY_THROTTLE_SECONDS && $tried) {
        log_v("ext $ext: recently probed without success ($age s ago), throttled");
        $stats['skip_throttled']++;
        continue;
    }

    // 4. PROBE
    log_v("ext $ext @ $ip: probing...");
    $found = null;
    foreach ($PROBE_PATHS as $cand) {
        $resp = rtsp_probe($ip, $cand['path'], PROBE_TIMEOUT_SECONDS);
        if (rtsp_url_is_valid($resp)) {
            $found = $cand;
            $found['code'] = $resp[0];
            break;
        }
    }

    $now = date('Y-m-d H:i:s');
    if ($found) {
        $detected = "rtsp://$ip" . $found['path'];
        $label    = ucfirst($found['vendor']) . ' auto';
        log_i("ext $ext: DETECTED ({$found['vendor']}, code {$found['code']}): $detected");
        $stats['detected']++;
        if (!$DRY_RUN) {
            $upsert = $tf->prepare("INSERT INTO ext_meta (ext, rtsp_url, rtsp_label, rtsp_url_source, rtsp_auto_tried_at) VALUES (?, ?, ?, 'auto', ?) ON DUPLICATE KEY UPDATE rtsp_url=VALUES(rtsp_url), rtsp_label=COALESCE(rtsp_label, VALUES(rtsp_label)), rtsp_url_source='auto', rtsp_auto_tried_at=VALUES(rtsp_auto_tried_at)");
            $upsert->execute([$ext, $detected, $label, $now]);
        }
    } else {
        log_v("ext $ext: no RTSP path matched");
        $stats['no_match']++;
        if (!$DRY_RUN) {
            $upsert = $tf->prepare("INSERT INTO ext_meta (ext, rtsp_auto_tried_at) VALUES (?, ?) ON DUPLICATE KEY UPDATE rtsp_auto_tried_at=VALUES(rtsp_auto_tried_at)");
            $upsert->execute([$ext, $now]);
        }
    }
}

$elapsed = round(microtime(true) - $started, 2);
log_i("done in {$elapsed}s: " .
      "detected={$stats['detected']}, " .
      "still_ok={$stats['still_ok']}, " .
      "no_match={$stats['no_match']}, " .
      "skip_manual={$stats['skip_manual']}, " .
      "skip_throttled={$stats['skip_throttled']}" .
      ($DRY_RUN ? ' [DRY-RUN]' : ''));
