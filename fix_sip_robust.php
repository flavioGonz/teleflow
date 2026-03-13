<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'Sildan.1329');
    
    $webrtc_ids = ['2001', '2002', '2003', '2004', '2005', '2006', '2007'];
    $standard_ids = ['1001', '1002', '1004', '1005'];

    echo "1. Harmonizing DB...\n";
    foreach (array_merge($webrtc_ids, $standard_ids) as $ext) {
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

    echo "3. Patching pjsip_additional.conf (Robust Pass)...\n";
    $file = '/etc/asterisk/pjsip_additional.conf';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $sections = preg_split('/^\[/m', $content);
        $newContent = $sections[0]; // Header comments
        
        $renamedAors = [];
        $endpointNames = [];

        // First pass: identify types
        $processedSections = [];
        for ($i = 1; $i < count($sections); $i++) {
            $section = "[" . $sections[$i];
            preg_match('/^\[(.*)\]/', $section, $m);
            $name = $m[1];
            preg_match('/type=(.*)/', $section, $tm);
            $type = isset($tm[1]) ? trim($tm[1]) : "";
            
            if ($type == 'endpoint') {
                $endpointNames[] = $name;
            }
            $processedSections[] = ['name' => $name, 'type' => $type, 'content' => $section];
        }

        // Second pass: rename clashes
        foreach ($processedSections as &$ps) {
            if (($ps['type'] == 'aor' || $ps['type'] == 'auth') && in_array($ps['name'], $endpointNames)) {
                $newName = "{$ps['name']}-{$ps['type']}";
                $ps['content'] = preg_replace('/^\[(.*)\]/', "[$newName]", $ps['content']);
                $renamedAors[$ps['name']][$ps['type']] = $newName;
                echo "Renaming duplicate section [{$ps['name']}] type={$ps['type']} to [{$newName}]\n";
            }
        }

        // Third pass: update references in endpoints
        foreach ($processedSections as &$ps) {
            if ($ps['type'] == 'endpoint') {
                foreach (['auth', 'aors'] as $key) {
                    preg_match("/^$key=(.*)/m", $ps['content'], $rm);
                    if (isset($rm[1])) {
                        $refName = trim($rm[1]);
                        $refType = ($key == 'aors') ? 'aor' : 'auth';
                        if (isset($renamedAors[$refName][$refType])) {
                            $ps['content'] = preg_replace("/^$key=.*/m", "$key={$renamedAors[$refName][$refType]}", $ps['content']);
                            echo "Updating reference in endpoint [{$ps['name']}]: $key={$renamedAors[$refName][$refType]}\n";
                        }
                    }
                }
            }
            $newContent .= $ps['content'];
        }
        
        file_put_contents($file, $newContent);
    }

    echo "4. Reloading Asterisk...\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

    echo "--- VERIFICATION ---\n";
    echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep Endpoint');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
