<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $stmt = $db->query("SELECT id, keyword, data FROM sip WHERE id LIKE '2004%' OR id LIKE '2005%' ORDER BY id, keyword");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "'{$row['id']}' | '{$row['keyword']}' | '{$row['data']}'\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
