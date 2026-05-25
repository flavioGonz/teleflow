import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Loader2, ShieldCheck, Headset, User, Lock, Eye, EyeOff, AlertCircle, ArrowRight,
  LayoutDashboard, BadgeCheck, Phone, KeyRound, BarChart3, Radio,
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { BRAND } from '../lib/brand';
import { cn } from '../lib/utils';

type Role = 'admin' | 'agent';

export default function Login() {
  const { login } = useAuth();
  const nav = useNavigate();

  const [role, setRole] = useState<Role>('agent');
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [callbackExt, setCallbackExt] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setErr(null);
    setLoading(true);
    try {
      if (role === 'admin') {
        await login(username, password);
        nav('/');
      } else {
        // El login de agente todavía no está implementado en v2
        setErr('El login de agente aún no está disponible en esta versión. Usá el modo Administrador.');
      }
    } catch (e: any) {
      setErr(e.response?.data?.error || 'Error de conexión con el servidor.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen grid lg:grid-cols-[1fr_1.1fr]">
      {/* ─── FORM (izquierda) ─── */}
      <div className="flex items-center justify-center p-6 lg:p-12 bg-background relative">
        <div className="w-full max-w-md">
          {/* Logo mobile (solo visible <lg) */}
          <div className="lg:hidden flex items-center gap-3 mb-6">
            <div className="h-12 w-12 rounded-xl bg-white flex items-center justify-center shadow-sm">
              <img src="/horizon-icon.svg" alt="" className="h-10 w-10" />
            </div>
            <div className="text-xl font-extrabold italic tracking-tight">{BRAND.logoText}</div>
          </div>

          {/* Heading */}
          <div className="mb-6">
            <h2 className="text-3xl font-bold tracking-tight">Bienvenido</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Iniciá sesión para acceder al panel de control
            </p>
          </div>

          {/* Tabs Agente / Admin — Agente primero porque es el más usado */}
          <div className="grid grid-cols-2 p-1 rounded-lg bg-muted/40 border border-border mb-5">
            <RoleTab active={role === 'agent'} onClick={() => setRole('agent')} icon={Headset}>
              Agente
            </RoleTab>
            <RoleTab active={role === 'admin'} onClick={() => setRole('admin')} icon={ShieldCheck}>
              Administrador
            </RoleTab>
          </div>

          {/* Form */}
          <form onSubmit={submit} className="space-y-4">
            {/* Usuario */}
            <div>
              <label className="block text-xs font-semibold mb-1.5 text-muted-foreground">
                {role === 'agent' ? 'Número de agente' : 'Usuario'}
              </label>
              <div className="relative">
                {role === 'agent' ? (
                  <BadgeCheck className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                ) : (
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                )}
                <input
                  className="w-full h-11 pl-10 pr-3 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
                  type="text"
                  placeholder={role === 'agent' ? 'ej. 200' : 'admin'}
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  autoComplete="username"
                  required
                  autoFocus
                />
              </div>
            </div>

            {/* Password */}
            <div>
              <label className="block text-xs font-semibold mb-1.5 text-muted-foreground">Contraseña</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <input
                  className="w-full h-11 pl-10 pr-11 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
                  type={showPass ? 'text' : 'password'}
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="current-password"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPass((s) => !s)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted/50 transition"
                  aria-label="Mostrar/ocultar contraseña"
                >
                  {showPass ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            {/* Extensión callback — solo agente */}
            {role === 'agent' && (
              <div>
                <label className="block text-xs font-semibold mb-1.5 text-muted-foreground">
                  Extensión de callback
                </label>
                <div className="relative">
                  <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <input
                    className="w-full h-11 pl-10 pr-3 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
                    type="text"
                    placeholder="ej. 9006"
                    value={callbackExt}
                    onChange={(e) => setCallbackExt(e.target.value)}
                    required
                  />
                </div>
                <p className="text-[10px] text-muted-foreground mt-1">
                  El teléfono donde recibirás las llamadas hoy
                </p>
              </div>
            )}

            {/* Error */}
            {err && (
              <div role="alert" className="flex items-start gap-2 px-3 py-2.5 rounded-md border border-destructive/30 bg-destructive/5 text-destructive text-sm">
                <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                <span>{err}</span>
              </div>
            )}

            {/* Submit */}
            <button
              type="submit"
              disabled={loading}
              className="w-full h-11 inline-flex items-center justify-center gap-2 rounded-md bg-primary text-primary-foreground font-bold text-sm hover:brightness-110 transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {loading ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Verificando…
                </>
              ) : (
                <>
                  Iniciar sesión
                  <ArrowRight className="h-4 w-4" />
                </>
              )}
            </button>
          </form>

          {/* Footer */}
          <div className="mt-8 flex items-center justify-center gap-2 text-[10px] text-muted-foreground">
            <span>{BRAND.appName}</span>
            <span>·</span>
            <span>© {BRAND.copyright} {new Date().getFullYear()}</span>
          </div>
        </div>
      </div>

      {/* ─── HERO (derecha — solo desktop) ─── */}
      <div className="hidden lg:block relative overflow-hidden">
        {/* Imagen de fondo */}
        <div
          className="absolute inset-0 bg-cover bg-center"
          style={{ backgroundImage: "url('/login-bg.jpg')" }}
        />
        {/* Overlay verde Horizon + degradé */}
        <div
          className="absolute inset-0"
          style={{
            background:
              'linear-gradient(135deg, rgba(78, 184, 87, 0.55) 0%, rgba(35, 31, 32, 0.85) 100%)',
          }}
        />

        {/* Content */}
        <div className="relative h-full flex flex-col p-12">
          {/* Logo top-right */}
          <div className="flex items-center justify-end gap-3">
            <div className="text-right">
              <div className="text-2xl font-black tracking-widest text-white">HORIZON</div>
              <div className="text-[10px] font-bold tracking-[0.3em] text-primary mt-0.5">SEGURIDAD</div>
            </div>
            <div className="h-14 w-14 rounded-full bg-white/95 flex items-center justify-center shadow-lg">
              <img src="/horizon-icon.svg" alt="" className="h-11 w-11" />
            </div>
          </div>

          {/* Tagline */}
          <div className="flex-1 flex flex-col justify-center max-w-lg">
            {role === 'admin' ? (
              <>
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/20 text-primary text-xs font-bold uppercase tracking-wider self-start mb-5 backdrop-blur">
                  <ShieldCheck className="h-3.5 w-3.5" />
                  Acceso administrador
                </span>
                <h1 className="text-4xl font-black tracking-tight text-white leading-tight">
                  Panel de control unificado
                </h1>
                <p className="text-sm text-white/80 mt-4 leading-relaxed">
                  Gestioná extensiones, colas, agentes y reportes desde un único lugar.
                  Monitoreo en tiempo real del callcenter y la PBX.
                </p>
                <ul className="space-y-3 mt-7">
                  <FeatureItem icon={LayoutDashboard} title="Dashboard live" sub="KPIs y signos vitales del PBX en vivo" />
                  <FeatureItem icon={Headset} title="Hotdesking dinámico" sub="Login/logout de agentes sin reaprovisionar SIP" />
                  <FeatureItem icon={BarChart3} title="Reportes detallados" sub="Por agente, cola y CDR · export PDF/Excel" />
                  <FeatureItem icon={Radio} title="Monitoreo real-time" sub="Eventos AMI persistidos vía socket.io" />
                </ul>
              </>
            ) : (
              <>
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-bold uppercase tracking-wider self-start mb-5 backdrop-blur">
                  <Headset className="h-3.5 w-3.5" />
                  Portal del agente
                </span>
                <h1 className="text-4xl font-black tracking-tight text-white leading-tight">
                  Tu consola de trabajo
                </h1>
                <p className="text-sm text-white/80 mt-4 leading-relaxed">
                  Iniciá sesión con tu número de agente y la extensión del teléfono
                  donde vas a recibir las llamadas hoy.
                </p>
                <ul className="space-y-3 mt-7">
                  <FeatureItem icon={BadgeCheck} title="Número de agente" sub="El asignado en Issabel (ej. 200)" />
                  <FeatureItem icon={KeyRound} title="Contraseña personal" sub="La definida en tu perfil del callcenter" />
                  <FeatureItem icon={Phone} title="Extensión callback" sub="El teléfono donde vas a operar hoy (ej. 9006)" />
                  <FeatureItem icon={Headset} title="Alternativa por teléfono" sub="Marcá *7700 desde tu interno para login + cola" />
                </ul>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function RoleTab({
  active, onClick, icon: Icon, children,
}: { active: boolean; onClick: () => void; icon: any; children: React.ReactNode }) {
  return (
    <button
      type="button"
      role="tab"
      aria-selected={active}
      onClick={onClick}
      className={cn(
        'flex items-center justify-center gap-2 py-2 rounded-md text-xs font-bold transition-all',
        active
          ? 'bg-card text-foreground shadow-sm'
          : 'text-muted-foreground hover:text-foreground'
      )}
    >
      <Icon className="h-3.5 w-3.5" />
      {children}
    </button>
  );
}

function FeatureItem({ icon: Icon, title, sub }: { icon: any; title: string; sub: string }) {
  return (
    <li className="flex items-start gap-3">
      <div className="h-9 w-9 rounded-lg bg-white/15 backdrop-blur flex items-center justify-center shrink-0">
        <Icon className="h-4 w-4 text-white" />
      </div>
      <div className="flex flex-col">
        <strong className="text-sm text-white font-bold">{title}</strong>
        <span className="text-xs text-white/70">{sub}</span>
      </div>
    </li>
  );
}
