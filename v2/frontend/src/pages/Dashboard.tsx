import { useEffect, useState } from 'react';
import { Headset, PhoneCall, Hourglass, Users, AlertCircle, Activity } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { useSocketEvent } from '../hooks/useSocket';

interface Counts {
  extensions: number;
  queues: number;
  agentsLogged: number;
  agentsInPause: number;
}

export default function Dashboard() {
  const [counts, setCounts] = useState<Counts | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [lastEventAt, setLastEventAt] = useState<Date | null>(null);
  const [eventCount, setEventCount] = useState(0);

  const load = () => {
    api.get('/dashboard')
      .then((r) => { setCounts(r.data.counts); setAsteriskDown(false); setErr(null); })
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
        else setErr(e.response?.data?.error || 'Error cargando dashboard');
      });
  };

  useEffect(() => {
    load();
    const t = setInterval(load, 10000);
    return () => clearInterval(t);
  }, []);

  // Realtime — actualiza al recibir cualquier evento
  useSocketEvent('call_event', () => { setLastEventAt(new Date()); setEventCount((c) => c + 1); load(); });
  useSocketEvent('queue_update', () => { setLastEventAt(new Date()); setEventCount((c) => c + 1); load(); });
  useSocketEvent('peer_update', () => { setLastEventAt(new Date()); setEventCount((c) => c + 1); });

  return (
    <div className="space-y-6">
      {/* Hero */}
      <Card className="overflow-hidden relative">
        <div
          className="absolute top-0 right-0 w-80 h-80 rounded-full pointer-events-none -translate-y-1/2 translate-x-1/2"
          style={{ background: 'radial-gradient(circle, hsl(var(--primary) / 0.18), transparent 70%)' }}
        />
        <CardContent className="p-6 flex items-center gap-5">
          <div className="h-14 w-14 rounded-xl bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center shadow-lg shadow-primary/30 relative">
            <Activity className="h-7 w-7 text-white" />
            <span className="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full bg-green-500 ring-2 ring-card animate-pulse" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2 text-[10px] font-extrabold tracking-widest uppercase text-muted-foreground">
              <span className="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse" />
              EN VIVO · {new Date().toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' })}
            </div>
            <h1 className="text-2xl font-black tracking-tight leading-tight mt-0.5">HznFlow Operations</h1>
            <p className="text-xs text-muted-foreground mt-1">
              {counts ? `${counts.agentsLogged} agentes activos · ${counts.queues} colas configuradas` : '—'}
              {lastEventAt && ` · ${eventCount} eventos recibidos`}
            </p>
          </div>
        </CardContent>
      </Card>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm">
              <strong>Asterisk no disponible</strong> — no se pueden leer extensiones ni colas.
              Conectate a la red de Horizon o VPN.
            </div>
          </CardContent>
        </Card>
      )}

      {err && (
        <Card className="border-destructive/40 bg-destructive/5">
          <CardContent className="p-4 text-destructive text-sm">{err}</CardContent>
        </Card>
      )}

      {/* KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <KPI label="Agentes logueados" value={counts?.agentsLogged ?? '—'} icon={Headset} color="bg-green-500" />
        <KPI label="Agentes en pausa" value={counts?.agentsInPause ?? '—'} icon={Hourglass} color="bg-amber-500" />
        <KPI label="Extensiones SIP" value={counts?.extensions ?? '—'} icon={Users} color="bg-blue-500" />
        <KPI label="Colas configuradas" value={counts?.queues ?? '—'} icon={PhoneCall} color="bg-violet-500" />
      </div>
    </div>
  );
}

function KPI({
  label, value, icon: Icon, color,
}: { label: string; value: any; icon: any; color: string }) {
  return (
    <Card className="relative overflow-hidden hover:bg-accent/50 transition-colors">
      <CardContent className="p-5">
        <div className="flex items-center gap-3 mb-3">
          <div className={`h-9 w-9 rounded-lg ${color} bg-opacity-100 flex items-center justify-center shadow`}>
            <Icon className="h-4 w-4 text-white" />
          </div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{label}</span>
        </div>
        <div className="text-3xl font-black tracking-tight tabular-nums">{value}</div>
      </CardContent>
    </Card>
  );
}
