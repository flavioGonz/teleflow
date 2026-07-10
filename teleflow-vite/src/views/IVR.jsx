// src/views/IVR.jsx — placeholder para F2.6.1 (requiere ReactFlow).
import React from "react";
import { HORIZON } from "@lib/theme.js";
export default function IVR() {
  return (
    <div>
      <h1 style={{ margin: "0 0 16px", fontSize: 22, color: HORIZON.neutralInk }}>Diseñador IVR</h1>
      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 24, textAlign: "center" }}>
        <div style={{ fontSize: 14, color: HORIZON.neutralInk, marginBottom: 12, fontWeight: 600 }}>
          Requiere <code>reactflow</code> — se integra en F2.6.1.
        </div>
        <div style={{ fontSize: 13, color: HORIZON.muted, maxWidth: 420, margin: "0 auto 16px" }}>
          Mientras tanto, seguí usando el diseñador legacy para tocar los IVRs. El bundle nuevo no lo carga todavía porque reactflow suma ~350 KB al bundle.
        </div>
        <a href="/?legacy=1#ivr" style={{
          display: "inline-block", padding: "8px 16px", border: `1px solid ${HORIZON.green}`, borderRadius: 6,
          color: HORIZON.green, textDecoration: "none", fontSize: 13, fontWeight: 600
        }}>Abrir en shell legacy →</a>
      </div>
    </div>
  );
}
