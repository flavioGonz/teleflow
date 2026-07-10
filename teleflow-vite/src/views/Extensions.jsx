// src/views/Extensions.jsx — F2.4a: lista de extensiones.
import React, { useEffect, useMemo, useState } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";

function StatusDot({ status }) {
  const s = String(status || "");
  const ok = /^OK/.test(s) || /Registered/i.test(s);
  const ring = /Ring|Busy/i.test(s);
  const color = ok ? HORIZON.green : ring ? HORIZON.amber : HORIZON.muted;
  return <span title={s} style={{ display: "inline-block", width: 10, height: 10, borderRadius: 999, background: color, boxShadow: `0 0 0 4px ${color}22` }} />;
}

export default function Extensions() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const peers = useLive((s) => s.peers);
  const [q, setQ] = useState("");
  useEffect(() => { fetch(); }, [fetch]);

  const exts = useMemo(() => {
    const list = data?.extensions || [];
    const needle = q.trim().toLowerCase();
    return needle
      ? list.filter((e) => (e.extension || "").includes(needle) || (e.name || "").toLowerCase().includes(needle))
      : list;
  }, [data, q]);

  const enrich = (e) => {
    const live = peers[e.extension] || {};
    return { ...e, live_status: live.status || e.status };
  };

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Extensiones</h1>
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar…"
          style={{ padding: "8px 12px", border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, minWidth: 220 }} />
      </div>

      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
        <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
          <thead>
            <tr style={{ background: "#F8FAFC" }}>
              <th style={{ padding: "10px 12px", width: 30 }}></th>
              <th style={{ padding: "10px 12px", textAlign: "left", fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Ext</th>
              <th style={{ padding: "10px 12px", textAlign: "left", fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Nombre</th>
              <th style={{ padding: "10px 12px", textAlign: "left", fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Tecnología</th>
              <th style={{ padding: "10px 12px", textAlign: "center", fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Video</th>
              <th style={{ padding: "10px 12px", textAlign: "right", fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {exts.map(enrich).map((e, i) => (
              <tr key={e.extension || i} style={{ borderTop: i ? "1px solid #F1F5F9" : "none" }}>
                <td style={{ padding: "9px 12px", textAlign: "center" }}><StatusDot status={e.live_status} /></td>
                <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", fontWeight: 700 }}>{e.extension}</td>
                <td style={{ padding: "9px 12px" }}>{e.name || "-"}</td>
                <td style={{ padding: "9px 12px", fontSize: 12, color: HORIZON.muted }}>{e.tech || "chan_sip"}</td>
                <td style={{ padding: "9px 12px", textAlign: "center" }}>
                  {e.rtsp_url ? <span className="material-icons-round" style={{ fontSize: 16, color: HORIZON.green }}>videocam</span> : ""}
                </td>
                <td style={{ padding: "9px 12px", textAlign: "right" }}>
                  <a href={`/?ext=${e.extension}`} style={{ fontSize: 11, color: HORIZON.green, textDecoration: "none" }}>Editar (legacy) →</a>
                </td>
              </tr>
            ))}
            {exts.length === 0 && (
              <tr><td colSpan="6" style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>Sin coincidencias.</td></tr>
            )}
          </tbody>
        </table>
      </div>
      <p style={{ marginTop: 10, fontSize: 11, color: HORIZON.muted }}>
        Total: {exts.length} · Ficha completa (RTSP, agente, historial) queda en shell legacy — se migra en F2.4.1.
      </p>
    </div>
  );
}
