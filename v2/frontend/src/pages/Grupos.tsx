import { useEffect, useState } from 'react';
import { Network, Plus, Trash2, Loader2, AlertCircle } from 'lucide-react';
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

interface RingGroup {
  grpnum: string;
  description?: string;
  strategy: string;
  grptime: number;
  grplist: string;
}

const STRATEGIES = ['ringall', 'hunt', 'memoryhunt', 'firstavailable', 'firstnotonphone'];

export default function Grupos() {
  const [groups, setGroups] = useState<RingGroup[]>([]);
  const [loading, setLoading] = useState(true);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [showNew, setShowNew] = useState(false);
  const [editing, setEditing] = useState<RingGroup | null>(null);
  const [form, setForm] = useState({ grpnum: '', description: '', strategy: 'ringall', grptime: 20, grplist: '' });
  const [saving, setSaving] = useState(false);

  function load() {
    setLoading(true);
    api.get('/groups')
      .then((r) => { setGroups(r.data.groups || []); setAsteriskDown(false); setErr(null); })
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
        else setErr(e.response?.data?.error || 'Error');
      })
      .finally(() => setLoading(false));
  }
  useEffect(load, []);

  function openNew() {
    setForm({ grpnum: '', description: '', strategy: 'ringall', grptime: 20, grplist: '' });
    setEditing(null);
    setShowNew(true);
  }

  function openEdit(g: RingGroup) {
    setForm({ ...g, description: g.description || '' });
    setEditing(g);
    setShowNew(true);
  }

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) await api.put(`/groups/${editing.grpnum}`, form);
      else await api.post('/groups', form);
      setShowNew(false);
      load();
    } catch (e: any) {
      alert(e.response?.data?.error || 'Error guardando');
    } finally {
      setSaving(false);
    }
  }

  async function remove(grpnum: string) {
    if (!confirm(`¿Eliminar grupo ${grpnum}?`)) return;
    await api.delete(`/groups/${grpnum}`);
    load();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <Network className="h-6 w-6 text-primary" />
            Ring Groups
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            {groups.length} grupos de timbrado configurados.
          </p>
        </div>
        <Button onClick={openNew}><Plus className="h-4 w-4" />Nuevo grupo</Button>
      </div>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>Asterisk no disponible</strong></div>
          </CardContent>
        </Card>
      )}

      {err && <Card className="border-destructive/40 bg-destructive/5"><CardContent className="p-4 text-destructive text-sm">{err}</CardContent></Card>}

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Núm</TableHead>
              <TableHead>Descripción</TableHead>
              <TableHead>Estrategia</TableHead>
              <TableHead>Extensiones</TableHead>
              <TableHead className="text-right">Timeout</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {groups.map((g) => (
              <TableRow key={g.grpnum} className="cursor-pointer" onClick={() => openEdit(g)}>
                <TableCell className="font-mono font-semibold">{g.grpnum}</TableCell>
                <TableCell>{g.description || '—'}</TableCell>
                <TableCell><Badge variant="secondary">{g.strategy}</Badge></TableCell>
                <TableCell>
                  <div className="flex gap-1 flex-wrap">
                    {(g.grplist || '').split('-').filter(Boolean).slice(0, 6).map((e) => (
                      <Badge key={e} variant="outline" className="font-mono text-xs">{e}</Badge>
                    ))}
                    {(g.grplist || '').split('-').length > 6 && (
                      <Badge variant="outline" className="text-xs">+{(g.grplist || '').split('-').length - 6}</Badge>
                    )}
                  </div>
                </TableCell>
                <TableCell className="text-right text-sm">{g.grptime}s</TableCell>
                <TableCell className="text-right">
                  <Button size="sm" variant="ghost" className="text-destructive" onClick={(e) => { e.stopPropagation(); remove(g.grpnum); }}>
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </TableCell>
              </TableRow>
            ))}
            {!groups.length && !loading && !asteriskDown && (
              <TableRow><TableCell colSpan={6} className="text-center text-muted-foreground py-12">Sin ring groups.</TableCell></TableRow>
            )}
          </TableBody>
        </Table>
      </Card>

      <Dialog open={showNew} onOpenChange={setShowNew}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? `Editar grupo ${editing.grpnum}` : 'Nuevo ring group'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={save} className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Número *</Label>
                <Input required value={form.grpnum} onChange={(e) => setForm({ ...form, grpnum: e.target.value })} disabled={!!editing} placeholder="600" />
              </div>
              <div className="space-y-1.5">
                <Label>Timeout (seg)</Label>
                <Input type="number" value={form.grptime} onChange={(e) => setForm({ ...form, grptime: parseInt(e.target.value || '0') })} />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label>Descripción</Label>
              <Input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} placeholder="Equipo soporte" />
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
            <div className="space-y-1.5">
              <Label>Extensiones (separadas por guión)</Label>
              <Input value={form.grplist} onChange={(e) => setForm({ ...form, grplist: e.target.value })} placeholder="200-201-202" />
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
