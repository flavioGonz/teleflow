# Arquitectura — Teleflow Horizon

## Vista general

```
┌──────────────────────────┐         ┌──────────────────────────┐
│  Web/App VM              │  AMI    │  PBX Issabel             │
│  10.1.1.192              │ ◄─────► │  10.1.1.7                │
│  Apache + PHP 8.3        │  MySQL  │  Asterisk + chan_sip     │
│  Realtime hub (Node)     │ ◄─────► │  MariaDB                 │
│  MediaMTX (RTSP → HLS)   │  RTSP   │  videoporteros           │
└──────────────────────────┘         └──────────────────────────┘
            │
            │ HTTPS / WebSocket
            ▼
   Navegadores (admin + agentes)
```

## Componentes

### Web/App VM (`srv-teleflow`, `10.1.1.192`)

- **Apache 2.4** con vhost `teleflow.conf`. DocumentRoot `/var/www/teleflow`.
- **PHP 8.3** con módulos: pdo_mysql, curl, mbstring, json, openssl, gd, sqlite3, soap.
- **Node 18+** corriendo el hub realtime como `teleflow-realtime.service`.
- **MediaMTX v1.10** corriendo como `mediamtx.service`. Recibe RTSP de videoporteros y los expone como HLS en `:8888`.
- **SQLite** en `/var/www/teleflow/db/acl.db` para tabla `acl_user` (admins del portal).

### PBX VM (`10.1.1.7`, Issabel)

- **Asterisk** con **chan_sip** activo (chan_pjsip cargado pero sin uso).
- **MariaDB** con bases `asterisk` (config), `asteriskcdrdb` (CDR + queue_log), `call_center` (agentes).
- **AMI** en puerto 5038, usuario dedicado `teleflow`.

## Capas del frontend (SPA)

```
index.php (70 KB)
  └── Carga inline:
      - Tailwind CDN
      - Material Icons fonts
      - React 18 UMD
      - Babel-standalone
  └── <script type="text/babel" src="assets/app.jsx?v={cache}">
        └── assets/app.jsx (~945 KB)
            ├── Primitives shadcn inline (Button, Card, Dialog, etc.)
            ├── Theme tokens (CSS variables :root + .dark)
            ├── Vistas (Dashboard, Hotdesking, Reportes, etc.)
            ├── Componentes específicos (ExtEditPage, AgentLoginModal, etc.)
            └── Realtime client (socket.io) → window._tfSocket
```

**Sin build step**: Babel-standalone parsea `.jsx` en el browser. Trade-off: parse cost al primer load (~1-2s), pero deploys ultra rápidos (sin pipeline CI/CD). El JSX queda cacheado en el SW y entre page-loads no se re-parsea.

## Realtime hub (Node)

- Conecta al AMI vía `asterisk-manager`.
- Eventos consumidos: `newchannel`, `hangup`, `peerstatus`, `queuememberstatus`, `bridgeenter`, `dialbegin/end`, `agentcalled/complete/connect`.
- Mantiene estado en memoria (`state.peers`, `state.calls`, `state.queues`) + persistencia a MySQL `teleflow.calls_live`, `teleflow.agent_pauses`, `teleflow.agent_sessions`.
- Emite via socket.io en puerto `3001` con namespace `/teleflow-socket`:
  - `peer_update`, `call_update`, `queue_update`, `agent_login`, `agent_logout`, `agent_pause`, `agent_unpause`.
- Auto-snapshot RTSP: al recibir `newchannel` de una extensión con `rtsp_url` configurada en `ext_meta`, dispara `api/rtsp_snapshot.php?action=capture` con throttle de 30s.

## Endpoints HTTP (`api/`)

Ver [`api.md`](api.md) para el catálogo completo. Resumen por dominio:

- **Sesión**: `index.php?action=login`, `agent.php?action=login`
- **Data live**: `index.php?action=get_full_data` (single endpoint que retorna extensions + calls + queues + ringgroups + stats)
- **Agentes**: `agent.php`, `agent_commit.php`, `agent_pause_commit.php`, `agent_unpause_commit.php`, `agent_logout_commit.php`, `agents_crud.php`, `hotdesking.php`
- **Reportes**: `reports.php` (calls, agent_sessions, queue_detail, agent_detail), `reports_export.php` (PDF/Excel)
- **RTSP**: `rtsp_proxy.php` (RTSP→HLS via MediaMTX), `rtsp_snapshot.php` (capture/list/image)
- **Apertura DTMF**: `door_dtmf.php`
- **Atajos cola**: `queue_shortcuts.php`
- **Misc**: `recording.php`, `notify.php`, `server_time.php`

## Persistencia (DBs)

| Base | Server | Uso |
|------|--------|-----|
| `teleflow` | 10.1.1.7 MariaDB | Tablas propias: `ext_meta`, `agent_sessions`, `agent_pauses`, `agent_queue_pref`, `queue_shortcut`, `door_events`, `calls_live` |
| `asterisk` | 10.1.1.7 MariaDB | Config Issabel (read-only desde Teleflow): `queues_config`, `users`, `device`, `ringgroups`, `ivr_details`, `trunks` |
| `asteriskcdrdb` | 10.1.1.7 MariaDB | CDR + queue_log (read-only) |
| `call_center` | 10.1.1.7 MariaDB | `agent` (CRUD desde portal), `queue_call_entry` (read) |
| `acl_user` | SQLite local `/var/www/teleflow/db/acl.db` | Admins del portal (Horizon Login) |

## Decisiones técnicas

### ¿Por qué Babel-standalone en lugar de build step?

Decisión consciente: el target del equipo es alterar UI rápidamente sin pipeline. El parse cost se compensa con `assets/app.jsx` cacheado en el SW. A futuro, si el bundle pasa los 1.5 MB, evaluar Vite.

### ¿Por qué chan_sip en la PBX?

Issabel viene con chan_sip por compatibility. Asterisk 18+ ya marca chan_sip como deprecated pero sigue funcionando. **Cualquier llamada a AMI que cree members debe respetar el formato exacto del `Location`** (puede ser `SIP/N` para dynamic o `Local/N@from-queue/n` para static — Issabel mezcla). Ver [`pbx-integration.md`](pbx-integration.md).

### ¿Por qué SQLite para admins del portal?

Decisión histórica: separar ACL del portal (admin) de la PBX (agentes en MySQL `call_center.agent`). Permite que el portal funcione si la PBX está caída para gestión administrativa básica.

### ¿Por qué `index.php` se sirve vía PHP y no nginx estático?

Porque el `index.php` inyecta dinámicamente el cache version del SW en el `src=` del JSX:
```html
<script src="assets/app.jsx?v=<?php echo file_get_contents('sw.js')... ?>"></script>
```
