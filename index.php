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
    <meta name="theme-color" content="#11B328" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0a0a0d" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <title>Teleflow Horizon · PBX Control</title>
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
    <script>
      // ─── PWA Service Worker: register + auto-update on next reload ─────
      if ('serviceWorker' in navigator && location.protocol !== 'file:') {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
              if (reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
              reg.addEventListener('updatefound', () => {
                const nw = reg.installing;
                if (!nw) return;
                nw.addEventListener('statechange', () => {
                  if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                    window.dispatchEvent(new CustomEvent('tf-sw-update', { detail: { reg } }));
                  }
                });
              });
            }).catch(err => console.warn('[SW] register failed:', err));

          let refreshing = false;
          navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            refreshing = true;
            if (window._tfUpdateAccepted) location.reload();
          });
        });
      }
    </script>
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
    // Pre-paint theme robusto: aplica .dark/.light a html (shadcn) y body.light (legacy)
    // ANTES del primer render para evitar flash y race conditions
    (function() {
        try {
            var dark = localStorage.getItem('tf_dark');
            var isDark = dark !== '0'; // default = dark si no hay valor previo
            var html = document.documentElement;
            if (isDark) {
                html.classList.add('dark');
                html.classList.remove('light');
            } else {
                html.classList.add('light');
                html.classList.remove('dark');
            }
            var applyBody = function() {
                if (!document.body) return;
                document.body.classList.toggle('light', !isDark);
                document.body.classList.toggle('dark', isDark);
            };
            applyBody(); // si body ya existe (poco probable en head)
            document.addEventListener('DOMContentLoaded', applyBody);
        } catch(e) {}
    })();
    </script>
    <style>
    /* HORIZON: shadcn/ui design tokens (colores completos para compat con legacy var(--x)) */
    :root, .light {
        --background: #ffffff;
        --foreground: #1A1A1A;           /* Horizon black */
        --card: #ffffff;
        --card-foreground: #1A1A1A;
        --popover: #ffffff;
        --popover-foreground: #1A1A1A;
        --primary: #11B328;              /* Horizon green */
        --primary-foreground: #ffffff;
        --secondary: #E6E7E8;            /* Horizon bg light */
        --secondary-foreground: #1A1A1A;
        --muted-foreground: #6b7280;
        --accent: #E6E7E8;
        --accent-foreground: #1A1A1A;
        --destructive: #ef4444;
        --destructive-foreground: #ffffff;
        --success: #11B328;
        --success-foreground: #ffffff;
        --warning: #f59e0b;
        --warning-foreground: #1A1A1A;
        --input: #d4d4d8;
        --ring: #11B328;
        --radius: 0.5rem;
        /* Horizon brand colors */
        --horizon-green: #11B328;
        --horizon-green-glow: rgba(17, 179, 40, 0.45);
        --horizon-black: #1A1A1A;
        --horizon-bg-light: #E6E7E8;
    }
    .dark {
        --background: #0d0d0d;           /* near-black */
        --foreground: #f5f5f5;
        --card: #1A1A1A;                 /* Horizon black */
        --card-foreground: #f5f5f5;
        --popover: #1A1A1A;
        --popover-foreground: #f5f5f5;
        --primary: #11B328;              /* Horizon green — mismo en ambos themes */
        --primary-foreground: #0d0d0d;
        --secondary: #242424;
        --secondary-foreground: #f5f5f5;
        --muted-foreground: #a3a3a3;
        --accent: #242424;
        --accent-foreground: #f5f5f5;
        --destructive: #ef4444;
        --destructive-foreground: #ffffff;
        --success: #11B328;
        --success-foreground: #0d0d0d;
        --warning: #f59e0b;
        --warning-foreground: #0d0d0d;
        --input: #2a2a2a;
        --ring: #11B328;
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
            --bg: #0d0d0d;
            --surface: #1A1A1A;
            --surface2: #242424;
            --border: rgba(255,255,255,0.08);
            --accent: #11B328;
            --accent2: #0d8a1f;
            --accent-glow: rgba(17,179,40,0.35);
            --text: #f5f5f5;
            --muted: #a3a3a3;
            --green: #22c55e;
            --red: #ef4444;
            --yellow: #f59e0b;
            --blue: #3b82f6;
            --sidebar-w: 230px;
        }
        body.light {
            --bg: #ffffff;
            --surface: #ffffff;
            --surface2: #E6E7E8;
            --border: rgba(0,0,0,0.10);
            --text: #1A1A1A;
            --muted: #6b7280;
            --accent: #11B328;
            --accent2: #0d8a1f;
            --accent-glow: rgba(17,179,40,0.18);
        }
        body.light .login-bg { background: radial-gradient(ellipse 80% 60% at 50% -10%,color-mix(in srgb, var(--primary) 18%, transparent) 0%,transparent 70%),#f5f7fb; }
        body.light .glass { background-color: var(--card) !important; border-color: var(--border) !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
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
        body { font-family: 'Inter', sans-serif; background: var(--background); color: var(--foreground); overflow: hidden; height: 100vh; transition: background 0.4s ease, color 0.4s ease; }
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
        .context-menu-item:hover { background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--text); }
        .context-menu-item.danger:hover { background: rgba(239,68,68,0.1); color: #f87171; }

        /* ── LOGIN ── */
        .login-bg {
            background: radial-gradient(ellipse 80% 60% at 50% -10%, color-mix(in srgb, var(--primary) 25%, transparent) 0%, transparent 70%),
                        radial-gradient(ellipse 50% 40% at 80% 80%, color-mix(in srgb, var(--primary) 15%, transparent) 0%, transparent 60%),
                        var(--bg);
        }
        .login-card {
            background: rgba(15,15,26,0.7);
            backdrop-filter: blur(40px) saturate(180%);
            border: 1px solid color-mix(in srgb, var(--primary) 20%, transparent);
            box-shadow: 0 0 80px color-mix(in srgb, var(--primary) 10%, transparent), 0 40px 80px rgba(0,0,0,0.6);
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
        @keyframes tf-status-shake {
            0%, 100% { transform: rotate(0deg) scale(1); }
            25% { transform: rotate(-8deg) scale(1.05); }
            75% { transform: rotate(8deg) scale(1.05); }
        }
        @keyframes tf-status-breath {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 8px currentColor); }
            50% { transform: scale(1.06); filter: drop-shadow(0 0 16px currentColor); }
        }
        @keyframes tf-door-left {
            0%   { transform: perspective(900px) rotateY(0deg); }
            18%  { transform: perspective(900px) rotateY(-6deg); }
            72%  { transform: perspective(900px) rotateY(-92deg); }
            100% { transform: perspective(900px) rotateY(-92deg); opacity:0; }
        }
        @keyframes tf-door-right {
            0%   { transform: perspective(900px) rotateY(0deg); }
            18%  { transform: perspective(900px) rotateY(6deg); }
            72%  { transform: perspective(900px) rotateY(92deg); }
            100% { transform: perspective(900px) rotateY(92deg); opacity:0; }
        }
        @keyframes tf-door-flash {
            0%, 100% { opacity:0; }
            20%      { opacity:0.55; }
            70%      { opacity:0.0; }
        }
        @keyframes tf-door-icon {
            0%   { transform: scale(0.4) rotate(-25deg); opacity:0; }
            30%  { transform: scale(1.15) rotate(8deg);  opacity:1; }
            70%  { transform: scale(1.0)  rotate(0deg);  opacity:1; }
            100% { transform: scale(0.85) rotate(0deg);  opacity:0; }
        }
        .tf-tooltip::after {
            content: "";
            position: absolute;
            left: 50%;
            top: -4px;
            width: 8px;
            height: 8px;
            transform: translateX(-50%) rotate(45deg);
            background: inherit;
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
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 15%, transparent);
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
        .tfbar-item { position: relative; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 12.5px; font-weight: 700; color: var(--foreground); display: flex; align-items: center; gap: 7px; transition: all .15s ease; white-space: nowrap; user-select: none; }
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
            .tfbar-mobile-toggle:hover { background: color-mix(in srgb, var(--primary) 10%, transparent); }
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
        /* .glass legacy override removed — uses shadcn token from line 327 */
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
        .agent-row:hover { border-color: color-mix(in srgb, var(--primary) 35%, transparent); background: var(--surface2); }
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
            background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 10%, transparent), color-mix(in srgb, var(--primary) 5%, transparent));
            border: 1px solid color-mix(in srgb, var(--primary) 30%, transparent);
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
        .modal-box { background: var(--surface); border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent); border-radius: 20px; padding: 28px; max-width: 520px; width: 100%; box-shadow: 0 40px 80px rgba(0,0,0,.6); }

        /* ── DRAWER ── */
        .drawer-backdrop { position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(10px);z-index:300; }
        .drawer {
            position:fixed;right:0;top:0;bottom:0;
            width:100%; max-width:440px;
            background:var(--surface);
            border-left:1px solid color-mix(in srgb, var(--primary) 25%, transparent);
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
        .drawer-body::-webkit-scrollbar-thumb { background:color-mix(in srgb, var(--primary) 30%, transparent); border-radius:4px; }
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
        .tf-table thead { background: color-mix(in srgb, var(--primary) 5%, transparent); border-bottom: 1px solid var(--border); }
        body.light .tf-table thead { background: #f0f2f7; }
        .tf-table th { padding: 12px 14px; text-align: left; font-size: 10.5px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .tf-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); }
        .tf-table tbody tr { transition: background 0.15s; }
        .tf-table tbody tr:hover { background: color-mix(in srgb, var(--primary) 4%, transparent); }
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
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
            color: var(--card-foreground);
            pointer-events: auto;
            animation: sileo-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex; gap: 12px; align-items: flex-start;
        }
        html.dark .sileo-notif {
            box-shadow: 0 12px 40px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.25);
        }
        .sileo-notif.dismissing { animation: sileo-out 0.3s ease forwards; }
        .sileo-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 75%, #000)); color: #fff;
        }
        .sileo-notif.call .sileo-icon {
            background: linear-gradient(135deg, var(--horizon-green), color-mix(in srgb, var(--horizon-green) 65%, #000));
            animation: sileo-pulse-ring 1.4s infinite;
        }
        .sileo-notif.warning .sileo-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .sileo-notif.error .sileo-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .sileo-body { flex: 1; min-width: 0; }
        .sileo-title { font-size: 12.5px; font-weight: 800; letter-spacing: -0.2px; margin-bottom: 2px; color: var(--foreground); }
        .sileo-msg { font-size: 11.5px; line-height: 1.35; color: var(--muted-foreground); }
        .sileo-actions { display: flex; gap: 6px; margin-top: 8px; }
        .sileo-btn { padding: 5px 12px; border-radius: 8px; border: none; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.15s; }
        .sileo-btn.primary { background: var(--primary); color: var(--primary-foreground); }
        .sileo-btn.primary:hover { background: color-mix(in srgb, var(--primary) 85%, #000); }
        .sileo-btn.secondary { background: var(--secondary); color: var(--secondary-foreground); border: 1px solid var(--border); }
        .sileo-btn.secondary:hover { background: var(--accent); }
        .sileo-close { 
            width: 22px; height: 22px; border-radius: 50%; border: none; 
            background: color-mix(in srgb, var(--muted) 35%, transparent); color: var(--muted-foreground); cursor: pointer; 
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            font-size: 14px; transition: all 0.15s;
        }
        .sileo-close:hover { background: var(--accent); color: var(--accent-foreground); }
        body.light .sileo-close { background: rgba(0,0,0,0.06); }
        body.light .sileo-close:hover { background: rgba(0,0,0,0.1); color: #111; }
        .sileo-time { font-size: 9.5px; opacity: 0.55; font-variant-numeric: tabular-nums; }


    </style>
</head>
<body>
<div id="root"></div>
<div id="tf-modal-root" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:2147483647;"></div>
<script src="sw.js"></script>
<script type="text/babel" data-presets="env,react" src="assets/app.jsx?v=<?php echo @file_get_contents(__DIR__.'/sw.js') ? preg_replace('/.*teleflow-cache-(v\d+).*/s', '$1', file_get_contents(__DIR__.'/sw.js')) : time(); ?>"></script>

</body>
</html>

