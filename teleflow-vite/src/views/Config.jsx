// src/views/Config.jsx — F2.8 + F178: layout de configuración con 7 sub-tabs.
import React, { useEffect, useState, lazy, Suspense } from "react";
import { HORIZON } from "@lib/theme.js";
import { useSettings } from "../stores/settings.js";

const Branding        = lazy(() => import("./config/Branding.jsx"));
const Softphone       = lazy(() => import("./config/Softphone.jsx"));
const Agents          = lazy(() => import("./config/Agents.jsx"));
const AgentQueuePref  = lazy(() => import("./config/AgentQueuePref.jsx"));
const SSL             = lazy(() => import("./config/SSL.jsx"));
const Changelog       = lazy(() => import("./config/Changelog.jsx"));
const PBX             = lazy(() => import("./config/PBX.jsx"));

const TABS = [
  { id: "branding",   label: "Branding",         comp: Branding },
  { id: "softphone",  label: "Softphone",        comp: Softphone },
  { id: "agents",     label: "Agentes",          comp: Agents },
  { id: "agent_queue",label: "Colas x Agente",   comp: AgentQueuePref },
  { id: "pbx",        label: "PBX",              comp: PBX },
  { id: "ssl",        label: "SSL / Cert",       comp: SSL },
  { id: "changelog",  label: "Changelog",        comp: Changelog },
];

export default function Config() {
  const [tab, setTab] = useState("branding");
  const load = useSettings((s) => s.load);
  useEffect(() => { load(); }, [load]);
  const Active = (TABS.find((t) => t.id === tab) || TABS[0]).comp;
  return (
    <div>
      <h1 style={{ margin: "0 0 16px", fontSize: 22, color: HORIZON.neutralInk }}>Configuración</h1>
      <div style={{ display: "flex", gap: 4, borderBottom: "1px solid #E5E7EB", marginBottom: 16, overflowX: "auto" }}>
        {TABS.map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)} style={{
            padding: "10px 16px", border: 0, background: "transparent", cursor: "pointer", fontSize: 13,
            borderBottom: `3px solid ${tab === t.id ? HORIZON.green : "transparent"}`,
            color: tab === t.id ? HORIZON.neutralInk : HORIZON.muted,
            fontWeight: tab === t.id ? 700 : 500, whiteSpace: "nowrap",
          }}>{t.label}</button>
        ))}
      </div>
      <Suspense fallback={<div style={{ padding: 20, color: HORIZON.muted }}>Cargando…</div>}>
        <Active />
      </Suspense>
    </div>
  );
}
