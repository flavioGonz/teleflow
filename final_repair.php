<?php
include 'api/index.php';
$db = mysql_pbx();

$exts = ['2004', '2005'];

foreach ($exts as $ext) {
    echo "Reparando extension $ext...\n";
    // 1. Asegurar que webrtc esté activado en ps_endpoints
    $db->query("UPDATE ps_endpoints SET webrtc='yes', dtls_verify='no', dtls_setup='actpass', use_avpf='yes', ice_support='yes', media_encryption='dtls', rtp_symmetric='yes', force_rport='yes', rewrite_contact='yes', direct_media='no' WHERE id='$ext'");
    
    // 2. Asegurar max_contacts en ps_aors
    $db->query("UPDATE ps_aors SET max_contacts=5, remove_existing=yes WHERE id='$ext'");
    
    // 3. Quitar cualquier realm extraño que pueda causar 403
    $db->query("UPDATE ps_auths SET realm='201.217.134.124' WHERE id='$ext'");
}

reload_dialplan();
echo "Reparación finalizada y Asterisk recargado.\n";
