// src/lib/api.js — cliente único para /api/*.php de TeleFlow.
// Reemplaza los ~153 `fetch()` sueltos del app.jsx legacy.
//
// Diseño:
//  - credentials: 'include' siempre (sesión PHP)
//  - AbortController con timeout configurable (default 15s)
//  - Retry con backoff exponencial en errores de red (no en 4xx)
//  - 401 → dispara callback global (para relogin) sin propagar
//  - JSON auto-decode, error tipado (ApiError)

const DEFAULT_TIMEOUT_MS = 15000;
const DEFAULT_RETRIES = 2;

let onUnauthorized = null;
export function setUnauthorizedHandler(cb) { onUnauthorized = cb; }

export class ApiError extends Error {
  constructor(message, { status, code, body } = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status ?? 0;
    this.code = code ?? null;
    this.body = body ?? null;
  }
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function doFetch(url, opts, timeoutMs) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), timeoutMs);
  try {
    return await fetch(url, { ...opts, signal: ctrl.signal });
  } finally {
    clearTimeout(t);
  }
}

async function request(path, { method = "GET", query, body, headers, timeout = DEFAULT_TIMEOUT_MS, retries = DEFAULT_RETRIES } = {}) {
  let url = path.startsWith("http") || path.startsWith("/") ? path : `/api/${path}`;
  if (query && typeof query === "object") {
    const qs = new URLSearchParams(query).toString();
    if (qs) url += (url.includes("?") ? "&" : "?") + qs;
  }
  const init = {
    method,
    credentials: "include",
    headers: { Accept: "application/json", ...(headers || {}) },
  };
  if (body !== undefined) {
    if (body instanceof FormData) {
      init.body = body;
    } else {
      init.headers["Content-Type"] = "application/json";
      init.body = JSON.stringify(body);
    }
  }

  let lastErr = null;
  for (let attempt = 0; attempt <= retries; attempt++) {
    try {
      const res = await doFetch(url, init, timeout);
      if (res.status === 401) {
        if (typeof onUnauthorized === "function") onUnauthorized({ url, res });
        throw new ApiError("Unauthorized", { status: 401 });
      }
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch { data = text; }
      if (!res.ok) throw new ApiError(`HTTP ${res.status}`, { status: res.status, body: data });
      return data;
    } catch (err) {
      lastErr = err;
      const isNetwork = !(err instanceof ApiError) || err.status === 0;
      if (!isNetwork || attempt === retries) throw err;
      await sleep(200 * Math.pow(2, attempt));  // 200, 400, 800…
    }
  }
  throw lastErr;
}

// Azúcar sintáctico para el patrón dominante: action-based endpoints
export const api = {
  request,
  get:  (path, query, opts)      => request(path, { method: "GET",  query, ...opts }),
  post: (path, body,  opts)      => request(path, { method: "POST", body,  ...opts }),
  action: (endpoint, action, params) => request(endpoint, { method: "POST", body: { action, ...(params || {}) } }),
};
