<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    // Eliminar la opción rrealm que rompe PJSIP
    $db->exec("DELETE FROM sip WHERE keyword='realm'");
    echo "Opción 'realm' eliminada de la tabla sip.\n";
    
    // Asegurar que las extensiones 2000 tengan el transporte correcto
    $exts = ['2001', '2002', '2003', '2004'];
    foreach($exts as $ext) {
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'transport', 'transport-wss', 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext]);
        echo "Transporte WSS asegurado para $ext.\n";
    }
    
    // Aplicar cambios en Issabel
    echo "Regenerando configuración de Asterisk...\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("/usr/sbin/asterisk -rx 'core reload'");
    shell_exec("/usr/sbin/asterisk -rx 'module reload res_pjsip.so'");
    
    echo "Proceso finalizado.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
