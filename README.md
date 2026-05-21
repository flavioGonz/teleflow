# Teleflow Horizon

> PBX management & call center suite para Asterisk / Issabel.
> Web app PWA con softphone WebRTC, monitor en vivo, hotdesking, reportes, snapshots automáticos de videoporteros RTSP y debug SIP en línea.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Branch](https://img.shields.io/badge/branch-horizon%2Fmain-11B328)](https://github.com/flavioGonz/teleflow/tree/horizon/main)
[![Stack](https://img.shields.io/badge/stack-PHP%208.3%20%2B%20React%20%2B%20Node-orange)]()
[![PBX](https://img.shields.io/badge/PBX-Asterisk%20%7C%20Issabel%20(chan__sip)-blue)]()

---

## Producción

- **URL**: [http://10.1.1.192/](http://10.1.1.192/)
- **VM web**: `srv-teleflow` (Ubuntu), Apache + PHP 8.3 + Node hub
- **PBX**: Issabel @ `10.1.1.7` con chan_sip
- **Rama productiva**: `horizon/main` (rama default del repo)

## Stack

- **Frontend**: React + Babel-standalone (sin build step), Tailwind CDN, primitives shadcn inline.
- **Backend**: PHP 8.3 + PDO MySQL + composer (TCPDF, PhpSpreadsheet para exports).
- **Realtime**: Node 18+ con `asterisk-manager` + `socket.io`.
- **Media**: MediaMTX v1.10 (proxy RTSP → HLS).
- **PWA**: service worker con strategies por tipo de recurso, manifest, offline page.

## Quick start

### 1. Acceso SSH a la VM productiva

```bash
ssh hzn@10.1.1.192   # password en LastPass del equipo
cd /home/hzn/teleflow-horizon-clean
git status
```

### 2. Editar y deployar (workflow estándar)

El flujo está descripto paso a paso en [`docs/deploy.md`](docs/deploy.md). Resumen:

1. Editar archivos en una rama feature (`feature/mi-cambio`).
2. Subir vía SFTP a `/var/www/teleflow/` y a `/home/hzn/teleflow-horizon-clean/`.
3. Bumpear `CACHE_NAME` en `sw.js`.
4. `sudo apache2ctl -t && sudo systemctl reload apache2`.
5. Si tocaste `realtime/index.js`: `sudo systemctl restart teleflow-realtime`.
6. Commit + push desde la VM. PR a `horizon/main`.

> ⚠ **DocumentRoot es `/var/www/teleflow/`**, no `/var/www/html/`. Más detalles en [`docs/deploy.md`](docs/deploy.md).

### 3. Cómo correr local (sin la PBX)

```bash
git clone git@github.com:flavioGonz/teleflow.git
cd teleflow
cp config.example.php config.php
# Editar config.php con creds reales (no commitear)
php -S 0.0.0.0:8080 -t .
# Abrir http://localhost:8080
```

Sin acceso a la PBX algunas vistas no van a renderizar data live; el front sigue funcional para trabajar UI.

## Estructura del repo

```
.
├── index.php                  # Punto de entrada del SPA (70 KB)
├── assets/app.jsx             # JSX/React del SPA (945 KB, cacheable)
├── sw.js / manifest.json      # PWA
├── offline.html               # Página offline elegante
├── api/                       # Endpoints PHP
├── dist/                      # Dialplan + AGIs + audios para la PBX
├── realtime/                  # Node hub socket.io + AMI
├── softphone/                 # WebRTC softphone (sub-app)
└── docs/                      # Documentación detallada
```

Detalle completo de cada carpeta en [`docs/architecture.md`](docs/architecture.md).

## Documentación

| Doc | Tema |
|-----|------|
| [`docs/architecture.md`](docs/architecture.md) | Diagrama, capas, decisiones técnicas |
| [`docs/deploy.md`](docs/deploy.md) | Deploy paso a paso, drift check, troubleshooting |
| [`docs/api.md`](docs/api.md) | Catálogo de endpoints PHP |
| [`docs/pbx-integration.md`](docs/pbx-integration.md) | chan_sip vs PJSIP, feature codes, AGI |
| [`docs/pwa.md`](docs/pwa.md) | Service worker strategies, manifest, push |
| [`docs/troubleshooting.md`](docs/troubleshooting.md) | Errores comunes y soluciones |
| [`docs/onboarding.md`](docs/onboarding.md) | Checklist para devs nuevos |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Cómo contribuir (branches, commits, PRs) |
| [`CHANGELOG.md`](CHANGELOG.md) | Histórico de versiones |

## Feature codes (Asterisk)

| Código | Acción |
|--------|--------|
| `*7700` | Login agente con prefs guardadas |
| `*7700*N` | Login a cola mapeada al dígito N (config en Configuración → Atajos) |
| `*7700*N*M*K` | Login simultáneo a múltiples colas |
| `*7700*QUEUE` | Login a cola por ID literal (ej. `*7700*8000`) |
| `*7701` | Logout total |
| `*7702` | Pausar (pide motivo DTMF) |
| `*7703` | Despausar |

Tabla completa en [`docs/pbx-integration.md`](docs/pbx-integration.md).

## Licencia

MIT.
