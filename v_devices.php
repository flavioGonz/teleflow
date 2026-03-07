<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- DEVICES TABLE 2005 ---\n";
    $stmt = $db->query("SELECT * FROM devices WHERE id='2005'");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach($row as $k => $v) echo "$k => $v\n";
    }

} catch (Exception $e) { echo $e->getMessage(); }
