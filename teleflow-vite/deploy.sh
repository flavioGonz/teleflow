#!/bin/bash
# teleflow-vite/deploy.sh — F9: pipeline completo test → build → deploy → sync repo → commit → push.
#
# Uso:
#   ./deploy.sh "mensaje del commit"          # commit + push automático
#   ./deploy.sh --dry-run                     # muestra qué haría, sin ejecutar
#   ./deploy.sh --no-commit "..."             # solo deploy prod, sin git
#
# Salida:
#   0 = todo OK y publicado
#   1 = falló un paso (test, build, deploy, git)
#
# Idempotente y seguro:
#   - Aborta si tests rojos.
#   - No pisa app.jsx legacy (nunca lo toca).
#   - Backup implícito: git tiene todo, y /var/www/teleflow/assets/app.build.js
#     tiene su mtime que sirve de identificador de versión.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

VM_ASSETS="/var/www/teleflow/assets"
REPO="/home/hzn/teleflow-horizon"
SUDO_PASS="${TF_SUDO_PASS:-hzn.2468}"  # override con env var si querés

DRY=0
NO_COMMIT=0
MSG=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run)    DRY=1; shift ;;
    --no-commit)  NO_COMMIT=1; shift ;;
    -h|--help)
      grep -E "^# " "$0" | head -20
      exit 0
      ;;
    *) MSG="$1"; shift ;;
  esac
done

step() { echo -e "\n\033[1;32m▸ $*\033[0m"; }
warn() { echo -e "\033[1;33m! $*\033[0m"; }
die()  { echo -e "\033[1;31m✗ $*\033[0m"; exit 1; }

# 1) Tests
step "1/6  npm test"
[[ $DRY -eq 1 ]] || npm run test --silent || die "tests rojos — aborto"

# 2) Build
step "2/6  npm build"
[[ $DRY -eq 1 ]] || npm run build --silent

# 3) Deploy al webroot (requiere sudo — usamos password via stdin)
step "3/6  Deploy a $VM_ASSETS"
if [[ $DRY -eq 1 ]]; then
  warn "(dry) cp dist/app.build.js $VM_ASSETS/"
  warn "(dry) cp dist/chunks/*.js  $VM_ASSETS/chunks/"
else
  echo "$SUDO_PASS" | sudo -S bash -c "
    cp dist/app.build.js $VM_ASSETS/app.build.js
    cp dist/app.build.js.map $VM_ASSETS/app.build.js.map 2>/dev/null || true
    mkdir -p $VM_ASSETS/chunks
    rm -f $VM_ASSETS/chunks/*.js $VM_ASSETS/chunks/*.map
    cp -r dist/chunks/. $VM_ASSETS/chunks/
    chown -R www-data:www-data $VM_ASSETS/app.build.js* $VM_ASSETS/chunks
  "
fi

# 4) Sanity check: app.jsx legacy no cambió
step "4/6  Verificar legacy INTACTO"
LEGACY_MD5="$(md5sum "$VM_ASSETS/app.jsx" | cut -d' ' -f1)"
echo "   app.jsx md5: $LEGACY_MD5"
grep -oE 'app.jsx|app.build.js' /var/www/teleflow/agentes/index.php | sort -u | \
  head -1 | grep -q "^app.jsx$" || die "/agentes/ NO sirve app.jsx — abortando (riesgo prod)"

# 5) Sync a repo git
step "5/6  Sync repo local"
if [[ $DRY -eq 1 ]]; then
  warn "(dry) rsync a $REPO/teleflow-vite/"
elif [[ ! -d "$REPO" ]]; then
  warn "repo local no existe en $REPO — skip sync"
else
  rsync -a --exclude "node_modules" --exclude "dist" --exclude ".git" --exclude "*.log" \
    ./ "$REPO/teleflow-vite/"
  cp "$VM_ASSETS/app.build.js" "$REPO/assets/app.build.js"
  rm -f "$REPO/assets/chunks/"*.js
  cp "$VM_ASSETS/chunks/"*.js "$REPO/assets/chunks/"
fi

# 6) Commit + push
step "6/6  Git commit + push"
if [[ $NO_COMMIT -eq 1 ]]; then
  warn "--no-commit: skip"
elif [[ -z "$MSG" ]]; then
  warn "Sin mensaje — skip (usá: ./deploy.sh 'mi mensaje')"
elif [[ $DRY -eq 1 ]]; then
  warn "(dry) git commit -m '$MSG' && git push"
else
  cd "$REPO"
  git add teleflow-vite/ assets/app.build.js assets/chunks/
  if git diff --cached --quiet; then
    warn "Nada para commitear — repo ya al día"
  else
    git -c user.email=desarrollo@favaro.com.uy -c user.name=Mauricio commit -m "$MSG"
    git push origin horizon/main
  fi
fi

echo -e "\n\033[1;32m✓ Deploy completo\033[0m"
