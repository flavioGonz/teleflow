<?php
// Copiar a config.php y completar con creds reales del entorno.
// config.php está en .gitignore para no exponer credenciales.
$TF_MODE = 'prod';
$DB_HOST = '10.1.1.7';
$DB_USER = 'CHANGEME';
$DB_PASS = 'CHANGEME';
$TF_DB_NAME  = 'teleflow';
$PBX_DB_NAME = 'asterisk';
$CDR_DB_NAME = 'asteriskcdrdb';
$PBX_HOST = '10.1.1.7';
$PBX_DB_HOST = $PBX_HOST;
$PBX_DB_USER = 'CHANGEME';
$PBX_DB_PASS = 'CHANGEME';
$AMI_HOST = '10.1.1.7';
$AMI_PORT = 5038;
$AMI_USER = 'CHANGEME';
$AMI_PASS = 'CHANGEME';
$ACL_DB_PATH = '/var/www/teleflow/db/acl.db';
$DEFAULT_ADMIN_PASS = 'CHANGEME';
$TF_DISABLE_LOCAL_ASTERISK_SHELL = true;
$RECORDINGS_PATH = '/var/cache/teleflow/recordings';
