// src/components/IncomingCallCard.jsx — card modal cuando llega INVITE.
import React from "react";
import { useSoftphone } from "../stores/softphone.js";
import { HORIZON } from "@lib/theme.js";

export default function IncomingCallCard() {
  const status = useSoftphone((s) => s.status);
  const remote = useSoftphone((s) => s.remoteName);
  const answer = useSoftphone((s) => s.answer);
  const decline = useSoftphone((s) => s.decline);
  if (status !== "incoming") return null;
  return (
    <div style={{
      position: "fixed", inset: 0, display: "grid", placeItems: "center",
      background: "rgba(15,23,42,0.55)", backdropFilter: "blur(6px)", zIndex: 9999
    }}>
      <div style={{
        background: "#fff", borderRadius: 16, padding: 30, minWidth: 320, textAlign: "center",
        boxShadow: "0 20px 40px rgba(0,0,0,0.30)", border: `2px solid ${HORIZON.green}`
      }}>
        <div style={{
          width: 70, height: 70, margin: "0 auto 14px", borderRadius: "50%",
          background: HORIZON.greenSoft, display: "grid", placeItems: "center",
          animation: "pulse 1s infinite"
        }}>
          <span className="material-icons-round" style={{ fontSize: 34, color: HORIZON.green }}>call</span>
        </div>
        <style>{"@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}"}</style>
        <div style={{ fontSize: 12, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1.5 }}>Llamada entrante</div>
        <div style={{ fontSize: 24, fontWeight: 800, color: HORIZON.neutralInk, margin: "6px 0 20px" }}>{remote || "?"}</div>
        <div style={{ display: "flex", gap: 12, justifyContent: "center" }}>
          <button onClick={decline} style={{
            padding: "12px 24px", border: 0, borderRadius: 999, cursor: "pointer",
            background: HORIZON.danger, color: "#fff", fontWeight: 700, fontSize: 14
          }}>Rechazar</button>
          <button onClick={answer} style={{
            padding: "12px 24px", border: 0, borderRadius: 999, cursor: "pointer",
            background: HORIZON.green, color: "#fff", fontWeight: 700, fontSize: 14
          }}>Atender</button>
        </div>
      </div>
    </div>
  );
}
