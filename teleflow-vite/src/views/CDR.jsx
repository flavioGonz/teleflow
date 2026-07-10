// src/views/CDR.jsx — F2.6: historial CDR con filtros + audio inline.
import React, { useCallback, useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

const today = () => new Date().toISOString().slice(0, 10);
const daysAgo = (n) => new Date(Date.now() - n * 86400000).toISOString().slice(0, 10);
const fmtSec = (s) => (!s ? "—" : `${Math.floor(s / 60)}m ${String(s % 60).padStart(2, "0")}s`);
const fmtDate = (d) => (!d ? "—" : new Date(d).toLocaleString("es-UY", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit", timeZone: "America/Montevideo" }));

const DISP_COLOR = {
  ANSWERED:   HORIZON.green,
  NO_ANSWER:  HORIZON.amber,
  BUSY:       HORIZON.amber,
  FAILED:     HORIZON.danger,
  CANCEL:     HORIZON.muted,
};

export default function CDR() {
  const [filters, setFilters] = useState({ from: daysAgo(7), to: today(), src: "", disp: "" });
  const [rows, setRows] = useState([]);
  const [stats, setStats] = useState({});
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [expanded, setExpanded] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const j = await api.get("index.php", { action: "get_cdr", ...filters, limit: 500 });
      if (j?.success) { setRows(j.rows || []); setStats(j.stats || {}); setTotal(j.total || 0); }
    } catch { /* ignore */ }
    setLoading(false);
  }, [filters]);

  useEffect(() => { load(); }, [load]);

  const upd = (k, v) => setFilters({ ...filters, [k]: v });
  const inp = { padding: 8, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13 };

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 16 }}>
        <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>CDR — Historial de llamadas</h1>
        <div style={{ fontSize: 12, color: HORIZON.muted }}>{total.toLocaleString()} llamadas · {rows.length} mostradas</div>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(130px,1fr))", gap: 8, marginBottom: 12, background: "#F8FAFC", padding: 12, borderRadius: 8 }}>
        <label><div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Desde</div><input type="date" value={filters.from} onChange={(e) => upd("from", e.target.value)} style={{ ...inp, width: "100%" }} /></label>
        <label><div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Hasta</div><input type="date" value={filters.to} onChange={(e) => upd("to", e.target.value)} style={{ ...inp, width: "100%" }} /></label>
        <label><div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Origen</div><input value={filters.src} onChange={(e) => upd("src", e.target.value)} placeholder="ext, número…" style={{ ...inp, width: "100%" }} /></label>
        <label><div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>Estado</div>
          <select value={filters.disp} onChange={(e) => upd("disp", e.target.value)} style={{ ...inp, width: "100%" }}>
            <option value="">Todos</option>
            <option value="ANSWERED">Atendidas</option>
            <option value="NO_ANSWER">Sin atender</option>
            <option value="BUSY">Ocupado</option>
            <option value="FAILED">Fallidas</option>
          </select>
        </label>
      </div>

      {Object.keys(stats).length > 0 && (
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(150px,1fr))", gap: 8, marginBottom: 12 }}>
          {Object.entries(stats).map(([k, v]) => (
            <div key={k} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 8, padding: 10 }}>
              <div style={{ fontSize: 10, color: HORIZON.muted, textTransform: "uppercase" }}>{k}</div>
              <div style={{ fontSize: 18, fontWeight: 700, fontFamily: "ui-monospace", color: HORIZON.neutralInk }}>{v}</div>
            </div>
          ))}
        </div>
      )}

      <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, overflow: "auto" }}>
        {loading && <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>Cargando…</div>}
        {!loading && rows.length === 0 && <div style={{ padding: 20, textAlign: "center", color: HORIZON.muted }}>Sin llamadas para los filtros.</div>}
        {!loading && rows.length > 0 && (
          <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 12 }}>
            <thead>
              <tr style={{ background: "#F8FAFC" }}>
                {["Fecha", "Origen", "Destino", "Duración", "Talk", "Estado", ""].map((h, i) => (
                  <th key={i} style={{ textAlign: "left", padding: "9px 10px", fontSize: 10, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1 }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {rows.map((r, i) => (
                <React.Fragment key={i}>
                  <tr style={{ borderTop: "1px solid #F1F5F9" }}>
                    <td style={{ padding: "8px 10px", fontFamily: "ui-monospace", fontSize: 11 }}>{fmtDate(r.calldate)}</td>
                    <td style={{ padding: "8px 10px", fontFamily: "ui-monospace" }}>{r.src || "—"}</td>
                    <td style={{ padding: "8px 10px", fontFamily: "ui-monospace", fontWeight: 600 }}>{r.dst || "—"}</td>
                    <td style={{ padding: "8px 10px", fontFamily: "ui-monospace", color: HORIZON.muted }}>{fmtSec(r.duration)}</td>
                    <td style={{ padding: "8px 10px", fontFamily: "ui-monospace", color: HORIZON.green }}>{fmtSec(r.billsec)}</td>
                    <td style={{ padding: "8px 10px", color: DISP_COLOR[r.disposition] || HORIZON.muted, fontWeight: 600 }}>{r.disposition || "—"}</td>
                    <td style={{ padding: "8px 10px", textAlign: "right" }}>
                      {r.recordingfile && <button onClick={() => setExpanded(expanded === i ? null : i)} style={{ padding: "3px 8px", border: "1px solid #E5E7EB", background: "#fff", borderRadius: 4, cursor: "pointer", fontSize: 11, color: HORIZON.green }}>
                        {expanded === i ? "▲" : "▶"} audio
                      </button>}
                    </td>
                  </tr>
                  {expanded === i && r.recordingfile && (
                    <tr style={{ background: "#F8FAFC" }}>
                      <td colSpan="7" style={{ padding: "10px 14px" }}>
                        <audio controls style={{ width: "100%", height: 30 }} src={`api/rec_stream.php?file=${encodeURIComponent(r.recordingfile)}`} />
                      </td>
                    </tr>
                  )}
                </React.Fragment>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
