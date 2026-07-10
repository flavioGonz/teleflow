import { describe, it, expect, beforeEach } from "vitest";
import { forceLight, HORIZON } from "@lib/theme.js";

describe("lib/theme", () => {
  beforeEach(() => {
    document.documentElement.className = "";
  });

  it("forceLight applies .light and removes .dark", () => {
    document.documentElement.classList.add("dark");
    forceLight();
    expect(document.documentElement.classList.contains("light")).toBe(true);
    expect(document.documentElement.classList.contains("dark")).toBe(false);
  });

  it("HORIZON palette is frozen", () => {
    expect(Object.isFrozen(HORIZON)).toBe(true);
    expect(HORIZON.green).toMatch(/^#[0-9a-fA-F]+$/);
  });

  it("MutationObserver reverts .dark re-added by other code", async () => {
    forceLight();
    document.documentElement.classList.add("dark");
    // wait a microtask for MutationObserver
    await new Promise((r) => setTimeout(r, 20));
    expect(document.documentElement.classList.contains("dark")).toBe(false);
  });
});
