import { useEffect, useMemo, useState } from 'react';
import { PhoneCall, PhoneOff, Headphones, RefreshCw, Activity, AlertCircle } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../components/ui/table';
import { useSocketEvent } from '../hooks/useSocket';

interface Channel {
  channel: string;
  channelstatedesc?: string;
  calleridnum?: string;
  connectedlinenum?: string;
  exten?: string;
  context?: string;
  duration?: string;
  application?: string;
}

export default function Llamadas() {
  const [channels, setChannels] = useState<Channel[]>([]);
  const [loading, setLoading] = useState(true);
  const [asteriskDown, setAsteriskDown] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [spyExt, setSpyExt] = useState(() => localStorage.getItem('hznflow_spy_ext') || '');
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

  function load() {
    api.get('/calls/active')
      .then((r) => {
        setChannels(r.data.channels || []);
        setAsteriskDown(false);
        setErr(null);
        setLastUpdate(new Date());
      })
      .catch((e) => {
        if (e.response?.data?.code === 'ASTERISK_UNAVAILABLE') setAsteriskDown(true);
        else setErr(e.response?.data?.error || 'Error');
      })
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    load();
    const t = setInterval(load, 5000);
    return () => clearInterval(t);
  }, []);

  // Realtime — refresca al recibir cualquier evento de llamada
  useSocketEvent('call_event', () => load());
  useSocketEvent('peer_update', () => load());

  function saveSpyExt(v: string) {
    setSpyExt(v);
    localStorage.setItem('hznflow_spy_ext', v);
  }

  async function spy(channel: string, mode: 'spy' | 'whisper' | 'barge') {
    if (!spyExt) { alert('Configurá tu extensión para escuchar'); return; }
    try {
      await api.post('/calls/spy', { myExt: spyExt, targetChannel: channel, mode });
      alert(`${mode === 'spy' ? 'Escuchando' : mode === 'whisper' ? 'Whisper iniciado' : 'Conferencia (barge)'}: llamando a ext ${spyExt}...`);
    } catch (e: any) {
      alert('Error: ' + (e.response?.data?.error || e.message));
    }
  }

  async function hangup(channel: string) {
    if (!confirm(`¿Colgar canal ${channel}?`)) return;
    try {
      await api.post('/calls/hangup', { channel });
      load();
    } catch (e: any) {
      alert('Error: ' + (e.response?.data?.error || e.message));
    }
  }

  const counts = useMemo(() => ({
    total: channels.length,
    upCount: channels.filter((c) => c.channelstatedesc === 'Up').length,
    ringCount: channels.filter((c) => /Ring/i.test(c.channelstatedesc || '')).length,
  }), [channels]);

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <PhoneCall className="h-6 w-6 text-primary" />
            Llamadas en Vivo
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            Canales activos en el PBX. Realtime vía Socket.io + polling cada 5s.
            {lastUpdate && ` Última actualización: ${lastUpdate.toLocaleTimeString('es-UY')}`}
          </p>
        </div>
        <Button variant="outline" size="sm" onClick={load}>
          <RefreshCw className="h-4 w-4" />
          Refrescar
        </Button>
      </div>

      {/* Spy ext input */}
      <Card>
        <CardContent className="p-4 flex items-center gap-3">
          <Headphones className="h-4 w-4 text-primary shrink-0" />
          <Input
            placeholder="Tu extensión SIP para escuchar (ej. 9001)"
            value={spyExt}
            onChange={(e) => saveSpyExt(e.target.value.replace(/\D/g, '').slice(0, 6))}
            className="max-w-xs"
          />
          <span className="text-xs text-muted-foreground">
            Asterisk llama a esta extensión cuando hacés Spy/Whisper/Barge.
          </span>
        </CardContent>
      </Card>

      {asteriskDown && (
        <Card className="border-amber-500/40 bg-amber-500/5">
          <CardContent className="p-4 flex items-center gap-3">
            <AlertCircle className="h-5 w-5 text-amber-500" />
            <div className="text-sm"><strong>AMI no disponible</strong> — no se pueden listar canales activos.</div>
          </CardContent>
        </Card>
      )}

      {err && (
        <Card className="border-destructive/40 bg-destructive/5">
          <CardContent className="p-4 text-destructive text-sm">{err}</CardContent>
        </Card>
      )}

      {/* Counts */}
      <div className="grid grid-cols-3 gap-3">
        <CountCard label="Total" value={counts.total} icon={Activity} color="text-foreground" />
        <CountCard label="En conversación" value={counts.upCount} icon={PhoneCall} color="text-green-500" />
        <CountCard label="Sonando" value={counts.ringCount} icon={PhoneCall} color="text-amber-500" />
      </div>

      {/* Channels table */}
      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Canal</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead>Caller</TableHead>
              <TableHead>Destino</TableHead>
              <TableHead>App</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {channels.map((c) => (
              <TableRow key={c.channel}>
                <TableCell className="font-mono text-xs">{c.channel}</TableCell>
                <TableCell>
                  <Badge variant={c.channelstatedesc === 'Up' ? 'success' : 'warning'}>
                    {c.channelstatedesc || '?'}
                  </Badge>
                </TableCell>
                <TableCell>{c.calleridnum || '—'}</TableCell>
                <TableCell>{c.exten || c.connectedlinenum || '—'}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{c.application || '—'}</TableCell>
                <TableCell className="text-right">
                  <div className="inline-flex gap-1">
                    <Button size="sm" variant="ghost" onClick={() => spy(c.channel, 'spy')}>Spy</Button>
                    <Button size="sm" variant="ghost" onClick={() => spy(c.channel, 'whisper')}>Whisper</Button>
                    <Button size="sm" variant="ghost" onClick={() => spy(c.channel, 'barge')}>Barge</Button>
                    <Button size="sm" variant="ghost" className="text-destructive" onClick={() => hangup(c.channel)}>
                      <PhoneOff className="h-3 w-3" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
            {!channels.length && !loading && (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground py-12">
                  <PhoneOff className="h-8 w-8 mx-auto mb-2 opacity-30" />
                  {asteriskDown ? 'AMI sin datos' : 'Sin llamadas activas'}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
}

function CountCard({ label, value, icon: Icon, color }: { label: string; value: number; icon: any; color: string }) {
  return (
    <Card>
      <CardContent className="p-4 flex items-center justify-between">
        <div>
          <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{label}</div>
          <div className={`text-2xl font-black tabular-nums ${color}`}>{value}</div>
        </div>
        <Icon className={`h-7 w-7 opacity-40 ${color}`} />
      </CardContent>
    </Card>
  );
}
