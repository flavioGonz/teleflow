import { describe, it, expect, vi, beforeEach } from "vitest";
import { useSession } from "../src/stores/session.js";

describe("stores/session", () => {
  beforeEach(() => {
    useSession.setState({ user: null, status: "idle", error: null });
    global.fetch = vi.fn();
    // localStorage stub
    global.localStorage = { removeItem: vi.fn(), setItem: vi.fn(), getItem: vi.fn() };
  });

  it("loginAgent sets user on success", async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: true, user: { role: "agent", agent_number: "200" } }), { status: 200 }));
    await useSession.getState().loginAgent("200", "x");
    const s = useSession.getState();
    expect(s.user?.role).toBe("agent");
    expect(s.status).toBe("ready");
  });

  it("loginAgent sets error on 401", async () => {
    global.fetch.mockResolvedValueOnce(new Response(JSON.stringify({ ok: false, error: "cred inválidas" }), { status: 200 }));
    await useSession.getState().loginAgent("200", "x").catch(() => {});
    const s = useSession.getState();
    expect(s.status).toBe("error");
    expect(s.error).toMatch(/cred/);
  });

  it("logout clears user and localStorage", async () => {
    useSession.setState({ user: { role: "agent" }, status: "ready" });
    global.fetch.mockResolvedValueOnce(new Response("{}", { status: 200 }));
    await useSession.getState().logout();
    expect(useSession.getState().user).toBe(null);
    expect(global.localStorage.removeItem).toHaveBeenCalledWith("tf_user_cache");
  });

  it("isAgent + isAdmin discriminan role", () => {
    useSession.setState({ user: { role: "agent" } });
    expect(useSession.getState().isAgent()).toBe(true);
    expect(useSession.getState().isAdmin()).toBe(false);
    useSession.setState({ user: { role: "admin" } });
    expect(useSession.getState().isAdmin()).toBe(true);
    expect(useSession.getState().isAgent()).toBe(false);
  });
});
