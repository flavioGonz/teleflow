// src/components/QueuesRow.jsx — fila de colas con métricas.
import React, { useEffect } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";

export default function QueuesRow() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const liveQueues = useLive((s) => s.queues);
  useEffect(() => { fetch(); }, [fetch]);
  const queues = data?.queues || [];
  if (queues.length === 0) return null;
  return (
    <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill,minmax(220px,1fr))", gap: 10 }}>
      {queues.map((q) => {
        const live = liveQueues[q.extension || q.id] || {};
        const waiting = live.waiting ?? q.calls_waiting ?? 0;
        const answered = live.answered ?? q.answered ?? 0;
        return (
          <div key={q.extension || q.id} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 14 }}>
            <div style={{ fontSize: 12, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>
              {q.extension || q.id}
            </div>
            <div style={{ fontSize: 15, fontWeight: 700, color: HORIZON.neutralInk, marginTop: 2 }}>
              {q.descr || q.name || "-"}
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", marginTop: 10, fontFamily: "ui-monospace" }}>
              <div>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>en espera</div>
                <div style={{ fontSize: 22, fontWeight: 800, color: waiting > 0 ? HORIZON.amber : HORIZON.muted }}>{waiting}</div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: 10, color: HORIZON.muted }}>atendidas hoy</div>
                <div style={{ fontSize: 22, fontWeight: 800, color: HORIZON.green }}>{answered}</div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
