// src/stores/session.js — sesión del usuario (admin o agente).
import { create } from "zustand";
import { api, ApiError, setUnauthorizedHandler } from "@lib/api.js";

export const useSession = create((set, get) => ({
  user: null,               // { id, name, role, agent_number?, ext? }
  status: "idle",           // idle | loading | ready | error
  error: null,

  async loginAgent(agentNumber, password) {
    set({ status: "loading", error: null });
    try {
      const j = await api.action("agent.php", "login", { agent_number: agentNumber, password });
      if (!j?.ok) throw new ApiError(j?.error || "login failed", { status: 401 });
      set({ user: j.user || { role: "agent", agent_number: agentNumber, ext: j.ext }, status: "ready" });
      return j;
    } catch (err) {
      set({ status: "error", error: err.message || String(err) });
      throw err;
    }
  },

  async logout() {
    try { await api.action("agent.php", "logout"); } catch { /* ignore */ }
    set({ user: null, status: "idle", error: null });
    try { localStorage.removeItem("tf_user_cache"); localStorage.removeItem("tf_user"); } catch (_e) { /* ignore */ }
  },

  isAgent: () => get().user?.role === "agent",
  isAdmin: () => get().user?.role === "admin" || get().user?.role === "supervisor",
}));

// Registrar handler global de 401 → limpia sesión
setUnauthorizedHandler(() => {
  useSession.setState({ user: null, status: "idle", error: "sesión expirada" });
});
