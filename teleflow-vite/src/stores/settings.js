// src/stores/settings.js — cache de app_settings key/value.
import { create } from "zustand";
import { api } from "@lib/api.js";

export const useSettings = create((set, get) => ({
  settings: {},
  loading: false,
  saved: null,   // key del último saved, para mostrar checkmark efimero

  async load() {
    if (get().loading) return;
    set({ loading: true });
    try {
      const j = await api.get("app_settings.php");
      set({ settings: j?.settings || {}, loading: false });
    } catch { set({ loading: false }); }
  },

  async save(patch) {
    const optimistic = { ...get().settings, ...patch };
    set({ settings: optimistic });
    try {
      await api.post("app_settings.php", patch);
      set({ saved: Object.keys(patch)[0] });
      setTimeout(() => { if (get().saved === Object.keys(patch)[0]) set({ saved: null }); }, 2000);
    } catch {
      // rollback: recargar del server
      get().load();
    }
  },
}));
