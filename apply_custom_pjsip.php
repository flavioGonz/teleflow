<?php
$content = "
[2002](+)
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes

[2003](+)
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes

[2004](+)
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes
";
file_put_contents('/etc/asterisk/pjsip_custom_post.conf', $content);
shell_exec("asterisk -rx 'module reload res_pjsip.so'");
echo "Custom post updated and reloaded.\n";
