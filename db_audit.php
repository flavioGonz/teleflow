<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    echo "--- SIP TABLE OVERVIEW ---\n";
    foreach($db->query("SELECT DISTINCT id FROM sip ORDER BY id") as $r) {
        $id = $r['id'];
        $transport = $db->query("SELECT data FROM sip WHERE id=$id AND keyword='transport'")->fetchColumn();
        $encryption = $db->query("SELECT data FROM sip WHERE id=$id AND keyword='media_encryption'")->fetchColumn();
        $allow = $db->query("SELECT data FROM sip WHERE id=$id AND keyword='allow'")->fetchColumn();
        echo "$id | Transport: " . ($transport ?: "N/A") . " | MediaEnc: " . ($encryption ?: "N/A") . " | Allow: " . ($allow ?: "N/A") . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
