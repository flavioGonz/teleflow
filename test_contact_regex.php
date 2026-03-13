<?php
$out = shell_exec("asterisk -rx 'pjsip show contacts'");
// Contact:  2004/sip:vuk3d7i1@186.52.172.28:57078;transpor 214
preg_match_all('/Contact:\s+([\w\d]+)\/sip:.*?@([\d\.]+):(\d+)/i', $out, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " contacts:\n";
foreach($matches as $m) {
    echo "Ext: {$m[1]}, IP: {$m[2]}, Port: {$m[3]}\n";
}
