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
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .glass-panel { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(25px) saturate(180%); -webkit-backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: 150px; }
        
        /* TAB BAR CENTRADO CON ELEMENTO "VIVO" DESTACADO */
        .tab-bar { 
            position: fixed; bottom: 25px; left: 20px; right: 20px; 
            height: 75px; border-radius: 25px;
            display: flex; justify-content: space-around; align-items: center;
            z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6);
            padding: 0 10px;
        }

        .tab-item { color: #8b949e; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 2px; transition: 0.3s; padding: 10px; }
        .tab-item.active { color: #8B5CF6; }
        
        /* ELEMENTO VIVO (CENTRO) */
        .tab-item-center { 
            background: var(--tf-primary); color: white !important; 
            width: 55px; height: 55px; border-radius: 20px; 
            justify-content: center; box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
            transform: translateY(-5px);
        }
        .tab-item-center.active { background: #9d5ba3; }

        /* LIVE CALL CARD */
        .live-card { background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(13, 17, 23, 0.6) 100%); border-left: 4px solid #8B5CF6; position: relative; overflow: hidden; }
        .live-card::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.05), transparent); animation: sweep 3s infinite; }
        @keyframes sweep { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        
        .pulse-red { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444; animation: pulse-ring 1.5s infinite; }
        @keyframes pulse-ring { 0% { transform: scale(0.9); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.5; } 100% { transform: scale(0.9); opacity: 1; } }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect } = React;
        const { motion, AnimatePresence } = window.Motion;

        function App() {
            const [view, setView] = useState('vivo');
            const [data, setData] = useState({ pbx: { extensions: [], calls: [], queues: [], recordings: [] } });

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r => r.json()).then(d => setData(d)).catch(e => {});
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return () => clearInterval(i); }, []);

            return (
                <div className="flex h-screen w-screen bg-[#030406] overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, scale: 0.98 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 1.02 }} className="flex-1 page-wrapper px-6">
                            <header className="flex justify-between items-center mb-8">
                                <div><h1 className="text-3xl font-black uppercase text-white tracking-tighter">{view}</h1><p className="text-[10px] text-purple-400 font-black uppercase tracking-widest">Infratec Live Bridge</p></div>
                                <div className="flex items-center gap-3">
                                    <div className="flex flex-col text-right"><span className="text-xs font-black">SILDAN PBX</span><span className="text-[9px] text-green-500 font-bold">● ONLINE</span></div>
                                    <div className="w-11 h-11 bg-purple-600 rounded-2xl flex items-center justify-center font-black shadow-lg shadow-purple-500/30">FG</div>
                                </div>
                            </header>

                            {view === 'vivo' && (
                                <div className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4 mb-6">
                                        <div className="glass-panel p-5 rounded-3xl text-center"><div className="text-[10px] text-gray-500 font-black mb-1">LLAMADAS VIVAS</div><div className="text-4xl font-black text-purple-500">{data.pbx.calls.length}</div></div>
                                        <div className="glass-panel p-5 rounded-3xl text-center"><div className="text-[10px] text-gray-500 font-black mb-1">CANALES SIP</div><div className="text-4xl font-black text-white">{data.pbx.calls.length * 2}</div></div>
                                    </div>

                                    {data.pbx.calls.length === 0 ? (
                                        <div className="glass-panel p-20 rounded-3xl text-center opacity-50"><span className="material-icons text-5xl mb-4">phonelink_erase</span><p className="font-bold">No hay actividad en curso</p></div>
                                    ) : data.pbx.calls.map((c, i) => (
                                        <motion.div key={i} initial={{ x: -20, opacity: 0 }} animate={{ x: 0, opacity: 1 }} className="glass-panel live-card rounded-2xl p-6 flex items-center justify-between">
                                            <div className="flex items-center gap-6">
                                                <div className="pulse-red"></div>
                                                <div>
                                                    <div className="flex items-center gap-3"><b className="text-2xl tracking-tighter">#{c.from}</b><span className="material-icons text-gray-600 text-sm">arrow_forward</span><b className="text-2xl tracking-tighter">{c.to}</b></div>
                                                    <div className="text-[10px] text-gray-500 font-black uppercase tracking-widest mt-1">Conexión Punto a Punto</div>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-xl font-black text-purple-400 font-mono tracking-tighter">{c.duration}</div>
                                                <div className="text-[9px] text-gray-600 font-black uppercase mt-1">Tiempo de Conversación</div>
                                            </div>
                                        </motion.div>
                                    ))}
                                </div>
                            )}

                            {/* Otros módulos se mantienen simplificados aquí para la v13.4 */}
                        </motion.main>
                    </AnimatePresence>

                    {/* GLASS TAB BAR CON ELEMENTO "VIVO" CENTRADO */}
                    <nav className="tab-bar glass-panel">
                        <div className={`tab-item ${view==='dashboard'?'active':''}`} onClick={()=>setView('dashboard')}><span className="material-icons">grid_view</span><span className="text-[8px] font-black uppercase">Dash</span></div>
                        <div className={`tab-item ${view==='extensiones'?'active':''}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span><span className="text-[8px] font-black uppercase">Exts</span></div>
                        
                        <div className={`tab-item tab-item-center ${view==='vivo'?'active':''}`} onClick={()=>setView('vivo')}>
                            <span className="material-icons" style={{fontSize:30}}>sensors</span>
                        </div>

                        <div className={`tab-item ${view==='callcenter'?'active':''}`} onClick={()=>setView('callcenter')}><span className="material-icons">headset_mic</span><span className="text-[8px] font-black uppercase">Call</span></div>
                        <div className={`tab-item ${view==='grabaciones'?'active':''}`} onClick={()=>setView('grabaciones')}><span className="material-icons">mic</span><span className="text-[8px] font-black uppercase">Recs</span></div>
                    </nav>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
