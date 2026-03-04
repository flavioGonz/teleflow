<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Teleflow Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .glass { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(25px) saturate(180%); -webkit-backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: 150px; }
        .tab-bar { position: fixed; bottom: 25px; left: 20px; right: 20px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .tab-item { color: #8b949e; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px; transition: 0.3s; }
        .tab-item.active { color: #8B5CF6; }
        .tab-item-center { background: #8B5CF6; color: white !important; width: 55px; height: 55px; border-radius: 20px; justify-content: center; transform: translateY(-5px); box-shadow: 0 5px 20px rgba(139, 92, 246, 0.4); }
        .st-pill { padding: 4px 12px; border-radius: 20px; font-size: 9px; font-weight: 800; text-transform: uppercase; border: 1px solid currentColor; }
        .ONLINE { color: #22c55e; } .BUSY { color: #f59e0b; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        /* VIVO CARD ANIMATION */
        .live-card { border-left: 4px solid #ef4444; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(13, 17, 23, 0.8) 100%); animation: liveGlow 3s infinite; }
        @keyframes liveGlow { 0%, 100% { box-shadow: 0 0 10px rgba(239, 68, 68, 0.1); } 50% { box-shadow: 0 0 25px rgba(239, 68, 68, 0.3); } }
        .live-dot { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444; animation: ring 1.5s infinite; }
        @keyframes ring { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.4; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { motion, AnimatePresence } = window.Motion;
        const RF = window.ReactFlow;

        function App() {
            const [view, setView] = useState('extensiones');
            const [data, setData] = useState({ pbx: { extensions: [], calls: [], queues: [], recordings: [] }, system: {}, summary: {} });

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r => r.json()).then(d => setData(d));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return () => clearInterval(i); }, []);

            return (
                <div className="flex h-screen w-screen bg-[#030406] overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -15 }} transition={{ duration: 0.2 }} className="flex-1 page-wrapper px-6">
                            <header className="flex justify-between items-center mb-10">
                                <div><h1 className="text-3xl font-black uppercase text-white tracking-tighter">{view}</h1><p className="text-[10px] text-purple-400 font-black uppercase tracking-widest italic">Infratec Teleflow</p></div>
                                <div className="w-11 h-11 bg-purple-600 rounded-2xl flex items-center justify-center font-black shadow-lg">FG</div>
                            </header>

                            {/* MÓDULO VIVO (REPARADO Y ESTILIZADO) */}
                            {view === 'vivo' && (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                        <div className="glass p-5 rounded-3xl text-center"><div className="text-[10px] font-black text-gray-500 mb-1">CONVERSACIONES</div><div className="text-4xl font-black text-purple-500">{data.pbx.calls.length}</div></div>
                                        <div className="glass p-5 rounded-3xl text-center"><div className="text-[10px] font-black text-gray-500 mb-1">CANALES SIP</div><div className="text-4xl font-black text-white">{data.pbx.calls.length * 2}</div></div>
                                    </div>
                                    {data.pbx.calls.length === 0 ? (
                                        <div className="glass p-20 rounded-[40px] text-center opacity-40">
                                            <span className="material-icons text-5xl mb-4">sensors_off</span>
                                            <p className="font-bold uppercase tracking-widest">Sin actividad en tiempo real</p>
                                        </div>
                                    ) : data.pbx.calls.map((c, i) => (
                                        <motion.div key={i} initial={{ x: -20, opacity: 0 }} animate={{ x: 0, opacity: 1 }} className="glass live-card rounded-2xl p-6 flex justify-between items-center">
                                            <div className="flex items-center gap-6">
                                                <div className="live-dot"></div>
                                                <div>
                                                    <div className="flex items-center gap-3"><b className="text-2xl tracking-tighter">#{c.from}</b><span className="material-icons text-gray-600 text-sm">arrow_forward</span><b className="text-2xl tracking-tighter">{c.to}</b></div>
                                                    <div className="text-[10px] text-gray-500 font-black uppercase tracking-[0.2em] mt-1">Conexión SIP Activa</div>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-2xl font-black text-purple-400 font-mono tracking-tighter">{c.duration}</div>
                                                <div className="text-[8px] text-gray-600 font-black uppercase tracking-widest">Talk Time</div>
                                            </div>
                                        </motion.div>
                                    ))
                                    }
                                </div>
                            )}

                            {/* MÓDULO EXTENSIONES */}
                            {view === 'extensiones' && (
                                <div className="space-y-4">
                                    {data.pbx.extensions.map((e, idx) => (
                                        <div key={e.ext} className={`glass border-l-4 ${e.status==='ONLINE'?'border-l-green-500':'border-l-gray-600'} rounded-2xl p-5 flex justify-between items-center`}>
                                            <div className="flex items-center gap-5">
                                                <img src={e.avatar} className="w-14 h-14 rounded-2xl object-cover"/><div className="font-black text-xl">#{e.ext}<br/><span className="text-[10px] text-gray-500 uppercase">{e.name}</span></div>
                                            </div>
                                            <div className="text-right"><span className={`st-pill ${e.status} block mb-1`}>{e.status}</span><code className="text-purple-400 font-bold">{e.rtt}</code></div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* MÓDULO CALLCENTER */}
                            {view === 'callcenter' && (
                                <div className="grid grid-cols-2 gap-4">
                                    {data.pbx.queues.map(q => (
                                        <div key={q.name} className="glass p-6 rounded-3xl text-center border-t-4 border-t-purple-600">
                                            <div className="text-[10px] font-black text-gray-500 uppercase">{q.name}</div>
                                            <div className="text-5xl font-black text-white mt-2">{q.waiting}</div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* MÓDULO GRABACIONES */}
                            {view === 'grabaciones' && (
                                <div className="space-y-4">
                                    {data.pbx.recordings.map((r, i) => (
                                        <div key={i} className="glass p-6 rounded-2xl flex flex-col gap-4">
                                            <div className="flex justify-between items-center">
                                                <div><b className="text-lg">#{r.src} → {r.dst}</b><br/><small className="text-gray-500">{r.calldate}</small></div>
                                                <span className="text-xs font-black opacity-50">{r.duration}s</span>
                                            </div>
                                            <audio controls src={`/monitor/${r.recordingfile}`} preload="none"></audio>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </motion.main>
                    </AnimatePresence>

                    {/* TAB BAR NAVEGACIÓN */}
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
