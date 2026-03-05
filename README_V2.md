# 📊 Teleflow Agent Monitoring v2.0

## 📌 Resumen Ejecutivo

**Teleflow Agent Monitoring v2.0** es un dashboard profesional en tiempo real para monitoreo de agentes de call center en **IISABEL 5 con Asterisk 16+**.

### Características Clave
- ✅ **Softphone WebRTC (Beta):** Teléfono incorporado estilo *Grandstream Wave* con soporte para Video.
- ✅ **Badges en Tiempo Real:** Contadores dinámicos en el menú para Extensiones Online, Llamadas en Vivo y Colas.
- ✅ **Gestión avanzada de Extensiones:** Edición de claves SIP, cambio de tipo de dispositivo y autoconfiguración DTLS/ICE.
- ✅ **Persistencia de Sesión:** El softphone recuerda tu extensión y se auto-conecta al refrescar la página.
- ✅ SIN dependencias Node.js (puro PHP + SIP.js + React)
- ✅ 100% compatible con Apache + Asterisk PJSIP
- ✅ **Seguridad:** Soporte completo para WSS (WebSocket Secure) a través de proxy.

## 🚀 Acceso Rápido

```
Dashboard:        https://pbx01.infratec.com.uy/teleflow/dashboard.html
Manual Usuario:   https://localhost/teleflow/manual-usuario.html
Instalación:      sudo /var/www/html/teleflow/deploy.sh
```

## 📁 Estructura del Proyecto

```
/var/www/html/teleflow/
├── dashboard.html              ← DASHBOARD PRINCIPAL (v2)
├── dashboard-agents.html       ← Versión anterior
├── api/
│   └── agents.php              ← API REST (Asterisk CLI + MySQL)
├── manual-usuario.html         ← Guía interactiva para usuarios
├── README_V2.md                ← Este archivo
├── DEPLOYMENT_v2.md            ← Guía de instalación detallada
├── agents.md                   ← Especificación técnica completa
├── memory.md                   ← Arquitectura avanzada
├── IMPLEMENTATION.md           ← Guía de implementación
├── schema.sql                  ← Esquema de base de datos
└── deploy.sh                   ← Script de instalación automática
```

## 🛠️ Stack Tecnológico

| Componente | Tecnología |
|-----------|-------------|
| **Frontend** | React 18 + Tailwind CSS + Material Icons |
| **Backend** | PHP 7.4+ + Apache 2.4 |
| **PBX** | Asterisk 16+ (PJSIP module) |
| **BD** | MySQL 5.7+ (asteriskcdrdb) |
| **Real-time** | Polling automático (5 segundos) |
| **PWA** | Native Notifications API |

## 📊 Características Principales

### 1. **Dashboard con Métricas en Vivo**
- Lista de agentes con estado actualizado automáticamente
- Llamadas del día, AHT, IP/MAC de origen
- Auto-actualización cada 5 segundos (sin Socket.io)
- Indicadores visuales (badges de color, puntos pulsantes)

### 2. **Filtros y Búsqueda Inteligente**
- Buscar por nombre, interno o IP
- Filtrar por estado (Online, Busy, Offline)
- Actualización manual con un clic
- Resultados en tiempo real

### 3. **Detalles de Agente**
- Modal con información técnica (IP, MAC, RTT)
- Métricas completas (llamadas, AHT, ACW)
- Indicador de llamada activa con duración
- Botón de notificación de prueba

### 4. **Sistema de Notificaciones PWA**
- 7 tipos de alertas personalizables
- Funcionan incluso con dashboard cerrado
- Configuración guardada en localStorage
- Requiere HTTPS

### 5. **Tema Claro/Oscuro**
- Toggle desde panel de configuración
- Respeta preferencias del navegador
- Guardado automático entre sesiones

## 🔔 Sistema de Notificaciones PWA

### Tipos de Alertas Disponibles

| Icono | Tipo | Evento | Ejemplo |
|-------|------|--------|---------|
| 🔓 | Agente Conectado | Login | "Agente #1001 conectado" |
| 🔒 | Agente Desconectado | Logout | "Agente #1002 desconectado" |
| 🔄 | Cambio de Estado | Status change | "#1001 cambió a BUSY" |
| 📞 | Llamada Recibida | Inbound call | "#1003 recibió llamada" |
| ❌ | Llamada Perdida | Missed call | "#1004 perdió llamada" |
| ⚠️ | Alerta de Cola | Queue alert | "Cola espera 5 clientes" |
| ☕ | Agente en Break | Break time | "#1005 en break" |

### Activación Paso a Paso

```
1. Haz clic en ⚙️ (Configuración) - arriba a la derecha
2. Verás panel "Configuración" con 7 toggles
3. Activa los tipos de notificaciones que deseas
4. Haz clic en "🔔 Habilitar Notificaciones"
5. Autoriza en el popup del navegador que aparecerá
6. ¡Listo! Las notificaciones se guardan automáticamente
```

### Requisitos para Notificaciones

- ✅ Debes estar en **HTTPS** (no HTTP)
- ✅ Has hecho clic en "🔔 Habilitar Notificaciones"
- ✅ Autorizaste notificaciones en el navegador
- ✅ El toggle de ese tipo de alerta está **activo**
- ✅ Las preferencias se guardan en localStorage

## 📡 API REST - Endpoints

### GET `/teleflow/api/agents.php?action=get_agents_data`
Obtiene lista de todos los agentes con estado en vivo.

**Respuesta:**
```json
{
  "success": true,
  "agents": [
    {
      "ext": "1001",
      "name": "Juan García",
      "status": "ONLINE",          // ONLINE|BUSY|OFFLINE
      "ip": "192.168.1.120",
      "mac": "00:1A:2B:3C:4D:5E",
      "rtt": "2ms",
      "in_call": 0,                // segundos si > 0
      "total_calls": 24,           // del día actual
      "avg_aht": "5:32",           // MM:SS
      "acw": "1:15"                // after call work
    }
  ]
}
```

### GET `/teleflow/api/agents.php?action=get_agent&ext=1001`
Obtiene información detallada de un agente específico.

### GET `/teleflow/api/agents.php?action=get_queues`
Información de colas de espera.

### POST `/teleflow/api/agents.php?action=set_agent_status`
Cambia estado de un agente.

**Body:**
```json
{
  "ext": "1001",
  "status": "ONLINE|AWAY|OFFLINE"
}
```

## 🔧 Configuración Técnica

### Requisitos Mínimos del Sistema

| Componente | Versión Mínima | Recomendada |
|-----------|---------------|----|
| IISABEL | 5.0 | 5.x (actual) |
| Asterisk | 16.0 | 18.x+ |
| Apache | 2.4 | 2.4.51+ |
| PHP | 7.4 | 8.0+ |
| MySQL | 5.7 | 8.0 |
| Navegador | Moderno | Chrome 90+, Firefox 88+, Safari 15+ |

### Conectividad Requerida

La API PHP accede a:
1. **Asterisk CLI** - Ejecuta comandos pjsip/core show
2. **MySQL (asteriskcdrdb)** - Lee CDR y grabaciones
3. **HTTPS** - Para notificaciones PWA

### Permisos Necesarios

```bash
# Usuario apache necesita ejecutar asterisk CLI
# Verificar:
sudo -u apache /usr/sbin/asterisk -rx "core show version"

# Si falla, agregar a sudoers:
sudo visudo
# Agregar esta línea:
apache ALL=(asterisk) NOPASSWD: /usr/sbin/asterisk -rx *
```

## 📊 Interfaz de Usuario Explicada

### Elementos Principales

**Encabezado:**
- Título "Agent Monitoring"
- Contador: "X agentes"
- Botón ⚙️ Configuración

**Controles de Filtrado:**
```
🔍 [Buscar por nombre/interno/IP...]
[Dropdown: Todos los Estados ▼]
[🔄 Actualizar]
```

**Lista de Agentes (Tabla):**
| Columna | Contenido | Significado |
|---------|-----------|-------------|
| **Agente** | #1001 Juan | Interno y nombre |
| **Estado** | ● ONLINE | Verde=Online, Amarillo=Busy, Gris=Offline |
| **Llamada Actual** | 2:45 (rojo pulsante) | Duración actual o "Disponible" |
| **Llamadas/AHT** | 24 / 5:32 | Total del día y tiempo promedio |
| **Red** | 192.168.1.120 | IP de origen |

**Tarjetas de Resumen (Abajo):**
```
[Agentes Online: 15/20]  [En Llamada: 8]
[Total Llamadas: 247]    [AHT Promedio: 5:28]
```

### Modales Emergentes

**Modal de Detalles (clic en agente):**
- Información técnica (IP, MAC, RTT)
- Métricas (llamadas, AHT, ACW)
- Indicador de llamada activa
- Botón de prueba de notificación

**Modal de Configuración (⚙️):**
- 7 toggles de notificaciones
- Opciones de tema (oscuro/claro)
- Botón de habilitar notificaciones

## 🛠️ Troubleshooting

### ❌ "No se pudo conectar a la API"

```bash
# Verificar que API responde
curl -k https://localhost/teleflow/api/agents.php?action=get_agents_data

# Si da error, ver logs:
tail -f /var/log/httpd/error_log

# Verificar Apache está corriendo:
systemctl status httpd
```

### ❌ "No hay agentes en la lista"

```bash
# Verificar que Asterisk está corriendo:
systemctl status asterisk

# Ver agentes registrados:
sudo asterisk -rx "pjsip show endpoints"

# Verificar que apache puede ejecutar asterisk:
sudo -u apache /usr/sbin/asterisk -rx "core show version"
```

### ❌ "Las notificaciones no funcionan"

Checklist:
- [ ] ¿Estás en **HTTPS**? (no HTTP)
- [ ] ¿Hiciste clic en "🔔 Habilitar Notificaciones"?
- [ ] ¿Autorizaste notificaciones en el navegador?
- [ ] ¿El toggle de ese tipo está **activo**?
- [ ] ¿Tu navegador no tiene bloqueadas las notificaciones?

Limpiar permisos en Chrome:
```
chrome://settings/content/notifications
→ Buscar pbx01.infratec.com.uy
→ Permitir notificaciones
```

### ⚠️ "Los datos se ven muy antiguos"

El dashboard se auto-actualiza cada 5 segundos. Si necesitas más rápido:

1. Haz clic en "🔄 Actualizar" (manual inmediato)
2. Para cambiar intervalo, editar dashboard.html:
   ```javascript
   // Buscar línea ~550:
   pollIntervalRef.current = setInterval(loadAgents, 5000);
   // Cambiar 5000 a 3000 para 3 segundos
   // Luego: sudo systemctl restart httpd
   ```

### 📉 "Base de datos no conecta"

```bash
# Verificar MySQL
mysql -u asterisk -p -D asteriskcdrdb -e "SELECT COUNT(*) FROM cdr;"

# Ver credenciales en:
/var/www/html/teleflow/api/agents.php
# Línea ~25-30 tiene PDO connection
```

## 📚 Documentación Adicional

| Archivo | Contenido |
|---------|-----------|
| [manual-usuario.html](manual-usuario.html) | Guía interactiva para usuarios finales |
| [DEPLOYMENT_v2.md](DEPLOYMENT_v2.md) | Instrucciones de instalación detalladas |
| [agents.md](agents.md) | Especificación técnica completa del módulo |
| [memory.md](memory.md) | Arquitectura avanzada (memory, routing) |
| [IMPLEMENTATION.md](IMPLEMENTATION.md) | Guía de implementación con ejemplos de código |
| [schema.sql](schema.sql) | Esquema de base de datos (10 tablas) |

## ✅ Checklist de Post-Instalación

Antes de considerar que está listo para producción:

- [ ] Apache está activo y reiniciado
- [ ] Archivos en `/var/www/html/teleflow/` con permisos correctos
- [ ] Permisos: `asterisk:asterisk`, modo `755`
- [ ] API responde: `curl -k https://localhost/teleflow/api/agents.php?action=get_agents_data`
- [ ] Asterisk CLI accesible desde Apache: `sudo -u apache /usr/sbin/asterisk -rx "core show version"`
- [ ] MySQL conecta correctamente
- [ ] Dashboard carga sin errores en navegador
- [ ] Se pueden solicitarnotificaciones PWA
- [ ] HTTPS funciona en https://pbx01.infratec.com.uy/teleflow/
- [ ] Manual de usuario accesible y legible

## 🚀 Próximos Pasos (Expansiones Futuras)

### Fase 2: Nuevas Características
- [ ] Sección de Grabaciones (playback + filtros)
- [ ] Sección de Reportes (gráficas, exportación)
- [ ] Gestión de Colas
- [ ] Heat map de agentes por ubicación
- [ ] Historial de cambios de estado

### Fase 3: Integraciones
- [ ] Sincronización LDAP
- [ ] API para apps de escritorio
- [ ] Webhooks para eventos
- [ ] Dashboard de métricas en vivo

## 🔐 Consideraciones de Seguridad

### HTTPS Obligatorio
- Las notificaciones PWA **requieren HTTPS**
- pbx01.infratec.com.uy usa SSL con certificado válido
- Sin HTTPS, las notificaciones no funcionarán

### Validación de Sesión (Opcional)
En `api/agents.php`, está comentada validación de sesión. Para habilitar:
```php
session_start();
if (!isset($_SESSION['tf_user'])) {
    http_response_code(401);
    exit;
}
```

### CORS (Cross-Origin Resource Sharing)
La API permite acceso desde cualquier origen por defecto. Para restringir:
```php
header('Access-Control-Allow-Origin: https://pbx01.infratec.com.uy');
```

## 📞 Soporte y Reportes de Bugs

### Información a Incluir en Reportes

1. **Descripción clara** del problema
2. **Pasos para reproducir** exactamente
3. **Captura de pantalla** si es posible
4. **Navegador y versión** (Chrome 99, Firefox 107, etc.)
5. **Horario exacto** cuando ocurrió
6. **Logs relevantes** si aplica:
   ```bash
   # Para logs de Apache:
   tail -n 50 /var/log/httpd/error_log
   ```

### Contacto

- **Email Soporte:** soporte@infratec.com.uy
- **Documentación:** [manual-usuario.html](manual-usuario.html)
- **Administrador IISABEL:** [Tu contacto]

## 📝 Licencia y Créditos

**Teleflow Agent Monitoring v2.0**
- Desarrollado para IISABEL 5
- Compatible con Asterisk 16+
- Frontend: React 18 + Tailwind CSS
- Backend: PHP + Asterisk CLI + MySQL
- Basado en arquitectura modular de Teleflow

## 📅 Changelog

### v2.1 (2025-03-05 - Actualización Mayor)
- ✨ **Módulo Softphone Rediseñado:** Interfaz profesional tipo Wave con panel de contactos integrado y soporte Video WebRTC.
- ✨ **Badges Dinámicos:** El menú lateral ahora muestra contadores en vivo de extensiones online y llamadas activas.
- ✨ **Persistencia SIP:** Implementado auto-login del softphone mediante localStorage (soporta F5).
- ✨ **Mejoras en CRUD Extensiones:** Ahora permite ver/cambiar claves SIP y tipo de dispositivo (Audio/Video/WebRTC) con auto-aprovisionamiento.
- 🐛 **Fix:** Corregido error de registro SIP "silencioso" con sistema de diagnóstico y toasts de error precisos.
- 🐛 **Fix:** Sincronización forzada con Asterisk al crear/editar extensiones (retrieve_conf + restart PJSIP).

### v2.0 (2025-03-05)
- ✨ **Rediseño completo sin dependencias Node.js**
- ✨ **Polling automático cada 5 segundos**
- ✨ **Tema claro/oscuro integrado**
- ✨ **Manual de usuario HTML interactivo**
- ✨ **Script de deploy automático**
- ✨ **API simplificada con 5 endpoints**
- 🐛 **Fix:** Soporte completo para Asterisk 16+ PJSIP
- 🐛 **Fix:** Permisos de usuario apache/asterisk

### v1.0 (2025-01-21)
- 🎉 Versión inicial con Socket.io
- Dashboard básico con React
- Notificaciones PWA

---

**Versión:** 2.0  
**Última actualización:** 5 de marzo de 2025  
**Compatible con:** IISABEL 5 + Asterisk 16+ + Apache 2.4 + PHP 7.4+  
**Mantenedor:** Equipo de Desarrollo Teleflow

**Estado:** ✅ Listo para Producción
