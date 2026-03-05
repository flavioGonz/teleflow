<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0f">
    <title>TeleFlow · Next-Gen PBX Control</title>
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); overflow: hidden; height: 100vh; }

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
        .anim-fadeup { animation: fadeUp 0.7s ease both; }
        .anim-fadeup-2 { animation: fadeUp 0.7s ease 0.15s both; }
        .anim-fadeup-3 { animation: fadeUp 0.7s ease 0.3s both; }
        .input-tf {
            background: rgba(0,0,0,0.35);
            border: 1px solid var(--border);
            color: var(--text);
            outline: none;
            transition: border-color .25s, box-shadow .25s;
            width: 100%;
        }
        .input-tf:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
        }
        .input-tf::placeholder { color: #4b5563; }
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
            transition: width .3s;
        }
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
            const r = await fetch('api/index.php?action=login', { method:'POST', body:fd });
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
// SIDEBAR
// ─────────────────────────────────────────────
function Sidebar({ view, setView, user, onLogout }) {
    const nav = [
        { section: 'Principal' },
        { id:'dashboard', icon:'grid_view', label:'Dashboard' },
        { id:'extensiones', icon:'group', label:'Extensiones' },
        { section: 'Call Center' },
        { id:'agentes', icon:'support_agent', label:'Agentes' },
        { id:'vivo', icon:'sensors', label:'Llamadas Vivo' },
        { id:'colas', icon:'queue', label:'Colas' },
        { section: 'Registros' },
        { id:'grabaciones', icon:'mic', label:'Grabaciones' },
        { id:'cdr', icon:'history', label:'CDR' },
    ];

    return (
        <div className="sidebar">
            <div className="sidebar-logo">
                <div style={{display:'flex',alignItems:'center',gap:10}}>
                    <div style={{width:32,height:32,background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',borderRadius:9,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                        <span className="material-icons-round" style={{fontSize:16,color:'white'}}>sensors</span>
                    </div>
                    <div>
                        <div style={{fontSize:14,fontWeight:800,color:'white',letterSpacing:-0.5,fontStyle:'italic'}}>TeleFlow</div>
                        <div style={{fontSize:9,fontWeight:600,color:'#6b7280',letterSpacing:'0.1em',textTransform:'uppercase'}}>PBX Control</div>
                    </div>
                </div>
            </div>

            <div className="sidebar-nav">
                {nav.map((item, i) =>
                    item.section
                        ? <div key={i} className="nav-section">{item.section}</div>
                        : <div
                            key={item.id}
                            className={`nav-item ${view===item.id?'active':''}`}
                            onClick={()=>setView(item.id)}
                          >
                            <span className="material-icons-round">{item.icon}</span>
                            {item.label}
                          </div>
                )}
            </div>

            <div className="sidebar-bottom">
                <div style={{display:'flex',alignItems:'center',gap:10,padding:'8px 12px',borderRadius:10,background:'rgba(139,92,246,0.08)',marginBottom:8}}>
                    <div style={{width:28,height:28,borderRadius:8,background:'linear-gradient(135deg,#8b5cf6,#6d28d9)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:11,fontWeight:800,color:'white',flexShrink:0}}>
                        {initials(user)}
                    </div>
                    <div style={{flex:1,minWidth:0}}>
                        <div style={{fontSize:12,fontWeight:600,color:'white',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{user}</div>
                        <div style={{fontSize:10,color:'#22c55e',display:'flex',alignItems:'center',gap:3}}>
                            <span style={{width:5,height:5,borderRadius:'50%',background:'#22c55e',display:'inline-block'}} /> Conectado
                        </div>
                    </div>
                </div>
                <div className="nav-item" onClick={onLogout} style={{color:'#ef4444',marginBottom:0}}>
                    <span className="material-icons-round" style={{color:'#ef4444'}}>logout</span>
                    Cerrar Sesión
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// TOPBAR
// ─────────────────────────────────────────────
function Topbar({ view, data, onRefresh }) {
    const titles = {
        dashboard:'Dashboard', extensiones:'Extensiones', agentes:'Monitor de Agentes',
        vivo:'Llamadas en Vivo', colas:'Colas', grabaciones:'Grabaciones', cdr:'CDR'
    };
    const [time, setTime] = useState(new Date());
    useEffect(()=>{ const t=setInterval(()=>setTime(new Date()),1000); return()=>clearInterval(t); },[]);

    return (
        <div className="topbar">
            <div>
                <h2 style={{fontSize:18,fontWeight:700,color:'white'}}>{titles[view]||view}</h2>
                <div style={{fontSize:11,color:'#6b7280',marginTop:1}}>
                    {time.toLocaleDateString('es-UY',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}
                </div>
            </div>
            <div style={{display:'flex',alignItems:'center',gap:12}}>
                <div style={{fontSize:11,padding:'5px 12px',borderRadius:8,background:'var(--surface2)',border:'1px solid var(--border)',fontFamily:'monospace',color:'#c4b5fd',fontWeight:600}}>
                    {time.toLocaleTimeString('es-UY')}
                </div>
                <button onClick={onRefresh} style={{width:36,height:36,borderRadius:10,background:'var(--surface2)',border:'1px solid var(--border)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',color:'#9ca3af',transition:'all .2s'}}
                    onMouseEnter={e=>{e.currentTarget.style.borderColor='rgba(139,92,246,.4)';e.currentTarget.style.color='white'}}
                    onMouseLeave={e=>{e.currentTarget.style.borderColor='var(--border)';e.currentTarget.style.color='#9ca3af'}}>
                    <span className="material-icons-round" style={{fontSize:18}}>refresh</span>
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

    const stats = [
        { label:'Extensiones Online', val:`${online}/${exts.length}`, icon:'group', color:'#22c55e', bg:'rgba(34,197,94,0.12)' },
        { label:'En Llamada', val:busy, icon:'call', color:'#f59e0b', bg:'rgba(245,158,11,0.12)' },
        { label:'Grabaciones Hoy', val:recs.length, icon:'mic', color:'#8b5cf6', bg:'rgba(139,92,246,0.12)' },
        { label:'CPU PBX', val:`${cpu}%`, icon:'memory', color:'#3b82f6', bg:'rgba(59,130,246,0.12)' },
    ];

    return (
        <div className="content-area">
            <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:16,marginBottom:24}}>
                {stats.map(s=>(
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
// VISTA: EXTENSIONES
// ─────────────────────────────────────────────
function ViewExtensiones({ data }) {
    const [search, setSearch] = useState('');
    const [selected, setSelected] = useState(null);
    const exts = (data?.pbx?.extensions||[]).filter(e=>
        e.ext.includes(search) || e.name.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="content-area">
            {/* Buscador */}
            <div style={{position:'relative',marginBottom:20,maxWidth:400}}>
                <span className="material-icons-round" style={{position:'absolute',left:12,top:'50%',transform:'translateY(-50%)',fontSize:18,color:'#6b7280'}}>search</span>
                <input
                    className="input-tf py-2.5 pl-10 pr-4 rounded-xl text-sm"
                    placeholder="Buscar extensión..."
                    value={search}
                    onChange={e=>setSearch(e.target.value)}
                />
            </div>

            <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(260px,1fr))',gap:12}}>
                {exts.map(ext=>(
                    <div key={ext.ext} className="glass glass-hover" style={{padding:'16px',cursor:'pointer'}} onClick={()=>setSelected(ext)}>
                        <div style={{display:'flex',alignItems:'center',gap:12}}>
                            <img src={ext.avatar} style={{width:44,height:44,borderRadius:12,objectFit:'cover',border:'2px solid var(--border)'}}
                                onError={e=>{ const el=e.target; el.style.display='none'; el.nextSibling.style.display='flex'; }} />
                            <div className={`agent-avatar bg-gradient-to-br ${getColor(ext.name)}`} style={{display:'none'}}>{initials(ext.name)}</div>
                            <div style={{flex:1,minWidth:0}}>
                                <div style={{fontSize:15,fontWeight:800,color:'white'}}>#{ext.ext}</div>
                                <div style={{fontSize:12,color:'#9ca3af',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{ext.name}</div>
                            </div>
                            <span className={`badge ${ext.status==='ONLINE'?'badge-online':ext.status==='BUSY'?'badge-busy':'badge-offline'}`}>
                                <span className={`badge-dot ${ext.status==='ONLINE'?'dot-online':ext.status==='BUSY'?'dot-busy':'dot-offline'}`} />
                                {ext.status}
                            </span>
                        </div>
                        <div style={{marginTop:12,display:'flex',gap:16,fontSize:11,color:'#6b7280'}}>
                            <span>IP: <span style={{color:'#c4b5fd',fontFamily:'monospace'}}>{ext.ip}</span></span>
                            <span>RTT: <span style={{color:'#c4b5fd'}}>{ext.rtt}</span></span>
                        </div>
                    </div>
                ))}
                {exts.length===0 && <div style={{color:'#6b7280',fontSize:13,gridColumn:'1/-1',textAlign:'center',padding:40}}>No se encontraron extensiones</div>}
            </div>

            {/* Modal detalle */}
            {selected && (
                <div className="modal-backdrop" onClick={()=>setSelected(null)}>
                    <div className="modal-box" onClick={e=>e.stopPropagation()}>
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:20}}>
                            <div style={{display:'flex',gap:14,alignItems:'center'}}>
                                <img src={selected.avatar} style={{width:56,height:56,borderRadius:14,objectFit:'cover'}} />
                                <div>
                                    <div style={{fontSize:20,fontWeight:800,color:'white'}}>#{selected.ext}</div>
                                    <div style={{fontSize:13,color:'#9ca3af'}}>{selected.name}</div>
                                </div>
                            </div>
                            <button onClick={()=>setSelected(null)} style={{background:'none',border:'none',cursor:'pointer',color:'#6b7280',padding:0}}>
                                <span className="material-icons-round" style={{fontSize:22}}>close</span>
                            </button>
                        </div>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
                            {[
                                {l:'Estado',v:<span className={`badge ${selected.status==='ONLINE'?'badge-online':selected.status==='BUSY'?'badge-busy':'badge-offline'}`}>{selected.status}</span>},
                                {l:'IP Origen',v:<code style={{color:'#c4b5fd'}}>{selected.ip}</code>},
                                {l:'MAC',v:<code style={{color:'#c4b5fd',fontSize:11}}>{selected.mac||'—'}</code>},
                                {l:'Latencia RTT',v:selected.rtt},
                            ].map(({l,v})=>(
                                <div key={l} style={{background:'var(--surface2)',borderRadius:10,padding:'12px 14px',border:'1px solid var(--border)'}}>
                                    <div style={{fontSize:10,fontWeight:600,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.08em',marginBottom:6}}>{l}</div>
                                    <div style={{fontSize:13,fontWeight:600,color:'white'}}>{v}</div>
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
// VISTA: AGENTES (dashboard.html integrado)
// ─────────────────────────────────────────────
function ViewAgentes() {
    const [agents, setAgents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [selected, setSelected] = useState(null);

    const load = useCallback(async () => {
        try {
            const r = await fetch('api/agents.php?action=get_agents_data');
            const d = await r.json();
            if (d.success) setAgents(d.agents);
        } catch {}
        setLoading(false);
    }, []);

    useEffect(() => { load(); const t = setInterval(load, 5000); return () => clearInterval(t); }, [load]);

    const filtered = agents.filter(a =>
        (a.name.toLowerCase().includes(search.toLowerCase()) || a.ext?.includes(search)) &&
        (!statusFilter || a.status === statusFilter)
    );
    const online = agents.filter(a=>a.status==='ONLINE').length;
    const busy   = agents.filter(a=>a.status==='BUSY').length;
    const calls  = agents.reduce((s,a)=>s+(a.total_calls||0),0);

    return (
        <div className="content-area">
            {/* Stats rápidas */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:12,marginBottom:20}}>
                {[
                    {l:'Agentes Online',v:`${online}/${agents.length}`,c:'#22c55e'},
                    {l:'En Llamada',v:busy,c:'#f59e0b'},
                    {l:'Total Llamadas',v:calls,c:'#8b5cf6'},
                    {l:'Offline',v:agents.length-online-busy,c:'#6b7280'},
                ].map(s=>(
                    <div key={s.l} className="glass" style={{padding:'14px 16px'}}>
                        <div style={{fontSize:10,fontWeight:700,color:'#6b7280',textTransform:'uppercase',letterSpacing:'.08em'}}>{s.l}</div>
                        <div style={{fontSize:28,fontWeight:800,color:s.c,marginTop:4,lineHeight:1}}>{s.v}</div>
                    </div>
                ))}
            </div>

            {/* Filtros */}
            <div style={{display:'flex',gap:10,marginBottom:16}}>
                <div style={{position:'relative',flex:1}}>
                    <span className="material-icons-round" style={{position:'absolute',left:12,top:'50%',transform:'translateY(-50%)',fontSize:17,color:'#6b7280'}}>search</span>
                    <input className="input-tf py-2 pl-10 pr-4 rounded-xl text-sm" placeholder="Buscar agente..." value={search} onChange={e=>setSearch(e.target.value)} />
                </div>
                <select
                    className="input-tf py-2 px-4 rounded-xl text-sm"
                    style={{width:'auto'}}
                    value={statusFilter}
                    onChange={e=>setStatusFilter(e.target.value)}
                >
                    <option value="">Todos</option>
                    <option value="ONLINE">Online</option>
                    <option value="BUSY">En Llamada</option>
                    <option value="OFFLINE">Offline</option>
                </select>
            </div>

            {/* Header tabla */}
            <div style={{display:'grid',gridTemplateColumns:'2.5fr 1.2fr 1.2fr 1.5fr 1.5fr',padding:'8px 18px',fontSize:10,fontWeight:700,color:'#4b5563',textTransform:'uppercase',letterSpacing:'.1em',marginBottom:4}}>
                <span>Agente</span><span style={{textAlign:'center'}}>Estado</span><span style={{textAlign:'center'}}>En Llamada</span><span style={{textAlign:'center'}}>Rendimiento</span><span style={{textAlign:'right'}}>Red</span>
            </div>

            {loading
                ? <div style={{textAlign:'center',padding:40,color:'#6b7280'}}>Cargando agentes...</div>
                : filtered.length === 0
                    ? <div style={{textAlign:'center',padding:40,color:'#6b7280'}}>Sin agentes</div>
                    : filtered.map(agent=>(
                        <div key={agent.ext} className="agent-row" onClick={()=>setSelected(agent)}>
                            <div style={{display:'flex',alignItems:'center',gap:12}}>
                                <div className={`agent-avatar bg-gradient-to-br ${getColor(agent.name)}`}>{initials(agent.name)}</div>
                                <div>
                                    <div style={{fontSize:13,fontWeight:700,color:'white'}}>#{agent.ext}</div>
                                    <div style={{fontSize:11,color:'#9ca3af'}}>{agent.name}</div>
                                </div>
                            </div>
                            <div style={{textAlign:'center'}}>
                                <span className={`badge ${agent.status==='ONLINE'?'badge-online':agent.status==='BUSY'?'badge-busy':'badge-offline'}`}>
                                    <span className={`badge-dot ${agent.status==='ONLINE'?'dot-online':agent.status==='BUSY'?'dot-busy':'dot-offline'}`} />
                                    {agent.status}
                                </span>
                            </div>
                            <div style={{textAlign:'center'}}>
                                {(agent.in_call||0)>0
                                    ? <span style={{fontSize:12,fontWeight:700,color:'#f59e0b'}}>{fmtTime(agent.in_call)}</span>
                                    : <span style={{fontSize:12,color:'#6b7280'}}>—</span>}
                            </div>
                            <div style={{textAlign:'center',display:'flex',gap:16,justifyContent:'center'}}>
                                <div style={{textAlign:'center'}}>
                                    <div style={{fontSize:13,fontWeight:700,color:'#c4b5fd'}}>{agent.total_calls||0}</div>
                                    <div style={{fontSize:9,color:'#6b7280'}}>LLAMADAS</div>
                                </div>
                                <div style={{textAlign:'center'}}>
                                    <div style={{fontSize:13,fontWeight:700,color:'#c4b5fd'}}>{agent.avg_aht||'0:00'}</div>
                                    <div style={{fontSize:9,color:'#6b7280'}}>AHT</div>
                                </div>
                            </div>
                            <div style={{textAlign:'right'}}>
                                <div style={{fontSize:10,fontFamily:'monospace',color:'#ec4899'}}>{agent.ip}</div>
                                <div style={{fontSize:10,fontFamily:'monospace',color:'#8b5cf6'}}>{agent.mac}</div>
                            </div>
                        </div>
                    ))
            }

            {/* Modal agente */}
            {selected && (
                <div className="modal-backdrop" onClick={()=>setSelected(null)}>
                    <div className="modal-box" onClick={e=>e.stopPropagation()}>
                        <div style={{display:'flex',justifyContent:'space-between',marginBottom:20}}>
                            <div style={{display:'flex',gap:12,alignItems:'center'}}>
                                <div className={`agent-avatar bg-gradient-to-br ${getColor(selected.name)}`} style={{width:48,height:48,borderRadius:12,fontSize:15}}>{initials(selected.name)}</div>
                                <div>
                                    <div style={{fontSize:18,fontWeight:800,color:'white'}}>#{selected.ext} — {selected.name}</div>
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
// VISTA: PLACEHOLDER (colas, CDR)
// ─────────────────────────────────────────────
function ViewPlaceholder({ label }) {
    return (
        <div className="content-area" style={{display:'flex',alignItems:'center',justifyContent:'center',flex:1}}>
            <div style={{textAlign:'center',color:'#6b7280'}}>
                <span className="material-icons-round" style={{fontSize:56,display:'block',marginBottom:12,color:'#374151'}}>construction</span>
                <div style={{fontSize:15,fontWeight:600,color:'#4b5563'}}>{label}</div>
                <div style={{fontSize:12,marginTop:6}}>Próximamente disponible</div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// APP PRINCIPAL
// ─────────────────────────────────────────────
function App() {
    const [isLogged, setIsLogged] = useState(false);
    const [user, setUser] = useState('');
    const [view, setView] = useState('dashboard');
    const [data, setData] = useState({ pbx:{ extensions:[], recordings:[], calls:[], queues:[] }, system:{} });

    const refresh = useCallback(async () => {
        try {
            const r = await fetch('api/index.php?action=get_full_data');
            if (r.status === 403) { setIsLogged(false); return; }
            const d = await r.json();
            if (d) setData(d);
        } catch {}
    }, []);

    useEffect(() => {
        if (!isLogged) return;
        refresh();
        const t = setInterval(refresh, 4000);
        return () => clearInterval(t);
    }, [isLogged, refresh]);

    const onLogin = (u) => { setUser(u); setIsLogged(true); };
    const onLogout = async () => {
        await fetch('api/index.php?action=logout');
        setIsLogged(false); setUser('');
    };

    if (!isLogged) return <Login onLogin={onLogin} />;

    const renderView = () => {
        switch(view) {
            case 'dashboard':    return <ViewDashboard data={data} />;
            case 'extensiones':  return <ViewExtensiones data={data} />;
            case 'agentes':      return <ViewAgentes />;
            case 'vivo':         return <ViewVivo data={data} />;
            case 'grabaciones':  return <ViewGrabaciones data={data} />;
            default:             return <ViewPlaceholder label={view} />;
        }
    };

    return (
        <div id="app">
            <Sidebar view={view} setView={setView} user={user} onLogout={onLogout} />
            <div className="main-content">
                <Topbar view={view} data={data} onRefresh={refresh} />
                {renderView()}
            </div>
        </div>
    );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
</script>
</body>
</html>
