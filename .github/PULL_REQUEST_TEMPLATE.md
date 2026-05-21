## ¿Qué cambia?

<!-- Descripción concisa del cambio. Una o dos líneas. -->

## Tipo de cambio

- [ ] `feat:` Funcionalidad nueva
- [ ] `fix:` Bug fix
- [ ] `docs:` Sólo documentación
- [ ] `chore:` Mantenimiento (sin lógica)
- [ ] `perf:` Performance
- [ ] `refactor:` Refactor sin cambio de comportamiento

## ¿Cómo lo probaste?

<!-- Pasos concretos: qué endpoint, qué vista, qué clicks, qué viste. -->

- [ ] Smoke test `curl http://10.1.1.192/` → HTTP 200
- [ ] Hard refresh del browser tras deploy
- [ ] Sin errores en DevTools console
- [ ] Drift check: md5 de archivos sync entre VM repo y Apache root

## Screenshots / Logs

<!-- Si es UI, antes/después. Si es backend, logs relevantes. -->

## Checklist

- [ ] Branch creado desde `horizon/main`
- [ ] Commit con prefijo convencional (`feat:`, `fix:`, etc.)
- [ ] `CACHE_NAME` bumpeado en `sw.js` (si tocaste `index.php` o `assets/app.jsx`)
- [ ] Balance de llaves del JSX validado (`delta = 0`)
- [ ] `sudo apache2ctl -t` pasa
- [ ] Sin secretos hardcodeados (passwords, API keys, etc.)
- [ ] Si tocaste el schema MySQL: SQL de migración en la descripción del PR
- [ ] `CHANGELOG.md` actualizado (si el cambio es visible al usuario)

## Notas para el reviewer

<!-- Cosas específicas a mirar, decisiones técnicas, alternativas consideradas. -->
