// main entry con router y stores.
// Stores importados estaticamente para forzar inline en app.build.js.
import React from "react";
import { createRoot } from "react-dom/client";

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
