// src/views/Queues.jsx — F2.4b: colas con estrategia + timeout + failover + members.
import React, { useEffect, useMemo, useState } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";

export default function Queues() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const liveQueues = useLive((s) => s.queues);
  const [expanded, setExpanded] = useState(null);
  useEffect(() => { fetch(); }, [fetch]);

  const enriched = useMemo(() => {
    const queues = data?.queues || [];
    return queues.map((q) => {
      const live = liveQueues[q.extension || q.id] || {};
      return {
        id: q.extension || q.id,
        name: q.descr || q.name,
        strategy: q.strategy || live.strategy,
        timeout: q.timeout,
        failover: q.dest || q.failover,
        members: q.members || live.members || [],
        waiting: live.waiting ?? q.calls_waiting ?? 0,
      };
    });
  }, [data, liveQueues]);

  return (
    <div>
      <h1 style={{ margin: "0 0 16px", fontSize: 22, color: HORIZON.neutralInk }}>Colas</h1>
      <div style={{ display: "grid", gap: 10 }}>
        {enriched.map((q) => (
          <div key={q.id} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
            <div onClick={() => setExpanded(expanded === q.id ? null : q.id)} style={{
              padding: "12px 16px", display: "grid", gridTemplateColumns: "auto 1fr auto auto auto", gap: 16, alignItems: "center", cursor: "pointer"
            }}>
              <div style={{ fontFamily: "ui-monospace", fontWeight: 800, color: HORIZON.green, fontSize: 16 }}>{q.id}</div>
              <div>
                <div style={{ fontSize: 14, fontWeight: 600, color: HORIZON.neutralInk }}>{q.name}</div>
                <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 2 }}>
                  {q.strategy || "?"} · timeout {q.timeout || "-"}s {q.failover && <>· failover <code>{q.failover}</code></>}
                </div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>En espera</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: q.waiting > 0 ? HORIZON.amber : HORIZON.muted, fontFamily: "ui-monospace" }}>{q.waiting}</div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>Miembros</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: HORIZON.green, fontFamily: "ui-monospace" }}>{q.members.length}</div>
              </div>
              <span className="material-icons-round" style={{ color: HORIZON.muted, fontSize: 22 }}>{expanded === q.id ? "expand_less" : "expand_more"}</span>
            </div>
            {expanded === q.id && q.members.length > 0 && (
              <div style={{ borderTop: "1px solid #F1F5F9", padding: 12, background: "#F8FAFC" }}>
                <div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", marginBottom: 8 }}>Miembros de la cola</div>
                <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                  {q.members.map((m, i) => (
                    <span key={i} style={{ padding: "4px 10px", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 999, fontSize: 12, fontFamily: "ui-monospace" }}>
                      {typeof m === "string" ? m : (m.location || m.interface || "?")}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
