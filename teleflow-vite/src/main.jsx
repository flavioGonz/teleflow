// main entry con router y stores.
// IMPORTANTE: importamos todos los stores estáticamente para forzar que Vite los
// inline en app.build.js. Si los stores quedan en un chunk shared (pbxData-XXX.js),
// múltiples lazy chunks pueden obtener instancias distintas del store al importarlo
// dinámicamente → useSyncExternalStore falla con #321.
import React from "react";
import { createRoot } from "react-dom/client";

// Force-inline stores + libs al bundle principal (no lazy)
import "./stores/session.js";
import "./stores/live.js";
import "./stores/pbxData.js";
import "./stores/softphone.js";
import "./stores/settings.js";
import "@lib/api.js";
import "@lib/rt.js";
import "@lib/clock.js";
import "@lib/theme.js";
import "@lib/webrtc.js";

import { AppRouter } from "./router.jsx";
import { wireLive } from "./stores/live.js";
import { primeClock } from "@lib/clock.js";

primeClock();
wireLive();

const el = document.getElementById("root") || document.body;
createRoot(el).render(<AppRouter />);
