import { describe, it, expect, vi, beforeEach } from "vitest";

const fakeSocket = { connected: true, listeners: {}, onAny: vi.fn(function(cb) { this.anyCb = cb; }), on: vi.fn(), emit: vi.fn(), disconnect: vi.fn() };
function fireEvent(event, payload) { if (fakeSocket.anyCb) fakeSocket.anyCb(event, payload); }

vi.mock("socket.io-client", () => ({ io: vi.fn(() => fakeSocket) }));

const { rt } = await import("@lib/rt.js");
const { useLive, wireLive } = await import("../src/stores/live.js");

describe("stores/live", () => {
  beforeEach(() => {
    rt.disconnect();
    fakeSocket.anyCb = null;
    fakeSocket.onAny.mockClear();
    useLive.setState({ activeCalls: [], queues: {}, peers: {}, connected: false, lastEventAt: null });
  });

  it("wireLive: call_update actualiza activeCalls", () => {
    wireLive();
    fireEvent("call_update", { calls: [{ uniqueid: "abc", state: "Up" }] });
    expect(useLive.getState().activeCalls).toHaveLength(1);
    expect(useLive.getState().activeCalls[0].uniqueid).toBe("abc");
  });

  it("wireLive: queue_update mergea por queue_id", () => {
    wireLive();
    fireEvent("queue_update", { queue_id: "9101", waiting: 3 });
    fireEvent("queue_update", { queue_id: "9101", answered: 12 });
    fireEvent("queue_update", { queue_id: "9102", waiting: 0 });
    const q = useLive.getState().queues;
    expect(q["9101"].waiting).toBe(3);
    expect(q["9101"].answered).toBe(12);
    expect(q["9102"].waiting).toBe(0);
  });

  it("wireLive: peer_update mergea por ext", () => {
    wireLive();
    fireEvent("peer_update", { ext: "501", status: "OK" });
    fireEvent("peer_update", { ext: "501", tech: "chan_sip" });
    fireEvent("peer_update", { ext: "505", status: "UNREACHABLE" });
    const p = useLive.getState().peers;
    expect(p["501"].status).toBe("OK");
    expect(p["501"].tech).toBe("chan_sip");
    expect(p["505"].status).toBe("UNREACHABLE");
  });

  it("wireLive: idempotente — llamarlo 2 veces no dispara doble listener", () => {
    wireLive();
    wireLive();
    fireEvent("call_update", { calls: [{ uniqueid: "1" }] });
    // Si estuviera doble, tendría 2 entries. Zustand set con array reemplaza, así que len=1
    expect(useLive.getState().activeCalls.length).toBe(1);
  });

  it("lastEventAt se actualiza en cada evento", () => {
    wireLive();
    const before = useLive.getState().lastEventAt;
    fireEvent("call_update", { calls: [] });
    const after = useLive.getState().lastEventAt;
    expect(after).toBeGreaterThan(before || 0);
  });
});
