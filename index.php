<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Teleflow Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.min.js"></script>
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .glass-login { background: rgba(13, 17, 23, 0.6); backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .glass { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 15px); padding-bottom: 120px; }
        .tab-bar { position: fixed; bottom: 25px; left: 15px; right: 15px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .tab-item { color: #8b949e; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 2px; transition: 0.3s; }
        .tab-item.active { color: #8B5CF6; }
        .input-tf { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; transition: 0.3s; outline: none; }
        .input-tf:focus { border-color: #8B5CF6; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }
        .btn-glow { background: linear-gradient(135deg, #8B5CF6 0%, #714B67 100%); transition: 0.4s; }
        .btn-glow:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(139, 92, 246, 0.5); }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) { window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = () => {}; window.__REACT_DEVTOOLS_GLOBAL_HOOK__.inject = () => {}; }
        const { useState, useEffect, useRef } = React;
        const { motion, AnimatePresence } = window.Motion;

        function App() {
            const [isLogged, setIsLogged] = useState(false);
            const [view, setView] = useState('extensiones');
            const [data, setData] = useState({ pbx: { extensions: [], recordings: [] }, system: {} });
            const [error, setError] = useState(null);

            const refresh = () => {
                fetch('api/index.php?action=get_full_data').then(r => {
                    if (r.status === 403) { setIsLogged(false); return null; }
                    return r.json();
                }).then(d => { if(d) setData(d); }).catch(e => {});
            };

            useEffect(() => { if (isLogged) { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); } }, [isLogged]);

            const handleLogin = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                try {
                    const r = await fetch('api/index.php?action=login', { method:'POST', body:fd });
                    const res = await r.json();
                    if (res.status === 'success') setIsLogged(true); else setError('Credenciales incorrectas');
                } catch (e) { setError('Error de conexión'); }
            };

            if (!isLogged) return (
                <div className="h-screen flex items-center justify-center bg-[#030406]">
                    <motion.div initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} className="glass-login w-full max-w-[400px] rounded-[40px] p-10 text-center">
                        <div className="w-20 h-20 bg-[#8B5CF6] rounded-[28px] mx-auto flex items-center justify-center mb-8 shadow-2xl">
                            <span className="material-icons text-white text-5xl">sensors</span>
                        </div>
                        <h1 className="text-4xl font-black text-white mb-2 italic">Teleflow</h1>
                        <p className="text-gray-500 text-[10px] font-black uppercase mb-10 tracking-[0.3em]">Next-Gen PBX Control</p>
                        <form onSubmit={handleLogin} className="space-y-4">
                            <input name="username" type="text" placeholder="Usuario" className="input-tf w-full py-4 px-6 rounded-2xl" required />
                            <input name="password" type="password" placeholder="Contraseña" className="input-tf w-full py-4 px-6 rounded-2xl" required />
                            {error && <div className="text-red-400 text-xs font-bold">{error}</div>}
                            <button className="btn-glow w-full py-4 rounded-2xl text-white font-black uppercase text-sm">Acceder al Sistema</button>
                        </form>
                    </motion.div>
                </div>
            );

            return (
                <div className="flex h-screen w-screen overflow-hidden">
                    <main className="flex-1 page-wrapper px-6 overflow-y-auto pb-32">
                        <header className="flex justify-between items-center mb-10">
                            <div><h1 className="text-3xl font-black uppercase text-white tracking-tighter">{view}</h1></div>
                            <div className="bg-[#8B5CF6] text-white w-11 h-11 flex items-center justify-center rounded-xl font-black cursor-pointer" onClick={()=>window.location.reload()}>FG</div>
                        </header>

                        {view === 'extensiones' && (
                            <div className="space-y-4">
                                {data.pbx.extensions.map(e => (
                                    <div key={e.ext} className="glass p-5 flex items-center justify-between rounded-2xl border-l-4 border-l-purple-500">
                                        <div className="flex items-center gap-4">
                                            <img src={e.avatar} className="w-14 h-14 rounded-xl object-cover"/>
                                            <div><div className="font-black text-xl">#{e.ext}</div><div className="text-xs text-gray-500 uppercase">{e.name}</div></div>
                                        </div>
                                        <div className="text-right"><span className="text-[9px] font-black text-green-500 uppercase">{e.status}</span></div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {view === 'grabaciones' && (
                            <div className="space-y-4">
                                {data.pbx.recordings.map((r, i) => (
                                    <div key={i} className="glass p-5 rounded-2xl flex flex-col gap-3">
                                        <div className="flex justify-between items-center text-sm font-bold">
                                            <b>#{r.src} → {r.dst}</b>
                                            <span className="opacity-50 font-black">{r.duration}s</span>
                                        </div>
                                        <audio controls src={`/monitor/${r.recordingfile}`} className="w-full"></audio>
                                    </div>
                                ))}
                            </div>
                        )}
                    </main>

                    <nav className="tab-bar glass">
                        <div className={`tab-item ${view==='dashboard'?'active':''}`} onClick={()=>setView('dashboard')}><span className="material-icons">grid_view</span></div>
                        <div className={`tab-item ${view==='extensiones'?'active':''}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span></div>
                        <div className={`tab-item tab-item-center ${view==='vivo'?'active':''}`} onClick={()=>setView('vivo')}><span className="material-icons text-3xl">sensors</span></div>
                        <div className={`tab-item ${view==='callcenter'?'active':''}`} onClick={()=>setView('callcenter')}><span className="material-icons">headset_mic</span></div>
                        <div className={`tab-item ${view==='grabaciones'?'active':''}`} onClick={()=>setView('grabaciones')}><span className="material-icons">mic</span></div>
                    </nav>
                </div>
            );
        }
        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
