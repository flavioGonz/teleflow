# PWA — Service Worker, Manifest y Offline

## Resumen

Teleflow es una PWA completa: instalable, funciona offline (con limitaciones), tema dark/light, shortcuts en el menú de iconos del browser, push notifications opcionales.

## Service Worker (`sw.js`)

### Estrategias por tipo de recurso

| Tipo | Estrategia | Razón |
|------|-----------|-------|
| `/api/*` GETs | **Network-only** | Datos siempre frescos (no cachear) |
| `/assets/app.jsx` y `/assets/*` | **Cache-first** | Cache busting con `?v=` controlado por SW version |
| HTML / navegación | **Network-first → cache → offline.html** | Funciona offline con la última versión cacheada |
| Imágenes (incluye snapshots RTSP) | **Cache-first** | Snapshots no cambian una vez tomados |
| CDNs y default | **Stale-while-revalidate** | Tailwind, React, Babel — cache + revalidar en background |

### Cache busting

`CACHE_NAME` es la "versión" del SW. Al cambiarlo, el SW:
1. Limpia caches viejos en `activate`.
2. El `<script src="assets/app.jsx?v=<CACHE_NAME>">` cambia su URL → el browser baja la nueva versión.

**Bumpear el cache** después de cualquier deploy de `index.php` o `assets/app.jsx`:

```bash
NEW_TAG="v$(date +%Y%m%d%H%M)"
sed -i "s|teleflow-cache-v[0-9]\+|teleflow-cache-${NEW_TAG}|" sw.js
```

### Precache

El SW precachea en `install`:
- App shell: `/`, `/index.php`, `/manifest.json`, `/icon-192.svg`, `/offline.html`
- CDNs críticos: Tailwind, React, Babel, hls.js, Material Icons

Failure-tolerant: usa `Promise.allSettled` para que falle silenciosamente si un CDN no responde.

### Push notifications

Soporte completo en el SW (`push` event listener). Para activar push real:

1. Generar par de keys VAPID:
   ```bash
   npx web-push generate-vapid-keys
   ```
2. Guardar la public key en config front, private key en server.
3. Server-side: usar `web-push` (Node) para mandar notificaciones via `pushManager.subscribe()` del navegador.

Por ahora hay un fallback "in-page notifications" via `postMessage({type: 'NOTIFY', ...})` desde el código del cliente, que el SW relayea como notificación nativa.

## Manifest (`manifest.json`)

Configurado con paleta Horizon:

```json
{
  "name": "Teleflow Horizon · PBX Control",
  "short_name": "Teleflow",
  "theme_color": "#11B328",
  "background_color": "#0a0a0d",
  "display": "standalone",
  "display_override": ["window-controls-overlay", "minimal-ui", "standalone"]
}
```

### Shortcuts (4)

| Atajo | URL |
|-------|-----|
| Llamadas en Vivo | `/?view=vivo&source=pwa-shortcut` |
| Hotdesking | `/?view=hotdesking&source=pwa-shortcut` |
| Reportes | `/?view=reportes&source=pwa-shortcut` |
| Dashboard | `/?view=dashboard&source=pwa-shortcut` |

Se ven al click derecho sobre el icono de la PWA en taskbar/launcher.

### Iconos

Único archivo SVG `icon-192.svg` con declaración `"sizes": "any"` y variant `maskable` para Android adaptive icons.

## Offline page (`offline.html`)

Página standalone (sin SPA) servida cuando no hay red y no hay HTML cacheado.

- Splash con gradient verde Horizon.
- Botones "Reintentar" / "Volver al inicio".
- Indicador de estado de red en tiempo real (`navigator.onLine` + `online`/`offline` events).
- **Auto-reload** cuando vuelve la conexión.

## Theme color responsive

El meta `theme-color` usa `media="(prefers-color-scheme: ...)"`:

```html
<meta name="theme-color" content="#11B328" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0a0a0d" media="(prefers-color-scheme: dark)">
```

Resultado: la barra de URL del browser (Android Chrome) y la barra de ventana (desktop standalone) toman el color correcto según preferencia del sistema.

## Registro del SW

En el `<head>` del `index.php`:

```js
navigator.serviceWorker.register('/sw.js', { scope: '/' })
  .then(reg => {
    if (reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
    reg.addEventListener('updatefound', () => {
      const nw = reg.installing;
      nw?.addEventListener('statechange', () => {
        if (nw.state === 'installed' && navigator.serviceWorker.controller) {
          window.dispatchEvent(new CustomEvent('tf-sw-update', { detail: { reg } }));
        }
      });
    });
  });
```

El custom event `tf-sw-update` se puede capturar desde la SPA para mostrar un toast "nueva versión disponible — reload".

## Anti-patrón a evitar

❌ **NO desregistrar el SW ni limpiar caches en cada page load**:

```js
// MAL — esto rompe la PWA
navigator.serviceWorker.getRegistrations().then(rs => rs.forEach(r => r.unregister()));
caches.keys().then(ks => ks.forEach(k => caches.delete(k)));
```

Este código apareció en el `<head>` durante debugging y rompió la PWA hasta que se descubrió. **Si necesitás "reset duro" del SW, usar DevTools** o `clear-site-data` header.

## Testing PWA

1. **Lighthouse audit**: DevTools → Lighthouse → Progressive Web App. Apuntar a score 90+.
2. **Application tab**: ver Manifest, Service Worker, Storage.
3. **Offline mode**: DevTools → Network → Offline → recargar → debería verse `offline.html`.
4. **Install**: en Chrome desktop, ícono "+" en URL bar.
