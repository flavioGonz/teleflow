<?php
$exts = ['2002', '2003', '2004'];
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    foreach($exts as $ext) {
        $flags = [
            'webrtc' => 'yes',
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'use_avpf' => 'yes',
            'ice_support' => 'yes',
            'media_encryption' => 'dtls',
            'remove_existing' => 'yes'
        ];
        foreach($flags as $k => $v) {
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, $k, $v]);
        }
        echo "Flags actualizados para $ext.\n";
    }
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    echo "Sincronización con Asterisk completada.\n";
} catch (Exception $e) { echo "Error: ".$e->getMessage(); }
