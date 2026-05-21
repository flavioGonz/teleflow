# Onboarding — Primera semana

Bienvenido al equipo de Teleflow. Esta es una checklist para que arranques productivo rápido.

## Día 1 — Accesos

- [ ] Acceso al repo GitHub `flavioGonz/teleflow` (pedir invitación al owner).
- [ ] Generar SSH key local y enviar la pública al owner para agregar a `hzn@10.1.1.192:.ssh/authorized_keys`.
- [ ] Acceso a la VPN del cliente (Horizon) — credenciales en el password manager del equipo.
- [ ] Credenciales del portal Teleflow en `http://10.1.1.192/` — pedir al owner si se necesita una cuenta admin.
- [ ] Acceso al canal del equipo (Slack/Discord/Telegram según el caso).

## Día 1 — Setup local

- [ ] Clonar el repo:
  ```bash
  git clone git@github.com:flavioGonz/teleflow.git
  cd teleflow
  git checkout horizon/main
  ```
- [ ] Configurar git:
  ```bash
  git config user.name "Tu Nombre"
  git config user.email tu@email.com
  ```
- [ ] Copiar `config.example.php` a `config.php` (no commitear). Pedir las creds reales al owner si vas a correr local con datos productivos.
- [ ] Probar correr local:
  ```bash
  php -S 0.0.0.0:8080 -t .
  # Abrir http://localhost:8080 — debería verse el login
  ```
- [ ] Sin la PBX local accesible, varias features no van a renderizar data live. Eso es esperado.

## Día 2 — Lecturas obligatorias

En este orden:

1. [`README.md`](../README.md) — overview general.
2. [`docs/architecture.md`](architecture.md) — entender capas + DBs.
3. [`docs/deploy.md`](deploy.md) — flujo de deploy y la regla del DocumentRoot.
4. [`docs/pbx-integration.md`](pbx-integration.md) — chan_sip vs PJSIP, feature codes.
5. [`CONTRIBUTING.md`](../CONTRIBUTING.md) — branches, commits, checklist de PR.
6. [`docs/troubleshooting.md`](troubleshooting.md) — errores comunes (skimmear, volver cuando aparezca).

## Día 3 — Hands-on guiado

Pedile al owner un issue chico para arrancar. Sugerencias:
- Cambio de copy o microcopy en alguna vista.
- Agregar un tooltip a un componente existente.
- Ajustar un color o espacio.

Workflow para el primer PR:
1. `git checkout -b feature/tu-nombre-arranque origin/horizon/main`
2. Hacer el cambio.
3. Bumpear cache del SW.
4. Sync a `/var/www/teleflow/` en la VM.
5. Reload Apache.
6. Smoke test (ver checklist en `CONTRIBUTING.md`).
7. Commit + push + PR.
8. El owner revisa y mergea.

## Día 4-5 — Familiarizarse con el código

- Explorar `assets/app.jsx` — el SPA completo está acá (~30 mil líneas). Buscar los componentes principales:
  - `App` — root.
  - `Login`, `AgentQueueSelectModal`.
  - `TopBarMenu`.
  - `ViewDashboard`, `ViewHotdesking`, `ViewVivo`, `ViewColas`, `ViewReportes`, `ViewConfiguracion`, `ViewCallCenter`.
  - `ExtEditPage` (ficha del interno).
  - Primitives shadcn: `Button`, `Card`, `Dialog`, `Sheet`, `Badge`, `Input`, etc. al top del archivo.
- Explorar `api/` — un endpoint por archivo, con acciones via `?action=`.
- Explorar `realtime/index.js` — un solo archivo Node, fácil de seguir.

## Convenciones del equipo

- **Sin build step**: NO uses TypeScript, ESM imports, JSX modules. Mantener vanilla Babel-standalone-friendly.
- **Sin libraries pesadas nuevas**: si necesitás algo, preguntá antes de meter `npm install`.
- **Tokens shadcn**: usar `var(--foreground)`, `var(--horizon-green)`. No hardcodear colores excepto semánticos puntuales.
- **Commits descriptivos**: `feat:`, `fix:`, `docs:`, `chore:`, `perf:`. Ver `CONTRIBUTING.md`.
- **CHANGELOG.md**: agregar entrada cuando el cambio sea visible al usuario o cambie un endpoint público.

## Contactos

- **Owner**: Mauricio (`@flavioGonz`, desarrollo@favaro.com.uy)
- **Cliente final**: Horizon (Infratec)
- **Issues** del repo: para bugs y features.
- **Para preguntas técnicas urgentes**: canal del equipo.

## FAQs rápidas

**¿Puedo trabajar local sin la VPN?**
Sí, levantando `php -S` y usando `config.example.php` adaptado. Algunas vistas no van a tener data real pero el frontend funciona.

**¿Cómo testeo cambios sin afectar producción?**
1. Crear branch `feature/...`.
2. Mantener cambios fuera de `/var/www/teleflow/` hasta el merge.
3. Si necesitás probar contra la PBX real: avisar al owner antes para coordinar.

**¿Hay tests automatizados?**
Todavía no. Si querés agregar (vitest, phpunit), bienvenida la propuesta — discutilo en un issue antes.

**¿Quién aprueba los PRs?**
El owner (Mauricio) por ahora. A medida que crezca el equipo, definimos CODEOWNERS por área.
