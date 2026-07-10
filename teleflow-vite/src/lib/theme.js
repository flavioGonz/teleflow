// src/lib/theme.js — tokens y utilidades de tema.
//
// - forceLight() aplica clase "light" al <html> y limpia "dark" — evita
//   el mismatch shadcn vs body.light del legacy.
// - HORIZON: paleta Horizon Seguridad (fuente de verdad de brand colors).
// - useForceLight() hook que asegura light theme mientras el componente esté montado.

import { useEffect } from "react";

export const HORIZON = Object.freeze({
  green:      "#0F7A3E",
  greenSoft:  "#E8F3EC",
  greenDark:  "#0A5A2C",
  amber:      "#D97706",
  amberSoft:  "#FEF3C7",
  danger:     "#DC2626",
  neutralInk: "#0F172A",
  muted:      "#64748B",
});

export function forceLight() {
  if (typeof document === "undefined") return;
  const root = document.documentElement;
  root.classList.remove("dark");
  root.classList.add("light");
  root.style.colorScheme = "light";
}

export function useForceLight() {
  useEffect(() => { forceLight(); }, []);
}

// Escucha cambios de otras rutas / SW que puedan re-agregar `.dark`
if (typeof MutationObserver !== "undefined" && typeof document !== "undefined") {
  const root = document.documentElement;
  new MutationObserver(() => {
    if (root.classList.contains("dark")) forceLight();
  }).observe(root, { attributes: true, attributeFilter: ["class"] });
}
