<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';
$avatar_dir = "../uploads/avatars/";

if ($action == 'get_full_data') {
    $load = sys_getloadavg();
    $free_m = shell_exec("free -m");
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free_m, $m_mem);

    $pjsip_e = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoints'");
    $pjsip_c = shell_exec("/usr/sbin/asterisk -rx 'pjsip show contacts'");
    $channels = shell_exec("/usr/sbin/asterisk -rx 'core show channels verbose'");
    
    $exts = [];
    preg_match_all('/Endpoint:\s+([\w]+)\/(.*?)\s+(.*?)\s+(\d+)\s+of/', $pjsip_e, $m_e, PREG_SET_ORDER);
    foreach ($m_e as $m) {
        $ext = $m[1];
        $avatar = "https://ui-avatars.com/api/?name=".urlencode($m[2])."&background=714B67&color=fff";
        if (file_exists($avatar_dir.$ext.".jpg")) $avatar = "uploads/avatars/$ext.jpg?".time();
        $exts[$ext] = [
            'ext'=>$ext, 'name'=>trim($m[2]), 'status'=>'OFFLINE', 
            'ip'=>'---', 'rtt'=>'---', 'avatar'=>$avatar, 'type'=>'PJSIP',
            'device_type'=>'deskphone', 'use_video'=>true, 'open_doors'=>true
        ];
    }
    preg_match_all('/Contact:\s+([\w]+)\/sip:.*?@([\d\.]+):(\d+).*?Avail\s+([\d\.]+)/', $pjsip_c, $m_c, PREG_SET_ORDER);
    foreach ($m_c as $m) { 
        if(isset($exts[$m[1]])){ 
            $exts[$m[1]]['status']='ONLINE'; $exts[$m[1]]['ip']=$m[2]; $exts[$m[1]]['rtt']=round($m[4]).'ms'; 
            if ($m[3] > 50000) $exts[$m[1]]['device_type'] = 'softphone';
        } 
    }
    $active_calls = [];
    preg_match_all('/PJSIP\/([\w]+)-.*?\s+.*?\s+.*?\s+.*?\s+([\w]+)\s+.*?\s+.*?\s+(.*?)\s+([\d:]+)/', $channels, $m_calls, PREG_SET_ORDER);
    foreach ($m_calls as $m) {
        if (isset($exts[$m[1]])) { $exts[$m[1]]['status'] = 'BUSY'; }
        $active_calls[] = ['from' => $m[1], 'to' => $m[3], 'duration' => $m[4]];
    }

    echo json_encode([
        'system' => ['cpu' => round($load[0]*25), 'ram' => round(($m_mem[2]/($m_mem[1])*100))],
        'pbx' => ['extensions' => array_values($exts), 'calls' => $active_calls]
    ]);
    exit;
}

if ($action == 'update_extension') {
    $ext = $_POST['ext'] ?? '';
    $new_name = $_POST['name'] ?? '';
    if ($ext && $new_name) {
        shell_exec("/usr/sbin/asterisk -rx 'database put cidname $ext \"$new_name\"'");
        echo json_encode(['status' => 'success']);
    }
    exit;
}
?>
