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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0B0E14; color: #f8fafc; margin: 0; overflow-x: hidden; }
        .sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); background-color: #161B22; border-right: 1px solid #30363d; }
        .nav-item { transition: 0.2s; white-space: nowrap; }
        .nav-item.active { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; border: 1px solid rgba(139, 92, 246, 0.2); }
        .sileo-toast { position: fixed; top: 25px; right: 25px; z-index: 10000; background: rgba(22, 27, 34, 0.9); backdrop-filter: blur(15px); border-left: 5px solid #8B5CF6; padding: 20px; border-radius: 18px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); animation: sIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        @keyframes sIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .pulse-busy { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); } }
        /* Fix para que los iconos Material se vean bien */
        .material-icons { font-size: 22px; }
    </style>
</head>
<body>
    <div id="root"></div>
    <script type="text/babel">
        // VACUNA REACT DEVTOOLS
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
            window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = () => {};
            window.__REACT_DEVTOOLS_GLOBAL_HOOK__.inject = () => {};
        }

        const { useState, useEffect } = React;

        function App() {
            const [collapsed, setCollapsed] = useState(false);
            const [view, setView] = useState('extensiones');
            const [isDark, setIsDark] = useState(true);
            const [data, setData] = useState({ pbx:{extensions:[], calls:[]}, summary:{queue:12, wait:'0:45', abandon:'2.4%'} });
            const [search, setSearch] = useState('');
            const [selectedExt, setSelectedExt] = useState(null);
            const [toast, setToast] = useState(null);

            const refresh = () => {
                fetch('api/index.php?action=get_full_data').then(r=>r.json()).then(res => {
                    if (res.pbx && res.pbx.calls && res.pbx.calls.length > (data.pbx.calls ? data.pbx.calls.length : 0)) {
                        setToast(res.pbx.calls[res.pbx.calls.length - 1]);
                        setTimeout(() => setToast(null), 5000);
                    }
                    setData(res);
                }).catch(e => console.log("Cargando..."));
            };

            useEffect(() => { 
                refresh(); 
                const i = setInterval(refresh, 3000); 
                document.body.className = isDark ? 'dark' : 'light';
                return ()=>clearInterval(i); 
            }, [isDark]);

            const filteredExts = (data.pbx.extensions || []).filter(e => 
                e.ext.includes(search) || e.name.toLowerCase().includes(search.toLowerCase())
            );

            return (
                <div className="flex min-h-screen">
                    {/* SILEO NOTIFICATION */}
                    {toast && (
                        <div className="sileo-toast flex items-center gap-4">
                            <span className="material-icons text-red-400 animate-pulse text-3xl">phone_in_talk</span>
                            <div>
                                <div className="text-[10px] font-black uppercase text-purple-400 tracking-tighter">Llamada en curso</div>
                                <div className="font-bold text-sm">Ext {toast.from} -> {toast.to}</div>
                            </div>
                        </div>
                    )}

                    {/* SIDEBAR */}
                    <aside className={`fixed h-full z-[100] sidebar ${collapsed ? 'w-24' : 'w-64'} p-6 flex flex-col`}>
                        <div className="flex items-center justify-between mb-12 px-2">
                            {!collapsed && <h2 className="text-2xl font-extrabold text-[#8B5CF6] tracking-tighter">Teleflow</h2>}
                            <span className="material-icons text-[#8B5CF6] cursor-pointer hover:rotate-180 transition-transform duration-500" onClick={()=>setCollapsed(!collapsed)}>
                                {collapsed ? 'last_page' : 'first_page'}
                            </span>
                        </div>
                        <nav className="space-y-2 flex-1">
                            <NavItem icon="dashboard" label="Dashboard" active={view==='dashboard'} collapsed={collapsed} onClick={()=>setView('dashboard')} />
                            <NavItem icon="people" label="Extensiones" active={view==='extensiones'} collapsed={collapsed} onClick={()=>setView('extensiones')} />
                            <NavItem icon="mic" label="Grabaciones" active={view==='grabaciones'} collapsed={collapsed} onClick={()=>setView('grabaciones')} />
                            <NavItem icon="headset_mic" label="CallCenter" active={view==='callcenter'} collapsed={collapsed} onClick={()=>setView('callcenter')} />
                        </nav>
                        <div className="pt-6 border-t border-gray-800">
                             <div className="flex items-center gap-4 px-4 py-3 cursor-pointer text-gray-500 hover:text-white transition-colors" onClick={()=>setIsDark(!isDark)}>
                                <span className="material-icons">{isDark ? 'light_mode' : 'dark_mode'}</span>
                                {!collapsed && <span className="text-sm font-bold">Tema</span>}
                             </div>
                        </div>
                    </aside>

                    {/* MAIN */}
                    <main className={`flex-1 p-10 main-content transition-all ${collapsed ? 'ml-24' : 'ml-64'}`}>
                        <header className="flex justify-between items-center mb-10">
                            <div>
                                <h1 className="text-3xl font-black uppercase tracking-tighter">{view === 'extensiones' ? 'Monitoreo de Agentes' : view}</h1>
                                <p className="text-gray-500 text-sm font-bold">Infratec Teleflow Pro v6.7</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <button className="w-11 h-11 bg-[#161B22] border border-[#30363d] rounded-xl flex items-center justify-center text-gray-500 hover:border-purple-500 hover:text-white transition-all"><span className="material-icons">settings</span></button>
                                <button className="w-11 h-11 bg-[#161B22] border border-[#30363d] rounded-xl flex items-center justify-center text-gray-500 hover:border-purple-500 hover:text-white transition-all relative"><span className="material-icons">notifications</span><span className="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full"></span></button>
                                <div className="bg-[#8B5CF6] text-white w-11 h-11 flex items-center justify-center rounded-xl font-black shadow-lg shadow-purple-500/20 ml-2">FG</div>
                            </div>
                        </header>

                        {view === 'extensiones' && (
                            <div className="fade-in space-y-6">
                                {/* SEARCH BAR CENTRADA */}
                                <div className="flex justify-center">
                                    <div className="relative w-full max-w-xl group">
                                        <span className="material-icons absolute left-4 top-3 text-gray-500 group-focus-within:text-[#8B5CF6] transition-colors">search</span>
                                        <input type="text" placeholder="Buscar por interno o nombre..." className="w-full bg-[#161B22] border border-[#30363d] rounded-2xl py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-[#8B5CF6] focus:border-[#8B5CF6] outline-none transition-all" onChange={e=>setSearch(e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-5 px-8 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">
                                    <div>Agente / Interno</div>
                                    <div className="text-center">Estado</div>
                                    <div className="text-center">En Llamada</div>
                                    <div className="text-center">Daily Perf.</div>
                                    <div className="text-right">IP / MAC</div>
                                </div>

                                <div className="space-y-3">
                                    {filteredExts.map(e => (
                                        <div key={e.ext} className="bg-[#161B22] border border-[#30363d] rounded-2xl p-4 grid grid-cols-5 items-center hover:border-[#8B5CF6]/50 hover:bg-[#1c2128] transition-all cursor-pointer group" onClick={()=>setSelectedExt(e)}>
                                            <div className="flex items-center gap-4">
                                                <div className="w-12 h-12 rounded-2xl overflow-hidden border border-white/10 bg-gray-900 flex items-center justify-center">
                                                    <img src={e.avatar} className="w-full h-full object-cover" />
                                                </div>
                                                <div>
                                                    <div className="font-black text-lg leading-tight">#{e.ext}</div>
                                                    <div className="text-[10px] text-gray-500 font-bold uppercase">{e.name}</div>
                                                </div>
                                            </div>
                                            <div className="flex justify-center">
                                                <span className={`px-3 py-1.5 rounded-full text-[9px] font-black flex items-center gap-2 ${e.status==='ONLINE'?'bg-green-500/10 text-green-400':'bg-gray-500/10 text-gray-500'}`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${e.status==='ONLINE'?'bg-green-400':'bg-gray-500'}`}></span>{e.status}
                                                </span>
                                            </div>
                                            <div className="text-center">
                                                <div className={`text-xs font-black ${e.in_call?'text-purple-400':'text-gray-600'}`}>{e.live_time || '---'}</div>
                                                <div className="text-[9px] text-gray-500 font-bold uppercase">{e.in_call?'En llamada':'Disponible'}</div>
                                            </div>
                                            <div className="flex justify-center gap-6">
                                                <div className="text-center"><div className="font-black text-sm">{e.calls_today || 0}</div><div className="text-[8px] text-gray-500 uppercase font-black">Calls</div></div>
                                                <div className="text-center"><div className="font-black text-sm">{e.aht || '0:00'}</div><div className="text-[8px] text-gray-500 uppercase font-black">AHT</div></div>
                                            </div>
                                            <div className="text-right leading-tight">
                                                <div className="text-[11px] font-bold text-red-400/70 font-mono">{e.ip}</div>
                                                <div className="text-[9px] text-gray-600 font-black uppercase tracking-tighter mt-0.5">{e.mac || '---'}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                
                                {/* SUMMARY CARDS */}
                                <div className="grid grid-cols-3 gap-6 mt-10">
                                    <div className="bg-[#161B22] border border-[#30363d] rounded-2xl p-6 shadow-2xl">
                                        <div className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Estado de Cola</div>
                                        <div className="flex items-end justify-between">
                                            <div className="text-4xl font-black">{data.summary.queue}</div>
                                            <div className="text-[10px] font-black text-green-400 flex items-center gap-1 bg-green-400/10 p-1.5 rounded-lg"><span className="material-icons text-xs">trending_down</span>-12% vs last hr</div>
                                        </div>
                                    </div>
                                    <div className="bg-[#161B22] border border-[#30363d] rounded-2xl p-6 shadow-2xl">
                                        <div className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Espera Promedio</div>
                                        <div className="flex items-end justify-between">
                                            <div className="text-4xl font-black">{data.summary.wait} seg</div>
                                            <div className="text-[10px] font-black text-red-400 flex items-center gap-1 bg-red-400/10 p-1.5 rounded-lg"><span className="material-icons text-xs">trending_up</span>+5% vs last hr</div>
                                        </div>
                                    </div>
                                    <div className="bg-[#161B22] border border-[#30363d] rounded-2xl p-6 shadow-2xl">
                                        <div className="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">Tasa de Abandono</div>
                                        <div className="flex items-end justify-between">
                                            <div className="text-4xl font-black">{data.summary.abandon}</div>
                                            <div className="text-[10px] font-black text-green-400 flex items-center gap-1 bg-green-400/10 p-1.5 rounded-lg"><span className="material-icons text-xs">verified</span>Meta: &lt;5%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </main>

                    {/* DRAWER CONFIGURACIÓN EXTENSIÓN */}
                    {selectedExt && (
                        <>
                            <div className="fixed inset-0 bg-black/60 backdrop-blur-md z-[2000] transition-opacity" onClick={()=>setSelectedExt(null)}></div>
                            <div className="fixed right-0 top-0 w-[450px] h-full bg-[#161B22] border-l border-[#30363d] z-[2001] p-10 transform transition-transform duration-500 shadow-2xl overflow-y-auto">
                                <div className="flex justify-between items-center mb-10">
                                    <h2 className="text-2xl font-black tracking-tighter">Configuración</h2>
                                    <button onClick={()=>setSelectedExt(null)} className="text-gray-500 hover:text-white"><span className="material-icons">close</span></button>
                                </div>
                                <div className="flex flex-col items-center mb-8">
                                    <div className="relative group">
                                        <img src={selectedExt.avatar} className="w-28 h-28 rounded-[30px] border-4 border-[#8B5CF6] object-cover shadow-2xl transition-transform group-hover:scale-105" />
                                        <label className="absolute bottom-1 right-1 bg-[#8B5CF6] p-2 rounded-xl cursor-pointer shadow-lg hover:scale-110 transition-transform">
                                            <span className="material-icons text-white text-sm">photo_camera</span>
                                            <input type="file" className="hidden" />
                                        </label>
                                    </div>
                                    <h3 className="mt-4 font-black text-xl">Interno #{selectedExt.ext}</h3>
                                    <p className="text-xs text-gray-500 font-bold uppercase tracking-widest">{selectedExt.name}</p>
                                </div>
                                <div className="space-y-6">
                                    <div><label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Nombre de Mostrar</label><input type="text" className="w-full bg-[#0B0E14] border border-[#30363d] rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8B5CF6] outline-none" defaultValue={selectedExt.name} /></div>
                                    <div><label className="text-[10px] font-black text-gray-500 uppercase mb-2 block">Contraseña del Dispositivo</label><input type="password" placeholder="••••••••" className="w-full bg-[#0B0E14] border border-[#30363d] rounded-xl p-3 text-sm focus:ring-1 focus:ring-[#8B5CF6] outline-none" /></div>
                                    <div className="bg-[#0B0E14] border border-[#30363d] rounded-2xl p-4 space-y-4">
                                        <div className="flex justify-between items-center"><div><div className="text-sm font-bold">Habilitar Video</div><div className="text-[10px] text-gray-500">Soporte H.264/VP8</div></div><input type="checkbox" className="w-5 h-5 rounded border-gray-800 text-purple-600 focus:ring-purple-600" defaultChecked /></div>
                                        <div className="flex justify-between items-center"><div><div className="text-sm font-bold">Apertura DTMF</div><div className="text-[10px] text-gray-500">Tonos para portería</div></div><input type="checkbox" className="w-5 h-5 rounded border-gray-800 text-purple-600 focus:ring-purple-600" defaultChecked /></div>
                                    </div>
                                    <button className="w-full bg-[#8B5CF6] text-white py-4 rounded-2xl font-black shadow-lg shadow-purple-500/20 hover:scale-[1.02] transition-transform mt-8" onClick={()=>setSelectedExt(null)}>GUARDAR CONFIGURACIÓN</button>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            );
        }

        function NavItem({ icon, label, active, collapsed, onClick }) {
            return (
                <div onClick={onClick} className={`flex items-center gap-4 px-4 py-3.5 rounded-2xl cursor-pointer transition-all ${active ? 'bg-[#8B5CF6]/10 text-[#8B5CF6] border border-[#8B5CF6]/10 shadow-inner shadow-purple-500/5' : 'text-gray-500 hover:text-white hover:bg-white/5'}`}>
                    <span className="material-icons">{icon}</span>
                    {!collapsed && <span className="text-sm font-black tracking-tight">{label}</span>}
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
