<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=asterisk", "asteriskuser", "Sildan.1329");
    
    // Setting SIP Channel Driver to PJSIP so WebSockets on chan_sip is disabled
    $stmt = $db->prepare("UPDATE freepbx_settings SET value='PJSIP' WHERE keyword='SIPCHANNELHW'");
    $stmt->execute();
    
    // Avoid PJSIP incorrectly identifying softphones behind NAT sharing same IP
    $stmt3 = $db->prepare("UPDATE sip SET data='username,auth_username' WHERE keyword='identify_by'");
    $stmt3->execute();
    echo "Identified_By fix applied to " . $stmt3->rowCount() . " extensions.\n";
    
    echo "Reloading FreePBX config...\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'core reload'");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
