// src/stores/softphone.js — estado del softphone del agente.
//
// Estados posibles:
//   idle        — no iniciado
//   connecting  — cargando SIP.js y conectando WSS
//   registered  — UserAgent + Registerer OK
//   incoming    — INVITE entrante, no atendido aún
//   in-call     — conversación establecida
//   error       — algo falló
import { create } from "zustand";
import { api } from "@lib/api.js";
import { startSoftphone, attachAudio } from "@lib/webrtc.js";

export const useSoftphone = create((set, get) => ({
  status: "idle",
  error: null,
  creds: null,
  ua: null,
  registerer: null,
  invite: null,
  hangup: null,
  dispose: null,
  incomingSession: null,
  activeSession: null,
  remoteName: null,

  async start() {
    if (get().status !== "idle") return;
    set({ status: "connecting", error: null });
    try {
      const creds = await api.action("agent.php", "softphone_creds");
      if (creds?.status !== "ok") throw new Error(creds?.message || "sin credenciales");
      if (!creds.ws_ready) throw new Error(creds.warning || "WebRTC no configurado en la ext");

      const handlers = {
        onIncoming: (session) => {
          const remote = session.remoteIdentity?.uri?.user || "?";
          set({ status: "incoming", incomingSession: session, remoteName: remote });
          session.stateChange.addListener((st) => {
            const SIP = get().SIP;
            if (!SIP) return;
            if (st === SIP.SessionState.Established) {
              set({ status: "in-call", activeSession: session, incomingSession: null });
              queueMicrotask(() => attachAudio(session, document.getElementById("tf-audio-remote")));
            } else if (st === SIP.SessionState.Terminated) {
              set({ status: "registered", activeSession: null, incomingSession: null, remoteName: null });
            }
          });
        },
        onRegistered: () => set({ status: "registered" }),
        onUnregistered: () => set({ status: "connecting" }),
      };

      const handle = await startSoftphone(creds, handlers);
      set({
        creds,
        ua: handle.ua,
        registerer: handle.registerer,
        invite: handle.invite,
        hangup: handle.hangup,
        dispose: handle.dispose,
        SIP: handle.SIP,
      });
    } catch (err) {
      set({ status: "error", error: err.message || String(err) });
    }
  },

  async call(target) {
    const { invite, SIP } = get();
    if (!invite) return;
    const session = await invite(target);
    set({ activeSession: session, remoteName: target });
    session.stateChange.addListener((st) => {
      if (st === SIP.SessionState.Established) {
        set({ status: "in-call" });
        queueMicrotask(() => attachAudio(session, document.getElementById("tf-audio-remote")));
      } else if (st === SIP.SessionState.Terminated) {
        set({ status: "registered", activeSession: null, remoteName: null });
      }
    });
  },

  async answer() {
    const { incomingSession } = get();
    if (!incomingSession) return;
    await incomingSession.accept({ sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } } });
  },

  async decline() {
    const { incomingSession, hangup } = get();
    if (incomingSession && hangup) await hangup(incomingSession);
    set({ status: "registered", incomingSession: null, remoteName: null });
  },

  async end() {
    const { activeSession, hangup } = get();
    if (activeSession && hangup) await hangup(activeSession);
    set({ status: "registered", activeSession: null, remoteName: null });
  },

  async stop() {
    const { dispose } = get();
    if (dispose) await dispose();
    set({ status: "idle", ua: null, registerer: null, invite: null, hangup: null, dispose: null, activeSession: null, incomingSession: null, remoteName: null, creds: null });
  },
}));
