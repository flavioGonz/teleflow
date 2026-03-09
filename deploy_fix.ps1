$base64Content = [Convert]::ToBase64String([System.IO.File]::ReadAllBytes('fix_sip_remote.php'))
$cmd = "echo $base64Content | base64 -d > /tmp/fix_webrtc.php && php /tmp/fix_webrtc.php && rm /tmp/fix_webrtc.php"
ssh issabel-pbx "$cmd"
