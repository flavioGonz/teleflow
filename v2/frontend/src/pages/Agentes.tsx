import { useEffect, useState } from 'react';
import { Plus, Trash2, Headset, Loader2, Pencil, Clock } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '../components/ui/dialog';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { cn } from '../lib/utils';

type Day = 'MON' | 'TUE' | 'WED' | 'THU' | 'FRI' | 'SAT' | 'SUN';
const DAYS: { value: Day; label: string }[] = [
  { value: 'MON', label: 'L' }, { value: 'TUE', label: 'M' }, { value: 'WED', label: 'X' },
  { value: 'THU', label: 'J' },  { value: 'FRI', label: 'V' }, { value: 'SAT', label: 'S' },
  { value: 'SUN', label: 'D' },
];

interface Shift { start: string; end: string; days: Day[] }

interface Agent {
  id: number;
  number: string;
  name: string;
  email?: string | null;
  phone?: string | null;
  active: boolean;
  sector?: string | null;
  shift?: Shift | null;
  notes?: string | null;
  logged_in: boolean;
  current_extension: string | null;
  paused: boolean;
  pause_reason: string | null;
}

interface FormData {
  name: string;
  email: string;
  phone: string;
  sector: string;
  notes: string;
  // Shift toggle: si false, no se envía. Si true, mandamos el objeto.
  hasShift: boolean;
  shiftStart: string;
  shiftEnd: string;
  shiftDays: Day[];
}

const emptyForm: FormData = {
  name: '', email: '', phone: '', sector: '', notes: '',
  hasShift: false, shiftStart: '09:00', shiftEnd: '18:00', shiftDays: ['MON', 'TUE', 'WED', 'THU', 'FRI'],
};

export default function Agentes() {
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editingNumber, setEditingNumber] = useState<string | null>(null);
  const [form, setForm] = useState<FormData>(emptyForm);
  const [saving, setSaving] = useState(false);

  function load() {
    setLoading(true);
    api.get('/agents').then((r) => setAgents(r.data.agents)).finally(() => setLoading(false));
  }
  useEffect(load, []);

  function openCreate() {
    setEditingId(null);
    setEditingNumber(null);
    setForm(emptyForm);
    setDialogOpen(true);
  }

  function openEdit(a: Agent) {
    setEditingId(a.id);
    setEditingNumber(a.number);
    setForm({
      name: a.name,
      email: a.email || '',
      phone: a.phone || '',
      sector: a.sector || '',
      notes: a.notes || '',
      hasShift: !!a.shift,
      shiftStart: a.shift?.start || '09:00',
      shiftEnd: a.shift?.end || '18:00',
      shiftDays: a.shift?.days || ['MON', 'TUE', 'WED', 'THU', 'FRI'],
    });
    setDialogOpen(true);
  }

  function toggleDay(d: Day) {
    setForm((f) => ({
      ...f,
      shiftDays: f.shiftDays.includes(d) ? f.shiftDays.filter((x) => x !== d) : [...f.shiftDays, d],
    }));
  }

  async function save(e: React.FormEvent) {
    e.preventDefault();
    if (form.hasShift && form.shiftDays.length === 0) {
      alert('Si configurás un turno, seleccioná al menos un día');
      return;
    }
    setSaving(true);
    const payload: any = {
      name: form.name,
      email: form.email || null,
      phone: form.phone || null,
      sector: form.sector || null,
      notes: form.notes || null,
      shift: form.hasShift
        ? { start: form.shiftStart, end: form.shiftEnd, days: form.shiftDays }
        : null,
    };
    try {
      if (editingId == null) await api.post('/agents', payload);
      else await api.put(`/agents/${editingId}`, payload);
      setDialogOpen(false);
      load();
    } catch (e: any) {
      alert(e.response?.data?.error?.fieldErrors
        ? JSON.stringify(e.response.data.error.fieldErrors)
        : e.response?.data?.error || 'Error guardando agente');
    } finally {
      setSaving(false);
    }
  }

  async function remove(id: number) {
    if (!confirm('¿Eliminar agente? Esta acción no se puede deshacer.')) return;
    await api.delete(`/agents/${id}`);
    load();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <Headset className="h-6 w-6 text-primary" />
            Agentes
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            {agents.length} agentes registrados · {agents.filter((a) => a.logged_in).length} en línea
          </p>
        </div>
        <Button onClick={openCreate}><Plus className="h-4 w-4" />Nuevo agente</Button>
      </div>

      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-16">ID</TableHead>
              <TableHead>Agente</TableHead>
              <TableHead>Contacto</TableHead>
              <TableHead>Sector</TableHead>
              <TableHead>Turno</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {agents.map((a) => (
              <TableRow key={a.id}>
                <TableCell className="font-mono text-xs text-muted-foreground">#{a.number}</TableCell>
                <TableCell>
                  <div className="flex items-center gap-3">
                    <div className="h-9 w-9 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-bold text-sm">
                      {(a.name || '?').split(' ').map((s) => s[0]).slice(0, 2).join('').toUpperCase()}
                    </div>
                    <div className="font-semibold">{a.name}</div>
                  </div>
                </TableCell>
                <TableCell className="text-sm">
                  <div>{a.email || <span className="text-muted-foreground">—</span>}</div>
                  <div className="text-xs text-muted-foreground">{a.phone || ''}</div>
                </TableCell>
                <TableCell className="text-sm">
                  {a.sector || <span className="text-muted-foreground">—</span>}
                </TableCell>
                <TableCell className="text-xs">
                  {a.shift ? <ShiftBadge shift={a.shift} /> : <span className="text-muted-foreground">—</span>}
                </TableCell>
                <TableCell>
                  {!a.active ? (
                    <Badge variant="outline">Inactivo</Badge>
                  ) : a.logged_in ? (
                    a.paused ? (
                      <Badge variant="warning">Pausa: {a.pause_reason}</Badge>
                    ) : (
                      <Badge variant="success">En línea (ext {a.current_extension})</Badge>
                    )
                  ) : (
                    <Badge variant="secondary">Offline</Badge>
                  )}
                </TableCell>
                <TableCell className="text-right">
                  <div className="inline-flex gap-1">
                    <Button size="sm" variant="ghost" onClick={() => openEdit(a)} title="Editar">
                      <Pencil className="h-3 w-3" />
                    </Button>
                    <Button size="sm" variant="ghost" className="text-destructive" onClick={() => remove(a.id)} title="Eliminar">
                      <Trash2 className="h-3 w-3" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
            {!agents.length && !loading && (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground py-12">
                  Sin agentes. Creá el primero con "+ Nuevo agente".
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </Card>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {editingId == null ? 'Nuevo agente' : 'Editar agente'}
              {editingNumber && (
                <span className="ml-2 text-xs font-mono text-muted-foreground">#{editingNumber}</span>
              )}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={save} className="space-y-4">
            <div className="space-y-1.5">
              <Label>Nombre completo *</Label>
              <Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Juan Pérez" />
              {editingId == null && (
                <p className="text-[10px] text-muted-foreground">
                  El número de agente se asigna automáticamente al crear.
                </p>
              )}
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Email</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="space-y-1.5">
                <Label>Teléfono</Label>
                <Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label>Sector</Label>
              <Input value={form.sector} onChange={(e) => setForm({ ...form, sector: e.target.value })} placeholder="Monitoreo · España · Operaciones…" />
            </div>

            {/* Turno */}
            <div className="space-y-2 border-t pt-4">
              <div className="flex items-center justify-between">
                <Label className="flex items-center gap-1.5">
                  <Clock className="h-3.5 w-3.5 text-primary" />
                  Turno laboral
                </Label>
                <button
                  type="button"
                  onClick={() => setForm({ ...form, hasShift: !form.hasShift })}
                  className={cn(
                    'text-xs font-semibold px-2.5 py-1 rounded-full transition-colors',
                    form.hasShift ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground'
                  )}
                >
                  {form.hasShift ? 'Activado' : 'Sin configurar'}
                </button>
              </div>

              {form.hasShift && (
                <div className="space-y-3 pl-1">
                  <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1">
                      <Label className="text-xs">Inicio</Label>
                      <Input
                        type="time"
                        value={form.shiftStart}
                        onChange={(e) => setForm({ ...form, shiftStart: e.target.value })}
                      />
                    </div>
                    <div className="space-y-1">
                      <Label className="text-xs">Fin</Label>
                      <Input
                        type="time"
                        value={form.shiftEnd}
                        onChange={(e) => setForm({ ...form, shiftEnd: e.target.value })}
                      />
                    </div>
                  </div>
                  <div className="space-y-1">
                    <Label className="text-xs">Días</Label>
                    <div className="flex gap-1.5">
                      {DAYS.map((d) => {
                        const active = form.shiftDays.includes(d.value);
                        return (
                          <button
                            key={d.value}
                            type="button"
                            onClick={() => toggleDay(d.value)}
                            className={cn(
                              'h-9 w-9 rounded-md font-bold text-sm transition-colors border',
                              active
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background text-muted-foreground border-input hover:bg-accent'
                            )}
                            title={d.value}
                          >
                            {d.label}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="space-y-1.5">
              <Label>Notas</Label>
              <textarea
                className="flex w-full min-h-[60px] rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder="Observaciones internas, etc."
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancelar</Button>
              <Button type="submit" disabled={saving}>
                {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                {editingId == null ? 'Crear' : 'Guardar'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/** Badge compacto que muestra un turno: 08:00-16:00 · L M X J V */
function ShiftBadge({ shift }: { shift: Shift }) {
  const dayLabels = DAYS.filter((d) => shift.days.includes(d.value)).map((d) => d.label).join(' ');
  return (
    <div className="flex flex-col gap-0.5">
      <span className="font-mono text-foreground">{shift.start}–{shift.end}</span>
      <span className="text-muted-foreground text-[10px] tracking-wider">{dayLabels}</span>
    </div>
  );
}
