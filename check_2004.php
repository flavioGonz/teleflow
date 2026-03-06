<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $res = $db->query("SELECT keyword, data FROM sip WHERE id='2004'");
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['keyword']} => {$row['data']}\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }

