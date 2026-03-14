# ============================================================
# deploy.ps1 - TeleFlow Deploy Script
# Uso: .\deploy.ps1 "mensaje del commit"
# ============================================================

param(
    [string]$CommitMsg = "update: cambios desde local"
)

$ErrorActionPreference = "Stop"
$REMOTE_PATH = "/var/www/html/teleflow"
$SSH_HOST = "issabel-pbx"


Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  TeleFlow Deploy Script" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Git add + commit + push al repositorio
Write-Host "[1/3] Commiteando y pusheando a GitHub..." -ForegroundColor Yellow
git add -A
$status = git status --porcelain
if ($status) {
    git commit -m $CommitMsg
    git push origin main
    Write-Host "      OK - Push exitoso a GitHub" -ForegroundColor Green
} else {
    Write-Host "      Sin cambios nuevos para commitear" -ForegroundColor Gray
}

# 2. Hacer pull en el servidor remoto
Write-Host ""
Write-Host "[2/3] Haciendo pull en el servidor Issabel..." -ForegroundColor Yellow
$result = ssh $SSH_HOST "cd $REMOTE_PATH && git pull origin main 2>&1"
Write-Host $result
Write-Host "      OK - Servidor actualizado" -ForegroundColor Green

# 3. Reiniciar permisos si es necesario
Write-Host ""
Write-Host "[3/3] Ajustando permisos en el servidor..." -ForegroundColor Yellow
ssh $SSH_HOST "chown -R asterisk:asterisk $REMOTE_PATH && chmod -R 755 $REMOTE_PATH" 2>&1
Write-Host "      OK - Permisos aplicados" -ForegroundColor Green

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Deploy completado exitosamente!" -ForegroundColor Green
Write-Host "  URL: https://pbx01.infratec.com.uy/teleflow" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
