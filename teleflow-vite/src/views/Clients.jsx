// src/views/Clients.jsx — F2.6: operaciones / clientes.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

function Sidebar({ list, selected, onSelect }) {
  return (
    <aside style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, maxHeight: "70vh", overflow: "auto" }}>
      {list.length === 0
        ? <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted, fontSize: 13 }}>Sin clientes.</div>
        : list.map((c) => (
            <div key={c.id} onClick={() => onSelect(c)} style={{
              padding: "10px 14px", borderBottom: "1px solid #F1F5F9", cursor: "pointer",
              background: selected?.id === c.id ? HORIZON.greenSoft : "transparent",
              borderLeft: `3px solid ${selected?.id === c.id ? HORIZON.green : "transparent"}`
            }}>
              <div style={{ fontSize: 14, fontWeight: 600, color: HORIZON.neutralInk }}>{c.name || `#${c.id}`}</div>
              <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 2 }}>
                {c.ext_count ?? 0} ext · {c.speaker_count ?? 0} bocinas · {c.nvr_count ?? 0} NVR
              </div>
            </div>
          ))}
    </aside>
  );
}

function Detail({ client }) {
  const [tab, setTab] = useState("extensions");
  const [data, setData] = useState(null);
  useEffect(() => {
    if (!client) return;
    let alive = true;
    api.get("clients.php", { action: "get", id: client.id })
      .then((j) => { if (alive) setData(j); })
      .catch(() => {});
    return () => { alive = false; };
  }, [client]);

  if (!client) return <div style={{ padding: 40, textAlign: "center", color: HORIZON.muted }}>Elegí un cliente a la izquierda.</div>;

  const items = data?.[tab] || [];

  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 20, maxHeight: "70vh", overflow: "auto" }}>
      <div style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: 16 }}>
        <div style={{
          width: 48, height: 48, borderRadius: 12, background: HORIZON.greenSoft,
          display: "grid", placeItems: "center", color: HORIZON.green, fontWeight: 700, fontSize: 20
        }}>
          {(client.name || "?").charAt(0).toUpperCase()}
        </div>
        <div>
          <h2 style={{ margin: 0, fontSize: 20, color: HORIZON.neutralInk }}>{client.name || "-"}</h2>
          <div style={{ fontSize: 12, color: HORIZON.muted, marginTop: 2 }}>Cliente #{client.id}</div>
        </div>
      </div>

      <div style={{ display: "flex", gap: 4, borderBottom: "1px solid #E5E7EB", marginBottom: 12 }}>
        {[
          { id: "extensions", label: "Extensiones" },
          { id: "speakers",   label: "Parlantes"   },
          { id: "queues",     label: "Colas"       },
          { id: "nvrs",       label: "NVRs"        },
        ].map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)} style={{
            padding: "8px 14px", border: 0, background: "transparent", cursor: "pointer",
            borderBottom: `2px solid ${tab === t.id ? HORIZON.green : "transparent"}`,
            color: tab === t.id ? HORIZON.neutralInk : HORIZON.muted,
            fontWeight: tab === t.id ? 700 : 500, fontSize: 13
          }}>{t.label}</button>
        ))}
      </div>

      {items.length === 0
        ? <div style={{ padding: 30, textAlign: "center", color: HORIZON.muted, fontSize: 13 }}>Sin {tab} asociadas.</div>
        : <div style={{ display: "grid", gap: 6 }}>
            {items.map((it, i) => (
              <div key={i} style={{ padding: "10px 12px", background: "#F8FAFC", borderRadius: 6, fontSize: 13 }}>
                <span style={{ fontFamily: "ui-monospace", fontWeight: 600, color: HORIZON.green }}>{it.ext || it.id || "?"}</span>
                {" · "}{it.name || it.label || it.hostname || it.host || "-"}
              </div>
            ))}
          </div>
      }
    </div>
  );
}

export default function Clients() {
  const [list, setList] = useState([]);
  const [selected, setSelected] = useState(null);
  const [q, setQ] = useState("");
  useEffect(() => {
    api.get("clients.php", { action: "list" }).then((j) => setList(j?.rows || j?.clients || j || [])).catch(() => {});
  }, []);
  const filtered = q ? list.filter((c) => (c.name || "").toLowerCase().includes(q.toLowerCase())) : list;
  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Operaciones · Clientes</h1>
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar cliente…" style={{ padding: "8px 12px", border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, minWidth: 220 }} />
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "300px 1fr", gap: 16 }}>
        <Sidebar list={filtered} selected={selected} onSelect={setSelected} />
        <Detail client={selected} />
      </div>
    </div>
  );
}
