// src/components/MyQueues.jsx — Mis colas: miembros + timeout + failover.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";

export default function MyQueues() {
  const [data, setData] = useState(null);
  const liveQueues = useLive((s) => s.queues);

  useEffect(() => {
    api.action("agent.php", "agent_data").then((j) => setData(j)).catch(() => {});
  }, []);

  const queues = data?.pbx?.queues || [];
  if (queues.length === 0) return <div style={{ color: HORIZON.muted, padding: 12 }}>No estás en ninguna cola.</div>;

  return (
    <div style={{ display: "grid", gap: 10 }}>
      {queues.map((q) => {
        const live = liveQueues[q.id] || {};
        const members = q.members || live.members || [];
        return (
          <div key={q.id} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 14 }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <div>
                <div style={{ fontSize: 13, fontWeight: 700, color: HORIZON.neutralInk }}>{q.name || q.id}</div>
                <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 2 }}>
                  {q.strategy || "?"} · timeout {q.timeout || "-"}s
                  {q.failover && <> · failover <code>{q.failover}</code></>}
                </div>
              </div>
              <div style={{ textAlign: "right", fontFamily: "ui-monospace" }}>
                <div style={{ fontSize: 20, fontWeight: 800, color: HORIZON.green }}>{members.length}</div>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>miembros</div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
