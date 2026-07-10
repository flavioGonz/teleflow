<?php
// api/version.php — F7: metadata del deploy actual.
//
// Devuelve md5 del legacy app.jsx, mtime del bundle Vite, git SHA HEAD del repo local
// (si existe), tamaños, y timestamps. Público (no requiere auth) para poder monitorearse
// desde herramientas externas.
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap();  // sin auth — es info pública del deploy

$ASSETS = __DIR__ . '/../assets';
$REPO   = '/home/hzn/teleflow-horizon';

$out = [
  'ok' => true,
  'ts' => date('c'),
  'server' => gethostname(),
];

// Legacy app.jsx
$legacy = "$ASSETS/app.jsx";
if (is_file($legacy)) {
  $out['legacy'] = [
    'exists' => true,
    'size' => filesize($legacy),
    'md5' => md5_file($legacy),
    'mtime' => date('c', filemtime($legacy)),
  ];
} else {
  $out['legacy'] = ['exists' => false];
}

// Bundle Vite
$bundle = "$ASSETS/app.build.js";
if (is_file($bundle)) {
  $chunks_dir = "$ASSETS/chunks";
  $chunks = is_dir($chunks_dir) ? glob("$chunks_dir/*.js") : [];
  $out['build'] = [
    'exists' => true,
    'size' => filesize($bundle),
    'mtime' => date('c', filemtime($bundle)),
    'chunks_count' => count($chunks),
    'chunks_size' => array_sum(array_map('filesize', $chunks)),
  ];
} else {
  $out['build'] = ['exists' => false];
}

// Git SHA del repo local (si existe)
if (is_dir("$REPO/.git")) {
  $sha = @trim((string)@shell_exec("cd $REPO && git rev-parse --short HEAD 2>/dev/null"));
  $subj = @trim((string)@shell_exec("cd $REPO && git log -1 --pretty=%s 2>/dev/null"));
  $out['git'] = [
    'sha' => $sha ?: null,
    'subject' => $subj ?: null,
    'branch' => trim((string)@shell_exec("cd $REPO && git rev-parse --abbrev-ref HEAD 2>/dev/null")) ?: null,
  ];
} else {
  $out['git'] = null;
}

echo json_encode($out, JSON_UNESCAPED_SLASHES);
