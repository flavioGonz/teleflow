<?php
$exts = ['2002', '2003', '2004'];
$pass = 'Teleflow2024'; // Nueva clave sin caracteres especiales
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    foreach($exts as $ext) {
        $db->prepare("UPDATE sip SET data=? WHERE id=? AND keyword='secret'")->execute([$pass, $ext]);
        // Asegurar que rewrite_contact y force_rport estén en YES en la DB para que Issabel no los pise
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'rewrite_contact', 'yes', 0) ON DUPLICATE KEY UPDATE data='yes'")->execute([$ext]);
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'force_rport', 'yes', 0) ON DUPLICATE KEY UPDATE data='yes'")->execute([$ext]);
    }
    
    // Cambiar el orden de identificacion global a username primero
    shell_exec("asterisk -rx 'pjsip set global endpoint_identifier_order username,ip,anonymous' 2>/dev/null");
    
    echo "Claves actualizadas a $pass y parámetros NAT forzados.\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    echo "Asterisk recargado.\n";
} catch (Exception $e) { echo $e->getMessage(); }
