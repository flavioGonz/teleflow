// src/AppShell.jsx — sidebar con las 6 rutas de F2.4.
import React from "react";
import { NavLink } from "react-router-dom";
import { useClock } from "@lib/clock.js";
import { useForceLight, HORIZON } from "@lib/theme.js";
import { useSession } from "./stores/session.js";
import { useLive } from "./stores/live.js";

const NAV = [
  { to: "/dashboard",  label: "Dashboard",    icon: "dashboard"  },
  { to: "/callcenter", label: "Panel Agente", icon: "headset"    },
  { to: "/extensions", label: "Extensiones",  icon: "call"       },
  { to: "/queues",     label: "Colas",        icon: "queue"      },
  { to: "/hotdesking", label: "Hotdesking",   icon: "groups"     },
  { to: "/reports",    label: "Reportes",     icon: "assessment" },
];

export function AppShell({ children }) {
  useForceLight();
  const clock = useClock();
  const user = useSession((s) => s.user);
  const connected = useLive((s) => s.connected);
  return (
    <div style={{ display: "grid", gridTemplateColumns: "220px 1fr", minHeight: "100vh", background: "#F8FAFC", fontFamily: "system-ui, sans-serif" }}>
      <aside style={{ background: "#0F172A", color: "#E2E8F0", padding: "16px 0" }}>
        <div style={{ padding: "0 20px 20px 20px", borderBottom: "1px solid rgba(255,255,255,0.1)" }}>
          <div style={{ fontSize: 11, opacity: 0.5, letterSpacing: 2, textTransform: "uppercase" }}>TeleFlow</div>
          <div style={{ fontSize: 18, fontWeight: 800, color: HORIZON.green, marginTop: 2 }}>Horizon</div>
        </div>
        <nav style={{ padding: 12, display: "flex", flexDirection: "column", gap: 4 }}>
          {NAV.map((n) => (
            <NavLink key={n.to} to={n.to} style={({ isActive }) => ({
              padding: "10px 12px", borderRadius: 8, textDecoration: "none",
              display: "flex", alignItems: "center", gap: 10, fontSize: 13,
              color: isActive ? "#fff" : "#94A3B8",
              background: isActive ? HORIZON.green : "transparent",
              fontWeight: isActive ? 600 : 400,
            })}>
              <span className="material-icons-round" style={{ fontSize: 18 }}>{n.icon}</span>
              {n.label}
            </NavLink>
          ))}
        </nav>
        <div style={{ position: "fixed", bottom: 12, left: 12, right: "calc(100% - 220px + 12px)", fontSize: 11, color: "#64748B" }}>
          <div>{clock.time} · {clock.date}</div>
          <div style={{ marginTop: 2 }}>
            <span style={{ display: "inline-block", width: 8, height: 8, borderRadius: 999, background: connected ? HORIZON.green : "#64748B", marginRight: 6, verticalAlign: "middle" }} />
            {connected ? "hub online" : "hub offline"}
            {user && ` · ${user.name || user.agent_number || "user"}`}
          </div>
        </div>
      </aside>
      <main style={{ padding: 20, minWidth: 0 }}>{children}</main>
    </div>
  );
}
