$phpCode = @"
\$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
\$exts = ['1001', '2004', '2005', '2006', '2007'];
foreach (\$exts as \$ext) {
    \$db->prepare("DELETE FROM sip WHERE id=? AND keyword IN ('rtp_symmetric','rewrite_contact','force_rport','ice_support','use_avpf','rtcp_mux','media_encryption','webrtc','bundle','dtls_auto_generate_cert','rtp_keepalive')")->execute([\$ext]);
    \$sip_data = [
        'rtp_keepalive' => '5',
        'rtp_symmetric' => 'yes',
        'rewrite_contact' => 'yes',
        'force_rport' => 'yes',
        'ice_support' => 'yes',
        'use_avpf' => 'yes',
        'rtcp_mux' => 'yes',
        'bundle' => 'yes',
        'media_encryption' => 'dtls',
        'webrtc' => 'yes',
        'dtls_auto_generate_cert' => 'yes'
    ];
    \$stmt = \$db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
    foreach (\$sip_data as \$kw => \$val) {
        \$stmt->execute([\$ext, \$kw, \$val]);
    }
}
shell_exec('/var/lib/asterisk/bin/retrieve_conf');
shell_exec('/usr/sbin/asterisk -rx \"core reload\"');
shell_exec('/usr/sbin/asterisk -rx \"module reload res_pjsip.so\"');
echo \"FIX_COMPLETED\n\";
"@
$phpCode = $phpCode.Replace('"', '\"')
ssh issabel-pbx "php -r ""$phpCode"""
