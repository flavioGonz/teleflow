import { describe, it, expect, vi, beforeEach } from "vitest";
import { useSoftphone } from "../src/stores/softphone.js";

// Mock del módulo webrtc: startSoftphone devuelve un handle fake.
vi.mock("../src/lib/webrtc.js", () => {
  const fakeSession = { state: "Initial", stateChange: { addListener: vi.fn() } };
  return {
    startSoftphone: vi.fn(async (creds, handlers) => {
      queueMicrotask(() => handlers.onRegistered?.());
      return {
        ua: {},
        registerer: { stateChange: { addListener: vi.fn() }, register: vi.fn(), unregister: vi.fn() },
        invite: vi.fn(async () => fakeSession),
        hangup: vi.fn(async () => {}),
        dispose: vi.fn(async () => {}),
        SIP: { SessionState: { Established: "Established", Terminated: "Terminated" } }
      };
    }),
    attachAudio: vi.fn()
  };
});

describe("stores/softphone", () => {
  beforeEach(() => {
    useSoftphone.setState({
      status: "idle", error: null, creds: null, ua: null, registerer: null,
      invite: null, hangup: null, dispose: null, incomingSession: null, activeSession: null, remoteName: null
    });
    global.fetch = vi.fn().mockImplementation(() => Promise.resolve(new Response(JSON.stringify({
      status: "ok", ext: "500", secret: "s", wss_url: "wss://x/ws", domain: "x", ws_ready: true
    }), { status: 200 })));
  });

  it("start transitions idle → connecting → registered", async () => {
    await useSoftphone.getState().start();
    // Esperar microtask del onRegistered
    await new Promise((r) => setTimeout(r, 20));
    expect(useSoftphone.getState().status).toBe("registered");
    expect(useSoftphone.getState().creds?.ext).toBe("500");
  });

  it("start sets error si softphone_creds falla", async () => {
    global.fetch.mockImplementationOnce(() => Promise.resolve(new Response(JSON.stringify({ status: "error", message: "sin_sesion" }), { status: 200 })));
    await useSoftphone.getState().start();
    expect(useSoftphone.getState().status).toBe("error");
    expect(useSoftphone.getState().error).toMatch(/sin_sesion/);
  });

  it("start no reinicia si ya está registered", async () => {
    useSoftphone.setState({ status: "registered" });
    await useSoftphone.getState().start();
    // fetch NO se llamó
    expect(global.fetch).not.toHaveBeenCalled();
  });

  it("stop limpia todo el estado", async () => {
    await useSoftphone.getState().start();
    await new Promise((r) => setTimeout(r, 20));
    await useSoftphone.getState().stop();
    const s = useSoftphone.getState();
    expect(s.status).toBe("idle");
    expect(s.creds).toBe(null);
    expect(s.ua).toBe(null);
  });
});
