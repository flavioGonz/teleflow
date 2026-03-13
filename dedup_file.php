<?php
$file = '/etc/asterisk/pjsip_additional.conf';
$lines = file($file);
$newLines = [];
$seen = [];
$currentSection = "";

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (preg_match('/^\[(.*)\]/', $trimmed, $m)) {
        $currentSection = $m[1];
        $seen = [];
        $newLines[] = $line;
    } elseif (strpos($trimmed, '=') !== false) {
        list($key, $val) = explode('=', $trimmed, 2);
        $key = trim($key);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $newLines[] = $line;
    } else {
        $newLines[] = $line;
    }
}

file_put_contents($file, implode("", $newLines));
echo "File $file deduplicated.\n";
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep Endpoint');
