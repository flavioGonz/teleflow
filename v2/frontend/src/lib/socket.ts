/**
 * Cliente Socket.io singleton.
 * El servicio realtime expone eventos en /teleflow-socket (puerto 3001).
 * En dev local: http://localhost:3001
 * En prod: probable proxy desde nginx.
 */
import { io, Socket } from 'socket.io-client';

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL || `${window.location.protocol}//${window.location.hostname}:3001`;

let socket: Socket | null = null;

export function getSocket(): Socket {
  if (socket) return socket;
  socket = io(SOCKET_URL, {
    path: '/teleflow-socket',
    transports: ['polling', 'websocket'],
    upgrade: true,
    reconnection: true,
    reconnectionDelay: 1500,
    reconnectionDelayMax: 10000,
  });
  socket.on('connect', () => console.log('[socket] connected', socket?.id));
  socket.on('disconnect', (reason) => console.warn('[socket] disconnected:', reason));
  socket.on('connect_error', (err) => console.warn('[socket] connect_error:', err.message));
  return socket;
}

export function disconnectSocket() {
  if (socket) {
    socket.disconnect();
    socket = null;
  }
}
