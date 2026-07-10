import { describe, it, expect, vi, beforeEach } from "vitest";
import { api, ApiError, setUnauthorizedHandler } from "@lib/api.js";

describe("lib/api", () => {
  beforeEach(() => {
    global.fetch = vi.fn();
  });

  it("decodes JSON and returns body on 200", async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: true, x: 1 }), { status: 200 }));
    const j = await api.get("foo.php");
    expect(j).toEqual({ ok: true, x: 1 });
  });

  it("throws ApiError on 500 with body", async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: false, error: "boom" }), { status: 500 }));
    await expect(api.get("foo.php", null, { retries: 0 })).rejects.toBeInstanceOf(ApiError);
  });

  it("calls unauthorized handler on 401", async () => {
    const cb = vi.fn();
    setUnauthorizedHandler(cb);
    global.fetch.mockResolvedValueOnce(new Response(null, { status: 401 }));
    await expect(api.get("foo.php")).rejects.toBeInstanceOf(ApiError);
    expect(cb).toHaveBeenCalled();
  });

  it("retries on network error then succeeds", async () => {
    global.fetch
      .mockRejectedValueOnce(new Error("network down"))
      .mockResolvedValueOnce(new Response(JSON.stringify({ ok: true }), { status: 200 }));
    const j = await api.get("foo.php", null, { retries: 1 });
    expect(j).toEqual({ ok: true });
    expect(global.fetch).toHaveBeenCalledTimes(2);
  });

  it("action() posts JSON body with action key", async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: true }), { status: 200 }));
    await api.action("agent.php", "login", { agent_number: "200", password: "x" });
    const call = global.fetch.mock.calls[0];
    expect(call[0]).toBe("/api/agent.php");
    expect(call[1].method).toBe("POST");
    expect(JSON.parse(call[1].body)).toEqual({ action: "login", agent_number: "200", password: "x" });
  });
});
