import { useEffect, useState } from 'react';
import { Activity, LogIn, LogOut, PauseCircle, PlayCircle, Plus } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogTrigger,
} from '../components/ui/dialog';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { useSocketEvent } from '../hooks/useSocket';

interface SessionAgent {
  session_id: number;
  agent_id: number;
  agent_number: string;
  name: string;
  extension: string;
  login_time: string;
  queues: string[];
  logged_in: boolean;
}

export default function Hotdesking() {
  const [agents, setAgents] = useState<SessionAgent[]>([]);
  const [loading, setLoading] = useState(true);
  const [showLogin, setShowLogin] = useState(false);
  const [form, setForm] = useState({ agent_number: '', extension: '', queues: '' });

  function load() {
    api.get('/hotdesking').then((r) => setAgents(r.data.agents || [])).finally(() => setLoading(false));
  }

  useEffect(() => {
    load();
    const t = setInterval(load, 8000);
    return () => clearInterval(t);
  }, []);

  useSocketEvent('agent_logout', () => load());
  useSocketEvent('agent_pause', () => load());

  async function doLogin(e: React.FormEvent) {
    e.preventDefault();
    try {
      await api.post('/hotdesking/login', {
        agent_number: form.agent_number,
        extension: form.extension,
        queues: form.queues.split(',').map((s) => s.trim()).filter(Boolean),
      });
      setShowLogin(false);
      setForm({ agent_number: '', extension: '', queues: '' });
      load();
    } catch (e: any) {
      alert('Error: ' + (e.response?.data?.message || e.response?.data?.error));
    }
  }

  async function doLogout(agent_number: string) {
    if (!confirm(`¿Cerrar sesión del agente ${agent_number}?`)) return;
    try {
      await api.post('/hotdesking/logout', { agent_number });
      load();
    } catch (e: any) {
      alert('Error: ' + (e.response?.data?.message || e.response?.data?.error));
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <Activity className="h-6 w-6 text-primary" />
            Hotdesking
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            Asignación dinámica de agentes a extensiones físicas y colas.
          </p>
        </div>
        <Dialog open={showLogin} onOpenChange={setShowLogin}>
          <DialogTrigger asChild>
            <Button><Plus className="h-4 w-4" />Login agente</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Login dinámico</DialogTitle>
              <DialogDescription>
                Asigná un agente a una extensión SIP y opcionalmente a colas.
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={doLogin} className="space-y-4">
              <div className="space-y-1.5">
                <Label>Número de agente</Label>
                <Input required value={form.agent_number} onChange={(e) => setForm({ ...form, agent_number: e.target.value })} placeholder="200" />
              </div>
              <div className="space-y-1.5">
                <Label>Extensión SIP</Label>
                <Input required value={form.extension} onChange={(e) => setForm({ ...form, extension: e.target.value })} placeholder="9001" />
              </div>
              <div className="space-y-1.5">
                <Label>Colas (CSV)</Label>
                <Input value={form.queues} onChange={(e) => setForm({ ...form, queues: e.target.value })} placeholder="Q8000, Q8001" />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setShowLogin(false)}>Cancelar</Button>
                <Button type="submit"><LogIn className="h-4 w-4" />Login</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Agente</TableHead>
              <TableHead>Extensión</TableHead>
              <TableHead>Logueado</TableHead>
              <TableHead>Colas</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {agents.map((a) => (
              <TableRow key={a.session_id}>
                <TableCell>
                  <div className="flex items-center gap-3">
                    <div className="h-9 w-9 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-bold text-sm">
                      {a.name.split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase()}
                    </div>
                    <div>
                      <div className="font-semibold">{a.name}</div>
                      <div className="text-xs text-muted-foreground font-mono">#{a.agent_number}</div>
                    </div>
                  </div>
                </TableCell>
                <TableCell className="font-mono">{a.extension}</TableCell>
                <TableCell>
                  <span className="text-xs text-muted-foreground">
                    {new Date(a.login_time).toLocaleString('es-UY', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })}
                  </span>
                </TableCell>
                <TableCell>
                  <div className="flex gap-1 flex-wrap">
                    {a.queues.length > 0
                      ? a.queues.map((q) => <Badge key={q} variant="secondary">{q}</Badge>)
                      : <span className="text-xs text-muted-foreground">—</span>}
                  </div>
                </TableCell>
                <TableCell className="text-right">
                  <Button size="sm" variant="ghost" className="text-destructive" onClick={() => doLogout(a.agent_number)}>
                    <LogOut className="h-3 w-3" />Logout
                  </Button>
                </TableCell>
              </TableRow>
            ))}
            {!agents.length && !loading && (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground py-12">
                  <PauseCircle className="h-8 w-8 mx-auto mb-2 opacity-30" />
                  Ningún agente logueado. Usá "Login agente" para empezar.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
}
