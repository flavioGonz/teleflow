<?php
$exts = ['1001', '2004', '2005', '2006', '2007'];

echo "1. Cleaning all possible custom files to avoid conflicts...\n";
$customFiles = [
    '/etc/asterisk/pjsip_custom_post.conf',
    '/etc/asterisk/pjsip.endpoint_custom.conf',
    '/etc/asterisk/pjsip.endpoint_custom_post.conf'
];
foreach ($customFiles as $f) {
    file_put_contents($f, "");
}

echo "2. Injecting force-settings into pjsip_custom_post.conf (the cleanest way)...\n";
$customContent = "";
foreach ($exts as $ext) {
    $customContent .= "[$ext](+)\n";
    $customContent .= "transport=transport-wss\n";
    $customContent .= "rtp_symmetric=yes\n";
    $customContent .= "force_rport=yes\n";
    $customContent .= "rewrite_contact=yes\n";
    $customContent .= "rtp_keepalive=5\n";
    $customContent .= "ice_support=yes\n";
    $customContent .= "use_avpf=yes\n";
    $customContent .= "rtcp_mux=yes\n";
    $customContent .= "media_encryption=dtls\n";
    $customContent .= "bundle=yes\n";
    $customContent .= "webrtc=yes\n";
    $customContent .= "dtls_auto_generate_cert=yes\n\n";
}
file_put_contents('/etc/asterisk/pjsip_custom_post.conf', $customContent);

echo "3. Cleaning database strings and forcing transport...\n";
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    foreach ($exts as $ext) {
        // Borramos basura
        $db->prepare("DELETE FROM sip WHERE id=? AND keyword IN ('rtp_symmetric','rtp_keepalive','rewrite_contact','force_rport','transport')")->execute([$ext]);
        // Insertamos limpio
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'transport', 'transport-wss', 0)")->execute([$ext]);
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'rtp_symmetric', 'yes', 0)")->execute([$ext]);
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'rtp_keepalive', '5', 0)")->execute([$ext]);
    }
} catch (Exception $e) { echo "DB bypass: " . $e->getMessage() . "\n"; }

echo "4. Running retrieve_conf...\n";
shell_exec('/var/lib/asterisk/bin/retrieve_conf');

echo "5. Post-generation FIX: Cleaning additional.conf with regex...\n";
// Borrar definiciones de rtp_symmetric y rtp_keepalive que Issabel pone al final de la sección
// Buscamos las líneas que fuerzan el "no" y las matamos
shell_exec("sed -i '/rtp_symmetric=no/d' /etc/asterisk/pjsip_additional.conf");
shell_exec("sed -i '/rtp_keepalive=0/d' /etc/asterisk/pjsip_additional.conf");
shell_exec("sed -i '/force_rport=no/d' /etc/asterisk/pjsip_additional.conf");
shell_exec("sed -i '/rewrite_contact=no/d' /etc/asterisk/pjsip_additional.conf");

echo "6. Final Full Reload...\n";
shell_exec('/usr/sbin/asterisk -rx "core reload"');
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

echo "\n--- VERIFICATION 2005 ---\n";
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 2005" | grep -E "transport|rtp_keepalive|rtp_symmetric|ice_support"');
echo "\nFIX_STABILITY_V6_COMPLETE\n";
