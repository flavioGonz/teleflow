<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    $exts = ['2004', '2005'];
    $pass = 'teleflow123';
    
    foreach($exts as $ext) {
        // Update secret
        $db->prepare("UPDATE sip SET data=? WHERE id=? AND keyword='secret'")->execute([$pass, $ext]);
        // Ensure realm is 'asterisk' (default) or the IP
        $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, 'realm', 'asterisk', 0) ON DUPLICATE KEY UPDATE data='asterisk'")->execute([$ext]);
        
        echo "[OK] Password and Realm updated for $ext.\n";
    }
    
    // Run repair script to apply changes
    echo "Ejecutando reparación...\n";
    include 'repair_all_exts.php';

} catch (Exception $e) { echo $e->getMessage(); }
