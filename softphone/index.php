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
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
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
            const [activeTab, setActiveTab] = useState('dialpad'); 
            
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
                <div className="app-container">
                    
                    {toast && (
                        <div className="toast" style={{background: toast.type==='error'?'#ef4444':toast.type==='success'?'#10b981':'var(--primary)'}}>
                            <span className="material-icons-round" style={{fontSize:18}}>{toast.type==='error'?'error':toast.type==='success'?'check_circle':'info'}</span>
                            {toast.msg}
                        </div>
                    )}

                    {/* HEADER STATUS */}
                    <div className="header">
                        <div style={{display:'flex', alignItems:'center', gap:10}}>
                            <div style={{width:10,height:10,borderRadius:'50%',background:status.includes('Libre')?'var(--accent)':'#f59e0b',animation:!status.includes('Libre')?'blink 1s infinite':'none'}} />
                            <div>
                                <div style={{fontSize:14,fontWeight:800}}>Extensión {ext}</div>
                                <div style={{fontSize:10,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'0.05em'}}>{status}</div>
                            </div>
                        </div>
                        <button onClick={disconnect} style={{background:'rgba(239,68,68,0.1)',color:'var(--danger)',border:'none',width:36,height:36,borderRadius:'50%',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer'}}>
                            <span className="material-icons-round" style={{fontSize:18}}>power_settings_new</span>
                        </button>
                    </div>

                    {/* VIEWPORT CONTENIDO */}
                    <div className="main-content">
                        
                        {/* ──────────────── TAB: DIALPAD ──────────────── */}
                        <div style={{display: activeTab==='dialpad'?'flex':'none', flexDirection:'column', height:'100%'}}>
                            <div style={{flex:1, display:'flex', flexDirection:'column', justifyContent:'center', paddingBottom:20}}>
                                {/* Display Number */}
                                <div style={{textAlign:'center', padding:'20px', minHeight:100, display:'flex', alignItems:'center', justifyContent:'center'}}>
                                    <input type="tel" 
                                        style={{background:'transparent',border:'none',color:'white',fontSize:42,fontWeight:300,textAlign:'center',width:'100%',outline:'none',letterSpacing:'2px'}} 
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
                            <h2 style={{fontSize:20,fontWeight:800,marginBottom:15}}>Directorio</h2>
                            <div style={{display:'flex', flexDirection:'column', gap:10}}>
                                {contacts.length===0 && <div style={{color:'var(--muted)',fontSize:12,textAlign:'center',padding:20}}>Buscando contactos...</div>}
                                {contacts.map((c,i) => (
                                    <div key={i} onClick={()=>{setDest(c.ext); setActiveTab('dialpad');}} style={{display:'flex',alignItems:'center',gap:12,padding:'12px 14px',background:'var(--surface2)',borderRadius:16,border:'1px solid var(--border)'}}>
                                        <div style={{width:40,height:40,borderRadius:'50%',background:'rgba(139,92,246,0.1)',color:'var(--primary)',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:800}}>
                                            {c.name?.substring(0,2).toUpperCase() || c.ext}
                                        </div>
                                        <div style={{flex:1}}>
                                            <div style={{fontSize:14,fontWeight:700}}>{c.name}</div>
                                            <div style={{fontSize:11,color:'var(--muted)',fontWeight:600}}>Ext: {c.ext}</div>
                                        </div>
                                        <div style={{width:10,height:10,borderRadius:'50%',background:c.status==='ONLINE'?'var(--accent)':c.status==='BUSY'?'#f59e0b':'var(--muted)'}}></div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* ──────────────── TAB: HISTORY ──────────────── */}
                        <div style={{display: activeTab==='history'?'block':'none', padding:20, height:'100%'}}>
                            <h2 style={{fontSize:20,fontWeight:800,marginBottom:15}}>Recientes</h2>
                            <div style={{display:'flex', flexDirection:'column', gap:0}}>
                                {history.length===0 && <div style={{color:'var(--muted)',fontSize:12,textAlign:'center',padding:20}}>Sin llamadas registradas en este equipo</div>}
                                {history.map((h,i) => (
                                    <div key={i} onClick={()=>{setDest(h.num); setActiveTab('dialpad');}} style={{display:'flex',alignItems:'center',gap:14,padding:'14px 10px',borderBottom:'1px solid var(--border)'}}>
                                        <span className="material-icons-round" style={{color:h.dir==='in'?'var(--primary)':'var(--muted)',fontSize:20}}>{h.dir==='in'?'call_received':'call_made'}</span>
                                        <div style={{flex:1}}>
                                            <div style={{fontSize:15,fontWeight:700,color:h.acc==='missed'?'var(--danger)':'white'}}>{h.num}</div>
                                            <div style={{fontSize:11,color:'var(--muted)',fontWeight:500}}>{h.acc}</div>
                                        </div>
                                        <div style={{fontSize:11,color:'var(--muted)',fontWeight:600}}>{formatSmartTime(h.time)}</div>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>

                    {/* Navbar */}
                    <div className="bottom-nav">
                        <button className={`nav-item ${activeTab==='contacts'?'active':''}`} onClick={()=>setActiveTab('contacts')}>
                            <span className="material-icons-round">contacts</span>Directorio
                        </button>
                        <button className={`nav-item ${activeTab==='dialpad'?'active':''}`} onClick={()=>setActiveTab('dialpad')}>
                            <span className="material-icons-round">dialpad</span>Teclado
                        </button>
                        <button className={`nav-item ${activeTab==='history'?'active':''}`} onClick={()=>setActiveTab('history')}>
                            <span className="material-icons-round">schedule</span>Recientes
                        </button>
                    </div>

                    {/* ──────────────── OVERLAY DE LLAMADA ACTIVA ──────────────── */}
                    {callStatus && (
                        <div className="call-overlay">
                            <div style={{flex:1, display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center', background:'linear-gradient(180deg, rgba(30,30,45,1) 0%, rgba(10,10,15,1) 100%)'}}>
                                
                                <div style={{fontSize:16,color:'var(--muted)',fontWeight:600,marginBottom:15,letterSpacing:'1px',textTransform:'uppercase'}}>
                                    {callStatus === 'ringing' && callDirection==='in' && 'Llamada Entrante'}
                                    {callStatus === 'calling' && 'Llamando...'}
                                    {callStatus === 'in-call' && 'Llamada Activa'}
                                    {callStatus === 'held' && 'En Espera'}
                                </div>
                                
                                <h2 style={{fontSize:48,fontWeight:300,marginBottom:30}}>{remoteNumber}</h2>
                                
                                {/* DURACIÓN O ICONO ANIMADO */}
                                <div style={{height:60, display:'flex',alignItems:'center',justifyContent:'center',marginBottom:40}}>
                                    {(callStatus==='in-call' || callStatus==='held') ? (
                                        <div style={{fontSize:36,fontWeight:200,fontFamily:'monospace',color:callStatus==='held'?'var(--muted)':'var(--accent)'}}>
                                            {formatTime(elapsed)}
                                        </div>
                                    ) : (
                                        <div style={{position:'relative',width:100,height:100,display:'flex',alignItems:'center',justifyContent:'center'}}>
                                            <div style={{position:'absolute',width:'100%',height:'100%',borderRadius:'50%',border:'2px solid var(--primary)',animation:'pulse 1.5s infinite'}}></div>
                                            <div style={{width:60,height:60,borderRadius:'50%',background:'var(--primary)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                                <span className="material-icons-round" style={{fontSize:32}}>person</span>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* CONTROLES DE LA LLAMADA */}
                                {(callStatus==='in-call' || callStatus==='held') && (
                                    <div style={{display:'flex',gap:24,marginBottom:60}}>
                                        <button onClick={toggleMute} style={{display:'flex',flexDirection:'column',alignItems:'center',gap:8,background:'transparent',border:'none',color:'white',cursor:'pointer'}}>
                                            <div style={{width:60,height:60,borderRadius:'50%',background:isMuted?'white':'var(--surface2)',color:isMuted?'black':'white',display:'flex',alignItems:'center',justifyContent:'center',transition:'all .2s'}}>
                                                <span className="material-icons-round">{isMuted?'mic_off':'mic'}</span>
                                            </div>
                                            <span style={{fontSize:11,fontWeight:600}}>Silenciar</span>
                                        </button>
                                        
                                        <button onClick={toggleHold} style={{display:'flex',flexDirection:'column',alignItems:'center',gap:8,background:'transparent',border:'none',color:'white',cursor:'pointer'}}>
                                            <div style={{width:60,height:60,borderRadius:'50%',background:isHeld?'white':'var(--surface2)',color:isHeld?'black':'white',display:'flex',alignItems:'center',justifyContent:'center',transition:'all .2s'}}>
                                                <span className="material-icons-round">{isHeld?'play_arrow':'pause'}</span>
                                            </div>
                                            <span style={{fontSize:11,fontWeight:600}}>Espera</span>
                                        </button>
                                    </div>
                                )}

                                {/* BOTONES INFERIORES: CONTESTAR/COLGAR */}
                                <div style={{display:'flex',gap:30,alignItems:'center'}}>
                                    {(callStatus === 'ringing' && callDirection === 'in') && (
                                        <button onClick={answerCall} style={{width:75,height:75,borderRadius:'50%',background:'var(--accent)',color:'white',border:'none',boxShadow:'0 10px 30px rgba(16,185,129,0.3)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                            <span className="material-icons-round" style={{fontSize:36}}>call</span>
                                        </button>
                                    )}
                                    <button onClick={hangupCall} style={{width:(callStatus==='ringing'&&callDirection==='in')?75:80,height:(callStatus==='ringing'&&callDirection==='in')?75:80,borderRadius:'50%',background:'var(--danger)',color:'white',border:'none',boxShadow:'0 10px 30px rgba(239,68,68,0.3)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                        <span className="material-icons-round" style={{fontSize:36}}>call_end</span>
                                    </button>
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
