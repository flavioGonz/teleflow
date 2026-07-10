// Fase 2 · main entry con router y stores.
import React from "react";
import { createRoot } from "react-dom/client";
import { AppRouter } from "./router.jsx";
import { wireLive } from "./stores/live.js";
import { primeClock } from "@lib/clock.js";

primeClock();
wireLive();

const el = document.getElementById("root") || document.body;
createRoot(el).render(<AppRouter />);
