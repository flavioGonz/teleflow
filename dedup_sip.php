<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $exts = ['1001', '2004', '2005', '2006', '2007'];

    foreach ($exts as $ext) {
        echo "Deduplicating $ext...\n";
        // Get all keywords
        $stmt = $db->prepare("SELECT keyword, data, flags FROM sip WHERE id = ?");
        $stmt->execute([$ext]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $seen = [];
        $db->prepare("DELETE FROM sip WHERE id = ?")->execute([$ext]);
        
        foreach ($rows as $row) {
            if (isset($seen[$row['keyword']])) continue;
            $seen[$row['keyword']] = true;
            $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, ?)")
               ->execute([$ext, $row['keyword'], $row['data'], $row['flags']]);
        }
    }
    
    // Create 2099 properly in DB if it doesn't exist to make it a first-class citizen of Issabel
    // Actually, creating it in DB is complex. I'll just fix the 2005 first.

    echo "Running retrieve_conf...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
    echo "DEDUPLICATION_DONE\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
