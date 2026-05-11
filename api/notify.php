<?php
// TeleFlow — notify endpoint, llamado por AGI/PBX para forzar refresh inmediato
header('Content-Type: application/json');
$event = $_GET['event'] ?? '';
$payload = $_GET;

// Invalidar caches relevantes
@unlink('/tmp/teleflow_queue_show.txt');
@unlink('/tmp/teleflow_full_data.json');

// Persistir evento para debug + notify socket.io
$logfile = '/tmp/teleflow_notify.log';
@file_put_contents($logfile, '['.date('Y-m-d H:i:s').'] '.$event.' '.json_encode($payload)."\n", FILE_APPEND | LOCK_EX);

// Reenviar al hub de socket.io (puerto 9001 del realtime/index.js)
$body = json_encode(['event'=>$event,'data'=>$payload]);
$ch = curl_init('http://127.0.0.1:9001/broadcast');
curl_setopt_array($ch, [
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json','X-TF-Token: '.(getenv('TF_BROADCAST_TOKEN') ?: ($GLOBALS['TF_BROADCAST_TOKEN'] ?? ''))],
    CURLOPT_TIMEOUT => 1,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_CONNECTTIMEOUT => 1,
]);
$r = @curl_exec($ch);
curl_close($ch);

echo json_encode(['status'=>'ok','event'=>$event,'broadcast'=>!!$r]);
