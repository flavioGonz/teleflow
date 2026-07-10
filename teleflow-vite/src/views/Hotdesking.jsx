// src/views/Hotdesking.jsx — F2.4c: wallboard agentes (logueados/offline).
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

function AgentCard({ a }) {
  const st = a.status || a.state || "offline";
  const inCall = /call|talking|in.?call/i.test(st);
  const paused = /pause/i.test(st);
  const online = /online|logged|available|idle/i.test(st) || a.logged_in;
  const color = inCall ? HORIZON.green : paused ? HORIZON.amber : online ? HORIZON.green : HORIZON.muted;
  const label = inCall ? "En llamada" : paused ? "En pausa" : online ? "Disponible" : "Offline";
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 12, position: "relative" }}>
      <div style={{ position: "absolute", top: 10, right: 10, width: 8, height: 8, borderRadius: 999, background: color, boxShadow: `0 0 0 4px ${color}22` }} />
      <div style={{ fontSize: 11, color: HORIZON.muted, fontFamily: "ui-monospace" }}>#{a.agent_number || a.number}</div>
      <div style={{ fontSize: 14, fontWeight: 700, color: HORIZON.neutralInk, marginTop: 2 }}>{a.name || a.agent_name || "-"}</div>
      <div style={{ fontSize: 12, marginTop: 8, color }}>{label}</div>
      {a.ext && <div style={{ fontSize: 10, color: HORIZON.muted, marginTop: 4, fontFamily: "ui-monospace" }}>ext {a.ext}</div>}
    </div>
  );
}

export default function Hotdesking() {
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    const load = () => api.get("hotdesking.php", { action: "list_agents" })
      .then((j) => setAgents(j?.agents || j?.rows || []))
      .catch(() => setAgents([]))
      .finally(() => setLoading(false));
    load();
    const t = setInterval(load, 10000);
    return () => clearInterval(t);
  }, []);

  const logged   = agents.filter((a) => /online|logged|available|call|pause/i.test(a.status || "") || a.logged_in);
  const offline  = agents.filter((a) => !logged.includes(a));

  return (
    <div>
      <h1 style={{ margin: "0 0 16px", fontSize: 22, color: HORIZON.neutralInk }}>Hotdesking / Wallboard</h1>
      {loading && <div style={{ color: HORIZON.muted, padding: 20 }}>Cargando agentes…</div>}
      <section style={{ marginBottom: 24 }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
          <h2 style={{ margin: 0, fontSize: 11, textTransform: "uppercase", letterSpacing: 1.5, color: HORIZON.green, fontWeight: 700 }}>Logueados ({logged.length})</h2>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill,minmax(200px,1fr))", gap: 10 }}>
          {logged.map((a) => <AgentCard key={a.agent_number || a.id} a={a} />)}
          {logged.length === 0 && !loading && <div style={{ color: HORIZON.muted, gridColumn: "1/-1", padding: 20, textAlign: "center", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10 }}>Nadie logueado.</div>}
        </div>
      </section>
      <section>
        <h2 style={{ margin: "0 0 10px", fontSize: 11, textTransform: "uppercase", letterSpacing: 1.5, color: HORIZON.muted, fontWeight: 700 }}>Offline ({offline.length})</h2>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill,minmax(200px,1fr))", gap: 10 }}>
          {offline.map((a) => <AgentCard key={a.agent_number || a.id} a={a} />)}
        </div>
      </section>
    </div>
  );
}
