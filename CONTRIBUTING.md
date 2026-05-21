# Contributing to Teleflow Horizon

Gracias por sumarte. Esta guía describe el workflow de contribución del equipo.

## Branching

- **Rama default**: `horizon/main` (rama productiva).
- **No commitear directo a `horizon/main`** — siempre vía PR.
- Crear feature branches con prefijo según el tipo:

```
feature/nombre-corto-descriptivo
fix/nombre-del-bug
chore/limpieza-cosa
docs/que-cambia
perf/optimizacion-x
```

## Convenciones de commit

Usamos prefijos al estilo Conventional Commits para que el historial sea legible y el `CHANGELOG.md` se mantenga solo:

| Prefijo | Cuándo |
|---------|--------|
| `feat:` | Funcionalidad nueva |
| `fix:` | Bug fix |
| `docs:` | Sólo documentación |
| `chore:` | Mantenimiento (deps, refactor sin lógica) |
| `perf:` | Mejora de performance |
| `style:` | Formato, sin cambio de comportamiento |
| `refactor:` | Reorganización del código |
| `test:` | Tests |

Ejemplo:

```
feat(dashboard): mini video RTSP en filas de llamadas activas
fix(agent): QueuePause usa Location del QueueStatus para chan_sip
docs(deploy): aclarar que DocumentRoot es /var/www/teleflow/
```

Si el commit cambia algo visible en la UI o un endpoint público, agregar línea al `CHANGELOG.md` en el mismo PR.

## Antes de abrir un PR — checklist

- [ ] Bumpear `CACHE_NAME` en `sw.js` si tocaste `index.php` o `assets/app.jsx`.
- [ ] Validar balance de llaves del JSX:
  ```bash
  node -e "const fs=require('fs'); const s=fs.readFileSync('assets/app.jsx','utf8'); let o=0,c=0; for(const ch of s){if(ch==='{')o++;if(ch==='}')c++;} console.log('open=',o,'close=',c,'delta=',o-c);"
  ```
  El `delta` debe ser **0**.
- [ ] `sudo apache2ctl -t` en la VM (sintaxis PHP/Apache).
- [ ] Smoke test:
  ```bash
  curl -s -o /dev/null -w "HTTP %{http_code} size=%{size_download}\n" http://10.1.1.192/
  curl -s -o /dev/null -w "HTTP %{http_code}\n" http://10.1.1.192/api/index.php?action=ping
  ```
- [ ] Drift check post-deploy: md5 de archivos modificados debe coincidir entre `/var/www/teleflow/`, `/home/hzn/teleflow-horizon-clean/` y sandbox local.
- [ ] Si tocaste el `realtime/index.js`: `sudo systemctl restart teleflow-realtime` y revisar `journalctl -u teleflow-realtime -n 30`.
- [ ] Si cambiaste el schema MySQL: incluir el SQL de migración en el PR description.

## Code review

- Asignar al menos 1 reviewer en GitHub.
- El reviewer debe verificar el smoke test live en `http://10.1.1.192/` antes de mergear.
- Los PRs son squash-merge para mantener el historial limpio en `horizon/main`.

## Convenciones de código

### Frontend (React/JSX en `assets/app.jsx`)

- **Sin build step**: Babel-standalone parsea el archivo en el browser. NO usar imports/exports modernos, sintaxis JSX clásica.
- **Tokens shadcn**: usar `var(--foreground)`, `var(--background)`, `var(--primary)`, `var(--horizon-green)`. Evitar hardcoded `#hex` excepto valores semánticos (verde/ámbar/rojo para estados).
- **Hooks al top del componente**: nunca `useState/useEffect/useRef` dentro de un `if`. React tira #310 cuando el número de hooks cambia entre renders.
- **`</script>` dentro de template literals**: escapar como `<\/script>` para no romper el parser del `<script type="text/babel">`.

### Backend (PHP en `api/`)

- Endpoints retornan JSON. Header `Content-Type: application/json`.
- Validar sesión con `if (!isset($_SESSION['tf_user']) && !isset($_SESSION['agent_user']))` → HTTP 401.
- PDO con `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- Logging a `/tmp/teleflow_<feature>.log` para debugging.

### PBX (en `dist/`)

- `extensions_teleflow.conf` se copia a `/etc/asterisk/extensions_custom.conf` en la PBX (o include).
- AGIs se copian a `/var/lib/asterisk/agi-bin/`.
- Audios custom a `/var/lib/asterisk/sounds/custom/`.
- Las credenciales del AGI viven en `/etc/asterisk/teleflow_agi.conf` (no en el AGI script).

## Reglas del DocumentRoot

⚠ **CRITICO**: Apache sirve desde `/var/www/teleflow/`, NO `/var/www/html/`. Si subís archivos al lugar equivocado, "no se ven los cambios". Detalles en [`docs/deploy.md`](docs/deploy.md).

## Reportar bugs

Usar el template de issue en GitHub (`.github/ISSUE_TEMPLATE/bug_report.md`). Incluir:
- Pasos para reproducir
- Resultado esperado vs observado
- Screenshots o logs (`journalctl -u teleflow-realtime`, `/var/log/apache2/teleflow_error.log`)
- HEAD de `horizon/main` y cache version del SW

## Sugerencias / features

Issue con el template `feature_request.md`. Antes de implementar features grandes, discutir en un issue para evitar trabajo duplicado.

## Maintainers

- Mauricio (`@flavioGonz`) — owner principal

Para acceso SSH a la VM, credenciales de PBX o agregar tu key a `~/.ssh/authorized_keys` de `hzn@10.1.1.192`, pedir al owner.
