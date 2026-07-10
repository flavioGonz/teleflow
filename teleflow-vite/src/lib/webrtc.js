// src/lib/webrtc.js — wrapper SIP.js.
// Carga SIP.js dinámicamente desde CDN (no inflar bundle) y expone helpers
// para armar UserAgent + Registerer + Session con la config del PBX.

const SIP_CDN = "https://cdn.jsdelivr.net/npm/sip.js@0.21.2/dist/sip.min.js";
let sipLoadPromise = null;

/** Carga SIP.js una única vez. Retorna window.SIP. */
export function loadSip() {
  if (window.SIP) return Promise.resolve(window.SIP);
  if (sipLoadPromise) return sipLoadPromise;
  sipLoadPromise = new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = SIP_CDN;
    s.async = true;
    s.onload = () => window.SIP ? resolve(window.SIP) : reject(new Error("SIP.js cargó pero window.SIP undefined"));
    s.onerror = () => { sipLoadPromise = null; reject(new Error("no se pudo cargar SIP.js")); };
    document.head.appendChild(s);
  });
  return sipLoadPromise;
}

/**
 * Crea y arranca un UserAgent registrando la extensión WebRTC.
 * @param {object} creds — respuesta de agent.php?action=softphone_creds
 * @param {object} handlers — { onIncoming(session), onRegistered(), onUnregistered(), onError(err) }
 * @returns {Promise<{ua, registerer, invite, hangup, dispose}>}
 */
export async function startSoftphone(creds, handlers = {}) {
  const SIP = await loadSip();
  const uri = SIP.UserAgent.makeURI(`sip:${creds.ext}@${creds.domain}`);
  if (!uri) throw new Error("URI inválida");

  const ua = new SIP.UserAgent({
    uri,
    authorizationUsername: creds.ext,
    authorizationPassword: creds.secret,
    transportOptions: { server: creds.wss_url, keepAliveInterval: 20 },
    sessionDescriptionHandlerFactoryOptions: {
      constraints: { audio: true, video: false },
      peerConnectionConfiguration: { iceServers: [{ urls: "stun:stun.l.google.com:19302" }] }
    },
    delegate: {
      onInvite(session) {
        try { handlers.onIncoming?.(session); } catch (e) { console.error("[wrtc] onIncoming", e); }
      },
    },
  });

  await ua.start();
  const registerer = new SIP.Registerer(ua, { expires: 300, refreshFrequency: 60 });

  registerer.stateChange.addListener((state) => {
    if (state === SIP.RegistererState.Registered) handlers.onRegistered?.();
    else if (state === SIP.RegistererState.Unregistered) handlers.onUnregistered?.();
  });
  await registerer.register();

  async function invite(target) {
    if (!target) throw new Error("target vacío");
    const targetURI = SIP.UserAgent.makeURI(`sip:${target}@${creds.domain}`);
    const inviter = new SIP.Inviter(ua, targetURI, {
      sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } },
    });
    await inviter.invite();
    return inviter;
  }

  async function hangup(session) {
    if (!session) return;
    try {
      const st = session.state;
      if (st === SIP.SessionState.Establishing) await session.cancel?.();
      else if (st === SIP.SessionState.Established) await session.bye?.();
      else await session.reject?.({ statusCode: 486 });
    } catch (e) { console.warn("[wrtc] hangup", e); }
  }

  async function dispose() {
    try { await registerer.unregister(); } catch (_a) { /* ignore */ }
    try { await ua.stop(); } catch (_b) { /* ignore */ }
  }

  return { ua, registerer, invite, hangup, dispose, SIP };
}

/**
 * Adjunta el audio de una session al elemento <audio> dado.
 * SIP.js entrega el track via sessionDescriptionHandler.peerConnection.
 */
export function attachAudio(session, audioEl) {
  if (!session || !audioEl) return;
  const sdh = session.sessionDescriptionHandler;
  const pc = sdh?.peerConnection;
  if (!pc) return;
  const stream = new MediaStream();
  pc.getReceivers().forEach((r) => { if (r.track) stream.addTrack(r.track); });
  audioEl.srcObject = stream;
  audioEl.play().catch(() => {});
}
