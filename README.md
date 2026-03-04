# 🚀 TeleFlow | Infratec Next-Gen PBX Interface

TeleFlow es una interfaz de gestión telefónica moderna, diseñada para reemplazar la interfaz web tradicional de Issabel/Asterisk en entornos de alta demanda.

## 🛠️ Stack Tecnológico
- **Frontend:** React JS (SPA) con Tailwind CSS y Framer-style animations.
- **Backend:** PHP API Bridge (Conexión directa con Asterisk CLI).
- **Métricas:** Sistema de monitoreo en tiempo real con latencia RTT.
- **Visualización:** Mapa de flujos interactivo con React Flow.

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

3. **Acceso:**
   Navegar a `https://TU_IP/teleflow/index.php`. El sistema utiliza las mismas credenciales de administrador de Issabel.

## 🚀 Funcionalidades Incluidas (v8.1)
- [x] **Login Bridge:** Autenticación vinculada a la DB de ACL de Issabel.
- [x] **Dashboard 360:** Mapa de nodos dinámico con SIP CORE central.
- [x] **Gestión de Extensiones:** Lista premium con RTT, MAC y Avatares.
- [x] **Módulo de Colas:** Visualización de clientes en espera y estrategias.
- [x] **Sileo Notifications:** Avisos elásticos de llamadas en vivo.
- [x] **Dual Theme:** Cambio animado entre Modo Oscuro y Modo Claro.

---
Desarrollado por **Infratec** | *Innovación en Telecomunicaciones*
