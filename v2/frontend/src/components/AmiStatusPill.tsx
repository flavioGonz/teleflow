/**
 * Pill que muestra el estado real del realtime (Socket.io) Y del AMI.
 * Click → popover con detalle (mensaje + hint para admins).
 */
import { useEffect, useState } from 'react';
import { Radio, AlertTriangle, CheckCircle2, XCircle, Loader2 } from 'lucide-react';
import { api } from '../lib/api';
import { cn } from '../lib/utils';
import { getSocket } from '../lib/socket';
import { Popover, PopoverTrigger, PopoverContent } from './ui/popover';

type AmiStatus = 'authenticated' | 'tcp_only' | 'unreachable' | 'checking';
interface AmiHealth {
  status: AmiStatus;
  lastCheckedAt: string | null;
  message?: string;
  hint?: string;
}

export function AmiStatusPill() {
  const [rtConnected, setRtConnected] = useState(false);
  const [ami, setAmi] = useState<AmiHealth | null>(null);

  useEffect(() => {
    const socket = getSocket();
    const onConn = () => setRtConnected(true);
    const onDis = () => setRtConnected(false);
    socket.on('connect', onConn);
    socket.on('disconnect', onDis);
    setRtConnected(socket.connected);
    return () => { socket.off('connect', onConn); socket.off('disconnect', onDis); };
  }, []);

  useEffect(() => {
    let cancel = false;
    const fetch = () => api.get('/health/ami').then((r) => { if (!cancel) setAmi(r.data); }).catch(() => {});
    fetch();
    const t = setInterval(fetch, 15000);
    return () => { cancel = true; clearInterval(t); };
  }, []);

  const view = computeView(rtConnected, ami);
  const cls: Record<typeof view.tone, string> = {
    success: 'bg-primary/15 text-primary',
    warning: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    destructive: 'bg-destructive/15 text-destructive',
    muted: 'bg-muted text-muted-foreground',
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <button
          type="button"
          className={cn(
            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider transition-colors outline-none cursor-pointer hover:brightness-110',
            cls[view.tone]
          )}
        >
          <view.Icon className={cn('h-3 w-3', view.blink && 'animate-pulse')} />
          {view.label}
        </button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-80">
        <PopoverBody view={view} ami={ami} rtConnected={rtConnected} />
      </PopoverContent>
    </Popover>
  );
}

function PopoverBody({
  view, ami, rtConnected,
}: { view: ReturnType<typeof computeView>; ami: AmiHealth | null; rtConnected: boolean }) {
  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        <view.Icon className="h-4 w-4" />
        <h4 className="font-bold text-sm">{view.title}</h4>
      </div>

      <div className="space-y-2 text-xs">
        <Row
          label="Realtime (Socket.io)"
          status={rtConnected ? 'ok' : 'down'}
          detail={rtConnected ? 'Conectado al servicio realtime' : 'Sin conexión al puerto 3001'}
        />
        <Row
          label="Asterisk Manager (AMI)"
          status={
            ami?.status === 'authenticated' ? 'ok'
              : ami?.status === 'tcp_only' ? 'warn'
              : ami?.status === 'unreachable' ? 'down'
              : 'checking'
          }
          detail={ami?.message || (ami?.status === 'checking' ? 'Verificando…' : '—')}
        />
      </div>

      {/* Hints — combinamos hint del backend (AMI) y hint client (socket) */}
      {(() => {
        const hints: string[] = [];
        if (!rtConnected) {
          hints.push(
            'Realtime: verificá que el servicio en el puerto 3001 esté corriendo (docker compose ps realtime).'
          );
        }
        if (ami?.hint) hints.push(`AMI: ${ami.hint}`);
        if (!hints.length) return null;
        return (
          <div className="text-[11px] text-muted-foreground border-t pt-3 leading-relaxed space-y-2">
            <strong className="text-foreground block">Cómo arreglar:</strong>
            {hints.map((h, i) => <p key={i}>{h}</p>)}
          </div>
        );
      })()}

      {ami?.lastCheckedAt && (
        <div className="text-[10px] text-muted-foreground border-t pt-2">
          Último chequeo: {new Date(ami.lastCheckedAt).toLocaleTimeString('es-UY')}
        </div>
      )}
    </div>
  );
}

function Row({
  label, status, detail,
}: { label: string; status: 'ok' | 'warn' | 'down' | 'checking'; detail: string }) {
  const Icon = status === 'ok' ? CheckCircle2 : status === 'warn' ? AlertTriangle : status === 'down' ? XCircle : Loader2;
  const color =
    status === 'ok' ? 'text-green-500'
    : status === 'warn' ? 'text-amber-500'
    : status === 'down' ? 'text-destructive'
    : 'text-muted-foreground';
  return (
    <div className="flex items-start gap-2">
      <Icon className={cn('h-3.5 w-3.5 mt-0.5 shrink-0', color, status === 'checking' && 'animate-spin')} />
      <div className="flex-1 min-w-0">
        <div className="font-semibold">{label}</div>
        <div className="text-muted-foreground">{detail}</div>
      </div>
    </div>
  );
}

function computeView(rtConnected: boolean, ami: AmiHealth | null) {
  if (!ami) {
    return {
      label: rtConnected ? 'En vivo · ?' : 'Conectando',
      title: rtConnected ? 'AMI en verificación' : 'Conectando al backend',
      tone: rtConnected ? 'warning' as const : 'muted' as const,
      Icon: Loader2,
      blink: false,
    };
  }
  if (rtConnected && ami.status === 'authenticated') {
    return { label: 'En vivo · AMI', title: 'Todo conectado', tone: 'success' as const, Icon: Radio, blink: true };
  }
  if (rtConnected && ami.status === 'tcp_only') {
    return { label: 'En vivo · sin AMI', title: 'AMI rechazó la autenticación', tone: 'warning' as const, Icon: AlertTriangle, blink: false };
  }
  if (rtConnected && ami.status === 'unreachable') {
    return { label: 'En vivo · PBX caído', title: 'No se puede alcanzar el AMI', tone: 'warning' as const, Icon: AlertTriangle, blink: false };
  }
  if (!rtConnected && ami.status === 'authenticated') {
    return { label: 'AMI · sin socket', title: 'Realtime caído', tone: 'warning' as const, Icon: AlertTriangle, blink: false };
  }
  return {
    label: ami.status === 'tcp_only' ? 'Auth AMI fallido' : 'Offline',
    title: 'Sin conectividad con la PBX',
    tone: 'destructive' as const,
    Icon: XCircle,
    blink: false,
  };
}
