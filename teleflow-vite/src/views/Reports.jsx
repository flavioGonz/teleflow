// src/views/Reports.jsx — F2.3 + F179: 6 tabs con charts visuales.
import React, { useEffect, useState, useMemo } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";
import { usePbxData } from "../stores/pbxData.js";
import ReportFilters from "../components/reports/Filters.jsx";
import ReportTable from "../components/reports/ReportTable.jsx";
import { Pie, Bar } from "../components/SimpleChart.jsx";

const TABS = [
  { id: "summary",  label: "Resumen"       },
  { id: "by_agent", label: "Por agente"    },
  { id: "by_queue", label: "Por cola"      },
  { id: "calls",    label: "Llamadas"      },
  { id: "pauses",   label: "Pausas"        },
  { id: "failover_calls", label: "Failover" },
];

const today = () => new Date().toISOString().slice(0, 10);
const daysAgo = (n) => new Date(Date.now() - n * 86400e3).toISOString().slice(0, 10);

function Section({ title, children }) {
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 16, marginBottom: 12 }}>
      <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, marginBottom: 10 }}>{title}</div>
      {children}
    </div>
  );
}

function ExportButtons({ action, filters }) {
  const params = new URLSearchParams({ action, ...filters }).toString();
  const s = { padding: "6px 12px", borderRadius: 6, border: "1px solid #E5E7EB", background: "#fff", cursor: "pointer", fontSize: 12, marginLeft: 6, color: HORIZON.neutralInk, textDecoration: "none" };
  return (
    <div>
      <a href={`api/reports_export.php?format=xlsx&${params}`} style={s}>Excel</a>
      <a href={`api/reports_export.php?format=pdf&${params}`} style={s}>PDF</a>
    </div>
  );
}

function TabSummary({ result }) {
  const totals = result?.totals || {};
  const disp = result?.disposition_breakdown || {};
  const totalsData = Object.entries(totals).map(([k, v]) => ({ label: k.replace(/_/g, " ").slice(0, 8), value: Number(v) || 0 }));
  const dispData   = Object.entries(disp).map(([k, v]) => ({ label: k, value: Number(v) || 0 }));
  return (
    <div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(160px,1fr))", gap: 10, marginBottom: 16 }}>
        {Object.entries(totals).map(([k, v]) => (
          <div key={k} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 12 }}>
            <div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase" }}>{k.replace(/_/g, " ")}</div>
            <div style={{ fontSize: 22, fontWeight: 800, color: HORIZON.neutralInk, fontFamily: "ui-monospace" }}>{v}</div>
          </div>
        ))}
      </div>
      {dispData.length > 0 && <Section title="Disposición"><Pie data={dispData} /></Section>}
      {totalsData.length > 1 && <Section title="Totales"><Bar data={totalsData} /></Section>}
    </div>
  );
}

function TabByAgent({ rows }) {
  const chart = useMemo(() => rows.slice(0, 10).map((r) => ({ label: String(r.agent || "?").slice(0, 4), value: Number(r.answered) || Number(r.calls) || 0 })), [rows]);
  return (
    <div>
      {chart.length > 0 && <Section title="Top 10 agentes por atendidas"><Bar data={chart} /></Section>}
      <ReportTable columns={[
        { key: "agent",         label: "Agente" },
        { key: "calls",         label: "Llamadas",  align: "right", mono: true },
        { key: "answered",      label: "Atendidas", align: "right", mono: true },
        { key: "avg_talk_time", label: "Talk avg",  align: "right", mono: true, render: (r) => r.avg_talk_time || "-" },
        { key: "total_pause",   label: "Pausa (s)", align: "right", mono: true },
      ]} rows={rows} />
    </div>
  );
}

function TabByQueue({ rows }) {
  const chart = useMemo(() => rows.slice(0, 10).map((r) => ({ label: String(r.queue || "?").slice(0, 4), value: Number(r.answered) || Number(r.calls) || 0 })), [rows]);
  const disp = useMemo(() => {
    const ans = rows.reduce((s, r) => s + (Number(r.answered) || 0), 0);
    const abn = rows.reduce((s, r) => s + (Number(r.abandoned) || 0), 0);
    return [{ label: "Atendidas", value: ans }, { label: "Abandonadas", value: abn }].filter((x) => x.value > 0);
  }, [rows]);
  return (
    <div>
      <div style={{ display: "grid", gridTemplateColumns: chart.length > 0 && disp.length > 0 ? "1fr 300px" : "1fr", gap: 12, marginBottom: 12 }}>
        {chart.length > 0 && <Section title="Volumen por cola"><Bar data={chart} /></Section>}
        {disp.length > 0 && <Section title="Atendidas vs abandonadas"><Pie data={disp} /></Section>}
      </div>
      <ReportTable columns={[
        { key: "queue",     label: "Cola" },
        { key: "calls",     label: "Llamadas",  align: "right", mono: true },
        { key: "answered",  label: "Atendidas", align: "right", mono: true },
        { key: "abandoned", label: "Abandon.",  align: "right", mono: true },
        { key: "avg_wait",  label: "Wait avg",  align: "right", mono: true },
      ]} rows={rows} />
    </div>
  );
}

function TabCalls({ rows }) {
  return (
    <ReportTable columns={[
      { key: "calldate", label: "Fecha", mono: true, render: (r) => (r.calldate || "").replace("T", " ").slice(0, 19) },
      { key: "src",      label: "Origen" },
      { key: "dst",      label: "Destino" },
      { key: "billsec",  label: "Duración", align: "right", mono: true, render: (r) => r.billsec > 0 ? `${Math.floor(r.billsec/60)}:${String(r.billsec%60).padStart(2,"0")}` : "0:00" },
      { key: "disposition", label: "Estado" },
    ]} rows={rows} />
  );
}

function TabPauses({ rows }) {
  const chart = useMemo(() => {
    const byReason = {};
    rows.forEach((r) => { byReason[r.reason || "Sin motivo"] = (byReason[r.reason || "Sin motivo"] || 0) + Number(r.duration || 0); });
    return Object.entries(byReason).map(([k, v]) => ({ label: k, value: v }));
  }, [rows]);
  return (
    <div>
      {chart.length > 0 && <Section title="Tiempo en pausa por motivo (s)"><Pie data={chart} /></Section>}
      <ReportTable columns={[
        { key: "agent",      label: "Agente" },
        { key: "reason",     label: "Motivo" },
        { key: "duration",   label: "Duración (s)", align: "right", mono: true },
        { key: "started_at", label: "Inicio", mono: true },
      ]} rows={rows} />
    </div>
  );
}

function TabFailover({ rows }) {
  return (
    <ReportTable columns={[
      { key: "calldate",     label: "Fecha", mono: true, render: (r) => (r.calldate || "").replace("T", " ").slice(0, 19) },
      { key: "src",          label: "Origen" },
      { key: "first_queue",  label: "Cola inicial" },
      { key: "final_queue",  label: "Cola final" },
      { key: "final_agent",  label: "Agente" },
      { key: "disposition",  label: "Estado" },
    ]} rows={rows} />
  );
}

export default function Reports() {
  const [tab, setTab] = useState("summary");
  const [filters, setFilters] = useState({ from: daysAgo(7), to: today(), agent: "", queue: "" });
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  useEffect(() => { fetch(); }, [fetch]);

  const agents = useMemo(() => (data?.agents || []).map((a) => a.agent_number || a.number).filter(Boolean), [data]);
  const queues = useMemo(() => (data?.queues || []).map((q) => q.extension || q.id).filter(Boolean), [data]);

  useEffect(() => {
    let alive = true;
    setLoading(true);
    api.get("reports.php", { action: tab, ...filters })
      .then((j) => { if (alive) setResult(j); })
      .catch(() => { if (alive) setResult(null); })
      .finally(() => { if (alive) setLoading(false); });
    return () => { alive = false; };
  }, [tab, filters]);

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Reportes</h1>
        <ExportButtons action={tab} filters={filters} />
      </div>

      <div style={{ display: "flex", gap: 4, borderBottom: "1px solid #E5E7EB", marginBottom: 16 }}>
        {TABS.map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)} style={{
            padding: "10px 16px", border: 0, borderBottom: `3px solid ${tab === t.id ? HORIZON.green : "transparent"}`,
            background: "transparent", cursor: "pointer", fontSize: 13,
            color: tab === t.id ? HORIZON.neutralInk : HORIZON.muted,
            fontWeight: tab === t.id ? 700 : 500,
          }}>{t.label}</button>
        ))}
      </div>

      <ReportFilters filters={filters} setFilters={setFilters} agents={agents} queues={queues} />

      {loading && <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>Cargando…</div>}
      {!loading && tab === "summary"       && <TabSummary  result={result} />}
      {!loading && tab === "by_agent"      && <TabByAgent  rows={result?.rows || result?.by_agent || []} />}
      {!loading && tab === "by_queue"      && <TabByQueue  rows={result?.rows || result?.by_queue || []} />}
      {!loading && tab === "calls"         && <TabCalls    rows={result?.rows || result?.calls || []} />}
      {!loading && tab === "pauses"        && <TabPauses   rows={result?.rows || result?.pauses || []} />}
      {!loading && tab === "failover_calls" && <TabFailover rows={result?.rows || result?.calls || []} />}
    </div>
  );
}
