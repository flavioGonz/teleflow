# 🚀 TeleFlow | Infratec Next-Gen PBX Interface

TeleFlow es una interfaz de gestión telefónica moderna, diseñada para reemplazar la interfaz web tradicional de Issabel/Asterisk en entornos de alta demanda.

## 🛠️ Stack Tecnológico
- **Frontend:** React JS (SPA) con Tailwind CSS.
- **Backend:** PHP API Bridge (Conexión Asterisk CLI).
- **Métricas:** Sistema de monitoreo en tiempo real vía AJAX/Polling.
- **Mobile:** PWA (Progressive Web App) optimizada para iPhone.

## 📋 Requisitos e Instalación
Para instalar TeleFlow en una central Issabel 5 (Rocky Linux 8):

1. **Clonar el repositorio** en la raíz web:
   ```bash
   cd /var/www/html
   git clone https://github.com/flavioGonz/teleflow.git
   ```

2. **Permisos de Archivos:**
   ```bash
   chown -R asterisk:asterisk /var/www/html/teleflow
   chmod -R 755 /var/www/html/teleflow
   ```

3. **Permisos de Asterisk:**
   Asegurar que el usuario web pueda ejecutar comandos de Asterisk:
   ```bash
   chmod 755 /usr/sbin/asterisk
   ```

4. **Acceso:**
   Navegar a `https://TU_IP/teleflow/index.php`. El sistema utiliza las mismas credenciales de administrador de Issabel.

## 🚀 Funcionalidades Principales
- **Dashboard Visual 360°:** Mapa de flujos con React Flow.
- **Monitoreo de Agentes:** Estados Online/Busy/Offline con latencia RTT.
- **Gestión de Extensiones:** ABM completo (Nombre, Clave, Video, DTMF).
- **Sileo Notifications:** Avisos de llamadas en tiempo real arriba a la derecha.
- **Multi-Protocolo:** Soporte para PJSIP y SIP clásico.
- **Sistema de Avatares:** Carga y visualización de fotos de perfil.
