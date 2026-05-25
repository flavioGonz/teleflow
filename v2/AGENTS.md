# HznFlow v2 — Developer Guide

> Documentación para devs y agentes IA que trabajen sobre este código.
> Si nunca tocaste el proyecto, leé esto primero.

---

## 🎯 ¿Qué es?

Panel web de control de PBX (central telefónica) para Horizon Seguridad. El equipo operativo recibe llamadas de **porteros eléctricos con video** instalados en edificios y autoriza/deniega el ingreso. La app:

- Muestra llamadas entrantes en tiempo real (con preview RTSP del videoportero)
- Asigna llamadas a operadores disponibles
- Gestiona agentes, colas, extensiones SIP
- Genera reportes y consulta el CDR (Call Detail Records)
- Permite spy/whisper/barge sobre llamadas activas

Es **una interfaz web encima de Asterisk PBX**. El motor de telefonía es Asterisk; esta app es la UI + lógica de negocio + datos propios.

---

## 🧱 Stack

| Capa | Tecnología | Por qué |
|---|---|---|
| Frontend | React 18 + Vite + TypeScript + Tailwind + **shadcn/ui** | UI moderna, componentes consistentes, build rápido |
| Backend | Node.js 22 + Express + TypeScript + **Prisma** | Mismo lenguaje que frontend, ORM con tipado, DX excelente |
| DB app | **PostgreSQL 16** | Auth, agentes propios, settings, metadata |
| DB PBX | MySQL en `10.1.1.7` (servidor Asterisk) | No la tocamos — la maneja FreePBX |
| Realtime | Socket.io + **AMI** (Asterisk Manager Interface) | Eventos en vivo del PBX |
| Auth | **JWT** (header `Authorization: Bearer`) | Stateless, sin sesiones server-side |
| Validación | **Zod** | Schemas runtime + tipos TS |
| Charts | **recharts** | KPIs y reportes |
| Flow editor | **reactflow** | IVR designer visual |
| Container | Docker + Docker Compose (OrbStack recomendado) | Setup consistente entre devs |

**NO hay un solo archivo `.js`** en código de app — todo es TS estricto.

---

## 📁 Estructura

```
v2/
├── AGENTS.md                ← este archivo
├── README.md                ← quick start para devs
├── Makefile                 ← comandos (make dev, make prod-deploy, etc.)
├── docker-compose.yml       ← stack base
├── docker-compose.dev.yml   ← override dev (hot-reload, volúmenes)
├── docker-compose.prod.yml  ← override prod (sin postgres local, healthchecks)
├── .env.example             ← plantilla dev (commiteable)
├── .env.prod.example        ← plantilla prod (commiteable)
├── .env                     ← dev real (gitignored)
├── .env.prod                ← prod real (gitignored, NUNCA commitear)
│
├── backend/                 ← Node + Express + Prisma
│   ├── Dockerfile           ← prod build (multi-stage)
│   ├── Dockerfile.dev       ← dev (tsx watch)
│   ├── package.json
│   ├── tsconfig.json
│   ├── prisma/
│   │   ├── schema.prisma           ← modelos de Postgres
│   │   ├── seed.ts                 ← seed manual (admin + defaults)
│   │   └── migrate-from-legacy.ts  ← importador MySQL legacy → Postgres
│   └── src/
│       ├── index.ts         ← Express entry point
│       ├── lib/
│       │   ├── env.ts       ← validación de env vars
│       │   ├── prisma.ts    ← cliente Prisma singleton
│       │   ├── auto-seed.ts ← seed automático en boot
│       │   └── asyncHandler.ts ← wrapper para async routes
│       ├── middleware/
│       │   └── auth.ts      ← requireAuth (JWT), signToken, requireRole
│       ├── services/
│       │   ├── asterisk-db.ts   ← pools mysql2 (asterisk + cdr) + graceful degradation
│       │   └── asterisk-ami.ts  ← cliente AMI (originate, hangup, spy)
│       └── routes/          ← 1 archivo por dominio
│           ├── auth.ts            POST /login /logout, GET /me
│           ├── dashboard.ts       GET counts agregados
│           ├── extensions.ts      CRUD extensiones SIP (MySQL Asterisk)
│           ├── agents.ts          CRUD agentes (Postgres)
│           ├── hotdesking.ts      Login/logout dinámico
│           ├── queues.ts          CRUD colas (MySQL Asterisk)
│           ├── groups.ts          CRUD ring groups
│           ├── calls.ts           Canales activos + spy/whisper/barge (AMI)
│           ├── cdr.ts             Historial llamadas (MySQL CDR)
│           ├── reports.ts         KPIs agregados
│           ├── recordings.ts      Stream de grabaciones
│           ├── ivr.ts             IVR flow (Postgres + FreePBX)
│           ├── settings.ts        AppSetting CRUD
│           └── ext-meta.ts        Metadata extensiones (RTSP, etc)
│
├── frontend/                ← React + Vite + TS + Tailwind
│   ├── Dockerfile           ← prod build (nginx)
│   ├── Dockerfile.dev       ← dev (vite HMR)
│   ├── nginx.conf           ← SPA + proxy /api
│   ├── package.json
│   ├── vite.config.ts       ← proxy /api → backend
│   ├── tailwind.config.js   ← tokens shadcn + verde Horizon
│   ├── tsconfig.json
│   ├── index.html
│   ├── public/
│   │   └── horizon-icon.svg ← logo
│   └── src/
│       ├── main.tsx         ← React root
│       ├── App.tsx          ← router (React Router 7)
│       ├── index.css        ← Tailwind + CSS vars (light/dark)
│       ├── vite-env.d.ts    ← types env vars
│       ├── lib/
│       │   ├── api.ts       ← axios client + interceptor JWT
│       │   ├── socket.ts    ← Socket.io singleton
│       │   └── utils.ts     ← cn() helper (shadcn)
│       ├── hooks/
│       │   └── useSocket.ts ← useSocketEvent, useSocketStatus
│       ├── contexts/
│       │   ├── AuthContext.tsx   ← provider de user actual + login/logout
│       │   └── ThemeContext.tsx  ← light/dark toggle
│       ├── components/
│       │   ├── Layout.tsx        ← topbar + outlet
│       │   └── ui/               ← shadcn primitives
│       │       ├── button.tsx
│       │       ├── card.tsx
│       │       ├── input.tsx
│       │       ├── label.tsx
│       │       ├── table.tsx
│       │       ├── badge.tsx
│       │       ├── dialog.tsx
│       │       ├── dropdown-menu.tsx
│       │       └── separator.tsx
│       └── pages/                ← 1 archivo por ruta
│           ├── Login.tsx
│           ├── Dashboard.tsx
│           ├── Extensiones.tsx
│           ├── Agentes.tsx
│           ├── Hotdesking.tsx
│           ├── Colas.tsx
│           ├── Grupos.tsx
│           ├── Llamadas.tsx
│           ├── CDR.tsx
│           ├── Reportes.tsx       (recharts)
│           ├── IVR.tsx            (reactflow)
│           └── Configuracion.tsx
│
└── realtime/                ← Node service: AMI ↔ Socket.io bridge
    ├── Dockerfile
    ├── package.json
    ├── index.js             ← único archivo
    └── README.md
```

---

## 🚀 Quick start

**Requisito único**: Docker corriendo (OrbStack recomendado).

```bash
git clone <repo>
cd v2
make dev
```

Eso es todo. Tarda ~2 min la primera vez (build de imágenes).

| URL | Servicio |
|---|---|
| http://localhost:3000 | Frontend (React) |
| http://localhost:4000/api/health | Backend |
| http://localhost:3001 | Realtime (Socket.io) |
| `postgresql://hznflow:hznflow_dev_pass@localhost:5432/hznflow` | DB |

**Login default**: `admin` / `RK-ARrSKsefUOqyszL-p`

### Sin Docker (raw npm)

```bash
# Requisitos: Node 22+, PostgreSQL 16, MySQL Asterisk accesible

# Backend
cd backend && npm install
npx prisma db push && npx tsx prisma/seed.ts
npm run dev   # :4000

# Frontend (otra terminal)
cd frontend && npm install && npm run dev   # :3000

# Realtime (otra terminal)
cd realtime && npm install
AMI_HOST=10.1.1.7 AMI_USER=... AMI_PASS=... DB_HOST=10.1.1.7 ... node index.js
```

---

## 🗄️ Bases de datos

### PostgreSQL local (`hznflow`)

Toda la **data propia de la app**. Schema en `backend/prisma/schema.prisma`. Modelos:

| Modelo | Para qué |
|---|---|
| `User` | Auth de admins/supervisores (passwordHash con bcrypt) |
| `Agent` | Agentes (metadata: nombre, depto, equipo, contacto) |
| `AgentSession` | Sesiones de hotdesking (login/logout en extensiones) |
| `AgentPause` | Pausas (con tipo: BREAK, LUNCH, etc) |
| `AgentQueuePref` | Preferencias de colas por agente |
| `PauseType` | Catálogo de tipos de pausa |
| `QueueEvent` | Eventos de colas (join, leave, abandon, connect, complete) |
| `QueueCallEntry` | Tracking de cada llamada por cola |
| `ExtMeta` | Metadata de extensiones (RTSP URL, tipo, DTMF de puerta, isBocina) |
| `AppSetting` | Settings key-value (branding, etc) |
| `PbxConfig` | Overrides de config del PBX |

**Comandos Prisma**:
```bash
cd backend
npx prisma db push          # aplicar schema (dev — DESTRUCTIVO si hay drift)
npx prisma migrate dev      # crear migración versionada (recomendado prod)
npx prisma studio           # UI web para explorar la DB
npx tsx prisma/seed.ts      # re-correr seed manual
```

### MySQL Asterisk (`10.1.1.7`, NO la tocamos directamente)

| DB | Para qué | La maneja |
|---|---|---|
| `asterisk` | Extensiones SIP (devices, users, sip), colas (queues_config, queues_details), ring groups, IVRs | FreePBX |
| `asteriskcdrdb` | CDR — log de cada llamada | Asterisk lo escribe automáticamente |

Acceso desde `backend/src/services/asterisk-db.ts` con 2 pools `mysql2`. **Tiene graceful degradation**: si el host no responde, las queries tiran `AsteriskUnavailableError` y el handler global devuelve `503` con `code: ASTERISK_UNAVAILABLE` — el frontend muestra un warning amarillo en vez de pantalla rota.

### Migración del legacy

Si la app vieja (PHP) tiene data útil:

```bash
cd backend
MIGR_MYSQL_HOST=10.1.1.7 \
MIGR_MYSQL_USER=tfremote \
MIGR_MYSQL_PASS=xxx \
MIGR_MYSQL_DB=teleflow \
MIGR_SQLITE_PATH=/var/www/teleflow/db/acl.db \
npx tsx prisma/migrate-from-legacy.ts
```

Idempotente. Importa: `agents`, `agent_sessions`, `agent_pauses`, `pause_types`, `queue_events`, `ext_meta`, `app_settings`, admins de SQLite ACL.

---

## 🔐 Autenticación

Flow JWT clásico:

1. Cliente: `POST /api/auth/login` con `{username, password}` → recibe `{token, user}`
2. Cliente: guarda `token` en `localStorage.hznflow_token`
3. Cada request lleva header `Authorization: Bearer <token>`
4. Backend: `requireAuth` middleware valida → setea `req.user`
5. Logout: cliente borra el token (no hay invalidación server-side; si hace falta, agregar blacklist)

Roles: `admin | supervisor | operator`. Usar `requireRole('admin')` en routes que lo necesiten.

**Hashing**: bcrypt con cost 10. Password default del admin: `RK-ARrSKsefUOqyszL-p` (cambialo en prod).

---

## 📡 Realtime

Servicio aparte (`realtime/`) — Node + Socket.io + AMI client.

- Se conecta al AMI de Asterisk (`10.1.1.7:5038`)
- Escucha eventos: `Newchannel`, `Hangup`, `QueueCallerJoin`, etc.
- Los normaliza y los broadcast por Socket.io en `path: /teleflow-socket`
- También persiste eventos críticos en MySQL `teleflow.queue_events` (legacy — eventualmente se podría duplicar a Postgres)

**Eventos que emite** (consumidos por el frontend):

| Evento | Payload |
|---|---|
| `peer_update` | `{peer, status}` (estado SIP cambió) |
| `call_event` | `{type: 'new'\|'hangup', channel, ext, call}` |
| `queue_update` | `{queue, type: 'join'\|'leave'\|'abandon'\|'connect'}` |
| `agent_logout` | `{agent}` |
| `agent_pause` | `{agent, paused}` |

**Cómo consumir en una página React**:
```tsx
import { useSocketEvent } from '../hooks/useSocket';

function MiPagina() {
  useSocketEvent('call_event', (ev) => {
    console.log('nueva llamada', ev);
    refetchData();
  });
}
```

---

## 🛣️ Routing

### Frontend
React Router 7. Todas las rutas viven en `App.tsx` dentro del `<Layout />` (con auth guard). Para agregar una página:

1. Crear `frontend/src/pages/MiPagina.tsx`
2. Agregar import + `<Route>` en `App.tsx`
3. Agregar entrada en `nav[]` de `components/Layout.tsx`

### Backend
Express convencional. Para agregar un endpoint:

1. Crear o editar `backend/src/routes/midominio.ts`:
   ```ts
   import { Router } from 'express';
   import { requireAuth } from '../middleware/auth.js';
   import { asyncHandler } from '../lib/asyncHandler.js';
   import { prisma } from '../lib/prisma.js';

   const router = Router();
   router.use(requireAuth);

   router.get('/', asyncHandler(async (req, res) => {
     const data = await prisma.miModel.findMany();
     res.json({ data });
   }));

   export default router;
   ```
2. Registrarlo en `backend/src/index.ts`:
   ```ts
   import miRoutes from './routes/midominio.js';
   app.use('/api/midominio', miRoutes);
   ```

**Convenciones**:
- Siempre `asyncHandler` en handlers async (centraliza error handling)
- Validar input con Zod (`z.object({...}).safeParse(req.body)`)
- Para queries a Postgres: usar Prisma (`prisma.X.findMany`)
- Para queries a MySQL Asterisk: usar `aq()` / `cdrq()` de `services/asterisk-db.ts`
- Para acciones del PBX: `amiAction({...})` de `services/asterisk-ami.ts`

---

## 🔒 Seguridad de endpoints — REGLA INMUTABLE

> **Todo endpoint nuevo va PROTEGIDO con `requireAuth` por defecto.**
> Hacer un endpoint público requiere **autorización explícita del owner del proyecto** + **justificación documentada** en el código.

### Reglas

1. **Default = protegido.** Mounta el router con `router.use(requireAuth);` al inicio, antes de cualquier `router.get/post/...`. O usá `requireAuth` como middleware por endpoint.
2. **Filtrado por rol** cuando aplique: `requireRole('admin')` para endpoints sensibles (settings globales, gestión de usuarios, info de infraestructura).
3. **Nunca exponer info de infraestructura** sin auth (creds, hosts internos, mensajes de error con detalles del servidor, paths del filesystem, configs del PBX).
4. **Devolver lo mínimo necesario** por rol — usuarios no-admin no deberían ver detalles internos. Ejemplo del `/api/health/ami`: usuarios autenticados ven `status`, solo admins ven `message` y `hint`.
5. **No usar 404 para revelar existencia**. Si un agente normal pide un recurso de otro tenant/admin, devolver `403`, no `404` con info.
6. **Auditar endpoints públicos periódicamente**:
   ```bash
   grep -rn "app\.\(get\|post\|put\|delete\)" backend/src/index.ts
   grep -rn "router\.\(get\|post\|put\|delete\)" backend/src/routes/ | grep -v requireAuth
   ```

### Endpoints públicos actuales (auditados y justificados)

| Endpoint | Por qué público | Riesgo |
|---|---|---|
| `GET /api/health` | Estándar de health checks (load balancers, monitoring). Solo devuelve `{ok, env}`. | Bajo |
| `POST /api/auth/login` | Endpoint de autenticación — debe ser accesible sin token | Bajo (requiere credenciales) |

> **`GET /api/auth/avatar/:userId` está PROTEGIDO con `requireAuth`.** El frontend lo consume vía `fetch` con header JWT → `Blob` → `URL.createObjectURL` en `components/UserAvatar.tsx`. No usar `<img src="/api/auth/avatar/X">` directo — no llevaría el token.

### Antes de agregar un endpoint público

1. **STOP**. Pregúntele al owner del proyecto.
2. Si se autoriza, agregar comentario en el código:
   ```ts
   /**
    * PUBLIC ENDPOINT — autorizado por <owner>, <fecha>.
    * Razón: <justificación clara>
    * Mitigación: <rate limiting / data mínima / etc>
    */
   app.get('/api/public/x', ...);
   ```
3. Agregar fila a la tabla de "Endpoints públicos auditados" en este archivo.

---

## 🎨 UI / Componentes

### shadcn/ui
NO usamos el CLI `npx shadcn-ui add`. Los componentes están copiados directo en `frontend/src/components/ui/`. Si necesitás uno nuevo (Toast, Tabs, Select, etc.), buscalo en https://ui.shadcn.com/docs/components y pegalo ahí adaptando los imports.

### Componentes disponibles
`Button, Card, Input, Label, Table, Badge, Dialog, DropdownMenu, Separator`.

### Tokens de color
Definidos en `frontend/src/index.css` como CSS vars HSL. Light/dark mode via `.dark` class en `<html>`.

**Color principal**: verde Horizon `#4eb857`. **NO uses colores hardcoded** — usá las clases Tailwind (`text-primary`, `bg-card`, `border-border`, etc.) o las CSS vars.

### Estructura de una página típica

```tsx
import { useEffect, useState } from 'react';
import { MiIcono } from 'lucide-react';
import { api } from '../lib/api';
import { Card, CardContent } from '../components/ui/card';

export default function MiPagina() {
  const [data, setData] = useState(null);
  useEffect(() => { api.get('/mi-endpoint').then(r => setData(r.data)); }, []);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2">
        <MiIcono className="h-6 w-6 text-primary" />
        Título
      </h1>
      <Card>
        <CardContent className="p-5">
          {/* contenido */}
        </CardContent>
      </Card>
    </div>
  );
}
```

---

## 🛠️ Comandos del Makefile

```bash
make             # ayuda
make dev         # stack completo con hot-reload (foreground)
make dev-d       # igual pero detached
make down        # apagar
make logs        # tail logs de todos los servicios
make logs-backend
make shell-backend       # bash dentro del container backend
make shell-db            # psql interactivo
make seed                # re-correr seed
make migrate-from-mysql  # importar legacy
make reset               # ⚠ DESTRUCTIVO: borra DB y rearranca

make prod-local          # smoke test del build de prod (con postgres local)
make prod-deploy         # deploy real (requiere .env.prod completo)
make prod-logs
make prod-down
make prod-migrate        # aplicar prisma migrate deploy
```

---

## 🚢 Deploy a producción

Ver README.md para el flow completo. Resumen:

1. Crear `.env.prod` desde `.env.prod.example`
2. Generar JWT secret fuerte: `openssl rand -base64 64`
3. Apuntar `DATABASE_URL` a Postgres managed externo (Supabase/Neon/RDS)
4. (Primera vez) Generar migraciones versionadas: `cd backend && npx prisma migrate dev --name init`
5. `make prod-deploy`

**Diferencias dev vs prod**:
- Prod usa Postgres managed externo, no el dockerizado
- Prod hace `prisma migrate deploy` (versionado), no `db push --accept-data-loss`
- Prod tiene healthchecks, restart policies, resource limits
- Prod valida que `JWT_SECRET` y `DATABASE_URL` no estén vacíos

---

## 🐛 Troubleshooting

| Síntoma | Causa probable | Fix |
|---|---|---|
| `Cannot connect to Docker daemon` | OrbStack/Docker Desktop no está corriendo | Abrir la app |
| Backend tira `ASTERISK_UNAVAILABLE` en `/api/extensions` | Sin acceso a `10.1.1.7` (VPN caída) | Conectar VPN. La app sigue funcionando, solo los endpoints PBX devuelven 503 |
| Frontend en blanco después de cambiar schema | Frontend cacheado | `Cmd+Shift+R` (hard reload) o `make down && make dev` |
| Migraciones no se aplican | `prisma db push` falló por drift | `make reset` (DESTRUCTIVO) o `npx prisma db push --accept-data-loss` |
| `client.off is not a function` (AMI) | La lib `asterisk-manager` usa `removeListener` | Usar `removeListener` no `off` |
| `[object Object]` en localStorage | Guardaste un objeto sin serializar | Siempre `JSON.stringify` al guardar |
| Vite arranca en `:5173` en vez de `:3000` | `npx vite` agarró versión global | Usar `npm run dev` o `./node_modules/.bin/vite` |

---

## 🧠 Glosario PBX (palabras que vas a ver en el código)

- **PJSIP / SIP**: protocolo de VoIP. Cada teléfono es una "extensión SIP".
- **Extensión** (ext): número interno (ej. `2005`). Asociado a un device SIP.
- **Cola (queue)**: grupo de extensiones que reciben llamadas en orden / round-robin.
- **Ring group**: varias extensiones suenan a la vez.
- **CDR**: Call Detail Record. Un log por cada llamada (origen, destino, duración, disposición).
- **AMI**: Asterisk Manager Interface. API TCP texto plano para mandarle órdenes a Asterisk.
- **ARI**: Asterisk REST Interface (más moderna que AMI; no la usamos mucho).
- **AGI**: Asterisk Gateway Interface. Scripts ejecutados durante una llamada (lado servidor).
- **Hotdesking**: un agente se loguea en cualquier extensión física; el sistema lo agrega como member dinámico de las queues correspondientes.
- **ChanSpy**: escuchar una llamada sin que se enteren.
- **Whisper**: hablarle solo al agente durante una llamada.
- **Barge**: meterse en la llamada como tercero.
- **Disposition**: cómo terminó la llamada (`ANSWERED`, `NO ANSWER`, `BUSY`, `FAILED`).
- **Bocina**: parlante/bocina del videoportero (campo `isBocina` en ExtMeta).
- **Videoportero / portero eléctrico**: dispositivo en la entrada del edificio que llama a la portería con video + audio.

---

## 📝 Convenciones de código

- **Idioma**: comentarios y mensajes UI en **español**; código (funciones, variables) en **inglés**.
- **Imports**: relativos con `.js` al final (porque TS + ESM lo requiere para Node).
- **Async**: siempre `async/await`, nunca `.then` en handlers de Express.
- **Errors**: tirar excepciones, dejar que el error handler global las capture (asyncHandler).
- **Validation**: Zod en boundary (req.body), tipos TS internos.
- **Naming**:
  - Componentes React: `PascalCase` (`MiPagina.tsx`)
  - Hooks: `useNombre` (`useSocket.ts`)
  - Servicios: `kebab-case.ts` (`asterisk-db.ts`)
  - Routes: domain singular (`agents.ts`, `cdr.ts`)

---

## 🤖 Para agentes IA

Si sos un agente trabajando en este código:

1. **Leé este archivo primero** — te da el mapa mental completo.
2. **🔒 SEGURIDAD — leé también la sección "Seguridad de endpoints" arriba.** Todo endpoint nuevo va con `requireAuth`. Hacerlo público requiere autorización explícita del owner.
3. **Antes de cambiar el schema de Postgres**, mirá `backend/prisma/schema.prisma` y entendé las relaciones.
4. **Antes de tocar un route**, fijate si ya hay un servicio (`services/`) que hace lo que necesitás.
5. **No inventés colores hardcoded** — usá tokens shadcn (`text-primary`, etc.).
6. **No agregues archivos `.js`** — todo es TypeScript estricto.
7. **Si tocás MySQL Asterisk**, recordá que las queries pueden fallar con `AsteriskUnavailableError`. Wrappealas con `try/catch` o dejá que el handler global devuelva 503.
8. **Si agregás dependencias**, justificá la razón en el commit. Preferí libs livianas y populares.
9. **No commitees** `.env`, `.env.prod`, `node_modules/`, `dist/`, archivos temporales.
10. **Si rompés algo**, revisá `tsc --noEmit` en backend y frontend antes de dar por terminado.
11. **No expongas info de infraestructura** (creds, hosts, paths, configs del PBX) ni siquiera a usuarios autenticados sin rol admin.

---

## 📚 Más documentación

- **README.md** — quick start y comandos
- **backend/prisma/schema.prisma** — modelos con comentarios
- **realtime/README.md** — detalle del servicio realtime
- Cada archivo de route tiene un comentario header explicando qué hace
