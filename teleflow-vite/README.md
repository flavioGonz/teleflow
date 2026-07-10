# teleflow-vite — Fase 0

Workspace paralelo con Vite + React 18. **No reemplaza prod**.

## Estado
Fase 0: build tooling operativo. El bundle sale a `/var/www/teleflow/assets/app.build.js` y solo se carga cuando la URL trae `?build=1`. El `app.jsx` legacy (Babel Standalone) sigue siendo el default.

## Comandos
- `npm install` — instala deps
- `npm run dev` — dev server (puerto 5173)
- `npm run build` — produce `/var/www/teleflow/assets/app.build.js`
- `npm run lint` — ESLint
- `npm run format` — Prettier

## Roadmap
- F0: infra ✅
- F1: `lib/api.js`, `lib/rt.js`, `lib/clock.js`
- F2: views separadas + lazy load
- F3: state global (zustand)
- F4: tests + CI
