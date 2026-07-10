// src/components/SimpleChart.jsx — pie + bar chart en SVG puro.
import React from "react";
import { HORIZON } from "@lib/theme.js";

const PALETTE = [HORIZON.green, HORIZON.amber, "#3B82F6", "#8B5CF6", HORIZON.danger, "#0EA5E9", "#EC4899"];

export function Pie({ data, size = 140 }) {
  const total = data.reduce((s, d) => s + d.value, 0);
  if (total === 0) return <div style={{ color: HORIZON.muted, fontSize: 12 }}>Sin datos.</div>;
  let angle = -Math.PI / 2;
  const r = size / 2 - 4;
  const cx = size / 2, cy = size / 2;
  return (
    <div style={{ display: "flex", gap: 16, alignItems: "center" }}>
      <svg width={size} height={size}>
        {data.map((d, i) => {
          const a = (d.value / total) * Math.PI * 2;
          const x1 = cx + Math.cos(angle) * r, y1 = cy + Math.sin(angle) * r;
          const x2 = cx + Math.cos(angle + a) * r, y2 = cy + Math.sin(angle + a) * r;
          const large = a > Math.PI ? 1 : 0;
          const path = `M${cx},${cy} L${x1},${y1} A${r},${r} 0 ${large} 1 ${x2},${y2} Z`;
          angle += a;
          return <path key={i} d={path} fill={PALETTE[i % PALETTE.length]} stroke="#fff" strokeWidth="1" />;
        })}
      </svg>
      <ul style={{ margin: 0, padding: 0, listStyle: "none", fontSize: 12 }}>
        {data.map((d, i) => (
          <li key={i} style={{ display: "flex", alignItems: "center", gap: 6, marginBottom: 4 }}>
            <span style={{ display: "inline-block", width: 10, height: 10, borderRadius: 2, background: PALETTE[i % PALETTE.length] }} />
            {d.label} <strong>{d.value}</strong>
            <span style={{ color: HORIZON.muted }}>({Math.round((d.value / total) * 100)}%)</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function Bar({ data, height = 120 }) {
  const max = Math.max(...data.map((d) => d.value), 1);
  return (
    <div style={{ display: "flex", alignItems: "flex-end", gap: 4, height, borderBottom: "1px solid #E5E7EB", padding: "0 4px" }}>
      {data.map((d, i) => (
        <div key={i} title={`${d.label}: ${d.value}`} style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "flex-end", height: "100%" }}>
          <div style={{ width: "80%", background: PALETTE[i % PALETTE.length], height: `${(d.value / max) * 100}%`, borderRadius: "3px 3px 0 0", minHeight: 2 }} />
          <div style={{ fontSize: 9, color: HORIZON.muted, marginTop: 2 }}>{d.label}</div>
        </div>
      ))}
    </div>
  );
}
