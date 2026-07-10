// src/views/config/Agents.jsx — F2.8.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

export default function Agents() {
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);

  const load = () => {
    setLoading(true);
    api.get("agents_crud.php", { action: "list" })
      .then((j) => setAgents(j?.agents || j?.rows || []))
      .catch(() => setAgents([]))
      .finally(() => setLoading(false));
  };
  useEffect(() => { load(); }, []);

  const save = async (a) => {
    try {
      await api.post("agents_crud.php?action=update", a);
      setEditing(null);
      load();
    } catch (err) {
      alert("Error guardando: " + (err.message || err));
    }
  };
  const del = async (agent_number) => {
    if (!confirm(`¿Eliminar agente ${agent_number}?`)) return;
    try { await api.post("agents_crud.php?action=delete", { agent_number }); load(); }
    catch (err) { alert("Error: " + err.message); }
  };

  if (loading) return <div style={{ padding: 20, color: HORIZON.muted }}>Cargando…</div>;

  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
      <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
        <thead><tr style={{ background: "#F8FAFC" }}>
          {["#", "Nombre", "Password", "WebRTC ext", "Acciones"].map((h, i) => (
            <th key={i} style={{ textAlign: "left", padding: "10px 12px", fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>{h}</th>
          ))}
        </tr></thead>
        <tbody>
          {agents.map((a) => (
            <tr key={a.agent_number} style={{ borderTop: "1px solid #F1F5F9" }}>
              <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", fontWeight: 700 }}>{a.agent_number}</td>
              <td style={{ padding: "9px 12px" }}>
                {editing?.agent_number === a.agent_number
                  ? <input value={editing.name || ""} onChange={(e) => setEditing({ ...editing, name: e.target.value })} style={{ padding: 6, border: "1px solid #D1D5DB", borderRadius: 4, fontSize: 12 }} />
                  : a.name || "-"}
              </td>
              <td style={{ padding: "9px 12px" }}>
                {editing?.agent_number === a.agent_number
                  ? <input type="password" placeholder="(sin cambios)" value={editing.password || ""} onChange={(e) => setEditing({ ...editing, password: e.target.value })} style={{ padding: 6, border: "1px solid #D1D5DB", borderRadius: 4, fontSize: 12 }} />
                  : <span style={{ fontFamily: "ui-monospace", color: HORIZON.muted }}>••••</span>}
              </td>
              <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", color: HORIZON.green }}>{a.webrtc_ext || "—"}</td>
              <td style={{ padding: "9px 12px" }}>
                {editing?.agent_number === a.agent_number
                  ? <>
                      <button onClick={() => save(editing)} style={{ padding: "4px 10px", background: HORIZON.green, color: "#fff", border: 0, borderRadius: 4, cursor: "pointer", fontSize: 11, marginRight: 6 }}>Guardar</button>
                      <button onClick={() => setEditing(null)} style={{ padding: "4px 10px", background: "#fff", color: HORIZON.muted, border: "1px solid #E5E7EB", borderRadius: 4, cursor: "pointer", fontSize: 11 }}>Cancelar</button>
                    </>
                  : <>
                      <button onClick={() => setEditing({ ...a, password: "" })} style={{ padding: "4px 10px", background: "#fff", color: HORIZON.green, border: `1px solid ${HORIZON.green}`, borderRadius: 4, cursor: "pointer", fontSize: 11, marginRight: 6 }}>Editar</button>
                      <button onClick={() => del(a.agent_number)} style={{ padding: "4px 10px", background: "#fff", color: HORIZON.danger, border: `1px solid ${HORIZON.danger}`, borderRadius: 4, cursor: "pointer", fontSize: 11 }}>Eliminar</button>
                    </>}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
