<?php
$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
$res = $db->query('SELECT * FROM sip WHERE id="2004"');
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['keyword']} => {$row['data']}\n";
}
