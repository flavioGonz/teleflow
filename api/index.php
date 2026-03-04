<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';
$avatar_dir = "../uploads/avatars/";

if ($action == 'get_full_data') {
    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    $arp_table = shell_exec("/usr/sbin/arp -an");
    
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $ext = $m[1];
        $avatar = "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff";
        if (file_exists($avatar_dir.$ext.".jpg")) $avatar = "uploads/avatars/$ext.jpg?".time();
        $exts[$ext] = [
            'ext'=>$ext, 'name'=>trim($m[2]), 'status'=>'OFFLINE', 
            'ip'=>'---', 'mac'=>'---', 'rtt'=>'---', 'avatar'=>$avatar, 
            'calls_today'=>rand(10, 50), 'aht'=>'02:'.rand(10,59), 'live_time' => '---'
        ];
    }
    
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { 
        if(isset($exts[$m[1]])){ 
            $ip = $m[2];
            $exts[$m[1]]['status']='ONLINE'; 
            $exts[$m[1]]['ip']=$ip; 
            if(preg_match('/\('.preg_quote($ip).'\)\s+at\s+([0-9a-fA-F:]+)/', $arp_table, $m_arp)) $exts[$m[1]]['mac'] = strtoupper($m_arp[1]);
        } 
    }

    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { 
            $exts[$m[1]]['status'] = 'BUSY'; 
            $exts[$m[1]]['live_time'] = $m[4];
            $exts[$m[1]]['live_cid'] = $m[3];
        }
    }

    echo json_encode([
        'pbx' => ['extensions' => array_values($exts)],
        'summary' => ['queue' => 12, 'wait' => '0:45', 'abandon' => '2.4%']
    ]);
    exit;
}
?>
