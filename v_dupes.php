<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- CHECKING FOR DUPLICATES FOR 2005 ---\n";
    $stmt = $db->query("SELECT keyword, COUNT(*) as c FROM sip WHERE id='2005' GROUP BY keyword HAVING c > 1");
    $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($dupes) {
        foreach($dupes as $d) echo "Keyword '{$d['keyword']}' has {$d['c']} entries!\n";
    } else {
        echo "No duplicates found in MySQL.\n";
    }

    echo "\n--- ALL ENTRIES FOR 2005 ---\n";
    $stmt = $db->query("SELECT * FROM sip WHERE id='2005' ORDER BY keyword");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "[{$row['id']}] {$row['keyword']} => {$row['data']}\n";
    }

} catch (Exception $e) { echo $e->getMessage(); }
