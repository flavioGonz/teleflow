// src/views/config/Changelog.jsx — F2.8 + F7. Widget diff prod/build.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

const ENTRIES = [
  { v: "F5.4/F7",   date: "2026-07-10", body: "7 endpoints backend más migrados (21 total). Endpoint /api/version.php + widget diff prod vs build." },
  { v: "F5.3/F4.4", date: "2026-07-10", body: "6 endpoints backend a bootstrap. Tests rt.js + live.js. 32 tests verdes." },
  { v: "F2.8/F5.2", date: "2026-07-10", body: "Configuración migrada (6 sub-tabs). 5 endpoints backend más. Tests softphone." },
  { v: "F2.7",      date: "2026-07-10", body: "Recordings, Live, Groups, Radar migradas. Charts en tabs Reportes." },
  { v: "F2.6",      date: "2026-07-10", body: "CDR, Clientes, IVR migradas." },
  { v: "F2.5",      date: "2026-07-10", body: "Flip /agentes/ REVERTIDO — SW cacheado del legacy interfería en prod." },
  { v: "F2.4",      date: "2026-07-09", body: "Extensions, Queues, Hotdesking migradas al bundle Vite." },
  { v: "F2.1-F2.3", date: "2026-07-09", body: "CallCenter, Dashboard, Reportes migrados. Softphone SIP.js integrado." },
  { v: "F0-F1",     date: "2026-07-09", body: "Vite 5 + React 18 + zustand + stores. api/rt/clock/theme como libs limpios." },
];

function VersionWidget() {
  const [v, setV] = useState(null);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    api.get("version.php").then(setV).catch(() => setV(null)).finally(() => setLoading(false));
  }, []);
  if (loading) return <div style={{ padding: 14, color: HORIZON.muted, fontSize: 13 }}>Cargando estado del deploy…</div>;
  if (!v) return <div style={{ padding: 14, color: HORIZON.danger, fontSize: 13 }}>No se pudo leer /api/version.php</div>;
  const fmtSize = (n) => n < 1024 ? `${n} B` : n < 1024*1024 ? `${(n/1024).toFixed(1)} KB` : `${(n/1024/1024).toFixed(2)} MB`;
  const box = { background: "#fff", border: "1px solid #E5E7EB", borderRadius: 8, padding: 14 };
  const lbl = { fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, fontWeight: 700 };
  return (
    <div style={{ background: HORIZON.greenSoft, border: `1px solid ${HORIZON.green}`, borderRadius: 10, padding: 14, marginBottom: 20 }}>
      <div style={{ fontSize: 11, color: HORIZON.greenDark, textTransform: "uppercase", letterSpacing: 1.5, fontWeight: 700, marginBottom: 10 }}>
        Estado del deploy · {v.server}
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 10 }}>
        <div style={box}>
          <div style={lbl}>Legacy (app.jsx)</div>
          {v.legacy?.exists ? <>
            <div style={{ fontSize: 12, fontFamily: "ui-monospace", marginTop: 4 }}>md5: {v.legacy.md5.slice(0, 12)}…</div>
            <div style={{ fontSize: 11, color: HORIZON.muted }}>{fmtSize(v.legacy.size)} · {new Date(v.legacy.mtime).toLocaleString("es-UY")}</div>
          </> : <div style={{ fontSize: 12, color: HORIZON.danger }}>no encontrado</div>}
        </div>
        <div style={box}>
          <div style={lbl}>Bundle Vite (app.build.js)</div>
          {v.build?.exists ? <>
            <div style={{ fontSize: 12, fontFamily: "ui-monospace", marginTop: 4 }}>{fmtSize(v.build.size)} + {v.build.chunks_count} chunks</div>
            <div style={{ fontSize: 11, color: HORIZON.muted }}>chunks: {fmtSize(v.build.chunks_size)} · {new Date(v.build.mtime).toLocaleString("es-UY")}</div>
          </> : <div style={{ fontSize: 12, color: HORIZON.muted }}>no desplegado</div>}
        </div>
        {v.git && (
          <div style={box}>
            <div style={lbl}>Git ({v.git.branch})</div>
            <div style={{ fontSize: 12, fontFamily: "ui-monospace", marginTop: 4, color: HORIZON.green }}>{v.git.sha}</div>
            <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 2, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{v.git.subject}</div>
          </div>
        )}
      </div>
    </div>
  );
}

export default function Changelog() {
  return (
    <div>
      <VersionWidget />
      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 20 }}>
        <ol style={{ margin: 0, padding: 0, listStyle: "none" }}>
          {ENTRIES.map((e, i) => (
            <li key={i} style={{ paddingBottom: 14, marginBottom: 14, borderBottom: i < ENTRIES.length - 1 ? "1px solid #F1F5F9" : "none" }}>
              <div style={{ display: "flex", gap: 12, alignItems: "baseline" }}>
                <span style={{ background: HORIZON.greenSoft, color: HORIZON.greenDark, padding: "2px 10px", borderRadius: 4, fontSize: 11, fontWeight: 700, fontFamily: "ui-monospace" }}>{e.v}</span>
                <span style={{ fontSize: 11, color: HORIZON.muted, fontFamily: "ui-monospace" }}>{e.date}</span>
              </div>
              <p style={{ margin: "6px 0 0", fontSize: 13, color: HORIZON.neutralInk, lineHeight: 1.5 }}>{e.body}</p>
            </li>
          ))}
        </ol>
      </div>
    </div>
  );
}
