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
    
    <!-- PWA Optimized Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Teleflow">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- React DevTools Workaround: Prevents crashes with Babel-standalone -->
    <script>
        (function() {
            const noop = () => {};
            const patch = (h) => {
                if (!h) return;
                ['on', 'off', 'emit', 'sub', 'inject'].forEach(m => {
                    if (typeof h[m] !== 'function') h[m] = noop;
                });
            };
            if (typeof window !== 'undefined') {
                if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
                    patch(window.__REACT_DEVTOOLS_GLOBAL_HOOK__);
                } else {
                    Object.defineProperty(window, '__REACT_DEVTOOLS_GLOBAL_HOOK__', {
                        configurable: true,
                        enumerable: false,
                        get: () => window._rdgh,
                        set: (v) => { patch(v); window._rdgh = v; }
                    });
                }
            }
        })();
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
            background-color: #0f1923;
            color: var(--text);
            margin: 0;
            padding: 0;
            overflow: hidden; 
            overscroll-behavior: none;
            display: flex;
            flex-direction: column;
            width: 100vw;
            height: 100dvh; 
        }

        /* Fluid Layout */
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            background: #0f1923;
            /* Safe areas for notched phones */
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 120px; /* Space for Nav */
            position: relative;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            background: rgba(15, 25, 35, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-bottom: calc(env(safe-area-inset-bottom) + 10px);
            padding-top: 12px;
            z-index: 100;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.3);
        }
        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            border: none;
            background: transparent;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            gap: 4px;
        }
        .nav-item.active { color: #007bff; }
        .nav-item .material-symbols-outlined { font-size: 26px; }

        /* Page Transitions */
        .page-enter {
            animation: pageFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

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
            const [domain, setDomain] = useState(() => 'pbx01.infratec.com.uy'); // Default PBX domain
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
            const [videoActive, setVideoActive] = useState(false);
            const [remoteNumber, setRemoteNumber] = useState('');
            const [callDirection, setCallDirection] = useState(''); // 'in' or 'out'
            const [elapsed, setElapsed] = useState(0);
            const [audioLevel, setAudioLevel] = useState(0); // For visualizer

            // Audio/Video Refs
            const audioContextRef = useRef(null);
            const analyserRef = useRef(null);
            const audioRef = useRef(null);
            const localVideoRef = useRef(null);
            const remoteVideoRef = useRef(null);
            const timerRef = useRef(null);

            // History / Contacts
            const [history, setHistory] = useState(() => JSON.parse(localStorage.getItem('tf_call_history')||'[]'));
            const [contacts, setContacts] = useState([]); // Fetch from API or PBX if available
            const [toast, setToast] = useState(null);

            const showToast = (msg, type='info') => {
                setToast({msg, type});
                setTimeout(()=>setToast(null), 3000);
            };

            const [showCallChoice, setShowCallChoice] = useState(false);

            const handleAvatarUpload = async (e) => {
                const file = e.target.files[0];
                if(!file) return;
                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('ext', ext);
                try {
                    const r = await fetch('../api/index.php?action=upload_avatar', {
                        method: 'POST',
                        body: formData
                    });
                    const d = await r.json();
                    if(d.success) {
                        showToast('Avatar actualizado','success');
                        // Refresh contacts to update UI
                        const r2 = await fetch('../api/index.php?action=get_agents_data');
                        const d2 = await r2.json();
                        if(d2.success) setContacts(d2.agents);
                    } else showToast('Error al subir avatar','error');
                } catch(e) { showToast('Error de conexión','error'); }
            };

            const checkPermissions = async (type) => {
                try {
                    if(type === 'microphone') await navigator.mediaDevices.getUserMedia({audio:true});
                    if(type === 'camera') await navigator.mediaDevices.getUserMedia({video:true});
                    if(type === 'notifications') await Notification.requestPermission();
                    showToast('Permiso verificado correctamente','success');
                } catch(e) { showToast('Permiso denegado','error'); }
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

                // Request Notification Permission
                if ("Notification" in window) {
                    Notification.requestPermission();
                }
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
                        media: { 
                            remote: { audio: audioRef.current, video: remoteVideoRef.current },
                            local: { video: localVideoRef.current }
                        },
                        userAgentOptions: {
                            authorizationUsername: ext.trim(),
                            authorizationPassword: pass.trim(),
                            transportOptions: { server: server, traceSip: true },
                            iceGatheringTimeout: 2000
                        }
                    });

                    su.delegate = {
                        onCallReceived: () => { 
                            const caller = su.session.remoteIdentity.uri.user;
                            showToast('Llamada Entrante: ' + caller,'info');
                            setRemoteNumber(caller); 
                            setCallDirection('in');
                            setCallStatus('ringing'); 
                            setActiveTab('dialpad');
                            
                            // Native Notification
                            if (Notification.permission === "granted") {
                                new Notification("Llamada Entrante", {
                                    body: "Extensión " + caller,
                                    icon: "icon-192.svg"
                                });
                            }

                            // Trigger wake lock vibration if mobile
                            if (navigator.vibrate) navigator.vibrate([500, 300, 500, 300, 500]);
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
                            if(timerRef.current) clearInterval(timerRef.current);
                            timerRef.current = setInterval(() => setElapsed(e => e + 1), 1000);
                            
                            // Save to history
                            const num = callDirection === 'in' ? su.session?.remoteIdentity?.uri?.user : dest;
                            saveHistory({ num, dir: callDirection, time: new Date().getTime(), acc:'answered' });
                            
                            // Essential: Setup tracks after short delay for iPhone
                            setTimeout(() => {
                                setupVideoTracks(su.session);
                                // Start analyzer on remote stream
                                const pc = su.session.sessionDescriptionHandler.peerConnection;
                                const remoteStream = new MediaStream();
                                pc.getReceivers().forEach(r => { if(r.track) remoteStream.addTrack(r.track); });
                                startAudioAnalyzer(remoteStream);
                            }, 1000);
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

            const setupVideoTracks = (session) => {
                if (!session || !session.sessionDescriptionHandler) return;
                const remoteStream = new MediaStream();
                const localStream = new MediaStream();

                session.sessionDescriptionHandler.peerConnection.getReceivers().forEach(receiver => {
                    if (receiver.track) remoteStream.addTrack(receiver.track);
                });
                session.sessionDescriptionHandler.peerConnection.getSenders().forEach(sender => {
                    if (sender.track) localStream.addTrack(sender.track);
                });

                if (remoteVideoRef.current) remoteVideoRef.current.srcObject = remoteStream;
                if (localVideoRef.current) localVideoRef.current.srcObject = localStream;
            };

            const disconnect = () => {
                if(simpleUser) {
                    simpleUser.unregister().then(()=> simpleUser.disconnect());
                    setSimpleUser(null);
                }
                setStatus('Desconectado');
            };

            // ───────────────── CALL HANDLING ─────────────────
            const startCall = (video = false) => {
                if(!simpleUser || !status.includes('Registrado')) return showToast('Debe estar registrado','error');
                if(!dest) return showToast('Ingrese un número','error');

                const opts = { 
                    sessionDescriptionHandlerOptions: { 
                        constraints: { audio: true, video: video } 
                    } 
                };
                
                setShowCallChoice(false);
                setVideoActive(video);
                setCallDirection('out');
                setRemoteNumber(dest);
                
                simpleUser.call(`sip:${dest}@${domain}`, opts)
                  .then(() => {
                      setCallStatus('calling');
                      saveHistory({ num: dest, dir: 'out', time: new Date().getTime(), acc:'calling' });
                  })
                  .catch(e => showToast('Error al llamar al destino','error'));
            };

            const answerCall = (video = false) => {
                if(!simpleUser) return;
                setVideoActive(video);
                const opts = { 
                    sessionDescriptionHandlerOptions: { 
                        constraints: { audio: true, video: video } 
                    } 
                };
                simpleUser.answer(opts).then(() => {
                    setTimeout(() => setupVideoTracks(simpleUser.session), 1000);
                }).catch(e => showToast('Falló al contestar','error'));
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

            const toggleVideo = () => {
                if(!simpleUser || !simpleUser.session) return;
                const active = !videoActive;
                setVideoActive(active);
                
                // For SIP.js v0.20 we might need to renegotiate, but simpler:
                if(simpleUser.session.sessionDescriptionHandler) {
                    const pc = simpleUser.session.sessionDescriptionHandler.peerConnection;
                    pc.getSenders().forEach(sender => {
                        if(sender.track && sender.track.kind === 'video') {
                            sender.track.enabled = active;
                        }
                    });
                }
                showToast(active ? 'Cámara activada' : 'Cámara desactivada');
            };

            const flipCamera = async () => {
                if(!simpleUser || !simpleUser.session) return;
                showToast('Cambiando cámara...');
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoDevices = devices.filter(d => d.kind === 'videoinput');
                    if(videoDevices.length < 2) return showToast('No hay otra cámara');
                    
                    // Logic to find current and switch to next
                    const currentStream = localVideoRef.current.srcObject;
                    const currentTrack = currentStream.getVideoTracks()[0];
                    const nextDevice = videoDevices.find(d => d.deviceId !== (currentTrack ? currentTrack.getSettings().deviceId : ''));
                    
                    const newStream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: { exact: nextDevice.deviceId } }
                    });
                    const newTrack = newStream.getVideoTracks()[0];
                    
                    const pc = simpleUser.session.sessionDescriptionHandler.peerConnection;
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if(sender) sender.replaceTrack(newTrack);
                    
                    localVideoRef.current.srcObject = newStream;
                } catch(e) { showToast('Error al girar cámara','error'); }
            };

            const toggleHold = () => {
                if(!simpleUser || !simpleUser.session) return;
                if(isHeld) simpleUser.unhold();
                else simpleUser.hold();
                setIsHeld(!isHeld);
            };

            // Audio Level Analyzer
            const startAudioAnalyzer = (stream) => {
                if(!stream) return;
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    const ctx = new AudioContext();
                    const source = ctx.createMediaStreamSource(stream);
                    const analyser = ctx.createAnalyser();
                    analyser.fftSize = 256;
                    source.connect(analyser);
                    
                    audioContextRef.current = ctx;
                    analyserRef.current = analyser;
                    
                    const data = new Uint8Array(analyser.frequencyBinCount);
                    const check = () => {
                        if(!analyserRef.current) return;
                        analyser.getByteFrequencyData(data);
                        let sum = 0;
                        for(let i=0; i<data.length; i++) sum += data[i];
                        setAudioLevel(sum / data.length);
                        requestAnimationFrame(check);
                    };
                    check();
                } catch(e) {}
            };

            useEffect(() => {
                if(!callStatus) {
                    if(audioContextRef.current) audioContextRef.current.close().catch(()=>{});
                    analyserRef.current = null;
                    setAudioLevel(0);
                }
            }, [callStatus]);

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

                    {/* VIEWPORT CONTENIDO (Con transiciones suaves) */}
                    <div className="main-content z-10">
                        
                        {/* ──────────────── TAB: DASHBOARD (IDLE SCREEN) ──────────────── */}
                        {activeTab==='dashboard' && (
                          <div className="page-enter flex flex-col h-full px-5">
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
                        )}

                        {/* ──────────────── TAB: DIALPAD ──────────────── */}
                        {activeTab==='dialpad' && (
                          <div className="page-enter flex flex-col h-full">
                            <div className="flex-1 flex flex-col justify-center pb-5">
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
                                
                                <button className="call-btn" style={{width:75,height:75,background:'var(--accent)'}} onClick={() => setShowCallChoice(true)}>
                                    <span className="material-icons-round" style={{fontSize:36}}>call</span>
                                </button>

                                {showCallChoice && (
                                    <div className="absolute inset-0 bg-black/60 backdrop-blur-md z-[150] flex items-end p-6" onClick={() => setShowCallChoice(false)}>
                                        <div className="w-full flex flex-col gap-3 animate-slideUp" onClick={e => e.stopPropagation()}>
                                            <button onClick={() => startCall(false)} className="w-full bg-white/10 hover:bg-white/20 p-5 rounded-2xl flex items-center justify-between border border-white/5">
                                                <div className="flex items-center gap-4">
                                                    <span className="material-symbols-outlined text-green-500 text-3xl">call</span>
                                                    <span className="font-bold">Llamada de Audio</span>
                                                </div>
                                                <span className="material-symbols-outlined text-slate-500">chevron_right</span>
                                            </button>
                                            <button onClick={() => startCall(true)} className="w-full bg-white/10 hover:bg-white/20 p-5 rounded-2xl flex items-center justify-between border border-white/5">
                                                <div className="flex items-center gap-4">
                                                    <span className="material-symbols-outlined text-blue-500 text-3xl">videocam</span>
                                                    <span className="font-bold">Llamada de Video</span>
                                                </div>
                                                <span className="material-symbols-outlined text-slate-500">chevron_right</span>
                                            </button>
                                            <button onClick={() => setShowCallChoice(false)} className="w-full bg-slate-800 p-4 rounded-2xl font-bold mt-2">Cancelar</button>
                                        </div>
                                    </div>
                                )}
                                
                                <div style={{width:60, display:'flex', justifyContent:'flex-end'}}>
                                    <button onClick={()=>setDest(d=>d.slice(0,-1))} style={{background:'transparent',border:'none',color:'var(--muted)',padding:10}}>
                                        <span className="material-icons-round" style={{fontSize:28}}>{dest?'backspace':''}</span>
                                    </button>
                                </div>
                            </div>
                          </div>
                        )}

                        {/* ──────────────── TAB: CONTACTS ──────────────── */}
                        {activeTab==='contacts' && (
                          <div className="page-enter p-5 flex flex-col h-full overflow-hidden">
                            <h2 className="text-2xl font-extrabold mb-5 pl-1">Directorio</h2>
                            <div className="flex flex-col gap-3 flex-1 overflow-y-auto pb-40">
                                {contacts.length===0 && <div className="text-slate-500 text-sm text-center p-20 glass-panel rounded-3xl border border-white/5">Buscando internos...</div>}
                                {contacts.map((c,i) => (
                                    <div key={i} onClick={()=>{setDest(c.ext); setActiveTab('dialpad');}} 
                                        className="flex items-center gap-4 p-4 glass-panel rounded-2xl border border-white/5 active:scale-[0.98] transition-all">
                                        <div className="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-lg border border-primary/20">
                                            {c.avatar ? <img src={`../${c.avatar}`} className="w-full h-full object-cover rounded-full" /> : (c.name?.substring(0,1).toUpperCase() || '?')}
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-bold">{c.name}</div>
                                            <div className="text-[11px] text-slate-400 font-medium tracking-tight">Extensión {c.ext}</div>
                                        </div>
                                        <div className={`w-2.5 h-2.5 rounded-full ${c.status==='ONLINE'?'bg-green-500':c.status==='BUSY'?'bg-orange-500':'bg-slate-600'}`}></div>
                                    </div>
                                ))}
                                <div className="h-10"></div>
                            </div>
                          </div>
                        )}

                        {/* ──────────────── TAB: HISTORY ──────────────── */}
                        {activeTab==='history' && (
                          <div className="page-enter p-5 h-full overflow-y-auto">
                            <h2 className="text-2xl font-extrabold mb-5 pl-1">Recientes</h2>
                            <div className="flex flex-col pb-20">
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
                        )}

                        {/* ──────────────── TAB: SETTINGS ──────────────── */}
                        {activeTab==='settings' && (
                          <div className="page-enter p-5 h-full overflow-y-auto">
                            <h2 className="text-2xl font-extrabold mb-8 pl-1">Ajustes</h2>
                            <div className="flex flex-col gap-6 pb-28">
                                <div className="glass-panel p-6 rounded-3xl border border-white/5 flex flex-col items-center gap-4">
                                    <div className="relative group cursor-pointer" onClick={() => document.getElementById('avatarInput').click()}>
                                        <div className="w-24 h-24 rounded-full bg-slate-800 overflow-hidden border-2 border-primary/40 relative">
                                            {contacts.find(c => c.ext === ext)?.avatar ? (
                                                <img src={`../${contacts.find(c => c.ext === ext).avatar}`} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-3xl text-white/20">{ext.substring(0,2)}</div>
                                            )}
                                            <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <span className="material-symbols-outlined text-white">photo_camera</span>
                                            </div>
                                        </div>
                                        <input type="file" id="avatarInput" className="hidden" accept="image/*" onChange={handleAvatarUpload} />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="text-lg font-bold">{contacts.find(c => c.ext === ext)?.name || 'Usuario'}</h3>
                                        <p className="text-xs text-slate-500 font-medium tracking-wider uppercase mt-1">Extensión {ext}</p>
                                    </div>
                                </div>
                                <div className="flex flex-col gap-2">
                                    <h4 className="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-2 mb-1">Permisos</h4>
                                    <div className="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                                        <button onClick={() => checkPermissions('microphone')} className="w-full flex items-center justify-between p-4 border-b border-white/5 hover:bg-white/5 transition-all text-left">
                                            <div className="flex items-center gap-3">
                                                <span className="material-symbols-outlined text-primary">mic</span>
                                                <span className="text-sm font-medium">Micrófono</span>
                                            </div>
                                            <span className="text-[9px] bg-primary/10 text-primary px-3 py-1 rounded-full font-bold uppercase">Verificar</span>
                                        </button>
                                        <button onClick={() => checkPermissions('camera')} className="w-full flex items-center justify-between p-4 border-b border-white/5 hover:bg-white/5 transition-all text-left">
                                            <div className="flex items-center gap-3">
                                                <span className="material-symbols-outlined text-primary">videocam</span>
                                                <span className="text-sm font-medium">Cámara</span>
                                            </div>
                                            <span className="text-[9px] bg-primary/10 text-primary px-3 py-1 rounded-full font-bold uppercase">Verificar</span>
                                        </button>
                                        <button onClick={() => checkPermissions('notifications')} className="w-full flex items-center justify-between p-4 hover:bg-white/5 transition-all text-left">
                                            <div className="flex items-center gap-3">
                                                <span className="material-symbols-outlined text-primary">notifications</span>
                                                <span className="text-sm font-medium">Notificaciones</span>
                                            </div>
                                            <span className="text-[9px] bg-primary/10 text-primary px-3 py-1 rounded-full font-bold uppercase">Verificar</span>
                                        </button>
                                    </div>
                                </div>
                                <button onClick={disconnect} className="w-full flex items-center gap-3 p-4 bg-red-500/10 text-red-500 rounded-2xl border border-red-500/10 hover:bg-red-500/20 transition-all font-bold text-sm">
                                    <span className="material-symbols-outlined">logout</span>
                                    Cerrar Sesión
                                </button>
                            </div>
                          </div>
                        )}
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
                            <button className={`flex flex-col items-center gap-1 ${activeTab==='settings'?'text-primary':'text-slate-400'}`} onClick={()=>setActiveTab('settings')}>
                                <span className={`material-symbols-outlined ${activeTab==='settings'?'filled-icon':''}`}>settings</span>
                                <span className="text-[9px] font-bold uppercase tracking-tighter">Ajustes</span>
                            </button>
                        </div>
                    </nav>

                    <div className="absolute bottom-1.5 left-1/2 -translate-x-1/2 w-32 h-1 bg-white/20 rounded-full z-[101]"></div>

                    {/* ──────────────── OVERLAY DE LLAMADA ACTIVA (iOS STYLE PREMIUM) ──────────────── */}
                    {callStatus && (
                        <div className="call-overlay fixed inset-0 z-[200] bg-slate-900 overflow-hidden flex flex-col">
                            {/* Overlay Background Wrapper (Handles content but not the video tags because they are outside) */}
                            <div className="absolute inset-0 z-0 overflow-hidden">
                                {(!videoActive || callStatus !== 'in-call') && (
                                    <div className="w-full h-full bg-slate-900 flex flex-col items-center justify-center transition-opacity">
                                        <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
                                        <div className="w-32 h-32 rounded-full glass-panel flex items-center justify-center border-2 border-white/5 shadow-2xl overflow-hidden backdrop-blur-3xl animate-fadeIn">
                                            {contacts.find(c => c.ext === remoteNumber)?.avatar ? (
                                                <img src={`../${contacts.find(c => c.ext === remoteNumber).avatar}`} className="w-full h-full object-cover opacity-50" />
                                            ) : (
                                                <div className="w-full h-full bg-slate-800 flex items-center justify-center">
                                                    <span className="text-4xl text-white/30">{remoteNumber.substring(0,2)}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/70"></div>
                            </div>

                            {/* Top Header Area */}
                            <div className="relative z-10 w-full pt-14 px-6 flex flex-col items-center animate-fadeIn">
                                <h1 className="text-white text-2xl font-semibold tracking-tight text-center">{contacts.find(c => c.ext === remoteNumber)?.name || remoteNumber}</h1>
                                <div className="mt-3 bg-black/30 backdrop-blur-2xl px-5 py-2 rounded-full border border-white/10 shadow-lg flex items-center gap-4">
                                    <p className="text-white text-[12px] font-bold tabular-nums uppercase tracking-widest min-w-[80px] text-center">
                                        {callStatus === 'ringing' ? 'llamada...' : callStatus === 'calling' ? 'conectando' : callStatus === 'held' ? 'en espera' : formatTime(elapsed)}
                                    </p>
                                    {callStatus === 'in-call' && (
                                        <div className="flex items-center gap-1 h-3 ml-1 border-l border-white/10 pl-3">
                                            {[1,2,3,4,5,6].map(i => {
                                                const h = Math.max(3, (audioLevel * (0.8 + Math.random()*0.5)));
                                                return (
                                                    <div key={i} className="w-1 bg-primary rounded-full transition-all duration-75" 
                                                         style={{height: `${h}%`, opacity: 0.6 + (h/100)}}></div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Bottom Controls Area */}
                            <div className="relative z-10 w-full px-6 pb-12 mt-auto animate-slideUp">
                                {/* Answer Controls (Only when ringing) */}
                                {callStatus === 'ringing' && (
                                    <div className="flex justify-center gap-12 mb-10 translate-y-[-20px]">
                                        <button onClick={() => answerCall(true)} className="flex items-center justify-center size-16 rounded-full bg-blue-500 shadow-xl shadow-blue-500/30 text-white active:scale-90 transition-transform">
                                            <span className="material-symbols-outlined text-3xl">videocam</span>
                                        </button>
                                        <button onClick={() => answerCall(false)} className="flex items-center justify-center size-16 rounded-full bg-green-500 shadow-xl shadow-green-500/30 text-white active:scale-90 transition-transform">
                                            <span className="material-symbols-outlined text-3xl">call</span>
                                        </button>
                                    </div>
                                )}

                                {/* Main Glass Bar */}
                                <div className="max-w-md mx-auto bg-slate-800/40 backdrop-blur-3xl rounded-[2.5rem] p-4 flex items-center justify-between border border-white/10 shadow-2xl">
                                    {/* Mute Toggle */}
                                    <button onClick={toggleMute} className={`flex items-center justify-center size-14 rounded-full transition-all ${isMuted?'bg-white text-slate-900':'bg-white/10 text-white'}`}>
                                        <span className="material-symbols-outlined text-[28px]">{isMuted?'mic_off':'mic'}</span>
                                    </button>
                                    
                                    {/* Flip Camera */}
                                    <button onClick={flipCamera} className="flex items-center justify-center size-14 rounded-full bg-white/10 hover:bg-white/20 text-white transition-all">
                                        <span className="material-symbols-outlined text-[28px]">cameraswitch</span>
                                    </button>

                                    {/* Video Toggle */}
                                    <button onClick={toggleVideo} className={`flex items-center justify-center size-14 rounded-full transition-all ${!videoActive?'bg-white text-slate-900':'bg-white/10 text-white'}`}>
                                        <span className="material-symbols-outlined text-[28px]">{videoActive?'videocam':'videocam_off'}</span>
                                    </button>
                                    
                                    {/* End Call Button */}
                                    <button onClick={hangupCall} className="flex items-center justify-center size-14 rounded-full bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/20 active:scale-95 transition-all outline outline-offset-4 outline-red-500/30">
                                        <span className="material-symbols-outlined text-[28px] rotate-[135deg]">call_end</span>
                                    </button>
                                </div>

                                {/* Additional Actions */}
                                <div className="flex justify-center mt-6 gap-10">
                                    <button onClick={toggleHold} className="flex flex-col items-center gap-1.5 group">
                                        <div className={`size-10 flex items-center justify-center rounded-full transition-all ${isHeld?'bg-white text-slate-900':'bg-white/5 text-white'}`}>
                                            <span className="material-symbols-outlined text-xl">{isHeld?'play_arrow':'pause'}</span>
                                        </div>
                                        <span className="text-[10px] text-slate-300 font-bold uppercase tracking-widest">{isHeld?'Retomar':'Pausar'}</span>
                                    </button>
                                    <button onClick={() => showToast('Funcionalidad próximamente')} className="flex flex-col items-center gap-1.5 opacity-80 group">
                                        <div className="size-10 flex items-center justify-center rounded-full bg-white/5 text-slate-100">
                                            <span className="material-symbols-outlined text-xl">person_add</span>
                                        </div>
                                        <span className="text-[10px] text-slate-300 font-bold uppercase tracking-widest">Invitar</span>
                                    </button>
                                </div>
                            </div>
                            {/* Home indicator inside overlay */}
                            <div className="fixed bottom-2 left-1/2 -translate-x-1/2 w-32 h-1.5 bg-white/30 rounded-full z-[210]"></div>
                        </div>
                    )}

                    {/* Persistent Video Elements (Hidden when not in call) */}
                    <div className={`fixed inset-0 pointer-events-none z-0 ${callStatus==='in-call'?'block':'hidden'}`}>
                         <video ref={remoteVideoRef} autoPlay playsInline className="w-full h-full object-cover" />
                    </div>
                    <div className={`fixed top-14 right-4 z-[210] w-32 aspect-[3/4] rounded-2xl overflow-hidden border-2 border-white/20 shadow-2xl pointer-events-none ${(callStatus==='in-call' || callStatus==='calling') && videoActive ? 'block' : 'hidden'}`}>
                         <video ref={localVideoRef} autoPlay playsInline muted className="w-full h-full object-cover scale-x-[-1]" />
                    </div>

                    <audio ref={audioRef} autoPlay />
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<SoftphoneApp />);
    </script>
</body>
</html>
