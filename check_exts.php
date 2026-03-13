<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- SIP Table ---\n";
    $stmt = $db->query("SELECT id, keyword, data FROM sip WHERE keyword='secret'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Ext: {$row['id']} - Pass: {$row['data']}\n";
    }

    echo "\n--- PJSIP Auth Table ---\n";
    // Check if ps_auths table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ps_auths'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT id, password FROM ps_auths");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Auth ID: {$row['id']} - Pass: {$row['password']}\n";
        }
    } else {
        echo "ps_auths table doesn't exist.\n";
    }

} catch (Exception $e) { 
    echo "Error: " . $e->getMessage() . "\n"; 
}
?>
