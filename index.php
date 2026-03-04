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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@11.10.1/dist/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030406; color: #f8fafc; margin: 0; overflow: hidden; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); background-color: #0d1117; border-right: 1px solid #21262d; }
        
        /* REACT FLOW STYLING */
        .node-core { background: #714B67; color: #fff; width: 140px; height: 140px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 50px rgba(113, 75, 103, 0.4); border: 4px solid #fff; }
        .node-ext { background: rgba(22, 27, 34, 0.7); border: 1px solid #30363d; border-radius: 12px; padding: 10px; width: 160px; color: #fff; backdrop-filter: blur(10px); }
        .node-trunk { background: rgba(35, 134, 54, 0.2); border: 1px solid #238636; color: #fff; width: 150px; padding: 10px; border-radius: 8px; backdrop-filter: blur(5px); }

        .pulse-core { animation: p-core 4s infinite; }
        @keyframes p-core { 0% { box-shadow: 0 0 20px rgba(113, 75, 103, 0.4); } 50% { box-shadow: 0 0 70px rgba(113, 75, 103, 0.7); } 100% { box-shadow: 0 0 20px rgba(113, 75, 103, 0.4); } }
        
        .sileo-toast { position: fixed; top: 25px; right: 25px; z-index: 10000; background: rgba(22, 27, 34, 0.9); backdrop-filter: blur(15px); border-left: 5px solid #8B5CF6; padding: 20px; border-radius: 18px; animation: sIn 0.5s ease-out forwards; }
        @keyframes sIn { from { transform: translateX(120%); } to { transform: translateX(0); } }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;
        const RF = window.ReactFlow;

        const CoreNode = () => (
            <div className="node-core pulse-core text-center">
                <span className="material-icons text-4xl mb-1">dns</span>
                <b className="text-sm">SIP CORE</b>
                <div className="text-[8px] opacity-70">AST-18-SILDAN</div>
            </div>
        );

        const ExtNode = ({ data }) => (
            <div className={`node-ext transition-all ${data.status === 'ONLINE' ? 'border-green-500 shadow-lg shadow-green-500/20' : ''}`}>
                <div className="flex items-center gap-3">
                    <img src={data.avatar} className="w-8 h-8 rounded-lg" />
                    <div>
                        <div className="text-xs font-black">#{data.ext}</div>
                        <div className="text-[9px] text-gray-500 font-bold truncate w-24">{data.name}</div>
                    </div>
                </div>
                <div className="mt-2 flex justify-between items-center opacity-80">
                    <span className="text-[8px] font-mono text-purple-400">{data.ip}</span>
                    <span className="text-[8px] font-bold text-gray-400">{data.rtt}</span>
                </div>
            </div>
        );

        const TrunkNode = ({ data }) => (
            <div className="node-trunk flex items-center gap-3">
                <span className="material-icons text-green-500">settings_input_component</span>
                <div>
                    <div className="text-[10px] font-black">{data.name}</div>
                    <div className="text-[7px] font-bold text-green-400 uppercase">Trunk Link</div>
                </div>
            </div>
        );

        const nodeTypes = { core: CoreNode, extension: ExtNode, trunk: TrunkNode };

        function App() {
            const [view, setView] = useState('dashboard');
            const [data, setData] = useState({ system:{cpu:0, ram:0, disk:0}, pbx:{extensions:[], trunks:[], calls:[]} });
            const [toast, setToast] = useState(null);

            const refresh = () => fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res=>{
                if(res.pbx && res.pbx.calls && res.pbx.calls.length > (data.pbx.calls ? data.pbx.calls.length : 0)){
                    setToast(res.pbx.calls[res.pbx.calls.length-1]);
                    setTimeout(()=>setToast(null), 5000);
                }
                setData(res);
            });
            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const flowElements = useMemo(() => {
                const nodes = [{ id:'core', type:'core', position:{x:0, y:0} }];
                const edges = [];
                // Troncales arriba
                (data.pbx.trunks || []).forEach((t,i) => {
                    nodes.push({ id:t.id, type:'trunk', data:t, position:{x:(i-1)*200, y:-250} });
                    edges.push({ id:`e-${t.id}`, source:t.id, target:'core', animated:true, style:{stroke:'#238636', strokeWidth:3} });
                });
                // Extensiones abajo en arco
                (data.pbx.extensions || []).forEach((e,i) => {
                    const a = (i / (data.pbx.extensions.length || 1)) * Math.PI + (Math.PI/4);
                    nodes.push({ id:e.ext, type:'extension', data:e, position:{x:500*Math.cos(a), y:350*Math.sin(a)} });
                    edges.push({ id:`e-${e.ext}`, source:'core', target:e.ext, animated:e.status!=='OFFLINE', style:{stroke:e.status==='ONLINE'?'#238636':'#21262d', strokeWidth:2} });
                });
                return { nodes, edges };
            }, [data.pbx.extensions, data.pbx.trunks]);

            return (
                <div className="flex h-screen w-screen bg-[#030406]">
                    {toast && <div className="sileo-toast flex items-center gap-4"><span className="material-icons text-red-500 animate-pulse">phone_in_talk</span><div><b>Llamada en curso</b><br/><small>{toast.from} -> {toast.to}</small></div></div>}
                    
                    <aside className="w-64 bg-[#0d1117] border-r border-[#21262d] p-6 flex flex-col">
                        <h2 className="text-2xl font-black text-[#8B5CF6] mb-10 tracking-tighter">Teleflow</h2>
                        <nav className="space-y-4">
                            <div className={`flex items-center gap-4 px-3 py-2 rounded-xl cursor-pointer ${view==='dashboard'?'text-purple-400 bg-purple-500/10':'text-gray-500'}`} onClick={()=>setView('dashboard')}><span className="material-icons">dashboard</span><b>Dashboard</b></div>
                            <div className={`flex items-center gap-4 px-3 py-2 rounded-xl cursor-pointer ${view==='extensiones'?'text-purple-400 bg-purple-500/10':'text-gray-500'}`} onClick={()=>setView('extensiones')}><span className="material-icons">people</span><b>Extensiones</b></div>
                        </nav>
                    </aside>

                    <main className="flex-1 relative">
                        {view === 'dashboard' ? (
                            <div className="w-full h-full relative">
                                <div className="absolute top-10 left-10 z-10 flex gap-4">
                                    <div className="bg-[#161B22]/60 backdrop-blur-md p-4 rounded-2xl border border-white/5 shadow-2xl">
                                        <div className="flex gap-8">
                                            <div><div className="text-xl font-black">{data.system.cpu}%</div><div className="text-[8px] font-bold text-gray-500 uppercase">CPU</div></div>
                                            <div><div className="text-xl font-black">{data.system.ram}%</div><div className="text-[8px] font-bold text-gray-500 uppercase">RAM</div></div>
                                            <div className="border-l border-white/10 pl-8">
                                                <div className="text-xl font-black text-green-400">{data.pbx.extensions.filter(x=>x.status!=='OFFLINE').length}</div>
                                                <div className="text-[8px] font-bold text-gray-500 uppercase">En Línea</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <RF.ReactFlow nodes={flowElements.nodes} edges={flowElements.edges} nodeTypes={nodeTypes} fitView>
                                    <RF.Background color="#111" gap={25} />
                                </RF.ReactFlow>
                            </div>
                        ) : (
                            <div className="p-10">
                                <h1 className="text-3xl font-black mb-10 uppercase">Gestión de Extensiones</h1>
                                <div className="space-y-3">
                                    {data.pbx.extensions.map(e => (
                                        <div key={e.ext} className="bg-[#161B22] border border-[#30363d] rounded-2xl p-4 flex items-center justify-between">
                                            <div className="flex items-center gap-4"><img src={e.avatar} className="w-10 h-10 rounded-xl"/><b className="text-lg">#{e.ext}</b><span className="text-sm text-gray-500">{e.name}</span></div>
                                            <div className="flex items-center gap-8">
                                                <span className={`px-3 py-1 rounded-full text-[9px] font-black ${e.status==='ONLINE'?'bg-green-500/10 text-green-400':'bg-gray-500/10 text-gray-500'}`}>{e.status}</span>
                                                <div className="text-right text-[10px] font-mono font-bold text-purple-400">{e.ip}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
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
