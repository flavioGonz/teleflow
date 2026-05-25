import { useEffect, useMemo, useState } from 'react';
import { Users, AlertCircle, Video, Search, X, Filter } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import { Button } from '../components/ui/button';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '../components/ui/select';

interface ExtMeta { tipo?: string | null; rtspUrl?: string | null; isBocina?: boolean }
interface Ext { ext: string; name: string; tech?: string; meta?: ExtMeta | null }

type VideoFilter = '' | 'yes' | 'no';

const PAGE_SIZE = 50;

export default function Extensiones() {
  const [allExts, setAllExts] = useState<Ext[]>([]);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  // Filtros
  const [search, setSearch] = useState('');
  const [filterTech, setFilterTech] = useState('');
  const [filterTipo, setFilterTipo] = useState('');
  const [filterVideo, setFilterVideo] = useState<VideoFilter>('');
  const [page, setPage] = useState(0);

  useEffect(() => {
    api.get('/extensions')
      .then((r) => setAllExts(r.data.extensions))
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
        else setErr(e.response?.data?.error || 'Error');
      })
      .finally(() => setLoading(false));
  }, []);

  // Opciones únicas para los dropdowns (calculadas de la data real)
  const techOptions = useMemo(
    () => Array.from(new Set(allExts.map((e) => e.tech).filter(Boolean) as string[])).sort(),
    [allExts]
  );
  const tipoOptions = useMemo(
    () => Array.from(new Set(allExts.map((e) => e.meta?.tipo).filter(Boolean) as string[])).sort(),
    [allExts]
  );

  // Filtrado client-side (las 470 ya están en memoria)
  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    return allExts.filter((e) => {
      if (q && !e.ext.toLowerCase().includes(q) && !(e.name || '').toLowerCase().includes(q)) return false;
      if (filterTech && e.tech !== filterTech) return false;
      if (filterTipo) {
        const tipo = e.meta?.tipo || '';
        if (filterTipo === '__none__' ? tipo !== '' : tipo !== filterTipo) return false;
      }
      if (filterVideo) {
        const hasVideo = !!e.meta?.rtspUrl;
        if (filterVideo === 'yes' && !hasVideo) return false;
        if (filterVideo === 'no' && hasVideo) return false;
      }
      return true;
    });
  }, [allExts, search, filterTech, filterTipo, filterVideo]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const pageRows = filtered.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE);
  const hasActiveFilters = search || filterTech || filterTipo || filterVideo;

  function resetFilters() {
    setSearch(''); setFilterTech(''); setFilterTipo(''); setFilterVideo(''); setPage(0);
  }

  // Reset page cuando cambian los filtros
  useEffect(() => { setPage(0); }, [search, filterTech, filterTipo, filterVideo]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
          <Users className="h-6 w-6 text-primary" />
          Extensiones SIP
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          {allExts.length} extensiones · {filtered.length} mostradas con filtros actuales
        </p>
      </div>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>Asterisk MySQL no disponible</strong> — las extensiones viven en el PBX.</div>
          </CardContent>
        </Card>
      )}

      {err && (
        <Card className="border-destructive/40 bg-destructive/5">
          <CardContent className="p-4 text-destructive text-sm">{err}</CardContent>
        </Card>
      )}

      {/* Filtros */}
      <Card>
        <CardContent className="p-4">
          {/* Layout: search (320px) + 3 selects (170px c/u) + ícono limpiar */}
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1 w-full sm:w-80">
              <Label className="text-xs">Buscar</Label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                <Input
                  className="pl-9"
                  placeholder="Ext o nombre…"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>

            <FilterDropdown label="Tech" value={filterTech} onChange={setFilterTech} placeholder="Todos">
              {techOptions.map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}
            </FilterDropdown>

            <FilterDropdown label="Tipo" value={filterTipo} onChange={setFilterTipo} placeholder="Todos">
              {tipoOptions.map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}
              <SelectItem value="__none__">Sin tipo</SelectItem>
            </FilterDropdown>

            <FilterDropdown label="Video" value={filterVideo} onChange={(v) => setFilterVideo(v as VideoFilter)} placeholder="Todos">
              <SelectItem value="yes">Con RTSP</SelectItem>
              <SelectItem value="no">Sin RTSP</SelectItem>
            </FilterDropdown>

            {hasActiveFilters && (
              <Button
                variant="ghost"
                onClick={resetFilters}
                title="Limpiar todos los filtros"
                className="text-muted-foreground hover:text-destructive hover:bg-destructive/10"
              >
                <X className="h-4 w-4" />
                Limpiar
              </Button>
            )}
          </div>

          {hasActiveFilters && (
            <div className="mt-3 flex items-center gap-2 flex-wrap text-xs">
              <Filter className="h-3 w-3 text-muted-foreground" />
              <span className="text-muted-foreground">Activos:</span>
              {search && <Badge variant="secondary" className="gap-1">Búsqueda: {search}<button onClick={() => setSearch('')} className="hover:opacity-70"><X className="h-3 w-3" /></button></Badge>}
              {filterTech && <Badge variant="secondary" className="gap-1">Tech: {filterTech}<button onClick={() => setFilterTech('')} className="hover:opacity-70"><X className="h-3 w-3" /></button></Badge>}
              {filterTipo && <Badge variant="secondary" className="gap-1">Tipo: {filterTipo === '__none__' ? 'Sin tipo' : filterTipo}<button onClick={() => setFilterTipo('')} className="hover:opacity-70"><X className="h-3 w-3" /></button></Badge>}
              {filterVideo && <Badge variant="secondary" className="gap-1">Video: {filterVideo === 'yes' ? 'Con RTSP' : 'Sin RTSP'}<button onClick={() => setFilterVideo('')} className="hover:opacity-70"><X className="h-3 w-3" /></button></Badge>}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Tabla */}
      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Ext</TableHead>
              <TableHead>Nombre</TableHead>
              <TableHead>Tech</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Video</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {pageRows.map((e) => (
              <TableRow key={e.ext}>
                <TableCell className="font-mono">{e.ext}</TableCell>
                <TableCell className="font-semibold">{e.name}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{e.tech || '—'}</TableCell>
                <TableCell>
                  {e.meta?.tipo ? <Badge variant="secondary">{e.meta.tipo}</Badge> : <span className="text-muted-foreground">—</span>}
                </TableCell>
                <TableCell>
                  {e.meta?.rtspUrl
                    ? <Badge variant="success"><Video className="h-3 w-3 mr-1" />RTSP</Badge>
                    : <span className="text-muted-foreground">—</span>}
                </TableCell>
              </TableRow>
            ))}
            {!pageRows.length && !loading && (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground py-12">
                  {hasActiveFilters ? 'Sin resultados con esos filtros' : 'Sin extensiones'}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        {/* Paginación */}
        {filtered.length > PAGE_SIZE && (
          <div className="flex items-center justify-between px-4 py-3 border-t text-xs">
            <span className="text-muted-foreground">
              Mostrando {page * PAGE_SIZE + 1}–{Math.min((page + 1) * PAGE_SIZE, filtered.length)} de {filtered.length}
            </span>
            <div className="flex items-center gap-2">
              <Button size="sm" variant="outline" disabled={page === 0} onClick={() => setPage((p) => Math.max(0, p - 1))}>
                Anterior
              </Button>
              <span className="text-muted-foreground">{page + 1} / {totalPages}</span>
              <Button size="sm" variant="outline" disabled={page >= totalPages - 1} onClick={() => setPage((p) => p + 1)}>
                Siguiente
              </Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  );
}

/**
 * Wrapper de shadcn Select tratando '' como "Todos" (sin filtro).
 * shadcn Select no permite item con value="" — usamos un sentinel "__all__".
 */
const ALL = '__all__';

function FilterDropdown({
  label, value, onChange, placeholder, children,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  placeholder: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1 w-full sm:w-44">
      <Label className="text-xs">{label}</Label>
      <Select value={value || ALL} onValueChange={(v) => onChange(v === ALL ? '' : v)}>
        <SelectTrigger className={value ? 'border-primary/40' : undefined}>
          <SelectValue placeholder={placeholder} />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={ALL}>Todos</SelectItem>
          {children}
        </SelectContent>
      </Select>
    </div>
  );
}
