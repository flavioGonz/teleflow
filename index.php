<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Teleflow Pro v9.3</title>
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
        .sidebar { background-color: #0d1117; border-right: 1px solid #21262d; }
        
        /* REACT FLOW CUSTOM STYLES */
        .react-flow__edge-path { stroke-width: 2; transition: stroke 0.5s ease, stroke-width 0.5s ease; }
        
        /* CORE NODE */
        .node-core { background: #714B67; color: #fff; width: 140px; height: 140px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 60px rgba(113, 75, 103, 0.4); border: 4px solid #fff; position: relative; z-index: 10; }
        .pulse-core { animation: p-core 4s infinite cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes p-core { 0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(113, 75, 103, 0.3); } 50% { transform: scale(1.08); box-shadow: 0 0 80px rgba(113, 75, 103, 0.6); } }

        /* EXTENSION NODE */
        .node-ext { background: rgba(22, 27, 34, 0.85); border: 1px solid #30363d; border-radius: 14px; padding: 10px; width: 160px; color: #fff; backdrop-filter: blur(12px); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .node-ext.busy { border-color: #f59e0b; box-shadow: 0 0 20px rgba(245, 158, 11, 0.2); }
        .node-ext.online { border-color: #238636; }
        
        .glass-panel { background: rgba(13, 17, 23, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; }
        
        /* OVERRIDES REACT FLOW */
        .react-flow__background { background: #030406; }
        .react-flow__handle { opacity: 0; } /* Escondemos los puntos de conexión para un look más limpio */
    </style>
</head>
<body>
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const { ReactFlow, Background, Controls } = window.ReactFlow;

        const CoreNode = () => (
            <div className="node-core pulse-core">
                <span className="material-icons text-4xl mb-1">dns</span>
                <b className="text-xs font-black uppercase tracking-widest">SIP CORE</b>
                <small className="text-[8px] opacity-60 font-bold">INFRATEC PBX</small>
            </div>
        );

        const ExtensionNode = ({ data }) => (
            <div className={`node-ext transition-all duration-500 ${data.status === 'BUSY' ? 'busy' : (data.status === 'ONLINE' ? 'online' : '')}`}>
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <img src={data.avatar} className="w-10 h-10 rounded-xl object-cover border border-white/10" />
                        {data.status !== 'OFFLINE' && (
                            <span className={`absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-[#161B22] ${data.status === 'BUSY' ? 'bg-yellow-500 animate-pulse' : 'bg-green-500'}`}></span>
                        )}
                    </div>
                    <div className="leading-tight">
                        <div className="text-sm font-black">#{data.ext}</div>
                        <div className="text-[9px] text-gray-500 font-bold uppercase truncate w-24">{data.name}</div>
                    </div>
                </div>
            </div>
        );

        const nodeTypes = { core: CoreNode, extension: ExtensionNode };

        function App() {
            const [view, setView] = useState('dashboard');
            const [data, setData] = useState({ system:{cpu:0, ram:0}, pbx:{extensions:[], calls:[], recordings:[]} });

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res=>setData(res));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const flowElements = useMemo(() => {
                const nodes = [{ id: 'core', type: 'core', position: { x: 0, y: 0 }, draggable: false }];
                const edges = [];
                
                if (!data.pbx.extensions) return { nodes, edges };

                data.pbx.extensions.slice(0, 20).forEach((e, i) => {
                    const angle = (i / (data.pbx.extensions.length || 1)) * 2 * Math.PI;
                    const radius = 350;
                    
                    nodes.push({
                        id: e.ext,
                        type: 'extension',
                        data: e,
                        position: { x: radius * Math.cos(angle), y: radius * Math.sin(angle) }
                    });

                    edges.push({
                        id: `e-${e.ext}`,
                        source: 'core',
                        target: e.ext,
                        animated: e.status === 'BUSY', // Solo animamos el "vuelo de datos" en llamada
                        style: { 
                            stroke: e.status === 'BUSY' ? '#f59e0b' : (e.status === 'ONLINE' ? '#238636' : '#21262d'), 
                            strokeWidth: e.status === 'BUSY' ? 3 : 1,
                            opacity: e.status === 'OFFLINE' ? 0.3 : 1
                        }
                    });
                });

                return { nodes, edges };
            }, [data.pbx.extensions]);

            return (
                <div className="flex h-screen w-screen bg-[#030406]">
                    <aside className="w-64 sidebar p-6 flex flex-col z-50">
                        <div className="flex items-center gap-3 mb-12">
                            <h2 className="text-2xl font-black text-[#8B5CF6] tracking-tighter">Teleflow</h2>
                        </div>
                        <nav className="space-y-4">
                            <div className={`flex items-center gap-4 px-4 py-3 rounded-2xl cursor-pointer transition-all ${view==='dashboard'?'bg-purple-500/10 text-purple-400 border border-purple-500/20':'text-gray-500'}`} onClick={()=>setView('dashboard')}>
                                <span className="material-icons">dashboard</span><span className="text-sm font-black uppercase">Dashboard</span>
                            </div>
                            <div className="flex items-center gap-4 px-4 py-3 text-gray-700 cursor-not-allowed">
                                <span className="material-icons">people</span><span className="text-sm font-black uppercase">Extensiones</span>
                            </div>
                        </nav>
                    </aside>

                    <main className="flex-1 relative overflow-hidden">
                        {view === 'dashboard' && (
                            <div className="w-full h-full relative">
                                {/* RECURSOS OVERLAY */}
                                <div className="absolute top-10 left-10 z-10 space-y-6">
                                    <div className="glass-panel p-6 w-72 shadow-2xl">
                                        <div className="flex justify-between items-center mb-6">
                                            <b className="text-[10px] font-black text-gray-500 uppercase tracking-widest">Estado del Servidor</b>
                                            <span className="text-[8px] px-2 py-1 bg-green-500/10 text-green-500 rounded-full font-black uppercase">Live</span>
                                        </div>
                                        <div className="space-y-4">
                                            <div>
                                                <div className="flex justify-between text-[10px] font-black mb-1"><span>PROCESADOR</span><span className="text-purple-400">{data.system.cpu}%</span></div>
                                                <div className="h-1 bg-white/5 rounded-full overflow-hidden"><div className="h-full bg-purple-500 transition-all duration-1000" style={{width: `${data.system.cpu}%`}}></div></div>
                                            </div>
                                            <div>
                                                <div className="flex justify-between text-[10px] font-black mb-1"><span>MEMORIA RAM</span><span className="text-green-400">{data.system.ram}%</span></div>
                                                <div className="h-1 bg-white/5 rounded-full overflow-hidden"><div className="h-full bg-green-500 transition-all duration-1000" style={{width: `${data.system.ram}%`}}></div></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="glass-panel p-5 w-72 shadow-2xl border-l-4 border-l-purple-500">
                                        <div className="text-[10px] font-black text-gray-500 uppercase mb-2">Monitor de Red</div>
                                        <div className="text-2xl font-black">{data.pbx.extensions.filter(x=>x.status==='ONLINE').length} <span className="text-xs text-gray-600">Internos Online</span></div>
                                    </div>
                                </div>

                                <ReactFlow nodes={flowElements.nodes} edges={flowElements.edges} nodeTypes={nodeTypes} fitView minZoom={0.2}>
                                    <Background color="#111" gap={30} />
                                </ReactFlow>
                            </div>
                        )}
                    </main>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
