<?php
$cmd_prefix = "COLUMNS=200 ";
$endpoints = shell_exec($cmd_prefix . "/usr/sbin/asterisk -rx 'pjsip show endpoints' 2>/dev/null");
echo "Raw output length: " . strlen($endpoints) . "\n";

// This is the EXACT regex I put in agents.php
$regex = '/Endpoint:\s+([\w]+)(?:\/.*?)?\s+(.*?)\s+(\d+)\s+of/i';
preg_match_all($regex, $endpoints, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " matches.\n";
foreach($matches as $m) {
    echo "Ext: {$m[1]}, Status: {$m[2]}\n";
}
echo "Sample of raw output:\n" . substr($endpoints, 0, 500) . "\n";
