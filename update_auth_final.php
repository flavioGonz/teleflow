<?php
$exts = ['2004', '2002', '2003'];
$pass = 'teleflow123';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    foreach($exts as $ext) {
        $db->prepare("UPDATE sip SET data=? WHERE id=? AND keyword='secret'")->execute([$pass, $ext]);
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'realm', 'asterisk', 0) ON DUPLICATE KEY UPDATE data='asterisk'")->execute([$ext]);
    }
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'module reload res_pjsip.so'");
    echo "Asterisk auth updated with 'teleflow123' and realm 'asterisk'.\n";
} catch (Exception $e) { echo $e->getMessage(); }
