<?php
$body = file_get_contents('php://input');
file_put_contents('/var/www/teleflow/dist/lastlog.txt', $body);
echo "OK ".strlen($body);
