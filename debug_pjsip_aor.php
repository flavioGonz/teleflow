<?php
$file = '/etc/asterisk/pjsip_additional.conf';
if (file_exists($file)) {
    echo "--- Content of $file (first 500 lines) ---\n";
    $content = file_get_contents($file);
    echo substr($content, 0, 10000); // Read a good chunk to see the sections
} else {
    echo "File $file does not exist.\n";
}

echo "\n--- PJSIP Endpoints Status ---\n";
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints"');

echo "\n--- PJSIP AORs Status ---\n";
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show aors"');
?>
