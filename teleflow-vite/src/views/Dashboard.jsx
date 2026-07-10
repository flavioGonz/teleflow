// src/views/Dashboard.jsx — F2.2 dashboard real.
import React, { useEffect } from "react";
import { HORIZON } from "@lib/theme.js";
import { useLive } from "../stores/live.js";
import { usePbxData } from "../stores/pbxData.js";
import QueuesRow from "../components/QueuesRow.jsx";
import ActiveCallsRow from "../components/ActiveCallsRow.jsx";
import RecordingsRow from "../components/RecordingsRow.jsx";

function KpiCard({ label, value, hint, color }) {
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 16 }}>
      <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1.5 }}>{label}</div>
      <div style={{ fontSize: 28, fontWeight: 800, color: color || HORIZON.neutralInk, marginTop: 6, fontFamily: "ui-monospace" }}>{value}</div>
      {hint && <div style={{ fontSize: 12, color: HORIZON.muted, marginTop: 4 }}>{hint}</div>}
    </div>
  );
}

function Section({ title, children, right }) {
  return (
    <section style={{ marginBottom: 24 }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 12 }}>
        <h2 style={{ margin: 0, fontSize: 12, textTransform: "uppercase", letterSpacing: 1.5, color: HORIZON.muted, fontWeight: 700 }}>{title}</h2>
        {right}
      </div>
      {children}
    </section>
  );
}

export default function Dashboard() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const activeCalls = useLive((s) => s.activeCalls);
  const connected = useLive((s) => s.connected);

  useEffect(() => { fetch(); const t = setInterval(() => fetch(true), 30000); return () => clearInterval(t); }, [fetch]);

  const nExts = data?.extensions?.length ?? "…";
  const nQueues = data?.queues?.length ?? "…";
  const nOnline = data?.extensions?.filter?.((e) => /^(Registered|OK)/.test(e.status || "")).length ?? "…";

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 20 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Dashboard</h1>
        <button onClick={() => fetch(true)} style={{
          padding: "6px 12px", border: `1px solid ${HORIZON.green}`, background: "#fff",
          color: HORIZON.green, borderRadius: 6, cursor: "pointer", fontSize: 12
        }}>Refrescar</button>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(180px,1fr))", gap: 12, marginBottom: 24 }}>
        <KpiCard label="Extensiones" value={nExts} hint={`${nOnline} online`} />
        <KpiCard label="Colas" value={nQueues} />
        <KpiCard label="Llamadas activas" value={activeCalls.length} color={activeCalls.length > 0 ? HORIZON.green : undefined} hint="realtime hub" />
        <KpiCard label="Hub" value={connected ? "OK" : "off"} color={connected ? HORIZON.green : HORIZON.danger} />
      </div>

      <Section title="Colas"><QueuesRow /></Section>
      <Section title="Llamadas activas"><ActiveCallsRow /></Section>
      <Section title="Últimas grabaciones"><RecordingsRow limit={8} /></Section>
    </div>
  );
}
