// src/lib/clock.js — hora oficial del PBX/server (TZ America/Montevideo).
//
// Diseño: `useClock()` devuelve un objeto con `time`, `date`, `syncedWithServer`.
// SIEMPRE llama `new Date()` internamente (nunca `new Date(counter)`), y aplica
// timeZone Montevideo. Por diseño resiste el bug del legacy que mostraba 21:00
// porque hacía `new Date(tick)` con tick=1,2,3…

import { useEffect, useRef, useState } from "react";
import { api } from "./api.js";

const TZ = "America/Montevideo";
const SYNC_EVERY_MS = 5 * 60 * 1000;

let offsetMs = 0;      // server - client
let synced = false;

async function syncOnce() {
  try {
    const t0 = Date.now();
    const j = await api.get("server_time.php", null, { timeout: 4000, retries: 1 });
    const t1 = Date.now();
    if (j && j.ok && j.ts) {
      const rtt = t1 - t0;
      const serverNow = j.ts + Math.round(rtt / 2);
      offsetMs = serverNow - t1;
      synced = true;
    }
  } catch { /* usa hora local */ }
}

let syncPromise = null;
export function primeClock() {
  if (!syncPromise) syncPromise = syncOnce();
  return syncPromise;
}

setInterval(syncOnce, SYNC_EVERY_MS);

export function pbxNow() {
  return new Date(Date.now() + offsetMs);
}

export function useClock({ withSeconds = true } = {}) {
  const [, setTick] = useState(0);
  const mounted = useRef(true);
  useEffect(() => {
    primeClock();
    mounted.current = true;
    const t = setInterval(() => { if (mounted.current) setTick(k => k + 1); }, 1000);
    return () => { mounted.current = false; clearInterval(t); };
  }, []);
  const d = pbxNow();
  return {
    date: d.toLocaleDateString("es-UY", { timeZone: TZ }),
    time: d.toLocaleTimeString("es-UY", {
      timeZone: TZ, hour12: false,
      hour: "2-digit", minute: "2-digit", ...(withSeconds ? { second: "2-digit" } : {})
    }),
    syncedWithServer: synced,
    offsetMs,
    raw: d,
  };
}
