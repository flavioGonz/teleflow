<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    // Fetch all extensions from devices table
    $stmt = $db->query("SELECT id FROM devices WHERE tech IN ('pjsip','sip')");
    $exts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Reparando extensiones: " . implode(', ', $exts) . "\n";

    foreach($exts as $ext) {
        $flags = [
            'webrtc' => 'yes',
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'use_avpf' => 'yes',
            'ice_support' => 'yes',
            'media_encryption' => 'dtls',
            'remove_existing' => 'yes',
            'dial' => 'PJSIP/' . $ext,
            'devicetype' => 'webrtc'
        ];
        foreach($flags as $k => $v) {
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, $k, $v]);
        }
        
        // Manual AstDB fix just in case retrieve_conf fails or doesn't sync everything
        shell_exec("/usr/sbin/asterisk -rx 'database put AMPUSER $ext/device $ext'");
        shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/user $ext'");
        shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/dial PJSIP/$ext'");
        shell_exec("/usr/sbin/asterisk -rx 'database put DEVICE $ext/type fixed'");
        
        echo "[OK] AstDB & MySQL Flags updated for $ext.\n";
    }

    echo "Ejecutando retrieve_conf...\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    
    echo "Recargando PJSIP...\n";
    shell_exec("/usr/sbin/asterisk -rx 'module reload res_pjsip.so'");
    shell_exec("/usr/sbin/asterisk -rx 'dialplan reload'");
    
    echo "Sincronización total completada.\n";
} catch (Exception $e) { echo "Error: ".$e->getMessage(); }
