<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $db->prepare("DELETE FROM sip WHERE id='2004' AND (keyword='deny' OR keyword='permit')")->execute();
    echo "Deny/Permit records deleted.\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    echo "Reload done.\n";
} catch (Exception $e) { echo $e->getMessage(); }

