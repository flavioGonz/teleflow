<?php
// TeleFlow — devuelve la hora actual del server con timezone correcta
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

@include __DIR__ . '/../config.php';   // aplica date_default_timezone_set

$now = microtime(true);
echo json_encode([
    'ok'   => true,
    'ts'   => round($now * 1000),
    'iso'  => date('Y-m-d H:i:s', (int)$now),
    'tz'   => date_default_timezone_get(),
    'host' => gethostname(),
]);
