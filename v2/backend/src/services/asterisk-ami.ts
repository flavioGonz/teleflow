/**
 * Cliente AMI — singleton.
 * El servicio `realtime/` ya escucha eventos AMI y los broadcast por socket.
 * Este cliente es para ENVIAR comandos (originate, hangup, spy, etc.).
 */
// @ts-expect-error — asterisk-manager no tiene tipos
import AsteriskManager from 'asterisk-manager';
import { env } from '../lib/env.js';

type ActionCb = (err: any, res: any) => void;

let amiClient: any = null;
let connected = false;

function connect() {
  if (amiClient) return amiClient;
  amiClient = new AsteriskManager(env.AMI_PORT, env.AMI_HOST, env.AMI_USER, env.AMI_PASS, true);
  amiClient.keepConnected();
  amiClient.on('connect', () => {
    connected = true;
    console.log(`[ami] connected to ${env.AMI_HOST}:${env.AMI_PORT}`);
  });
  amiClient.on('disconnect', () => {
    connected = false;
    console.warn('[ami] disconnected');
  });
  amiClient.on('error', (err: any) => console.error('[ami] error:', err.message));
  return amiClient;
}

export function getAmi() {
  return connect();
}

export function amiAction(action: Record<string, any>): Promise<any> {
  return new Promise((resolve, reject) => {
    const client = connect();
    const cb: ActionCb = (err, res) => (err ? reject(err) : resolve(res));
    client.action(action, cb);
  });
}

/** Originate una llamada (ejemplo de comando AMI) */
export async function originate(opts: {
  channel: string;
  context: string;
  exten: string;
  priority?: number;
  callerId?: string;
  variables?: Record<string, string>;
}) {
  return amiAction({
    Action: 'Originate',
    Channel: opts.channel,
    Context: opts.context,
    Exten: opts.exten,
    Priority: opts.priority ?? 1,
    CallerID: opts.callerId,
    Variable: opts.variables ? Object.entries(opts.variables).map(([k, v]) => `${k}=${v}`).join(',') : undefined,
    Async: 'true',
  });
}

/** Colgar un canal */
export async function hangup(channel: string) {
  return amiAction({ Action: 'Hangup', Channel: channel });
}

/** Spy / Whisper / Barge — usando Originate + ChanSpy */
export async function spy(opts: { myExt: string; targetChannel: string; mode?: 'spy' | 'whisper' | 'barge' }) {
  const optChar = opts.mode === 'whisper' ? 'w' : opts.mode === 'barge' ? 'B' : '';
  return amiAction({
    Action: 'Originate',
    Channel: `Local/${opts.myExt}@from-internal`,
    Application: 'ChanSpy',
    Data: `${opts.targetChannel.split('-')[0]},${optChar}qs`,
    Async: 'true',
  });
}

export function isAmiConnected() {
  return connected;
}
