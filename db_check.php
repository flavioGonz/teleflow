<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $exts = ['2005']; // Check specifically for 2005 first
    foreach ($exts as $ext) {
        echo "--- Extension $ext ---\n";
        $stmt = $db->prepare("SELECT keyword, data FROM sip WHERE id = ?");
        $stmt->execute([$ext]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['keyword']} = {$row['data']}\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
