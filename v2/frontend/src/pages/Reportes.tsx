import { useEffect, useState } from 'react';
import { BarChart3, Loader2, AlertCircle, Phone, PhoneCall, PhoneMissed, Headset } from 'lucide-react';
import {
  ResponsiveContainer, PieChart, Pie, Cell, Legend, Tooltip,
  BarChart, Bar, XAxis, YAxis, CartesianGrid,
} from 'recharts';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

const today = () => new Date().toISOString().slice(0, 10);
const daysAgo = (n: number) => new Date(Date.now() - n * 86400_000).toISOString().slice(0, 10);

interface Summary {
  total: number;
  answered: number;
  no_answer: number;
  busy: number;
  failed: number;
  avg_talk: number;
  avg_wait: number;
}

interface AgentReport {
  id: number; number: string; name: string;
  sessions: number; total_login_sec: number; total_paused_sec: number;
}

function fmtSec(s: number) {
  if (!s) return '0s';
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = Math.round(s % 60);
  if (h) return `${h}h ${m}m`;
  if (m) return `${m}m ${sec}s`;
  return `${sec}s`;
}

export default function Reportes() {
  const [summary, setSummary] = useState<Summary | null>(null);
  const [agents, setAgents] = useState<AgentReport[]>([]);
  const [loading, setLoading] = useState(false);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [start, setStart] = useState(daysAgo(30));
  const [end, setEnd] = useState(today());

  async function load() {
    setLoading(true);
    setAsteriskDown(false);
    try {
      const [s, a] = await Promise.all([
        api.get('/reports/summary', { params: { start, end } }),
        api.get('/reports/by-agent', { params: { start, end } }),
      ]);
      setSummary(s.data.stats);
      setAgents(a.data.agents);
    } catch (e: any) {
      if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  // Resultados de llamadas (lo que antes en el CDR se llamaba "disposition")
  const pieData = summary ? [
    { name: 'Atendida', value: Number(summary.answered) || 0, color: '#22c55e' },
    { name: 'No atendida', value: Number(summary.no_answer) || 0, color: '#f59e0b' },
    { name: 'Ocupado', value: Number(summary.busy) || 0, color: '#ef4444' },
    { name: 'Falló', value: Number(summary.failed) || 0, color: '#71717a' },
  ].filter((d) => d.value > 0) : [];

  const agentChartData = agents
    .filter((a) => a.total_login_sec > 0)
    .sort((a, b) => b.total_login_sec - a.total_login_sec)
    .slice(0, 10)
    .map((a) => ({
      name: a.name.length > 14 ? a.name.slice(0, 14) + '…' : a.name,
      Logueado: Math.round(a.total_login_sec / 3600),
      Pausado: Math.round(a.total_paused_sec / 3600),
    }));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
          <BarChart3 className="h-6 w-6 text-primary" />
          Reportes
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          KPIs agregados del callcenter para el período seleccionado.
        </p>
      </div>

      <Card>
        <CardContent className="p-4 flex flex-wrap items-end gap-3">
          <div className="space-y-1">
            <Label className="text-xs">Desde</Label>
            <Input type="date" value={start} onChange={(e) => setStart(e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label className="text-xs">Hasta</Label>
            <Input type="date" value={end} onChange={(e) => setEnd(e.target.value)} />
          </div>
          <Button onClick={load} disabled={loading}>
            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            Actualizar
          </Button>
        </CardContent>
      </Card>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>CDR DB no disponible</strong> — los KPIs vienen del CDR.</div>
          </CardContent>
        </Card>
      )}

      {/* KPIs */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <KPI label="Total llamadas" value={Number(summary?.total) || 0} icon={Phone} color="bg-blue-500" />
        <KPI label="Atendidas" value={Number(summary?.answered) || 0} icon={PhoneCall} color="bg-green-500" />
        <KPI label="No atendidas" value={Number(summary?.no_answer) || 0} icon={PhoneMissed} color="bg-amber-500" />
        <KPI label="Promedio conv." value={fmtSec(Math.round(Number(summary?.avg_talk) || 0))} icon={Headset} color="bg-violet-500" />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Pie: distribución por disposición */}
        <Card>
          <CardContent className="p-5">
            <h3 className="font-semibold mb-4">Resultado de llamadas</h3>
            {pieData.length ? (
              <div style={{ width: '100%', height: 280 }}>
                <ResponsiveContainer>
                  <PieChart>
                    <Pie
                      data={pieData} cx="50%" cy="50%" innerRadius={60} outerRadius={100}
                      paddingAngle={2} dataKey="value" label={(d: any) => `${d.name}: ${d.value}`}
                    >
                      {pieData.map((d) => <Cell key={d.name} fill={d.color} />)}
                    </Pie>
                    <Tooltip contentStyle={{ background: 'hsl(var(--card))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            ) : (
              <div className="h-[280px] flex items-center justify-center text-muted-foreground text-sm">Sin datos</div>
            )}
          </CardContent>
        </Card>

        {/* Bar: top agentes por tiempo logueado */}
        <Card>
          <CardContent className="p-5">
            <h3 className="font-semibold mb-4">Top agentes — horas logueadas</h3>
            {agentChartData.length ? (
              <div style={{ width: '100%', height: 280 }}>
                <ResponsiveContainer>
                  <BarChart data={agentChartData}>
                    <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" />
                    <XAxis dataKey="name" tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} />
                    <YAxis tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} />
                    <Tooltip contentStyle={{ background: 'hsl(var(--card))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} />
                    <Legend />
                    <Bar dataKey="Logueado" fill="#4eb857" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Pausado" fill="#f59e0b" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            ) : (
              <div className="h-[280px] flex items-center justify-center text-muted-foreground text-sm">Sin sesiones de agentes</div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function KPI({ label, value, icon: Icon, color }: { label: string; value: any; icon: any; color: string }) {
  return (
    <Card>
      <CardContent className="p-4 flex items-center gap-3">
        <div className={`h-9 w-9 rounded-lg ${color} flex items-center justify-center shadow shrink-0`}>
          <Icon className="h-4 w-4 text-white" />
        </div>
        <div className="min-w-0">
          <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground truncate">{label}</div>
          <div className="text-xl font-black tabular-nums">{value}</div>
        </div>
      </CardContent>
    </Card>
  );
}
