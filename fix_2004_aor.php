<?php
$ext = '2004';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'remove_existing', 'yes', 0) ON DUPLICATE KEY UPDATE data='yes'")->execute([$ext]);
    echo "remove_existing=yes actualizado para $ext.\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    echo "Reload completado.\n";
} catch (Exception $e) { echo "Error: ".$e->getMessage(); }
