<?php
$db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
foreach($db->query('SELECT id, keyword, data FROM sip WHERE id IN (2004, 2005, 2007) ORDER BY id, keyword') as $r) {
    echo "{$r['id']} | {$r['keyword']} = {$r['data']}\n";
}
