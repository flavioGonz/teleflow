# Deploy guide — Teleflow Horizon

## ⚠ DocumentRoot

Apache sirve desde **`/var/www/teleflow/`**, NO `/var/www/html/`. Si copiás archivos al lugar equivocado:

- No se ven los cambios en producción.
- `apache2ctl -t` pasa pero el HTML servido sigue siendo el viejo.
- Verificación: `curl -s http://10.1.1.192/index.php | md5sum` debe coincidir con `md5sum /var/www/teleflow/index.php`.

El vhost vive en `/etc/apache2/sites-enabled/teleflow.conf` y NO se debe modificar manualmente sin discutir en el equipo.

## Flujo estándar

### 1. Crear branch feature

```bash
ssh hzn@10.1.1.192
cd /home/hzn/teleflow-horizon-clean
git fetch origin
git checkout -b feature/mi-cambio origin/horizon/main
```

### 2. Editar archivos

Editar en la VM o desde tu máquina (SFTP / VSCode Remote SSH).

### 3. Bumpear cache del SW

Si tocaste `index.php` o `assets/app.jsx`:

```bash
NEW_TAG="v$(date +%Y%m%d%H%M)"
sed -i "s|teleflow-cache-v[0-9]\+|teleflow-cache-${NEW_TAG}|" sw.js
grep "teleflow-cache-v" sw.js   # verificar
```

### 4. Validar JSX (balance de llaves)

```bash
node -e "const fs=require('fs'); const s=fs.readFileSync('assets/app.jsx','utf8'); let o=0,c=0; for(const ch of s){if(ch==='{')o++;if(ch==='}')c++;} console.log('open=',o,'close=',c,'delta=',o-c);"
```

Si `delta` != 0, el archivo está roto. Buscá tu cambio reciente y arreglá.

### 5. Sincronizar a Apache root

```bash
sudo rsync -av \
  --include='*.php' --include='*.js' --include='*.json' --include='*.html' --include='*.jsx' --include='*.svg' --include='/api/' --include='/api/*' --include='/assets/' --include='/assets/*' --include='/dist/' --include='/dist/*' --include='/realtime/' --include='/realtime/*' --include='/softphone/' --include='/softphone/*' \
  --exclude='*' \
  /home/hzn/teleflow-horizon-clean/ /var/www/teleflow/
sudo chown -R www-data:www-data /var/www/teleflow/
```

Alternativa simple — sólo copiar archivos modificados:

```bash
sudo cp -v index.php sw.js assets/app.jsx /var/www/teleflow/...
sudo chown www-data:www-data ...
```

### 6. Reload Apache

```bash
sudo apache2ctl -t && sudo systemctl reload apache2
```

Si `apache2ctl -t` falla, revisar `sudo tail -30 /var/log/apache2/teleflow_error.log`.

### 7. Restart realtime hub (si tocaste `realtime/`)

```bash
sudo systemctl restart teleflow-realtime
sudo journalctl -u teleflow-realtime -n 30 --no-pager
```

### 8. Drift check

```bash
md5sum /home/hzn/teleflow-horizon-clean/index.php /var/www/teleflow/index.php
# Los dos hashes deben coincidir.
curl -s http://127.0.0.1/index.php | md5sum
# Este debería ser distinto porque PHP ejecuta el archivo, pero el SIZE debe ser similar al disk.
```

### 9. Commit + push + PR

```bash
git add -A
git commit -m "feat(area): descripción corta"
git push origin feature/mi-cambio
```

Después abrir PR en GitHub apuntando a `horizon/main`. Pedir review.

### 10. Post-merge

Una vez mergeado el PR:

```bash
cd /home/hzn/teleflow-horizon-clean
git checkout horizon/main
git pull origin horizon/main
git branch -d feature/mi-cambio
```

## Hard refresh del browser tras deploy

El SW cachea agresivamente. Para que tus cambios se vean:

1. **Hard refresh**: Ctrl+Shift+R (Win/Linux) o Cmd+Shift+R (Mac).
2. Si no alcanza: DevTools → Application → Service Workers → Unregister + Application → Storage → Clear site data.
3. Cerrar todas las tabs del Teleflow y volver a abrir.

## Smoke tests post-deploy

```bash
# 1. Frontend
curl -s -o /dev/null -w "GET /         HTTP %{http_code} size=%{size_download}\n" http://10.1.1.192/

# 2. Endpoints públicos
curl -s -o /dev/null -w "manifest      HTTP %{http_code}\n" http://10.1.1.192/manifest.json
curl -s -o /dev/null -w "sw.js         HTTP %{http_code}\n" http://10.1.1.192/sw.js
curl -s -o /dev/null -w "app.jsx       HTTP %{http_code}\n" http://10.1.1.192/assets/app.jsx
curl -s -o /dev/null -w "offline.html  HTTP %{http_code}\n" http://10.1.1.192/offline.html

# 3. Server time (sanity de timezone)
curl -s http://10.1.1.192/api/server_time.php
```

## Rollback rápido

Si después de un deploy algo está roto:

```bash
cd /home/hzn/teleflow-horizon-clean
git log --oneline -5                 # ver commits recientes
git revert HEAD                      # revertir el último commit
# ... sincronizar a /var/www/teleflow/ y reload Apache
```

O ir a un commit anterior directamente:

```bash
git checkout <SHA_ANTERIOR> -- index.php assets/app.jsx
# Copiar a Apache root + reload
```

## Servicios systemd relevantes

```bash
sudo systemctl status apache2            # web server
sudo systemctl status teleflow-realtime  # Node hub socket.io
sudo systemctl status mediamtx           # RTSP→HLS proxy
```

Logs:

```bash
sudo tail -50 /var/log/apache2/teleflow_error.log
sudo tail -50 /var/log/apache2/teleflow_access.log
sudo journalctl -u teleflow-realtime -f
sudo journalctl -u mediamtx -n 50
```
