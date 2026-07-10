// src/views/Reports.jsx — F2.3: 6 tabs + filtros + exports.
import React, { useEffect, useState, useMemo } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";
import { usePbxData } from "../stores/pbxData.js";
import ReportFilters from "../components/reports/Filters.jsx";
import ReportTable from "../components/reports/ReportTable.jsx";
import { Pie } from "../components/SimpleChart.jsx";

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

function ExportButtons({ action, filters }) {
  const params = new URLSearchParams({ action, ...filters }).toString();
  const styleBtn = { padding: "6px 12px", borderRadius: 6, border: "1px solid #E5E7EB", background: "#fff", cursor: "pointer", fontSize: 12, marginLeft: 6, color: HORIZON.neutralInk, textDecoration: "none" };
  return (
    <div>
      <a href={`api/reports_export.php?format=xlsx&${params}`} style={styleBtn}>Excel</a>
      <a href={`api/reports_export.php?format=pdf&${params}`} style={styleBtn}>PDF</a>
    </div>
  );
}

function TabSummary({ result }) {
  const totals = result?.totals || {};
  const disp = result?.disposition_breakdown || {};
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
      {Object.keys(disp).length > 0 && (
        <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 16 }}>
          <div style={{ fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, marginBottom: 10 }}>Disposición</div>
          <Pie data={Object.entries(disp).map(([k, v]) => ({ label: k, value: Number(v) || 0 }))} />
        </div>
      )}
    </div>
  );
}

function TabByAgent({ rows }) {
  return (
    <ReportTable columns={[
      { key: "agent",         label: "Agente" },
      { key: "calls",         label: "Llamadas", align: "right", mono: true },
      { key: "answered",      label: "Atendidas", align: "right", mono: true },
      { key: "avg_talk_time", label: "Talk avg", align: "right", mono: true, render: (r) => r.avg_talk_time || "-" },
      { key: "total_pause",   label: "Pausa (s)", align: "right", mono: true },
    ]} rows={rows} />
  );
}

function TabByQueue({ rows }) {
  return (
    <ReportTable columns={[
      { key: "queue",     label: "Cola" },
      { key: "calls",     label: "Llamadas",  align: "right", mono: true },
      { key: "answered",  label: "Atendidas", align: "right", mono: true },
      { key: "abandoned", label: "Abandon.",  align: "right", mono: true },
      { key: "avg_wait",  label: "Wait avg",  align: "right", mono: true },
    ]} rows={rows} />
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
  return (
    <ReportTable columns={[
      { key: "agent",      label: "Agente" },
      { key: "reason",     label: "Motivo" },
      { key: "duration",   label: "Duración (s)", align: "right", mono: true },
      { key: "started_at", label: "Inicio", mono: true },
    ]} rows={rows} />
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
