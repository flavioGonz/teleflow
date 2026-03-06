<?php
include 'config.php';
$exts = ['2002', '2003', '2004'];
$pass = 'Teleflow2024*';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    foreach($exts as $ext) {
        $db->prepare("UPDATE sip SET data=? WHERE id=? AND keyword='secret'")->execute([$pass, $ext]);
    }
    echo "Contraseñas unificadas a $pass\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
} catch (Exception $e) { echo $e->getMessage(); }

