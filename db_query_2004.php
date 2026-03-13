<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $res = $db->query("SELECT id, keyword, data FROM sip WHERE id='2004'");
    echo "ID | KEYWORD | DATA\n";
    while($r=$res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$r['id']} | {$r['keyword']} | {$r['data']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
