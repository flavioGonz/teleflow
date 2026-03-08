<?php
$line = "PJSIP/1002-00000222          ivr-node-node-1773003 s                1 Up      WaitExten    5                1002             00:00:10";
if (preg_match("/^((?:PJSIP|SIP)\/[\w\-]+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w+)\s+(\S+)\s+(.*?)\s+(\S+)\s+(\d+:\d{2}:\d{2}|\d+:\d{2})/", $line, $m)) {
    print_r($m);
} else {
    echo "No match\n";
}
