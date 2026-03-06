<?php
include 'config.php';
$db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
$res = $db->query("SHOW TABLES LIKE 'ps_%'");
while($row = $res->fetch(PDO::FETCH_NUM)) {
    echo "Table: {$row[0]}\n";
}

