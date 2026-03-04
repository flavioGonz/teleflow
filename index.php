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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; transition: 0.3s; margin: 0; background-color: #0B0E14; color: #f8fafc; overflow: hidden; }
        .dark { background-color: #0B0E14; color: #f8fafc; }
        .light { background-color: #F9FAFB; color: #0f172a; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sileo-toast { position: fixed; top: 25px; right: 25px; z-index: 10000; background: rgba(22, 27, 34, 0.9); backdrop-filter: blur(15px); border-left: 5px solid #8B5CF6; padding: 20px; border-radius: 18px; animation: sIn 0.5s ease-out forwards; }
        @keyframes sIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .drawer { position: fixed; right: 0; top: 0; width: 450px; h: 100vh; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 2001; }
    </style>
</head>
<body class="dark">
    <div id="root"></div>
    <script type="text/babel">
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) { window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = () => {}; window.__REACT_DEVTOOLS_GLOBAL_HOOK__.inject = () => {}; }
        const { useState, useEffect, useMemo } = React;
        const RF = window.ReactFlow;

        function App() {
            const [isLogged, setIsLogged] = useState(false);
            const [isDark, setIsDark] = useState(true);
            const [collapsed, setCollapsed] = useState(false);
            const [view, setView] = useState('extensiones');
            const [search, setSearch] = useState('');
            const [selected, setSelected] = useState(null);
            const [data, setData] = useState({ pbx:{extensions:[], calls:[], queues:[], recordings:[]}, system:{cpu:0, uptime:''} });

            const refresh = () => {
                fetch('api/index.php?action=get_full_data').then(r => r.status === 403 ? setIsLogged(false) : r.json()).then(res => { if(res) setData(res); });
            };

            useEffect(() => { if (isLogged) { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); } }, [isLogged]);
            useEffect(() => { document.body.className = isDark ? 'dark' : 'light'; }, [isDark]);

            const handleLogin = (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                fetch('api/index.php?action=login', { method:'POST', body:fd }).then(r=>r.json()).then(res => res.status==='success' && setIsLogged(true));
            };

            const saveExt = (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                fd.append('ext', selected.ext);
                fetch('api/index.php?action=update_extension', { method:'POST', body:fd }).then(() => { setSelected(null); refresh(); });
            };

            if (!isLogged) return (
                <div className="h-screen flex items-center justify-center bg-gradient-to-br from-[#1e1b2e] to-[#0B0E14]">
                    <form onSubmit={handleLogin} className="bg-[#161B22] p-10 rounded-[30px] border border-white/5 w-[400px] shadow-2xl">
                        <h1 className="text-4xl font-black text-[#8B5CF6] mb-10 text-center tracking-tighter">Teleflow</h1>
                        <input name="username" type="text" placeholder="Usuario" className="w-full bg-black/50 border border-white/10 rounded-2xl p-4 text-white mb-4 outline-none" required />
                        <input name="password" type="password" placeholder="Contraseña" className="w-full bg-black/50 border border-white/10 rounded-2xl p-4 text-white mb-6 outline-none" required />
                        <button className="w-full bg-[#8B5CF6] text-white py-4 rounded-2xl font-black">ACCEDER</button>
                    </form>
                </div>
            );

            return (
                <div className="flex min-h-screen">
                    <aside className={`fixed h-full ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-[#e2e8f0]'} border-r p-6 flex flex-col sidebar ${collapsed?'w-24':'w-64'} z-50`}>
                        <div className="flex items-center justify-between mb-12">
                            {!collapsed && <h2 className="text-2xl font-black text-[#8B5CF6]">Teleflow</h2>}
                            <span className="material-icons text-[#8B5CF6] cursor-pointer" onClick={()=>setCollapsed(!collapsed)}>{collapsed?'menu_open':'menu'}</span>
                        </div>
                        <nav className="space-y-2 flex-1">
                            <NavItem icon="dashboard" label="Dashboard" active={view==='dashboard'} collapsed={collapsed} onClick={()=>setView('dashboard')} />
                            <NavItem icon="people" label="Extensiones" active={view==='extensiones'} collapsed={collapsed} onClick={()=>setView('extensiones')} />
                            <NavItem icon="headset_mic" label="CallCenter" active={view==='callcenter'} collapsed={collapsed} onClick={()=>setView('callcenter')} />
                            <NavItem icon="mic" label="Grabaciones" active={view==='grabaciones'} collapsed={collapsed} onClick={()=>setView('grabaciones')} />
                        </nav>
                        <div className="pt-6 border-t border-gray-800 cursor-pointer" onClick={()=>setIsDark(!isDark)}>
                            <span className="material-icons">{isDark ? 'light_mode' : 'dark_mode'}</span>
                        </div>
                    </aside>

                    <main className={`flex-1 p-10 main-content transition-all ${collapsed ? 'ml-24' : 'ml-64'} overflow-y-auto h-screen`}>
                        <header className="flex justify-between items-center mb-10">
                            <h1 className="text-3xl font-black uppercase">{view}</h1>
                            <div className="bg-[#8B5CF6] text-white w-11 h-11 flex items-center justify-center rounded-xl font-black">FG</div>
                        </header>

                        {view === 'extensiones' && (
                            <div className="space-y-4">
                                <div className="flex justify-center mb-6"><input type="text" placeholder="🔍 Buscar..." className={`w-full max-w-xl py-3.5 px-6 rounded-2xl outline-none ${isDark?'bg-[#161B22] text-white':'bg-white text-black border border-gray-200 shadow-sm'}`} onChange={e=>setSearch(e.target.value)} /></div>
                                {data.pbx.extensions.filter(e=>e.ext.includes(search)||e.name.toLowerCase().includes(search.toLowerCase())).map(e => (
                                    <div key={e.ext} className={`p-4 grid grid-cols-5 items-center rounded-2xl border transition-all ${isDark?'bg-[#161B22] border-[#30363d] text-white':'bg-white border-gray-100 text-black shadow-sm'} hover:border-purple-500 cursor-pointer`} onClick={()=>setSelected(e)}>
                                        <div className="flex items-center gap-4"><img src={e.avatar} className="w-11 h-11 rounded-xl object-cover"/><b>#{e.ext}</b></div>
                                        <div className="text-center font-bold">{e.name}</div>
                                        <div className="flex justify-center"><span className={`px-3 py-1.5 rounded-full text-[9px] font-black ${e.status==='ONLINE'?'bg-green-500/10 text-green-400':'bg-gray-500/10 text-gray-500'}`}>{e.status}</span></div>
                                        <div className="text-center font-mono text-[10px] text-purple-400">{e.rtt}</div>
                                        <div className="text-right text-[11px] font-bold text-red-400/80">{e.ip}</div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </main>

                    {selected && (
                        <>
                            <div className="fixed inset-0 bg-black/60 backdrop-blur-md z-[2000]" onClick={()=>setSelected(null)}></div>
                            <div className={`fixed right-0 top-0 w-[450px] h-full ${isDark?'bg-[#161B22] border-l border-[#30363d]':'bg-white border-l border-gray-200'} z-[2001] p-10 shadow-2xl`}>
                                <h2 className="text-2xl font-black mb-8">Editar #{selected.ext}</h2>
                                <form onSubmit={saveExt}>
                                    <label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Nombre</label>
                                    <input name="name" type="text" className={`w-full p-3 rounded-xl mb-6 outline-none ${isDark?'bg-black text-white':'bg-gray-50 text-black'}`} defaultValue={selected.name} />
                                    <button className="w-full bg-[#8B5CF6] text-white py-4 rounded-xl font-black shadow-lg">GUARDAR CAMBIOS</button>
                                    <button type="button" className="w-full mt-4 text-gray-500 font-bold" onClick={()=>setSelected(null)}>CANCELAR</button>
                                </form>
                            </div>
                        </>
                    )}
                </div>
            );
        }

        function NavItem({ icon, label, active, collapsed, onClick }) {
            return (
                <div onClick={onClick} className={`flex items-center gap-4 px-4 py-3.5 rounded-2xl cursor-pointer transition-all ${active ? 'bg-purple-500/10 text-[#8B5CF6] border border-purple-500/20' : 'text-gray-500 hover:text-white hover:bg-white/5'}`}>
                    <span className="material-icons">{icon}</span>{!collapsed && <span className="text-sm font-bold">{label}</span>}
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
