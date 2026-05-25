/**
 * AMI Health Checker — TCP raw, sin librería intermediaria.
 *
 * La lib `asterisk-manager` enmascara el authentication failed como un
 * reconnect silencioso. Este checker hace login real cada 30s y reporta
 * el estado verdadero al resto de la app.
 */
import net from 'net';
import { env } from '../lib/env.js';

export type AmiStatus = 'authenticated' | 'tcp_only' | 'unreachable' | 'checking';

interface AmiHealth {
  status: AmiStatus;
  message: string;
  lastCheckedAt: Date | null;
  /** Para mostrar al admin: si está en tcp_only el motivo más probable */
  hint?: string;
}

let current: AmiHealth = {
  status: 'checking',
  message: 'Aún no se chequeó el AMI',
  lastCheckedAt: null,
};

function probeOnce(): Promise<AmiHealth> {
  return new Promise((resolve) => {
    const result = (h: AmiHealth) => resolve({ ...h, lastCheckedAt: new Date() });
    let tcpOk = false;
    let buffer = '';
    let timeoutHandle: NodeJS.Timeout;
    const socket = net.createConnection({
      host: env.AMI_HOST, port: env.AMI_PORT, timeout: 4000,
    });

    const cleanup = () => {
      clearTimeout(timeoutHandle);
      try { socket.destroy(); } catch {}
    };

    socket.on('connect', () => {
      tcpOk = true;
      socket.write(
        `Action: Login\r\nUsername: ${env.AMI_USER}\r\nSecret: ${env.AMI_PASS}\r\nEvents: off\r\n\r\n`
      );
    });

    socket.on('data', (chunk) => {
      buffer += chunk.toString();
      while (buffer.includes('\r\n\r\n')) {
        const idx = buffer.indexOf('\r\n\r\n');
        const raw = buffer.slice(0, idx);
        buffer = buffer.slice(idx + 4);
        if (!raw.trim()) continue;
        const ev: Record<string, string> = {};
        for (const line of raw.split('\r\n')) {
          const [k, ...rest] = line.split(': ');
          if (k && rest.length) ev[k.toLowerCase()] = rest.join(': ');
        }
        if (ev.response === 'Success' && ev.message?.toLowerCase().includes('authentication accepted')) {
          cleanup();
          return result({
            status: 'authenticated',
            message: 'AMI conectado y autenticado',
            lastCheckedAt: null,
          });
        }
        if (ev.response === 'Error') {
          cleanup();
          return result({
            status: 'tcp_only',
            message: `AMI rechazó el login: ${ev.message}`,
            lastCheckedAt: null,
            hint:
              'Causa probable: tu IP no está en la lista `permit=` de /etc/asterisk/manager.conf, ' +
              'o el usuario/password AMI no coinciden con manager.conf. ' +
              'Si la app corre fuera del servidor de Asterisk (ej. dev local vía VPN), agregá tu IP al permit.',
          });
        }
      }
    });

    socket.on('error', (err) => {
      cleanup();
      result({
        status: 'unreachable',
        message: `No se pudo conectar al AMI (${err.message})`,
        lastCheckedAt: null,
        hint: 'Verificá VPN/red al servidor Asterisk y que el puerto 5038 esté abierto.',
      });
    });

    socket.on('end', () => {
      if (!tcpOk) {
        cleanup();
        result({
          status: 'unreachable',
          message: 'AMI cerró la conexión antes de responder',
          lastCheckedAt: null,
        });
      }
    });

    timeoutHandle = setTimeout(() => {
      cleanup();
      result({
        status: tcpOk ? 'tcp_only' : 'unreachable',
        message: 'Timeout esperando respuesta del AMI',
        lastCheckedAt: null,
      });
    }, 5000);
  });
}

export function getAmiHealth(): AmiHealth {
  return current;
}

let started = false;
export function startAmiHealthChecker(intervalMs = 30000) {
  if (started) return;
  started = true;
  const tick = async () => {
    const prev = current.status;
    current = await probeOnce();
    // Log SOLO cuando cambia el estado (evita spam de logs)
    if (current.status !== prev) {
      const sigil = current.status === 'authenticated' ? '✓' : current.status === 'tcp_only' ? '⚠' : '✗';
      console.log(`[ami-health] ${sigil} ${current.status}: ${current.message}`);
      if (current.hint) console.log(`[ami-health]   → ${current.hint}`);
    }
  };
  tick(); // primer probe inmediato
  setInterval(tick, intervalMs);
}
