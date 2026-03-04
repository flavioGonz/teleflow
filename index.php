<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Teleflow Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/umd/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .sidebar { transition: width 0.3s ease; background-color: #0d1117; border-right: 1px solid #21262d; }
        .node-core { background: #714B67; color: #fff; width: 120px; height: 120px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 40px rgba(113, 75, 103, 0.5); border: 3px solid #fff; }
        .node-ext { background: rgba(22, 27, 34, 0.8); border: 1px solid #30363d; border-radius: 12px; padding: 10px; width: 150px; color: #fff; backdrop-filter: blur(10px); }
        .glass-panel { background: rgba(13, 17, 23, 0.7); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; }
        .pulse-core { animation: p-core 4s infinite; }
        @keyframes p-core { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); opacity: 0.8; } }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo, useRef } = React;
        const RF = window.ReactFlow;

        // --- COMPONENTES DEL DASHBOARD ---
        const CoreNode = () => (
            <div className="node-core pulse-core text-center">
                <span className="material-icons text-3xl">dns</span>
                <b className="text-[10px] mt-1">SIP CORE</b>
            </div>
        );

        const ExtNode = ({ data }) => (
            <div className={`node-ext border-l-4 ${data.status === 'ONLINE' ? 'border-l-green-500' : 'border-l-gray-600'}`}>
                <div className="flex items-center gap-3">
                    <img src={data.avatar} className="w-8 h-8 rounded-lg object-cover" />
                    <div>
                        <div className="text-[11px] font-black">#{data.ext}</div>
                        <div className="text-[8px] text-gray-500 font-bold uppercase truncate w-20">{data.name}</div>
                    </div>
                </div>
            </div>
        );

        function App() {
            const [view, setView] = useState('dashboard');
            const [data, setData] = useState({ system:{cpu:0, ram:0, disk:0}, pbx:{extensions:[], calls:[], recordings:[]} });
            
            const refresh = () => fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res=>setData(res));
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const nodeTypes = { core: CoreNode, extension: ExtNode };

            const flowElements = useMemo(() => {
                const nodes = [{ id:'core', type:'core', position:{x:0, y:0} }];
                const edges = [];
                data.pbx.extensions.slice(0, 15).forEach((e,i) => {
                    const a = (i / 15) * 2 * Math.PI;
                    nodes.push({ id:e.ext, type:'extension', data:e, position:{x:450*Math.cos(a), y:350*Math.sin(a)} });
                    edges.push({ id:`e-${e.ext}`, source:'core', target:e.ext, animated:e.status!=='OFFLINE', style:{stroke:e.status==='ONLINE'?'#238636':'#30363d', strokeWidth:2} });
                });
                return { nodes, edges };
            }, [data.pbx.extensions]);

            return (
                <div className="flex h-screen w-screen bg-[#030406]">
                    {/* SIDEBAR */}
                    <aside className="w-64 bg-[#0d1117] border-r border-[#21262d] p-6 flex flex-col z-50 shadow-2xl">
                        <div className="flex items-center gap-3 mb-12">
                            <h2 className="text-2xl font-black text-[#8B5CF6] tracking-tighter">Teleflow</h2>
                            <div className="w-8 h-8 bg-[#8B5CF6] rounded-lg flex items-center justify-center shadow-lg shadow-purple-500/20"><i className="fa fa-wave-square text-white text-xs"></i></div>
                        </div>
                        <nav className="space-y-4">
                            <NavItem icon="dashboard" label="Dashboard" active={view==='dashboard'} onClick={()=>setView('dashboard')} />
                            <NavItem icon="people" label="Extensiones" active={view==='extensiones'} onClick={()=>setView('extensiones')} />
                        </nav>
                    </aside>

                    {/* MAIN CONTENT AREA */}
                    <main className="flex-1 relative overflow-hidden">
                        {view === 'dashboard' && (
                            <div className="w-full h-full relative">
                                {/* OVERLAY: RECURSOS DEL SISTEMA */}
                                <div className="absolute top-8 left-8 z-10 space-y-4">
                                    <div className="glass-panel p-5 w-80 shadow-2xl">
                                        <h5 className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Recursos del Servidor</h5>
                                        <div className="grid grid-cols-2 gap-6">
                                            <div className="stat-item">
                                                <div className="flex justify-between items-end mb-1">
                                                    <span className="text-[9px] font-bold text-gray-400">CPU</span>
                                                    <span className="text-xs font-black text-purple-400">{data.system.cpu}%</span>
                                                </div>
                                                <div className="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                                    <div className="h-full bg-purple-500" style={{width: `${data.system.cpu}%`}}></div>
                                                </div>
                                            </div>
                                            <div className="stat-item">
                                                <div className="flex justify-between items-end mb-1">
                                                    <span className="text-[9px] font-bold text-gray-400">RAM</span>
                                                    <span className="text-xs font-black text-green-400">{data.system.ram}%</span>
                                                </div>
                                                <div className="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                                    <div className="h-full bg-green-500" style={{width: `${data.system.ram}%`}}></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="mt-4 pt-4 border-t border-white/5">
                                            <div className="flex justify-between text-[10px] font-bold">
                                                <span className="text-gray-500">DISCO DURO (SSD)</span>
                                                <span className="text-gray-300">{data.system.disk}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* LOG LLAMADAS RECIENTES */}
                                    <div className="glass-panel p-5 w-80 shadow-2xl">
                                        <h5 className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Llamadas Recientes</h5>
                                        <div className="space-y-3">
                                            {(data.pbx.recordings || []).slice(0,3).map((r,i) => (
                                                <div key={i} className="flex items-center justify-between text-[10px] bg-white/5 p-2 rounded-lg border border-white/5">
                                                    <div className="font-bold"><span className="text-purple-400">{r.src}</span> → {r.dst}</div>
                                                    <div className="opacity-50 font-mono">{r.duration}s</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                {/* MAPA DE FLUJOS (100%) */}
                                <div className="w-full h-full">
                                    <RF.ReactFlow nodes={flowElements.nodes} edges={flowElements.edges} nodeTypes={nodeTypes} fitView>
                                        <RF.Background color="#111" gap={30} />
                                    </RF.ReactFlow>
                                </div>
                            </div>
                        )}
                    </main>
                </div>
            );
        }

        function NavItem({ icon, label, active, onClick }) {
            return (
                <div onClick={onClick} className={`flex items-center gap-4 px-4 py-3 rounded-2xl cursor-pointer transition-all ${active ? 'bg-purple-500/10 text-purple-500 border border-purple-500/20 shadow-lg shadow-purple-500/5' : 'text-gray-500 hover:text-white hover:bg-white/5'}`}>
                    <span className="material-icons text-xl">{icon}</span>
                    <span className="text-sm font-black tracking-tight">{label}</span>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
