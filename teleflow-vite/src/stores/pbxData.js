// src/stores/pbxData.js — datos "pesados" del PBX (get_full_data).
// Cachea la respuesta de api/index.php?action=get_full_data y provee refresh.
import { create } from "zustand";
import { api } from "@lib/api.js";

export const usePbxData = create((set, get) => ({
  data: null,       // { extensions, queues, live_calls, recordings, disuasion_groups, clients, ... }
  loading: false,
  error: null,
  lastFetchAt: null,

  async fetch(force = false) {
    const { lastFetchAt } = get();
    if (!force && lastFetchAt && Date.now() - lastFetchAt < 15000) return; // caché 15s
    set({ loading: true, error: null });
    try {
      const j = await api.get("index.php", { action: "get_full_data" });
      set({ data: j, loading: false, lastFetchAt: Date.now() });
    } catch (err) {
      set({ loading: false, error: err.message || String(err) });
    }
  },
}));
