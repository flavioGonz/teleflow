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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; transition: 0.3s; margin: 0; background-color: #0B0E14; color: #f8fafc; overflow: hidden; }
        .dark { background-color: #0B0E14; color: #f8fafc; }
        .light { background-color: #F9FAFB; color: #0f172a; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sileo-toast { position: fixed; top: 25px; right: 25px; z-index: 10000; background: rgba(22, 27, 34, 0.9); backdrop-filter: blur(15px); border-left: 5px solid #8B5CF6; padding: 20px; border-radius: 18px; animation: sIn 0.5s ease-out forwards; }
        @keyframes sIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;

        function App() {
            const [isLogged, setIsLogged] = useState(false);
            const [isDark, setIsDark] = useState(true);
            const [collapsed, setCollapsed] = useState(false);
            const [view, setView] = useState('dashboard');
            const [search, setSearch] = useState('');
            const [data, setData] = useState({ pbx:{extensions:[], calls:[], queues:[]}, summary:{queue:0, wait:'0:00', abandon:'0%'}, system:{cpu:0, uptime:''} });

            useEffect(() => { document.body.className = isDark ? 'dark' : 'light'; }, [isDark]);

            const refresh = () => {
                fetch('api/index.php?action=get_full_data').then(r => {
                    if(r.status === 403) { setIsLogged(false); return null; }
                    return r.json();
                }).then(res => { if(res) setData(res); }).catch(e => {});
            };

            useEffect(() => {
                if (isLogged) { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }
            }, [isLogged]);

            const handleLogin = (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                fetch('api/index.php?action=login', { method:'POST', body:fd }).then(r=>r.json()).then(res=>{
                    if(res.status==='success') setIsLogged(true); else alert('Error de login');
                });
            };

            const handleLogout = () => {
                fetch('api/index.php?action=logout').then(()=>setIsLogged(false));
            };

            if (!isLogged) {
                return (
                    <div className="h-screen flex items-center justify-center bg-gradient-to-br from-[#1e1b2e] to-[#0B0E14]">
                        <div className="bg-[#161B22] p-10 rounded-[30px] border border-white/5 w-[400px] shadow-2xl">
                            <div className="text-center mb-10">
                                <h1 className="text-4xl font-black text-[#8B5CF6] mb-2 tracking-tighter">Teleflow</h1>
                                <p className="text-gray-500 text-sm font-bold uppercase tracking-widest">PBX Control Center</p>
                            </div>
                            <form onSubmit={handleLogin} className="space-y-4">
                                <input name="username" type="text" placeholder="Usuario" className="w-full bg-black/50 border border-white/10 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-purple-600 transition-all" required />
                                <input name="password" type="password" placeholder="Contraseña" className="w-full bg-black/50 border border-white/10 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-purple-600 transition-all" required />
                                <button className="w-full bg-[#8B5CF6] text-white py-4 rounded-2xl font-black shadow-lg shadow-purple-500/20 hover:scale-[1.02] transition-transform">ACCEDER AL SISTEMA</button>
                            </form>
                        </div>
                    </div>
                );
            }

            return (
                <div className="flex min-h-screen">
                    {/* SIDEBAR */}
                    <aside className={`fixed h-full ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-[#e2e8f0]'} border-r p-6 flex flex-col sidebar ${collapsed?'w-24':'w-64'} z-50`}>
                        <div className="flex items-center justify-between mb-12">
                            {!collapsed && <h2 className="text-2xl font-black text-[#8B5CF6] tracking-tighter">Teleflow</h2>}
                            <span className="material-icons text-[#8B5CF6] cursor-pointer" onClick={()=>setCollapsed(!collapsed)}>{collapsed?'menu_open':'menu'}</span>
                        </div>
                        <nav className="space-y-2 flex-1">
                            <NavItem icon="dashboard" label="Dashboard" active={view==='dashboard'} collapsed={collapsed} onClick={()=>setView('dashboard')} />
                            <NavItem icon="people" label="Extensiones" active={view==='extensiones'} collapsed={collapsed} onClick={()=>setView('extensiones')} />
                            <NavItem icon="headset_mic" label="CallCenter" active={view==='callcenter'} collapsed={collapsed} onClick={()=>setView('callcenter')} />
                            <NavItem icon="format_list_bulleted" label="Colas" active={view==='colas'} collapsed={collapsed} onClick={()=>setView('colas')} />
                            <NavItem icon="mic" label="Grabaciones" active={view==='grabaciones'} collapsed={collapsed} onClick={()=>setView('grabaciones')} />
                        </nav>
                        <div className="pt-6 border-t border-gray-800">
                             <div className="flex items-center gap-4 px-4 py-3 cursor-pointer text-gray-500 hover:text-white" onClick={()=>setIsDark(!isDark)}>
                                <span className="material-icons">{isDark ? 'light_mode' : 'dark_mode'}</span>
                                {!collapsed && <span className="text-sm font-bold">Tema</span>}
                             </div>
                             <div className="flex items-center gap-4 px-4 py-3 cursor-pointer text-red-400 hover:text-red-500" onClick={handleLogout}>
                                <span className="material-icons">logout</span>
                                {!collapsed && <span className="text-sm font-bold">Salir</span>}
                             </div>
                        </div>
                    </aside>

                    <main className={`flex-1 p-10 main-content transition-all ${collapsed ? 'ml-24' : 'ml-64'} overflow-y-auto h-screen`}>
                        <header className="flex justify-between items-center mb-10">
                            <div><h1 className="text-3xl font-black uppercase tracking-tighter">{view}</h1><p className="text-gray-500 text-sm font-bold">Panel de Gestión Sildan</p></div>
                            <div className="flex items-center gap-4">
                                <div className={`px-4 py-2 rounded-xl border ${isDark?'bg-[#161B22] border-[#30363d] text-purple-400':'bg-white text-purple-600 shadow-sm'} text-xs font-black`}>CPU: {data.system.cpu}%</div>
                                <div className="bg-[#8B5CF6] text-white w-11 h-11 flex items-center justify-center rounded-xl font-black shadow-lg shadow-purple-500/20">FG</div>
                            </div>
                        </header>

                        {view === 'extensiones' && (
                            <div className="space-y-4">
                                <div className="flex justify-center mb-6"><input type="text" placeholder="🔍 Buscar por interno o nombre..." className={`w-full max-w-xl py-3.5 px-6 rounded-2xl outline-none focus:ring-2 focus:ring-purple-600 transition-all ${isDark?'bg-[#161B22] text-white border-none':'bg-white text-black border border-gray-200 shadow-sm'}`} onChange={e=>setSearch(e.target.value)} /></div>
                                {data.pbx.extensions.filter(e=>e.ext.includes(search)||e.name.toLowerCase().includes(search.toLowerCase())).map(e => (
                                    <div key={e.ext} className={`p-4 grid grid-cols-5 items-center rounded-2xl border transition-all ${isDark?'bg-[#161B22] border-[#30363d] text-white':'bg-white border-gray-100 text-black shadow-sm'} hover:border-purple-500 cursor-pointer`}>
                                        <div className="flex items-center gap-4"><img src={e.avatar} className="w-11 h-11 rounded-xl object-cover"/><div className="font-black">#{e.ext}<br/><span className="text-[10px] text-gray-500">{e.name}</span></div></div>
                                        <div className="flex justify-center"><span className={`px-3 py-1.5 rounded-full text-[9px] font-black ${e.status==='ONLINE'?'bg-green-500/10 text-green-400':'bg-gray-500/10 text-gray-500'}`}>{e.status}</span></div>
                                        <div className="text-center text-[10px] font-mono font-bold text-purple-400">{e.rtt}</div>
                                        <div className="text-right col-span-2 leading-tight"><div className="text-[11px] font-bold text-red-400/80">{e.ip}</div></div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {view === 'colas' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {data.pbx.queues.map(q => (
                                    <div key={q.name} className={`p-6 rounded-2xl border ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-gray-100 shadow-sm'} text-center`}>
                                        <div className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">{q.name}</div>
                                        <div className="text-6xl font-black text-purple-500">{q.waiting}</div>
                                        <div className="text-xs font-bold text-gray-500 mt-2">Clientes en espera</div>
                                        <div className="mt-4 pt-4 border-t border-white/5"><span className="text-[9px] font-black bg-purple-500/10 text-purple-400 px-3 py-1 rounded-full uppercase">{q.strategy}</span></div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {view === 'callcenter' && (
                            <div className="space-y-6">
                                <div className={`p-10 rounded-3xl border ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-gray-100 shadow-sm'} text-center`}>
                                    <h2 className="text-2xl font-black mb-2">Monitor de Supervisión</h2>
                                    <p className="text-gray-500 text-sm">Control total sobre llamadas y agentes en tiempo real</p>
                                </div>
                                <div className="grid grid-cols-3 gap-6">
                                    <StatCard label="Colas Activas" value={data.pbx.queues.length} isDark={isDark} />
                                    <StatCard label="Promedio Espera" value="45s" isDark={isDark} />
                                    <StatCard label="Llamadas Vivas" value={data.pbx.calls.length} isDark={isDark} />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            );
        }

        function NavItem({ icon, label, active, collapsed, onClick }) {
            return (
                <div onClick={onClick} className={`flex items-center gap-4 px-4 py-3.5 rounded-2xl cursor-pointer transition-all ${active ? 'bg-purple-500/10 text-purple-500 border border-purple-500/20' : 'text-gray-500 hover:text-white hover:bg-white/5'}`}>
                    <span className="material-icons">{icon}</span>
                    {!collapsed && <span className="text-sm font-black tracking-tight">{label}</span>}
                </div>
            );
        }

        function StatCard({ label, value, isDark }) {
            return (
                <div className={`p-6 rounded-2xl border ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-gray-100 shadow-sm'}`}>
                    <div className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">{label}</div>
                    <div className="text-4xl font-black">{value}</div>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
