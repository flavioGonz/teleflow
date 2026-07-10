// src/stores/live.js — estado realtime derivado del hub socket.io.
import { create } from "zustand";
import { rt } from "@lib/rt.js";

export const useLive = create(() => ({
  activeCalls: [],
  queues: {},          // { queueId: { name, waiting, members[] } }
  peers: {},           // { ext: { status, tech } }
  connected: false,
  lastEventAt: null,
}));

// Auto-wire eventos del hub → store. Re-wireable — si se llama varias veces,
// desconecta los listeners anteriores primero. Idempotente en el efecto observable.
let unwireFns = [];
export function wireLive() {
  // Cleanup previo
  unwireFns.forEach((fn) => { try { fn(); } catch { /* noop */ } });
  unwireFns = [];

  rt.connect();

  unwireFns.push(rt.on("connect",    () => useLive.setState({ connected: true })));
  unwireFns.push(rt.on("disconnect", () => useLive.setState({ connected: false })));

  unwireFns.push(rt.on("call_update", (payload) => {
    useLive.setState((s) => ({
      activeCalls: Array.isArray(payload?.calls) ? payload.calls : s.activeCalls,
      lastEventAt: Date.now(),
    }));
  }));

  unwireFns.push(rt.on("queue_update", (payload) => {
    if (!payload) return;
    useLive.setState((s) => ({
      queues: { ...s.queues, [payload.queue_id]: { ...(s.queues[payload.queue_id] || {}), ...payload } },
      lastEventAt: Date.now(),
    }));
  }));

  unwireFns.push(rt.on("peer_update", (payload) => {
    if (!payload?.ext) return;
    useLive.setState((s) => ({
      peers: { ...s.peers, [payload.ext]: { ...(s.peers[payload.ext] || {}), ...payload } },
      lastEventAt: Date.now(),
    }));
  }));
}
