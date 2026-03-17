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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    
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
            --p-glow: rgba(139, 92, 246, 0.4);
        }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body {
            font-family: 'Outfit', 'Inter', sans-serif;
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
            /* Extra padding to ensure content is not hidden by fixed navbar */
            padding-bottom: 140px; 
            position: relative;
            z-index: 10;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            background: rgba(15, 25, 35, 0.9);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-bottom: calc(env(safe-area-inset-bottom) + 15px);
            padding-top: 12px;
            z-index: 500; /* Higher than items */
            box-shadow: 0 -10px 40px rgba(0,0,0,0.5);
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
            transition: font-variation-settings 0.2s;
        }
        .filled-icon { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        /* ACTIVE STATES & ANIMATIONS */
        .active-scale:active { transform: scale(0.92); }
        
        .dial-btn:active { 
            background: var(--primary) !important; 
            color: white !important;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
            transform: scale(0.9);
        }

        .btn-toggle-active {
            background: white !important;
            color: #1a2a3a !important;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .pulse-green { animation: pulse-green 2s infinite; }

        .call-btn {
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .call-btn:active { transform: scale(0.85); box-shadow: 0 5px 10px rgba(0,0,0,0.4); }

        .call-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #0f1923;
            z-index: 500;
            display: flex;
            flex-direction: column;
            animation: slideUpIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        @keyframes slideUpIn { from { transform: translateY(100%); } to { transform: translateY(0); } }
        @keyframes pageFadeIn { from { opacity: 0; transform: scale(1.05); } to { opacity: 1; transform: scale(1); } }

        .toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            padding: 12px 24px; border-radius: 50px; color: white;
            font-weight: 600; font-size: 13px; z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex; alignItems: center; gap: 8px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from{top:-50px;opacity:0} to{top:20px;opacity:1} }
        
        .glass-panel {
            background: rgba(23, 38, 54, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .animate-blob { animation: blob 7s infinite; }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }

        .animation-delay-2000 { animation-delay: 2s; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .animate-fadeIn { animation: fadeIn 0.8s ease-out forwards; }
        
        @keyframes slideUpFade { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slideUp { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.4s ease-in-out; }

        .bg-app-gradient {
            background: linear-gradient(180deg, #0f1923 0%, #1a2a3a 50%, #0f1923 100%);
        }

        /* Animations for call process */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
        
        .pulse-ring {
            position: absolute;
            width: 100%; height: 100%;
            border-radius: 50%;
            background: var(--primary);
            opacity: 0.2;
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        /* Wave Animation for Call */
        .wave-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 40px;
        }
        .wave-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            transition: transform 0.08s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.1s ease;
            box-shadow: 0 0 15px var(--p-glow);
        }
        .call-timer-big {
            font-size: 64px;
            font-weight: 200;
            font-family: 'Outfit', sans-serif;
            color: white;
            letter-spacing: -2px;
            line-height: 1;
            text-shadow: 0 0 20px rgba(255,255,255,0.1);
        }
        .proximity-overlay {
            position: fixed;
            inset: 0;
            background: black;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: all;
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
            const [domain, setDomain] = useState(() => '201.217.134.124'); // IP del Asterisk para evitar fallos de realm/DNS
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
            const [isSpeaker, setIsSpeaker] = useState(false);
            const [showUpdateModal, setShowUpdateModal] = useState(false);
            const [showInCallKeypad, setShowInCallKeypad] = useState(false);
            const [isNear, setIsNear] = useState(false);
            const [updateStatus, setUpdateStatus] = useState('idle'); // 'idle', 'checking', 'up-to-date', 'available'
            const appVersion = "1.4.0"; 
            const appVersionNumeric = 140;

            // Audio/Video Refs
            const audioContextRef = useRef(null);
            const analyserRef = useRef(null);
            const audioRef = useRef(null);
            const localVideoRef = useRef(null);
            const remoteVideoRef = useRef(null);
            const vibrateInterval = useRef(null);
            const toneCtxRef = useRef(null);
            const ringIntervalRef = useRef(null);
            const timerRef = useRef(null);
            // Audio context unlock and Sound Warm-up
            useEffect(() => {
                const unlock = () => {
                    if (toneCtxRef.current) return;
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    toneCtxRef.current = ctx;
                    // Play a silent buffer to unlock
                    const buffer = ctx.createBuffer(1, 1, 22050);
                    const source = ctx.createBufferSource();
                    source.buffer = buffer;
                    source.connect(ctx.destination);
                    source.start(0);

                    window.removeEventListener('click', unlock);
                    window.removeEventListener('touchstart', unlock);
                };
                window.addEventListener('click', unlock);
                window.addEventListener('touchstart', unlock);
            }, []);

            // Registro de Service Worker para Notificaciones Push (Solo en Softphone)
            useEffect(() => {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('../sw.js')
                        .then(reg => console.log('SW Registered in Softphone scope:', reg.scope))
                        .catch(err => console.error('SW Registration failed:', err));
                }
            }, []);

            // ───────────────── SYNTHESIZED RINGTONES ─────────────────
            const playSynthesizedRing = (type) => {
                const ctx = toneCtxRef.current;
                if (!ctx) return;
                if (ctx.state === 'suspended') ctx.resume();

                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);

                if (type === 'ringback') {
                    // Europe/US standard ringback: 440+480Hz
                    osc1.frequency.value = 440;
                    osc2.frequency.value = 480;
                    gain.gain.value = 0.2;
                    osc1.start(); osc2.start();
                    osc1.stop(ctx.currentTime + 1.5);
                    osc2.stop(ctx.currentTime + 1.5);
                } else if (type === 'ringing') {
                    // Typical phone ring pattern: 400+450Hz
                    osc1.frequency.value = 400;
                    osc2.frequency.value = 450;
                    
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.setValueAtTime(0, ctx.currentTime + 0.4);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime + 0.6);
                    gain.gain.setValueAtTime(0, ctx.currentTime + 1.0);
                    
                    osc1.start(); osc2.start();
                    osc1.stop(ctx.currentTime + 1.0);
                    osc2.stop(ctx.currentTime + 1.0);
                } else if (type === 'click') {
                    osc1.frequency.value = 800;
                    gain.gain.setValueAtTime(0.1, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
                    osc1.start(); osc1.stop(ctx.currentTime + 0.05);
                }
            };

            const startSynthesizedRing = (type) => {
                stopSynthesizedRing();
                playSynthesizedRing(type);
                ringIntervalRef.current = setInterval(() => {
                    playSynthesizedRing(type);
                }, type === 'ringback' ? 4000 : 3000);
            };

            const stopSynthesizedRing = () => {
                if (ringIntervalRef.current) clearInterval(ringIntervalRef.current);
            };
            
            // ───────────────── PROXIMITY SENSOR (EAR DETECTION) ─────────────────
            useEffect(() => {
                if (!('ProximitySensor' in window)) return;
                try {
                    const sensor = new ProximitySensor();
                    sensor.addEventListener('reading', () => {
                        console.log('Proximity:', sensor.near);
                        setIsNear(sensor.near);
                    });
                    sensor.start();
                } catch(e) { console.warn("ProximitySensor API not supported or blocked"); }
            }, []);

            const playClick = () => playSynthesizedRing('click');

            const playTone = (freq1, freq2 = 0) => {
                if (!toneCtxRef.current) return;
                const ctx = toneCtxRef.current;
                if (ctx.state === 'suspended') ctx.resume();
                
                const osc1 = ctx.createOscillator();
                const gain = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(freq1, ctx.currentTime);
                osc1.connect(gain);
                
                let osc2;
                if(freq2) {
                    osc2 = ctx.createOscillator();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(freq2, ctx.currentTime);
                    osc2.connect(gain);
                }

                // Volume and Envelope
                gain.connect(ctx.destination);
                gain.gain.setValueAtTime(0.3, ctx.currentTime); // A bit louder
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);

                osc1.start();
                if(osc2) osc2.start();
                osc1.stop(ctx.currentTime + 0.2);
                if(osc2) osc2.stop(ctx.currentTime + 0.2);
            };

            // PERSISTENCE & HEARTBEAT
            useEffect(() => {
                const handleVisibility = () => {
                    if (document.visibilityState === 'visible') {
                        console.log('PWA Resumed: Checking SIP status:', status);
                        // Comprobar estado real de la conexión al volver del segundo plano
                        if (simpleUser && !simpleUser.isConnected() && localStorage.getItem('tf_sip_ext')) {
                            console.log('Reconectando transporte SIP...');
                            simpleUser.connect().then(() => simpleUser.register());
                        } else if (status === 'Desconectado' && localStorage.getItem('tf_sip_ext')) {
                            setTimeout(connect, 500);
                        }
                    }
                };
                document.addEventListener('visibilitychange', handleVisibility);
                
                // Keep-Alive Híbrido:
                // El transporte (WSS) ya hace ping/pong cada 15s.
                // Aquí hacemos un re-registro suave cada 120s para mantener la sesión en Asterisk viva.
                const hb = setInterval(() => {
                    if (status.includes('Registrado') && simpleUser && simpleUser.isConnected()) {
                        console.log('SIP Active: Soft re-registration');
                        simpleUser.register();
                    }
                }, 120000);

                return () => {
                    document.removeEventListener('visibilitychange', handleVisibility);
                    clearInterval(hb);
                };
            }, [status, simpleUser]);

            const playDTMF = (digit) => {
                const dtmfFreqs = {
                    '1': [697, 1209], '2': [697, 1336], '3': [697, 1477],
                    '4': [770, 1209], '5': [770, 1336], '6': [770, 1477],
                    '7': [852, 1209], '8': [852, 1336], '9': [852, 1477],
                    '*': [941, 1209], '0': [941, 1336], '#': [941, 1477]
                };
                if(dtmfFreqs[digit]) playTone(dtmfFreqs[digit][0], dtmfFreqs[digit][1]);
                else playTone(440); // Simple beep for others

                // Send DTMF via SIP INFO
                if (simpleUser && simpleUser.session) {
                    simpleUser.session.sendDTMF(digit);
                }
            };

            const openDoor = () => {
                haptic('heavy');
                showToast('Abriendo puerta...', 'success');
                playDTMF('*');
                setTimeout(() => playDTMF('6'), 300); // Common combo for some intercoms
            };

            // History / Contacts
            const [history, setHistory] = useState([]);
            useEffect(() => {
                if (ext) {
                    setHistory(JSON.parse(localStorage.getItem('tf_call_history_' + ext) || '[]'));
                }
            }, [ext]);
            const [contacts, setContacts] = useState([]); // Fetch from API or PBX if available
            const [toast, setToast] = useState(null);

            const showToast = (msg, type='info') => {
                setToast({msg, type});
                haptic('light');
                setTimeout(()=>setToast(null), 3000);
            };

            const haptic = (type = 'success') => {
                if (!navigator.vibrate) return;
                switch(type) {
                    case 'light': navigator.vibrate(10); break;
                    case 'medium': navigator.vibrate(50); break;
                    case 'heavy': navigator.vibrate(100); break;
                    case 'success': navigator.vibrate([10, 30, 10]); break;
                    case 'error': navigator.vibrate([50, 100, 50, 100]); break;
                }
            };

            const checkUpdate = async () => {
                setUpdateStatus('checking');
                haptic('light');
                
                // Simulación de verificación (En prod esto consultaría un endpoint o el sw)
                setTimeout(() => {
                    // Si el usuario reporta que tiene la 1.2.0, forzamos que vea que hay update
                    setUpdateStatus('available');
                }, 1500);
            };

            const applyUpdate = () => {
                setUpdateStatus('downloading');
                haptic('medium');
                
                // Efecto visual de progreso
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    if (progress >= 100) {
                        clearInterval(interval);
                        showToast('Instalando...', 'success');
                        
                        if ('serviceWorker' in navigator) {
                            navigator.serviceWorker.getRegistrations().then(regs => {
                                regs.forEach(r => r.update());
                                setTimeout(() => {
                                    if ('caches' in window) {
                                        caches.keys().then(names => {
                                            for (let name of names) caches.delete(name);
                                        });
                                    }
                                    window.location.reload(true);
                                }, 1000);
                            });
                        } else {
                            window.location.reload(true);
                        }
                    }
                }, 100);
            };

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

                // Autoconnect on load if credentials exist
                if (localStorage.getItem('tf_sip_ext') && localStorage.getItem('tf_sip_pass')) {
                    setTimeout(() => {
                        const btn = document.getElementById('btn-connect-hidden');
                        if (btn) btn.click();
                    }, 600);
                }
            }, []);

            // ───────────────── WEB PUSH (BACKGROUND ACTIONS) ─────────────────
            useEffect(() => {
                if (!('serviceWorker' in navigator)) return;
                const handleSWMessage = (event) => {
                    if (event.data && event.data.type === 'CALL_ACTION') {
                        console.log('Web Push Action Reibida:', event.data.action);
                        
                        // Pequeño delay para asegurar que iOS despertó el DOM e hidrató el hardware de audio
                        setTimeout(() => {
                            if (event.data.action === 'answer') {
                                if (simpleUser) {
                                    setVideoActive(false);
                                    const opts = { sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } } };
                                    simpleUser.answer(opts).then(() => setTimeout(() => setupVideoTracks(simpleUser.session), 1000)).catch(e => console.error("SW answer error:", e));
                                }
                            } else if (event.data.action === 'reject') {
                                if (simpleUser) {
                                    if (simpleUser.session) simpleUser.decline();
                                    else simpleUser.hangup() || showToast('Cuelgue enviado','info');
                                }
                            }
                        }, 300);
                    }
                };
                navigator.serviceWorker.addEventListener('message', handleSWMessage);
                return () => navigator.serviceWorker.removeEventListener('message', handleSWMessage);
            }, [simpleUser]);

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
                            transportOptions: { server: server, traceSip: false, keepAliveInterval: 15 }, // WSS Ping/Pong cada 15s para evitar cortes de NAT
                            sessionDescriptionHandlerFactoryOptions: {
                                peerConnectionConfiguration: {
                                    iceServers: [
                                        { urls: "stun:stun.l.google.com:19302" },
                                        { urls: "stun:stun1.l.google.com:19302" },
                                        { urls: "stun:stun.sipgate.net:3478" }
                                    ]
                                },
                                iceGatheringTimeout: 3000, // Aumentado para mayor estabilidad en redes móviles
                                modifiers: [
                                    (sessionDescription) => {
                                        // SDP Modifier: Priorizar Opus y activar FEC (Forward Error Correction) para evitar cortes
                                        let sdp = sessionDescription.sdp;
                                        const opusRegex = /a=rtpmap:(\d+) opus\/48000\/2/i;
                                        const match = sdp.match(opusRegex);
                                        if (match) {
                                            const pt = match[1];
                                            sdp = sdp.replace(/m=audio (\d+) RTP\/SAVP(F?) ([0-9 ]+)/, (m, port, f, codecs) => {
                                                const codecsList = codecs.split(' ').filter(c => c !== pt);
                                                return `m=audio ${port} RTP/SAVP${f} ${pt} ${codecsList.join(' ')}`;
                                            });
                                            if (!sdp.includes(`a=fmtp:${pt}`)) {
                                                sdp += `a=fmtp:${pt} useinbandfec=1; usedtx=1\r\n`;
                                            } else {
                                                sdp = sdp.replace(new RegExp(`a=fmtp:${pt}(.*)`), `a=fmtp:${pt}$1; useinbandfec=1; usedtx=1`);
                                            }
                                        }
                                        sessionDescription.sdp = sdp;
                                        return Promise.resolve(sessionDescription);
                                    }
                                ]
                            }
                        },
                        delegate: {
                            onCallReceived: () => {
                                console.log("SIP Event: Call Received");
                                setCallStatus('ringing');
                                setCallDirection('in');
                                const num = su.session?.remoteIdentity?.uri?.user || 'Desconocido';
                                setRemoteNumber(num);
                                
                                // Timbre Nativo (Web Audio Synthsizer)
                                startSynthesizedRing('ringing');
                                haptic('heavy');
                                
                                // iOS Backgrounding Avanzado (Web Push)
                                if (document.visibilityState !== 'visible' && 'serviceWorker' in navigator) {
                                    navigator.serviceWorker.ready.then(reg => {
                                        reg.showNotification('Llamada Entrante', {
                                            body: `📞 Llamada de ${num}`,
                                            icon: '/teleflow/icon-192.png',
                                            tag: 'incoming-call',
                                            vibrate: [300, 100, 300, 100, 300],
                                            requireInteraction: true,
                                            actions: [
                                                { action: 'answer', title: 'Contestar' },
                                                { action: 'reject', title: 'Rechazar' }
                                            ]
                                        });
                                    });
                                }
                            },
                            onCallHangup: () => { 
                                console.log("SIP Event: Hangup");
                                setCallStatus(null);
                                setRemoteNumber('');
                                setIsHeld(false);
                                setIsMuted(false);
                                setIsSpeaker(false);
                                clearInterval(timerRef.current);
                                if(vibrateInterval.current) clearInterval(vibrateInterval.current);
                                if(navigator.vibrate) navigator.vibrate(0);
                                setElapsed(0);
                                setStatus('Registrado (Libre)');
                                
                                stopSynthesizedRing();
                                
                                if ('serviceWorker' in navigator) {
                                    navigator.serviceWorker.ready.then(reg => {
                                        reg.getNotifications({ tag: 'incoming-call' }).then(ns => ns.forEach(n => n.close()));
                                    });
                                }
                                haptic('medium');
                            },
                            onCallAnswered: () => { 
                                console.log("SIP Event: Answered");
                                setCallStatus('in-call'); 
                                setStatus('En Llamada');
                                setElapsed(0);
                                if(timerRef.current) clearInterval(timerRef.current);
                                if(vibrateInterval.current) clearInterval(vibrateInterval.current);
                                if(navigator.vibrate) navigator.vibrate(0);
                                timerRef.current = setInterval(() => setElapsed(e => e + 1), 1000);
                                
                                stopSynthesizedRing();
                                
                                if ('serviceWorker' in navigator) {
                                    navigator.serviceWorker.ready.then(reg => {
                                        reg.getNotifications({ tag: 'incoming-call' }).then(ns => ns.forEach(n => n.close()));
                                    });
                                }
                                haptic('success');

                                // Manejo de Re-Invites de SIP (Tiempos muertos/Held/Cambios de Red)
                                if (su.session && su.session.sessionDescriptionHandler && su.session.sessionDescriptionHandler.peerConnection) {
                                    const pc = su.session.sessionDescriptionHandler.peerConnection;
                                    pc.addEventListener('iceconnectionstatechange', () => {
                                        console.log('ICE Connection State:', pc.iceConnectionState);
                                        if (pc.iceConnectionState === 'failed') {
                                            console.error('ICE Connection FAILED: Posible problema de NAT o Firewall.');
                                            showToast('Falla de conexión media (ICE)', 'error');
                                        }
                                        if (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') {
                                            setupVideoTracks(su.session);
                                        }
                                    });
                                    
                                    // Interceptar Asterisk Re-Invites
                                    // SIP.js v0.20 permite escuchar session.delegate.onInvite
                                    const existingDelegate = su.session.delegate || {};
                                    su.session.delegate = {
                                        ...existingDelegate,
                                        onInvite: (request, response, statusCode) => {
                                            console.log("SIP Event: Re-Invite recivido (Asterisk Session Timer u Hold)");
                                            setTimeout(() => setupVideoTracks(su.session), 300);
                                            if (existingDelegate.onInvite) existingDelegate.onInvite(request, response, statusCode);
                                        }
                                    };
                                }

                                const num = callDirection === 'in' ? (su.session?.remoteIdentity?.uri?.user || remoteNumber) : dest;
                                saveHistory({ num, dir: callDirection, time: new Date().getTime(), acc:'answered' });
                                
                                setTimeout(() => {
                                    setupVideoTracks(su.session);
                                    if (su.session && su.session.sessionDescriptionHandler) {
                                        const pc = su.session.sessionDescriptionHandler.peerConnection;
                                        const remoteStream = new MediaStream();
                                        pc.getReceivers().forEach(r => { if(r.track) remoteStream.addTrack(r.track); });
                                        startAudioAnalyzer(remoteStream);
                                    }
                                }, 800);
                            },
                            onCallHold: (session, hold) => {
                                console.log("SIP Event: Hold", hold);
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
                    }
                });

                setStatus('Registrando...');
                    su.connect()
                      .then(() => su.register({
                          requestOptions: {
                              extraHeaders: [ 'X-Teleflow-PWA: 1' ]
                          }
                      }))
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
                const pc = session.sessionDescriptionHandler.peerConnection;
                if (!pc) return;

                const remoteStream = new MediaStream();
                const localStream = new MediaStream();

                pc.getReceivers().forEach(receiver => {
                    if (receiver.track) {
                         remoteStream.addTrack(receiver.track);
                         // If it's audio only, ensure audio element has it
                         if (receiver.track.kind === 'audio' && audioRef.current) {
                             const aStream = new MediaStream([receiver.track]);
                             audioRef.current.srcObject = aStream;
                             audioRef.current.play().catch(()=>{});
                             
                             // Start audio analysis for the wave effect
                             startAudioAnalyzer(aStream);
                         }
                    }
                });

                pc.getSenders().forEach(sender => {
                    if (sender.track) localStream.addTrack(sender.track);
                });

                if (remoteVideoRef.current) {
                    remoteVideoRef.current.srcObject = remoteStream;
                    remoteVideoRef.current.play().catch(()=>{});
                    // Also analyze from video stream if it exists
                    if (remoteStream.getAudioTracks().length > 0) {
                        startAudioAnalyzer(remoteStream);
                    }
                }
                if (localVideoRef.current) {
                    localVideoRef.current.srcObject = localStream;
                    localVideoRef.current.play().catch(()=>{});
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
            const startCall = (video = false) => {
                if(!simpleUser || !status.includes('Registrado')) return showToast('Debe estar registrado','error');
                if(!dest) return showToast('Ingrese un número','error');

                const opts = { 
                    sessionDescriptionHandlerOptions: { 
                        constraints: { audio: true, video: video } 
                    } 
                };
                
                setVideoActive(video);
                setCallDirection('out');
                setRemoteNumber(dest);
                
                haptic('light');
                playClick(); // Feedback for the button
                startSynthesizedRing('ringback');

                simpleUser.call(`sip:${dest}@${domain}`, opts)
                   .then(() => {
                       setCallStatus('calling');
                       saveHistory({ num: dest, dir: 'out', time: new Date().getTime(), acc:'calling' });
                   })
                   .catch(e => {
                       stopSynthesizedRing();
                       setCallStatus(null);
                       setRemoteNumber('');
                       setVideoActive(false);
                       showToast('Llamada rechazada o inalcanzable (' + (e?.message?.substring(0,18) || 'Error') + ')','error');
                   });
            };

            const answerCall = (video = false) => {
                if(!simpleUser) return;
                console.log("Answering call...");
                setVideoActive(video);
                stopSynthesizedRing(); 
                setCallStatus('in-call'); 
                
                const opts = { 
                    sessionDescriptionHandlerOptions: { 
                        constraints: { audio: true, video: video } 
                    } 
                };
                
                simpleUser.answer(opts).then(() => {
                    console.log("Answer signal sent successfully");
                    // Force multiple checks for media tracks
                    setTimeout(() => setupVideoTracks(simpleUser.session), 500);
                    setTimeout(() => setupVideoTracks(simpleUser.session), 2000);
                }).catch(e => {
                    console.error("Answer error:", e);
                    showToast('Falló al contestar','error');
                    setCallStatus(null);
                    stopSynthesizedRing();
                });
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
                const active = isMuted; // If it was muted, we want to activate it
                const pc = simpleUser.session.sessionDescriptionHandler.peerConnection;
                if(pc) {
                    pc.getSenders().forEach(sender => {
                        if(sender.track && sender.track.kind === 'audio') {
                            sender.track.enabled = active;
                        }
                    });
                }
                setIsMuted(!active);
                showToast(!active ? 'Micrófono silenciado' : 'Micrófono activado');
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
                haptic('light');
                if(isHeld) simpleUser.unhold();
                else simpleUser.hold();
                setIsHeld(!isHeld);
            };

            const toggleSpeaker = async () => {
                const audio = audioRef.current;
                const video = remoteVideoRef.current;
                if(!audio && !video) return;
                
                haptic('light');
                try {
                    const active = !isSpeaker;
                    setIsSpeaker(active);

                    if (audio.setSinkId) {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const speakers = devices.filter(d => d.kind === 'audiooutput');
                        
                        // Pick best speaker (often labeled 'Speaker' or 'Altavoz')
                        const bestSpeaker = speakers.find(d => 
                            d.label.toLowerCase().includes('speaker') || 
                            d.label.toLowerCase().includes('altavoz') ||
                            d.label.toLowerCase().includes('built-in speaker')
                        ) || (speakers.length > 1 ? speakers[1] : speakers[0]);

                        if (active && bestSpeaker) {
                            await audio.setSinkId(bestSpeaker.deviceId);
                            if(video) await video.setSinkId(bestSpeaker.deviceId);
                            console.log("Using speaker:", bestSpeaker.label);
                        } else {
                            await audio.setSinkId(speakers[0]?.deviceId || '');
                            if(video) await video.setSinkId(speakers[0]?.deviceId || '');
                        }
                    } else {
                        // iOS/Safari Fallback: change volume level to hint behavior
                        audio.volume = active ? 1.0 : 0.4;
                        if(video) video.volume = active ? 1.0 : 0.4;
                    }
                    
                    showToast(active ? 'Altavoz activado' : 'Modo auricular', 'success');
                } catch(e) { 
                    console.error("Speaker error:", e);
                    showToast('Error de ruteo audio','error'); 
                }
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
                        setAudioLevel(sum / data.length * 3.5); // Much higher boost for visible animation
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
                setHistory(prev => {
                    const nh = [record, ...prev].slice(0, 50); // Keep last 50
                    if (ext) {
                        localStorage.setItem('tf_call_history_' + ext, JSON.stringify(nh));
                    }
                    return nh;
                });
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
                    <div className="app-container flex flex-col justify-center items-center px-8 relative overflow-hidden bg-[#0f1923]">
                        {/* Dynamic Background Blobs */}
                        <div className="absolute top-[-10%] left-[-10%] w-[60%] h-[40%] bg-primary/10 blur-[100px] rounded-full animate-blob"></div>
                        <div className="absolute bottom-[-10%] right-[-10%] w-[60%] h-[40%] bg-blue-500/10 blur-[100px] rounded-full animate-blob animation-delay-2000"></div>
                        
                        <div className="w-full max-w-sm z-10">
                            {/* Logo/Icon Section */}
                            <div className="flex flex-col items-center mb-10 animate-fadeIn">
                                <div className="w-20 h-20 bg-slate-800/50 backdrop-blur-xl rounded-3xl flex items-center justify-center border border-white/10 shadow-2xl mb-6 relative">
                                    <div className="absolute inset-0 bg-primary/20 blur-xl rounded-full opacity-50"></div>
                                    <span className="material-symbols-outlined text-4xl text-primary filled-icon relative">phonelink_ring</span>
                                </div>
                                <h1 className="text-3xl font-extrabold text-white tracking-tight mb-2">Softphone App</h1>
                                <p className="text-slate-400 font-medium text-sm">Teleflow Enterprise Client</p>
                            </div>

                            {/* Login Card */}
                            <div className="glass-panel p-8 rounded-[40px] border border-white/5 shadow-2xl backdrop-blur-3xl animate-slideUp">
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] px-1">Extensión SIP</label>
                                        <div className="relative">
                                            <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">person</span>
                                            <input 
                                                type="number" 
                                                className="w-full bg-slate-900/50 border border-white/5 p-4 pl-12 rounded-2xl text-white font-bold text-lg placeholder:text-slate-700 focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all outline-none" 
                                                value={ext} 
                                                onChange={e=>setExt(e.target.value)} 
                                                placeholder="1005" 
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] px-1">Contraseña</label>
                                        <div className="relative">
                                            <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xl">lock</span>
                                            <input 
                                                type="password" 
                                                className="w-full bg-slate-900/50 border border-white/5 p-4 pl-12 rounded-2xl text-white font-bold text-lg placeholder:text-slate-700 focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all outline-none" 
                                                value={pass} 
                                                onChange={e=>setPass(e.target.value)} 
                                                placeholder="••••••••" 
                                            />
                                        </div>
                                    </div>

                                    <button 
                                        onClick={connect} 
                                        className="w-full py-4 bg-primary hover:bg-primary/90 text-white font-extrabold rounded-2xl shadow-xl shadow-primary/20 active:scale-[0.97] transition-all flex items-center justify-center gap-2 group mt-4 relative"
                                    >
                                        <span>Conectar</span>
                                        <span className="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                        <div id="btn-connect-hidden" className="absolute" style={{visibility:'hidden'}} onClick={connect}></div>
                                    </button>
                                </div>
                            </div>

                            {/* Status/Error Message */}
                            {status.includes('Error') && (
                                <div className="mt-8 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center gap-3 animate-shake">
                                    <span className="material-symbols-outlined text-red-500">error</span>
                                    <span className="text-xs text-red-400 font-bold whitespace-pre-wrap">{status}</span>
                                </div>
                            )}

                            {/* Footer links */}
                            <div className="mt-12 text-center opacity-40">
                                <p className="text-[10px] font-bold uppercase tracking-widest text-slate-500">Powererd by Teleflow PWA Engine</p>
                            </div>
                        </div>
                        
                        {/* iOS Home Indicator */}
                        <div className="absolute bottom-2 left-1/2 -translate-x-1/2 w-32 h-1.5 bg-white/10 rounded-full"></div>
                    </div>
                );
            }

            // 2. PANTALLA PRINCIPAL
            return (
                <div className="app-container bg-app-gradient relative overflow-hidden">
                    <div className={`main-content transition-all duration-500 ${callStatus ? 'hidden overflow-hidden' : 'opacity-100 scale-100'}`}>
                        {/* Radial background overlay */}
                        <div className="absolute inset-0 opacity-20 pointer-events-none" 
                             style={{backgroundImage: 'radial-gradient(circle at 50% 0%, #007bff 0%, transparent 70%)'}}></div>                    
                        
                        {toast && (
                            <div className="toast" style={{background: toast.type==='error'?'#ef4444':toast.type==='success'?'#10b981':'var(--primary)'}}>
                                <span className="material-icons-round" style={{fontSize:18}}>{toast.type==='error'?'error':toast.type==='success'?'check_circle':'info'}</span>
                                {toast.msg}
                            </div>
                        )}

                        {activeTab==='dashboard' && (
                          <div className="page-enter flex flex-col px-5 pb-40">
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
                          <div className="page-enter flex flex-col pb-40">
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
                                        <button key={k.n} className="dial-btn" onClick={()=>{ setDest(d=>d+k.n); playDTMF(k.n); haptic('light'); }}>
                                            <span style={{fontSize:28,fontWeight:400,lineHeight:1}}>{k.n}</span>
                                            {k.l && <span style={{fontSize:9,color:'var(--muted)',fontWeight:700,letterSpacing:'1px',marginTop:2}}>{k.l}</span>}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            
                            {/* Action Tools (Call & Backspace) */}
                            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', padding:'0 50px 30px'}}>
                                <div style={{width:60}}></div> {/* Spacer to center call btn */}
                                
                                <div style={{display:'flex', gap:'15px'}}>
                                    <button className="call-btn" style={{width:65,height:65,background:'var(--accent)'}} onClick={() => startCall(false)}>
                                        <span className="material-icons-round" style={{fontSize:30}}>call</span>
                                    </button>
                                    <button className="call-btn" style={{width:65,height:65,background:'#38bdf8'}} onClick={() => startCall(true)}>
                                        <span className="material-icons-round" style={{fontSize:30}}>videocam</span>
                                    </button>
                                </div>
                                
                                <div style={{width:60, display:'flex', justifyContent:'flex-end'}}>
                                    <button onClick={()=>{setDest(d=>d.slice(0,-1)); playClick();}} style={{background:'transparent',border:'none',color:'var(--muted)',padding:10}}>
                                        <span className="material-icons-round" style={{fontSize:28}}>{dest?'backspace':''}</span>
                                    </button>
                                </div>
                            </div>
                          </div>
                        )}  
                        {/* ──────────────── TAB: DASHBOARD ──────────────── */}
                        {activeTab==='dashboard' && (
                          <div className="page-enter p-5 flex flex-col gap-6 animate-fadeIn pb-32">
                             {/* Content */}
                          </div>
                        )}

                        {/* ──────────────── TAB: CONTACTS ──────────────── */}
                        {activeTab==='contacts' && (
                          <div className="page-enter p-5 flex flex-col h-full overflow-hidden">
                            <h2 className="text-2xl font-extrabold mb-5 pl-1">Directorio</h2>
                            <div className="flex-1 overflow-y-auto pr-1 flex flex-col gap-3 pb-32">
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
                          <div className="page-enter p-5 flex flex-col h-full overflow-hidden">
                            <h2 className="text-2xl font-extrabold mb-5 pl-1">Recientes</h2>
                            <div className="flex-1 overflow-y-auto pr-1 pb-32">
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
                          <div className="page-enter p-5 min-h-full">
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
                                <button onClick={() => { setShowUpdateModal(true); checkUpdate(); }} className="w-full flex items-center justify-between p-4 bg-primary/10 text-primary rounded-2xl border border-primary/20 hover:bg-primary/20 transition-all font-bold text-sm">
                                    <div className="flex items-center gap-3">
                                        <span className="material-symbols-outlined">system_update</span>
                                        <span>Actualizar App (PWA)</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-[10px] opacity-60">v{appVersion}</span>
                                        <span className="material-symbols-outlined text-[18px]">chevron_right</span>
                                    </div>
                                </button>

                                <button onClick={disconnect} className="w-full flex items-center gap-3 p-4 bg-red-500/10 text-red-500 rounded-2xl border border-red-500/10 hover:bg-red-500/20 transition-all font-bold text-sm">
                                    <span className="material-symbols-outlined">logout</span>
                                    Cerrar Sesión
                                </button>
                            </div>
                          </div>
                        )}
                    </div>

                    {/* Navbar (iOS STYLE PREMIUM - STAYS FIXED AT BOTTOM) */}
                    <nav className={`fixed bottom-0 left-0 right-0 glass-panel border-t border-white/10 pb-8 pt-3 px-6 z-[100] transition-transform duration-500 ${callStatus ? 'translate-y-full' : 'translate-y-0'}`}>
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
                        <div className={`call-overlay fixed inset-0 z-[200] overflow-hidden flex flex-col transition-colors duration-500 ${videoActive && callStatus === 'in-call' ? 'bg-transparent' : 'bg-slate-950'}`}>
                            
                            {/* Proximity Overlay (Black screen when near ear) */}
                            {isNear && callStatus === 'in-call' && (
                                <div className="fixed inset-0 bg-black z-[1000] flex items-center justify-center animate-fadeIn">
                                    <div className="text-white/5 flex flex-col items-center gap-4">
                                        <span className="material-symbols-outlined text-6xl">visibility_off</span>
                                        <p className="text-[10px] font-bold uppercase tracking-[0.3em]">Modo Proximidad Activo</p>
                                    </div>
                                </div>
                            )}

                            {/* Overlay Background Wrapper */}
                            <div className="absolute inset-0 z-0 overflow-hidden">
                                {(!videoActive || callStatus !== 'in-call') && (
                                    <div className="w-full h-full bg-slate-900 flex flex-col items-center justify-center transition-opacity">
                                        <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
                                        <div className="w-32 h-32 rounded-full glass-panel flex items-center justify-center border-2 border-white/5 shadow-2xl overflow-hidden backdrop-blur-3xl animate-fadeIn">
                                            {contacts.find(c => c.ext === remoteNumber)?.avatar ? (
                                                <img src={`../${contacts.find(c => c.ext === remoteNumber).avatar}`} className="w-full h-full object-cover" />
                                            ) : (
                                                <span className="text-4xl text-white/30 font-bold">{remoteNumber?.substring(0,2)}</span>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Top Header Area */}
                            <div className="relative z-10 w-full pt-44 px-6 flex flex-col items-center animate-fadeIn">
                                <h1 className="text-white text-3xl font-bold tracking-tight text-center">
                                    {contacts.find(c => c.ext === remoteNumber)?.name || remoteNumber}
                                </h1>
                                
                                <div className="mt-14 flex flex-col items-center justify-center min-h-[160px]">
                                    {callStatus === 'in-call' ? (
                                        <div className="flex flex-col items-center">
                                            <div className="call-timer-big animate-fadeIn mb-10">{formatTime(elapsed)}</div>
                                            <div className="wave-container">
                                                {[...Array(12)].map((_, i) => {
                                                    const scale = 0.5 + (audioLevel * (0.4 + Math.random() * 0.8));
                                                    return (
                                                        <div key={i} className="wave-dot" 
                                                             style={{ 
                                                                 transform: `scale(${Math.max(1, scale)})`,
                                                                 opacity: 0.3 + (scale/10),
                                                                 background: i % 2 === 0 ? 'var(--primary)' : '#fff'
                                                             }}></div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="bg-black/30 backdrop-blur-2xl px-6 py-3 rounded-full border border-white/10 shadow-lg animate-pulse">
                                            <p className="text-white text-sm font-bold uppercase tracking-widest text-center">
                                                {callStatus === 'ringing' ? 'Llamada Entrante...' : callStatus === 'calling' ? 'Conectando...' : 'En Espera'}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Bottom Controls Area (Fixed at bottom) */}
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


                                {/* Additional Actions */}
                                <div className="flex justify-center mb-6 gap-10">
                                    <button onClick={()=>setShowInCallKeypad(true)} className="flex flex-col items-center gap-1.5 group active-scale">
                                        <div className="size-10 flex items-center justify-center rounded-full bg-white/5 text-white">
                                            <span className="material-symbols-outlined text-xl">dialpad</span>
                                        </div>
                                        <span className="text-[10px] text-slate-300 font-bold uppercase tracking-widest">Teclado</span>
                                    </button>
                                    
                                    <button onClick={openDoor} className="flex flex-col items-center gap-1.5 group active-scale">
                                        <div className="size-10 flex items-center justify-center rounded-full bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 shadow-lg shadow-yellow-500/10">
                                            <span className="material-symbols-outlined text-xl">lock</span>
                                        </div>
                                        <span className="text-[10px] text-yellow-500/80 font-bold uppercase tracking-widest">Abrir</span>
                                    </button>

                                    <button onClick={toggleMute} className="flex flex-col items-center gap-1.5 group active-scale">
                                        <div className={`size-10 flex items-center justify-center rounded-full transition-all ${isMuted?'btn-toggle-active':'bg-white/5 text-white'}`}>
                                            <span className={`material-symbols-outlined text-xl ${isMuted?'filled-icon':''}`}>{isMuted?'mic_off':'mic'}</span>
                                        </div>
                                        <span className="text-[10px] text-slate-300 font-bold uppercase tracking-widest">{isMuted?'Mudo':'Micro'}</span>
                                    </button>
                                </div>

                                {/* Main Glass Bar */}
                                <div className="max-w-md mx-auto bg-slate-800/40 backdrop-blur-3xl rounded-[2.5rem] p-4 flex items-center justify-between border border-white/10 shadow-2xl">
                                    {/* Mute Toggle */}
                                    <button onClick={toggleHold} className={`flex items-center justify-center size-14 rounded-full transition-all active-scale ${isHeld?'btn-toggle-active':'bg-white/10 text-white'}`}>
                                        <span className="material-symbols-outlined text-[28px]">{isHeld?'play_arrow':'pause'}</span>
                                    </button>
                                    
                                    {/* Speaker Toggle */}
                                    <button onClick={toggleSpeaker} className={`flex items-center justify-center size-14 rounded-full transition-all active-scale ${isSpeaker?'btn-toggle-active':'bg-white/10 text-white'}`}>
                                        <span className="material-symbols-outlined text-[28px]">{isSpeaker?'volume_up':'volume_down'}</span>
                                    </button>

                                    {/* Video Toggle */}
                                    <button onClick={toggleVideo} className={`flex items-center justify-center size-14 rounded-full transition-all active-scale ${!videoActive?'btn-toggle-active':'bg-white/10 text-white'}`}>
                                        <span className={`material-symbols-outlined text-[28px] ${videoActive?'filled-icon':''}`}>{videoActive?'videocam':'videocam_off'}</span>
                                    </button>
                                    
                                    {/* End Call Button */}
                                    <button onClick={hangupCall} className="flex items-center justify-center size-14 rounded-full bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/20 active:scale-95 transition-all outline outline-offset-4 outline-red-500/30">
                                        <span className="material-symbols-outlined text-[28px]">call_end</span>
                                    </button>
                                </div>
                            </div>
                            {/* Keypad */}
                            {showInCallKeypad && (
                                <div className="fixed inset-0 bg-slate-950/98 backdrop-blur-3xl z-[500] flex flex-col pt-20 animate-fadeIn transition-all">
                                    <div className="flex flex-col items-center mb-10">
                                        <div className="size-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4 border border-white/10">
                                            <span className="material-symbols-outlined text-primary text-3xl">dialpad</span>
                                        </div>
                                        <h2 className="text-white text-xl font-bold tracking-widest uppercase opacity-80">Tonos DTMF</h2>
                                        <p className="text-slate-500 text-[10px] font-bold mt-1 tracking-tighter">PARA MARCACIÓN INTERNA</p>
                                    </div>
                                    <div className="grid grid-cols-3 gap-y-8 gap-x-10 px-12 max-w-sm mx-auto">
                                        {['1','2','3','4','5','6','7','8','9','*','0','#'].map(d => (
                                            <button key={d} className="dial-btn border border-white/10 size-16 bg-white/5 active:bg-primary transition-colors flex items-center justify-center rounded-full" onClick={() => { playDTMF(d); haptic('light'); }}>
                                                <span className="text-3xl font-bold">{d}</span>
                                            </button>
                                        ))}
                                    </div>
                                    <div className="mt-auto mb-24 flex justify-center w-full px-10">
                                        <button onClick={() => setShowInCallKeypad(false)} className="w-full max-w-xs py-4 glass-panel border border-white/10 rounded-full text-white font-bold uppercase tracking-[0.2em] shadow-xl active:scale-95 transition-all">
                                            Cerrar Teclado
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* ──────────────── UPDATE MODAL (iOS/SILEO STYLE PREMIUM) ──────────────── */}
                    {showUpdateModal && (
                        <div className="fixed inset-0 z-[600] flex items-center justify-center bg-slate-950/80 backdrop-blur-md transition-all p-6" onClick={() => updateStatus !== 'downloading' && setShowUpdateModal(false)}>
                            <div className="bg-[#1c1c1e] w-full max-w-sm rounded-[2.5rem] border border-white/10 shadow-2xl overflow-hidden animate-pageFadeIn flex flex-col" onClick={e => e.stopPropagation()}>
                                
                                {/* Splash Header */}
                                <div className="h-44 bg-gradient-to-br from-primary to-purple-900 relative flex flex-col items-center justify-center p-8 overflow-hidden">
                                    <div className="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                                    <div className="relative z-10 w-16 h-16 bg-white/10 backdrop-blur-xl rounded-2xl flex items-center justify-center border border-white/20 shadow-2xl mb-2">
                                        <span className="material-symbols-outlined text-4xl text-white">
                                            {updateStatus === 'checking' ? 'sync' : updateStatus === 'available' ? 'download' : updateStatus === 'downloading' ? 'cloud_download' : 'verified'}
                                        </span>
                                    </div>
                                    {updateStatus === 'checking' && <div className="absolute bottom-0 left-0 h-1 bg-white animate-[progress_2s_infinite] w-1/3"></div>}
                                    {updateStatus === 'downloading' && <div className="absolute bottom-0 left-0 h-1 bg-primary animate-pulse w-full"></div>}
                                </div>

                                <div className="p-6 flex flex-col gap-5">
                                    <div className="text-center">
                                        {updateStatus === 'checking' ? (
                                            <>
                                                <h2 className="text-2xl font-bold text-white mb-2">Buscando...</h2>
                                                <p className="text-slate-400 text-sm">Verificando versiones en el servidor</p>
                                            </>
                                        ) : updateStatus === 'available' ? (
                                            <>
                                                <h2 className="text-2xl font-bold text-white mb-1">Nueva Versión</h2>
                                                <div className="flex items-center justify-center gap-2 mb-4">
                                                    <span className="text-xs font-bold px-2 py-0.5 bg-white/10 text-slate-400 rounded">v{appVersion}</span>
                                                    <span className="material-symbols-outlined text-xs text-slate-600">arrow_forward</span>
                                                    <span className="text-xs font-bold px-2 py-0.5 bg-primary/20 text-primary rounded">v1.3.8</span>
                                                </div>
                                            </>
                                        ) : (
                                            <>
                                                <h2 className="text-2xl font-bold text-white mb-2">Estás al día</h2>
                                                <p className="text-slate-400 text-sm tracking-tight text-center">TeleFlow se encuentra en su última versión (v{appVersion})</p>
                                            </>
                                        )}
                                    </div>

                                    {updateStatus === 'available' && (
                                        <div className="bg-white/5 rounded-3xl p-5 border border-white/5">
                                            <p className="text-[10px] font-bold text-primary uppercase tracking-widest mb-3">Cambios Importantes</p>
                                            <ul className="space-y-3">
                                                <li className="flex items-start gap-3">
                                                    <span className="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                                                    <span className="text-xs text-slate-300 leading-normal"><strong>Optimización IVR:</strong> Audio GSM nativo para mayor compatibilidad.</span>
                                                </li>
                                                <li className="flex items-start gap-3">
                                                    <span className="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                                                    <span className="text-xs text-slate-300 leading-normal"><strong>Sensor Proximidad:</strong> Apagado de pantalla inteligente en llamadas.</span>
                                                </li>
                                                <li className="flex items-start gap-3">
                                                    <span className="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                                                    <span className="text-xs text-slate-300 leading-normal"><strong>Nuevas Acciones:</strong> Botones de Teclado DTMF y Apertura de Puerta.</span>
                                                </li>
                                            </ul>
                                        </div>
                                    )}

                                    <div className="flex flex-col gap-3">
                                        {updateStatus === 'available' ? (
                                            <button onClick={applyUpdate} 
                                                className="w-full p-4 bg-primary text-white rounded-2xl font-bold text-sm shadow-xl shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                                                <span className="material-symbols-outlined">download</span>
                                                Actualizar Ahora
                                            </button>
                                        ) : updateStatus === 'checking' ? (
                                            <button disabled className="w-full p-4 bg-white/5 text-slate-500 rounded-2xl font-bold text-sm cursor-not-allowed">
                                                Espere...
                                            </button>
                                        ) : (
                                            <button onClick={() => setShowUpdateModal(false)} 
                                                className="w-full p-4 bg-white/10 text-white rounded-2xl font-bold text-sm active:scale-95 transition-all">
                                                Entendido
                                            </button>
                                        )}
                                        <button onClick={() => setShowUpdateModal(false)} className="w-full p-3 text-slate-500 text-[11px] font-bold uppercase tracking-widest hover:text-slate-300 transition-colors">
                                            Cerrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    {/* Update Available Banner */}
                    {updateStatus === 'updated' && (
                        <div className="fixed bottom-0 left-0 right-0 z-[500] p-4 bg-gradient-to-r from-primary to-purple-600 text-white text-center shadow-lg animate-slideUp">
                            <div className="max-w-md mx-auto flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <span className="material-symbols-outlined text-2xl">check_circle</span>
                                    <p className="text-sm font-semibold">¡Actualización Completa!</p>
                                </div>
                                <button onClick={() => {
                                    if ('serviceWorker' in navigator) {
                                        navigator.serviceWorker.getRegistrations().then(registrations => {
                                            for (let registration of registrations) {
                                                registration.unregister();
                                            }
                                            console.log('Service Worker unregistered.');
                                            setTimeout(() => {
                                                window.location.reload(true);
                                            }, 1000);
                                        });
                                    } else {
                                        window.location.reload(true);
                                    }
                                }} 
                                className="w-full mt-2 flex items-center justify-center gap-2 p-4 bg-primary hover:bg-primary-hover text-white rounded-2xl shadow-[0_0_20px_rgba(139,92,246,0.2)] transition-all font-bold text-base active:scale-95">
                                    <span className="material-symbols-outlined animate-spin-slow">sync</span>
                                    Sincronizar Cambios
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Persistent Video Elements (Using opacity to keep them 'alive' for the browser engine) */}
                    <div className={`fixed inset-0 pointer-events-none z-0 transition-opacity duration-700 ${callStatus==='in-call' ? 'opacity-100' : 'opacity-0'}`}>
                         <video ref={remoteVideoRef} autoPlay playsInline muted={false} className="w-full h-full object-cover" />
                    </div>
                    
                    <div className={`fixed top-14 right-4 z-[210] w-32 aspect-[3/4] rounded-2xl overflow-hidden border-2 border-white/20 shadow-2xl pointer-events-none transition-all duration-500 ${(callStatus==='in-call' || callStatus==='calling') && videoActive ? 'opacity-100 scale-100' : 'opacity-0 scale-50'}`}>
                         <video ref={localVideoRef} autoPlay playsInline muted className="w-full h-full object-cover scale-x-[-1]" />
                    </div>

                    <audio ref={audioRef} autoPlay playsInline />
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<SoftphoneApp />);
    </script>
</body>
</html>
