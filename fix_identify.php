<?php
$exts = ['2001', '2002', '2003', '2004', '2005'];
$content = "";
foreach($exts as $i) {
    $content .= "[$i](+)\nidentify_by=username,auth_username\n\n";
}
file_put_contents('/etc/asterisk/pjsip_custom_post.conf', $content);
echo "Custom configs applied to endpoints.\n";
shell_exec("asterisk -rx 'pjsip reload'");
