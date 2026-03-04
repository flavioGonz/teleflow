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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0B0E14; color: #f8fafc; margin: 0; }
        .dark { background-color: #0B0E14; color: #f8fafc; }
        .light { background-color: #F9FAFB; color: #0f172a; }
        .card-dark { background-color: #161B22; border: 1px solid #30363d; }
        .card-light { background-color: #ffffff; border: 1px solid #e2e8f0; }
        .pulse-busy { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); } }
    </style>
</head>
<body>
    <div id="root"></div>
    <script type="text/babel">
        // VACUNA REACT
        if (window.__REACT_DEVTOOLS_GLOBAL_HOOK__) {
            window.__REACT_DEVTOOLS_GLOBAL_HOOK__.on = () => {};
            window.__REACT_DEVTOOLS_GLOBAL_HOOK__.inject = () => {};
        }

        const { useState, useEffect } = React;

        function App() {
            const [isDark, setIsDark] = useState(true);
            const [view, setView] = useState('extensiones');
            const [data, setData] = useState({ 
                pbx: { extensions: [], calls: [] }, 
                summary: { queue: 0, wait: '0:00', abandon: '0%' },
                system: { cpu: 0, uptime: '...' }
            });

            useEffect(() => { document.body.className = isDark ? 'dark' : 'light'; }, [isDark]);

            const refresh = () => {
                fetch('api/index.php?action=get_full_data')
                    .then(r => r.json())
                    .then(res => { if(res.summary) setData(res); })
                    .catch(e => console.log("Cargando datos..."));
            };

            useEffect(() => { refresh(); const i = setInterval(refresh, 3000); return ()=>clearInterval(i); }, []);

            const cardClass = isDark ? 'card-dark' : 'card-light';

            return (
                <div className="flex min-h-screen">
                    {/* SIDEBAR SIMPLE PARA FIX */}
                    <aside className={`w-64 fixed h-full ${isDark?'bg-[#161B22] border-[#30363d]':'bg-white border-[#e2e8f0]'} border-r p-6 flex flex-col`}>
                        <h2 className="text-2xl font-black text-[#8B5CF6] mb-10">Teleflow</h2>
                        <nav className="space-y-2">
                            <div className={`p-3 rounded-xl cursor-pointer ${view==='extensiones'?'bg-purple-500/10 text-purple-500':'text-gray-500'}`} onClick={()=>setView('extensiones')}>Extensiones</div>
                            <div className="p-3 text-gray-500 cursor-pointer" onClick={()=>setIsDark(!isDark)}>Tema: {isDark?'Dark':'Light'}</div>
                        </nav>
                    </aside>

                    <main className="flex-1 ml-64 p-10">
                        <header className="mb-10"><h1 className="text-3xl font-black uppercase">{view}</h1></header>

                        <div className="space-y-3">
                            {data.pbx.extensions.map(e => (
                                <div key={e.ext} className={`${cardClass} rounded-2xl p-4 grid grid-cols-4 items-center`}>
                                    <div className="flex items-center gap-4">
                                        <img src={e.avatar} className="w-10 h-10 rounded-xl" />
                                        <b>#{e.ext}</b>
                                    </div>
                                    <div className="text-center font-bold">{e.name}</div>
                                    <div className="text-center">
                                        <span className={`px-3 py-1 rounded-full text-[10px] font-black ${e.status==='ONLINE'?'text-green-500 bg-green-500/10':'text-gray-500 bg-gray-500/10'}`}>
                                            {e.status}
                                        </span>
                                    </div>
                                    <div className="text-right font-mono text-[10px] text-red-400">{e.ip}</div>
                                </div>
                            ))}
                        </div>

                        {/* BOTTOM SUMMARY FIX */}
                        <div className="grid grid-cols-3 gap-6 mt-10">
                            <div className={`${cardClass} rounded-2xl p-6`}>
                                <div className="text-[10px] font-bold text-gray-500 uppercase mb-2">Estado Cola</div>
                                <div className="text-4xl font-black">{data.summary.queue || 0}</div>
                            </div>
                            <div className={`${cardClass} rounded-2xl p-6`}>
                                <div className="text-[10px] font-bold text-gray-500 uppercase mb-2">Espera</div>
                                <div className="text-4xl font-black">{data.summary.wait || '0:00'}</div>
                            </div>
                            <div className={`${cardClass} rounded-2xl p-6`}>
                                <div className="text-[10px] font-bold text-gray-500 uppercase mb-2">Abandono</div>
                                <div className="text-4xl font-black">{data.summary.abandon || '0%'}</div>
                            </div>
                        </div>
                    </main>
                </div>
            );
        }
        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
