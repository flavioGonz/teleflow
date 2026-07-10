# Refactor Teleflow — Guía de arquitectura

**Estado:** F0 a F9 completo. Bundle Vite paralelo al legacy Babel Standalone. Prod intacta (defaults sin cambios).

## Estructura del repo

```
teleflow/
├── assets/
│   ├── app.jsx              ← Legacy (Babel Standalone, 1.3 MB monolítico)
│   ├── app.build.js         ← Bundle Vite (218 KB + chunks lazy)
│   └── chunks/*.js          ← Chunks lazy-loaded on-demand
├── agentes/
│   └── index.php            ← Shell agente. SIEMPRE sirve app.jsx (legacy)
├── api/
│   ├── _bootstrap.php       ← Helper compartido: session + auth + JSON headers
│   ├── *.php                ← Endpoints (22 usando bootstrap, 8 correctos sin él)
│   └── version.php          ← Metadata pública del deploy
├── index.php                ← Shell admin. Default = app.jsx. ?build=1 = app.build.js
└── teleflow-vite/           ← Workspace del bundle nuevo
    ├── src/
    │   ├── main.jsx         ← Entry point → AppRouter
    │   ├── router.jsx       ← HashRouter con lazy() por view
    │   ├── AppShell.jsx     ← Sidebar con 14 rutas en 6 secciones
    │   ├── lib/             ← Utilidades transversales (api, rt, clock, theme, webrtc)
    │   ├── stores/          ← Zustand: session, live, pbxData, softphone, settings
    │   ├── components/      ← Reutilizables (Softphone, MyCalls, SimpleChart, etc.)
    │   └── views/           ← Una por ruta (Dashboard, CallCenter, Reports, Config, …)
    ├── tests/               ← Vitest (39 tests verdes)
    ├── deploy.sh            ← Pipeline test→build→deploy→commit→push
    ├── vite.config.js       ← base:"/assets/", chunks separados
    └── package.json
```

## Cómo agregar una view nueva

1. Escribir `src/views/MiView.jsx` con `export default function MiView() { … }`. Usar `api.get()`/`api.post()` para fetches y `useLive()`/`usePbxData()` para estado.
2. Editar `src/router.jsx`: `const MiView = lazy(() => import("./views/MiView.jsx"));` + `<Route path="/miview" element={<MiView />} />`.
3. Editar `src/AppShell.jsx`: agregar `{ to: "/miview", label: "…", icon: "…" }` a la sección apropiada.
4. `cd teleflow-vite && ./deploy.sh "feat: MiView nueva"` — tests + build + deploy + commit + push en 1 paso.

## Cómo agregar una store nueva

```jsx
// src/stores/miStore.js
import { create } from "zustand";
import { api } from "@lib/api.js";

export const useMiStore = create((set, get) => ({
  data: null,
  async load() {
    const j = await api.get("mi_endpoint.php");
    set({ data: j?.data });
  },
}));
```

Uso en un componente: `const data = useMiStore((s) => s.data);` (selector para minimizar re-renders).

## Cómo migrar un endpoint backend a `tf_bootstrap`

Antes:
```php
<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
```

Después:
```php
<?php
require_once __DIR__ . '/_bootstrap.php';
tf_bootstrap(['auth' => 'admin']);   // 'admin' | 'agent' | 'any' | null
```

Modos de auth:
- `'admin'` — requiere `$_SESSION['tf_user']`
- `'agent'` — requiere `$_SESSION['agent_user']`
- `'any'` — cualquiera de los dos
- `null` — sin auth (público)

Si el endpoint sirve **binario** (audio, PDF, XLSX), NO usar `tf_bootstrap()` porque fuerza `Content-Type: application/json`. En su lugar usar el patrón manual:

```php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>28800,'path'=>'/','samesite'=>'Lax','httponly'=>true]);
    session_start();
}
if (empty($_SESSION['tf_user'])) { http_response_code(401); exit; }
```

## Cómo usar `deploy.sh`

```bash
cd teleflow-vite

# Deploy completo (test + build + prod + git)
./deploy.sh "feat: mi cambio"

# Dry-run: muestra qué haría, sin ejecutar
./deploy.sh --dry-run "feat: mi cambio"

# Solo deploy prod, sin commit
./deploy.sh --no-commit
```

**Guarda contra regresión**: el paso 4 verifica que `/agentes/index.php` sigue sirviendo `app.jsx` (legacy). Si no, aborta con error rojo antes de tocar git — protege contra un flip prematuro.

## Rollback

| Nivel | Comando | Efecto |
|---|---|---|
| Bundle nuevo (chunks rotos) | `sudo rm /var/www/teleflow/assets/app.build.js` | `?build=1` deja de funcionar, todo lo demás igual |
| Feature flag `?build=1` | `sudo cp /var/www/teleflow/index.php.bak.fase0 /var/www/teleflow/index.php` | Revierte F0 en admin |
| Shell agente F2.5 | `sudo cp /var/www/teleflow/agentes/index.php.bak.fase25 /var/www/teleflow/agentes/index.php` | Revierte flip agentes (nota: F2.5 ya está revertida) |
| Endpoint backend individual | `sudo cp /var/www/teleflow/api/foo.php.bak.faseN /var/www/teleflow/api/foo.php` | Restaura un endpoint específico |
| Todo el repo | `cd repo && git reset --hard origin/horizon/main~N` | Revierte N commits |

## Reglas duras del refactor

Ver [`feedback_flip_prod.md`](../memory) para el contexto de por qué existen:

1. **Nunca cambiar el default de una ruta productiva** (`/`, `/agentes/`, `/supervisor/`) durante refactor. Usar feature flag opt-in `?build=1`.
2. **Coexistencia legacy + bundle nuevo es OK sólo si NO comparten shell**. Los CDN del legacy en el HTML rompen React con el bundle Vite (2 copias de React → error #321). Si vas a flippear, primero eliminá los CDN del shell viejo, esperá 1 semana para que los SW se limpien, y recién ahí flippeás.
3. **Si un flip rompe prod, revertir inmediatamente**. No fixear encima.
4. **Cambios backend deben ser aditivos**. `_bootstrap.php` no rompe endpoints que no lo cargan — cada endpoint se migra por separado.

## Testing

```bash
npm run test         # single pass, aborta si algo rojo
npm run test:watch   # watch mode
```

Convenciones:
- `tests/foo.test.js` para lib/stores/utilidades unitarias
- Mocks preferidos: `vi.mock("module")` para deps grandes (SIP.js, socket.io-client)
- No testear el DOM renderizado — Playwright e2e queda para futuro F4.5+

## Endpoints en `tf_bootstrap` (22 migrados)

**auth=admin (12):** agents_crud, app_settings, asterisk, floor_map, last_calls, letsencrypt, pbx_config, queue_shortcuts, reports, users, agent_queue_pref, index (con excepciones internas).

**auth=agent (0)**.

**auth=any (6):** agent_report, door_dtmf, rtsp_proxy, hotdesking (mixed con público `get_webrtc`), clients + disuasion + nvr (delegado a `ops_auth_or_403`).

**auth=null (4):** version.php (público metadata), agent.php (auth propia via agent_user), agents.php (auth opcional), server_time.php (helper público).

## Estado de fases (2026-07-10)

- F0-F9 ✅ completo
- Views migradas: 14 de 15 (todas menos ViewCallCenter admin variant si existe)
- Tests: 39 verdes
- Backend endpoints en bootstrap: 22 de 30
- CI GitHub Actions: activo
- Deploy scripted: `deploy.sh` end-to-end en 1 comando

## Fases pendientes (opt-in, opcionales)

- **F10**: cuando quieras probar flip `/agentes/`, primero eliminá TODOS los CDN del shell viejo, esperá SW clearing, luego flippeá con Clear-Site-Data preload.
- **F11**: split `api/index.php` en `api/index/*.php` por dominio.
- **F12**: Playwright e2e para 3 flujos (login agente + softphone register + hangup).
