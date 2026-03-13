<?php
/**
 * TeleFlow - Extension Password Unification Script
 * Sets 'teleflow123' as the password for ALL internal extensions (SIP & PJSIP).
 */

include 'config.php';

$NEW_PASS = 'teleflow123';

try {
    $db = new PDO("mysql:host=$DB_HOST;dbname=asterisk", $DB_USER, $DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Starting Password Unification to: $NEW_PASS ---\n";

    // 1. Get all extensions from the 'devices' table
    $stmt = $db->query("SELECT id, tech FROM devices");
    $extensions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($extensions)) {
        echo "No extensions found in 'devices' table.\n";
    } else {
        foreach ($extensions as $ext) {
            $id = $ext['id'];
            $tech = $ext['tech'];
            echo "Processing extension: $id ($tech)...\n";

            // 2. Update 'sip' table (used for both SIP and as a source for PJSIP config generation in FreePBX/Issabel)
            $updSip = $db->prepare("UPDATE sip SET data = ? WHERE id = ? AND keyword = 'secret'");
            $updSip->execute([$NEW_PASS, $id]);
            echo "  - Updated 'sip' table 'secret'.\n";

            // 3. Update 'ps_auths' table (PJSIP specific)
            // In Issabel/FreePBX, the auth ID in ps_auths is usually the same as the extension ID
            $checkPs = $db->query("SHOW TABLES LIKE 'ps_auths'");
            if ($checkPs->rowCount() > 0) {
                $updPs = $db->prepare("UPDATE ps_auths SET password = ? WHERE id = ?");
                $updPs->execute([$NEW_PASS, $id]);
                if ($updPs->rowCount() > 0) {
                    echo "  - Updated 'ps_auths' table 'password'.\n";
                }
            }
        }
    }

    echo "\n--- Applying changes to Asterisk config files ---\n";
    // This script recreates the .conf files from the DB settings
    $output = shell_exec('/var/lib/asterisk/bin/retrieve_conf 2>&1');
    echo $output . "\n";

    echo "--- Reloading Asterisk ---\n";
    shell_exec('/usr/sbin/asterisk -rx "core reload"');
    shell_exec('/usr/sbin/asterisk -rx "module reload res_pjsip.so"');
    
    echo "\nSUCCESS: All extension passwords have been unified to $NEW_PASS and Asterisk has been reloaded.\n";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
?>
