// src/components/RecordingsRow.jsx — últimas grabaciones con audio inline.
import React, { useEffect, useState } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { HORIZON } from "@lib/theme.js";

export default function RecordingsRow({ limit = 8 }) {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const [expanded, setExpanded] = useState(null);
  useEffect(() => { fetch(); }, [fetch]);
  const recs = (data?.recordings || []).slice(0, limit);
  if (recs.length === 0) return <div style={{ color: HORIZON.muted, padding: 12 }}>Sin grabaciones recientes.</div>;
  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
      {recs.map((r, i) => (
        <div key={i} style={{ borderTop: i ? "1px solid #F1F5F9" : "none" }}>
          <div onClick={() => setExpanded(expanded === i ? null : i)} style={{
            padding: "10px 14px", display: "grid", gridTemplateColumns: "1fr auto auto", gap: 12,
            cursor: "pointer", alignItems: "center", fontSize: 13
          }}>
            <div>
              <div style={{ fontFamily: "ui-monospace", fontSize: 11, color: HORIZON.muted }}>{(r.calldate || "").replace("T", " ").slice(0, 19)}</div>
              <div>{r.src || "?"} → <strong>{r.dst || "?"}</strong></div>
            </div>
            <div style={{ fontFamily: "ui-monospace", color: HORIZON.muted, fontSize: 12 }}>
              {r.billsec > 0 ? `${Math.floor(r.billsec / 60)}:${String(r.billsec % 60).padStart(2, "0")}` : "0:00"}
            </div>
            <span className="material-icons-round" style={{ fontSize: 20, color: HORIZON.green }}>
              {expanded === i ? "expand_less" : "play_circle"}
            </span>
          </div>
          {expanded === i && r.recordingfile && (
            <div style={{ padding: "0 14px 14px" }}>
              <audio controls src={`api/rec_stream.php?file=${encodeURIComponent(r.recordingfile)}`} style={{ width: "100%" }} />
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
