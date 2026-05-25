import { useEffect, useState } from 'react';
import { History, Search, AlertCircle, Phone, PhoneMissed, PhoneOff, Loader2, X } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import {
  dispositionLabel, dispositionVariant, DISPOSITION_OPTIONS, type Disposition,
} from '../lib/calls';
import { cn } from '../lib/utils';

interface CdrRow {
  calldate: string;
  src: string;
  dst: string;
  dcontext?: string;
  duration: number;
  billsec: number;
  disposition: Disposition;
  uniqueid: string;
}

const today = () => new Date().toISOString().slice(0, 10);
const daysAgo = (n: number) => new Date(Date.now() - n * 86400_000).toISOString().slice(0, 10);

function fmtDuration(s: number) {
  if (!s) return '0s';
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return m ? `${m}m ${sec}s` : `${sec}s`;
}

function dispositionIcon(d: Disposition) {
  if (d === 'ANSWERED') return Phone;
  if (d === 'NO ANSWER') return PhoneMissed;
  return PhoneOff;
}

export default function CDR() {
  const [rows, setRows] = useState<CdrRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [filters, setFilters] = useState({
    start: daysAgo(7),
    end: today(),
    src: '',
    dst: '',
    disposition: '' as '' | Disposition,
  });

  async function load() {
    setLoading(true);
    setErr(null);
    try {
      const params: any = { start: filters.start, end: filters.end, limit: 500 };
      if (filters.src) params.src = filters.src;
      if (filters.dst) params.dst = filters.dst;
      if (filters.disposition) params.disposition = filters.disposition;
      const r = await api.get('/cdr', { params });
      setRows(r.data.cdr || []);
      setAsteriskDown(false);
    } catch (e: any) {
      if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
      else setErr(e.response?.data?.error || 'Error');
    } finally {
      setLoading(false);
    }
  }

  // Recarga automática al cambiar filtros (debounce mínimo)
  useEffect(() => {
    const t = setTimeout(load, 250);
    return () => clearTimeout(t);
  }, [filters.disposition]);

  useEffect(() => { load(); }, []);

  const summary = {
    total: rows.length,
    answered: rows.filter((r) => r.disposition === 'ANSWERED').length,
    missed: rows.filter((r) => r.disposition === 'NO ANSWER').length,
    avgTalk: rows.length ? Math.round(rows.reduce((s, r) => s + (r.billsec || 0), 0) / rows.length) : 0,
  };

  /** Toggle de filtro disposition desde las cards */
  function applyDispositionFilter(d: '' | Disposition) {
    setFilters((f) => ({ ...f, disposition: f.disposition === d ? '' : d }));
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
          <History className="h-6 w-6 text-primary" />
          CDR — Historial de llamadas
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Detalle completo de llamadas del PBX. Filtrá por fecha, origen, destino o resultado.
        </p>
      </div>

      {/* Filtros */}
      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div className="space-y-1">
              <Label className="text-xs">Desde</Label>
              <Input type="date" value={filters.start} onChange={(e) => setFilters({ ...filters, start: e.target.value })} />
            </div>
            <div className="space-y-1">
              <Label className="text-xs">Hasta</Label>
              <Input type="date" value={filters.end} onChange={(e) => setFilters({ ...filters, end: e.target.value })} />
            </div>
            <div className="space-y-1">
              <Label className="text-xs">Origen</Label>
              <Input placeholder="Ext o número" value={filters.src} onChange={(e) => setFilters({ ...filters, src: e.target.value })} />
            </div>
            <div className="space-y-1">
              <Label className="text-xs">Destino</Label>
              <Input placeholder="Ext o número" value={filters.dst} onChange={(e) => setFilters({ ...filters, dst: e.target.value })} />
            </div>
            <div className="space-y-1">
              <Label className="text-xs">Resultado</Label>
              <select
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={filters.disposition}
                onChange={(e) => setFilters({ ...filters, disposition: e.target.value as any })}
              >
                <option value="">Todos</option>
                {DISPOSITION_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </select>
            </div>
          </div>
          <div className="flex justify-end mt-3">
            <Button onClick={load} disabled={loading}>
              {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
              Buscar
            </Button>
          </div>
        </CardContent>
      </Card>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>CDR DB no disponible</strong> — necesita conexión a `asteriskcdrdb`.</div>
          </CardContent>
        </Card>
      )}

      {err && (
        <Card className="border-destructive/40 bg-destructive/5">
          <CardContent className="p-4 text-destructive text-sm">{err}</CardContent>
        </Card>
      )}

      {/* Summary cards clickeables como filtros */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <FilterCard
          label="Total"
          value={summary.total}
          active={filters.disposition === ''}
          onClick={() => applyDispositionFilter('')}
        />
        <FilterCard
          label="Atendidas"
          value={summary.answered}
          color="text-green-500"
          active={filters.disposition === 'ANSWERED'}
          onClick={() => applyDispositionFilter('ANSWERED')}
        />
        <FilterCard
          label="No atendidas"
          value={summary.missed}
          color="text-amber-500"
          active={filters.disposition === 'NO ANSWER'}
          onClick={() => applyDispositionFilter('NO ANSWER')}
        />
        <SummaryCard label="Promedio conversación" value={fmtDuration(summary.avgTalk)} />
      </div>

      {filters.disposition && (
        <div className="flex items-center gap-2 text-xs">
          <span className="text-muted-foreground">Filtrando por:</span>
          <Badge variant={dispositionVariant(filters.disposition)} className="gap-1">
            {dispositionLabel(filters.disposition)}
            <button onClick={() => applyDispositionFilter('')} className="hover:opacity-70">
              <X className="h-3 w-3" />
            </button>
          </Badge>
        </div>
      )}

      {/* Tabla */}
      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Fecha</TableHead>
              <TableHead>Origen</TableHead>
              <TableHead>Destino</TableHead>
              <TableHead>Resultado</TableHead>
              <TableHead className="text-right">Duración</TableHead>
              <TableHead className="text-right">Conversación</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows.map((r) => {
              const Icon = dispositionIcon(r.disposition);
              return (
                <TableRow key={r.uniqueid}>
                  <TableCell className="font-mono text-xs">
                    {new Date(r.calldate).toLocaleString('es-UY', { dateStyle: 'short', timeStyle: 'short' })}
                  </TableCell>
                  <TableCell className="font-mono">{r.src}</TableCell>
                  <TableCell className="font-mono">{r.dst}</TableCell>
                  <TableCell>
                    <Badge variant={dispositionVariant(r.disposition)} className="gap-1">
                      <Icon className="h-3 w-3" />
                      {dispositionLabel(r.disposition)}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right text-sm">{fmtDuration(r.duration)}</TableCell>
                  <TableCell className="text-right text-sm font-semibold">{fmtDuration(r.billsec)}</TableCell>
                </TableRow>
              );
            })}
            {!rows.length && !loading && !asteriskDown && (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground py-12">
                  Sin llamadas en el rango seleccionado.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
}

function SummaryCard({ label, value, color }: { label: string; value: any; color?: string }) {
  return (
    <Card>
      <CardContent className="p-4">
        <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{label}</div>
        <div className={cn('text-2xl font-black tabular-nums mt-1', color)}>{value}</div>
      </CardContent>
    </Card>
  );
}

function FilterCard({
  label, value, color, active, onClick,
}: { label: string; value: any; color?: string; active: boolean; onClick: () => void }) {
  return (
    <Card
      onClick={onClick}
      className={cn(
        'cursor-pointer transition-all hover:bg-accent/40',
        active && 'ring-2 ring-primary bg-primary/5'
      )}
    >
      <CardContent className="p-4">
        <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{label}</div>
        <div className={cn('text-2xl font-black tabular-nums mt-1', color)}>{value}</div>
      </CardContent>
    </Card>
  );
}
