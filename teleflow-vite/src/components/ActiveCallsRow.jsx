// src/components/ActiveCallsRow.jsx — cards horizontal scroll con llamadas en vivo.
import React from "react";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";

const stateColor = (st) => (/Up/.test(st) ? HORIZON.green : /Ring/.test(st) ? HORIZON.amber : HORIZON.muted);

export default function ActiveCallsRow() {
  const calls = useLive((s) => s.activeCalls);
  if (!calls?.length) {
    return <div style={{ color: HORIZON.muted, padding: 20, textAlign: "center", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10 }}>Sin llamadas activas.</div>;
  }
  return (
    <div style={{ display: "flex", gap: 10, overflowX: "auto", paddingBottom: 6 }}>
      {calls.map((c, i) => (
        <div key={c.uniqueid || i} style={{
          flex: "0 0 260px", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10,
          padding: 14, position: "relative"
        }}>
          <div style={{ position: "absolute", top: 12, right: 12, width: 8, height: 8, borderRadius: 999, background: stateColor(c.state), boxShadow: `0 0 0 4px ${stateColor(c.state)}22` }} />
          <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Origen → Destino</div>
          <div style={{ fontSize: 16, fontWeight: 700, color: HORIZON.neutralInk, marginTop: 4 }}>
            {c.caller || "?"} → <span style={{ color: HORIZON.green }}>{c.dest || "?"}</span>
          </div>
          <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 8 }}>
            {c.state || "?"} · {c.duration ? `${c.duration}s` : ""}
          </div>
        </div>
      ))}
    </div>
  );
}
