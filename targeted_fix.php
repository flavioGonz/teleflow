<?php
/**
 * TARGETED PJSIP FIX FOR 1002 and 2004
 */

$file = '/etc/asterisk/pjsip_additional.conf';
if (!file_exists($file)) die("File not found\n");

$content = file_get_contents($file);

// 1002 Fix
if (preg_match('/\[1002\][^\[]*/', $content, $m)) {
    $section = $m[0];
    $newSection = $section;
    $newSection = preg_replace('/auth=.*/', 'auth=auth1002', $newSection);
    $newSection = preg_replace('/aors=.*/', 'aors=1002-aor', $newSection);
    $content = str_replace($section, $newSection, $content);
}

// 2004 Fix
if (preg_match('/\[2004\][^\[]*/', $content, $m)) {
    $section = $m[0];
    $newSection = $section;
    $newSection = preg_replace('/auth=.*/', 'auth=auth2004', $newSection);
    $newSection = preg_replace('/aors=.*/', 'aors=2004-aor', $newSection);
    $content = str_replace($section, $newSection, $content);
}

file_put_contents($file, $content);
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
echo "Targeted fix applied for 1002 and 2004.\n";
?>
