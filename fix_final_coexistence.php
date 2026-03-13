<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    
    $webrtc_ids = ['2001', '2002', '2003', '2004', '2005', '2006', '2007'];
    $standard_ids = ['1001', '1002', '1004', '1005'];

    echo "1. Configurando Base de Datos (Serie 2000 - WebRTC)...\n";
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
            'allow' => 'alaw,ulaw,opus',
            'dtls_setup' => 'actpass',
            'dtls_verify' => 'fingerprint',
            'rtp_symmetric' => 'yes',
            'rtp_keepalive' => '5',
            'rewrite_contact' => 'yes',
            'force_rport' => 'yes',
            'devicetype' => 'webrtc',
            'context' => 'from-internal',
            'qualify' => 'yes',
            'max_contacts' => '5'
        ];
        $ins = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
        foreach ($settings as $kw => $val) { $ins->execute([$ext, $kw, $val]); }
    }

    echo "2. Configurando Base de Datos (Serie 1000 - Estándar UDP)...\n";
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
            'qualify' => 'yes',
            'max_contacts' => '5'
        ];
        $ins = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
        foreach ($settings as $kw => $val) { $ins->execute([$ext, $kw, $val]); }
    }

    echo "3. Vaciando pjsip_custom_post.conf (para evitar conflictos de herencia)...\n";
    file_put_contents('/etc/asterisk/pjsip_custom_post.conf', "; Archivo vaciado por script de estabilizacion\n");

    echo "4. Generando configuracion y deduplicando...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');
    
    // Deduplicacion final del archivo para evitar el error "Could not create object"
    $file = '/etc/asterisk/pjsip_additional.conf';
    if (file_exists($file)) {
        $lines = file($file); $newLines = []; $seen = []; $currentSection = "";
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === "" || strpos($trimmed, ';') === 0) { $newLines[] = $line; continue; }
            if (preg_match('/^\[(.*)\]/', $trimmed, $m)) { $currentSection = $m[1]; $seen = []; $newLines[] = $line; }
            elseif (strpos($trimmed, '=') !== false) {
                list($key, $val) = explode('=', $trimmed, 2);
                $key = trim($key);
                if (isset($seen[$key])) continue;
                $seen[$key] = true; $newLines[] = $line;
            } else { $newLines[] = $line; }
        }
        file_put_contents($file, implode("", $newLines));
    }

    echo "5. Reinicio Limpio de PJSIP...\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

    echo "\n--- RESULTADO FINAL ---\n";
    echo "2005 Status: " . shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 2005" | grep -E "allow|transport"');
    echo "1001 Status: " . shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 1001" | grep -E "allow|transport"');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
