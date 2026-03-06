<?php
include 'config.php';
$ext = '2004';
$pass = 'Teleflow2024*';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    // Update secret
    $db->prepare("UPDATE sip SET data=? WHERE id=? AND keyword='secret'")->execute([$pass, $ext]);
    
    // Ensure vital PJSIP/WebRTC flags (mapped by Issabel)
    $flags = [
        'webrtc' => 'yes',
        'force_rport' => 'yes',
        'rewrite_contact' => 'yes',
        'rtp_symmetric' => 'yes',
        'use_avpf' => 'yes',
        'ice_support' => 'yes',
        'media_encryption' => 'dtls'
    ];
    
    foreach($flags as $k => $v) {
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, $k, $v]);
    }
    
    echo "Configuración en base de datos actualizada para $ext.\n";
    
    // Run retrieve_conf
    $fw = '/var/lib/asterisk/bin/fwconsole';
    if (file_exists($fw)) {
        echo "Ejecutando fwconsole reload...\n";
        shell_exec("$fw reload --quiet");
    } else {
        echo "No se encontró fwconsole. Recargando Asterisk directamente...\n";
        shell_exec("asterisk -rx 'dialplan reload'");
        shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    }
    echo "Proceso completado.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

