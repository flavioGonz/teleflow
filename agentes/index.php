<?php
// TeleFlow — Panel del Agente (bundle compartido)
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$ver = 'v' . time();
if (file_exists(__DIR__.'/../sw.js')) {
    $sw = @file_get_contents(__DIR__.'/../sw.js');
    if (preg_match('/teleflow-cache-(v\d+)/', $sw, $m)) $ver = $m[1];
}
$admin_css = '';
$admin_path = __DIR__.'/../index.php';
if (file_exists($admin_path)) {
    $admin_content = @file_get_contents($admin_path);
    // Extraer TODOS los bloques <style>...</style> (admin tiene 2)
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $admin_content, $mAll)) {
        $admin_css = implode("
", $mAll[1]);
    }
}
?><!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#11B328">
    <meta name="color-scheme" content="light">
    <script>
    // Silenciar warnings de Babel/Tailwind/DevTools que ensucian console (deoptimised, cdn.tailwindcss, etc)
    (function nuclearSilence() {
        const patterns = [
            'BABEL', 'deoptimised', 'deoptimized',
            'cdn.tailwindcss.com',
            'in-browser Babel transformer',
            'Wake Lock fail'
        ];
        function shouldSilence(args) {
            const first = args && args[0];
            if (typeof first !== 'string') return false;
            return patterns.some(p => first.includes(p));
        }
        const _warn = console.warn.bind(console);
        const _log  = console.log.bind(console);
        const _err  = console.error.bind(console);
        console.warn = function() { if (shouldSilence(arguments)) return; return _warn.apply(null, arguments); };
        console.log  = function() { if (shouldSilence(arguments)) return; return _log.apply(null,  arguments); };
        console.error = function() { if (shouldSilence(arguments)) return; return _err.apply(null, arguments); };
    })();
</script>
    <title>Teleflow · Panel del Agente</title>
    <link rel="icon" type="image/svg+xml" href="/icon-192.svg">
    <link rel="apple-touch-icon" href="/icon-192.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="TeleFlow Agente">
    <script>
        (function() {
            try {
                if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
                    const h = window.__REACT_DEVTOOLS_GLOBAL_HOOK__;
                    ['on','off','emit','inject','sub','unsub'].forEach(s => { if (typeof h[s] !== 'function') h[s] = function(){}; });
                }
            } catch(e){}
        })();
        
        // Silenciar AbortError globales (wavesurfer, fetch cancelados)
        window.addEventListener("unhandledrejection", function(ev) {
            if (ev.reason && (ev.reason.name === "AbortError" || String(ev.reason).indexOf("aborted") !== -1)) {
                ev.preventDefault(); // AbortError silenced
            }
        });
        window.__TF_AGENT_ONLY__ = true;
        // KILL SW: en /agentes/ no queremos service worker cacheando
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => {
                regs.forEach(reg => {
                    console.log('[SW] unregistering to avoid stale bundle', reg.scope);
                    reg.unregister().catch(()=>{});
                });
            });
            if (window.caches) {
                caches.keys().then(keys => keys.forEach(k => caches.delete(k)));
            }
        }

        (function forceLight(){
            try {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
                document.body && document.body.classList.remove('dark');
                document.body && document.body.classList.add('light');
                localStorage.setItem('tf_dark_user_choice', 'light');
                localStorage.setItem('theme', 'light');
            } catch(e){}
        })();
        setInterval(function() {
            try {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                }
            } catch(e){}
        }, 2000);

        
        // NUCLEAR: si hay SW viejo, desregistrar + reload sin cache
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => {
                if (regs.length > 0) {
                    // Chequear si el SW controla ya la pagina con version vieja
                    fetch('/sw.js', {cache: 'no-cache'}).then(r => r.text()).then(swText => {
                        const match = swText.match(/teleflow-cache-(v[0-9]+)/);
                        const serverVer = match ? match[1] : null;
                        const clientVer = localStorage.getItem('tf_sw_last_ver');
                        if (serverVer && serverVer !== clientVer) {
                            console.log('[SW] version mismatch — force update', clientVer, '→', serverVer);
                            localStorage.setItem('tf_sw_last_ver', serverVer);
                            regs.forEach(reg => reg.update().catch(()=>{}));
                        }
                    });
                }
            });
        }

        window.__TF_BASE_URL__ = '/';
    </script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <?php
    // F2.6 fix: cargar CDNs SÓLO en modo legacy. En modo bundle Vite, evitamos
    // dos copias de React (que causaba error #321 con zustand.useSyncExternalStore).
    $__legacy_cdns = (isset($_GET['legacy']) && $_GET['legacy'] === '1') || !is_file(__DIR__.'/../assets/app.build.js');
    if ($__legacy_cdns): ?>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.24.7/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sip.js/0.20.0/sip.min.js"></script>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5/dist/hls.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: {} } };</script>
    <?php endif; ?>
    <!-- CSS reusado del panel admin (tokens shadcn, Horizon palette, keyframes) -->
    <style>
        <?php echo $admin_css; ?>
        /* Portal del modal root — permitir clicks solo en hijos */
        #tf-modal-root { pointer-events: none; }
        #tf-modal-root > * { pointer-events: auto !important; }
        #tf-modal-root:not(:empty) { pointer-events: auto; }
    
        /* Tipografía agente — más corporativa y moderna */
        html, body, #root, button, input, select, textarea {
            font-family: "Manrope", "Plus Jakarta Sans", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";  /* tabular numbers + alt glyphs */
        }
        .font-mono, .font-mono * { font-family: "JetBrains Mono", "SF Mono", ui-monospace, monospace !important; }
    

        /* NUCLEAR LIGHT — agente jamas dark */
        html.dark, body.dark, [data-theme="dark"] {
            color-scheme: light !important;
        }
        html, body { background: #ffffff !important; color: #0a0a0a !important; }
        html.dark { background: #ffffff !important; }
        body.dark { background: #ffffff !important; }
        @keyframes tf-svg-shake { 0%,100% { transform: rotate(0deg); } 25% { transform: rotate(-15deg); } 75% { transform: rotate(15deg); } }
        @keyframes tf-svg-pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.12); } }
        @keyframes tf-svg-drop { 0% { transform: translateY(-4px) rotate(0deg); } 100% { transform: translateY(0) rotate(35deg); } }
        @keyframes tf-svg-slash { 0% { stroke-dasharray: 0 40; } 100% { stroke-dasharray: 40 0; } }
        @keyframes tf-svg-rise { 0% { transform: translateY(4px); opacity: 0.5; } 100% { transform: translateY(0); opacity: 1; } }
        @keyframes tf-svg-fall { 0% { transform: translateY(-4px); opacity: 0.5; } 100% { transform: translateY(0); opacity: 1; } }
        @keyframes tf-svg-pop { 0% { transform: scale(0.4); } 60% { transform: scale(1.15); } 100% { transform: scale(1); } }
    
        @keyframes tf-slide-in {
            0% { opacity: 0; transform: translateX(20px) scale(0.98); }
            100% { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes tf-slide-out {
            0% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(-20px); }
        }
        .animate-slide-in { animation: tf-slide-in 0.32s cubic-bezier(0.16, 1, 0.3, 1); }
        .animate-slide-out { animation: tf-slide-out 0.24s cubic-bezier(0.16, 1, 0.3, 1); }
    
        @keyframes tf-door-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px) rotate(-0.4deg); }
            75% { transform: translateX(4px) rotate(0.4deg); }
        }
        @keyframes tf-door-glow {
            0% { opacity: 0; }
            15% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        @keyframes tf-door-fade {
            0% { opacity: 0; }
            20% { opacity: 1; }
            85% { opacity: 1; }
            100% { opacity: 0; }
        }
        @keyframes tf-door-scale {
            0% { opacity: 0; transform: scale(0.7); }
            15% { opacity: 1; transform: scale(1.05); }
            25% { transform: scale(1); }
            85% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(0.95); }
        }
        @keyframes tf-door-bounce {
            0% { transform: scale(0.4) rotate(-15deg); }
            40% { transform: scale(1.2) rotate(8deg); }
            60% { transform: scale(0.95) rotate(-3deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
    
        @keyframes tf-dlg-fade { 0% { opacity: 0; } 100% { opacity: 1; } }
        @keyframes tf-dlg-pop {
            0% { opacity: 0; transform: translateY(12px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes tf-shimmer-row {
            0%, 100% { background: color-mix(in srgb, var(--horizon-green) 4%, transparent); }
            50% { background: color-mix(in srgb, var(--horizon-green) 10%, transparent); }
        }
    </style>
</head>
<body>
<div id="root"></div>
<div id="tf-modal-root" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:2147483647;"></div>
<?php
// F2.5 flip: default = bundle nuevo (Vite). ?legacy=1 → shell legacy.
$__legacy = isset($_GET['legacy']) && $_GET['legacy'] === '1';
if ($__legacy || !is_file(__DIR__.'/../assets/app.build.js')): ?>
    <script type="text/babel" data-presets="react" src="/assets/app.jsx?v=<?php echo $ver; ?>"></script>
<?php else: ?>
    <script>
        // El bundle nuevo usa HashRouter. Este shell (agentes/) arranca directo en /callcenter.
        if (!location.hash || location.hash === '#') location.hash = '#/callcenter';
        // Marker por si algún componente lee esto
        window.__TF_AGENT_SHELL__ = true;
    </script>
    <script type="module" src="/assets/app.build.js?v=<?php echo filemtime(__DIR__.'/../assets/app.build.js'); ?>"></script>
<?php endif; ?>
</body>
</html>
