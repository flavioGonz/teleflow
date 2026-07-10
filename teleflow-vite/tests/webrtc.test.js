import { describe, it, expect, vi, beforeEach } from "vitest";
import { loadSip, startSoftphone, attachAudio } from "@lib/webrtc.js";

describe("lib/webrtc", () => {
  beforeEach(() => {
    delete window.SIP;
    document.head.innerHTML = "";
  });

  describe("loadSip", () => {
    it("devuelve window.SIP si ya existe (sin insertar script)", async () => {
      const fake = { UserAgent: {}, Registerer: {}, Inviter: {} };
      window.SIP = fake;
      const sip = await loadSip();
      expect(sip).toBe(fake);
      expect(document.querySelectorAll("script").length).toBe(0);
    });

    it("cachea la promise en llamadas concurrentes", () => {
      const p1 = loadSip();
      const p2 = loadSip();
      expect(p1).toBe(p2);
    });

    // Nota: el test de inserción real del <script> queda para e2e con Playwright.
    // sipLoadPromise es un cache module-scoped, difícil de resetear en unit tests.
  });

  describe("startSoftphone", () => {
    beforeEach(() => {
      // Mock UserAgent + Registerer + estados SIP
      window.SIP = {
        UserAgent: class {
          constructor(opts) { this._opts = opts; this.delegate = opts.delegate; }
          static makeURI(u) { return { uri: u, toString: () => u }; }
          async start() { this._started = true; }
          async stop()  { this._started = false; }
        },
        Registerer: class {
          constructor(ua, opts) { this._ua = ua; this._opts = opts; this.stateChange = { addListener: () => {} }; }
          async register()   { this._registered = true; }
          async unregister() { this._registered = false; }
        },
        Inviter: class {
          constructor() { this.state = "Initial"; this.stateChange = { addListener: () => {} }; }
          async invite() {}
        },
        RegistererState:  { Registered: "Registered", Unregistered: "Unregistered" },
        SessionState:     { Established: "Established", Terminated: "Terminated", Establishing: "Establishing" }
      };
    });

    it("crea UserAgent con URI de la ext + domain", async () => {
      const handle = await startSoftphone({ ext: "500", secret: "s", domain: "pbx.test", wss_url: "wss://pbx.test/ws" }, {});
      expect(handle.ua).toBeTruthy();
      expect(handle.ua._opts.authorizationUsername).toBe("500");
      expect(handle.ua._opts.transportOptions.server).toBe("wss://pbx.test/ws");
    });

    it("registra y expone invite/hangup/dispose", async () => {
      const handle = await startSoftphone({ ext: "500", secret: "s", domain: "x", wss_url: "wss://x/ws" }, {});
      expect(typeof handle.invite).toBe("function");
      expect(typeof handle.hangup).toBe("function");
      expect(typeof handle.dispose).toBe("function");
    });

    it("dispose desregistra y detiene el UA", async () => {
      const handle = await startSoftphone({ ext: "500", secret: "s", domain: "x", wss_url: "wss://x/ws" }, {});
      await handle.dispose();
      expect(handle.ua._started).toBe(false);
    });
  });

  describe("attachAudio", () => {
    it("no rompe si session es null", () => {
      expect(() => attachAudio(null, document.createElement("audio"))).not.toThrow();
    });
    it("no rompe si audioEl es null", () => {
      expect(() => attachAudio({}, null)).not.toThrow();
    });
  });
});
