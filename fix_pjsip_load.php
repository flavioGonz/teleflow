<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $exts = ['1001', '2004', '2005', '2006', '2007'];

    foreach ($exts as $ext) {
        echo "Fixing $ext (Removing keyword 'type' from DB)...\n";
        $db->prepare("DELETE FROM sip WHERE id = ? AND keyword = 'type'")->execute([$ext]);
    }

    echo "Running retrieve_conf...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
    
    echo "Verifying endpoint 2005 status:\n";
    echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 2005"');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
