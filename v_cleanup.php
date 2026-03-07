<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    // Remove invalid realm from endpoint section (sip table)
    $db->query("DELETE FROM sip WHERE keyword='realm'");
    echo "Removed invalid realm keywords from sip table.\n";
    
    // Run repair script
    include 'repair_all_exts.php';

} catch (Exception $e) { echo $e->getMessage(); }
