import { describe, it, expect } from "vitest";
import { pbxNow } from "@lib/clock.js";

describe("lib/clock", () => {
  it("pbxNow returns a Date in the current century (not 1970)", () => {
    const d = pbxNow();
    expect(d).toBeInstanceOf(Date);
    expect(d.getFullYear()).toBeGreaterThan(2020);
  });

  it("pbxNow renders time in HH:MM:SS via toLocaleTimeString es-UY", () => {
    const d = pbxNow();
    const t = d.toLocaleTimeString("es-UY", { timeZone: "America/Montevideo", hour12: false, hour: "2-digit", minute: "2-digit", second: "2-digit" });
    expect(t).toMatch(/^\d{2}:\d{2}:\d{2}$/);
  });

  it("pbxNow date parses back as ISO", () => {
    const d = pbxNow();
    const iso = d.toISOString();
    expect(iso).toMatch(/^\d{4}-\d{2}-\d{2}T/);
  });
});
