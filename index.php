<?php
// HORIZON: anti-cache para que el browser siempre traiga la última versión del JSX
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0f">
    <!-- HORIZON: cache busters -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <script>
      try {
        if ('serviceWorker' in navigator) {
          navigator.serviceWorker.getRegistrations().then(rs => rs.forEach(r => r.unregister()));
        }
        if ('caches' in window) caches.keys().then(ks => ks.forEach(k => caches.delete(k)));
      } catch(e) {}
    </script>
    <!-- TF_BUILD: 2026-05-08T1778189164-SESSION -->
    <title>TeleFlow · Next-Gen PBX Control</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/svg+xml" href="icon-192.svg">
    <link rel="apple-touch-icon" href="icon-192.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TeleFlow">
    <meta name="mobile-web-app-capable" content="yes">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script>
        // Blindaje contra errores de React DevTools hook corrupto
        (function() {
            try {
                if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
                    const h = window.__REACT_DEVTOOLS_GLOBAL_HOOK__;
                    const stubs = ['on', 'off', 'emit', 'inject', 'sub', 'unsub'];
                    stubs.forEach(s => { if (typeof h[s] !== 'function') h[s] = function(){}; });
                    if (!h.renderers || typeof h.renderers.get !== 'function') h.renderers = new Map();
                } else {
                    // Pre-instalar hook vacío para evitar que extensiones lo rompan a medias
                    window.__REACT_DEVTOOLS_GLOBAL_HOOK__ = {
                        renderers: new Map(),
                        on: function(){}, off: function(){}, emit: function(){},
                        inject: function(){}, sub: function(){}, unsub: function(){}
                    };
                }
            } catch(e) {}
        })();
    </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.31/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/style.css">
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sip.js/0.20.0/sip.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <!-- HORIZON: Tailwind CDN + shadcn tokens (migración progresiva A1) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        border: 'var(--border)',
                        input: 'var(--input)',
                        ring: 'var(--ring)',
                        background: 'var(--background)',
                        foreground: 'var(--foreground)',
                        primary: { DEFAULT: 'var(--primary)', foreground: 'var(--primary-foreground)' },
                        secondary: { DEFAULT: 'var(--secondary)', foreground: 'var(--secondary-foreground)' },
                        destructive: { DEFAULT: 'var(--destructive)', foreground: 'var(--destructive-foreground)' },
                        muted: { DEFAULT: 'var(--muted)', foreground: 'var(--muted-foreground)' },
                        accent: { DEFAULT: 'var(--accent)', foreground: 'var(--accent-foreground)' },
                        popover: { DEFAULT: 'var(--popover)', foreground: 'var(--popover-foreground)' },
                        card: { DEFAULT: 'var(--card)', foreground: 'var(--card-foreground)' },
                        success: { DEFAULT: 'var(--success)', foreground: 'var(--success-foreground)' },
                        warning: { DEFAULT: 'var(--warning)', foreground: 'var(--warning-foreground)' },
                    },
                    borderRadius: { lg: 'var(--radius)', md: 'calc(var(--radius) - 2px)', sm: 'calc(var(--radius) - 4px)' },
                    fontFamily: { sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'] },
                    keyframes: {
                        'accordion-down': { from: { height: 0 }, to: { height: 'var(--radix-accordion-content-height)' } },
                        'accordion-up': { from: { height: 'var(--radix-accordion-content-height)' }, to: { height: 0 } },
                    },
                }
            }
        };
    </script>
    <script>
    // Pre-paint theme: aplica .dark al <html> y .light al body antes del primer render
    // Evita el flash en blanco cuando el usuario tiene dark mode preferido
    (function() {
        try {
            var dark = localStorage.getItem('tf_dark');
            var isDark = dark !== '0'; // default = dark
            if (isDark) document.documentElement.classList.add('dark');
            else document.body && document.body.classList.add('light');
            // Aplicar también al body tan pronto como exista
            document.addEventListener('DOMContentLoaded', function() {
                if (!isDark) document.body.classList.add('light');
            });
        } catch(e) {}
    })();
    </script>
    <style>
    /* HORIZON: shadcn/ui design tokens (colores completos para compat con legacy var(--x)) */
    :root, .light {
        --background: #ffffff;
        --foreground: #0a0a0a;
        --card: #ffffff;
        --card-foreground: #0a0a0a;
        --popover: #ffffff;
        --popover-foreground: #0a0a0a;
        --primary: #7c3aed;             /* morado Horizon */
        --primary-foreground: #fafafa;
        --secondary: #f4f4f5;
        --secondary-foreground: #18181b;
        --muted-foreground: #71717a;
        --accent-foreground: #18181b;
        --destructive: #ef4444;
        --destructive-foreground: #fafafa;
        --success: #16a34a;
        --success-foreground: #fafafa;
        --warning: #f59e0b;
        --warning-foreground: #18181b;
        --input: #e4e4e7;
        --ring: #7c3aed;
        --radius: 0.5rem;
        /* Horizon brand colors */
        --horizon-green: #11B328;
        --horizon-green-glow: rgba(17, 179, 40, 0.45);
        --horizon-black: #1A1A1A;
        --horizon-bg-light: #E6E7E8;
    }
    .dark {
        --background: #0a0a0d;          /* near-black levemente morado */
        --foreground: #fafafa;
        --card: #14141a;
        --card-foreground: #fafafa;
        --popover: #14141a;
        --popover-foreground: #fafafa;
        --primary: #8b5cf6;
        --primary-foreground: #0a0a0d;
        --secondary: #1f1f26;
        --secondary-foreground: #fafafa;
        --muted-foreground: #a1a1aa;
        --accent-foreground: #fafafa;
        --destructive: #ef4444;
        --destructive-foreground: #fafafa;
        --success: #22c55e;
        --success-foreground: #0a0a0d;
        --warning: #f59e0b;
        --warning-foreground: #0a0a0d;
        --input: #2a2a33;
        --ring: #8b5cf6;
    }
    /* IMPORTANTE: --border, --muted, --accent, --text, --bg, --surface, --surface2
       son tokens legacy (rgba/hex completos) definidos en el primer :root mas abajo.
       NO los redefinimos acá para no romper var(--border) etc. */
    /* Animaciones shadcn */
    @keyframes slide-in-right { from { transform: translateX(100%); } to { transform: translateX(0); } }
    @keyframes slide-out-right { from { transform: translateX(0); } to { transform: translateX(100%); } }
    @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fade-out { from { opacity: 1; } to { opacity: 0; } }
    .animate-slide-in { animation: slide-in-right 0.25s ease-out; }
    .animate-fade-in { animation: fade-in 0.2s ease-out; }

    /* HORIZON: vibrate ringing — cards de agente/extension con llamada entrante */
    @keyframes hzn-vibrate {
        0%, 100% { transform: translate(0, 0); }
        10% { transform: translate(-2px, -1px) rotate(-0.5deg); }
        20% { transform: translate(2px, 1px) rotate(0.5deg); }
        30% { transform: translate(-2px, 1px) rotate(-0.3deg); }
        40% { transform: translate(2px, -1px) rotate(0.3deg); }
        50% { transform: translate(-1px, 0) rotate(-0.2deg); }
        60% { transform: translate(1px, 0) rotate(0.2deg); }
        70% { transform: translate(-1px, 1px); }
        80% { transform: translate(1px, -1px); }
    }
    @keyframes hzn-ring-glow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.55), 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 6px rgba(239,68,68,0.0), 0 0 24px 4px rgba(239,68,68,0.55); }
    }
    .hzn-ringing {
        animation: hzn-vibrate 0.5s ease-in-out infinite, hzn-ring-glow 1.5s ease-in-out infinite;
        border-color: #ef4444 !important;
    }
    /* Body: heredar background/foreground del token */
    body { background-color: var(--background); color: var(--foreground); }
    
        /* ═══════════════════════════════════════════════════════════════════════════
           HORIZON: shadcn/ui — overrides finales para tablas y modales legacy
           Ningún color hardcoded — todo deriva de HSL tokens (hsl(var(--xxx)))
           Compatible con theme light/dark automático
           ═══════════════════════════════════════════════════════════════════════════ */

        /* Tabla shadcn (override de .tf-table) */
        .tf-table {
            width: 100%;
            border-collapse: collapse;
            caption-side: bottom;
            font-size: 13px;
            color: var(--foreground);
        }
        .tf-table thead {
            background: transparent;
            border-bottom: 1px solid var(--border);
        }
        .tf-table th {
            height: 40px;
            padding: 0 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted-foreground);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            vertical-align: middle;
        }
        .tf-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background-color 0.15s ease;
        }
        .tf-table tbody tr:last-child {
            border-bottom: none;
        }
        .tf-table tbody tr:hover {
            background-color: color-mix(in srgb, var(--muted) 50%, transparent);
        }
        .tf-table td {
            padding: 8px 12px;
            font-size: 13px;
            color: var(--foreground);
            vertical-align: middle;
            border-bottom: none; /* lo maneja el tr */
        }
        body.light .tf-table thead,
        body.light .tf-table tbody tr:hover {
            background: transparent;
        }
        body.light .tf-table tbody tr:hover {
            background-color: color-mix(in srgb, var(--muted) 50%, transparent);
        }

        /* Modal / Drawer backdrops shadcn */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            animation: fade-in 0.2s ease-out;
        }
        .modal-box {
            background: var(--background);
            color: var(--foreground);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 24px 48px -12px rgba(0,0,0,0.35), 0 0 0 1px var(--border);
            animation: fade-in 0.2s ease-out;
        }
        .drawer-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 300;
            animation: fade-in 0.2s ease-out;
        }
        .drawer {
            position: fixed; right: 0; top: 0; bottom: 0;
            width: 100%; max-width: 440px;
            background: var(--background);
            color: var(--foreground);
            border-left: 1px solid var(--border);
            z-index: 9999;
            display: flex; flex-direction: column;
            overflow: hidden;
            box-shadow: -12px 0 40px -8px rgba(0,0,0,0.35);
            animation: slide-in-right 0.25s ease-out;
        }

        /* Inputs / selects legacy: heredar tokens */
        .input-tf {
            background: var(--background) !important;
            color: var(--foreground) !important;
            border: 1px solid var(--input) !important;
            border-radius: calc(var(--radius) - 2px) !important;
        }
        .input-tf:focus {
            outline: none !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--ring) 40%, transparent) !important;
            border-color: var(--ring) !important;
        }

        /* Cards legacy con className="glass" — alinear a shadcn Card */
        .glass {
            background-color: var(--card);
            color: var(--card-foreground);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }
        body.light .glass {
            background: var(--card) !important;
        }

        /* Scrollbar shadcn-flavor */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--muted-foreground) 50%, transparent); }

        /* ═══════════════════════════════════════════════════════════════════════════
           HORIZON LOGIN (infratec-style, brand green #11B328)
           ═══════════════════════════════════════════════════════════════════════════ */
        .hzn-login-root {
            position: fixed; inset: 0;
            display: grid;
            grid-template-columns: 2fr 4fr;   /* form 2/6 (izquierda) · imagen 4/6 (derecha) */
            background: #fff;
            color: var(--horizon-black);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }
        /* Form a la IZQUIERDA, imagen a la DERECHA */
        .hzn-login-form-wrap { order: 1; grid-column: 1; }
        .hzn-login-hero      { order: 2; grid-column: 2; }

        @media (max-width: 900px) {
            .hzn-login-root { grid-template-columns: 1fr; }
            .hzn-login-hero { display: none; }
            .hzn-login-form-wrap { grid-column: 1; }
        }

        /* ── HERO (left) ── */
        .hzn-login-hero {
            position: relative;
            overflow: hidden;
            background: #000;
        }
        .hzn-login-hero-image {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: saturate(1.05);
        }
        .hzn-login-hero-overlay {
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse at 30% 50%, rgba(17,179,40,0.18), transparent 60%),
                linear-gradient(135deg, rgba(26,26,26,0.65) 0%, rgba(26,26,26,0.45) 50%, rgba(26,26,26,0.75) 100%);
        }
        .hzn-login-hero-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 50px 60px;
            color: #fff;
            text-align: right;
        }
        .hzn-logo {
            display: flex; align-items: center; gap: 14px;
            justify-content: flex-end;
        }
        .hzn-logo-mark {
            display: flex; align-items: center; justify-content: center;
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.96);
            border-radius: 50%;
            box-shadow: 0 6px 16px rgba(0,0,0,0.25), 0 0 0 4px rgba(17,179,40,0.18);
        }
        .hzn-logo-text {
            font-size: 26px; font-weight: 900;
            letter-spacing: 0.08em;
            color: #fff;
            line-height: 1;
        }
        .hzn-logo-sub {
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.32em;
            color: var(--horizon-green);
            margin-top: 4px;
            text-transform: uppercase;
        }
        .hzn-login-tagline {
            max-width: 560px;
            margin-left: auto;     /* push to the right */
        }
        .hzn-login-tagline h1 {
            font-size: 38px; font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin: 14px 0 14px;
            color: #fff;
        }
        .hzn-login-tagline p {
            font-size: 14.5px; font-weight: 500;
            line-height: 1.6;
            color: rgba(255,255,255,0.85);
            margin: 0 0 20px;
        }
        /* Role pill arriba del heading */
        .hzn-role-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px;
            background: rgba(17,179,40,0.18);
            border: 1px solid rgba(17,179,40,0.4);
            border-radius: 100px;
            font-size: 11px; font-weight: 700;
            color: var(--horizon-green);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .hzn-role-pill[data-variant="agent"] {
            background: rgba(59,130,246,0.18);
            border-color: rgba(59,130,246,0.4);
            color: #60a5fa;
        }
        .hzn-role-pill .material-icons-round { font-size: 13px; }
        /* Feature list */
        .hzn-feature-list {
            list-style: none; padding: 0; margin: 8px 0 0;
            display: flex; flex-direction: column;
            gap: 12px;
        }
        .hzn-feature-list li {
            display: flex; align-items: flex-start; gap: 12px;
            justify-content: flex-end;
        }
        .hzn-feature-list li > .material-icons-round {
            order: 2;
            width: 36px; height: 36px;
            border-radius: 9px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            color: var(--horizon-green);
            flex-shrink: 0;
        }
        .hzn-feature-list li > div {
            order: 1;
            text-align: right;
            display: flex; flex-direction: column;
            min-width: 0;
        }
        .hzn-feature-list li strong {
            font-size: 13px; font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }
        .hzn-feature-list li span:not(.material-icons-round) {
            font-size: 11.5px; font-weight: 500;
            color: rgba(255,255,255,0.6);
            margin-top: 3px;
            line-height: 1.4;
        }

        /* ── FORM (right) ── */
        .hzn-login-form-wrap {
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            overflow-y: auto;
        }
        @media (max-width: 900px) {
            .hzn-login-form-wrap {
                background-image:
                    linear-gradient(135deg, rgba(255,255,255,0.96), rgba(255,255,255,0.92)),
                    url('assets/login-bg.jpg');
                background-size: cover;
                background-position: center;
            }
        }
        .hzn-login-form-inner {
            width: 100%;
            max-width: 420px;
        }
        .hzn-login-mobile-logo {
            display: none;
            align-items: center; justify-content: center; gap: 12px;
            margin-bottom: 24px;
        }
        @media (max-width: 900px) {
            .hzn-login-mobile-logo { display: flex; }
        }
        .hzn-logo-text-mobile {
            font-size: 22px; font-weight: 900;
            letter-spacing: 0.08em;
            color: var(--horizon-black);
        }
        .hzn-login-heading {
            margin-bottom: 28px;
        }
        .hzn-login-heading h2 {
            font-size: 28px; font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--horizon-black);
            margin: 0;
            line-height: 1.1;
        }
        .hzn-login-heading p {
            font-size: 13px;
            color: #6b7280;
            margin: 8px 0 0;
            line-height: 1.5;
        }

        /* ── Role tabs ── */
        .hzn-role-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px;
            background: var(--horizon-bg-light);
            border-radius: 10px;
            margin-bottom: 22px;
        }
        .hzn-role-tabs button {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 12px;
            border: none;
            background: transparent;
            border-radius: 7px;
            font-size: 12px; font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .hzn-role-tabs button .material-icons-round { font-size: 16px; }
        .hzn-role-tabs button:hover { color: var(--horizon-black); }
        .hzn-role-tabs button.active {
            background: #fff;
            color: var(--horizon-black);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.04);
        }

        /* ── Form fields ── */
        .hzn-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .hzn-field { display: block; }
        .hzn-label {
            display: block;
            font-size: 12px; font-weight: 700;
            color: var(--horizon-black);
            margin-bottom: 6px;
            letter-spacing: 0.01em;
        }
        .hzn-input-wrap {
            position: relative;
        }
        .hzn-input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 42px;
            background: #fff;
            border: 1.5px solid #d4d4d8;
            border-radius: 8px;
            font-size: 14px;
            color: var(--horizon-black);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            font-family: inherit;
        }
        .hzn-input:focus {
            outline: none;
            border-color: var(--horizon-green);
            box-shadow: 0 0 0 3px rgba(17,179,40,0.18);
        }
        .hzn-input::placeholder { color: #9ca3af; }
        .hzn-input.pr-12 { padding-right: 44px; }
        .hzn-input-icon {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #9ca3af;
            pointer-events: none;
            transition: color 0.15s ease;
        }
        .hzn-input:focus ~ .hzn-input-icon,
        .hzn-input-wrap:focus-within .hzn-input-icon {
            color: var(--horizon-green);
        }
        .hzn-input-toggle {
            position: absolute;
            right: 8px; top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            color: #9ca3af;
            display: inline-flex;
            transition: color 0.15s ease, background 0.15s ease;
        }
        .hzn-input-toggle:hover {
            color: var(--horizon-black);
            background: var(--horizon-bg-light);
        }
        .hzn-input-toggle .material-icons-round { font-size: 18px; }
        .hzn-field-help {
            margin-top: 6px;
            font-size: 11px;
            color: #6b7280;
        }

        /* ── Alert ── */
        .hzn-alert {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.32);
            border-radius: 8px;
            font-size: 12.5px; font-weight: 600;
            color: #b91c1c;
        }
        .hzn-alert .material-icons-round { font-size: 18px; color: #dc2626; }

        /* ── Primary button (green Horizon) ── */
        .hzn-btn-primary {
            width: 100%;
            height: 46px;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--horizon-green);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px; font-weight: 700;
            letter-spacing: 0.02em;
            cursor: pointer;
            box-shadow: 0 4px 12px var(--horizon-green-glow), inset 0 -2px 0 rgba(0,0,0,0.08);
            transition: all 0.18s ease;
            margin-top: 4px;
        }
        .hzn-btn-primary:hover:not(:disabled) {
            background: #0ea021;
            box-shadow: 0 6px 18px var(--horizon-green-glow), inset 0 -2px 0 rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        .hzn-btn-primary:active:not(:disabled) { transform: translateY(0); }
        .hzn-btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }
        .hzn-btn-primary .material-icons-round { font-size: 18px; }

        /* ── Footer ── */
        .hzn-login-footer {
            margin-top: 28px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            font-size: 11px;
            color: #9ca3af;
        }
        .hzn-dot { opacity: 0.4; }


    </style>
    <style>
        :root {
            --bg: #07070d;
            --surface: #0f0f1a;
            --surface2: #15151f;
            --border: rgba(255,255,255,0.07);
            --accent: #8b5cf6;
            --accent2: #6d28d9;
            --accent-glow: rgba(139,92,246,0.35);
            --text: #f0f0ff;
            --muted: #6b7280;
            --green: #22c55e;
            --red: #ef4444;
            --yellow: #f59e0b;
            --blue: #3b82f6;
            --sidebar-w: 230px;
        }
        body.light {
            --bg: #f5f7fb;
            --surface: #ffffff;
            --surface2: #f0f2f7;
            --border: rgba(0,0,0,0.08);
            --text: #111827;
            --muted: #6b7280;
            --accent-glow: rgba(139,92,246,0.18);
        }
        body.light .login-bg { background: radial-gradient(ellipse 80% 60% at 50% -10%,rgba(139,92,246,0.18) 0%,transparent 70%),#f5f7fb; }
        body.light .glass { background: var(--surface) !important; border-color: var(--border) !important; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        body.light .sidebar { background: linear-gradient(180deg,#fafbff,#f5f7fb) !important; border-right: 1px solid var(--border); }
        body.light .nav-item:hover { background: color-mix(in srgb, var(--primary) 8%, transparent) !important; }
        body.light .nav-item.active { background: color-mix(in srgb, var(--primary) 14%, transparent) !important; color: var(--primary) !important; }
        body.light .input-tf { background: #ffffff !important; color: #111827 !important; border:1px solid var(--border) !important; }
        body.light .tf-table { color: #111827; }
        body.light .tf-table thead { background: #f0f2f7; }
        body.light .tf-table tr:hover { background: #f5f7fb; }
        body.light .badge-online { background: rgba(34,197,94,0.15); color: #15803d; }
        body.light .badge-offline { background: rgba(107,114,128,0.12); color: #4b5563; }
        body.light .badge-busy { background: rgba(245,158,11,0.15); color: #b45309; }

        /* Theme toggle animated button */
        .theme-toggle {
            position: relative; width: 52px; height: 28px; border-radius: 14px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
            border: 1px solid rgba(255,255,255,0.08); cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.4);
            overflow: hidden;
        }
        body.light .theme-toggle {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.15);
        }
        .theme-toggle .knob {
            position: absolute; top: 2px; left: 2px;
            width: 22px; height: 22px; border-radius: 50%;
            background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; justify-content: center;
        }
        body.light .theme-toggle .knob { transform: translateX(24px); }
        .theme-toggle .knob .moon { color: #1e3a8a; font-size: 14px; transition: all 0.4s; }
        .theme-toggle .knob .sun  { color: #f59e0b; font-size: 14px; transition: all 0.4s; position:absolute; opacity: 0; transform: rotate(-90deg); }
        body.light .theme-toggle .knob .moon { opacity: 0; transform: rotate(90deg); }
        body.light .theme-toggle .knob .sun  { opacity: 1; transform: rotate(0deg); }
        /* Stars + sky decoration */
        .theme-toggle .stars { position:absolute; inset:0; transition: opacity 0.4s; }
        .theme-toggle .stars::before, .theme-toggle .stars::after {
            content: ''; position: absolute; width: 2px; height: 2px;
            background: #fff; border-radius: 50%; box-shadow: 8px 6px 0 rgba(255,255,255,0.6), 18px 14px 0 rgba(255,255,255,0.5);
        }
        .theme-toggle .stars::before { top: 4px; left: 4px; }
        body.light .theme-toggle .stars { opacity: 0; }
        .theme-toggle .clouds {
            position:absolute; inset:0; opacity: 0; transition: opacity 0.4s;
        }
        body.light .theme-toggle .clouds { opacity: 1; }
        .theme-toggle .clouds::before {
            content: ''; position: absolute; left: 4px; top: 8px;
            width: 10px; height: 6px; border-radius: 4px;
            background: rgba(255,255,255,0.7);
            box-shadow: 8px 4px 0 -1px rgba(255,255,255,0.6);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); overflow: hidden; height: 100vh; transition: background 0.4s ease, color 0.4s ease; }
        .theme-transition * { transition: background 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease !important; }
        
        /* ── CONTEXT MENU ── */
        .context-menu {
            position: absolute;
            bottom: 70px;
            left: 10px;
            width: 200px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            z-index: 1000;
            padding: 8px;
            animation: viewIn 0.2s ease;
        }
        .context-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
        }
        .context-menu-item:hover { background: rgba(139,92,246,0.1); color: var(--text); }
        .context-menu-item.danger:hover { background: rgba(239,68,68,0.1); color: #f87171; }

        /* ── LOGIN ── */
        .login-bg {
            background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(139,92,246,0.25) 0%, transparent 70%),
                        radial-gradient(ellipse 50% 40% at 80% 80%, rgba(109,40,217,0.15) 0%, transparent 60%),
                        var(--bg);
        }
        .login-card {
            background: rgba(15,15,26,0.7);
            backdrop-filter: blur(40px) saturate(180%);
            border: 1px solid rgba(139,92,246,0.2);
            box-shadow: 0 0 80px rgba(139,92,246,0.1), 0 40px 80px rgba(0,0,0,0.6);
        }
        .login-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            animation: orbFloat 8s ease-in-out infinite alternate;
        }
        @keyframes orbFloat { from { transform: translateY(0) scale(1); } to { transform: translateY(-30px) scale(1.1); } }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes ring-pulse {
            0% { opacity: 0.7; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.7); }
        }
        @keyframes tf-q-vibrate {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            20% { transform: translateX(-1.8px) rotate(-1deg); }
            40% { transform: translateX(1.8px) rotate(1deg); }
            60% { transform: translateX(-1.4px) rotate(-0.6deg); }
            80% { transform: translateX(1.4px) rotate(0.6deg); }
        }
        @keyframes phone-shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-12deg); }
            75% { transform: rotate(12deg); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.45; }
        }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: .6; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        @keyframes spin-slow { to { transform: rotate(360deg); } }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        @keyframes callActive { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
        @keyframes toastIn { from{opacity:0;transform:translateY(20px) scale(.95)} to{opacity:1;transform:translateY(0) scale(1)} }
        .anim-fadeup { animation: fadeUp 0.7s ease both; }
        .anim-fadeup-2 { animation: fadeUp 0.7s ease 0.15s both; }
        .anim-fadeup-3 { animation: fadeUp 0.7s ease 0.3s both; }
        
        /* ── NOTIFICATIONS SILEO ── */
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px) scale(0.9); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
        @keyframes callPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { transform: scale(1.15); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        @keyframes pulse-red {
          0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
          70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
          100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .animate-pulse-red {
          animation: pulse-red 2s infinite;
        }
        .glass-effect {
          background: rgba(255, 255, 255, 0.03);
          backdrop-filter: blur(12px);
          border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .call-pulse { animation: callPulse 1.2s infinite; }
        .input-tf {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            color: var(--text);
            outline: none;
            transition: border-color .25s, box-shadow .25s;
            width: 100%;
            -webkit-appearance: none;
            appearance: none;
        }
        .input-tf:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
        }
        .input-tf::placeholder { color: #6b7280; }
        /* Light mode inputs */
        body.light .input-tf { background: rgba(0,0,0,0.05); color: #111827; }
        body.light .input-tf::placeholder { color: #9ca3af; }
        /* HORIZON FIX (refuerzo): caja de inputs siempre con buen contraste */
        input.input-tf, .login-card input.input-tf {
            background: rgba(255,255,255,0.06) !important;
            color: #f0f0ff !important;
            -webkit-text-fill-color: #f0f0ff !important;
            caret-color: #f0f0ff !important;
        }
        body.light input.input-tf, body.light .login-card input.input-tf {
            background: rgba(0,0,0,0.05) !important;
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
            caret-color: #111827 !important;
        }
        /* HORIZON FIX: forzar color de texto en inputs (override autofill de Chrome) */
        .input-tf, input.input-tf, input[type="text"].input-tf, input[type="password"].input-tf, input[type="email"].input-tf {
            color: var(--text) !important;
            -webkit-text-fill-color: var(--text) !important;
        }
        body.light .input-tf, body.light input.input-tf {
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
        }
        /* Override del autofill amarillo de Chrome */
        .input-tf:-webkit-autofill,
        .input-tf:-webkit-autofill:hover,
        .input-tf:-webkit-autofill:focus,
        .input-tf:-webkit-autofill:active {
            -webkit-text-fill-color: var(--text) !important;
            -webkit-box-shadow: 0 0 0 1000px rgba(255,255,255,0.06) inset !important;
            transition: background-color 5000s ease-in-out 0s !important;
            caret-color: var(--text) !important;
        }
        body.light .input-tf:-webkit-autofill,
        body.light .input-tf:-webkit-autofill:hover,
        body.light .input-tf:-webkit-autofill:focus {
            -webkit-text-fill-color: #111827 !important;
            -webkit-box-shadow: 0 0 0 1000px rgba(0,0,0,0.05) inset !important;
        }
        /* Selects same as inputs */
        select.input-tf option { background: var(--surface); color: var(--text); }
        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
            position: relative;
            border-radius: var(--radius);
        }
        .btn-primary:hover { background: color-mix(in srgb, var(--primary) 90%, transparent); box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 35%, transparent); }
        .btn-primary:active { transform: scale(0.98); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ── LAYOUT ── */
        #app { display:flex; height:100vh; }
        .sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .28s cubic-bezier(.4,0,.2,1);
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }
        .sidebar.collapsed .nav-label, .sidebar.collapsed .nav-section,
        .sidebar.collapsed .sidebar-text, .sidebar.collapsed .sidebar-bottom-text { display:none!important; }
        .sidebar.collapsed .nav-item { justify-content:center; padding:10px 0; }
        .sidebar.collapsed .sidebar-logo { padding:16px 0; justify-content:center; }
        .sidebar.collapsed .sidebar-logo-text { display:none; }

        /* ═══ TOPBAR UNIFICADO (HORIZON) ═══ */
        .tf-q-btn:hover { background: linear-gradient(135deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04)) !important; transform: translateX(2px); }
        .tf-q-btn:active { transform: translateX(2px) scale(.98); }

        /* ═══ MODAL OVERLAY UNIFICADO ═══ */
        .tf-modal-overlay { position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 2147483647 !important; display: flex; align-items: flex-start; justify-content: center; background: rgba(8, 10, 18, 0.78); backdrop-filter: blur(20px) saturate(0.8) brightness(0.55); -webkit-backdrop-filter: blur(20px) saturate(0.8) brightness(0.55); animation: tfModalFade .18s ease both; padding: 5vh 20px; overflow-y: auto; overscroll-behavior: contain; pointer-events: auto; transform: none !important; filter: none !important; will-change: auto !important; contain: none !important; }
        .tf-modal-overlay > div { flex-shrink: 0; }
        /* Máscara superior fija para el blur — fade del contenido al hacer scroll */
        .tf-modal-overlay::before { content: ''; position: fixed; top: 0; left: 0; right: 0; height: 40px; background: linear-gradient(180deg, rgba(7,11,22,0.6), transparent); pointer-events: none; z-index: 100000; }
        body.light .tf-modal-overlay { background: rgba(30, 35, 60, 0.72); }
        
        #tf-modal-root > * { pointer-events: auto; }
        #tf-modal-root:not(:empty) { pointer-events: auto; }
        
        

        @keyframes tfModalFade { from { opacity: 0; } to { opacity: 1; } }
        .tf-modal-card { animation: tfModalPop .22s cubic-bezier(.16,1,.3,1) both; }
        @keyframes tfModalPop { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        body.tf-modal-open { overflow: hidden; }

        .tf-page-header { display: flex; align-items: center; justify-content: center; padding: 10px 4px 14px; margin: 0 0 16px 0; gap: 14px; flex-wrap: wrap; }
        .tf-page-actions { display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; width: 100%; }

        .tfbar { position: sticky; top: 0; z-index: 90; height: 56px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 14px; gap: 14px; box-shadow: 0 1px 0 var(--border), 0 4px 12px rgba(0,0,0,0.04); backdrop-filter: blur(6px); overflow: visible; }
        .tfbar-logo { display: flex; align-items: center; gap: 9px; flex-shrink: 0; cursor: pointer; }
        .tfbar-logo-mark { width: 30px; height: 30px; background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 65%, #000)); border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px color-mix(in srgb, var(--primary) 35%, transparent); }
        .tfbar-logo-text { font-size: 14px; font-weight: 800; color: var(--foreground); letter-spacing: -0.4px; font-style: italic; line-height: 1; }
        .tfbar-logo-sub { font-size: 8px; font-weight: 700; color: var(--muted-foreground); letter-spacing: .12em; text-transform: uppercase; line-height: 1; margin-top: 2px; }
        .tfbar-menu { display: flex; align-items: center; gap: 2px; flex: 0 1 auto; overflow: visible; flex-wrap: nowrap; min-width: 0; }
        .tfbar-menu::-webkit-scrollbar { display: none; }
        .tfbar-item { position: relative; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--muted-foreground); display: flex; align-items: center; gap: 7px; transition: all .15s ease; white-space: nowrap; user-select: none; }
        .tfbar-item:hover { background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--foreground); }
        .tfbar-item.active { background: color-mix(in srgb, var(--primary) 18%, transparent); color: var(--primary); }
        .tfbar-item .material-icons-round { font-size: 17px; }
        .tfbar-item .chev { font-size: 14px; opacity: .55; margin-left: -2px; transition: transform .2s; }
        .tfbar-item.open .chev { transform: rotate(180deg); }
        .tfbar-dropdown { position: fixed; min-width: 260px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 12px 40px rgba(0,0,0,.28), 0 2px 6px rgba(0,0,0,.10); padding: 6px; z-index: 9000; animation: tfDrop .18s ease both; overflow: hidden; color: var(--card-foreground); }
        @keyframes tfDrop { from { opacity: 0; transform: translateY(-6px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .tfbar-drop-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 6px; cursor: pointer; font-size: 12.5px; font-weight: 500; color: var(--foreground); transition: background .12s; }
        .tfbar-drop-item:hover { background: var(--accent); color: var(--accent-foreground); }
        .tfbar-drop-item.active { background: color-mix(in srgb, var(--primary) 15%, transparent); color: var(--primary); }
        .tfbar-drop-item .material-icons-round { font-size: 18px; color: var(--muted-foreground); flex-shrink: 0; }
        .tfbar-drop-item.active .material-icons-round { color: var(--primary); }
        .tfbar-drop-item .tfbar-drop-text { flex: 1; min-width: 0; }
        .tfbar-drop-item .tfbar-drop-sub { font-size: 10px; font-weight: 500; color: var(--muted-foreground); margin-top: 1px; }
        .tfbar-badge { background: var(--primary); color: var(--primary-foreground); font-size: 9.5px; font-weight: 800; padding: 1px 6px; border-radius: 9px; min-width: 16px; text-align: center; }
        .tfbar-spacer { flex: 1; }
        .tfbar-stat { display: flex; align-items: center; gap: 7px; padding: 5px 9px; border-radius: 8px; cursor: pointer; transition: background .12s; border: none; background: transparent; color: inherit; }
        .tfbar-stat:hover { background: color-mix(in srgb, var(--primary) 8%, transparent); }
        .tfbar-stat-icon { width: 26px; height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
        .tfbar-stat-icon .material-icons-round { font-size: 15px; }
        .tfbar-stat-val { font-size: 12.5px; font-weight: 800; line-height: 1; color: var(--text); }
        .tfbar-stat-lbl { font-size: 8.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; line-height: 1; margin-top: 2px; }
        .tfbar-divider { width: 1px; height: 26px; background: var(--border); margin: 0 4px; flex-shrink: 0; }
        .tfbar-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border); background: var(--card); color: var(--card-foreground); transition: background .12s, border-color .12s; }
        .tfbar-pill:hover { background: var(--accent); }
        .tfbar-pill:focus-within { border-color: var(--ring); box-shadow: 0 0 0 2px color-mix(in srgb, var(--ring) 25%, transparent); }
        .tfbar-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000)); color: var(--primary-foreground); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12.5px; cursor: pointer; box-shadow: 0 2px 6px color-mix(in srgb, var(--primary) 28%, transparent); user-select: none; transition: transform .12s, box-shadow .12s; }
        .tfbar-avatar:hover { transform: scale(1.06); box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 40%, transparent); }
        .tfbar-mobile-toggle { display: none; }
        @media (max-width: 900px) {
            .tfbar { padding: 0 8px; gap: 8px; }
            .tfbar-stat-val, .tfbar-stat-lbl { display: none; }
            .tfbar-stat { padding: 5px 6px; }
            .tfbar-pill > div { display: none; }
            .tfbar-pill > div:first-child { display: block; }
        }
        @media (max-width: 768px) {
            .tfbar-menu { display: none; }
            .tfbar-mobile-toggle { display: flex; padding: 7px; border-radius: 8px; cursor: pointer; }
            .tfbar-mobile-toggle:hover { background: rgba(139,92,246,.10); }
            .tfbar-mobile-panel { position: fixed; top: 56px; left: 0; right: 0; background: var(--surface); border-bottom: 1px solid var(--border); max-height: calc(100vh - 56px); overflow-y: auto; z-index: 95; padding: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
            .tfbar-mobile-section { font-size: 9.5px; font-weight: 800; color: var(--muted); letter-spacing: .12em; text-transform: uppercase; padding: 10px 12px 4px; }
        }
        /* Override #app: ya no es flex horizontal, todo va vertical */
        #app.tfshell { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        #app.tfshell .main-content { flex: 1; min-height: 0; padding: 0; display: flex; flex-direction: column; overflow: hidden; }
        #app.tfshell .main-scroll { flex: 1; overflow-y: auto; padding: 14px; }
        body.light .tfbar { background: linear-gradient(180deg, #ffffff, #fafbff) !important; }

        @keyframes viewIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        .view-enter { animation: viewIn .25s ease both; }
        .sidebar-logo {
            padding: 24px 20px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 12px 10px; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--accent2); border-radius: 3px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--muted);
            transition: all .2s;
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .nav-item:hover { background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--text); }
        .nav-item.active { background: color-mix(in srgb, var(--primary) 20%, transparent); color: var(--primary); font-weight: 600; }
        .nav-item .material-icons-round { font-size: 20px; flex-shrink:0; }
        .nav-section { font-size: 10px; font-weight: 700; letter-spacing: .12em; color: #374151; text-transform: uppercase; padding: 12px 12px 4px; }
        .sidebar-bottom {
            padding: 12px 10px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* ── RESPONSIVE / PWA ── */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0; top: 0; bottom: 0;
                z-index: 1000;
                transform: translateX(-100%);
                box-shadow: 20px 0 50px rgba(0,0,0,0.5);
            }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar.collapsed { display: none; }
            .content-area { padding: 16px; }
            .topbar { padding: 12px 16px; }
            .sidebar-overlay {
                position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 999;
                display: none;
            }
            .sidebar-overlay.active { display: block; }
        }

        /* ── MAIN ── */
        .main-content {
            flex: 1;
            overflow-y: auto;
            background: var(--bg);
            display: flex;
            flex-direction: column;
        }
        .main-content::-webkit-scrollbar { width: 4px; }
        .main-content::-webkit-scrollbar-thumb { background: var(--surface2); border-radius:4px; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: color-mix(in srgb, var(--background) 90%, transparent);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .content-area { padding: 24px 28px; flex: 1; }

        /* ── GLASS CARDS — shadcn-flavored ── */
        .glass { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
        .glass-hover { transition: background-color .15s, border-color .15s, transform .15s; }
        .glass-hover:hover { border-color: color-mix(in srgb, var(--primary) 30%, transparent); background: color-mix(in srgb, var(--primary) 4%, var(--surface)); }

        /* ── STATUS BADGES — alineados a shadcn ── */
        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600; letter-spacing:.02em; }
        .badge-online { background:color-mix(in srgb, #22c55e 18%, transparent); color:#22c55e; }
        .badge-offline { background:color-mix(in srgb, #6b7280 18%, transparent); color:#9ca3af; }
        .badge-busy { background:color-mix(in srgb, #ef4444 18%, transparent); color:#ef4444; }
        .badge-dot { width:6px; height:6px; border-radius:50%; }
        .dot-online { background:var(--green); box-shadow:0 0 6px var(--green); animation:blink 2s infinite; }
        .dot-offline { background:#6b7280; }
        .dot-busy { background:var(--yellow); box-shadow:0 0 6px var(--yellow); animation:blink 1s infinite; }

        /* ── STAT CARDS ── */
        .stat-card { padding: 20px; border-radius: 14px; }
        .stat-val { font-size: 32px; font-weight: 800; line-height: 1; margin: 8px 0 4px; }
        .stat-label { font-size: 11px; font-weight: 600; letter-spacing:.1em; text-transform:uppercase; color: var(--muted); }

        /* ── AGENT ROW ── */
        .agent-row {
            display: grid;
            grid-template-columns: 2.5fr 1.2fr 1.2fr 1.5fr 1.5fr;
            align-items: center;
            padding: 14px 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: all .2s;
            border: 1px solid var(--border);
            margin-bottom: 8px;
            background: var(--surface);
        }
        .agent-row:hover { border-color: rgba(139,92,246,.35); background: var(--surface2); }
        .agent-avatar {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items:center; justify-content:center;
            font-size: 13px; font-weight: 800; color: white;
            flex-shrink: 0;
        }

        /* ── RECORDING ── */
        audio { filter: invert(1) hue-rotate(180deg); max-width: 100%; }
        audio::-webkit-media-controls-panel { background: var(--surface2); }

        /* ── LIVE CALL ── */
        .live-call-card {
            background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(109,40,217,0.05));
            border: 1px solid rgba(139,92,246,.3);
            border-radius: 14px;
            padding: 16px;
        }
        @keyframes livePulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
        }
        .live-indicator { animation: livePulse 1.5s infinite; }

        /* ── SCROLLBAR GLOBAL ── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--surface2); border-radius: 4px; }

        /* ── MODAL ── */
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.7); backdrop-filter:blur(8px); z-index:200; display:flex; align-items:center; justify-content:center; padding:16px; }
        .modal-box { background: var(--surface); border: 1px solid rgba(139,92,246,.25); border-radius: 20px; padding: 28px; max-width: 520px; width: 100%; box-shadow: 0 40px 80px rgba(0,0,0,.6); }

        /* ── DRAWER ── */
        .drawer-backdrop { position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(10px);z-index:300; }
        .drawer {
            position:fixed;right:0;top:0;bottom:0;
            width:100%; max-width:440px;
            background:var(--surface);
            border-left:1px solid rgba(139,92,246,.25);
            z-index:9999;
            display:flex; flex-direction:column;
            overflow:hidden;
            box-shadow:-25px 0 80px rgba(0,0,0,.7);
            animation:slideInDrawer .35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideInDrawer { from{transform:translateX(100%)} to{transform:translateX(0)} }
        .drawer-header {
            padding:20px 24px;
            border-bottom:1px solid var(--border);
            display:flex;align-items:center;justify-content:space-between;
            flex:0 0 auto;
        }
        .drawer-body {
            flex:1 1 auto;
            min-height:0;
            overflow-y:auto;
            overflow-x:hidden;
            padding:24px;
            -webkit-overflow-scrolling:touch;
        }
        .drawer-body::-webkit-scrollbar { width:4px; }
        .drawer-body::-webkit-scrollbar-thumb { background:rgba(139,92,246,.3); border-radius:4px; }
        .drawer-footer {
            flex:0 0 auto;
            padding:16px 24px;
            border-top:1px solid var(--border);
            background:rgba(0,0,0,0.2);
        }

        /* ── LIVE CALL ANIM ── */
        @keyframes callPulse { 0%{box-shadow:0 0 0 0 rgba(239,68,68,.5)} 70%{box-shadow:0 0 0 12px rgba(239,68,68,0)} 100%{box-shadow:0 0 0 0 rgba(239,68,68,0)} }
        .live-pulse { animation:callPulse 1.5s ease-out infinite; }
        @keyframes countUp { from{opacity:0;transform:scale(.8)} to{opacity:1;transform:scale(1)} }
        .call-timer { animation:countUp .3s ease; font-family:monospace; font-weight:800; font-size:20px; color:#f59e0b; }

        /* ── TABLE ── */
        .tf-table { width:100%;border-collapse:collapse; }
        .tf-table th { font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6b7280;padding:8px 12px;text-align:left;border-bottom:1px solid var(--border); }
        .tf-table td { padding:10px 12px;border-bottom:1px solid var(--border);font-size:12px;color:var(--text); }
        .tf-table tr:hover td { background:var(--surface2); }

        /* ── CDR COLORS ── */
        .cdr-answered { color:#4ade80; }
        .cdr-noanswer { color:#9ca3af; }
        .cdr-busy { color:#fbbf24; }
        .cdr-failed { color:#f87171; }

        /* ── TOAST ── */
        .toast-container { position:fixed;bottom:24px;right:24px;z-index:500;display:flex;flex-direction:column;gap:8px; }
        @keyframes toastIn { from{opacity:0;transform:translateX(100%)} to{opacity:1;transform:translateX(0)} }
        .toast { padding:12px 16px;border-radius:12px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;min-width:260px;box-shadow:0 8px 30px rgba(0,0,0,.4);animation:toastIn .3s ease; }
        .toast-success { background:#14532d;border:1px solid #166534;color:#4ade80; }
        .toast-error { background:#450a0a;border:1px solid #991b1b;color:#f87171; }
        .toast-info { background:#1e1b4b;border:1px solid #3730a3;color:#a5b4fc; }
        .toast-warning { background:#431407;border:1px solid #9a3412;color:#fb923c; }
        /* HORIZON: Tabla estilo UCM/Grandstream — limpia y profesional */
        .tf-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .tf-table thead { background: rgba(139,92,246,0.05); border-bottom: 1px solid var(--border); }
        body.light .tf-table thead { background: #f0f2f7; }
        .tf-table th { padding: 12px 14px; text-align: left; font-size: 10.5px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .tf-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); }
        .tf-table tbody tr { transition: background 0.15s; }
        .tf-table tbody tr:hover { background: rgba(139,92,246,0.04); }
        body.light .tf-table tbody tr:hover { background: #f5f7fb; }
        .tf-table tbody tr:last-child td { border-bottom: none; }
        /* HORIZON: Sileo-style notifications */
        @keyframes sileo-in {
            0% { opacity: 0; transform: translateY(120%) translateX(20%) scale(0.85); }
            60% { opacity: 1; transform: translateY(-6px) translateX(0) scale(1.02); }
            100% { opacity: 1; transform: translateY(0) translateX(0) scale(1); }
        }
        @keyframes sileo-out {
            0% { opacity: 1; transform: translateY(0) translateX(0) scale(1); }
            100% { opacity: 0; transform: translateY(40%) translateX(20%) scale(0.85); }
        }
        @keyframes sileo-pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
            100% { box-shadow: 0 0 0 12px rgba(34,197,94,0); }
        }
        .sileo-stack { 
            position: fixed; bottom: 18px; right: 18px; z-index: 9999;
            display: flex; flex-direction: column-reverse; gap: 10px;
            pointer-events: none;
            max-height: calc(100vh - 36px); overflow: hidden;
        }
        .sileo-notif {
            min-width: 340px; max-width: 380px;
            background: rgba(20,20,32,0.92);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 12px 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.25);
            color: #f0f0ff;
            pointer-events: auto;
            animation: sileo-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex; gap: 12px; align-items: flex-start;
        }
        .sileo-notif.dismissing { animation: sileo-out 0.3s ease forwards; }
        body.light .sileo-notif {
            background: rgba(255,255,255,0.95);
            border-color: rgba(0,0,0,0.08);
            color: #111827;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
        }
        .sileo-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: #fff;
        }
        .sileo-notif.call .sileo-icon {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            animation: sileo-pulse-ring 1.4s infinite;
        }
        .sileo-notif.warning .sileo-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .sileo-notif.error .sileo-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .sileo-body { flex: 1; min-width: 0; }
        .sileo-title { font-size: 12.5px; font-weight: 800; letter-spacing: -0.2px; margin-bottom: 2px; }
        .sileo-msg { font-size: 11.5px; opacity: 0.78; line-height: 1.35; }
        .sileo-actions { display: flex; gap: 6px; margin-top: 8px; }
        .sileo-btn { padding: 5px 12px; border-radius: 8px; border: none; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .sileo-btn.primary { background: rgba(139,92,246,0.25); color: #c4b5fd; }
        .sileo-btn.primary:hover { background: rgba(139,92,246,0.4); color: #fff; }
        .sileo-btn.secondary { background: rgba(255,255,255,0.06); color: #fff; }
        body.light .sileo-btn.secondary { background: rgba(0,0,0,0.05); color: #111827; }
        .sileo-close { 
            width: 22px; height: 22px; border-radius: 50%; border: none; 
            background: rgba(255,255,255,0.08); color: #999; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            font-size: 14px; transition: all 0.15s;
        }
        .sileo-close:hover { background: rgba(255,255,255,0.18); color: #fff; }
        body.light .sileo-close { background: rgba(0,0,0,0.06); }
        body.light .sileo-close:hover { background: rgba(0,0,0,0.1); color: #111; }
        .sileo-time { font-size: 9.5px; opacity: 0.55; font-variant-numeric: tabular-nums; }


    </style>
</head>
<body>
<div id="root"></div>
<div id="tf-modal-root" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:2147483647;"></div>
<script src="sw.js"></script>
<script type="text/babel">
const { useState, useEffect, useRef, useCallback, useMemo, useContext } = React;

// ═══════════════════════════════════════════════════════════════════════════
// HORIZON: shadcn/ui primitives — adaptados a JSX inline + Tailwind CDN
// (sin Radix porque no tenemos build pipeline; comportamiento manual)
// ═══════════════════════════════════════════════════════════════════════════

// Utility para mergear clases tipo cn() de shadcn
function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}

// ─── Theme management ──────────────────────────────────────────────────────
const ThemeContext = React.createContext({ theme: 'dark', setTheme: () => {} });

function ThemeProvider({ children }) {
    const [theme, setThemeState] = useState(() => {
        try { return localStorage.getItem('tf_theme') || 'dark'; } catch(e) { return 'dark'; }
    });
    useEffect(() => {
        const root = document.documentElement;
        root.classList.remove('light', 'dark');
        root.classList.add(theme);
        document.body.classList.remove('light', 'dark');
        document.body.classList.add(theme);
        try { localStorage.setItem('tf_theme', theme); } catch(e) {}
    }, [theme]);
    const setTheme = useCallback((t) => setThemeState(t), []);
    const value = useMemo(() => ({ theme, setTheme }), [theme, setTheme]);
    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

function useTheme() { return useContext(ThemeContext); }

function ThemeToggle({ className }) {
    const { theme, setTheme } = useTheme();
    const next = theme === 'dark' ? 'light' : 'dark';
    return (
        <button
            type="button"
            onClick={() => setTheme(next)}
            className={cn(
                "inline-flex items-center justify-center rounded-md h-9 w-9 transition-colors",
                "border border-border bg-card hover:bg-accent hover:text-accent-foreground",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
                className
            )}
            title={theme === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro'}
        >
            <span className="material-icons-round text-base">
                {theme === 'dark' ? 'light_mode' : 'dark_mode'}
            </span>
        </button>
    );
}

// ─── Button ─────────────────────────────────────────────────────────────────
const BUTTON_VARIANTS = {
    default: 'bg-primary text-primary-foreground hover:bg-primary/90',
    destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
    success: 'bg-green-600 text-white hover:bg-green-700',
};
const BUTTON_SIZES = {
    default: 'h-9 px-4 py-2 text-sm',
    sm: 'h-8 rounded-md px-3 text-xs',
    lg: 'h-10 rounded-md px-8 text-sm',
    icon: 'h-9 w-9',
};
function Button({ children, variant = 'default', size = 'default', className, type = 'button', asLink = false, href, target, ...props }) {
    const cls = cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        'disabled:pointer-events-none disabled:opacity-50',
        BUTTON_VARIANTS[variant] || BUTTON_VARIANTS.default,
        BUTTON_SIZES[size] || BUTTON_SIZES.default,
        className
    );
    if (asLink) {
        return <a href={href} target={target} rel={target === '_blank' ? 'noopener noreferrer' : undefined} className={cls} {...props}>{children}</a>;
    }
    return <button type={type} className={cls} {...props}>{children}</button>;
}

// ─── Card ───────────────────────────────────────────────────────────────────
function Card({ children, className, ...props }) {
    return <div className={cn("rounded-lg border border-border bg-card text-card-foreground shadow-sm", className)} {...props}>{children}</div>;
}
function CardHeader({ children, className, ...props }) {
    return <div className={cn("flex flex-col space-y-1.5 p-6", className)} {...props}>{children}</div>;
}
function CardTitle({ children, className, ...props }) {
    return <h3 className={cn("text-lg font-semibold leading-none tracking-tight", className)} {...props}>{children}</h3>;
}
function CardDescription({ children, className, ...props }) {
    return <p className={cn("text-sm text-muted-foreground", className)} {...props}>{children}</p>;
}
function CardContent({ children, className, ...props }) {
    return <div className={cn("p-6 pt-0", className)} {...props}>{children}</div>;
}
function CardFooter({ children, className, ...props }) {
    return <div className={cn("flex items-center p-6 pt-0", className)} {...props}>{children}</div>;
}

// ─── Input ──────────────────────────────────────────────────────────────────
function Input({ className, type = 'text', ...props }) {
    return (
        <input
            type={type}
            className={cn(
                "flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm",
                "transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium",
                "placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring",
                "disabled:cursor-not-allowed disabled:opacity-50",
                className
            )}
            {...props}
        />
    );
}

// ─── Select (HTML nativo con styles shadcn) ─────────────────────────────────
function Select({ className, children, ...props }) {
    return (
        <select
            className={cn(
                "flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm",
                "focus:outline-none focus:ring-1 focus:ring-ring",
                "disabled:cursor-not-allowed disabled:opacity-50",
                className
            )}
            {...props}
        >{children}</select>
    );
}

// ─── Label ──────────────────────────────────────────────────────────────────
function Label({ children, className, htmlFor, ...props }) {
    return <label htmlFor={htmlFor} className={cn("text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70", className)} {...props}>{children}</label>;
}

// ─── Badge ──────────────────────────────────────────────────────────────────
const BADGE_VARIANTS = {
    default: 'border-transparent bg-primary text-primary-foreground hover:bg-primary/80',
    secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
    destructive: 'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
    outline: 'text-foreground border-border',
    success: 'border-transparent bg-green-500/15 text-green-600 dark:text-green-400',
    warning: 'border-transparent bg-amber-500/15 text-amber-600 dark:text-amber-400',
    info: 'border-transparent bg-blue-500/15 text-blue-600 dark:text-blue-400',
};
function Badge({ children, variant = 'default', className, ...props }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors',
            BADGE_VARIANTS[variant] || BADGE_VARIANTS.default,
            className
        )} {...props}>{children}</span>
    );
}

// ─── Separator ──────────────────────────────────────────────────────────────
function Separator({ className, orientation = 'horizontal', ...props }) {
    return (
        <div className={cn(
            'shrink-0 bg-border',
            orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
            className
        )} {...props}/>
    );
}

// ─── Skeleton ───────────────────────────────────────────────────────────────
function Skeleton({ className, ...props }) {
    return <div className={cn("animate-pulse rounded-md bg-muted", className)} {...props}/>;
}

// ─── Tabs ───────────────────────────────────────────────────────────────────
const TabsContext = React.createContext({ value: '', onChange: () => {} });
function Tabs({ value, onChange, defaultValue, children, className, ...props }) {
    const [internal, setInternal] = useState(defaultValue || '');
    const current = value !== undefined ? value : internal;
    const setCurrent = (v) => { if (value === undefined) setInternal(v); onChange?.(v); };
    const ctx = useMemo(() => ({ value: current, onChange: setCurrent }), [current]);
    return <TabsContext.Provider value={ctx}><div className={cn('', className)} {...props}>{children}</div></TabsContext.Provider>;
}
function TabsList({ children, className, ...props }) {
    return <div role="tablist" className={cn("inline-flex h-9 items-center justify-center rounded-lg bg-muted p-1 text-muted-foreground", className)} {...props}>{children}</div>;
}
function TabsTrigger({ value, children, className, ...props }) {
    const { value: current, onChange } = useContext(TabsContext);
    const active = current === value;
    return (
        <button
            role="tab"
            aria-selected={active}
            onClick={() => onChange(value)}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-medium ring-offset-background transition-all',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                'disabled:pointer-events-none disabled:opacity-50',
                active && 'bg-background text-foreground shadow',
                className
            )}
            {...props}
        >{children}</button>
    );
}
function TabsContent({ value, children, className, ...props }) {
    const { value: current } = useContext(TabsContext);
    if (current !== value) return null;
    return <div role="tabpanel" className={cn('mt-4 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2', className)} {...props}>{children}</div>;
}

// ─── Dialog (modal) ─────────────────────────────────────────────────────────
function Dialog({ open, onOpenChange, children }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onOpenChange?.(false); };
        document.addEventListener('keydown', onKey);
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prevOverflow;
        };
    }, [open, onOpenChange]);
    if (!open) return null;
    const content = (
        <div className="fixed inset-0 flex items-center justify-center animate-fade-in" style={{zIndex:9999}}>
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" onClick={() => onOpenChange?.(false)}/>
            <div className="relative grid w-full max-w-lg gap-4 border bg-card text-card-foreground p-6 rounded-lg animate-fade-in" style={{borderColor:'var(--border)', boxShadow:'0 25px 50px -12px rgba(0,0,0,0.6)'}} onClick={e => e.stopPropagation()}>
                {children}
                <button onClick={() => onOpenChange?.(false)} className="absolute right-4 top-4 rounded-sm opacity-70 hover:opacity-100 transition-opacity">
                    <span className="material-icons-round text-base">close</span>
                </button>
            </div>
        </div>
    );
    if (typeof document === 'undefined') return content;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(content, root);
}
function DialogHeader({ children, className, ...props }) {
    return <div className={cn("flex flex-col space-y-1.5 text-center sm:text-left", className)} {...props}>{children}</div>;
}
function DialogTitle({ children, className, ...props }) {
    return <h2 className={cn("text-lg font-semibold leading-none tracking-tight", className)} {...props}>{children}</h2>;
}
function DialogDescription({ children, className, ...props }) {
    return <p className={cn("text-sm text-muted-foreground", className)} {...props}>{children}</p>;
}
function DialogFooter({ children, className, ...props }) {
    return <div className={cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 mt-4", className)} {...props}>{children}</div>;
}

// ─── Sheet (drawer lateral) ─────────────────────────────────────────────────
function Sheet({ open, onOpenChange, side = 'right', children, size = 'default' }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onOpenChange?.(false); };
        document.addEventListener('keydown', onKey);
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prevOverflow;
        };
    }, [open, onOpenChange]);
    if (!open) return null;
    const widths = { sm: 'sm:max-w-sm', default: 'sm:max-w-md', lg: 'sm:max-w-lg', xl: 'sm:max-w-xl', '2xl': 'sm:max-w-2xl', '3xl': 'sm:max-w-3xl', '4xl': 'sm:max-w-4xl' };
    const sideClass = side === 'right'
        ? `right-0 inset-y-0 h-full w-3/4 ${widths[size] || widths.default} border-l animate-slide-in`
        : `left-0 inset-y-0 h-full w-3/4 ${widths[size] || widths.default} border-r animate-slide-in`;
    const content = (
        <div className="fixed inset-0 animate-fade-in" style={{zIndex:9999, pointerEvents:'auto'}}>
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" onClick={() => onOpenChange?.(false)}/>
            <div className={cn("fixed bg-background shadow-2xl flex flex-col", sideClass)} style={{borderColor:'var(--border)'}}>{children}</div>
        </div>
    );
    if (typeof document === 'undefined') return content;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(content, root);
}
function SheetHeader({ children, className, ...props }) {
    return <div className={cn("flex flex-col space-y-2 text-left p-6", className)} {...props}>{children}</div>;
}
function SheetTitle({ children, className, ...props }) {
    return <h2 className={cn("text-lg font-semibold text-foreground", className)} {...props}>{children}</h2>;
}
function SheetDescription({ children, className, ...props }) {
    return <p className={cn("text-sm text-muted-foreground", className)} {...props}>{children}</p>;
}
function SheetContent({ children, className, ...props }) {
    return <div className={cn("flex-1 overflow-auto px-6 pb-6", className)} {...props}>{children}</div>;
}

// ─── Table ──────────────────────────────────────────────────────────────────
function ShTable({ children, className, ...props }) {
    return (
        <div className="relative w-full overflow-auto">
            <table className={cn("w-full caption-bottom text-sm", className)} {...props}>{children}</table>
        </div>
    );
}
function ShTHead({ children, className, ...props }) {
    return <thead className={cn("[&_tr]:border-b sticky top-0 bg-background z-10", className)} {...props}>{children}</thead>;
}
function ShTBody({ children, className, ...props }) {
    return <tbody className={cn("[&_tr:last-child]:border-0", className)} {...props}>{children}</tbody>;
}
function ShTR({ children, className, onClick, ...props }) {
    return <tr onClick={onClick} className={cn("border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted", onClick && "cursor-pointer", className)} {...props}>{children}</tr>;
}
function ShTH({ children, className, align = 'left', ...props }) {
    return <th className={cn("h-10 px-3 align-middle font-semibold text-muted-foreground text-xs uppercase tracking-wider", align === 'right' && 'text-right', align === 'center' && 'text-center', className)} {...props}>{children}</th>;
}
function ShTD({ children, className, align = 'left', mono = false, ...props }) {
    return <td className={cn("px-3 py-2 align-middle text-sm", align === 'right' && 'text-right', align === 'center' && 'text-center', mono && 'font-mono text-xs', className)} {...props}>{children}</td>;
}

// ─── Toaster (sistema simple por window.shToast) ────────────────────────────
const ToasterContext = React.createContext({ push: () => {}, items: [] });
function Toaster() {
    const [items, setItems] = useState([]);
    useEffect(() => {
        window.shToast = (msg, variant = 'default', durationMs = 4000) => {
            const id = Date.now() + Math.random();
            setItems((arr) => [...arr, { id, msg, variant }]);
            setTimeout(() => setItems((arr) => arr.filter((t) => t.id !== id)), durationMs);
        };
        return () => { delete window.shToast; };
    }, []);
    return (
        <div className="fixed bottom-6 right-6 z-[200] flex flex-col gap-2">
            {items.map((t) => {
                const cls = t.variant === 'destructive' ? 'border-destructive/40 bg-destructive text-destructive-foreground'
                          : t.variant === 'success' ? 'border-green-600/40 bg-green-600 text-white'
                          : 'border-border bg-popover text-popover-foreground';
                return (
                    <div key={t.id} className={cn("animate-slide-in pointer-events-auto flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg min-w-[300px] max-w-[420px]", cls)}>
                        <span className="material-icons-round text-base">{t.variant === 'destructive' ? 'error' : t.variant === 'success' ? 'check_circle' : 'info'}</span>
                        <span className="flex-1 text-sm">{t.msg}</span>
                    </div>
                );
            })}
        </div>
    );
}


// HORIZON: RTSP Preview — popup encima del toast Sileo cuando llama un interno con rtsp_url configurado
function RtspPreviewLayer() {
    const [previews, setPreviews] = useState([]);

    useEffect(() => {
        const onOpen = (e) => {
            const d = e.detail || {};
            if (!d.url || !d.id) return;
            setPreviews(prev => prev.find(p => p.id === d.id) ? prev : [...prev, d]);
        };
        const onClose = (e) => {
            const id = e.detail?.id;
            if (id) setPreviews(prev => prev.filter(p => p.id !== id));
            else setPreviews([]);
        };
        window.addEventListener('tf-rtsp-preview-open', onOpen);
        window.addEventListener('tf-rtsp-preview-close', onClose);
        return () => {
            window.removeEventListener('tf-rtsp-preview-open', onOpen);
            window.removeEventListener('tf-rtsp-preview-close', onClose);
        };
    }, []);

    if (previews.length === 0) return null;

    const content = (
        <div style={{
            position:'fixed', top:24, right:24, zIndex:10001,
            display:'flex', flexDirection:'column', gap:12,
            pointerEvents:'auto'
        }}>
            {previews.map(p => (
                <RtspPreviewCard
                    key={p.id}
                    preview={p}
                    onClose={() => setPreviews(prev => prev.filter(x => x.id !== p.id))}
                />
            ))}
        </div>
    );
    if (typeof document === 'undefined') return content;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(content, root);
}

function RtspPreviewCard({ preview, onClose }) {
    const videoRef = useRef(null);
    const [error, setError] = useState(null);
    const [muted, setMuted] = useState(true);
    const [resolvedUrl, setResolvedUrl] = useState(null);
    const [resolving, setResolving] = useState(false);

    // Si la URL original es rtsp://, pedir al backend proxy que la convierta a HLS
    useEffect(() => {
        const u = preview.url || '';
        const isRtspNative = /^rtsps?:\/\//i.test(u);
        if (isRtspNative && preview.ext) {
            setResolving(true);
            fetch(`api/rtsp_proxy.php?ext=${encodeURIComponent(preview.ext)}`, { credentials: 'include' })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'ok' && d.hls_url) setResolvedUrl(d.hls_url);
                    else setError(d.message || 'Proxy RTSP no disponible');
                })
                .catch(() => setError('Error al contactar proxy RTSP'))
                .finally(() => setResolving(false));
        }
    }, [preview.url, preview.ext]);

    // Detectar tipo de stream — usar resolvedUrl si existe (RTSP convertido a HLS), sino la original
    const url = resolvedUrl || preview.url || '';
    const isHls = /\.m3u8(\?|$)/i.test(url);
    const isMp4 = /\.(mp4|webm|ogv)(\?|$)/i.test(url);
    const isMjpeg = /\.(mjpg|mjpeg|cgi)(\?|$)/i.test(url) || /\/snap|\/mjpg|action=stream/i.test(url);
    const isRtsp = /^rtsps?:\/\//i.test(url);
    const isHttp = /^https?:\/\//i.test(url);

    useEffect(() => {
        if (!isHls || !videoRef.current) return;
        const video = videoRef.current;
        // Cargar HLS.js si no está
        const setupHls = () => {
            if (!window.Hls) { setError('HLS.js no cargó'); return; }
            if (window.Hls.isSupported()) {
                const hls = new window.Hls();
                hls.loadSource(url);
                hls.attachMedia(video);
                hls.on(window.Hls.Events.ERROR, (_, data) => {
                    if (data.fatal) setError('Stream HLS no disponible');
                });
                video._hls = hls;
                return () => { try { hls.destroy(); } catch(e) {} };
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = url;
            } else {
                setError('Tu browser no soporta HLS');
            }
        };
        if (window.Hls) {
            return setupHls();
        } else {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/hls.js@1.5/dist/hls.min.js';
            s.onload = setupHls;
            s.onerror = () => setError('No se pudo cargar HLS.js');
            document.head.appendChild(s);
        }
    }, [url, isHls]);

    return (
        <div style={{
            width: 340,
            background: '#0a0a0d',
            borderRadius: 12,
            overflow: 'hidden',
            border: '1px solid var(--border)',
            boxShadow: '0 20px 50px -10px rgba(0,0,0,0.6), 0 0 0 2px var(--horizon-green), 0 0 30px rgba(17,179,40,0.35)',
            animation: 'slide-in-right 0.3s ease-out'
        }}>
            {/* Header */}
            <div style={{
                padding: '8px 12px',
                background: 'linear-gradient(135deg, rgba(17,179,40,0.22), rgba(17,179,40,0.05))',
                borderBottom: '1px solid rgba(17,179,40,0.35)',
                display: 'flex', alignItems: 'center', gap: 8
            }}>
                <span className="material-icons-round" style={{fontSize:16, color:'var(--horizon-green)', animation:'pulse 1.5s infinite'}}>videocam</span>
                <div style={{flex:1, minWidth:0}}>
                    <div style={{fontSize:11, fontWeight:800, color:'#fff', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>
                        {preview.label || 'Videoportero'}
                    </div>
                    <div style={{fontSize:9, color:'rgba(255,255,255,0.6)', fontFamily:'monospace'}}>
                        ext {preview.ext} · llamada en curso
                    </div>
                </div>
                <button onClick={() => { setMuted(m=>!m); if(videoRef.current) videoRef.current.muted = !muted; }}
                    title={muted?'Activar audio':'Silenciar'}
                    style={{padding:4, border:'none', background:'rgba(255,255,255,0.08)', borderRadius:6, cursor:'pointer', color:'#fff'}}>
                    <span className="material-icons-round" style={{fontSize:14}}>{muted?'volume_off':'volume_up'}</span>
                </button>
                <button onClick={onClose} title="Cerrar"
                    style={{padding:4, border:'none', background:'rgba(255,255,255,0.08)', borderRadius:6, cursor:'pointer', color:'#fff'}}>
                    <span className="material-icons-round" style={{fontSize:14}}>close</span>
                </button>
            </div>

            {/* Video area */}
            <div style={{position:'relative', width:340, height:255, background:'#000', display:'flex', alignItems:'center', justifyContent:'center'}}>
                {error && (
                    <div style={{textAlign:'center', color:'#fca5a5', padding:20, fontSize:11}}>
                        <span className="material-icons-round" style={{fontSize:32, display:'block', marginBottom:6}}>broken_image</span>
                        {error}
                    </div>
                )}
                {!error && (isHls || isMp4) && (
                    <video
                        ref={videoRef}
                        autoPlay muted={muted} playsInline
                        src={isMp4 ? url : undefined}
                        onError={()=>setError('No se pudo cargar el video')}
                        style={{width:'100%', height:'100%', objectFit:'cover'}}
                    />
                )}
                {!error && isMjpeg && (
                    <img src={url} alt="MJPEG stream"
                        onError={()=>setError('Stream MJPEG no disponible')}
                        style={{width:'100%', height:'100%', objectFit:'cover'}}/>
                )}
                {!error && isRtsp && !resolving && (
                    <div style={{textAlign:'center', color:'#fcd34d', padding:20, fontSize:11}}>
                        <span className="material-icons-round" style={{fontSize:32, display:'block', marginBottom:6, color:'#f59e0b'}}>warning</span>
                        <div style={{fontWeight:800, marginBottom:4}}>Stream RTSP no disponible</div>
                        <div style={{color:'rgba(252,211,77,0.7)', fontSize:10, lineHeight:1.4}}>
                            El proxy MediaMTX no pudo conectarse a la cámara.<br/>
                            Verificá que rtsp_url sea accesible.
                        </div>
                    </div>
                )}
                {resolving && (
                    <div style={{textAlign:'center', color:'rgba(255,255,255,0.7)', padding:20, fontSize:11}}>
                        <span className="material-icons-round animate-spin" style={{fontSize:28, display:'block', marginBottom:6, color:'var(--horizon-green)'}}>autorenew</span>
                        Conectando al stream…
                    </div>
                )}
                {!error && !isHls && !isMp4 && !isMjpeg && !isRtsp && isHttp && (
                    <img src={url} alt="HTTP stream"
                        onError={()=>setError('Stream no disponible')}
                        style={{width:'100%', height:'100%', objectFit:'cover'}}/>
                )}
                {/* Live indicator */}
                <div style={{position:'absolute', top:8, left:8, padding:'3px 8px', background:'rgba(239,68,68,0.95)', color:'#fff', borderRadius:4, fontSize:9, fontWeight:900, letterSpacing:'.06em', display:'inline-flex', alignItems:'center', gap:4}}>
                    <span style={{width:6, height:6, borderRadius:'50%', background:'#fff', animation:'pulse 1s infinite'}}/>LIVE
                </div>
            </div>
        </div>
    );
}

// HORIZON: wrapper para migrar modales legacy a Dialog shadcn sin reescribir todo el contenido
function LegacyDialogShell({ open = true, onClose, maxWidth = 560, maxHeight = '90vh', children, className, autoHeight = true }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onClose?.(); };
        document.addEventListener('keydown', onKey);
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prevOverflow;
        };
    }, [open, onClose]);
    if (!open) return null;
    // HORIZON: render via portal en #tf-modal-root para escapar stacking contexts
    // (transform/filter de ancestors rompen position: fixed)
    const content = (
        <div
            onClick={onClose}
            className="fixed inset-0 flex items-center justify-center animate-fade-in"
            style={{background:'rgba(0,0,0,0.65)', backdropFilter:'blur(6px)', padding:'4vh 20px', overflowY:'auto', zIndex:9999, pointerEvents:'auto'}}
        >
            <div
                onClick={(e) => e.stopPropagation()}
                className={cn(
                    "relative bg-card text-card-foreground border rounded-lg shadow-2xl",
                    "flex flex-col overflow-hidden animate-fade-in",
                    className
                )}
                style={{
                    maxWidth: typeof maxWidth === 'number' ? `${maxWidth}px` : maxWidth,
                    width: '100%',
                    maxHeight,
                    minHeight: 0,
                    borderColor: 'var(--border)',
                    boxShadow: '0 25px 50px -12px rgba(0,0,0,0.6), 0 0 0 1px var(--border)'
                }}
            >
                {children}
            </div>
        </div>
    );
    if (typeof document === 'undefined') return content;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(content, root);
}

// HORIZON: ConfirmDialog reusable — confirmaciones estilizadas via shadcn Dialog pattern
function ConfirmDialog({ open, onCancel, onConfirm, title, message, confirmLabel = 'Confirmar', cancelLabel = 'Cancelar', variant = 'default', icon = 'help_outline', loading = false }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') !loading && onCancel?.(); };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = prev; };
    }, [open, onCancel, loading]);
    if (!open) return null;
    const accent = variant === 'destructive' ? '#ef4444' : (variant === 'success' ? '#11B328' : '#3b82f6');
    const content = (
        <div onClick={() => !loading && onCancel?.()} className="fixed inset-0 flex items-center justify-center animate-fade-in" style={{background:'rgba(0,0,0,0.65)', backdropFilter:'blur(6px)', padding:'4vh 20px', overflowY:'auto', zIndex:10000}}>
            <div onClick={e => e.stopPropagation()} className="relative bg-card text-card-foreground rounded-lg border animate-fade-in" style={{width:420, maxWidth:'100%', borderColor:'var(--border)', boxShadow:'0 25px 50px -12px rgba(0,0,0,0.6), 0 0 0 1px var(--border)'}}>
                <div style={{padding:'24px 24px 16px'}}>
                    <div className="flex items-center gap-3 mb-3">
                        <div className="flex items-center justify-center rounded-full" style={{width:48, height:48, background:`${accent}1f`}}>
                            <span className="material-icons-round" style={{color:accent, fontSize:26}}>{icon}</span>
                        </div>
                        <div className="flex-1">
                            <h3 className="text-base font-bold" style={{color:'var(--foreground)'}}>{title}</h3>
                        </div>
                    </div>
                    <p className="text-sm leading-relaxed" style={{color:'var(--muted-foreground)'}}>{message}</p>
                </div>
                <div className="flex items-center justify-end gap-2 px-6 py-4 border-t" style={{borderColor:'var(--border)', background:'var(--secondary)'}}>
                    <button
                        onClick={() => !loading && onCancel?.()}
                        disabled={loading}
                        className="h-9 px-4 rounded-md text-sm font-medium transition-colors border hover:bg-accent"
                        style={{borderColor:'var(--border)', background:'var(--background)', color:'var(--foreground)'}}
                    >{cancelLabel}</button>
                    <button
                        onClick={onConfirm}
                        disabled={loading}
                        className="h-9 px-4 rounded-md text-sm font-bold transition-colors inline-flex items-center gap-2"
                        style={{background:accent, color:'#fff', opacity: loading ? 0.65 : 1}}
                    >
                        {loading && <span className="material-icons-round animate-spin" style={{fontSize:14}}>autorenew</span>}
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
    if (typeof document === 'undefined') return content;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(content, root);
}




// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
const fmtTime = (s) => `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
function AgentCallTimer({ seconds }) {
    const [elapsed, setElapsed] = useState(seconds);
    useEffect(() => { setElapsed(seconds); }, [seconds]);
    useEffect(() => {
        const t = setInterval(() => setElapsed(e => e + 1), 1000);
        return () => clearInterval(t);
    }, []);
    return <span className="font-mono font-bold tracking-tight">{fmtTime(elapsed)}</span>;
}
const avatarColors = ['from-violet-500 to-purple-700','from-blue-500 to-cyan-600','from-rose-500 to-red-700','from-amber-500 to-orange-600','from-emerald-500 to-teal-600','from-pink-500 to-fuchsia-600'];
const getColor = (n) => avatarColors[(n?.charCodeAt(0) || 0) % avatarColors.length];
const initials = (n) => { const s = (typeof n === 'string') ? n : (n && (n.name || n.agent_name || n.username || '')); const str = String(s||'').trim() || '?'; return str.split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase(); };
// HORIZON: formato del destino de una llamada (ext, cola, grupo, externo)

// HORIZON: helper para formatear segundos a hh:mm:ss
function tfFmtSecs(s) {
    s = parseInt(s) || 0;
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sc = s % 60;
    return h > 0 ? `${h}h ${String(m).padStart(2,'0')}m` : (m > 0 ? `${m}m ${String(sc).padStart(2,'0')}s` : `${sc}s`);
}

function fmtDest(c) {
    if (!c) return '—';
    const d = c.dest || c.peer_ext || c.exten || '';
    const ctx = c.context || '';
    if (/macro-dial-one|grp-|ext-group/i.test(ctx)) return d ? `Grupo ${d}` : 'Grupo';
    if (/macro-queue|app-queue|ext-queues/i.test(ctx)) return d ? `Cola ${d}` : 'Cola';
    if (/^\d{2,5}$/.test(String(d))) return `Ext ${d}`;
    return String(d || '—');
}

// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// STORE HELPER (MINIMAL ZUSTAND REPLACEMENT)
// ─────────────────────────────────────────────
const createStore = (config) => {
    let state;
    const listeners = new Set();
    const setState = (partial, replace) => {
        const nextState = typeof partial === 'function' ? partial(state) : partial;
        if (nextState !== state) {
            state = replace ? nextState : { ...state, ...nextState };
            listeners.forEach((l) => l(state));
        }
    };
    const getState = () => state;
    const subscribe = (l) => { listeners.add(l); return () => listeners.delete(l); };
    const api = { setState, getState, subscribe };
    state = config(setState, getState, api);
    return (selector) => {
        const [, forceUpdate] = React.useReducer((c) => c + 1, 0);
        React.useEffect(() => subscribe(() => forceUpdate()), []);
        return selector ? selector(state) : state;
    };
};

const useRadarStore = createStore((set) => ({
    calls: {},
    isOffline: true,
    setOffline: (v) => set({ isOffline: v }),
    setInitialState: (calls) => set({ calls, isOffline: false }),
    upsertCall: (call) => set((state) => ({ 
        calls: { ...state.calls, [call.id]: { ...(state.calls[call.id] || {}), ...call } } 
    })),
    removeCall: (id) => set((state) => {
        const next = { ...state.calls };
        delete next[id];
        return { calls: next };
    })
}));

// ─────────────────────────────────────────────
// LOGIN
// ─────────────────────────────────────────────
function Login({ onLogin }) {
    const [role, setRole] = useState('admin');
    const [user, setUser] = useState('');
    const [pass, setPass] = useState('');
    const [callbackExt, setCallbackExt] = useState('');
    const [err, setErr] = useState('');
    const [loading, setLoading] = useState(false);
    const [showPass, setShowPass] = useState(false);

    const submit = async (e) => {
        e.preventDefault(); setErr(''); setLoading(true);
        try {
            if (role === 'admin') {
                const fd = new FormData();
                fd.append('username', user); fd.append('password', pass);
                const r = await fetch('api/index.php?action=login', { method:'POST', body:fd, credentials:'include' });
                const d = await r.json();
                if (d.status === 'success') onLogin({ name: d.user, role: 'admin' });
                else setErr('Credenciales incorrectas.');
            } else {
                const fd = new FormData();
                fd.append('agent_number', user);
                fd.append('password', pass);
                fd.append('callback_extension', callbackExt);
                const r = await fetch('api/agent.php?action=login', { method:'POST', body:fd, credentials:'include' });
                const d = await r.json();
                if (d.status === 'success') onLogin({ name: d.agent.name, role: 'agent', agent: d.agent });
                else setErr(d.message || 'Login de agente falló.');
            }
        } catch { setErr('Error de conexión con el servidor.'); }
        setLoading(false);
    };

    return (
        <div className="hzn-login-root">
            {/* ───────── RIGHT: imagen Horizon ───────── */}
            <div className="hzn-login-hero">
                <div className="hzn-login-hero-image" style={{backgroundImage:"url('assets/login-bg.jpg')"}}/>
                <div className="hzn-login-hero-overlay"/>
                <div className="hzn-login-hero-content">
                    <div className="hzn-logo">
                        <div>
                            <div className="hzn-logo-text">HORIZON</div>
                            <div className="hzn-logo-sub">SEGURIDAD</div>
                        </div>
                        <div className="hzn-logo-mark">
                            <svg viewBox="0 0 100 100" width="36" height="36">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="var(--horizon-green)" strokeWidth="4"/>
                                <path d="M 10 50 A 40 40 0 0 1 90 50" fill="var(--horizon-green)"/>
                            </svg>
                        </div>
                    </div>
                    {role === 'admin' ? (
                        <div className="hzn-login-tagline">
                            <span className="hzn-role-pill"><span className="material-icons-round">shield</span> Acceso administrador</span>
                            <h1>Panel de control unificado</h1>
                            <p>Gestioná extensiones, colas, agentes y reportes desde un único lugar. Monitoreo en tiempo real del callcenter y la PBX.</p>
                            <ul className="hzn-feature-list">
                                <li><span className="material-icons-round">dashboard</span><div><strong>Dashboard live</strong><span>KPIs y signos vitales del PBX en vivo</span></div></li>
                                <li><span className="material-icons-round">support_agent</span><div><strong>Hotdesking dinámico</strong><span>Login/logout de agentes sin reaprovisionar SIP</span></div></li>
                                <li><span className="material-icons-round">analytics</span><div><strong>Reportes detallados</strong><span>Por agente, cola y CDR · export PDF/Excel</span></div></li>
                                <li><span className="material-icons-round">sensors</span><div><strong>Monitoreo real-time</strong><span>Eventos AMI persistidos vía socket.io</span></div></li>
                            </ul>
                        </div>
                    ) : (
                        <div className="hzn-login-tagline">
                            <span className="hzn-role-pill" data-variant="agent"><span className="material-icons-round">headset_mic</span> Portal del agente</span>
                            <h1>Tu consola de trabajo</h1>
                            <p>Iniciá sesión con tu número de agente y la extensión del teléfono donde vas a recibir las llamadas hoy.</p>
                            <ul className="hzn-feature-list">
                                <li><span className="material-icons-round">badge</span><div><strong>Número de agente</strong><span>El asignado en Issabel (ej. 200)</span></div></li>
                                <li><span className="material-icons-round">vpn_key</span><div><strong>Contraseña personal</strong><span>La definida en tu perfil del callcenter</span></div></li>
                                <li><span className="material-icons-round">phone_in_talk</span><div><strong>Extensión callback</strong><span>El teléfono donde vas a operar hoy (ej. 9006)</span></div></li>
                                <li><span className="material-icons-round">dialpad</span><div><strong>Alternativa por teléfono</strong><span>Marcá *7700 desde tu interno para login + cola</span></div></li>
                            </ul>
                        </div>
                    )}
                </div>
            </div>

            {/* ───────── LEFT: form (área dominante) ───────── */}
            <div className="hzn-login-form-wrap">
                <div className="hzn-login-form-inner">
                    {/* Logo mobile (visible solo en mobile) */}
                    <div className="hzn-login-mobile-logo">
                        <svg viewBox="0 0 100 100" width="44" height="44">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--horizon-green)" strokeWidth="4"/>
                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="var(--horizon-green)"/>
                        </svg>
                        <div className="hzn-logo-text-mobile">HORIZON</div>
                    </div>

                    <div className="hzn-login-heading">
                        <h2>Bienvenido</h2>
                        <p>Iniciá sesión para acceder al panel de control</p>
                    </div>

                    {/* Toggle Admin / Agente */}
                    <div className="hzn-role-tabs" role="tablist">
                        <button type="button" role="tab" aria-selected={role==='admin'} onClick={()=>setRole('admin')} className={role==='admin'?'active':''}>
                            <span className="material-icons-round">shield</span>
                            <span>Administrador</span>
                        </button>
                        <button type="button" role="tab" aria-selected={role==='agent'} onClick={()=>setRole('agent')} className={role==='agent'?'active':''}>
                            <span className="material-icons-round">support_agent</span>
                            <span>Agente</span>
                        </button>
                    </div>

                    <form onSubmit={submit} className="hzn-form">
                        <label className="hzn-field">
                            <span className="hzn-label">{role==='agent' ? 'Número de agente' : 'Usuario'}</span>
                            <div className="hzn-input-wrap">
                                <span className="material-icons-round hzn-input-icon">{role==='agent'?'badge':'person'}</span>
                                <input
                                    className="hzn-input"
                                    type="text"
                                    placeholder={role==='agent' ? 'ej. 200' : 'admin'}
                                    value={user}
                                    onChange={e=>setUser(e.target.value)}
                                    autoComplete="username"
                                    required
                                    autoFocus
                                />
                            </div>
                        </label>

                        <label className="hzn-field">
                            <span className="hzn-label">Contraseña</span>
                            <div className="hzn-input-wrap">
                                <span className="material-icons-round hzn-input-icon">lock</span>
                                <input
                                    className="hzn-input pr-12"
                                    type={showPass?'text':'password'}
                                    placeholder="••••••••"
                                    value={pass}
                                    onChange={e=>setPass(e.target.value)}
                                    autoComplete="current-password"
                                    required
                                />
                                <button type="button" onClick={()=>setShowPass(!showPass)} className="hzn-input-toggle" aria-label="Toggle password visibility">
                                    <span className="material-icons-round">{showPass?'visibility_off':'visibility'}</span>
                                </button>
                            </div>
                        </label>

                        {role === 'agent' && (
                            <label className="hzn-field">
                                <span className="hzn-label">Extensión de callback</span>
                                <div className="hzn-input-wrap">
                                    <span className="material-icons-round hzn-input-icon">phone_in_talk</span>
                                    <input
                                        className="hzn-input"
                                        type="text"
                                        placeholder="ej. 9006"
                                        value={callbackExt}
                                        onChange={e=>setCallbackExt(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="hzn-field-help">El teléfono donde recibirás las llamadas hoy</div>
                            </label>
                        )}

                        {err && (
                            <div className="hzn-alert" role="alert">
                                <span className="material-icons-round">error_outline</span>
                                <span>{err}</span>
                            </div>
                        )}

                        <button type="submit" className="hzn-btn-primary" disabled={loading}>
                            {loading
                                ? <>
                                    <span className="material-icons-round" style={{animation:'spin 1s linear infinite'}}>autorenew</span>
                                    Verificando…
                                  </>
                                : <>
                                    <span>Iniciar sesión</span>
                                    <span className="material-icons-round">arrow_forward</span>
                                  </>}
                        </button>
                    </form>

                    <div className="hzn-login-footer">
                        <span>TeleFlow v18</span>
                        <span className="hzn-dot">·</span>
                        <span>© Infratec {new Date().getFullYear()}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}

const SID = {success:{bg:'linear-gradient(135deg,#052e16,#14532d)',border:'#166534',ic:'check_circle',color:'#4ade80'},error:{bg:'linear-gradient(135deg,#450a0a,#7f1d1d)',border:'#991b1b',ic:'cancel',color:'#f87171'},warning:{bg:'linear-gradient(135deg,#431407,#7c2d12)',border:'#9a3412',ic:'warning',color:'#fb923c'},info:{bg:'linear-gradient(135deg,#0c1445,#1e1b4b)',border:'#3730a3',ic:'info',color:'#a5b4fc'},call:{bg:'linear-gradient(135deg,#450a0a,#7f1d1d)',border:'#dc2626',ic:'call',color:'#fca5a5'}};
function Toast({ toasts, remove }) {
    return (
        <div style={{position:'fixed',bottom:24,right:24,zIndex:9999,display:'flex',flexDirection:'column-reverse',gap:8,maxWidth:340}}>
            {toasts.map(t=>{
                const s=SID[t.type]||SID.info;
                return(
                    <div key={t.id} style={{background:s.bg,border:`1px solid ${s.border}`,borderRadius:16,padding:'14px 16px',display:'flex',alignItems:'flex-start',gap:12,boxShadow:'0 20px 60px rgba(0,0,0,.8),0 0 0 1px rgba(255,255,255,.05)',animation:'toastIn .35s cubic-bezier(.175,.885,.32,1.275)',minWidth:280,backdropFilter:'blur(20px)'}}>
                        <div style={{width:36,height:36,borderRadius:10,background:`${s.color}20`,border:`1px solid ${s.color}40`,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                            <span className="material-icons-round" style={{fontSize:20,color:s.color}}>{s.ic}</span>
                        </div>
                        <div style={{flex:1,minWidth:0}}>
                            <div style={{fontSize:12,fontWeight:800,color:s.color,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:3}}>{t.type==='call'?'📞 Llamada Entrante':t.type==='success'?'✔ Éxito':t.type==='error'?'✖ Error':t.type==='warning'?'⚡ Aviso':'ℹ Info'}</div>
                            <div style={{fontSize:13,color:'rgba(255,255,255,.85)',lineHeight:1.4,wordBreak:'break-word'}}>{t.msg}</div>
                            {t.sub&&<div style={{fontSize:11,color:'rgba(255,255,255,.45)',marginTop:4}}>{t.sub}</div>}
                        </div>
                        <button onClick={()=>remove(t.id)} style={{background:'none',border:'none',cursor:'pointer',color:'rgba(255,255,255,.35)',padding:0,flexShrink:0,transition:'color .2s'}} onMouseEnter={e=>e.target.style.color='white'} onMouseLeave={e=>e.target.style.color='rgba(255,255,255,.35)'}>
                            <span className="material-icons-round" style={{fontSize:18}}>close</span>
                        </button>
                    </div>
                );
            })}
        </div>
    );
}

// ─────────────────────────────────────────────
// SIDEBAR
// ─────────────────────────────────────────────
function Sidebar({ view, setView, user, onLogout, collapsed, setCollapsed, darkMode, setDarkMode, data, activeCalls }) {
    const [showUserMenu, setShowUserMenu] = useState(false);
    const extsOnline = data?.pbx?.extensions?.filter(e=>e.status==='ONLINE')?.length || 0;
    const qWaiting = data?.pbx?.queues?.reduce((acc, q) => acc + (q.calls_waiting || 0), 0) || 0;

    const toggleTheme = (e) => {
        e.stopPropagation();
        document.body.classList.add('theme-transition');
        setDarkMode(!darkMode);
        setTimeout(() => document.body.classList.remove('theme-transition'), 500);
    };

    // HORIZON: badges para agentes logueados + grupos sonando
    const agentsLogged = (data?._agentsLogged ?? 0);
    const groupsRinging = (data?.pbx?.live_calls || []).filter(c => 
        (c.context || '').includes('macro-dial-one') || /grp/i.test(c.dest || '')
    ).length;

    const nav = [
        { section: 'Principal' },
        { id:'dashboard', icon:'grid_view', label:'Dashboard' },
        { id:'extensiones', icon:'group', label:'Extensiones', badge: extsOnline, badgeColor: '#22c55e' },
        { id:'agentes', icon:'support_agent', label:'Agentes', badge: agentsLogged, badgeColor: '#22c55e' },
        { section: 'Call Center' },
        { id:'callcenter', icon:'headset_mic', label:'Mi Consola' },
        { id:'hotdesking', icon:'phonelink_setup', label:'Hotdesking' },
        { id:'vivo', icon:'sensors', label:'Llamas en Vivo', badge: activeCalls, badgeColor: '#ef4444' },
        { id:'colas', icon:'queue', label:'Colas', badge: qWaiting, badgeColor: '#f59e0b' },
        { id:'grupos', icon:'ring_volume', label:'Grupos', badge: groupsRinging, badgeColor: '#f59e0b' },
        { id:'ivr', icon:'account_tree', label:'IVR' },
        { id:'radar', icon:'radar', label:'Tráfico', icColor: '#3b82f6' },
        { section: 'Herramientas' },
        { id:'cdr', icon:'history', label:'CDR' },
        { id:'reportes', icon:'analytics', label:'Reportes' },
        { id:'configuracion', icon:'settings', label:'Configuración' },
    ];

    return (
        <div className={`sidebar${collapsed?' collapsed':''} ${!collapsed && window.innerWidth < 768 ? 'mobile-open' : ''}`} style={{ position: 'relative' }}>
            <div className="sidebar-logo" style={{display:'flex',alignItems:'center',gap:10,padding:collapsed?'18px 0':'20px 14px 14px',justifyContent:collapsed?'center':'flex-start'}}>
                <div style={{width:32,height:32,background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',borderRadius:9,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,cursor:'pointer'}} onClick={()=>setCollapsed(!collapsed)}>
                    <span className="material-icons-round" style={{fontSize:16,color:'white'}}>{collapsed?'chevron_right':'sensors'}</span>
                </div>
                {!collapsed&&<div className="sidebar-logo-text"><div style={{fontSize:14,fontWeight:800,color:'var(--text)',letterSpacing:-0.5,fontStyle:'italic'}}>TeleFlow</div><div style={{fontSize:9,fontWeight:600,color:'#6b7280',letterSpacing:'0.1em',textTransform:'uppercase'}}>PBX Control</div></div>}
            </div>
            
            <div className="sidebar-nav">
                {nav.map((item,i)=> item.section
                    ? (!collapsed&&<div key={i} className="nav-section">{item.section}</div>)
                    : <div key={item.id} className={`nav-item${view===item.id?' active':''}`} onClick={()=>setView(item.id)} title={item.label} style={{position:'relative'}}>
                        <span className="material-icons-round">{item.icon}</span>
                        {!collapsed&&<span className="nav-label" style={{flex:1}}>{item.label}</span>}
                        {item.badge > 0 && (
                            <div style={{
                                background: item.badgeColor || 'var(--accent)',
                                color: 'white',
                                fontSize: '10px',
                                fontWeight: 800,
                                padding: '2px 6px',
                                borderRadius: '10px',
                                minWidth: '18px',
                                textAlign: 'center',
                                boxShadow: `0 0 10px ${item.badgeColor}40`,
                                animation: 'viewIn .3s ease'
                            }}>
                                {item.badge}
                            </div>
                        )}
                      </div>
                )}
            </div>

            <div className="sidebar-bottom">


                {/* Context Menu */}
                {showUserMenu && (
                    <div className="context-menu" style={{ left: collapsed ? '65px' : '10px', bottom: '60px' }}>
                        <div className="context-menu-title" style={{padding:'8px 12px', fontSize:10, fontWeight:800, color:'#6b7280', textTransform:'uppercase', letterSpacing:'0.1em'}}>Cuenta</div>
                        <div className="context-menu-item" onClick={() => { setView('configuracion'); setShowUserMenu(false); }}>
                            <span className="material-icons-round">settings</span>Configuración
                        </div>
                        <div className="context-menu-item" onClick={() => { toggleTheme(); setShowUserMenu(false); }}>
                            <span className="material-icons-round">{darkMode?'light_mode':'dark_mode'}</span>Modo {darkMode?'Claro':'Oscuro'}
                        </div>
                        <div className="context-menu-item" onClick={() => { setShowUserMenu(false); }}>
                            <span className="material-icons-round">vpn_key</span>Cambiar Clave
                        </div>
                        <div style={{height:1, background:'var(--border)', margin:'4px 8px'}} />
                        <div className="context-menu-item danger" onClick={onLogout}>
                            <span className="material-icons-round">logout</span>Cerrar Sesión
                        </div>
                    </div>
                )}

                <div 
                    className="flex items-center p-2 rounded-2xl bg-white/5 hover:bg-white/10 transition-all border border-transparent hover:border-purple-500/20 group relative cursor-pointer" 
                    style={{gap:collapsed?0:10, justifyContent:collapsed?'center':'flex-start'}}
                    onClick={() => setShowUserMenu(!showUserMenu)}
                >
                    <div 
                        className="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-black text-white shadow-lg shadow-purple-500/20 transform group-hover:scale-105 transition-transform"
                        style={{background:'linear-gradient(135deg,#8b5cf6,#6d28d9)', flexShrink:0}}
                    >
                        {initials(user)}
                    </div>
                    
                    {!collapsed && (
                        <div style={{flex:1, minWidth:0}}>
                            <div style={{fontSize:13, fontWeight:800, color:'var(--text)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{(typeof user === 'string') ? user : ((user && typeof user === 'object') ? (user.name || user.agent_name || user.tf_user || 'Usuario') : 'Cargando…')}</div>
                            <div style={{fontSize:9, color:'#22c55e', display:'flex', alignItems:'center', gap:3, fontWeight:700, textTransform:'uppercase', letterSpacing:'0.5px'}}>
                                <span style={{width:4,height:4,borderRadius:'50%',background:'#22c55e',display:'inline-block'}}/>Conectado
                            </div>
                        </div>
                    )}

                    {!collapsed && (
                        <div className="text-gray-500 group-hover:text-white transition-colors">
                            <span className="material-icons-round">unfold_more</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// COMPONENTE: NOTIFICACIONES SLEEK (SILEO)
// ─────────────────────────────────────────────
/*
function LiveCallNotifications({ calls, extensions }) {
    const [notifs, setNotifs] = useState([]);
    const prevCalls = useRef([]);

    useEffect(() => {
        const newCalls = calls.filter(c => !prevCalls.current.some(pc => pc.ext === c.ext));
        if (newCalls.length > 0) {
            newCalls.forEach(c => {
                const extInfo = extensions.find(e => e.ext === c.ext);
                const id = Date.now() + Math.random();
                setNotifs(n => [...n, { id, ext: c.ext, name: extInfo?.name || 'Desconocido', avatar: extInfo?.avatar }]);
                setTimeout(() => setNotifs(n => n.filter(x => x.id !== id)), 5000);
            });
        }
        prevCalls.current = calls;
    }, [calls, extensions]);

    return (
        <div style={{position:'fixed', top:20, right:20, zIndex:9999, display:'flex', flexDirection:'column', gap:10}}>
            {notifs.map(n => (
                <div key={n.id} className="glass glass-hover" style={{
                    width:280, padding:14, borderRadius:18, display:'flex', alignItems:'center', gap:12, 
                    border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)', background:'rgba(15,15,25,0.9)', backdropFilter:'blur(20px)',
                    animation:'slideInRight 0.5s cubic-bezier(0.16, 1, 0.3, 1), fadeOut 0.5s 4.5s forwards'
                }}>
                    <div style={{position:'relative'}}>
                        <img src={n.avatar} style={{width:40,height:40,borderRadius:12,objectFit:'cover'}} />
                        <div style={{position:'absolute',bottom:-4,right:-4,width:18,height:18,background:'#ef4444',borderRadius:'50%',display:'flex',alignItems:'center',justifyContent:'center',border:'2px solid #0f0f19'}}>
                             <span className="material-icons-round" style={{fontSize:10,color:'white'}}>call</span>
                        </div>
                    </div>
                    <div style={{flex:1}}>
                        <div style={{fontSize:13,fontWeight:800,color:'white'}}>{n.name}</div>
                        <div style={{fontSize:10,color:'#3b82f6',fontWeight:700,letterSpacing:'0.5px'}}>LLAMADA EN VIVO • #{n.ext}</div>
                    </div>
                    <div className="call-pulse" style={{width:8,height:8,borderRadius:'50%',background:'#ef4444',boxShadow:'0 0 8px #ef4444'}} />
                </div>
            ))}
        </div>
    );
}
*/

// ─────────────────────────────────────────────
// COMPONENTE: TOPBAR (PARA REFERENCIA, PERO ELIMINADO DEL LAYOUT)
// ─────────────────────────────────────────────
function Topbar({ view, data, onRefresh, setCollapsed }) {
    const titles = { 
        dashboard:'Dashboard General', 
        extensiones:'Gestión de Ext.', 
        agentes:'Panel Agentes', 
        vivo:'Llamadas en Vivo', 
        colas:'Colas', 
        grabaciones:'Grabaciones', 
        cdr:'CDR / Historial', 
        configuracion: 'Configuración',
        webphone: 'Softphone Cloud'
    };
    const [time, setTime] = useState(new Date());
    useEffect(()=>{ const t=setInterval(()=>setTime(new Date()),1000); return()=>clearInterval(t); },[]);
    
    return (
        <div className="topbar">
            <div style={{display:'flex', alignItems:'center', gap:12}}>
                <button 
                  className="glass-hover" 
                  onClick={() => setCollapsed(false)}
                  style={{background:'none', border:'none', padding:8, borderRadius:10, cursor:'pointer', color: 'var(--text)', display: window.innerWidth < 768 ? 'flex' : 'none', alignItems:'center', justifyContent:'center'}}
                >
                    <span className="material-icons-round">menu</span>
                </button>
                <div>
                   <h2 style={{fontSize:16,fontWeight:800,color:'var(--text)', letterSpacing:'-0.5px'}}>{titles[view]||view}</h2>
                   <div style={{fontSize:9,color:'#6b7280',marginTop:1,textTransform:'uppercase',fontWeight:800,letterSpacing:'0.05em'}}>{time.toLocaleDateString('es-UY',{day:'numeric',month:'short'})} · {time.toLocaleTimeString('es-UY',{hour:'2-digit',minute:'2-digit'})}</div>
                </div>
            </div>
            <div style={{display:'flex',alignItems:'center',gap:10}}>
                <button onClick={onRefresh} style={{width:34,height:34,borderRadius:9,background:'var(--surface2)',border:'1px solid var(--border)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',color:'#9ca3af',transition:'all .2s'}}>
                    <span className="material-icons-round" style={{fontSize:17}}>refresh</span>
                </button>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: DASHBOARD
// ─────────────────────────────────────────────
function ViewDashboard({ data }) {
    const [agentsLogged, setAgentsLogged] = useState(0);
    const [agentsAvail, setAgentsAvail] = useState(0);
    const [agentsBusy, setAgentsBusy] = useState(0);
    const [todayStats, setTodayStats] = useState(null);

    useEffect(() => {
        const fetchAg = () => {
            fetch('api/hotdesking.php?action=list',{credentials:'include'})
                .then(r=>r.json()).then(j=>{
                    if (j.status==='ok') {
                        const ags = j.agents||[];
                        setAgentsLogged(ags.filter(a=>a.logged_in).length);
                        setAgentsAvail(ags.filter(a=>a.logged_in && !a.in_call && !a.paused).length);
                        setAgentsBusy(ags.filter(a=>a.in_call).length);
                        // setAgentsPaused podría agregarse si hay estado para ello
                    }
                }).catch(()=>{});
        };
        fetchAg();
        const t = setInterval(fetchAg, 5000);
        return () => clearInterval(t);
    }, []);

    useEffect(() => {
        const today = new Date().toISOString().split('T')[0];
        fetch(`api/index.php?action=get_reports&start=${today}&end=${today}`)
            .then(r=>r.json()).then(d=>{ if (d.success) setTodayStats(d.stats); })
            .catch(()=>{});
    }, []);

    const exts = data?.pbx?.extensions || [];
    const online = exts.filter(e=>e.status==='ONLINE').length;
    const busy = exts.filter(e=>e.status==='BUSY').length;
    const queues = data?.pbx?.queues || [];
    const liveCalls = data?.pbx?.live_calls || [];
    const upCalls = liveCalls.filter(c => c.state === 'Up').length;
    const ringingCalls = liveCalls.filter(c => /Ring/.test(c.state||'')).length;
    const totalWaiting = queues.reduce((s,q) => s + (q.calls_waiting||0), 0);
    const cpu = data?.system?.cpu || 0;
    const ram = data?.system?.ram || 0;
    const disk = data?.system?.disk || 0;
    const conn = data?.system?.connections || 0;
    const uptime = data?.system?.uptime || 'Desconocido';
    const ts = todayStats || {};
    const todayTotal = ts.total || 0;
    const todayAns = ts.answered || 0;
    const eff = todayTotal > 0 ? Math.round((todayAns/todayTotal)*100) : 0;

    // HORIZON: grabaciones recientes para el panel "Últimas Grabaciones"
    const recs = data?.pbx?.recordings || [];

    // HORIZON: signos vitales del servidor PBX (CPU/RAM/Disco/Conexiones)
    const systemStats = [
        { label: 'CPU',         val: `${cpu}%`,  icon: 'memory',     bg: 'rgba(59,130,246,0.12)',  color: '#3b82f6' },
        { label: 'RAM',         val: `${ram}%`,  icon: 'memory',     bg: 'rgba(139,92,246,0.12)',  color:'var(--primary)' },
        { label: 'Disco',       val: `${disk}%`, icon: 'storage',    bg: 'rgba(245,158,11,0.12)',  color: '#f59e0b' },
        { label: 'Conexiones',  val: conn,       icon: 'cable',      bg: 'rgba(34,197,94,0.12)',   color: '#22c55e' },
    ];

    const KPIBig = ({label, value, sub, icon, color}) => (
        <div className="rounded-lg border bg-card text-card-foreground p-5 relative overflow-hidden transition-colors hover:bg-muted/30" style={{borderColor:'var(--border)'}}>
            <div style={{position:'absolute',top:-20,right:-20,width:80,height:80,borderRadius:'50%',background:`radial-gradient(circle,${color}22,transparent 70%)`,pointerEvents:'none'}}/>
            <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:10,position:'relative'}}>
                <div style={{width:36,height:36,borderRadius:9,background:`linear-gradient(135deg,${color},${color}cc)`,display:'flex',alignItems:'center',justifyContent:'center',boxShadow:`0 2px 8px ${color}40`}}>
                    <span className="material-icons-round" style={{color:'#fff',fontSize:18}}>{icon}</span>
                </div>
                <span className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>{label}</span>
            </div>
            <div className="text-3xl font-black leading-none tracking-tight" style={{color,fontVariantNumeric:'tabular-nums'}}>{value}</div>
            {sub && <div className="text-[11px] font-semibold mt-2" style={{color:'var(--muted-foreground)'}}>{sub}</div>}
        </div>
    );

    return (
        <div className="content-area">
            {/* Hero — pulse del callcenter */}
            <div className="anim-fadeup rounded-xl border bg-card p-5 mb-4 flex flex-wrap items-center gap-4 relative overflow-hidden" style={{borderColor:'var(--border)'}}>
                <div style={{position:'absolute',top:-30,right:-30,width:200,height:200,borderRadius:'50%',background:'radial-gradient(circle, color-mix(in srgb, var(--primary) 18%, transparent), transparent 70%)',pointerEvents:'none'}}/>
                <div style={{width:60,height:60,borderRadius:14,background:'linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #3b82f6))',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:'0 4px 16px color-mix(in srgb, var(--primary) 35%, transparent)',position:'relative',zIndex:1}}>
                    <span className="material-icons-round" style={{color:'#fff',fontSize:30}}>insights</span>
                    <span style={{position:'absolute',top:-3,right:-3,width:12,height:12,borderRadius:'50%',background:'#22c55e',border:'3px solid var(--surface)',boxShadow:'0 0 10px #22c55e'}}/>
                </div>
                <div style={{flex:1,minWidth:240}}>
                    <div style={{fontSize:11,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.12em',display:'flex',alignItems:'center',gap:6}}>
                        <span style={{width:6,height:6,borderRadius:'50%',background:'#22c55e',animation:'pulse 2s infinite',boxShadow:'0 0 6px #22c55e'}}/>EN VIVO · {new Date().toLocaleTimeString('es-UY',{hour:'2-digit',minute:'2-digit'})}
                    </div>
                    <div style={{fontSize:24,fontWeight:900,letterSpacing:'-0.6px',marginTop:3,color:'var(--text)'}}>TeleFlow Operations</div>
                    <div style={{fontSize:12,color:'var(--muted)',marginTop:3}}>{agentsLogged} agentes activos · {upCalls + ringingCalls} canales en uso · {totalWaiting > 0 ? `${totalWaiting} en espera` : 'sin espera'}</div>
                </div>
                <div style={{display:'flex',gap:14,alignItems:'center'}}>
                    <div style={{textAlign:'right'}}>
                        <div style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>Hoy</div>
                        <div style={{fontSize:22,fontWeight:900,color:'#22c55e'}}>{todayAns.toLocaleString()}</div>
                        <div style={{fontSize:10,color:'var(--muted)',fontWeight:700}}>de {todayTotal.toLocaleString()} ({eff}%)</div>
                    </div>
                </div>
            </div>

            {/* 4 KPI cards principales */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(200px,1fr))',gap:12,marginBottom:14}}>
                <KPIBig label="Agentes activos" value={`${agentsLogged}/${agentsLogged > 0 ? agentsLogged : '—'}`} sub={`${agentsAvail} disponibles · ${agentsBusy} en llamada`} icon="support_agent" color="#22c55e"/>
                <KPIBig label="Llamadas en curso" value={upCalls} sub={`${ringingCalls} sonando`} icon="phone_in_talk" color="#3b82f6"/>
                <KPIBig label="En espera" value={totalWaiting} sub={`${queues.length} colas configuradas`} icon="hourglass_top" color={totalWaiting>0?'#ef4444':'#f59e0b'}/>
                <KPIBig label="Extensiones online" value={`${online}/${exts.length}`} sub={`${busy} en llamada`} icon="dialpad" color="#8b5cf6"/>
            </div>

            {/* 3 columnas: Colas activas | Cards en llamada | Sistema */}
            <div style={{display:'grid',gridTemplateColumns:'1.4fr 1fr',gap:14,marginBottom:14}}>
                <div className="glass" style={{padding:'18px 22px',borderRadius:16}}>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:14}}>
                        <div style={{display:'flex',alignItems:'center',gap:8}}>
                            <span className="material-icons-round" style={{fontSize:18,color:'#f59e0b'}}>queue</span>
                            <span style={{fontSize:13,fontWeight:800,color:'var(--text)',textTransform:'uppercase',letterSpacing:'.08em'}}>Colas en vivo</span>
                        </div>
                        <span style={{fontSize:10,color:'var(--muted)',fontWeight:700,fontFamily:'monospace'}}>{queues.length} colas</span>
                    </div>
                    {queues.length === 0 ? <div style={{padding:30,textAlign:'center',color:'var(--muted)',fontSize:11}}>Sin colas configuradas</div> : (
                        <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(140px,1fr))',gap:8}}>
                            {queues.slice(0,12).map(q => {
                                const w = q.calls_waiting || 0;
                                const c = w > 5 ? '#ef4444' : (w > 0 ? '#f59e0b' : '#22c55e');
                                return (
                                    <div key={q.id} style={{padding:'10px 12px',borderRadius:10,background:'var(--surface2)',border:`1px solid ${c}33`,position:'relative'}}>
                                        <div style={{fontSize:10,fontWeight:700,color:'var(--muted)',fontFamily:'monospace'}}>Q{q.id}</div>
                                        <div style={{fontSize:11,fontWeight:600,color:'var(--text)',marginTop:1,whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{q.name||'—'}</div>
                                        <div style={{display:'flex',alignItems:'baseline',gap:4,marginTop:6}}>
                                            <span style={{fontSize:18,fontWeight:900,color:c,lineHeight:1}}>{w}</span>
                                            <span style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>en espera</span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <div className="glass" style={{padding:'18px 22px',borderRadius:16}}>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:14}}>
                        <div style={{display:'flex',alignItems:'center',gap:8}}>
                            <span className="material-icons-round" style={{fontSize:18,color:'#22c55e'}}>sensors</span>
                            <span style={{fontSize:13,fontWeight:800,color:'var(--text)',textTransform:'uppercase',letterSpacing:'.08em'}}>Llamadas activas</span>
                        </div>
                        <span style={{fontSize:10,color:'var(--muted)',fontWeight:700,fontFamily:'monospace'}}>{liveCalls.length} canales</span>
                    </div>
                    {liveCalls.length === 0 ? <div style={{padding:30,textAlign:'center',color:'var(--muted)',fontSize:11}}><span className="material-icons-round" style={{fontSize:36,color:'var(--muted)',display:'block',marginBottom:6}}>phone_disabled</span>Sin llamadas en curso</div> : (
                        <div style={{display:'flex',flexDirection:'column',gap:6,maxHeight:280,overflowY:'auto'}}>
                            {liveCalls.slice(0,10).map((c,i) => {
                                const isUp = c.state === 'Up';
                                const isRing = /Ring/.test(c.state||'');
                                const sc = isUp?'#22c55e':(isRing?'#f59e0b':'#6b7280');
                                return (
                                    <div key={i} style={{padding:'8px 10px',borderRadius:9,background:'var(--surface2)',border:`1px solid ${sc}33`,display:'flex',alignItems:'center',gap:8}}>
                                        <span style={{width:8,height:8,borderRadius:'50%',background:sc,animation:isRing?'pulse 1s infinite':'none',boxShadow:`0 0 8px ${sc}cc`,flexShrink:0}}/>
                                        <div style={{flex:1,minWidth:0}}>
                                            <div style={{fontSize:11,fontFamily:'monospace',fontWeight:700,color:'var(--text)',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{c.ext||c.callerid||'?'} → {c.dest||'?'}</div>
                                            <div style={{fontSize:9,color:'var(--muted)',fontFamily:'monospace'}}>{c.duration||'00:00'} · {isUp?'En conversación':(isRing?'Sonando':c.state)}</div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* Salud del sistema PBX */}
            <div className="glass" style={{padding:'18px 22px',borderRadius:16,marginBottom:14}}>
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:14}}>
                    <div style={{display:'flex',alignItems:'center',gap:8}}>
                        <span className="material-icons-round" style={{fontSize:18,color:'#06b6d4'}}>monitor_heart</span>
                        <span style={{fontSize:13,fontWeight:800,color:'var(--text)',textTransform:'uppercase',letterSpacing:'.08em'}}>Salud del PBX</span>
                    </div>
                    <div style={{fontSize:10,color:'var(--muted)',fontWeight:700,fontFamily:'monospace',padding:'4px 10px',borderRadius:7,background:'var(--surface2)'}}>Uptime · {uptime}</div>
                </div>
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(160px,1fr))',gap:10}}>
                    {[
                        {l:'CPU',v:cpu,u:'%',c:cpu>80?'#ef4444':(cpu>50?'#f59e0b':'#22c55e'),i:'memory'},
                        {l:'RAM',v:ram,u:'%',c:ram>80?'#ef4444':(ram>50?'#f59e0b':'#22c55e'),i:'sd_storage'},
                        {l:'Disco',v:disk,u:'%',c:disk>80?'#ef4444':(disk>50?'#f59e0b':'#22c55e'),i:'storage'},
                        {l:'Conexiones',v:conn,u:'',c:'#06b6d4',i:'lan'},
                    ].map((m,i)=>(
                        <div key={i} style={{padding:'10px 12px',borderRadius:10,background:'var(--surface2)',border:`1px solid ${m.c}33`}}>
                            <div style={{display:'flex',alignItems:'center',gap:6,marginBottom:6}}>
                                <span className="material-icons-round" style={{fontSize:14,color:m.c}}>{m.i}</span>
                                <span style={{fontSize:10,fontWeight:700,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.05em'}}>{m.l}</span>
                            </div>
                            <div style={{fontSize:18,fontWeight:900,color:m.c,fontFamily:'monospace'}}>{m.v}{m.u}</div>
                            {m.u==='%' && (
                                <div style={{height:4,background:'var(--border)',borderRadius:2,marginTop:6,overflow:'hidden'}}>
                                    <div style={{height:'100%',width:`${m.v}%`,background:m.c,borderRadius:2,transition:'width .5s'}}/>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
            
            {/* System Status */}
            <div className="glass" style={{padding:'20px', marginBottom:24}}>
                <div style={{fontSize:13,fontWeight:700,color:'var(--text)',marginBottom:16,display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                    <div style={{display:'flex',alignItems:'center',gap:8}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#3b82f6'}}>dns</span>
                        Signos Vitales del Servidor PBX
                    </div>
                    <div style={{fontSize:12,color:'#6b7280',backgroundColor:'var(--surface)',padding:'4px 12px',borderRadius:20,border:'1px solid var(--border)'}}>
                        Uptime: {uptime}
                    </div>
                </div>
                <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:16}}>
                    {systemStats.map(s=>(
                        <div key={s.label} style={{display:'flex',alignItems:'center',gap:14}}>
                            <div style={{width:40,height:40,borderRadius:12,background:s.bg,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:20,color:s.color}}>{s.icon}</span>
                            </div>
                            <div>
                                <div style={{fontSize:11,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.05em',fontWeight:700}}>{s.label}</div>
                                <div style={{fontSize:18,fontWeight:800,color:'var(--text)'}}>{s.val}</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div style={{display:'grid',gridTemplateColumns:'1.6fr 1fr',gap:16}}>
                {/* Extensiones activas */}
                <div className="glass" style={{padding:'20px'}}>
                    <div style={{fontSize:13,fontWeight:700,color:'white',marginBottom:16,display:'flex',alignItems:'center',gap:8}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'var(--primary)'}}>group</span>
                        Extensiones Activas
                    </div>
                    <div style={{display:'flex',flexDirection:'column',gap:8}}>
                        {exts.slice(0,6).map(ext=>(
                            <div key={ext.ext} style={{display:'flex',alignItems:'center',gap:12,padding:'8px 0',borderBottom:'1px solid var(--border)'}}>
                                <img src={ext.avatar} style={{width:32,height:32,borderRadius:8,objectFit:'cover'}} onError={e=>{e.target.style.display='none'}} />
                                <div style={{flex:1}}>
                                    <div style={{fontSize:12,fontWeight:600,color:'white'}}>#{ext.ext} <span style={{color:'#9ca3af',fontWeight:400}}>{ext.name}</span></div>
                                    <div style={{fontSize:10,color:'#6b7280'}}>{ext.ip}</div>
                                </div>
                                <span className={`badge ${ext.status==='ONLINE'?'badge-online':ext.status==='BUSY'?'badge-busy':'badge-offline'}`}>
                                    <span className={`badge-dot ${ext.status==='ONLINE'?'dot-online':ext.status==='BUSY'?'dot-busy':'dot-offline'}`} />
                                    {ext.status}
                                </span>
                            </div>
                        ))}
                        {exts.length===0 && <div style={{color:'#6b7280',fontSize:13,textAlign:'center',padding:20}}>Sin datos de extensiones</div>}
                    </div>
                </div>

                {/* Últimas grabaciones */}
                <div className="glass" style={{padding:'20px'}}>
                    <div style={{fontSize:13,fontWeight:700,color:'white',marginBottom:16,display:'flex',alignItems:'center',gap:8}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'var(--primary)'}}>mic</span>
                        Últimas Grabaciones
                    </div>
                    <div style={{display:'flex',flexDirection:'column',gap:8}}>
                        {recs.slice(0,5).map((r,i)=>(
                            <div key={i} style={{padding:'8px 10px',background:'var(--surface2)',borderRadius:10,border:'1px solid var(--border)'}}>
                                <div style={{fontSize:11,fontWeight:700,color:'white'}}>#{r.src} → {r.dst}</div>
                                <div style={{fontSize:10,color:'#6b7280',display:'flex',justifyContent:'space-between',marginTop:2}}>
                                    <span>{r.calldate?.substring(0,16)}</span>
                                    <span style={{color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>{r.duration}s</span>
                                </div>
                            </div>
                        ))}
                        {recs.length===0 && <div style={{color:'#6b7280',fontSize:13,textAlign:'center',padding:20}}>Sin grabaciones</div>}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: EXTENSIONES (CRUD + grid/tabla)
// ─────────────────────────────────────────────
// No longer used as a floating drawer - ExtEditPage is now a full-page view
// ExtDrawer left as dead code for reference, replaced by ExtEditPage below

// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// COMPONENTE: AvatarUploader (drag&drop + click)
// ─────────────────────────────────────────────
function AvatarUploader({ ext, name, onUploaded, size = 96 }) {
    const fileRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [bust, setBust] = useState(Date.now());
    const [dragOver, setDragOver] = useState(false);
    const [error, setError] = useState('');
    const initials = (name||'').split(' ').map(s=>s[0]).join('').substring(0,2).toUpperCase() || '?';
    const url = ext ? `uploads/avatars/${ext}.jpg?v=${bust}` : null;
    const [imgOk, setImgOk] = useState(true);

    const upload = async (file) => {
        if (!file || !file.type.match(/^image\//)) { setError('Solo imágenes'); return; }
        if (file.size > 2*1024*1024) { setError('Máx 2MB'); return; }
        setError(''); setUploading(true);
        const fd = new FormData();
        fd.append('ext', ext);
        fd.append('avatar', file);
        try {
            const r = await fetch('api/index.php?action=upload_avatar', { method:'POST', body:fd, credentials:'include' });
            const d = await r.json();
            if (d.success) {
                setBust(Date.now());
                setImgOk(true);
                onUploaded && onUploaded(d.url);
            } else {
                setError(d.error || 'Error al subir');
            }
        } catch(e) { setError('Error de red'); }
        setUploading(false);
    };

    // HORIZON: cache "no existe" para no re-pedir avatar 404 cada render
    const knownMissing = (typeof localStorage !== 'undefined') && localStorage.getItem('tf_av_404_'+ext) === '1';
    const effectiveImgOk = imgOk && url && !knownMissing;
    return (
        <div style={{display:'inline-flex', flexDirection:'column', alignItems:'center', gap:6}}>
            <div
                onClick={()=>fileRef.current?.click()}
                onDragOver={e=>{e.preventDefault(); setDragOver(true);}}
                onDragLeave={()=>setDragOver(false)}
                onDrop={e=>{e.preventDefault(); setDragOver(false); upload(e.dataTransfer.files[0]);}}
                style={{
                    width:size, height:size, borderRadius:'50%', cursor:'pointer',
                    background: effectiveImgOk ? `url("${url}") center/cover` : 'linear-gradient(135deg,#8b5cf6,#6d28d9)',
                    border: dragOver ? '2px dashed #c4b5fd' : '2px solid var(--border)',
                    display:'flex', alignItems:'center', justifyContent:'center',
                    color:'#fff', fontWeight:900, fontSize:size*0.32,
                    position:'relative', transition:'all 0.2s',
                    boxShadow:'0 4px 14px rgba(139,92,246,0.2)'
                }}
            >
                {imgOk && url && (
                    <img src={url} alt="" style={{display:'none'}} onError={()=>{setImgOk(false); try{localStorage.setItem('tf_av_404_'+ext,'1');}catch(e){}}} onLoad={()=>{setImgOk(true); try{localStorage.removeItem('tf_av_404_'+ext);}catch(e){}}} />
                )}
                {(!imgOk || !url) && initials}
                {uploading && (
                    <div style={{position:'absolute',inset:0,borderRadius:'50%',background:'rgba(0,0,0,0.5)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:24,animation:'spin 1s linear infinite'}}>autorenew</span>
                    </div>
                )}
                {!uploading && (
                    <div style={{position:'absolute',bottom:0,right:0,width:size*0.32,height:size*0.32,borderRadius:'50%',background:'var(--primary)',display:'flex',alignItems:'center',justifyContent:'center',border:'2px solid var(--surface)',boxShadow:'0 2px 6px rgba(0,0,0,0.3)'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:size*0.18}}>photo_camera</span>
                    </div>
                )}
            </div>
            <input ref={fileRef} type="file" accept="image/*" onChange={e=>upload(e.target.files[0])} style={{display:'none'}} />
            {error && <div style={{fontSize:10,color:'#ef4444'}}>{error}</div>}
            {!error && <div style={{fontSize:9,color:'var(--muted)',textTransform:'uppercase',fontWeight:700,letterSpacing:'.05em'}}>Click o arrastrá imagen</div>}
        </div>
    );
}


// ─── ExtStatusPanel: estado en vivo del interno con preview RTSP y RTT animado ───
function ExtStatusPanel({ ext, form, avatarUrl, ini, statusColor, statusLabel }) {
    // Parse RTT (puede venir como "12ms", "150ms", "—", null, etc.)
    const rttMs = useMemo(() => {
        if (!ext?.rtt || ext.rtt === '—') return null;
        const m = String(ext.rtt).match(/(\d+(?:\.\d+)?)/);
        return m ? parseFloat(m[1]) : null;
    }, [ext?.rtt]);
    // Threshold: <60 verde, 60-150 amarillo, >150 rojo
    const rttGrade = rttMs === null ? null : (rttMs < 60 ? 'good' : (rttMs < 150 ? 'mid' : 'bad'));
    const rttColor = rttGrade === 'good' ? '#22c55e' : (rttGrade === 'mid' ? '#f59e0b' : (rttGrade === 'bad' ? '#ef4444' : 'var(--muted-foreground)'));
    const rttPulseSpeed = rttGrade === 'good' ? '2.4s' : (rttGrade === 'mid' ? '1.4s' : '0.8s');

    const hasRtsp = !!form?.rtsp_url;

    return (
        <div className="flex flex-col gap-3">
            {/* Preview RTSP en vivo (si hay URL) */}
            {hasRtsp && (
                <RtspInlinePreview ext={form.ext} url={form.rtsp_url} label={form.rtsp_label}/>
            )}

            {/* Avatar + nombre + status */}
            <div className="flex flex-col items-center gap-2">
                <div className="relative shrink-0">
                    {form.ext && avatarUrl ? (
                        <img src={`uploads/avatars/${form.ext}.jpg?v=${Date.now()}`}
                             className="rounded-full object-cover"
                             style={{width: hasRtsp ? 52 : 72, height: hasRtsp ? 52 : 72, border:'3px solid var(--background)', boxShadow:'0 4px 12px rgba(0,0,0,.15)'}}
                             onError={ev=>{ev.target.style.display='none';ev.target.nextSibling.style.display='flex';}}/>
                    ) : null}
                    <div className="rounded-full flex items-center justify-center font-black text-white"
                         style={{
                             width: hasRtsp ? 52 : 72, height: hasRtsp ? 52 : 72, fontSize: hasRtsp ? 17 : 22,
                             background:`linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000))`,
                             display: form.ext && avatarUrl ? 'none' : 'flex',
                             boxShadow:'0 4px 12px rgba(0,0,0,.15)'
                         }}>{ini}</div>
                    <div className="absolute rounded-full"
                         style={{
                             bottom:0, right:0,
                             width: hasRtsp ? 14 : 18, height: hasRtsp ? 14 : 18,
                             background: statusColor,
                             border:'3px solid var(--card)',
                             animation: ext?.status === 'BUSY' ? 'pulse 1.4s ease-in-out infinite' : 'none'
                         }}/>
                </div>
                <Badge variant="outline" className="font-bold uppercase text-[10px]" style={{borderColor:`${statusColor}66`,color:statusColor,background:`${statusColor}11`}}>
                    {statusLabel}
                </Badge>
            </div>

            {/* Métricas */}
            <div className="rounded-md border overflow-hidden" style={{borderColor:'var(--border)'}}>
                {/* IP */}
                <div className="flex items-center justify-between px-3 py-2"
                     style={{background:'color-mix(in srgb, var(--muted) 30%, transparent)'}}>
                    <div className="flex items-center gap-1.5">
                        <span className="material-icons-round" style={{fontSize:13,color:'var(--muted-foreground)'}}>lan</span>
                        <span className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>IP</span>
                    </div>
                    <span className="font-mono text-xs font-bold" style={{color: ext?.ip && ext.ip !== '—' ? '#3b82f6' : 'var(--muted-foreground)'}}>{ext?.ip || '—'}</span>
                </div>
                {/* RTT con animación */}
                <div className="flex items-center justify-between px-3 py-2 border-t" style={{borderColor:'var(--border)'}}>
                    <div className="flex items-center gap-1.5">
                        <span className="material-icons-round" style={{fontSize:13,color:'var(--muted-foreground)'}}>speed</span>
                        <span className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>RTT</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        {rttGrade && (
                            <span style={{
                                width:8, height:8, borderRadius:'50%',
                                background:rttColor,
                                boxShadow:`0 0 6px ${rttColor}`,
                                animation:`pulse ${rttPulseSpeed} ease-in-out infinite`
                            }}/>
                        )}
                        <span className="font-mono text-xs font-bold" style={{color:rttColor}}>
                            {rttMs !== null ? `${rttMs}ms` : '—'}
                        </span>
                        {rttGrade && (
                            <span className="text-[9px] font-bold uppercase ml-1" style={{color:rttColor,opacity:0.85}}>
                                {rttGrade === 'good' ? 'OK' : (rttGrade === 'mid' ? 'MID' : 'HIGH')}
                            </span>
                        )}
                    </div>
                </div>
                {/* MAC */}
                <div className="flex items-center justify-between px-3 py-2 border-t" style={{borderColor:'var(--border)', background:'color-mix(in srgb, var(--muted) 30%, transparent)'}}>
                    <div className="flex items-center gap-1.5">
                        <span className="material-icons-round" style={{fontSize:13,color:'var(--muted-foreground)'}}>memory</span>
                        <span className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>MAC</span>
                    </div>
                    <span className="font-mono text-[11px] font-bold" style={{color:'var(--muted-foreground)'}}>{ext?.mac || '—'}</span>
                </div>
            </div>
        </div>
    );
}

// ─── RtspInlinePreview: miniatura HLS embebida en la columna Estado ───────
function RtspInlinePreview({ ext, url, label }) {
    const videoRef = useRef(null);
    const [error, setError] = useState(null);
    const [resolvedUrl, setResolvedUrl] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!url) return;
        let cancelled = false;
        setLoading(true); setError(null); setResolvedUrl(null);
        const isRtsp = /^rtsps?:\/\//i.test(url);
        if (isRtsp && ext) {
            fetch(`api/rtsp_proxy.php?ext=${encodeURIComponent(ext)}`, { credentials:'include' })
                .then(r => r.json())
                .then(d => {
                    if (cancelled) return;
                    if (d.status === 'ok' && d.hls_url) setResolvedUrl(d.hls_url);
                    else setError(d.message || 'Proxy no disponible');
                })
                .catch(() => { if (!cancelled) setError('Error de proxy'); })
                .finally(() => { if (!cancelled) setLoading(false); });
        } else {
            setResolvedUrl(url);
            setLoading(false);
        }
        return () => { cancelled = true; };
    }, [url, ext]);

    const playUrl = resolvedUrl || '';
    const isHls = /\.m3u8(\?|$)/i.test(playUrl);

    useEffect(() => {
        if (!isHls || !playUrl || !videoRef.current) return;
        const video = videoRef.current;
        const setupHls = () => {
            if (!window.Hls) { setError('HLS.js no cargó'); return; }
            if (window.Hls.isSupported()) {
                const hls = new window.Hls();
                hls.loadSource(playUrl);
                hls.attachMedia(video);
                hls.on(window.Hls.Events.ERROR, (_, data) => { if (data.fatal) setError('Stream no disponible'); });
                video._hls = hls;
                return () => { try { hls.destroy(); } catch(e) {} };
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = playUrl;
            }
        };
        if (window.Hls) return setupHls();
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/hls.js@1.5/dist/hls.min.js';
        s.onload = setupHls;
        s.onerror = () => setError('No se pudo cargar HLS.js');
        document.head.appendChild(s);
    }, [playUrl, isHls]);

    return (
        <div className="relative rounded-lg overflow-hidden border" style={{
            borderColor:'color-mix(in srgb, var(--horizon-green) 40%, transparent)',
            background:'#0a0a0d',
            aspectRatio:'16/10',
            boxShadow:'0 4px 12px rgba(0,0,0,0.2), 0 0 0 1px color-mix(in srgb, var(--horizon-green) 25%, transparent)'
        }}>
            {/* Header overlay */}
            <div className="absolute top-0 left-0 right-0 z-10 flex items-center gap-1.5 px-2 py-1.5"
                 style={{background:'linear-gradient(180deg, rgba(0,0,0,0.55), transparent)'}}>
                <span className="material-icons-round" style={{fontSize:13, color:'var(--horizon-green)', animation:'pulse 1.5s infinite'}}>videocam</span>
                <span className="text-[10px] font-bold text-white truncate flex-1">{label || 'Live'}</span>
                <span className="text-[9px] font-bold uppercase tracking-wider" style={{color:'var(--horizon-green)'}}>LIVE</span>
            </div>
            {(loading || error) && (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-1.5" style={{color:'rgba(255,255,255,0.6)'}}>
                    {loading && !error && (
                        <>
                            <span className="material-icons-round animate-spin" style={{fontSize:24, color:'var(--horizon-green)'}}>autorenew</span>
                            <span className="text-[10px]">Conectando…</span>
                        </>
                    )}
                    {error && (
                        <>
                            <span className="material-icons-round" style={{fontSize:24, color:'#ef4444'}}>videocam_off</span>
                            <span className="text-[10px] px-3 text-center">{error}</span>
                        </>
                    )}
                </div>
            )}
            {!error && (
                <video ref={videoRef} autoPlay muted playsInline className="w-full h-full object-cover"
                       onError={() => setError('Reproducción falló')}/>
            )}
        </div>
    );
}

// ─── RtspSnapshotGallery: histórico de capturas RTSP de los últimos 30 días ───
function RtspSnapshotGallery({ ext, onShotClick }) {
    const [shots, setShots] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!ext) return;
        let cancelled = false;
        setLoading(true); setError(null);
        fetch(`api/rtsp_snapshot.php?action=list&ext=${encodeURIComponent(ext)}`, { credentials:'include' })
            .then(r => r.json())
            .then(d => {
                if (cancelled) return;
                if (d.status === 'ok') setShots(d.snapshots || []);
                else setError(d.message || 'Sin datos');
            })
            .catch(() => { if (!cancelled) setError('No se pudieron cargar las capturas'); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [ext]);

    const captureNow = async () => {
        try {
            const fd = new FormData();
            fd.append('ext', ext);
            const r = await fetch('api/rtsp_snapshot.php?action=capture', { method:'POST', body:fd, credentials:'include' });
            const j = await r.json();
            if (j.status === 'ok') {
                // refresh list
                setShots(prev => [{ url: j.url, timestamp: j.timestamp, filename: j.filename }, ...(prev || [])]);
            }
        } catch(e) {}
    };

    if (loading) return (
        <div className="flex items-center justify-center py-6 gap-2" style={{color:'var(--muted-foreground)'}}>
            <span className="material-icons-round animate-spin" style={{fontSize:18}}>autorenew</span>
            <span className="text-[11px]">Cargando capturas…</span>
        </div>
    );
    if (error) return (
        <div className="flex flex-col items-center justify-center py-6 gap-2 text-center">
            <span className="material-icons-round" style={{fontSize:26,color:'var(--muted-foreground)',opacity:0.5}}>broken_image</span>
            <p className="text-[10px]" style={{color:'var(--muted-foreground)'}}>{error}</p>
        </div>
    );

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-2">
                <span className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>
                    {shots && shots.length > 0 ? `${shots.length} ${shots.length === 1 ? 'captura' : 'capturas'}` : 'Sin registros'}
                </span>
                <Button variant="ghost" size="sm" onClick={captureNow} className="h-7 px-2 text-[10px]">
                    <span className="material-icons-round mr-1" style={{fontSize:13}}>add_a_photo</span>
                    Capturar ahora
                </Button>
            </div>
            {(!shots || shots.length === 0) ? (
                <div className="flex flex-col items-center justify-center py-4 gap-2 text-center rounded-md border border-dashed" style={{borderColor:'var(--border)'}}>
                    <span className="material-icons-round" style={{fontSize:26,color:'var(--muted-foreground)',opacity:0.4}}>image_not_supported</span>
                    <p className="text-[10px]" style={{color:'var(--muted-foreground)'}}>Sin capturas todavía.<br/>Se generan automáticamente al recibir llamadas.</p>
                </div>
            ) : (
                <div className="grid grid-cols-3 gap-1.5">
                    {shots.slice(0, 9).map((s, i) => (
                        <button key={i} type="button"
                                onClick={()=>onShotClick ? onShotClick(s) : window.open(s.url, '_blank')}
                                className="block w-full rounded-md overflow-hidden border transition-all hover:scale-105 hover:shadow-lg relative group cursor-pointer"
                                style={{borderColor:'var(--border)',aspectRatio:'1/1',padding:0}}
                                title={`Click para ampliar — ${s.timestamp}`}>
                            <img src={s.url} alt={s.timestamp} className="w-full h-full object-cover"
                                 loading="lazy"
                                 onError={ev => ev.target.style.display='none'}/>
                            <div className="absolute bottom-0 left-0 right-0 px-1.5 py-0.5 text-[8px] font-mono font-bold text-white"
                                 style={{background:'linear-gradient(0deg, rgba(0,0,0,0.7), transparent)'}}>
                                {s.timestamp && s.timestamp.split(' ')[1]?.substring(0,5) || ''}
                            </div>
                        </button>
                    ))}
                </div>
            )}
            {shots && shots.length > 9 && (
                <div className="text-[10px] text-center" style={{color:'var(--muted-foreground)'}}>
                    +{shots.length - 9} capturas más en el último mes
                </div>
            )}
        </div>
    );
}

// FICHA DEL INTERNO — Página dedicada (no modal)
// ─────────────────────────────────────────────
function ExtEditPage({ ext, onBack, onSaved, toast }) {
    const isNew = !ext;
    const [form, setForm] = useState({ 
        ext: ext?.ext||'', name: ext?.name||'', secret: '', email: '', 
        tipo: (window._tfExtMeta||{})[ext?.ext]?.tipo || '',
        rtsp_url: (window._tfExtMeta||{})[ext?.ext]?.rtsp_url || '',
        rtsp_label: (window._tfExtMeta||{})[ext?.ext]?.rtsp_label || ''
    });
    const [recording, setRecording] = useState(ext?.recording||'dontcare');
    const [devType, setDevType] = useState('webrtc');
    const [showPass, setShowPass] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [avatarUrl, setAvatarUrl] = useState(ext?.avatar || null);
    // HORIZON v4: modales para RTSP y Grabación
    const [showRtspModal, setShowRtspModal] = useState(false);
    const [showRecModal, setShowRecModal] = useState(false);
    const [lightboxShot, setLightboxShot] = useState(null);

    // HORIZON: Tabs para ficha — Datos / Historial / Agentes
    const [activeTab, setActiveTab] = useState('datos');
    const [callHistory, setCallHistory] = useState(null);
    const [agentHistory, setAgentHistory] = useState(null);
    const [historyLoading, setHistoryLoading] = useState(false);

    useEffect(() => {
        if (isNew || !ext?.ext) return;
        if (activeTab === 'historial' && !callHistory) {
            setHistoryLoading(true);
            const today = new Date(); const past = new Date(); past.setDate(past.getDate()-30);
            const from = past.toISOString().split('T')[0];
            const to = today.toISOString().split('T')[0];
            fetch(`api/reports.php?action=calls&ext=${ext.ext}&from=${from}&to=${to}&limit=300`, {credentials:'include'})
                .then(r=>r.json()).then(d=>{ setCallHistory(d.calls || []); setHistoryLoading(false); })
                .catch(()=>setHistoryLoading(false));
        }
        if (activeTab === 'agentes' && !agentHistory) {
            setHistoryLoading(true);
            const today = new Date(); const past = new Date(); past.setDate(past.getDate()-90);
            const from = past.toISOString().split('T')[0];
            const to = today.toISOString().split('T')[0];
            fetch(`api/reports.php?action=agent_sessions&agent=${ext.ext}&from=${from}&to=${to}`, {credentials:'include'})
                .then(r=>r.json()).then(d=>{ setAgentHistory(d.sessions || []); setHistoryLoading(false); })
                .catch(()=>setHistoryLoading(false));
        }
    }, [activeTab, ext?.ext, isNew]);

    useEffect(() => {
        if (!isNew && ext.ext) {
            fetch(`api/index.php?action=get_extension&ext=${ext.ext}`, { credentials: 'include' })
                .then(r=>r.json())
                .then(d=>{ if(d.success) {
                    setForm(f=>({...f, secret: d.secret||''}));
                    if (d.device_type) setDevType(d.device_type);
                }});
        }
    }, [ext, isNew]);

    const set = (k,v) => setForm(f=>({...f,[k]:v}));

    const ini = ((form.name||form.ext||'?').split(/[\s\-_]+/).filter(s=>s.length>0).map(s=>s[0]||'').join('') || '?').substring(0,2).toUpperCase();
    const statusColor = ext?.status === 'BUSY' ? '#ef4444' : (ext?.status === 'ONLINE' ? '#22c55e' : '#6b7280');
    const statusLabel = ext?.status === 'BUSY' ? 'En llamada' : (ext?.status === 'ONLINE' ? 'Disponible' : 'Desconectado');

    const tipoConfig = {
        '': { color: '#6b7280', icon: 'help_outline', label: 'Sin asignar', desc: 'Sin clasificar' },
        'horizon': { color:'var(--primary)', icon: 'business', label: 'Horizon', desc: 'Interno propio' },
        'cliente': { color: '#3b82f6', icon: 'person', label: 'Cliente', desc: 'Cliente externo' }
    };

    const recOptions = [
        { v:'always', l:'Siempre', c:'#22c55e', i:'fiber_manual_record', d:'Toda llamada queda grabada' },
        { v:'dontcare', l:'Opcional', c:'#9ca3af', i:'radio_button_unchecked', d:'Decisión por usuario' },
        { v:'never', l:'Nunca', c:'#ef4444', i:'block', d:'Jamás se graba' }
    ];

    const devOptions = [
        { v:'webrtc', l:'WebRTC', c:'#8b5cf6', i:'computer', d:'Softphone en navegador' },
        { v:'sip', l:'SIP Fijo', c:'#3b82f6', i:'phone', d:'Teléfono físico SIP' },
        { v:'video', l:'Video', c:'#ec4899', i:'videocam', d:'Con cámara WebRTC' }
    ];

    const save = async () => {
        setSaving(true);
        const fd = new FormData();
        Object.entries(form).forEach(([k,v])=>fd.append(k,v));
        fd.append('device_type', devType);
        fd.append('recording', recording);
        const action = isNew ? 'create_extension' : 'update_extension';
        // Guardar ext_meta (tipo + rtsp) si cambió alguno
        const prevMeta = (window._tfExtMeta||{})[form.ext] || {};
        const metaChanged = form.tipo !== (prevMeta.tipo || '') ||
                            form.rtsp_url !== (prevMeta.rtsp_url || '') ||
                            form.rtsp_label !== (prevMeta.rtsp_label || '');
        if (metaChanged) {
            const tfd = new FormData();
            tfd.append('ext', form.ext);
            tfd.append('tipo', form.tipo);
            tfd.append('rtsp_url', form.rtsp_url || '');
            tfd.append('rtsp_label', form.rtsp_label || '');
            fetch('api/index.php?action=set_ext_meta', {method:'POST',body:tfd,credentials:'include'})
                .then(r=>r.json()).then(j=>{
                    if(j.success) {
                        window._tfExtMeta = window._tfExtMeta||{};
                        window._tfExtMeta[form.ext] = {
                            ext: form.ext,
                            tipo: form.tipo,
                            rtsp_url: form.rtsp_url || '',
                            rtsp_label: form.rtsp_label || ''
                        };
                    }
                });
        }
        try {
            const r = await fetch(`api/index.php?action=${action}`, { method:'POST', body:fd, credentials:'include' });
            const d = await r.json();
            if (d.success) { toast('Cambios guardados','success'); onSaved?.(); }
            else toast(d.error || 'Error guardando','error');
        } catch(e) { toast('Error de red','error'); }
        setSaving(false);
    };

    const remove = async () => {
        if (!confirm(`¿Eliminar interno #${form.ext}? Esta acción no se puede deshacer.`)) return;
        setDeleting(true);
        const fd = new FormData(); fd.append('ext', form.ext);
        try {
            const r = await fetch('api/index.php?action=delete_extension', {method:'POST',body:fd,credentials:'include'});
            const d = await r.json();
            if (d.success) { toast('Interno eliminado','success'); onBack?.(); }
            else toast(d.error || 'Error','error');
        } catch(e) { toast('Error de red','error'); }
        setDeleting(false);
    };

    return (
        <div className="content-area view-enter">
            {/* ─── BREADCRUMB + ACCIONES ─────────────────────────────── */}
            <div className="flex items-center gap-3 mb-5 flex-wrap">
                <Button variant="outline" size="icon" onClick={onBack} className="h-9 w-9 shrink-0">
                    <span className="material-icons-round" style={{fontSize:18}}>arrow_back</span>
                </Button>
                <nav className="flex items-center gap-1.5 text-sm" style={{color:'var(--muted-foreground)'}}>
                    <button onClick={onBack} className="hover:underline" style={{color:'var(--muted-foreground)'}}>Extensiones</button>
                    <span className="material-icons-round" style={{fontSize:14,opacity:0.5}}>chevron_right</span>
                    <span style={{color:'var(--foreground)',fontWeight:700}}>{isNew ? 'Nueva extensión' : `Interno #${form.ext}`}</span>
                </nav>
                <div className="flex-1"/>
                {!isNew && (
                    <Button variant="destructive" size="sm" onClick={remove} disabled={deleting}>
                        <span className="material-icons-round mr-1.5" style={{fontSize:16}}>delete_outline</span>
                        {deleting?'Eliminando…':'Eliminar interno'}
                    </Button>
                )}
            </div>

            {/* ─── TABS — Datos / Historial / Agentes ─────────────────── */}
            {!isNew && (
                <div className="flex items-center gap-1 border-b mb-5" style={{borderColor:'var(--border)'}}>
                    {[
                        { id:'datos',     icon:'tune',           label:'Datos' },
                        { id:'historial', icon:'history',        label:'Historial de llamadas' },
                        { id:'agentes',   icon:'support_agent',  label:'Historial de agentes' },
                    ].map(t => (
                        <button
                            key={t.id}
                            onClick={()=>setActiveTab(t.id)}
                            className={cn(
                                "px-4 py-2.5 -mb-px flex items-center gap-2 text-sm font-medium transition-colors border-b-2",
                                activeTab === t.id ? "border-primary" : "border-transparent hover:bg-accent/50"
                            )}
                            style={{
                                borderBottomColor: activeTab === t.id ? 'var(--primary)' : 'transparent',
                                color: activeTab === t.id ? 'var(--primary)' : 'var(--muted-foreground)'
                            }}
                        >
                            <span className="material-icons-round" style={{fontSize:16}}>{t.icon}</span>
                            {t.label}
                        </button>
                    ))}
                </div>
            )}

            {/* ─── TAB: HISTORIAL DE LLAMADAS ────────────────────────── */}
            {!isNew && activeTab === 'historial' && (
                <Card className="overflow-hidden">
                    <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle className="text-base">Historial de llamadas — últimos 30 días</CardTitle>
                            <CardDescription>Todas las llamadas donde esta extensión figura como origen o destino</CardDescription>
                        </div>
                        {callHistory && <Badge variant="secondary">{callHistory.length} llamadas</Badge>}
                    </CardHeader>
                    {historyLoading && (
                        <div className="py-12 text-center" style={{color:'var(--muted-foreground)'}}>
                            <span className="material-icons-round animate-spin" style={{fontSize:32, color:'var(--primary)'}}>autorenew</span>
                            <div className="mt-2 text-sm">Cargando historial…</div>
                        </div>
                    )}
                    {!historyLoading && callHistory && callHistory.length === 0 && (
                        <div className="py-12 text-center" style={{color:'var(--muted-foreground)'}}>
                            <span className="material-icons-round" style={{fontSize:40, opacity:0.4}}>phone_disabled</span>
                            <div className="mt-2 text-sm font-bold">Sin llamadas en el período</div>
                        </div>
                    )}
                    {!historyLoading && callHistory && callHistory.length > 0 && (
                        <div className="overflow-auto border-t" style={{maxHeight:'65vh',borderColor:'var(--border)'}}>
                            <table className="w-full text-sm">
                                <thead className="sticky top-0" style={{background:'var(--card)', borderBottom:'1px solid var(--border)'}}>
                                    <tr>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Fecha/Hora</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Origen</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Destino</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>CallerID</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Estado</th>
                                        <th className="text-right px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Hablado</th>
                                        <th className="text-center px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Grab.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {callHistory.map((c,i) => {
                                        const variant = c.disposition === 'ANSWERED' ? 'success' : (c.disposition === 'BUSY' || c.disposition === 'FAILED' ? 'destructive' : 'warning');
                                        const isInbound = String(c.dst) === String(ext.ext);
                                        return (
                                            <tr key={c.uniqueid || i} className="border-b transition-colors hover:bg-muted/40" style={{borderColor:'var(--border)'}}>
                                                <td className="px-3 py-2 font-mono text-xs">{c.calldate}</td>
                                                <td className="px-3 py-2 font-mono">
                                                    {isInbound ? <span style={{color:'var(--muted-foreground)'}}>{c.src}</span> : <strong style={{color:'var(--primary)'}}>{c.src}</strong>}
                                                </td>
                                                <td className="px-3 py-2 font-mono">
                                                    {isInbound ? <strong style={{color:'var(--primary)'}}>{c.dst}</strong> : <span style={{color:'var(--muted-foreground)'}}>{c.dst}</span>}
                                                </td>
                                                <td className="px-3 py-2 text-xs truncate" style={{color:'var(--muted-foreground)', maxWidth:200}}>{c.clid}</td>
                                                <td className="px-3 py-2"><Badge variant={variant}>{c.disposition}</Badge></td>
                                                <td className="px-3 py-2 text-right font-mono">{c.billsec}s</td>
                                                <td className="px-3 py-2 text-center">
                                                    {c.recordingfile
                                                        ? <a href={`api/recording.php?file=${encodeURIComponent(c.recordingfile)}`} target="_blank" rel="noopener noreferrer" style={{color:'var(--primary)'}}><span className="material-icons-round" style={{fontSize:16}}>play_circle</span></a>
                                                        : <span style={{color:'var(--muted-foreground)'}}>—</span>}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            )}

            {/* ─── TAB: HISTORIAL DE AGENTES ────────────────────────── */}
            {!isNew && activeTab === 'agentes' && (
                <Card className="overflow-hidden">
                    <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle className="text-base">Agentes logueados — últimos 90 días</CardTitle>
                            <CardDescription>Sesiones de agentes que ocuparon este interno</CardDescription>
                        </div>
                        {agentHistory && <Badge variant="secondary">{agentHistory.length} sesiones</Badge>}
                    </CardHeader>
                    {historyLoading && (
                        <div className="py-12 text-center" style={{color:'var(--muted-foreground)'}}>
                            <span className="material-icons-round animate-spin" style={{fontSize:32, color:'var(--primary)'}}>autorenew</span>
                            <div className="mt-2 text-sm">Cargando historial…</div>
                        </div>
                    )}
                    {!historyLoading && agentHistory && agentHistory.length === 0 && (
                        <div className="py-12 text-center" style={{color:'var(--muted-foreground)'}}>
                            <span className="material-icons-round" style={{fontSize:40, opacity:0.4}}>person_off</span>
                            <div className="mt-2 text-sm font-bold">Sin sesiones de agente</div>
                            <div className="mt-1 text-xs">Ningún agente se ha logueado en esta extensión en los últimos 90 días</div>
                        </div>
                    )}
                    {!historyLoading && agentHistory && agentHistory.length > 0 && (
                        <div className="overflow-auto border-t" style={{maxHeight:'65vh',borderColor:'var(--border)'}}>
                            <table className="w-full text-sm">
                                <thead className="sticky top-0" style={{background:'var(--card)', borderBottom:'1px solid var(--border)'}}>
                                    <tr>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Estado</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Agente</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Login</th>
                                        <th className="text-left px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Logout</th>
                                        <th className="text-right px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Duración</th>
                                        <th className="text-right px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Llamadas</th>
                                        <th className="text-right px-3 py-2 text-xs font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Talk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {agentHistory.map((s,i) => {
                                        const active = !s.logout_time;
                                        return (
                                            <tr key={s.session_id || i} className="border-b transition-colors hover:bg-muted/40" style={{borderColor:'var(--border)'}}>
                                                <td className="px-3 py-2"><Badge variant={active ? 'success' : 'secondary'}>{active ? 'ACTIVA' : 'CERRADA'}</Badge></td>
                                                <td className="px-3 py-2"><strong style={{color:'var(--primary)'}}>#{s.agent_number || '—'}</strong></td>
                                                <td className="px-3 py-2 font-mono text-xs">{s.login_time}</td>
                                                <td className="px-3 py-2 font-mono text-xs">{s.logout_time || '—'}</td>
                                                <td className="px-3 py-2 text-right font-mono"><span style={{color:'#3b82f6'}}>{tfFmtSecs(s.duration_sec)}</span></td>
                                                <td className="px-3 py-2 text-right">{s.total_calls || 0}</td>
                                                <td className="px-3 py-2 text-right font-mono">{s.total_talk_time ? tfFmtSecs(s.total_talk_time) : '—'}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            )}

            {/* ─── TAB: DATOS (form shadcn) ──────────────────────────── */}
            {(isNew || activeTab === 'datos') && (
            <div className="grid gap-5" style={{gridTemplateColumns:'minmax(0, 1fr) 320px'}}>
                {/* COLUMNA PRINCIPAL */}
                <div className="flex flex-col gap-5">

                    {/* ─── 4 Cards independientes v4 (Info | Categoría+Tecno | ÚltimoAcceso+RTSP-modal | Estado+Grabación-modal) ─── */}
                    <div className="grid gap-4 lg:grid-cols-4">

                        {/* ─── Card 1: Información básica ─── */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-sm uppercase tracking-wider">
                                    <span className="material-icons-round" style={{fontSize:18,color:'var(--primary)'}}>badge</span>
                                    Información básica
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="ext-num">Número de interno</Label>
                                    <Input id="ext-num" value={form.ext} onChange={e=>set('ext',e.target.value)} disabled={!isNew}
                                           placeholder="1000" className="font-mono font-bold"/>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="ext-name">Nombre o alias</Label>
                                    <Input id="ext-name" value={form.name} onChange={e=>set('name',e.target.value)} placeholder="Recepción"/>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="ext-mail">Correo electrónico</Label>
                                    <Input id="ext-mail" type="email" value={form.email} onChange={e=>set('email',e.target.value)} placeholder="usuario@empresa.com"/>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="ext-secret">Contraseña SIP <span className="font-normal" style={{color:'var(--muted-foreground)'}}>(secret)</span></Label>
                                    <div className="relative">
                                        <Input id="ext-secret" type={showPass?'text':'password'} value={form.secret} onChange={e=>set('secret',e.target.value)} className="pr-9 font-mono"/>
                                        <button type="button" onClick={()=>setShowPass(!showPass)}
                                                className="absolute top-1/2 -translate-y-1/2 right-2 hover:opacity-80"
                                                style={{color:'var(--muted-foreground)'}}>
                                            <span className="material-icons-round" style={{fontSize:16}}>{showPass?'visibility_off':'visibility'}</span>
                                        </button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ─── Card 2: Categoría + Tecnología (toggle buttons) ─── */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-sm uppercase tracking-wider">
                                    <span className="material-icons-round" style={{fontSize:18,color:'#3b82f6'}}>category</span>
                                    Categoría
                                </CardTitle>
                                <CardDescription className="text-[10px]">Discrimina Cliente vs Horizon</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {/* Toggles de tipo */}
                                <div className="space-y-2">
                                    {Object.entries(tipoConfig).map(([v,o]) => (
                                        <button key={v} type="button" onClick={()=>set('tipo',v)}
                                                className="relative w-full rounded-lg border-2 p-3 text-left transition-all hover:shadow-sm flex items-center gap-3"
                                                style={{
                                                    borderColor: form.tipo===v ? `${o.color}` : 'var(--border)',
                                                    background: form.tipo===v ? `color-mix(in srgb, ${o.color} 8%, var(--card))` : 'var(--card)'
                                                }}>
                                            <span className="material-icons-round shrink-0" style={{fontSize:22,color:o.color}}>{o.icon}</span>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-xs font-bold" style={{color:form.tipo===v?o.color:'var(--foreground)'}}>{o.label}</div>
                                                <div className="text-[10px] mt-0.5" style={{color:'var(--muted-foreground)'}}>{o.desc}</div>
                                            </div>
                                            {form.tipo===v && (
                                                <span className="shrink-0 rounded-full flex items-center justify-center"
                                                      style={{width:18,height:18,background:o.color}}>
                                                    <span className="material-icons-round text-white" style={{fontSize:12}}>check</span>
                                                </span>
                                            )}
                                        </button>
                                    ))}
                                </div>

                                {/* Separator + Tecnología toggle buttons */}
                                <Separator className="my-2"/>
                                <div>
                                    <Label className="block mb-2 text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Tecnología de dispositivo</Label>
                                    <div className="grid grid-cols-3 gap-1.5">
                                        {devOptions.map(o => (
                                            <button key={o.v} type="button" onClick={()=>setDevType(o.v)}
                                                    className="flex flex-col items-center justify-center gap-1 rounded-lg border-2 px-1.5 py-2.5 transition-all hover:shadow-sm"
                                                    style={{
                                                        borderColor: devType===o.v ? o.c : 'var(--border)',
                                                        background: devType===o.v ? `color-mix(in srgb, ${o.c} 10%, var(--card))` : 'var(--card)'
                                                    }}
                                                    title={o.d}>
                                                <span className="material-icons-round" style={{fontSize:20,color:devType===o.v?o.c:'var(--muted-foreground)'}}>{o.i}</span>
                                                <span className="text-[10px] font-bold" style={{color:devType===o.v?o.c:'var(--foreground)'}}>{o.l}</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ─── Card 3: Último acceso (thumbnails RTSP) + botón configurar ─── */}
                        <Card>
                            <CardHeader className="pb-3 flex flex-row items-start justify-between space-y-0 gap-2">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-sm uppercase tracking-wider">
                                        <span className="material-icons-round" style={{fontSize:18,color:'var(--horizon-green)'}}>photo_library</span>
                                        Último acceso
                                    </CardTitle>
                                    <CardDescription className="text-[10px]">Capturas RTSP últimos 30 días</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" onClick={()=>setShowRtspModal(true)} className="h-8 px-2 text-[10px] shrink-0" title="Configurar URL RTSP del stream">
                                    <span className="material-icons-round" style={{fontSize:13,marginRight:3}}>settings</span>
                                    RTSP
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {!isNew && form.rtsp_url ? (
                                    <RtspSnapshotGallery ext={form.ext} onShotClick={setLightboxShot}/>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-6 px-3 text-center gap-2">
                                        <span className="material-icons-round" style={{fontSize:32,color:'var(--muted-foreground)',opacity:0.5}}>no_photography</span>
                                        <p className="text-[11px]" style={{color:'var(--muted-foreground)'}}>
                                            {isNew ? 'Disponible al guardar el interno' : 'Configurá el stream RTSP arriba a la derecha para empezar a capturar snapshots'}
                                        </p>
                                        {!isNew && !form.rtsp_url && (
                                            <Button variant="outline" size="sm" onClick={()=>setShowRtspModal(true)} className="mt-1">
                                                <span className="material-icons-round mr-1" style={{fontSize:14}}>videocam</span>
                                                Configurar RTSP
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* ─── Card 4: Estado del interno (con preview RTSP en vivo si hay URL) ─── */}
                        <Card className="overflow-hidden">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-sm uppercase tracking-wider">
                                    <span className="material-icons-round" style={{fontSize:18, color: !isNew ? statusColor : 'var(--muted-foreground)'}}>circle</span>
                                    Estado del interno
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!isNew ? (
                                    <div className="space-y-3">
                                        <ExtStatusPanel
                                            ext={ext}
                                            form={form}
                                            avatarUrl={avatarUrl}
                                            ini={ini}
                                            statusColor={statusColor}
                                            statusLabel={statusLabel}
                                        />
                                        {/* Botón Grabación de llamadas — abre modal */}
                                        <Separator/>
                                        <button type="button" onClick={()=>setShowRecModal(true)}
                                                className="w-full rounded-lg border p-2.5 flex items-center gap-2.5 text-left transition-all hover:shadow-sm"
                                                style={{
                                                    borderColor: 'var(--border)',
                                                    background: 'color-mix(in srgb, var(--muted) 30%, var(--card))'
                                                }}>
                                            {(() => {
                                                const cur = recOptions.find(o => o.v === recording) || recOptions[1];
                                                return (
                                                    <>
                                                        <span className="material-icons-round shrink-0" style={{fontSize:18,color:cur.c}}>{cur.i}</span>
                                                        <div className="flex-1 min-w-0">
                                                            <div className="text-[10px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Grabación de llamadas</div>
                                                            <div className="text-xs font-bold mt-0.5" style={{color:cur.c}}>{cur.l}</div>
                                                        </div>
                                                        <span className="material-icons-round" style={{fontSize:16,color:'var(--muted-foreground)',opacity:0.6}}>tune</span>
                                                    </>
                                                );
                                            })()}
                                        </button>
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-6 gap-2" style={{color:'var(--muted-foreground)'}}>
                                        <span className="material-icons-round" style={{fontSize:36,opacity:0.5}}>fiber_new</span>
                                        <p className="text-xs text-center">Una vez creado el interno,<br/>se mostrará su estado en vivo aquí.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                    </div>
                    {/* ─── Action bar ─── */}
                    <div className="flex items-center justify-end gap-2.5 pt-1">
                        <Button variant="outline" onClick={onBack}>Cancelar</Button>
                        <Button onClick={save} disabled={saving}>
                            <span className="material-icons-round mr-1.5" style={{fontSize:16, animation: saving?'spin 1s linear infinite':'none'}}>{saving?'autorenew':'save'}</span>
                            {saving ? 'Guardando…' : 'Guardar cambios'}
                        </Button>
                    </div>
                    {/* ─── Modal RTSP config ─── */}
                    <Dialog open={showRtspModal} onOpenChange={setShowRtspModal}>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <span className="material-icons-round" style={{fontSize:20,color:'var(--horizon-green)'}}>videocam</span>
                                Configurar videoportero / RTSP
                            </DialogTitle>
                            <DialogDescription>
                                URL del stream del videoportero o cámara asociada a este interno. El preview aparecerá en vivo en la tarjeta Estado y al recibir llamadas.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 mt-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="rtsp-label-m">Etiqueta visible</Label>
                                <Input id="rtsp-label-m" value={form.rtsp_label} onChange={e=>set('rtsp_label',e.target.value)}
                                       placeholder="Portero entrada principal" maxLength={80}/>
                                <p className="text-[11px]" style={{color:'var(--muted-foreground)'}}>Texto que aparece junto al video</p>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="rtsp-url-m">URL del stream</Label>
                                <Input id="rtsp-url-m" value={form.rtsp_url} onChange={e=>set('rtsp_url',e.target.value)}
                                       placeholder="rtsp://user:pass@10.1.2.3:554/stream1" maxLength={500} className="font-mono text-xs"/>
                                <p className="text-[11px]" style={{color:'var(--muted-foreground)'}}>
                                    Acepta <span className="font-mono">rtsp://</span>, <span className="font-mono">rtsps://</span>, <span className="font-mono">http(s)://</span>, <span className="font-mono">.m3u8</span> (HLS), <span className="font-mono">.mp4</span>
                                </p>
                            </div>
                            {form.rtsp_url && (
                                <div className="flex gap-2.5 items-start rounded-md border px-3 py-2.5"
                                     style={{borderColor:'color-mix(in srgb, var(--horizon-green) 30%, transparent)',
                                             background:'color-mix(in srgb, var(--horizon-green) 8%, transparent)'}}>
                                    <span className="material-icons-round shrink-0" style={{fontSize:16,color:'var(--horizon-green)',marginTop:1}}>check_circle</span>
                                    <p className="text-xs leading-relaxed" style={{color:'var(--foreground)'}}>
                                        Stream configurado. Apenas guardes el interno, las capturas comenzarán a aparecer en "Último acceso".
                                    </p>
                                </div>
                            )}
                        </div>
                        <DialogFooter>
                            {form.rtsp_url && (
                                <Button variant="outline" onClick={()=>{ set('rtsp_url',''); set('rtsp_label',''); }}>
                                    <span className="material-icons-round mr-1.5" style={{fontSize:14}}>delete</span>
                                    Limpiar
                                </Button>
                            )}
                            <Button onClick={()=>setShowRtspModal(false)}>
                                <span className="material-icons-round mr-1.5" style={{fontSize:14}}>check</span>
                                Listo
                            </Button>
                        </DialogFooter>
                    </Dialog>

                    {/* ─── Modal Grabación de llamadas ─── */}
                    <Dialog open={showRecModal} onOpenChange={setShowRecModal}>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <span className="material-icons-round" style={{fontSize:20,color:'#ef4444'}}>fiber_manual_record</span>
                                Política de grabación
                            </DialogTitle>
                            <DialogDescription>
                                Definí cómo se graban las llamadas de este interno
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2 mt-2">
                            {recOptions.map(o => (
                                <button key={o.v} type="button" onClick={()=>{ setRecording(o.v); }}
                                        className="relative w-full rounded-lg border-2 p-4 text-left transition-all hover:shadow-sm flex items-center gap-3"
                                        style={{
                                            borderColor: recording===o.v ? o.c : 'var(--border)',
                                            background: recording===o.v ? `color-mix(in srgb, ${o.c} 8%, var(--card))` : 'var(--card)'
                                        }}>
                                    <span className="material-icons-round shrink-0" style={{fontSize:24,color:o.c}}>{o.i}</span>
                                    <div className="flex-1 min-w-0">
                                        <div className="text-sm font-bold" style={{color:recording===o.v?o.c:'var(--foreground)'}}>{o.l}</div>
                                        <div className="text-xs mt-0.5" style={{color:'var(--muted-foreground)'}}>{o.d}</div>
                                    </div>
                                    {recording===o.v && (
                                        <span className="shrink-0 rounded-full flex items-center justify-center"
                                              style={{width:22,height:22,background:o.c}}>
                                            <span className="material-icons-round text-white" style={{fontSize:14}}>check</span>
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                        <DialogFooter>
                            <Button onClick={()=>setShowRecModal(false)}>
                                <span className="material-icons-round mr-1.5" style={{fontSize:14}}>check</span>
                                Listo
                            </Button>
                        </DialogFooter>
                    </Dialog>

                    {/* ─── Lightbox para snapshot clickeado ─── */}
                    {lightboxShot && (
                        <Dialog open={true} onOpenChange={()=>setLightboxShot(null)}>
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <span className="material-icons-round" style={{fontSize:20,color:'var(--horizon-green)'}}>photo</span>
                                    Captura del {lightboxShot.timestamp}
                                </DialogTitle>
                                <DialogDescription>
                                    Snapshot del videoportero / cámara
                                </DialogDescription>
                            </DialogHeader>
                            <div className="rounded-lg overflow-hidden border" style={{borderColor:'var(--border)',background:'#0a0a0d'}}>
                                <img src={lightboxShot.url} alt={lightboxShot.timestamp}
                                     className="w-full max-h-[70vh] object-contain"
                                     style={{display:'block'}}/>
                            </div>
                            <DialogFooter>
                                <Button asLink href={lightboxShot.url} target="_blank" variant="outline">
                                    <span className="material-icons-round mr-1.5" style={{fontSize:14}}>open_in_new</span>
                                    Abrir en pestaña
                                </Button>
                                <Button onClick={()=>setLightboxShot(null)}>
                                    Cerrar
                                </Button>
                            </DialogFooter>
                        </Dialog>
                    )}

                </div>

                {/* COLUMNA LATERAL */}
                <div className="flex flex-col gap-5">
                    {/* Foto de perfil */}
                    <Card>
                        <CardHeader className="pb-3 items-center text-center">
                            <CardTitle className="flex items-center justify-center gap-2 text-sm">
                                <span className="material-icons-round" style={{fontSize:16,color:'#ec4899'}}>photo_camera</span>
                                Foto de perfil
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col items-center gap-2">
                            <AvatarUploader ext={form.ext} name={form.name} onUploaded={u=>setAvatarUrl(u)} size={100}/>
                            <p className="text-[10px] text-center leading-relaxed mt-1" style={{color:'var(--muted-foreground)'}}>
                                JPG/PNG hasta 2MB.<br/>Aparece en toda la app.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Tip aplicar cambios */}
                    <div className="rounded-md border px-3 py-3 flex gap-2.5 items-start"
                         style={{
                             borderColor:'color-mix(in srgb, var(--primary) 25%, transparent)',
                             background:'color-mix(in srgb, var(--primary) 6%, transparent)'
                         }}>
                        <span className="material-icons-round shrink-0" style={{fontSize:16,color:'var(--primary)',marginTop:1}}>info</span>
                        <p className="text-xs leading-relaxed" style={{color:'var(--muted-foreground)'}}>
                            Los cambios aplicarán un <strong style={{color:'var(--foreground)'}}>core reload</strong> automático en Asterisk para sincronizar SIP y dialplan.
                        </p>
                    </div>

                    {/* Quick actions (solo si no es nuevo) */}
                    {!isNew && ext && (
                        <Card>
                            <CardHeader className="pb-2 items-center text-center">
                                <CardTitle className="flex items-center justify-center gap-2 text-sm">
                                    <span className="material-icons-round" style={{fontSize:16,color:'var(--horizon-green)'}}>flash_on</span>
                                    Acciones rápidas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1.5 pt-1">
                                <Button variant="ghost" size="sm" className="w-full justify-start"
                                        onClick={()=>setActiveTab('historial')}>
                                    <span className="material-icons-round mr-2" style={{fontSize:15}}>history</span>
                                    Ver llamadas de este interno
                                </Button>
                                <Button variant="ghost" size="sm" className="w-full justify-start"
                                        onClick={()=>setActiveTab('agentes')}>
                                    <span className="material-icons-round mr-2" style={{fontSize:15}}>support_agent</span>
                                    Sesiones de agentes
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
            )}
        </div>
    );
}
function ExtensionRow({ e, liveCalls, onClick }) {
    const [tick, setTick] = useState(0);
    // Encontrar la llamada activa de esta extensión
    const myCall = liveCalls.find(c => 
        String(c.ext) === String(e.ext) || 
        String(c.dest) === String(e.ext) ||
        String(c.peer_ext) === String(e.ext)
    );
    // Encontrar peer (la otra parte): si yo soy ext en la llamada, peer es dest; si soy dest, peer es ext
    let peer = null;
    if (myCall) {
        if (String(myCall.ext) === String(e.ext)) peer = myCall.dest || myCall.peer_ext;
        else if (String(myCall.dest) === String(e.ext)) peer = myCall.ext;
        else if (String(myCall.peer_ext) === String(e.ext)) peer = myCall.ext;
        if (peer && !/^\d+$/.test(String(peer))) peer = fmtDest(myCall);
    }
    // Timer local: incrementar cada segundo cuando hay llamada
    useEffect(() => {
        if (!myCall) return;
        const t = setInterval(() => setTick(k => k+1), 1000);
        return () => clearInterval(t);
    }, [myCall?.channel]);

    // Parsear duración inicial HH:MM:SS o MM:SS
    const parseDur = (s) => {
        if (!s) return 0;
        const parts = String(s).split(':').map(x=>parseInt(x)||0);
        if (parts.length===3) return parts[0]*3600+parts[1]*60+parts[2];
        if (parts.length===2) return parts[0]*60+parts[1];
        return parts[0]||0;
    };
    const fmtDur = (s) => {
        const h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sc = s%60;
        return h>0 ? `${h}:${String(m).padStart(2,'0')}:${String(sc).padStart(2,'0')}` : `${m}:${String(sc).padStart(2,'0')}`;
    };
    const liveDur = myCall ? parseDur(myCall.duration) + tick : 0;

    // Status visual: Grandstream-like icons + colors
    const statusMap = {
        ONLINE:  { label:'Idle',           color:'#22c55e', bg:'rgba(34,197,94,0.12)',  icon:'check_circle' },
        BUSY:    { label:'En Llamada',     color:'#ef4444', bg:'rgba(239,68,68,0.12)',  icon:'phone_in_talk' },
        OFFLINE: { label:'Desconectado',   color:'#6b7280', bg:'rgba(107,114,128,0.12)',icon:'radio_button_unchecked' }
    };
    const st = statusMap[e.status] || statusMap.OFFLINE;
    const isBusy = e.status === 'BUSY';

    // HORIZON: tipo interno desde data global
    const tipo = (window._tfExtMeta || {})[e.ext]?.tipo || '';
    const tipoColor = tipo === 'cliente' ? '#3b82f6' : (tipo === 'horizon' ? '#8b5cf6' : '#6b7280');
    const tipoLabel = tipo === 'cliente' ? 'Cliente' : (tipo === 'horizon' ? 'Horizon' : '—');

    return (
        <tr onClick={onClick} style={{cursor:'pointer',transition:'background 0.15s'}}>
            <td style={{padding:'10px 14px'}}>
                <span style={{display:'inline-flex',alignItems:'center',gap:7,fontSize:11,fontWeight:800,color:st.color,padding:'4px 10px',borderRadius:6,background:st.bg,border:`1px solid ${st.color}33`}}>
                    <span className="material-icons-round" style={{fontSize:13,animation:isBusy?'phone-shake 0.6s ease-in-out infinite':'none'}}>{st.icon}</span>
                    {st.label}
                </span>
            </td>
            <td>
                {(() => {
                    const localUrl = `uploads/avatars/${e.ext}.jpg`;
                    const isKnown404 = (typeof localStorage !== 'undefined') && localStorage.getItem('tf_av_404_'+e.ext) === '1';
                    if (isKnown404 || (!e.avatar) || e.avatar.includes('ui-avatars')) return null;
                    return <img src={e.avatar} style={{width:32,height:32,borderRadius:'50%',objectFit:'cover',border:'2px solid var(--border)'}} onError={ev=>{try{localStorage.setItem('tf_av_404_'+e.ext,'1');}catch(_){}; ev.target.style.display='none'; if(ev.target.nextSibling) ev.target.nextSibling.style.display='flex';}} />;
                })()}
                <div style={{width:32,height:32,borderRadius:'50%',background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',display:e.avatar && !e.avatar.includes('ui-avatars')?'none':'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:10,fontWeight:900}}>
                    {(e.name||e.ext||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}
                </div>
            </td>
            <td>
                <div style={{display:'flex',alignItems:'center',gap:6}}>
                    <span style={{fontFamily:'monospace',fontWeight:800,color:'var(--text)',fontSize:13}}>#{e.ext}</span>
                    {e.recording === 'always' && <span title="Grabación siempre" className="material-icons-round" style={{fontSize:13,color:'#ef4444'}}>fiber_manual_record</span>}
                    {e.device_type === 'softphone' && <span title="WebRTC" className="material-icons-round" style={{fontSize:13,color:'#3b82f6'}}>computer</span>}
                    {e.device_type === 'phone' && <span title="Teléfono SIP" className="material-icons-round" style={{fontSize:13,color:'#6b7280'}}>phone</span>}
                </div>
            </td>
            <td style={{fontSize:13,fontWeight:600}}>{e.name}</td>
            <td>
                {peer ? (
                    <span style={{display:'inline-flex',alignItems:'center',gap:6,fontFamily:'monospace',fontSize:12,fontWeight:700,color:'#fbbf24'}}>
                        <span className="material-icons-round" style={{fontSize:14}}>swap_horiz</span>
                        {peer}
                    </span>
                ) : <span style={{color:'var(--muted)',fontSize:11}}>—</span>}
            </td>
            <td>
                {isBusy ? (
                    <span style={{fontFamily:'monospace',fontWeight:800,color:'#22c55e',fontSize:12,display:'inline-flex',alignItems:'center',gap:5}}>
                        <span style={{width:6,height:6,borderRadius:'50%',background:'#22c55e',animation:'pulse 1s infinite'}}/>
                        {fmtDur(liveDur)}
                    </span>
                ) : <span style={{color:'var(--muted)',fontSize:11}}>—</span>}
            </td>
            <td><code style={{fontSize:11,color:'#ec4899'}}>{e.ip}</code></td>
            <td style={{color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontSize:11,fontFamily:'monospace'}}>{e.rtt}</td>
            <td>
                {tipo ? (
                    <span style={{fontSize:10,padding:'3px 9px',borderRadius:5,background:`${tipoColor}22`,color:tipoColor,fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em',border:`1px solid ${tipoColor}55`}}>
                        {tipoLabel}
                    </span>
                ) : <span style={{fontSize:10,color:'var(--muted)'}}>—</span>}
            </td>
            <td>
                <span style={{fontSize:10,padding:'2px 7px',borderRadius:4,background:'rgba(255,255,255,0.05)',color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>
                    {e.device_type === 'softphone' ? 'WebRTC' : 'SIP'}
                </span>
            </td>
            <td><span className="material-icons-round" style={{fontSize:17,color:'#6b7280'}}>chevron_right</span></td>
        </tr>
    );
}


function ViewExtensiones({ data, toast }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [viewMode, setViewMode] = useState(localStorage.getItem('tf_ext_view') || 'table');
    const [editing, setEditing] = useState(null); // null | 'new' | ext object
    const [saved, setSaved] = useState(0);

    // HORIZON: skeleton loader si todavía no llegó la primer data
    if (!data || !data.pbx) {
        return (
            <div className="content-area view-enter">
                <div style={{display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(220px, 1fr))', gap:16, padding:'20px 0'}}>
                    {Array.from({length: 12}).map((_,i)=>(
                        <div key={i} className="glass animate-pulse" style={{padding:18, borderRadius:18, height:110, border:'1px solid var(--border)', background:'linear-gradient(90deg, rgba(255,255,255,0.04), rgba(255,255,255,0.08), rgba(255,255,255,0.04))'}}>
                            <div style={{width:48, height:48, borderRadius:'50%', background:'rgba(255,255,255,0.08)', marginBottom:10}} />
                            <div style={{width:'70%', height:10, borderRadius:4, background:'rgba(255,255,255,0.08)', marginBottom:6}} />
                            <div style={{width:'40%', height:8, borderRadius:4, background:'rgba(255,255,255,0.06)'}} />
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    const allExts = data?.pbx?.extensions || [];
    const liveCalls = data?.pbx?.live_calls || [];
    const onlineTotal = allExts.filter(e=>e.status==='ONLINE').length;
    const busyTotal   = allExts.filter(e=>e.status==='BUSY').length;
    const offlineTotal = allExts.length - onlineTotal - busyTotal;

    const exts = allExts.filter(e => {
        const matchesSearch = e.ext.includes(search) || e.name.toLowerCase().includes(search.toLowerCase());
        const matchesStatus = !statusFilter || e.status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    const badgeCls = s => s==='ONLINE'?'badge-online':s==='BUSY'?'badge-busy':'badge-offline';
    const dotCls   = s => s==='ONLINE'?'dot-online':s==='BUSY'?'dot-busy':'dot-offline';

    // Si está editando, mostrar página de edición en lugar de la lista
    if (editing) {
        return (
            <ExtEditPage
                ext={editing === 'new' ? null : editing}
                onBack={() => setEditing(null)}
                onSaved={() => { setEditing(null); setSaved(s=>s+1); }}
                toast={toast}
            />
        );
    }

    const Chip = ({ label, count, status, color, bg }) => (
        <div 
            onClick={() => setStatusFilter(statusFilter === status ? '' : status)}
            style={{
                padding:'6px 12px', borderRadius:8, background: statusFilter === status ? bg : 'rgba(255,255,255,0.03)',
                border:`1px solid ${statusFilter === status ? color : 'var(--border)'}`,
                color: statusFilter === status ? color : 'var(--muted)',
                fontWeight:700, whiteSpace:'nowrap', cursor:'pointer', fontSize:11, transition:'all 0.2s',
                display:'flex', alignItems:'center', gap:6
            }}
        >
            <span style={{ width:6, height:6, borderRadius:'50%', background:color }} />
            {count} {label}
        </div>
    );

    return (
        <div className="content-area view-enter">
            <PageActions>
                <div style={{position:'relative',width:240}}>
                    <span className="material-icons-round" style={{position:'absolute',left:11,top:'50%',transform:'translateY(-50%)',fontSize:17,color:'#6b7280'}}>search</span>
                    <input className="input-tf py-2 pl-10 pr-4 rounded-xl text-sm" placeholder="Buscar extensión..." value={search} onChange={e=>setSearch(e.target.value)} style={{padding:'8px 10px 8px 32px',width:'100%'}}/>
                </div>
                <Chip label="Online" count={onlineTotal} status="ONLINE" color="#4ade80" bg="rgba(34,197,94,0.1)" />
                <Chip label="En Llamada" count={busyTotal} status="BUSY" color="#ef4444" bg="rgba(239,68,68,0.1)" />
                <Chip label="Offline" count={offlineTotal} status="OFFLINE" color="#9ca3af" bg="rgba(107,114,128,0.1)" />
                <div style={{display:'flex',gap:4,background:'var(--surface2)',borderRadius:10,padding:4,border:'1px solid var(--border)'}}>
                    {['grid','table'].map(m=>(
                        <button key={m} onClick={()=>{setViewMode(m);try{localStorage.setItem('tf_ext_view',m);}catch(e){}}} style={{padding:'6px 10px',borderRadius:8,border:'none',cursor:'pointer',background:viewMode===m?'rgba(139,92,246,.25)':'transparent',color:viewMode===m?'#c4b5fd':'#6b7280',transition:'all .2s'}}>
                            <span className="material-icons-round" style={{fontSize:16,display:'block'}}>{m==='grid'?'grid_view':'table_rows'}</span>
                        </button>
                    ))}
                </div>
                <button className="btn-primary" style={{padding:'8px 14px',borderRadius:10,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={()=>setEditing('new')}>
                    <span className="material-icons-round" style={{fontSize:16}}>add</span>Nueva
                </button>
            </PageActions>

            {/* GRID — split en activas (top) / offline */}
            {viewMode==='grid' && (() => {
                const isExtRinging = (e) => liveCalls.some(c => /Ring/i.test(c.state||'') && (String(c.ext)===String(e.ext) || String(c.dest)===String(e.ext)));
                const isExtBusy = (e) => e.status === 'BUSY' || liveCalls.some(c => /Up/i.test(c.state||'') && (String(c.ext)===String(e.ext) || String(c.dest)===String(e.ext)));
                const activeExts  = exts.filter(e => e.status === 'ONLINE' || e.status === 'BUSY');
                const offlineExts = exts.filter(e => e.status !== 'ONLINE' && e.status !== 'BUSY');

                const renderActive = (e) => {
                    const ringing = isExtRinging(e);
                    const busy = isExtBusy(e);
                    const sc = busy ? '#ef4444' : (e.status === 'ONLINE' ? '#22c55e' : '#6b7280');
                    return (
                        <div
                            key={e.ext}
                            onClick={()=>setEditing(e)}
                            className={cn(
                                "rounded-xl border bg-card text-card-foreground overflow-hidden cursor-pointer transition-all hover:bg-muted/40",
                                ringing && "hzn-ringing"
                            )}
                            style={{borderColor:'var(--border)', width:200}}
                        >
                            <div style={{height:3, background:sc}}/>
                            <div style={{padding:'14px 14px 12px', textAlign:'center'}}>
                                <div style={{position:'relative', width:64, height:64, margin:'0 auto 10px'}}>
                                    <img src={e.avatar} alt={e.name}
                                        onError={(ev)=>{ev.target.style.display='none'; const n=ev.target.nextSibling; if(n) n.style.display='flex';}}
                                        style={{width:64, height:64, borderRadius:'50%', objectFit:'cover', display:'block', border:`2px solid ${sc}`, boxShadow:`0 4px 14px ${sc}55`}}/>
                                    <div style={{display:'none', width:64, height:64, borderRadius:'50%', background:`linear-gradient(135deg, ${sc}, ${sc}aa)`, alignItems:'center', justifyContent:'center', color:'#fff', fontSize:18, fontWeight:900, border:`2px solid ${sc}`}}>
                                        {initials(e.name)}
                                    </div>
                                    <span style={{position:'absolute', bottom:0, right:6, width:14, height:14, borderRadius:'50%', background:sc, border:'3px solid var(--card)', animation:busy?'pulse-ring 1.5s infinite':''}}/>
                                </div>
                                <div style={{fontSize:14, fontWeight:800, color:'var(--text)', marginBottom:3, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{e.name}</div>
                                <div style={{fontSize:22, fontWeight:900, color:'var(--horizon-green)', lineHeight:1, fontFamily:'monospace', letterSpacing:'-.5px', marginBottom:6}}>#{e.ext}</div>
                                <div style={{fontSize:9, fontWeight:900, padding:'4px 10px', borderRadius:6, background:`${sc}22`, color:sc, display:'inline-block', letterSpacing:'.1em', marginBottom:8}}>
                                    {busy ? 'EN LLAMADA' : 'ONLINE'}{ringing ? ' · LLAMADA ENTRANTE' : ''}
                                </div>
                                <div style={{display:'flex', gap:14, fontSize:10, color:'var(--muted)', justifyContent:'center'}}>
                                    {e.ip && e.ip !== '—' && <span style={{fontFamily:'monospace'}}>{e.ip}</span>}
                                    {e.rtt && e.rtt !== '—' && <span style={{fontFamily:'monospace', opacity:0.7}}>· {e.rtt}</span>}
                                </div>
                            </div>
                        </div>
                    );
                };

                const renderOffline = (e) => (
                    <div key={e.ext} onClick={()=>setEditing(e)} className="rounded-lg border border-border bg-card text-card-foreground cursor-pointer transition-colors hover:bg-muted/40" style={{opacity:0.6}}>
                        <div style={{padding:'10px 12px', display:'flex', alignItems:'center', gap:10}}>
                            <div className={`agent-avatar bg-gradient-to-br ${getColor(e.name)}`} style={{display:'flex', width:32, height:32, fontSize:11, flexShrink:0}}>{initials(e.name)}</div>
                            <div style={{flex:1, minWidth:0}}>
                                <div style={{fontSize:12, fontWeight:700, color:'var(--text)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{e.name}</div>
                                <div style={{fontSize:10, color:'var(--muted)', fontFamily:'monospace'}}>#{e.ext}</div>
                            </div>
                            <span style={{fontSize:9, padding:'2px 7px', borderRadius:4, background:'rgba(107,114,128,0.18)', color:'#9ca3af', fontWeight:800}}>OFFLINE</span>
                        </div>
                    </div>
                );

                return (
                    <div style={{display:'flex', flexDirection:'column', gap:18}}>
                        {activeExts.length > 0 && (
                            <div>
                                <div style={{display:'flex', alignItems:'center', gap:8, marginBottom:10, justifyContent:'center'}}>
                                    <span style={{width:6, height:6, borderRadius:'50%', background:'var(--horizon-green)', animation:'pulse 2s infinite'}}/>
                                    <span style={{fontSize:11, fontWeight:800, color:'var(--horizon-green)', textTransform:'uppercase', letterSpacing:'.08em'}}>{activeExts.length} extensione{activeExts.length!==1?'s activas':' activa'}</span>
                                </div>
                                <div style={{display:'flex', flexWrap:'wrap', gap:14, justifyContent:'center'}}>
                                    {activeExts.map(renderActive)}
                                </div>
                            </div>
                        )}
                        {offlineExts.length > 0 && (
                            <div>
                                <div style={{display:'flex', alignItems:'center', gap:8, marginBottom:10}}>
                                    <span style={{width:6, height:6, borderRadius:'50%', background:'#6b7280'}}/>
                                    <span style={{fontSize:11, fontWeight:800, color:'var(--muted)', textTransform:'uppercase', letterSpacing:'.08em'}}>{offlineExts.length} offline</span>
                                </div>
                                <div style={{display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(220px, 1fr))', gap:8}}>
                                    {offlineExts.map(renderOffline)}
                                </div>
                            </div>
                        )}
                        {exts.length === 0 && <div className="rounded-lg border border-border bg-card p-10 text-center text-muted-foreground">Sin extensiones</div>}
                    </div>
                );
            })()}

            {/* TABLE — estilo Grandstream UCM */}
            {viewMode==='table' && (
                <div className="glass" style={{overflow:'hidden',borderRadius:12}}>
                    <table className="tf-table">
                        <thead>
                            <tr>
                                <th style={{width:130}}>Estado</th>
                                <th style={{width:60}}></th>
                                <th>Extensión</th>
                                <th>Nombre</th>
                                <th>Hablando con</th>
                                <th>Duración</th>
                                <th>IP</th>
                                <th>RTT</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th style={{width:40}}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {exts.map(e => <ExtensionRow key={e.ext} e={e} liveCalls={liveCalls} onClick={()=>setEditing(e)} />)}
                            {exts.length===0&&<tr><td colSpan={10} style={{textAlign:'center',color:'#6b7280',padding:30}}>Sin extensiones</td></tr>}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: AGENTES - Dashboard Activo
// ─────────────────────────────────────────────
function ViewAgentes({ toast, data }) {
    const [agents, setAgents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [selected, setSelected] = useState(null);
    const [viewMode, setViewMode] = useState(localStorage.getItem('tf_ag_view') || 'table');
    const [editing, setEditing] = useState(null);

    const saveAgent = async (form) => {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k, v));
        const isNew = !form.id;
        const r = await fetch('api/hotdesking.php?action=' + (isNew?'create':'update'), {method:'POST', body:fd, credentials:'include'});
        const j = await r.json();
        if (j.status === 'ok') { toast?.(isNew?'Agente creado':'Actualizado','success'); setEditing(null); load(); }
        else toast?.(j.message||'Error','error');
    };

    const liveCalls = data?.pbx?.live_calls || [];
    const exts = data?.pbx?.extensions || [];

    const load = async () => {
        try {
            const r = await fetch('api/hotdesking.php?action=list', {credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') setAgents(j.agents || []);
        } catch(e) {}
        setLoading(false);
    };
    useEffect(()=>{ load(); const t = setInterval(load, 6000); return ()=>clearInterval(t); }, []);

    // Cruzar agent.extension con live_calls para detectar EN LLAMADA
    const enriched = agents.map(a => {
        const inCall = a.extension && liveCalls.some(c => c.ext === a.extension || c.dest === a.extension);
        const extInfo = a.extension ? exts.find(x => x.ext === a.extension) : null;
        return {
            ...a,
            in_call: !!inCall,
            ip: extInfo?.ip,
            rtt: extInfo?.rtt,
            ext_name: extInfo?.name,
            queues: a.queues || []
        };
    });

    const totalOnline = enriched.filter(a => a.logged_in && !a.in_call).length;
    const totalBusy   = enriched.filter(a => a.in_call).length;
    const totalLogged = enriched.filter(a => a.logged_in).length;

    const filtered = enriched.filter(a => {
        if (statusFilter === 'online' && (!a.logged_in || a.in_call)) return false;
        if (statusFilter === 'busy' && !a.in_call) return false;
        if (statusFilter === 'offline' && a.logged_in) return false;
        if (!search) return true;
        const q = search.toLowerCase();
        return (a.number||'').includes(search) || (a.name||'').toLowerCase().includes(q) || (a.extension||'').includes(search);
    });

    if (loading) {
        return (
            <div className="content-area view-enter">
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(280px,1fr))',gap:14}}>
                    {Array.from({length:8}).map((_,i)=>(
                        <div key={i} className="glass animate-pulse" style={{padding:16,borderRadius:14,height:120,opacity:0.5}}>
                            <div style={{width:48,height:48,borderRadius:'50%',background:'rgba(255,255,255,0.08)',marginBottom:8}}/>
                            <div style={{width:'70%',height:10,background:'rgba(255,255,255,0.08)',borderRadius:4}}/>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    const Chip = ({label, count, value, color}) => (
        <div onClick={()=>setStatusFilter(statusFilter===value?'':value)} style={{
            padding:'7px 14px', borderRadius:8, cursor:'pointer',
            background: statusFilter===value ? `${color}33` : 'rgba(255,255,255,0.03)',
            border: `1px solid ${statusFilter===value ? color : 'var(--border)'}`,
            color: statusFilter===value ? color : 'var(--muted)',
            fontWeight:700, fontSize:11, whiteSpace:'nowrap',
            display:'flex', alignItems:'center', gap:6, transition:'all 0.2s'
        }}>
            <span style={{width:6,height:6,borderRadius:'50%',background:color}}/>
            {count} {label}
        </div>
    );

    return (
        <div className="content-area view-enter">
            <PageActions>
                <Chip label="Online" count={totalOnline} value="online" color="#22c55e" />
                <Chip label="En Llamada" count={totalBusy} value="busy" color="#ef4444" />
                <Chip label="Offline" count={agents.length - totalLogged} value="offline" color="#6b7280" />
                <input className="input-tf py-2 px-4 rounded-xl text-sm" style={{width:220}}
                    placeholder="Buscar agente, número o ext..."
                    value={search} onChange={e=>setSearch(e.target.value)} />
                <div style={{display:'flex',gap:4,background:'var(--surface2)',borderRadius:10,padding:4,border:'1px solid var(--border)'}}>
                    {['table','grid'].map(m=>(
                        <button key={m} onClick={()=>{setViewMode(m);try{localStorage.setItem('tf_ag_view',m);}catch(e){}}} style={{padding:'6px 14px',borderRadius:7,border:'none',cursor:'pointer',background:viewMode===m?'rgba(139,92,246,0.25)':'transparent',color:viewMode===m?'#c4b5fd':'var(--muted)',fontWeight:700,fontSize:11,display:'flex',alignItems:'center',gap:5}}>
                            <span className="material-icons-round" style={{fontSize:14}}>{m==='grid'?'grid_view':'table_rows'}</span>
                            {m==='grid'?'Tarjetas':'Tabla'}
                        </button>
                    ))}
                </div>
            </PageActions>

            {viewMode==='table' && (
                <div className="glass" style={{borderRadius:12,overflow:'hidden'}}>
                    <table className="tf-table">
                        <thead>
                            <tr>
                                <th style={{padding:'10px 14px',width:90}}>Estado</th>
                                <th style={{width:50}}></th>
                                <th>Agente #</th>
                                <th>Nombre</th>
                                <th>Extensión</th>
                                <th>IP</th>
                                <th>Colas</th>
                                <th style={{width:80}}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map(a => {
                                const sc = a.in_call ? '#ef4444' : (a.logged_in ? '#22c55e' : '#6b7280');
                                const lbl = a.in_call?'En llamada':(a.logged_in?'Disponible':'Offline');
                                return (
                                <tr key={a.id} onClick={()=>setSelected(a)} style={{cursor:'pointer'}}>
                                    <td style={{padding:'8px 14px'}}>
                                        <span style={{display:'inline-flex',alignItems:'center',gap:6,fontSize:11,fontWeight:700,color:sc}}>
                                            <span style={{width:7,height:7,borderRadius:'50%',background:sc,boxShadow:`0 0 8px ${sc}66`}}/>{lbl}
                                        </span>
                                    </td>
                                    <td>
                                        <div style={{width:30,height:30,borderRadius:'50%',background:`linear-gradient(135deg,${sc},${sc}88)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:10,fontWeight:900}}>
                                            {(a.name||'').split(' ').map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                                        </div>
                                    </td>
                                    <td style={{fontFamily:'monospace',fontWeight:700,fontSize:12}}>#{a.number}</td>
                                    <td style={{fontSize:13,fontWeight:700}}>{a.name}</td>
                                    <td style={{fontFamily:'monospace',fontSize:12}}>{a.extension||'—'}</td>
                                    <td style={{fontFamily:'monospace',fontSize:11,color:'var(--muted)'}}>{a.ip||'—'}</td>
                                    <td>
                                        <div style={{display:'flex',gap:3,flexWrap:'wrap'}}>
                                            {a.queues.slice(0,4).map((q,i)=>(
                                                <span key={i} style={{fontSize:9,padding:'1px 6px',borderRadius:4,background:'color-mix(in srgb, var(--primary) 15%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontFamily:'monospace',fontWeight:700}}>
                                                    Q{q.queue||q}
                                                </span>
                                            ))}
                                            {a.queues.length>4 && <span style={{fontSize:9,color:'var(--muted)'}}>+{a.queues.length-4}</span>}
                                        </div>
                                    </td>
                                    <td style={{textAlign:'right',padding:'0 14px'}}>
                                        <span className="material-icons-round" style={{fontSize:16,color:'var(--muted)'}}>chevron_right</span>
                                    </td>
                                </tr>
                                );
                            })}
                            {filtered.length===0 && <tr><td colSpan={8} style={{textAlign:'center',padding:30,color:'var(--muted)'}}>Ningún agente coincide</td></tr>}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Grid (tarjetas) opcional */}
            {viewMode==='grid' && <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(290px,1fr))',gap:12}}>
                {filtered.map(a => {
                    const statusColor = a.in_call ? '#ef4444' : (a.logged_in ? '#22c55e' : '#6b7280');
                    return (
                    <div key={a.id} onClick={()=>setSelected(a)} style={{
                        padding:14, borderRadius:12, cursor:'pointer',
                        background:'var(--surface)',
                        border:`1px solid ${a.logged_in?statusColor+'40':'var(--border)'}`,
                        transition:'all 0.2s'
                    }}>
                        <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:8}}>
                            <div style={{width:42,height:42,borderRadius:'50%',background:`linear-gradient(135deg,${statusColor},${statusColor}88)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontWeight:900,fontSize:13,position:'relative'}}>
                                {(a.name||'').split(' ').map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                                {a.in_call && <span style={{position:'absolute',bottom:-2,right:-2,width:14,height:14,borderRadius:'50%',background:'#ef4444',border:'2px solid var(--surface)',animation:'pulse-ring 1.5s infinite'}}/>}
                            </div>
                            <div style={{flex:1,minWidth:0}}>
                                <div style={{fontSize:13,fontWeight:800,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{a.name}</div>
                                <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>Agente #{a.number}</div>
                            </div>
                        </div>
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:8}}>
                            <span style={{fontSize:9,fontWeight:800,padding:'3px 8px',borderRadius:5,background:`${statusColor}22`,color:statusColor,textTransform:'uppercase'}}>
                                {a.in_call ? 'En llamada' : (a.logged_in ? 'Disponible' : 'Offline')}
                            </span>
                            {a.extension && (
                                <span style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>
                                    ext {a.extension}{a.ip && a.ip!=='—' ? ' · '+a.ip : ''}
                                </span>
                            )}
                        </div>
                        {a.queues.length > 0 && (
                            <div style={{display:'flex',flexWrap:'wrap',gap:3}}>
                                {a.queues.slice(0,5).map((q,i)=>(
                                    <span key={i} style={{fontSize:9,padding:'1px 6px',borderRadius:4,background:'color-mix(in srgb, var(--primary) 15%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontFamily:'monospace',fontWeight:700}}>
                                        Q{q.queue || q}
                                    </span>
                                ))}
                                {a.queues.length > 5 && <span style={{fontSize:9,color:'var(--muted)'}}>+{a.queues.length-5}</span>}
                            </div>
                        )}
                    </div>
                    );
                })}
                {filtered.length===0 && (
                    <div className="glass" style={{padding:40,textAlign:'center',color:'var(--muted)',gridColumn:'1/-1'}}>
                        <span className="material-icons-round" style={{fontSize:40}}>person_search</span>
                        <div style={{marginTop:6,fontSize:12}}>Ningún agente coincide</div>
                    </div>
                )}
            </div>}

            {selected && <AgentDetailModal agent={selected} onClose={()=>setSelected(null)} onEdit={()=>{ setEditing(selected); setSelected(null); }} />}
            {editing && <HotdeskingEditModal agent={editing} onClose={()=>setEditing(null)} onSave={saveAgent} queues={data?.pbx?.queues||[]} />}

            {/* Footer: contador */}
            <div style={{marginTop:18,padding:'10px 14px',display:'flex',alignItems:'center',justifyContent:'flex-end',gap:8,fontSize:11,color:'var(--muted)',fontWeight:700,borderTop:'1px solid var(--border)'}}>
                <span className="material-icons-round" style={{fontSize:14,color:'var(--muted)'}}>group</span>
                Mostrando <strong style={{color:'var(--text)',margin:'0 4px'}}>{filtered.length}</strong>
                de <strong style={{color:'var(--text)',margin:'0 4px'}}>{agents.length}</strong> agentes
                · <span style={{color:'#22c55e'}}>{totalLogged} activos</span>
            </div>
        </div>
    );
}

function AgentDetailModal({ agent, onClose, onEdit }) {
    const sc = agent.in_call?'#ef4444':(agent.logged_in?'#22c55e':'#6b7280');
    const lbl = agent.in_call?'En llamada':(agent.logged_in?'Disponible':'Offline');
    const inits = (agent.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase();
    return (

        <LegacyDialogShell onClose={onClose} maxWidth={560}>
                {/* Header con gradient */}
                <div style={{padding:'18px 22px',background:`linear-gradient(135deg, ${sc}22, transparent 70%)`,borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:14}}>
                    <div style={{width:54,height:54,borderRadius:14,background:`linear-gradient(135deg,${sc},${sc}aa)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontWeight:900,fontSize:18,boxShadow:`0 6px 18px ${sc}55`,position:'relative',flexShrink:0}}>
                        {inits}
                        {agent.logged_in && <span style={{position:'absolute',bottom:-2,right:-2,width:14,height:14,borderRadius:'50%',background:sc,border:'3px solid var(--surface)',boxShadow:`0 0 8px ${sc}`}}/>}
                    </div>
                    <div style={{flex:1,minWidth:0}}>
                        <h2 style={{fontSize:18,fontWeight:900,letterSpacing:'-0.3px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{agent.name}</h2>
                        <div style={{fontSize:11,color:'var(--muted)',display:'flex',alignItems:'center',gap:8,marginTop:2,fontFamily:'monospace'}}>
                            <span>Agente #{agent.number}</span>
                            <span style={{opacity:.4}}>·</span>
                            <span>{agent.type || 'Agent'}</span>
                            <span style={{opacity:.4}}>·</span>
                            <span style={{color:sc,fontWeight:800}}>{lbl}</span>
                        </div>
                    </div>
                    <button onClick={onClose} style={{padding:8,borderRadius:10,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:20,color:'var(--muted)'}}>close</span>
                    </button>
                </div>

                {/* Body */}
                <div style={{padding:'18px 22px',display:'flex',flexDirection:'column',gap:12}}>
                    {/* Grid info en vivo */}
                    <div style={{display:'grid',gridTemplateColumns:'repeat(2,1fr)',gap:10}}>
                        <div className="glass" style={{padding:'12px 14px',borderRadius:12}}>
                            <div style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:4}}>Extensión</div>
                            <div style={{fontSize:14,fontWeight:800,fontFamily:'monospace'}}>{agent.extension || '—'}</div>
                            {agent.ext_name && <div style={{fontSize:10,color:'var(--muted)',marginTop:2}}>{agent.ext_name}</div>}
                        </div>
                        <div className="glass" style={{padding:'12px 14px',borderRadius:12}}>
                            <div style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:4}}>Estado SIP</div>
                            <div style={{fontSize:14,fontWeight:800,color:sc,display:'flex',alignItems:'center',gap:6}}>
                                <span style={{width:7,height:7,borderRadius:'50%',background:sc,boxShadow:`0 0 8px ${sc}`}}/>
                                {lbl}
                            </div>
                        </div>
                        <div className="glass" style={{padding:'12px 14px',borderRadius:12}}>
                            <div style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:4}}>IP</div>
                            <div style={{fontSize:13,fontFamily:'monospace'}}>{agent.ip || '—'}</div>
                        </div>
                        <div className="glass" style={{padding:'12px 14px',borderRadius:12}}>
                            <div style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:4}}>Latencia</div>
                            <div style={{fontSize:13,fontFamily:'monospace'}}>{agent.rtt || '—'}</div>
                        </div>
                    </div>

                    {/* Colas asignadas */}
                    <div className="glass" style={{padding:'12px 14px',borderRadius:12}}>
                        <div style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.06em',marginBottom:8,display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:13,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>queue</span>
                            Colas asignadas ({(agent.queues||[]).length})
                        </div>
                        <div style={{display:'flex',flexWrap:'wrap',gap:6}}>
                            {(agent.queues||[]).length===0 && <span style={{fontSize:11,color:'var(--muted)',fontStyle:'italic'}}>Sin colas activas</span>}
                            {(agent.queues||[]).map((q,i)=>(
                                <span key={i} style={{fontSize:10,padding:'4px 10px',borderRadius:6,background:'color-mix(in srgb, var(--primary) 18%, transparent)',border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontFamily:'monospace',fontWeight:800}}>
                                    Q{q.queue || q}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Footer con acciones */}
                <div style={{padding:'14px 22px',borderTop:'1px solid var(--border)',display:'flex',gap:8,justifyContent:'flex-end',background:'rgba(139,92,246,0.04)'}}>
                    <button onClick={onClose} style={{padding:'9px 16px',borderRadius:10,border:'1px solid var(--border)',background:'transparent',color:'var(--muted)',fontWeight:700,fontSize:12,cursor:'pointer'}}>
                        Cerrar
                    </button>
                    <button onClick={onEdit} style={{padding:'9px 18px',borderRadius:10,border:'none',background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',color:'#fff',fontWeight:800,fontSize:12,cursor:'pointer',display:'flex',alignItems:'center',gap:6,boxShadow:'0 4px 14px rgba(139,92,246,0.4)'}}>
                        <span className="material-icons-round" style={{fontSize:16}}>edit</span>
                        Editar agente
                    </button>
                </div>
            
        </LegacyDialogShell>
    );
}


// ─────────────────────────────────────────────
// VISTA: VIVO
// ─────────────────────────────────────────────
function ViewVivo({ data }) {
    const calls = (data?.pbx?.calls || []);
    return (
        <div className="content-area">
            <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:20}}>
                <div className="live-indicator" style={{width:10,height:10,borderRadius:'50%',background:'#ef4444',flexShrink:0}} />
                <span style={{fontSize:13,fontWeight:700,color:'#ef4444'}}>TRANSMISIÓN EN VIVO</span>
                <span style={{fontSize:12,color:'#6b7280'}}>{calls.length} canales activos</span>
            </div>
            <div style={{display:'flex',flexDirection:'column',gap:10}}>
                {calls.length === 0
                    ? <div className="glass" style={{padding:40,textAlign:'center',color:'#6b7280'}}>
                        <span className="material-icons-round" style={{fontSize:48,marginBottom:12,display:'block',color:'#4b5563'}}>phone_disabled</span>
                        Sin llamadas activas en este momento
                      </div>
                    : calls.map((c,i)=>(
                        <div key={i} className="live-call-card">
                            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                                <div style={{display:'flex',gap:12,alignItems:'center'}}>
                                    <div style={{width:36,height:36,borderRadius:10,background:'color-mix(in srgb, var(--primary) 20%, transparent)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                        <span className="material-icons-round" style={{fontSize:18,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>call</span>
                                    </div>
                                    <div>
                                        <div style={{fontSize:13,fontWeight:700,color:'white'}}>{c.src} → {c.dst}</div>
                                        <div style={{fontSize:11,color:'#9ca3af'}}>{c.state || 'Up'}</div>
                                    </div>
                                </div>
                                <div style={{fontSize:13,fontWeight:700,color:'#f59e0b',fontFamily:'monospace'}}>{fmtTime(c.duration||0)}</div>
                            </div>
                        </div>
                    ))
                }
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: GRABACIONES
// ─────────────────────────────────────────────
function ViewGrabaciones({ data }) {
    const recs = data?.pbx?.recordings || [];
    return (
        <div className="content-area">
            <div style={{display:'flex',flexDirection:'column',gap:10}}>
                {recs.length === 0
                    ? <div className="glass" style={{padding:40,textAlign:'center',color:'#6b7280'}}>Sin grabaciones disponibles</div>
                    : recs.map((r,i)=>(
                        <div key={i} className="glass" style={{padding:'16px 18px'}}>
                            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:10}}>
                                <div style={{display:'flex',gap:10,alignItems:'center'}}>
                                    <div style={{width:34,height:34,borderRadius:9,background:'color-mix(in srgb, var(--primary) 15%, transparent)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                        <span className="material-icons-round" style={{fontSize:16,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>mic</span>
                                    </div>
                                    <div>
                                        <div style={{fontSize:13,fontWeight:700,color:'white'}}>#{r.src} → {r.dst}</div>
                                        <div style={{fontSize:11,color:'#6b7280'}}>{r.calldate?.substring(0,16)}</div>
                                    </div>
                                </div>
                                <div style={{display:'flex',gap:12,alignItems:'center'}}>
                                    <span style={{fontSize:11,padding:'4px 10px',borderRadius:8,background:'color-mix(in srgb, var(--primary) 12%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:600}}>{r.duration}s</span>
                                    <span style={{fontSize:11,padding:'4px 10px',borderRadius:8,background:r.disposition==='ANSWERED'?'rgba(34,197,94,0.12)':'rgba(239,68,68,0.12)',color:r.disposition==='ANSWERED'?'#4ade80':'#f87171',fontWeight:600}}>{r.disposition}</span>
                                </div>
                            </div>
                            {r.recordingfile && <audio controls src={`api/recording.php?file=${encodeURIComponent(r.recordingfile.split('/').pop())}`} style={{width:'100%'}} />}
                        </div>
                    ))
                }
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: CDR — Impresionante con iconos + export CSV
// ─────────────────────────────────────────────
const DISP_CFG = {
    'ANSWERED': {label:'Contestada',color:'#4ade80',bg:'rgba(34,197,94,0.12)',border:'rgba(34,197,94,0.25)',icon:'call'},
    'NO ANSWER': {label:'Sin Respuesta',color:'#9ca3af',bg:'rgba(107,114,128,0.12)',border:'rgba(107,114,128,0.25)',icon:'phone_missed'},
    'BUSY':      {label:'Comunicando',color:'#fbbf24',bg:'rgba(245,158,11,0.12)',border:'rgba(245,158,11,0.25)',icon:'phone_in_talk'},
    'FAILED':    {label:'Fallida',color:'#f87171',bg:'rgba(239,68,68,0.12)',border:'rgba(239,68,68,0.25)',icon:'phone_disabled'},
};

// ─────────────────────────────────────────────
// COMPONENTE: Audio Player profesional para CDR
// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// COMPONENTE: ExportButton (CSV / Excel / PDF profesional)
// ─────────────────────────────────────────────
function ExportButton({ rows, filename, title, stats }) {
    const [open, setOpen] = useState(false);

    const exportCSV = () => {
        if (!rows?.length) return;
        const cols = ['Fecha','CID','Origen','Destino','Dur. Total','Dur. Facturada','Estado','Grabación'];
        const lines = [cols.join(';'), ...rows.map(r => 
            [r.calldate, r.clid, r.src, r.dst, r.duration, r.billsec, r.disposition, r.recordingfile||''].join(';')
        )];
        const blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8;'});
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
        a.download = filename + '.csv'; a.click();
        setOpen(false);
    };

    const exportXLSX = () => {
        if (!rows?.length || !window.XLSX) return;
        const data = rows.map(r => ({
            Fecha: r.calldate, CID: r.clid, Origen: r.src, Destino: r.dst,
            'Duración Total (s)': r.duration, 'Duración Facturada (s)': r.billsec,
            Estado: r.disposition, Grabación: r.recordingfile||''
        }));
        const wb = window.XLSX.utils.book_new();
        const ws = window.XLSX.utils.json_to_sheet(data);
        // Ancho de columnas
        ws['!cols'] = [{wch:20},{wch:25},{wch:12},{wch:12},{wch:12},{wch:12},{wch:14},{wch:50}];
        window.XLSX.utils.book_append_sheet(wb, ws, 'CDR');
        // Hoja de stats
        if (stats) {
            const sdata = [
                ['Métrica','Valor'],
                ['Llamadas Totales', stats.total||0],
                ['Contestadas', stats.answered||0],
                ['Sin Respuesta', stats.no_answer||0],
                ['Ocupado', stats.busy||0],
                ['Fallidas', stats.failed||0],
                ['Duración Promedio (s)', Math.round(stats.avg_duration||0)],
                ['Tiempo Total (s)', stats.total_seconds||0]
            ];
            const ws2 = window.XLSX.utils.aoa_to_sheet(sdata);
            ws2['!cols'] = [{wch:25},{wch:15}];
            window.XLSX.utils.book_append_sheet(wb, ws2, 'Resumen');
        }
        window.XLSX.writeFile(wb, filename + '.xlsx');
        setOpen(false);
    };

    const exportPDF = () => {
        if (!rows?.length || !window.jspdf) return;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({orientation:'landscape', unit:'mm', format:'a4'});
        // Header
        doc.setFillColor(139, 92, 246);
        doc.rect(0, 0, 297, 22, 'F');
        doc.setFontSize(16); doc.setTextColor(255,255,255);
        doc.text('TeleFlow · ' + (title || 'Reporte CDR'), 14, 14);
        doc.setFontSize(9); doc.setTextColor(255,255,255,0.85);
        doc.text(`Generado: ${new Date().toLocaleString('es-UY')}`, 14, 19);
        // Stats summary
        if (stats) {
            doc.setFontSize(9); doc.setTextColor(60,60,80);
            const sline = `Total: ${stats.total||0}  ·  Contestadas: ${stats.answered||0}  ·  Sin resp: ${stats.no_answer||0}  ·  Ocupado: ${stats.busy||0}  ·  Promedio: ${Math.round(stats.avg_duration||0)}s`;
            doc.text(sline, 14, 30);
        }
        // Table
        doc.autoTable({
            startY: stats ? 36 : 28,
            head: [['Fecha','CID','Origen','Destino','Dur (s)','Estado']],
            body: rows.slice(0,1000).map(r => [
                (r.calldate||'').slice(0,16),
                (r.clid||'').replace(/<[^>]+>/g,'').trim().slice(0,20),
                r.src||'',
                r.dst||'',
                r.billsec||0,
                r.disposition||''
            ]),
            theme: 'striped',
            headStyles: { fillColor: [99,102,241], textColor: 255, fontStyle: 'bold' },
            bodyStyles: { fontSize: 8 },
            alternateRowStyles: { fillColor: [248,250,252] },
            columnStyles: { 0:{cellWidth:32}, 1:{cellWidth:50}, 2:{cellWidth:25}, 3:{cellWidth:25}, 4:{cellWidth:18}, 5:{cellWidth:30} }
        });
        // Footer pages
        const pages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pages; i++) {
            doc.setPage(i);
            doc.setFontSize(8); doc.setTextColor(150);
            doc.text(`Página ${i} de ${pages}  ·  TeleFlow · Horizon Seguridad`, 14, 200);
        }
        doc.save(filename + '.pdf');
        setOpen(false);
    };

    return (
        <div style={{position:'relative',display:'inline-block'}} onMouseLeave={()=>setOpen(false)}>
            <button onClick={()=>setOpen(!open)} style={{padding:'7px 14px',borderRadius:10,background:'rgba(34,197,94,0.12)',border:'1px solid rgba(34,197,94,0.3)',color:'#4ade80',fontSize:12,fontWeight:700,cursor:'pointer',display:'flex',alignItems:'center',gap:5}}>
                <span className="material-icons-round" style={{fontSize:16}}>download</span>
                Exportar
                <span className="material-icons-round" style={{fontSize:14}}>arrow_drop_down</span>
            </button>
            {open && (
                <div style={{position:'absolute',right:0,top:'100%',marginTop:4,background:'var(--surface)',border:'1px solid var(--border)',borderRadius:10,padding:6,boxShadow:'0 8px 24px rgba(0,0,0,0.3)',zIndex:10,minWidth:160}}>
                    <button onClick={exportCSV} style={{display:'flex',alignItems:'center',gap:8,width:'100%',padding:'8px 12px',border:'none',background:'transparent',color:'var(--text)',fontSize:12,fontWeight:600,cursor:'pointer',borderRadius:6,textAlign:'left'}} onMouseEnter={e=>e.currentTarget.style.background='rgba(139,92,246,0.1)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#22c55e'}}>description</span>CSV
                    </button>
                    <button onClick={exportXLSX} style={{display:'flex',alignItems:'center',gap:8,width:'100%',padding:'8px 12px',border:'none',background:'transparent',color:'var(--text)',fontSize:12,fontWeight:600,cursor:'pointer',borderRadius:6,textAlign:'left'}} onMouseEnter={e=>e.currentTarget.style.background='rgba(139,92,246,0.1)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#16a34a'}}>table_chart</span>Excel (.xlsx)
                    </button>
                    <button onClick={exportPDF} style={{display:'flex',alignItems:'center',gap:8,width:'100%',padding:'8px 12px',border:'none',background:'transparent',color:'var(--text)',fontSize:12,fontWeight:600,cursor:'pointer',borderRadius:6,textAlign:'left'}} onMouseEnter={e=>e.currentTarget.style.background='rgba(139,92,246,0.1)'} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#ef4444'}}>picture_as_pdf</span>PDF profesional
                    </button>
                </div>
            )}
        </div>
    );
}


function CDRAudioPlayer({ file, meta }) {
    const audioRef = useRef(null);
    const [playing, setPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [dur, setDur] = useState(0);
    const [speed, setSpeed] = useState(1);
    const [volume, setVolume] = useState(1);
    const [loading, setLoading] = useState(true);

    const filename = file?.split('/').pop() || '';
    const src = `api/recording.php?file=${encodeURIComponent(filename)}`;

    useEffect(() => {
        const a = audioRef.current; if (!a) return;
        const onLoaded = () => { setDur(a.duration); setLoading(false); };
        const onTime = () => setCurrentTime(a.currentTime);
        const onEnd = () => setPlaying(false);
        a.addEventListener('loadedmetadata', onLoaded);
        a.addEventListener('timeupdate', onTime);
        a.addEventListener('ended', onEnd);
        return () => {
            a.removeEventListener('loadedmetadata', onLoaded);
            a.removeEventListener('timeupdate', onTime);
            a.removeEventListener('ended', onEnd);
        };
    }, []);

    const togglePlay = () => {
        const a = audioRef.current; if (!a) return;
        if (playing) { a.pause(); setPlaying(false); } else { a.play(); setPlaying(true); }
    };
    const seek = (e) => {
        const a = audioRef.current; if (!a || !dur) return;
        const rect = e.currentTarget.getBoundingClientRect();
        const pct = (e.clientX - rect.left) / rect.width;
        a.currentTime = pct * dur;
    };
    const setSpeedFn = (s) => {
        const a = audioRef.current; if (!a) return;
        a.playbackRate = s; setSpeed(s);
    };
    const setVol = (v) => {
        const a = audioRef.current; if (!a) return;
        a.volume = v; setVolume(v);
    };
    const fmt = (s) => {
        if (!s || isNaN(s)) return '0:00';
        const m = Math.floor(s/60), ss = Math.floor(s%60);
        return `${m}:${String(ss).padStart(2,'0')}`;
    };
    const pct = dur > 0 ? (currentTime / dur) * 100 : 0;

    return (
        <div style={{display:'flex',alignItems:'center',gap:14,padding:12,background:'var(--surface2)',borderRadius:14,border:'1px solid rgba(139,92,246,0.15)'}}>
            <audio ref={audioRef} src={src} preload="metadata" />
            
            {/* Big play button */}
            <button onClick={togglePlay} disabled={loading} style={{
                width:48,height:48,borderRadius:'50%',border:'none',cursor:loading?'wait':'pointer',
                background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',
                display:'flex',alignItems:'center',justifyContent:'center',
                color:'#fff',boxShadow:'0 4px 14px rgba(139,92,246,0.4)',
                transition:'transform 0.15s', flexShrink:0
            }} onMouseDown={e=>e.currentTarget.style.transform='scale(0.95)'} onMouseUp={e=>e.currentTarget.style.transform=''}>
                <span className="material-icons-round" style={{fontSize:24}}>{loading?'hourglass_top':(playing?'pause':'play_arrow')}</span>
            </button>

            {/* Progress + times */}
            <div style={{flex:1,minWidth:0}}>
                <div style={{display:'flex',justifyContent:'space-between',fontSize:10,color:'var(--muted)',marginBottom:5,fontFamily:'monospace',fontWeight:700}}>
                    <span>{fmt(currentTime)}</span>
                    <span>{meta?.src} → {meta?.dst}</span>
                    <span>{fmt(dur)}</span>
                </div>
                <div onClick={seek} style={{height:6,background:'rgba(255,255,255,0.06)',borderRadius:3,cursor:'pointer',position:'relative',overflow:'hidden'}}>
                    <div style={{position:'absolute',left:0,top:0,bottom:0,width:`${pct}%`,background:'linear-gradient(90deg,#8b5cf6,#a855f7)',borderRadius:3,transition:'width 0.1s'}} />
                </div>
            </div>

            {/* Speed selector */}
            <div style={{display:'flex',gap:2,padding:3,background:'var(--surface)',borderRadius:8,border:'1px solid var(--border)'}}>
                {[0.5,1,1.5,2].map(s => (
                    <button key={s} onClick={()=>setSpeedFn(s)} style={{
                        padding:'4px 8px',borderRadius:5,border:'none',cursor:'pointer',
                        background:speed===s?'rgba(139,92,246,0.2)':'transparent',
                        color:speed===s?'#c4b5fd':'var(--muted)',
                        fontSize:10,fontWeight:800
                    }}>{s}x</button>
                ))}
            </div>

            {/* Volume */}
            <div style={{display:'flex',alignItems:'center',gap:6}}>
                <span className="material-icons-round" style={{fontSize:16,color:'var(--muted)'}}>{volume===0?'volume_off':(volume<0.5?'volume_down':'volume_up')}</span>
                <input type="range" min="0" max="1" step="0.05" value={volume} onChange={e=>setVol(parseFloat(e.target.value))} style={{width:60,height:4}} />
            </div>

            {/* Download */}
            <a href={src+'&download=1'} download={filename.replace(/\.\w+$/,'.wav')} style={{
                width:36,height:36,borderRadius:8,border:'1px solid var(--border)',background:'var(--surface)',
                display:'flex',alignItems:'center',justifyContent:'center',color:'var(--text)',
                cursor:'pointer',textDecoration:'none',transition:'all 0.15s',flexShrink:0
            }} title="Descargar">
                <span className="material-icons-round" style={{fontSize:16}}>download</span>
            </a>
        </div>
    );
}


function ViewCDR() {
    const today = new Date().toISOString().slice(0,10);
    const [from,setFrom]  = useState(new Date(Date.now()-7*86400000).toISOString().slice(0,10));
    const [to,setTo]      = useState(today);
    const [src,setSrc]    = useState('');
    const [disp,setDisp]  = useState('');
    const [rows,setRows]  = useState([]);
    const [stats,setStats]= useState({});
    const [total,setTotal]= useState(0);
    const [loading,setLoading] = useState(false);
    const [expanded,setExpanded] = useState(null);

    const load = async () => {
        setLoading(true);
        try {
            const p = new URLSearchParams({action:'get_cdr',from,to,src,disp,limit:500});
            const d = await (await fetch('api/index.php?'+p)).json();
            if(d.success){ setRows(d.rows); setStats(d.stats); setTotal(d.total); }
        } catch{} setLoading(false);
    };
    useEffect(()=>{ load(); },[]);

    const fmtSec = s => {
        if(!s||s===0) return '—';
        const m=Math.floor(s/60), ss=s%60;
        return m>0?`${m}m ${String(ss).padStart(2,'0')}s`:`${ss}s`;
    };
    const fmtDate = d => {
        if(!d) return '—';
        const dt=new Date(d);
        return dt.toLocaleString('es-UY',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
    };

    const exportCSV = () => {
        if(!rows.length) return;
        const cols=['Fecha','CID','Origen','Destino','Dur. Total','Dur. Facturada','Estado','Grabación'];
        const lines=[cols.join(';'),...rows.map(r=>[r.calldate,r.clid,r.src,r.dst,r.duration,r.billsec,r.disposition,r.recordingfile||''].join(';'))];
        const blob=new Blob([lines.join('\n')],{type:'text/csv;charset=utf-8;'});
        const a=document.createElement('a'); a.href=URL.createObjectURL(blob);
        a.download=`CDR_${from}_${to}.csv`; a.click();
    };

    const statCards = [
        {l:'Llamadas Totales',v:stats.total||0,c:'#c4b5fd',bg:'rgba(139,92,246,0.12)',ic:'list_alt'},
        {l:'Contestadas',v:stats.answered||0,c:'#4ade80',bg:'rgba(34,197,94,0.12)',ic:'call'},
        {l:'Sin Respuesta',v:stats.no_answer||0,c:'#9ca3af',bg:'rgba(107,114,128,0.15)',ic:'phone_missed'},
        {l:'En Ocupado',v:stats.busy||0,c:'#fbbf24',bg:'rgba(245,158,11,0.12)',ic:'phone_in_talk'},
        {l:'Fallidas',v:stats.failed||0,c:'#f87171',bg:'rgba(239,68,68,0.12)',ic:'phone_disabled'},
        {l:'Dur. Promedio',v:fmtSec(Math.round(stats.avg_duration)||0),c:'#60a5fa',bg:'rgba(59,130,246,0.12)',ic:'timer'},
    ];

    return(
        <div className="content-area view-enter">
            {/* Hero summary */}
            <div className="anim-fadeup" style={{padding:'18px 22px',marginBottom:14,borderRadius:18,background:'linear-gradient(135deg,rgba(168,85,247,0.08),rgba(59,130,246,0.04) 50%,transparent),var(--surface)',border:'1px solid var(--border)',display:'flex',flexWrap:'wrap',alignItems:'center',gap:14}}>
                <div style={{display:'flex',alignItems:'center',gap:14,flex:1,minWidth:280}}>
                    <div style={{width:52,height:52,borderRadius:13,background:'linear-gradient(135deg,#a855f7,#6366f1)',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:'0 8px 22px rgba(168,85,247,0.35)'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:24}}>history</span>
                    </div>
                    <div>
                        <div style={{fontSize:10,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.12em'}}>Detalle de llamadas · {from} → {to}</div>
                        <div style={{fontSize:21,fontWeight:900,letterSpacing:'-0.4px',marginTop:2,color:'var(--text)'}}>{(total||0).toLocaleString()} registros encontrados</div>
                        <div style={{fontSize:11,color:'var(--muted)',marginTop:2}}>{stats.total>0?Math.round((stats.answered/stats.total)*100):0}% efectividad · {fmtSec(Math.round(stats.avg_duration||0))} duración promedio</div>
                    </div>
                </div>
                <div style={{display:'flex',gap:8,flexWrap:'wrap',alignItems:'center'}}>
                    {[
                        {l:'Total',v:(stats.total||0).toLocaleString(),c:'#a855f7',i:'list_alt'},
                        {l:'OK',v:(stats.answered||0).toLocaleString(),c:'#22c55e',i:'check_circle'},
                        {l:'NO',v:(stats.no_answer||0).toLocaleString(),c:'#f59e0b',i:'phone_missed'},
                        {l:'BSY',v:(stats.busy||0).toLocaleString(),c:'#ef4444',i:'phone_in_talk'},
                        {l:'AHT',v:fmtSec(Math.round(stats.avg_duration||0)),c:'#3b82f6',i:'timer'},
                    ].map((k,i)=>(
                        <div key={i} style={{padding:'8px 12px',borderRadius:10,background:`linear-gradient(135deg,${k.c}18,${k.c}05)`,border:`1px solid ${k.c}33`,minWidth:90}}>
                            <div style={{display:'flex',alignItems:'center',gap:5,fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>
                                <span className="material-icons-round" style={{fontSize:11,color:k.c}}>{k.i}</span>{k.l}
                            </div>
                            <div style={{fontSize:16,fontWeight:900,color:k.c,marginTop:2}}>{k.v}</div>
                        </div>
                    ))}
                </div>
            </div>

            <PageActions>
                <span style={{fontSize:11,color:'var(--muted)',fontWeight:700}}>Desde</span>
                <input className="input-tf" type="date" value={from} onChange={e=>setFrom(e.target.value)} style={{padding:'6px 8px',borderRadius:8,fontSize:11,width:130}} />
                <span style={{fontSize:11,color:'var(--muted)',fontWeight:700}}>Hasta</span>
                <input className="input-tf" type="date" value={to} onChange={e=>setTo(e.target.value)} style={{padding:'6px 8px',borderRadius:8,fontSize:11,width:130}} />
                <div style={{position:'relative',width:160}}>
                    <span className="material-icons-round" style={{position:'absolute',left:9,top:'50%',transform:'translateY(-50%)',fontSize:15,color:'var(--muted)'}}>search</span>
                    <input className="input-tf" placeholder="Número..." value={src} onChange={e=>setSrc(e.target.value)} style={{padding:'7px 10px 7px 28px',fontSize:11,width:'100%',borderRadius:8}}/>
                </div>
                <select className="input-tf" value={disp} onChange={e=>setDisp(e.target.value)} style={{padding:'7px 10px',borderRadius:8,fontSize:11,width:140}}>
                    <option value="">Todos</option>
                    <option value="ANSWERED">Contestadas</option>
                    <option value="NO ANSWER">Sin Respuesta</option>
                    <option value="BUSY">Comunicando</option>
                    <option value="FAILED">Fallidas</option>
                </select>
                <button className="btn-primary" style={{padding:'7px 12px',borderRadius:9,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={load}>
                    <span className="material-icons-round" style={{fontSize:15}}>{loading?'hourglass_top':'search'}</span>
                    {loading?'…':'Buscar'}
                </button>
                <ExportButton rows={rows} filename={`CDR_${from}_${to}`} title={`CDR ${from} a ${to}`} stats={stats} />
            </PageActions>

            {/* Tabla */}
            <div className="glass" style={{overflow:'hidden'}}>
                <table className="tf-table">
                    <thead>
                        <tr style={{background:'color-mix(in srgb, var(--primary) 5%, transparent)'}}>
                            <th style={{padding:'12px 16px'}}><span className="material-icons-round" style={{fontSize:13,verticalAlign:'middle',marginRight:4}}>schedule</span>Fecha y Hora</th>
                            <th><span className="material-icons-round" style={{fontSize:13,verticalAlign:'middle',marginRight:4}}>call_made</span>Origen</th>
                            <th><span className="material-icons-round" style={{fontSize:13,verticalAlign:'middle',marginRight:4}}>call_received</span>Destino</th>
                            <th><span className="material-icons-round" style={{fontSize:13,verticalAlign:'middle',marginRight:4}}>timer</span>Duración</th>
                            <th>Estado</th>
                            <th>Grabación</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((r,i)=>{
                            const cfg=DISP_CFG[r.disposition]||{label:r.disposition,color:'#9ca3af',bg:'rgba(107,114,128,0.12)',border:'rgba(107,114,128,0.25)',icon:'phone'};
                            const isExp=expanded===i;
                            return(
                                <React.Fragment key={i}>
                                    <tr style={{cursor:'pointer',transition:'background .15s'}} onClick={()=>setExpanded(isExp?null:i)}>
                                        <td style={{fontFamily:'monospace',fontSize:11,padding:'10px 16px'}}>
                                            <div style={{fontWeight:600,color:'var(--text)'}}>{fmtDate(r.calldate)}</div>
                                            <div style={{fontSize:10,color:'#6b7280',marginTop:1}}>{r.calldate?.slice(0,10)}</div>
                                        </td>
                                        <td>
                                            <div style={{display:'flex',alignItems:'center',gap:6}}>
                                                <div style={{width:28,height:28,borderRadius:8,background:'color-mix(in srgb, var(--primary) 15%, transparent)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                                    <span className="material-icons-round" style={{fontSize:14,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>call_made</span>
                                                </div>
                                                <div>
                                                    <div style={{fontWeight:700,fontSize:13}}>{r.src}</div>
                                                    {r.clid&&r.clid!==r.src&&<div style={{fontSize:10,color:'#6b7280'}}>{r.clid.replace(/<[^>]+>/g,'').trim()}</div>}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style={{display:'flex',alignItems:'center',gap:6}}>
                                                <div style={{width:28,height:28,borderRadius:8,background:'rgba(59,130,246,0.12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                                    <span className="material-icons-round" style={{fontSize:14,color:'#60a5fa'}}>call_received</span>
                                                </div>
                                                <span style={{fontWeight:700,fontSize:13}}>{r.dst}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style={{fontFamily:'monospace',fontWeight:600,color:'var(--text)',fontSize:12}}>{fmtSec(r.billsec)}</div>
                                            {r.duration!==r.billsec&&<div style={{fontSize:10,color:'#6b7280'}}>Total: {fmtSec(r.duration)}</div>}
                                        </td>
                                        <td>
                                            <span style={{display:'inline-flex',alignItems:'center',gap:5,padding:'4px 10px',borderRadius:20,background:cfg.bg,border:`1px solid ${cfg.border}`,fontSize:11,fontWeight:700,color:cfg.color}}>
                                                <span className="material-icons-round" style={{fontSize:13}}>{cfg.icon}</span>
                                                {cfg.label}
                                            </span>
                                        </td>
                                        <td>
                                            {r.recordingfile
                                                ?<div style={{display:'flex',alignItems:'center',gap:6}}>
                                                    <span className="material-icons-round" style={{fontSize:16,color:'var(--primary)'}}>mic</span>
                                                    <span style={{fontSize:10,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:600}}>Ver ↓</span>
                                                  </div>
                                                :<span style={{color:'#374151',fontSize:12}}>—</span>
                                            }
                                        </td>
                                    </tr>
                                    {isExp&&r.recordingfile&&(
                                        <tr><td colSpan={6} style={{padding:'14px 16px 16px',background:'linear-gradient(180deg,rgba(139,92,246,0.06),rgba(139,92,246,0.02))',borderTop:'none'}}>
                                            <CDRAudioPlayer file={r.recordingfile} meta={{src:r.src, dst:r.dst, duration:r.billsec}} />
                                        </td></tr>
                                    )}
                                </React.Fragment>
                            );
                        })}
                        {!loading&&rows.length===0&&<tr><td colSpan={6} style={{textAlign:'center',color:'#6b7280',padding:40}}>
                            <span className="material-icons-round" style={{fontSize:40,display:'block',marginBottom:10,color:'#374151'}}>history</span>
                            Sin registros para los filtros seleccionados
                        </td></tr>}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// MODAL: Supervisor login/logout/move agents (profesional)
// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// COMPONENTE: HotdeskingWizard (proceso paso a paso)
// ─────────────────────────────────────────────
function HotdeskingWizard({ onClose }) {
    const steps = [
        { 
            icon:'badge', color:'var(--primary)',
            title:'1. Agente (persona)',
            text:'Cada agente es una persona del callcenter — no una extensión. Se identifica con un número (ej. 200) y un nombre (Brian Perez). Vive en la tabla call_center.agent de la PBX. Lo creás desde el botón "Nuevo Agente".'
        },
        {
            icon:'login', color:'#22c55e',
            title:'2. Login del agente',
            text:'Cuando llega a su puesto, se loguea en una extensión física (ej. SIP/9006). Lo podés hacer vos como supervisor (botón Login → modal con extensión y colas) o el agente solo desde la app Mi Consola con su número y password.'
        },
        {
            icon:'queue', color:'#3b82f6',
            title:'3. Asignación dinámica a colas',
            text:'Al loguearse, AMI ejecuta queue add member SIP/<ext> to <queue> para cada cola elegida. El agente queda como miembro DINÁMICO — al desloguearse sale automáticamente. Esto se ve en vivo en la columna Colas activas.'
        },
        {
            icon:'phone_in_talk', color:'#f59e0b',
            title:'4. Atención de llamadas',
            text:'La cola distribuye llamadas según su estrategia (ringall, rrmemory, leastrecent, etc.). Cuando llega una llamada al teléfono del agente, su estado cambia a En Llamada y vos lo ves en tiempo real en Agentes y en la card de la cola.'
        },
        {
            icon:'pause_circle', color:'#ec4899',
            title:'5. Pausas tipificadas',
            text:'Desde Mi Consola, el agente marca pausas (Almuerzo, Receso, Baño, Capacitación, Reunión, Personal). Cada pausa tiene tiempo máximo configurado y queda registrada para reportes (% de pausa por categoría).'
        },
        {
            icon:'logout', color:'#ef4444',
            title:'6. Cierre de sesión',
            text:'Al fin de turno, el agente cierra sesión (botón Logout). AMI ejecuta queue remove member SIP/<ext> from <queue> y la sesión se cierra en agent_sessions con el total de llamadas, tiempo en llamada y tiempo en pausa.'
        },
        {
            icon:'analytics', color:'#06b6d4',
            title:'7. Reportes y métricas',
            text:'Todo queda registrado: llamadas atendidas, AHT (tiempo medio de atención), tiempo total en llamada vs en pausa, breakdown por cola, eficiencia. Los reportes están en Reportes Analíticos.'
        }
    ];

    return (


        <LegacyDialogShell onClose={onClose} maxWidth={680}>
                <div style={{padding:'18px 24px',background:'linear-gradient(135deg, rgba(139,92,246,0.18), rgba(59,130,246,0.08))',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:14}}>
                    <div style={{width:46,height:46,borderRadius:12,background:'linear-gradient(135deg,#8b5cf6,#3b82f6)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:22}}>school</span>
                    </div>
                    <div style={{flex:1}}>
                        <h2 style={{fontSize:17,fontWeight:900,color:'var(--text)'}}>Cómo funciona el Hotdesking</h2>
                        <div style={{fontSize:11,color:'var(--muted)'}}>El ciclo de vida completo de un agente del callcenter</div>
                    </div>
                    <button onClick={onClose} style={{padding:6,borderRadius:8,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:20,color:'var(--muted)'}}>close</span>
                    </button>
                </div>

                <div style={{flex:1,overflow:'auto',padding:'18px 24px'}}>
                    {steps.map((s, i) => (
                        <div key={i} style={{display:'flex',gap:14,paddingBottom:18,marginBottom:18,borderBottom: i < steps.length-1 ? '1px solid var(--border)' : 'none'}}>
                            <div style={{flexShrink:0,position:'relative'}}>
                                <div style={{width:40,height:40,borderRadius:12,background:`linear-gradient(135deg,${s.color},${s.color}aa)`,display:'flex',alignItems:'center',justifyContent:'center',boxShadow:`0 4px 14px ${s.color}44`}}>
                                    <span className="material-icons-round" style={{color:'#fff',fontSize:20}}>{s.icon}</span>
                                </div>
                                {i < steps.length-1 && <div style={{position:'absolute',top:42,left:19,bottom:-18,width:2,background:'linear-gradient(180deg,'+s.color+'66,transparent)'}} />}
                            </div>
                            <div style={{flex:1,minWidth:0}}>
                                <div style={{fontSize:14,fontWeight:800,color:'var(--text)',marginBottom:6}}>{s.title}</div>
                                <div style={{fontSize:12,color:'var(--muted)',lineHeight:1.55}}>{s.text}</div>
                            </div>
                        </div>
                    ))}
                </div>

                <div style={{padding:'14px 24px',borderTop:'1px solid var(--border)',display:'flex',justifyContent:'space-between',alignItems:'center',background:'var(--surface2)'}}>
                    <div style={{fontSize:11,color:'var(--muted)'}}>Tip: para alta/baja de agentes usá "Nuevo Agente". Para login manual, click en "Login" en cualquier fila.</div>
                    <button onClick={onClose} className="btn-primary" style={{padding:'8px 18px',borderRadius:9,fontSize:12,fontWeight:800}}>Entendido</button>
                </div>
            
        </LegacyDialogShell>
    );
}


// ─────────────────────────────────────────────
// MODAL: Logout supervisor (selector de agentes en cola)
// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// MODAL: Ver agentes online en cola + moderar
// ─────────────────────────────────────────────
function QueueAgentsModal({ open, onClose, queue, queueName, toast, onDone}) {
    const [members, setMembers] = useState([]);
    const [agents, setAgents] = useState([]);
    const [loading, setLoading] = useState(false);
    const [busyId, setBusyId] = useState(null);

    const load = async () => {
        if (!queue) return;
        setLoading(true);
        try {
            const r1 = await fetch('api/hotdesking.php?action=agent_queues',{credentials:'include'});
            const j1 = await r1.json();
            if (j1.status === 'ok') setMembers((j1.by_queue||{})[queue] || []);
            
            const r2 = await fetch('api/hotdesking.php?action=list',{credentials:'include'});
            const j2 = await r2.json();
            if (j2.status === 'ok') setAgents(j2.agents || []);
        } catch(e) {}
        setLoading(false);
    };

    useEffect(() => { if (open) { load(); const t=setInterval(load,5000); return ()=>clearInterval(t);} }, [open, queue]);

    const enriched = members.map(m => {
        const a = agents.find(ag => ag.number === m.agent_number);
        return { ...m, name: a?.name || ('Agente '+m.agent_number), agent: a };
    });

    const doLogout = async (m) => {
        setBusyId(m.agent_number);
        const fd = new FormData(); fd.append('agent_number', m.agent_number); fd.append('extension', m.extension);
        try {
            const r = await fetch('api/hotdesking.php?action=logout_agent',{method:'POST',body:fd,credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') { toast?.(`${m.name} desconectado`,'success'); load(); }
            else toast?.(j.message||'Error','error');
        } catch(e) { toast?.('Error de red','error'); }
        setBusyId(null);
    };

    const doPause = async (m, paused) => {
        setBusyId(m.agent_number);
        // AMI Action: QueuePause via call_action no existe, usamos un endpoint nuevo o queue_pause
        const fd = new FormData(); fd.append('queue', queue); fd.append('interface', `${m.tech}/${m.extension}`); fd.append('paused', paused?'1':'0');
        try {
            const r = await fetch('api/hotdesking.php?action=pause_member',{method:'POST',body:fd,credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') { toast?.(`${m.name} ${paused?'pausado':'des-pausado'} en cola Q${queue}`,'success'); load(); }
            else toast?.(j.message||'Error','error');
        } catch(e) { toast?.('Error de red','error'); }
        setBusyId(null);
    };

    if (!open) return null;
    return (

        <LegacyDialogShell onClose={onClose} maxWidth={640}>
                <div style={{padding:'18px 24px',background:'linear-gradient(135deg,rgba(34,197,94,0.18),transparent)',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:12}}>
                    <div style={{width:46,height:46,borderRadius:12,background:'linear-gradient(135deg,#22c55e,#16a34a)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:22}}>group</span>
                    </div>
                    <div style={{flex:1}}>
                        <h2 style={{fontSize:17,fontWeight:900}}>Agentes online en Q{queue}</h2>
                        <div style={{fontSize:11,color:'var(--muted)'}}>{queueName} · {enriched.length} miembros activos</div>
                    </div>
                    <button onClick={load} title="Recargar" style={{padding:8,borderRadius:9,border:'1px solid var(--border)',background:'var(--surface2)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'var(--muted)',animation:loading?'spin 1s linear infinite':'none'}}>refresh</span>
                    </button>
                    <button onClick={onClose} style={{padding:8,borderRadius:10,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:18,color:'var(--muted)'}}>close</span>
                    </button>
                </div>

                <div style={{flex:1,overflow:'auto',padding:'14px 24px'}}>
                    {enriched.length === 0 ? (
                        <div style={{textAlign:'center',padding:40,color:'var(--muted)'}}>
                            <span className="material-icons-round" style={{fontSize:48,color:'var(--border)',display:'block',marginBottom:8}}>person_off</span>
                            <div style={{fontSize:13,fontWeight:700}}>Ningún agente logueado en esta cola</div>
                            <div style={{fontSize:11,marginTop:4}}>Usá el botón Login para añadir uno</div>
                        </div>
                    ) : (
                        <div style={{display:'flex',flexDirection:'column',gap:6}}>
                            {enriched.map((m,i) => {
                                const busy = busyId === m.agent_number;
                                const paused = m.paused;
                                return (
                                <div key={i} style={{padding:'12px 14px',borderRadius:10,background:'var(--surface2)',border:'1px solid var(--border)',display:'flex',alignItems:'center',gap:12}}>
                                    <div style={{width:38,height:38,borderRadius:'50%',background:`linear-gradient(135deg,${paused?'#f59e0b':'#22c55e'},${paused?'#d97706':'#16a34a'})`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:12,fontWeight:900,flexShrink:0,position:'relative'}}>
                                        {(m.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                                        <span style={{position:'absolute',bottom:-2,right:-2,width:11,height:11,borderRadius:'50%',background:paused?'#f59e0b':'#22c55e',border:'2px solid var(--surface)'}}/>
                                    </div>
                                    <div style={{flex:1,minWidth:0}}>
                                        <div style={{fontSize:13,fontWeight:800,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{m.name}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>#{m.agent_number} · {m.tech}/{m.extension}</div>
                                    </div>
                                    {paused && <span style={{fontSize:9,padding:'3px 8px',borderRadius:5,background:'rgba(245,158,11,0.2)',color:'#f59e0b',fontWeight:800,letterSpacing:'.05em'}}>EN PAUSA</span>}
                                    <div style={{display:'flex',gap:5}}>
                                        {paused ? (
                                            <button onClick={()=>doPause(m,false)} disabled={busy} title="Quitar pausa" style={{padding:'6px 10px',borderRadius:7,border:'1px solid rgba(34,197,94,0.4)',background:'rgba(34,197,94,0.1)',color:'#22c55e',fontWeight:800,fontSize:10,cursor:busy?'wait':'pointer',display:'flex',alignItems:'center',gap:4}}>
                                                <span className="material-icons-round" style={{fontSize:13}}>play_arrow</span>Reanudar
                                            </button>
                                        ) : (
                                            <button onClick={()=>doPause(m,true)} disabled={busy} title="Pausar" style={{padding:'6px 10px',borderRadius:7,border:'1px solid rgba(245,158,11,0.4)',background:'rgba(245,158,11,0.1)',color:'#f59e0b',fontWeight:800,fontSize:10,cursor:busy?'wait':'pointer',display:'flex',alignItems:'center',gap:4}}>
                                                <span className="material-icons-round" style={{fontSize:13}}>pause</span>Pausar
                                            </button>
                                        )}
                                        <button onClick={()=>doLogout(m)} disabled={busy} title="Desloguear de esta cola" style={{padding:'6px 10px',borderRadius:7,border:'1px solid rgba(239,68,68,0.4)',background:'rgba(239,68,68,0.1)',color:'#ef4444',fontWeight:800,fontSize:10,cursor:busy?'wait':'pointer',display:'flex',alignItems:'center',gap:4}}>
                                            <span className="material-icons-round" style={{fontSize:13}}>logout</span>Logout
                                        </button>
                                    </div>
                                </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <div style={{padding:'12px 24px',borderTop:'1px solid var(--border)',background:'var(--surface2)',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                    <div style={{fontSize:10,color:'var(--muted)',display:'flex',alignItems:'center',gap:5}}>
                        <span style={{width:6,height:6,borderRadius:'50%',background:'#22c55e',animation:'pulse 2s infinite'}}/>
                        Auto-refresh cada 5s
                    </div>
                    <button onClick={onClose} className="btn-primary" style={{padding:'8px 18px',borderRadius:9,fontSize:12,fontWeight:800}}>Cerrar</button>
                </div>
            
        </LegacyDialogShell>
    );
}

function AgentLogoutModal({ open, onClose, onDone, queue, toast }) {
    const [members, setMembers] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open || !queue) return;
        setLoading(true);
        fetch('api/hotdesking.php?action=agent_queues',{credentials:'include'})
            .then(r=>r.json()).then(j=>{
                if (j.status==='ok') {
                    const qm = (j.by_queue||{})[queue] || [];
                    setMembers(qm);
                }
            }).finally(()=>setLoading(false));
    }, [open, queue]);

    const doLogout = async (m) => {
        const fd = new FormData(); fd.append('agent_number', m.agent_number); fd.append('extension', m.extension);
        const r = await fetch('api/hotdesking.php?action=logout_agent', {method:'POST', body:fd, credentials:'include'});
        const j = await r.json();
        if (j.status==='ok') { toast?.(`Agente ${m.agent_number} desconectado de cola ${queue}`,'success'); if (typeof onDone === 'function') onDone(); else onClose(); }
        else toast?.(j.message||'Error','error');
    };

    if (!open) return null;
    return (

        <LegacyDialogShell onClose={onClose} maxWidth={520}>
                <div style={{padding:'16px 22px',background:'linear-gradient(135deg,rgba(239,68,68,0.15),transparent)',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:12}}>
                    <div style={{width:42,height:42,borderRadius:12,background:'linear-gradient(135deg,#ef4444,#dc2626)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:20}}>logout</span>
                    </div>
                    <div style={{flex:1}}>
                        <h2 style={{fontSize:16,fontWeight:900}}>Logout — Cola Q{queue}</h2>
                        <div style={{fontSize:11,color:'var(--muted)'}}>Seleccioná el agente a desloguear de esta cola</div>
                    </div>
                    <button onClick={onClose} style={{padding:6,borderRadius:8,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:18,color:'var(--muted)'}}>close</span>
                    </button>
                </div>
                <div style={{flex:1,overflow:'auto',padding:'14px 22px'}}>
                    <div style={{padding:'10px 12px',marginBottom:12,background:'rgba(239,68,68,0.08)',border:'1px solid rgba(239,68,68,0.25)',borderRadius:10,fontSize:11,color:'var(--muted)',display:'flex',gap:8,alignItems:'flex-start'}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#ef4444',flexShrink:0,marginTop:1}}>warning</span>
                        <div>Al desloguear, AMI ejecuta <code>queue remove member</code> sacando al agente solo de la cola Q{queue}. Otras colas siguen activas.</div>
                    </div>
                    {loading ? (
                        <div style={{textAlign:'center',padding:30,color:'var(--muted)'}}>Cargando miembros...</div>
                    ) : members.length === 0 ? (
                        <div style={{textAlign:'center',padding:30,color:'var(--muted)',fontSize:12}}>No hay agentes logueados en esta cola</div>
                    ) : (
                        <div style={{display:'flex',flexDirection:'column',gap:6}}>
                            {members.map((m,i) => (
                                <div key={i} style={{padding:'10px 14px',borderRadius:10,background:'var(--surface2)',border:'1px solid var(--border)',display:'flex',alignItems:'center',gap:10}}>
                                    <div style={{width:34,height:34,borderRadius:'50%',background:'linear-gradient(135deg,#22c55e,#16a34a)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900}}>
                                        {String(m.agent_number||'?').slice(0,3)}
                                    </div>
                                    <div style={{flex:1}}>
                                        <div style={{fontSize:13,fontWeight:800}}>Agente #{m.agent_number}</div>
                                        <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace'}}>{m.tech}/{m.extension}</div>
                                    </div>
                                    <button onClick={()=>doLogout(m)} style={{padding:'6px 14px',borderRadius:8,border:'none',background:'linear-gradient(135deg,#ef4444,#dc2626)',color:'#fff',fontWeight:800,fontSize:11,cursor:'pointer',display:'flex',alignItems:'center',gap:5}}>
                                        <span className="material-icons-round" style={{fontSize:14}}>logout</span>Desloguear
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            
        </LegacyDialogShell>
    );
}


function AgentLoginModal({ open, onClose, queueDefault, onDone, toast, preselectAgent }) {
    const [agents, setAgents] = useState([]);
    const [search, setSearch] = useState('');
    const [selAgent, setSelAgent] = useState(preselectAgent || null);
    const [extension, setExtension] = useState('');
    const [selectedQueues, setSelectedQueues] = useState(queueDefault ? [queueDefault] : []);
    const [allQueues, setAllQueues] = useState([]);
    const [mode, setMode] = useState('login');
    const [busy, setBusy] = useState(false);

    useEffect(() => { if (preselectAgent) setSelAgent(preselectAgent); }, [preselectAgent]);
    useEffect(() => {
        if (!open) return;
        fetch('api/hotdesking.php?action=list', {credentials:'include'})
            .then(r=>r.json()).then(j=>{ if(j.status==='ok') setAgents(j.agents||[]); });
        fetch('api/index.php?action=get_full_data', {credentials:'include'})
            .then(r=>r.json()).then(d=>setAllQueues(d.pbx?.queues||[]));
    }, [open]);
    
    useEffect(() => {
        if (!selAgent?.number) { setSelectedQueues(queueDefault ? [queueDefault] : []); return; }
        fetch('api/hotdesking.php?action=get_agent_prefs&agent_number=' + selAgent.number, {credentials:'include'})
            .then(r=>r.json()).then(j=>{
                if (j.status === 'ok' && j.queues?.length) setSelectedQueues(j.queues.map(q => String(q.queue)));
                else if (queueDefault) setSelectedQueues([queueDefault]);
            }).catch(()=>{});
    }, [selAgent?.number]);

    useEffect(() => { if (queueDefault) setSelectedQueues([queueDefault]); }, [queueDefault]);

    const filteredAgents = agents.filter(a => {
        if (!search) return true;
        const q = search.toLowerCase();
        return (a.number||'').includes(search) || (a.name||'').toLowerCase().includes(q);
    });

    const submit = async () => {
        if (!selAgent) { toast?.('Seleccioná un agente','error'); return; }
        if (mode==='login' && !extension) { toast?.('Ingresá la extensión','error'); return; }
        if (mode==='login' && selectedQueues.length === 0) { toast?.('Marcá al menos una cola','error'); return; }
        setBusy(true);
        const fd = new FormData();
        fd.append('agent_number', selAgent.number);
        if (mode==='login') {
            fd.append('extension', extension);
            fd.append('queues', selectedQueues.join(','));
        } else if (selAgent.extension) fd.append('extension', selAgent.extension);
        const action = mode==='login' ? 'login_agent' : 'logout_agent';
        try {
            const r = await fetch('api/hotdesking.php?action='+action, {method:'POST', body:fd, credentials:'include'});
            const j = await r.json();
            if (j.status==='ok') {
                toast?.(mode==='login' 
                    ? `${selAgent.name} logueado en ext ${extension} (${j.queues_added?.length||selectedQueues.length} colas)`
                    : `${selAgent.name} desconectado`, 'success');
                onDone?.();
                onClose();
            } else toast?.(j.message||'Error', 'error');
        } catch(e) { toast?.('Error de red','error'); }
        setBusy(false);
    };

    if (!open) return null;
    return (
        <LegacyDialogShell onClose={onClose} maxWidth={mode==='logout' ? 560 : 880}>
                {/* Header compacto */}
                <div style={{padding:'14px 20px',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:14,background:`linear-gradient(135deg, ${mode==='login'?'rgba(34,197,94,0.12)':'rgba(239,68,68,0.12)'}, transparent 70%)`}}>
                    <div style={{width:40,height:40,borderRadius:11,background:mode==='login'?'linear-gradient(135deg,#22c55e,#16a34a)':'linear-gradient(135deg,#ef4444,#dc2626)',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:mode==='login'?'0 4px 14px rgba(34,197,94,0.35)':'0 4px 14px rgba(239,68,68,0.35)'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:20}}>{mode==='login'?'login':'logout'}</span>
                    </div>
                    <div style={{flex:1,minWidth:0}}>
                        <h2 style={{fontSize:16,fontWeight:900,letterSpacing:'-0.3px',margin:0}}>{mode==='login'?'Loguear agente':'Desloguear agente'}</h2>
                        <div style={{fontSize:11,color:'var(--muted)',marginTop:2}}>
                            {mode==='login'
                                ? 'Asignás teléfono físico y colas → entra como miembro dinámico'
                                : 'Quita al agente de TODAS sus colas activas (queda registro en histórico)'}
                        </div>
                    </div>
                    <div style={{display:'flex',gap:4,padding:3,background:'var(--surface2)',borderRadius:9}}>
                        <button onClick={()=>setMode('login')} style={{padding:'6px 12px',borderRadius:7,border:'none',cursor:'pointer',background:mode==='login'?'rgba(34,197,94,0.2)':'transparent',color:mode==='login'?'#22c55e':'var(--muted)',fontWeight:800,fontSize:11}}>Login</button>
                        <button onClick={()=>setMode('logout')} style={{padding:'6px 12px',borderRadius:7,border:'none',cursor:'pointer',background:mode==='logout'?'rgba(239,68,68,0.2)':'transparent',color:mode==='logout'?'#ef4444':'var(--muted)',fontWeight:800,fontSize:11}}>Logout</button>
                    </div>
                    <button onClick={onClose} style={{padding:7,borderRadius:9,border:'none',background:'rgba(255,255,255,0.04)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:18,color:'var(--muted)'}}>close</span>
                    </button>
                </div>

                {/* BODY: 2 columnas en login, 1 columna en logout */}
                <div style={{flex:1,minHeight:0,overflow:'hidden',display:'grid',gridTemplateColumns: mode==='logout' ? '1fr' : '1fr 1fr',gap:0}}>
                    {/* COLUMNA IZQUIERDA: Selector de agente */}
                    <div style={{padding:'18px 22px',borderRight:'1px solid var(--border)',display:'flex',flexDirection:'column',overflow:'hidden',minHeight:0}}>
                        <label style={{fontSize:10,fontWeight:800,textTransform:'uppercase',color:'var(--muted)',letterSpacing:'.05em',marginBottom:8,display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:14,color:'var(--primary)'}}>person</span>
                            Agente
                        </label>
                        <div style={{position:'relative',marginBottom:10}}>
                            <span className="material-icons-round" style={{position:'absolute',left:11,top:'50%',transform:'translateY(-50%)',fontSize:16,color:'var(--muted)'}}>search</span>
                            <input className="input-tf" placeholder="Buscar nombre o número..." value={search} onChange={e=>setSearch(e.target.value)} style={{padding:'9px 10px 9px 34px',borderRadius:9,fontSize:12,width:'100%'}}/>
                        </div>
                        <div style={{flex:1,overflow:'auto',border:'1px solid var(--border)',borderRadius:10,padding:5,background:'var(--surface2)'}}>
                            {filteredAgents.length === 0 && <div style={{textAlign:'center',padding:24,color:'var(--muted)',fontSize:11}}>Sin resultados</div>}
                            {filteredAgents.map(a => (
                                <div key={a.id} onClick={()=>{setSelAgent(a); if(mode==='logout' && a.extension) setExtension(a.extension);}} style={{padding:'8px 10px',borderRadius:8,cursor:'pointer',display:'flex',alignItems:'center',gap:10,background:selAgent?.id===a.id?'rgba(139,92,246,0.2)':'transparent',marginBottom:2,transition:'all 0.15s',border:`1px solid ${selAgent?.id===a.id?'rgba(139,92,246,0.4)':'transparent'}`}}>
                                    <div style={{width:32,height:32,borderRadius:'50%',background:a.logged_in?'linear-gradient(135deg,#22c55e,#16a34a)':'linear-gradient(135deg,#6b7280,#4b5563)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900}}>
                                        {(a.name||'').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}
                                    </div>
                                    <div style={{flex:1,minWidth:0}}>
                                        <div style={{fontSize:12,fontWeight:700,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{a.name}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>#{a.number}{a.logged_in?` · ext ${a.extension}`:''}</div>
                                    </div>
                                    {a.logged_in && <span style={{fontSize:9,padding:'2px 7px',borderRadius:4,background:'rgba(34,197,94,0.2)',color:'#22c55e',fontWeight:800}}>ON</span>}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* COLUMNA DERECHA: Datos de login (oculta en logout) */}
                    {mode === 'login' && (
                        <div style={{padding:'18px 22px',display:'flex',flexDirection:'column',overflow:'hidden',minHeight:0}}>
                            <label style={{fontSize:10,fontWeight:800,textTransform:'uppercase',color:'var(--muted)',letterSpacing:'.05em',marginBottom:8,display:'flex',alignItems:'center',gap:6}}>
                                <span className="material-icons-round" style={{fontSize:14,color:'#3b82f6'}}>dialpad</span>
                                Extensión donde se sienta
                            </label>
                            <input className="input-tf" placeholder="Ej: 9006" value={extension} onChange={e=>setExtension(e.target.value)} style={{padding:'10px 14px',borderRadius:10,fontSize:14,fontFamily:'monospace',fontWeight:700,marginBottom:18}}/>

                            <label style={{fontSize:10,fontWeight:800,textTransform:'uppercase',color:'var(--muted)',letterSpacing:'.05em',marginBottom:8,display:'flex',alignItems:'center',gap:6}}>
                                <span className="material-icons-round" style={{fontSize:14,color:'#22c55e'}}>queue</span>
                                Colas a las que entra
                                <span style={{textTransform:'none',color:selectedQueues.length>0?'#22c55e':'var(--muted)',fontWeight:800,marginLeft:'auto',fontSize:11,fontFamily:'monospace'}}>{selectedQueues.length} seleccionada{selectedQueues.length!==1?'s':''} / {allQueues.length}</span>
                            </label>
                            <div style={{display:'flex',gap:5,marginBottom:8}}>
                                <button onClick={()=>setSelectedQueues(allQueues.map(q=>String(q.id)))} style={{padding:'4px 10px',borderRadius:6,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--muted)',fontSize:10,fontWeight:700,cursor:'pointer'}}>Todas</button>
                                <button onClick={()=>setSelectedQueues([])} style={{padding:'4px 10px',borderRadius:6,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--muted)',fontSize:10,fontWeight:700,cursor:'pointer'}}>Ninguna</button>
                            </div>
                            <div style={{flex:1,display:'flex',flexWrap:'wrap',gap:5,padding:8,border:'1px solid var(--border)',borderRadius:10,background:'var(--surface2)',overflow:'auto',alignContent:'flex-start'}}>
                                {allQueues.length === 0 && <div style={{fontSize:11,color:'var(--muted)',padding:10}}>Cargando colas...</div>}
                                {allQueues.map(q => {
                                    const sel = selectedQueues.includes(String(q.id));
                                    return (
                                    <span key={q.id} onClick={()=>setSelectedQueues(sel?selectedQueues.filter(x=>x!==String(q.id)):[...selectedQueues,String(q.id)])} style={{padding:'5px 11px',borderRadius:6,fontSize:11,fontWeight:700,cursor:'pointer',background:sel?'rgba(139,92,246,0.25)':'rgba(255,255,255,0.04)',color:sel?'#c4b5fd':'var(--muted)',border:`1px solid ${sel?'#8b5cf6':'var(--border)'}`,display:'flex',alignItems:'center',gap:5,transition:'all 0.15s'}}>
                                        {sel && <span className="material-icons-round" style={{fontSize:12}}>check</span>}
                                        <span style={{fontFamily:'monospace'}}>Q{q.id}</span>
                                        <span>{(q.name||'').slice(0,18)}</span>
                                    </span>
                                );
                                })}
                            </div>
                        </div>
                    )}

                    {/* Logout: confirmación inline al final del listado */}
                    {mode === 'logout' && selAgent && (
                        <div style={{padding:'14px 22px',borderTop:'1px solid var(--border)',background:'rgba(239,68,68,0.06)',display:'flex',alignItems:'center',gap:14}}>
                            <div style={{width:44,height:44,borderRadius:'50%',background:'linear-gradient(135deg,#ef4444,#dc2626)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontWeight:900,fontSize:13,flexShrink:0}}>
                                {(selAgent.name||'').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}
                            </div>
                            <div style={{flex:1,minWidth:0}}>
                                <div style={{fontSize:10,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.06em'}}>Vas a desloguear a</div>
                                <div style={{fontSize:14,fontWeight:800}}>{selAgent.name}</div>
                                <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace'}}>#{selAgent.number}{selAgent.extension?` · ext ${selAgent.extension}`:''}</div>
                            </div>
                        </div>
                    )}
                </div>


                {/* Footer */}
                <div style={{padding:'14px 24px',borderTop:'1px solid var(--border)',background:'var(--surface2)',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                    <div style={{fontSize:11,color:'var(--muted)'}}>
                        {selAgent ? (
                            <span>Agente: <strong style={{color:'var(--text)'}}>#{selAgent.number} {selAgent.name}</strong></span>
                        ) : 'Seleccioná un agente'}
                    </div>
                    <div style={{display:'flex',gap:8}}>
                        <button onClick={onClose} style={{padding:'9px 16px',borderRadius:9,border:'1px solid var(--border)',background:'var(--surface)',color:'var(--text)',fontWeight:700,fontSize:12,cursor:'pointer'}}>Cancelar</button>
                        <button onClick={submit} disabled={!selAgent||busy} style={{padding:'9px 22px',borderRadius:9,border:'none',cursor:(!selAgent||busy)?'not-allowed':'pointer',background:mode==='login'?'linear-gradient(135deg,#22c55e,#16a34a)':'linear-gradient(135deg,#ef4444,#dc2626)',color:'#fff',fontWeight:800,fontSize:12,opacity:(!selAgent||busy)?0.5:1,display:'flex',alignItems:'center',gap:6,boxShadow:mode==='login'?'0 4px 14px rgba(34,197,94,0.35)':'0 4px 14px rgba(239,68,68,0.35)'}}>
                            <span className="material-icons-round" style={{fontSize:16,animation:busy?'spin 1s linear infinite':'none'}}>{busy?'autorenew':(mode==='login'?'login':'logout')}</span>
                            {busy?'Procesando...':(mode==='login'?'Loguear':'Desloguear')}
                        </button>
                    </div>
                </div>
            
        </LegacyDialogShell>
    );
}
function QueueDrawer({ queue, onClose, onSaved, toast }) {
    const isNew = !queue;
    const [form, setForm] = useState({
        extension: queue?.id||'',
        descr: queue?.name||'',
        strategy: queue?.strategy||'ringall',
        timeout: queue?.timeout||15,
        wrapuptime: queue?.wrapuptime||5,
        members: (queue?.members||[]).map(m=>m.ext).join(','),
    });
    const [saving, setSaving] = useState(false);
    const set = (k,v) => setForm(f=>({...f,[k]:v}));
    const save = async () => {
        setSaving(true);
        const fd=new FormData(); Object.entries(form).forEach(([k,v])=>fd.append(k,v));
        const action = isNew ? 'create_queue' : 'update_queue';
        const d = await (await fetch(`api/index.php?action=${action}`,{method:'POST',body:fd})).json();
        setSaving(false);
        if(d.success){toast(d.message,'success');onSaved();}else toast(d.error||'Error','error');
    };
    const del = async () => {
        if(!confirm(`¿Eliminar cola ${queue?.id}?`)) return;
        const fd=new FormData();fd.append('extension',queue.id);
        const d=await(await fetch('api/index.php?action=delete_queue',{method:'POST',body:fd})).json();
        if(d.success){toast(d.message,'success');onSaved();}else toast(d.error||'Error','error');
    };
    const FI = ({label,k,type='text',ph='',readOnly=false}) => (
        <div className="mb-5">
            <label className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-2">{label}</label>
            <input 
                className={`input-tf p-3.5 rounded-2xl text-sm transition-all ${readOnly ? 'opacity-50 cursor-not-allowed' : 'hover:border-purple-500/40'}`} 
                type={type} 
                placeholder={ph} 
                value={form[k]} 
                onChange={e=>set(k,e.target.value)} 
                readOnly={readOnly} 
            />
        </div>
    );
    return(
        <>
            <div className="drawer-backdrop" onClick={onClose}/>
            <div className="drawer theme-transition">
                <div className="drawer-header">
                    <div>
                        <div style={{fontSize:18,fontWeight:900,letterSpacing:'-0.5px',color:'var(--text)'}}>{isNew?'Nueva Cola':`Cola: ${queue.name}`}</div>
                        <div style={{fontSize:11,color:'#6b7280',marginTop:2,fontWeight:600}}>ID de Cola: #{isNew?'por asignar':queue.id}</div>
                    </div>
                    <button onClick={onClose} className="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white/5 transition-colors text-gray-500 hover:text-white">
                        <span className="material-icons-round" style={{fontSize:24}}>close</span>
                    </button>
                </div>
                <div className="drawer-body">
                    <FI label="Número de Cola" k="extension" ph="Ej: 8001" readOnly={!isNew} />
                    <FI label="Nombre descriptivo" k="descr" ph="Soporte Técnico" />
                    
                    <div className="mb-5">
                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-2">Estrategia de Distribución</label>
                        <select className="input-tf p-3.5 rounded-2xl text-sm hover:border-purple-500/40" value={form.strategy} onChange={e=>set('strategy',e.target.value)}>
                            {STRAT_OPTS.map(o=><option key={o.v} value={o.v}>{o.l}</option>)}
                        </select>
                    </div>

                    <div className="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-2">Timeout (seg)</label>
                            <input className="input-tf p-3.5 rounded-2xl text-sm hover:border-purple-500/40" type="number" value={form.timeout} onChange={e=>set('timeout',e.target.value)} />
                        </div>
                        <div>
                            <label className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-2">Wrapup (seg)</label>
                            <input className="input-tf p-3.5 rounded-2xl text-sm hover:border-purple-500/40" type="number" value={form.wrapuptime} onChange={e=>set('wrapuptime',e.target.value)} />
                        </div>
                    </div>

                    <div className="mb-6">
                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-2">Internos miembros (separar con comas)</label>
                        <textarea 
                            className="input-tf p-3.5 rounded-2xl text-sm hover:border-purple-500/40 min-h-[100px] leading-relaxed" 
                            placeholder="Ej: 1001, 1002, 1005" 
                            value={form.members} 
                            onChange={e=>set('members',e.target.value)}
                        />
                        <div style={{fontSize:10,color:'#6b7280',marginTop:6,fontWeight:500}}>Miembros estáticos que recibirán llamadas de esta cola.</div>
                    </div>
                </div>
                <div className="drawer-footer" style={{display:'flex', gap:10}}>
                    {!isNew && <button onClick={del} className="w-12 h-12 rounded-2xl flex items-center justify-center bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-lg shadow-red-500/5">
                        <span className="material-icons-round">delete_outline</span>
                    </button>}
                    <button onClick={onClose} className="flex-1 p-3 rounded-2xl bg-white/5 border border-white/5 text-gray-400 font-bold text-sm hover:bg-white/10 transition-all">Cancelar</button>
                    <button onClick={save} disabled={saving} className="flex-[2] btn-primary p-3 rounded-2xl text-sm shadow-xl">{saving?'Procesando...':isNew?'Crear Cola':'Guardar Cambios'}</button>
                </div>
            </div>
        </>
    );
}

// ─── QueueActionsMenu: popover de acciones sobre el botón settings ───
function QueueActionsMenu({ q, onLogin, onLogout, onViewAgents, onReport, onConfigure }) {
    const [open, setOpen] = useState(false);
    const wrapRef = useRef(null);
    useEffect(() => {
        if (!open) return;
        const h = (e) => { if (!wrapRef.current?.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', h);
        const onEsc = (e) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('keydown', onEsc);
        return () => { document.removeEventListener('mousedown', h); document.removeEventListener('keydown', onEsc); };
    }, [open]);
    const items = [
        { icon:'person_add',    label:'Login agente',       color:'#22c55e',         onClick: onLogin },
        { icon:'person_remove', label:'Logout agente',      color:'#ef4444',         onClick: onLogout },
        { icon:'group',         label:'Ver agentes online', color:'#3b82f6',         onClick: onViewAgents },
        onReport ? { icon:'analytics', label:'Reportes', color:'var(--primary)', onClick: onReport } : null,
        { icon:'settings',      label:'Configurar cola',    color:'var(--muted-foreground)', onClick: onConfigure },
    ].filter(Boolean);
    return (
        <div ref={wrapRef} className="relative">
            <button title="Acciones de la cola"
                    onClick={(e)=>{ e.stopPropagation(); setOpen(v=>!v); }}
                    className="w-9 h-9 rounded-full flex items-center justify-center transition-all"
                    style={{
                        background: open ? 'var(--accent)' : 'color-mix(in srgb, var(--muted) 30%, transparent)',
                        color: open ? 'var(--accent-foreground)' : 'var(--muted-foreground)'
                    }}>
                <span className="material-icons-round" style={{fontSize:18}}>{open ? 'close' : 'more_vert'}</span>
            </button>
            {open && (
                <div onClick={e=>e.stopPropagation()}
                     className="absolute right-0 mt-2 rounded-lg border shadow-lg z-50 py-1 min-w-[220px]"
                     style={{
                         background:'var(--card)', borderColor:'var(--border)',
                         color:'var(--card-foreground)',
                         boxShadow:'0 12px 32px rgba(0,0,0,0.18), 0 2px 6px rgba(0,0,0,0.10)'
                     }}>
                    <div className="px-3 py-2 border-b" style={{borderColor:'var(--border)'}}>
                        <div className="text-[9px] font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Cola</div>
                        <div className="text-xs font-bold truncate" style={{color:'var(--foreground)'}}>{q.name} <span className="font-mono opacity-60">· #{q.id}</span></div>
                    </div>
                    {items.map((it, i) => (
                        <button key={i}
                                onClick={()=>{ setOpen(false); it.onClick?.(); }}
                                className="w-full px-3 py-2 flex items-center gap-2.5 text-left text-xs transition-colors hover:bg-accent">
                            <span className="material-icons-round shrink-0" style={{fontSize:16, color:it.color}}>{it.icon}</span>
                            <span className="flex-1 font-medium" style={{color:'var(--foreground)'}}>{it.label}</span>
                            <span className="material-icons-round" style={{fontSize:13, color:'var(--muted-foreground)', opacity:0.5}}>chevron_right</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function ViewColas({ toast, onReport, data }) {
    const [drawer,setDrawer]=useState(null);
    const [loginModalQ, setLoginModalQ] = useState(null);
    const [logoutModalQ, setLogoutModalQ] = useState(null);
    const [queueAgentsModalQ, setQueueAgentsModalQ] = useState(null);
    const [viewMode, setViewMode] = useState(localStorage.getItem('tf_q_view') || 'table');
    const extensions = data?.pbx?.extensions || [];
    const queues = data?.pbx?.queues || [];
    const liveCalls = data?.pbx?.live_calls || [];
    
    const stratLabel={ringall:'Simultáneo',rrmemory:'Round Robin',leastrecent:'Menos reciente',fewestcalls:'Menos llamadas',random:'Aleatorio',linear:'Lineal'};
    
    return(
        <div className="content-area view-enter">
            <PageActions>
                <div style={{display:'flex',gap:4,background:'var(--surface2)',borderRadius:10,padding:4,border:'1px solid var(--border)'}}>
                    {['table','grid'].map(m=>(
                        <button key={m} onClick={()=>{setViewMode(m);try{localStorage.setItem('tf_q_view',m);}catch(e){}}} style={{padding:'6px 12px',borderRadius:7,border:'none',cursor:'pointer',background:viewMode===m?'rgba(139,92,246,0.25)':'transparent',color:viewMode===m?'#c4b5fd':'var(--muted)',fontWeight:700,fontSize:11,display:'flex',alignItems:'center',gap:5}}>
                            <span className="material-icons-round" style={{fontSize:14}}>{m==='grid'?'grid_view':'table_rows'}</span>
                            {m==='grid'?'Tarjetas':'Tabla'}
                        </button>
                    ))}
                </div>
                <button className="btn-primary" style={{padding:'8px 14px',borderRadius:10,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={()=>setDrawer('new')}>
                    <span className="material-icons-round" style={{fontSize:16}}>add</span>Nueva Cola
                </button>
            </PageActions>
            {queues.length===0&&<div className="glass" style={{padding:40,textAlign:'center',color:'#6b7280'}}>
                <span className="material-icons-round" style={{fontSize:48,display:'block',marginBottom:12,color:'#374151'}}>queue</span>
                No hay colas configuradas
            </div>}

            {viewMode==='table' && (
                <div className="glass" style={{borderRadius:12,overflow:'hidden',marginBottom:14}}>
                    <table className="tf-table">
                        <thead>
                            <tr>
                                <th style={{width:100,padding:'10px 14px'}}>Estado</th>
                                <th>Cola</th>
                                <th>Nombre</th>
                                <th>Estrategia</th>
                                <th>Miembros</th>
                                <th style={{width:90}}>En espera</th>
                                <th style={{width:90}}>T. máx</th>
                                <th style={{width:170}}>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {queues.map((q,i)=>{
                                const w = q.calls_waiting||0;
                                const sc = w > 0 ? (w > 3 ? '#ef4444' : (w > 1 ? '#f59e0b' : '#22c55e')) : '#6b7280';
                                const lbl = w > 0 ? `${w} en espera` : 'Inactiva';
                                return (
                                <tr key={i}>
                                    <td style={{padding:'8px 14px'}}>
                                        <span style={{display:'inline-flex',alignItems:'center',gap:6,fontSize:11,fontWeight:700,color:sc}}>
                                            <span style={{width:7,height:7,borderRadius:'50%',background:sc,animation:w>0?'pulse 1.5s infinite':'none'}}/>{lbl}
                                        </span>
                                    </td>
                                    <td style={{fontFamily:'monospace',fontWeight:800,fontSize:13}}>#{q.id}</td>
                                    <td style={{fontSize:13,fontWeight:700}}>{q.name}</td>
                                    <td style={{fontSize:11,color:'var(--muted)'}}>{stratLabel[q.strategy]||q.strategy||'—'}</td>
                                    <td>
                                        <div style={{display:'flex',gap:-4,alignItems:'center'}}>
                                            {(q.members||[]).slice(0,5).map((m,j)=>{
                                                // Soporta SIP/PJSIP (extensiones fijas) y Local/Agent (agentes hot-desking)
                                                const mIface = (m.iface||'').match(/(?:SIP|PJSIP|Local|Agent)\/(\d+)/);
                                                const mExt = mIface ? mIface[1] : null;
                                                const isLocalChannel = /^(?:Local|Agent)\//i.test(m.iface||'');
                                                const eInfo = mExt ? extensions.find(e=>e.ext===mExt) : null;
                                                const stat = eInfo?.status || 'OFFLINE';
                                                const dotC = stat==='BUSY'?'#ef4444':(stat==='ONLINE'?'#22c55e':(isLocalChannel?'#8b5cf6':'#6b7280'));
                                                const dn = m.name && m.name!==mExt ? m.name : (eInfo?.name || mExt || '?');
                                                const ini = (dn||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase();
                                                return (
                                                <div key={j} title={`${dn} · ${stat}${isLocalChannel?' (agente)':' (ext fija)'}`} style={{width:24,height:24,borderRadius:'50%',background:`linear-gradient(135deg,${dotC},${dotC}aa)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:8,fontWeight:900,border:'2px solid var(--card)',marginLeft:j>0?-8:0,opacity:stat==='OFFLINE'?0.5:1}}>{ini}</div>
                                                );
                                            })}
                                            {(q.members||[]).length > 5 && <span style={{fontSize:10,color:'var(--muted-foreground)',marginLeft:6,fontWeight:700}}>+{q.members.length-5}</span>}
                                            {(q.members||[]).length === 0 && <span style={{fontSize:10,color:'var(--muted-foreground)',fontStyle:'italic'}}>Sin miembros</span>}
                                        </div>
                                    </td>
                                    <td style={{fontFamily:'monospace',fontWeight:800,color:sc}}>{w}</td>
                                    <td style={{fontFamily:'monospace',fontSize:11}}>{q.max_wait > 0 ? fmtTime(q.max_wait) : '—'}</td>
                                    <td>
                                        <div style={{display:'flex',gap:4}}>
                                            <button onClick={()=>setLoginModalQ(q.id)} style={{padding:'4px 8px',borderRadius:6,border:'1px solid rgba(34,197,94,0.3)',background:'rgba(34,197,94,0.08)',color:'#22c55e',fontWeight:700,fontSize:10,cursor:'pointer'}}>Login</button>
                                            {onReport && <button onClick={()=>onReport(q.id)} style={{padding:'4px 8px',borderRadius:6,border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)',background:'color-mix(in srgb, var(--primary) 8%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:700,fontSize:10,cursor:'pointer'}}>Reporte</button>}
                                            <button onClick={()=>setDrawer(q)} style={{padding:'4px 8px',borderRadius:6,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--text)',fontSize:10,cursor:'pointer'}}>
                                                <span className="material-icons-round" style={{fontSize:13}}>settings</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                );
                            })}
                            {queues.length===0 && <tr><td colSpan={8} style={{textAlign:'center',padding:30,color:'var(--muted)'}}>No hay colas configuradas</td></tr>}
                        </tbody>
                    </table>
                </div>
            )}

            {viewMode==='grid' && <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill, minmax(max(220px, calc((100% - 56px) / 5)), 1fr))',gap:14}}>
                {queues.map((q,i)=>{
                    const waiting = q.calls_waiting||0;
                    const isActive = waiting > 0;
                    const isCritical = isActive && q.max_wait > 40;
                    const isWarning = isActive && q.max_wait > 20 && !isCritical;
                    const statusColor = isCritical ? '#ef4444' : (isWarning ? '#f59e0b' : (isActive ? '#22c55e' : '#374151'));

                    return(
                    <div key={i} className={`glass group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:shadow-2xl ${isCritical ? 'animate-pulse' : ''}`}
                         style={{
                             padding:24, borderRadius:24, 
                             border: `1px solid ${isCritical ? 'rgba(239,68,68,0.5)' : (isWarning ? 'rgba(245,158,11,0.5)' : 'var(--border)')}`,
                             background: isCritical ? 'rgba(239,68,68,0.05)' : 'var(--surface)',
                             boxShadow: isCritical ? '0 0 30px rgba(239,68,68,0.15)' : 'none'
                         }}>

                        {/* Heatmap intensity indicator */}
                        <div style={{
                            position:'absolute', top:0, right:0, width:140, height:140,
                            background: `radial-gradient(circle at top right, ${statusColor}33, transparent)`,
                            zIndex: 0
                        }} />

                        <div style={{position:'relative', zIndex:1}}>
                            <div style={{display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:18, gap:10}}>
                                <div style={{display:'flex', alignItems:'center', gap:12, minWidth:0, flex:1}}>
                                    {/* Número de cola DESTACADO + animado cuando hay llamada */}
                                    <div style={{position:'relative', flexShrink:0}}>
                                        {isActive && [0,1,2].map(idx => (
                                            <div key={idx} style={{
                                                position:'absolute', inset:-2, borderRadius:14,
                                                border:`2px solid ${statusColor}`,
                                                opacity: 0,
                                                animation: `ring-pulse 1.5s ease-out infinite ${idx*0.5}s`
                                            }}/>
                                        ))}
                                        <div style={{
                                            minWidth:64, height:56, borderRadius:12, padding:'0 12px',
                                            background: isActive
                                                ? `linear-gradient(135deg, ${statusColor}, color-mix(in srgb, ${statusColor} 70%, #000))`
                                                : 'color-mix(in srgb, var(--muted) 30%, var(--card))',
                                            display:'flex', alignItems:'center', justifyContent:'center',
                                            boxShadow: isActive ? `0 4px 20px ${statusColor}55, 0 0 0 1px ${statusColor}` : '0 0 0 1px var(--border)',
                                            position:'relative', zIndex:1,
                                            animation: isActive ? 'tf-q-vibrate 0.5s ease-in-out infinite' : 'none'
                                        }}>
                                            <span style={{
                                                fontSize:26, fontWeight:900, fontFamily:'monospace',
                                                color: isActive ? '#fff' : 'var(--foreground)',
                                                letterSpacing:'-1px', lineHeight:1,
                                                textShadow: isActive ? `0 0 10px rgba(255,255,255,0.4)` : 'none'
                                            }}>{q.id}</span>
                                        </div>
                                    </div>
                                    <div style={{minWidth:0, flex:1}}>
                                        <h3 style={{fontSize:15, fontWeight:800, color:'var(--foreground)', lineHeight:1.2, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{q.name}</h3>
                                        <div className="flex items-center gap-1.5 mt-1" style={{fontSize:10, color:'var(--muted-foreground)'}}>
                                            <span className="material-icons-round" style={{fontSize:12}}>device_hub</span>
                                            <span title="Estrategia de ring — cómo distribuye llamadas a los agentes">
                                                Ring <strong style={{color:'var(--foreground)',fontWeight:700}}>{stratLabel[q.strategy]||q.strategy||'—'}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2 flex-shrink-0">
                                    {waiting > 1 && (
                                        <div className="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full flex items-center gap-1 shadow-lg shadow-red-500/20" style={{animation:'pulse 1s infinite'}}>
                                            <span className="material-icons-round" style={{fontSize:12}}>call</span>
                                            {waiting}
                                        </div>
                                    )}
                                    <QueueActionsMenu q={q}
                                        onLogin={()=>setLoginModalQ(q.id)}
                                        onLogout={()=>setLogoutModalQ(q.id)}
                                        onViewAgents={()=>setQueueAgentsModalQ(q)}
                                        onReport={onReport ? ()=>onReport(q.id) : null}
                                        onConfigure={()=>setDrawer(q)}/>
                                </div>
                            </div>

                            <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:10, marginBottom:20}}>
                                <div className="glass" style={{padding:'15px 10px', textAlign:'center', borderRadius:16, background:'rgba(255,255,255,0.02)'}}>
                                    <div style={{fontSize:38, fontWeight:900, color:statusColor, lineHeight:1, letterSpacing:'-2px'}}>{waiting}</div>
                                    <div style={{fontSize:9, color:'#6b7280', fontWeight:800, textTransform:'uppercase', marginTop:4, letterSpacing:'1px'}}>En Espera</div>
                                </div>
                                <div className="glass" style={{padding:'15px 10px', textAlign:'center', borderRadius:16, background:'rgba(255,255,255,0.02)'}}>
                                    <div style={{fontSize:18, fontWeight:800, color:'var(--text)', lineHeight:1}}>{q.max_wait > 0 ? fmtTime(q.max_wait) : '00:00'}</div>
                                    <div style={{fontSize:9, color:'#6b7280', fontWeight:800, textTransform:'uppercase', marginTop:14, letterSpacing:'1px'}}>T. Máximo</div>
                                </div>
                            </div>

                            <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:10}}>
                                <div style={{fontSize:10, color:'var(--muted-foreground)', fontWeight:800, textTransform:'uppercase'}}>Miembros de la Cola</div>
                                <div style={{fontSize:10, color:'var(--muted)', fontWeight:800}}>{q.members?.length || 0} miembros</div>
                            </div>

                            <div style={{display:'flex', flexDirection:'column', gap:4, minHeight:38}}>
                                {(q.members || []).slice(0, 6).map((m,j)=>{
                                    // m = {name, iface}. Parse ext desde iface — soporta SIP/PJSIP/Local/Agent
                                    const ifaceMatch = (m.iface||'').match(/(?:SIP|PJSIP|Local|Agent)\/(\d+)/);
                                    const ifaceExt = ifaceMatch ? ifaceMatch[1] : null;
                                    const isLocalChannel = /^(?:Local|Agent)\//i.test(m.iface||'');
                                    const extInfo = ifaceExt ? extensions.find(e => e.ext === ifaceExt) : null;
                                    // Status: si extInfo.status === BUSY → en llamada
                                    const memberStatus = extInfo ? extInfo.status : 'OFFLINE';
                                    const isOnline = memberStatus === 'ONLINE';
                                    const isBusy = memberStatus === 'BUSY';
                                    const dotColor = isBusy ? '#ef4444' : (isOnline ? '#22c55e' : '#6b7280');
                                    const displayName = m.name && m.name !== ifaceExt ? m.name : (extInfo?.name || ifaceExt || 'Member');
                                    const initialsTxt = (displayName||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase();
                                    const av = ifaceExt && extInfo?.avatar && !extInfo.avatar.includes('ui-avatars') ? extInfo.avatar : null;
                                    return (
                                        <div key={j} style={{display:'flex',alignItems:'center',gap:8,padding:'4px 6px',borderRadius:7,background:'var(--surface2)',border:'1px solid var(--border)'}}>
                                            <div style={{position:'relative',flexShrink:0}}>
                                                <div style={{
                                                    width:26, height:26, borderRadius:'50%', 
                                                    background: av ? `url(${av}) center/cover` : `linear-gradient(135deg, ${dotColor}, ${dotColor}88)`,
                                                    display:'flex', alignItems:'center', justifyContent:'center',
                                                    color:'#fff', fontSize:9, fontWeight:900,
                                                    opacity: memberStatus==='OFFLINE'?0.5:1
                                                }}>
                                                    {!av && initialsTxt}
                                                </div>
                                                <div style={{position:'absolute', bottom:-1, right:-1, width:8, height:8, borderRadius:'50%', background:dotColor, border:'2px solid var(--surface)', animation: isBusy ? 'pulse 1.5s infinite' : 'none'}} />
                                            </div>
                                            <div style={{flex:1,minWidth:0}}>
                                                <div style={{fontSize:11,fontWeight:700,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',color:'var(--text)'}}>{displayName}</div>
                                                {ifaceExt && <div style={{fontSize:9,fontFamily:'monospace',color:'var(--muted)',fontWeight:600}}>ext {ifaceExt}</div>}
                                            </div>
                                        </div>
                                    );
                                })}
                                {(q.members?.length || 0) === 0 && <div style={{fontSize:11,color:'var(--muted-foreground)',fontStyle:'italic',padding:'8px 0'}}>Sin miembros en esta cola — agregá extensiones fijas o logueá agentes</div>}
                                {q.members?.length > 10 && <div style={{width:34,height:34,borderRadius:'50%',background:'rgba(255,255,255,0.05)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:10,color:'var(--muted)',fontWeight:800}}>+{q.members.length-10}</div>}
                            </div>

                            {/* Llamadas activas EN ESTA cola */}
                            {(() => {
                                const queueCalls = liveCalls.filter(lc => 
                                    String(lc.dest) === String(q.id) || 
                                    String(lc.context) === 'ext-queues' && String(lc.dest) === String(q.id) ||
                                    (lc.app === 'Queue' && (lc.data || '').startsWith(q.id+','))
                                );
                                if (queueCalls.length === 0) return null;
                                return (
                                    <div style={{marginTop:12, padding:'10px 12px', background:'rgba(34,197,94,0.06)', border:'1px solid rgba(34,197,94,0.2)', borderRadius:10}}>
                                        <div style={{fontSize:9, fontWeight:800, color:'#22c55e', textTransform:'uppercase', marginBottom:6, display:'flex', alignItems:'center', gap:5}}>
                                            <span style={{width:6,height:6,borderRadius:'50%',background:'#22c55e',animation:'pulse 1s infinite'}}/>
                                            En llamada ahora ({queueCalls.length})
                                        </div>
                                        {queueCalls.slice(0,3).map((c,k)=>(
                                            <div key={k} style={{display:'flex',alignItems:'center',gap:6,fontSize:11,color:'var(--text)',padding:'3px 0'}}>
                                                <span className="material-icons-round" style={{fontSize:14,color:c.state==='Ringing'?'#f59e0b':'#22c55e',animation:c.state==='Ringing'?'pulse 0.8s infinite':'none'}}>
                                                    {c.state==='Ringing'?'phone_in_talk':'call'}
                                                </span>
                                                <span style={{fontFamily:'monospace',fontWeight:700}}>{c.callerid || c.ext}</span>
                                                <span style={{color:'var(--muted)'}}>· {c.state}</span>
                                                <span style={{marginLeft:'auto',fontSize:10,fontFamily:'monospace',color:'var(--muted)'}}>{c.duration||'0:00'}</span>
                                            </div>
                                        ))}
                                    </div>
                                );
                            })()}

                            {/* Estrategia chip al pie */}
                            <div style={{marginTop:12,paddingTop:10,borderTop:'1px solid var(--border)',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                                <span style={{fontSize:10,color:'var(--muted)',textTransform:'uppercase',fontWeight:700,letterSpacing:'.08em'}}>Estrategia: {stratLabel[q.strategy]||q.strategy}</span>
                                {(q.calls_waiting||0) > 0 && (
                                    <span style={{fontSize:10,padding:'3px 9px',borderRadius:5,background:'rgba(239,68,68,0.15)',color:'#ef4444',fontWeight:800,display:'flex',alignItems:'center',gap:4,animation:'pulse 1.5s infinite'}}>
                                        <span style={{width:6,height:6,borderRadius:'50%',background:'#ef4444'}}/>
                                        {q.calls_waiting} en espera
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>);
                })}
            </div>}
            {drawer&&<QueueDrawer queue={drawer==='new'?null:drawer} onClose={()=>setDrawer(null)} onSaved={()=>{setDrawer(null);toast?.('Cola actualizada','success');}} toast={toast||((m,t)=>alert(m))} />}
            <AgentLoginModal open={!!loginModalQ} onClose={()=>setLoginModalQ(null)} onDone={()=>{setLoginModalQ(null); window.dispatchEvent(new CustomEvent('tf-queues-refresh'));}} queueDefault={loginModalQ} toast={toast} />
            <AgentLogoutModal open={!!logoutModalQ} onClose={()=>setLogoutModalQ(null)} onDone={()=>{setLogoutModalQ(null); window.dispatchEvent(new CustomEvent('tf-queues-refresh'));}} queue={logoutModalQ} toast={toast} />
            <QueueAgentsModal open={!!queueAgentsModalQ} onClose={()=>setQueueAgentsModalQ(null)} onDone={()=>{setQueueAgentsModalQ(null); window.dispatchEvent(new CustomEvent('tf-queues-refresh'));}} queue={queueAgentsModalQ?.id} queueName={queueAgentsModalQ?.name} toast={toast} />
            <div style={{textAlign:'center',marginTop:20,padding:'14px 0',fontSize:11,color:'var(--muted)',borderTop:'1px solid var(--border)',fontWeight:700}}>
                <span className="material-icons-round" style={{fontSize:14,verticalAlign:'middle',marginRight:4}}>queue</span>
                {queues.length} colas configuradas en la PBX
            </div>
        </div>
    );
}


// ─────────────────────────────────────────────
// VISTA: GRUPOS DE TIMBRADO (con CRUD + numero + animación)
// ─────────────────────────────────────────────
const RG_STRATEGIES = [
    {v:'ringall',l:'Timbre simultáneo'},
    {v:'hunt',l:'Secuencial (Hunt)'},
    {v:'memoryhunt',l:'Memoria secuencial'},
    {v:'firstavailable',l:'Primero disponible'},
];

// ─────────────────────────────────────────────
// FICHA DEL GRUPO — Página dedicada (mismo estilo que ExtEditPage)
// ─────────────────────────────────────────────
function GroupEditPage({ group, activeCalls, onBack, onSaved, toast }) {
    const isNew = !group;
    const [form, setForm] = useState({
        grpnum: group?.grpnum||'',
        description: group?.description||'',
        strategy: group?.strategy||'ringall',
        grptime: group?.grptime||20,
        grplist: (group?.members||[]).join('-'),
    });
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [memberInput, setMemberInput] = useState('');

    const set = (k,v) => setForm(f=>({...f,[k]:v}));

    // Members as array from the grplist string
    const members = form.grplist ? form.grplist.split('-').filter(m => m.trim()) : [];

    const addMember = () => {
        const ext = memberInput.trim();
        if (!ext) return;
        const newList = [...members, ext].join('-');
        set('grplist', newList);
        setMemberInput('');
    };

    const removeMember = (ext) => {
        set('grplist', members.filter(m => m !== ext).join('-'));
    };

    const save = async () => {
        setSaving(true);
        const fd = new FormData();
        Object.entries(form).forEach(([k,v]) => fd.append(k,v));
        const action = isNew ? 'create_ring_group' : 'update_ring_group';
        const d = await(await fetch(`api/index.php?action=${action}`, { 
            method: 'POST', 
            body: fd,
            credentials: 'include'
        })).json();
        setSaving(false);
        if (d.success) { toast(d.message,'success'); onSaved(); }
        else toast(d.error||'Error','error');
    };

    const del = async () => {
        if (!confirm(`¿Eliminar grupo ${group?.grpnum}?`)) return;
        setDeleting(true);
        const fd = new FormData(); fd.append('grpnum', group.grpnum);
        const d = await(await fetch('api/index.php?action=delete_ring_group', {
            method: 'POST',
            body: fd,
            credentials: 'include'
        })).json();
        if (d.success) { toast(d.message,'success'); onSaved(); }
        else { toast(d.error||'Error','error'); setDeleting(false); }
    };

    const STRATEGIES = [
        {v:'ringall',    l:'Timbre Simultáneo', i:'ring_volume',     c:'#22c55e', desc:'Todos timbran a la vez'},
        {v:'hunt',       l:'Secuencial',         i:'trending_flat',  c:'#60a5fa', desc:'De a uno, en orden'},
        {v:'memoryhunt', l:'Mem. Secuencial',    i:'memory',         c:'#a78bfa', desc:'Recuerda donde quedó'},
        {v:'firstavailable', l:'1ro Disponible', i:'bolt',           c:'#f59e0b', desc:'El primero que conteste'},
    ];

    const isGroupActive = group?.members?.some(m => activeCalls.some(c => c.ext === m));

    return (
        <div className="content-area view-enter">
            {/* Breadcrumb */}
            <div style={{display:'flex', alignItems:'center', gap:12, marginBottom:24}}>
                <button
                    onClick={onBack}
                    style={{width:38,height:38,borderRadius:12,background:'var(--surface)',border:'1px solid var(--border)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',color:'var(--muted)',transition:'all .2s'}}
                    onMouseEnter={e=>e.currentTarget.style.color='var(--text)'}
                    onMouseLeave={e=>e.currentTarget.style.color='var(--muted)'}
                >
                    <span className="material-icons-round" style={{fontSize:20}}>arrow_back</span>
                </button>
                <div style={{display:'flex', alignItems:'center', gap:8, fontSize:12, color:'var(--muted)'}}>
                    <span style={{cursor:'pointer',fontWeight:600}} onClick={onBack}>Grupos de Timbrado</span>
                    <span className="material-icons-round" style={{fontSize:14}}>chevron_right</span>
                    <span style={{color:'var(--text)', fontWeight:700}}>
                        {isNew ? 'Nuevo Grupo' : `Grupo #${group.grpnum} — ${group.description}`}
                    </span>
                </div>
                <div style={{flex:1}} />
                {!isNew && (
                    <button
                        onClick={del}
                        disabled={deleting}
                        style={{padding:'8px 16px',borderRadius:10,fontSize:12,fontWeight:700,background:'rgba(239,68,68,0.1)',border:'1px solid rgba(239,68,68,0.25)',color:'#f87171',cursor:'pointer',display:'flex',alignItems:'center',gap:6,transition:'all .2s'}}
                    >
                        <span className="material-icons-round" style={{fontSize:16}}>{deleting?'hourglass_top':'delete_outline'}</span>
                        {deleting ? 'Eliminando...' : 'Eliminar Grupo'}
                    </button>
                )}
            </div>

            {/* Two-column layout */}
            <div style={{display:'grid', gridTemplateColumns:'280px 1fr', gap:24, alignItems:'start'}}>

                {/* LEFT — Group info card */}
                <div style={{display:'flex', flexDirection:'column', gap:16}}>
                    {/* Group avatar */}
                    <div className="glass" style={{padding:28, textAlign:'center', borderRadius:20, position:'relative', overflow:'hidden'}}>
                        {isGroupActive && <div style={{position:'absolute',top:0,left:0,right:0,height:3,background:'linear-gradient(90deg,#ef4444,#f59e0b,#ef4444)',backgroundSize:'200% 100%',animation:'callActive 1.5s linear infinite'}} />}
                        <div style={{
                            width:80, height:80, borderRadius:22, margin:'0 auto 16px',
                            background: isGroupActive
                                ? 'linear-gradient(135deg,#ef4444,#dc2626)'
                                : 'linear-gradient(135deg,#3b82f6,#1d4ed8)',
                            display:'flex', alignItems:'center', justifyContent:'center',
                            fontSize:36, color:'white',
                            boxShadow: isGroupActive ? '0 8px 32px rgba(239,68,68,0.45)' : '0 8px 32px rgba(59,130,246,0.45)'
                        }}>
                            <span className="material-icons-round" style={{fontSize:40}}>ring_volume</span>
                        </div>
                        <div style={{fontSize:18, fontWeight:900, color:'var(--text)'}}>{form.description || 'Sin nombre'}</div>
                        <div style={{fontFamily:'monospace', fontSize:13, color:'#60a5fa', fontWeight:700, marginTop:4}}>Grupo #{form.grpnum || '—'}</div>
                        {isGroupActive && (
                            <div style={{display:'flex', alignItems:'center', gap:6, justifyContent:'center', marginTop:12}}>
                                <span style={{width:8,height:8,borderRadius:'50%',background:'#ef4444',boxShadow:'0 0 8px #ef4444',animation:'blink 1s infinite'}} />
                                <span style={{fontSize:11, fontWeight:700, color:'#f87171'}}>LLAMADA ACTIVA</span>
                            </div>
                        )}
                    </div>

                    {/* Stats */}
                    <div className="glass" style={{padding:16, borderRadius:16}}>
                        <div style={{fontSize:10, fontWeight:700, color:'#6b7280', textTransform:'uppercase', letterSpacing:'.1em', marginBottom:12}}>Estadísticas</div>
                        {[
                            {l:'Miembros',      v: members.length,           c:'#60a5fa'},
                            {l:'Tiempo timbre', v: `${form.grptime}s`,       c:'#c4b5fd'},
                            {l:'Estrategia',    v: form.strategy,            c:'#22c55e'},
                            {l:'En llamada',    v: activeCalls.filter(c=>members.includes(c.ext)).length, c:'#f87171'},
                        ].map(({l,v}) => (
                            <div key={l} style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 0', borderBottom:'1px solid var(--border)'}}>
                                <span style={{fontSize:11, color:'#6b7280', fontWeight:600}}>{l}</span>
                                <span style={{fontSize:12, color:'#60a5fa', fontWeight:800, fontFamily:'monospace'}}>{v}</span>
                            </div>
                        ))}
                    </div>

                    {/* Members quick view */}
                    {members.length > 0 && (
                        <div className="glass" style={{padding:16, borderRadius:16}}>
                            <div style={{fontSize:10, fontWeight:700, color:'#6b7280', textTransform:'uppercase', letterSpacing:'.1em', marginBottom:10}}>Miembros Actuales</div>
                            <div style={{display:'flex', flexWrap:'wrap', gap:6}}>
                                {members.map(m => {
                                    const onCall = activeCalls.some(c => c.ext === m);
                                    return (
                                        <div key={m} style={{
                                            padding:'3px 10px', borderRadius:8,
                                            background: onCall ? 'rgba(239,68,68,0.12)' : 'rgba(59,130,246,0.1)',
                                            border: `1px solid ${onCall ? 'rgba(239,68,68,.3)' : 'rgba(59,130,246,.2)'}`,
                                            fontSize:11, fontWeight:700,
                                            color: onCall ? '#f87171' : '#60a5fa',
                                            display:'flex', alignItems:'center', gap:4
                                        }}>
                                            {onCall && <span style={{width:5,height:5,borderRadius:'50%',background:'#ef4444',animation:'blink 1s infinite'}} />}
                                            #{m}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>

                {/* RIGHT — Form */}
                <div className="glass" style={{padding:28, borderRadius:20}}>
                    {/* Row 1: Number + Description */}
                    <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:20, marginBottom:20}}>
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Número del Grupo</label>
                            <input
                                className="input-tf"
                                style={{padding:'12px 16px',borderRadius:14,fontSize:14,fontWeight:700,width:'100%',boxSizing:'border-box',opacity:isNew?1:0.7}}
                                placeholder="Ej: 700"
                                value={form.grpnum}
                                onChange={e=>set('grpnum',e.target.value)}
                                readOnly={!isNew}
                            />
                        </div>
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Nombre o Descripción</label>
                            <input
                                className="input-tf"
                                style={{padding:'12px 16px',borderRadius:14,fontSize:14,width:'100%',boxSizing:'border-box'}}
                                placeholder="Soporte Técnico..."
                                value={form.description}
                                onChange={e=>set('description',e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Row 2: Timeout */}
                    <div style={{marginBottom:20}}>
                        <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>
                            Tiempo de Timbrado <span style={{color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))', fontFamily:'monospace'}}>({form.grptime}s)</span>
                        </label>
                        <div style={{display:'flex', alignItems:'center', gap:12}}>
                            <input
                                type="range" min="5" max="120" step="5"
                                value={form.grptime}
                                onChange={e=>set('grptime', e.target.value)}
                                style={{flex:1, accentColor:'#8b5cf6', height:6}}
                            />
                            <input
                                className="input-tf"
                                type="number" min="5" max="120"
                                style={{width:80, padding:'10px 12px', borderRadius:12, fontSize:13, fontWeight:700, textAlign:'center', boxSizing:'border-box'}}
                                value={form.grptime}
                                onChange={e=>set('grptime',e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Estrategia visual */}
                    <div style={{marginBottom:20}}>
                        <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:12}}>Estrategia de Timbrado</label>
                        <div style={{display:'grid', gridTemplateColumns:'repeat(2,1fr)', gap:10}}>
                            {STRATEGIES.map(s=>(
                                <button
                                    key={s.v}
                                    onClick={()=>set('strategy',s.v)}
                                    style={{
                                        padding:'14px 16px', borderRadius:14, cursor:'pointer',
                                        border: form.strategy===s.v ? `1px solid ${s.c}50` : '1px solid var(--border)',
                                        background: form.strategy===s.v ? `${s.c}12` : 'var(--surface2)',
                                        display:'flex', alignItems:'center', gap:12, textAlign:'left',
                                        transition:'all .2s'
                                    }}
                                >
                                    <span className="material-icons-round" style={{fontSize:22, color:form.strategy===s.v?s.c:'var(--muted)', flexShrink:0}}>{s.i}</span>
                                    <div>
                                        <div style={{fontSize:12,fontWeight:800,color:form.strategy===s.v?s.c:'var(--text)'}}>{s.l}</div>
                                        <div style={{fontSize:10,color:'#6b7280',marginTop:1}}>{s.desc}</div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Members editor */}
                    <div style={{marginBottom:28}}>
                        <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:12}}>Internos del Grupo</label>

                        {/* Add member input */}
                        <div style={{display:'flex', gap:8, marginBottom:10}}>
                            <input
                                className="input-tf"
                                style={{flex:1, padding:'10px 16px', borderRadius:12, fontSize:13}}
                                placeholder="Agregar extensión (ej: 1001)"
                                value={memberInput}
                                onChange={e=>setMemberInput(e.target.value)}
                                onKeyDown={e=>e.key==='Enter'&&addMember()}
                            />
                            <button
                                onClick={addMember}
                                className="btn-primary"
                                style={{padding:'10px 16px', borderRadius:12, fontSize:12, display:'flex', alignItems:'center', gap:4}}
                            >
                                <span className="material-icons-round" style={{fontSize:16}}>add</span>
                                Agregar
                            </button>
                        </div>

                        {/* Members chips */}
                        {members.length > 0 ? (
                            <div style={{display:'flex', flexWrap:'wrap', gap:8}}>
                                {members.map(m => (
                                    <div
                                        key={m}
                                        style={{
                                            display:'flex', alignItems:'center', gap:6,
                                            padding:'6px 10px 6px 14px', borderRadius:10,
                                            background:'color-mix(in srgb, var(--primary) 10%, transparent)',
                                            border:'1px solid color-mix(in srgb, var(--primary) 25%, transparent)',
                                            fontSize:12, fontWeight:700, color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'
                                        }}
                                    >
                                        <span className="material-icons-round" style={{fontSize:13,color:'var(--primary)'}}>phone</span>
                                        #{m}
                                        <button
                                            onClick={()=>removeMember(m)}
                                            style={{background:'none',border:'none',cursor:'pointer',color:'#6b7280',display:'flex',padding:2,marginLeft:2,borderRadius:4}}
                                        >
                                            <span className="material-icons-round" style={{fontSize:14}}>close</span>
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div style={{padding:'20px',textAlign:'center',borderRadius:12,border:'1px dashed rgba(139,92,246,0.2)',color:'#4b5563',fontSize:12}}>
                                Sin miembros. Agrega extensiones con el campo de arriba.
                            </div>
                        )}

                        <div style={{fontSize:10,color:'#4b5563',marginTop:8,fontWeight:500}}>
                            También podés editar la lista directamente:
                        </div>
                        <input
                            className="input-tf"
                            style={{marginTop:6, padding:'10px 16px', borderRadius:12, fontSize:12, fontFamily:'monospace', width:'100%', boxSizing:'border-box', color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}
                            placeholder="1001-1002-1003"
                            value={form.grplist}
                            onChange={e => set('grplist', e.target.value)}
                        />
                    </div>

                    {/* Action buttons */}
                    <div style={{display:'flex', gap:12, justifyContent:'flex-end', paddingTop:20, borderTop:'1px solid var(--border)'}}>
                        <button
                            onClick={onBack}
                            style={{padding:'12px 24px',borderRadius:14,fontWeight:700,fontSize:13,background:'var(--surface2)',border:'1px solid var(--border)',color:'var(--muted)',cursor:'pointer',transition:'all .2s'}}
                        >Cancelar</button>
                        <button
                            onClick={save}
                            disabled={saving}
                            className="btn-primary"
                            style={{padding:'12px 32px',borderRadius:14,fontWeight:700,fontSize:13,cursor:'pointer',display:'flex',alignItems:'center',gap:8}}
                        >
                            <span className="material-icons-round" style={{fontSize:18}}>{saving?'hourglass_top':'save'}</span>
                            {saving ? 'Guardando...' : isNew ? 'Crear Grupo' : 'Guardar Cambios'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ViewGrupos({ toast }) {
    const [groups, setGroups] = useState([]);
    const [editing, setEditing] = useState(null); // null | 'new' | group object
    const [activeCalls, setActiveCalls] = useState([]);

    const load = async () => {
        try { const d=await(await fetch('api/index.php?action=get_ring_groups')).json(); if(d.success) setGroups(d.groups); } catch{}
        try { const d=await(await fetch('api/index.php?action=get_active_calls')).json(); if(d.success) setActiveCalls(d.calls||[]); } catch{}
    };
    useEffect(() => { load(); const t=setInterval(load,5000); return()=>clearInterval(t); }, []);

    const strategyLabel = {ringall:'Simultáneo', hunt:'Secuencial', memoryhunt:'Mem. secuencial', firstavailable:'1ro disponible'};
    const isGroupActive = (g) => g.members?.some(m => activeCalls.some(c => c.ext === m));

    // Si está editando, mostrar página dedicada
    if (editing) {
        return (
            <GroupEditPage
                group={editing === 'new' ? null : editing}
                activeCalls={activeCalls}
                onBack={() => setEditing(null)}
                onSaved={() => { setEditing(null); load(); }}
                toast={toast||(m=>alert(m))}
            />
        );
    }

    return (
        <div className="content-area view-enter">
            <PageActions>
                <div style={{fontSize:11,color:'var(--muted)',fontWeight:700}}>{groups.length} grupos configurados</div>
                <button className="btn-primary" style={{padding:'8px 14px',borderRadius:10,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={()=>setEditing('new')}>
                    <span className="material-icons-round" style={{fontSize:16}}>add</span>Nuevo Grupo
                </button>
            </PageActions>

            {groups.length===0 && (
                <div className="glass" style={{padding:40, textAlign:'center', color:'#6b7280'}}>
                    <span className="material-icons-round" style={{fontSize:48, display:'block', marginBottom:12, color:'#374151'}}>ring_volume</span>
                    Sin grupos de timbrado configurados
                </div>
            )}

            <div style={{display:'grid', gridTemplateColumns:'repeat(auto-fill,minmax(300px,1fr))', gap:14}}>
                {groups.map((g, i) => {
                    const active = isGroupActive(g);
                    return (
                        <div
                            key={i}
                            className="glass glass-hover"
                            style={{padding:20, borderTop:`2px solid ${active?'#ef4444':'#374151'}`, transition:'border-color .3s', position:'relative', overflow:'hidden', cursor:'pointer'}}
                            onClick={() => setEditing(g)}
                        >
                            {active && <div style={{position:'absolute',top:0,left:0,right:0,height:2,background:'linear-gradient(90deg,#ef4444,#f59e0b,#ef4444)',backgroundSize:'200% 100%',animation:'callActive 1.5s linear infinite'}} />}
                            <div style={{display:'flex', alignItems:'center', gap:12, marginBottom:12}}>
                                <div style={{width:40,height:40,borderRadius:12,background:active?'rgba(239,68,68,0.15)':'rgba(59,130,246,0.15)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,transition:'background .3s'}}>
                                    <span className="material-icons-round" style={{fontSize:20,color:active?'#f87171':'#60a5fa',animation:active?'blink 1s infinite':'none'}}>ring_volume</span>
                                </div>
                                <div style={{flex:1}}>
                                    <div style={{display:'flex', alignItems:'center', gap:8}}>
                                        <div style={{padding:'2px 8px',borderRadius:6,background:'rgba(59,130,246,0.12)',border:'1px solid rgba(59,130,246,.25)',fontSize:10,fontWeight:800,color:'#60a5fa',fontFamily:'monospace'}}>#{g.grpnum}</div>
                                        <div style={{fontSize:14,fontWeight:800,color:'var(--text)'}}>{g.description}</div>
                                    </div>
                                    <div style={{fontSize:11,color:'#9ca3af',marginTop:2}}>{strategyLabel[g.strategy]||g.strategy} · {g.grptime}s · {g.members?.length||0} miembros</div>
                                </div>
                                <span className="material-icons-round" style={{fontSize:18, color:'#4b5563'}}>chevron_right</span>
                            </div>
                            <div style={{display:'flex', flexWrap:'wrap', gap:6}}>
                                {(g.members||[]).map((m,j) => {
                                    const onCall = activeCalls.some(c => c.ext === m);
                                    return (
                                        <div key={j} style={{padding:'4px 12px',borderRadius:8,background:onCall?'rgba(239,68,68,0.12)':'rgba(139,92,246,0.1)',border:`1px solid ${onCall?'rgba(239,68,68,.3)':'rgba(139,92,246,.2)'}`,fontSize:11,fontWeight:700,color:onCall?'#f87171':'#c4b5fd',display:'flex',alignItems:'center',gap:5}}>
                                            {onCall && <span style={{width:6,height:6,borderRadius:'50%',background:'#ef4444',animation:'blink 1s infinite',flexShrink:0}} />}
                                            #{m}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ─── RADAR NODES ─ IVR AESTHETIC ───
// ─── RADAR NODES ─ HIERARCHICAL PBX STYLE ───
// ─────────────────────────────────────────────
// RADAR NODES ─ PROFESSIONAL REAL-TIME STYLE
// ─────────────────────────────────────────────

const RadarTrunkNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    return (
        <div className="radar-node-glass border-blue-500/50" style={{ 
            width: 130, height: 130, borderRadius: '2rem', background: 'rgba(59,130,246,0.05)', 
            border: '2px dashed rgba(59,130,246,0.3)', display: 'flex', flexDirection: 'column', 
            alignItems: 'center', justifyContent: 'center', backdropFilter: 'blur(10px)'
        }}>
            <div className="absolute -top-3 px-3 py-1 bg-blue-600 rounded-full text-[9px] font-black text-white tracking-widest uppercase shadow-lg shadow-blue-500/30">Línea Externa</div>
            <span className="material-icons-round text-4xl text-blue-500 mb-2">settings_input_component</span>
            <div className="text-sm font-bold text-white uppercase tracking-tighter">{data.name || 'TRUNK'}</div>
            <div className="text-[10px] text-blue-400 font-bold opacity-60">PROV: TELEFLOW</div>
            
            {H && <H type="source" position={P?.Right} id="r" style={{ background: '#3b82f6', width: 10, height: 10 }} />}
        </div>
    );
};

const RadarIVRNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    return (
        <div className="radar-node-glass border-purple-500/50" style={{ 
            width: 140, height: 140, borderRadius: '2rem', background: 'rgba(168,85,247,0.05)', 
            border: '2px solid rgba(168,85,247,0.4)', display: 'flex', flexDirection: 'column', 
            alignItems: 'center', justifyContent: 'center', backdropFilter: 'blur(10px)'
        }}>
            <div className="absolute -top-3 px-3 py-1 bg-purple-600 rounded-full text-[9px] font-black text-white tracking-widest uppercase">Menú IVR</div>
            <span className="material-icons-round text-4xl text-purple-400 mb-2">account_tree</span>
            <div className="text-sm font-bold text-white uppercase">{data.name || 'IVR'}</div>
            <div className="text-[10px] text-purple-300 opacity-60">ID: {data.id}</div>
            
            {H && (
                <>
                    <H type="target" position={P?.Left} id="l" style={{ background: '#a855f7' }} />
                    <H type="source" position={P?.Right} id="r" style={{ background: '#a855f7' }} />
                </>
            )}
        </div>
    );
};

const RadarQueueNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    const hasCalls = data.calls_waiting > 0;
    return (
        <div className={`radar-node-glass ${hasCalls ? 'anim-vibrate border-orange-500 shadow-[0_0_30px_rgba(245,158,11,0.2)]' : 'border-indigo-500/50'}`} style={{ 
            width: 160, height: 160, borderRadius: '50%', background: 'rgba(79,70,229,0.05)', 
            border: '3px solid rgba(79,70,229,0.4)', display: 'flex', flexDirection: 'column', 
            alignItems: 'center', justifyContent: 'center', backdropFilter: 'blur(10px)'
        }}>
            <div className={`absolute -top-3 px-4 py-1.5 ${hasCalls ? 'bg-orange-500' : 'bg-indigo-600'} rounded-full text-[10px] font-black text-white tracking-widest uppercase shadow-xl`}>
                {hasCalls ? 'Saturación' : 'Cola Espera'}
            </div>
            <span className={`material-icons-round text-5xl mb-1 ${hasCalls ? 'text-orange-500 anim-phone-ring' : 'text-indigo-400'}`}>hub</span>
            <div className="text-sm font-black text-white px-2 text-center leading-tight">{data.name || 'COLA'}</div>
            <div className="mt-2 flex flex-col items-center">
                <span className={`text-2xl font-black ${hasCalls ? 'text-orange-500' : 'text-white'}`}>{data.calls_waiting || 0}</span>
                <span className="text-[8px] font-bold text-slate-400 uppercase tracking-widest">En Espera</span>
            </div>
            <div className="absolute -bottom-2 text-[10px] font-bold text-indigo-400 bg-black/50 px-2 rounded-full">#{data.id}</div>
            
            {H && (
                <>
                    <H type="target" position={P?.Left} id="l" style={{ background: '#6366f1' }} />
                    <H type="source" position={P?.Right} id="r" style={{ background: '#6366f1' }} />
                    <H type="source" position={P?.Bottom} id="b" style={{ background: '#6366f1' }} />
                </>
            )}
        </div>
    );
};

const RadarGroupNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    return (
        <div className="radar-node-glass border-teal-500/50" style={{ 
            width: 140, height: 140, borderRadius: '2rem', background: 'rgba(20,184,166,0.05)', 
            border: '2px solid rgba(20,184,166,0.4)', display: 'flex', flexDirection: 'column', 
            alignItems: 'center', justifyContent: 'center', backdropFilter: 'blur(10px)'
        }}>
            <div className="absolute -top-3 px-3 py-1 bg-teal-600 rounded-full text-[9px] font-black text-white tracking-widest uppercase">Ring Group</div>
            <span className="material-icons-round text-4xl text-teal-400 mb-2">groups</span>
            <div className="text-sm font-bold text-white uppercase text-center px-2 leading-none">{data.name || 'GRUPO'}</div>
            <div className="text-[10px] text-teal-300 opacity-60 mt-1">NÚMERO: {data.id}</div>
            
            {H && (
                <>
                    <H type="target" position={P?.Left} id="l" style={{ background: '#14b8a6' }} />
                    <H type="source" position={P?.Right} id="r" style={{ background: '#14b8a6' }} />
                </>
            )}
        </div>
    );
};

const RadarAgentNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    const call = data.activeCall;
    const status = data.agent?.status || 'OFFLINE';
    
    const isTalking = call && call.isBridged;
    const isRinging = call && !call.isBridged;
    const isPaused = status === 'PAUSE' || status === 'OFFLINE';
    
    let borderColor = 'rgba(255,255,255,0.1)';
    let statusLabel = 'Disponible';
    let icon = 'person';
    let iconColor = '#94a3b8';
    
    if (isTalking) { borderColor = '#22c55e'; statusLabel = 'HABLANDO'; icon = 'record_voice_over'; iconColor = '#22c55e'; }
    else if (isRinging) { borderColor = '#f59e0b'; statusLabel = 'TIMBRANDO'; icon = 'notifications_active'; iconColor = '#f59e0b'; }
    else if (status === 'ONLINE') { borderColor = '#3b82f6'; statusLabel = 'ESPERANDO'; iconColor = '#3b82f6'; }
    else if (isPaused) { borderColor = '#64748b'; statusLabel = 'PAUSA'; icon = 'pause_circle'; }

    return (
        <div className={`radar-node-glass transition-all duration-500 group ${isTalking ? 'anim-pulse-border-green' : ''} ${isRinging ? 'anim-vibrate anim-phone-ring' : ''}`} style={{ 
            width: 120, height: 120, borderRadius: '50%', background: 'rgba(15,23,42,0.6)', 
            border: `3px solid ${borderColor}`, display: 'flex', flexDirection: 'column', 
            alignItems: 'center', justifyContent: 'center', position: 'relative'
        }}>
            {/* Context Actions */}
            <div className="absolute -right-20 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 backdrop-blur-md p-2 rounded-xl border border-white/10 z-50">
                <button className="p-2 hover:bg-white/10 rounded-lg text-blue-400" title="Espiar (Spy)"><span className="material-icons-round text-lg">visibility</span></button>
                <button className="p-2 hover:bg-white/10 rounded-lg text-orange-400" title="Susurrar (Whisper)"><span className="material-icons-round text-lg">record_voice_over</span></button>
                <button className="p-2 hover:bg-white/10 rounded-lg text-red-500" title="Cortar (Hangup)"><span className="material-icons-round text-lg">call_end</span></button>
            </div>

            <div className="absolute -bottom-6 w-full text-center">
                <div className="text-[11px] font-black text-white leading-none">#{data.agent?.ext}</div>
                <div className="text-[8px] font-bold text-slate-500 uppercase tracking-tighter mt-1">{data.agent?.name}</div>
            </div>

            <div className="size-16 rounded-full bg-slate-800/50 flex items-center justify-center border border-white/5 relative">
                <span className="material-icons-round" style={{ fontSize: 32, color: iconColor }}>{icon}</span>
                <div className="absolute top-0 right-0 size-4 rounded-full border-2 border-[#0f172a]" style={{ background: iconColor }}></div>
            </div>

            {call && (
                <div className="absolute -top-4 px-3 py-0.5 rounded-full bg-slate-900 border border-white/10 text-[8px] font-black text-white flex items-center gap-1 shadow-2xl">
                   <span className="size-1.5 rounded-full bg-red-500 animate-pulse"></span>
                   {isTalking ? `HABLANDO` : `ENTRANTE`}
                </div>
            )}

            {H && <H type="target" position={P?.Left} id="l" style={{ background: borderColor }} />}
        </div>
    );
};

// ─── CUSTOM EDGE ─ DATA FLOW ───
const AnimatedDataEdge = ({ id, data, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style = {}, animated }) => {
    const rfLib = window.ReactFlow || {};
    const getPath = rfLib.getBezierPath || (rfLib.default && rfLib.default.getBezierPath) || ((p) => ["", 0, 0]);
    
    const [edgePath, labelX, labelY] = getPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });

    const isProcessing = id.includes('-p-'); // Trunk to Processing or Processing to Agent
    const defaultColor = isProcessing ? 'rgba(79,70,229,0.2)' : 'rgba(255,255,255,0.15)';
    const activeColor = isProcessing ? '#3b82f6' : '#22c55e'; 

    return (
        <>
            <path id={id} className="react-flow__edge-path" d={edgePath} style={{ ...style, fill:'none', strokeWidth: animated ? 4 : 1.5, stroke: animated ? activeColor : (style.stroke || defaultColor), filter: animated ? `drop-shadow(0 0 8px ${activeColor})` : 'none', opacity: animated ? 1 : 0.4 }} />
            {animated && (
                <>
                    <circle r="5" fill={activeColor} style={{ filter: `drop-shadow(0 0 5px ${activeColor})` }}>
                        <animateMotion dur="1.2s" repeatCount="indefinite" path={edgePath} />
                    </circle>
                    <circle r="3" fill="#fff">
                        <animateMotion dur="1.2s" repeatCount="indefinite" path={edgePath} />
                    </circle>
                    {!isProcessing && (
                        <foreignObject width={100} height={40} x={labelX - 50} y={labelY - 20} className="pointer-events-none">
                            <div style={{ 
                                background: activeColor, color: 'white', 
                                fontSize: 10, fontWeight: 900, padding: '3px 10px', 
                                borderRadius: 20, textAlign: 'center', backdropFilter: 'blur(4px)',
                                border: '1px solid rgba(255,255,255,0.3)',
                                boxShadow: '0 4px 15px rgba(0,0,0,0.3)'
                            }}>
                                <div className="flex flex-col items-center">
                                    <div className="flex items-center gap-1.5 overflow-hidden">
                                        <span className="material-icons-round text-[10px]">waves</span>
                                        {data?.duration || '00:00'}
                                    </div>
                                    <div className="text-[6px] opacity-70 tracking-widest mt-0.5">OPUS · 24ms · 0% LOSS</div>
                                </div>
                            </div>
                        </foreignObject>
                    )}
                </>
            )}
        </>
    );
};

const radarNodeTypes = {
    trunk: RadarTrunkNode,
    ivr: RadarIVRNode,
    queue: RadarQueueNode,
    group: RadarGroupNode,
    agent: RadarAgentNode
};
const radarEdgeTypes = {
    animatedData: AnimatedDataEdge
};


// ─────────────────────────────────────────────
// VISTA: RADAR DE TRÁFICO (REACT FLOW)
// ─────────────────────────────────────────────
function ViewRadar({ data, toast }) {
    const [tick, setTick] = useState(0);
    const [search, setSearch] = useState('');
    const [supervisorExt, setSupervisorExt] = useState(() => localStorage.getItem('tf_supervisor_ext') || '');
    const [stateFilter, setStateFilter] = useState('all');
    
    useEffect(() => { const t = setInterval(()=>setTick(k=>k+1), 1000); return ()=>clearInterval(t); }, []);
    useEffect(() => { localStorage.setItem('tf_supervisor_ext', supervisorExt); }, [supervisorExt]);

    const liveCalls = data?.pbx?.live_calls || [];
    const exts = data?.pbx?.extensions || [];
    const queues = data?.pbx?.queues || [];
    const extByNum = useMemo(() => { const m={}; exts.forEach(e=>{m[e.ext]=e;}); return m; }, [exts]);
    const qByNum = useMemo(() => { const m={}; queues.forEach(q=>{m[q.id]=q;}); return m; }, [queues]);

    const filteredLive = liveCalls.filter(c => {
        if (stateFilter==='up') return c.state==='Up';
        if (stateFilter==='ringing') return /Ring/.test(c.state||'');
        return true;
    });
    const calls = filteredLive.map(c => {
        const fromExt = c.ext || c.callerid;
        const toExt = c.peer_ext || (c.dest && /^\d+$/.test(c.dest) ? c.dest : null);
        const fromInfo = fromExt ? extByNum[fromExt] : null;
        const toInfo = toExt ? extByNum[toExt] : null;
        const toQueue = c.dest && qByNum[c.dest] ? qByNum[c.dest] : null;
        const fromTipo = (window._tfExtMeta || {})[fromExt]?.tipo || '';
        return {
            ...c, from_ext: fromExt, from_name: fromInfo?.name || fromExt, from_ip: fromInfo?.ip,
            to_ext: toExt, to_name: toInfo?.name || (toQueue?'Cola '+toQueue.name:null) || (typeof fmtDest === 'function'?fmtDest(c):c.dest),
            to_ip: toInfo?.ip, to_queue: toQueue, from_tipo: fromTipo,
            destLabel: typeof fmtDest === 'function' ? fmtDest(c) : (c.dest || '—')
        };
    });

    const filtered = calls.filter(c => {
        if (stateFilter==='up' && c.state !== 'Up') return false;
        if (stateFilter==='ringing' && !/Ring/.test(c.state||'')) return false;
        if (stateFilter==='other' && (c.state==='Up' || /Ring/.test(c.state||''))) return false;
        if (!search) return true;
        const q = search.toLowerCase();
        return (c.from_ext||'').includes(search) || (c.to_ext||'').includes(search) || 
               (c.from_name||'').toLowerCase().includes(q) || (c.to_name||'').toLowerCase().includes(q) ||
               (c.callerid||'').includes(search);
    });

    const upCount = calls.filter(c => c.state === 'Up').length;
    const ringCount = calls.filter(c => /Ring/.test(c.state||'')).length;
    const otherCount = calls.length - upCount - ringCount;

    const parseDur = (s) => { if (!s) return 0; const p=String(s).split(':').map(x=>parseInt(x)||0); if(p.length===3) return p[0]*3600+p[1]*60+p[2]; if(p.length===2) return p[0]*60+p[1]; return p[0]||0; };
    const fmtDur = (s) => { const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sc=s%60; return h>0?`${h}:${String(m).padStart(2,'0')}:${String(sc).padStart(2,'0')}`:`${String(m).padStart(2,'0')}:${String(sc).padStart(2,'0')}`; };

    const handleHangup = async (channel) => {
        if (!confirm('Colgar la llamada?')) return;
        const fd = new FormData(); fd.append('type','hangup'); fd.append('channel',channel);
        const r = await fetch('api/index.php?action=call_action',{method:'POST',body:fd,credentials:'include'});
        const j = await r.json();
        toast?.(j.success?'Colgada':'Error', j.success?'success':'error');
    };
    const handleSpy = async (channel, mode) => {
        if (!supervisorExt) { toast?.('Configurá tu ext arriba','error'); return; }
        const fd = new FormData(); fd.append('type',mode); fd.append('channel',channel); fd.append('supervisor',supervisorExt);
        const r = await fetch('api/index.php?action=call_action',{method:'POST',body:fd,credentials:'include'});
        const j = await r.json();
        toast?.(j.success?`${mode} en ${supervisorExt}`:'Error', j.success?'success':'error');
    };
    const handleAssign = (c) => window.dispatchEvent(new CustomEvent('tf-assign-call', {detail: c}));

    const StatChip = ({label, count, value, color, icon}) => (
        <button onClick={()=>setStateFilter(value)} style={{padding:'8px 14px',borderRadius:10,border:`1px solid ${stateFilter===value?color:'var(--border)'}`,background:stateFilter===value?`${color}22`:'transparent',color:stateFilter===value?color:'var(--muted)',fontWeight:800,fontSize:11,cursor:'pointer',display:'flex',alignItems:'center',gap:6,transition:'all 0.2s'}}>
            <span className="material-icons-round" style={{fontSize:14,color}}>{icon}</span>
            {label} <span style={{fontFamily:'monospace',fontWeight:900,padding:'1px 7px',borderRadius:5,background:stateFilter===value?'rgba(255,255,255,0.06)':'rgba(255,255,255,0.04)',marginLeft:3}}>{count}</span>
        </button>
    );

    return (
        <div className="content-area view-enter">
            <PageActions>
                <div style={{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.05em'}}>{calls.length} llamadas activas</div>
                <div style={{display:'flex',alignItems:'center',gap:8}}>
                    <span className="material-icons-round" style={{fontSize:16,color:'var(--muted)'}}>admin_panel_settings</span>
                    <div>
                        <div style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>Mi ext</div>
                        <input type="text" value={supervisorExt} onChange={e=>setSupervisorExt(e.target.value)} placeholder="1001" className="input-tf" style={{padding:'5px 10px',borderRadius:8,fontSize:12,fontFamily:'monospace',width:80,textAlign:'center'}}/>
                    </div>
                </div>
            </PageActions>

            {/* Toolbar: filtros + búsqueda */}
            <div className="glass" style={{padding:'10px 14px',borderRadius:12,marginBottom:12,display:'flex',gap:8,alignItems:'center',flexWrap:'wrap'}}>
                <StatChip label="Todas" value="all" count={calls.length} color="#8b5cf6" icon="filter_list"/>
                <StatChip label="En conversación" value="up" count={upCount} color="#22c55e" icon="phone_in_talk"/>
                <StatChip label="Sonando" value="ringing" count={ringCount} color="#f59e0b" icon="ring_volume"/>
                <StatChip label="Otras" value="other" count={otherCount} color="#6b7280" icon="more_horiz"/>
                <div style={{flex:1}}/>
                <div style={{position:'relative',width:240}}>
                    <span className="material-icons-round" style={{position:'absolute',left:9,top:'50%',transform:'translateY(-50%)',fontSize:15,color:'var(--muted)'}}>search</span>
                    <input className="input-tf" placeholder="Buscar número o nombre..." value={search} onChange={e=>setSearch(e.target.value)} style={{padding:'7px 10px 7px 30px',borderRadius:8,fontSize:11,width:'100%'}}/>
                </div>
            </div>

            {/* Cards de llamadas */}
            {filtered.length === 0 ? (
                <div className="glass" style={{padding:60,textAlign:'center',borderRadius:18,border:'2px dashed var(--border)'}}>
                    <span className="material-icons-round" style={{fontSize:54,color:'var(--muted)',display:'block',marginBottom:12}}>phone_disabled</span>
                    <div style={{fontSize:14,fontWeight:700}}>{calls.length===0?'No hay llamadas activas':'Sin resultados con esos filtros'}</div>
                    <div style={{fontSize:11,color:'var(--muted)',marginTop:4}}>Aparecerán acá en tiempo real</div>
                </div>
            ) : (
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(440px,1fr))',gap:12}}>
                    {filtered.map((c,i) => {
                        const isUp = c.state === 'Up';
                        const isRing = /Ring/.test(c.state||'');
                        const sc = isUp ? '#22c55e' : (isRing ? '#f59e0b' : '#6b7280');
                        const dur = parseDur(c.duration) + (isUp ? tick : 0);
                        const fromTipoColor = c.from_tipo === 'cliente' ? '#3b82f6' : (c.from_tipo === 'horizon' ? '#8b5cf6' : null);
                        return (
                        <div key={c.channel||i} className="glass" style={{padding:0,borderRadius:14,border:`1px solid ${sc}33`,overflow:'hidden',position:'relative',boxShadow:isUp?`0 4px 18px ${sc}22`:(isRing?`0 4px 18px ${sc}33`:'none')}}>
                            {/* Top status bar */}
                            <div style={{padding:'7px 12px',background:`linear-gradient(90deg,${sc}22,${sc}05)`,borderBottom:`1px solid ${sc}22`,display:'flex',alignItems:'center',justifyContent:'space-between',gap:6}}>
                                <span style={{display:'inline-flex',alignItems:'center',gap:6,fontSize:10,fontWeight:900,color:sc,textTransform:'uppercase',letterSpacing:'.06em'}}>
                                    <span style={{width:7,height:7,borderRadius:'50%',background:sc,animation:isRing?'pulse 1s infinite':'none',boxShadow:`0 0 8px ${sc}cc`}}/>
                                    {isUp?'En conversación':(isRing?'Sonando':c.state||'—')}
                                </span>
                                {fromTipoColor && <span style={{fontSize:9,padding:'2px 8px',borderRadius:5,background:`${fromTipoColor}22`,color:fromTipoColor,fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em',border:`1px solid ${fromTipoColor}66`}}>{c.from_tipo}</span>}
                                <span style={{fontFamily:'monospace',fontSize:14,fontWeight:900,color:'var(--text)',marginLeft:'auto'}}>{fmtDur(dur)}</span>
                            </div>

                            {/* From → To */}
                            <div style={{padding:'12px',display:'flex',alignItems:'center',gap:8}}>
                                <div style={{flex:1,minWidth:0,display:'flex',alignItems:'center',gap:9}}>
                                    <div style={{width:38,height:38,borderRadius:'50%',background:'linear-gradient(135deg,#3b82f6,#1d4ed8)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900,flexShrink:0}}>
                                        {(c.from_name||c.from_ext||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}
                                    </div>
                                    <div style={{minWidth:0,flex:1}}>
                                        <div style={{fontSize:13,fontWeight:800,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{c.from_name}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>#{c.from_ext}{c.from_ip&&c.from_ip!=='—'?' · '+c.from_ip:''}</div>
                                    </div>
                                </div>
                                <span className="material-icons-round" style={{fontSize:20,color:sc,animation:isRing?'pulse 1s infinite':'none',flexShrink:0}}>arrow_forward</span>
                                <div style={{flex:1,minWidth:0,display:'flex',alignItems:'center',gap:9,justifyContent:'flex-end'}}>
                                    <div style={{minWidth:0,flex:1,textAlign:'right'}}>
                                        <div style={{fontSize:13,fontWeight:800,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{c.to_name||c.destLabel}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>{c.to_ext?'#'+c.to_ext:c.context||''}{c.to_ip&&c.to_ip!=='—'?' · '+c.to_ip:''}</div>
                                    </div>
                                    <div style={{width:38,height:38,borderRadius:'50%',background:c.to_queue?'linear-gradient(135deg,#f59e0b,#d97706)':'linear-gradient(135deg,#8b5cf6,#6d28d9)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900,flexShrink:0}}>
                                        {c.to_queue ? <span className="material-icons-round" style={{fontSize:18}}>queue</span> : (c.to_ext ? (c.to_name||c.to_ext||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase() : <span className="material-icons-round" style={{fontSize:16}}>more_horiz</span>)}
                                    </div>
                                </div>
                            </div>

                            {/* Channel info */}
                            <div style={{padding:'5px 12px',fontSize:9,color:'var(--muted)',fontFamily:'monospace',borderTop:'1px solid var(--border)',display:'flex',justifyContent:'space-between',gap:6,alignItems:'center'}}>
                                <span style={{overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',flex:1}}>{c.channel}</span>
                                <span style={{whiteSpace:'nowrap'}}>{c.app||'-'}</span>
                            </div>

                            {/* Action buttons */}
                            <div style={{padding:'8px 12px',background:'var(--surface2)',display:'flex',gap:5,borderTop:'1px solid var(--border)'}}>
                                <button onClick={()=>handleSpy(c.channel,'spy')} title="Escuchar" style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid rgba(59,130,246,0.3)',background:'rgba(59,130,246,0.08)',color:'#60a5fa',fontWeight:700,fontSize:9,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:3}}>
                                    <span className="material-icons-round" style={{fontSize:12}}>headphones</span>Espiar
                                </button>
                                <button onClick={()=>handleSpy(c.channel,'whisper')} title="Susurrar" style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid rgba(245,158,11,0.3)',background:'rgba(245,158,11,0.08)',color:'#f59e0b',fontWeight:700,fontSize:9,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:3}}>
                                    <span className="material-icons-round" style={{fontSize:12}}>record_voice_over</span>Susurro
                                </button>
                                <button onClick={()=>handleSpy(c.channel,'barge')} title="Intervenir 3 vías" style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)',background:'color-mix(in srgb, var(--primary) 8%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:700,fontSize:9,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:3}}>
                                    <span className="material-icons-round" style={{fontSize:12}}>group</span>Barge
                                </button>
                                <button onClick={()=>handleAssign(c)} title="Asignar" style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid rgba(34,197,94,0.3)',background:'rgba(34,197,94,0.08)',color:'#22c55e',fontWeight:700,fontSize:9,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:3}}>
                                    <span className="material-icons-round" style={{fontSize:12}}>swap_horiz</span>Asignar
                                </button>
                                <button onClick={()=>handleHangup(c.channel)} title="Colgar" style={{padding:'5px 10px',borderRadius:6,border:'1px solid rgba(239,68,68,0.3)',background:'rgba(239,68,68,0.08)',color:'#ef4444',fontWeight:700,fontSize:9,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                    <span className="material-icons-round" style={{fontSize:12}}>call_end</span>
                                </button>
                            </div>
                        </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
function LiveCallCard({ call, data, supervisorExt, toast }) {
    const [actionLoading, setActionLoading] = React.useState(null);
    
    // Buscar info extendida del agente si existe
    const agent = data?.pbx?.extensions?.find(e => e.ext === call.ext);
    const avatar = agent?.avatar || `https://ui-avatars.com/api/?name=${call.ext}&background=7c3aed&color=fff`;

    const handleAction = async (type) => {
        if (type !== 'hangup' && !supervisorExt) {
            toast('Primero configura tu extensión de supervisor en la parte superior', 'error');
            return;
        }
        setActionLoading(type);
        try {
            const fd = new FormData();
            fd.append('type', type);
            fd.append('channel', call.channel);
            fd.append('supervisor', supervisorExt);
            
            const r = await fetch('api/index.php?action=call_action', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) toast(d.message, 'success');
            else toast(d.error || 'Error en la acción', 'error');
        } catch (e) {
            toast('Error de conexión', 'error');
        }
        setActionLoading(null);
    };

    return (
        <div className="relative group anim-fadeup" style={{ marginBottom: 14 }}
             draggable="true" 
             onDragStart={(e) => {
                e.dataTransfer.setData('call', JSON.stringify({ channel: call.channel, ext: call.ext }));
                e.dataTransfer.effectAllowed = 'move';
             }}>
            {/* Accent Glow */}
            <div className="absolute -inset-0.5 bg-gradient-to-r from-brand-accent to-brand-success rounded-xl blur opacity-10 group-hover:opacity-20 transition duration-500"></div>
            
            {/* Main Card Body */}
            <div className="relative glass-effect rounded-xl p-4 flex items-center justify-between transition-all duration-300 hover:bg-white/[0.05] cursor-grab active:cursor-grabbing" style={{background: 'rgba(255,255,255,0.03)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.08)'}}>
                <div className="flex items-center gap-10">
                    {/* Connection Path */}
                    <div className="flex items-center gap-4">
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Origen</span>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-brand-accent/20 flex items-center justify-center">
                                    <span className="text-sm font-bold text-white">{call.ext.substring(0,2)}</span>
                                </div>
                                <span className="text-lg font-bold text-white">{call.ext}</span>
                                <span className={`w-2 h-2 rounded-full ${call.state==='Up'?'bg-brand-success':'bg-brand-warning animate-pulse'}`}></span>
                            </div>
                        </div>
                        
                        <svg className="w-5 h-5 text-gray-600 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M13 7l5 5-5 5M6 7l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"></path>
                        </svg>
                        
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Destino</span>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-bold text-white">{call.dest}</span>
                                <span className={`${call.state==='Up'?'bg-brand-success/10 text-brand-success':'bg-brand-warning/10 text-brand-warning'} text-[10px] px-1.5 py-0.5 rounded font-black uppercase tracking-tighter`}>
                                    {call.state}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Technical Stats */}
                    <div className="hidden lg:flex items-center gap-8">
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Codec</span>
                            <span className="text-sm font-medium text-gray-300">{call.tech?.codec || '—'}</span>
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Tx RTT</span>
                            <span className="text-sm font-medium text-gray-300">{call.tech?.tx_rtt || '—'}</span>
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Rx Loss</span>
                            <span className={`text-sm font-medium ${parseFloat(call.tech?.rx_loss)>1?'text-brand-warning':'text-gray-300'}`}>
                                {call.tech?.rx_loss || '—'}
                            </span>
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">REC</span>
                            <span className={`material-icons-round text-sm ${call.recording?'text-brand-danger animate-pulse':'text-gray-600'}`}>
                                {call.recording ? 'radio_button_checked' : 'radio_button_unchecked'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Timer & Actions */}
                <div className="flex items-center gap-8">
                    <div className="text-right">
                        <div className="text-2xl font-mono font-black text-yellow-500" style={{ letterSpacing: '-0.05em' }}>
                            {call.duration}
                        </div>
                        <span className="text-[10px] font-bold uppercase tracking-widest text-gray-500">DURACIÓN</span>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Spy (Listen) */}
                        <button 
                            onClick={() => handleAction('spy')}
                            disabled={actionLoading}
                            className={`w-10 h-10 rounded-lg border border-white/5 hover:bg-brand-accent/20 hover:border-brand-accent/30 text-gray-400 hover:text-white transition-all duration-300 flex items-center justify-center group/btn`} 
                            title="Solo Escuchar (Spy)"
                        >
                            <span className={`material-icons-round text-xl ${actionLoading==='spy'?'animate-spin':''}`}>
                                {actionLoading==='spy'?'sync':'headphones'}
                            </span>
                        </button>

                        {/* Whisper */}
                        <button 
                            onClick={() => handleAction('whisper')}
                            disabled={actionLoading}
                            className={`w-10 h-10 rounded-lg border border-white/5 hover:bg-blue-500/20 hover:border-blue-500/30 text-gray-400 hover:text-white transition-all duration-300 flex items-center justify-center group/btn`} 
                            title="Susurrar (Solo Agente Te Escucha)"
                        >
                            <span className={`material-icons-round text-xl ${actionLoading==='whisper'?'animate-spin':''}`}>
                                {actionLoading==='whisper'?'sync':'record_voice_over'}
                            </span>
                        </button>

                        {/* Barge */}
                        <button 
                            onClick={() => handleAction('barge')}
                            disabled={actionLoading}
                            className={`w-10 h-10 rounded-lg border border-white/5 hover:bg-brand-warning/20 hover:border-brand-warning/30 text-gray-400 hover:text-white transition-all duration-300 flex items-center justify-center group/btn`} 
                            title="Intervenir (Barge-in)"
                        >
                            <span className={`material-icons-round text-xl ${actionLoading==='barge'?'animate-spin':''}`}>
                                {actionLoading==='barge'?'sync':'group_add'}
                            </span>
                        </button>

                        {/* Hangup */}
                        <button 
                            onClick={() => handleAction('hangup')}
                            disabled={actionLoading}
                            className={`w-10 h-10 rounded-lg border border-white/5 bg-brand-danger/10 hover:bg-brand-danger/20 border-brand-danger/20 hover:border-brand-danger/30 text-brand-danger transition-all duration-300 flex items-center justify-center group/btn`} 
                            title="Finalizar Llamada"
                        >
                            <span className={`material-icons-round text-xl ${actionLoading==='hangup'?'animate-spin':''}`}>
                                {actionLoading==='hangup'?'sync':'call_end'}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
// ─────────────────────────────────────────────
// VISTA: LLAMADAS EN VIVO 2.0 — Control supervisor profesional
// ─────────────────────────────────────────────
function ViewVivo2({ data, toast, initialFilter }) {
    const [supervisorExt, setSupervisorExt] = useState(() => localStorage.getItem('tf_supervisor_ext') || '');
    const [tick, setTick] = useState(0);

    useEffect(() => { localStorage.setItem('tf_supervisor_ext', supervisorExt); }, [supervisorExt]);
    useEffect(() => { const t = setInterval(() => setTick(k => k+1), 1000); return () => clearInterval(t); }, []);

    const liveCalls = data?.pbx?.live_calls || [];
    const extensions = data?.pbx?.extensions || [];
    const extByNum = useMemo(() => { const m = {}; extensions.forEach(e => { m[e.ext] = e; }); return m; }, [extensions]);

    const calls = liveCalls.map(c => {
        const fromExt = c.ext || c.callerid;
        const toExt = c.peer_ext || (c.dest && /^\d+$/.test(c.dest) ? c.dest : null);
        const fromInfo = fromExt ? extByNum[fromExt] : null;
        const toInfo = toExt ? extByNum[toExt] : null;
        return {
            ...c,
            from_ext: fromExt, from_name: fromInfo?.name || fromExt,
            to_ext: toExt, to_name: toInfo?.name || toExt,
            destLabel: typeof fmtDest === 'function' ? fmtDest(c) : (c.dest || '—')
        };
    });

    const handleHangup = async (channel) => {
        if (!confirm('Colgar la llamada?')) return;
        const fd = new FormData(); fd.append('type', 'hangup'); fd.append('channel', channel);
        try { const r = await fetch('api/index.php?action=call_action', {method:'POST',body:fd,credentials:'include'}); const j = await r.json(); toast?.(j.success?'Colgada':'Error', j.success?'success':'error'); } catch(e) { toast?.('Error de red','error'); }
    };
    const handleSpy = async (channel, mode) => {
        if (!supervisorExt) { toast?.('Configurá tu extensión arriba','error'); return; }
        const fd = new FormData(); fd.append('type', mode); fd.append('channel', channel); fd.append('supervisor', supervisorExt);
        try { const r = await fetch('api/index.php?action=call_action', {method:'POST',body:fd,credentials:'include'}); const j = await r.json(); toast?.(j.success?`${mode} en ${supervisorExt}`:(j.error||'Error'), j.success?'success':'error'); } catch(e) { toast?.('Error de red','error'); }
    };
    const handleAssign = (c) => window.dispatchEvent(new CustomEvent('tf-assign-call', {detail: c}));

    const upCount = calls.filter(c => c.state === 'Up').length;
    const ringingCount = calls.filter(c => /Ring/.test(c.state||'')).length;

    const parseDur = (s) => { if (!s) return 0; const p = String(s).split(':').map(x => parseInt(x)||0); if (p.length===3) return p[0]*3600+p[1]*60+p[2]; if (p.length===2) return p[0]*60+p[1]; return p[0]||0; };
    const fmtDur = (s) => { const h = Math.floor(s/3600), mn = Math.floor((s%3600)/60), sc = s%60; return h>0 ? `${h}:${String(mn).padStart(2,'0')}:${String(sc).padStart(2,'0')}` : `${mn}:${String(sc).padStart(2,'0')}`; };

    return (
        <div className="content-area view-enter" style={{padding:'8px 4px'}}>
            <PageActions>
                <div style={{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.05em'}}>{calls.length} canales · {upCount} hablando · {ringingCount} sonando</div>
                <div style={{display:'flex',alignItems:'center',gap:8}}>
                    <span className="material-icons-round" style={{fontSize:16,color:'var(--muted)'}}>admin_panel_settings</span>
                    <div>
                        <div style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.05em'}}>Mi extensión</div>
                        <input type="text" value={supervisorExt} onChange={e=>setSupervisorExt(e.target.value)} placeholder="Ej: 1001" className="input-tf" style={{padding:'5px 10px',borderRadius:8,fontSize:12,fontFamily:'monospace',fontWeight:700,width:90,textAlign:'center'}}/>
                    </div>
                </div>
            </PageActions>

            {calls.length === 0 ? (
                <div className="glass" style={{padding:60,textAlign:'center',borderRadius:18,border:'2px dashed var(--border)'}}>
                    <span className="material-icons-round" style={{fontSize:54,color:'var(--muted)',display:'block',marginBottom:12}}>phone_disabled</span>
                    <div style={{fontSize:14,fontWeight:700,color:'var(--text)',marginBottom:4}}>No hay llamadas activas</div>
                    <div style={{fontSize:11,color:'var(--muted)'}}>Las llamadas aparecerán acá en tiempo real cuando entren</div>
                </div>
            ) : (
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(440px,1fr))',gap:18}}>
                    {calls.map((c,i) => {
                        const isUp = c.state === 'Up';
                        const isRing = /Ring/.test(c.state||'');
                        const sc = isUp ? '#22c55e' : (isRing ? '#f59e0b' : '#6b7280');
                        const dur = parseDur(c.duration) + (isUp ? tick : 0);
                        return (
                        <div key={c.channel||i} className="glass" style={{padding:0,borderRadius:16,border:`1px solid ${sc}33`,overflow:'hidden',position:'relative',marginBottom:0,boxShadow:isUp?'0 6px 22px rgba(34,197,94,0.15)':(isRing?'0 4px 18px rgba(245,158,11,0.18)':'none')}}>
                            <div style={{padding:'8px 14px',background:`linear-gradient(135deg,${sc}22,${sc}05)`,borderBottom:`1px solid ${sc}22`,display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                                <span style={{display:'inline-flex',alignItems:'center',gap:6,fontSize:11,fontWeight:800,color:sc,textTransform:'uppercase',letterSpacing:'.05em'}}>
                                    <span style={{width:7,height:7,borderRadius:'50%',background:sc,animation:isRing?'pulse 1s infinite':'none'}}/>
                                    {isUp ? 'En conversación' : (isRing ? 'Sonando' : c.state||'—')}
                                </span>
                                <span style={{fontFamily:'monospace',fontSize:11,fontWeight:800,color:'var(--text)'}}>{fmtDur(dur)}</span>
                            </div>
                            <div style={{padding:'14px 14px 12px',display:'flex',alignItems:'center',gap:10}}>
                                <div style={{flex:1,minWidth:0,display:'flex',alignItems:'center',gap:9}}>
                                    <div style={{width:42,height:42,borderRadius:'50%',background:'linear-gradient(135deg,#3b82f6,#1d4ed8)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900,flexShrink:0}}>
                                        {(c.from_name||c.from_ext||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}
                                    </div>
                                    <div style={{minWidth:0,flex:1}}>
                                        <div style={{fontSize:13,fontWeight:800,color:'var(--text)',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{c.from_name}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>#{c.from_ext}</div>
                                    </div>
                                </div>
                                <span className="material-icons-round" style={{fontSize:22,color:sc,flexShrink:0,animation:isRing?'pulse 1s infinite':'none'}}>{isRing?'phone_in_talk':'arrow_forward'}</span>
                                <div style={{flex:1,minWidth:0,display:'flex',alignItems:'center',gap:9,justifyContent:'flex-end'}}>
                                    <div style={{minWidth:0,flex:1,textAlign:'right'}}>
                                        <div style={{fontSize:13,fontWeight:800,color:'var(--text)',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{c.to_name||c.destLabel}</div>
                                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>{c.to_ext?'#'+c.to_ext:''}</div>
                                    </div>
                                    <div style={{width:42,height:42,borderRadius:'50%',background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900,flexShrink:0}}>
                                        {c.to_ext ? (c.to_name||c.to_ext||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase() : <span className="material-icons-round" style={{fontSize:18}}>queue</span>}
                                    </div>
                                </div>
                            </div>
                            <div style={{padding:'6px 14px',fontSize:10,color:'var(--muted)',fontFamily:'monospace',borderTop:'1px solid var(--border)',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                                <span style={{overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',maxWidth:240}}>{c.channel}</span>
                                <span>{c.context||''}</span>
                            </div>
                            <div style={{padding:'10px 14px',background:'var(--surface2)',display:'flex',gap:6,borderTop:'1px solid var(--border)'}}>
                                <button onClick={()=>handleSpy(c.channel,'spy')} title="Escuchar en silencio" style={{flex:1,padding:'7px',borderRadius:8,border:'1px solid rgba(59,130,246,0.3)',background:'rgba(59,130,246,0.08)',color:'#60a5fa',fontWeight:700,fontSize:10,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:5}}>
                                    <span className="material-icons-round" style={{fontSize:14}}>headphones</span>Espiar
                                </button>
                                <button onClick={()=>handleSpy(c.channel,'whisper')} title="Susurrar al agente" style={{flex:1,padding:'7px',borderRadius:8,border:'1px solid rgba(245,158,11,0.3)',background:'rgba(245,158,11,0.08)',color:'#f59e0b',fontWeight:700,fontSize:10,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:5}}>
                                    <span className="material-icons-round" style={{fontSize:14}}>record_voice_over</span>Susurrar
                                </button>
                                <button onClick={()=>handleSpy(c.channel,'barge')} title="Intervenir 3 vías" style={{flex:1,padding:'7px',borderRadius:8,border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)',background:'color-mix(in srgb, var(--primary) 8%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:700,fontSize:10,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:5}}>
                                    <span className="material-icons-round" style={{fontSize:14}}>group</span>Intervenir
                                </button>
                                <button onClick={()=>handleAssign(c)} title="Asignar a otra ext/cola" style={{flex:1,padding:'7px',borderRadius:8,border:'1px solid rgba(34,197,94,0.3)',background:'rgba(34,197,94,0.08)',color:'#22c55e',fontWeight:700,fontSize:10,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:5}}>
                                    <span className="material-icons-round" style={{fontSize:14}}>swap_horiz</span>Asignar
                                </button>
                                <button onClick={()=>handleHangup(c.channel)} title="Cortar llamada" style={{padding:'7px 12px',borderRadius:8,border:'1px solid rgba(239,68,68,0.3)',background:'rgba(239,68,68,0.08)',color:'#ef4444',fontWeight:700,fontSize:10,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                    <span className="material-icons-round" style={{fontSize:14}}>call_end</span>
                                </button>
                            </div>
                        </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}



function AgentReportPanel({ agentNumber, onClose, embedded }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date(Date.now() - 7*86400000).toISOString().split('T')[0];
    const [from, setFrom] = useState(weekAgo);
    const [to, setTo]     = useState(today);

    const load = async () => {
        if (!agentNumber) return;
        setLoading(true);
        try {
            const r = await fetch(`api/agent_report.php?agent=${agentNumber}&from=${from}&to=${to}`, {credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') setData(j);
        } catch(e) {}
        setLoading(false);
    };
    useEffect(() => { load(); }, [agentNumber, from, to]);

    const exportXLSX = () => {
        if (!data || !window.XLSX) return;
        const wb = window.XLSX.utils.book_new();
        const styleH = { font:{bold:true,color:{rgb:'FFFFFF'}}, fill:{fgColor:{rgb:'8B5CF6'}} };
        // Hoja Resumen
        const s = data.summary || {};
        const ag = data.agent || {};
        const summary = [
            ['REPORTE DE AGENTE','Horizon Seguridad'],
            ['Agente', `#${ag.number} ${ag.name||''}`],
            ['Período', `${data.period.from} a ${data.period.to}`],
            ['Generado', new Date().toLocaleString('es-UY')],
            ['',''],
            ['MÉTRICA','VALOR'],
            ['Tiempo total logueado (s)', s.total_logged_seconds||0],
            ['Tiempo total en pausa (s)', s.total_paused_seconds||0],
            ['Cantidad de pausas', s.total_pauses||0],
            ['Llamadas totales', s.total_calls||0],
            ['Llamadas atendidas', s.answered_calls||0],
            ['Tiempo total de habla (s)', s.total_talk_seconds||0],
            ['AHT (Average Handle Time, s)', s.aht_seconds||0],
            ['Tiempo disponible (s)', s.available_seconds||0],
            ['Occupancy (%)', s.occupancy_pct||0],
            ['Extensiones usadas', (s.extensions_used||[]).join(', ')],
        ];
        const ws1 = window.XLSX.utils.aoa_to_sheet(summary);
        ws1['!cols'] = [{wch:34},{wch:30}];
        ['A1','B1'].forEach(c=>{if(ws1[c])ws1[c].s=styleH;});
        ['A6','B6'].forEach(c=>{if(ws1[c])ws1[c].s=styleH;});
        window.XLSX.utils.book_append_sheet(wb, ws1, 'Resumen');

        // Pausas por motivo
        const pr = s.pauses_by_reason || {};
        const prRows = [['Motivo','Cantidad','Tiempo total (s)']];
        Object.entries(pr).forEach(([k,v]) => prRows.push([v.label||k, v.count, v.seconds]));
        const ws2 = window.XLSX.utils.aoa_to_sheet(prRows);
        ws2['!cols'] = [{wch:20},{wch:12},{wch:18}];
        ['A1','B1','C1'].forEach(c=>{if(ws2[c])ws2[c].s=styleH;});
        window.XLSX.utils.book_append_sheet(wb, ws2, 'Pausas por motivo');

        // Sesiones
        const sessRows = [['Fecha','Login','Logout','Extensión','Duración (s)','Estado']];
        (data.sessions||[]).forEach(s => sessRows.push([s.shift_date, s.login_time, s.logout_time||'', s.agent_ext, s.duration_seconds, s.status]));
        const ws3 = window.XLSX.utils.aoa_to_sheet(sessRows);
        ws3['!cols'] = [{wch:12},{wch:20},{wch:20},{wch:10},{wch:12},{wch:10}];
        ['A1','B1','C1','D1','E1','F1'].forEach(c=>{if(ws3[c])ws3[c].s=styleH;});
        window.XLSX.utils.book_append_sheet(wb, ws3, 'Sesiones');

        // Pausas detalladas
        const pRows = [['Fecha inicio','Fecha fin','Motivo','Duración (s)','Extensión']];
        (data.pauses||[]).forEach(p => pRows.push([p.pause_start, p.pause_end||'', p.type_label||p.pause_type_code, p.dur, p.agent_ext]));
        const ws4 = window.XLSX.utils.aoa_to_sheet(pRows);
        ws4['!cols'] = [{wch:20},{wch:20},{wch:18},{wch:14},{wch:10}];
        ['A1','B1','C1','D1','E1'].forEach(c=>{if(ws4[c])ws4[c].s=styleH;});
        window.XLSX.utils.book_append_sheet(wb, ws4, 'Pausas detalle');

        // Llamadas
        const cRows = [['Fecha','Origen','Destino','Duración total (s)','Billsec (s)','Estado','Grabación']];
        (data.calls||[]).forEach(c => cRows.push([c.calldate, c.src, c.dst, c.duration, c.billsec, c.disposition, c.recordingfile||'']));
        const ws5 = window.XLSX.utils.aoa_to_sheet(cRows);
        ws5['!cols'] = [{wch:20},{wch:14},{wch:14},{wch:16},{wch:12},{wch:14},{wch:50}];
        ['A1','B1','C1','D1','E1','F1','G1'].forEach(c=>{if(ws5[c])ws5[c].s=styleH;});
        window.XLSX.utils.book_append_sheet(wb, ws5, 'Llamadas');

        const fname = `Reporte_Agente_${ag.number}_${data.period.from}_${data.period.to}.xlsx`;
        window.XLSX.writeFile(wb, fname);
    };

    const exportPDF = () => {
        if (!data || !window.jspdf) return;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
        const W=210, H=297;
        const ag = data.agent||{}; const s = data.summary||{};
        // Banner
        doc.setFillColor(139,92,246); doc.rect(0,0,W,32,'F');
        doc.setTextColor(255,255,255); doc.setFontSize(20); doc.setFont(undefined,'bold');
        doc.text('Reporte de Agente', 14, 14);
        doc.setFontSize(11); doc.setFont(undefined,'normal');
        doc.text(`#${ag.number} ${ag.name||''}`, 14, 22);
        doc.setFontSize(8);
        doc.text(`Período: ${data.period.from}  →  ${data.period.to}`, 14, 28);
        doc.setTextColor(255,255,255,0.85);
        doc.text(`Generado: ${new Date().toLocaleString('es-UY')}`, W-14, 28, {align:'right'});

        // KPI cards
        const cards = [
            { l:'Logueado', v:tfFmtSecs(s.total_logged_seconds), c:[59,130,246] },
            { l:'Pausa total', v:tfFmtSecs(s.total_paused_seconds), c:[245,158,11] },
            { l:'Llamadas', v:String(s.total_calls||0), c:[34,197,94] },
            { l:'AHT', v:tfFmtSecs(s.aht_seconds), c:[236,72,153] },
            { l:'Occupancy', v:(s.occupancy_pct||0)+'%', c:[139,92,246] },
        ];
        const cw = (W-28-(cards.length-1)*5)/cards.length;
        let cx=14, cy=40;
        cards.forEach(k => {
            doc.setFillColor(k.c[0],k.c[1],k.c[2]);
            doc.roundedRect(cx,cy,cw,22,3,3,'F');
            doc.setTextColor(255,255,255); doc.setFontSize(8); doc.setFont(undefined,'bold');
            doc.text(k.l.toUpperCase(), cx+3, cy+5);
            doc.setFontSize(11); doc.text(k.v, cx+3, cy+15);
            cx += cw+5;
        });
        let nextY = cy + 32;

        // Pausas por motivo
        const pr = s.pauses_by_reason||{};
        const prBody = Object.entries(pr).map(([k,v]) => [v.label||k, v.count, tfFmtSecs(v.seconds)]);
        if (prBody.length) {
            doc.setFontSize(11); doc.setTextColor(40,40,55); doc.setFont(undefined,'bold');
            doc.text('Pausas por motivo', 14, nextY);
            doc.autoTable({ startY: nextY+3, head:[['Motivo','Cantidad','Duración']], body: prBody,
                theme:'grid', headStyles:{fillColor:[139,92,246],textColor:255,fontStyle:'bold',fontSize:9},
                bodyStyles:{fontSize:9}, margin:{left:14,right:14}});
            nextY = doc.lastAutoTable.finalY + 6;
        }

        // Llamadas — top 30
        const calls = (data.calls||[]).slice(0,30);
        if (calls.length) {
            doc.setFontSize(11); doc.setFont(undefined,'bold');
            doc.text(`Llamadas (top 30 de ${data.calls.length})`, 14, nextY);
            doc.autoTable({ startY: nextY+3,
                head:[['Fecha','Origen','Destino','Dur','Estado']],
                body: calls.map(c => [String(c.calldate).slice(0,16), c.src||'', c.dst||'', tfFmtSecs(c.billsec), c.disposition||'']),
                theme:'striped', headStyles:{fillColor:[59,130,246],textColor:255,fontStyle:'bold',fontSize:9},
                bodyStyles:{fontSize:8}, margin:{left:14,right:14}});
            nextY = doc.lastAutoTable.finalY + 6;
        }

        // Footer
        const pages = doc.internal.getNumberOfPages();
        for(let i=1;i<=pages;i++){doc.setPage(i);doc.setFontSize(7);doc.setTextColor(140);
            doc.text(`Página ${i} de ${pages}  ·  TeleFlow · Horizon Seguridad`, W/2, H-8, {align:'center'});}
        doc.save(`Reporte_Agente_${ag.number}_${data.period.from}_${data.period.to}.pdf`);
    };

    if (!agentNumber) return null;
    if (loading && !data) return <div style={{padding:40,textAlign:'center',color:'var(--muted)'}}><span className="material-icons-round" style={{fontSize:36,animation:'spin-slow 2s linear infinite'}}>refresh</span><div style={{marginTop:8}}>Cargando reporte...</div></div>;
    if (!data) return <div style={{padding:40,textAlign:'center',color:'var(--muted)'}}>Sin datos</div>;

    const s = data.summary || {};
    const ag = data.agent || {};
    const pr = s.pauses_by_reason || {};

    const KPI = ({label, value, color, icon}) => (
        <div className="glass" style={{padding:'14px 16px',borderRadius:14,border:`1px solid ${color}33`,background:`linear-gradient(135deg, ${color}15, transparent)`,flex:1,minWidth:140}}>
            <div style={{display:'flex',alignItems:'center',gap:8,marginBottom:6}}>
                <span className="material-icons-round" style={{fontSize:16,color}}>{icon}</span>
                <span style={{fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.08em'}}>{label}</span>
            </div>
            <div style={{fontSize:20,fontWeight:900,color}}>{value}</div>
        </div>
    );

    return (
        <div style={{display:'flex',flexDirection:'column',gap:14}}>
            {/* Header con filtros */}
            <div style={{display:'flex',gap:10,alignItems:'center',flexWrap:'wrap',padding:'10px 14px',background:'var(--surface)',borderRadius:12,border:'1px solid var(--border)'}}>
                <div style={{display:'flex',alignItems:'center',gap:10,flex:1,minWidth:200}}>
                    <div style={{width:42,height:42,borderRadius:11,background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontWeight:900,fontSize:14}}>{(ag.name||'?').split(/\s+/).map(x=>x[0]||'').join('').substring(0,2).toUpperCase()}</div>
                    <div>
                        <div style={{fontSize:14,fontWeight:900}}>{ag.name}</div>
                        <div style={{fontSize:10,color:'var(--muted)',fontFamily:'monospace'}}>Agente #{ag.number} · {ag.type}</div>
                    </div>
                </div>
                <input type="date" value={from} onChange={e=>setFrom(e.target.value)} className="input-tf" style={{padding:'6px 10px',borderRadius:8,fontSize:12}}/>
                <span style={{color:'var(--muted)',fontSize:11,fontWeight:700}}>HASTA</span>
                <input type="date" value={to} onChange={e=>setTo(e.target.value)} className="input-tf" style={{padding:'6px 10px',borderRadius:8,fontSize:12}}/>
                <button onClick={load} className="btn-primary" style={{padding:'7px 14px',borderRadius:9,fontSize:12,display:'flex',alignItems:'center',gap:5}}>
                    <span className="material-icons-round" style={{fontSize:15}}>{loading?'hourglass_top':'sync'}</span>Actualizar
                </button>
                <button onClick={exportXLSX} style={{padding:'7px 12px',borderRadius:9,fontSize:11,background:'rgba(34,197,94,0.12)',border:'1px solid rgba(34,197,94,0.35)',color:'#22c55e',fontWeight:800,cursor:'pointer',display:'flex',alignItems:'center',gap:5}}>
                    <span className="material-icons-round" style={{fontSize:14}}>table_chart</span>Excel
                </button>
                <button onClick={exportPDF} style={{padding:'7px 12px',borderRadius:9,fontSize:11,background:'rgba(239,68,68,0.12)',border:'1px solid rgba(239,68,68,0.35)',color:'#ef4444',fontWeight:800,cursor:'pointer',display:'flex',alignItems:'center',gap:5}}>
                    <span className="material-icons-round" style={{fontSize:14}}>picture_as_pdf</span>PDF
                </button>
            </div>

            {/* KPI cards */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(140px,1fr))',gap:10}}>
                <KPI label="Logueado" value={tfFmtSecs(s.total_logged_seconds)} color="#3b82f6" icon="schedule"/>
                <KPI label="En pausa" value={tfFmtSecs(s.total_paused_seconds)} color="#f59e0b" icon="pause_circle"/>
                <KPI label="Disponible" value={tfFmtSecs(s.available_seconds)} color="#10b981" icon="check_circle"/>
                <KPI label="Llamadas" value={s.total_calls||0} color="#22c55e" icon="phone_in_talk"/>
                <KPI label="Atendidas" value={s.answered_calls||0} color="#22c55e" icon="call_received"/>
                <KPI label="AHT" value={tfFmtSecs(s.aht_seconds)} color="#ec4899" icon="timer"/>
                <KPI label="Occupancy" value={(s.occupancy_pct||0)+'%'} color="#8b5cf6" icon="trending_up"/>
            </div>

            {/* Pausas por motivo */}
            {Object.keys(pr).length > 0 && (
                <div className="glass" style={{padding:14,borderRadius:12}}>
                    <div style={{fontSize:12,fontWeight:800,color:'var(--text)',marginBottom:10,textTransform:'uppercase',letterSpacing:'.08em',display:'flex',alignItems:'center',gap:6}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#f59e0b'}}>pause_circle</span>Pausas por motivo
                    </div>
                    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(180px,1fr))',gap:8}}>
                        {Object.entries(pr).map(([code,v]) => (
                            <div key={code} style={{padding:'10px 12px',background:'var(--surface2)',borderRadius:10,border:'1px solid var(--border)'}}>
                                <div style={{fontSize:10,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>{v.label||code}</div>
                                <div style={{display:'flex',justifyContent:'space-between',alignItems:'baseline',marginTop:4}}>
                                    <div style={{fontSize:18,fontWeight:900,color:'#f59e0b'}}>{v.count}</div>
                                    <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace'}}>{tfFmtSecs(v.seconds)}</div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Sesiones */}
            <div className="glass" style={{padding:14,borderRadius:12}}>
                <div style={{fontSize:12,fontWeight:800,color:'var(--text)',marginBottom:10,textTransform:'uppercase',letterSpacing:'.08em',display:'flex',alignItems:'center',gap:6}}>
                    <span className="material-icons-round" style={{fontSize:16,color:'#3b82f6'}}>schedule</span>Sesiones ({(data.sessions||[]).length})
                </div>
                {(data.sessions||[]).length === 0 ? <div style={{padding:20,textAlign:'center',color:'var(--muted)',fontSize:11}}>Sin sesiones en el período</div> : (
                    <div style={{maxHeight:200,overflowY:'auto'}}>
                        <table style={{width:'100%',fontSize:11}}>
                            <thead><tr style={{background:'var(--surface2)'}}><th style={{textAlign:'left',padding:'6px 8px'}}>Fecha</th><th style={{textAlign:'left',padding:'6px 8px'}}>Login</th><th style={{textAlign:'left',padding:'6px 8px'}}>Logout</th><th style={{textAlign:'left',padding:'6px 8px'}}>Ext</th><th style={{textAlign:'right',padding:'6px 8px'}}>Duración</th></tr></thead>
                            <tbody>
                                {(data.sessions||[]).map((s,i)=>(<tr key={i} style={{borderBottom:'1px solid var(--border)'}}>
                                    <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{s.shift_date}</td>
                                    <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{(s.login_time||'').slice(11,16)}</td>
                                    <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{(s.logout_time||'—').slice(11,16)}</td>
                                    <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{s.agent_ext}</td>
                                    <td style={{padding:'6px 8px',textAlign:'right',fontFamily:'monospace',color:'#22c55e',fontWeight:700}}>{tfFmtSecs(s.duration_seconds)}</td>
                                </tr>))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Llamadas */}
            <div className="glass" style={{padding:14,borderRadius:12}}>
                <div style={{fontSize:12,fontWeight:800,color:'var(--text)',marginBottom:10,textTransform:'uppercase',letterSpacing:'.08em',display:'flex',alignItems:'center',gap:6}}>
                    <span className="material-icons-round" style={{fontSize:16,color:'#22c55e'}}>phone_in_talk</span>Llamadas ({(data.calls||[]).length})
                </div>
                {(data.calls||[]).length === 0 ? <div style={{padding:20,textAlign:'center',color:'var(--muted)',fontSize:11}}>Sin llamadas en el período</div> : (
                    <div style={{maxHeight:280,overflowY:'auto'}}>
                        <table style={{width:'100%',fontSize:11}}>
                            <thead><tr style={{background:'var(--surface2)'}}><th style={{textAlign:'left',padding:'6px 8px'}}>Fecha</th><th style={{textAlign:'left',padding:'6px 8px'}}>Origen</th><th style={{textAlign:'left',padding:'6px 8px'}}>Destino</th><th style={{textAlign:'right',padding:'6px 8px'}}>Duración</th><th style={{textAlign:'left',padding:'6px 8px'}}>Estado</th></tr></thead>
                            <tbody>
                                {(data.calls||[]).slice(0,100).map((c,i)=>{
                                    const isAns = c.disposition==='ANSWERED';
                                    return (<tr key={i} style={{borderBottom:'1px solid var(--border)'}}>
                                        <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{(c.calldate||'').slice(0,16)}</td>
                                        <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{c.src}</td>
                                        <td style={{padding:'6px 8px',fontFamily:'monospace'}}>{c.dst}</td>
                                        <td style={{padding:'6px 8px',textAlign:'right',fontFamily:'monospace'}}>{tfFmtSecs(c.billsec)}</td>
                                        <td style={{padding:'6px 8px'}}><span style={{padding:'1px 7px',borderRadius:5,fontSize:9,fontWeight:800,background:isAns?'rgba(34,197,94,0.15)':'rgba(239,68,68,0.15)',color:isAns?'#22c55e':'#ef4444'}}>{c.disposition}</span></td>
                                    </tr>);
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}

function ViewReportes({ toast, queue, onClearQueue, agentReport, onClearAgent }) {
    const d = new Date(); d.setDate(d.getDate() - 7);
    const [from, setFrom] = useState(() => d.toISOString().split('T')[0]);
    const [to, setTo] = useState(() => new Date().toISOString().split('T')[0]);
    const [tab, setTab] = useState('summary');
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [callFilters, setCallFilters] = useState({ disposition: '', src: '', dst: '', min_dur: 0 });
    const [agentDetail, setAgentDetail] = useState(null);

    const presets = [
        { label: 'Hoy', from: () => new Date().toISOString().split('T')[0], to: () => new Date().toISOString().split('T')[0] },
        { label: 'Ayer', from: () => { const x=new Date(); x.setDate(x.getDate()-1); return x.toISOString().split('T')[0]; }, to: () => { const x=new Date(); x.setDate(x.getDate()-1); return x.toISOString().split('T')[0]; } },
        { label: '7 días', from: () => { const x=new Date(); x.setDate(x.getDate()-7); return x.toISOString().split('T')[0]; }, to: () => new Date().toISOString().split('T')[0] },
        { label: '30 días', from: () => { const x=new Date(); x.setDate(x.getDate()-30); return x.toISOString().split('T')[0]; }, to: () => new Date().toISOString().split('T')[0] },
        { label: 'Mes actual', from: () => { const x=new Date(); x.setDate(1); return x.toISOString().split('T')[0]; }, to: () => new Date().toISOString().split('T')[0] },
    ];

    const buildParams = () => {
        const p = new URLSearchParams({ from, to });
        if (tab === 'calls') {
            if (callFilters.disposition) p.set('disposition', callFilters.disposition);
            if (callFilters.src) p.set('src', callFilters.src);
            if (callFilters.dst) p.set('dst', callFilters.dst);
            if (callFilters.min_dur > 0) p.set('min_dur', callFilters.min_dur);
            p.set('limit', '500');
        }
        return p.toString();
    };

    // Effect-scoped fetch with proper cancellation guard (fixes pre-existing bug donde el cancel nunca corría)
    useEffect(() => {
        let cancelled = false;
        setLoading(true); setData(null);
        const params = buildParams();
        fetch(`api/reports.php?action=${tab}&${params}`, { credentials: 'include' })
            .then(r => r.json())
            .then(j => {
                if (cancelled) return;
                if (j.status === 'ok') setData(j);
                else toast?.(j.message || 'Error cargando reporte', 'error');
            })
            .catch(() => { if (!cancelled) toast?.('Error de red', 'error'); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [tab, from, to, callFilters.disposition, callFilters.src, callFilters.dst, callFilters.min_dur]);

    const exportUrl = (format, type = tab, extra = {}) => {
        const p = new URLSearchParams({ type, format, from, to, ...extra });
        if (type === 'calls') {
            if (callFilters.disposition) p.set('disposition', callFilters.disposition);
            if (callFilters.src) p.set('src', callFilters.src);
            if (callFilters.dst) p.set('dst', callFilters.dst);
        }
        return `api/reports_export.php?${p.toString()}`;
    };

    return (
        <div className="p-4 md:p-6 space-y-4">
            {/* Page heading */}
            <div className="flex items-start justify-between gap-4 flex-wrap">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-primary/15 text-primary flex items-center justify-center">
                        <span className="material-icons-round" style={{fontSize:22}}>analytics</span>
                    </div>
                    <div>
                        <h2 className="text-xl font-bold tracking-tight" style={{color:'var(--foreground)'}}>Reportes</h2>
                        <p className="text-xs" style={{color:'var(--muted-foreground)'}}>Análisis detallado del callcenter</p>
                    </div>
                </div>
                {/* Export buttons */}
                <div className="flex items-center gap-2">
                    <Button asLink href={exportUrl('pdf')} target="_blank" variant="destructive" size="sm">
                        <span className="material-icons-round" style={{fontSize:14}}>picture_as_pdf</span>PDF
                    </Button>
                    <Button asLink href={exportUrl('xlsx')} target="_blank" variant="success" size="sm">
                        <span className="material-icons-round" style={{fontSize:14}}>table_chart</span>Excel
                    </Button>
                </div>
            </div>

            {/* Tabs + Período en la misma fila (período como pill estilo TabsList) */}
            <Tabs value={tab} onChange={setTab}>
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    <TabsList className="h-10 p-1" style={{background:'var(--secondary)'}}>
                        <TabsTrigger value="summary" className="h-8 px-4 gap-1.5">
                            <span className="material-icons-round" style={{fontSize:15}}>dashboard</span>Resumen
                        </TabsTrigger>
                        <TabsTrigger value="by_agent" className="h-8 px-4 gap-1.5">
                            <span className="material-icons-round" style={{fontSize:15}}>support_agent</span>Por agente
                        </TabsTrigger>
                        <TabsTrigger value="by_queue" className="h-8 px-4 gap-1.5">
                            <span className="material-icons-round" style={{fontSize:15}}>queue</span>Por cola
                        </TabsTrigger>
                        <TabsTrigger value="calls" className="h-8 px-4 gap-1.5">
                            <span className="material-icons-round" style={{fontSize:15}}>phone</span>Llamadas
                        </TabsTrigger>
                        <TabsTrigger value="pauses" className="h-8 px-4 gap-1.5">
                            <span className="material-icons-round" style={{fontSize:15}}>pause_circle</span>Pausas
                        </TabsTrigger>
                    </TabsList>

                    {/* Período pill — mismo tamaño que TabsList */}
                    <div className="inline-flex items-center h-10 p-1 rounded-lg gap-1 flex-wrap" style={{background:'var(--secondary)'}}>
                        {presets.map(p => {
                            const isActive = p.from() === from && p.to() === to;
                            return (
                                <button
                                    key={p.label}
                                    type="button"
                                    onClick={() => { setFrom(p.from()); setTo(p.to()); }}
                                    className={cn(
                                        "inline-flex items-center justify-center whitespace-nowrap rounded-md h-8 px-3 text-xs font-medium transition-all",
                                        isActive ? "bg-background text-foreground shadow" : "text-muted-foreground hover:text-foreground"
                                    )}
                                >{p.label}</button>
                            );
                        })}
                        <span className="mx-1 h-5 w-px" style={{background:'var(--border)'}}/>
                        <input type="date" value={from} onChange={e => setFrom(e.target.value)}
                            className="h-8 w-[124px] px-2 rounded-md text-xs font-medium bg-background text-foreground border-0 focus:outline-none focus:ring-2 focus:ring-ring"/>
                        <span className="text-xs mx-0.5" style={{color:'var(--muted-foreground)'}}>→</span>
                        <input type="date" value={to} onChange={e => setTo(e.target.value)}
                            className="h-8 w-[124px] px-2 rounded-md text-xs font-medium bg-background text-foreground border-0 focus:outline-none focus:ring-2 focus:ring-ring"/>
                    </div>
                </div>

                {loading && (
                    <div className="mt-4 rounded-lg border bg-card text-card-foreground p-16 flex flex-col items-center justify-center" style={{borderColor:'var(--border)'}}>
                        <span className="material-icons-round animate-spin text-primary" style={{fontSize:36}}>autorenew</span>
                        <div className="mt-3 text-sm font-medium" style={{color:'var(--muted-foreground)'}}>Cargando reporte…</div>
                    </div>
                )}

                {!loading && data && tab === 'summary' && <TabsContent value="summary"><ReportTabSummary data={data}/></TabsContent>}
                {!loading && data && tab === 'by_agent' && <TabsContent value="by_agent"><ReportTabByAgent data={data} onPick={setAgentDetail}/></TabsContent>}
                {!loading && data && tab === 'by_queue' && <TabsContent value="by_queue"><ReportTabByQueue data={data}/></TabsContent>}
                {!loading && data && tab === 'calls' && <TabsContent value="calls"><ReportTabCalls data={data} filters={callFilters} setFilters={setCallFilters}/></TabsContent>}
                {!loading && data && tab === 'pauses' && <TabsContent value="pauses"><ReportTabPauses data={data} from={from} to={to}/></TabsContent>}
            </Tabs>

            {agentDetail && <AgentDetailDrawer agent={agentDetail} from={from} to={to} onClose={() => setAgentDetail(null)} toast={toast}/>}
        </div>
    );
}

// ─── Premium KPI card (shadcn-flavored) ────────────────────────────────────
function KPICard({ label, value, sub, icon, color = 'primary', compact = false }) {
    // color puede ser nombre tailwind (primary, destructive, etc.) o hex
    const isHex = typeof color === 'string' && color.startsWith('#');
    const iconBg = isHex
        ? { background: `linear-gradient(135deg, ${color}, ${color}cc)`, boxShadow: `0 4px 12px ${color}44` }
        : {};
    const iconClass = isHex ? '' : `bg-${color}/90 text-${color}-foreground shadow-${color}/30`;
    const valueColor = isHex ? color : undefined;
    return (
        <Card className={cn("relative overflow-hidden", compact ? "min-h-[90px]" : "min-h-[110px]")}>
            <div className="absolute -top-5 -right-5 w-20 h-20 rounded-full opacity-30 pointer-events-none"
                 style={{background: isHex ? `radial-gradient(circle, ${color}33, transparent 70%)` : undefined}}/>
            <div className={cn("relative p-4", compact && "p-3")}>
                <div className="flex items-center gap-2 mb-2">
                    <div className={cn("flex items-center justify-center rounded-lg flex-shrink-0", compact ? "w-7 h-7" : "w-9 h-9", iconClass)} style={iconBg}>
                        <span className="material-icons-round text-white" style={{fontSize: compact ? 14 : 18}}>{icon}</span>
                    </div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{label}</span>
                </div>
                <div className={cn("font-black leading-none tabular-nums truncate", compact ? "text-xl" : "text-2xl")}
                     style={{color: valueColor}}>{value}</div>
                {sub && <div className="text-[10px] text-muted-foreground mt-1 font-semibold">{sub}</div>}
            </div>
        </Card>
    );
}

function fmtDurationCompact(secs) {
    secs = parseInt(secs || 0);
    if (secs <= 0) return '0s';
    if (secs < 60) return `${secs}s`;
    if (secs < 3600) return `${Math.floor(secs/60)}m ${secs%60}s`;
    if (secs < 86400) {
        const h = Math.floor(secs/3600);
        const m = Math.floor((secs%3600)/60);
        return m ? `${h}h ${String(m).padStart(2,'0')}m` : `${h}h`;
    }
    const d = Math.floor(secs/86400);
    const h = Math.floor((secs%86400)/3600);
    return h ? `${d}d ${h}h` : `${d}d`;
}

function fmtDateTime(ts) {
    if (!ts) return '—';
    const d = new Date(ts.replace(' ', 'T'));
    if (isNaN(d.getTime())) return ts;
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

// ─── ReportTab* (shadcn-styled) ────────────────────────────────────────────
function ReportTabSummary({ data }) {
    const k = data.kpis || {}; const s = data.sessions || {}; const p = data.pauses || {};
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <KPICard label="Total llamadas" value={Number(k.total||0).toLocaleString()} sub={`${data.period?.from?.substring(0,10)} → ${data.period?.to?.substring(0,10)}`} icon="phone" color="#8b5cf6"/>
            <KPICard label="Contestadas" value={`${Number(k.answered||0).toLocaleString()} (${k.answer_rate||0}%)`} sub={`${k.no_answer||0} sin resp · ${k.busy||0} ocup · ${k.failed||0} fall`} icon="check_circle" color="#22c55e"/>
            <KPICard label="Tasa abandono" value={`${k.abandon_rate||0}%`} sub="Sobre ofrecidas" icon="trending_down" color="#ef4444"/>
            <KPICard label="AHT promedio" value={`${k.avg_billsec||0}s`} sub={`Espera prom: ${k.avg_wait||0}s`} icon="schedule" color="#3b82f6"/>
            <KPICard label="Talk time total" value={fmtDurationCompact(k.total_talk_seconds||0)} sub={`Máx call: ${k.max_billsec||0}s`} icon="forum" color="#ec4899"/>
            <KPICard label="Sesiones" value={s.sessions||0} sub={`${s.unique_agents||0} agentes · Login: ${fmtDurationCompact(s.total_login_sec||0)}`} icon="badge" color="#06b6d4"/>
            <KPICard label="Pausas" value={p.total_pauses||0} sub={`Total: ${fmtDurationCompact(p.total_pause_sec||0)}`} icon="pause_circle" color="#f59e0b"/>
        </div>
    );
}

function ReportTabByAgent({ data, onPick }) {
    const agents = data.agents || [];
    return (
        <Card>
            <CardHeader className="p-4 pb-2 flex-row items-center justify-between space-y-0">
                <div>
                    <CardTitle className="text-sm">Por agente</CardTitle>
                    <CardDescription className="text-xs">{agents.length} agentes con actividad · click para detalle</CardDescription>
                </div>
            </CardHeader>
            <CardContent className="p-0 pt-0">
                <div className="overflow-auto max-h-[70vh]">
                    <ShTable>
                        <ShTHead>
                            <ShTR>
                                <ShTH>Ext</ShTH>
                                <ShTH>Agente</ShTH>
                                <ShTH>Nombre</ShTH>
                                <ShTH align="right">Sesiones</ShTH>
                                <ShTH align="right">Login</ShTH>
                                <ShTH align="right">T. Pausa</ShTH>
                                <ShTH align="right">Productivo%</ShTH>
                                <ShTH align="right">Llam.</ShTH>
                                <ShTH align="right">Contest.</ShTH>
                                <ShTH align="right">AHT</ShTH>
                                <ShTH align="right">Talk</ShTH>
                            </ShTR>
                        </ShTHead>
                        <ShTBody>
                            {agents.map((a) => (
                                <ShTR key={a.ext} onClick={() => onPick?.(a)}>
                                    <ShTD mono>{a.ext}</ShTD>
                                    <ShTD><span className="text-primary font-semibold">{a.agent_number||'—'}</span></ShTD>
                                    <ShTD>{a.name||'—'}</ShTD>
                                    <ShTD align="right">{a.session_count||0}</ShTD>
                                    <ShTD align="right" mono><span className="text-blue-500">{fmtDurationCompact(a.login_sec)}</span></ShTD>
                                    <ShTD align="right" mono><span className="text-amber-500">{fmtDurationCompact(a.pause_sec)}</span></ShTD>
                                    <ShTD align="right">
                                        {a.productive_pct !== null
                                            ? <Badge variant={a.productive_pct > 80 ? 'success' : (a.productive_pct > 50 ? 'warning' : 'destructive')}>{a.productive_pct}%</Badge>
                                            : '—'}
                                    </ShTD>
                                    <ShTD align="right">{a.calls||0}</ShTD>
                                    <ShTD align="right"><span className="text-green-500">{a.answered||0}</span></ShTD>
                                    <ShTD align="right">{a.avg_aht !== null ? `${a.avg_aht}s` : '—'}</ShTD>
                                    <ShTD align="right" mono>{fmtDurationCompact(a.total_talk)}</ShTD>
                                </ShTR>
                            ))}
                        </ShTBody>
                    </ShTable>
                    {agents.length === 0 && <EmptyState icon="support_agent" title="Sin datos" subtitle="No hay agentes con actividad en este rango"/>}
                </div>
            </CardContent>
        </Card>
    );
}

function ReportTabByQueue({ data }) {
    const queues = data.queues || []; const sl = data.sl_threshold || 20;
    return (
        <Card>
            <CardHeader className="p-4 pb-2 flex-row items-center justify-between space-y-0">
                <div>
                    <CardTitle className="text-sm">Por cola</CardTitle>
                    <CardDescription className="text-xs">{queues.length} colas con tráfico · SL @ ≤{sl}s</CardDescription>
                </div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-auto max-h-[70vh]">
                    <ShTable>
                        <ShTHead>
                            <ShTR>
                                <ShTH>Cola</ShTH>
                                <ShTH>Descripción</ShTH>
                                <ShTH align="right">Ofrec.</ShTH>
                                <ShTH align="right">Contest.</ShTH>
                                <ShTH align="right">Aband.</ShTH>
                                <ShTH align="right">Aband.%</ShTH>
                                <ShTH align="right">SL%</ShTH>
                                <ShTH align="right">Esp. prom.</ShTH>
                                <ShTH align="right">Máx. esp.</ShTH>
                                <ShTH align="right">AHT</ShTH>
                            </ShTR>
                        </ShTHead>
                        <ShTBody>
                            {queues.map((q) => (
                                <ShTR key={q.queue}>
                                    <ShTD mono>{q.queue}</ShTD>
                                    <ShTD className="text-primary/80">{q.descr||'—'}</ShTD>
                                    <ShTD align="right">{q.offered||0}</ShTD>
                                    <ShTD align="right"><span className="text-green-500">{q.answered||0}</span></ShTD>
                                    <ShTD align="right"><span className="text-destructive">{q.abandoned||0}</span></ShTD>
                                    <ShTD align="right">
                                        <Badge variant={q.abandon_rate > 20 ? 'destructive' : (q.abandon_rate > 10 ? 'warning' : 'success')}>{q.abandon_rate}%</Badge>
                                    </ShTD>
                                    <ShTD align="right">
                                        {q.service_level !== null
                                            ? <Badge variant={q.service_level >= 80 ? 'success' : (q.service_level >= 60 ? 'warning' : 'destructive')}>{q.service_level}%</Badge>
                                            : '—'}
                                    </ShTD>
                                    <ShTD align="right">{q.avg_wait !== null ? `${q.avg_wait}s` : '—'}</ShTD>
                                    <ShTD align="right">{q.max_wait ? `${q.max_wait}s` : '—'}</ShTD>
                                    <ShTD align="right" mono>{q.avg_talk ? `${q.avg_talk}s` : '—'}</ShTD>
                                </ShTR>
                            ))}
                        </ShTBody>
                    </ShTable>
                    {queues.length === 0 && <EmptyState icon="queue" title="Sin datos" subtitle="No hay tráfico de colas en este rango"/>}
                </div>
            </CardContent>
        </Card>
    );
}

function ReportTabCalls({ data, filters, setFilters }) {
    const calls = data.calls || [];
    return (
        <Card>
            <div className="p-3 border-b flex items-center gap-2 flex-wrap" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-1.5 mr-1">
                    <span className="material-icons-round" style={{fontSize:14, color:'var(--muted-foreground)'}}>filter_list</span>
                    <Label className="text-xs font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Filtros</Label>
                </div>
                <Select value={filters.disposition} onChange={e => setFilters({...filters, disposition: e.target.value})} className="h-8 w-[150px] text-xs">
                    <option value="">Todos los estados</option>
                    <option value="ANSWERED">ANSWERED</option>
                    <option value="NO ANSWER">NO ANSWER</option>
                    <option value="BUSY">BUSY</option>
                    <option value="FAILED">FAILED</option>
                </Select>
                <Input placeholder="Origen" value={filters.src} onChange={e => setFilters({...filters, src: e.target.value})} className="h-8 w-[120px] text-xs"/>
                <Input placeholder="Destino" value={filters.dst} onChange={e => setFilters({...filters, dst: e.target.value})} className="h-8 w-[120px] text-xs"/>
                <Input placeholder="Min dur (s)" type="number" value={filters.min_dur || ''} onChange={e => setFilters({...filters, min_dur: parseInt(e.target.value) || 0})} className="h-8 w-[120px] text-xs"/>
                {(filters.disposition || filters.src || filters.dst || filters.min_dur) && (
                    <Button variant="ghost" size="sm" onClick={()=>setFilters({disposition:'',src:'',dst:'',min_dur:0})} className="h-8 px-2 text-xs">
                        <span className="material-icons-round" style={{fontSize:14}}>close</span>
                        Limpiar
                    </Button>
                )}
                <div className="flex-1"/>
                <Badge variant="secondary" className="text-xs">{calls.length.toLocaleString()} resultados</Badge>
            </div>
            <CardContent className="p-0">
                <div className="overflow-auto max-h-[70vh]">
                    <ShTable>
                        <ShTHead>
                            <ShTR>
                                <ShTH>Fecha/Hora</ShTH>
                                <ShTH>Origen</ShTH>
                                <ShTH>Destino</ShTH>
                                <ShTH>CallerID</ShTH>
                                <ShTH>Estado</ShTH>
                                <ShTH align="right">Dur.</ShTH>
                                <ShTH align="right">Hablado</ShTH>
                                <ShTH align="center">Grab.</ShTH>
                            </ShTR>
                        </ShTHead>
                        <ShTBody>
                            {calls.map((c, i) => {
                                const variant = c.disposition === 'ANSWERED' ? 'success' : (c.disposition === 'BUSY' || c.disposition === 'FAILED' ? 'destructive' : 'warning');
                                return (
                                    <ShTR key={c.uniqueid || i}>
                                        <ShTD mono>{c.calldate}</ShTD>
                                        <ShTD mono>{c.src}</ShTD>
                                        <ShTD mono>{c.dst}</ShTD>
                                        <ShTD className="max-w-[200px] truncate text-muted-foreground">{c.clid}</ShTD>
                                        <ShTD><Badge variant={variant}>{c.disposition}</Badge></ShTD>
                                        <ShTD align="right" mono>{c.duration}s</ShTD>
                                        <ShTD align="right" mono>{c.billsec}s</ShTD>
                                        <ShTD align="center">
                                            {c.recordingfile
                                                ? <a href={`api/recording.php?file=${encodeURIComponent(c.recordingfile)}`} target="_blank" rel="noopener noreferrer" className="text-primary inline-flex"><span className="material-icons-round text-base">play_circle</span></a>
                                                : <span className="text-muted-foreground">—</span>}
                                        </ShTD>
                                    </ShTR>
                                );
                            })}
                        </ShTBody>
                    </ShTable>
                    {calls.length === 0 && <EmptyState icon="phone_disabled" title="Sin llamadas" subtitle="Ajustá los filtros o el rango"/>}
                </div>
            </CardContent>
        </Card>
    );
}

function ReportTabPauses({ data, from, to }) {
    const pauses = data.pauses || [];
    const byMotive = {};
    pauses.forEach(p => {
        const k = p.pause_label || p.pause_type_code || 'sin_motivo';
        if (!byMotive[k]) byMotive[k] = { label: p.pause_label || p.pause_type_code || 'Sin motivo', color: p.pause_color || '#f59e0b', count: 0, total: 0 };
        byMotive[k].count++;
        byMotive[k].total += parseInt(p.duration_seconds || 0);
    });
    const motives = Object.values(byMotive).sort((a, b) => b.total - a.total);
    const maxTotal = Math.max(1, ...motives.map(m => m.total));

    return (
        <div className="space-y-4">
            <Card>
                <CardHeader className="p-4 pb-2">
                    <CardTitle className="text-sm flex items-center gap-2">
                        <span className="material-icons-round text-amber-500 text-base">pie_chart</span>
                        Distribución por motivo
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-4 pt-2 space-y-2">
                    {motives.map((m) => {
                        const pct = (m.total / maxTotal) * 100;
                        return (
                            <div key={m.label} className="grid grid-cols-[140px_1fr_80px_60px] gap-3 items-center">
                                <span className="text-xs font-semibold truncate">{m.label}</span>
                                <div className="h-3.5 bg-muted rounded-full overflow-hidden">
                                    <div className="h-full rounded-full transition-all duration-300" style={{width: `${pct}%`, background: `linear-gradient(90deg, ${m.color||'#f59e0b'}aa, ${m.color||'#f59e0b'})`}}/>
                                </div>
                                <span className="text-xs font-mono font-bold text-right" style={{color: m.color || '#f59e0b'}}>{fmtDurationCompact(m.total)}</span>
                                <span className="text-xs text-muted-foreground font-semibold text-right">{m.count}x</span>
                            </div>
                        );
                    })}
                    {motives.length === 0 && <EmptyState icon="pause_circle" title="Sin pausas" subtitle="No hay pausas en este rango"/>}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="p-4 pb-2 flex-row items-center justify-between space-y-0">
                    <CardTitle className="text-sm">Detalle de pausas</CardTitle>
                    <span className="text-xs text-muted-foreground">{pauses.length} registradas</span>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-auto max-h-[50vh]">
                        <ShTable>
                            <ShTHead>
                                <ShTR>
                                    <ShTH>Agente</ShTH>
                                    <ShTH>Ext</ShTH>
                                    <ShTH>Motivo</ShTH>
                                    <ShTH>Inicio</ShTH>
                                    <ShTH>Fin</ShTH>
                                    <ShTH align="right">Duración</ShTH>
                                </ShTR>
                            </ShTHead>
                            <ShTBody>
                                {pauses.map((p) => (
                                    <ShTR key={p.id}>
                                        <ShTD><span className="text-primary">{p.agent_number||'—'}</span></ShTD>
                                        <ShTD mono>{p.agent_ext}</ShTD>
                                        <ShTD>
                                            <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold" style={{background: (p.pause_color||'#f59e0b')+'22', color: p.pause_color||'#f59e0b'}}>
                                                {p.pause_label||p.pause_type_code}
                                            </span>
                                        </ShTD>
                                        <ShTD mono>{p.pause_start}</ShTD>
                                        <ShTD mono>{p.pause_end || <span className="text-green-500 font-semibold">(activa)</span>}</ShTD>
                                        <ShTD align="right" mono><span className="font-bold" style={{color: p.pause_color||'#f59e0b'}}>{fmtDurationCompact(p.duration_seconds)}</span></ShTD>
                                    </ShTR>
                                ))}
                            </ShTBody>
                        </ShTable>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

// ─── AgentDetailDrawer (shadcn Sheet) ──────────────────────────────────────
function AgentDetailDrawer({ agent, from, to, onClose, toast }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [section, setSection] = useState('overview');
    const agentId = useMemo(() => agent?.agent_number || agent?.ext || '', [agent]);
    // Stable ref for toast — evita re-fetch infinito cuando el padre re-renderea
    const toastRef = useRef(toast);
    useEffect(() => { toastRef.current = toast; }, [toast]);

    useEffect(() => {
        if (!agentId) return; // sin agente, no cargar nada
        let cancelled = false;
        setLoading(true); setData(null);
        (async () => {
            try {
                const r = await fetch(`api/reports.php?action=agent_detail&agent=${encodeURIComponent(agentId)}&from=${from}&to=${to}`, { credentials: 'include' });
                const j = await r.json();
                if (cancelled) return;
                if (j.status === 'ok') setData(j);
                else toastRef.current?.(j.message || 'Error', 'error');
            } catch (e) { if (!cancelled) toastRef.current?.('Error de red', 'error'); }
            finally { if (!cancelled) setLoading(false); }
        })();
        return () => { cancelled = true; };
    }, [agentId, from, to]);

    const exportUrl = useCallback((fmt) =>
        `api/reports_export.php?type=agent_detail&format=${fmt}&from=${from}&to=${to}&agent=${encodeURIComponent(agentId)}`,
        [agentId, from, to]);

    const initials = (agent?.name || '').split(/\s+/).map(x => x[0]).join('').substring(0, 2).toUpperCase() || '?';

    return (
        <Sheet open={!!agent} onOpenChange={(o) => !o && onClose?.()} size="3xl">
            <SheetHeader className="p-5 border-b border-border">
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-blue-500 flex items-center justify-center text-white font-black text-base shadow-lg flex-shrink-0">{initials}</div>
                    <div className="flex-1 min-w-0">
                        <div className="text-[10px] font-bold uppercase tracking-wider text-primary">Agente</div>
                        <SheetTitle className="truncate">{agent?.name || agentId}</SheetTitle>
                        <SheetDescription className="text-xs flex gap-2 items-center mt-0.5">
                            <span className="font-mono font-bold text-primary">#{agentId}</span>
                            {agent?.ext && <span>· ext {agent.ext}</span>}
                            <span>· {from} → {to}</span>
                        </SheetDescription>
                    </div>
                    <Button asLink href={exportUrl('pdf')} target="_blank" variant="destructive" size="sm">
                        <span className="material-icons-round text-sm">picture_as_pdf</span>PDF
                    </Button>
                    <Button asLink href={exportUrl('xlsx')} target="_blank" variant="success" size="sm">
                        <span className="material-icons-round text-sm">table_chart</span>Excel
                    </Button>
                    <Button variant="ghost" size="icon" onClick={onClose} title="Cerrar (Esc)">
                        <span className="material-icons-round text-base">close</span>
                    </Button>
                </div>
                <Tabs value={section} onChange={setSection} className="mt-3">
                    <TabsList>
                        <TabsTrigger value="overview"><span className="material-icons-round text-sm mr-1">dashboard</span>Resumen</TabsTrigger>
                        <TabsTrigger value="sessions"><span className="material-icons-round text-sm mr-1">login</span>Sesiones {data?.sessions ? `(${data.sessions.length})` : ''}</TabsTrigger>
                        <TabsTrigger value="pauses"><span className="material-icons-round text-sm mr-1">pause_circle</span>Pausas {data?.pauses ? `(${data.pauses.length})` : ''}</TabsTrigger>
                        <TabsTrigger value="calls"><span className="material-icons-round text-sm mr-1">phone</span>Llamadas {data?.calls ? `(${data.calls.length})` : ''}</TabsTrigger>
                    </TabsList>
                </Tabs>
            </SheetHeader>
            <SheetContent className="p-5 space-y-4">
                {loading && (
                    <div className="py-16 text-center text-muted-foreground">
                        <span className="material-icons-round text-4xl animate-spin text-primary">autorenew</span>
                        <div className="mt-2 text-sm">Cargando datos del agente…</div>
                    </div>
                )}
                {!loading && data && section === 'overview' && <AgentOverviewSection data={data}/>}
                {!loading && data && section === 'sessions' && <AgentSessionsSection sessions={data.sessions}/>}
                {!loading && data && section === 'pauses' && <AgentPausesSection pauses={data.pauses} breakdown={data.pause_breakdown}/>}
                {!loading && data && section === 'calls' && <AgentCallsSection calls={data.calls}/>}
            </SheetContent>
        </Sheet>
    );
}

function AgentOverviewSection({ data }) {
    const k = data.kpi || {};
    const breakdown = data.pause_breakdown || [];
    const maxPause = Math.max(1, ...breakdown.map(b => parseInt(b.total_sec || 0)));
    const prodColor = k.productive_pct > 80 ? '#22c55e' : (k.productive_pct > 50 ? '#f59e0b' : '#ef4444');
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                <KPICard compact label="Sesiones" value={k.sessions_count || 0} sub="En el período" icon="badge" color="#8b5cf6"/>
                <KPICard compact label="Login total" value={fmtDurationCompact(k.total_login_sec)} sub="Tiempo logueado" icon="login" color="#3b82f6"/>
                <KPICard compact label="Productivo" value={k.productive_pct !== null ? `${k.productive_pct}%` : '—'} sub={`${fmtDurationCompact(Math.max(0,(k.total_login_sec||0)-(k.total_pause_sec||0)))} activos`} icon="trending_up" color={prodColor}/>
                <KPICard compact label="Pausas" value={k.pauses_count || 0} sub={`Total: ${fmtDurationCompact(k.total_pause_sec)}`} icon="pause_circle" color="#f59e0b"/>
                <KPICard compact label="Llamadas" value={(k.total_calls || 0).toLocaleString()} sub={k.total_talk_sec ? `Talk: ${fmtDurationCompact(k.total_talk_sec)}` : 'Atendidas'} icon="phone" color="#ec4899"/>
                <KPICard compact label="Última actividad" value={data.sessions?.[0]?.logout_time ? 'Cerrada' : (data.sessions?.length ? 'Activa' : '—')} sub={data.sessions?.[0] ? fmtDateTime(data.sessions[0].login_time) : 'Sin actividad'} icon="schedule" color="#06b6d4"/>
            </div>

            {breakdown.length > 0 && (
                <Card>
                    <CardHeader className="p-4 pb-2">
                        <CardTitle className="text-sm flex items-center gap-2">
                            <span className="material-icons-round text-amber-500 text-base">pie_chart</span>
                            Distribución de pausas
                            <span className="ml-auto text-xs font-normal text-muted-foreground">Total: {fmtDurationCompact(k.total_pause_sec)}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-4 pt-2 space-y-2">
                        {breakdown.map((p) => {
                            const pct = (parseInt(p.total_sec) / maxPause) * 100;
                            const c = p.color || '#f59e0b';
                            return (
                                <div key={p.code} className="grid grid-cols-[140px_1fr_80px_60px] gap-3 items-center">
                                    <div className="flex items-center gap-1.5 min-w-0">
                                        <span className="w-2 h-2 rounded-full flex-shrink-0" style={{background: c}}/>
                                        <span className="text-xs font-semibold truncate">{p.label}</span>
                                    </div>
                                    <div className="h-3.5 bg-muted rounded-full overflow-hidden">
                                        <div className="h-full rounded-full transition-all duration-300" style={{width: `${pct}%`, background: `linear-gradient(90deg, ${c}aa, ${c})`}}/>
                                    </div>
                                    <span className="text-xs font-mono font-bold text-right" style={{color: c}}>{fmtDurationCompact(p.total_sec)}</span>
                                    <span className="text-xs text-muted-foreground font-semibold text-right">{p.count}x</span>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader className="p-4 pb-2">
                    <CardTitle className="text-sm flex items-center gap-2">
                        <span className="material-icons-round text-blue-500 text-base">insights</span>
                        Indicadores del período
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-4 pt-2">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                        <Row label="Tiempo total de login" value={fmtDurationCompact(k.total_login_sec)} color="#3b82f6"/>
                        <Row label="Tiempo en pausa" value={fmtDurationCompact(k.total_pause_sec)} color="#f59e0b"/>
                        <Row label="Tiempo activo (productivo)" value={fmtDurationCompact(Math.max(0,(k.total_login_sec||0)-(k.total_pause_sec||0)))} color="#22c55e"/>
                        <Row label="% Productividad" value={k.productive_pct !== null ? `${k.productive_pct}%` : '—'} color={prodColor}/>
                        <Row label="Llamadas atendidas" value={(k.total_calls || 0).toLocaleString()} color="#ec4899"/>
                        <Row label="Cantidad de pausas" value={k.pauses_count || 0} color="#f59e0b"/>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function Row({ label, value, color }) {
    return (
        <div className="flex justify-between items-center px-3 py-1.5 bg-muted/40 rounded-md">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-mono font-bold" style={{color: color || undefined}}>{value}</span>
        </div>
    );
}

function AgentSessionsSection({ sessions = [] }) {
    if (sessions.length === 0) return <EmptyState icon="login" title="Sin sesiones" subtitle="Este agente no se logueó en el período"/>;
    return (
        <Card>
            <CardContent className="p-0">
                <ShTable>
                    <ShTHead>
                        <ShTR>
                            <ShTH>Estado</ShTH>
                            <ShTH>Login</ShTH>
                            <ShTH>Logout</ShTH>
                            <ShTH align="right">Duración</ShTH>
                            <ShTH align="right">Ext</ShTH>
                            <ShTH align="right">Llamadas</ShTH>
                            <ShTH align="right">Talk</ShTH>
                        </ShTR>
                    </ShTHead>
                    <ShTBody>
                        {sessions.map((s, i) => {
                            const active = !s.logout_time;
                            return (
                                <ShTR key={s.session_id || i}>
                                    <ShTD><Badge variant={active ? 'success' : 'secondary'}>{active ? 'ACTIVA' : 'CERRADA'}</Badge></ShTD>
                                    <ShTD mono>{fmtDateTime(s.login_time)}</ShTD>
                                    <ShTD mono>{s.logout_time ? fmtDateTime(s.logout_time) : '—'}</ShTD>
                                    <ShTD align="right" mono><span className="text-blue-500 font-bold">{fmtDurationCompact(s.duration_sec)}</span></ShTD>
                                    <ShTD align="right" mono>{s.agent_ext}</ShTD>
                                    <ShTD align="right">{s.total_calls || 0}</ShTD>
                                    <ShTD align="right" mono>{s.total_talk_time ? fmtDurationCompact(s.total_talk_time) : '—'}</ShTD>
                                </ShTR>
                            );
                        })}
                    </ShTBody>
                </ShTable>
            </CardContent>
        </Card>
    );
}

function AgentPausesSection({ pauses = [], breakdown = [] }) {
    if (pauses.length === 0) return <EmptyState icon="pause_circle" title="Sin pausas" subtitle="Este agente no tomó pausas en el período"/>;
    return (
        <div className="space-y-4">
            {breakdown.length > 0 && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {breakdown.map(b => (
                        <Card key={b.code} className="p-3" style={{borderColor: (b.color||'#f59e0b')+'33', background: (b.color||'#f59e0b')+'10'}}>
                            <div className="text-[10px] font-bold uppercase mb-1" style={{color: b.color||'#f59e0b'}}>{b.label}</div>
                            <div className="text-xl font-black font-mono">{fmtDurationCompact(b.total_sec)}</div>
                            <div className="text-[10px] text-muted-foreground mt-1">{b.count} pausas</div>
                        </Card>
                    ))}
                </div>
            )}
            <Card>
                <CardContent className="p-0">
                    <ShTable>
                        <ShTHead>
                            <ShTR>
                                <ShTH>Motivo</ShTH>
                                <ShTH>Inicio</ShTH>
                                <ShTH>Fin</ShTH>
                                <ShTH align="right">Duración</ShTH>
                            </ShTR>
                        </ShTHead>
                        <ShTBody>
                            {pauses.map(p => (
                                <ShTR key={p.id}>
                                    <ShTD>
                                        <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold" style={{background: (p.pause_color||'#f59e0b')+'22', color: p.pause_color||'#f59e0b'}}>{p.pause_label || p.pause_type_code}</span>
                                    </ShTD>
                                    <ShTD mono>{fmtDateTime(p.pause_start)}</ShTD>
                                    <ShTD mono>{p.pause_end ? fmtDateTime(p.pause_end) : <span className="text-green-500 font-bold">(activa)</span>}</ShTD>
                                    <ShTD align="right" mono><span className="font-bold" style={{color: p.pause_color||'#f59e0b'}}>{fmtDurationCompact(p.duration_seconds)}</span></ShTD>
                                </ShTR>
                            ))}
                        </ShTBody>
                    </ShTable>
                </CardContent>
            </Card>
        </div>
    );
}

function AgentCallsSection({ calls = [] }) {
    if (calls.length === 0) return <EmptyState icon="phone_disabled" title="Sin llamadas" subtitle="No hay registros de CDR para este agente"/>;
    return (
        <Card>
            <CardContent className="p-0">
                <ShTable>
                    <ShTHead>
                        <ShTR>
                            <ShTH>Fecha/Hora</ShTH>
                            <ShTH>Origen</ShTH>
                            <ShTH>Destino</ShTH>
                            <ShTH>Estado</ShTH>
                            <ShTH align="right">Duración</ShTH>
                            <ShTH align="right">Hablado</ShTH>
                            <ShTH align="center">Grab.</ShTH>
                        </ShTR>
                    </ShTHead>
                    <ShTBody>
                        {calls.slice(0, 200).map((c, i) => {
                            const variant = c.disposition === 'ANSWERED' ? 'success' : (c.disposition === 'BUSY' || c.disposition === 'FAILED' ? 'destructive' : 'warning');
                            return (
                                <ShTR key={c.uniqueid || i}>
                                    <ShTD mono>{fmtDateTime(c.calldate)}</ShTD>
                                    <ShTD mono>{c.src}</ShTD>
                                    <ShTD mono>{c.dst}</ShTD>
                                    <ShTD><Badge variant={variant}>{c.disposition}</Badge></ShTD>
                                    <ShTD align="right" mono>{c.duration}s</ShTD>
                                    <ShTD align="right" mono>{c.billsec ? fmtDurationCompact(c.billsec) : '—'}</ShTD>
                                    <ShTD align="center">
                                        {c.recordingfile
                                            ? <a href={`api/recording.php?file=${encodeURIComponent(c.recordingfile)}`} target="_blank" rel="noopener noreferrer" className="text-primary inline-flex"><span className="material-icons-round text-base">play_circle</span></a>
                                            : <span className="text-muted-foreground">—</span>}
                                    </ShTD>
                                </ShTR>
                            );
                        })}
                    </ShTBody>
                </ShTable>
                {calls.length > 200 && (
                    <div className="px-4 py-2 bg-muted/40 border-t border-border text-center text-xs text-muted-foreground">
                        Mostrando 200 más recientes de {calls.length}. Usá el export para el listado completo.
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function EmptyState({ icon, title, subtitle }) {
    return (
        <div className="py-12 text-center">
            <span className="material-icons-round text-5xl text-muted-foreground/40">{icon}</span>
            <div className="mt-2 text-base font-bold text-foreground">{title}</div>
            <div className="mt-1 text-xs text-muted-foreground">{subtitle}</div>
        </div>
    );
}


// El Softphone ahora es una PWA independiente en /softphone/

// ─────────────────────────────────────────────
// VISTA: CONFIGURACIÓN — Debug SIP Profesional
// ─────────────────────────────────────────────
const SIP_PARSERS = [
    { re: /DTLS srtp - handle timeout/i, color:'#f43f5e', label:'?? MEDIA TIMEOUT', icon:'vibration' },
    { re: /Indicated Private Cause Code/i, color:'#94a3b8', label:'HUP CAUSE', icon:'call_missed_outgoing' },
    { re: /\bREGISTER\b/,   color:'#60a5fa', label:'REGISTER',  icon:'login' },
    { re: /\b200 OK\b/,     color:'#4ade80', label:'200 OK',    icon:'check_circle' },
    { re: /\b100 Trying\b/i,   color:'#94a3b8', label:'TRYING',   icon:'hourglass_empty' },
    { re: /\b180 Ringing\b/i,  color:'#f59e0b', label:'RINGING',  icon:'notifications_active' },
    { re: /\b183 Session Progress\b/i, color:'#3b82f6', label:'EARLY MEDIA', icon:'graphic_eq' },
    { re: /\b401 Unauthorized\b/i, color:'#fb923c', label:'401 AUTH', icon:'lock' },
    { re: /\b403 Forbidden\b/i,    color:'#f87171', label:'403 FORBID', icon:'block' },
    { re: /\b404 Not Found\b/i,    color:'#9ca3af', label:'404 NOTFOUND',icon:'search_off' },
    { re: /\b408\b/,        color:'#fb923c', label:'408 TIMEOUT', icon:'timer_off' },
    { re: /\b488 Not Acceptable\b/i, color:'#ef4444', label:'SDP ERR', icon:'broken_image' },
    { re: /\b487 Request Terminated\b/i, color:'#9ca3af', label:'TERM', icon:'cancel' },
    { re: /\b5\d\d\b/,      color:'#f87171', label:'5xx ERROR',  icon:'error' },
    { re: /\b603 Declined\b/i, color:'#ef4444', label:'DECLINED', icon:'do_not_disturb_on' },
    { re: /Received\s+SIP/i, color:'#a78bfa', label:'SIP RX',   icon:'arrow_downward' },
    { re: /Sending\s+SIP/i,  color:'#34d399', label:'SIP TX',   icon:'arrow_upward' },
    { re: /\bINVITE\b/,     color:'#f59e0b', label:'INVITE',   icon:'phone_forwarded' },
    { re: /\bBYE\b/,        color:'#f87171', label:'BYE',      icon:'call_end' },
    { re: /\bACK\b/,        color:'#6ee7b7', label:'ACK',      icon:'done' },
    { re: /\bCANCEL\b/,        color:'#9ca3af', label:'CANCEL',   icon:'close' },
    { re: /\bUPDATE\b/,        color:'#60a5fa', label:'UPDATE',   icon:'update' },
    { re: /\bOPTIONS\b/,    color:'#93c5fd', label:'OPTIONS',  icon:'settings' },
    { re: /\bNOTIFY\b/,     color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))', label:'NOTIFY',   icon:'notifications' },
    { re: /is ringing/i,     color:'#f59e0b', label:'SONANDO',    icon:'notifications_active' },
    { re: /answered/i,       color:'#22c55e', label:'CONTESTADA', icon:'call' },
    { re: /is now Unreachable/i, color:'#ef4444', label:'OFFLINE',    icon:'link_off' },
    { re: /is now Reachable/i,   color:'#22c55e', label:'ONLINE',     icon:'link' },
    { re: /is now Lagged/i,      color:'#f59e0b', label:'LAGGED',     icon:'timer' },
    { re: /m=audio/i,          color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))', label:'SDP AUDIO', icon:'audiotrack' },
    { re: /m=video/i,          color:'#f472b6', label:'SDP VIDEO', icon:'videocam' },
    { re: /ICE candidate/i,    color:'#2dd4bf', label:'ICE CAND',  icon:'lan' },
    { re: /ICE state changed/i, color:'#fb923c', label:'ICE STATE', icon:'wifi_tethering' },
    { re: /\bREINVITE\b/i,     color:'var(--primary)', label:'RE-INVITE', icon:'history' },
    { re: /\bWARNING\b/i,   color:'#eab308', label:'WARNING',  icon:'warning' },
    { re: /\bERROR\b/i,     color:'#ef4444', label:'ERROR',    icon:'error_outline' },
    { re: /\bCRITICAL\b/i,  color:'#dc2626', label:'CRITICAL', icon:'gavel' },
    { re: /\bNOTICE\b/i,    color:'#3b82f6', label:'NOTICE',   icon:'info' },
    { re: /PJSIP.*error/i,   color:'#f87171', label:'PJSIP ERR', icon:'warning' },
    { re: /Endpoint.*loaded/i, color:'#4ade80', label:'EP LOADED', icon:'power' },
    { re: /Unable to find/i, color:'#fb923c', label:'NOT FOUND', icon:'search_off' },
];

function parseLogLine(line) {
    for (const p of SIP_PARSERS) {
        if (p.re.test(line)) return p;
    }
    return { color: '#6b7280', label: 'LOG', icon: 'terminal' };
}

function SIPLogLine({ line, idx }) {
    const [open, setOpen] = useState(false);
    const parsed = parseLogLine(line);
    // Extract timestamp if present
    const tsMatch = line.match(/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/);
    const ts = tsMatch?.[1] || '';
    
    // Extract extension (e.g. from 1001, sip:1001@, Endpoint 1001)
    const extMatch = line.match(/(?:from|to|contact|endpoint|sip:|from:\s*<sip:)[^\d]*(\d{3,5})(?:@|>|\s|,)/i);
    const extChip = extMatch ? extMatch[1] : null;

    const content = line.replace(tsMatch?.[0]||'', '').trim();

    return (
        <div
            onClick={() => setOpen(o => !o)}
            style={{
                display:'flex', alignItems:'flex-start', gap:10,
                padding:'8px 12px', borderRadius:10, cursor:'pointer',
                background: open ? 'rgba(139,92,246,0.06)' : 'transparent',
                borderLeft:`3px solid ${parsed.color}`,
                marginBottom:2,
                transition:'background .15s'
            }}
        >
            <span className="material-icons-round" style={{fontSize:15, color:parsed.color, flexShrink:0, marginTop:1}}>{parsed.icon}</span>
            <div style={{flex:1, minWidth:0}}>
                <div style={{display:'flex', alignItems:'center', gap:8, flexWrap:'wrap'}}>
                    <span style={{
                        fontSize:9, fontWeight:800, letterSpacing:'.1em',
                        color: parsed.color,
                        background: `${parsed.color}18`,
                        border:`1px solid ${parsed.color}30`,
                        padding:'1px 7px', borderRadius:6,
                        textTransform:'uppercase', flexShrink:0
                    }}>{parsed.label}</span>
                    
                    {extChip && (
                        <span style={{
                            fontSize:10, fontWeight:700, color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',
                            background:'color-mix(in srgb, var(--primary) 15%, transparent)',
                            border:'1px solid color-mix(in srgb, var(--primary) 30%, transparent)',
                            padding:'1px 6px', borderRadius:6,
                            display:'flex', alignItems:'center', gap:3, flexShrink:0
                        }}>
                            <span className="material-icons-round" style={{fontSize:12}}>person</span>
                            {extChip}
                        </span>
                    )}

                    {ts && <span style={{fontSize:9, color:'#6b7280', fontFamily:'monospace', flexShrink:0}}>{ts}</span>}
                    <span style={{
                        fontSize:11, color: open ? '#e5e7eb' : '#9ca3af',
                        fontFamily: '"Fira Code", "Courier New", monospace',
                        overflow:'hidden', textOverflow:'ellipsis', whiteSpace: open?'pre-wrap':'nowrap',
                        lineHeight:'1.5'
                    }}>{content}</span>
                </div>
            </div>
            <div style={{display:'flex', alignItems:'center', gap:8, flexShrink:0}}>
                <span 
                    className="material-icons-round" 
                    style={{fontSize:15, color:'#6b7280', cursor:'pointer', padding:4, borderRadius:6}}
                    onClick={(e)=>{
                        e.stopPropagation();
                        navigator.clipboard.writeText(line);
                    }}
                    title="Copiar log"
                    onMouseOver={e=>e.currentTarget.style.background='rgba(255,255,255,0.05)'}
                    onMouseOut={e=>e.currentTarget.style.background='transparent'}
                >
                    content_copy
                </span>
                <span className="material-icons-round" style={{fontSize:18, color:'#374151', transform:open?'rotate(180deg)':'', transition:'transform .2s'}}>expand_more</span>
            </div>
        </div>
    );
}

const _rfc_main = window.ReactFlow || {};
const ReactFlowComp = _rfc_main.ReactFlow || _rfc_main.default || _rfc_main;
const _rf_u = _rfc_main.default || _rfc_main;

const Background = _rfc_main.Background || _rf_u.Background || (() => null);
const Controls = _rfc_main.Controls || _rf_u.Controls || (() => null);
const MiniMap = _rfc_main.MiniMap || _rf_u.MiniMap || (() => null);
const Handle = _rfc_main.Handle || _rf_u.Handle || (ReactFlowComp && ReactFlowComp.Handle) || (() => null);
const Position = _rfc_main.Position || _rf_u.Position || (ReactFlowComp && ReactFlowComp.Position) || { Top: 'top', Bottom: 'bottom', Left: 'left', Right: 'right' };
const ReactFlowProvider = _rfc_main.ReactFlowProvider || _rf_u.ReactFlowProvider || (({children}) => children);

const applyIvrNodeChanges = typeof (_rf_u.applyNodeChanges || ReactFlowComp.applyNodeChanges) === 'function' 
    ? (_rf_u.applyNodeChanges || ReactFlowComp.applyNodeChanges) 
    : (changes, nds) => {
        return nds.map(node => {
            const pos = changes.find(c => c.id === node.id && c.type === 'position');
            const sel = changes.find(c => c.id === node.id && c.type === 'select');
            let next = { ...node };
            if (pos && pos.position) next.position = pos.position;
            if (sel) next.selected = sel.selected;
            return next;
        });
    };

const applyIvrEdgeChanges = typeof (_rf_u.applyEdgeChanges || ReactFlowComp.applyEdgeChanges) === 'function'
    ? (_rf_u.applyEdgeChanges || ReactFlowComp.applyEdgeChanges)
    : (changes, eds) => {
        return eds.filter(edge => !changes.find(c => c.id === edge.id && c.type === 'remove'));
    };

const addIvrEdge = typeof (_rf_u.addEdge || ReactFlowComp.addEdge) === 'function'
    ? (_rf_u.addEdge || ReactFlowComp.addEdge)
    : (params, eds) => {
        return [...eds, { ...params, id: `e-${params.source}-${params.target}-${Date.now()}` }];
    };

let ivrNodeIdCounter = 0;
const getIvrNodeId = () => `node-${Date.now()}-${Math.floor(Math.random() * 10000)}`;

const NodeStart = ({ data }) => {
    const isLive = data.isLive;
    const ivrNum = data.ivrNumber || '7777';
    return (
        <div className={isLive ? 'anim-phone-ring' : ''} style={{background:'var(--surface)', border:`2px solid ${isLive ? '#22c55e' : 'var(--accent)'}`, borderRadius:16, padding:16, width:160, boxShadow: isLive ? '0 0 20px rgba(34,197,94,0.4), inset 0 0 10px rgba(34,197,94,0.1)' : '0 10px 25px rgba(0,0,0,0.1)', transition:'all 0.3s'}}>
            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:8}}>
                <span style={{fontSize:10, fontWeight:900, color:isLive ? '#22c55e' : 'var(--accent)', textTransform:'uppercase', letterSpacing:2}}>{isLive ? 'ACTIVO' : 'TRIGGER'}</span>
                <div style={{width:8, height:8, background:isLive ? '#22c55e' : '#6b7280', borderRadius:'50%', boxShadow: isLive ? '0 0 8px #22c55e' : 'none', animation: isLive ? 'pulse-red 1s infinite' : 'none'}}></div>
            </div>
            <div style={{fontSize:14, fontWeight:700, color:'var(--text)', display:'flex', alignItems:'center', gap:6}}>
                <span className="material-icons-round" style={{fontSize:18, color:isLive ? '#22c55e' : 'inherit'}}>play_arrow</span>
                IVR: {ivrNum}
            </div>
            {Handle && <Handle type="source" position={Position.Right} id="source" style={{width:12, height:12, background:'var(--surface)', border:`2px solid ${isLive ? '#22c55e' : 'var(--accent)'}`}} />}
        </div>
    );
};

const NodeMenu = ({ data, selected }) => {
    const isLive = data.isLive;
    return (
        <div className={isLive ? 'anim-vibrate anim-phone-ring' : ''} style={{background:'var(--surface)', border: selected ? '2px solid var(--accent)' : `1px solid ${isLive ? '#22c55e' : 'var(--border)'}`, borderRadius:16, padding:16, width:260, boxShadow: isLive ? '0 0 30px rgba(34,197,94,0.5), inset 0 0 10px rgba(34,197,94,0.1)' : (selected ? '0 10px 25px rgba(139,92,246,0.15)' : '0 10px 25px rgba(0,0,0,0.05)'), transition:'all 0.3s'}}>
            {Handle && <Handle type="target" position={Position.Left} id="target" style={{width:12, height:12, background:'var(--surface)', border:`2px solid ${isLive ? '#22c55e' : 'var(--accent)'}`}} />}
            <div style={{display:'flex', alignItems:'center', gap:8, marginBottom:12}}>
                <div style={{width:32, height:32, background: isLive ? 'rgba(34,197,94,0.2)' : 'var(--accent)', borderRadius:8, color: isLive ? '#22c55e' : 'white', display:'flex', alignItems:'center', justifyContent:'center'}}>
                    <span className="material-icons-round" style={{fontSize:16, animation: isLive ? 'pulse-red 0.5s infinite' : 'none'}}>splitscreen</span>
                </div>
                <span style={{flex:1, fontSize:11, fontWeight:900, color: isLive ? '#22c55e' : 'var(--accent)', textTransform:'uppercase', letterSpacing:2}}>{isLive ? 'LLAMADA ACTIVA' : (data.label || 'Menu')}</span>
                {isLive && <div style={{width:8, height:8, background:'#22c55e', borderRadius:'50%', boxShadow:'0 0 8px #22c55e', animation: 'pulse-red 0.5s infinite'}}></div>}
            </div>
            <div style={{background: isLive ? 'rgba(34,197,94,0.1)' : 'var(--surface2)', padding:8, borderRadius:8, fontSize:12, color:'var(--text)', textAlign:'center', border:`1px solid ${isLive ? 'rgba(34,197,94,0.3)' : 'var(--border)'}`, marginBottom:12}}>
                <span className="material-icons-round" style={{fontSize:14, verticalAlign:'middle', marginRight:4, color: isLive ? '#22c55e' : 'var(--muted)'}}>graphic_eq</span>
                {data.audio || 'Sin Audio'}
            </div>
            
            <div style={{display:'flex', flexDirection:'column', gap:8, position:'relative'}}>
                {(data.options || []).map(opt => (
                    <div key={opt.digit} style={{display:'flex', alignItems:'center', padding:8, background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:8, position:'relative'}}>
                        <div style={{width:24, height:24, background:'var(--bg)', borderRadius:4, display:'flex', alignItems:'center', justifyContent:'center', fontSize:11, fontWeight:800, color:'var(--text)', marginRight:10, flexShrink:0}}>{opt.digit}</div>
                        <div style={{display:'flex', flexDirection:'column', flex:1}}>
                            <div style={{fontSize:12, fontWeight:700, color:'var(--text)'}}>{opt.label}</div>
                            {opt.destination && <div style={{fontSize:10, color:'var(--muted)', marginTop:2, fontWeight:600}}>&rarr; {opt.destination}</div>}
                        </div>
                        <Handle type="source" position={Position.Right} id={`opt-${opt.digit}`} style={{right:-8, width:12, height:12, background:'var(--surface)', border:`2px solid ${isLive ? '#22c55e' : 'var(--accent)'}`, zIndex:20}} />
                    </div>
                ))}
            </div>
        </div>
    );
};

const NodeAction = ({ data, selected }) => {
    const isLive = data.isLive;
    return (
        <div style={{background:'var(--surface)', border: selected ? '2px solid var(--accent)' : `1px solid ${isLive ? '#22c55e' : 'var(--border)'}`, borderRadius:16, padding:16, width:200, boxShadow: isLive ? '0 0 20px rgba(34,197,94,0.4), inset 0 0 10px rgba(34,197,94,0.1)' : (selected ? '0 10px 25px rgba(0,0,0,0.05)' : 'none'), transition: 'all 0.3s'}}>
            {Handle && <Handle type="target" position={Position.Left} id="target" style={{width:12, height:12, background:'var(--surface)', border:`2px solid ${isLive ? '#22c55e' : 'var(--accent)'}`}} />}
            <div style={{display:'flex', alignItems:'center', gap:8, marginBottom:8}}>
                <div style={{width:28, height:28, background: isLive ? 'rgba(34,197,94,0.2)' : (data.colorbg || 'rgba(59,130,246,0.1)'), borderRadius:8, color: isLive ? '#22c55e' : (data.color || '#3b82f6'), display:'flex', alignItems:'center', justifyContent:'center'}}>
                    <span className="material-icons-round" style={{fontSize:16, animation: isLive ? 'pulse-red 1s infinite' : 'none'}}>{data.icon || 'phone_forwarded'}</span>
                </div>
                <span style={{fontSize:10, fontWeight:900, color: isLive ? '#22c55e' : (data.color || '#3b82f6'), textTransform:'uppercase', letterSpacing:1}}>{data.typeLabel || 'Action'}</span>
            </div>
            <div style={{fontSize:13, fontWeight:700, color:'var(--text)'}}>{data.label || 'Action'}</div>
            {Handle && <Handle type="source" position={Position.Right} style={{width:12, height:12, background:'var(--surface)', border:`1px solid ${isLive ? '#22c55e' : 'var(--border)'}`}} />}
        </div>
    );
}

const ivrNodeTypes = {
    start: NodeStart,
    menu: NodeMenu,
    action: NodeAction
};

const ivrEdgeTypes = {
    animatedData: AnimatedDataEdge
};

const ivrInitialNodes = [
  { id: 'start-1', type: 'start', position: { x: 50, y: 150 }, data: { ivrNumber: '7777' } }
];

function IVRDesignerApp({ toast }) {
    const [nodes, setNodes] = useState(ivrInitialNodes);
    const [edges, setEdges] = useState([]);
    const [ivrData, setIvrData] = useState({ recordings: [], extensions: [], queues: [], ringgroups: [] });
    const reactFlowWrapper = useRef(null);
    const [reactFlowInstance, setReactFlowInstance] = useState(null);
    const [selectedNode, setSelectedNode] = useState(null);

    const isIvrActiveRef = useRef(false);
    const nodesRef = useRef(ivrInitialNodes);
    const edgesRef = useRef([]);

    useEffect(() => {
        nodesRef.current = nodes;
        edgesRef.current = edges;
    }, [nodes, edges]);

    const onNodesChange = useCallback((changes) => {
        setNodes((nds) => applyIvrNodeChanges(changes, nds));
    }, []);
    const onEdgesChange = useCallback((changes) => {
        setEdges((eds) => applyIvrEdgeChanges(changes, eds));
    }, []);
    const onConnect = useCallback((params) => {
        setEdges((eds) => addIvrEdge({ 
            ...params, 
            type: 'animatedData',
            animated: isIvrActiveRef.current, 
            style: isIvrActiveRef.current ? { stroke: '#22c55e', strokeWidth: 3, filter: 'drop-shadow(0 0 4px #22c55e)' } : { stroke: 'var(--accent)', strokeWidth: 2 } 
        }, eds));
    }, []);

    useEffect(() => {
        // Load external data
        fetch('api/index.php?action=get_ivr_data', { credentials: 'include' }).then(r=>r.json()).then(d=>{
            if(d.success) setIvrData(d);
        });

        // Load existing flow (if any)
        fetch('api/index.php?action=get_ivr_flow', { credentials: 'include' })
            .then(r => r.json())
            .then(d => {
                if(d.nodes && d.edges) {
                    setNodes(d.nodes);
                    setEdges(d.edges);
                }
            }).catch(e => console.log('No existing flow to load', e));
            
        // Live Animation Polling
        const checkCalls = () => {
            fetch('api/index.php?action=get_active_calls', { credentials: 'include' }).then(r=>r.json()).then(d => {
                if(d.success && d.calls) {
                    const startNodes = nodesRef.current.filter(n => n.type === 'start');
                    const ivrNumbers = startNodes.map(n => String(n.data.ivrNumber || '7777'));
                    
                    const ivrCalls = d.calls.filter(c => 
                        (c.context && c.context.startsWith('ivr-node-')) || 
                        (c.dest && ivrNumbers.includes(String(c.dest))) ||
                        (c.from && ivrNumbers.includes(String(c.from)))
                    );

                    const activeTokens = ivrCalls.reduce((acc, c) => [...acc, c.dest, c.ext], []).filter(Boolean);
                    
                    const currentNodes = nodesRef.current;
                    const currentEdges = edgesRef.current;
                    
                    let ivrNumberCalled = false;
                    currentNodes.forEach(n => {
                        if (n.type === 'start') {
                            const ivrNum = String(n.data.ivrNumber || '7777');
                            if (activeTokens.includes(ivrNum)) ivrNumberCalled = true;
                        }
                    });
                    
                    const activeContexts = d.calls.filter(c => c.context && c.context.startsWith('ivr-node-')).map(c => c.context.replace('ivr-node-', ''));
                    
                    const activeNodeIds = new Set([
                        ...currentNodes.filter(n => {
                            if(n.type === 'action') {
                                const parts = (n.data.label || '').split(': ');
                                const num = parts.length > 1 ? parts[1].trim() : parts[0];
                                return activeTokens.includes(num);
                            }
                            return false;
                        }).map(n => n.id),
                        ...activeContexts
                    ]);
                    
                    const edgesToAnimate = new Set();
                    let currentTargets = [...activeNodeIds];
                    while(currentTargets.length > 0) {
                        const nextTargets = [];
                        currentEdges.forEach(e => {
                            if (currentTargets.includes(e.target) && !edgesToAnimate.has(e.id)) {
                                edgesToAnimate.add(e.id);
                                nextTargets.push(e.source);
                            }
                        });
                        currentTargets = [...new Set(nextTargets)];
                    }
                    
                    const hasActiveFlow = edgesToAnimate.size > 0 || ivrNumberCalled;
                    
                    setEdges(eds => {
                        let changed = false;
                        const newEds = eds.map(e => {
                            const isActiveEdge = edgesToAnimate.has(e.id);
                            if (e.animated !== isActiveEdge) changed = true;
                            return isActiveEdge ? {
                                ...e, type: 'animatedData', animated: true, style: { stroke: '#22c55e', strokeWidth: 3, filter: 'drop-shadow(0 0 4px #22c55e)' }
                            } : {
                                ...e, type: 'animatedData', animated: false, style: { stroke: 'var(--accent)', strokeWidth: 2, filter: 'none' }
                            };
                        });
                        return changed ? newEds : eds;
                    });
                    
                    setNodes(nds => {
                        let changed = false;
                        const newNds = nds.map(n => {
                            if(n.type === 'start') {
                                if (n.data.isLive !== hasActiveFlow) {
                                    changed = true;
                                    return { ...n, data: { ...n.data, isLive: hasActiveFlow } };
                                }
                            }
                            if(n.type === 'menu' || n.type === 'action') {
                                const isActiveNode = activeNodeIds.has(n.id);
                                if (n.data.isLive !== isActiveNode) {
                                    changed = true;
                                    return { ...n, data: { ...n.data, isLive: isActiveNode } };
                                }
                            }
                            return n;
                        });
                        return changed ? newNds : nds;
                    });
                }
            }).catch(e=>{});
        };
        const timer = setInterval(checkCalls, 2000);
        return () => clearInterval(timer);
    }, []);

    const onDragOver = useCallback((event) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }, []);

    const onDrop = useCallback(
        (event) => {
            event.preventDefault();
            const type = event.dataTransfer.getData('application/reactflow');
            if (!type || !reactFlowInstance) return;

            const position = reactFlowInstance.screenToFlowPosition({
                x: event.clientX,
                y: event.clientY,
            });

            let newNode = {
                id: getIvrNodeId(),
                type,
                position,
                data: { label: `Nuevo ${type}` },
            };

            if (type === 'menu') {
                newNode.data = { label: 'Menú Principal', audio: '', options: [{ digit: '1', label: 'Ventas' }] };
            } else if (type === 'action') {
                newNode.data = { typeLabel: 'Destino', label: 'Ext: 1000', icon: 'phone_forwarded', color: '#3b82f6', colorbg: 'rgba(59,130,246,0.1)' };
            }

            setNodes((nds) => nds.concat(newNode));
        },
        [reactFlowInstance]
    );

    const onSelectionChange = useCallback(({ nodes }) => {
        if (nodes.length === 1) setSelectedNode(nodes[0]);
        else setSelectedNode(null);
    }, []);

    const updateNodeData = (field, value) => {
        if (!selectedNode) return;
        setNodes(nds => nds.map(n => {
            if (n.id === selectedNode.id) {
                const newData = { ...n.data, [field]: value };
                // Keep selected node state in sync with actual list
                return { ...n, data: newData };
            }
            return n;
        }));
    };

    // Re-sync selectedNode when nodes array changes
    useEffect(() => {
        if (selectedNode) {
            const found = nodes.find(n => n.id === selectedNode.id);
            if (found) setSelectedNode(found);
            else setSelectedNode(null);
        }
    }, [nodes]);

    return (
        <div style={{display:'flex', height:'100%', width:'100%', flexDirection:'row', fontFamily:'var(--sans)'}}>
            {/* Sidebar Tools */}
            <div className="glass" style={{width: 260, borderLeft:'1px solid var(--border)', display:'flex', flexDirection:'column', zIndex:10, borderRadius:0, order: 2}}>
                <div style={{padding:20, borderBottom:'1px solid var(--border)'}}>
                    <h3 style={{fontSize:11, fontWeight:900, color:'var(--muted)', letterSpacing:2, textTransform:'uppercase'}}>Librería de Nodos</h3>
                    <p style={{fontSize:12, color:'var(--muted)', marginTop:4}}>Arrastra al lienzo para crear lógica</p>
                </div>
                <div style={{padding:20, display:'flex', flexDirection:'column', gap:10}}>
                    <div 
                        onDragStart={(e) => { e.dataTransfer.setData('application/reactflow', 'menu'); e.dataTransfer.effectAllowed = 'move'; }}
                        draggable 
                        style={{display:'flex', alignItems:'center', gap:12, padding:12, background:'var(--surface)', border:'1px dashed var(--border)', borderRadius:12, cursor:'grab'}}
                    >
                        <div style={{width:32, height:32, background:'color-mix(in srgb, var(--primary) 10%, transparent)', color:'var(--accent)', borderRadius:8, display:'flex', alignItems:'center', justifyContent:'center'}}>
                            <span className="material-icons-round" style={{fontSize:18}}>splitscreen</span>
                        </div>
                        <span style={{fontSize:13, fontWeight:700, color:'var(--text)'}}>Menú de Opciones</span>
                    </div>

                    <div 
                        onDragStart={(e) => { e.dataTransfer.setData('application/reactflow', 'action'); e.dataTransfer.effectAllowed = 'move'; }}
                        draggable 
                        style={{display:'flex', alignItems:'center', gap:12, padding:12, background:'var(--surface)', border:'1px dashed var(--border)', borderRadius:12, cursor:'grab'}}
                    >
                        <div style={{width:32, height:32, background:'rgba(59,130,246,0.1)', color:'#3b82f6', borderRadius:8, display:'flex', alignItems:'center', justifyContent:'center'}}>
                            <span className="material-icons-round" style={{fontSize:18}}>phone_forwarded</span>
                        </div>
                        <span style={{fontSize:13, fontWeight:700, color:'var(--text)'}}>Transferir / Cola</span>
                    </div>
                </div>
            </div>

            {/* Diagram */}
            <div style={{flex: 1, position:'relative', order: 1}} ref={reactFlowWrapper}>
                {ReactFlowComp ? <ReactFlowComp
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    onConnect={onConnect}
                    onInit={setReactFlowInstance}
                    onDrop={onDrop}
                    onDragOver={onDragOver}
                    onSelectionChange={onSelectionChange}
                    nodeTypes={ivrNodeTypes}
                    edgeTypes={ivrEdgeTypes}
                    fitView
                    snapToGrid={true}
                    snapGrid={[15, 15]}
                >
                    {Background && <Background color="rgba(255,255,255,0.05)" gap={20} size={1.5} />}
                    {Controls && <Controls style={{background:'var(--surface)', border:'1px solid var(--border)', borderRadius:8, boxShadow:'0 4px 12px rgba(0,0,0,0.1)'}} />}
                    {MiniMap && <MiniMap maskColor="rgba(0,0,0,0.5)" style={{ background: 'var(--surface2)', borderRadius: 12, border: '1px solid var(--border)' }} />}
                </ReactFlowComp> : <div style={{padding:40,textAlign:'center',color:'var(--muted)'}}>Editor IVR cargando...</div>}

                <div style={{position:'absolute', top:20, left:20, zIndex:10, background:'var(--surface)', padding:'10px 20px', borderRadius:12, border:'1px solid var(--border)', display:'flex', gap:12, boxShadow:'0 4px 20px rgba(0,0,0,0.1)'}}>
                    <button onClick={()=>{ 
                        const flowData = { nodes, edges };
                        fetch('api/index.php?action=save_ivr_flow', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(flowData)
                        }).then(r=>r.json()).then(d=>{
                            if(d.success) toast('Flujo guardado y vinculado correctamente', 'success');
                            else toast('Error al guardar: ' + d.error, 'error');
                        }).catch(e=>toast('Error de red al guardar', 'error'));
                    }} style={{background:'var(--accent)', color:'white', border:'none', padding:'8px 16px', borderRadius:8, fontWeight:700, cursor:'pointer', display:'flex', alignItems:'center', gap:6}}><span className="material-icons-round" style={{fontSize:18}}>save</span> Guardar Flujo</button>
                    <button onClick={()=>{ 
                        toast('Enviando despliegue a Asterisk...', 'info'); 
                        fetch('api/index.php?action=apply_ivr_flow')
                            .then(r=>r.json())
                            .then(d=>{
                                if(d.success) toast('IVR compilado y desplegado con éxito en la PBX.', 'success');
                                else toast('Error al compilar: ' + (d.error || 'Desconocido'), 'error');
                            })
                            .catch(e=>toast('Error de red desplegando IVR.', 'error'));
                    }} style={{background:'transparent', color:'var(--text)', border:'1px solid var(--border)', padding:'8px 16px', borderRadius:8, fontWeight:700, cursor:'pointer', display:'flex', alignItems:'center', gap:6}}><span className="material-icons-round" style={{fontSize:18}}>rocket_launch</span> Aplicar</button>
                </div>
            </div>

            {/* Properties Panel (Modal Overlay) */}
            {selectedNode && (
                <>
                    {/* Backdrop */}
                    <div 
                        onClick={() => setSelectedNode(null)}
                        style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(4px)', zIndex: 100 }} 
                    />
                    
                    {/* Sliding Modal */}
                    <div className="glass" style={{
                        position: 'fixed', top: 20, right: 20, bottom: 20, width: 360,
                        border: '1px solid rgba(255,255,255,0.1)', display: 'flex', flexDirection: 'column', 
                        zIndex: 101, animation: 'viewIn 0.3s ease', boxShadow: '0 20px 50px rgba(0,0,0,0.5)',
                        overflow: 'hidden'
                    }}>
                        <div style={{padding:20, borderBottom:'1px solid var(--border)', display:'flex', justifyContent:'space-between', alignItems:'center', background:'color-mix(in srgb, var(--primary) 10%, transparent)'}}>
                            <div>
                                <h3 style={{fontSize:16, fontWeight:800, color:'var(--text)', margin:0}}>Propiedades</h3>
                                <p style={{fontSize:10, color:'var(--muted)', marginTop:2, textTransform:'uppercase', fontWeight:800, margin:0}}>ID: {selectedNode.id}</p>
                            </div>
                            <button onClick={()=>setSelectedNode(null)} style={{background:'rgba(255,255,255,0.05)', border:'none', color:'var(--text)', cursor:'pointer', width:32, height:32, borderRadius:'50%', display:'flex', alignItems:'center', justifyContent:'center'}}><span className="material-icons-round" style={{fontSize:18}}>close</span></button>
                        </div>

                        <div style={{padding:24, display:'flex', flexDirection:'column', gap:20, flex: 1, overflowY:'auto'}}>
                            {selectedNode.type === 'start' && (
                                <div>
                                    <label style={{display:'block', fontSize:11, fontWeight:900, color:'var(--muted)', textTransform:'uppercase', marginBottom:8, letterSpacing: 1}}>Número de Acceso (IVR)</label>
                                    <input type="text" value={selectedNode.data.ivrNumber || '7777'} onChange={e=>updateNodeData('ivrNumber', e.target.value)} placeholder="Ej. 7777" style={{width:'100%', padding:'12px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:10, color:'var(--text)', outline:'none', fontWeight:700, fontSize: 13}} />
                                    <div style={{fontSize:11, color:'var(--muted)', marginTop:8, lineHeight:1.5}}>Configura el número que los usuarios deben marcar para activar este flujo IVR.</div>
                                </div>
                            )}

                            {selectedNode.type === 'menu' && (
                                <>
                                    <div>
                                        <label style={{display:'block', fontSize:11, fontWeight:900, color:'var(--muted)', textTransform:'uppercase', marginBottom:8, letterSpacing: 1}}>Nombre del Menú</label>
                                        <input type="text" value={selectedNode.data.label||''} onChange={e=>updateNodeData('label', e.target.value)} style={{width:'100%', padding:'12px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:10, color:'var(--text)', outline:'none', fontWeight:600}} />
                                    </div>
                                    <div>
                                        <label style={{display:'block', fontSize:11, fontWeight:900, color:'var(--muted)', textTransform:'uppercase', marginBottom:8, letterSpacing: 1}}>Audio de Bienvenida</label>
                                        <div style={{display:'flex', gap:8, alignItems:'center'}}>
                                            <select value={selectedNode.data.audio||''} onChange={e=>updateNodeData('audio', e.target.value)} style={{flex:1, padding:'12px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:10, color:'var(--text)', outline:'none'}}>
                                                <option value="">-- Seleccionar --</option>
                                                {ivrData.recordings.map(r=><option key={r} value={r}>{r}</option>)}
                                            </select>
                                            <button className="btn-glass" onClick={()=>{
                                                const fileInput = document.createElement('input'); fileInput.type = 'file'; fileInput.accept = 'audio/*';
                                                fileInput.onchange = (e) => {
                                                    const file = e.target.files[0]; if(!file) return;
                                                    const formData = new FormData(); formData.append('audio', file);
                                                    toast('Subiendo audio...', 'info');
                                                    fetch('api/index.php?action=upload_ivr_audio', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                                                        if(data.success) {
                                                            setIvrData(prev => ({...prev, recordings: [...prev.recordings, data.filename]}));
                                                            updateNodeData('audio', data.filename);
                                                            toast('Audio subido con éxito', 'success');
                                                        } else toast('Error: ' + data.error, 'error');
                                                    });
                                                }; fileInput.click();
                                            }} style={{padding:12, borderRadius:10}}><span className="material-icons-round">upload</span></button>
                                        </div>
                                    </div>
                                    <div>
                                        <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:10}}>
                                            <label style={{display:'block', fontSize:11, fontWeight:900, color:'var(--muted)', textTransform:'uppercase', letterSpacing:1}}>Opciones</label>
                                            <button onClick={()=>{
                                                const ops = selectedNode.data.options || [];
                                                updateNodeData('options', [...ops, {digit: (ops.length+1).toString(), label:'Nueva Opción'}]);
                                            }} style={{background:'var(--accent)', color:'white', border:'none', padding:'4px 10px', borderRadius:6, fontSize:10, fontWeight:900, cursor:'pointer'}}>+ AÑADIR</button>
                                        </div>
                                        <div style={{display:'flex', flexDirection:'column', gap:10}}>
                                            {(selectedNode.data.options||[]).map((opt, i) => (
                                                <div key={i} style={{background: 'rgba(255,255,255,0.03)', padding:12, borderRadius:12, border:'1px solid var(--border)'}}>
                                                    <div style={{display:'flex', gap:8, alignItems:'center', marginBottom:8}}>
                                                        <input type="text" placeholder="X" value={opt.digit} onChange={e=>{
                                                            const o = [...selectedNode.data.options]; o[i].digit = e.target.value; updateNodeData('options', o);
                                                        }} style={{width:40, padding:'8px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:6, color:'var(--text)', textAlign:'center', outline:'none', fontWeight:900}} />
                                                        <input type="text" placeholder="Etiqueta" value={opt.label} onChange={e=>{
                                                            const o = [...selectedNode.data.options]; o[i].label = e.target.value; updateNodeData('options', o);
                                                        }} style={{flex:1, padding:'8px 12px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:6, color:'var(--text)', outline:'none', fontSize:12, fontWeight:600}} />
                                                        <button onClick={()=>{
                                                            const o = [...selectedNode.data.options]; o.splice(i, 1); updateNodeData('options', o);
                                                        }} style={{color:'var(--danger)', opacity:0.6, cursor:'pointer', background:'none', border:'none'}}><span className="material-icons-round" style={{fontSize:18}}>delete_sweep</span></button>
                                                    </div>
                                                    <select value={opt.destination||''} onChange={e=>{
                                                        const o = [...selectedNode.data.options]; o[i].destination = e.target.value; updateNodeData('options', o);
                                                    }} style={{width:'100%', padding:'8px', background:'var(--bg)', border:'1px solid var(--border)', borderRadius:6, color:'var(--muted)', fontSize:11, outline:'none'}}>
                                                        <optgroup label="Extensiones">{ivrData.extensions.map(e=><option key={e.ext} value={`Ext: ${e.ext}`}>{e.ext} - {e.name}</option>)}</optgroup>
                                                        <optgroup label="Colas">{ivrData.queues.map(q=><option key={q.ext} value={`Cola: ${q.ext}`}>{q.name}</option>)}</optgroup>
                                                        <optgroup label="Grupos">{ivrData.ringgroups.map(g=><option key={g.ext} value={`Grupo: ${g.ext}`}>{g.name}</option>)}</optgroup>
                                                        <option value="Colgar Llamada">Colgar Llamada</option>
                                                    </select>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </>
                            )}
                            
                            {selectedNode.type === 'action' && (
                                <div>
                                    <label style={{display:'block', fontSize:11, fontWeight:900, color:'var(--muted)', textTransform:'uppercase', marginBottom:8, letterSpacing:1}}>Acción o Destino</label>
                                    <select value={selectedNode.data.label||''} onChange={e=>{
                                        const val = e.target.value;
                                        let icon = 'phone_forwarded', color = '#3b82f6', colorbg = 'rgba(59,130,246,0.1)';
                                        if(val.startsWith('Cola:')) { icon = 'trending_up'; color = '#6366f1'; colorbg = 'rgba(99,102,241,0.1)'; }
                                        if(val.startsWith('Grupo:')) { icon = 'groups'; color = '#8b5cf6'; colorbg = 'rgba(139,92,246,0.1)'; }
                                        if(val === 'Colgar Llamada') { icon = 'call_end'; color = '#ef4444'; colorbg = 'rgba(239,68,68,0.1)'; }
                                        updateNodeData('label', val); updateNodeData('icon', icon); updateNodeData('color', color); updateNodeData('colorbg', colorbg);
                                    }} style={{width:'100%', padding:'12px', background:'var(--surface2)', border:'1px solid var(--border)', borderRadius:10, color:'var(--text)', outline:'none', fontWeight:600}}>
                                        <option value="">-- Seleccionar --</option>
                                        <optgroup label="Extensiones">{ivrData.extensions.map(e=><option key={e.ext} value={`Ext: ${e.ext}`}>{e.ext} - {e.name}</option>)}</optgroup>
                                        <optgroup label="Colas">{ivrData.queues.map(q=><option key={q.ext} value={`Cola: ${q.ext}`}>{q.name}</option>)}</optgroup>
                                        <optgroup label="Grupos">{ivrData.ringgroups.map(g=><option key={g.ext} value={`Grupo: ${g.ext}`}>{g.name}</option>)}</optgroup>
                                        <option value="Colgar Llamada">Colgar Llamada</option>
                                    </select>
                                </div>
                            )}

                            <div style={{marginTop:'auto', paddingTop:20, borderTop:'1px solid var(--border)'}}>
                                <button onClick={()=>setNodes(nds => nds.filter(n=>n.id!==selectedNode.id))} style={{width:'100%', background:'rgba(239,68,68,0.1)', color:'#ef4444', border:'1px solid rgba(239,68,68,0.2)', padding:'12px', borderRadius:10, fontWeight:700, cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', gap:8, transition:'all 0.2s'}} onMouseOver={e=>e.currentTarget.style.background='rgba(239,68,68,0.2)'} onMouseOut={e=>e.currentTarget.style.background='rgba(239,68,68,0.1)'}>
                                    <span className="material-icons-round" style={{fontSize:20}}>delete_forever</span> Eliminar Nodo Seleccionado
                                </button>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

function ViewIVR({ toast }) {
    if (!ReactFlowComp) {
        return (
            <div className="content-area view-enter" style={{display:'flex', alignItems:'center', justifyContent:'center', height:'100%'}}>
                <div style={{textAlign:'center', color:'var(--muted)'}}>
                    <span className="material-icons-round" style={{fontSize:48, animation:'spin-slow 2s linear infinite'}}>refresh</span>
                    <h3 style={{marginTop:16, fontSize:18, fontWeight:700}}>Cargando Engine...</h3>
                </div>
            </div>
        );
    }
    
    return (
        <div className="content-area view-enter" style={{display:'flex', flexDirection:'column', padding: 0, height: '100%', borderRadius: 16, overflow: 'hidden'}}>
            <ReactFlowProvider>
                <IVRDesignerApp toast={toast} />
            </ReactFlowProvider>
        </div>
    );
}
function ViewConfiguracion() {
    const [activeTab, setActiveTab] = useState('notificaciones');
    const [sipLog, setSipLog] = useState('');
    const [loadingSip, setLoadingSip] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [pjsipActive, setPjsipActive] = useState(false);
    const [filter, setFilter] = useState('');
    const [senderFilter, setSenderFilter] = useState(''); 
    const logEndRef = useRef(null);

    const loadSipDebug = async () => {
        setLoadingSip(true);
        try {
            const r = await fetch('api/index.php?action=get_sip_debug');
            const d = await r.json();
            if (d.success) {
                setSipLog(d.log || '');
                if (d.is_debug_active !== undefined) setPjsipActive(d.is_debug_active);
            }
        } catch(e) { setSipLog('Error al conectar con el servidor.'); }
        setLoadingSip(false);
    };

    const toggleAsteriskDebug = async (level) => {
        try {
            const body = new FormData();
            body.append('level', level);
            const r = await fetch('api/index.php?action=set_sip_debug', { method: 'POST', body });
            const d = await r.json();
            if (d.success) {
                setPjsipActive(level === 'on');
                loadSipDebug(); 
            }
        } catch(e) { console.error('Error configurando asterisk'); }
    };

    useEffect(() => {
        if (activeTab === 'debug_sip') {
            loadSipDebug();
            if (autoRefresh) {
                const t = setInterval(loadSipDebug, 3000);
                return () => clearInterval(t);
            }
        }
    }, [activeTab, autoRefresh]);

    useEffect(() => {
        if (logEndRef.current) logEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }, [sipLog]);

    // Extraer IPs/extensiones únicas de los logs (remitentes)
    const extractSenders = (lines) => {
        const senders = new Set();
        lines.forEach(line => {
            const ipMatch = line.match(/(?:from|contact|via)[:\s]+(?:sip:)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i);
            const extMatch = line.match(/(?:From:|REGISTER sip:|from:\s*sip:)(\d{3,5})(?:@|>|\s)/i);
            if (ipMatch?.[1]) senders.add(ipMatch[1]);
            if (extMatch?.[1]) senders.add('Ext:' + extMatch[1]);
        });
        return Array.from(senders).sort();
    };

    const logLines = sipLog.split('\n').filter(l => l.trim());
    const senders = extractSenders(logLines);

    const filteredLines = logLines.filter(l => {
        const matchText = !filter || l.toLowerCase().includes(filter.toLowerCase());
        const matchSender = !senderFilter || l.toLowerCase().includes(
            senderFilter.startsWith('Ext:') ? senderFilter.replace('Ext:','') : senderFilter
        );
        return matchText && matchSender;
    });

    const stats = {
        ok:     logLines.filter(l => /200 OK/.test(l)).length,
        auth:   logLines.filter(l => /401|403/.test(l)).length,
        reg:    logLines.filter(l => /REGISTER/.test(l)).length,
        errors: logLines.filter(l => /error|failed/i.test(l)).length,
    };

    return (
        <div className="content-area view-enter">
            <div className="glass" style={{display:'flex', padding:4, borderRadius:16, marginBottom:24, background:'var(--surface2)', width:'fit-content'}}>
                <button onClick={()=>setActiveTab('notificaciones')} style={{padding:'10px 20px', borderRadius:12, border:'none', background:activeTab==='notificaciones'?'var(--surface)':'transparent', color:activeTab==='notificaciones'?'var(--accent)':'var(--muted)', fontWeight:700, fontSize:13, cursor:'pointer', transition:'all .3s'}}>
                    <span className="material-icons-round" style={{fontSize:18, marginRight:8, verticalAlign:'middle'}}>notifications</span>Notificaciones
                </button>
                <button onClick={()=>setActiveTab('debug_sip')} style={{padding:'10px 20px', borderRadius:12, border:'none', background:activeTab==='debug_sip'?'var(--surface)':'transparent', color:activeTab==='debug_sip'?'var(--accent)':'var(--muted)', fontWeight:700, fontSize:13, cursor:'pointer', transition:'all .3s'}}>
                    <span className="material-icons-round" style={{fontSize:18, marginRight:8, verticalAlign:'middle'}}>terminal</span>Debug SIP
                </button>
                <button onClick={()=>setActiveTab('pbx')} style={{padding:'10px 20px', borderRadius:12, border:'none', background:activeTab==='pbx'?'var(--surface)':'transparent', color:activeTab==='pbx'?'var(--accent)':'var(--muted)', fontWeight:700, fontSize:13, cursor:'pointer', transition:'all .3s'}}>
                    <span className="material-icons-round" style={{fontSize:18, marginRight:8, verticalAlign:'middle'}}>dns</span>PBX
                </button>
                <button onClick={()=>setActiveTab('branding')} style={{padding:'10px 20px', borderRadius:12, border:'none', background:activeTab==='branding'?'var(--surface)':'transparent', color:activeTab==='branding'?'var(--accent)':'var(--muted)', fontWeight:700, fontSize:13, cursor:'pointer', transition:'all .3s'}}>
                    <span className="material-icons-round" style={{fontSize:18, marginRight:8, verticalAlign:'middle'}}>palette</span>Branding
                </button>
                <button onClick={()=>setActiveTab('softphone')} style={{padding:'10px 20px', borderRadius:12, border:'none', background:activeTab==='softphone'?'var(--surface)':'transparent', color:activeTab==='softphone'?'var(--accent)':'var(--muted)', fontWeight:700, fontSize:13, cursor:'pointer', transition:'all .3s'}}>
                    <span className="material-icons-round" style={{fontSize:18, marginRight:8, verticalAlign:'middle'}}>phone_in_talk</span>Softphone
                </button>
            </div>

            {activeTab === 'notificaciones' && (
                <div className="anim-fadeup">
                    <div className="glass" style={{padding:24}}>
                        <h4 style={{fontSize:15, fontWeight:800, color:'var(--text)', marginBottom:20}}>Alertas del Navegador</h4>
                        <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', padding:'16px 0', borderBottom:'1px solid var(--border)'}}>
                            <div>
                                <div style={{fontSize:14, fontWeight:600, color:'var(--text)'}}>Notificaciones Push</div>
                                <div style={{fontSize:11, color:'#6b7280', marginTop:2}}>Recibe avisos de llamadas en vivo incluso si la pestaña está cerrada.</div>
                            </div>
                            <button className="btn-primary" style={{padding:'8px 16px', borderRadius:10, fontSize:12}} onClick={()=>Notification.requestPermission()}>Solicitar Permiso</button>
                        </div>
                        <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', padding:'16px 0'}}>
                            <div>
                                <div style={{fontSize:14, fontWeight:600, color:'var(--text)'}}>Alertas Sonoras</div>
                                <div style={{fontSize:11, color:'#6b7280', marginTop:2}}>Reproducir ringtone al recibir llamadas en el Softphone.</div>
                            </div>
                            <div style={{width:40, height:20, background:'var(--accent)', borderRadius:10, position:'relative', cursor:'pointer'}}>
                                <div style={{position:'absolute', right:2, top:2, width:16, height:16, background:'white', borderRadius:'50%'}}></div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'debug_sip' && (
                <div className="anim-fadeup">
                    <div className="glass" style={{padding:'12px 16px', borderRadius:16, marginBottom:12, display:'flex', alignItems:'center', gap:12, flexWrap:'wrap'}}>
                        <div style={{display:'flex', alignItems:'center', gap:10, marginRight:10}}>
                            <div className={pjsipActive ? 'pulse-green' : ''} style={{width:8, height:8, borderRadius:'50%', background: pjsipActive ? '#22c55e' : '#4b5563'}}></div>
                            <span style={{fontWeight:800, color: pjsipActive ? '#8b5cf6' : '#6b7280', fontSize:14, whiteSpace:'nowrap'}}>PJSIP Logger {pjsipActive ? '(ON)' : '(OFF)'}</span>
                        </div>

                        <div style={{display:'flex', gap:6, background:'rgba(0,0,0,0.15)', padding:3, borderRadius:12, marginRight:10}}>
                            {[
                                {l:'Registros OK',   v:stats.ok,     c:'#4ade80', i:'check_circle', f:'200 OK'},
                                {l:'Auth Errors',    v:stats.auth,   c:'#f87171', i:'lock', f:'401|403'},
                                {l:'REGISTER',       v:stats.reg,    c:'#60a5fa', i:'login', f:'REGISTER'},
                                {l:'Errores',        v:stats.errors, c:'#fb923c', i:'warning', f:'error|failed'},
                            ].map(s => (
                                <button 
                                    key={s.l} 
                                    onClick={()=>setFilter(f => f === s.f ? '' : s.f)}
                                    className="active-scale"
                                    style={{
                                        display:'flex', alignItems:'center', gap:6, padding:'5px 10px', 
                                        borderRadius:10, border:'none', cursor:'pointer',
                                        background: filter === s.f ? `${s.c}20` : 'transparent',
                                        border: filter === s.f ? `1px solid ${s.c}40` : '1px solid transparent',
                                        transition: 'all .2s'
                                    }}
                                >
                                    <span className="material-icons-round" style={{fontSize:14, color:s.c}}>{s.i}</span>
                                    <span style={{fontSize:10, fontWeight:700, color:filter === s.f ? s.c : '#6b7280'}}>{s.v}</span>
                                </button>
                            ))}
                        </div>

                        <div style={{flexGrow:1}} />
                        
                        <div style={{position:'relative'}}>
                            <span className="material-icons-round" style={{position:'absolute',left:10,top:'50%',transform:'translateY(-50%)',fontSize:14,color:'#4b5563',pointerEvents:'none',zIndex:1}}>router</span>
                            <select
                                style={{
                                    padding:'6px 12px 6px 32px', borderRadius:10, fontSize:12, width:150,
                                    background:'var(--surface2)', border:'1px solid var(--border)',
                                    color: senderFilter ? '#c4b5fd' : '#6b7280',
                                    cursor:'pointer', appearance:'none', outline:'none'
                                }}
                                value={senderFilter}
                                onChange={e=>setSenderFilter(e.target.value)}
                            >
                                <option value="">Remitentes</option>
                                {senders.map(s => <option key={s} value={s.startsWith('Ext:') ? s.replace('Ext:','') : s}>{s}</option>)}
                            </select>
                        </div>

                        <div style={{position:'relative'}}>
                            <span className="material-icons-round" style={{position:'absolute',left:10,top:'50%',transform:'translateY(-50%)',fontSize:15,color:'#4b5563'}}>search</span>
                            <input
                                className="input-tf"
                                style={{padding:'6px 12px 6px 34px', borderRadius:10, fontSize:12, width:150, background:'var(--surface2)'}}
                                placeholder="Filtrar..."
                                value={filter}
                                onChange={e=>setFilter(e.target.value)}
                            />
                        </div>

                        <div style={{display:'flex', gap:6}}>
                            <button
                                onClick={()=>setAutoRefresh(a=>!a)}
                                className="active-scale"
                                title={autoRefresh ? 'Pausar stream' : 'Reanudar stream'}
                                style={{
                                    width:32, height:32, borderRadius:10, display:'flex', alignItems:'center', justifyContent:'center',
                                    background: autoRefresh ? 'rgba(34,197,94,0.12)' : 'var(--surface2)',
                                    border: autoRefresh ? '1px solid rgba(34,197,94,0.3)' : '1px solid var(--border)',
                                    color: autoRefresh ? '#4ade80' : '#6b7280',
                                    cursor:'pointer'
                                }}
                            >
                                <span className="material-icons-round" style={{fontSize:18, animation:autoRefresh&&loadingSip?'spin-slow 1s linear infinite':''}}>
                                    {autoRefresh ? 'sync' : 'play_arrow'}
                                </span>
                            </button>

                            <button
                                onClick={()=>{setSipLog('');loadSipDebug();}}
                                className="active-scale"
                                title="Limpiar logs"
                                style={{
                                    width:32, height:32, borderRadius:10, display:'flex', alignItems:'center', justifyContent:'center',
                                    background:'var(--surface2)', border:'1px solid var(--border)', color:'#f87171', cursor:'pointer'
                                }}
                            >
                                <span className="material-icons-round" style={{fontSize:18}}>delete_sweep</span>
                            </button>

                            <button
                                onClick={()=>toggleAsteriskDebug('on')}
                                className="active-scale"
                                title="Activar Asterisk Verbose 6 + PJSIP Logger"
                                style={{
                                    width:32, height:32, borderRadius:10, display:'flex', alignItems:'center', justifyContent:'center',
                                    background: pjsipActive ? 'rgba(139,92,246,0.3)' : 'rgba(59,130,246,0.1)', 
                                    border: pjsipActive ? '1px solid #8b5cf6' : '1px solid rgba(59,130,246,0.3)', 
                                    color: pjsipActive ? '#c4b5fd' : '#60a5fa', 
                                    cursor:'pointer',
                                    boxShadow: pjsipActive ? '0 0 15px rgba(139,92,246,0.4)' : 'none'
                                }}
                            >
                                <span className="material-icons-round" style={{fontSize:18}}>{pjsipActive ? 'running_with_errors' : 'bug_report'}</span>
                            </button>
                            
                            <button
                                onClick={()=>toggleAsteriskDebug('off')}
                                className="active-scale"
                                title="Desactivar Debug (Verbose 3)"
                                style={{
                                    width:32, height:32, borderRadius:10, display:'flex', alignItems:'center', justifyContent:'center',
                                    background: !pjsipActive ? 'rgba(255,255,255,0.05)' : 'var(--surface2)', 
                                    border: '1px solid var(--border)', 
                                    color: !pjsipActive ? '#4ade80' : '#9ca3af', 
                                    cursor:'pointer'
                                }}
                            >
                                <span className="material-icons-round" style={{fontSize:18}}>healing</span>
                            </button>
                        </div>
                    </div>

                    {/* Log Panel */}
                    <div className="glass" style={{
                        background:'rgba(5,5,12,0.98)',
                        border:'1px solid rgba(139,92,246,0.15)',
                        borderRadius:16, overflow:'hidden'
                    }}>
                        {/* Log header */}
                        <div style={{padding:'10px 16px', borderBottom:'1px solid rgba(255,255,255,0.04)', display:'flex', alignItems:'center', gap:8}}>
                            <div style={{display:'flex', gap:6}}>
                                <div style={{width:10,height:10,borderRadius:'50%',background:'#ef4444'}}/>
                                <div style={{width:10,height:10,borderRadius:'50%',background:'#f59e0b'}}/>
                                <div style={{width:10,height:10,borderRadius:'50%',background:'#22c55e'}}/>
                            </div>
                            <span style={{fontSize:11, color:'#374151', fontFamily:'monospace', marginLeft:8}}>asterisk@pbx ~ pjsip-logger</span>
                            <div style={{marginLeft:'auto', display:'flex', alignItems:'center', gap:6}}>
                                <span style={{width:6,height:6,borderRadius:'50%',background:'#22c55e',animation:autoRefresh?'blink 1.5s infinite':''}} />
                                <span style={{fontSize:10,color:'#374151',fontWeight:600}}>{filteredLines.length} líneas</span>
                            </div>
                        </div>

                        {/* Log Content */}
                        <div style={{padding:'8px 4px', maxHeight:600, overflowY:'auto', fontFamily:'"Fira Code","Courier New",monospace'}}>
                            {filteredLines.length === 0 ? (
                                <div style={{padding:'40px',textAlign:'center',color:'#374151'}}>
                                    <span className="material-icons-round" style={{fontSize:40,display:'block',marginBottom:10}}>inbox</span>
                                    {filter ? `Sin resultados para "${filter}"` : 'Conectando al stream de Asterisk...'}
                                </div>
                            ) : filteredLines.map((line, i) => (
                                <SIPLogLine key={i} line={line} idx={i} />
                            ))}
                            <div ref={logEndRef} />
                        </div>
                    </div>

                    <div style={{marginTop:12, fontSize:10, color:'#374151', display:'flex', alignItems:'center', gap:6}}>
                        <span style={{width:6, height:6, borderRadius:'50%', background:'#22c55e'}} />
                        Mostrando últimos eventos de registro y autenticación SIP/PJSIP en tiempo real.
                        Haz clic en cada línea para expandirla.
                    </div>
                </div>
            )}

            {activeTab === 'pbx' && (
                <ViewConfigPBX />
            )}

            {activeTab === 'branding' && <ViewConfigBranding />}
            {activeTab === 'softphone' && <ViewConfigSoftphone />}
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: CONFIGURACIÓN — Branding (logos, colores, nombres)
// ─────────────────────────────────────────────
function ViewConfigBranding() {
    const [settings, setSettings] = useState(null);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState({});

    useEffect(() => {
        fetch('api/app_settings.php', {credentials:'include'})
            .then(r=>r.json()).then(d => { if (d.status==='ok') setSettings(d.settings || {}); });
    }, []);

    if (!settings) return <div className="rounded-lg border border-border bg-card p-12 text-center text-muted-foreground">Cargando…</div>;

    const set = (key, val) => { setSettings(s => ({...s, [key]: val})); setDirty(d => ({...d, [key]: true})); };

    const save = async () => {
        if (Object.keys(dirty).length === 0) return;
        setSaving(true);
        const payload = {};
        Object.keys(dirty).forEach(k => payload[k] = settings[k]);
        try {
            const r = await fetch('api/app_settings.php', {
                method:'POST', credentials:'include',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const j = await r.json();
            if (j.status === 'ok') {
                setDirty({});
                window.shToast?.('Branding guardado · ' + j.count + ' campos', 'success');
            } else {
                window.shToast?.('Error: ' + (j.message||''), 'destructive');
            }
        } catch(e) { window.shToast?.('Error de red', 'destructive'); }
        setSaving(false);
    };

    const Field = ({label, k, type='text', placeholder=''}) => (
        <div>
            <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>{label}</label>
            {type === 'color' ? (
                <div className="flex items-center gap-2">
                    <input type="color" value={settings[k]||'#11B328'} onChange={e=>set(k, e.target.value)}
                        style={{width:48, height:38, padding:2, borderRadius:8, border:'1px solid var(--border)', background:'var(--background)', cursor:'pointer'}}/>
                    <input type="text" value={settings[k]||''} onChange={e=>set(k, e.target.value)}
                        placeholder="#11B328"
                        className="flex-1 h-9 px-3 rounded-md text-sm border font-mono focus:outline-none focus:ring-2"
                        style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}/>
                </div>
            ) : (
                <input type={type} value={settings[k]||''} onChange={e=>set(k, e.target.value)} placeholder={placeholder}
                    className="w-full h-9 px-3 rounded-md text-sm border focus:outline-none focus:ring-2"
                    style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}/>
            )}
        </div>
    );

    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-card p-5" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-3 mb-1">
                    <span className="material-icons-round" style={{fontSize:24, color:'var(--horizon-green)'}}>palette</span>
                    <div>
                        <h3 className="text-base font-bold" style={{color:'var(--foreground)'}}>Branding del sistema</h3>
                        <p className="text-xs" style={{color:'var(--muted-foreground)'}}>Personalizá nombres, textos y colores que aparecen en login, header y reportes</p>
                    </div>
                    <div className="flex-1"/>
                    {Object.keys(dirty).length > 0 && (
                        <button onClick={save} disabled={saving}
                            className="h-9 px-4 rounded-md text-sm font-medium transition-colors disabled:opacity-50"
                            style={{background:'var(--horizon-green)', color:'#fff'}}>
                            {saving ? 'Guardando…' : `Guardar ${Object.keys(dirty).length} cambio${Object.keys(dirty).length!==1?'s':''}`}
                        </button>
                    )}
                </div>
            </div>

            <div className="rounded-lg border bg-card p-5 space-y-4" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-2 mb-2">
                    <span className="material-icons-round" style={{fontSize:18, color:'var(--primary)'}}>business</span>
                    <h4 className="text-sm font-bold" style={{color:'var(--foreground)'}}>Identidad</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Field label="Nombre de la empresa" k="brand_company_name" placeholder="Horizon Seguridad"/>
                    <Field label="Nombre de la aplicación" k="brand_app_name" placeholder="TeleFlow"/>
                    <Field label="Logo — texto principal" k="brand_logo_text" placeholder="HORIZON"/>
                    <Field label="Logo — subtítulo" k="brand_logo_sub" placeholder="SEGURIDAD"/>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Field label="Título del login (tagline)" k="brand_tagline" placeholder="Centro de monitoreo"/>
                    <Field label="Subtítulo del login" k="brand_subtitle" placeholder="Plataforma unificada…"/>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-5 space-y-4" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-2 mb-2">
                    <span className="material-icons-round" style={{fontSize:18, color:'var(--primary)'}}>color_lens</span>
                    <h4 className="text-sm font-bold" style={{color:'var(--foreground)'}}>Paleta de colores</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Field label="Color primario" k="brand_primary_color" type="color"/>
                    <Field label="Color acento" k="brand_accent_color" type="color"/>
                </div>
                <div className="text-xs px-3 py-2 rounded border" style={{color:'var(--muted-foreground)', background:'var(--secondary)', borderColor:'var(--border)'}}>
                    <span className="material-icons-round mr-1" style={{fontSize:13, verticalAlign:'-2px'}}>info</span>
                    Los cambios de color requieren recargar la página (Ctrl+Shift+R) para aplicarse globalmente.
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: CONFIGURACIÓN — Softphone (defaults WebRTC)
// ─────────────────────────────────────────────
function ViewConfigSoftphone() {
    const [settings, setSettings] = useState(null);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState({});
    const [devices, setDevices] = useState({ audioin: [], audioout: [] });

    useEffect(() => {
        fetch('api/app_settings.php', {credentials:'include'})
            .then(r=>r.json()).then(d => { if (d.status==='ok') setSettings(d.settings || {}); });
        // Enumerar audio devices del browser
        if (navigator.mediaDevices?.enumerateDevices) {
            navigator.mediaDevices.getUserMedia({audio:true}).catch(()=>{}).finally(() => {
                navigator.mediaDevices.enumerateDevices().then(devs => {
                    setDevices({
                        audioin: devs.filter(d => d.kind === 'audioinput'),
                        audioout: devs.filter(d => d.kind === 'audiooutput'),
                    });
                });
            });
        }
    }, []);

    if (!settings) return <div className="rounded-lg border border-border bg-card p-12 text-center text-muted-foreground">Cargando…</div>;

    const set = (k, v) => { setSettings(s => ({...s, [k]: v})); setDirty(d => ({...d, [k]: true})); };

    const save = async () => {
        if (Object.keys(dirty).length === 0) return;
        setSaving(true);
        const payload = {};
        Object.keys(dirty).forEach(k => payload[k] = settings[k]);
        try {
            const r = await fetch('api/app_settings.php', {
                method:'POST', credentials:'include',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const j = await r.json();
            if (j.status === 'ok') { setDirty({}); window.shToast?.('Softphone guardado', 'success'); }
            else window.shToast?.('Error: '+(j.message||''), 'destructive');
        } catch(e) { window.shToast?.('Error de red', 'destructive'); }
        setSaving(false);
    };

    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-card p-5" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-3 mb-1">
                    <span className="material-icons-round" style={{fontSize:24, color:'var(--primary)'}}>phone_in_talk</span>
                    <div>
                        <h3 className="text-base font-bold" style={{color:'var(--foreground)'}}>Softphone WebRTC</h3>
                        <p className="text-xs" style={{color:'var(--muted-foreground)'}}>Configuración por defecto del softphone integrado (codecs, audio, comportamiento)</p>
                    </div>
                    <div className="flex-1"/>
                    {Object.keys(dirty).length > 0 && (
                        <button onClick={save} disabled={saving}
                            className="h-9 px-4 rounded-md text-sm font-medium transition-colors disabled:opacity-50"
                            style={{background:'var(--primary)', color:'var(--primary-foreground)'}}>
                            {saving ? 'Guardando…' : 'Guardar cambios'}
                        </button>
                    )}
                </div>
            </div>

            <div className="rounded-lg border bg-card p-5 space-y-4" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-2 mb-2">
                    <span className="material-icons-round" style={{fontSize:18, color:'var(--primary)'}}>equalizer</span>
                    <h4 className="text-sm font-bold" style={{color:'var(--foreground)'}}>Audio</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Micrófono</label>
                        <select value={settings.softphone_audio_input||''} onChange={e=>set('softphone_audio_input', e.target.value)}
                            className="w-full h-9 px-3 rounded-md text-sm border focus:outline-none focus:ring-2"
                            style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}>
                            <option value="">(default del browser)</option>
                            {devices.audioin.map(d => <option key={d.deviceId} value={d.deviceId}>{d.label || `Audio in ${d.deviceId.substring(0,8)}…`}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Altavoces</label>
                        <select value={settings.softphone_audio_output||''} onChange={e=>set('softphone_audio_output', e.target.value)}
                            className="w-full h-9 px-3 rounded-md text-sm border focus:outline-none focus:ring-2"
                            style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}>
                            <option value="">(default del browser)</option>
                            {devices.audioout.map(d => <option key={d.deviceId} value={d.deviceId}>{d.label || `Audio out ${d.deviceId.substring(0,8)}…`}</option>)}
                        </select>
                    </div>
                </div>
                <div>
                    <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Volumen del ringing ({Math.round((parseFloat(settings.softphone_ringing_volume)||0.7)*100)}%)</label>
                    <input type="range" min="0" max="1" step="0.05" value={parseFloat(settings.softphone_ringing_volume)||0.7}
                        onChange={e=>set('softphone_ringing_volume', e.target.value)}
                        className="w-full" style={{accentColor:'var(--primary)'}}/>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-5 space-y-4" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-2 mb-2">
                    <span className="material-icons-round" style={{fontSize:18, color:'var(--primary)'}}>settings_voice</span>
                    <h4 className="text-sm font-bold" style={{color:'var(--foreground)'}}>Códecs y DTMF</h4>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Códecs preferidos (orden)</label>
                        <input type="text" value={settings.softphone_default_codec||''} onChange={e=>set('softphone_default_codec', e.target.value)}
                            placeholder="opus,PCMA,PCMU"
                            className="w-full h-9 px-3 rounded-md text-sm border font-mono focus:outline-none focus:ring-2"
                            style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}/>
                        <div className="text-xs mt-1" style={{color:'var(--muted-foreground)'}}>Coma-separados: opus, PCMA (alaw), PCMU (ulaw), G722, G729</div>
                    </div>
                    <div>
                        <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Modo DTMF</label>
                        <select value={settings.softphone_dtmf_mode||'rfc2833'} onChange={e=>set('softphone_dtmf_mode', e.target.value)}
                            className="w-full h-9 px-3 rounded-md text-sm border focus:outline-none focus:ring-2"
                            style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}>
                            <option value="rfc2833">RFC 2833 (RTP events) — Recomendado</option>
                            <option value="inband">Inband audio</option>
                            <option value="info">SIP INFO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-5 space-y-4" style={{borderColor:'var(--border)'}}>
                <div className="flex items-center gap-2 mb-2">
                    <span className="material-icons-round" style={{fontSize:18, color:'var(--primary)'}}>tune</span>
                    <h4 className="text-sm font-bold" style={{color:'var(--foreground)'}}>Comportamiento</h4>
                </div>
                <div className="flex items-center justify-between p-3 rounded-md border" style={{borderColor:'var(--border)', background:'var(--secondary)'}}>
                    <div>
                        <div className="text-sm font-semibold" style={{color:'var(--foreground)'}}>Auto-responder llamadas entrantes</div>
                        <div className="text-xs mt-0.5" style={{color:'var(--muted-foreground)'}}>El softphone contesta automáticamente cuando suena (útil para hot-desk fijos)</div>
                    </div>
                    <input type="checkbox" checked={settings.softphone_auto_answer === '1'} onChange={e=>set('softphone_auto_answer', e.target.checked?'1':'0')}
                        style={{width:18, height:18, accentColor:'var(--primary)', cursor:'pointer'}}/>
                </div>
                <div>
                    <label className="block text-xs font-bold mb-1.5 uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>Extensiones SIN softphone (separadas por coma)</label>
                    <input type="text" value={settings.softphone_disabled_extensions||''} onChange={e=>set('softphone_disabled_extensions', e.target.value)}
                        placeholder="ej: 9999, 8001, 5000"
                        className="w-full h-9 px-3 rounded-md text-sm border font-mono focus:outline-none focus:ring-2"
                        style={{borderColor:'var(--input)', background:'var(--background)', color:'var(--foreground)'}}/>
                    <div className="text-xs mt-1" style={{color:'var(--muted-foreground)'}}>Estas extensiones quedan ocultas en el portal de agente (solo teléfono físico).</div>
                </div>
            </div>
        </div>
    );
}

function ViewConfigPBX() {
    const [groups, setGroups] = useState({});
    const [loading, setLoading] = useState(true);
    const [activeGroup, setActiveGroup] = useState('pbx');
    const [edits, setEdits] = useState({});
    const [testResult, setTestResult] = useState(null);
    const [testing, setTesting] = useState(false);

    const groupLabels = {
        pbx: 'PBX',
        database: 'MySQL',
        ami: 'AMI Asterisk',
        webrtc: 'WebRTC / WSS',
    };
    const groupIcons = {
        pbx: 'dns',
        database: 'storage',
        ami: 'bolt',
        webrtc: 'language',
    };

    const load = async () => {
        setLoading(true);
        try {
            const r = await fetch('api/pbx_config.php?action=list', {credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') { setGroups(j.groups); setEdits({}); }
        } catch(e) { console.error(e); }
        setLoading(false);
    };
    useEffect(()=>{ load(); }, []);

    const setVal = (key, val) => setEdits(e => ({...e, [key]: val}));
    const valOf = (s) => edits[s.config_key] !== undefined ? edits[s.config_key] : s.config_value;

    const save = async () => {
        const settings = Object.entries(edits).map(([key, value]) => ({key, value}));
        if (settings.length === 0) return;
        const r = await fetch('api/pbx_config.php?action=save', {
            method:'POST', credentials:'include',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({settings}),
        });
        const j = await r.json();
        if (j.status === 'ok') { load(); }
    };

    const testConn = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            const r = await fetch('api/pbx_config.php?action=test', {credentials:'include'});
            setTestResult(await r.json());
        } catch(e) { setTestResult({status:'error', message:e.message}); }
        setTesting(false);
    };

    if (loading) return <div className="anim-fadeup" style={{padding:40,textAlign:'center',color:'var(--muted)'}}><span className="material-icons-round" style={{fontSize:32,animation:'spin 1s linear infinite'}}>autorenew</span><div style={{marginTop:12,fontSize:13}}>Cargando configuración...</div></div>;

    const items = groups[activeGroup] || [];
    const dirty = Object.keys(edits).length;

    return (
        <div className="anim-fadeup">
            <div style={{display:'flex',gap:6,marginBottom:18,flexWrap:'wrap',background:'var(--surface2)',padding:4,borderRadius:14,width:'fit-content'}}>
                {Object.keys(groupLabels).map(g => (
                    <button key={g} onClick={()=>setActiveGroup(g)} style={{padding:'8px 16px',borderRadius:10,border:'none',background:activeGroup===g?'var(--surface)':'transparent',color:activeGroup===g?'var(--accent)':'var(--muted)',fontWeight:700,fontSize:12,cursor:'pointer',transition:'all .25s',display:'flex',alignItems:'center',gap:6}}>
                        <span className="material-icons-round" style={{fontSize:16}}>{groupIcons[g]}</span>
                        {groupLabels[g]}
                    </button>
                ))}
            </div>

            <div className="glass" style={{padding:24,borderRadius:18,marginBottom:14}}>
                {items.map(s => (
                    <div key={s.config_key} style={{marginBottom:18}}>
                        <label style={{display:'flex',alignItems:'center',gap:8,marginBottom:6,fontSize:12,fontWeight:700,color:'var(--text)'}}>
                            <span style={{fontFamily:'monospace'}}>{s.config_key}</span>
                            {s.is_secret==1 && <span style={{padding:'2px 7px',borderRadius:6,fontSize:10,fontWeight:700,background:'rgba(245,158,11,0.15)',color:'#fbbf24'}}>SECRET</span>}
                        </label>
                        <input
                            className="input-tf"
                            style={{padding:'10px 14px',borderRadius:10,fontSize:13}}
                            type={s.is_secret==1 ? 'password' : 'text'}
                            value={valOf(s)}
                            onChange={e=>setVal(s.config_key, e.target.value)}
                            placeholder={s.description || ''}
                        />
                        <div style={{fontSize:11,color:'var(--muted)',marginTop:4}}>{s.description}</div>
                    </div>
                ))}
            </div>

            <div style={{display:'flex',gap:10,alignItems:'center',marginBottom:14}}>
                <button onClick={testConn} disabled={testing} style={{padding:'10px 18px',borderRadius:10,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--text)',cursor:testing?'wait':'pointer',fontWeight:600,display:'flex',alignItems:'center',gap:6}}>
                    <span className="material-icons-round" style={{fontSize:18,animation:testing?'spin 1s linear infinite':''}}>{testing?'autorenew':'cable'}</span>
                    {testing ? 'Probando...' : 'Probar Conexión'}
                </button>
                <button onClick={save} disabled={!dirty} style={{padding:'10px 22px',borderRadius:10,border:'none',background: dirty ? 'linear-gradient(135deg,#8b5cf6,#6d28d9)' : 'var(--surface2)', color:dirty?'white':'var(--muted)',cursor:dirty?'pointer':'not-allowed',fontWeight:700,opacity:dirty?1:0.6}}>
                    <span className="material-icons-round" style={{fontSize:16,marginRight:6,verticalAlign:'middle'}}>save</span>
                    Guardar {dirty>0 && `(${dirty})`}
                </button>
            </div>

            {testResult && testResult.tests && (
                <div className="glass anim-fadeup" style={{padding:16,borderRadius:14}}>
                    {Object.entries(testResult.tests).map(([name, t]) => (
                        <div key={name} style={{padding:'10px 12px',borderRadius:10,marginBottom:6,background: t.ok ? 'rgba(34,197,94,0.08)' : 'rgba(239,68,68,0.08)',border: t.ok ? '1px solid rgba(34,197,94,0.25)' : '1px solid rgba(239,68,68,0.25)',display:'flex',alignItems:'center',gap:10}}>
                            <span className="material-icons-round" style={{color: t.ok ? '#4ade80' : '#f87171', fontSize:20}}>{t.ok ? 'check_circle' : 'error'}</span>
                            <div>
                                <div style={{fontWeight:700,fontSize:13,color: t.ok ? '#4ade80' : '#f87171'}}>{name.toUpperCase()}</div>
                                <div style={{fontSize:12,color:'var(--muted)'}}>{t.detail}</div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// ─────────────────────────────────────────────
// CALL CENTER — Consola del Agente (Horizon)
// ─────────────────────────────────────────────
function ViewCallCenter({ user, onLogout, data }) {
    const [status, setStatus] = useState(null);
    const [pauseTypes, setPauseTypes] = useState([]);
    const [showPauseModal, setShowPauseModal] = useState(false);
    const [callHistory, setCallHistory] = useState([]);
    const [tick, setTick] = useState(0);
    const isAgent = user?.role === 'agent';

    // Onboarding para admin
    if (!isAgent) {
        return (
            <div className="content-area view-enter">
                <div className="glass" style={{padding:32,borderRadius:18,textAlign:'center',maxWidth:680,margin:'40px auto'}}>
                    <div style={{width:84,height:84,borderRadius:'50%',background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',margin:'0 auto 20px',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{fontSize:42,color:'#fff'}}>headset_mic</span>
                    </div>
                    <h2 style={{fontSize:22,fontWeight:900,marginBottom:10}}>Mi Consola</h2>
                    <p style={{fontSize:13,color:'var(--muted)',marginBottom:18,lineHeight:1.5}}>
                        Esta es la <strong>consola del agente</strong>. Para usarla, salí (logout) y volvé a entrar seleccionando "Agente" con tu número y password.
                    </p>
                    <p style={{fontSize:11,color:'var(--muted)'}}>Estás logueado como <strong>{user?.name||'admin'}</strong> ({user?.role||'admin'}). La administración está en <strong>Hotdesking</strong>.</p>
                </div>
            </div>
        );
    }

    const loadStatus = async () => {
        try {
            const r = await fetch('api/agent.php?action=status', {credentials:'include'});
            setStatus(await r.json());
        } catch(e) {}
    };
    const loadPauseTypes = async () => {
        try {
            const r = await fetch('api/agent.php?action=pause_types', {credentials:'include'});
            const j = await r.json();
            setPauseTypes(j.types || []);
        } catch(e) {}
    };
    const loadHistory = async () => {
        try {
            const ext = user?.agent?.callback?.replace(/^\w+\//,'') || '';
            if (!ext) return;
            const today = new Date().toISOString().slice(0,10);
            const r = await fetch(`api/index.php?action=get_cdr&from=${today}&to=${today}&src=${ext}&limit=20`, {credentials:'include'});
            const j = await r.json();
            if (j.success) setCallHistory(j.rows || []);
        } catch(e) {}
    };

    useEffect(() => { loadStatus(); loadPauseTypes(); loadHistory(); }, []);
    useEffect(() => { const t = setInterval(loadStatus, 3000); return () => clearInterval(t); }, []);
    useEffect(() => { const t = setInterval(() => setTick(k=>k+1), 1000); return () => clearInterval(t); }, []);
    useEffect(() => { const t = setInterval(loadHistory, 30000); return () => clearInterval(t); }, []);

    const doPause = async (code) => {
        const fd = new FormData(); fd.append('pause_type_code', code);
        await fetch('api/agent.php?action=pause', {method:'POST', body:fd, credentials:'include'});
        setShowPauseModal(false);
        loadStatus();
    };
    const doUnpause = async () => {
        await fetch('api/agent.php?action=unpause', {method:'POST', credentials:'include'});
        loadStatus();
    };
    const doLogout = async () => {
        if (!confirm('¿Cerrar sesión y salir de las colas?')) return;
        await fetch('api/agent.php?action=logout', {method:'POST', credentials:'include'});
        onLogout?.();
    };

    // Llamada actual
    const liveCalls = data?.pbx?.live_calls || [];
    const myExt = (user?.agent?.callback || '').replace(/^\w+\//, '');
    const myCall = myExt ? liveCalls.find(c => String(c.ext) === String(myExt) || String(c.dest) === String(myExt)) : null;

    const fmtTime = (s) => { if (!s) return '00:00'; const m = Math.floor(s/60), sc = s%60; const h = Math.floor(m/60); return h>0 ? `${h}:${String(m%60).padStart(2,'0')}:${String(sc).padStart(2,'0')}` : `${String(m).padStart(2,'0')}:${String(sc).padStart(2,'0')}`; };

    const sessionSec = status?.session?.login_time ? Math.floor((Date.now() - new Date(status.session.login_time).getTime())/1000) : 0;
    const pauseSec = status?.pause?.started_at ? Math.floor((Date.now() - new Date(status.pause.started_at).getTime())/1000) : 0;

    const isPaused = !!status?.pause;
    const isInCall = !!myCall;
    const stateLabel = isInCall ? 'EN LLAMADA' : (isPaused ? 'EN PAUSA' : 'DISPONIBLE');
    const stateColor = isInCall ? '#ef4444' : (isPaused ? '#f59e0b' : '#22c55e');

    return (
        <div className="content-area view-enter">
            {/* HERO: avatar + nombre + estado grande */}
            <div className="glass" style={{padding:0,borderRadius:18,marginBottom:14,overflow:'hidden',position:'relative'}}>
                <div style={{height:64,background:`linear-gradient(135deg,${stateColor}55,${stateColor}11)`}}/>
                <div style={{padding:'0 22px 18px',display:'flex',alignItems:'flex-end',gap:14,marginTop:-32}}>
                    <div style={{width:70,height:70,borderRadius:'50%',background:`linear-gradient(135deg,${stateColor},${stateColor}aa)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:24,fontWeight:900,border:'4px solid var(--surface)',boxShadow:`0 4px 14px ${stateColor}66`,flexShrink:0,position:'relative'}}>
                        {(user?.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                        {isInCall && <span style={{position:'absolute',bottom:-2,right:-2,width:18,height:18,borderRadius:'50%',background:'#ef4444',border:'3px solid var(--surface)',animation:'pulse-ring 1.5s infinite'}}/>}
                    </div>
                    <div style={{flex:1,paddingTop:30}}>
                        <h1 style={{fontSize:22,fontWeight:900,letterSpacing:'-0.5px'}}>{user?.name}</h1>
                        <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace',fontWeight:700,marginTop:2}}>
                            Agente #{user?.agent?.number} · {user?.agent?.callback} · Sesión {fmtTime(sessionSec)}
                        </div>
                    </div>
                    <div style={{padding:'10px 18px',borderRadius:12,background:`${stateColor}22`,border:`2px solid ${stateColor}66`,display:'flex',alignItems:'center',gap:10}}>
                        <span style={{width:10,height:10,borderRadius:'50%',background:stateColor,boxShadow:`0 0 12px ${stateColor}`,animation:isInCall?'pulse 1s infinite':'none'}}/>
                        <span style={{fontSize:13,fontWeight:900,color:stateColor,letterSpacing:'.05em'}}>{stateLabel}</span>
                    </div>
                </div>
            </div>

            {/* Llamada actual prominente */}
            {isInCall && (
                <div className="glass" style={{padding:18,borderRadius:14,marginBottom:14,border:'2px solid #ef4444',boxShadow:'0 0 24px rgba(239,68,68,0.25)',background:'linear-gradient(135deg,rgba(239,68,68,0.08),transparent 60%)'}}>
                    <div style={{display:'flex',alignItems:'center',gap:14}}>
                        <div style={{width:54,height:54,borderRadius:'50%',background:'linear-gradient(135deg,#ef4444,#dc2626)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',animation:'phone-shake 0.6s ease-in-out infinite'}}>
                            <span className="material-icons-round" style={{fontSize:28}}>phone_in_talk</span>
                        </div>
                        <div style={{flex:1}}>
                            <div style={{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.05em'}}>Llamada en curso</div>
                            <div style={{fontSize:18,fontWeight:900,color:'var(--text)',marginTop:2}}>{myCall.callerid || myCall.from_ext || myCall.ext}</div>
                            <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace'}}>{myCall.channel}</div>
                        </div>
                        <div style={{textAlign:'right'}}>
                            <div style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>Duración</div>
                            <div style={{fontSize:24,fontWeight:900,fontFamily:'monospace',color:'#22c55e'}}>{myCall.duration||'00:00'}</div>
                        </div>
                    </div>
                </div>
            )}

            {/* Acciones grandes: Pausa o Despausar */}
            {!isInCall && (
                isPaused ? (
                    <div className="glass" style={{padding:24,borderRadius:14,marginBottom:14,background:`linear-gradient(135deg,${status.pause.color||'#f59e0b'}15,transparent 60%)`,border:`1px solid ${status.pause.color||'#f59e0b'}44`}}>
                        <div style={{display:'flex',alignItems:'center',gap:18,flexWrap:'wrap'}}>
                            <div style={{width:54,height:54,borderRadius:14,background:`linear-gradient(135deg,${status.pause.color||'#f59e0b'},${status.pause.color||'#f59e0b'}aa)`,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:28,color:'#fff'}}>pause_circle</span>
                            </div>
                            <div style={{flex:1,minWidth:200}}>
                                <div style={{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase'}}>En Pausa</div>
                                <h3 style={{fontSize:22,fontWeight:900,color:status.pause.color||'#f59e0b'}}>{status.pause.label||'Pausa'}</h3>
                                <div style={{fontSize:36,fontWeight:900,fontFamily:'monospace',color:'var(--text)',marginTop:6}}>{fmtTime(pauseSec)}</div>
                            </div>
                            <button onClick={doUnpause} style={{padding:'14px 22px',borderRadius:12,border:'none',background:'linear-gradient(135deg,#22c55e,#16a34a)',color:'#fff',fontWeight:900,fontSize:13,cursor:'pointer',display:'flex',alignItems:'center',gap:8,boxShadow:'0 8px 24px rgba(34,197,94,0.3)'}}>
                                <span className="material-icons-round">play_arrow</span>Volver a atender
                            </button>
                        </div>
                    </div>
                ) : (
                    <div style={{display:'flex',gap:10,marginBottom:14,flexWrap:'wrap'}}>
                        <button onClick={()=>setShowPauseModal(true)} style={{flex:1,minWidth:200,padding:'18px',borderRadius:14,border:'1px solid rgba(245,158,11,0.4)',background:'rgba(245,158,11,0.08)',color:'#f59e0b',fontWeight:900,fontSize:14,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:10}}>
                            <span className="material-icons-round" style={{fontSize:24}}>pause_circle</span>
                            Iniciar pausa
                        </button>
                        <button onClick={doLogout} style={{flex:1,minWidth:200,padding:'18px',borderRadius:14,border:'1px solid rgba(239,68,68,0.4)',background:'rgba(239,68,68,0.08)',color:'#ef4444',fontWeight:900,fontSize:14,cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:10}}>
                            <span className="material-icons-round" style={{fontSize:24}}>logout</span>
                            Cerrar sesión
                        </button>
                    </div>
                )
            )}

            {/* Stats sesión */}
            <div className="grid grid-cols-4 gap-3 mb-4">
                <div className="glass" style={{padding:14,borderRadius:12,textAlign:'center'}}>
                    <div style={{fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>Llamadas</div>
                    <div style={{fontSize:24,fontWeight:900,color:'var(--text)'}}>{status?.session?.total_calls||0}</div>
                </div>
                <div className="glass" style={{padding:14,borderRadius:12,textAlign:'center'}}>
                    <div style={{fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>T. en llamada</div>
                    <div style={{fontSize:18,fontWeight:900,fontFamily:'monospace',color:'#22c55e'}}>{fmtTime(status?.session?.total_talk_time||0)}</div>
                </div>
                <div className="glass" style={{padding:14,borderRadius:12,textAlign:'center'}}>
                    <div style={{fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>T. en pausa</div>
                    <div style={{fontSize:18,fontWeight:900,fontFamily:'monospace',color:'#f59e0b'}}>{fmtTime(status?.session?.total_pause_time||0)}</div>
                </div>
                <div className="glass" style={{padding:14,borderRadius:12,textAlign:'center'}}>
                    <div style={{fontSize:9,fontWeight:800,color:'var(--muted)',textTransform:'uppercase'}}>AHT promedio</div>
                    <div style={{fontSize:18,fontWeight:900,fontFamily:'monospace',color:'#3b82f6'}}>
                        {status?.session?.total_calls>0 ? fmtTime(Math.round((status.session.total_talk_time||0)/status.session.total_calls)) : '—'}
                    </div>
                </div>
            </div>

            {/* Histórico de llamadas */}
            <div className="glass" style={{padding:0,borderRadius:14,overflow:'hidden'}}>
                <div style={{padding:'14px 18px',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:10}}>
                    <span className="material-icons-round" style={{fontSize:18,color:'#3b82f6'}}>history</span>
                    <h3 style={{fontSize:13,fontWeight:800,color:'var(--text)'}}>Mis llamadas hoy</h3>
                    <span style={{fontSize:11,color:'var(--muted)',marginLeft:'auto'}}>{callHistory.length} registros</span>
                </div>
                <table className="tf-table">
                    <thead><tr><th>Hora</th><th>Origen</th><th>Destino</th><th>Duración</th><th>Estado</th></tr></thead>
                    <tbody>
                        {callHistory.slice(0,15).map((r,i) => (
                            <tr key={i}>
                                <td style={{fontSize:11,fontFamily:'monospace'}}>{(r.calldate||'').slice(11,16)}</td>
                                <td style={{fontSize:12}}>{r.src}</td>
                                <td style={{fontSize:12}}>{r.dst}</td>
                                <td style={{fontFamily:'monospace',fontSize:11}}>{r.billsec ? fmtTime(r.billsec) : '—'}</td>
                                <td><span style={{fontSize:10,padding:'2px 7px',borderRadius:4,background:r.disposition==='ANSWERED'?'rgba(34,197,94,0.15)':'rgba(107,114,128,0.15)',color:r.disposition==='ANSWERED'?'#22c55e':'var(--muted)',fontWeight:700}}>{r.disposition}</span></td>
                            </tr>
                        ))}
                        {callHistory.length===0 && <tr><td colSpan={5} style={{textAlign:'center',padding:24,color:'var(--muted)',fontSize:11}}>Sin llamadas todavía hoy</td></tr>}
                    </tbody>
                </table>
            </div>

            {/* Modal selector de pausa */}
            {showPauseModal && (
                <div onClick={()=>setShowPauseModal(false)} className="tf-modal-overlay">
                    <div onClick={e=>e.stopPropagation()} style={{background:'var(--surface)',padding:24,borderRadius:18,width:540,maxWidth:'92%',border:'1px solid var(--border)'}}>
                        <h2 style={{fontSize:18,fontWeight:900,marginBottom:6}}>Iniciar pausa</h2>
                        <p style={{fontSize:11,color:'var(--muted)',marginBottom:16}}>Seleccioná el motivo de la pausa. Quedás fuera de las colas hasta que vuelvas.</p>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:8}}>
                            {pauseTypes.map(pt => (
                                <button key={pt.code} onClick={()=>doPause(pt.code)} style={{padding:'14px',borderRadius:12,border:`1px solid ${pt.color||'#f59e0b'}55`,background:`${pt.color||'#f59e0b'}11`,cursor:'pointer',textAlign:'left',transition:'all 0.15s'}} onMouseEnter={e=>e.currentTarget.style.background=`${pt.color||'#f59e0b'}22`} onMouseLeave={e=>e.currentTarget.style.background=`${pt.color||'#f59e0b'}11`}>
                                    <div style={{display:'flex',alignItems:'center',gap:10}}>
                                        <div style={{width:34,height:34,borderRadius:10,background:pt.color||'#f59e0b',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                            <span className="material-icons-round" style={{color:'#fff',fontSize:18}}>{pt.code==='LUNCH'?'restaurant':(pt.code==='BREAK'?'free_breakfast':(pt.code==='BATHROOM'?'wc':(pt.code==='TRAINING'?'school':(pt.code==='MEETING'?'groups':'person'))))}</span>
                                        </div>
                                        <div style={{flex:1,minWidth:0}}>
                                            <div style={{fontSize:13,fontWeight:800,color:'var(--text)'}}>{pt.label}</div>
                                            <div style={{fontSize:10,color:'var(--muted)'}}>Máx {pt.max_duration_min} min · {pt.is_paid?'Pagada':'No pagada'}</div>
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                        <div style={{textAlign:'center',marginTop:16}}>
                            <button onClick={()=>setShowPauseModal(false)} style={{padding:'8px 18px',borderRadius:9,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--muted)',fontWeight:700,fontSize:12,cursor:'pointer'}}>Cancelar</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
function ViewHotdesking({ data, toast }) {
    const [agents, setAgents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('');
    const [editing, setEditing] = useState(null);
    const [statusFilter, setStatusFilter] = useState('all'); // all|logged|available|busy|offline
    const [viewMode, setViewMode] = useState((localStorage.getItem('tf_hd_view') === 'grid' ? 'wallboard' : (localStorage.getItem('tf_hd_view') || 'wallboard')));
    const [showWizard, setShowWizard] = useState(false);
    const [agentQueues, setAgentQueues] = useState({});
    const [loginAgentTarget, setLoginAgentTarget] = useState(null);
    const [tick, setTick] = useState(0);

    const liveCalls = data?.pbx?.live_calls || [];
    const exts = data?.pbx?.extensions || [];

    const load = async () => {
        try {
            const r = await fetch('api/hotdesking.php?action=list', {credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') setAgents(j.agents || []);
        } catch(e) {}
        setLoading(false);
    };
    const loadQueues = async () => {
        try {
            const r = await fetch('api/hotdesking.php?action=agent_queues', {credentials:'include'});
            const j = await r.json();
            if (j.status === 'ok') setAgentQueues(j.by_agent || {});
        } catch(e) {}
    };
    useEffect(()=>{
        load(); loadQueues();
        const t = setInterval(()=>{ load(); loadQueues(); }, 2500);
        return () => clearInterval(t);
    }, []);
    useEffect(()=>{
        const openLogin = (e) => setLoginAgentTarget(e.detail);
        window.addEventListener('tf-open-login', openLogin);
        return () => window.removeEventListener('tf-open-login', openLogin);
    }, []);
    useEffect(()=>{
        const h = () => { console.log('[HD] tf-queues-refresh → reload'); load(); loadQueues(); };
        window.addEventListener('tf-queues-refresh', h);
        // Forzar refresh ALSO al recibir agent_login/agent_logout vía socket directo
        const sock = window._tfSocket;
        const onLogin  = (ev) => { console.log('[HD] socket agent_login', ev); load(); loadQueues(); };
        const onLogout = (ev) => { console.log('[HD] socket agent_logout', ev); load(); loadQueues(); };
        if (sock) {
            sock.on('agent_login', onLogin);
            sock.on('agent_logout', onLogout);
            sock.on('tf-realtime-refresh', h);
        }
        return () => {
            window.removeEventListener('tf-queues-refresh', h);
            if (sock) {
                sock.off('agent_login', onLogin);
                sock.off('agent_logout', onLogout);
                sock.off('tf-realtime-refresh', h);
            }
        };
    }, []);
    useEffect(()=>{ const t=setInterval(()=>setTick(k=>k+1), 1000); return ()=>clearInterval(t); }, []);

    const enriched = agents.map(a => {
        const inCall = a.extension && liveCalls.some(c => String(c.ext) === String(a.extension) || String(c.dest) === String(a.extension));
        const extInfo = a.extension ? exts.find(x => x.ext === a.extension) : null;
        return { ...a, in_call: !!inCall, ip: extInfo?.ip, rtt: extInfo?.rtt, ext_name: extInfo?.name, queues: agentQueues[a.number] || a.queues || [] };
    });

    const totalLogged = enriched.filter(a => a.logged_in).length;
    const totalAvail  = enriched.filter(a => a.logged_in && !a.in_call && !a.paused).length;
    const totalBusy   = enriched.filter(a => a.in_call).length;
    const totalPaused = enriched.filter(a => a.paused).length;
    const totalOff    = enriched.filter(a => !a.logged_in).length;

    const filtered = enriched.filter(a => {
        if (statusFilter==='logged' && !a.logged_in) return false;
        if (statusFilter==='available' && (!a.logged_in || a.in_call || a.paused)) return false;
        if (statusFilter==='busy' && !a.in_call) return false;
        if (statusFilter==='paused' && !a.paused) return false;
        if (statusFilter==='offline' && a.logged_in) return false;
        if (!filter) return true;
        const q = filter.toLowerCase();
        return (a.number||'').includes(filter) || (a.name||'').toLowerCase().includes(q) || (a.extension||'').includes(filter);
    });

    const remove = async (a) => {
        if (!confirm(`Eliminar agente #${a.number} ${a.name}?`)) return;
        const fd = new FormData(); fd.append('id', a.id);
        const r = await fetch('api/hotdesking.php?action=delete', {method:'POST',body:fd,credentials:'include'});
        const j = await r.json();
        if (j.status==='ok') { toast('Agente eliminado','success'); load(); } else toast(j.message||'Error','error');
    };
    const [logoutTarget, setLogoutTarget] = useState(null);
    const [logoutBusy, setLogoutBusy] = useState(false);
    const logoutAgent = (a) => setLogoutTarget(a);
    const confirmLogout = async () => {
        const a = logoutTarget; if (!a) return;
        setLogoutBusy(true);
        const fd = new FormData(); fd.append('agent_number', a.number); if (a.extension) fd.append('extension', a.extension);
        try {
            const r = await fetch('api/hotdesking.php?action=logout_agent', {method:'POST',body:fd,credentials:'include'});
            const j = await r.json();
            if (j.status==='ok') { toast(`${a.name} desconectado`,'success'); load(); loadQueues(); }
            else toast(j.message||'Error','error');
        } catch(e) { toast('Error de red','error'); }
        setLogoutBusy(false);
        setLogoutTarget(null);
    };
    const save = async (form) => {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k, v));
        const isNew = !form.id;
        const r = await fetch('api/hotdesking.php?action='+(isNew?'create':'update'), {method:'POST',body:fd,credentials:'include'});
        const j = await r.json();
        if (j.status==='ok') { toast(isNew?'Agente creado':'Actualizado','success'); setEditing(null); load(); } else toast(j.message||'Error','error');
    };

    const StatCard = ({label, value, color, icon, sub, gradient}) => (
        <div className="glass" style={{padding:'14px 16px',borderRadius:14,position:'relative',overflow:'hidden',border:`1px solid ${color}33`,background:`linear-gradient(135deg,${color}0a,transparent 70%),var(--surface)`,minWidth:160,flex:1}}>
            <div style={{position:'absolute',top:-8,right:-8,width:60,height:60,borderRadius:'50%',background:`radial-gradient(circle, ${color}33, transparent 70%)`}}/>
            <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:6,position:'relative'}}>
                <div style={{width:34,height:34,borderRadius:10,background:`linear-gradient(135deg,${color},${color}aa)`,display:'flex',alignItems:'center',justifyContent:'center',boxShadow:`0 4px 14px ${color}66`}}>
                    <span className="material-icons-round" style={{color:'#fff',fontSize:18}}>{icon}</span>
                </div>
                <div style={{fontSize:10,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em'}}>{label}</div>
            </div>
            <div style={{display:'flex',alignItems:'baseline',gap:6}}>
                <div style={{fontSize:30,fontWeight:900,color,lineHeight:1,letterSpacing:'-1px'}}>{value}</div>
                {sub && <div style={{fontSize:11,color:'var(--muted)',fontWeight:700}}>{sub}</div>}
            </div>
        </div>
    );

    const FilterChip = ({value, label, count, color}) => (
        <button onClick={()=>setStatusFilter(value)} style={{padding:'7px 14px',borderRadius:10,border:`1px solid ${statusFilter===value?color:'var(--border)'}`,background:statusFilter===value?`${color}22`:'transparent',color:statusFilter===value?color:'var(--muted)',fontWeight:800,fontSize:11,cursor:'pointer',display:'flex',alignItems:'center',gap:6,transition:'all 0.2s'}}>
            <span style={{width:7,height:7,borderRadius:'50%',background:color,boxShadow:statusFilter===value?`0 0 8px ${color}`:'none'}}/>
            {label} <span style={{fontFamily:'monospace',fontWeight:900,marginLeft:4,padding:'1px 7px',borderRadius:6,background:statusFilter===value?'rgba(255,255,255,0.06)':'rgba(255,255,255,0.04)'}}>{count}</span>
        </button>
    );

    if (loading) {
        return (
            <div className="content-area view-enter">
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(300px,1fr))',gap:14}}>
                    {Array.from({length:8}).map((_,i)=>(
                        <div key={i} className="glass animate-pulse" style={{padding:18,borderRadius:14,height:120,opacity:0.5}}>
                            <div style={{width:48,height:48,borderRadius:'50%',background:'rgba(255,255,255,0.08)',marginBottom:10}}/>
                            <div style={{width:'70%',height:10,background:'rgba(255,255,255,0.08)',borderRadius:4}}/>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="content-area view-enter">
            <PageActions>
                <FilterChip value="all" label="Todos" count={agents.length} color="#8b5cf6"/>
                <FilterChip value="available" label="Disponibles" count={totalAvail} color="#22c55e"/>
                <FilterChip value="busy" label="En Llamada" count={totalBusy} color="#ef4444"/>
                <FilterChip value="paused" label="En Pausa" count={totalPaused} color="#f59e0b"/>
                <FilterChip value="logged" label="Logueados" count={totalLogged} color="#3b82f6"/>
                <FilterChip value="offline" label="Offline" count={totalOff} color="#6b7280"/>
                <div style={{position:'relative',width:220}}>
                    <span className="material-icons-round" style={{position:'absolute',left:10,top:'50%',transform:'translateY(-50%)',fontSize:16,color:'var(--muted)'}}>search</span>
                    <input className="input-tf" placeholder="Buscar nombre, número o ext..." value={filter} onChange={e=>setFilter(e.target.value)} style={{padding:'8px 10px 8px 32px',borderRadius:9,fontSize:12,width:'100%'}}/>
                </div>
                <div style={{display:'flex',gap:4,padding:3,background:'var(--surface2)',borderRadius:9,border:'1px solid var(--border)'}}>
                    {[
                        {v:'wallboard',i:'dashboard',t:'Wallboard'},
                        {v:'table',i:'table_rows',t:'Tabla'}
                    ].map(m=>(
                        <button key={m.v} title={m.t} onClick={()=>{setViewMode(m.v);try{localStorage.setItem('tf_hd_view',m.v);}catch(e){}}} style={{padding:'5px 10px',borderRadius:7,border:'none',cursor:'pointer',background:viewMode===m.v?'rgba(139,92,246,0.25)':'transparent',color:viewMode===m.v?'#c4b5fd':'var(--muted)'}}>
                            <span className="material-icons-round" style={{fontSize:15}}>{m.i}</span>
                        </button>
                    ))}
                </div>

                <button onClick={()=>setEditing('new')} className="btn-primary" style={{padding:'8px 14px',borderRadius:9,fontSize:12,fontWeight:800,display:'flex',alignItems:'center',gap:5}}>
                    <span className="material-icons-round" style={{fontSize:16}}>person_add</span>Nuevo Agente
                </button>
            </PageActions>

            {/* WALLBOARD VIEW (default) — layout 2 columnas: offline izq, logueados der 4 filas */}
            {viewMode==='wallboard' && (() => {
                const loggedAgents  = filtered.filter(a => a.logged_in);
                const offlineAgents = filtered.filter(a => !a.logged_in);

                const isAgentRinging = (a) => {
                    if (!liveCalls?.length) return false;
                    const myQueues = (a.queues || []).map(q => String(q.queue || q));
                    return liveCalls.some(c => {
                        const ringing = /Ring/i.test(c.state || '');
                        if (!ringing) return false;
                        return String(c.ext) === String(a.extension) ||
                               String(c.dest) === String(a.extension) ||
                               myQueues.includes(String(c.dest));
                    });
                };

                const avatarFor = (a) => {
                    const ext = exts.find(e => e.ext === a.extension);
                    if (ext?.avatar && !ext.avatar.includes('ui-avatars')) return ext.avatar;
                    const name = encodeURIComponent(a.name || a.number || 'A');
                    return `https://ui-avatars.com/api/?name=${name}&background=11B328&color=fff&size=80&bold=true&format=svg`;
                };

                // Hangup directo (con confirmación implícita por ser acción única)
                const doHangup = async (a) => {
                    if (!a.extension) return;
                    const fd = new FormData(); fd.append('ext', a.extension);
                    try {
                        const r = await fetch('api/hotdesking.php?action=hangup_call', {method:'POST', body:fd, credentials:'include'});
                        const j = await r.json();
                        if (j.status === 'ok') toast?.(`Llamada cortada (${j.count} canal${j.count!==1?'es':''})`, 'success');
                        else toast?.(j.message||'Error', 'error');
                    } catch(e) { toast?.('Error de red', 'error'); }
                };

                const renderLogged = (a) => {
                    const ringing = !a.in_call && !a.paused && isAgentRinging(a);
                    const sc = a.in_call ? '#ef4444' : (a.paused ? (a.pause_color || '#f59e0b') : (ringing ? '#ef4444' : '#11B328'));
                    const lbl = a.in_call ? 'EN LLAMADA' : (a.paused ? `EN PAUSA · ${a.pause_label || a.pause_type_code}` : (ringing ? 'LLAMADA ENTRANTE' : 'DISPONIBLE'));
                    const myCall = a.in_call ? liveCalls.find(c => String(c.ext)===String(a.extension) || String(c.dest)===String(a.extension)) : null;
                    const pauseDur = a.paused ? tfFmtSecs((a.pause_seconds || 0) + Math.floor((Date.now() - (window._tfPauseTickT0||(window._tfPauseTickT0=Date.now())))/1000)) : null;
                    return (
                        <div
                            key={a.id}
                            onClick={()=>setEditing(a)}
                            className={cn(
                                "rounded-xl border bg-card text-card-foreground overflow-hidden cursor-pointer transition-all hover:bg-muted/40",
                                ringing && "hzn-ringing"
                            )}
                            style={{borderColor: a.in_call || ringing ? '#ef4444' : 'var(--border)'}}
                        >
                            <div style={{height:3, background:sc}}/>
                            <div style={{padding:'12px', display:'flex', alignItems:'center', gap:12}}>
                                {/* Avatar — más chico en layout horizontal */}
                                <div style={{position:'relative', width:52, height:52, flexShrink:0}}>
                                    <img src={avatarFor(a)} alt={a.name}
                                        onError={(e)=>{e.target.style.display='none'; const n=e.target.nextSibling; if(n) n.style.display='flex';}}
                                        style={{width:52, height:52, borderRadius:'50%', objectFit:'cover', display:'block', border:`2px solid ${sc}`, boxShadow:`0 4px 12px ${sc}55`}}/>
                                    <div style={{display:'none', width:52, height:52, borderRadius:'50%', background:`linear-gradient(135deg, ${sc}, ${sc}aa)`, alignItems:'center', justifyContent:'center', color:'#fff', fontSize:14, fontWeight:900, border:`2px solid ${sc}`}}>
                                        {(a.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                                    </div>
                                    <span style={{position:'absolute', bottom:-1, right:-1, width:14, height:14, borderRadius:'50%', background:sc, border:'3px solid var(--card)', animation:a.in_call||ringing?'pulse-ring 1.5s infinite':''}}/>
                                </div>

                                {/* Info principal */}
                                <div style={{flex:1, minWidth:0}}>
                                    <div style={{display:'flex', alignItems:'baseline', gap:6, marginBottom:2}}>
                                        <span style={{fontSize:18, fontWeight:900, color:'var(--horizon-green)', lineHeight:1, fontFamily:'monospace', letterSpacing:'-.3px'}}>#{a.number}</span>
                                        {a.extension && <span style={{fontSize:10, color:'var(--muted)', fontFamily:'monospace', fontWeight:700}}>ext {a.extension}</span>}
                                    </div>
                                    <div style={{fontSize:12, fontWeight:700, color:'var(--text)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', marginBottom:4}}>{a.name}</div>
                                    <div style={{display:'flex', flexWrap:'wrap', gap:3, alignItems:'center'}}>
                                        <span style={{fontSize:9, fontWeight:900, padding:'2px 7px', borderRadius:5, background:`${sc}22`, color:sc, letterSpacing:'.05em'}}>
                                            {lbl}{a.in_call && myCall ? ` · ${myCall.duration||'00:00'}` : ''}{a.paused && pauseDur ? ` · ${pauseDur}` : ''}
                                        </span>
                                        {a.queues.slice(0,3).map((q,i)=>(
                                            <span key={i} style={{fontSize:9, padding:'2px 6px', borderRadius:4, background:'rgba(17,179,40,0.12)', color:'var(--horizon-green)', fontFamily:'monospace', fontWeight:800, border:'1px solid rgba(17,179,40,0.25)'}}>Q{q.queue||q}</span>
                                        ))}
                                    </div>
                                </div>

                                {/* Actions verticales */}
                                <div style={{display:'flex', flexDirection:'column', gap:4}} onClick={e=>e.stopPropagation()}>
                                    {a.in_call ? (
                                        <button onClick={()=>doHangup(a)} title="Colgar llamada"
                                            className="rounded-md border bg-destructive/15 text-destructive font-bold transition-colors hover:bg-destructive/25"
                                            style={{padding:'4px 8px', fontSize:10, borderColor:'rgba(239,68,68,0.4)'}}>
                                            <span className="material-icons-round" style={{fontSize:14, verticalAlign:'middle'}}>call_end</span>
                                        </button>
                                    ) : ringing ? (
                                        <button onClick={()=>doHangup(a)} title="Rechazar llamada"
                                            className="rounded-md border bg-destructive/15 text-destructive font-bold transition-colors hover:bg-destructive/25"
                                            style={{padding:'4px 8px', fontSize:10, borderColor:'rgba(239,68,68,0.4)'}}>
                                            <span className="material-icons-round" style={{fontSize:14, verticalAlign:'middle'}}>phone_disabled</span>
                                        </button>
                                    ) : null}
                                    <button onClick={()=>logoutAgent(a)} title="Cerrar sesión"
                                        className="rounded-md border transition-colors hover:bg-accent"
                                        style={{padding:'4px 8px', fontSize:10, borderColor:'var(--border)', background:'var(--secondary)', color:'var(--foreground)'}}>
                                        <span className="material-icons-round" style={{fontSize:14, verticalAlign:'middle'}}>logout</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    );
                };

                const renderOffline = (a) => (
                    <div
                        key={a.id}
                        onClick={()=>setEditing(a)}
                        className="rounded-lg border cursor-pointer transition-all hover:shadow-sm flex items-center gap-2.5 px-3 py-2"
                        style={{
                            borderColor:'var(--border)',
                            background:'color-mix(in srgb, var(--muted) 30%, var(--card))',
                            color:'var(--card-foreground)'
                        }}>
                        <div className="rounded-full flex items-center justify-center font-black text-white shrink-0"
                             style={{
                                 width:32, height:32, fontSize:11,
                                 background:'linear-gradient(135deg, color-mix(in srgb, var(--muted-foreground) 70%, #000), color-mix(in srgb, var(--muted-foreground) 90%, #000))'
                             }}>
                            {(a.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="text-xs font-bold truncate" style={{color:'var(--foreground)'}}>{a.name}</div>
                            <div className="text-[10px] font-mono" style={{color:'var(--muted-foreground)'}}>#{a.number}</div>
                        </div>
                        <button onClick={(e)=>{e.stopPropagation(); setLoginAgentTarget(a);}}
                                className="rounded-md border px-2.5 py-1 text-[10px] font-bold transition-colors hover:shadow-sm"
                                style={{
                                    color:'var(--horizon-green)',
                                    borderColor:'color-mix(in srgb, var(--horizon-green) 40%, transparent)',
                                    background:'color-mix(in srgb, var(--horizon-green) 10%, transparent)'
                                }}>
                            Login
                        </button>
                    </div>
                );

                if (loggedAgents.length === 0 && offlineAgents.length === 0) {
                    return (
                        <div className="rounded-lg border border-border bg-card text-card-foreground p-10 text-center text-muted-foreground">
                            <span className="material-icons-round" style={{fontSize:48, opacity:0.4, display:'block', marginBottom:8}}>person_off</span>
                            <div className="text-sm font-bold">Sin agentes para mostrar</div>
                        </div>
                    );
                }

                return (
                    <div className="grid gap-4" style={{gridTemplateColumns:'1fr minmax(280px, 340px)', alignItems:'start'}}>
                        {/* LEFT: agentes logueados — panel destacado con header tipo card shadcn */}
                        <div className="rounded-xl border overflow-hidden" style={{
                            borderColor:'color-mix(in srgb, var(--horizon-green) 25%, var(--border))',
                            background:'linear-gradient(180deg, color-mix(in srgb, var(--horizon-green) 6%, var(--card)) 0%, var(--card) 100%)',
                            boxShadow:'0 1px 3px rgba(0,0,0,0.05), 0 0 0 1px color-mix(in srgb, var(--horizon-green) 8%, transparent)'
                        }}>
                            {/* Header del panel — separado con border-bottom */}
                            <div className="flex items-center justify-between gap-2 px-4 py-3 border-b" style={{
                                borderColor:'color-mix(in srgb, var(--horizon-green) 18%, var(--border))',
                                background:'color-mix(in srgb, var(--horizon-green) 7%, transparent)'
                            }}>
                                <div className="flex items-center gap-2 min-w-0">
                                    <div style={{
                                        width:30, height:30, borderRadius:9,
                                        background:'linear-gradient(135deg, var(--horizon-green), color-mix(in srgb, var(--horizon-green) 70%, #16a34a))',
                                        display:'flex', alignItems:'center', justifyContent:'center',
                                        boxShadow:'0 2px 8px color-mix(in srgb, var(--horizon-green) 40%, transparent)',
                                        flexShrink:0
                                    }}>
                                        <span className="material-icons-round" style={{color:'#fff', fontSize:17}}>support_agent</span>
                                    </div>
                                    <div className="min-w-0">
                                        <div style={{fontSize:12, fontWeight:800, color:'var(--foreground)', letterSpacing:'-.01em', lineHeight:1.1}}>Logueados</div>
                                        <div style={{fontSize:10, color:'var(--muted-foreground)', fontWeight:600, marginTop:1, lineHeight:1.2}}>Activos en tiempo real</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 flex-shrink-0">
                                    <span style={{
                                        display:'inline-flex', alignItems:'center', gap:5,
                                        padding:'3px 9px', borderRadius:9999,
                                        background:'color-mix(in srgb, var(--horizon-green) 18%, transparent)',
                                        color:'var(--horizon-green)',
                                        fontSize:11, fontWeight:800, fontFamily:'monospace',
                                        border:'1px solid color-mix(in srgb, var(--horizon-green) 30%, transparent)'
                                    }}>
                                        {loggedAgents.length > 0 && <span style={{width:6, height:6, borderRadius:'50%', background:'var(--horizon-green)', boxShadow:'0 0 6px var(--horizon-green)', animation:'pulse 1.5s infinite'}}/>}
                                        {loggedAgents.length}
                                    </span>
                                </div>
                            </div>

                            {/* Body */}
                            <div className="p-3">
                                {loggedAgents.length > 0 ? (
                                    <div style={{
                                        display:'grid',
                                        gridTemplateRows:'repeat(4, minmax(72px, auto))',
                                        gridAutoFlow:'column',
                                        gridAutoColumns:'minmax(320px, 1fr)',
                                        gap:8,
                                        overflowX:'auto',
                                        overflowY:'hidden',
                                        paddingBottom:6,
                                        scrollbarWidth:'thin'
                                    }}>
                                        {loggedAgents.map(renderLogged)}
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-3 py-4 px-3">
                                        <div className="rounded-full flex items-center justify-center shrink-0" style={{
                                            width:40, height:40,
                                            background:'color-mix(in srgb, var(--horizon-green) 12%, transparent)',
                                            border:'1.5px dashed color-mix(in srgb, var(--horizon-green) 40%, transparent)'
                                        }}>
                                            <span className="material-icons-round" style={{fontSize:20, color:'var(--horizon-green)'}}>person_off</span>
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="text-sm font-bold" style={{color:'var(--foreground)'}}>Ningún agente logueado</div>
                                            <div className="text-xs mt-0.5" style={{color:'var(--muted-foreground)'}}>
                                                Marcá <strong className="font-mono" style={{color:'var(--horizon-green)'}}>*7700</strong> desde el teléfono · {offlineAgents.length} disponibles
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* RIGHT: agentes offline (compactos) */}
                        <div>
                            <div className="flex items-center gap-2 mb-2.5">
                                <span className="rounded-full" style={{width:6, height:6, background:'var(--muted-foreground)'}}/>
                                <span className="text-xs font-bold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>{offlineAgents.length} offline</span>
                            </div>
                            {offlineAgents.length > 0 ? (
                                <div style={{display:'grid', gridTemplateColumns:'1fr', gap:6}}>
                                    {offlineAgents.map(renderOffline)}
                                </div>
                            ) : (
                                <div className="rounded-lg border border-dashed p-6 text-center text-xs" style={{borderColor:'var(--border)', color:'var(--muted-foreground)'}}>
                                    Todos los agentes están logueados
                                </div>
                            )}
                        </div>
                    </div>
                );
            })()}


            {/* TABLE VIEW */}
            {viewMode==='table' && (
                <div className="glass" style={{borderRadius:14,overflow:'hidden'}}>
                    <table className="tf-table">
                        <thead><tr>
                            <th style={{width:130}}>Estado</th><th style={{width:50}}></th>
                            <th>Agente #</th><th>Nombre</th><th>Tipo</th><th>Extensión</th>
                            <th>Colas activas</th><th style={{width:160}}>Acciones</th>
                        </tr></thead>
                        <tbody>
                            {filtered.map(a => {
                                const sc = a.in_call?'#ef4444':(a.logged_in?'#22c55e':'#6b7280');
                                const lbl = a.in_call?'En llamada':(a.logged_in?'Disponible':'Offline');
                                return (
                                <tr key={a.id}>
                                    <td style={{padding:'10px 14px'}}><span style={{display:'inline-flex',alignItems:'center',gap:7,fontSize:11,fontWeight:800,color:sc,padding:'4px 10px',borderRadius:6,background:`${sc}1a`,border:`1px solid ${sc}44`}}>
                                        <span style={{width:7,height:7,borderRadius:'50%',background:sc,boxShadow:`0 0 8px ${sc}99`,animation:a.in_call?'pulse 1s infinite':'none'}}/>{lbl}
                                    </span></td>
                                    <td><div style={{width:32,height:32,borderRadius:'50%',background:`linear-gradient(135deg,${sc},${sc}88)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:11,fontWeight:900,boxShadow:`0 2px 8px ${sc}44`}}>
                                        {(a.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}
                                    </div></td>
                                    <td style={{fontFamily:'monospace',fontWeight:800,fontSize:13}}>#{a.number}</td>
                                    <td style={{fontSize:13,fontWeight:700}}>{a.name}</td>
                                    <td><span style={{fontSize:10,padding:'2px 7px',borderRadius:5,background:'color-mix(in srgb, var(--primary) 15%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:700}}>{a.type||'Agent'}</span></td>
                                    <td style={{fontFamily:'monospace',fontSize:12,fontWeight:700,color:a.extension?'var(--text)':'var(--muted)'}}>{a.extension||'—'}</td>
                                    <td><div style={{display:'flex',gap:3,flexWrap:'wrap'}}>
                                        {(a.queues||[]).slice(0,5).map((qm,i)=>(<span key={i} style={{fontSize:9,padding:'2px 7px',borderRadius:4,background:'color-mix(in srgb, var(--primary) 15%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontFamily:'monospace',fontWeight:800}}>Q{qm.queue||qm}</span>))}
                                        {(a.queues||[]).length===0 && <span style={{fontSize:10,color:'var(--muted)',fontStyle:'italic'}}>—</span>}
                                    </div></td>
                                    <td><div style={{display:'flex',gap:4}}>
                                        {a.logged_in 
                                            ? <button onClick={()=>logoutAgent(a)} style={{padding:'4px 10px',borderRadius:6,border:'1px solid rgba(239,68,68,0.4)',background:'rgba(239,68,68,0.1)',color:'#ef4444',fontWeight:800,fontSize:10,cursor:'pointer'}}>Logout</button>
                                            : <button onClick={()=>setLoginAgentTarget(a)} style={{padding:'4px 10px',borderRadius:6,border:'1px solid rgba(34,197,94,0.4)',background:'rgba(34,197,94,0.1)',color:'#22c55e',fontWeight:800,fontSize:10,cursor:'pointer'}}>Login</button>}
                                        <button onClick={()=>setEditing(a)} style={{padding:'4px 8px',borderRadius:6,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--text)',fontSize:10,cursor:'pointer'}}><span className="material-icons-round" style={{fontSize:13}}>edit</span></button>
                                        <button onClick={()=>remove(a)} style={{padding:'4px 8px',borderRadius:6,border:'1px solid rgba(239,68,68,0.2)',background:'rgba(239,68,68,0.05)',color:'#ef4444',fontSize:10,cursor:'pointer'}}><span className="material-icons-round" style={{fontSize:13}}>delete</span></button>
                                    </div></td>
                                </tr>);
                            })}
                            {filtered.length===0 && <tr><td colSpan={8} style={{textAlign:'center',padding:30,color:'var(--muted)'}}>Sin resultados</td></tr>}
                        </tbody>
                    </table>
                </div>
            )}

            {/* GRID/CARDS view (compact) */}
            {viewMode==='grid' && (
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(280px,1fr))',gap:12}}>
                    {filtered.map(a => {
                        const sc = a.in_call?'#ef4444':(a.logged_in?'#22c55e':'#6b7280');
                        return (
                        <div key={a.id} className="glass" style={{padding:14,borderRadius:12,border:`1px solid ${a.logged_in?sc+'44':'var(--border)'}`,cursor:'pointer'}} onClick={()=>setEditing(a)}>
                            <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:10}}>
                                <div style={{width:42,height:42,borderRadius:'50%',background:`linear-gradient(135deg,${sc},${sc}aa)`,display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontWeight:900,fontSize:13}}>{(a.name||'?').split(/\s+/).map(x=>x[0]).join('').substring(0,2).toUpperCase()}</div>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:13,fontWeight:800,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{a.name}</div>
                                    <div style={{fontSize:10,color:'var(--muted)'}}>#{a.number}</div>
                                </div>
                            </div>
                            <div style={{display:'flex',gap:5}} onClick={e=>e.stopPropagation()}>
                                {a.logged_in?<button onClick={()=>logoutAgent(a)} style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid rgba(239,68,68,0.4)',background:'rgba(239,68,68,0.1)',color:'#ef4444',fontWeight:800,fontSize:10,cursor:'pointer'}}>Logout</button>:<button onClick={()=>setLoginAgentTarget(a)} style={{flex:1,padding:'5px',borderRadius:6,border:'1px solid rgba(34,197,94,0.4)',background:'rgba(34,197,94,0.1)',color:'#22c55e',fontWeight:800,fontSize:10,cursor:'pointer'}}>Login</button>}
                            </div>
                        </div>);
                    })}
                </div>
            )}

            {filtered.length===0 && viewMode!=='table' && (
                <div className="glass" style={{padding:60,textAlign:'center',borderRadius:18,border:'2px dashed var(--border)',marginTop:14}}>
                    <span className="material-icons-round" style={{fontSize:54,color:'var(--muted)',display:'block',marginBottom:10}}>person_search</span>
                    <div style={{fontSize:14,fontWeight:700,color:'var(--text)'}}>Sin agentes</div>
                </div>
            )}

            {loginAgentTarget && <AgentLoginModal open={true} onClose={()=>setLoginAgentTarget(null)} onDone={()=>{setLoginAgentTarget(null);load();loadQueues();}} toast={toast} preselectAgent={loginAgentTarget} />}
            {showWizard && <HotdeskingWizard onClose={()=>setShowWizard(false)} />}
            {editing && <HotdeskingEditModal agent={editing==='new'?null:editing} onClose={()=>setEditing(null)} onSave={save} queues={data?.pbx?.queues||[]} />}

            <ConfirmDialog
                open={!!logoutTarget}
                onCancel={()=>setLogoutTarget(null)}
                onConfirm={confirmLogout}
                title="Cerrar sesión del agente"
                message={logoutTarget ? `¿Cerrar la sesión de ${logoutTarget.name} (#${logoutTarget.number})? El agente saldrá de todas las colas activas y deberá loguearse nuevamente para atender llamadas.` : ''}
                confirmLabel="Sí, cerrar sesión"
                cancelLabel="Cancelar"
                variant="destructive"
                icon="logout"
                loading={logoutBusy}
            />
        </div>
    );
}


function HotdeskingEditModal({ agent, onClose, onSave, queues }) {
    const isNew = !agent;
    const [form, setForm] = useState({
        id: agent?.id || '',
        type: agent?.type || 'Agent',
        number: agent?.number || '',
        name: agent?.name || '',
        password: '',
        eccp_password: '',
        estatus: agent?.estatus || 'A'
    });
    const [errors, setErrors] = useState({});
    const [showPass, setShowPass] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const set = (k,v) => { setForm(f => ({...f, [k]: v})); setErrors(e => ({...e, [k]: undefined})); };

    const validate = () => {
        const e = {};
        if (!form.number || !/^\d+$/.test(form.number)) e.number = 'Solo números (ej: 250)';
        if (!form.name || form.name.trim().length < 2) e.name = 'Mínimo 2 caracteres';
        if (isNew && !form.password) e.password = 'Password requerida (mín 4)';
        if (form.password && form.password.length < 4) e.password = 'Mínimo 4 caracteres';
        setErrors(e);
        return Object.keys(e).length === 0;
    };

    const handleSubmit = async () => {
        if (!validate()) return;
        setSubmitting(true);
        const data = {...form};
        if (!data.password) { delete data.password; delete data.eccp_password; }
        else if (!data.eccp_password) data.eccp_password = data.password;
        if (!data.id) delete data.id;
        await onSave(data);
        setSubmitting(false);
    };

    const typeOptions = [
        { v:'Agent', l:'Agent', i:'badge', d:'Asterisk Agent (recomendado)' },
        { v:'SIP', l:'SIP', i:'phone', d:'Interface SIP directa' },
        { v:'IAX2', l:'IAX2', i:'router', d:'Inter-Asterisk Exchange' }
    ];

    return (


        <LegacyDialogShell onClose={onClose} maxWidth={560}>
                {/* Header con gradient */}
                <div style={{padding:'18px 24px',background:`linear-gradient(135deg, ${isNew?'rgba(34,197,94,0.18)':'rgba(139,92,246,0.18)'}, transparent)`,borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',gap:14}}>
                    <div style={{width:48,height:48,borderRadius:14,background:`linear-gradient(135deg, ${isNew?'#22c55e,#16a34a':'#8b5cf6,#6d28d9'})`,display:'flex',alignItems:'center',justifyContent:'center',boxShadow:`0 6px 18px ${isNew?'rgba(34,197,94,0.4)':'rgba(139,92,246,0.4)'}`}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:24}}>{isNew?'person_add':'edit'}</span>
                    </div>
                    <div style={{flex:1}}>
                        <h2 style={{fontSize:18,fontWeight:900,letterSpacing:'-0.3px'}}>{isNew ? 'Nuevo agente' : `Editar #${agent.number}`}</h2>
                        <div style={{fontSize:11,color:'var(--muted)'}}>{isNew?'Persona del callcenter — quedará en call_center.agent':`${agent.name}`}</div>
                    </div>
                    <button onClick={onClose} style={{padding:8,borderRadius:10,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                        <span className="material-icons-round" style={{fontSize:20,color:'var(--muted)'}}>close</span>
                    </button>
                </div>

                {/* Body scrollable */}
                <div style={{flex:1,overflow:'auto',padding:'18px 24px'}}>
                    {/* Avatar uploader si ya hay número */}
                    {form.number && (
                        <div style={{display:'flex',alignItems:'center',gap:14,marginBottom:16,padding:14,background:'var(--surface2)',borderRadius:12,border:'1px solid var(--border)'}}>
                            <AvatarUploader ext={form.number} name={form.name} size={64}/>
                            <div style={{flex:1}}>
                                <div style={{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',marginBottom:2}}>Foto del agente</div>
                                <div style={{fontSize:11,color:'var(--muted)'}}>Aparece en wallboard, llamadas en vivo y reportes.</div>
                            </div>
                        </div>
                    )}

                    {/* Número agente */}
                    <Field label="Número de agente" icon="badge" required error={errors.number} hint="Único en el callcenter (ej: 250)">
                        <input
                            className="input-tf"
                            style={{padding:'11px 14px',paddingLeft:42,borderRadius:10,fontSize:14,fontFamily:'monospace',fontWeight:700,width:'100%',border:errors.number?'1px solid #ef4444':undefined}}
                            value={form.number}
                            onChange={e=>set('number',e.target.value.replace(/\D/g,''))}
                            disabled={!isNew}
                            placeholder="200"
                            maxLength="6"
                        />
                    </Field>

                    {/* Nombre */}
                    <Field label="Nombre completo" icon="person" required error={errors.name} hint="Aparece en cards, llamadas y reportes">
                        <input
                            className="input-tf"
                            style={{padding:'11px 14px',paddingLeft:42,borderRadius:10,fontSize:13,width:'100%',border:errors.name?'1px solid #ef4444':undefined}}
                            value={form.name}
                            onChange={e=>set('name',e.target.value)}
                            placeholder="Ej: Brian Pérez"
                        />
                    </Field>

                    {/* Password */}
                    <Field label={isNew?"Password":"Password (vacío = no cambia)"} icon="lock" required={isNew} error={errors.password} hint={isNew?"Min 4 chars · El agente la usa para login":"Solo si querés actualizarla"}>
                        <input
                            className="input-tf"
                            style={{padding:'11px 14px 11px 42px',borderRadius:10,fontSize:13,width:'100%',fontFamily:'monospace',border:errors.password?'1px solid #ef4444':undefined}}
                            type={showPass?'text':'password'}
                            value={form.password}
                            onChange={e=>set('password',e.target.value)}
                            placeholder={isNew?'mín 4 caracteres':'(sin cambios)'}
                        />
                        <button type="button" onClick={()=>setShowPass(!showPass)} style={{position:'absolute',right:12,top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',color:'var(--muted)',padding:0}}>
                            <span className="material-icons-round" style={{fontSize:18}}>{showPass?'visibility_off':'visibility'}</span>
                        </button>
                    </Field>

                    {/* Tipo */}
                    <div style={{marginBottom:14}}>
                        <label style={{fontSize:10,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.05em',marginBottom:6,display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:14,color:'var(--primary)'}}>devices</span>
                            Tipo de interface
                        </label>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr',gap:8}}>
                            {typeOptions.map(o => (
                                <div key={o.v} onClick={()=>set('type',o.v)} title={o.d} style={{padding:'10px 8px',borderRadius:10,border:`2px solid ${form.type===o.v?'#8b5cf6cc':'var(--border)'}`,background:form.type===o.v?'rgba(139,92,246,0.1)':'var(--surface2)',cursor:'pointer',textAlign:'center',transition:'all 0.15s'}}>
                                    <span className="material-icons-round" style={{fontSize:18,color:form.type===o.v?'#c4b5fd':'var(--muted)',display:'block',marginBottom:3}}>{o.i}</span>
                                    <div style={{fontSize:11,fontWeight:800,color:form.type===o.v?'#c4b5fd':'var(--text)'}}>{o.l}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Estado */}
                    <div style={{marginBottom:8}}>
                        <label style={{fontSize:10,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.05em',marginBottom:6,display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:14,color:'#22c55e'}}>toggle_on</span>
                            Estado del agente
                        </label>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:8}}>
                            <div onClick={()=>set('estatus','A')} style={{padding:'12px',borderRadius:10,border:`2px solid ${form.estatus==='A'?'#22c55ecc':'var(--border)'}`,background:form.estatus==='A'?'rgba(34,197,94,0.1)':'var(--surface2)',cursor:'pointer',textAlign:'center'}}>
                                <span className="material-icons-round" style={{fontSize:20,color:form.estatus==='A'?'#22c55e':'var(--muted)',display:'block',marginBottom:3}}>check_circle</span>
                                <div style={{fontSize:12,fontWeight:800,color:form.estatus==='A'?'#22c55e':'var(--text)'}}>Activo</div>
                                <div style={{fontSize:9,color:'var(--muted)',marginTop:2}}>Puede loguearse</div>
                            </div>
                            <div onClick={()=>set('estatus','I')} style={{padding:'12px',borderRadius:10,border:`2px solid ${form.estatus==='I'?'#ef4444cc':'var(--border)'}`,background:form.estatus==='I'?'rgba(239,68,68,0.08)':'var(--surface2)',cursor:'pointer',textAlign:'center'}}>
                                <span className="material-icons-round" style={{fontSize:20,color:form.estatus==='I'?'#ef4444':'var(--muted)',display:'block',marginBottom:3}}>block</span>
                                <div style={{fontSize:12,fontWeight:800,color:form.estatus==='I'?'#ef4444':'var(--text)'}}>Inactivo</div>
                                <div style={{fontSize:9,color:'var(--muted)',marginTop:2}}>Bloqueado</div>
                            </div>
                        </div>
                    </div>

                    {/* Validación errors summary */}
                    {Object.keys(errors).length > 0 && (
                        <div style={{padding:'10px 12px',background:'rgba(239,68,68,0.1)',border:'1px solid rgba(239,68,68,0.3)',borderRadius:10,marginTop:12,display:'flex',gap:8,alignItems:'flex-start'}}>
                            <span className="material-icons-round" style={{fontSize:16,color:'#ef4444',flexShrink:0,marginTop:1}}>error_outline</span>
                            <div style={{fontSize:11,color:'#fca5a5'}}>
                                <strong style={{color:'#ef4444'}}>Falta completar:</strong> {Object.entries(errors).filter(([_,v])=>v).map(([k,v])=>v).join(' · ')}
                            </div>
                        </div>
                    )}
                </div>

                {/* Acciones de sesión telefónica (Login/Logout inline) + Reporte */}
                {!isNew && (
                    <div style={{padding:'12px 24px',borderTop:'1px solid var(--border)',background:'rgba(139,92,246,0.04)',display:'flex',gap:8,flexWrap:'wrap',alignItems:'center'}}>
                        <div style={{flex:1,minWidth:0,fontSize:11,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em',display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:14,color:'var(--primary)'}}>headset_mic</span>
                            Sesión telefónica
                        </div>
                        <button type="button" onClick={()=>{
                            window.dispatchEvent(new CustomEvent('tf-open-report', {detail: agent}));
                            onClose();
                        }} style={{padding:'8px 16px',borderRadius:9,border:'1px solid rgba(139,92,246,0.4)',background:'color-mix(in srgb, var(--primary) 12%, transparent)',color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))',fontWeight:800,fontSize:12,cursor:'pointer',display:'flex',alignItems:'center',gap:6}}>
                            <span className="material-icons-round" style={{fontSize:16}}>analytics</span>Reporte
                        </button>
                        {agent?.logged_in ? (
                            <button type="button" onClick={()=>{
                                const fd = new FormData(); fd.append('agent_number', agent.number); if (agent.extension) fd.append('extension', agent.extension);
                                fetch('api/hotdesking.php?action=logout_agent', {method:'POST',body:fd,credentials:'include'})
                                    .then(r=>r.json()).then(j=>{
                                        if (j.status==='ok') { window.dispatchEvent(new CustomEvent('tf-queues-refresh')); onClose(); }
                                    });
                            }} style={{padding:'8px 16px',borderRadius:9,border:'1px solid rgba(239,68,68,0.4)',background:'rgba(239,68,68,0.12)',color:'#ef4444',fontWeight:800,fontSize:12,cursor:'pointer',display:'flex',alignItems:'center',gap:6}}>
                                <span className="material-icons-round" style={{fontSize:16}}>logout</span>Desloguear
                            </button>
                        ) : (
                            <button type="button" onClick={()=>{
                                window.dispatchEvent(new CustomEvent('tf-open-login', {detail: agent}));
                                onClose();
                            }} style={{padding:'8px 16px',borderRadius:9,border:'1px solid rgba(34,197,94,0.4)',background:'rgba(34,197,94,0.12)',color:'#22c55e',fontWeight:800,fontSize:12,cursor:'pointer',display:'flex',alignItems:'center',gap:6}}>
                                <span className="material-icons-round" style={{fontSize:16}}>login</span>Loguear en cola
                            </button>
                        )}
                    </div>
                )}

                {/* Footer */}
                <div style={{padding:'14px 24px',borderTop:'1px solid var(--border)',background:'var(--surface2)',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                    <div style={{fontSize:10,color:'var(--muted)',display:'flex',alignItems:'center',gap:5}}>
                        <span className="material-icons-round" style={{fontSize:13}}>info</span>
                        {isNew?'El agente quedará disponible para login inmediato':'Cambios se aplican al confirmar'}
                    </div>
                    <div style={{display:'flex',gap:8}}>
                        <button onClick={onClose} style={{padding:'9px 16px',borderRadius:10,border:'1px solid var(--border)',background:'var(--surface)',color:'var(--text)',fontWeight:700,fontSize:12,cursor:'pointer'}}>Cancelar</button>
                        <button onClick={handleSubmit} disabled={submitting} className="btn-primary" style={{padding:'9px 22px',borderRadius:10,fontSize:12,fontWeight:800,cursor:submitting?'wait':'pointer',display:'flex',alignItems:'center',gap:6,boxShadow:`0 4px 14px ${isNew?'rgba(34,197,94,0.35)':'rgba(139,92,246,0.35)'}`,background:isNew?'linear-gradient(135deg,#22c55e,#16a34a)':'linear-gradient(135deg,#8b5cf6,#6d28d9)',border:'none',color:'#fff'}}>
                            <span className="material-icons-round" style={{fontSize:16,animation:submitting?'spin 1s linear infinite':'none'}}>{submitting?'autorenew':(isNew?'person_add':'save')}</span>
                            {submitting?'Guardando...':(isNew?'Crear agente':'Guardar cambios')}
                        </button>
                    </div>
                </div>
            
        </LegacyDialogShell>
    );
}

// Helper: campo con label + icon + error
function Field({ label, icon, required, error, hint, children }) {
    return (
        <div style={{marginBottom:14,position:'relative'}}>
            <label style={{fontSize:10,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.05em',marginBottom:6,display:'flex',alignItems:'center',gap:6}}>
                <span className="material-icons-round" style={{fontSize:14,color:'var(--primary)'}}>{icon}</span>
                {label}{required && <span style={{color:'#ef4444',marginLeft:2}}>*</span>}
                {hint && <span style={{textTransform:'none',color:'var(--muted)',fontWeight:500,marginLeft:'auto',fontSize:9}}>{hint}</span>}
            </label>
            <div style={{position:'relative'}}>
                <span className="material-icons-round" style={{position:'absolute',left:13,top:'50%',transform:'translateY(-50%)',fontSize:18,color:error?'#ef4444':'var(--muted)',pointerEvents:'none',zIndex:1}}>{icon}</span>
                {children}
            </div>
            {error && <div style={{fontSize:10,color:'#ef4444',marginTop:4,display:'flex',alignItems:'center',gap:4}}><span className="material-icons-round" style={{fontSize:12}}>error_outline</span>{error}</div>}
        </div>
    );
}
const SileoContext = React.createContext(null);

// HORIZON: Portal para modales — escapa cualquier stacking context del parent
function TFModalPortal({ children }) {
    if (typeof document === 'undefined') return null;
    const root = document.getElementById('tf-modal-root') || document.body;
    return ReactDOM.createPortal(children, root);
}

function SileoProvider({ children }) {
    const [notifs, setNotifs] = useState([]);
    const idRef = useRef(0);

    const push = (n) => {
        const id = ++idRef.current;
        const item = { id, ts: Date.now(), ...n };
        setNotifs(prev => [...prev.slice(-4), item]);
        if (n.autoDismiss !== false) {
            setTimeout(() => dismiss(id), n.duration || 8000);
        }
        return id;
    };
    const dismiss = (id) => {
        setNotifs(prev => prev.map(n => n.id === id ? {...n, _dismiss:true} : n));
        setTimeout(() => setNotifs(prev => prev.filter(n => n.id !== id)), 320);
    };

    // Expose globally for non-React contexts
    useEffect(() => { window.sileo = { push, dismiss }; }, []);

    return (
        <SileoContext.Provider value={{ push, dismiss }}>
            {children}
            <div className="sileo-stack">
                {notifs.map(n => (
                    <div key={n.id} className={`sileo-notif ${n.kind||''} ${n._dismiss?'dismissing':''}`}>
                        <div className="sileo-icon">
                            <span className="material-icons-round" style={{fontSize:20}}>{n.icon || (n.kind==='call'?'phone_in_talk':(n.kind==='warning'?'warning':(n.kind==='error'?'error':'info')))}</span>
                        </div>
                        <div className="sileo-body">
                            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:6}}>
                                <div className="sileo-title">{n.title}</div>
                                <span className="sileo-time">ahora</span>
                            </div>
                            <div className="sileo-msg">{n.msg}</div>
                            {n.actions && n.actions.length > 0 && (
                                <div className="sileo-actions">
                                    {n.actions.map((a,i)=>(
                                        <button key={i} className={`sileo-btn ${a.primary?'primary':'secondary'}`} onClick={()=>{a.onClick && a.onClick(); dismiss(n.id);}}>{a.label}</button>
                                    ))}
                                </div>
                            )}
                        </div>
                        <button className="sileo-close" onClick={()=>dismiss(n.id)}>
                            <span className="material-icons-round" style={{fontSize:14}}>close</span>
                        </button>
                    </div>
                ))}
            </div>
        </SileoContext.Provider>
    );
}

function useSileo() { return useContext(SileoContext); }

// ─────────────────────────────────────────────
// MODAL: Asignar llamada en vivo (Redirect AMI)
// ─────────────────────────────────────────────
function AssignCallModal({ open, call, onClose, queues, extensions, toast }) {
    const [target, setTarget] = useState('');
    const [type, setType] = useState('extension'); // 'extension' | 'queue'
    const [busy, setBusy] = useState(false);
    if (!open || !call) return null;

    const submit = async () => {
        if (!target) { toast?.('Ingresá un destino','error'); return; }
        setBusy(true);
        const fd = new FormData();
        fd.append('channel', call.channel);
        fd.append('exten', target);
        fd.append('context', type==='queue'?'ext-queues':'from-internal');
        try {
            const r = await fetch('api/index.php?action=redirect_call',{method:'POST',body:fd,credentials:'include'});
            const j = await r.json();
            if (j.success) { toast?.(`Llamada redirigida a ${target}`,'success'); onClose(); }
            else toast?.(j.error||'Error redirigiendo','error');
        } catch(e) { toast?.('Error de red','error'); }
        setBusy(false);
    };

    return (


        <LegacyDialogShell onClose={onClose} maxWidth={480}>
                <div style={{display:'flex',alignItems:'center',gap:12,marginBottom:18}}>
                    <div style={{width:46,height:46,borderRadius:'50%',background:'linear-gradient(135deg,#3b82f6,#1d4ed8)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <span className="material-icons-round" style={{color:'#fff',fontSize:22}}>swap_horiz</span>
                    </div>
                    <div style={{flex:1}}>
                        <h2 style={{fontSize:16,fontWeight:900}}>Asignar llamada en vivo</h2>
                        <div style={{fontSize:11,color:'var(--muted)',fontFamily:'monospace'}}>{call.callerid || call.ext} · {call.state}</div>
                    </div>
                </div>
                <div style={{marginBottom:14}}>
                    <div style={{display:'flex',gap:6,padding:4,background:'var(--surface2)',borderRadius:10,marginBottom:10}}>
                        <button onClick={()=>setType('extension')} style={{flex:1,padding:'7px',borderRadius:7,border:'none',cursor:'pointer',background:type==='extension'?'rgba(139,92,246,0.2)':'transparent',color:type==='extension'?'#c4b5fd':'var(--muted)',fontWeight:800,fontSize:11}}>A extensión</button>
                        <button onClick={()=>setType('queue')} style={{flex:1,padding:'7px',borderRadius:7,border:'none',cursor:'pointer',background:type==='queue'?'rgba(139,92,246,0.2)':'transparent',color:type==='queue'?'#c4b5fd':'var(--muted)',fontWeight:800,fontSize:11}}>A cola</button>
                    </div>
                    <input className="input-tf py-2 px-3 rounded-lg text-sm w-full" placeholder={type==='extension'?'Ej: 9006':'Ej: 8000'} value={target} onChange={e=>setTarget(e.target.value)} />
                    <div style={{fontSize:10,color:'var(--muted)',marginTop:6}}>{type==='extension'?'Llamada se transfiere directo a esa ext':'Llamada vuelve a entrar en esa cola'}</div>
                </div>
                <div style={{display:'flex',gap:8,justifyContent:'flex-end'}}>
                    <button onClick={onClose} style={{padding:'8px 14px',borderRadius:9,border:'1px solid var(--border)',background:'var(--surface2)',color:'var(--text)',fontWeight:700,fontSize:12,cursor:'pointer'}}>Cancelar</button>
                    <button onClick={submit} disabled={!target||busy} style={{padding:'8px 18px',borderRadius:9,border:'none',cursor:!target||busy?'not-allowed':'pointer',background:'linear-gradient(135deg,#3b82f6,#1d4ed8)',color:'#fff',fontWeight:800,fontSize:12,opacity:!target||busy?0.5:1}}>{busy?'Redirigiendo...':'Redirigir'}</button>
                </div>
            
        </LegacyDialogShell>
    );
}


// ─────────────────────────────────────────────
// COMPONENTE: CallCenterTopBar (stats globales sticky)
// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// COMPONENTE: Tip (tooltip animado con Tippy)
// ─────────────────────────────────────────────
function Tip({ content, children, placement = 'top' }) {
    const ref = useRef(null);
    useEffect(() => {
        if (!ref.current || !window.tippy) return;
        const inst = window.tippy(ref.current, {
            content,
            placement,
            animation: 'shift-toward',
            arrow: true,
            theme: document.body.classList.contains('light') ? 'light' : '',
            allowHTML: true,
            interactive: false,
            delay: [200, 0],
            duration: [200, 150],
            maxWidth: 280
        });
        return () => inst.destroy();
    }, [content, placement]);
    return React.cloneElement(children, { ref, ...(children.props || {}) });
}




const PageActionsCtx = React.createContext({ set: () => {} });
function PageHeader({ view }) {
    const [actions, setActions] = useState(null);
    useEffect(() => { setActions(null); window.__tfSetPageActions = setActions; return () => { window.__tfSetPageActions = null; }; }, [view]);
    if (!actions) return null;
    return (
        <div className="tf-page-header">
            <div className="tf-page-actions">{actions}</div>
        </div>
    );
}
function PageActions({ children }) {
    useEffect(() => {
        if (window.__tfSetPageActions) window.__tfSetPageActions(children);
        return () => { if (window.__tfSetPageActions) window.__tfSetPageActions(null); };
    }, [children]);
    return null;
}

function TopBarMenu({ view, setView, user, onLogout, darkMode, setDarkMode, data, activeCalls, setVivoFilter }) {
    const [openMenu, setOpenMenu] = useState(null);
    const [menuAnchor, setMenuAnchor] = useState(null);
    const [showUserMenu, setShowUserMenu] = useState(false);
    const [showSysModal, setShowSysModal] = useState(false);
    const [pbxBrand, setPbxBrand] = useState(null);
    const [agentsLogged, setAgentsLogged] = useState(0);
    const [mobileOpen, setMobileOpen] = useState(false);

    // HORIZON: Extension de escucha (ChanSpy destination). Persiste en localStorage
    const [spyExt, setSpyExt] = useState(() => { try { return localStorage.getItem('tf_spy_ext') || ''; } catch(e) { return ''; } });
    useEffect(() => { try { localStorage.setItem('tf_spy_ext', spyExt); window._tfSpyExt = spyExt; } catch(e) {} }, [spyExt]);

    // Listener global del evento tf-spy-call disparado desde el toast Sileo
    useEffect(() => {
        const onSpy = async (e) => {
            const call = e.detail;
            const my = (window._tfSpyExt || localStorage.getItem('tf_spy_ext') || '').trim();
            if (!my || !/^\d+$/.test(my)) {
                window.shToast?.('Configurá una extensión de escucha en la barra superior antes de espiar', 'destructive');
                return;
            }
            if (!call?.channel) {
                window.shToast?.('Llamada sin canal — no se puede espiar', 'destructive');
                return;
            }
            try {
                const fd = new FormData();
                fd.append('channel', call.channel);
                fd.append('my_ext', my);
                fd.append('mode', 'spy');
                const r = await fetch('api/hotdesking.php?action=spy_call', { method:'POST', body: fd, credentials: 'include' });
                const j = await r.json();
                if (j.status === 'ok') window.shToast?.(`Escuchando en ext ${my}…`, 'success');
                else window.shToast?.('Error: '+(j.message||''), 'destructive');
            } catch(err) { window.shToast?.('Error de red', 'destructive'); }
        };
        window.addEventListener('tf-spy-call', onSpy);
        return () => window.removeEventListener('tf-spy-call', onSpy);
    }, []);

    const liveCalls = data?.pbx?.live_calls || [];
    const queues = data?.pbx?.queues || [];
    const exts = data?.pbx?.extensions || [];
    const totalWaiting = queues.reduce((s,q) => s + (q.calls_waiting||0), 0);
    const upCount = liveCalls.filter(c => c.state === 'Up').length;
    const ringCount = liveCalls.filter(c => /Ring/.test(c.state||'')).length;
    const onlineExts = exts.filter(e => e.status === 'ONLINE').length;
    const groupsRinging = liveCalls.filter(c => (c.context||'').includes('macro-dial-one') || /grp/i.test(c.dest||'')).length;

    // close on outside click
    useEffect(() => {
        const h = (e) => {
            if (!e.target.closest('.tfbar-item') && !e.target.closest('.tfbar-dropdown')) setOpenMenu(null);
            if (!e.target.closest('.tfbar-avatar') && !e.target.closest('.tfbar-user-pop')) setShowUserMenu(false);
        };
        document.addEventListener('click', h);
        return () => document.removeEventListener('click', h);
    }, []);
    useEffect(() => {
        const close = () => { setOpenMenu(null); setMenuAnchor(null); };
        window.addEventListener('resize', close);
        window.addEventListener('scroll', close, true);
        return () => { window.removeEventListener('resize', close); window.removeEventListener('scroll', close, true); };
    }, []);


    useEffect(() => {
        fetch('api/hotdesking.php?action=list',{credentials:'include'})
            .then(r=>r.json()).then(j=>{ if (j.status==='ok') setAgentsLogged((j.agents||[]).filter(a=>a.logged_in).length); })
            .catch(()=>{});
        const t = setInterval(()=> fetch('api/hotdesking.php?action=list',{credentials:'include'})
            .then(r=>r.json()).then(j=>{ if (j.status==='ok') setAgentsLogged((j.agents||[]).filter(a=>a.logged_in).length); })
            .catch(()=>{}), 10000);
        return () => clearInterval(t);
    }, []);
    useEffect(() => {
        fetch('api/index.php?action=detect_pbx',{credentials:'include'})
            .then(r=>r.json()).then(j=>{ if(j.success) setPbxBrand(j); }).catch(()=>{});
    }, []);

    const groups = [
        { id:'inicio', label:'Inicio', icon:'home', single:'dashboard', items:[
            { id:'dashboard', icon:'grid_view', label:'Dashboard', sub:'Resumen general' },
        ]},
        { id:'op', label:'Operación', icon:'support_agent', items:[
            { id:'hotdesking', icon:'phonelink_setup', label:'Hotdesking', sub:'Asignación dinámica de agentes', badge: agentsLogged },
            { id:'vivo', icon:'sensors', label:'Llamadas en Vivo', sub:'En curso ahora', badge: activeCalls },
            { id:'radar', icon:'radar', label:'Tráfico', sub:'Mapa visual de canales activos' },
            { id:'colas', icon:'queue', label:'Colas', sub:'Distribución y espera', badge: totalWaiting },
            { id:'grupos', icon:'ring_volume', label:'Grupos', sub:'Ring groups', badge: groupsRinging },
        ]},
        { id:'tel', label:'Telefonía', icon:'dialpad', items:[
            { id:'extensiones', icon:'group', label:'Extensiones', sub:'Internos SIP/PJSIP', badge: onlineExts },
            { id:'agentes', icon:'support_agent', label:'Agentes', sub:'Personas y métricas' },
            { id:'ivr', icon:'account_tree', label:'IVR', sub:'Menús de voz' },
        ]},
        { id:'rep', label:'Reportes', icon:'analytics', items:[
            { id:'cdr', icon:'history', label:'CDR', sub:'Detalle de llamadas' },
            { id:'reportes', icon:'assessment', label:'Reportes Avanzados', sub:'KPIs callcenter' },
        ]},
        { id:'cfg', label:'Configuración', icon:'settings', single:'configuracion', items:[
            { id:'configuracion', icon:'tune', label:'Ajustes', sub:'PBX, AMI, integraciones' },
        ]},
    ];

    const goItem = (id, extra) => {
        if (extra?.vivoFilter) { try{localStorage.setItem('tf_vivo_filter', extra.vivoFilter)}catch(e){} setVivoFilter?.(extra.vivoFilter); }
        if (extra?.extFilter) { try{localStorage.setItem('tf_ext_filter', extra.extFilter)}catch(e){} }
        setView(id);
        setOpenMenu(null);
        setMobileOpen(false);
    };

    const isInGroup = (g) => g.items.some(it => it.id === view);

    const userName = (typeof user === 'string') ? user : (user?.name || user?.agent_name || 'Usuario');
    const isAgent = (typeof user === 'object' && user?.role === 'agent');
    const inits = (s) => (s||'?').split(/[\s.@_-]+/).filter(Boolean).slice(0,2).map(p=>p[0]).join('').toUpperCase();

    return (
        <>
        <div className="tfbar">
            <div className="tfbar-logo" onClick={()=>setView('dashboard')}>
                <div className="tfbar-logo-mark"><span className="material-icons-round" style={{fontSize:16,color:'#fff'}}>sensors</span></div>
                <div>
                    <div className="tfbar-logo-text">TeleFlow</div>
                    <div className="tfbar-logo-sub">PBX Control</div>
                </div>
            </div>

            {!isAgent && <div className="tfbar-mobile-toggle" onClick={()=>setMobileOpen(v=>!v)}>
                <span className="material-icons-round">{mobileOpen?'close':'menu'}</span>
            </div>}

            <div className="tfbar-spacer" />

            {isAgent ? (
                <div className="tfbar-menu">
                    <div className={`tfbar-item ${view==='callcenter'?'active':''}`} onClick={()=>setView('callcenter')}>
                        <span className="material-icons-round">headset_mic</span>
                        <span>Mi Panel</span>
                    </div>
                </div>
            ) : (
            <div className="tfbar-menu">
                {groups.map(g => {
                    const active = isInGroup(g);
                    if (g.single && g.items.length === 1) {
                        const it = g.items[0];
                        return (
                            <div key={g.id} className={`tfbar-item ${view===it.id?'active':''}`} onClick={()=>goItem(it.id)}>
                                <span className="material-icons-round">{g.icon}</span>
                                <span>{g.label}</span>
                            </div>
                        );
                    }
                    const isOpen = openMenu === g.id;
                    return (
                        <div key={g.id} className={`tfbar-item ${active?'active':''} ${isOpen?'open':''}`}
                             onClick={(e)=>{
                                e.stopPropagation();
                                if (isOpen) { setOpenMenu(null); return; }
                                const r = e.currentTarget.getBoundingClientRect();
                                setMenuAnchor({ id: g.id, left: Math.max(8, Math.min(window.innerWidth - 280, r.left)), top: r.bottom + 6 });
                                setOpenMenu(g.id);
                             }}>
                            <span className="material-icons-round">{g.icon}</span>
                            <span>{g.label}</span>
                            <span className="material-icons-round chev">expand_more</span>
                        </div>
                    );
                })}
            </div>
            )}

            <div className="tfbar-spacer" />

            <div className="tfbar-pill" title="Extensión donde recibís las escuchas (ChanSpy)" onClick={e=>e.stopPropagation()}>
                <span className="material-icons-round" style={{fontSize:14, color:'var(--horizon-green)'}}>headset_mic</span>
                <Input
                    type="text"
                    value={spyExt}
                    onChange={e=>setSpyExt(e.target.value.replace(/\D/g,'').substring(0,6))}
                    placeholder="Ext. escucha"
                    title="Tu extensión SIP — al apretar Escuchar en un toast de llamada entrante, llama acá y vos escuchás la conversación"
                    className="h-7 w-[100px] px-2 text-xs font-mono font-bold border-0 shadow-none focus-visible:ring-0"
                />
                {spyExt && <span style={{width:6, height:6, borderRadius:'50%', background:'var(--horizon-green)', boxShadow:'0 0 6px var(--horizon-green)'}} title="Configurada"/>}
            </div>

            <div style={{position:'relative'}}>
                <div className="tfbar-avatar" onClick={(e)=>{ e.stopPropagation(); setShowUserMenu(v=>!v); }}>{inits(userName)}</div>
                {showUserMenu && (
                    <div className="tfbar-dropdown tfbar-user-pop" style={{right:0,left:'auto',minWidth:240}} onClick={e=>e.stopPropagation()}>
                        <div className="px-2.5 py-2 mb-1 border-b" style={{borderColor:'var(--border)'}}>
                            <div className="text-sm font-bold truncate" style={{color:'var(--foreground)'}}>{userName}</div>
                            <div className="flex items-center gap-1.5 mt-0.5">
                                <span className="rounded-full" style={{width:6,height:6,background:'#22c55e',boxShadow:'0 0 4px rgba(34,197,94,.5)'}}/>
                                <span className="text-[10px] font-semibold uppercase tracking-wider" style={{color:'var(--muted-foreground)'}}>
                                    {isAgent ? 'Agente' : 'Administrador'}
                                </span>
                            </div>
                        </div>
                        <button className="tfbar-drop-item w-full text-left" onClick={()=>{ setView('configuracion'); setShowUserMenu(false); }}>
                            <span className="material-icons-round">tune</span>
                            <div className="tfbar-drop-text"><div>Configuración</div></div>
                        </button>
                        <button className="tfbar-drop-item w-full text-left" onClick={()=>{ setDarkMode(!darkMode); }}>
                            <span className="material-icons-round">{darkMode?'light_mode':'dark_mode'}</span>
                            <div className="tfbar-drop-text"><div>Modo {darkMode?'Claro':'Oscuro'}</div></div>
                        </button>
                        <Separator className="my-1"/>
                        <button className="tfbar-drop-item w-full text-left" style={{color:'var(--destructive)'}} onClick={onLogout}>
                            <span className="material-icons-round" style={{color:'var(--destructive)'}}>logout</span>
                            <div className="tfbar-drop-text"><div style={{color:'var(--destructive)',fontWeight:600}}>Cerrar sesión</div></div>
                        </button>
                    </div>
                )}
            </div>
        </div>

        {openMenu && menuAnchor && menuAnchor.id === openMenu && (() => {
            const grp = groups.find(g => g.id === openMenu);
            if (!grp) return null;
            return (
                <div className="tfbar-dropdown" style={{ left: menuAnchor.left, top: menuAnchor.top }} onClick={e=>e.stopPropagation()}>
                    {grp.items.map(it => (
                        <div key={it.id} className={`tfbar-drop-item ${view===it.id?'active':''}`} onClick={()=>goItem(it.id)}>
                            <span className="material-icons-round">{it.icon}</span>
                            <div className="tfbar-drop-text">
                                <div>{it.label}</div>
                                {it.sub && <div className="tfbar-drop-sub">{it.sub}</div>}
                            </div>
                            {it.badge > 0 && <span className="tfbar-badge">{it.badge}</span>}
                        </div>
                    ))}
                </div>
            );
        })()}

        {mobileOpen && (
            <div className="tfbar-mobile-panel" onClick={e=>e.stopPropagation()}>
                {groups.map(g => (
                    <div key={g.id}>
                        <div className="tfbar-mobile-section">{g.label}</div>
                        {g.items.map(it => (
                            <div key={it.id} className={`tfbar-drop-item ${view===it.id?'active':''}`} onClick={()=>goItem(it.id)}>
                                <span className="material-icons-round">{it.icon}</span>
                                <div className="tfbar-drop-text">
                                    <div>{it.label}</div>
                                    {it.sub && <div className="tfbar-drop-sub">{it.sub}</div>}
                                </div>
                                {it.badge > 0 && <span className="tfbar-badge">{it.badge}</span>}
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        )}

        {showSysModal && (
            <div onClick={()=>setShowSysModal(false)} className="tf-modal-overlay">
                <div onClick={e=>e.stopPropagation()} style={{background:'var(--surface)',padding:0,borderRadius:18,width:560,maxWidth:'92%',border:'1px solid var(--border)',overflow:'hidden'}}>
                    <div style={{padding:'18px 22px',borderBottom:'1px solid var(--border)',background:'linear-gradient(135deg,rgba(34,197,94,0.15),transparent)'}}>
                        <div style={{display:'flex',alignItems:'center',gap:12}}>
                            <div style={{width:46,height:46,borderRadius:12,background:'linear-gradient(135deg,#22c55e,#16a34a)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{color:'#fff',fontSize:22}}>dns</span>
                            </div>
                            <div style={{flex:1}}>
                                <h2 style={{fontSize:17,fontWeight:900}}>Estado de la PBX</h2>
                                <div style={{fontSize:11,color:'var(--muted)'}}>Información del sistema en tiempo real</div>
                            </div>
                            <button onClick={()=>setShowSysModal(false)} style={{padding:6,borderRadius:8,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                                <span className="material-icons-round" style={{fontSize:18,color:'var(--muted)'}}>close</span>
                            </button>
                        </div>
                    </div>
                    <div style={{padding:'18px 22px'}}>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10}}>
                            {[
                                {l:'Estado', v:'Online', c:'#22c55e', i:'check_circle'},
                                {l:'Uptime', v:data?.system?.uptime||'—', c:'#3b82f6', i:'schedule'},
                                {l:'CPU Load', v:`${data?.system?.cpu||0}%`, c:'#8b5cf6', i:'memory'},
                                {l:'RAM', v:`${data?.system?.ram||0}%`, c:'#ec4899', i:'sd_storage'},
                                {l:'Disco', v:`${data?.system?.disk||0}%`, c:'#f59e0b', i:'storage'},
                                {l:'Conexiones TCP', v:data?.system?.connections||0, c:'#06b6d4', i:'lan'},
                                {l:'Brand', v:pbxBrand?(pbxBrand.brand+' '+(pbxBrand.variant||'')):'detectando...', c:'#a855f7', i:'router'},
                                {l:'Asterisk', v:pbxBrand?.asterisk_version||'—', c:'#10b981', i:'tag'},
                            ].map((s,i)=>(
                                <div key={i} className="glass" style={{padding:14,borderRadius:12}}>
                                    <div style={{display:'flex',alignItems:'center',gap:8,marginBottom:6}}>
                                        <span className="material-icons-round" style={{fontSize:16,color:s.c}}>{s.i}</span>
                                        <span style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em'}}>{s.l}</span>
                                    </div>
                                    <div style={{fontSize:14,fontWeight:800,color:s.c}}>{s.v}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        )}
        </>
    );
}
function toggleableTheme(darkMode, setDarkMode) {
    return (
        <div className="theme-toggle" onClick={()=>{ document.body.classList.add('theme-transition'); setDarkMode(!darkMode); setTimeout(()=>document.body.classList.remove('theme-transition'),500); }} title={darkMode?'Cambiar a modo claro':'Cambiar a modo oscuro'} style={{marginRight:0}}>
            <div className="stars"></div>
            <div className="clouds"></div>
            <div className="knob">
                <span className="material-icons-round moon">dark_mode</span>
                <span className="material-icons-round sun">light_mode</span>
            </div>
        </div>
    );
}

function CallCenterTopBar({ data, setView, setVivoFilter, darkMode, toggleTheme }) {
    const [agentsLogged, setAgentsLogged] = useState(0);
    const [pbxBrand, setPbxBrand] = useState(null);
    const [showSysModal, setShowSysModal] = useState(false);
    const liveCalls = data?.pbx?.live_calls || [];
    const queues = data?.pbx?.queues || [];
    const exts = data?.pbx?.extensions || [];

    useEffect(() => {
        const fetchAgents = () => {
            fetch('api/hotdesking.php?action=list',{credentials:'include'})
                .then(r=>r.json()).then(j=>{ if (j.status==='ok') setAgentsLogged((j.agents||[]).filter(a=>a.logged_in).length); })
                .catch(()=>{});
        };
        fetchAgents();
        const t = setInterval(fetchAgents, 10000);
        return () => clearInterval(t);
    }, []);
    useEffect(() => {
        fetch('api/index.php?action=detect_pbx',{credentials:'include'})
            .then(r=>r.json()).then(j=>{ if(j.success) setPbxBrand(j); }).catch(()=>{});
    }, []);

    const totalWaiting = queues.reduce((s,q) => s + (q.calls_waiting||0), 0);
    const upCount = liveCalls.filter(c => c.state === 'Up').length;
    const ringCount = liveCalls.filter(c => /Ring/.test(c.state||'')).length;
    const onlineExts = exts.filter(e => e.status === 'ONLINE').length;
    const busyExts = exts.filter(e => e.status === 'BUSY').length;

    const ClickStat = ({icon, label, value, color, sub, onClick, badge}) => (
        <button onClick={onClick} disabled={!onClick} style={{display:'flex',alignItems:'center',gap:8,padding:'8px 14px',borderRight:'1px solid var(--border)',background:'transparent',border:'none',cursor:onClick?'pointer':'default',transition:'background 0.15s',position:'relative'}} onMouseEnter={e=>{if(onClick) e.currentTarget.style.background='rgba(139,92,246,0.06)'}} onMouseLeave={e=>e.currentTarget.style.background='transparent'}>
            <div style={{width:32,height:32,borderRadius:8,background:`${color}22`,display:'flex',alignItems:'center',justifyContent:'center',position:'relative'}}>
                <span className="material-icons-round" style={{fontSize:17,color}}>{icon}</span>
                {badge && <span style={{position:'absolute',top:-3,right:-3,width:10,height:10,borderRadius:'50%',background:'#ef4444',border:'2px solid var(--surface)',animation:'pulse 1s infinite'}}/>}
            </div>
            <div style={{textAlign:'left'}}>
                <div style={{fontSize:9,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.04em'}}>{label}</div>
                <div style={{fontSize:14,fontWeight:900,color:'var(--text)',lineHeight:1}}>{value}{sub && <span style={{fontSize:10,color:'var(--muted)',fontWeight:600,marginLeft:4}}>{sub}</span>}</div>
            </div>
        </button>
    );

    const goVivo = (filter) => { try{localStorage.setItem('tf_vivo_filter', filter)}catch(e){} setVivoFilter?.(filter); setView?.('vivo'); };
    const goExtensiones = (filter) => { try{localStorage.setItem('tf_ext_filter', filter)}catch(e){} setView?.('extensiones'); };

    return (
        <>
        <div style={{position:'sticky',top:0,zIndex:50,background:'var(--surface)',borderBottom:'1px solid var(--border)',display:'flex',alignItems:'center',padding:'0 6px',marginBottom:14,boxShadow:'0 1px 0 var(--border), 0 4px 12px rgba(0,0,0,0.04)',overflow:'auto'}}>
            <ClickStat icon="phone_in_talk" label="En Llamada" value={upCount} color="#22c55e" badge={upCount>0} onClick={()=>goVivo('up')} />
            <ClickStat icon="ring_volume" label="Sonando" value={ringCount} color="#f59e0b" badge={ringCount>0} onClick={()=>goVivo('ringing')} />
            <ClickStat icon="hourglass_empty" label="En Espera" value={totalWaiting} color={totalWaiting>0?'#ef4444':'#6b7280'} badge={totalWaiting>0} onClick={()=>setView?.('colas')} />
            <ClickStat icon="support_agent" label="Agentes" value={agentsLogged} sub={'logueados'} color="#3b82f6" onClick={()=>setView?.('hotdesking')} />
            <ClickStat icon="dialpad" label="Internos" value={onlineExts} sub={`/ ${exts.length}`} color="#8b5cf6" onClick={()=>goExtensiones('ONLINE')} />
            <ClickStat icon="bar_chart" label="Activos PBX" value={busyExts + upCount} color="#ec4899" onClick={()=>goVivo('all')} />
            <div style={{flex:1}}/>

            {/* PBX Status pill clickeable */}
            <button onClick={()=>setShowSysModal(true)} style={{padding:'6px 10px',marginRight:6,borderRadius:8,background:'rgba(34,197,94,0.08)',border:'1px solid rgba(34,197,94,0.25)',cursor:'pointer',display:'flex',alignItems:'center',gap:7,color:'inherit'}}>
                <span style={{width:7,height:7,borderRadius:'50%',background:'#22c55e',boxShadow:'0 0 8px rgba(34,197,94,0.6)',animation:'pulse 2s infinite',flexShrink:0}}/>
                <div style={{textAlign:'left'}}>
                    <div style={{fontSize:8,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.04em',lineHeight:1}}>PBX</div>
                    <div style={{fontSize:10,color:'#22c55e',fontWeight:800,lineHeight:1.1,marginTop:1}}>Online · {data?.system?.uptime?.split(' ')[0]||'—'}d</div>
                </div>
                <div style={{display:'flex',flexDirection:'column',gap:1,marginLeft:4}}>
                    <div style={{fontSize:8,color:'var(--muted)',fontFamily:'monospace',fontWeight:700,lineHeight:1}}>CPU {data?.system?.cpu||0}%</div>
                    <div style={{fontSize:8,color:'var(--muted)',fontFamily:'monospace',fontWeight:700,lineHeight:1}}>RAM {data?.system?.ram||0}%</div>
                </div>
            </button>

            {/* PBX brand badge */}
            {pbxBrand && (() => {
                const brandColors = { grandstream:'#3b82f6', asterisk:'#6b7280' };
                const brandLabels = { grandstream:'Grandstream UCM', asterisk: pbxBrand.variant==='issabel'?'Issabel':(pbxBrand.variant==='freepbx'?'FreePBX':'Asterisk') };
                const c = brandColors[pbxBrand.brand] || '#8b5cf6';
                return (
                    <div title={`${brandLabels[pbxBrand.brand]} · Asterisk ${pbxBrand.asterisk_version} · parser ${pbxBrand.recommended_parser}`} style={{padding:'5px 10px',marginRight:6,borderRadius:7,background:`${c}15`,border:`1px solid ${c}55`,fontSize:10,color:c,fontWeight:800,display:'flex',alignItems:'center',gap:5}}>
                        <span className="material-icons-round" style={{fontSize:12}}>memory</span>
                        v{pbxBrand.asterisk_version}
                    </div>
                );
            })()}

            {/* Theme toggle */}
            {toggleTheme && (
                <div className="theme-toggle" onClick={toggleTheme} title={darkMode?'Cambiar a modo claro':'Cambiar a modo oscuro'} style={{marginRight:6}}>
                    <div className="stars"></div>
                    <div className="clouds"></div>
                    <div className="knob">
                        <span className="material-icons-round moon">dark_mode</span>
                        <span className="material-icons-round sun">light_mode</span>
                    </div>
                </div>
            )}

            {/* Realtime indicator */}
            <div style={{padding:'6px 10px',fontSize:9,color:'var(--muted)',display:'flex',alignItems:'center',gap:5,fontFamily:'monospace',marginRight:6}}>
                <span style={{width:5,height:5,borderRadius:'50%',background:'#22c55e',boxShadow:'0 0 6px rgba(34,197,94,0.6)',animation:'pulse 2s infinite'}}/>
                Realtime
            </div>
        </div>

        {/* Modal info de sistema PBX */}
        {showSysModal && (
            <div onClick={()=>setShowSysModal(false)} className="tf-modal-overlay">
                <div onClick={e=>e.stopPropagation()} style={{background:'var(--surface)',padding:0,borderRadius:18,width:560,maxWidth:'92%',border:'1px solid var(--border)',overflow:'hidden'}}>
                    <div style={{padding:'18px 22px',borderBottom:'1px solid var(--border)',background:'linear-gradient(135deg,rgba(34,197,94,0.15),transparent)'}}>
                        <div style={{display:'flex',alignItems:'center',gap:12}}>
                            <div style={{width:46,height:46,borderRadius:12,background:'linear-gradient(135deg,#22c55e,#16a34a)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{color:'#fff',fontSize:22}}>dns</span>
                            </div>
                            <div style={{flex:1}}>
                                <h2 style={{fontSize:17,fontWeight:900}}>Estado de la PBX</h2>
                                <div style={{fontSize:11,color:'var(--muted)'}}>Información del sistema en tiempo real</div>
                            </div>
                            <button onClick={()=>setShowSysModal(false)} style={{padding:6,borderRadius:8,border:'none',background:'rgba(255,255,255,0.05)',cursor:'pointer'}}>
                                <span className="material-icons-round" style={{fontSize:18,color:'var(--muted)'}}>close</span>
                            </button>
                        </div>
                    </div>
                    <div style={{padding:'18px 22px'}}>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10}}>
                            {[
                                {l:'Estado', v:'Online', c:'#22c55e', i:'check_circle'},
                                {l:'Uptime', v:data?.system?.uptime||'—', c:'#3b82f6', i:'schedule'},
                                {l:'CPU Load', v:`${data?.system?.cpu||0}%`, c:'#8b5cf6', i:'memory'},
                                {l:'RAM', v:`${data?.system?.ram||0}%`, c:'#ec4899', i:'sd_storage'},
                                {l:'Disco', v:`${data?.system?.disk||0}%`, c:'#f59e0b', i:'storage'},
                                {l:'Conexiones TCP', v:data?.system?.connections||0, c:'#06b6d4', i:'lan'},
                                {l:'Brand', v:pbxBrand?(pbxBrand.brand+' '+pbxBrand.variant):'detectando...', c:'#a855f7', i:'router'},
                                {l:'Asterisk', v:pbxBrand?.asterisk_version||'—', c:'#10b981', i:'tag'},
                            ].map((s,i)=>(
                                <div key={i} className="glass" style={{padding:14,borderRadius:12}}>
                                    <div style={{display:'flex',alignItems:'center',gap:8,marginBottom:6}}>
                                        <span className="material-icons-round" style={{fontSize:16,color:s.c}}>{s.i}</span>
                                        <span style={{fontSize:9,color:'var(--muted)',fontWeight:800,textTransform:'uppercase',letterSpacing:'.05em'}}>{s.l}</span>
                                    </div>
                                    <div style={{fontSize:14,fontWeight:800,color:s.c}}>{s.v}</div>
                                </div>
                            ))}
                        </div>
                        <div style={{marginTop:14,padding:'10px 12px',background:'color-mix(in srgb, var(--primary) 6%, transparent)',border:'1px solid color-mix(in srgb, var(--primary) 20%, transparent)',borderRadius:10,fontSize:10,color:'var(--muted)',display:'flex',gap:8,alignItems:'center'}}>
                            <span className="material-icons-round" style={{fontSize:14,color:'color-mix(in srgb, var(--primary) 60%, var(--foreground))'}}>info</span>
                            Datos via AMI a {pbxBrand?.brand||'asterisk'} en {data?.system?.connections||0} conexiones TCP activas
                        </div>
                    </div>
                </div>
            </div>
        )}
        </>
    );
}
function App() {
    const [vivoFilter, setVivoFilter] = useState(localStorage.getItem('tf_vivo_filter')||'all');
    const toggleTheme = () => setDarkMode(d=>!d);
    const [assignCall, setAssignCall] = useState(null);
    useEffect(() => { const h = (e) => setAssignCall(e.detail); window.addEventListener('tf-assign-call', h); return () => window.removeEventListener('tf-assign-call', h); }, []);
    const [reportAgent, setReportAgent] = useState(null);
    useEffect(() => {
        const h = (e) => { setReportAgent(e.detail); setView('reportes'); };
        window.addEventListener('tf-open-report', h);
        return () => window.removeEventListener('tf-open-report', h);
    }, []);
    const [user, setUser] = useState(() => {
        const u = localStorage.getItem('tf_user');
        return (u && u !== 'null') ? u : null;
    }); 
    const [view, setView] = useState(() => localStorage.getItem('tf_view') || 'dashboard');
    const [data, setData] = useState({ pbx:{ extensions:[], recordings:[], calls:[], queues:[] }, system:{} });
    const [collapsed, setCollapsed] = useState(() => localStorage.getItem('tf_collapsed') === '1');
    const [darkMode, setDarkMode] = useState(() => localStorage.getItem('tf_dark') !== '0');
    const [toast, setToast] = useState(null);
    const [activeCalls, setActiveCalls] = useState(0);
    const [reportQueue, setReportQueue] = useState(null);

    // Persist view & user to localStorage
    useEffect(() => { if (user) localStorage.setItem('tf_user', user); else localStorage.removeItem('tf_user'); }, [user]);
    useEffect(() => { localStorage.setItem('tf_view', view); }, [view]);
    useEffect(() => { localStorage.setItem('tf_collapsed', collapsed ? '1' : '0'); }, [collapsed]);
    useEffect(() => { localStorage.setItem('tf_dark', darkMode ? '1' : '0'); }, [darkMode]);

    // Dark/light toggle — sincroniza ambos sistemas: legacy (body.light) + shadcn (html.dark)
    useEffect(()=>{
        document.body.classList.toggle('light', !darkMode);
        document.documentElement.classList.toggle('dark', darkMode);
    },[darkMode]);

    // Toast helper
    const showToast = (msg, type='info') => {
        setToast({msg,type});
        setTimeout(()=>setToast(null),4000);
    };

    // Load data
    const load = useCallback(async () => {
        try {
            const res = await fetch('api/index.php?action=get_full_data', { credentials: 'include' });
            // HORIZON: handle 403/503 graciously
            if (res.status === 403) { setUser(null); try{localStorage.removeItem('tf_user_cache')}catch(e){}; return; }
            if (res.status === 503) { /* retry siguiente tick */ return; }
            const d = await res.json();
            if (d.status === 'error' && d.message === 'No autorizado') {
                setUser(null);
            } else {
                // HORIZON: agregar count de agentes logueados (lookup paralelo)
                fetch('api/hotdesking.php?action=list', { credentials:'include' })
                    .then(r=>r.json()).then(j=>{
                        if (j.status==='ok') {
                            d._agentsLogged = (j.agents||[]).filter(a=>a.logged_in).length;
                            setData({...d});
                        }
                    }).catch(()=>{});
                setData(d);
                // HORIZON: cargar ext_meta una vez
                if (!window._tfExtMeta) {
                    fetch('api/index.php?action=get_ext_meta',{credentials:'include'})
                        .then(r=>r.json()).then(j=>{ if(j.success) window._tfExtMeta = j.meta; }).catch(()=>{});
                }
                if (d.user) {
                    // HORIZON: garantizar shape válido + persistir local
                    let u = d.user;
                    if (typeof u === 'string') u = { name: u, role: 'admin' };
                    else if (typeof u === 'object' && u && !u.name && !u.agent_name) {
                        u = { name: u.tf_user || u.username || 'admin', role: u.role || 'admin' };
                    }
                    setUser(u);
                    try { localStorage.setItem('tf_user_cache', JSON.stringify(u)); } catch(e) {}
                }
            }
        } catch(e) {}
    }, []);

    // Check session on mount
    useEffect(()=>{
        load();
        const t = setInterval(load, 8000); // 8s polling — socket realtime hace el resto
        return () => clearInterval(t);
    }, [load]);

    // HORIZON: Realtime via Socket.io — actualiza data.pbx.extensions y live_calls sin esperar polling
    useEffect(() => {
        if (!user || typeof io === 'undefined') return;
        const socketUrl = window.location.protocol + '//' + window.location.hostname + ':3001';
        const socket = io(socketUrl, { path: '/teleflow-socket', transports: ['polling','websocket'], upgrade: true, reconnection: true });
        window._tfSocket = socket;
        socket.on('connect', () => console.log('[realtime] connected'));
        socket.on('disconnect', () => console.log('[realtime] disconnected'));

        socket.on('peer_update', (ev) => {
            setData(d => {
                if (!d?.pbx?.extensions) return d;
                const exts = d.pbx.extensions.map(e =>
                    e.ext === ev.ext ? { ...e, status: ev.status, ip: ev.ip || e.ip } : e
                );
                return { ...d, pbx: { ...d.pbx, extensions: exts } };
            });
        });

        socket.on('call_update', (ev) => {
            // HORIZON: Sileo notif (dedup por linkedid + acciones Asignar/Escuchar)
            if (ev.type === 'new' && ev.call && window.sileo && user?.role !== 'agent') {
                const c = ev.call;
                // Skip si ya hay notif para este linkedid/bridge
                const dedupKey = c.linkedId || c.bridge || c.id || c.channel;
                window._tfSeenCalls = window._tfSeenCalls || new Set();
                if (window._tfSeenCalls.has(dedupKey)) return;
                window._tfSeenCalls.add(dedupKey);
                setTimeout(() => window._tfSeenCalls.delete(dedupKey), 30000);

                // HORIZON: si el caller ext tiene rtsp_url configurado, abrir preview encima del toast
                const callerMeta = (window._tfExtMeta || {})[c.ext];
                if (callerMeta?.rtsp_url) {
                    window.dispatchEvent(new CustomEvent('tf-rtsp-preview-open', {
                        detail: {
                            id: dedupKey,
                            ext: c.ext,
                            url: callerMeta.rtsp_url,
                            label: callerMeta.rtsp_label || `Videoportero · ext ${c.ext}`,
                            channel: c.channel
                        }
                    }));
                }

                const callerLabel = c.name || c.ext || 'Caller';
                const destLabel = fmtDest(c);
                // HORIZON: discriminar tipo de origen (cliente / horizon / sin asignar)
                const fromTipo = (window._tfExtMeta || {})[c.ext]?.tipo || '';
                const tipoLabel = fromTipo === 'cliente' ? '👤 Cliente' : (fromTipo === 'horizon' ? '🏢 Horizon' : '');
                window.sileo.push({
                    kind: 'call',
                    icon: 'phone_in_talk',
                    title: tipoLabel ? `Llamada · ${tipoLabel}` : 'Llamada entrante',
                    msg: `${callerLabel} → ${destLabel}`,
                    actions: [
                        { label: 'Asignar', primary: true, onClick: () => { window.dispatchEvent(new CustomEvent('tf-assign-call', {detail: c})); } },
                        { label: 'Escuchar', onClick: () => { window.dispatchEvent(new CustomEvent('tf-spy-call', {detail: c})); } },
                        { label: 'Ignorar' }
                    ],
                    duration: 12000
                });
            }
            setData(d => {
                if (!d?.pbx) return d;
                let live = d.pbx.live_calls || [];
                if (ev.type === 'hangup') {
                    // Cerrar preview RTSP si estaba abierto
                    window.dispatchEvent(new CustomEvent('tf-rtsp-preview-close', { detail: { id: ev.channel } }));
                    live = live.filter(c => c.channel !== ev.channel && c.id !== ev.id);
                } else if (ev.type === 'new' && ev.call) {
                    if (!live.find(c => c.channel === ev.call.channel || c.id === ev.call.id)) {
                        live = [...live, {
                            channel: ev.call.channel,
                            ext: ev.call.ext,
                            state: ev.call.state,
                            callerid: ev.call.name || ev.call.ext,
                            dest: ev.call.exten,
                            duration: '0:00',
                            id: ev.call.id
                        }];
                    }
                } else if (ev.type === 'state' || ev.type === 'bridge' || ev.type === 'unbridge') {
                    live = live.map(c => (c.id === ev.id || c.channel === ev.channel) 
                        ? { ...c, state: ev.state || c.state, isBridged: ev.type === 'bridge' } 
                        : c);
                }
                return { ...d, pbx: { ...d.pbx, live_calls: live } };
            });
        });


        // HORIZON: realtime push from AGI / TeleFlow notify endpoint
        socket.on('agent_login', (ev) => {
            console.log('[realtime] agent_login', ev);
            window.dispatchEvent(new CustomEvent('tf-queues-refresh'));
            if (window.sileo && user?.role !== 'agent') {
                window.sileo.push({ kind:'info', icon:'login', title:'Agente logueado', msg: `#${ev.agent} en ext ${ev.ext} (${(ev.queues||'').split(',').length} colas)`, duration: 4000 });
            }
        });
        socket.on('agent_logout', (ev) => {
            console.log('[realtime] agent_logout', ev);
            window.dispatchEvent(new CustomEvent('tf-queues-refresh'));
            if (window.sileo && user?.role !== 'agent') {
                window.sileo.push({ kind:'info', icon:'logout', title:'Agente desconectado', msg: `Ext ${ev.ext} salió de ${(ev.queues||'').split(',').length} colas`, duration: 4000 });
            }
        });
        socket.on('tf-realtime-refresh', (ev) => {
            console.log('[realtime] catch-all refresh', ev?.source);
            // Refresh general state
            load();
        });

                socket.on('queue_update', (ev) => {
            setData(d => {
                if (!d?.pbx?.queues) return d;
                const queues = d.pbx.queues.map(q => {
                    if (q.id !== ev.queue) return q;
                    if (ev.type === 'join') return { ...q, calls_waiting: (q.calls_waiting||0) + 1 };
                    if (ev.type === 'abandon' || ev.type === 'connect') return { ...q, calls_waiting: Math.max(0, (q.calls_waiting||0) - 1) };
                    return q;
                });
                return { ...d, pbx: { ...d.pbx, queues } };
            });
        });

        return () => socket.disconnect();
    }, [user]);

    // Register SW
    /* El Service Worker ahora es manejado por la PWA en /softphone/
    useEffect(()=>{
        if('serviceWorker' in navigator){
            navigator.serviceWorker.register('sw.js').catch(()=>{});
        }
    },[]);
    */

    if (user === null) return (<><Login onLogin={u => { setUser(u); if (u.role === 'agent') setView('callcenter'); load(); }} /></>);

    const renderView = () => {
        switch(view) {
            case 'dashboard':   return <ViewDashboard data={data} />;
            case 'extensiones': return <ViewExtensiones data={data} toast={showToast} />;
            case 'agentes':     return <ViewAgentes toast={showToast} data={data} />;
            case 'vivo':        return <ViewVivo2 data={data} toast={showToast} initialFilter={vivoFilter} />;
            case 'radar':       return <ViewRadar data={data} toast={showToast} />;
            case 'cdr':         return <ViewCDR toast={showToast} />;
            case 'reportes':    return <ViewReportes toast={showToast} queue={reportQueue} onClearQueue={()=>setReportQueue(null)} agentReport={reportAgent} onClearAgent={()=>setReportAgent(null)} />;
            case 'colas':       return <ViewColas toast={showToast} data={data} onReport={(qid)=>{setReportQueue(qid);setView('reportes');}} />;
            case 'grupos':      return <ViewGrupos toast={showToast} />;
            case 'ivr':         return <ViewIVR toast={showToast} />;
// El Softphone ahora es una PWA independiente en /softphone/

            case 'callcenter':  return <ViewCallCenter user={user} data={data} onLogout={() => { setUser(null); }} />;
            case 'hotdesking':   return <ViewHotdesking data={data} toast={showToast} />;
            case 'configuracion': return <ViewConfiguracion />;
            default:            return <div className="content-area">Vista no implementada</div>;
        }
    };

    return (
        <div id="app" className="tfshell">
            <TopBarMenu
                view={view} setView={setView} user={user}
                onLogout={async () => { await fetch('api/index.php?action=logout', { credentials: 'include' }); setUser(null); try{localStorage.removeItem('tf_user_cache')}catch(e){} }}
                darkMode={darkMode} setDarkMode={setDarkMode}
                data={data}
                activeCalls={data?.pbx?.live_calls?.length || 0}
                setVivoFilter={setVivoFilter}
            />
            <main className="main-content">
                <div className="main-scroll">
                    <PageHeader view={view} />
                    {renderView()}
                </div>
                <AssignCallModal open={!!assignCall} call={assignCall} onClose={()=>setAssignCall(null)} queues={data?.pbx?.queues||[]} extensions={data?.pbx?.extensions||[]} toast={showToast} />
            </main>

            {toast && (
                <div className="toast-container">
                    <div className={`toast toast-${toast.type || 'info'}`}>
                        <span className="material-icons-round">{toast.type==='success'?'check_circle':toast.type==='error'?'error':'info'}</span>
                        {toast.msg}
                    </div>
                </div>
            )}
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("root")).render(<ThemeProvider><SileoProvider><App /></SileoProvider><Toaster /><RtspPreviewLayer /></ThemeProvider>);
</script>

</body>
</html>

