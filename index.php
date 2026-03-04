<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Teleflow Pro v9.4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .sidebar { background-color: #0d1117; border-right: 1px solid #21262d; width: 260px; height: 100vh; position: fixed; z-index: 100; transition: 0.3s; }
        .sidebar.collapsed { width: 80px; }
        .main-content { margin-left: 260px; height: 100vh; transition: 0.3s; padding: 40px; overflow-y: auto; }
        .sidebar.collapsed + .main-content { margin-left: 80px; }
        
        .nav-item { padding: 12px 16px; margin: 4px 12px; border-radius: 12px; cursor: pointer; color: #8b949e; display: flex; align-items: center; gap: 14px; transition: 0.2s; font-weight: 600; }
        .nav-item.active { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2); }
        .nav-item:hover:not(.active) { background: rgba(255,255,255,0.03); color: #fff; }

        /* TANK TABLE STYLE */
        .tf-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .tr-pro { background: #0d1117; border: 1px solid #21262d; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
        .tr-pro:hover { transform: scale(1.005); border-color: #8b5cf6; background: #161b22; }
        .tr-pro td { padding: 18px 24px; border-top: 1px solid #21262d; border-bottom: 1px solid #21262d; }
        .tr-pro td:first-child { border-left: 1px solid #21262d; border-radius: 14px 0 0 14px; }
        .tr-pro td:last-child { border-right: 1px solid #21262d; border-radius: 0 14px 14px 0; }

        /* DRAWER APPLE STYLE */
        .drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); z-index: 2000; opacity: 0; pointer-events: none; transition: 0.4s; }
        .drawer-overlay.open { opacity: 1; pointer-events: auto; }
        .drawer { position: fixed; top: 0; right: 0; width: 480px; height: 100vh; background: #0d1117; border-left: 1px solid #21262d; z-index: 2001; transform: translateX(100%); transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); padding: 40px; }
        .drawer.open { transform: translateX(0); box-shadow: -20px 0 60px rgba(0,0,0,0.5); }

        .input-dark { width: 100%; padding: 12px 16px; border-radius: 10px; background: #030406; border: 1px solid #21262d; color: #fff; margin-bottom: 20px; outline: none; }
        .input-dark:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .switch-row { background: rgba(255,255,255,0.02); padding: 14px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border: 1px solid #21262d; }

        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; border: 1px solid currentColor; }
        .online { color: #22c55e; } .busy { color: #f59e0b; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(210, 153, 34, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(210, 153, 34, 0); } }
    </style>
</head>
<body>
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { ReactFlow, Background } = window.ReactFlow;

        function App() {
            const [view, setView] = useState('dashboard');
            const [collapsed, setCollapsed] = useState(false);
            const [data, setData] = useState({ system:{}, pbx:{extensions:[], calls:[]} });
            const [selected, setSelected] = useState(null);
            const [search, setSearch] = useState('');

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res=>setData(res));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const flowElements = useMemo(() => {
                const nodes = [{ id:'core', data:{label:'SIP CORE'}, position:{x:0,y:0}, style:{background:'#714B67',color:'#fff',borderRadius:'50%',width:100,height:100,display:'flex',alignItems:'center',justifyContent:'center',fontWeight:900,border:'2px solid #fff'} }];
                const edges = [];
                data.pbx.extensions.forEach((e,i) => {
                    const a = (i / (data.pbx.extensions.length || 1)) * 2 * Math.PI;
                    nodes.push({ id:e.ext, data:{label:e.ext}, position:{x:350*Math.cos(a),y:350*Math.sin(a)}, style:{background:e.status==='ONLINE'?'#238636':'#21262d',color:'#fff',fontSize:'9px',width:50,borderRadius:'8px'} });
                    edges.push({ id:`e-${e.ext}`, source:'core', target:e.ext, type: 'straight', animated:e.status!=='OFFLINE' });
                });
                return { nodes, edges };
            }, [data.pbx.extensions]);

            return (
                <div className="flex">
                    <aside className={`sidebar ${collapsed?'collapsed':''}`}>
                        <div className="p-8 mb-4">
                            <div className="flex items-center gap-3">
                                {!collapsed && <h2 className="text-2xl font-black text-[#8B5CF6] tracking-tighter">Teleflow</h2>}
                                <div className="w-8 h-8 bg-[#8B5CF6] rounded-lg flex items-center justify-center shadow-lg"><i className="fa fa-wave-square text-white text-xs"></i></div>
                            </div>
                        </div>
                        <nav>
                            <NavItem icon="dashboard" label="Dashboard" active={view==='dashboard'} collapsed={collapsed} onClick={()=>setView('dashboard')} />
                            <NavItem icon="people" label="Extensiones" active={view==='extensiones'} collapsed={collapsed} onClick={()=>setView('extensiones')} />
                        </nav>
                    </aside>

                    <main className="main-content flex-1">
                        <header className="flex justify-between items-center mb-12">
                            <h1 className="text-3xl font-black uppercase tracking-tighter">{view}</h1>
                            <div className="flex items-center gap-4">
                                <div className="text-right"><div className="text-sm font-black">Flavio González</div><div className="text-[10px] text-gray-500 font-bold uppercase">Administrator</div></div>
                                <div className="w-12 h-12 bg-purple-600 rounded-2xl flex items-center justify-center font-black shadow-xl shadow-purple-500/20">FG</div>
                            </div>
                        </header>

                        {view === 'dashboard' ? (
                            <div className="h-[650px] bg-black/40 rounded-3xl border border-white/5 overflow-hidden shadow-2xl">
                                <ReactFlow nodes={flowElements.nodes} edges={flowElements.edges} fitView><Background color="#111" gap={25}/></ReactFlow>
                            </div>
                        ) : (
                            <div className="fade-in">
                                <div className="flex justify-between mb-8">
                                    <input type="text" placeholder="🔍 Buscar por interno o nombre..." className="w-1/2 bg-[#0d1117] border border-[#21262d] rounded-2xl p-4 text-sm outline-none focus:ring-2 focus:ring-purple-600" onChange={e=>setSearch(e.target.value)} />
                                    <button className="bg-purple-600 text-white px-8 rounded-2xl font-black shadow-lg shadow-purple-500/10 hover:scale-105 transition-all">+ NUEVA EXTENSIÓN</button>
                                </div>
                                <table className="tf-table">
                                    <thead><tr className="text-[10px] text-gray-500 font-black uppercase tracking-widest text-left"><th></th><th>Interno</th><th>Usuario</th><th>IP Origen</th><th>Latencia</th><th>Estado</th></tr></thead>
                                    <tbody>
                                        {data.pbx.extensions.filter(e=>e.ext.includes(search)||e.name.toLowerCase().includes(search.toLowerCase())).map(e => (
                                            <tr key={e.ext} className="tr-pro" onClick={()=>setSelected(e)}>
                                                <td className="w-16"><div className="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center"><i className={`fa ${e.device_type==='softphone'?'fa-laptop-code':'fa-phone'} text-gray-600`}></i></div></td>
                                                <td><b className="text-lg font-black">#{e.ext}</b></td>
                                                <td><div className="flex items-center gap-3"><img src={e.avatar} className="w-8 h-8 rounded-full border border-white/10" /><b>{e.name}</b></div></td>
                                                <td><code className="text-red-400/80 font-bold">{e.ip}</code></td>
                                                <td><b className="text-purple-400">{e.rtt}</b></td>
                                                <td><span className={`status-pill ${e.status}`}>{e.status}</span></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </main>

                    {/* DRAWER APPLE STYLE */}
                    <div className={`drawer-overlay ${selected?'open':''}`} onClick={()=>setSelected(null)}></div>
                    <div className={`drawer ${selected?'open':''}`}>
                        {selected && (
                            <div className="h-full flex flex-col">
                                <div className="flex justify-between items-center mb-10">
                                    <h2 className="text-2xl font-black tracking-tighter">Ficha Técnica</h2>
                                    <span className="material-icons cursor-pointer text-gray-600 hover:text-white" onClick={()=>setSelected(null)}>close</span>
                                </div>
                                <div className="text-center mb-8">
                                    <div className="relative inline-block group">
                                        <img src={selected.avatar} className="w-24 h-24 rounded-[30px] border-4 border-purple-600 shadow-2xl transition-transform group-hover:scale-105" />
                                        <label htmlFor="avatar-up" className="absolute bottom-0 right-0 bg-purple-600 p-2 rounded-xl shadow-lg cursor-pointer hover:scale-110"><span className="material-icons text-white text-sm">photo_camera</span></label>
                                        <input type="file" id="avatar-up" hidden />
                                    </div>
                                    <h4 className="mt-4 font-black">Interno #{selected.ext}</h4>
                                    <p className="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{selected.name}</p>
                                </div>
                                <div className="flex-1">
                                    <label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Nombre de Mostrar</label>
                                    <input type="text" className="input-dark" defaultValue={selected.name} />
                                    <label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Password SIP (Secret)</label>
                                    <input type="password" placeholder="••••••••" className="input-dark" />
                                    <div className="switch-row"><b>Habilitar Video</b><input type="checkbox" defaultChecked={selected.use_video} className="w-5 h-5 accent-purple-600" /></div>
                                    <div className="switch-row"><b>Apertura Puertas (DTMF)</b><input type="checkbox" defaultChecked={selected.open_doors} className="w-5 h-5 accent-purple-600" /></div>
                                </div>
                                <button className="w-full bg-purple-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-purple-500/20 hover:scale-[1.02] transition-transform" onClick={()=>setSelected(null)}>GUARDAR CAMBIOS</button>
                            </div>
                        )}
                    </div>
                </div>
            );
        }

        function NavItem({ icon, label, active, collapsed, onClick }) {
            return (
                <div onClick={onClick} className={`nav-item ${active?'active':''}`}>
                    <span className="material-icons">{icon}</span>{!collapsed && <span>{label}</span>}
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
