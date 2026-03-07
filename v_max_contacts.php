<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $db->query("UPDATE sip SET data='5' WHERE keyword='max_contacts'");
    echo "Updated max_contacts to 5 for all extensions.\n";
    include 'repair_all_exts.php';
} catch (Exception $e) { echo $e->getMessage(); }
