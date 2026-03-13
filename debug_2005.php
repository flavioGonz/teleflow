<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $ext = '2005';
    $stmt = $db->prepare("SELECT keyword, data FROM sip WHERE id = ?");
    $stmt->execute([$ext]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['keyword']} = {$row['data']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
