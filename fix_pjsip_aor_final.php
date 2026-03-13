<?php
/**
 * PJSIP AOR Fix Script
 * Specifically targets the "AOR not found" issue by ensuring AORs have unique names 
 * and are correctly referenced in endpoints.
 */

try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    
    echo "1. Harmonizing DB for all extensions...\n";
    $webrtc_ids = ['2001', '2002', '2003', '2004', '2005', '2006', '2007'];
    $standard_ids = ['1001', '1002', '1004', '1005'];
    $all_ids = array_merge($webrtc_ids, $standard_ids);

    foreach ($all_ids as $ext) {
        $is_webrtc = in_array($ext, $webrtc_ids);
        $db->prepare("DELETE FROM sip WHERE id=?")->execute([$ext]);
        
        $settings = [
            'account' => $ext,
            'secret' => 'teleflow123',
            'context' => 'from-internal',
            'qualify' => 'yes',
            'max_contacts' => '5',
            'rtp_symmetric' => 'yes',
            'rewrite_contact' => 'yes',
            'force_rport' => 'yes'
        ];

        if ($is_webrtc) {
            $settings += [
                'transport' => 'transport-wss',
                'webrtc' => 'yes',
                'use_avpf' => 'yes',
                'media_encryption' => 'dtls',
                'ice_support' => 'yes',
                'dtls_setup' => 'actpass',
                'dtls_verify' => 'fingerprint',
                'rtp_keepalive' => '5',
                'disallow' => 'all',
                'allow' => 'alaw,ulaw,opus',
                'devicetype' => 'webrtc'
            ];
        } else {
            $settings += [
                'transport' => 'transport-udp',
                'webrtc' => 'no',
                'use_avpf' => 'no',
                'media_encryption' => 'no',
                'ice_support' => 'no',
                'disallow' => 'all',
                'allow' => 'alaw,ulaw,gsm',
                'devicetype' => 'pjsip'
            ];
        }

        $ins = $db->prepare("INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)");
        foreach ($settings as $kw => $val) { $ins->execute([$ext, $kw, $val]); }
    }

    echo "2. Generating config via retrieve_conf...\n";
    shell_exec('/var/lib/asterisk/bin/retrieve_conf');

    echo "3. Patching pjsip_additional.conf to resolve section name clashes...\n";
    $file = '/etc/asterisk/pjsip_additional.conf';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $sections = preg_split('/^\[/m', $content);
        $newContent = $sections[0]; 
        
        $renamedAors = [];
        $endpointNames = [];

        // Identify endpoints
        $processedSections = [];
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

        // Rename AORs and Auths that clash with endpoint names
        foreach ($processedSections as &$ps) {
            if (preg_match('/type=aor/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
                $newName = "{$ps['name']}-aor";
                $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
                $ps['newName'] = $newName;
                $ps['type'] = 'aor';
                $renamedAors[$ps['name']]['aor'] = $newName;
                echo "Renaming AOR [{$ps['name']}] to [{$newName}]\n";
            }
            if (preg_match('/type=auth/i', $ps['content']) && in_array($ps['name'], $endpointNames)) {
                $newName = "{$ps['name']}-auth";
                $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
                $ps['newName'] = $newName;
                $ps['type'] = 'auth';
                $renamedAors[$ps['name']]['auth'] = $newName;
                echo "Renaming AUTH [{$ps['name']}] to [{$newName}]\n";
            }
        }

        // Update references in endpoints
        foreach ($processedSections as &$ps) {
            if (preg_match('/type=endpoint/i', $ps['content'])) {
                $originalName = $ps['name'];
                if (isset($renamedAors[$originalName]['auth'])) {
                    $ps['content'] = preg_replace('/^auth=.*/m', "auth={$renamedAors[$originalName]['auth']}", $ps['content']);
                }
                if (isset($renamedAors[$originalName]['aor'])) {
                    $ps['content'] = preg_replace('/^aors=.*/m', "aors={$renamedAors[$originalName]['aor']}", $ps['content']);
                }
            }
            $newContent .= $ps['content'];
        }
        
        file_put_contents($file, $newContent);
        echo "Successfully patched $file\n";
    } else {
        echo "WARNING: $file not found. This might be a production environment restriction.\n";
    }

    echo "4. Reloading Asterisk PJSIP...\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

    echo "--- VERIFICATION FOR 1002 ---\n";
    echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoint 1002"');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
