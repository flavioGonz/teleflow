#!/bin/bash
# ============================================================
# TELEFLOW AGENT MONITORING v2.0
# Deploy Script para IISABEL 5
# ============================================================

echo "🚀 INICIANDO DEPLOY DE TELEFLOW v2.0"
echo "==========================================="

# Verificar permisos de sudo
if [ "$EUID" -ne 0 ]; then 
   echo "❌ Este script requiere permisos de administrador (sudo)"
   exit 1
fi

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Crear directorios
echo -e "${YELLOW}[1/6]${NC} Creando directorios..."
mkdir -p /var/www/html/teleflow/api
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Directorio /var/www/html/teleflow creado"
else
    echo -e "${RED}✗${NC} Error creando directorio"
    exit 1
fi

# 2. Copiar archivos (si existen en /tmp/teleflow)
echo -e "${YELLOW}[2/6]${NC} Copiando archivos del dashboard..."

if [ -f "/tmp/teleflow/dashboard-agents-v2.html" ]; then
    cp /tmp/teleflow/dashboard-agents-v2.html /var/www/html/teleflow/dashboard.html
    echo -e "${GREEN}✓${NC} Dashboard copiado"
else
    echo -e "${RED}⚠${NC} dashboard-agents-v2.html no encontrado en /tmp/teleflow/"
fi

if [ -f "/tmp/teleflow/api/agents.php" ]; then
    cp /tmp/teleflow/api/agents.php /var/www/html/teleflow/api/agents.php
    echo -e "${GREEN}✓${NC} API PHP copiada"
else
    echo -e "${RED}⚠${NC} api/agents.php no encontrado"
fi

# 3. Copiar archivos markdown
echo -e "${YELLOW}[3/6]${NC} Copiando documentación..."
for file in "/tmp/teleflow/DEPLOYMENT_v2.md" "/tmp/teleflow/agents.md" "/tmp/teleflow/memory.md"; do
    if [ -f "$file" ]; then
        cp "$file" /var/www/html/teleflow/
        echo -e "${GREEN}✓${NC} $(basename $file) copiado"
    fi
done

# 4. Establecer permisos
echo -e "${YELLOW}[4/6]${NC} Estableciendo permisos..."
chown -R asterisk:asterisk /var/www/html/teleflow
chmod -R 755 /var/www/html/teleflow
chmod 644 /var/www/html/teleflow/dashboard.html
chmod 644 /var/www/html/teleflow/api/agents.php
echo -e "${GREEN}✓${NC} Permisos establecidos (asterisk:asterisk)"

# 5. Verificar Apache
echo -e "${YELLOW}[5/6]${NC} Verificando Apache..."
if systemctl is-active --quiet httpd; then
    echo -e "${GREEN}✓${NC} Apache está activo"
    systemctl restart httpd
    echo -e "${GREEN}✓${NC} Apache reiniciado"
else
    echo -e "${RED}✗${NC} Apache no está corriendo. Iniciando..."
    systemctl start httpd
    systemctl enable httpd
fi

# 6. Verificar conectividad
echo -e "${YELLOW}[6/6]${NC} Verificando conectividad de API..."
sleep 2
RESPONSE=$(curl -k -s "https://localhost/teleflow/api/agents.php?action=get_agents_data" | grep -o '"success":true' 2>/dev/null)

if [ ! -z "$RESPONSE" ]; then
    echo -e "${GREEN}✓${NC} API respondiendo correctamente"
else
    echo -e "${YELLOW}⚠${NC} API en espera o con problemas - revisar logs"
    echo "   tail -f /var/log/httpd/error_log"
fi

# Resumen
echo ""
echo "==========================================="
echo -e "${GREEN}✓ DEPLOY COMPLETADO${NC}"
echo "==========================================="
echo ""
echo "📊 ACCESO AL DASHBOARD:"
echo "   • HTTPS: https://pbx01.infratec.com.uy/teleflow/dashboard.html"
echo "   • LOCAL: https://localhost/teleflow/dashboard.html"
echo "   • MANUAL: https://localhost/teleflow/manual-usuario.html"
echo ""
echo "📁 ARCHIVOS INSTALADOS:"
echo "   • /var/www/html/teleflow/dashboard.html"
echo "   • /var/www/html/teleflow/api/agents.php"
echo "   • /var/www/html/teleflow/DEPLOYMENT_v2.md"
echo ""
echo "🔍 VERIFICACIÓN:"
curl -k -s "https://localhost/teleflow/api/agents.php?action=get_agents_data" | python3 -m json.tool 2>/dev/null | head -20
echo "..."
echo ""
echo "🛠️  TROUBLESHOOTING:"
echo "   • Logs: tail -f /var/log/httpd/error_log"
echo "   • Estado: systemctl status httpd"
echo "   • Restart: systemctl restart httpd"
echo ""
echo "✅ El dashboard está listo para usar!"
