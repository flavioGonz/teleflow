$base64Content = [Convert]::ToBase64String([System.IO.File]::ReadAllBytes('restore_sip.php'))
$cmd = "echo $base64Content | base64 -d > /tmp/restore_sip.php && php /tmp/restore_sip.php && rm /tmp/restore_sip.php"
ssh issabel-pbx "$cmd"
