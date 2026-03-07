<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- SIP TABLE 2005 ---\n";
    $res = $db->query("SELECT keyword, data FROM sip WHERE id='2005'");
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['keyword']} => {$row['data']}\n";
    }

    echo "\n--- DEVICES TABLE 2005 ---\n";
    $res = $db->query("SELECT id, tech, dial FROM devices WHERE id='2005'");
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['id']} | {$row['tech']} | {$row['dial']}\n";
    }

    echo "\n--- ASTDB 2005 ---\n";
    echo shell_exec("/usr/sbin/asterisk -rx 'database show DEVICE/2005'");

    echo "\n--- PJSIP ENDPOINT 2005 ---\n";
    echo shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoint 2005'");

} catch (Exception $e) { echo $e->getMessage(); }
