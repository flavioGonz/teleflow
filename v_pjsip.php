<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $stmt = $db->query("SHOW TABLES LIKE 'ps_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "PJSIP TABLES: " . implode(', ', $tables) . "\n";
    
    if(in_array('ps_endpoints', $tables)) {
        $stmt = $db->query("SELECT id, transport, rewrite_contact, rtp_symmetric FROM ps_endpoints WHERE id LIKE '2004%' OR id LIKE '2005%'");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "PS_ENDPOINT: " . json_encode($row) . "\n";
        }
    }
} catch (Exception $e) { echo $e->getMessage(); }
