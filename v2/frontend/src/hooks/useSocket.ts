/**
 * Hook React para suscribirse a eventos de Socket.io.
 *
 * Eventos que emite el servicio realtime (ver realtime/index.js):
 *  - peer_update     { peer, status }          ← cambios de estado SIP
 *  - call_event      { type, channel, ext, ...} ← nueva llamada / hangup
 *  - queue_update    { queue, type, ... }      ← join/abandon/connect en queue
 *  - agent_logout    { agent }                  ← agente cerró sesión
 *  - agent_pause     { agent, paused }          ← pausa/unpausa
 *
 * Uso:
 *   useSocketEvent('call_event', (ev) => console.log(ev));
 */
import { useEffect, useRef } from 'react';
import { getSocket } from '../lib/socket';

export function useSocketEvent<T = any>(eventName: string, handler: (data: T) => void) {
  const handlerRef = useRef(handler);
  useEffect(() => { handlerRef.current = handler; }, [handler]);

  useEffect(() => {
    const socket = getSocket();
    const cb = (data: T) => handlerRef.current(data);
    socket.on(eventName, cb);
    return () => { socket.off(eventName, cb); };
  }, [eventName]);
}

/** Hook que devuelve el estado de conexión del socket */
export function useSocketStatus() {
  const ref = useRef<{ connected: boolean }>({ connected: false });

  useEffect(() => {
    const socket = getSocket();
    const onConnect = () => { ref.current.connected = true; };
    const onDisconnect = () => { ref.current.connected = false; };
    socket.on('connect', onConnect);
    socket.on('disconnect', onDisconnect);
    ref.current.connected = socket.connected;
    return () => {
      socket.off('connect', onConnect);
      socket.off('disconnect', onDisconnect);
    };
  }, []);

  return ref.current;
}
