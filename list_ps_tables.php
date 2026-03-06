<?php
$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
$res = $db->query("SHOW TABLES LIKE 'ps_%'");
while($row = $res->fetch(PDO::FETCH_NUM)) {
    echo "Table: {$row[0]}\n";
}
