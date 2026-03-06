<?php
// Teleflow WebPhone (PWA Standalone)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <title>Teleflow Softphone</title>
    <meta name="theme-color" content="#0a0a0f">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">📱</text></svg>'>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- React DevTools Workaround -->
    <script>
        if (typeof window !== 'undefined' && window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
            window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on || function() {};
        }
    </script>
    
    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#007bff",
                        "background-light": "#f5f7f8",
                        "background-dark": "#0f1923",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- SIP.js 0.20.0 (Compatibilidad Asterisk PJSIP WSS) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sip.js/0.20.0/sip.min.js"></script>

    <style>
        :root {
            --bg: #0a0a0f;
            --surface: #13131c;
            --surface2: #1e1e2d;
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --accent: #10b981;
            --danger: #ef4444;
            --text: #ffffff;
            --muted: #9ca3af;
            --border: rgba(255,255,255,0.08);
        }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevent bounce scrolling on iOS */
            overscroll-behavior-y: none;
            display: flex;
            flex-direction: column;
            width: 100vw;
            height: 100dvh; /* Mobile aware height */
        }

        /* Ocultar barra de scroll para vista nativa */
        ::-webkit-scrollbar { width: 0px; background: transparent; }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            background: var(--bg);
        }

        .header {
            padding: env(safe-area-inset-top, 20px) 20px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            z-index: 10;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .bottom-nav {
            display: flex;
            background: rgba(19, 19, 28, 0.9);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            padding-bottom: env(safe-area-inset-bottom, 0px);
            z-index: 100;
        }
        .nav-item {
            flex: 1;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            border: none;
            background: transparent;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
        }
        .nav-item.active { color: var(--primary); }
        .nav-item span.material-icons-round { font-size: 24px; margin-bottom: 4px; transition: transform 0.2s; }
        .nav-item.active span.material-icons-round { transform: scale(1.1); font-weight: 900;}

        .input-tf {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: white;
            outline: none;
            transition: all 0.3s;
        }
        .input-tf:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(139,92,246,0.2); }

        .dial-btn {
            background: var(--surface2);
            border: none;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            transition: transform 0.1s, background 0.2s;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            aspect-ratio: 1;
        }
        .dial-btn:active { background: rgba(139,92,246,0.2); transform: scale(0.92); }

        .call-btn {
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .call-btn:active { transform: scale(0.9); }

        .call-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bg);
            z-index: 200;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp { from { transform: translateY(100%); opacity:0; } to { transform: translateY(0); opacity:1;} }
        @keyframes blink { 0%, 100% {opacity:1} 50% {opacity:0.3} }
        @keyframes pulse { 0% {box-shadow:0 0 0 0 rgba(16,185,129,0.4)} 70% {box-shadow:0 0 0 20px rgba(16,185,129,0)} 100% {box-shadow:0 0 0 0 rgba(16,185,129,0)} }

        /* Toast Styles */
        .toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            padding: 12px 24px; border-radius: 50px; color: white;
            font-weight: 600; font-size: 13px; z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex; alignItems: center; gap: 8px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from{top:-50px;opacity:0} to{top:20px;opacity:1} }

        /* Premium Design Additions */
        .ios-blur { backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px); }
        .ios-button-bg { background-color: rgba(255, 255, 255, 0.1); border: none; }
        .ios-button-bg:active { background-color: rgba(255, 255, 255, 0.25); transform: scale(0.92); }
        .end-call-bg { background-color: #ff3b30; }
        
        .glass-panel {
            background: rgba(23, 38, 54, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        .status-bar-time { font-weight: 600; font-size: 14px; }
        .home-indicator { width: 130px; height: 5px; background: rgba(255,255,255,0.2); border-radius: 10px; margin: 10px auto; }
        
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
            transition: font-variation-settings 0.2s;
        }
        .filled-icon { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        .bg-app-gradient {
            background: linear-gradient(180deg, #0f1923 0%, #1a2a3a 50%, #0f1923 100%);
        }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useRef, useCallback } = React;

        function formatTime(seconds) {
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }

        function SoftphoneApp() {
            // General settings / Auth
            const [ext, setExt] = useState(() => localStorage.getItem('tf_sip_ext') || '');
            const [pass, setPass] = useState(() => localStorage.getItem('tf_sip_pass') || 'teleflow123');
            const [domain, setDomain] = useState(() => '201.217.134.124'); // Default IP for production Asterisk
            const [status, setStatus] = useState('Desconectado');
            
            // Navigation
            const [activeTab, setActiveTab] = useState('dashboard'); 
            const [currentTime, setCurrentTime] = useState(new Date());

            useEffect(() => {
                const timer = setInterval(() => setCurrentTime(new Date()), 1000);
                return () => clearInterval(timer);
            }, []);            
            // Call State
            const [dest, setDest] = useState('');
            const [simpleUser, setSimpleUser] = useState(null);
            const [callStatus, setCallStatus] = useState(null); // 'calling', 'ringing', 'in-call', 'held'
            const [isMuted, setIsMuted] = useState(false);
            const [isHeld, setIsHeld] = useState(false);
            const [remoteNumber, setRemoteNumber] = useState('');
            const [callDirection, setCallDirection] = useState(''); // 'in' or 'out'
            const [elapsed, setElapsed] = useState(0);

            // Audio element required by SIP.js
            const audioRef = useRef(null);
            const timerRef = useRef(null);

            // History / Contacts
            const [history, setHistory] = useState(() => JSON.parse(localStorage.getItem('tf_call_history')||'[]'));
            const [contacts, setContacts] = useState([]); // Fetch from API or PBX if available
            const [toast, setToast] = useState(null);

            const showToast = (msg, type='info') => {
                setToast({msg, type});
                setTimeout(()=>setToast(null), 3000);
            };

            // ───────────────── API POLLING FOR CONTACTS ─────────────────
            useEffect(() => {
                const loadContacts = async () => {
                    try {
                        const r = await fetch('../api/index.php?action=get_agents_data');
                        const d = await r.json();
                        if(d.success) setContacts(d.agents);
                    } catch(e) {}
                };
                loadContacts();
            }, []);

            // ───────────────── SIP REGISTRATION ─────────────────
            const connect = () => {
                if(!ext || !pass) return showToast('Extension & Password required','error');
                if(!window.SIP) return showToast('SIP stack initializing...','warning');
                
                try {
                    const server = 'wss://' + window.location.host + '/ws'; // Usa wss://pbx01.infratec.com.uy/ws
                    const aor = 'sip:' + ext + '@' + domain;

                    // Instanciar SimpleUser con SIP.js 0.20.0
                    const su = new window.SIP.Web.SimpleUser(server, {
                        aor,
                        media: { remote: { audio: audioRef.current } },
                        userAgentOptions: {
                            authorizationUsername: ext.trim(),
                            authorizationPassword: pass.trim(),
                            transportOptions: { server: server, traceSip: false }
                        }
                    });

                    su.delegate = {
                        onCallReceived: () => { 
                            showToast('Llamada Entrante','info');
                            setRemoteNumber(su.session.remoteIdentity.uri.user); 
                            setCallDirection('in');
                            setCallStatus('ringing'); 
                            setActiveTab('dialpad');
                            
                            // Trigger wake lock vibration if mobile
                            if (navigator.vibrate) navigator.vibrate([500, 300, 500]);
                        },
                        onCallHangup: () => { 
                            setCallStatus(null);
                            setRemoteNumber('');
                            setIsHeld(false);
                            setIsMuted(false);
                            clearInterval(timerRef.current);
                            setElapsed(0);
                            setStatus('Registrado (Libre)');
                            
                            if(navigator.vibrate) navigator.vibrate(100);
                        },
                        onCallAnswered: () => { 
                            setCallStatus('in-call'); 
                            setStatus('En Llamada');
                            setElapsed(0);
                            timerRef.current = setInterval(() => setElapsed(e => e + 1), 1000);
                            
                            // Save to history
                            const num = callDirection === 'in' ? su.session?.remoteIdentity?.uri?.user : dest;
                            saveHistory({ num, dir: callDirection, time: new Date().getTime(), acc:'answered' });
                        },
                        onCallHold: (session, hold) => {
                            setIsHeld(hold);
                            setCallStatus(hold ? 'held' : 'in-call');
                        },
                        onRegistered: () => { 
                            setStatus('Registrado (Libre)'); 
                            localStorage.setItem('tf_sip_ext', ext);
                            localStorage.setItem('tf_sip_pass', pass);
                            showToast(`Ext. ${ext} Online`, 'success');
                        },
                        onUnregistered: () => {
                            if(status === 'Registrando...') {
                                setStatus('Error de Credenciales');
                                showToast('Autenticación Fallida (SIP 403)','error');
                            } else {
                                setStatus('Desconectado');
                            }
                        },
                        onServerDisconnect: (e) => { 
                            setStatus('Error de Red');
                            showToast('Protocol Error o WSS Caído','error');
                        }
                    };

                    setStatus('Registrando...');
                    su.connect()
                      .then(() => su.register())
                      .catch(e => {
                          setStatus('Error de Conexión');
                          showToast('No se alcanzó el WSS proxy','error');
                      });

                    setSimpleUser(su);
                } catch(e) {
                    showToast('Error interno SIP: '+e.message, 'error');
                }
            };

            const disconnect = () => {
                if(simpleUser) {
                    simpleUser.unregister().then(()=> simpleUser.disconnect());
                    setSimpleUser(null);
                }
                setStatus('Desconectado');
            };

            // ───────────────── CALL HANDLING ─────────────────
            const startCall = () => {
                if(!simpleUser || !status.includes('Registrado')) return showToast('Debe estar registrado','error');
                if(!dest) return showToast('Ingrese un número','error');

                // Asegurar solo audio
                const opts = { sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } } };
                
                setCallDirection('out');
                setRemoteNumber(dest);
                
                simpleUser.call(`sip:${dest}@${window.location.hostname}`, opts)
                  .then(() => {
                      setCallStatus('calling');
                      saveHistory({ num: dest, dir: 'out', time: new Date().getTime(), acc:'calling' });
                  })
                  .catch(e => showToast('Error al llamar al destino','error'));
            };

            const answerCall = () => {
                if(!simpleUser) return;
                const opts = { sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } } };
                simpleUser.answer(opts).catch(e => showToast('Falló al contestar','error'));
            };

            const hangupCall = () => {
                if(simpleUser) {
                    // depending on state, might need to reject or cancel
                    if(callStatus === 'ringing' && callDirection==='in') simpleUser.decline();
                    else simpleUser.hangup() || showToast('Cuelgue enviado','info');
                }
            };

            const toggleMute = () => {
                if(!simpleUser || !simpleUser.session) return;
                if(isMuted) simpleUser.unmute();
                else simpleUser.mute();
                setIsMuted(!isMuted);
            };

            const toggleHold = () => {
                if(!simpleUser || !simpleUser.session) return;
                if(isHeld) simpleUser.unhold();
                else simpleUser.hold();
                // isHeld is actually updated by onCallHold delegate, but we eagerly flip
                setIsHeld(!isHeld);
            };

            // ───────────────── UTIL ─────────────────
            const saveHistory = (record) => {
                const nh = [record, ...history].slice(0, 50); // Keep last 50
                setHistory(nh);
                localStorage.setItem('tf_call_history', JSON.stringify(nh));
            };

            const formatSmartTime = (ts) => {
                const diff = new Date().getTime() - ts;
                if(diff < 86400000) return new Date(ts).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
                return new Date(ts).toLocaleDateString();
            };

            // ================= RENDER =================

            // 1. PANTALLA DE LOGIN
            if (status === 'Desconectado' || status.includes('Error')) {
                return (
                    <div className="app-container" style={{justifyContent:'center', padding:30}}>
                        <div style={{textAlign:'center', marginBottom:40}}>
                            <div style={{width:80,height:80,borderRadius:'50%',background:'rgba(139,92,246,0.1)',margin:'0 auto 20px',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <span className="material-icons-round" style={{fontSize:40,color:'var(--primary)'}}>phonelink_ring</span>
                            </div>
                            <h1 style={{fontSize:24, fontWeight:900, marginBottom:8}}>Softphone App</h1>
                            <p style={{color:'var(--muted)', fontSize:13}}>Teleflow PWA Client</p>
                        </div>

                        <div style={{background:'var(--surface)', padding:24, borderRadius:24, border:'1px solid var(--border)'}}>
                            <div style={{marginBottom:15}}>
                                <label style={{fontSize:11,fontWeight:700,color:'var(--muted)',textTransform:'uppercase',marginBottom:6,display:'block'}}>Extensión SIP</label>
                                <input type="number" className="input-tf p-3 rounded-xl w-full font-bold text-center text-lg" 
                                    value={ext} onChange={e=>setExt(e.target.value)} placeholder="Ej. 1005" />
                            </div>
                            <div style={{marginBottom:25}}>
                                <label style={{fontSize:11,fontWeight:700,color:'var(--muted)',textTransform:'uppercase',marginBottom:6,display:'block'}}>Contraseña</label>
                                <input type="password" className="input-tf p-3 rounded-xl w-full font-bold text-center" 
                                    value={pass} onChange={e=>setPass(e.target.value)} placeholder="••••••••" />
                            </div>
                            <button onClick={connect} style={{width:'100%',padding:16,background:'var(--primary)',color:'white',fontWeight:800,borderRadius:16,border:'none',boxShadow:'0 8px 16px rgba(139,92,246,0.3)',fontSize:16}}>
                                Conectar
                            </button>
                        </div>
                        {status.includes('Error') && <div style={{color:'var(--danger)',textAlign:'center',marginTop:20,fontWeight:700,fontSize:14}}>{status}</div>}
                    </div>
                );
            }

            // 2. PANTALLA PRINCIPAL
            return (
                <div className="app-container bg-app-gradient relative overflow-hidden">
                    
                    {/* Radial background overlay */}
                    <div className="absolute inset-0 opacity-20 pointer-events-none" 
                         style={{backgroundImage: 'radial-gradient(circle at 50% 0%, #007bff 0%, transparent 70%)'}}></div>                    
                    {toast && (
                        <div className="toast" style={{background: toast.type==='error'?'#ef4444':toast.type==='success'?'#10b981':'var(--primary)'}}>
                            <span className="material-icons-round" style={{fontSize:18}}>{toast.type==='error'?'error':toast.type==='success'?'check_circle':'info'}</span>
                            {toast.msg}
                        </div>
                    )}

                    {/* HEADER STATUS BAR */}
                    <header className="flex items-center justify-between px-6 pt-10 pb-4 z-20">
                        <div className="flex items-center gap-2">
                            <span className="text-[10px] font-semibold tracking-wider opacity-60">TELEFLOW</span>
                            <span className="material-symbols-outlined text-[14px] text-primary">signal_cellular_alt</span>
                        </div>
                        <div className="absolute left-1/2 -translate-x-1/2">
                            <p className="text-sm font-medium">{currentTime.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="material-symbols-outlined text-[16px]">wifi</span>
                            <span className="material-symbols-outlined text-[18px] rotate-90">battery_full</span>
                        </div>
                    </header>

                    {/* VIEWPORT CONTENIDO */}
                    <div className="main-content z-10">
                        
                        {/* ──────────────── TAB: DASHBOARD (IDLE SCREEN) ──────────────── */}
                        <div style={{display: activeTab==='dashboard'?'flex':'none', flexDirection:'column', height:'100%', padding:'0 20px'}}>
                            <section className="flex flex-col items-center gap-4 text-center mt-6">
                                <div className="relative">
                                    <div className="w-32 h-32 rounded-full border-2 border-primary/30 p-1">
                                        <div className="w-full h-full rounded-full bg-slate-700/50 flex items-center justify-center overflow-hidden">
                                            {contacts.find(c => c.ext === ext)?.avatar ? (
                                                <img src={`../${contacts.find(c => c.ext === ext).avatar}`} className="w-full h-full object-cover" />
                                            ) : (
                                                <span className="text-4xl text-white/30 uppercase">{ext.substring(0,2)}</span>
                                            )}
                                        </div>
                                    </div>
                                    <div className={`absolute bottom-1 right-1 w-6 h-6 border-4 border-[#0f1923] rounded-full ${status.includes('Libre')?'bg-green-500':'bg-orange-500'}`}></div>
                                </div>
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight">{contacts.find(c => c.ext === ext)?.name || 'Agente Teleflow'}</h1>
                                    <p className="text-primary font-medium">{status.includes('Libre') ? 'Disponible' : status}</p>
                                    <p className="text-slate-400 text-sm mt-1">Int. {ext} • {domain}</p>
                                </div>
                            </section>

                            <section className="w-full grid grid-cols-2 gap-4 mt-8">
                                <div className="glass-panel rounded-2xl p-5 flex flex-col gap-1 border border-white/5">
                                    <span className="material-symbols-outlined text-primary mb-1">cloud_done</span>
                                    <p className="text-[10px] font-medium text-slate-400 uppercase tracking-widest">Servidor</p>
                                    <p className="text-lg font-semibold">{status.includes('Registrado') ? 'Conectado' : 'Offline'}</p>
                                </div>
                                <div className="glass-panel rounded-2xl p-5 flex flex-col gap-1 border border-white/5">
                                    <span className="material-symbols-outlined text-primary mb-1">history</span>
                                    <p className="text-[10px] font-medium text-slate-400 uppercase tracking-widest">Llamadas Hoy</p>
                                    <p className="text-lg font-semibold">{history.length}</p>
                                </div>
                            </section>

                            <section className="w-full flex flex-col gap-4 mt-8">
                                <h3 className="text-[11px] font-semibold text-slate-500 uppercase tracking-widest px-1">Acciones Rápidas</h3>
                                <div className="flex flex-col gap-3">
                                    <button onClick={()=>setActiveTab('history')} className="w-full flex items-center justify-between glass-panel p-4 rounded-xl border border-white/5 hover:bg-white/10 transition-all active:scale-[0.98]">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                                                <span className="material-symbols-outlined">call_log</span>
                                            </div>
                                            <div className="text-left">
                                                <p className="font-semibold text-sm">Historial</p>
                                                <p className="text-[11px] text-slate-400">Ver llamadas recientes</p>
                                            </div>
                                        </div>
                                        <span className="material-symbols-outlined text-slate-500 text-xl">chevron_right</span>
                                    </button>
                                    <button onClick={disconnect} className="w-full flex items-center justify-between glass-panel p-4 rounded-xl border border-white/5 hover:bg-white/10 transition-all active:scale-[0.98]">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-500">
                                                <span className="material-symbols-outlined">logout</span>
                                            </div>
                                            <div className="text-left">
                                                <p className="font-semibold text-sm">Cerrar Sesión</p>
                                                <p className="text-[11px] text-slate-400">Desvincular extensión</p>
                                            </div>
                                        </div>
                                        <span className="material-symbols-outlined text-slate-500 text-xl">chevron_right</span>
                                    </button>
                                </div>
                            </section>
                        </div>

                        {/* ──────────────── TAB: DIALPAD ──────────────── */}
                        <div style={{display: activeTab==='dialpad'?'flex':'none', flexDirection:'column', height:'100%'}}>
                            <div style={{flex:1, display:'flex', flexDirection:'column', justifyContent:'center', paddingBottom:20}}>
                                {/* Display Number */}
                                <div className="text-center px-5 min-h-[120px] flex items-center justify-center">
                                    <input type="tel" 
                                        className="bg-transparent border-none color-white text-5xl font-light text-center w-full outline-none tracking-widest"
                                        value={dest} onChange={e=>setDest(e.target.value)} placeholder="0" />
                                </div>
                                
                                {/* Keypad grid */}
                                <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:'15px 20px', padding:'0 30px', margin:'auto', maxWidth:350, width:'100%'}}>
                                    {[{n:'1',l:''},{n:'2',l:'ABC'},{n:'3',l:'DEF'},{n:'4',l:'GHI'},{n:'5',l:'JKL'},{n:'6',l:'MNO'},{n:'7',l:'PQRS'},{n:'8',l:'TUV'},{n:'9',l:'WXYZ'},{n:'*',l:''},{n:'0',l:'+'},{n:'#',l:''}].map(k => (
                                        <button key={k.n} className="dial-btn" onClick={()=>{ setDest(d=>d+k.n); if(navigator.vibrate)navigator.vibrate(20); }}>
                                            <span style={{fontSize:28,fontWeight:400,lineHeight:1}}>{k.n}</span>
                                            {k.l && <span style={{fontSize:9,color:'var(--muted)',fontWeight:700,letterSpacing:'1px',marginTop:2}}>{k.l}</span>}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            
                            {/* Action Tools (Call & Backspace) */}
                            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'0 50px 30px'}}>
                                <div style={{width:60}}></div> {/* Spacer to center call btn */}
                                
                                <button className="call-btn" style={{width:75,height:75,background:'var(--accent)'}} onClick={startCall}>
                                    <span className="material-icons-round" style={{fontSize:36}}>call</span>
                                </button>
                                
                                <div style={{width:60, display:'flex', justifyContent:'flex-end'}}>
                                    <button onClick={()=>setDest(d=>d.slice(0,-1))} style={{background:'transparent',border:'none',color:'var(--muted)',padding:10}}>
                                        <span className="material-icons-round" style={{fontSize:28}}>{dest?'backspace':''}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* ──────────────── TAB: CONTACTS ──────────────── */}
                        <div style={{display: activeTab==='contacts'?'block':'none', padding:20, height:'100%'}}>
                            <h2 style={{fontSize:22,fontWeight:800,marginBottom:20,paddingLeft:4}}>Directorio</h2>
                            <div className="flex flex-col gap-3">
                                {contacts.length===0 && <div className="text-slate-500 text-xs text-center p-10">Buscando contactos...</div>}
                                {contacts.map((c,i) => (
                                    <div key={i} onClick={()=>{setDest(c.ext); setActiveTab('dialpad');}} 
                                        className="flex items-center gap-4 p-4 glass-panel rounded-2xl border border-white/5 active:scale-[0.98] transition-all">
                                        <div className="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-lg border border-primary/20">
                                            {c.avatar ? <img src={`../${c.avatar}`} className="w-full h-full object-cover rounded-full" /> : (c.name?.substring(0,1).toUpperCase() || '?')}
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-bold">{c.name}</div>
                                            <div className="text-[11px] text-slate-400 font-medium">Extensión {c.ext}</div>
                                        </div>
                                        <div className={`w-2.5 h-2.5 rounded-full ${c.status==='ONLINE'?'bg-green-500':c.status==='BUSY'?'bg-orange-500':'bg-slate-600'}`}></div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* ──────────────── TAB: HISTORY ──────────────── */}
                        <div style={{display: activeTab==='history'?'block':'none', padding:20, height:'100%'}}>
                            <h2 style={{fontSize:22,fontWeight:800,marginBottom:20,paddingLeft:4}}>Recientes</h2>
                            <div className="flex flex-col">
                                {history.length===0 && <div className="text-slate-500 text-xs text-center p-10">Sin llamadas registradas</div>}
                                {history.map((h,i) => (
                                    <div key={i} onClick={()=>{setDest(h.num); setActiveTab('dialpad');}} 
                                         className="flex items-center gap-4 py-4 px-2 border-b border-white/5 active:bg-white/5 transition-all">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center ${h.dir==='in'?'bg-primary/10 text-primary':'bg-slate-700/30 text-slate-400'}`}>
                                            <span className="material-symbols-outlined text-lg">{h.dir==='in'?'call_received':'call_made'}</span>
                                        </div>
                                        <div className="flex-1">
                                            <div className={`text-base font-semibold ${h.acc==='missed'?'text-red-500':'text-white'}`}>{h.num}</div>
                                            <div className="text-[11px] text-slate-500 font-medium uppercase">{h.acc}</div>
                                        </div>
                                        <div className="text-[10px] text-slate-500 font-bold">{formatSmartTime(h.time)}</div>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>

                    {/* Navbar (iOS STYLE PREMIUM) */}
                    <nav className="absolute bottom-0 left-0 right-0 glass-panel border-t border-white/10 pb-8 pt-3 px-6 z-[100]">
                        <div className="flex items-center justify-between">
                            <button className={`flex flex-col items-center gap-1 ${activeTab==='dashboard'?'text-primary':'text-slate-400'}`} onClick={()=>setActiveTab('dashboard')}>
                                <span className={`material-symbols-outlined ${activeTab==='dashboard'?'filled-icon':''}`}>home</span>
                                <span className="text-[9px] font-bold uppercase tracking-tighter">Inicio</span>
                            </button>
                            <button className={`flex flex-col items-center gap-1 ${activeTab==='history'?'text-primary':'text-slate-400'}`} onClick={()=>setActiveTab('history')}>
                                <span className={`material-symbols-outlined ${activeTab==='history'?'filled-icon':''}`}>history</span>
                                <span className="text-[9px] font-bold uppercase tracking-tighter">Recientes</span>
                            </button>
                            
                            <button className="flex flex-col items-center gap-1 -mt-10" onClick={()=>setActiveTab('dialpad')}>
                                <div className={`w-14 h-14 rounded-full flex items-center justify-center text-white shadow-lg transition-all active:scale-90 ${activeTab==='dialpad'?'bg-primary shadow-primary/40':'bg-slate-700 shadow-black/40'}`}>
                                    <span className="material-symbols-outlined text-3xl">dialpad</span>
                                </div>
                                <span className={`text-[9px] font-bold mt-2 uppercase tracking-tighter ${activeTab==='dialpad'?'text-primary':'text-slate-400'}`}>Teclado</span>
                            </button>
                            
                            <button className={`flex flex-col items-center gap-1 ${activeTab==='contacts'?'text-primary':'text-slate-400'}`} onClick={()=>setActiveTab('contacts')}>
                                <span className={`material-symbols-outlined ${activeTab==='contacts'?'filled-icon':''}`}>person_book</span>
                                <span className="text-[9px] font-bold uppercase tracking-tighter">Contactos</span>
                            </button>
                            <button className="flex flex-col items-center gap-1 text-slate-400" onClick={disconnect}>
                                <span className="material-symbols-outlined">settings</span>
                                <span className="text-[9px] font-bold uppercase tracking-tighter">Ajustes</span>
                            </button>
                        </div>
                    </nav>

                    <div className="absolute bottom-1.5 left-1/2 -translate-x-1/2 w-32 h-1 bg-white/20 rounded-full z-[101]"></div>

                    {/* ──────────────── OVERLAY DE LLAMADA ACTIVA (iOS STYLE PREMIUM) ──────────────── */}
                    {callStatus && (
                        <div className="call-overlay overflow-hidden">
                            {/* Status Bar simulation */}
                            <div className="flex justify-between items-center px-8 pt-4 pb-2 text-white text-xs font-semibold z-20">
                                <span className="status-bar-time">{new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                <div className="flex gap-1.5 items-center">
                                    <span className="material-symbols-outlined text-[14px]">signal_cellular_4_bar</span>
                                    <span className="material-symbols-outlined text-[14px]">wifi</span>
                                    <span className="material-symbols-outlined text-[14px]">battery_full</span>
                                </div>
                            </div>

                            <div className="flex-1 flex flex-col items-center pt-10 px-6 bg-[#0f1923] text-white relative">
                                {/* Blurred background effect */}
                                <div className="absolute inset-0 opacity-20 pointer-events-none overflow-hidden">
                                     <div className="absolute top-[-10%] left-[-10%] w-[120%] h-[120%] bg-gradient-to-br from-primary via-background-dark to-slate-900 blur-[100px]"></div>
                                </div>

                                {/* Call Status and Avatar */}
                                <div className="flex flex-col items-center mb-10 text-center z-10">
                                    <div className="w-28 h-28 rounded-full bg-slate-700/30 flex items-center justify-center mb-6 border-4 border-slate-800/50 shadow-2xl relative overflow-hidden group">
                                        {contacts.find(c => c.ext === remoteNumber)?.avatar ? (
                                            <img src={`../${contacts.find(c => c.ext === remoteNumber).avatar}`} className="w-full h-full object-cover" />
                                        ) : (
                                            <div className="w-full h-full bg-gradient-to-tr from-slate-700 to-slate-600 flex items-center justify-center">
                                                <span className="text-4xl font-light text-white/50">{remoteNumber.substring(0,2)}</span>
                                            </div>
                                        )}
                                        {(callStatus === 'ringing' || callStatus === 'calling') && (
                                            <div className="absolute inset-0 rounded-full border-2 border-white/20 animate-ping opacity-30"></div>
                                        )}
                                    </div>
                                    
                                    <h1 className="text-3xl font-medium tracking-tight mb-1">{contacts.find(c => c.ext === remoteNumber)?.name || remoteNumber}</h1>
                                    <p className="text-slate-400 text-lg font-light tracking-wide uppercase text-[12px] opacity-80">
                                        {callStatus === 'ringing' ? 'llamada entrante...' : 
                                         callStatus === 'calling' ? 'conectando...' : 
                                         callStatus === 'held' ? 'en espera' : formatTime(elapsed)}
                                    </p>
                                </div>

                                {/* Buttons Grid (iOS Style) */}
                                <div className="mt-auto px-10 pb-16 w-full max-w-sm z-10">
                                    <div className="grid grid-cols-3 gap-y-10 gap-x-6 justify-items-center mb-20">
                                        {/* Mute */}
                                        <div className="flex flex-col items-center gap-2">
                                            <button 
                                                onClick={toggleMute}
                                                className={`w-16 h-16 rounded-full flex items-center justify-center transition-all shadow-lg ${isMuted ? 'bg-white text-black' : 'ios-button-bg text-white'}`}
                                            >
                                                <span className={`material-symbols-outlined text-3xl ${isMuted ? 'filled-icon' : ''}`}>mic_off</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">mute</span>
                                        </div>

                                        {/* Keypad */}
                                        <div className="flex flex-col items-center gap-2">
                                            <button className="ios-button-bg w-16 h-16 rounded-full flex items-center justify-center text-white shadow-lg">
                                                <span className="material-symbols-outlined text-3xl">dialpad</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">keypad</span>
                                        </div>

                                        {/* Audio / Speaker */}
                                        <div className="flex flex-col items-center gap-2">
                                            <button className="ios-button-bg w-16 h-16 rounded-full flex items-center justify-center text-white shadow-lg">
                                                <span className="material-symbols-outlined text-3xl">volume_up</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">audio</span>
                                        </div>

                                        {/* Hold / Add Call */}
                                        <div className="flex flex-col items-center gap-2">
                                            <button 
                                                onClick={toggleHold}
                                                className={`w-16 h-16 rounded-full flex items-center justify-center transition-all shadow-lg ${isHeld ? 'bg-white text-black' : 'ios-button-bg text-white'}`}
                                            >
                                                <span className={`material-symbols-outlined text-3xl ${isHeld ? 'filled-icon' : ''}`}>pause</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">{isHeld ? 'unhold' : 'hold'}</span>
                                        </div>

                                        {/* FaceTime (Disabled) */}
                                        <div className="flex flex-col items-center gap-2 opacity-50">
                                            <button className="ios-button-bg w-16 h-16 rounded-full flex items-center justify-center text-white cursor-not-allowed">
                                                <span className="material-symbols-outlined text-3xl">videocam</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">FaceTime</span>
                                        </div>

                                        {/* Contacts */}
                                        <div className="flex flex-col items-center gap-2">
                                            <button onClick={() => { setCallStatus(null); setActiveTab('contacts'); }} className="ios-button-bg w-16 h-16 rounded-full flex items-center justify-center text-white shadow-lg">
                                                <span className="material-symbols-outlined text-3xl">account_circle</span>
                                            </button>
                                            <span className="text-[11px] text-slate-300 font-medium">contacts</span>
                                        </div>
                                    </div>

                                    {/* Action Buttons (Answer/Hangup) */}
                                    <div className="flex justify-center gap-12">
                                        {callStatus === 'ringing' ? (
                                            <>
                                                <button onClick={hangupCall} className="bg-[#ff3b30] w-16 h-16 rounded-full flex items-center justify-center shadow-xl active:scale-95 transition-all">
                                                    <span className="material-symbols-outlined text-white text-3xl transform rotate-[135deg]">call_end</span>
                                                </button>
                                                <button onClick={answerCall} className="bg-[#34c759] w-16 h-16 rounded-full flex items-center justify-center shadow-xl active:scale-95 transition-all">
                                                    <span className="material-symbols-outlined text-white text-3xl">call</span>
                                                </button>
                                            </>
                                        ) : (
                                            <button onClick={hangupCall} className="bg-[#ff3b30] w-16 h-16 rounded-full flex items-center justify-center shadow-xl active:scale-95 transition-all">
                                                <span className="material-symbols-outlined text-white text-3xl transform rotate-[135deg]">call_end</span>
                                            </button>
                                        )}
                                    </div>
                                    
                                    <div className="home-indicator mt-12 bg-white/20"></div>
                                </div>
                            </div>
                        </div>
                    )}

                    <audio ref={audioRef} autoPlay />
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<SoftphoneApp />);
    </script>
</body>
</html>
