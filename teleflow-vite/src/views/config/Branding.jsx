// src/views/config/Branding.jsx — F2.8.
import React from "react";
import { HORIZON } from "@lib/theme.js";
import { useSettings } from "../../stores/settings.js";

const FIELDS = [
  { k: "brand_company_name",   label: "Empresa",              ph: "Horizon Seguridad" },
  { k: "brand_app_name",       label: "Nombre de la app",     ph: "TeleFlow" },
  { k: "brand_logo_text",      label: "Logo — texto",         ph: "HORIZON" },
  { k: "brand_logo_sub",       label: "Logo — subtítulo",     ph: "SEGURIDAD" },
  { k: "brand_tagline",        label: "Tagline del login",    ph: "Centro de monitoreo" },
  { k: "brand_subtitle",       label: "Subtítulo del login",  ph: "Plataforma unificada…" },
  { k: "brand_primary_color",  label: "Color primario",       type: "color" },
  { k: "brand_accent_color",   label: "Color acento",         type: "color" },
];

export default function Branding() {
  const settings = useSettings((s) => s.settings);
  const save = useSettings((s) => s.save);
  const saved = useSettings((s) => s.saved);
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 20 }}>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: 14 }}>
        {FIELDS.map((f) => (
          <label key={f.k} style={{ display: "flex", flexDirection: "column", gap: 4 }}>
            <span style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>
              {f.label} {saved === f.k && <span style={{ color: HORIZON.green }}>✓</span>}
            </span>
            <input type={f.type || "text"} placeholder={f.ph}
              value={settings[f.k] || ""}
              onChange={(e) => save({ [f.k]: e.target.value })}
              style={{ padding: 10, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, background: f.type === "color" ? "transparent" : "#fff" }} />
          </label>
        ))}
      </div>
      <p style={{ marginTop: 16, fontSize: 11, color: HORIZON.muted }}>Auto-guardado en cada cambio. Endpoint: <code>api/app_settings.php</code></p>
    </div>
  );
}
