<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- TABLES LIKE SIP OR PJSIP ---\n";
    foreach($db->query("SHOW TABLES LIKE 'sip%'")->fetchAll(PDO::FETCH_COLUMN) as $t) echo "$t\n";
    foreach($db->query("SHOW TABLES LIKE 'pjsip%'")->fetchAll(PDO::FETCH_COLUMN) as $t) echo "$t\n";

} catch (Exception $e) { echo $e->getMessage(); }
