// src/lib/rt.js — cliente único socket.io al realtime hub.
// Reemplaza los múltiples io()/socket manejados por componentes en el legacy.
//
// Diseño:
//  - Singleton por hostname (no múltiples conexiones)
//  - Reconnect automático (delegado a socket.io) con backoff
//  - Emisor `on(event, cb)` devuelve función unsubscribe — evita duplicados
//  - Cache del último payload por evento (útil para hooks React que montan tarde)

import { io } from "socket.io-client";

let socket = null;
const lastEvent = new Map();
const listeners = new Map(); // event → Set<cb>

function ensureSocket() {
  if (socket) return socket;
  const url = `${location.protocol}//${location.host}`;
  socket = io(url, {
    path: "/socket.io",
    withCredentials: true,
    transports: ["websocket", "polling"],
    reconnection: true,
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
  });

  // Middleware genérico: guarda último payload y notifica listeners
  // patched onAny hides raw socket.on
  const eventsSeen = new Set();
  socket.onAny((event, ...args) => {
    lastEvent.set(event, args[0]);
    if (!eventsSeen.has(event)) eventsSeen.add(event);
    const set = listeners.get(event);
    if (set) set.forEach((cb) => { try { cb(...args); } catch(e) { console.error("[rt]", event, e); } });
  });
  return socket;
}

export const rt = {
  connect: ensureSocket,
  isConnected: () => !!(socket && socket.connected),
  emit: (event, payload) => ensureSocket().emit(event, payload),
  on(event, cb) {
    ensureSocket();
    if (!listeners.has(event)) listeners.set(event, new Set());
    listeners.get(event).add(cb);
    // Si ya vimos un payload, re-enviarlo (evita race conditions al montar)
    if (lastEvent.has(event)) queueMicrotask(() => cb(lastEvent.get(event)));
    return () => {
      const set = listeners.get(event);
      if (set) set.delete(cb);
    };
  },
  last: (event) => lastEvent.get(event) ?? null,
  disconnect() {
    if (socket) { socket.disconnect(); socket = null; }
    listeners.clear();
    lastEvent.clear();
  }
};
