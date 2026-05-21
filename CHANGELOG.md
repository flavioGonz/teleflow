# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).
Los releases se nombran por semántica del cambio, no por SemVer estricto (todavía).

## [Unreleased]

## [v7.5] - 2026-05-20

### Added
- Atajos `*7700*N` para login a colas mapeadas vía dígito único. Soporta combinaciones `*7700*N*M*K` para multi-cola.
- Tabla MySQL `queue_shortcut` con CRUD desde UI **Configuración → Atajos \*7700**.
- Endpoint `api/queue_shortcuts.php` (list/set/delete).
- Reorganización del repo: `docs/`, `.github/`, README corto, CONTRIBUTING.md, este CHANGELOG.md.

## [v7.4] - 2026-05-14

### Added
- PWA polish completo: Service Worker v8 con strategies por tipo (network-first HTML, cache-first JSX/imágenes, SWR CDNs).
- Manifest con paleta Horizon, 4 shortcuts, display_override.
- `offline.html` elegante con auto-reload al volver online.
- Theme color por preferencia del sistema (light/dark).

### Fixed
- **Bug crítico**: el `<head>` desregistraba el SW y borraba todos los caches en cada page load (legacy de debug). Eliminado.

## [v7.3] - 2026-05-13

### Added
- Bundle reducido 93%: `index.php` 1MB → 70KB extrayendo Babel JSX a `assets/app.jsx` cacheable.
- Cache versioning con query param `?v=` inyectado desde `sw.js`.

### Fixed
- Escape `</script>` en template literal de `openFullscreen` (rompía parser Babel).
- Timezone: `date_default_timezone_set('America/Montevideo')` en `config.php`. Queries CDR devuelven `UNIX_TIMESTAMP` para matchear snapshots sin ambigüedad de TZ.

## [v7.2] - 2026-05-12

### Added
- Rediseño completo `ViewCallCenter` (panel del agente) 100% shadcn: hero compacto, live call destacada, KPIs, colas asignadas, historial del día.
- `AgentQueueSelectModal`: paso 2 del login del agente para elegir colas.
- `RtspMiniLive` / `RtspMiniLiveFill`: mini video en filas de llamadas activas del Dashboard.
- `PbxClock`: reloj live del PBX en el topbar.
- Tab **Historial de aperturas** en ficha del interno (foto + actor + DTMF).
- Auto-snapshot RTSP al disparar DTMF de apertura.

### Changed
- Llamadas activas en Dashboard ahora son cards horizontales con scroll-x (3 visibles).
- Pausas: removido "Almuerzo", `BREAK` → "Descanso".

## [v7.1] - 2026-05-11

### Added
- Hotdesking en grid 2x2.
- Wallboard con bloques compartimentados.
- Modo Kiosko fullscreen para colas.

### Fixed
- QueuePause/QueueRemove ahora usa `Location` del QueueStatus (soporta tanto `SIP/N` dynamic como `Local/N@from-queue/n` static de Issabel).

## [v7.0] - 2026-05-11

### Added
- Ficha del interno v7: 4 tabs (Datos / Historial llamadas / Historial agentes / Debug SIP).
- Toggle Bocina IP en categoría Cliente.
- Tab Debug SIP refinado para chan_sip (cache 10s).
- Export PDF/Excel del historial de llamadas filtrado por extensión.

## [v6.5] - 2026-05-10

### Changed
- Paleta brand: `--primary #11B328` (Horizon green), `--foreground #1A1A1A`, `--secondary #E6E7E8`.
- Sweep total: 0 hardcoded purples/violets.
- Todas las vistas migradas a primitives shadcn.

## [v6.0] - 2026-05-09

### Added
- Reproductor de audio wavesurfer.js v7 para grabaciones (con fallback nativo).
- Miniatura RTSP (±5min) en cada fila del Historial de llamadas.
- Helper global `tfPlayRecording(file, meta)`.

## [v5.5] - 2026-05-08

### Added
- MediaMTX v1.10 instalado como proxy RTSP → HLS.
- Endpoints `api/rtsp_proxy.php` y `api/rtsp_snapshot.php`.
- Preview RTSP docked sobre toast de llamada entrante.

## [v5.0] - 2026-05-07

### Added
- Reportes con 4 tabs + export PDF/Excel.
- AgentDetailDrawer profesional.
- Composer + TCPDF + PhpSpreadsheet en la VM.

## [v4.0] - 2026-05-05

### Security
- Rotación completa de credenciales (MySQL `tfremote`, AMI `teleflow`).
- Reescritura de `horizon/main` sin secretos vía force-push.

## [v3.0] - 2026-05-04

### Added
- Realtime hub Node con `asterisk-manager` + `socket.io` + `mysql2`.
- Endpoints Horizon: agent, hotdesking, reports, ChanSpy.
- AGIs y dialplan custom en `dist/`.
- PWA: manifest, icon, sw.js versionado.
