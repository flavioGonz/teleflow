// src/views/config/PBX.jsx — F2.8: configuración de conexión al PBX.
import React, { useEffect, useState } from "react";
import { api } from "@lib/api.js";
import { HORIZON } from "@lib/theme.js";

export default function PBX() {
  const [cfg, setCfg] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [msg, setMsg] = useState(null);

  useEffect(() => {
    api.get("pbx_config.php", { action: "list" })
      .then((j) => setCfg(j?.config || {}))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const set = (k, v) => setCfg({ ...cfg, [k]: v });
  const save = async () => {
    setSaving(true); setMsg(null);
    try { await api.post("pbx_config.php?action=save", cfg); setMsg({ ok: true, text: "Guardado." }); }
    catch (err) { setMsg({ ok: false, text: err.message }); }
    setSaving(false);
  };
  const testConn = async () => {
    setTesting(true); setMsg(null);
    try { const j = await api.get("pbx_config.php", { action: "test" }); setMsg({ ok: !!j?.success, text: j?.message || (j?.success ? "OK" : "Falló") }); }
    catch (err) { setMsg({ ok: false, text: err.message }); }
    setTesting(false);
  };

  const inp = { padding: 10, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 13, width: "100%" };
  const lbl = { fontSize: 11, color: HORIZON.muted, textTransform: "uppercase", letterSpacing: 1, marginBottom: 4, display: "block" };

  if (loading) return <div style={{ padding: 20, color: HORIZON.muted }}>Cargando…</div>;

  return (
    <div style={{ background: "#fff", border: "1px solid #E5E7EB", borderRadius: 10, padding: 20 }}>
      {msg && <div style={{ padding: 10, marginBottom: 12, borderRadius: 6, background: msg.ok ? HORIZON.greenSoft : "#FEE2E2", color: msg.ok ? HORIZON.greenDark : HORIZON.danger, fontSize: 13 }}>{msg.text}</div>}
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
        <div><span style={lbl}>Host AMI</span><input value={cfg.ami_host || ""} onChange={(e) => set("ami_host", e.target.value)} style={inp} /></div>
        <div><span style={lbl}>Puerto AMI</span><input value={cfg.ami_port || ""} onChange={(e) => set("ami_port", e.target.value)} style={inp} /></div>
        <div><span style={lbl}>Usuario AMI</span><input value={cfg.ami_user || ""} onChange={(e) => set("ami_user", e.target.value)} style={inp} /></div>
        <div><span style={lbl}>Password AMI</span><input type="password" value={cfg.ami_pass || ""} onChange={(e) => set("ami_pass", e.target.value)} style={inp} /></div>
        <div><span style={lbl}>Host DB PBX</span><input value={cfg.pbx_db_host || ""} onChange={(e) => set("pbx_db_host", e.target.value)} style={inp} /></div>
        <div><span style={lbl}>Usuario DB</span><input value={cfg.pbx_db_user || ""} onChange={(e) => set("pbx_db_user", e.target.value)} style={inp} /></div>
      </div>
      <div style={{ display: "flex", gap: 10, marginTop: 16 }}>
        <button onClick={save} disabled={saving} style={{ padding: "8px 18px", background: HORIZON.green, color: "#fff", border: 0, borderRadius: 6, cursor: saving ? "wait" : "pointer", fontWeight: 600, fontSize: 13 }}>{saving ? "Guardando…" : "Guardar"}</button>
        <button onClick={testConn} disabled={testing} style={{ padding: "8px 18px", background: "#fff", color: HORIZON.neutralInk, border: "1px solid #D1D5DB", borderRadius: 6, cursor: testing ? "wait" : "pointer", fontSize: 13 }}>{testing ? "Probando…" : "Probar conexión"}</button>
      </div>
    </div>
  );
}
