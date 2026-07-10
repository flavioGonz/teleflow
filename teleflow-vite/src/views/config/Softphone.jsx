// src/views/config/Softphone.jsx — F2.8.
import React from "react";
import { HORIZON } from "@lib/theme.js";
import { useSettings } from "../../stores/settings.js";

export default function Softphone() {
  const settings = useSettings((s) => s.settings);
  const save = useSettings((s) => s.save);
  const inp = { padding: 10, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, width: "100%" };
  const lbl = { fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, marginBottom: 4, display: "block" };
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 20, display: "grid", gap: 16 }}>
      <div>
        <span style={lbl}>Volumen del ringing ({Math.round((parseFloat(settings.softphone_ringing_volume) || 0.7) * 100)}%)</span>
        <input type="range" min="0" max="1" step="0.05"
          value={parseFloat(settings.softphone_ringing_volume) || 0.7}
          onChange={(e) => save({ softphone_ringing_volume: e.target.value })}
          style={{ width: "100%" }} />
      </div>
      <div>
        <span style={lbl}>Codec preferido</span>
        <input type="text" value={settings.softphone_default_codec || ""}
          placeholder="opus, PCMU, PCMA…"
          onChange={(e) => save({ softphone_default_codec: e.target.value })}
          style={inp} />
      </div>
      <div>
        <span style={lbl}>Modo DTMF</span>
        <select value={settings.softphone_dtmf_mode || "rfc2833"} onChange={(e) => save({ softphone_dtmf_mode: e.target.value })} style={inp}>
          <option value="rfc2833">RFC 2833 (recomendado)</option>
          <option value="inband">In-band</option>
          <option value="info">SIP INFO</option>
        </select>
      </div>
      <label style={{ display: "flex", gap: 8, alignItems: "center", fontSize: 13 }}>
        <input type="checkbox" checked={settings.softphone_auto_answer === "1"}
          onChange={(e) => save({ softphone_auto_answer: e.target.checked ? "1" : "0" })} />
        Auto-atender llamadas entrantes
      </label>
      <div>
        <span style={lbl}>Extensiones sin softphone (coma o espacio)</span>
        <input type="text" value={settings.softphone_disabled_extensions || ""}
          placeholder="ej: 1500,1550,1601"
          onChange={(e) => save({ softphone_disabled_extensions: e.target.value })}
          style={inp} />
      </div>
    </div>
  );
}
