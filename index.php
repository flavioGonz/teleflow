<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
        .glass { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: 150px; }
        .tab-bar { position: fixed; bottom: 25px; left: 20px; right: 20px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .tab-item { color: #8b949e; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px; transition: 0.3s; }
        .tab-item.active { color: #8B5CF6; }
        .tab-item-center { background: #8B5CF6; color: white !important; width: 55px; height: 55px; border-radius: 20px; justify-content: center; transform: translateY(-5px); box-shadow: 0 5px 20px rgba(139, 92, 246, 0.4); }
        
        /* CDR CLONE STYLING */
        .cdr-row { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); transition: 0.2s; }
        .cdr-row:hover { background: rgba(139, 92, 246, 0.05); border-color: rgba(139, 92, 246, 0.3); }
        .disposition-badge { font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
        .ANSWERED { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
        .NO_ANSWER { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
        audio { height: 30px; filter: invert(1) hue-rotate(180deg); opacity: 0.8; width: 120px; }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect } = React;
        const { motion, AnimatePresence } = window.Motion;

        function App() {
            const [view, setView] = useState('callcenter');
            const [data, setData] = useState({ pbx: { extensions: [], calls: [], queues: [], recordings: [] } });

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r => r.json()).then(d => setData(d));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return () => clearInterval(i); }, []);

            return (
                <div className="flex h-screen w-screen bg-[#030406] overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, x: 15 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -15 }} transition={{ duration: 0.2 }} className="flex-1 page-wrapper px-6">
                            <header className="flex justify-between items-center mb-10">
                                <div><h1 className="text-3xl font-black uppercase text-white tracking-tighter">{view}</h1><p className="text-[10px] text-purple-400 font-black uppercase tracking-widest italic">Panel de Supervisión v17.2</p></div>
                                <div className="w-11 h-11 bg-purple-600 rounded-2xl flex items-center justify-center font-black shadow-lg">FG</div>
                            </header>

                            {/* CALLCENTER CLON CDR MODERNIZADO */}
                            {view === 'callcenter' && (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="glass p-6 rounded-3xl text-center"><div className="text-[10px] font-black text-gray-500 mb-1">EN ESPERA</div><div className="text-5xl font-black text-purple-500">{data.pbx.queues.reduce((a,b)=>a+b.waiting,0)}</div></div>
                                        <div className="glass p-6 rounded-3xl text-center"><div className="text-[10px] font-black text-gray-500 mb-1">CALLS LIVE</div><div className="text-5xl font-black text-white">{data.pbx.calls.length}</div></div>
                                    </div>

                                    <div className="glass rounded-3xl overflow-hidden border border-white/5">
                                        <div className="p-5 bg-white/5 border-b border-white/5 flex justify-between items-center">
                                            <b className="text-xs uppercase tracking-widest text-gray-400">Historial Reciente (Clon CDR Pro)</b>
                                            <span className="material-icons text-purple-400">history</span>
                                        </div>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left">
                                                <thead>
                                                    <tr className="text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-white/5">
                                                        <th className="p-4">Fecha</th>
                                                        <th>Origen</th>
                                                        <th>Destino</th>
                                                        <th>Estado</th>
                                                        <th>Duración</th>
                                                        <th className="p-4 text-right">Escuchar</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {data.pbx.recordings.map((r, i) => (
                                                        <tr key={i} className="cdr-row border-b border-white/5">
                                                            <td className="p-4 text-[10px] font-bold text-gray-500">{r.calldate.split(' ')[1]}<br/>{r.calldate.split(' ')[0]}</td>
                                                            <td><div className="flex items-center gap-2"><span className="material-icons text-blue-400 text-sm">phone_callback</span><b className="text-sm font-black tracking-tighter">{r.src}</b></div></td>
                                                            <td><div className="flex items-center gap-2"><span className="material-icons text-purple-400 text-sm">phone_forwarded</span><b className="text-sm font-black tracking-tighter">{r.dst}</b></div></td>
                                                            <td><span className={`disposition-badge ${r.disposition}`}>{r.disposition}</span></td>
                                                            <td className="font-mono text-xs">{r.duration}s</td>
                                                            <td className="p-4 text-right"><audio controls src={`/monitor/${r.recordingfile}`} preload="none"></audio></td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* MÓDULO EXTENSIONES */}
                            {view === 'extensiones' && (
                                <div className="space-y-4">
                                    {data.pbx.extensions.map((e) => (
                                        <div key={e.ext} className={`glass border-l-4 ${e.status==='ONLINE'?'border-l-green-500':'border-l-gray-600'} rounded-2xl p-5 flex justify-between items-center`}>
                                            <div className="flex items-center gap-5">
                                                <img src={e.avatar} className="w-14 h-14 rounded-2xl object-cover"/><div className="font-black text-xl">#{e.ext}<br/><span className="text-[10px] text-gray-500 uppercase">{e.name}</span></div>
                                            </div>
                                            <div className="text-right"><span className={`st-pill ${e.status} block mb-1`}>{e.status}</span><code className="text-purple-400 font-bold">{e.rtt}</code></div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Otros módulos simplificados para v17.2 */}
                            {view === 'dashboard' && <div className="p-20 text-center glass rounded-3xl opacity-40">Dashboard Turbo-Flow Activo</div>}
                            {view === 'vivo' && <div className="p-20 text-center glass rounded-3xl opacity-40">Monitor Tiempo Real Activo</div>}
                        </motion.main>
                    </AnimatePresence>

                    {/* TAB BAR NAVEGACIÓN */}
                    <nav className="tab-bar glass">
                        <div className={`tab-item ${view==='dashboard'?'active':''}`} onClick={()=>setView('dashboard')}><span className="material-icons">grid_view</span></div>
                        <div className={`tab-item ${view==='extensiones'?'active':''}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span></div>
                        <div className={`tab-item tab-item-center ${view==='vivo'?'active':''}`} onClick={()=>setView('vivo')}><span className="material-icons" style={{fontSize:30}}>sensors</span></div>
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
