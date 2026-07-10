// src/components/reports/ReportTable.jsx — tabla genérica para todos los tabs.
import React from "react";
import { HORIZON } from "@lib/theme.js";

export default function ReportTable({ columns, rows, loading, empty = "Sin datos." }) {
  if (loading) return <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>Cargando…</div>;
  if (!rows?.length) return <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>{empty}</div>;
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "auto" }}>
      <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
        <thead>
          <tr style={{ background: "#F8FAFC" }}>
            {columns.map((c) => (
              <th key={c.key} style={{
                textAlign: c.align || "left", padding: "10px 12px", color: HORIZON.muted,
                fontSize: 10, textTransform: "uppercase", letterSpacing: 1, borderBottom: "1px solid #E5E7EB"
              }}>{c.label}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((r, i) => (
            <tr key={i} style={{ borderTop: i ? "1px solid #F1F5F9" : "none" }}>
              {columns.map((c) => (
                <td key={c.key} style={{ padding: "9px 12px", textAlign: c.align || "left", fontFamily: c.mono ? "ui-monospace" : "inherit" }}>
                  {c.render ? c.render(r) : (r[c.key] ?? "-")}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
