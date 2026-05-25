import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import {
  Home, Users, Headset, Layers, Network, PhoneCall, Activity,
  History, BarChart3, Workflow, Settings as SettingsIcon,
  Moon, Sun, LogOut, ChevronDown, Phone, Clock, User as UserIcon, type LucideIcon,
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useTheme } from '../contexts/ThemeContext';
import { BRAND } from '../lib/brand';
import { cn } from '../lib/utils';
import {
  DropdownMenu, DropdownMenuTrigger, DropdownMenuContent,
  DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator,
} from './ui/dropdown-menu';
import { UserAvatar } from './UserAvatar';
import { AmiStatusPill } from './AmiStatusPill';

interface NavItem { to: string; label: string; icon: LucideIcon; sub?: string }
interface NavGroup { id: string; label: string; icon: LucideIcon; items: NavItem[]; single?: boolean }

/** Estructura inspirada en la v1 — grupos con dropdowns. */
const nav: NavGroup[] = [
  { id: 'inicio', label: 'Inicio', icon: Home, single: true, items: [
    { to: '/', label: 'Dashboard', icon: Home, sub: 'Resumen general' },
  ]},
  { id: 'op', label: 'Operación', icon: Activity, items: [
    { to: '/hotdesking', label: 'Hotdesking', icon: Activity, sub: 'Asignación dinámica de agentes' },
    { to: '/llamadas', label: 'Llamadas en vivo', icon: PhoneCall, sub: 'Canales activos ahora' },
    { to: '/colas', label: 'Colas', icon: Layers, sub: 'Distribución y espera' },
    { to: '/grupos', label: 'Ring groups', icon: Network, sub: 'Timbrado simultáneo' },
  ]},
  { id: 'tel', label: 'Telefonía', icon: Phone, items: [
    { to: '/extensiones', label: 'Extensiones', icon: Users, sub: 'Internos SIP/PJSIP' },
    { to: '/agentes', label: 'Agentes', icon: Headset, sub: 'Personas y métricas' },
    { to: '/ivr', label: 'IVR', icon: Workflow, sub: 'Menús de voz visuales' },
  ]},
  { id: 'rep', label: 'Reportes', icon: BarChart3, items: [
    { to: '/cdr', label: 'CDR', icon: History, sub: 'Historial detallado' },
    { to: '/reportes', label: 'Reportes', icon: BarChart3, sub: 'KPIs y gráficos' },
  ]},
  // "Configuración" vive en el dropdown del avatar (es una preferencia personal,
  // no parte de la navegación operativa).
];

export default function Layout() {
  const { user, logout } = useAuth();
  const { theme, toggle } = useTheme();
  const location = useLocation();
  const navigate = useNavigate();

  const displayName = user?.fullName || user?.username || '';

  const isGroupActive = (group: NavGroup) =>
    group.items.some((it) =>
      it.to === '/' ? location.pathname === '/' : location.pathname.startsWith(it.to)
    );

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <header className="sticky top-0 z-30 h-14 border-b bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/75">
        <div className="flex h-full items-center gap-4 px-4">
          {/* Logo */}
          <NavLink to="/" className="flex items-center gap-2.5 shrink-0">
            <div className="h-9 w-9 rounded-lg bg-white flex items-center justify-center shadow-sm">
              <img src="/horizon-icon.svg" alt="" className="h-7 w-7" />
            </div>
            <div className="leading-tight">
              <div className="text-sm font-extrabold italic tracking-tight">{BRAND.logoText}</div>
              <div className="text-[9px] font-semibold text-muted-foreground tracking-wider">
                {BRAND.logoSub}
              </div>
            </div>
          </NavLink>

          {/* Nav — grupos */}
          <nav className="flex-1 flex items-center gap-1">
            {nav.map((g) => {
              const active = isGroupActive(g);
              const Icon = g.icon;

              // Grupos "single" → NavLink directo a su único item
              if (g.single) {
                const it = g.items[0];
                return (
                  <NavLink
                    key={g.id}
                    to={it.to}
                    end={it.to === '/'}
                    className={({ isActive }) =>
                      cn(
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap',
                        isActive
                          ? 'bg-primary/15 text-primary'
                          : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                      )
                    }
                  >
                    <Icon className="h-3.5 w-3.5" />
                    {g.label}
                  </NavLink>
                );
              }

              // Grupos con múltiples items → DropdownMenu
              return (
                <DropdownMenu key={g.id}>
                  <DropdownMenuTrigger asChild>
                    <button
                      className={cn(
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap outline-none',
                        active
                          ? 'bg-primary/15 text-primary'
                          : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                      )}
                    >
                      <Icon className="h-3.5 w-3.5" />
                      {g.label}
                      <ChevronDown className="h-3 w-3 opacity-60" />
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="start" className="w-64">
                    <DropdownMenuLabel className="text-[10px] uppercase tracking-wider text-muted-foreground font-bold">
                      {g.label}
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {g.items.map((it) => {
                      const ItemIcon = it.icon;
                      const itemActive =
                        it.to === '/' ? location.pathname === '/' : location.pathname.startsWith(it.to);
                      return (
                        <DropdownMenuItem
                          key={it.to}
                          onClick={() => navigate(it.to)}
                          className={cn(
                            'cursor-pointer py-2',
                            itemActive && 'bg-primary/10 text-primary'
                          )}
                        >
                          <ItemIcon className="h-4 w-4" />
                          <div className="flex flex-col gap-0.5 flex-1">
                            <span className="font-semibold">{it.label}</span>
                            {it.sub && (
                              <span className="text-[10px] text-muted-foreground">{it.sub}</span>
                            )}
                          </div>
                        </DropdownMenuItem>
                      );
                    })}
                  </DropdownMenuContent>
                </DropdownMenu>
              );
            })}
          </nav>

          {/* Clock */}
          <ClockBox />

          {/* AMI + Realtime status */}
          <AmiStatusPill />

          {/* User menu */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <button className="rounded-full outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:opacity-90 transition-opacity">
                <UserAvatar
                  userId={user?.id}
                  name={displayName}
                  hasAvatar={user?.hasAvatar}
                  cacheBust={user?.avatarUpdatedAt}
                  size="md"
                />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuLabel className="font-normal">
                <div className="flex flex-col space-y-1">
                  <p className="text-sm font-bold leading-none">{user?.fullName || user?.username}</p>
                  <p className="text-xs leading-none text-muted-foreground uppercase tracking-wider mt-1">{user?.role}</p>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => navigate('/perfil')}>
                <UserIcon className="h-4 w-4" />
                Mi perfil
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => navigate('/configuracion')}>
                <SettingsIcon className="h-4 w-4" />
                Configuración
              </DropdownMenuItem>
              <DropdownMenuItem onClick={toggle}>
                {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                Modo {theme === 'dark' ? 'Claro' : 'Oscuro'}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={logout} className="text-destructive focus:text-destructive">
                <LogOut className="h-4 w-4" />
                Cerrar sesión
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <main className="flex-1 p-6">
        <Outlet />
      </main>
    </div>
  );
}

/** Reloj en vivo del topbar — refresca cada segundo. */
function ClockBox() {
  const [now, setNow] = useState(() => new Date());
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(t);
  }, []);
  const time = now.toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  const date = now.toLocaleDateString('es-UY', { day: '2-digit', month: '2-digit', year: 'numeric' });
  return (
    <div
      className="hidden md:inline-flex items-center gap-2 px-3 py-1 rounded-md border border-border bg-muted/40"
      title="Hora local"
    >
      <Clock className="h-4 w-4 text-primary shrink-0" />
      <div className="leading-tight">
        <div className="text-xs font-bold tabular-nums">{time}</div>
        <div className="text-[9px] text-muted-foreground tabular-nums">{date}</div>
      </div>
    </div>
  );
}
