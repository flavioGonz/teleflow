// src/views/Live.jsx — F2.7: llamadas activas en vivo (via lib/rt).
import React, { useEffect, useState } from "react";
import { useLive } from "../stores/live.js";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

const stateColor = (st) => (/Up/.test(st) ? HORIZON.green : /Ring/.test(st) ? HORIZON.amber : HORIZON.muted);

export default function Live() {
  const calls = useLive((s) => s.activeCalls);
  const connected = useLive((s) => s.connected);
  // Fallback: si el hub no está conectado, poll al backend cada 5s
  const [fallback, setFallback] = useState([]);
  useEffect(() => {
    if (connected) return;
    const load = () => api.get("index.php", { action: "get_active_calls" }).then((j) => setFallback(j?.calls || j?.rows || [])).catch(() => {});
    load();
    const t = setInterval(load, 5000);
    return () => clearInterval(t);
  }, [connected]);
  const list = connected ? calls : fallback;

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Llamadas en vivo</h1>
        <div style={{ fontSize: 12, color: HORIZON.muted }}>
          <span style={{ display: "inline-block", width: 8, height: 8, borderRadius: 999, background: connected ? HORIZON.green : HORIZON.amber, marginRight: 6 }} />
          {connected ? "hub online" : "poll fallback 5s"} · {list.length} activas
        </div>
      </div>
      {list.length === 0
        ? <div style={{ padding: 40, textAlign: "center", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, color: HORIZON.muted }}>Sin llamadas activas.</div>
        : <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill,minmax(280px,1fr))", gap: 10 }}>
            {list.map((c, i) => (
              <div key={c.uniqueid || i} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 14, position: "relative" }}>
                <div style={{ position: "absolute", top: 12, right: 12, width: 8, height: 8, borderRadius: 999, background: stateColor(c.state), boxShadow: `0 0 0 4px ${stateColor(c.state)}22` }} />
                <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Origen → Destino</div>
                <div style={{ fontSize: 16, fontWeight: 700, color: HORIZON.neutralInk, marginTop: 4 }}>
                  {c.caller || c.src || "?"} → <span style={{ color: HORIZON.green }}>{c.dest || c.dst || "?"}</span>
                </div>
                <div style={{ display: "flex", justifyContent: "space-between", marginTop: 10, fontSize: 11 }}>
                  <span style={{ color: HORIZON.muted }}>{c.state || "?"}</span>
                  <span style={{ fontFamily: "ui-monospace", color: HORIZON.muted }}>{c.duration ? `${c.duration}s` : ""}</span>
                </div>
              </div>
            ))}
          </div>
      }
    </div>
  );
}
