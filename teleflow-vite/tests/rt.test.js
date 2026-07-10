import { describe, it, expect, vi, beforeEach } from "vitest";

// Fake socket que capturamos y podemos disparar eventos manualmente
const fakeSocket = {
  connected: false,
  listeners: {},
  onAny: vi.fn(function(cb) { this.anyCb = cb; }),
  on: vi.fn(),
  emit: vi.fn(),
  disconnect: vi.fn(),
};
function fireEvent(event, payload) {
  if (fakeSocket.anyCb) fakeSocket.anyCb(event, payload);
}

vi.mock("socket.io-client", () => ({
  io: vi.fn(() => {
    fakeSocket.connected = true;
    return fakeSocket;
  })
}));

// import DESPUÉS del mock
const { rt } = await import("@lib/rt.js");

describe("lib/rt", () => {
  beforeEach(() => {
    rt.disconnect();
    fakeSocket.anyCb = null;
    fakeSocket.onAny.mockClear();
  });

  it("connect() crea singleton (solo un socket)", () => {
    rt.connect();
    rt.connect();
    rt.connect();
    // ensureSocket internamente llama io() sólo la primera vez
    expect(fakeSocket.onAny).toHaveBeenCalledTimes(1);
  });

  it("on() suscribe y unsub cancela el listener", () => {
    rt.connect();
    const cb = vi.fn();
    const off = rt.on("test_event", cb);
    fireEvent("test_event", { x: 1 });
    expect(cb).toHaveBeenCalledWith({ x: 1 });
    off();
    fireEvent("test_event", { x: 2 });
    // No debe llamar de nuevo tras unsubscribe
    expect(cb).toHaveBeenCalledTimes(1);
  });

  it("on() replay último payload al listener que llega tarde", async () => {
    rt.connect();
    fireEvent("late_event", { hello: "world" });
    const cb = vi.fn();
    rt.on("late_event", cb);
    // Replay es via queueMicrotask, esperar un tick
    await new Promise((r) => setTimeout(r, 5));
    expect(cb).toHaveBeenCalledWith({ hello: "world" });
  });

  it("last() devuelve el ultimo payload sin suscribirse", () => {
    rt.connect();
    fireEvent("stat_event", { count: 42 });
    expect(rt.last("stat_event")).toEqual({ count: 42 });
    expect(rt.last("nunca_visto")).toBe(null);
  });

  it("isConnected refleja el estado", () => {
    expect(rt.isConnected()).toBe(false);
    rt.connect();
    expect(rt.isConnected()).toBe(true);
  });
});
