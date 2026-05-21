# Troubleshooting — Teleflow Horizon

## "No veo los cambios después de deployar"

**Causa más común**: archivos copiados a `/var/www/html/` en lugar de `/var/www/teleflow/` (DocumentRoot real).

```bash
sudo md5sum /home/hzn/teleflow-horizon-clean/index.php /var/www/teleflow/index.php
# Los hashes deben coincidir
```

Si difieren, re-deployar a la ruta correcta (ver [`deploy.md`](deploy.md)).

**Segunda causa**: SW cacheando versión vieja. Hard refresh (Ctrl+Shift+R) + DevTools → Application → Service Workers → Unregister.

**Tercera causa**: no se bumpeó `CACHE_NAME` en `sw.js` después de modificar `index.php` o `assets/app.jsx`.

## "403 Forbidden en todos los endpoints"

Sesión PHP expirada. El cliente sigue creyendo que está logueado (state en localStorage/React) pero el server perdió la sesión.

**Solución**: cerrar todas las tabs, abrir UNA sola y volver a hacer login desde `/`.

Si persiste: revisar `/var/log/apache2/teleflow_error.log` por errores PHP.

## "Socket realtime entra en loop connected/disconnected"

Causas comunes:
1. **Múltiples tabs** abiertas — cada una abre su propio cliente socket.io.
2. **SW viejo cacheado** con código JS obsoleto.
3. **Cliente extra** que apunta al path equivocado. El cliente "oficial" usa `path:/teleflow-socket` en puerto 3001. Cualquier otro cliente con `path:/socket.io` da 404 contra Apache.

**Solución**: cerrar todas las tabs, hard refresh, abrir UNA sola.

## "React error #310 — Rendered fewer hooks than expected"

Tenés un `useState/useEffect/useRef` dentro de un `if`. React requiere que los hooks corran SIEMPRE en el mismo orden. Moverlos al top del componente.

## "React error #300 — Element type is invalid / Max update depth"

Tenés un componente referenciado que es `undefined` (typo o falta de definición) o un setState en loop dentro de un useEffect sin dependency.

```js
// MAL
useEffect(() => {
  setState(newRef);  // crea ref nueva cada render → loop
});

// BIEN
useEffect(() => {
  setState(newRef);
}, [actualDependency]);
```

## "Página renderiza el código JSX como texto plano"

Causa: `</script>` literal dentro de un template literal en `assets/app.jsx`. El browser corta el `<script type="text/babel">` prematuramente al detectar la cadena.

**Solución**: escapar como `<\/script>` (el backslash desaparece al ejecutar el JS).

```js
// MAL
const html = `<script>...</script>`;

// BIEN
const html = `<script>...<\/script>`;
```

## "Login del agente XXX no funciona"

Verificar que el agente exista y esté activo en `call_center.agent`:

```sql
SELECT number, name, password, estatus FROM agent WHERE number = 200;
```

- Si `estatus = 'I'` → inactivo, reactivar desde Configuración → Agentes.
- Si `password` no matchea → resetear desde Configuración → Agentes.

## "Agente pausado sigue recibiendo llamadas"

La pausa via AMI `QueuePause` requiere que el `Interface` coincida exactamente con el `Location` del QueueStatus. Si el agente está como **static member** (`Local/N@from-queue/n` en Issabel queues.conf), y enviamos `SIP/N`, el pause no aplica.

**Ya está reparado** en `agent_pause_commit.php`, `agent_unpause_commit.php`, `agent_logout_commit.php`. Si vuelve a pasar, revisar que el código sigue usando `$m['location']` y no construyendo `SIP/$ext` por hardcode.

## "Stream RTSP no carga"

1. Verificar que MediaMTX está corriendo:
   ```bash
   sudo systemctl status mediamtx
   curl -s http://127.0.0.1:9997/v3/paths/list | head
   ```
2. Verificar que la cámara responde RTSP:
   ```bash
   ffprobe -rtsp_transport tcp rtsp://10.20.140.6/live/ch00_1
   ```
3. Verificar la config del proxy: `/etc/mediamtx.yml` debe tener `hlsVariant: mpegts` y `rtspTransport: tcp`.

## "Hora del PBX está 3h adelantada en el reloj"

El timezone PHP estaba en UTC. Verificar:

```bash
sudo grep date_default_timezone /var/www/teleflow/config.php
# debe decir: date_default_timezone_set('America/Montevideo');
```

Si falta, agregarlo después de la línea `$TF_MODE = ...`.

## "Hora del CDR no coincide con los snapshots"

El CDR de MariaDB en la PBX puede estar en UTC mientras la app PHP está en Montevideo. Las queries que devuelven calldate también deben devolver `UNIX_TIMESTAMP(calldate) AS call_epoch` para que el frontend use epoch real (TZ-agnostic) en lugar de strings.

Endpoints ya corregidos: `reports.php?action=calls`, `reports.php?action=agent_sessions`, `agent_report.php`.

## Logs útiles

```bash
# Apache
sudo tail -100 /var/log/apache2/teleflow_error.log
sudo tail -100 /var/log/apache2/teleflow_access.log | grep -E '4[0-9]{2}|5[0-9]{2}'

# Realtime hub
sudo journalctl -u teleflow-realtime -f
sudo journalctl -u teleflow-realtime -n 100 --no-pager

# MediaMTX
sudo journalctl -u mediamtx -n 50

# AGI Asterisk (logs custom de Teleflow)
sudo tail -100 /var/log/asterisk/teleflow_agi.log

# Commits internos del backend (notify, pauses)
sudo tail -50 /tmp/teleflow_agent_commit.log
sudo tail -50 /tmp/teleflow_door.log
```

## Recovery rápido si nada funciona

```bash
# 1. Verificar servicios
sudo systemctl status apache2 teleflow-realtime mediamtx

# 2. Reiniciar servicios web
sudo systemctl restart apache2 teleflow-realtime

# 3. Verificar sintaxis PHP
sudo apache2ctl -t

# 4. Si Apache no levanta, ver el último error
sudo tail -50 /var/log/apache2/error.log

# 5. Rollback al commit estable anterior
cd /home/hzn/teleflow-horizon-clean
git log --oneline -5
git checkout <SHA_ESTABLE> -- index.php assets/app.jsx
# Resincronizar a /var/www/teleflow/

# 6. Como último recurso: clonar fresh desde GitHub
sudo systemctl stop apache2
cd /tmp
git clone git@github.com:flavioGonz/teleflow.git fresh
sudo cp -r fresh/* /var/www/teleflow/
sudo chown -R www-data:www-data /var/www/teleflow/
sudo systemctl start apache2
```
