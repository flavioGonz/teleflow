<?php
include 'config.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    
    echo "--- Tables related to extensions ---\n";
    $tables = ['devices', 'users', 'sip', 'ps_auths', 'ps_endpoints'];
    foreach ($tables as $t) {
        $stmt = $db->query("SHOW TABLES LIKE '$t'");
        if ($stmt->rowCount() > 0) {
            echo "Table $t exists.\n";
            if ($t == 'devices') {
                $stmt = $db->query("SELECT id, tech, description FROM devices LIMIT 5");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    print_r($row);
                }
            }
        } else {
            echo "Table $t does NOT exist.\n";
        }
    }

} catch (Exception $e) { 
    echo "Error: " . $e->getMessage() . "\n"; 
}
?>
