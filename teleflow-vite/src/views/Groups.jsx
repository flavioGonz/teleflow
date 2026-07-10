// src/views/Groups.jsx — F2.7: grupos de timbrado.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

export default function Groups() {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    api.get("index.php", { action: "get_ring_groups" })
      .then((j) => setGroups(j?.groups || j?.rows || j || []))
      .catch(() => setGroups([]))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div>
      <h1 style={{ margin: "0 0 16px", fontSize: 22, color: HORIZON.neutralInk }}>Grupos de timbrado</h1>
      {loading && <div style={{ padding: 20, color: HORIZON.muted }}>Cargando…</div>}
      {!loading && groups.length === 0 && <div style={{ padding: 30, textAlign: "center", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, color: HORIZON.muted }}>Sin grupos configurados.</div>}
      <div style={{ display: "grid", gap: 10 }}>
        {groups.map((g) => (
          <div key={g.grpnum || g.id} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 14 }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <div>
                <div style={{ fontSize: 14, fontWeight: 700, color: HORIZON.neutralInk }}>{g.description || g.name || `Grupo ${g.grpnum || g.id}`}</div>
                <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 2, fontFamily: "ui-monospace" }}>
                  {g.grpnum || g.id} · estrategia {g.strategy || "ringall"}
                </div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>Miembros</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: HORIZON.green, fontFamily: "ui-monospace" }}>
                  {typeof g.grplist === "string" ? g.grplist.split(/[-,]/).filter(Boolean).length : (g.members?.length ?? 0)}
                </div>
              </div>
            </div>
            {g.grplist && (
              <div style={{ marginTop: 8, display: "flex", flexWrap: "wrap", gap: 6 }}>
                {String(g.grplist).split(/[-,]/).filter(Boolean).map((m, i) => (
                  <span key={i} style={{ padding: "3px 10px", background: "#F8FAFC", border: "1px solid #E5E7EB", borderRadius: 999, fontSize: 12, fontFamily: "ui-monospace" }}>{m}</span>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
