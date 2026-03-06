<?php
$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
$res = $db->query('SELECT * FROM devices WHERE id="2004"');
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
