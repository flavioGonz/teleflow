// src/components/MyCalls.jsx — tabla mis llamadas (histórico de agente).
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

export default function MyCalls({ compact = false }) {
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;
    api.action("agent.php", "my_calls", { limit: compact ? 10 : 50 })
      .then((j) => { if (alive) setRows(Array.isArray(j?.calls) ? j.calls : []); })
      .catch(() => { if (alive) setRows([]); })
      .finally(() => { if (alive) setLoading(false); });
    return () => { alive = false; };
  }, [compact]);

  if (loading) return <div style={{ color: HORIZON.muted, padding: 12 }}>Cargando llamadas…</div>;
  if (rows.length === 0) return <div style={{ color: HORIZON.muted, padding: 12 }}>Sin llamadas registradas.</div>;

  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
      <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
        <thead>
          <tr style={{ background: "#F8FAFC" }}>
            <th style={{ textAlign: "left", padding: "10px 12px", color: HORIZON.muted, fontSize: 11, textTransform: "uppercase", letterSpacing: 1 }}>Fecha</th>
            <th style={{ textAlign: "left", padding: "10px 12px", color: HORIZON.muted, fontSize: 11, textTransform: "uppercase", letterSpacing: 1 }}>Origen → Destino</th>
            <th style={{ textAlign: "right", padding: "10px 12px", color: HORIZON.muted, fontSize: 11, textTransform: "uppercase", letterSpacing: 1 }}>Duración</th>
            <th style={{ textAlign: "left", padding: "10px 12px", color: HORIZON.muted, fontSize: 11, textTransform: "uppercase", letterSpacing: 1 }}>Estado</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((c, i) => (
            <tr key={i} style={{ borderTop: "1px solid #F1F5F9" }}>
              <td style={{ padding: "10px 12px", fontFamily: "ui-monospace", fontSize: 12 }}>{(c.calldate || "").replace("T", " ").slice(0, 19)}</td>
              <td style={{ padding: "10px 12px" }}>{c.src || "-"} → <strong>{c.dst || "-"}</strong></td>
              <td style={{ padding: "10px 12px", textAlign: "right", fontFamily: "ui-monospace" }}>
                {c.billsec > 0 ? `${Math.floor(c.billsec / 60)}:${String(c.billsec % 60).padStart(2, "0")}` : "0:00"}
              </td>
              <td style={{ padding: "10px 12px", color: c.disposition === "ANSWERED" ? HORIZON.green : HORIZON.muted, fontSize: 12 }}>
                {c.disposition || "-"}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
