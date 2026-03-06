<?php
$exts = ['2004', '2002', '2003'];
$cert = '/etc/asterisk/keys/asterisk.pem';
$pass = 'Teleflow2024';

try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');

    // 1. Ensure cert bundle exists or use httpd certs if missing
    if (!file_exists($cert)) {
        echo "Generando certificado Asterisk desde Apache...\n";
        shell_exec("for A in `grep '^SSLCert' /etc/httpd/conf.d/ssl.conf | grep -v chain | awk '{print $2}'`; do cat \$A >> $cert; done");
        shell_exec("chown asterisk.asterisk $cert");
    }

    // 2. Global RTP settings
    file_put_contents('/etc/asterisk/rtp_custom.conf', "icesupport=true\nstunaddr=stun.l.google.com:19302\n");

    // 3. Update extensions with guide specific flags + explicit DTLS certs
    foreach($exts as $ext) {
        $flags = [
            'webrtc' => 'yes',
            'dtls_cert_file' => $cert,
            'dtls_setup' => 'actpass',
            'dtls_verify' => 'fingerprint',
            'media_encryption' => 'dtls',
            'rtcp_mux' => 'yes',
            'ice_support' => 'yes',
            'use_avpf' => 'yes',
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'secret' => $pass
        ];
        foreach($flags as $k => $v) {
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE data=VALUES(data)")->execute([$ext, $k, $v]);
        }
    }

    echo "Configuración aplicada según guía oficial para: " . implode(', ', $exts) . "\n";
    
    // Execute reload sequence
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    shell_exec("asterisk -rx 'module reload res_rtp_asterisk.so'");
    echo "Asterisk recargado con éxito.\n";

} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
