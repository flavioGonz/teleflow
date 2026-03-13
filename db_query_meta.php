<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    echo "--- USERS TABLE ---\n";
    $res = $db->query("SELECT extension, name, password FROM users WHERE extension IN ('1001', '2004', '2005')");
    while($r=$res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$r['extension']} | {$r['name']} | {$r['password']}\n";
    }
    echo "--- DEVICES TABLE ---\n";
    $res = $db->query("SELECT id, tech, dial FROM devices WHERE id IN ('1001', '2004', '2005')");
    while($r=$res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$r['id']} | {$r['tech']} | {$r['dial']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
