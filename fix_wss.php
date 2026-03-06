<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=asterisk", "asteriskuser", "Sildan.1329");
    // Setting SIP Channel Driver to PJSIP so WebSockets on chan_sip is disabled
    $stmt = $db->prepare("UPDATE freepbx_settings SET value='PJSIP' WHERE keyword='SIPCHANNELHW'");
    $stmt->execute();
    echo "SIP channel hardware set to PJSIP. \n";
    
    // Also disable websocket in advanced settings if there is any explicit flag
    $stmt2 = $db->prepare("UPDATE freepbx_settings SET value='0' WHERE keyword LIKE '%WEB%' AND keyword LIKE '%SIP%'");
    $stmt2->execute();
    
    echo "Reloading FreePBX config...\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module unload chan_sip.so'");
    shell_exec("asterisk -rx 'module load res_pjsip_transport_websocket.so'");
    shell_exec("asterisk -rx 'core reload'");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
