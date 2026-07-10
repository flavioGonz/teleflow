// src/components/reports/Filters.jsx — filtros fecha/agente/cola.
import React from "react";
import { HORIZON } from "@lib/theme.js";

export default function ReportFilters({ filters, setFilters, agents = [], queues = [] }) {
  const upd = (k, v) => setFilters({ ...filters, [k]: v });
  const inputStyle = { padding: 8, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13 };
  return (
    <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(150px,1fr))", gap: 8, marginBottom: 16, background: "#F8FAFC", padding: 12, borderRadius: 8 }}>
      <label style={{ display: "flex", flexDirection: "column", gap: 4 }}>
        <span style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Desde</span>
        <input type="date" value={filters.from || ""} onChange={(e) => upd("from", e.target.value)} style={inputStyle} />
      </label>
      <label style={{ display: "flex", flexDirection: "column", gap: 4 }}>
        <span style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Hasta</span>
        <input type="date" value={filters.to || ""} onChange={(e) => upd("to", e.target.value)} style={inputStyle} />
      </label>
      <label style={{ display: "flex", flexDirection: "column", gap: 4 }}>
        <span style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Agente</span>
        <select value={filters.agent || ""} onChange={(e) => upd("agent", e.target.value)} style={inputStyle}>
          <option value="">Todos</option>
          {agents.map((a) => <option key={a} value={a}>{a}</option>)}
        </select>
      </label>
      <label style={{ display: "flex", flexDirection: "column", gap: 4 }}>
        <span style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: 1, color: HORIZON.muted }}>Cola</span>
        <select value={filters.queue || ""} onChange={(e) => upd("queue", e.target.value)} style={inputStyle}>
          <option value="">Todas</option>
          {queues.map((q) => <option key={q} value={q}>{q}</option>)}
        </select>
      </label>
    </div>
  );
}
