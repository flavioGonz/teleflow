// src/views/config/Changelog.jsx — F2.8. Changelog visual estatico.
import React from "react";
import { HORIZON } from "@lib/theme.js";

const ENTRIES = [
  { v: "F2.8", date: "2026-07-10", body: "Configuración migrada a bundle Vite (Branding/Softphone/Agentes/PBX/SSL/Changelog)." },
  { v: "F2.7", date: "2026-07-10", body: "Recordings, Live, Groups, Radar migradas. Charts en tabs Reportes." },
  { v: "F2.6", date: "2026-07-10", body: "CDR, Clientes, IVR migradas. Fix crítico: shell agentes cargaba 2 copias de React (revertido, ver F2.5)." },
  { v: "F2.5", date: "2026-07-10", body: "Flip /agentes/ a bundle Vite (REVERTIDO — SW cacheado del legacy interfería en prod)." },
  { v: "F2.4", date: "2026-07-09", body: "Extensions, Queues, Hotdesking migradas al bundle Vite." },
  { v: "F2.1-F2.3", date: "2026-07-09", body: "CallCenter, Dashboard, Reportes migrados. Softphone SIP.js integrado." },
  { v: "F0-F1", date: "2026-07-09", body: "Vite 5 + React 18 + zustand + stores. api/rt/clock/theme como libs limpios." },
];

export default function Changelog() {
  return (
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
  );
}
