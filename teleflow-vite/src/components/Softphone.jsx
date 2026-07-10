// src/components/Softphone.jsx — panel del softphone (estado + dialpad + controles).
import React, { useEffect, useState } from "react";
import { useSoftphone } from "../stores/softphone.js";
import { HORIZON } from "@lib/theme.js";

const STATUS_LABEL = {
  idle:       { text: "Inactivo",    color: HORIZON.muted },
  connecting: { text: "Conectando…", color: HORIZON.amber },
  registered: { text: "Registrado",  color: HORIZON.green },
  incoming:   { text: "Entrante…",   color: HORIZON.amber },
  "in-call":  { text: "En llamada",  color: HORIZON.green },
  error:      { text: "Error",       color: HORIZON.danger },
};

export default function Softphone() {
  const status = useSoftphone((s) => s.status);
  const error  = useSoftphone((s) => s.error);
  const creds  = useSoftphone((s) => s.creds);
  const remote = useSoftphone((s) => s.remoteName);
  const start  = useSoftphone((s) => s.start);
  const call   = useSoftphone((s) => s.call);
  const end    = useSoftphone((s) => s.end);
  const [dial, setDial] = useState("");

  useEffect(() => { if (status === "idle") start(); }, [status, start]);

  const badge = STATUS_LABEL[status] || STATUS_LABEL.idle;

  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 12, padding: 16, minWidth: 260 }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
        <span style={{ width: 10, height: 10, borderRadius: 999, background: badge.color, boxShadow: `0 0 0 4px ${badge.color}22` }} />
        <div style={{ flex: 1 }}>
          <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1.2 }}>Softphone</div>
          <div style={{ fontSize: 14, fontWeight: 700, color: badge.color }}>{badge.text}</div>
        </div>
        {creds?.ext && <div style={{ fontSize: 11, color: HORIZON.muted, fontFamily: "ui-monospace" }}>ext {creds.ext}</div>}
      </div>

      {error && (
        <div style={{ background: "#FEE2E2", color: HORIZON.danger, padding: 8, borderRadius: 6, fontSize: 12, marginBottom: 12 }}>
          {error}
        </div>
      )}

      {status === "in-call" && (
        <div style={{ background: HORIZON.greenSoft, padding: 10, borderRadius: 8, marginBottom: 12 }}>
          <div style={{ fontSize: 11, color: HORIZON.greenDark, textTransform: "uppercase" }}>En llamada con</div>
          <div style={{ fontSize: 16, fontWeight: 700, color: HORIZON.neutralInk, marginTop: 2 }}>{remote}</div>
          <button onClick={end} style={{
            marginTop: 8, width: "100%", padding: "8px 12px", border: 0, borderRadius: 6,
            background: HORIZON.danger, color: "#fff", cursor: "pointer", fontWeight: 600, fontSize: 13
          }}>Colgar</button>
        </div>
      )}

      {status === "registered" && (
        <div>
          <input value={dial} onChange={(e) => setDial(e.target.value)}
                 placeholder="Nº a marcar…"
                 style={{ width: "100%", padding: 10, marginBottom: 8, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 14, fontFamily: "ui-monospace" }} />
          <button disabled={!dial} onClick={() => { call(dial); setDial(""); }} style={{
            width: "100%", padding: "10px 12px", border: 0, borderRadius: 6, cursor: dial ? "pointer" : "not-allowed",
            background: dial ? HORIZON.green : "#D1D5DB", color: "#fff", fontWeight: 700, fontSize: 14
          }}>Llamar</button>
        </div>
      )}

      <audio id="tf-audio-remote" autoPlay />
    </div>
  );
}
