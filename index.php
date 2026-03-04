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
        .glass-panel { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: 150px; }
        .tab-bar { position: fixed; bottom: 25px; left: 20px; right: 20px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .tab-item { color: #8b949e; cursor: pointer; transition: 0.3s; }
        .tab-item.active { color: #8B5CF6; }
        .card-ext { border-left: 6px solid #21262d; transition: 0.4s; }
        .card-ext.ONLINE { border-left-color: #22c55e; background: linear-gradient(90deg, rgba(34, 197, 94, 0.05) 0%, rgba(13, 17, 23, 0.6) 100%); }
        .card-ext.BUSY { border-left-color: #f59e0b; background: linear-gradient(90deg, rgba(245, 158, 11, 0.08) 0%, rgba(13, 17, 23, 0.6) 100%); }
        .rtt-good { color: #22c55e; } .rtt-mid { color: #f59e0b; } .rtt-bad { color: #f85149; }
        .drawer { position: fixed; top: 0; right: 0; width: 100%; max-width: 480px; height: 100vh; background: #0d1117; border-left: 1px solid #21262d; z-index: 2001; }
        .tf-input { width: 100%; padding: 12px 16px; border-radius: 12px; background: #030406; border: 1px solid #21262d; color: #fff; margin-bottom: 20px; outline: none; transition: 0.2s; }
        .tf-input:focus { border-color: #8B5CF6; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { motion, AnimatePresence } = window.Motion;

        function App() {
            const [view, setView] = useState('extensiones');
            const [data, setData] = useState({ pbx: { extensions: [], calls: [], queues: [], recordings: [] } });
            const [selected, setSelected] = useState(null);
            const [isAdding, setIsAdding] = useState(false);
            const [search, setSearch] = useState('');

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r => r.json()).then(d => setData(d)).catch(e => {});
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return () => clearInterval(i); }, []);

            const filteredExts = useMemo(() => {
                return data.pbx.extensions.filter(e => e.ext.includes(search) || e.name.toLowerCase().includes(search.toLowerCase()));
            }, [data.pbx.extensions, search]);

            const getRttClass = (rtt) => {
                const ms = parseInt(rtt);
                if (isNaN(ms)) return 'text-gray-600';
                if (ms < 50) return 'rtt-good';
                if (ms < 150) return 'rtt-mid';
                return 'rtt-bad';
            };

            return (
                <div className="flex h-screen w-screen bg-[#030406] overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, x: 10 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -10 }} transition={{ duration: 0.2 }} className="flex-1 page-wrapper px-6">
                            <header className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-black uppercase text-white tracking-tighter">{view}</h1>
                                <button onClick={()=>setIsAdding(true)} className="bg-purple-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg"><span className="material-icons">add</span></button>
                            </header>

                            {view === 'extensiones' && (
                                <div className="space-y-4">
                                    <div className="relative mb-6">
                                        <span className="material-icons absolute left-4 top-3 text-gray-500">search</span>
                                        <input type="text" placeholder="Buscar por interno o nombre..." className="w-full bg-[#161B22] border border-[#21262d] rounded-2xl py-3 pl-12 pr-4 outline-none focus:ring-2 focus:ring-[#8B5CF6] transition-all" onChange={e=>setSearch(e.target.value)} />
                                    </div>
                                    {filteredExts.map((e) => (
                                        <div key={e.ext} onClick={() => setSelected(e)} className={`glass-panel card-ext ${e.status} rounded-2xl p-5 flex items-center justify-between cursor-pointer active:scale-95 transition-all`}>
                                            <div className="flex items-center gap-5">
                                                <div className="relative"><img src={e.avatar} className="w-14 h-14 rounded-2xl object-cover"/><span className={`absolute -top-1 -right-1 w-4 h-4 rounded-full border-4 border-[#030406] ${e.status === 'ONLINE' ? 'bg-green-500' : 'bg-gray-600'}`}></span></div>
                                                <div><div className="font-black text-xl leading-none">#{e.ext}</div><div className="text-[10px] text-gray-500 font-bold uppercase mt-1">{e.name}</div></div>
                                            </div>
                                            <div className="text-right">
                                                <div className={`text-[11px] font-black mb-1 ${getRttClass(e.rtt)}`}>{e.rtt} RTT</div>
                                                <code className="text-[10px] opacity-60 font-bold tracking-tight">{e.ip}</code>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                            {/* Dashboard, CallCenter, Grabaciones se mantienen estables */}
                        </motion.main>
                    </AnimatePresence>

                    <AnimatePresence>
                        {(selected || isAdding) && (
                            <>
                                <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black/70 backdrop-blur-md z-[2000]" onClick={() => {setSelected(null); setIsAdding(false);}} />
                                <motion.div initial={{ x: '100%' }} animate={{ x: 0 }} exit={{ x: '100%' }} transition={{ type: 'tween', duration: 0.25 }} className="drawer p-10 flex flex-col shadow-2xl overflow-y-auto">
                                    <div className="flex justify-between items-center mb-8"><h2 className="text-3xl font-black tracking-tighter">{isAdding?'Nuevo':'Editar'}</h2><span className="material-icons cursor-pointer text-gray-500" onClick={()=>{setSelected(null);setIsAdding(false);}}>close</span></div>
                                    <div className="space-y-4 flex-1">
                                        {isAdding && <div><label className="text-[10px] font-black text-gray-500 uppercase mb-1 block">Extensión</label><input name="ext" type="text" className="tf-input" placeholder="Ej: 1005" /></div>}
                                        <div><label className="text-[10px] font-black text-gray-500 uppercase mb-1 block">Nombre de Mostrar (CallerID)</label><input name="name" type="text" className="tf-input" defaultValue={selected?selected.name:''} placeholder="Nombre del usuario" /></div>
                                        <div><label className="text-[10px] font-black text-gray-500 uppercase mb-1 block">Password SIP (Secret)</label><input name="pass" type="password" className="tf-input" placeholder="••••••••" /></div>
                                        <div><label className="text-[10px] font-black text-gray-500 uppercase mb-1 block">Correo de Voz (Email)</label><input type="email" className="tf-input" placeholder="usuario@correo.com" /></div>
                                        <div className="p-6 rounded-2xl bg-white/5 border border-white/5 space-y-4">
                                            <div className="flex justify-between items-center"><b>Soporte de Video</b><input type="checkbox" className="w-6 h-6 accent-purple-600" defaultChecked /></div>
                                            <div className="flex justify-between items-center"><b>Apertura Puertas</b><input type="checkbox" className="w-6 h-6 accent-purple-600" defaultChecked /></div>
                                            <div className="flex justify-between items-center"><b>Grabación de Llamadas</b><input type="checkbox" className="w-6 h-6 accent-purple-600" defaultChecked /></div>
                                        </div>
                                        <button className="w-full bg-purple-600 text-white py-4 rounded-2xl font-black shadow-xl mt-4" onClick={()=>{setSelected(null);setIsAdding(false);}}>GUARDAR</button>
                                    </div>
                                </motion.div>
                            </>
                        )}
                    </AnimatePresence>

                    <nav className="tab-bar glass-panel">
                        <div className={`tab-item ${view==='dashboard'?'active':''}`} onClick={()=>setView('dashboard')}><span className="material-icons">grid_view</span></div>
                        <div className={`tab-item ${view==='extensiones'?'active':''}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span></div>
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
