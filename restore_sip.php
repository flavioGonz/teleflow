<?php
$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
$exts = ['1001', '2004', '2005', '2006', '2007'];

foreach ($exts as $ext) {
    echo "Restoring $ext to basic state...\n";
    // Devolvemos a Issabel el control total pero con WebRTC activado
    $db->prepare("DELETE FROM sip WHERE id=? AND keyword NOT IN ('secret','callerid','context','type','host','port','mailbox','dial','account','dtmfmode','disallow','allow','transport','webrtc','use_avpf','media_encryption','dtls_verify','dtls_setup','ice_support','media_use_received_transport','qualify','qualifyfreq','max_contacts','remove_existing','devicetype')")->execute([$ext]);
    
    // Asegurar valores base
    $baseSettings = [
        'transport' => 'transport-wss',
        'webrtc' => 'yes',
        'use_avpf' => 'yes',
        'media_encryption' => 'dtls',
        'ice_support' => 'yes',
        'media_use_received_transport' => 'yes',
        'devicetype' => 'webrtc'
    ];
    
    foreach ($baseSettings as $kw => $val) {
        $db->prepare("DELETE FROM sip WHERE id=? AND keyword=?")->execute([$ext, $kw]);
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)")->execute([$ext, $kw, $val]);
    }
}

echo "Generating config files...\n";
shell_exec('/var/lib/asterisk/bin/retrieve_conf');
shell_exec('/usr/sbin/asterisk -rx "core reload"');
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
echo "RESTORATION_COMPLETE\n";
