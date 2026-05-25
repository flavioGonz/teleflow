import { useEffect, useState } from 'react';
import { Layers, Plus, Trash2, Users, Loader2, AlertCircle } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '../components/ui/dialog';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { useSocketEvent } from '../hooks/useSocket';

interface Queue {
  id: string;
  name?: string;
  strategy: string;
  timeout: number;
  retry: number;
  weight: number;
  maxlen: number;
  members: { interface: string }[];
}

const STRATEGIES = ['ringall', 'leastrecent', 'fewestcalls', 'random', 'rrmemory', 'linear', 'wrandom'];

export default function Colas() {
  const [queues, setQueues] = useState<Queue[]>([]);
  const [loading, setLoading] = useState(true);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [showNew, setShowNew] = useState(false);
  const [editing, setEditing] = useState<Queue | null>(null);
  const [form, setForm] = useState({ id: '', name: '', strategy: 'ringall', timeout: 15, retry: 5, weight: 0, maxlen: 0 });
  const [saving, setSaving] = useState(false);

  function load() {
    setLoading(true);
    api.get('/queues')
      .then((r) => { setQueues(r.data.queues || []); setAsteriskDown(false); setErr(null); })
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
        else setErr(e.response?.data?.error || 'Error');
      })
      .finally(() => setLoading(false));
  }
  useEffect(load, []);

  useSocketEvent('queue_update', () => load());

  function openNew() {
    setForm({ id: '', name: '', strategy: 'ringall', timeout: 15, retry: 5, weight: 0, maxlen: 0 });
    setEditing(null);
    setShowNew(true);
  }

  function openEdit(q: Queue) {
    setForm({
      id: q.id, name: q.name || '', strategy: q.strategy || 'ringall',
      timeout: q.timeout, retry: q.retry, weight: q.weight, maxlen: q.maxlen,
    });
    setEditing(q);
    setShowNew(true);
  }

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) {
        await api.put(`/queues/${editing.id}`, form);
      } else {
        await api.post('/queues', form);
      }
      setShowNew(false);
      load();
    } catch (e: any) {
      alert(e.response?.data?.error || 'Error guardando cola');
    } finally {
      setSaving(false);
    }
  }

  async function remove(id: string) {
    if (!confirm(`¿Eliminar cola ${id}?`)) return;
    await api.delete(`/queues/${id}`);
    load();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <Layers className="h-6 w-6 text-primary" />
            Colas
          </h1>
          <p className="text-sm text-muted-foreground mt-1">{queues.length} colas configuradas en el PBX.</p>
        </div>
        <Button onClick={openNew}><Plus className="h-4 w-4" />Nueva cola</Button>
      </div>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>Asterisk no disponible</strong> — colas viven en el PBX.</div>
          </CardContent>
        </Card>
      )}

      {err && <Card className="border-destructive/40 bg-destructive/5"><CardContent className="p-4 text-destructive text-sm">{err}</CardContent></Card>}

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Nombre</TableHead>
              <TableHead>Estrategia</TableHead>
              <TableHead className="text-center">Miembros</TableHead>
              <TableHead className="text-right">Timeout</TableHead>
              <TableHead className="text-right">Max len</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {queues.map((q) => (
              <TableRow key={q.id} className="cursor-pointer" onClick={() => openEdit(q)}>
                <TableCell className="font-mono font-semibold">{q.id}</TableCell>
                <TableCell>{q.name || '—'}</TableCell>
                <TableCell><Badge variant="secondary">{q.strategy}</Badge></TableCell>
                <TableCell className="text-center">
                  <Badge variant="outline" className="gap-1">
                    <Users className="h-3 w-3" />{q.members?.length || 0}
                  </Badge>
                </TableCell>
                <TableCell className="text-right text-sm">{q.timeout}s</TableCell>
                <TableCell className="text-right text-sm">{q.maxlen || '∞'}</TableCell>
                <TableCell className="text-right">
                  <Button size="sm" variant="ghost" className="text-destructive" onClick={(e) => { e.stopPropagation(); remove(q.id); }}>
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </TableCell>
              </TableRow>
            ))}
            {!queues.length && !loading && !asteriskDown && (
              <TableRow><TableCell colSpan={7} className="text-center text-muted-foreground py-12">Sin colas configuradas.</TableCell></TableRow>
            )}
          </TableBody>
        </Table>
      </Card>

      <Dialog open={showNew} onOpenChange={setShowNew}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? `Editar cola ${editing.id}` : 'Nueva cola'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={save} className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>ID *</Label>
                <Input required value={form.id} onChange={(e) => setForm({ ...form, id: e.target.value })} disabled={!!editing} placeholder="Q8000" />
              </div>
              <div className="space-y-1.5">
                <Label>Nombre</Label>
                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Soporte" />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label>Estrategia</Label>
              <select
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={form.strategy}
                onChange={(e) => setForm({ ...form, strategy: e.target.value })}
              >
                {STRATEGIES.map((s) => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
            <div className="grid grid-cols-4 gap-3">
              <div className="space-y-1.5">
                <Label>Timeout</Label>
                <Input type="number" value={form.timeout} onChange={(e) => setForm({ ...form, timeout: parseInt(e.target.value || '0') })} />
              </div>
              <div className="space-y-1.5">
                <Label>Retry</Label>
                <Input type="number" value={form.retry} onChange={(e) => setForm({ ...form, retry: parseInt(e.target.value || '0') })} />
              </div>
              <div className="space-y-1.5">
                <Label>Weight</Label>
                <Input type="number" value={form.weight} onChange={(e) => setForm({ ...form, weight: parseInt(e.target.value || '0') })} />
              </div>
              <div className="space-y-1.5">
                <Label>Max len</Label>
                <Input type="number" value={form.maxlen} onChange={(e) => setForm({ ...form, maxlen: parseInt(e.target.value || '0') })} />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setShowNew(false)}>Cancelar</Button>
              <Button type="submit" disabled={saving}>
                {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                {editing ? 'Guardar' : 'Crear'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
