// src/AppShell.jsx — F2.8: 14 rutas totales.
import React from "react";
import { NavLink } from "react-router-dom";
import { useClock } from "@lib/clock.js";
import { useForceLight, HORIZON } from "@lib/theme.js";
import { useSession } from "./stores/session.js";
import { useLive } from "./stores/live.js";

const SECTIONS = [
  { title: "Overview", items: [
    { to: "/dashboard",  label: "Dashboard",    icon: "dashboard"  },
    { to: "/radar",      label: "Radar",        icon: "radar"      },
  ]},
  { title: "Operación", items: [
    { to: "/callcenter", label: "Panel Agente", icon: "headset"    },
    { to: "/live",       label: "Llamadas en vivo", icon: "podcasts" },
    { to: "/hotdesking", label: "Hotdesking",   icon: "groups"     },
  ]},
  { title: "PBX", items: [
    { to: "/extensions", label: "Extensiones",  icon: "call"       },
    { to: "/queues",     label: "Colas",        icon: "queue"      },
    { to: "/groups",     label: "Grupos",       icon: "diversity_3"},
    { to: "/ivr",        label: "IVR",          icon: "hub"        },
  ]},
  { title: "Historia", items: [
    { to: "/cdr",        label: "CDR",          icon: "history"    },
    { to: "/recordings", label: "Grabaciones",  icon: "voicemail"  },
    { to: "/reports",    label: "Reportes",     icon: "assessment" },
  ]},
  { title: "Datos", items: [
    { to: "/clients",    label: "Clientes",     icon: "business"   },
  ]},
  { title: "Sistema", items: [
    { to: "/config",     label: "Configuración", icon: "settings"  },
  ]},
];

export function AppShell({ children }) {
  useForceLight();
  const clock = useClock();
  const user = useSession((s) => s.user);
  const connected = useLive((s) => s.connected);
  return (
    <div style={{ display: "grid", gridTemplateColumns: "230px 1fr", minHeight: "100vh", background: "#F8FAFC", fontFamily: "system-ui, sans-serif" }}>
      <aside style={{ background: "#0F172A", color: "#E2E8F0", padding: "14px 0", overflowY: "auto" }}>
        <div style={{ padding: "0 20px 14px 20px", borderBottom: "1px solid rgba(255,255,255,0.1)" }}>
          <div style={{ fontSize: 11, opacity: 0.5, letterSpacing: 2, textTransform: "uppercase" }}>TeleFlow</div>
          <div style={{ fontSize: 18, fontWeight: 800, color: HORIZON.green, marginTop: 2 }}>Horizon</div>
        </div>
        <nav style={{ padding: 10 }}>
          {SECTIONS.map((sec) => (
            <div key={sec.title} style={{ marginBottom: 12 }}>
              <div style={{ padding: "0 8px 4px 10px", fontSize: 9, color: "#64748B", textTransform: "uppercase", letterSpacing: 2, fontWeight: 700 }}>{sec.title}</div>
              {sec.items.map((n) => (
                <NavLink key={n.to} to={n.to} style={({ isActive }) => ({
                  padding: "8px 12px", borderRadius: 8, textDecoration: "none",
                  display: "flex", alignItems: "center", gap: 10, fontSize: 13, marginBottom: 2,
                  color: isActive ? "#fff" : "#94A3B8",
                  background: isActive ? HORIZON.green : "transparent",
                  fontWeight: isActive ? 600 : 400,
                })}>
                  <span className="material-icons-round" style={{ fontSize: 17 }}>{n.icon}</span>
                  {n.label}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>
        <div style={{ padding: "0 14px 14px", fontSize: 11, color: "#64748B", borderTop: "1px solid rgba(255,255,255,0.1)", paddingTop: 10, marginTop: 6 }}>
          <div>{clock.time} · {clock.date}</div>
          <div style={{ marginTop: 3 }}>
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
