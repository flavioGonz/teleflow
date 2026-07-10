<?php
// vite/index.php — shell dedicado del bundle Vite.
//
// PROBLEMA HISTORICO: un SW viejo con scope: "/" intercepta TODAS las rutas del origen
// (incluida /vite/) y sirve HTML cacheado con CDNs de React -> 2 copias de React -> #321.
// El sw.js nuevo se auto-desinstala pero el browser solo lo actualiza cuando el usuario
// cierra todas las tabs. Este shell muestra un boton bloqueante si detecta un SW activo,
// para que el usuario lo elimine con 1 click sin tener que ir a DevTools.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: 0');
$bundleVer = file_exists(__DIR__.'/../assets/app.build.js') ? filemtime(__DIR__.'/../assets/app.build.js') : time();
?><!DOCTYPE html>
<html lang="es" class="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Teleflow · Vite</title>
<link rel="icon" type="image/svg+xml" href="/icon-192.svg">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
html,body{margin:0;padding:0;height:100%;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#F8FAFC;color:#0F172A}
html.light,body{color-scheme:light}
#root:empty::before{content:"Cargando TeleFlow…";display:flex;align-items:center;justify-content:center;min-height:100vh;color:#64748B;font-size:14px}
.sw-gate{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#0F172A;color:#fff;z-index:99999;padding:20px;text-align:center}
.sw-card{max-width:520px;background:#1e293b;border-radius:16px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.08)}
.sw-icon{font-size:56px;color:#F59E0B;margin-bottom:16px}
.sw-h{font-size:20px;font-weight:800;margin:0 0 10px;color:#fff}
.sw-p{font-size:14px;line-height:1.6;color:#94A3B8;margin:0 0 24px}
.sw-btn{background:#0F7A3E;color:#fff;border:0;border-radius:8px;padding:14px 28px;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.15s}
.sw-btn:hover{background:#0A5A2C;transform:translateY(-1px)}
.sw-mini{font-size:11px;color:#64748B;margin-top:16px;font-family:ui-monospace,SFMono-Regular,monospace}
</style>
</head>
<body>
<div id="root"></div>
<div id="sw-gate" style="display:none" class="sw-gate">
    <div class="sw-card">
        <span class="material-icons-round sw-icon">warning</span>
        <div class="sw-h">Service Worker viejo detectado</div>
        <p class="sw-p">
            Tu navegador tiene un Service Worker del deploy anterior que está interceptando peticiones y sirviendo HTML cacheado con dos copias de React.<br><br>
            <strong>Este botón lo elimina y recarga la app limpia:</strong>
        </p>
        <button class="sw-btn" id="sw-kill-btn">
            <span class="material-icons-round" style="vertical-align:middle;font-size:18px;margin-right:6px">delete</span>
            Eliminar SW y recargar
        </button>
        <div class="sw-mini" id="sw-info"></div>
    </div>
</div>
<script>
(function() {
    // Chequea si hay un SW controlando esta página.
    var hasController = "serviceWorker" in navigator && navigator.serviceWorker.controller !== null;
    if (!hasController) {
        // No hay SW controller — cargar bundle directamente.
        loadBundle();
        return;
    }
    // Hay SW activo — mostrar gate.
    var gate = document.getElementById("sw-gate");
    var info = document.getElementById("sw-info");
    gate.style.display = "flex";
    if (info && navigator.serviceWorker.controller) {
        info.textContent = "SW: " + navigator.serviceWorker.controller.scriptURL;
    }
    document.getElementById("sw-kill-btn").addEventListener("click", async function() {
        this.disabled = true;
        this.innerHTML = "Eliminando…";
        try {
            var regs = await navigator.serviceWorker.getRegistrations();
            await Promise.all(regs.map(function(r){ return r.unregister(); }));
            if (window.caches) {
                var keys = await caches.keys();
                await Promise.all(keys.map(function(k){ return caches.delete(k); }));
            }
        } catch(e) { console.error(e); }
        // Hard reload para evitar bfcache
        location.reload(true);
    });
    function loadBundle() {
        var s = document.createElement("script");
        s.type = "module";
        s.src = "/assets/app.build.js?v=<?php echo $bundleVer; ?>";
        document.body.appendChild(s);
    }
})();
</script>
</body>
</html>
