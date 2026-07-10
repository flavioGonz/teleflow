// src/views/Recordings.jsx — F2.7: grabaciones (más completo que RecordingsRow del Dashboard).
import React, { useEffect, useMemo, useState } from "react";
import { usePbxData } from "../stores/pbxData.js";
import { HORIZON } from "@lib/theme.js";

const fmtDate = (d) => (!d ? "—" : new Date(String(d).replace(" ", "T")).toLocaleString("es-UY", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit", timeZone: "America/Montevideo" }));
const fmtSec  = (s) => (!s ? "—" : `${Math.floor(s / 60)}m ${String(s % 60).padStart(2, "0")}s`);

export default function Recordings() {
  const fetch = usePbxData((s) => s.fetch);
  const data = usePbxData((s) => s.data);
  const [q, setQ] = useState("");
  const [expanded, setExpanded] = useState(null);
  useEffect(() => { fetch(); }, [fetch]);

  const recs = useMemo(() => {
    const all = data?.recordings || [];
    const needle = q.trim().toLowerCase();
    if (!needle) return all;
    return all.filter((r) => (r.src || "").includes(needle) || (r.dst || "").includes(needle) || (r.clid || "").toLowerCase().includes(needle));
  }, [data, q]);

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>Grabaciones</h1>
        <div style={{ display: "flex", gap: 12, alignItems: "center" }}>
          <span style={{ fontSize: 12, color: HORIZON.muted }}>{recs.length} de {(data?.recordings || []).length}</span>
          <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar por origen/destino…" style={{ padding: "8px 12px", border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, minWidth: 260 }} />
        </div>
      </div>

      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "hidden" }}>
        {recs.length === 0
          ? <div style={{ padding: 30, textAlign: "center", color: HORIZON.muted }}>Sin grabaciones.</div>
          : <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
              <thead>
                <tr style={{ background: "#F8FAFC" }}>
                  {["Fecha", "Origen", "Destino", "Duración", ""].map((h, i) => (
                    <th key={i} style={{ textAlign: "left", padding: "10px 12px", fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {recs.map((r, i) => (
                  <React.Fragment key={i}>
                    <tr style={{ borderTop: i ? "1px solid #F1F5F9" : "none" }}>
                      <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", fontSize: 11 }}>{fmtDate(r.calldate)}</td>
                      <td style={{ padding: "9px 12px", fontFamily: "ui-monospace" }}>{r.src || "—"}</td>
                      <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", fontWeight: 600 }}>{r.dst || "—"}</td>
                      <td style={{ padding: "9px 12px", fontFamily: "ui-monospace", color: HORIZON.muted }}>{fmtSec(r.billsec)}</td>
                      <td style={{ padding: "9px 12px", textAlign: "right" }}>
                        <button onClick={() => setExpanded(expanded === i ? null : i)} style={{ padding: "3px 10px", border: "1px solid #E5E7EB", background: "#fff", borderRadius: 4, cursor: "pointer", fontSize: 11, color: HORIZON.green }}>
                          {expanded === i ? "▲" : "▶"} audio
                        </button>
                      </td>
                    </tr>
                    {expanded === i && (
                      <tr style={{ background: "#F8FAFC" }}>
                        <td colSpan="5" style={{ padding: "10px 14px" }}>
                          <audio controls style={{ width: "100%", height: 32 }} src={`api/rec_stream.php?file=${encodeURIComponent(r.recordingfile || "")}`} />
                        </td>
                      </tr>
                    )}
                  </React.Fragment>
                ))}
              </tbody>
            </table>
        }
      </div>
    </div>
  );
}
