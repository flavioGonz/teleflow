// src/router.jsx — F2.6: agrega CDR / Clients / IVR.
import React, { lazy, Suspense } from "react";
import { HashRouter, Routes, Route, Navigate } from "react-router-dom";
import { AppShell } from "./AppShell.jsx";
import { HORIZON } from "@lib/theme.js";

const Dashboard  = lazy(() => import("./views/Dashboard.jsx"));
const CallCenter = lazy(() => import("./views/CallCenter.jsx"));
const Reports    = lazy(() => import("./views/Reports.jsx"));
const Extensions = lazy(() => import("./views/Extensions.jsx"));
const Queues     = lazy(() => import("./views/Queues.jsx"));
const Hotdesking = lazy(() => import("./views/Hotdesking.jsx"));
const CDR        = lazy(() => import("./views/CDR.jsx"));
const Clients    = lazy(() => import("./views/Clients.jsx"));
const IVR        = lazy(() => import("./views/IVR.jsx"));

function Loading() {
  return (
    <div style={{ padding: 40, textAlign: "center", color: HORIZON.muted, fontFamily: "system-ui" }}>
      <div style={{ width: 32, height: 32, margin: "0 auto 12px",
        border: `3px solid ${HORIZON.greenSoft}`, borderTopColor: HORIZON.green,
        borderRadius: "50%", animation: "spin 0.8s linear infinite" }} />
      <style>{"@keyframes spin{to{transform:rotate(360deg)}}"}</style>
      Cargando vista…
    </div>
  );
}

export function AppRouter() {
  return (
    <HashRouter>
      <AppShell>
        <Suspense fallback={<Loading />}>
          <Routes>
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard"  element={<Dashboard />} />
            <Route path="/callcenter" element={<CallCenter />} />
            <Route path="/extensions" element={<Extensions />} />
            <Route path="/queues"     element={<Queues />} />
            <Route path="/hotdesking" element={<Hotdesking />} />
            <Route path="/cdr"        element={<CDR />} />
            <Route path="/clients"    element={<Clients />} />
            <Route path="/ivr"        element={<IVR />} />
            <Route path="/reports"    element={<Reports />} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </Suspense>
      </AppShell>
    </HashRouter>
  );
}
