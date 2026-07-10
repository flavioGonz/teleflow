// src/views/config/AgentQueuePref.jsx — F178: asignación colas por agente.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { usePbxData } from "../../stores/pbxData.js";
import { HORIZON } from "@lib/theme.js";

export default function AgentQueuePref() {
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(null);
  const [msg, setMsg] = useState(null);
  const fetch = usePbxData((s) => s.fetch);
  const pbx = usePbxData((s) => s.data);

  useEffect(() => { fetch(); }, [fetch]);

  const load = () => {
    setLoading(true);
    api.get("agent_queue_pref.php", { action: "list_all" })
      .then((j) => setAgents(j?.agents || []))
      .catch(() => setAgents([]))
      .finally(() => setLoading(false));
  };
  useEffect(() => { load(); }, []);

  const queues = (pbx?.queues || []).map((q) => q.extension || q.id).filter(Boolean);

  const toggle = async (agent, queue) => {
    const cur = agent.queues || [];
    const next = cur.includes(queue) ? cur.filter((q) => q !== queue) : [...cur, queue];
    // Optimistic update
    setAgents((prev) => prev.map((a) => a.agent_number === agent.agent_number ? { ...a, queues: next } : a));
    setSaving(agent.agent_number);
    try {
      await api.post("agent_queue_pref.php?action=set", { agent_number: agent.agent_number, queues: next });
      setMsg({ ok: true, text: `Guardado agente ${agent.agent_number}` });
      setTimeout(() => setMsg(null), 2000);
    } catch (err) {
      setMsg({ ok: false, text: err.message });
      load(); // rollback
    } finally { setSaving(null); }
  };

  if (loading) return <div style={{ padding: 20, color: HORIZON.muted }}>Cargando agentes y prefs…</div>;
  if (queues.length === 0) return <div style={{ padding: 20, color: HORIZON.muted }}>Cargando colas del PBX…</div>;

  return (
    <div>
      {msg && (
        <div style={{ padding: 10, marginBottom: 12, borderRadius: 6, background: msg.ok ? HORIZON.greenSoft : "#FEE2E2", color: msg.ok ? HORIZON.greenDark : HORIZON.danger, fontSize: 13 }}>
          {msg.text}
        </div>
      )}
      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "auto" }}>
        <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
          <thead>
            <tr style={{ background: "#F8FAFC" }}>
              <th style={{ textAlign: "left", padding: "10px 12px", fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, position: "sticky", left: 0, background: "#F8FAFC" }}>Agente</th>
              {queues.map((q) => (
                <th key={q} style={{ padding: "10px 8px", fontSize: 10, color: HORIZON.muted, fontFamily: "ui-monospace", textAlign: "center", minWidth: 60 }}>{q}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {agents.map((a, i) => (
              <tr key={a.agent_number} style={{ borderTop: i ? "1px solid #F1F5F9" : "none", opacity: saving === a.agent_number ? 0.55 : 1 }}>
                <td style={{ padding: "8px 12px", position: "sticky", left: 0, background: "#fff", borderRight: "1px solid #F1F5F9" }}>
                  <div style={{ fontFamily: "ui-monospace", fontWeight: 700, color: HORIZON.green }}>{a.agent_number}</div>
                  <div style={{ fontSize: 11, color: HORIZON.muted }}>{a.name || "-"}</div>
                </td>
                {queues.map((q) => {
                  const on = (a.queues || []).includes(q);
                  return (
                    <td key={q} style={{ padding: "8px 4px", textAlign: "center" }}>
                      <button onClick={() => toggle(a, q)} disabled={saving === a.agent_number}
                        title={on ? `Quitar cola ${q}` : `Agregar cola ${q}`}
                        style={{
                          width: 24, height: 24, borderRadius: 4, cursor: saving === a.agent_number ? "wait" : "pointer",
                          border: `1.5px solid ${on ? HORIZON.green : "#D1D5DB"}`,
                          background: on ? HORIZON.green : "#fff",
                          color: on ? "#fff" : "transparent",
                          fontWeight: 700, fontSize: 14, transition: "all 0.15s"
                        }}>{on ? "✓" : ""}</button>
                    </td>
                  );
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <p style={{ marginTop: 10, fontSize: 11, color: HORIZON.muted }}>
        Cambio guardado automáticamente al hacer clic. Se aplica al próximo login del agente.
      </p>
    </div>
  );
}
