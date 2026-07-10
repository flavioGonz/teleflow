// src/views/config/SSL.jsx — F2.8.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

export default function SSL() {
  const [certs, setCerts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [renewing, setRenewing] = useState(false);
  const [msg, setMsg] = useState(null);

  const load = () => {
    setLoading(true);
    api.get("letsencrypt.php", { action: "status" })
      .then((j) => setCerts(j?.certs || []))
      .catch(() => setCerts([]))
      .finally(() => setLoading(false));
  };
  useEffect(() => { load(); }, []);

  const renew = async () => {
    if (!confirm("Renovar certificados con --force-renewal?")) return;
    setRenewing(true); setMsg(null);
    try {
      const j = await api.get("letsencrypt.php", { action: "renew" });
      setMsg({ ok: true, text: j?.message || "Renovado" });
      load();
    } catch (err) {
      setMsg({ ok: false, text: err.message || String(err) });
    } finally { setRenewing(false); }
  };

  return (
    <div>
      {loading && <div style={{ padding: 20, color: HORIZON.muted }}>Leyendo certificados…</div>}
      {msg && (
        <div style={{ padding: 10, marginBottom: 12, borderRadius: 6, background: msg.ok ? HORIZON.greenSoft : "#FEE2E2", color: msg.ok ? HORIZON.greenDark : HORIZON.danger, fontSize: 13 }}>
          {msg.text}
        </div>
      )}
      {certs.map((c) => (
        <div key={c.name} style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 16, marginBottom: 10 }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "start" }}>
            <div>
              <div style={{ fontSize: 15, fontWeight: 700, color: HORIZON.neutralInk }}>{c.name}</div>
              <div style={{ fontSize: 12, color: HORIZON.muted, marginTop: 4 }}>
                Dominios: <code>{(c.domains || []).join(", ")}</code>
              </div>
              <div style={{ fontSize: 12, color: HORIZON.muted, marginTop: 2 }}>
                Expira: <strong style={{ color: c.days_left < 30 ? HORIZON.danger : HORIZON.green }}>{c.expiry || "?"}</strong>
                {c.days_left !== undefined && ` (${c.days_left} días)`}
              </div>
            </div>
            <button onClick={renew} disabled={renewing} style={{
              padding: "8px 16px", background: HORIZON.green, color: "#fff", border: 0, borderRadius: 6,
              cursor: renewing ? "wait" : "pointer", fontSize: 13, fontWeight: 600,
              opacity: renewing ? 0.7 : 1
            }}>{renewing ? "Renovando…" : "Renovar ahora"}</button>
          </div>
        </div>
      ))}
      {!loading && certs.length === 0 && <div style={{ padding: 30, textAlign: "center", background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, color: HORIZON.muted }}>Sin certificados encontrados.</div>}
    </div>
  );
}
