# 📦 TELEFLOW SETUP & DEPLOYMENT GUIDE

## 🎯 Objetivo General

Implementar un sistema completo de **monitoreo de agentes** y **gestión de contexto de llamadas** (Memory) para Teleflow en IISABEL 5.

---

## 📂 Archivos Nuevos Creados

| Archivo | Tamaño | Descripción |
|---------|--------|------------|
| `agents.md` | 7KB | 📖 Documentación completa del módulo de Agentes |
| `memory.md` | 10KB | 💾 Documentación del sistema de Memoria/Contexto |
| `dashboard-agents.html` | 21KB | 🎨 Dashboard HTML con Tailwind CSS (responsive, dark mode) |
| `IMPLEMENTATION.md` | 15KB | 🔧 Guía técnica de implementación paso a paso |
| `schema.sql` | 16KB | 🗄️ Script SQL con tablas, vistas y stored procedures |
| `SETUP.md` | Este archivo | 📋 Guía de instalación y deployment |

**Total: ~70KB de código y documentación**

---

## 🚀 Quick Start (5 pasos)

### 1. Actualizar repositorio Git
```bash
cd /var/www/html/teleflow
git pull origin main
# O si no está en git:
git init && git remote add origin https://github.com/flavioGonz/teleflow.git
git pull origin main
```

### 2. Crear tablas en base de datos
```bash
# Conectarse a MySQL
mysql -u root -p asterisk < schema.sql

# O manualmente:
mysql -u root -p
> USE asterisk;
> source /var/www/html/teleflow/schema.sql;
> EXIT;
```

### 3. Crear archivos PHP faltantes
```bash
# Crear directorio api si no existe
mkdir -p /var/www/html/teleflow/api

# Los endpoints de API necesitarán ser implementados
# Ver IMPLEMENTATION.md para el código
```

### 4. Establecer permisos
```bash
chown -R apache:apache /var/www/html/teleflow
chmod -R 755 /var/www/html/teleflow
chmod 644 /var/www/html/teleflow/*.{php,html,js,sql,md}
```

### 5. Acceder al dashboard
```
https://pbx01.infratec.com.uy/teleflow/dashboard-agents.html
```

---

## 🔨 Instalación Completa

### Paso 1: Verificar Requisitos

```bash
# PHP 7.4+ con PDO
php -v

# MySQL/MariaDB corriendo
systemctl status mysqld
# O: systemctl status mariadb

# Asterisk con PJSIP
asterisk -v

# Redis (opcional, para caché)
redis-cli ping
```

### Paso 2: Clonar/Actualizar Repositorio

```bash
cd /var/www/html
git clone https://github.com/flavioGonz/teleflow.git
cd teleflow
```

### Paso 3: Ejecutar Schema SQL

```bash
# Backup de BD existente (recomendado)
mysqldump -u root -p asterisk > /backup/asterisk_$(date +%Y%m%d).sql

# Aplicar schema
mysql -u root -p asterisk < schema.sql

# Verificar tablas
mysql -u root -p -e "USE asterisk; SHOW TABLES LIKE 'agent%';"
```

**Esperado:**
```
Tables_in_asterisk
agents
agent_notes
agent_sessions
agent_status_history
agent_daily_metrics
agent_skills
```

### Paso 4: Crear API endpoints

Crear archivo `/var/www/html/teleflow/api/agents.php`:

```bash
cp api/index.php api/agents.php
```

Editar y agregar los endpoints (ver código en IMPLEMENTATION.md)

### Paso 5: Configurar Permisos

```bash
# Usuario web (apache en CentOS/RHEL)
chown -R apache:apache /var/www/html/teleflow
chmod -R 755 /var/www/html/teleflow

# O si es nginx:
chown -R nginx:nginx /var/www/html/teleflow

# Carpeta uploads (para avatares)
chmod 777 /var/www/html/teleflow/uploads
chmod 777 /var/www/html/teleflow/uploads/avatars
```

### Paso 6: Configurar SSL/Proxy (NHX)

En la configuración del proxy NHX, asegurar que Teleflow reciba:

```
Backend: pbx01 (IP interna)
Frontend: pbx01.infratec.com.uy
Path: /teleflow/
SSL Certificate: ✅ Válido
```

### Paso 7: Reiniciar Servicios

```bash
systemctl restart httpd  # O: systemctl restart nginx
systemctl restart asterisk

# Verificar logs
tail -20 /var/log/httpd/error_log
tail -20 /var/log/asterisk/full
```

---

## 🧪 Pruebas Post-Instalación

### Test 1: Base de datos
```bash
mysql -u root -p asterisk -e "SELECT ext, name, status FROM agents LIMIT 5;"
```

**Esperado:** Lista de agentes

```
+-----+---------+--------+
| ext | name    | status |
+-----+---------+--------+
| 101 | Agent 1 | ONLINE |
+-----+---------+--------+
```

### Test 2: Acceso web
```bash
curl -s -k https://pbx01.infratec.com.uy/teleflow/ | head -20
```

### Test 3: Dashboard
```
https://pbx01.infratec.com.uy/teleflow/dashboard-agents.html
```

✅ Debería cargar y mostrar agentes (si están online en Asterisk)

### Test 4: API
```bash
# Sin autenticación (debería fallar con 403)
curl -s https://pbx01.infratec.com.uy/teleflow/api/agents.php?action=list_agents

# Con sesión autenticada
curl -s -b "PHPSESSID=..." https://pbx01.infratec.com.uy/teleflow/api/agents.php?action=list_agents
```

---

## 📊 Estructura de Base de Datos (Resumen)

```
asterisk/
├── agents                  # Extensiones/agentes
├── customers              # CRM de clientes
├── agent_notes            # Notas por llamada
├── agent_sessions         # Sesiones diarias
├── agent_status_history   # Historial de estados
├── agent_daily_metrics    # Métricas diarias
├── agent_skills           # Habilidades por agente
├── queues_config          # Configuración de colas
├── queue_events           # Eventos de cola en tiempo real
├── cdr (ampliada)         # Registros de llamadas extendidos
│
├── v_agents_summary_today          # Vista: Resumen hoy
├── v_sentiment_by_agent             # Vista: Sentimientos
└── v_weekly_agent_performance       # Vista: Performance semanal
```

---

## 🔌 Integración con Asterisk

### Event Listener (Webhook)

Setup en `/etc/asterisk/ari.conf`:

```ini
[general]
enabled = yes
bindaddr = 127.0.0.1
bindport = 8088
debug = no

[teleflow]
type = user
read_only = no
password = teleflow_secret
```

Luego crear `/var/www/html/teleflow/api/webhook.php` para eventos en tiempo real.

---

## 📱 Acceso a Interfaces

| Interfaz | URL | Descripción |
|----------|-----|-------------|
| Dashboard Agentes | `/teleflow/dashboard-agents.html` | Monitoreo en tiempo real |
| Admin Principal | `/teleflow/` | Interfaz principal (si existe) |
| API Agentes | `/teleflow/api/agents.php` | REST API para agentes |
| API Memoria | `/teleflow/api/memory.php` | REST API para contexto |

---

## 🛠️ Troubleshooting

### Problema: "No database selected"
```bash
# Solución: Verificar BD asterisk existe
mysql -u root -p -e "SHOW DATABASES;"

# Si no existe, crear:
mysqladmin -u root -p create asterisk
```

### Problema: "Permission denied" en uploads
```bash
# Solución: Permisos en carpeta uploads
chmod 777 /var/www/html/teleflow/uploads
chmod 777 /var/www/html/teleflow/uploads/avatars
```

### Problema: "Extension not found" en Asterisk
```bash
# Verificar extensiones
asterisk -rx "pjsip show endpoints"

# Si vacío, revisar:
/etc/asterisk/pjsip.conf
/etc/asterisk/extensions.conf
```

### Problema: SSL Error (NHX Proxy)
```bash
# Verificar certificado
openssl s_client -connect pbx01.infratec.com.uy:443

# En NHX panel: Settings → SSL → Seleccionar certificado correcto
```

### Problema: CORS (JavaScript cliente-lado)
```bash
# En /var/www/html/teleflow/api/index.php agregar:
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

---

## 📈 Próximos Pasos (Roadmap)

### Fase 2: Integración Avanzada
- [ ] WebSocket real-time para actualizaciones live
- [ ] Supervisor whisper/barge-in implementation
- [ ] Quality scoring automation
- [ ] AI-powered call recommendations

### Fase 3: Analytics
- [ ] Dashboards de reporting con gráficos
- [ ] Análisis de sentimiento (NLP)
- [ ] Predictive quality alerts
- [ ] Agent coaching module

### Fase 4: Mobile
- [ ] App mobile para agentes
- [ ] Push notifications
- [ ] Mobile quality feedback

---

## 📞 Soporte

Para problemas o preguntas:
1. Revisar logs: `/var/log/asterisk/full`
2. Verificar BD: Ejecutar queries de test
3. Revisar documentación en `agents.md` y `memory.md`
4. Contactar: dev@infratec.com.uy

---

## 📝 Cambios en Producción (pbx01.infratec.com.uy)

Cuando esté listo para ir a producción:

```bash
# 1. Backup
mysqldump -u root -p asterisk > /backup/asterisk_pre_teleflow.sql
cp -r /var/www/html/teleflow /backup/teleflow_backup_$(date +%Y%m%d)

# 2. En horario de bajo tráfico
systemctl stop asterisk
mysql -u root -p asterisk < schema.sql
systemctl start asterisk

# 3. Verificar
asterisk -rx "core show version"
mysql -u root -p -e "USE asterisk; SELECT COUNT(*) FROM agents;"

# 4. Comunicar a usuarios
echo "Teleflow agents monitoring ahora disponible en https://pbx01.infratec.com.uy/teleflow/"
```

---

## ✅ Checklist de Implementación

- [ ] Clonar repositorio actualizado
- [ ] Ejecutar schema.sql en base de datos
- [ ] Crear archivos PHP faltantes (agents.php, memory.php)
- [ ] Configurar permisos (apache:apache, 755)
- [ ] Verificar acceso HTTPS con certificado
- [ ] Test: Dashboard cargando agentes
- [ ] Test: Cambio de estado de agente
- [ ] Test: Guardar notas de cliente
- [ ] Configurar auto-refresh en dashboard
- [ ] Documentación actualizada en /teleflow/
- [ ] Capacitar supervisores en uso
- [ ] Monitorear logs en primeros días

---

*Guía actualizada: 2026-03-05*
*Versión Teleflow: Next-Gen Agent & Memory Module*
*Compatible con: IISABEL 5, Asterisk 16+, MySQL 5.7+*
