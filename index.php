<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0f">
    <title>TeleFlow · Next-Gen PBX Control</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">📞</text></svg>'>
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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                brand: {
                  bg: '#050508',
                  surface: '#0d0d14',
                  accent: '#7c3aed',
                  success: '#10b981',
                  warning: '#f59e0b',
                  danger: '#ef4444'
                }
              },
              fontFamily: {
                sans: ['Inter', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'monospace']
              }
            }
          }
        }
    </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/style.css">
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sip.js/0.20.0/sip.min.js"></script>
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
            --bg: #f1f3f9;
            --surface: #ffffff;
            --surface2: #f8f9fc;
            --border: rgba(0,0,0,0.08);
            --text: #111827;
            --muted: #6b7280;
        }
        body.light .login-bg { background: radial-gradient(ellipse 80% 60% at 50% -10%,rgba(139,92,246,0.18) 0%,transparent 70%),#f1f3f9; }
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
        /* Selects same as inputs */
        select.input-tf option { background: var(--surface); color: var(--text); }
        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all .3s;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(139,92,246,0.45); }
        .btn-primary::after { content:''; position:absolute; inset:0; background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent); }

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
        .nav-item:hover { background: rgba(139,92,246,0.1); color: var(--text); }
        .nav-item.active { background: rgba(139,92,246,0.18); color: #c4b5fd; }
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
            background: rgba(7,7,13,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .content-area { padding: 24px 28px; flex: 1; }

        /* ── GLASS CARDS ── */
        .glass { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
        .glass-hover { transition: border-color .2s, transform .2s, box-shadow .2s; }
        .glass-hover:hover { border-color: rgba(139,92,246,0.35); transform: translateY(-1px); box-shadow: 0 8px 30px rgba(139,92,246,0.1); }

        /* ── STATUS BADGES ── */
        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.04em; }
        .badge-online { background:rgba(34,197,94,0.15); color:#4ade80; }
        .badge-offline { background:rgba(107,114,128,0.15); color:#9ca3af; }
        .badge-busy { background:rgba(245,158,11,0.15); color:#fbbf24; }
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
    </style>
</head>
<body>
<div id="root"></div>
<script src="sw.js"></script>
<script type="text/babel">
const { useState, useEffect, useRef, useCallback } = React;

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
const initials = (n='') => n.split(' ').map(x=>x[0]).join('').substring(0,2).toUpperCase();

// ─────────────────────────────────────────────
// LOGIN
// ─────────────────────────────────────────────
function Login({ onLogin }) {
    const [user, setUser] = useState('');
    const [pass, setPass] = useState('');
    const [err, setErr] = useState('');
    const [loading, setLoading] = useState(false);
    const [showPass, setShowPass] = useState(false);

    const submit = async (e) => {
        e.preventDefault(); setErr(''); setLoading(true);
        const fd = new FormData();
        fd.append('username', user); fd.append('password', pass);
        try {
            const r = await fetch('api/index.php?action=login', { 
                method: 'POST', 
                body: fd,
                credentials: 'include'
            });
            const d = await r.json();
            if (d.status === 'success') onLogin(d.user);
            else setErr('Credenciales incorrectas. Verificá usuario y contraseña.');
        } catch { setErr('Error de conexión con el servidor.'); }
        setLoading(false);
    };

    return (
        <div className="login-bg h-screen flex items-center justify-center relative overflow-hidden">
            {/* Orbs decorativos */}
            <div className="login-orb" style={{width:500,height:500,background:'radial-gradient(circle,rgba(139,92,246,0.2),transparent)',top:'-10%',left:'-5%'}} />
            <div className="login-orb" style={{width:400,height:400,background:'radial-gradient(circle,rgba(109,40,217,0.15),transparent)',bottom:'-5%',right:'-5%',animationDelay:'4s'}} />

            {/* Líneas de grid decorativas */}
            <div style={{position:'absolute',inset:0,backgroundImage:'linear-gradient(rgba(139,92,246,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(139,92,246,0.03) 1px,transparent 1px)',backgroundSize:'50px 50px',pointerEvents:'none'}} />

            <div className="login-card rounded-[32px] p-10 w-full max-w-[420px] relative z-10">
                {/* Logo */}
                <div className="anim-fadeup flex flex-col items-center mb-10">
                    <div style={{width:68,height:68,background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',borderRadius:20,display:'flex',alignItems:'center',justifyContent:'center',marginBottom:20,boxShadow:'0 0 40px rgba(139,92,246,0.5)'}}>
                        <span className="material-icons-round" style={{fontSize:34,color:'white'}}>sensors</span>
                    </div>

                    {/* Pulse rings */}
                    <div style={{position:'relative',display:'inline-flex',alignItems:'center',justifyContent:'center',marginBottom:6}}>
                        <div style={{position:'absolute',width:16,height:16,borderRadius:'50%',background:'var(--accent)',boxShadow:'0 0 12px var(--accent)',animation:'pulse-ring 2s ease-out infinite'}} />
                        <div style={{position:'absolute',width:16,height:16,borderRadius:'50%',background:'var(--accent)',boxShadow:'0 0 12px var(--accent)',animation:'pulse-ring 2s ease-out infinite',animationDelay:'.6s'}} />
                    </div>

                    <h1 style={{fontSize:36,fontWeight:900,letterSpacing:-1,color:'white',fontStyle:'italic',marginTop:10}}>TeleFlow</h1>
                    <p style={{fontSize:10,fontWeight:700,letterSpacing:'0.25em',color:'#6b7280',textTransform:'uppercase',marginTop:4}}>Next-Gen PBX Control · Infratec</p>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="anim-fadeup-2 space-y-4">
                    <div style={{position:'relative'}}>
                        <span className="material-icons-round" style={{position:'absolute',left:14,top:'50%',transform:'translateY(-50%)',fontSize:18,color:'#4b5563'}}>person</span>
                        <input
                            className="input-tf py-3.5 pl-11 pr-4 rounded-[14px] text-sm"
                            type="text"
                            placeholder="Usuario"
                            value={user}
                            onChange={e=>setUser(e.target.value)}
                            required
                        />
                    </div>
                    <div style={{position:'relative'}}>
                        <span className="material-icons-round" style={{position:'absolute',left:14,top:'50%',transform:'translateY(-50%)',fontSize:18,color:'#4b5563'}}>lock</span>
                        <input
                            className="input-tf py-3.5 pl-11 pr-12 rounded-[14px] text-sm"
                            type={showPass?'text':'password'}
                            placeholder="Contraseña"
                            value={pass}
                            onChange={e=>setPass(e.target.value)}
                            required
                        />
                        <button type="button" onClick={()=>setShowPass(!showPass)} style={{position:'absolute',right:12,top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',color:'#4b5563',padding:0}}>
                            <span className="material-icons-round" style={{fontSize:18}}>{showPass?'visibility_off':'visibility'}</span>
                        </button>
                    </div>

                    {err && (
                        <div style={{background:'rgba(239,68,68,0.1)',border:'1px solid rgba(239,68,68,0.3)',borderRadius:10,padding:'10px 14px',fontSize:12,color:'#f87171',display:'flex',alignItems:'center',gap:8}}>
                            <span className="material-icons-round" style={{fontSize:16}}>error_outline</span>
                            {err}
                        </div>
                    )}

                    <button type="submit" className="btn-primary w-full py-3.5 rounded-[14px] text-sm uppercase tracking-widest mt-2" disabled={loading}>
                        {loading
                            ? <span style={{display:'flex',alignItems:'center',justifyContent:'center',gap:8}}>
                                <span className="material-icons-round" style={{fontSize:18,animation:'spin-slow 1s linear infinite'}}>refresh</span> Verificando...
                              </span>
                            : 'Acceder al Sistema'}
                    </button>
                </form>

                <p className="anim-fadeup-3" style={{textAlign:'center',marginTop:24,fontSize:11,color:'#374151'}}>
                    TeleFlow v18 · © Infratec {new Date().getFullYear()}
                </p>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// SILEO TOAST (notificaciones premium)
// ─────────────────────────────────────────────
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

    const nav = [
        { section: 'Principal' },
        { id:'dashboard', icon:'grid_view', label:'Dashboard' },
        { id:'extensiones', icon:'group', label:'Extensiones', badge: extsOnline, badgeColor: '#22c55e' },
        { id:'agentes', icon:'support_agent', label:'Agentes' },
        { section: 'Call Center' },
        { id:'vivo', icon:'sensors', label:'Llamas en Vivo', badge: activeCalls, badgeColor: '#ef4444' },
        { id:'colas', icon:'queue', label:'Colas', badge: qWaiting, badgeColor: '#f59e0b' },
        { id:'grupos', icon:'ring_volume', label:'Grupos' },
        { id:'ivr', icon:'account_tree', label:'IVR' },
        { id:'radar', icon:'radar', label:'Tráfico', icColor: '#3b82f6' },
        { section: 'Herramientas' },
        { id:'cdr', icon:'history', label:'CDR' },
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
                {/* System Stats (only when expanded) */}
                {!collapsed && data?.system && (
                    <div className="mb-4 px-2 anim-fadeup" style={{animationDelay:'0.4s'}}>
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">PBX Status</span>
                            <div className="flex items-center gap-1.5">
                                <span className="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)] anim-blink" />
                                <span className="text-[10px] text-green-500 font-bold uppercase">Online</span>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div className="p-2 rounded-xl bg-white/5 border border-white/5">
                                <div className="text-[8px] text-gray-500 font-bold uppercase mb-0.5">CPU Load</div>
                                <div className="text-xs font-black text-purple-400">{data?.system?.cpu}%</div>
                            </div>
                            <div className="p-2 rounded-xl bg-white/5 border border-white/5">
                                <div className="text-[8px] text-gray-500 font-bold uppercase mb-0.5">Uptime</div>
                                <div className="text-xs font-black text-blue-400">{data?.system?.uptime?.split(' ')[0]}d</div>
                            </div>
                        </div>
                    </div>
                )}

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
                            <div style={{fontSize:13, fontWeight:800, color:'var(--text)', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{user}</div>
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
                    border:'1px solid rgba(139,92,246,0.3)', background:'rgba(15,15,25,0.9)', backdropFilter:'blur(20px)',
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
    const exts = data?.pbx?.extensions || [];
    const online = exts.filter(e=>e.status==='ONLINE').length;
    const busy = exts.filter(e=>e.status==='BUSY').length;
    const recs = data?.pbx?.recordings || [];
    const cpu = data?.system?.cpu || 0;
    const ram = data?.system?.ram || 0;
    const disk = data?.system?.disk || 0;
    const conn = data?.system?.connections || 0;
    const uptime = data?.system?.uptime || 'Desconocido';

    const mainStats = [
        { label:'Extensiones Online', val:`${online}/${exts.length}`, icon:'group', color:'#22c55e', bg:'rgba(34,197,94,0.12)' },
        { label:'En Llamada', val:busy, icon:'call', color:'#f59e0b', bg:'rgba(245,158,11,0.12)' },
        { label:'Grabaciones Hoy', val:recs.length, icon:'mic', color:'#8b5cf6', bg:'rgba(139,92,246,0.12)' },
    ];

    const systemStats = [
        { label:'CPU PBX', val:`${cpu}%`, icon:'memory', color:'#3b82f6', bg:'rgba(59,130,246,0.12)' },
        { label:'RAM PBX', val:`${ram}%`, icon:'memory', color:'#3b82f6', bg:'rgba(59,130,246,0.12)' },
        { label:'Disco', val:`${disk}%`, icon:'storage', color:'#3b82f6', bg:'rgba(59,130,246,0.12)' },
        { label:'Conexiones', val:conn, icon:'router', color:'#3b82f6', bg:'rgba(59,130,246,0.12)' },
    ];

    return (
        <div className="content-area">
            {/* Main Stats PBX */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:16,marginBottom:16}}>
                {mainStats.map(s=>(
                    <div key={s.label} className="glass stat-card glass-hover">
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                            <div className="stat-label">{s.label}</div>
                            <div style={{width:36,height:36,borderRadius:10,background:s.bg,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:18,color:s.color}}>{s.icon}</span>
                            </div>
                        </div>
                        <div className="stat-val" style={{color:s.color}}>{s.val}</div>
                    </div>
                ))}
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
                        <span className="material-icons-round" style={{fontSize:16,color:'#8b5cf6'}}>group</span>
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
                        <span className="material-icons-round" style={{fontSize:16,color:'#8b5cf6'}}>mic</span>
                        Últimas Grabaciones
                    </div>
                    <div style={{display:'flex',flexDirection:'column',gap:8}}>
                        {recs.slice(0,5).map((r,i)=>(
                            <div key={i} style={{padding:'8px 10px',background:'var(--surface2)',borderRadius:10,border:'1px solid var(--border)'}}>
                                <div style={{fontSize:11,fontWeight:700,color:'white'}}>#{r.src} → {r.dst}</div>
                                <div style={{fontSize:10,color:'#6b7280',display:'flex',justifyContent:'space-between',marginTop:2}}>
                                    <span>{r.calldate?.substring(0,16)}</span>
                                    <span style={{color:'#c4b5fd'}}>{r.duration}s</span>
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
// FICHA DEL INTERNO — Página dedicada (no modal)
// ─────────────────────────────────────────────
function ExtEditPage({ ext, onBack, onSaved, toast }) {
    const isNew = !ext;
    const [form, setForm] = useState({ ext: ext?.ext||'', name: ext?.name||'', secret: '', email: '' });
    const [recording, setRecording] = useState(ext?.recording||'dontcare');
    const [devType, setDevType] = useState('webrtc');
    const [showPass, setShowPass] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        if (!isNew && ext.ext) {
            fetch(`api/index.php?action=get_extension&ext=${ext.ext}`, { credentials: 'include' })
                .then(r=>r.json())
                .then(d=>{ if(d.success) setForm(f=>({...f, secret: d.secret||''})); });
        }
    }, [ext, isNew]);

    const set = (k,v) => setForm(f=>({...f,[k]:v}));

    const save = async () => {
        setSaving(true);
        const fd = new FormData();
        Object.entries(form).forEach(([k,v])=>fd.append(k,v));
        fd.append('device_type', devType);
        const action = isNew ? 'create_extension' : 'update_extension';
        const r = await fetch(`api/index.php?action=${action}`, { 
            method: 'POST', 
            body: fd,
            credentials: 'include'
        });
        const d = await r.json();
        if (d.success) {
            if (!isNew) {
                const fd2=new FormData(); fd2.append('ext',form.ext); fd2.append('mode',recording);
                await fetch('api/index.php?action=set_recording',{method:'POST',body:fd2});
            }
            toast(d.message,'success'); onSaved();
        } else toast(d.error||'Error al guardar','error');
        setSaving(false);
    };

    const del = async () => {
        if(!confirm(`¿Eliminar extensión #${form.ext}?`)) return;
        setDeleting(true);
        const fd=new FormData(); fd.append('ext',form.ext);
        const d=await(await fetch('api/index.php?action=delete_extension',{method:'POST',body:fd})).json();
        if(d.success){toast(d.message,'success');onSaved();}
        else { toast(d.error||'Error','error'); setDeleting(false); }
    };

    const recOptions=[{v:'always',l:'Siempre',c:'#4ade80',i:'fiber_manual_record'},{v:'dontcare',l:'Opcional',c:'#9ca3af',i:'radio_button_unchecked'},{v:'never',l:'Nunca',c:'#f87171',i:'not_interested'}];
    const devOptions=[{v:'audio',l:'SIP Fijo',i:'call'},{v:'video',l:'Video',i:'videocam'},{v:'webrtc',l:'WebRTC',i:'laptop'}];

    const ini = form.name ? form.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() : (form.ext || '?');
    const statusColor = ext?.status === 'ONLINE' ? '#22c55e' : ext?.status === 'BUSY' ? '#f59e0b' : '#6b7280';

    return (
        <div className="content-area view-enter">
            {/* Breadcrumb header */}
            <div style={{display:'flex', alignItems:'center', gap:12, marginBottom:24}}>
                <button
                    onClick={onBack}
                    style={{
                        width:38, height:38, borderRadius:12,
                        background:'var(--surface)', border:'1px solid var(--border)',
                        display:'flex', alignItems:'center', justifyContent:'center',
                        cursor:'pointer', color:'var(--muted)', transition:'all .2s'
                    }}
                    onMouseEnter={e=>e.currentTarget.style.color='var(--text)'}
                    onMouseLeave={e=>e.currentTarget.style.color='var(--muted)'}
                >
                    <span className="material-icons-round" style={{fontSize:20}}>arrow_back</span>
                </button>
                <div style={{display:'flex', alignItems:'center', gap:8, fontSize:12, color:'var(--muted)'}}>
                    <span style={{cursor:'pointer', fontWeight:600}} onClick={onBack}>Extensiones</span>
                    <span className="material-icons-round" style={{fontSize:14}}>chevron_right</span>
                    <span style={{color:'var(--text)', fontWeight:700}}>
                        {isNew ? 'Nueva Extensión' : `Interno #${ext.ext}`}
                    </span>
                </div>
                <div style={{flex:1}} />
                {!isNew && (
                    <button
                        onClick={del}
                        disabled={deleting}
                        style={{
                            padding:'8px 16px', borderRadius:10, fontSize:12, fontWeight:700,
                            background:'rgba(239,68,68,0.1)', border:'1px solid rgba(239,68,68,0.25)',
                            color:'#f87171', cursor:'pointer', display:'flex', alignItems:'center', gap:6,
                            transition:'all .2s'
                        }}
                    >
                        <span className="material-icons-round" style={{fontSize:16}}>{deleting?'hourglass_top':'delete_outline'}</span>
                        {deleting ? 'Eliminando...' : 'Eliminar Interno'}
                    </button>
                )}
            </div>

            {/* Main two-column layout */}
            <div style={{display:'grid', gridTemplateColumns:'280px 1fr', gap:24, alignItems:'start'}}>

                {/* LEFT — Avatar / Info card */}
                <div style={{display:'flex', flexDirection:'column', gap:16}}>
                    {/* Avatar big */}
                    <div className="glass" style={{padding:28, textAlign:'center', borderRadius:20}}>
                        <div style={{
                            width:80, height:80, borderRadius:22, margin:'0 auto 16px',
                            background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',
                            display:'flex', alignItems:'center', justifyContent:'center',
                            fontSize:28, fontWeight:900, color:'white',
                            boxShadow:'0 8px 32px rgba(139,92,246,0.45)'
                        }}>{ini}</div>
                        <div style={{fontSize:18, fontWeight:900, color:'var(--text)'}}>{form.name || 'Sin nombre'}</div>
                        <div style={{fontFamily:'monospace', fontSize:13, color:'#c4b5fd', fontWeight:700, marginTop:4}}>#{form.ext || '—'}</div>
                        {ext?.status && (
                            <div style={{display:'flex', alignItems:'center', gap:6, justifyContent:'center', marginTop:12}}>
                                <span style={{width:8,height:8,borderRadius:'50%',background:statusColor,boxShadow:`0 0 8px ${statusColor}`}} />
                                <span style={{fontSize:11, fontWeight:700, color:statusColor}}>{ext.status}</span>
                            </div>
                        )}
                    </div>

                    {/* Network info */}
                    {ext && (
                        <div className="glass" style={{padding:16, borderRadius:16}}>
                            <div style={{fontSize:10, fontWeight:700, color:'#6b7280', textTransform:'uppercase', letterSpacing:'.1em', marginBottom:12}}>Información de Red</div>
                            {[
                                {l:'IP', v: ext.ip, c:'#ec4899'},
                                {l:'RTT', v: ext.rtt, c:'#c4b5fd'},
                                {l:'MAC', v: ext.mac, c:'#6b7280'},
                            ].map(({l,v}) => v && v!=='—' ? (
                                <div key={l} style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 0', borderBottom:'1px solid var(--border)'}}>
                                    <span style={{fontSize:11, color:'#6b7280', fontWeight:600}}>{l}</span>
                                    <code style={{fontSize:11, color:'#ec4899', fontFamily:'monospace'}}>{v}</code>
                                </div>
                            ) : null)}
                        </div>
                    )}

                    {/* Asterisk tip */}
                    {!isNew && (
                        <div style={{
                            padding:14, borderRadius:14,
                            background:'rgba(139,92,246,0.06)',
                            border:'1px solid rgba(139,92,246,0.2)'
                        }}>
                            <div style={{display:'flex', gap:10, alignItems:'flex-start'}}>
                                <span className="material-icons-round" style={{fontSize:15, color:'#8b5cf6', marginTop:1}}>info</span>
                                <span style={{fontSize:11, color:'#a78bfa', lineHeight:1.5}}>Los cambios aplicarán un <b>core reload</b> automático en Asterisk para sincronizar SIP y dialplan.</span>
                            </div>
                        </div>
                    )}
                </div>

                {/* RIGHT — Form */}
                <div className="glass" style={{padding:28, borderRadius:20}}>
                    <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:20, marginBottom:20}}>
                        {/* Ext number */}
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Número de Interno</label>
                            <input
                                className="input-tf"
                                style={{padding:'12px 16px', borderRadius:14, fontSize:14, fontWeight:700, width:'100%', boxSizing:'border-box', opacity: isNew ? 1 : 0.7}}
                                placeholder="Ej: 1005"
                                value={form.ext}
                                onChange={e=>set('ext',e.target.value)}
                                readOnly={!isNew}
                            />
                        </div>
                        {/* Name */}
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Nombre o Alias</label>
                            <input
                                className="input-tf"
                                style={{padding:'12px 16px', borderRadius:14, fontSize:14, width:'100%', boxSizing:'border-box'}}
                                placeholder="Ej: Juan Pérez"
                                value={form.name}
                                onChange={e=>set('name',e.target.value)}
                            />
                        </div>
                        {/* Email */}
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Correo Electrónico</label>
                            <input
                                className="input-tf"
                                style={{padding:'12px 16px', borderRadius:14, fontSize:14, width:'100%', boxSizing:'border-box'}}
                                placeholder="usuario@empresa.com"
                                type="email"
                                value={form.email}
                                onChange={e=>set('email',e.target.value)}
                            />
                        </div>
                        {/* Password */}
                        <div>
                            <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:8}}>Contraseña SIP (Secret)</label>
                            <div style={{position:'relative'}}>
                                <input
                                    className="input-tf"
                                    style={{padding:'12px 48px 12px 16px', borderRadius:14, fontSize:14, width:'100%', boxSizing:'border-box'}}
                                    type={showPass?'text':'password'}
                                    placeholder="Mínimo 6 caracteres"
                                    value={form.secret}
                                    onChange={e=>set('secret',e.target.value)}
                                />
                                <button
                                    type="button"
                                    onClick={()=>setShowPass(!showPass)}
                                    style={{position:'absolute',right:12,top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',color:'#6b7280',display:'flex',padding:4}}
                                >
                                    <span className="material-icons-round" style={{fontSize:18}}>{showPass?'visibility_off':'visibility'}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Recording */}
                    <div style={{marginBottom:20}}>
                        <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:12}}>Grabación de Llamadas</label>
                        <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:10}}>
                            {recOptions.map(o=>(
                                <button
                                    key={o.v}
                                    onClick={()=>setRecording(o.v)}
                                    style={{
                                        padding:'14px 10px', borderRadius:14, cursor:'pointer',
                                        border: recording===o.v ? `1px solid ${o.c}40` : '1px solid var(--border)',
                                        background: recording===o.v ? `${o.c}12` : 'var(--surface2)',
                                        color: recording===o.v ? o.c : 'var(--muted)',
                                        display:'flex', flexDirection:'column', alignItems:'center', gap:6,
                                        transition:'all .2s', fontWeight:700, fontSize:11
                                    }}
                                >
                                    <span className="material-icons-round" style={{fontSize:22, color:recording===o.v?o.c:'var(--muted)'}}>{o.i}</span>
                                    {o.l}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Device type */}
                    <div style={{marginBottom:28}}>
                        <label style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em',display:'block',marginBottom:12}}>Tecnología de Dispositivo</label>
                        <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:10}}>
                            {devOptions.map(o=>(
                                <button
                                    key={o.v}
                                    onClick={()=>setDevType(o.v)}
                                    style={{
                                        padding:'14px 10px', borderRadius:14, cursor:'pointer',
                                        border: devType===o.v ? '1px solid rgba(139,92,246,0.4)' : '1px solid var(--border)',
                                        background: devType===o.v ? 'rgba(139,92,246,0.12)' : 'var(--surface2)',
                                        color: devType===o.v ? '#c4b5fd' : 'var(--muted)',
                                        display:'flex', flexDirection:'column', alignItems:'center', gap:6,
                                        transition:'all .2s', fontWeight:700, fontSize:11
                                    }}
                                >
                                    <span className="material-icons-round" style={{fontSize:22, color:devType===o.v?'#8b5cf6':'var(--muted)'}}>{o.i}</span>
                                    {o.l}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Action buttons */}
                    <div style={{display:'flex', gap:12, justifyContent:'flex-end', paddingTop:20, borderTop:'1px solid var(--border)'}}>
                        <button
                            onClick={onBack}
                            style={{
                                padding:'12px 24px', borderRadius:14, fontWeight:700, fontSize:13,
                                background:'var(--surface2)', border:'1px solid var(--border)',
                                color:'var(--muted)', cursor:'pointer', transition:'all .2s'
                            }}
                        >Cancelar</button>
                        <button
                            onClick={save}
                            disabled={saving}
                            className="btn-primary"
                            style={{padding:'12px 32px', borderRadius:14, fontWeight:700, fontSize:13, cursor:'pointer', display:'flex', alignItems:'center', gap:8}}
                        >
                            <span className="material-icons-round" style={{fontSize:18}}>{saving?'hourglass_top':'save'}</span>
                            {saving ? 'Guardando...' : isNew ? 'Crear Interno' : 'Guardar Cambios'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}


function ViewExtensiones({ data, toast }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [viewMode, setViewMode] = useState('grid');
    const [editing, setEditing] = useState(null); // null | 'new' | ext object
    const [saved, setSaved] = useState(0);

    const allExts = data?.pbx?.extensions || [];
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
            {/* Toolbar & Stats */}
            <div style={{display:'flex', gap:10, alignItems:'center', justifyContent:'flex-end', marginBottom:20, flexWrap:'wrap'}}>
                <div style={{position:'relative', width:280}}>
                    <span className="material-icons-round" style={{position:'absolute',left:11,top:'50%',transform:'translateY(-50%)',fontSize:17,color:'#6b7280'}}>search</span>
                    <input className="input-tf py-2.5 pl-10 pr-4 rounded-xl text-sm" placeholder="Buscar extensión..." value={search} onChange={e=>setSearch(e.target.value)} />
                </div>
                
                {/* Status Chips Filter */}
                <div style={{display:'flex', gap:6}}>
                    <Chip label="Online" count={onlineTotal} status="ONLINE" color="#4ade80" bg="rgba(34,197,94,0.1)" />
                    <Chip label="En Llamada" count={busyTotal} status="BUSY" color="#fbbf24" bg="rgba(245,158,11,0.1)" />
                    <Chip label="Offline" count={offlineTotal} status="OFFLINE" color="#9ca3af" bg="rgba(107,114,128,0.1)" />
                </div>

                <div style={{display:'flex',gap:4,background:'var(--surface2)',borderRadius:10,padding:4,border:'1px solid var(--border)'}}>  
                    {['grid','table'].map(m=>(
                        <button key={m} onClick={()=>setViewMode(m)} style={{padding:'6px 10px',borderRadius:8,border:'none',cursor:'pointer',background:viewMode===m?'rgba(139,92,246,.25)':'transparent',color:viewMode===m?'#c4b5fd':'#6b7280',transition:'all .2s'}}>
                            <span className="material-icons-round" style={{fontSize:18,display:'block'}}>{m==='grid'?'grid_view':'table_rows'}</span>
                        </button>
                    ))}
                </div>
                <button className="btn-primary" style={{padding:'10px 16px',borderRadius:10,fontSize:13,display:'flex',alignItems:'center',gap:6}} onClick={()=>setEditing('new')}>
                    <span className="material-icons-round" style={{fontSize:18}}>add</span>Nueva
                </button>
            </div>

            {/* GRID */}
            {viewMode==='grid' && (
                <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(255px,1fr))',gap:12}}>
                    {exts.map(e=>(
                        <div key={e.ext} className="glass glass-hover" style={{padding:'16px',cursor:'pointer'}} onClick={()=>setEditing(e)}>
                            <div style={{display:'flex',alignItems:'center',gap:12}}>
                                <img src={e.avatar} style={{width:44,height:44,borderRadius:12,objectFit:'cover',border:'2px solid var(--border)'}} onError={ev=>{ ev.target.style.display='none'; ev.target.nextSibling.style.display='flex'; }} />
                                <div className={`agent-avatar bg-gradient-to-br ${getColor(e.name)}`} style={{display:'none'}}>{initials(e.name)}</div>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:14,fontWeight:800,color:'var(--text)',display:'flex',alignItems:'center',gap:6}}>
                                        #{e.ext}
                                        {e.recording === 'always' && <span className="material-icons-round" style={{fontSize:14,color:'#ef4444'}}>fiber_manual_record</span>}
                                    </div>
                                    <div style={{fontSize:11,color:'#9ca3af',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{e.name}</div>
                                </div>
                                <span className={`badge ${badgeCls(e.status)}`}><span className={`badge-dot ${dotCls(e.status)}`} />{e.status}</span>
                            </div>
                            <div style={{marginTop:10,display:'flex',gap:14,fontSize:10,color:'#6b7280'}}>
                                <span>IP: <span style={{color:'#c4b5fd',fontFamily:'monospace'}}>{e.ip}</span></span>
                                <span>RTT: <span style={{color:'#c4b5fd'}}>{e.rtt}</span></span>
                            </div>
                        </div>
                    ))}
                    {exts.length===0&&<div style={{color:'#6b7280',gridColumn:'1/-1',textAlign:'center',padding:40}}>Sin extensiones</div>}
                </div>
            )}

            {/* TABLE */}
            {viewMode==='table' && (
                <div className="glass" style={{overflow:'hidden'}}>
                    <table className="tf-table">
                        <thead><tr><th>#</th><th>Nombre</th><th>Estado</th><th>IP</th><th>RTT</th><th></th></tr></thead>
                        <tbody>
                            {exts.map(e=>(
                                <tr key={e.ext} style={{cursor:'pointer'}} onClick={()=>setEditing(e)}>
                                    <td>
                                        <div style={{display:'flex',alignItems:'center',gap:8}}>
                                            <span style={{fontFamily:'monospace',fontWeight:800,color:'#c4b5fd'}}>#{e.ext}</span>
                                            {e.recording === 'always' && <span className="material-icons-round" style={{fontSize:12,color:'#ef4444'}}>fiber_manual_record</span>}
                                        </div>
                                    </td>
                                    <td style={{fontWeight:600}}>{e.name}</td>
                                    <td><span className={`badge ${badgeCls(e.status)}`}><span className={`badge-dot ${dotCls(e.status)}`}/>{e.status}</span></td>
                                    <td><code style={{fontSize:11,color:'#ec4899'}}>{e.ip}</code></td>
                                    <td style={{color:'#c4b5fd'}}>{e.rtt}</td>
                                    <td><span className="material-icons-round" style={{fontSize:17,color:'#6b7280'}}>chevron_right</span></td>
                                </tr>
                            ))}
                            {exts.length===0&&<tr><td colSpan={6} style={{textAlign:'center',color:'#6b7280',padding:30}}>Sin extensiones</td></tr>}
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
function ViewAgentes({ toast }) {
    const [agents, setAgents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [selected, setSelected] = useState(null);
    const [activeCalls, setActiveCalls] = useState([]);
    const [isGrid, setIsGrid] = useState(true);
    const [supervisorExt, setSupervisorExt] = useState(() => localStorage.getItem('tf_supervisor_ext') || '');
    const [actionLoading, setActionLoading] = useState(null);
    
    // UI Enhancements state
    const [contextMenu, setContextMenu] = useState(null);
    const [peekHistory, setPeekHistory] = useState(null);
    const [historyData, setHistoryData] = useState({});
    const menuRef = useRef(null);

    useEffect(() => {
        localStorage.setItem('tf_supervisor_ext', supervisorExt);
    }, [supervisorExt]);

    const load = useCallback(async () => {
        try {
            const [ar, cr] = await Promise.all([
                fetch('api/index.php?action=get_full_data', { credentials: 'include' }).then(r=>r.json()).catch(()=>({success:false})),
                fetch('api/index.php?action=get_active_calls', { credentials: 'include' }).then(r=>r.json()).catch(()=>({success:false}))
            ]);
            // Full data returns extensions in pbx.extensions
            if (ar.pbx?.extensions) setAgents(ar.pbx.extensions);
            if (cr.success) setActiveCalls(cr.calls||[]);
        } catch {}
        setLoading(false);
    }, []);

    useEffect(() => { load(); const t = setInterval(load, 4000); return () => clearInterval(t); }, [load]);
    
    // Close context menu on click outside
    useEffect(() => {
        const h = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setContextMenu(null); };
        document.addEventListener('mousedown', h); return () => document.removeEventListener('mousedown', h);
    }, []);

    const handleAction = async (agent, type) => {
        setContextMenu(null);
        const callInfo = activeCalls.find(c => c.ext === String(agent.ext));
        if (!callInfo && (type === 'spy' || type === 'whisper' || type === 'hangup')) {
            toast('El agente no está en una llamada activa', 'warning');
            return;
        }
        if ((type === 'spy' || type === 'whisper') && !supervisorExt) {
            toast('Configura tu extensión de supervisor primero', 'error');
            return;
        }
        
        setActionLoading(agent.ext);
        try {
            const fd = new FormData();
            fd.append('type', type);
            fd.append('channel', callInfo?.channel || '');
            fd.append('supervisor', supervisorExt);
            
            const r = await fetch('api/index.php?action=call_action', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) toast(d.message, 'success');
            else toast(d.error || 'Error', 'error');
        } catch (e) {
            toast('Error de conexión', 'error');
        }
        setActionLoading(null);
    };

    const fetchHistory = async (ext) => {
        if (historyData[ext]) { setPeekHistory(ext); return; }
        try {
            const r = await fetch(`api/index.php?action=get_agent_history&ext=${ext}`, { credentials: 'include' });
            const d = await r.json();
            if (d.success) {
                setHistoryData(prev => ({...prev, [ext]: d.history}));
                setPeekHistory(ext);
            }
        } catch {}
    };

    const onDropTransfer = async (e, targetExt) => {
        e.preventDefault();
        const callData = JSON.parse(e.dataTransfer.getData('call') || '{}');
        if (!callData.channel) return;
        
        toast(`Transfiriendo llamada ${callData.ext} -> ${targetExt}...`, 'info');
        try {
            const fd = new FormData();
            fd.append('channel', callData.channel);
            fd.append('ext', targetExt);
            const r = await fetch('api/index.php?action=redirect_call', { 
                method: 'POST', 
                body: fd,
                credentials: 'include'
            });
            const d = await r.json();
            if (d.success) toast(d.message, 'success');
            else toast(d.error, 'error');
        } catch { toast('Error al transferir', 'error'); }
    };

    const getCallInfo = (ext) => activeCalls.find(c => c.ext === String(ext));
    const getLatencyRef = (rtt) => {
        const ms = parseInt(rtt) || 999;
        if (ms < 50) return { color: '#22c55e', label: 'Excelente' };
        if (ms < 150) return { color: '#f59e0b', label: 'Medio' };
        return { color: '#ef4444', label: 'Pobre' };
    };

    const filtered = agents.filter(a =>
        (a.name.toLowerCase().includes(search.toLowerCase()) || String(a.ext).includes(search)) &&
        (!statusFilter || a.status === statusFilter)
    );
    const online = agents.filter(a=>a.status==='ONLINE').length;
    const busy   = agents.filter(a=>a.status==='BUSY').length;
    const calls  = agents.reduce((s,a)=>s+(a.total_calls||0),0);

    return (
        <div className="content-area view-enter">
            <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:16,marginBottom:24}}>
                {[
                    {l:'Agentes Online',v:`${online}/${agents.length}`,c:'#22c55e',ic:'sensors',bg:'rgba(34,197,94,0.1)'},
                    {l:'En Llamada',v:busy,c:'#ef4444',ic:'call',bg:'rgba(239,68,68,0.1)'},
                    {l:'Total Llamadas',v:calls,c:'#8b5cf6',ic:'bar_chart',bg:'rgba(139,92,246,0.1)'},
                    {l:'Offline',v:agents.length-online-busy,c:'#6b7280',ic:'do_not_disturb',bg:'rgba(255,255,255,0.05)'},
                ].map(s=>(
                    <div key={s.l} className="glass" style={{padding:20, borderRadius:20}}>
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                            <div style={{fontSize:10,fontWeight:800,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.1em'}}>{s.l}</div>
                            <div style={{width:36,height:36,borderRadius:10,background:s.bg,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:18,color:s.c}}>{s.ic}</span>
                            </div>
                        </div>
                        <div style={{fontSize:32,fontWeight:900,color:s.c,marginTop:8,lineHeight:1}}>{s.v}</div>
                    </div>
                ))}
            </div>

            <div style={{display:'flex',gap:12,marginBottom:24,alignItems:'center'}}>
                <div style={{position:'relative',flex:1}}>
                    <span className="material-icons-round" style={{position:'absolute',left:14,top:'50%',transform:'translateY(-50%)',fontSize:18,color:'#6b7280'}}>search</span>
                    <input className="input-tf py-3 pl-12 pr-4 rounded-2xl text-sm" placeholder="Buscar por nombre o extensión..." value={search} onChange={e=>setSearch(e.target.value)} />
                </div>
                
                <div className="glass" style={{display:'flex', padding:4, borderRadius:16, background:'rgba(255,255,255,0.02)'}}>
                    <button onClick={()=>setIsGrid(true)} className={`p-2 rounded-xl transition-all ${isGrid?'bg-white/10 text-white shadow-lg':'text-gray-500 hover:text-gray-300'}`}>
                        <span className="material-icons-round" style={{fontSize:20}}>grid_view</span>
                    </button>
                    <button onClick={()=>setIsGrid(false)} className={`p-2 rounded-xl transition-all ${!isGrid?'bg-white/10 text-white shadow-lg':'text-gray-500 hover:text-gray-300'}`}>
                        <span className="material-icons-round" style={{fontSize:20}}>format_list_bulleted</span>
                    </button>
                </div>

                <div className="glass" style={{display:'flex', alignItems:'center', gap:10, padding:'4px 12px', borderRadius:16}}>
                    <span className="material-icons-round" style={{fontSize:16, color:'#6b7280'}}>admin_panel_settings</span>
                    <input type="text" placeholder="Mi Ext" title="Tu extensión para Spy/Whisper" value={supervisorExt} onChange={e=>setSupervisorExt(e.target.value)} className="bg-transparent border-none text-xs font-bold text-white focus:outline-none w-16" />
                </div>

                <select className="input-tf py-3 px-6 rounded-2xl text-sm" style={{width:'auto'}} value={statusFilter} onChange={e=>setStatusFilter(e.target.value)}>
                    <option value="">Todos los Estados</option>
                    <option value="ONLINE">Disponibles</option>
                    <option value="BUSY">En Llamada</option>
                    <option value="OFFLINE">Desconectados</option>
                </select>
            </div>

            {loading ? (
                <div style={{textAlign:'center',padding:40,color:'#6b7280'}}>Cargando agentes...</div>
            ) : filtered.length === 0 ? (
                <div style={{textAlign:'center',padding:40,color:'#6b7280'}}>Sin agentes que coincidan</div>
            ) : isGrid ? (
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-8 anim-fadeup">
                    {filtered.map(agent => {
                        const callInfo = getCallInfo(agent.ext);
                        const isBusy = agent.status === 'BUSY' || !!callInfo;
                        const isOffline = agent.status === 'OFFLINE';
                        const statusColor = isBusy ? '#ef4444' : (isOffline ? '#4b5563' : '#22c55e');
                        const rttInfo = getLatencyRef(agent.rtt_ms || agent.rtt);
                        
                        return (
                            <div key={agent.ext} className="group relative flex flex-col items-center" 
                                 onContextMenu={(e)=>{ e.preventDefault(); setContextMenu({x:e.clientX, y:e.clientY, agent}); }}
                                 onDragOver={(e)=>e.preventDefault()}
                                 onDrop={(e)=>onDropTransfer(e, agent.ext)}
                                 onMouseEnter={()=>fetchHistory(agent.ext)}
                                 onMouseLeave={()=>setPeekHistory(null)}
                                 style={{cursor:'pointer'}}>
                                
                                <div className="relative mb-4">
                                    <div className={`w-20 h-20 rounded-[28px] bg-gradient-to-br ${getColor(agent.name)} flex items-center justify-center text-xl font-black text-white shadow-xl transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-2`}
                                         style={{
                                             boxShadow: isBusy ? `0 10px 40px rgba(239,68,68,0.4)` : (isOffline ? 'none' : `0 10px 40px rgba(34,197,94,0.2)`),
                                             border: `2px solid ${statusColor}44`,
                                             filter: isOffline ? 'grayscale(0.8) opacity-0.6' : 'none',
                                             background: `url(${agent.avatar}) center/cover`
                                         }}>
                                        {!agent.avatar.includes('uploads') && initials(agent.name)}
                                    </div>

                                    {/* Status Badge */}
                                    <div className="absolute -bottom-1 -right-1 w-8 h-8 rounded-xl border-4 border-[#0b0b14] flex items-center justify-center transform group-hover:scale-110 transition-transform" style={{background: statusColor}}>
                                        <span className="material-icons-round text-[14px] text-white" style={{animation: isBusy?'pulse-ring 1.5s infinite':'none'}}>
                                            {isBusy ? 'call' : (isOffline ? 'cloud_off' : 'check')}
                                        </span>
                                    </div>

                                    {/* Latency Dot */}
                                    {!isOffline && (
                                        <div className="absolute -top-1 -right-1 w-4 h-4 rounded-full border-2 border-[#0b0b14] flex items-center justify-center shadow-lg" 
                                             title={`Latencia: ${agent.rtt} (${rttInfo.label})`}
                                             style={{background: rttInfo.color}}>
                                            <div className="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                                        </div>
                                    )}

                                    {/* Device Badge */}
                                    <div className="absolute -bottom-1 -left-1 w-6 h-6 rounded-lg bg-surface2 border border-white/5 flex items-center justify-center text-gray-400">
                                        <span className="material-icons-round text-[12px]">{agent.device_type === 'softphone' ? 'laptop_mac' : 'phone_android'}</span>
                                    </div>

                                    {/* Quick Hover Actions Overlay */}
                                    <div className="absolute inset-0 rounded-[28px] bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center backdrop-blur-[4px] group-hover:scale-110 group-hover:-translate-y-2">
                                        <div className="flex gap-2">
                                            <button onClick={(e)=>{ e.stopPropagation(); handleAction(agent,'spy'); }} title="Espiar" className="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
                                                <span className="material-icons-round" style={{fontSize:20}}>headphones</span>
                                            </button>
                                            <button onClick={(e)=>{ e.stopPropagation(); handleAction(agent,'whisper'); }} title="Susurrar" className="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
                                                <span className="material-icons-round" style={{fontSize:20}}>record_voice_over</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div className="text-center group-hover:translate-y-[-4px] transition-transform">
                                    <div style={{fontSize:13, fontWeight:900, color:'white'}}>#{agent.ext}</div>
                                    <div style={{fontSize:10, fontWeight:700, color:'#6b7280', textTransform:'uppercase', maxWidth:100, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>{agent.name}</div>
                                    {isBusy && (
                                        <div className="mt-2 px-2 py-0.5 rounded-full bg-red-500/10 border border-red-500/20 flex items-center gap-1.5 justify-center">
                                            <span className="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" />
                                            <AgentCallTimer seconds={callInfo?.elapsed_sec || agent.in_call || 0} />
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <div className="anim-fadeup">
                    {filtered.map(agent => {
                        const callInfo = getCallInfo(agent.ext);
                        const isBusy = agent.status==='BUSY' || !!callInfo;
                        return (
                            <div key={agent.ext} className="agent-row" style={{gridTemplateColumns:'2.5fr 1.2fr 2fr 1.3fr 1.2fr'}} onClick={()=>setSelected(agent)}>
                                <div style={{display:'flex',alignItems:'center',gap:12}}>
                                    <div className={`agent-avatar bg-gradient-to-br ${getColor(agent.name)}`} style={{position:'relative'}}>
                                        {initials(agent.name)}
                                        {isBusy&&<div style={{position:'absolute',bottom:-2,right:-2,width:8,height:8,borderRadius:'50%',background:'#f59e0b',border:'1px solid var(--surface)',animation:'blink 1s infinite'}} />}
                                    </div>
                                    <div>
                                        <div style={{fontSize:13,fontWeight:700,color:'var(--text)'}}>#{agent.ext}</div>
                                        <div style={{fontSize:11,color:'#9ca3af'}}>{agent.name}</div>
                                    </div>
                                    {isBusy && (
                                        <button onClick={(e)=>{ e.stopPropagation(); handleAction(agent,'spy'); }} className="ml-auto w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition-all flex items-center justify-center">
                                            <span className="material-icons-round" style={{fontSize:18}}>headphones</span>
                                        </button>
                                    )}
                                </div>
                                <div style={{textAlign:'center'}}>
                                    <span className={`badge ${agent.status==='ONLINE'?'badge-online':agent.status==='BUSY'?'badge-busy':'badge-offline'}`}>
                                        <span className={`badge-dot ${agent.status==='ONLINE'?'dot-online':agent.status==='BUSY'?'dot-busy':'dot-offline'}`} />
                                        {agent.status==='ONLINE'?'Online':agent.status==='BUSY'?'Llamada':'Offline'}
                                    </span>
                                </div>
                                <div style={{textAlign:'center'}}>
                                    {callInfo ? (
                                        <div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:2}}>
                                            <AgentCallTimer seconds={callInfo.elapsed_sec||0} />
                                            <div style={{fontSize:10,color:'#ec4899',fontFamily:'monospace'}}>{callInfo.dest||'—'}</div>
                                        </div>
                                    ) : (agent.in_call||0)>0 ? (
                                        <AgentCallTimer seconds={agent.in_call||0} />
                                    ) : <span style={{fontSize:12,color:'#6b7280'}}>—</span>}
                                </div>
                                <div style={{textAlign:'center',display:'flex',gap:14,justifyContent:'center'}}>
                                    <div style={{textAlign:'center'}}>
                                        <div style={{fontSize:14,fontWeight:800,color:'#c4b5fd'}}>{agent.total_calls||0}</div>
                                        <div style={{fontSize:9,color:'#6b7280'}}>LLAMADAS</div>
                                    </div>
                                    <div style={{textAlign:'center'}}>
                                        <div style={{fontSize:14,fontWeight:800,color:'#c4b5fd'}}>{agent.avg_aht||'0:00'}</div>
                                        <div style={{fontSize:9,color:'#6b7280'}}>AHT</div>
                                    </div>
                                </div>
                                <div style={{textAlign:'right'}}>
                                    <div style={{fontSize:10,fontFamily:'monospace',color:'#ec4899'}}>{agent.ip}</div>
                                    <div style={{fontSize:10,fontFamily:'monospace',color:'#8b5cf6'}}>{agent.rtt}</div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {selected && (
                <div className="modal-backdrop" onClick={()=>setSelected(null)}>
                    <div className="modal-box" onClick={e=>e.stopPropagation()}>
                        <div style={{display:'flex',justifyContent:'space-between',marginBottom:20}}>
                            <div style={{display:'flex',gap:12,alignItems:'center'}}>
                                <div className={`agent-avatar bg-gradient-to-br ${getColor(selected.name)}`} style={{width:48,height:48,borderRadius:12,fontSize:15}}>{initials(selected.name)}</div>
                                <div>
                                    <div style={{fontSize:18,fontWeight:800,color:'var(--text)'}}>#{selected.ext} — {selected.name}</div>
                                    <span className={`badge ${selected.status==='ONLINE'?'badge-online':selected.status==='BUSY'?'badge-busy':'badge-offline'}`}>
                                        <span className={`badge-dot ${selected.status==='ONLINE'?'dot-online':selected.status==='BUSY'?'dot-busy':'dot-offline'}`} />
                                        {selected.status}
                                    </span>
                                </div>
                            </div>
                            <button onClick={()=>setSelected(null)} style={{background:'none',border:'none',cursor:'pointer',color:'#6b7280'}}>
                                <span className="material-icons-round" style={{fontSize:22}}>close</span>
                            </button>
                        </div>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10}}>
                            {[
                                {l:'IP Origen',v:selected.ip},{l:'MAC',v:selected.mac},
                                {l:'Latencia RTT',v:selected.rtt},{l:'En Llamada',v:selected.in_call>0?fmtTime(selected.in_call):'No'},
                                {l:'Llamadas Hoy',v:selected.total_calls},{l:'AHT Promedio',v:selected.avg_aht},
                            ].map(({l,v})=>(
                                <div key={l} style={{background:'var(--surface2)',borderRadius:10,padding:'12px',border:'1px solid var(--border)'}}>
                                    <div style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.08em',marginBottom:4}}>{l}</div>
                                    <div style={{fontSize:13,fontWeight:600,color:'#c4b5fd',fontFamily:'monospace'}}>{v||'—'}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
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
                                    <div style={{width:36,height:36,borderRadius:10,background:'rgba(139,92,246,0.2)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                        <span className="material-icons-round" style={{fontSize:18,color:'#c4b5fd'}}>call</span>
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
                                    <div style={{width:34,height:34,borderRadius:9,background:'rgba(139,92,246,0.15)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                        <span className="material-icons-round" style={{fontSize:16,color:'#c4b5fd'}}>mic</span>
                                    </div>
                                    <div>
                                        <div style={{fontSize:13,fontWeight:700,color:'white'}}>#{r.src} → {r.dst}</div>
                                        <div style={{fontSize:11,color:'#6b7280'}}>{r.calldate?.substring(0,16)}</div>
                                    </div>
                                </div>
                                <div style={{display:'flex',gap:12,alignItems:'center'}}>
                                    <span style={{fontSize:11,padding:'4px 10px',borderRadius:8,background:'rgba(139,92,246,0.12)',color:'#c4b5fd',fontWeight:600}}>{r.duration}s</span>
                                    <span style={{fontSize:11,padding:'4px 10px',borderRadius:8,background:r.disposition==='ANSWERED'?'rgba(34,197,94,0.12)':'rgba(239,68,68,0.12)',color:r.disposition==='ANSWERED'?'#4ade80':'#f87171',fontWeight:600}}>{r.disposition}</span>
                                </div>
                            </div>
                            {r.recordingfile && <audio controls src={`/monitor/${r.recordingfile}`} style={{width:'100%'}} />}
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
            {/* Stats */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(6,1fr)',gap:10,marginBottom:20}}>
                {statCards.map(s=>(
                    <div key={s.l} className="glass" style={{padding:'14px 16px',borderTop:`2px solid ${s.c}`}}>
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:6}}>
                            <div style={{fontSize:9,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.08em'}}>{s.l}</div>
                            <div style={{width:26,height:26,borderRadius:7,background:s.bg,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:14,color:s.c}}>{s.ic}</span>
                            </div>
                        </div>
                        <div style={{fontSize:22,fontWeight:800,color:s.c,lineHeight:1}}>{s.v}</div>
                    </div>
                ))}
            </div>

            {/* Filtros */}
            <div className="glass" style={{padding:16,marginBottom:16}}>
                <div style={{display:'flex',gap:8,flexWrap:'wrap',alignItems:'center'}}>
                    <div style={{display:'flex',alignItems:'center',gap:6,flex:'none'}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'#6b7280'}}>calendar_today</span>
                        <span style={{fontSize:11,color:'#6b7280',fontWeight:600}}>Desde</span>
                        <input className="input-tf py-1.5 px-3 rounded-lg text-xs" type="date" value={from} onChange={e=>setFrom(e.target.value)} style={{width:135}} />
                    </div>
                    <div style={{display:'flex',alignItems:'center',gap:6,flex:'none'}}>
                        <span style={{fontSize:11,color:'#6b7280',fontWeight:600}}>Hasta</span>
                        <input className="input-tf py-1.5 px-3 rounded-lg text-xs" type="date" value={to} onChange={e=>setTo(e.target.value)} style={{width:135}} />
                    </div>
                    <div style={{position:'relative',flex:1,minWidth:120}}>
                        <span className="material-icons-round" style={{position:'absolute',left:9,top:'50%',transform:'translateY(-50%)',fontSize:15,color:'#6b7280'}}>search</span>
                        <input className="input-tf py-1.5 pl-8 pr-3 rounded-lg text-xs" placeholder="Número origen/destino..." value={src} onChange={e=>setSrc(e.target.value)} />
                    </div>
                    <select className="input-tf py-1.5 px-3 rounded-lg text-xs" value={disp} onChange={e=>setDisp(e.target.value)} style={{width:150}}>
                        <option value="">Todos los estados</option>
                        <option value="ANSWERED">Contestadas</option>
                        <option value="NO ANSWER">Sin Respuesta</option>
                        <option value="BUSY">Comunicando</option>
                        <option value="FAILED">Fallidas</option>
                    </select>
                    <button className="btn-primary" style={{padding:'7px 16px',borderRadius:10,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={load}>
                        <span className="material-icons-round" style={{fontSize:16}}>{loading?'hourglass_top':'search'}</span>
                        {loading?'Buscando...':'Buscar'}
                    </button>
                    <button onClick={exportCSV} style={{padding:'7px 14px',borderRadius:10,background:'rgba(34,197,94,0.12)',border:'1px solid rgba(34,197,94,0.3)',color:'#4ade80',fontSize:12,fontWeight:700,cursor:'pointer',display:'flex',alignItems:'center',gap:5}}>
                        <span className="material-icons-round" style={{fontSize:16}}>download</span>
                        Exportar CSV
                    </button>
                    <span style={{fontSize:11,color:'#6b7280',marginLeft:'auto',fontWeight:600}}>{total.toLocaleString()} registros</span>
                </div>
            </div>

            {/* Tabla */}
            <div className="glass" style={{overflow:'hidden'}}>
                <table className="tf-table">
                    <thead>
                        <tr style={{background:'rgba(139,92,246,0.05)'}}>
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
                                                <div style={{width:28,height:28,borderRadius:8,background:'rgba(139,92,246,0.15)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                                    <span className="material-icons-round" style={{fontSize:14,color:'#c4b5fd'}}>call_made</span>
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
                                                    <span className="material-icons-round" style={{fontSize:16,color:'#8b5cf6'}}>mic</span>
                                                    <span style={{fontSize:10,color:'#c4b5fd',fontWeight:600}}>Ver ↓</span>
                                                  </div>
                                                :<span style={{color:'#374151',fontSize:12}}>—</span>
                                            }
                                        </td>
                                    </tr>
                                    {isExp&&r.recordingfile&&(
                                        <tr><td colSpan={6} style={{padding:'8px 16px 12px',background:'rgba(139,92,246,0.04)',borderTop:'none'}}>
                                            <div style={{display:'flex',alignItems:'center',gap:10}}>
                                                <span className="material-icons-round" style={{fontSize:18,color:'#8b5cf6'}}>mic</span>
                                                <audio controls src={`/monitor/${r.recordingfile}`} style={{flex:1,height:32}} />
                                                <span style={{fontSize:10,color:'#6b7280',fontFamily:'monospace'}}>{r.recordingfile}</span>
                                            </div>
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
// VISTA: COLAS (con CRUD + numero + animación activa)
// ─────────────────────────────────────────────
const STRAT_OPTS = [
    {v:'ringall',l:'Timbre simultáneo'},
    {v:'rrmemory',l:'Round Robin memoria'},
    {v:'leastrecent',l:'Menos reciente'},
    {v:'fewestcalls',l:'Menos llamadas'},
    {v:'random',l:'Aleatorio'},
    {v:'linear',l:'Lineal'},
];

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

function ViewColas({ toast, onReport, data }) {
    const [queues,setQueues]=useState([]);
    const [drawer,setDrawer]=useState(null);
    const extensions = data?.pbx?.extensions || [];
    
    const load=async()=>{
        try{const d=await(await fetch('api/index.php?action=get_queues')).json();if(d.success)setQueues(d.queues);}catch{}
    };
    useEffect(()=>{load();const t=setInterval(load,5000);return()=>clearInterval(t);},[]);
    
    const stratLabel={ringall:'Simultáneo',rrmemory:'Round Robin',leastrecent:'Menos reciente',fewestcalls:'Menos llamadas',random:'Aleatorio',linear:'Lineal'};
    
    return(
        <div className="content-area view-enter">
            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:20}}>
                <div style={{fontSize:11,color:'#6b7280'}}>{queues.length} colas configuradas</div>
                <button className="btn-primary" style={{padding:'9px 16px',borderRadius:10,fontSize:13,display:'flex',alignItems:'center',gap:6}} onClick={()=>setDrawer('new')}>
                    <span className="material-icons-round" style={{fontSize:18}}>add</span>Nueva Cola
                </button>
            </div>
            {queues.length===0&&<div className="glass" style={{padding:40,textAlign:'center',color:'#6b7280'}}>
                <span className="material-icons-round" style={{fontSize:48,display:'block',marginBottom:12,color:'#374151'}}>queue</span>
                No hay colas configuradas
            </div>}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 anim-fadeup">
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
                            <div style={{display:'flex', justifyContent:'space-between', alignItems:'flex-start', marginBottom:20}}>
                                <div style={{display:'flex', alignItems:'center', gap:12}}>
                                    <div style={{
                                        width:48, height:48, borderRadius:16, 
                                        background: isActive ? `${statusColor}22` : 'var(--surface2)',
                                        display:'flex', alignItems:'center', justifyContent:'center',
                                        boxShadow: isActive ? `0 0 20px ${statusColor}33` : 'none'
                                    }}>
                                        <span className="material-icons-round" style={{fontSize:24, color:statusColor, animation: isActive ? 'blink 1.5s infinite' : 'none'}}>hub</span>
                                    </div>
                                    <div>
                                        <div style={{display:'flex', alignItems:'center', gap:8}}>
                                            <span style={{fontSize:10, fontWeight:900, color:'#6b7280', fontFamily:'monospace', background:'var(--surface2)', padding:'2px 6px', borderRadius:5}}>#{q.id}</span>
                                            <h3 style={{fontSize:16, fontWeight:900, color:'var(--text)'}}>{q.name}</h3>
                                        </div>
                                        <div style={{fontSize:11, color:'#6b7280', marginTop:2}}>{stratLabel[q.strategy]||q.strategy}</div>
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    {waiting > 1 && (
                                        <div className="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full flex items-center gap-1 shadow-lg shadow-red-500/20">
                                            <span className="material-icons-round" style={{fontSize:12}}>call</span>
                                            {waiting}
                                        </div>
                                    )}
                                    <button title="Ajustes" onClick={()=>setDrawer(q)} className="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                                        <span className="material-icons-round" style={{fontSize:18}}>settings</span>
                                    </button>
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

                            <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:12}}>
                                <div style={{fontSize:10, color:'#4b5563', fontWeight:800, textTransform:'uppercase'}}>Agentes de la Cola</div>
                                <div style={{fontSize:10, color:'#4b5563', fontWeight:800}}>{q.members?.length || 0} Total</div>
                            </div>

                            <div style={{display:'flex', flexWrap:'wrap', gap:6, minHeight:34}}>
                                {q.members?.slice(0, 10).map((m,j)=>{
                                    const extInfo = extensions.find(e => e.ext === m.ext);
                                    const av = extInfo?.avatar || `https://ui-avatars.com/api/?name=${m.ext}&background=714B67&color=fff&size=40`;
                                    return (
                                        <div key={j} className="relative group/member">
                                            <div className="w-8 h-8 rounded-lg overflow-hidden border border-white/5 transition-transform hover:scale-110" 
                                                 style={{boxShadow: m.status==='ONLINE'?'0 0 10px rgba(34,197,94,0.1)':(m.status==='BUSY'?'0 0 10px rgba(245,158,11,0.1)':'none')}}>
                                                <img src={av} style={{width:'100%', height:'100%', objectFit:'cover', opacity: m.status==='OFFLINE'?0.5:1}} />
                                            </div>
                                            <div style={{position:'absolute', bottom:-2, right:-2, width:10, height:10, borderRadius:'50%', background:m.status==='ONLINE'?'#22c55e':(m.status==='BUSY'?'#f59e0b':'#374151'), border:'2px solid var(--surface)'}} />
                                            
                                            {/* Member tooltip */}
                                            <div className="absolute left-1/2 -top-1 px-2 py-1 bg-black/90 text-white text-[9px] font-bold rounded -translate-x-1/2 -translate-y-full opacity-0 group-hover/member:opacity-100 pointer-events-none transition-opacity z-10 whitespace-nowrap border border-white/10">
                                                #{m.ext} · {extInfo?.name || m.ext}
                                            </div>
                                        </div>
                                    );
                                })}
                                {q.members?.length > 10 && <div className="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-[10px] text-gray-500">+{q.members.length-10}</div>}
                            </div>

                            {/* Floating Hover Actions */}
                            <div className="absolute inset-0 bg-black/40 backdrop-blur-[6px] opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-center gap-2 rounded-2xl pointer-events-none group-hover:pointer-events-auto">
                                <div style={{display:'flex', gap:8}}>
                                    <button className="btn-primary" style={{padding:'8px 16px', borderRadius:10, fontSize:11, fontWeight:800}} onClick={()=>toast('Función habilitada pronto','info')}>
                                        LOGUEAR AGENTE
                                    </button>
                                    <button className="btn-secondary" style={{padding:'8px 16px', borderRadius:10, fontSize:11, fontWeight:800, background:'rgba(255,255,255,0.1)', color:'white', border:'1px solid rgba(255,255,255,0.1)'}} onClick={()=>onReport(q.id)}>
                                        REPORTE
                                    </button>
                                </div>
                                <button className="text-[10px] font-bold text-gray-400 hover:text-white transition-colors uppercase tracking-widest mt-1" onClick={()=>toast('Estrategia: '+q.strategy,'info')}>
                                    Estrategia: {q.strategy}
                                </button>
                            </div>
                        </div>
                    </div>);
                })}
            </div>
            {drawer&&<QueueDrawer queue={drawer==='new'?null:drawer} onClose={()=>setDrawer(null)} onSaved={()=>{setDrawer(null);load();}} toast={toast||((m,t)=>alert(m))} />}
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
                            Tiempo de Timbrado <span style={{color:'#c4b5fd', fontFamily:'monospace'}}>({form.grptime}s)</span>
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
                                            background:'rgba(139,92,246,0.1)',
                                            border:'1px solid rgba(139,92,246,0.25)',
                                            fontSize:12, fontWeight:700, color:'#c4b5fd'
                                        }}
                                    >
                                        <span className="material-icons-round" style={{fontSize:13,color:'#8b5cf6'}}>phone</span>
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
                            style={{marginTop:6, padding:'10px 16px', borderRadius:12, fontSize:12, fontFamily:'monospace', width:'100%', boxSizing:'border-box', color:'#c4b5fd'}}
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
            <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:20}}>
                <div style={{fontSize:11, color:'#6b7280'}}>{groups.length} grupos configurados</div>
                <button className="btn-primary" style={{padding:'9px 16px',borderRadius:10,fontSize:13,display:'flex',alignItems:'center',gap:6}} onClick={()=>setEditing('new')}>
                    <span className="material-icons-round" style={{fontSize:18}}>add</span>Nuevo Grupo
                </button>
            </div>

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
const RadarCoreNode = ({ data }) => (
    <div className="radar-node-glass anim-pulse-border" style={{ 
        width: 140, height: 140, borderRadius: '50%', background: 'rgba(124,58,237,0.1)', 
        border: '3px solid #7c3aed', display: 'flex', flexDirection: 'column', 
        alignItems: 'center', justifyContent: 'center', boxShadow: '0 0 50px rgba(124,58,237,0.3)',
        position: 'relative', zIndex: 10
    }}>
        <div style={{ position: 'absolute', top: -30, width: '100%', textAlign: 'center', fontSize: 10, fontWeight: 900, color: '#7c3aed', textTransform: 'uppercase', letterSpacing: 2 }}>Master Node</div>
        <span className="material-icons-round" style={{ fontSize: 50, color: '#7c3aed' }}>hub</span>
        <div style={{ fontSize: 13, fontWeight: 900, color: 'white', marginTop: 5 }}>CORE PBX</div>
        
        {data.Handle && (
            <>
                <data.Handle type="source" position={data.Position?.Top} id="t" style={{ background: '#7c3aed' }} />
                <data.Handle type="source" position={data.Position?.Bottom} id="b" style={{ background: '#7c3aed' }} />
                <data.Handle type="source" position={data.Position?.Left} id="l" style={{ background: '#7c3aed' }} />
                <data.Handle type="source" position={data.Position?.Right} id="r" style={{ background: '#7c3aed' }} />
            </>
        )}
    </div>
);

const RadarGroupNode = ({ data }) => {
    const H = data.Handle;
    const P = data.Position;
    // Si el nodo padre es circular, el contenido se dibuja en el centro si usamos top:50%, left:50%
    return (
        <div style={{ position:'absolute', top:'50%', left:'50%', transform:'translate(-50%, -50%)', pointerEvents:'none', width:0, height:0 }}>
            {/* Title positioned at top of group circle */}
            <div style={{ 
                position: 'absolute', top: -20, left: '50%', transform: 'translateX(-50%)',
                fontSize: 14, fontWeight: 900, color: '#8b5cf6', textTransform: 'uppercase',
                letterSpacing: 2, whiteSpace: 'nowrap', opacity: 0.8
            }}>
                {data.label}
            </div>
            {/* Multi-side handles for manual and system routing */}
            {H && (
                <>
                    <H type="target" position={P?.Top} id="t" style={{ background: '#8b5cf6' }} />
                    <H type="target" position={P?.Bottom} id="b" style={{ background: '#8b5cf6' }} />
                    <H type="target" position={P?.Left} id="l" style={{ background: '#8b5cf6' }} />
                    <H type="target" position={P?.Right} id="r" style={{ background: '#8b5cf6' }} />

                    <H type="source" position={P?.Top} id="st" style={{ background: '#8b5cf6' }} />
                    <H type="source" position={P?.Bottom} id="sb" style={{ background: '#8b5cf6' }} />
                    <H type="source" position={P?.Left} id="sl" style={{ background: '#8b5cf6' }} />
                    <H type="source" position={P?.Right} id="sr" style={{ background: '#8b5cf6' }} />
                </>
            )}
        </div>
    );
};

const RadarQueueNode = ({ data }) => {
    const H = data.Handle || null; 
    const P = data.Position || null;
    const hasCalls = data.calls_waiting > 0;
    
    return (
        <div className={`glass box-shadow-premium ${hasCalls ? 'anim-vibrate' : ''}`} style={{ 
            width: 150, height: 150, borderRadius: '50%',
            border:`2.5px solid ${hasCalls ? '#f59e0b' : '#3b82f6'}`, 
            background:'var(--surface)', display:'flex', flexDirection:'column',
            alignItems:'center', justifyContent:'center', textAlign: 'center',
            padding: 15, transition:'all 0.3s',
            boxShadow: hasCalls ? '0 0 30px rgba(245,158,11,0.2)' : '0 0 20px rgba(59,130,246,0.1)',
            position: 'relative'
        }}>
             <div style={{ width:40, height:40, background:hasCalls?'rgba(245,158,11,0.15)':'rgba(59,130,246,0.1)', borderRadius:'50%', display:'flex', alignItems:'center', justifyContent:'center', marginBottom: 6 }}>
                <span className={`material-icons-round ${hasCalls ? 'anim-phone-ring' : ''}`} style={{ fontSize:22, color: hasCalls ? '#f59e0b' : '#3b82f6' }}>hub</span>
             </div>
             
             <div style={{ fontSize:8, fontWeight:900, color: hasCalls ? '#f59e0b' : '#3b82f6', textTransform:'uppercase', letterSpacing: 1 }}>{hasCalls ? 'LLAMADA EN COLA' : `COLA #${data.id}`}</div>
             <div style={{ fontSize:12, fontWeight:800, color:'var(--text)', maxWidth: 120, whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis' }}>{data.name}</div>
             
             <div style={{ marginTop: 8, display:'flex', flexDirection: 'column', alignItems:'center' }}>
                <div style={{ fontSize:18, fontWeight:900, color:hasCalls?'#f59e0b':'var(--text)', lineHeight: 1 }}>{data.calls_waiting}</div>
                <div style={{ fontSize:7, fontWeight:700, color:'var(--muted)', textTransform: 'uppercase' }}>En espera</div>
             </div>

             {H && (
                 <>
                    <H type="target" position={P?.Top} id="t" style={{ background: '#f59e0b', opacity: 0.1 }} />
                    <H type="target" position={P?.Bottom} id="b" style={{ background: '#f59e0b', opacity: 0.1 }} />
                    <H type="target" position={P?.Left} id="l" style={{ background: '#f59e0b', opacity: 0.1 }} />
                    <H type="target" position={P?.Right} id="r" style={{ background: '#f59e0b', opacity: 0.1 }} />
                    <H type="source" position={P?.Right} id="sr" style={{ background: '#f59e0b', opacity: 0.1 }} />
                 </>
             )}
        </div>
    );
};

const RadarAgentNode = ({ data }) => {
    const call = data.activeCall;
    const isBusy = data.agent.status === 'BUSY' || !!call;
    const isOffline = data.agent.status === 'OFFLINE';
    const isRinging = call && call.state !== 'Up';
    const isTalking = call && call.state === 'Up';
    const statusColor = isRinging ? '#f59e0b' : (isTalking ? '#22c55e' : (isBusy ? '#ef4444' : '#22c55e'));
    const badgeColor = isRinging ? '#f59e0b' : (isTalking ? '#22c55e' : '#ef4444');

    return (
        <div style={{ position:'relative', width:100, height:100, display:'flex', alignItems:'center', justifyContent:'center' }}>
            {call && (
                <div style={{ 
                    position: 'absolute', top: -15, left: '50%', transform: 'translateX(-50%)',
                    background: badgeColor, color: 'white', padding: '2px 10px', borderRadius: 12,
                    fontSize: 9, fontWeight: 900, whiteSpace: 'nowrap', zIndex: 10,
                    boxShadow: `0 4px 10px ${badgeColor}44`, display:'flex', alignItems:'center', gap:4
                }}>
                    <span className="material-icons-round" style={{fontSize:10}}>{isRinging ? 'notifications_active' : 'record_voice_over'}</span>
                    {isRinging ? 'TIMBRANDO...' : `HABLANDO (${call.duration})`}
                </div>
            )}
            
            <div className={`glass shadow-xl ${isRinging ? 'anim-vibrate anim-phone-ring' : ''} ${isTalking ? 'anim-pulse-border-green' : ''}`} style={{ 
                width: 75, height: 75, borderRadius: '50%', border: `3px solid ${statusColor}`,
                background: isBusy ? 'rgba(239,68,68,0.05)' : 'var(--surface)',
                display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all 0.4s', position: 'relative',
                boxShadow: (isRinging || isTalking) ? `0 0 25px ${statusColor}33` : 'none'
            }}>
                <span className={`material-icons-round ${isRinging ? 'anim-phone-ring' : ''}`} style={{ fontSize: 36, color: statusColor }}>
                    {isRinging ? 'ring_volume' : (isTalking ? 'record_voice_over' : (isBusy ? 'call' : 'person'))}
                </span>
                {!isOffline && <div style={{ position: 'absolute', top: 2, right: 2, width: 14, height: 14, borderRadius: '50%', background: statusColor, border: '3px solid var(--surface)' }} />}

                {data.Handle && (
                    <>
                        <data.Handle type="target" position={data.Position?.Top} id="t" style={{ background: statusColor, opacity: 0.05 }} />
                        <data.Handle type="target" position={data.Position?.Bottom} id="b" style={{ background: statusColor, opacity: 0.05 }} />
                        <data.Handle type="target" position={data.Position?.Left} id="l" style={{ background: statusColor, opacity: 0.05 }} />
                        <data.Handle type="target" position={data.Position?.Right} id="r" style={{ background: statusColor, opacity: 0.05 }} />
                        <data.Handle type="source" position={data.Position?.Right} id="sr" style={{ background: statusColor, opacity: 0 }} />
                    </>
                )}
            </div>
            <div style={{ position: 'absolute', bottom: -25, left: '50%', transform: 'translateX(-50%)', whiteSpace: 'nowrap', textAlign: 'center', pointerEvents: 'none' }}>
                <div style={{ fontSize: 11, fontWeight: 900, color: 'var(--text)' }}>#{data.agent.ext}</div>
                <div style={{ fontSize: 8, fontWeight: 700, color: 'var(--muted)', textTransform: 'uppercase', letterSpacing:0.5 }}>{data.agent.name}</div>
            </div>
        </div>
    );
};

// ─── CUSTOM EDGE ─ DATA FLOW ───
const AnimatedDataEdge = ({ id, data, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style = {}, animated }) => {
    const rfLib = window.ReactFlow || {};
    const getPath = rfLib.getBezierPath || (rfLib.default && rfLib.default.getBezierPath) || ((p) => ["", 0, 0]);
    
    const [edgePath, labelX, labelY] = getPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });

    const isIvr = id.startsWith('e-node-') || id.startsWith('ivr-') || id.startsWith('man-');
    const defaultColor = isIvr ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)';
    const activeColor = isIvr ? '#22c55e' : '#f43f5e'; 

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
                    {!isIvr && (
                        <foreignObject width={100} height={40} x={labelX - 50} y={labelY - 20} className="pointer-events-none">
                            <div style={{ 
                                background: activeColor, color: 'white', 
                                fontSize: 10, fontWeight: 900, padding: '3px 10px', 
                                borderRadius: 20, textAlign: 'center', backdropFilter: 'blur(4px)',
                                border: '1px solid rgba(255,255,255,0.3)',
                                boxShadow: '0 4px 15px rgba(0,0,0,0.3)'
                            }}>
                                {data?.duration || 'VOZ'}
                            </div>
                        </foreignObject>
                    )}
                </>
            )}
        </>
    );
};

const radarNodeTypes = {
    core: RadarCoreNode,
    group: RadarGroupNode,
    queue: RadarQueueNode,
    agent: RadarAgentNode
};
const radarEdgeTypes = {
    animatedData: AnimatedDataEdge
};


// ─────────────────────────────────────────────
// VISTA: RADAR DE TRÁFICO (REACT FLOW)
// ─────────────────────────────────────────────
function ViewRadar({ data, toast }) {
    const rf = window.ReactFlow;
    if (!rf) return (
        <div className="content-area flex flex-col items-center justify-center gap-4 text-gray-500">
            <span className="material-icons-round text-6xl">running_with_errors</span>
            <div className="text-xl font-bold">React Flow no cargado</div>
            <p className="text-sm">Verifica la conexión a internet o la carga del CDN.</p>
        </div>
    );

    // Estrategia de extracción ultra-robusta con FALLBACKS MANUALES
    const ReactFlow = rf.ReactFlow || rf.default || rf;
    const DummyComp = () => null;
    const Background = rf.Background || (rf.default && rf.default.Background) || ReactFlow.Background || DummyComp;
    const Controls = rf.Controls || (rf.default && rf.default.Controls) || ReactFlow.Controls || DummyComp;
    const Handle = rf.Handle || (rf.default && rf.default.Handle) || ReactFlow.Handle || DummyComp;
    const Position = rf.Position || (rf.default && rf.default.Position) || ReactFlow.Position || { Top: 'top', Bottom: 'bottom', Left: 'left', Right: 'right' };
    
    // Fallback manual para applyNodeChanges (para que nunca sea undefined)
    const _anc = rf.applyNodeChanges || (rf.default && rf.default.applyNodeChanges) || ReactFlow.applyNodeChanges;
    const applyNodeChanges = typeof _anc === 'function' ? _anc : (changes, nds) => {
        return nds.map(node => {
            const pos = changes.find(c => c.id === node.id && c.type === 'position');
            const sel = changes.find(c => c.id === node.id && c.type === 'select');
            let next = { ...node };
            if (pos && pos.position) next.position = pos.position;
            if (sel) next.selected = sel.selected;
            return next;
        });
    };

    const _aec = rf.applyEdgeChanges || (rf.default && rf.default.applyEdgeChanges) || ReactFlow.applyEdgeChanges;
    const applyEdgeChanges = typeof _aec === 'function' ? _aec : (changes, eds) => {
        return eds.filter(edge => !changes.find(c => c.id === edge.id && c.type === 'remove'));
    };

    const _ae = rf.addEdge || (rf.default && rf.default.addEdge) || ReactFlow.addEdge;
    const addEdge = typeof _ae === 'function' ? _ae : (params, eds) => {
        return [...eds, { ...params, id: `e-${params.source}-${params.target}-${Date.now()}` }];
    };

    const [nodes, setNodes] = useState([]);
    const [edges, setEdges] = useState([]);

    // Persistencia
    const POS_KEY = 'teleflow_radar_positions';
    const EDGE_KEY = 'teleflow_radar_edges';

    const onNodesChange = useCallback(
        (changes) => {
            setNodes((nds) => {
                const nextNodes = applyNodeChanges(changes, nds);
                const positions = {};
                nextNodes.forEach(n => { if(n.position) positions[n.id] = n.position; });
                localStorage.setItem(POS_KEY, JSON.stringify(positions));
                return nextNodes;
            });
        }, [applyNodeChanges]
    );

    const onEdgesChange = useCallback(
        (changes) => {
            setEdges((eds) => {
                const nextEdges = applyEdgeChanges(changes, eds);
                // Solo guardamos conexiones manuales (las que no empiezan con 'sys-')
                const manualEdges = nextEdges.filter(e => !e.id.startsWith('sys-'));
                localStorage.setItem(EDGE_KEY, JSON.stringify(manualEdges));
                return nextEdges;
            });
        }, [applyEdgeChanges]
    );

    const onConnect = useCallback(
        (params) => {
            setEdges((eds) => {
                const nextEdges = addEdge({ ...params, type: 'animatedData', animated: true, id: `man-${Date.now()}` }, eds);
                const manualEdges = nextEdges.filter(e => !e.id.startsWith('sys-'));
                localStorage.setItem(EDGE_KEY, JSON.stringify(manualEdges));
                return nextEdges;
            });
        }, [addEdge]
    );

    useEffect(() => {
        const savedPositions = JSON.parse(localStorage.getItem(POS_KEY) || '{}');
        const savedEdges = JSON.parse(localStorage.getItem(EDGE_KEY) || '[]');
        const newNodes = [];
        
        // Empezamos con las conexiones manuales guardadas
        const newEdges = [...savedEdges];
        
        const nodeDataGlobals = { Handle, Position, initials, getColor };
        const getPos = (id, fallback) => savedPositions[id] || fallback;

        // 1. CORE PBX
        newNodes.push({
            id: 'core-pbx',
            type: 'core',
            data: { ...nodeDataGlobals },
            position: getPos('core-pbx', { x: 0, y: 0 }),
            dragHandle: '.radar-node-glass' 
        });

        // Distribute Queues, Ring Groups, IVR & Agents
        const queues = data?.pbx?.queues || [];
        const rgs = data?.pbx?.ringgroups || [];
        const ivrs = data?.pbx?.ivrs || [];
        const agents = data?.pbx?.extensions?.filter(a => a.status !== 'OFFLINE') || [];
        const activeCalls = data?.pbx?.live_calls || [];

        let currentY = 0;
        const processedAgents = new Set();
        const groupSpacing = 100;

        const categories = [
            { id: 'q', list: queues, label: 'COLAS DE ATENCIÓN' },
            { id: 'rg', list: rgs, label: 'GRUPOS DE TIMBRADO' },
            { id: 'ivr', list: ivrs, label: 'IVR / MENÚS' }
        ];

        categories.forEach((cat) => {
            cat.list.forEach((item, idx) => {
                const itemAgents = agents.filter(a => {
                    const isMember = (item.members || []).some(m => (typeof m === 'string' ? m === a.ext : m.ext === a.ext));
                    const isInCall = activeCalls.some(c => (c.ext === a.ext || c.dest === a.ext) && (c.dest === item.id || c.from === item.id));
                    return isMember || isInCall;
                });

                if (itemAgents.length === 0 && cat.id !== 'q') return;

                const agentCount = itemAgents.length;
                const circleSize = Math.max(400, 200 + (Math.sqrt(agentCount) * 150));
                const groupId = `parent-${cat.id}-${item.id}`;
                const xPos = 400; // Ajuste de margen lateral
                const yPos = currentY;

                if (cat.id === 'q') {
                    const queueNodeId = `q-${item.id}`;
                    newNodes.push({
                        id: queueNodeId,
                        type: 'queue',
                        data: { ...nodeDataGlobals, ...item },
                        position: getPos(queueNodeId, { x: xPos + circleSize/2 - 75, y: yPos + circleSize/2 - 75 }),
                    });

                    // Sistema: Conexión dinámica desde el PBX Core al elemento central (Cola)
                    if (activeCalls.length > 0) {
                        newEdges.push({
                            id: `sys-core-q-${item.id}`,
                            source: 'core-pbx',
                            sourceHandle: 'r',
                            target: queueNodeId,
                            targetHandle: 'l',
                            type: 'animatedData',
                            animated: activeCalls.some(c => c.dest === item.id || c.from === item.id),
                            style: { stroke: '#8b5cf6', strokeWidth: 2, opacity: 0.2 }
                        });
                    }
                }

                // Sistema: Conexión dinámica (Opcional, desactivada por defecto para evitar ruido)
                if (activeCalls.length > 0) {
                    newEdges.push({
                        id: `sys-core-${groupId}`,
                        source: 'core-pbx',
                        sourceHandle: 'r',
                        target: groupId,
                        targetHandle: 'l',
                        type: 'animatedData',
                        animated: true,
                        style: { stroke: '#8b5cf6', strokeWidth: 2, opacity: 0.15 } // Más tenue
                    });
                }

                itemAgents.forEach((a, aIdx) => {
                    const angle = (aIdx / agentCount) * 2 * Math.PI;
                    const radius = (circleSize / 2) - 60;
                    const ax = (circleSize / 2) + radius * Math.cos(angle) - 40;
                    const ay = (circleSize / 2) + radius * Math.sin(angle) - 40;
                    const nodeAgentId = `a-${cat.id}-${item.id}-${a.ext}`;
                    const isActive = activeCalls.some(c => c.ext === String(a.ext) || c.dest === String(a.ext));

                    newNodes.push({
                        id: nodeAgentId,
                        type: 'agent',
                        data: { ...nodeDataGlobals, agent: a, activeCall: activeCalls.find(c => c.ext === String(a.ext) || c.dest === String(a.ext)) },
                        position: getPos(nodeAgentId, { x: xPos + ax, y: yPos + ay }),
                        className: isActive ? 'anim-phone-ring' : ''
                    });

                    // Conexión dinámica del sistema entre el item y el agente
                    newEdges.push({
                        id: `sys-g-a-${item.id}-${a.ext}`,
                        source: cat.id === 'q' ? `q-${item.id}` : 'core-pbx',
                        sourceHandle: 'sr',
                        target: nodeAgentId,
                        targetHandle: 'l',
                        type: 'animatedData',
                        animated: isActive,
                        style: { stroke: isActive ? '#f43f5e' : '#8b5cf6', strokeWidth: isActive ? 2 : 1.5, opacity: isActive ? 0.8 : 0.1 }
                    });
                });

                currentY += circleSize + groupSpacing;
            });
        });

        // Eliminado: Sección de Otros Internos por solicitud del usuario

        setNodes(newNodes);
        setEdges(newEdges);
    }, [data, Handle, Position]); 

    return (
        <div className="content-area view-enter" style={{ height: 'calc(100vh - 80px)', position: 'relative', overflow: 'hidden', padding: 0 }}>
            <style>{`
                .react-flow__background { background: var(--bg); }
                .react-flow__edge-path { transition: all 0.5s; }
                
                @keyframes pulse-border-red {
                    0% { border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                    70% { border-color: rgba(239, 68, 68, 1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
                    100% { border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
                }
                @keyframes pulse-border-amber {
                    0% { border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
                    70% { border-color: rgba(245, 158, 11, 1); box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
                    100% { border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
                }
                .anim-pulse-border-red { animation: pulse-border-red 2s infinite; }
                .anim-pulse-border-amber { animation: pulse-border-amber 2s infinite; }

                @keyframes vibrate {
                    0% { transform: rotate(0deg) translate(0, 0); }
                    25% { transform: rotate(5deg) translate(1px, -1px); }
                    50% { transform: rotate(-5deg) translate(-1px, 1px); }
                    75% { transform: rotate(5deg) translate(1px, 1px); }
                    100% { transform: rotate(0deg) translate(0, 0); }
                }
                .anim-vibrate { animation: vibrate 0.15s infinite linear; }

                @keyframes phone-ring {
                    0% { transform: scale(1); filter: brightness(1); }
                    50% { transform: scale(1.1); filter: brightness(1.5); }
                    100% { transform: scale(1); filter: brightness(1); }
                }
                .anim-phone-ring { animation: phone-ring 0.5s infinite ease-in-out; }
                @keyframes pulse-border-green {
                    0% { border-color: rgba(34, 197, 94, 0.4); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
                    70% { border-color: rgba(34, 197, 94, 1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
                    100% { border-color: rgba(34, 197, 94, 0.4); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
                }
                .anim-pulse-border-green { animation: pulse-border-green 2s infinite; }
            `}</style>
            
            <div style={{ position: 'absolute', top: 20, left: 24, zIndex: 10, display:'flex', alignItems:'center', gap:10 }}>
                <span className="material-icons-round" style={{ color: 'var(--accent)', fontSize: 24 }}>radar</span>
                <div>
                    <h2 style={{ fontSize:15, fontWeight:900, color:'var(--text)', margin:0, textTransform:'uppercase', letterSpacing:1 }}>Mapa de Tráfico PBX</h2>
                    <div style={{ fontSize:10, color:'var(--muted)', fontWeight:700 }}>Flujo jerárquico y estados en tiempo real</div>
                </div>
            </div>

            <ReactFlow 
                nodes={nodes} 
                edges={edges}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                nodeTypes={radarNodeTypes}
                edgeTypes={radarEdgeTypes}
                fitView
                snapToGrid={true}
                snapGrid={[15, 15]}
                zoomOnScroll={true}
                panOnDrag={true}
                minZoom={0.1}
                maxZoom={2}
            >
                {Background && <Background variant="dots" gap={20} size={1} color="rgba(255,255,255,0.05)" />}
                {Controls && <Controls showInteractive={false} className="glass !border-white/10 !bg-black/20" />}
            </ReactFlow>
        </div>
    );
}


// ─────────────────────────────────────────────
// COMPONENTE: LIVE CALL CARD
// ─────────────────────────────────────────────
// ─────────────────────────────────────────────
// COMPONENTE: LIVE CALL CARD (PREMIUM DESIGN)
// ─────────────────────────────────────────────
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

function ViewVivo2({ data, toast }) {
    const [calls, setCalls] = useState([]);
    const [prev, setPrev] = useState([]);
    const [supervisorExt, setSupervisorExt] = useState(() => localStorage.getItem('tf_supervisor_ext') || '');

    useEffect(() => {
        localStorage.setItem('tf_supervisor_ext', supervisorExt);
    }, [supervisorExt]);

    const load = async () => {
        try {
            const r = await fetch('api/index.php?action=get_active_calls');
            const d = await r.json();
            if (d.success) {
                // Notificación si hay nuevas llamadas
                const newC = d.calls.filter(c => !prev.find(p => p.channel === c.channel));
                newC.forEach(c => {
                    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                        navigator.serviceWorker.controller.postMessage({ type: 'NOTIFY', title: '📞 Llamada Entrante', body: `${c.ext} → ${c.dest}`, tag: 'call-' + c.channel });
                    }
                });
                setPrev(d.calls);
                setCalls(d.calls);
            }
        } catch (e) { }
    };

    useEffect(() => {
        load();
        const t = setInterval(load, 2500); // More frequent updates for real-time feel
        return () => clearInterval(t);
    }, []);

    return (
        <div className="content-area view-enter bg-brand-bg min-h-screen p-6">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    <div className="w-3 h-3 rounded-full bg-brand-danger" style={{ boxShadow: '0 0 12px rgba(239,68,68,0.6)', animation: 'pulse-ring 1.5s infinite' }}></div>
                    <div>
                        <h1 className="text-xl font-black text-white tracking-tight uppercase">Control en Vivo</h1>
                        <p className="text-[11px] font-bold text-gray-500 uppercase tracking-widest">{calls.length} CANALES ACTIVOS</p>
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <div className="flex flex-col items-end">
                        <span className="text-[10px] uppercase font-bold text-gray-500 tracking-widest mb-1.5">MI EXTENSIÓN (SUPERVISOR)</span>
                        <div className="relative">
                            <input 
                                type="text" 
                                placeholder="Ej: 1001"
                                value={supervisorExt}
                                onChange={e => setSupervisorExt(e.target.value)}
                                className="bg-brand-surface border border-white/5 rounded-lg px-4 py-2 text-sm font-bold text-brand-accent focus:outline-none focus:border-brand-accent/50 transition-all w-32 text-center"
                            />
                            <span className="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 text-sm">admin_panel_settings</span>
                        </div>
                    </div>
                </div>
            </div>

            <div className="max-w-6xl mx-auto">
                {calls.length === 0 ? (
                    <div className="glass-effect rounded-2xl py-32 text-center flex flex-col items-center justify-center opacity-60" style={{background: 'rgba(255,255,255,0.02)', border: '1px dashed rgba(255,255,255,0.1)'}}>
                        <div className="w-20 h-20 rounded-full bg-gray-500/10 flex items-center justify-center mb-6">
                            <span className="material-icons-round text-4xl text-gray-600">phone_disabled</span>
                        </div>
                        <h3 className="text-xl font-bold text-gray-400">Silencio en la red</h3>
                        <p className="text-gray-500 mt-2 text-sm">No hay llamadas activas en este momento.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4">
                        {calls.map((c, i) => (
                            <LiveCallCard 
                                key={c.channel || i} 
                                call={c} 
                                data={data} 
                                supervisorExt={supervisorExt}
                                toast={toast} 
                            />
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-12 pt-6 border-t border-white/5 flex items-center justify-between opacity-40">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-brand-success"></span>
                        <span className="text-[10px] font-black uppercase tracking-tighter text-gray-400">Excelente Calidad</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-brand-warning"></span>
                        <span className="text-[10px] font-black uppercase tracking-tighter text-gray-400">Latencia Detectada</span>
                    </div>
                </div>
                <div className="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    PJSIP REAL-TIME STREAM · AUTO-REFRESH 2.5S
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// VISTA: REPORTES AVANZADOS
// ─────────────────────────────────────────────
function ViewReportes({ toast, queue, onClearQueue }) {
    const d=new Date(); d.setDate(d.getDate()-7);
    const [start, setStart] = useState(() => d.toISOString().split('T')[0]);
    const [end, setEnd] = useState(() => new Date().toISOString().split('T')[0]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const chartRef = useRef(null);
    const chartInst = useRef(null);

    const load = async () => {
        setLoading(true);
        try {
            const qParam = queue ? `&queue=${queue}` : '';
            const r = await fetch(`api/index.php?action=get_reports&start=${start}&end=${end}${qParam}`);
            const d = await r.json();
            if(d.success) setStats(d);
            else toast(d.error||'Error al cargar reportes','error');
        } catch { toast('Error de red al obtener reportes','error'); }
        setLoading(false);
    };

    useEffect(() => { load(); }, [queue, start, end]);

    useEffect(() => {
        if (!stats || !chartRef.current || !window.Chart) return;
        if (chartInst.current) chartInst.current.destroy();
        
        const labels = Object.keys(stats.trend);
        const answered = labels.map(l => stats.trend[l].ANSWERED || 0);
        const failed = labels.map(l => (stats.trend[l].FAILED || 0) + (stats.trend[l]['NO ANSWER'] || 0) + (stats.trend[l].BUSY || 0));
        
        chartInst.current = new window.Chart(chartRef.current, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Contestadas', data: answered, borderColor: '#4ade80', backgroundColor: 'rgba(74,222,128,0.2)', fill: true, tension: 0.4, borderWidth: 3, pointBackgroundColor: '#22c55e', pointBorderColor: '#fff', pointRadius: 4, pointHoverRadius: 6 },
                    { label: 'Otras/Fallidas', data: failed, borderColor: '#f87171', backgroundColor: 'rgba(248,113,113,0.1)', fill: true, tension: 0.4, borderWidth: 2, pointBackgroundColor: '#ef4444', pointBorderColor: '#fff', pointRadius: 3, pointHoverRadius: 5 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: '#9ca3af', font: { family: 'Inter', weight: 600 } } },
                    tooltip: { backgroundColor:'rgba(15,15,26,0.9)', titleColor:'#fff', bodyColor:'#cbd5e1', borderColor:'rgba(139,92,246,0.3)', borderWidth:1, padding:12, boxPadding:6, usePointStyle:true }
                },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, ticks: { color: '#6b7280', font: { family: 'monospace' } }, beginAtZero: true },
                    x: { grid: { display: false }, ticks: { color: '#6b7280', font: { family: 'monospace' } } }
                }
            }
        });
    }, [stats]);

    const Card = ({ title, value, sub, icon, color, bg }) => (
        <div className="glass" style={{padding:20,display:'flex',alignItems:'center',gap:16}}>
            <div style={{width:50,height:50,borderRadius:16,background:bg,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:`0 8px 24px ${bg}`}}>
                <span className="material-icons-round" style={{fontSize:24,color:color}}>{icon}</span>
            </div>
            <div>
                <div style={{fontSize:12,fontWeight:700,color:'#9ca3af',textTransform:'uppercase',letterSpacing:'.1em',marginBottom:4}}>{title}</div>
                <div style={{fontSize:28,fontWeight:900,color:'var(--text)',lineHeight:1}}>{value}</div>
                <div style={{fontSize:11,color:'#6b7280',marginTop:6,fontWeight:600}}>{sub}</div>
            </div>
        </div>
    );

    return(
        <div className="content-area view-enter">
            {/* Header + Filtros */}
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:24,flexWrap:'wrap',gap:16}}>
                <div style={{display:'flex', alignItems:'center', gap:15}}>
                    {queue && (
                        <button onClick={onClearQueue} style={{width:40, height:40, borderRadius:12, background:'var(--surface)', border:'1px solid var(--border)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', color:'var(--muted)'}}>
                            <span className="material-icons-round">arrow_back</span>
                        </button>
                    )}
                    <div>
                        <h2 style={{fontSize:22,fontWeight:900,color:'var(--text)'}}>
                            {queue ? `Reporte de Cola #${queue}` : 'Reportes Analíticos'}
                        </h2>
                        <p style={{fontSize:12,color:'#6b7280',marginTop:2}}>
                            {queue ? 'Métricas específicas de esta cola' : 'Métricas y gráficas de la actividad de llamadas'}
                        </p>
                    </div>
                </div>
                <div className="glass" style={{display:'flex',alignItems:'center',gap:10,padding:'8px 16px',borderRadius:14}}>
                    <input type="date" value={start} onChange={e=>setStart(e.target.value)} className="input-tf py-1.5 px-3 rounded-lg text-sm" style={{background:'var(--surface)'}} />
                    <span style={{color:'#6b7280',fontSize:12,fontWeight:800}}>HASTA</span>
                    <input type="date" value={end} onChange={e=>setEnd(e.target.value)} className="input-tf py-1.5 px-3 rounded-lg text-sm" style={{background:'var(--surface)'}} />
                    <button className="btn-primary" style={{padding:'7px 16px',borderRadius:10,fontSize:12,display:'flex',alignItems:'center',gap:5}} onClick={load} disabled={loading}>
                        <span className="material-icons-round" style={{fontSize:16}}>{loading?'hourglass_top':'sync'}</span> Actualizar
                    </button>
                </div>
            </div>

            {loading && !stats && <div style={{textAlign:'center',padding:40,color:'#6b7280'}}><span className="material-icons-round" style={{fontSize:40,animation:'spin-slow 2s linear infinite'}}>refresh</span><div style={{marginTop:10}}>Generando reporte...</div></div>}

            {stats && (
                <>
                    {/* KPIs */}
                    <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(220px,1fr))',gap:16,marginBottom:24}} className="anim-fadeup">
                        <Card title="Total Procesadas" value={stats.stats.total?.toLocaleString()||0} sub="Todas las disposiciones" icon="functions" color="#c4b5fd" bg="rgba(139,92,246,0.15)" />
                        <Card title="Contestadas" value={stats.stats.answered?.toLocaleString()||0} sub={`${stats.stats.total>0?Math.round((stats.stats.answered/stats.stats.total)*100):0}% de efectividad`} icon="check_circle" color="#4ade80" bg="rgba(34,197,94,0.15)" />
                        <Card title="Espera Promedio" value={fmtTime(Math.round(stats.stats.avg_wait||0))} sub="Tiempo antes de atender" icon="hourglass_top" color="#3b82f6" bg="rgba(59,130,246,0.15)" />
                        <Card title="Duración Promedio" value={fmtTime(Math.round(stats.stats.avg_duration||0))} sub="Tiempo de habla (billsec)" icon="timer" color="#f59e0b" bg="rgba(245,158,11,0.15)" />
                    </div>

                    {/* Gráfica principal */}
                    <div className="glass anim-fadeup-2" style={{padding:24,marginBottom:24,height:340,display:'flex',flexDirection:'column'}}>
                        <div style={{fontSize:13,fontWeight:800,color:'var(--text)',marginBottom:16,textTransform:'uppercase',letterSpacing:'.1em'}}>Tendencia de Llamadas por Día</div>
                        <div style={{flex:1,position:'relative',minHeight:0}}>
                            <canvas ref={chartRef} />
                        </div>
                    </div>

                    {/* Tops */}
                    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:24}} className="anim-fadeup-3">
                        <div className="glass" style={{padding:24}}>
                            <div style={{fontSize:13,fontWeight:800,color:'var(--text)',marginBottom:16,textTransform:'uppercase',letterSpacing:'.1em',display:'flex',alignItems:'center',gap:8}}>
                                <span className="material-icons-round" style={{fontSize:18,color:'#8b5cf6'}}>call_made</span> Top Orígenes Activos
                            </div>
                            <div style={{display:'flex',flexDirection:'column',gap:8}}>
                                {stats.origins?.map((o,i)=>(
                                    <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 14px',background:'var(--surface2)',borderRadius:12,border:'1px solid var(--border)'}}>
                                        <div style={{display:'flex',alignItems:'center',gap:10}}>
                                            <div style={{width:24,height:24,borderRadius:6,background:'rgba(139,92,246,0.1)',color:'#c4b5fd',display:'flex',alignItems:'center',justifyContent:'center',fontSize:10,fontWeight:800}}>{i+1}</div>
                                            <span style={{fontSize:14,fontWeight:700,fontFamily:'monospace',color:'var(--text)'}}>{o.src}</span>
                                        </div>
                                        <div style={{fontSize:12,fontWeight:800,color:'#4ade80'}}>{o.count} calls</div>
                                    </div>
                                ))}
                                {!stats.origins?.length && <div style={{color:'#6b7280',fontSize:12,textAlign:'center'}}>Sin datos</div>}
                            </div>
                        </div>
                        <div className="glass" style={{padding:24}}>
                            <div style={{fontSize:13,fontWeight:800,color:'var(--text)',marginBottom:16,textTransform:'uppercase',letterSpacing:'.1em',display:'flex',alignItems:'center',gap:8}}>
                                <span className="material-icons-round" style={{fontSize:18,color:'#3b82f6'}}>call_received</span> Top Destinos Alcanzados
                            </div>
                            <div style={{display:'flex',flexDirection:'column',gap:8}}>
                                {stats.dests?.map((d,i)=>(
                                    <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 14px',background:'var(--surface2)',borderRadius:12,border:'1px solid var(--border)'}}>
                                        <div style={{display:'flex',alignItems:'center',gap:10}}>
                                            <div style={{width:24,height:24,borderRadius:6,background:'rgba(59,130,246,0.1)',color:'#60a5fa',display:'flex',alignItems:'center',justifyContent:'center',fontSize:10,fontWeight:800}}>{i+1}</div>
                                            <span style={{fontSize:14,fontWeight:700,fontFamily:'monospace',color:'var(--text)'}}>{d.dst}</span>
                                        </div>
                                        <div style={{fontSize:12,fontWeight:800,color:'#f59e0b'}}>{d.count} calls</div>
                                    </div>
                                ))}
                                {!stats.dests?.length && <div style={{color:'#6b7280',fontSize:12,textAlign:'center'}}>Sin datos</div>}
                            </div>
                        </div>
                    </div>
                </>
            )}
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
    { re: /\bNOTIFY\b/,     color:'#c4b5fd', label:'NOTIFY',   icon:'notifications' },
    { re: /is ringing/i,     color:'#f59e0b', label:'SONANDO',    icon:'notifications_active' },
    { re: /answered/i,       color:'#22c55e', label:'CONTESTADA', icon:'call' },
    { re: /is now Unreachable/i, color:'#ef4444', label:'OFFLINE',    icon:'link_off' },
    { re: /is now Reachable/i,   color:'#22c55e', label:'ONLINE',     icon:'link' },
    { re: /is now Lagged/i,      color:'#f59e0b', label:'LAGGED',     icon:'timer' },
    { re: /m=audio/i,          color:'#c4b5fd', label:'SDP AUDIO', icon:'audiotrack' },
    { re: /m=video/i,          color:'#f472b6', label:'SDP VIDEO', icon:'videocam' },
    { re: /ICE candidate/i,    color:'#2dd4bf', label:'ICE CAND',  icon:'lan' },
    { re: /ICE state changed/i, color:'#fb923c', label:'ICE STATE', icon:'wifi_tethering' },
    { re: /\bREINVITE\b/i,     color:'#8b5cf6', label:'RE-INVITE', icon:'history' },
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
                            fontSize:10, fontWeight:700, color:'#c4b5fd',
                            background: 'rgba(139,92,246,0.15)',
                            border:'1px solid rgba(139,92,246,0.3)',
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
                        <div style={{width:32, height:32, background:'rgba(139,92,246,0.1)', color:'var(--accent)', borderRadius:8, display:'flex', alignItems:'center', justifyContent:'center'}}>
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
                <ReactFlowComp
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
                </ReactFlowComp>

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
                        <div style={{padding:20, borderBottom:'1px solid var(--border)', display:'flex', justifyContent:'space-between', alignItems:'center', background:'rgba(139,92,246,0.1)'}}>
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
        </div>
    );
}

// ─────────────────────────────────────────────
// APP PRINCIPAL
// ─────────────────────────────────────────────
function App() {
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

    // Dark/light toggle
    useEffect(()=>{ document.body.classList.toggle('light',!darkMode); },[darkMode]);

    // Toast helper
    const showToast = (msg, type='info') => {
        setToast({msg,type});
        setTimeout(()=>setToast(null),4000);
    };

    // Load data
    const load = useCallback(async () => {
        try {
            const res = await fetch('api/index.php?action=get_full_data', { credentials: 'include' });
            const d = await res.json();
            if (d.status === 'error' && d.message === 'No autorizado') {
                setUser(null);
            } else {
                setData(d);
                if (d.user) setUser(d.user);
            }
        } catch(e) {}
    }, []);

    // Check session on mount
    useEffect(()=>{
        load();
        const t = setInterval(load, 5000);
        return () => clearInterval(t);
    }, [load]);

    // Register SW
    /* El Service Worker ahora es manejado por la PWA en /softphone/
    useEffect(()=>{
        if('serviceWorker' in navigator){
            navigator.serviceWorker.register('sw.js').catch(()=>{});
        }
    },[]);
    */

    if (user === null) return <Login onLogin={u => { setUser(u); load(); }} />;

    const renderView = () => {
        switch(view) {
            case 'dashboard':   return <ViewDashboard data={data} />;
            case 'extensiones': return <ViewExtensiones data={data} toast={showToast} />;
            case 'agentes':     return <ViewAgentes toast={showToast} />;
            case 'vivo':        return <ViewVivo2 data={data} toast={showToast} />;
            case 'radar':       return <ViewRadar data={data} toast={showToast} />;
            case 'cdr':         return <ViewCDR toast={showToast} />;
            case 'reportes':    return <ViewReportes toast={showToast} queue={reportQueue} onClearQueue={()=>setReportQueue(null)} />;
            case 'colas':       return <ViewColas toast={showToast} data={data} onReport={(qid)=>{setReportQueue(qid);setView('reportes');}} />;
            case 'grupos':      return <ViewGrupos toast={showToast} />;
            case 'ivr':         return <ViewIVR toast={showToast} />;
// El Softphone ahora es una PWA independiente en /softphone/

            case 'configuracion': return <ViewConfiguracion />;
            default:            return <div className="content-area">Vista no implementada</div>;
        }
    };

    return (
        <div id="app">
            <div className={`sidebar-overlay ${!collapsed && window.innerWidth < 768 ? 'active' : ''}`} onClick={() => setCollapsed(true)} />
            <Sidebar 
                view={view} setView={setView} user={user} 
                onLogout={async () => { await fetch('api/index.php?action=logout', { credentials: 'include' }); setUser(null); }} 
                collapsed={collapsed} setCollapsed={setCollapsed}
                darkMode={darkMode} setDarkMode={setDarkMode}
                data={data}
                activeCalls={data?.pbx?.live_calls?.length || 0}
            />
            <main className="main-content" style={{padding: 0}}>
                <div style={{flex:1, overflowY:'auto', height:'100%'}}>
                    {renderView()}
                </div>
            </main>

            {/* Reemplazo de boton menu para moviles */}
            {window.innerWidth < 768 && collapsed && (
                <button 
                  onClick={() => setCollapsed(false)}
                  style={{position:'fixed', bottom:20, right:20, zIndex:990, width:48, height:48, borderRadius:'50%', background:'var(--accent)', color:'white', border:'none', display:'flex', alignItems:'center', justifyContent:'center', boxShadow:'0 4px 12px rgba(0,0,0,0.3)'}}
                >
                    <span className="material-icons-round">menu</span>
                </button>
            )}

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

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
</script>
</body>
</html>

