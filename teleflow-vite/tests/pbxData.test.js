import { describe, it, expect, vi, beforeEach } from "vitest";
import { usePbxData } from "../src/stores/pbxData.js";

const makeResp = () => new Response(JSON.stringify({ extensions: [{ extension: "500" }] }), { status: 200 });

describe("stores/pbxData", () => {
  beforeEach(() => {
    usePbxData.setState({ data: null, loading: false, error: null, lastFetchAt: null });
    global.fetch = vi.fn().mockImplementation(() => Promise.resolve(makeResp()));
  });

  it("fetch populates data and lastFetchAt", async () => {
    await usePbxData.getState().fetch();
    const s = usePbxData.getState();
    expect(s.data?.extensions?.[0]?.extension).toBe("500");
    expect(s.lastFetchAt).toBeGreaterThan(0);
  });

  it("cache prevents refetch within 15s window", async () => {
    await usePbxData.getState().fetch();
    global.fetch.mockClear();
    await usePbxData.getState().fetch();
    expect(global.fetch).toHaveBeenCalledTimes(0); // segundo call es cache-hit
  });

  it("force=true bypasses cache", async () => {
    await usePbxData.getState().fetch();
    global.fetch.mockClear();
    await usePbxData.getState().fetch(true);
    expect(global.fetch).toHaveBeenCalledTimes(1);
  });
});
