// src/views/Radar.jsx — F2.7: dashboard alternativo compacto para monitoreo.
import React, { useEffect } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { useLive } from "../stores/live.js";
import { HORIZON } from "@lib/theme.js";
import { useClock } from "@lib/clock.js";

function Big({ value, label, color }) {
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 12, padding: "18px 20px" }}>
      <div style={{ fontSize: 40, fontWeight: 800, color: color || HORIZON.neutralInk, fontFamily: "ui-monospace", lineHeight: 1 }}>{value}</div>
      <div style={{ fontSize: 11, color: HORIZON.muted, marginTop: 6, textTransform: "uppercase", letterSpacing: 1 }}>{label}</div>
    </div>
  );
}

export default function Radar() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const calls = useLive((s) => s.activeCalls);
  const queues = useLive((s) => s.queues);
  const connected = useLive((s) => s.connected);
  const clock = useClock();

  useEffect(() => { fetch(); const t = setInterval(() => fetch(true), 15000); return () => clearInterval(t); }, [fetch]);

  const totalWaiting = Object.values(queues).reduce((s, q) => s + (q.waiting || 0), 0);
  const inCall = calls.filter((c) => /Up/.test(c.state || "")).length;
  const ringing = calls.filter((c) => /Ring/.test(c.state || "")).length;
  const nExts = data?.extensions?.length ?? 0;
  const nOnline = data?.extensions?.filter?.((e) => /^(Registered|OK)/.test(e.status || "")).length ?? 0;

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 20 }}>
        <h1 style={{ margin: 0, fontSize: 24, color: HORIZON.neutralInk }}>Radar</h1>
        <div style={{ fontSize: 14, color: HORIZON.muted, fontFamily: "ui-monospace" }}>{clock.time}</div>
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(180px,1fr))", gap: 12 }}>
        <Big value={calls.length} label="Llamadas activas" color={calls.length > 0 ? HORIZON.green : undefined} />
        <Big value={inCall} label="En conversación" color={inCall > 0 ? HORIZON.green : undefined} />
        <Big value={ringing} label="Timbrando" color={ringing > 0 ? HORIZON.amber : undefined} />
        <Big value={totalWaiting} label="En espera" color={totalWaiting > 0 ? HORIZON.amber : undefined} />
        <Big value={`${nOnline}/${nExts}`} label="Ext online" />
        <Big value={connected ? "OK" : "off"} label="Hub" color={connected ? HORIZON.green : HORIZON.danger} />
      </div>
    </div>
  );
}
