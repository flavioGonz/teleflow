<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    echo "Cleaning 'type' keyword from ALL extensions in DB...\n";
    $db->prepare("DELETE FROM sip WHERE keyword = 'type'")->execute();
    
    echo "Running retrieve_conf...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
    
    echo "Checking endpoints again...\n";
    echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep Endpoint');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
