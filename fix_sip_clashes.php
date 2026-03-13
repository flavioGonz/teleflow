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

    echo "3. Patching pjsip_additional.conf to resolve name clashes...\n";
    $file = '/etc/asterisk/pjsip_additional.conf';
    if (file_exists($file)) {
        $lines = file($file);
        $newLines = [];
        $currentSection = "";
        $currentType = "";
        
        // Strategy: We will detect sections and if it's an aor or auth, we rename the header.
        // And we will keep track of names to update references in endpoints.
        
        $content = file_get_contents($file);
        
        // First pass: identify all endpoint names
        preg_match_all('/^\[(.*)\]\s*type=endpoint/m', $content, $endpoints);
        $endpointNames = $endpoints[1];
        
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\[(.*)\]/', $trimmed, $m)) {
                $currentSection = $m[1];
                $newLines[] = $line;
            } elseif (preg_match('/^type=(.*)/', $trimmed, $m)) {
                $currentType = $m[1];
                // If this is an aor or auth with the SAME name as an endpoint, we have a problem in the header we just added
                if (($currentType == 'aor' || $currentType == 'auth') && in_array($currentSection, $endpointNames)) {
                    // Update the LAST line added (the header)
                    $lastIdx = count($newLines) - 2; // -1 is this line, -2 is the header
                    // Actually, we might have had multiple lines between header and type=...
                    // Let's find the header
                    for ($i = count($newLines) - 1; $i >= 0; $i--) {
                        if (trim($newLines[$i]) == "[$currentSection]") {
                            $newName = "{$currentSection}-{$currentType}";
                            $newLines[$i] = "[$newName]\n";
                            echo "Renamed duplicate section [$currentSection] to [$newName]\n";
                            break;
                        }
                    }
                }
                $newLines[] = $line;
            } elseif (preg_match('/^(auth|aors|outbound_auth)=(.*)/', $trimmed, $m)) {
                $key = $m[1];
                $val = $m[2];
                if (in_array($val, $endpointNames)) {
                    $type = ($key == 'aors') ? 'aor' : 'auth';
                    $newVal = "{$val}-{$type}";
                    $newLines[] = "{$key}={$newVal}\n";
                    echo "Updated reference in endpoint: {$key}={$newVal}\n";
                } else {
                    $newLines[] = $line;
                }
            } else {
                $newLines[] = $line;
            }
        }
        file_put_contents($file, implode("\n", $newLines));
    }

    echo "4. Reloading Asterisk...\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');

    echo "--- VERIFICATION ---\n";
    echo shell_exec('/usr/sbin/asterisk -rx "pjsip show endpoints" | grep Endpoint');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
