<?php
include 'config.php';
$id = $_GET['id'] ?? $argv[1] ?? '2004';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- ENTRIES FOR $id ---\n";
    $stmt = $db->prepare("SELECT * FROM sip WHERE id=? ORDER BY keyword");
    $stmt->execute([$id]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "[{$row['id']}] {$row['keyword']} => {$row['data']}\n";
    }

} catch (Exception $e) { echo $e->getMessage(); }
