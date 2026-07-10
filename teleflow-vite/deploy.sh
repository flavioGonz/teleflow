#!/bin/bash
# Fase 0: deploya el bundle nuevo como app.build.js SIN pisar app.jsx legacy.
# Uso: ./deploy.sh
set -e
cd "$(dirname "$0")"
DIST=./dist
TARGET=/var/www/teleflow/assets
if [ ! -f "$DIST/app.build.js" ]; then
  echo "[deploy] error: $DIST/app.build.js no existe. Corré 'npm run build' primero."
  exit 1
fi
echo "[deploy] copiando bundle nuevo..."
sudo cp "$DIST/app.build.js" "$TARGET/app.build.js"
if [ -f "$DIST/app.build.js.map" ]; then
  sudo cp "$DIST/app.build.js.map" "$TARGET/app.build.js.map"
fi
if [ -d "$DIST/chunks" ]; then
  sudo mkdir -p "$TARGET/chunks"
  sudo cp -r "$DIST/chunks/." "$TARGET/chunks/"
fi
sudo chown www-data:www-data "$TARGET/app.build.js"* 2>/dev/null || true
ls -lh "$TARGET/app.build.js"
echo "[deploy] OK. Activar con ?build=1 en la URL."
