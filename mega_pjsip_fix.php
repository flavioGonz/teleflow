<?php
/**
 * MEGA PJSIP FIX
 * 1. Backups custom files.
 * 2. Clears them to avoid overrides/conflicts.
 * 3. Runs retrieve_conf to get a fresh pjsip_additional.conf.
 * 4. Runs surgical patch on pjsip_additional.conf.
 * 5. Reloads Asterisk.
 */

$DB_USER = 'root';
$DB_PASS = 'Sildan.1329';

$custom_file = '/etc/asterisk/pjsip_custom.conf';
$post_file = '/etc/asterisk/pjsip_custom_post.conf';

echo "1. Backing up custom files...\n";
if (file_exists($custom_file)) copy($custom_file, $custom_file . '.bak');
if (file_exists($post_file)) copy($post_file, $post_file . '.bak');

echo "2. Clearing custom files to avoid duplicates...\n";
file_put_contents($custom_file, "; Limpiado para evitar duplicados\n");
file_put_contents($post_file, "; Limpiado para evitar duplicados\n");

echo "3. Generating fresh config with retrieve_conf...\n";
shell_exec('/var/lib/asterisk/bin/retrieve_conf');

echo "4. Applying surgical patch to pjsip_additional.conf...\n";
$file = '/etc/asterisk/pjsip_additional.conf';
$content = file_get_contents($file);
$sections = preg_split('/^\[/m', $content);
$newContent = $sections[0];

$endpointNames = [];
$processedSections = [];

for ($i = 1; $i < count($sections); $i++) {
    $section = "[" . $sections[$i];
    if (preg_match('/^\[(.*)\]/', $section, $m)) {
        $name = $m[1];
        if (preg_match('/type=endpoint/i', $section)) $endpointNames[] = $name;
        $processedSections[] = ['name' => $name, 'content' => $section];
    }
}

$renamedRef = [];
foreach ($processedSections as &$ps) {
    if (preg_match('/type=aor/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
        $newName = "{$ps['name']}-aor";
        $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
        $renamedRef[$ps['name']]['aor'] = $newName;
    }
    if (preg_match('/type=auth/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
        $newName = "{$ps['name']}-auth";
        $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
        $renamedRef[$ps['name']]['auth'] = $newName;
    }
}

foreach ($processedSections as &$ps) {
    if (preg_match('/type=endpoint/i', $ps['content'])) {
        $name = $ps['name'];
        $targetAuth = isset($renamedRef[$name]['auth']) ? $renamedRef[$name]['auth'] : $name;
        $targetAor = isset($renamedRef[$name]['aor']) ? $renamedRef[$name]['aor'] : $name;
        
        if (preg_match('/^auth=.*/m', $ps['content'])) $ps['content'] = preg_replace('/^auth=.*/m', "auth=$targetAuth", $ps['content']);
        else $ps['content'] = rtrim($ps['content']) . "\nauth=$targetAuth\n";
        
        if (preg_match('/^aors=.*/m', $ps['content'])) $ps['content'] = preg_replace('/^aors=.*/m', "aors=$targetAor", $ps['content']);
        else $ps['content'] = rtrim($ps['content']) . "\naors=$targetAor\n";
    }
    $newContent .= $ps['content'];
}
file_put_contents($file, $newContent);

echo "5. Reloading Asterisk...\n";
shell_exec('/usr/sbin/asterisk -rx "core reload"');
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

echo "\nDONE. Please try to register 1002 and 2004 again.\n";
echo "--- Final State Check ---\n";
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep -E "1002|2004"');
?>
