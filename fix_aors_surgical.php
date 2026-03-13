<?php
/**
 * SURGICAL PJSIP AOR FIX
 * This script addresses the "AOR '' not found" error by:
 * 1. Renaming clashing AOR/AUTH sections in pjsip_additional.conf.
 * 2. Ensuring the 'aors' and 'auth' lines in the endpoint section are present and correct.
 */

// Hardcoded credentials for surgical fix execution on the server
$DB_USER = 'root';
$DB_PASS = 'Sildan.1329';
$DB_HOST = 'localhost';

try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', $DB_USER, $DB_PASS);
    echo "Database connected. Running retrieve_conf first...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');
} catch (Exception $e) {
    echo "Warning: Database connection failed. Proceeding with file patch anyway.\n";
}

$file = '/etc/asterisk/pjsip_additional.conf';
if (!file_exists($file)) {
    die("Error: $file does not exist. Run this script on the Asterisk server.\n");
}

echo "Reading $file...\n";
$content = file_get_contents($file);
$sections = preg_split('/^\[/m', $content);
$newContent = $sections[0]; // Header comments

$endpointNames = [];
$processedSections = [];

// Step 1: Identify all endpoints
for ($i = 1; $i < count($sections); $i++) {
    $section = "[" . $sections[$i];
    if (preg_match('/^\[(.*)\]/', $section, $m)) {
        $name = $m[1];
        if (preg_match('/type=endpoint/i', $section)) {
            $endpointNames[] = $name;
        }
        $processedSections[] = ['name' => $name, 'content' => $section];
    }
}

$renamedRef = [];

// Step 2: Rename AORs and Auths that clash with endpoint names
foreach ($processedSections as &$ps) {
    if (preg_match('/type=aor/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
        $newName = "{$ps['name']}-aor";
        $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
        $renamedRef[$ps['name']]['aor'] = $newName;
        echo "Renaming clashing AOR [{$ps['name']}] to [$newName]\n";
    }
    if (preg_match('/type=auth/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
        $newName = "{$ps['name']}-auth";
        $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
        $renamedRef[$ps['name']]['auth'] = $newName;
        echo "Renaming clashing AUTH [{$ps['name']}] to [$newName]\n";
    }
}

// Step 3: Update and ENSURE references in Endpoints
foreach ($processedSections as &$ps) {
    if (preg_match('/type=endpoint/i', $ps['content'])) {
        $name = $ps['name'];
        
        // Fix AUTH reference
        $targetAuth = isset($renamedRef[$name]['auth']) ? $renamedRef[$name]['auth'] : $name;
        if (preg_match('/^auth=.*/m', $ps['content'])) {
            $ps['content'] = preg_replace('/^auth=.*/m', "auth=$targetAuth", $ps['content']);
        } else {
            // Append auth= line if missing
            $ps['content'] = rtrim($ps['content']) . "\nauth=$targetAuth\n";
        }

        // Fix AORS reference (CRITICAL)
        $targetAor = isset($renamedRef[$name]['aor']) ? $renamedRef[$name]['aor'] : $name;
        if (preg_match('/^aors=.*/m', $ps['content'])) {
            $ps['content'] = preg_replace('/^aors=.*/m', "aors=$targetAor", $ps['content']);
        } else {
            // Append aors= line if missing
            $ps['content'] = rtrim($ps['content']) . "\naors=$targetAor\n";
        }
        
        echo "Fixed endpoint [$name]: auth=$targetAuth, aors=$targetAor\n";
    }
    $newContent .= $ps['content'];
}

// Write back to file
if (file_put_contents($file, $newContent)) {
    echo "\nSuccessfully updated $file\n";
    echo "Reloading Asterisk PJSIP...\n";
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
    echo "Done. Please check registration now.\n";
} else {
    echo "Error: Could not write to $file. Check permissions.\n";
}
?>
