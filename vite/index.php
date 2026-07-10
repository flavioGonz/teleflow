<?php
// vite/index.php — Shell dedicado del bundle Vite. Cero legacy, cero SW conflict.
// Este path (/vite/) NO estaba en el manifest del SW viejo, por eso no lo intercepta.
//
// La app queda accesible en: https://hzn-flow.horizonseguridad.com/vite/
//
// No carga NINGUN CDN. No hay Babel, no hay React UMD, no hay Tailwind CDN, no hay
// Chart.js, jsPDF, XLSX, tippy, ReactFlow, SIP.js, socket.io — el bundle Vite bundelea
// todo lo que necesita. SIP.js se carga on-demand desde CDN solo cuando el softphone
// se instancia.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: 0');

$bundleFile = __DIR__ . '/../assets/app.build.js';
$bundleVer  = file_exists($bundleFile) ? filemtime($bundleFile) : time();
?><!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0F7A3E">
    <meta name="color-scheme" content="light">
    <title>Teleflow · Bundle Vite</title>
    <link rel="icon" type="image/svg+xml" href="/icon-192.svg">
    <link rel="apple-touch-icon" href="/icon-192.svg">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #F8FAFC; color: #0F172A; }
        html.light, body { color-scheme: light; }
        code { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
        #root:empty::before {
            content: "Cargando TeleFlow…";
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; color: #64748B; font-size: 14px;
        }
    </style>
    <script>
        // Anti-SW-viejo: si por algo hay un SW registrado en este origen, lo eliminamos
        // ANTES de que pueda interceptar el fetch del bundle.
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(regs){
                regs.forEach(function(r){ r.unregister(); });
            }).catch(function(){});
            if (window.caches) {
                caches.keys().then(function(ks){ ks.forEach(function(k){ caches.delete(k); }); }).catch(function(){});
            }
        }
        // Marker
        window.__TF_VITE_SHELL__ = true;
    </script>
</head>
<body>
    <div id="root"></div>
    <script type="module" src="/assets/app.build.js?v=<?php echo $bundleVer; ?>"></script>
</body>
</html>
