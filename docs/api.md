# API Reference — Teleflow Horizon

Todos los endpoints retornan JSON. La mayoría requieren sesión PHP válida (`$_SESSION['tf_user']` para admin o `$_SESSION['agent_user']` para agente). Si no hay sesión, retornan HTTP 401.

## Sesión

### `POST /api/index.php?action=login`
Body (form): `username`, `password`.
Retorna: `{status:'success', user:'admin'}` o HTTP 401.

### `POST /api/agent.php?action=login`
Body (form): `agent_number`, `password`, `callback_extension`.
Retorna: `{status:'success', agent:{...available_queues, pref_queues, pending_queue_selection:true}}` para el flujo de 2 pasos (paso 2 = `join_queues`).

### `POST /api/agent.php?action=join_queues`
Body (form): `queues=<csv de queue IDs>`.
Hace QueueAdd a las colas pedidas. Persiste prefs.
Retorna: `{status:'success', joined_queues:[...]}`.

### `POST /api/agent.php?action=logout`
Cierra sesión. Hace QueueRemove de todas las colas.

## Data live

### `GET /api/index.php?action=get_full_data`
Single endpoint que retorna el snapshot completo: extensions + live_calls + queues + ringgroups + ivrs + trunks + stats. **Polling cada 8s** desde el frontend.

### `GET /api/index.php?action=get_cdr&from=&to=&src=&dst=&disposition=&min_dur=&limit=&page=`
CDR paginado con filtros.

## Agentes / Hotdesking

### `POST /api/hotdesking.php?action=login_agent`
Body: `agent_number`, `extension`, `queues`. Login manual desde la UI admin.

### `POST /api/hotdesking.php?action=logout_agent`
Body: `agent_number`. Logout manual.

### `GET /api/hotdesking.php?action=list`
Lista de agentes con su estado (logueado, ext, colas).

### `GET /api/hotdesking.php?action=agent_queues`
Colas activas en el callcenter.

### `GET /api/hotdesking.php?action=get_agent_prefs&agent_number=`
Prefs guardadas del agente.

## CRUD de agentes

### `GET /api/agents_crud.php?action=list`
Lista de agentes desde `call_center.agent`.

### `POST /api/agents_crud.php?action=create`
Body: `type`, `number`, `name`, `password`.

### `POST /api/agents_crud.php?action=update`
Body: `id`, `name?`, `password?`, `estatus?` (A/I).

### `POST /api/agents_crud.php?action=delete`
Body: `id`. **Soft-delete** (estatus='I').

### `POST /api/agents_crud.php?action=hard_delete`
Body: `id`. Borrado real (cuidado con FKs de históricos).

## Atajos de cola (*7700*N)

### `GET /api/queue_shortcuts.php?action=list`
Lista de mapeos dígito→cola.

### `POST /api/queue_shortcuts.php?action=set`
Body: `digit` (1-9), `queue`, `label?`. Upsert.

### `POST /api/queue_shortcuts.php?action=delete`
Body: `digit`.

## Reportes

### `GET /api/reports.php?action=overview&from=&to=&sl_threshold=`
KPIs generales del callcenter.

### `GET /api/reports.php?action=by_agent&from=&to=`
Métricas por agente.

### `GET /api/reports.php?action=by_queue&from=&to=&sl_threshold=`
Métricas por cola.

### `GET /api/reports.php?action=calls&from=&to=&ext=&src=&dst=&disposition=&min_dur=&limit=`
Llamadas con filtros. Incluye `call_epoch` (UNIX_TIMESTAMP) para matching cross-TZ.

### `GET /api/reports.php?action=agent_sessions&from=&to=&agent=`
Sesiones de agentes. Incluye `login_epoch`.

### `GET /api/reports.php?action=agent_detail&agent=&from=&to=`
Detalle completo de un agente.

### `GET /api/reports.php?action=queue_detail&queue=&from=&to=&sl_threshold=`
Detalle completo de una cola.

### `GET /api/reports_export.php?type=calls&format=pdf|xlsx&from=&to=&ext=`
Export filtrado a PDF (TCPDF) o Excel (PhpSpreadsheet).

## RTSP

### `GET /api/rtsp_proxy.php?ext=<ext>`
Resuelve la URL HLS pública via MediaMTX para el stream RTSP de la extensión.
Retorna: `{status:'ok', hls_url:'http://10.1.1.192:8888/ext_N/index.m3u8'}`.

### `POST /api/rtsp_snapshot.php?action=capture`
Body: `ext`. Captura una imagen JPG del stream y la guarda en `uploads/rtsp_snapshots/<ext>/YYYYMMDD_HHMMSS.jpg`.
Retorna: `{status:'ok', filename, url, mtime}`.

### `GET /api/rtsp_snapshot.php?action=list&ext=<ext>`
Lista de capturas de los últimos 30 días.
Retorna: `{status:'ok', snapshots:[{filename, timestamp, mtime, size, url}, ...]}`.

### `GET /api/rtsp_snapshot.php?action=image&ext=&file=`
Sirve el JPG con cache headers.

## Apertura DTMF

### `POST /api/door_dtmf.php?action=log`
Body: `ext`, `dtmf`. Loggea el evento + auto-snapshot.
Retorna: `{ok:true, snapshot: 'YYYYMMDD_HHMMSS.jpg'}`.

### `GET /api/door_dtmf.php?action=list&ext=&limit=`
Eventos de apertura del interno.

## Misc

### `GET /api/server_time.php`
Hora del server (UTC + timezone). Usado por el `PbxClock` del topbar.
Retorna: `{ok:true, ts:<epoch ms>, iso, tz, host}`.

### `GET /api/index.php?action=sip_debug_ext&ext=<ext>`
Debug SIP en vivo de una extensión (peer info, channels activos, queue memberships). Cache 10s.

### `GET /api/recording.php?file=<filename>&format=mp3|wav`
Stream de grabación MixMonitor. Formato `mp3` recomendado (más compat que WAV 8kHz GSM).

### `POST /api/notify.php?event=&...`
Trigger interno usado por AGIs/commit handlers para notificar al hub realtime.

### `GET /api/index.php?action=detect_pbx`
Discovery del PBX (versión Asterisk, conteo de extensiones, etc.).

### `GET /api/index.php?action=set_ext_meta`
Upsert de `ext_meta` (rtsp_url, tipo, is_bocina, door_dtmf_code).
