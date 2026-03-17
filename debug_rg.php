<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    $s = $db->query('SELECT grpnum, grplist FROM ringgroups LIMIT 5');
    foreach($s as $r) {
        echo "GRP: " . $r['grpnum'] . " | LIST: " . str_replace("\n", "\\n", $r['grplist']) . "\n";
    }
} catch(Exception $e) {
    echo $e->getMessage();
}
