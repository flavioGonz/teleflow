<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Teleflow Pro v10</title>
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
        .main-content { margin-left: 260px; height: 100vh; transition: 0.3s; position: relative; }
        .sidebar.collapsed + .main-content { margin-left: 80px; }
        
        /* TURBO FLOW STYLES (REACT FLOW) */
        .react-flow__edge-path { stroke: url(#edge-gradient); stroke-width: 2.5; stroke-opacity: 0.6; }
        .react-flow__edge.animated path { stroke-dasharray: 8; animation: dash 1s linear infinite; stroke-opacity: 1; stroke-width: 3; }
        @keyframes dash { from { stroke-dashoffset: 16; } to { stroke-dashoffset: 0; } }
        
        .node-turbo-core { background: #714B67; color: #fff; width: 140px; height: 140px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 80px rgba(113, 75, 103, 0.4); border: 4px solid #fff; position: relative; z-index: 10; }
        .node-turbo-ext { background: rgba(22, 27, 34, 0.6); border: 1px solid #30363d; border-radius: 12px; padding: 12px; width: 180px; color: #fff; backdrop-filter: blur(15px); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .node-turbo-ext.active { border-color: #8b5cf6; box-shadow: 0 0 20px rgba(139, 92, 246, 0.3); }

        /* DRAWER APPLE STYLE */
        .drawer { position: fixed; top: 0; right: 0; width: 480px; height: 100vh; background: #0d1117; border-left: 1px solid #21262d; z-index: 2001; transform: translateX(100%); transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); padding: 40px; }
        .drawer.open { transform: translateX(0); box-shadow: -20px 0 80px rgba(0,0,0,0.6); }
        .drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 2000; opacity: 0; pointer-events: none; transition: 0.4s; }
        .drawer-overlay.open { opacity: 1; pointer-events: auto; }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { ReactFlow, Background, Controls } = window.ReactFlow;

        const CoreNode = () => (
            <div className="node-turbo-core">
                <span className="material-icons text-5xl mb-1">dns</span>
                <b className="text-xs uppercase tracking-tighter">SIP CORE</b>
            </div>
        );

        const ExtensionNode = ({ data }) => (
            <div className={`node-turbo-ext ${data.status !== 'OFFLINE' ? 'active' : ''}`}>
                <div className="flex items-center gap-3">
                    <img src={data.avatar} className="w-10 h-10 rounded-xl object-cover border border-white/10" />
                    <div>
                        <div className="text-[13px] font-black leading-none">#{data.ext}</div>
                        <div className="text-[9px] text-gray-500 font-bold uppercase truncate w-24 mt-1">{data.name}</div>
                    </div>
                </div>
                <div className="mt-3 pt-2 border-t border-white/5 flex justify-between">
                    <span className="text-[8px] font-mono text-purple-400">{data.ip}</span>
                    <span className={`text-[8px] font-black ${data.status === 'ONLINE' ? 'text-green-500' : 'text-gray-600'}`}>{data.status}</span>
                </div>
            </div>
        );

        const nodeTypes = { core: CoreNode, extension: ExtensionNode };

        function App() {
            const [view, setView] = useState('dashboard');
            const [data, setData] = useState({ system:{}, pbx:{extensions:[], calls:[]} });
            const [selectedExt, setSelectedExt] = useState(null);

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res=>setData(res));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const flowElements = useMemo(() => {
                const nodes = [{ id:'core', type:'core', position:{x:0, y:0}, draggable: false }];
                const edges = [];
                data.pbx.extensions.forEach((e,i) => {
                    const angle = (i / (data.pbx.extensions.length || 1)) * 2 * Math.PI;
                    nodes.push({ id:e.ext, type:'extension', data:e, position:{x:600*Math.cos(angle), y:450*Math.sin(angle)} });
                    edges.push({ id:`e-${e.ext}`, source:'core', target:e.ext, type:'straight', animated:e.status==='BUSY', style:{ stroke: e.status==='ONLINE'?'#238636':(e.status==='BUSY'?'#f59e0b':'#21262d'), strokeWidth: e.status==='OFFLINE'?1:3 } });
                });
                return { nodes, edges };
            }, [data.pbx.extensions]);

            return (
                <div className="flex h-screen w-screen overflow-hidden bg-[#030406]">
                    <svg style={{ position: 'absolute', width: 0, height: 0 }}>
                        <defs>
                            <linearGradient id="edge-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stopColor="#714B67" />
                                <stop offset="100%" stopColor="#8b5cf6" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <aside className="sidebar p-6 flex flex-col z-[100]">
                        <div className="p-4 mb-10"><h2 className="text-2xl font-black text-[#8B5CF6] tracking-tighter">Teleflow</h2></div>
                        <nav className="space-y-4">
                            <div className={`flex items-center gap-4 px-4 py-3 rounded-2xl cursor-pointer ${view==='dashboard'?'bg-purple-500/10 text-purple-400':'text-gray-500'}`} onClick={()=>setView('dashboard')}><span className="material-icons">dashboard</span><b>DASHBOARD</b></div>
                            <div className={`flex items-center gap-4 px-4 py-3 rounded-2xl cursor-pointer ${view==='extensiones'?'bg-purple-500/10 text-purple-400':'text-gray-500'}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span><b>EXTENSIONES</b></div>
                            <div className={`flex items-center gap-4 px-4 py-3 rounded-2xl cursor-pointer ${view==='callcenter'?'bg-purple-500/10 text-purple-400':'text-gray-500'}`} onClick={()=>setView('callcenter')}><span className="material-icons">headset_mic</span><b>CALLCENTER</b></div>
                        </nav>
                    </aside>

                    <main className="main-content flex-1 bg-[#030406]">
                        {view === 'dashboard' ? (
                            <div className="w-full h-full relative">
                                <div className="absolute top-10 left-10 z-10 bg-[#161B22]/70 backdrop-blur-xl p-6 rounded-3xl border border-white/5 shadow-2xl">
                                    <div className="flex gap-10">
                                        <div><div className="text-2xl font-black">{data.system.cpu}%</div><div className="text-[9px] font-black text-gray-500 uppercase tracking-widest">CPU LOAD</div></div>
                                        <div><div className="text-2xl font-black">{data.system.ram}%</div><div className="text-[9px] font-black text-gray-500 uppercase tracking-widest">RAM USAGE</div></div>
                                    </div>
                                </div>
                                <ReactFlow nodes={flowElements.nodes} edges={flowElements.edges} nodeTypes={nodeTypes} fitView>
                                    <Background color="#111" gap={30} />
                                </ReactFlow>
                            </div>
                        ) : (
                            <div className="p-12">
                                <h1 className="text-4xl font-black mb-12 uppercase tracking-tighter">Gestión de Extensiones</h1>
                                <div className="space-y-3">
                                    {data.pbx.extensions.map(e => (
                                        <div key={e.ext} className="bg-[#0d1117] border border-[#21262d] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500 transition-all cursor-pointer" onClick={()=>setSelectedExt(e)}>
                                            <div className="flex items-center gap-6"><img src={e.avatar} className="w-12 h-12 rounded-xl object-cover border border-white/5"/><b className="text-xl font-black">#{e.ext}</b><span className="text-gray-400 font-semibold">{e.name}</span></div>
                                            <div className="flex items-center gap-12"><span className={`px-4 py-1.5 rounded-full text-[10px] font-black uppercase ${e.status==='ONLINE'?'bg-green-500/10 text-green-400':'bg-gray-500/10 text-gray-500'}`}>{e.status}</span><code className="text-purple-400/80 font-bold">{e.ip}</code></div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </main>

                    {/* DRAWER EDICIÓN */}
                    <div className={`drawer-overlay ${selectedExt?'open':''}`} onClick={()=>setSelectedExt(null)}></div>
                    <div className={`drawer ${selectedExt?'open':''}`}>
                        {selectedExt && (
                            <div className="h-full flex flex-col">
                                <div className="flex justify-between items-center mb-12">
                                    <h2 className="text-3xl font-black tracking-tighter">Editar Extensión</h2>
                                    <span className="material-icons cursor-pointer text-gray-600 hover:text-white" onClick={()=>setSelectedExt(null)}>close</span>
                                </div>
                                <div className="flex flex-col items-center mb-10">
                                    <div className="relative group"><img src={selectedExt.avatar} className="w-28 h-28 rounded-[35px] border-4 border-purple-600 shadow-2xl transition-transform group-hover:scale-105"/><label className="absolute bottom-0 right-0 bg-purple-600 p-2.5 rounded-xl cursor-pointer shadow-lg"><span className="material-icons text-white text-sm">photo_camera</span><input type="file" className="hidden"/></label></div>
                                    <h3 className="mt-5 font-black text-xl">#{selectedExt.ext}</h3>
                                </div>
                                <div className="flex-1 space-y-6">
                                    <div><label className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nombre de Mostrar</label><input type="text" className="w-full bg-[#030406] border border-[#21262d] rounded-xl p-4 text-sm outline-none focus:border-purple-600 transition-all" defaultValue={selectedExt.name}/></div>
                                    <div><label className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Password SIP (Secret)</label><input type="password" placeholder="••••••••" className="w-full bg-[#030406] border border-[#21262d] rounded-xl p-4 text-sm outline-none focus:border-purple-600 transition-all"/></div>
                                </div>
                                <button className="w-full bg-purple-600 text-white py-5 rounded-2xl font-black shadow-2xl shadow-purple-500/20 hover:scale-[1.02] transition-transform" onClick={()=>setSelectedExt(null)}>GUARDAR CONFIGURACIÓN</button>
                            </div>
                        )}
                    </div>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
