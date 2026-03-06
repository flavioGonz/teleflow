<?php
include __DIR__.'/api/config.php';
try {
    $db = mysql_pbx();
    $stmt = $db->prepare("DELETE FROM sip WHERE keyword='transport' AND data='transport-wss'");
    $stmt->execute();
    echo "Removed explicit transport-wss from " . $stmt->rowCount() . " devices.\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'core reload'");
    echo "Asterisk config regenerated and reloaded.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
