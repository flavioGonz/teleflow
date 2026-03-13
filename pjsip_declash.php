<?php
/**
 * FINAL PJSIP DE-CLASH SCRIPT
 * Renames AOR and AUTH sections to include -aor and -auth suffixes
 * to avoid clashing with the endpoint section (which keeps the extension ID).
 */

$file = '/etc/asterisk/pjsip_additional.conf';
if (!file_exists($file)) die("File not found\n");

echo "1. Regenerating with retrieve_conf...\n";
shell_exec('/var/lib/asterisk/bin/retrieve_conf');

echo "2. Processing $file...\n";
$content = file_get_contents($file);
$sections = preg_split('/^\[/m', $content);
$newContent = $sections[0];

$endpointNames = [];
$processed = [];

// Pass 1: find all endpoints and their IDs
for ($i = 1; $i < count($sections); $i++) {
    $section = "[" . $sections[$i];
    if (preg_match('/^\[(.*)\]/', $section, $m)) {
        $name = $m[1];
        $type = "";
        if (preg_match('/type=endpoint/i', $section)) {
            $type = 'endpoint';
            $endpointNames[] = $name;
        } elseif (preg_match('/type=aor/i', $section)) {
            $type = 'aor';
        } elseif (preg_match('/type=auth/i', $section)) {
            $type = 'auth';
        }
        $processed[] = ['name' => $name, 'type' => $type, 'content' => $section];
    }
}

// Pass 2: Rename clashing AORs and Auths, and fix references
foreach ($processed as &$p) {
    if ($p['type'] == 'aor' && in_array($p['name'], $endpointNames)) {
        $oldName = $p['name'];
        $newName = "$oldName-aor";
        $p['content'] = preg_replace('/^\[.*\]/m', "[$newName]", $p['content']);
        echo "Renaming AOR [$oldName] -> [$newName]\n";
    }
    if ($p['type'] == 'auth') {
        // Issabel uses [authID] or [ID]. We'll standardize to [ID-auth]
        $oldName = $p['name'];
        // Detect if it's a numeric ID or auth+numeric
        $id = preg_replace('/^auth/', '', $oldName);
        if (in_array($id, $endpointNames)) {
            $newName = "$id-auth";
            $p['content'] = preg_replace('/^\[.*\]/m', "[$newName]", $p['content']);
            echo "Renaming AUTH [$oldName] -> [$newName]\n";
        }
    }
}

// Pass 3: Update references in endpoints
foreach ($processed as &$p) {
    if ($p['type'] == 'endpoint') {
        $id = $p['name'];
        $p['content'] = preg_replace('/^aors=.*/m', "aors=$id-aor", $p['content']);
        $p['content'] = preg_replace('/^auth=.*/m', "auth=$id-auth", $p['content']);
    }
    $newContent .= $p['content'];
}

file_put_contents($file, $newContent);

echo "3. Reloading Asterisk...\n";
shell_exec('/usr/sbin/asterisk -rx "core reload"');
shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

echo "\nVerification:\n";
echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep -E "1002|2004"');
?>
