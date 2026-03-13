<?php
$out = shell_exec("asterisk -rx 'pjsip show endpoints'");
// New proposed regex: optional slash and optional status name
// Format: Endpoint:  EXT[/CID]  STATUS  N of N
preg_match_all('/Endpoint:\s+([\w\d]+)(?:\/.*?)?\s+(.*?)\s+(\d+)\s+of/i', $out, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " matches:\n";
foreach($matches as $m) {
    echo "Ext: {$m[1]}, Status: " . trim($m[2]) . ", Count: {$m[3]}\n";
}
