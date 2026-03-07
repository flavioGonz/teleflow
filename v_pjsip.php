<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- CHECKING PJSIP TABLE ---\n";
    $stmt = $db->query("SELECT * FROM pjsip WHERE id='2005'");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach($row as $k => $v) echo "[$k] $v\n";
    }

} catch (Exception $e) { echo $e->getMessage(); }
