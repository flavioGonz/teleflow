$base64Content = [Convert]::ToBase64String([System.IO.File]::ReadAllBytes('db_check.php'))
$cmd = "echo $base64Content | base64 -d > /tmp/db_check.php && php /tmp/db_check.php && rm /tmp/db_check.php"
ssh issabel-pbx "$cmd"
