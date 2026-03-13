<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    
    // Lista de extensiones según su tipo
    $webrtc_ids = ['2001', '2002', '2003', '2004', '2005', '2006', '2007'];
    $standard_ids = ['1001', '1002', '1004', '1005'];

    echo "1. Limpiando y configurando Serie 2000 (WebRTC)...\n";
    foreach ($webrtc_ids as $ext) {
        $db->prepare("DELETE FROM sip WHERE id=?")->execute([$ext]);
        $settings = [
            'account' => $ext,
            'secret' => 'teleflow123',
            'transport' => 'transport-wss',
            'webrtc' => 'yes',
            'use_avpf' => 'yes',
            'media_encryption' => 'dtls',
            'ice_support' => 'yes',
            'disallow' => 'all',
            'allow' => 'alaw,ulaw,opus,vp8,h264',
            'dtls_setup' => 'actpass',
            'dtls_verify' => 'fingerprint',
            'rtp_symmetric' => 'yes',
            'rtp_keepalive' => '5',
            'rewrite_contact' => 'yes',
            'force_rport' => 'yes',
            'devicetype' => 'webrtc',
            'context' => 'from-internal',
            'qualify' => 'yes'
        ];
        $ins = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
        foreach ($settings as $kw => $val) { $ins->execute([$ext, $kw, $val]); }
    }

    echo "2. Limpiando y configurando Serie 1000 (Estándar PJSIP)...\n";
    foreach ($standard_ids as $ext) {
        $db->prepare("DELETE FROM sip WHERE id=?")->execute([$ext]);
        $settings = [
            'account' => $ext,
            'secret' => 'teleflow123',
            'transport' => 'transport-udp',
            'webrtc' => 'no',
            'use_avpf' => 'no',
            'media_encryption' => 'no',
            'ice_support' => 'no',
            'disallow' => 'all',
            'allow' => 'alaw,ulaw,gsm',
            'rtp_symmetric' => 'yes',
            'rewrite_contact' => 'yes',
            'force_rport' => 'yes',
            'devicetype' => 'pjsip',
            'context' => 'from-internal',
            'qualify' => 'yes'
        ];
        $ins = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
        foreach ($settings as $kw => $val) { $ins->execute([$ext, $kw, $val]); }
    }

    echo "3. Regenerando archivos de configuración...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');

    echo "4. Limpiando pjsip_custom_post.conf (Surgical Overrides)...\n";
    $overrides = "; Overrides especificos para estabilidad WebRTC\n";
    foreach ($webrtc_ids as $ext) {
        $overrides .= "[$ext](+)\n";
        $overrides .= "rtp_keepalive=5\n";
        $overrides .= "rtp_symmetric=yes\n";
        $overrides .= "ice_support=yes\n";
        $overrides .= "rewrite_contact=yes\n";
        $overrides .= "force_rport=yes\n\n";
    }
    file_put_contents('/etc/asterisk/pjsip_custom_post.conf', $overrides);

    echo "5. Recargando Asterisk...\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

    echo "--- VERIFICACION FINAL ---\n";
    echo "ID 2004 (WebRTC): " . shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 2004" | grep -E "allow|transport"');
    echo "ID 1001 (Estándar): " . shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 1001" | grep -E "allow|transport"');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
