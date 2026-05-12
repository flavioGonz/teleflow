<?php
// TeleFlow — devuelve la hora actual del server (sincronizado por NTP con la PBX 10.1.1.7)
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$now = microtime(true);
echo json_encode([
    'ok'   => true,
    'ts'   => round($now * 1000),
    'iso'  => date('Y-m-d H:i:s', (int)$now),
    'tz'   => date_default_timezone_get(),
    'host' => gethostname(),
]);
