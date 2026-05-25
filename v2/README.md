# HznFlow v2

Panel web de control de PBX para Horizon Seguridad. Operadores reciben llamadas de porteros eléctricos con video y autorizan/deniegan ingresos.

**Stack:** React 18 + Vite + TypeScript + shadcn/ui (frontend) · Express + Prisma + TypeScript (backend) · PostgreSQL 16 (storage) · Socket.io + AMI (realtime) · Docker (deploy).

> Para guía profunda de arquitectura y convenciones, ver [AGENTS.md](AGENTS.md).

---

## 🚀 Quick start

**Pre-requisito único:** Docker corriendo — [OrbStack](https://orbstack.dev/) (recomendado, gratis para uso interno) ó [Docker Desktop](https://www.docker.com/products/docker-desktop/). **No necesitás** Node, Postgres ni MySQL instalados localmente.

```bash
git clone <repo>
cd <repo>
make dev
```

Tarda ~2 min la primera vez (build de imágenes). Después es instantáneo.

| Servicio | URL |
|---|---|
| Frontend (React) | http://localhost:3000 |
| Backend (API) | http://localhost:4000/api/health |
| Realtime (Socket.io) | http://localhost:3001/teleflow-socket/ |
| PostgreSQL | `postgresql://hznflow:hznflow_dev_pass@localhost:5432/hznflow` |

**Login default** (autocreado en el primer arranque):
- Usuario: `admin`
- Password: `RK-ARrSKsefUOqyszL-p`

---

## 🛠️ Comandos (`make help`)

```bash
make             # ayuda con todos los comandos

# Desarrollo
make dev         # stack completo con hot-reload (foreground)
make dev-d       # igual pero detached (background)
make down        # apagar todo
make logs        # tail logs de todos los servicios
make logs-backend
make shell-backend       # bash dentro del container backend
make shell-db            # psql interactivo en Postgres
make seed                # re-correr seed manual
make migrate-from-mysql  # importar data del MySQL legacy
make reset               # ⚠ DESTRUCTIVO: borra DB y rearranca

# Producción
make prod-local          # smoke test del build de prod (postgres local)
make prod-deploy         # deploy real (requiere .env.prod completo)
make prod-logs
make prod-down
make prod-migrate        # aplicar prisma migrate deploy

make clean               # detiene y borra imágenes locales
```

---

## 📁 Estructura

```
.
├── AGENTS.md                ← guía profunda (arquitectura, convenciones, glosario PBX)
├── README.md                ← este archivo (quick start)
├── Makefile                 ← comandos
├── docker-compose.yml       ← stack base
├── docker-compose.dev.yml   ← override dev (hot-reload, volúmenes)
├── docker-compose.prod.yml  ← override prod (sin postgres local, healthchecks)
├── .env.example             ← plantilla dev
├── .env.prod.example        ← plantilla prod
│
├── backend/                 ← Node + Express + Prisma + TypeScript
│   ├── prisma/
│   │   ├── schema.prisma           ← modelos de Postgres
│   │   ├── seed.ts                 ← seed manual
│   │   └── migrate-from-legacy.ts  ← importador MySQL → Postgres
│   ├── src/
│   │   ├── index.ts                ← entry point Express
│   │   ├── lib/                    ← prisma client, env, auto-seed, helpers
│   │   ├── middleware/             ← JWT auth
│   │   ├── services/               ← AMI + MySQL Asterisk (graceful degradation)
│   │   └── routes/                 ← 14 endpoints (auth, agents, calls, cdr...)
│   ├── Dockerfile                  ← prod (multi-stage)
│   └── Dockerfile.dev              ← dev (tsx watch)
│
├── frontend/                ← React + Vite + TypeScript + Tailwind + shadcn/ui
│   ├── public/horizon-icon.svg
│   ├── src/
│   │   ├── App.tsx                 ← router (React Router 7)
│   │   ├── main.tsx                ← React root
│   │   ├── index.css               ← Tailwind + CSS vars (light/dark)
│   │   ├── lib/                    ← api (axios+JWT), socket (Socket.io), utils
│   │   ├── hooks/useSocket.ts      ← useSocketEvent, useSocketStatus
│   │   ├── contexts/               ← AuthContext, ThemeContext
│   │   ├── components/
│   │   │   ├── Layout.tsx          ← topbar + outlet
│   │   │   └── ui/                 ← shadcn primitives (Button, Card, Table, Dialog...)
│   │   └── pages/                  ← 12 páginas (Login, Dashboard, Agentes...)
│   ├── nginx.conf                  ← SPA fallback + proxy /api
│   ├── Dockerfile                  ← prod (build estático + nginx)
│   └── Dockerfile.dev              ← dev (Vite HMR)
│
└── realtime/                ← Node + Socket.io + AMI bridge
    ├── index.js                    ← AMI events → Socket.io broadcast
    └── Dockerfile
```

---

## 🔌 Acceso a Asterisk (MySQL + AMI)

El backend y el realtime necesitan alcanzar el MySQL de Asterisk (`10.1.1.7:3306`) y el AMI (`10.1.1.7:5038`) para mostrar extensiones, colas, llamadas en vivo, CDR, etc.

- **Con VPN de Horizon conectada**: funciona automáticamente (default).
- **Sin VPN**: la app **no se rompe** — devuelve `503 ASTERISK_UNAVAILABLE` solo en endpoints PBX, y el frontend muestra un warning amarillo en esa página. Auth, agentes, settings, branding y hotdesking funcionan igual (viven en Postgres).

Para overridear credenciales, editá `.env` (se crea automáticamente desde `.env.example` cuando corrés `make dev`).

---

## 🌐 API

Todos los endpoints viven bajo `/api/*`. Auth con JWT (header `Authorization: Bearer <token>`).

| Método | Ruta | Descripción | Dependencia |
|---|---|---|---|
| POST | `/api/auth/login` | Login → `{token, user}` | Postgres |
| POST | `/api/auth/logout` | Logout (no-op server) | — |
| GET | `/api/auth/me` | Datos del usuario | Postgres |
| GET | `/api/dashboard` | Counts agregados | Postgres + Asterisk |
| GET/POST/PUT/DEL | `/api/extensions` | CRUD extensiones SIP | Asterisk |
| GET/POST/PUT/DEL | `/api/agents` | CRUD agentes | Postgres |
| GET/POST | `/api/hotdesking` | Sesiones activas + login/logout dinámico | Postgres + AMI |
| POST | `/api/hotdesking/pause` `/unpause` | Control pausas | Postgres |
| GET/POST/PUT/DEL | `/api/queues` | CRUD colas | Asterisk |
| GET/POST/PUT/DEL | `/api/groups` | CRUD ring groups | Asterisk |
| GET | `/api/calls/active` | Canales activos vía AMI | AMI |
| POST | `/api/calls/spy` `/hangup` `/originate` | Acciones AMI | AMI |
| GET | `/api/cdr` | Historial llamadas (con filtros) | Asterisk CDR |
| GET | `/api/reports/summary` `/by-agent` `/by-queue` | KPIs agregados | Postgres + Asterisk |
| GET/PUT | `/api/settings` | App settings (branding, etc) | Postgres |
| GET/PUT/DEL | `/api/ext-meta/:ext` | Metadata extensiones (RTSP, tipo, DTMF) | Postgres |
| GET/PUT | `/api/ivr` `/ivr/flow` | IVR flow visual | Postgres + Asterisk |
| GET | `/api/recordings/file` | Stream de grabación | Filesystem PBX |

### Realtime (Socket.io)

Conexión: `http://localhost:3001` path `/teleflow-socket`.

| Evento | Payload | Significado |
|---|---|---|
| `peer_update` | `{peer, status}` | Estado SIP cambió |
| `call_event` | `{type: 'new'\|'hangup', channel, ext, call}` | Nueva llamada / hangup |
| `queue_update` | `{queue, type: 'join'\|'leave'\|'abandon'\|'connect'}` | Cola |
| `agent_logout` | `{agent}` | Agente cerró sesión |
| `agent_pause` | `{agent, paused}` | Agente pausó / despausó |

---

## 🗄️ Bases de datos

| DB | Tipo | Para qué | Quien la maneja |
|---|---|---|---|
| `hznflow` (Postgres local) | PostgreSQL 16 | Auth, agentes propios, sesiones, pausas, ext_meta, settings | Esta app (Prisma) |
| `asterisk` (MySQL en 10.1.1.7) | MySQL | Extensiones SIP, colas, ring groups, IVRs | FreePBX |
| `asteriskcdrdb` (MySQL en 10.1.1.7) | MySQL | CDR — log de llamadas | Asterisk |

Schema Prisma en [`backend/prisma/schema.prisma`](backend/prisma/schema.prisma). Modelos: `User`, `Agent`, `AgentSession`, `AgentPause`, `AgentQueuePref`, `PauseType`, `QueueEvent`, `QueueCallEntry`, `ExtMeta`, `AppSetting`, `PbxConfig`.

### Importar data del legacy

```bash
MIGR_MYSQL_HOST=10.1.1.7 \
MIGR_MYSQL_USER=tfremote \
MIGR_MYSQL_PASS=xxx \
MIGR_MYSQL_DB=teleflow \
make migrate-from-mysql
```

Idempotente. Importa agentes, sesiones, pausas, ext_meta, app_settings, queue_events del MySQL legacy de la app vieja.

---

## 🚢 Producción

### Opción A — Todo en un host con Docker (recomendado para empezar)

1. **Crear `.env.prod`**:
   ```bash
   cp .env.prod.example .env.prod
   nano .env.prod
   ```
2. **Generar JWT secret fuerte**:
   ```bash
   openssl rand -base64 64
   # pegar el resultado en JWT_SECRET del .env.prod
   ```
3. **Apuntar `DATABASE_URL`** a un Postgres managed externo (Supabase, Neon, RDS) o uno selfhosted aparte.
4. **(Primera vez) Generar migraciones versionadas**:
   ```bash
   cd backend && npx prisma migrate dev --name init
   git add prisma/migrations/ && git commit -m "chore: initial Prisma migrations"
   ```
5. **Deploy**:
   ```bash
   make prod-deploy
   ```
   El comando valida que `JWT_SECRET` y `DATABASE_URL` no estén vacíos antes de arrancar.

### Opción B — Servicios separados

| Servicio | Hosting sugerido |
|---|---|
| Frontend (estático) | Vercel / Netlify / S3 + CloudFront / nginx propio |
| Backend (Node) | Railway / Fly.io / VPS con Docker |
| Realtime (Node) | Mismo host que backend (necesita acceso AMI) |
| PostgreSQL | Supabase / Neon / RDS / EC2 propio |

Para frontend separado, setear `VITE_API_BASE` en build apuntando al dominio del backend.

### Diferencias dev vs prod

| | Dev | Prod |
|---|---|---|
| Postgres | Container con volumen local | Managed externo |
| JWT_SECRET | Default débil OK | Obligatorio (validado en deploy) |
| DATABASE_URL | Hardcoded al postgres del compose | Managed externo |
| CORS_ORIGIN | `http://localhost:3000` | Dominio real |
| NODE_ENV | development | production |
| Volúmenes código | Montados (hot-reload) | Imagen inmutable |
| Healthchecks | No | Sí (cada 30s) |
| Restart policy | `unless-stopped` | `always` |
| Resource limits | No | Sí (CPU/mem) |
| Migraciones Prisma | `db push --accept-data-loss` | `migrate deploy` versionadas |
| Logging | stdout | json-file rotado (10MB × 5) |

---

## 🐛 Troubleshooting

| Síntoma | Fix |
|---|---|
| `make dev` falla "Cannot connect to Docker daemon" | Abrir OrbStack / Docker Desktop |
| Frontend carga pero `/api/health` da error | Postgres tardó en arrancar (healthcheck). Esperá 10s y refrescá, o `make logs-backend` |
| Backend tira `ASTERISK_UNAVAILABLE` | Sin acceso a 10.1.1.7. Conectar VPN. El resto de la app funciona igual |
| Cambié `schema.prisma` y no se aplica | `make down && make dev` (el backend en dev corre `prisma db push` al arrancar) |
| Quiero borrar todo y empezar limpio | `make reset` (⚠ borra la DB Postgres local) |
| Vite arranca en `:5173` en vez de `:3000` | Estás corriendo `npx vite` global. Usá `npm run dev` o `./node_modules/.bin/vite` |
| Cambio en código no se refleja | El hot-reload está dentro de Docker. Si no detecta cambios, `make down && make dev` |

---

## 📚 Documentación adicional

- **[AGENTS.md](AGENTS.md)** — guía profunda: arquitectura, cómo agregar features, convenciones de código, glosario PBX, guidelines para agentes IA
- **[backend/prisma/schema.prisma](backend/prisma/schema.prisma)** — modelos de DB con comentarios
- **[realtime/README.md](realtime/README.md)** — detalle del servicio realtime
- Cada `backend/src/routes/*.ts` tiene comentario header con qué hace
