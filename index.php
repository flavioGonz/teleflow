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
        .glass { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .page-wrapper { height: 100vh; overflow-y: auto; padding-bottom: 120px; }
        .tab-bar { position: fixed; bottom: 20px; left: 15px; right: 15px; height: 75px; border-radius: 25px; display: flex; justify-content: space-around; align-items: center; z-index: 1000; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .st-pill { padding: 4px 12px; border-radius: 20px; font-size: 9px; font-weight: 800; text-transform: uppercase; border: 1px solid currentColor; }
        .ONLINE { color: #22c55e; } .BUSY { color: #f59e0b; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        audio { height: 35px; border-radius: 10px; filter: invert(1) hue-rotate(180deg); width: 100%; opacity: 0.8; }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) { window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = () => {}; window.__REACT_DEVTOOLS_GLOBAL_HOOK__.inject = () => {}; }
        const { useState, useEffect, useMemo } = React;
        const { motion, AnimatePresence } = window.Motion;
        const RF = window.ReactFlow;

        function App() {
            const [view, setView] = useState('dashboard');
            const [isDark, setIsDark] = useState(true);
            const [search, setSearch] = useState('');
            const [selected, setSelected] = useState(null);
            const [data, setData] = useState({ pbx: { extensions: [], calls: [], queues: [], recordings: [] }, system: {}, summary: {} });

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r => r.json()).then(d => setData(d));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return () => clearInterval(i); }, []);
            useEffect(() => { document.body.className = isDark ? 'dark' : 'light-mode bg-gray-50 text-slate-900'; }, [isDark]);

            const flowData = useMemo(() => {
                const nodes = [{ id:'core', data:{label:'SIP CORE'}, position:{x:0, y:0}, style:{background:'#714B67',color:'#fff',borderRadius:'50%',width:120,height:120,display:'flex',alignItems:'center',justifyContent:'center',fontWeight:900,border:'3px solid #fff'} }];
                const edges = [];
                data.pbx.extensions.slice(0, 15).forEach((e,i) => {
                    const a = (i/15)*2*Math.PI;
                    nodes.push({ id:e.ext, data:{label:e.ext}, position:{x:450*Math.cos(a),y:350*Math.sin(a)}, style:{background:e.status==='ONLINE'?'#238636':'#21262d',color:'#fff',fontSize:'10px',width:60,borderRadius:'8px'} });
                    edges.push({ id:`e-${e.ext}`, source:'core', target:e.ext, type:'straight', animated:e.status==='BUSY', style:{stroke: e.status==='ONLINE'?'#238636':'#30363d'} });
                });
                return { nodes, edges };
            }, [data.pbx.extensions]);

            return (
                <div className="flex h-screen w-screen overflow-hidden">
                    <AnimatePresence mode="wait">
                        <motion.main key={view} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -15 }} className="flex-1 page-wrapper px-8 pt-10">
                            <header className="flex justify-between items-center mb-10">
                                <div><h1 className="text-3xl font-black uppercase tracking-tighter">{view}</h1></div>
                                <div className="flex items-center gap-3">
                                    <button onClick={()=>setIsDark(!isDark)} className="w-11 h-11 glass rounded-2xl flex items-center justify-center"><span className="material-icons">{isDark?'light_mode':'dark_mode'}</span></button>
                                    <div className="bg-[#8B5CF6] text-white w-11 h-11 flex items-center justify-center rounded-xl font-black shadow-lg">FG</div>
                                </div>
                            </header>

                            {view === 'dashboard' && (
                                <div className="h-[650px] glass rounded-[30px] overflow-hidden shadow-2xl relative">
                                    <RF.ReactFlow nodes={flowData.nodes} edges={flowData.edges} fitView><RF.Background color={isDark?"#111":"#eee"} /></RF.ReactFlow>
                                </div>
                            )}

                            {view === 'extensiones' && (
                                <div className="space-y-4">
                                    <div className="flex justify-center mb-8"><input type="text" placeholder="🔍 Buscar..." className={`w-full max-w-xl py-3.5 px-6 rounded-2xl outline-none focus:ring-2 focus:ring-purple-600 ${isDark?'bg-[#161B22] text-white':'bg-white text-black border border-gray-200'}`} onChange={e=>setSearch(e.target.value)} /></div>
                                    {data.pbx.extensions.filter(e=>e.ext.includes(search)||e.name.toLowerCase().includes(search.toLowerCase())).map(e => (
                                        <div key={e.ext} className={`p-5 flex items-center justify-between rounded-2xl border ${isDark?'bg-[#161B22] border-[#21262d]':'bg-white border-gray-100 shadow-sm'} transition-all cursor-pointer`} onClick={()=>setSelected(e)}>
                                            <div className="flex items-center gap-4"><img src={e.avatar} className="w-12 h-12 rounded-xl object-cover"/><div className="font-black text-lg">#{e.ext}<br/><span className="text-[10px] text-gray-500 uppercase">{e.name}</span></div></div>
                                            <div className="text-right"><span className={`st-pill ${e.status} block mb-1`}>{e.status}</span><code className="text-[10px] text-purple-400 font-bold">{e.rtt}</code></div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {view === 'callcenter' && (
                                <div className="grid grid-cols-2 gap-6">
                                    {data.pbx.queues.map(q => (
                                        <div key={q.name} className="glass p-8 rounded-3xl text-center border-t-4 border-t-purple-600">
                                            <div className="text-[12px] font-black text-gray-500 mb-4 uppercase">{q.name}</div>
                                            <div className="text-6xl font-black text-white">{q.waiting}</div>
                                        </div>
                                    ))}
                                </div>
                            )}

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

                    <nav className={`tab-bar glass ${isDark?'':'bg-white/90 border-gray-200'}`}>
                        <NavItem icon="grid_view" active={view==='dashboard'} onClick={()=>setView('dashboard')} />
                        <NavItem icon="people" active={view==='extensiones'} onClick={()=>setView('extensiones')} />
                        <div className="tab-item-center" onClick={()=>setView('vivo')}><span className="material-icons text-3xl">sensors</span></div>
                        <NavItem icon="headset_mic" active={view==='callcenter'} onClick={()=>setView('callcenter')} />
                        <NavItem icon="mic" active={view==='grabaciones'} onClick={()=>setView('grabaciones')} />
                    </nav>

                    {selected && (
                        <div className="fixed inset-0 bg-black/60 backdrop-blur-md z-[2000] flex items-center justify-center" onClick={()=>setSelected(null)}>
                            <div className={`w-[450px] p-10 rounded-2xl border ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-gray-200 shadow-2xl'}`} onClick={e=>e.stopPropagation()}>
                                <h2 className="text-2xl font-black mb-8 italic">Editar Extensión {selected.ext}</h2>
                                <label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Nombre</label>
                                <input type="text" className={`w-full p-3 rounded-xl mb-6 outline-none ${isDark?'bg-[#0B0E14] text-white border-gray-800':'bg-gray-50 text-black border-gray-200'}`} defaultValue={selected.name} />
                                <button className="w-full bg-[#8B5CF6] text-white py-4 rounded-xl font-black shadow-xl" onClick={()=>setSelected(null)}>GUARDAR</button>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function NavItem({ icon, active, onClick }) {
            return (
                <div onClick={onClick} className={`tab-item ${active?'active':''}`}>
                    <span className="material-icons text-2xl">{icon}</span>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
