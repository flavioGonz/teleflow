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
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .glass-panel { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(25px) saturate(180%); -webkit-backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: 150px; }
        .tab-bar { position: fixed; bottom: 25px; left: 20px; right: 20px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .tab-item { color: #8b949e; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px; transition: 0.3s; }
        .tab-item.active { color: #8B5CF6; }
        .tab-item-center { background: #8B5CF6; color: white !important; width: 55px; height: 55px; border-radius: 20px; justify-content: center; transform: translateY(-5px); }

        /* PLAYER STYLING */
        audio { height: 35px; border-radius: 12px; filter: invert(1) hue-rotate(180deg) brightness(1.5); opacity: 0.9; width: 100%; margin-top: 10px; }
        .rec-card { border-left: 4px solid #8B5CF6; background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, rgba(13, 17, 23, 0.6) 100%); transition: 0.3s; }
        .rec-card:hover { border-left-color: #d946ef; transform: translateY(-2px); background: rgba(255,255,255,0.05); }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect } = React;
        const { motion, AnimatePresence } = window.Motion;

        function App() {
            const [view, setView] = useState('grabaciones');
            const [recs, setRecs] = useState([]);
            const [search, setSearch] = useState('');
            const [loading, setLoading] = useState(false);

            const fetchRecs = (query = '') => {
                setLoading(true);
                fetch(`api/index.php?action=get_recordings&q=${query}`)
                    .then(r => r.json())
                    .then(d => { setRecs(d); setLoading(false); })
                    .catch(e => setLoading(false));
            };

            useEffect(() => { fetchRecs(); }, []);

            const handleSearch = (e) => {
                const val = e.target.value;
                setSearch(val);
                fetchRecs(val);
            };

            return (
                <div className="flex h-screen w-screen bg-[#030406] overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="flex-1 page-wrapper px-6">
                            <header className="flex justify-between items-center mb-8">
                                <div><h1 className="text-3xl font-black uppercase text-white tracking-tighter">Grabaciones</h1><p className="text-[10px] text-purple-400 font-black uppercase tracking-widest italic">Archivo de Voz Infratec</p></div>
                                <div className="w-11 h-11 bg-purple-600 rounded-2xl flex items-center justify-center font-black shadow-lg shadow-purple-500/30">FG</div>
                            </header>

                            <div className="relative mb-8">
                                <span className="material-icons absolute left-4 top-3.5 text-gray-500">search</span>
                                <input type="text" placeholder="Buscar por número origen o destino..." className="w-full bg-[#161B22] border border-[#21262d] rounded-2xl py-3.5 pl-12 pr-4 text-sm focus:ring-2 focus:ring-[#8B5CF6] outline-none transition-all shadow-xl" value={search} onChange={handleSearch} />
                            </div>

                            {loading ? (
                                <div className="text-center p-20 opacity-50"><span className="material-icons animate-spin text-4xl mb-4 text-purple-500">sync</span><p className="font-bold">Sincronizando archivo...</p></div>
                            ) : (
                                <div className="space-y-4">
                                    {recs.length === 0 ? (
                                        <div className="glass-panel p-20 rounded-3xl text-center opacity-50"><p>No se encontraron grabaciones</p></div>
                                    ) : recs.map((r, i) => (
                                        <motion.div key={i} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="glass-panel rec-card rounded-2xl p-5 flex flex-col gap-3">
                                            <div className="flex justify-between items-start">
                                                <div className="flex items-center gap-4">
                                                    <div className="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400"><span className="material-icons text-xl">mic</span></div>
                                                    <div>
                                                        <div className="flex items-center gap-2"><b className="text-lg tracking-tight">#{r.src}</b><span className="material-icons text-gray-600 text-xs">arrow_forward</span><b className="text-lg tracking-tight">{r.dst}</b></div>
                                                        <div className="text-[10px] text-gray-500 font-black uppercase mt-0.5">{r.calldate}</div>
                                                    </div>
                                                </div>
                                                <div className="text-right"><div className="text-sm font-black text-white">{r.duration}s</div><div className="text-[8px] text-gray-600 font-bold uppercase">Duración</div></div>
                                            </div>
                                            <audio controls src={`/monitor/${r.recordingfile}`} preload="none"></audio>
                                        </motion.div>
                                    ))}
                                </div>
                            )}
                        </motion.main>
                    </AnimatePresence>

                    {/* TAB BAR */}
                    <nav className="tab-bar glass-panel">
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
