<?php
$DB_USER = 'root';
$DB_PASS = '';
if (file_exists('/etc/issabelpbx.conf')) {
    $conf = parse_ini_file('/etc/issabelpbx.conf');
    $DB_USER = $conf['AMPDBUSER'] ?? 'root';
    $DB_PASS = $conf['AMPDBPASS'] ?? '';
}

try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    $stmt = $db->prepare("DELETE FROM sip WHERE keyword='transport' AND data='transport-wss'");
    $stmt->execute();
    echo "Removed explicit transport-wss from " . $stmt->rowCount() . " devices.\n";
    shell_exec("/var/lib/asterisk/bin/retrieve_conf");
    shell_exec("asterisk -rx 'core reload'");
    echo "Asterisk config regenerated and reloaded.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
