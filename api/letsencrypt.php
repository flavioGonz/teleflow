<?php
/**
 * api/letsencrypt.php
 *
 * Endpoint para gestionar el certificado Let's Encrypt desde la UI.
 *
 * Actions:
 *   ?action=status  → lee `sudo certbot certificates`, devuelve info de cada cert
 *                     (dominio, fechas, días restantes, paths)
 *   ?action=renew   → ejecuta `sudo certbot renew --force-renewal --quiet`
 *                     y devuelve el nuevo status
 *
 * Requiere /etc/sudoers.d/teleflow-le que autoriza a www-data a correr
 * certbot certificates y certbot renew sin password.
 */
// F5.3: session + JSON + auth admin via _bootstrap.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);
require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

function read_certs(): array {
    // certbot certificates devuelve algo como:
    //   Certificate Name: hzn-flow.horizonseguridad.com
    //     Serial Number: ...
    //     Domains: hzn-flow.horizonseguridad.com
    //     Expiry Date: 2026-08-20 10:51:54+00:00 (VALID: 89 days)
    //     Certificate Path: /etc/letsencrypt/live/hzn-flow.horizonseguridad.com/fullchain.pem
    //     Private Key Path: /etc/letsencrypt/live/hzn-flow.horizonseguridad.com/privkey.pem
    $out = shell_exec('sudo /usr/bin/certbot certificates 2>&1');
    if (!$out) return ['error' => 'certbot output empty'];

    $certs = [];
    $current = null;
    foreach (explode("\n", $out) as $line) {
        $trim = trim($line);
        if (preg_match('/^Certificate Name:\s*(.+)$/', $trim, $m)) {
            if ($current) $certs[] = $current;
            $current = ['name' => $m[1]];
        } elseif ($current) {
            if (preg_match('/^Serial Number:\s*(.+)$/', $trim, $m))         $current['serial']      = $m[1];
            elseif (preg_match('/^Domains:\s*(.+)$/', $trim, $m))           $current['domains']     = preg_split('/\s+/', $m[1]);
            elseif (preg_match('/^Expiry Date:\s*(\S+\s+\S+\S+)\s*\((\w+):\s*(\d+)\s*days?\)/', $trim, $m)) {
                $current['expiry']      = $m[1];
                $current['status']      = $m[2];     // VALID, EXPIRED, INVALID
                $current['days_left']   = (int)$m[3];
            }
            elseif (preg_match('/^Certificate Path:\s*(.+)$/', $trim, $m)) $current['cert_path']   = $m[1];
            elseif (preg_match('/^Private Key Path:\s*(.+)$/', $trim, $m)) $current['key_path']    = $m[1];
            elseif (preg_match('/^Key Type:\s*(.+)$/', $trim, $m))         $current['key_type']    = $m[1];
        }
    }
    if ($current) $certs[] = $current;
    return ['certs' => $certs, 'raw' => $out];
}

if ($action === 'status') {
    echo json_encode(['success' => true] + read_certs());
    exit;
}

if ($action === 'renew') {
    // --force-renewal pide a LE un cert nuevo aunque el actual no esté por vencer.
    // --no-random-sleep-on-renew: no demorarse el random 0-480s típico del cron.
    $out = shell_exec('sudo /usr/bin/certbot renew --force-renewal --no-random-sleep-on-renew 2>&1');
    // Refrescar status post-renew
    echo json_encode([
        'success' => true,
        'output'  => $out,
    ] + read_certs());
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown action']);
