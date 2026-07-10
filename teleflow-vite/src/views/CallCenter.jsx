// src/views/CallCenter.jsx — panel del agente completo (F2.1).
import React, { useState } from "react";
import { useSession } from "../stores/session.js";
import { useClock } from "@lib/clock.js";
import { HORIZON } from "@lib/theme.js";
import Softphone from "../components/Softphone.jsx";
import IncomingCallCard from "../components/IncomingCallCard.jsx";
import MyCalls from "../components/MyCalls.jsx";
import MyQueues from "../components/MyQueues.jsx";

function LoginForm() {
  const login = useSession((s) => s.loginAgent);
  const status = useSession((s) => s.status);
  const err = useSession((s) => s.error);
  const [agent, setAgent] = useState("");
  const [pass, setPass] = useState("");
  const submit = (e) => { e.preventDefault(); login(agent, pass).catch(() => {}); };
  return (
    <form onSubmit={submit} style={{
      maxWidth: 320, margin: "60px auto", padding: 24, background: "#fff",
      border: "1px solid #E5E7EB", borderRadius: 12
    }}>
      <h2 style={{ margin: "0 0 16px", color: HORIZON.neutralInk }}>Login agente</h2>
      <input type="text" placeholder="Nº agente" value={agent} onChange={(e) => setAgent(e.target.value)}
        style={{ width: "100%", padding: 10, marginBottom: 8, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 14 }} />
      <input type="password" placeholder="Contraseña" value={pass} onChange={(e) => setPass(e.target.value)}
        style={{ width: "100%", padding: 10, marginBottom: 12, border: "1px solid #D1D5DB", borderRadius: 6, fontSize: 14 }} />
      <button type="submit" disabled={status === "loading"} style={{
        width: "100%", padding: "10px 16px", border: 0, borderRadius: 6, cursor: "pointer",
        background: HORIZON.green, color: "#fff", fontWeight: 600, fontSize: 14, opacity: status === "loading" ? 0.7 : 1
      }}>{status === "loading" ? "Ingresando…" : "Ingresar"}</button>
      {err && <div style={{ color: HORIZON.danger, marginTop: 10, fontSize: 13 }}>{err}</div>}
    </form>
  );
}

function Section({ title, children, right }) {
  return (
    <section style={{ marginBottom: 20 }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
        <h2 style={{ margin: 0, fontSize: 14, textTransform: "uppercase", letterSpacing: 1.5, color: HORIZON.muted }}>{title}</h2>
        {right}
      </div>
      {children}
    </section>
  );
}

function AgentPanel() {
  const user = useSession((s) => s.user);
  const logout = useSession((s) => s.logout);
  const clock = useClock();
  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 24 }}>
        <div>
          <h1 style={{ margin: 0, fontSize: 22, color: HORIZON.neutralInk }}>
            Panel {user?.agent_number ? `agente #${user.agent_number}` : ""}
          </h1>
          <div style={{ fontSize: 12, color: HORIZON.muted, marginTop: 4 }}>
            {clock.date} · {clock.time} {clock.syncedWithServer && <span style={{ color: HORIZON.green }}>· sync PBX</span>}
          </div>
        </div>
        <button onClick={logout} style={{ padding: "8px 14px", border: `1px solid ${HORIZON.danger}`, background: "#fff", color: HORIZON.danger, borderRadius: 6, cursor: "pointer", fontSize: 13 }}>Salir</button>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "280px 1fr", gap: 20 }}>
        <aside>
          <Section title="Softphone"><Softphone /></Section>
          <Section title="Mis colas"><MyQueues /></Section>
        </aside>
        <main>
          <Section title="Mis llamadas"><MyCalls /></Section>
        </main>
      </div>

      <IncomingCallCard />
    </div>
  );
}

export default function CallCenter() {
  const user = useSession((s) => s.user);
  return user ? <AgentPanel /> : <LoginForm />;
}
