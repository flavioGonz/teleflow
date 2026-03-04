# 🚀 TeleFlow | Infratec Next-Gen PBX

TeleFlow es una interfaz de gestión telefónica moderna, ultra-rápida y visual, diseñada como alternativa superior a la web de Issabel/Asterisk. Orientada a Call Centers, Portería Inteligente y profesionales IT.

## 🛠️ Stack Tecnológico
- **Frontend:** React JS + Tailwind CSS + React Flow (Visual Engine).
- **Backend:** PHP API Bridge (Conexión directa con Asterisk CLI & MariaDB).
- **Estética:** Ultra-Dark "Black Deep" con diseño minimalista y técnico.
- **PWA:** Optimizada para uso nativo en iPhone y iPad.

## 📋 Proceso de Instalación Limpio (Debian/Rocky)

1. **Clonar el repositorio:**
   ```bash
   cd /var/www/html
   git clone https://github.com/flavioGonz/teleflow.git
   ```

2. **Permisos de Archivos y Asterisk:**
   ```bash
   chown -R asterisk:asterisk /var/www/html/teleflow
   chmod 755 /usr/sbin/asterisk
   ```

3. **Acceso:**
   Navegar a `https://TU_IP/teleflow/index.php`. El sistema utiliza las credenciales de administrador de Issabel.

## 🚀 Roadmap 2026
- [x] **Dashboard Visual 360°:** Mapa de flujos en tiempo real con SIP CORE.
- [x] **Gestión de Extensiones Pro:** ABM completo con soporte de Video y Portería (DTMF).
- [x] **Monitor de Red:** Visualización de IP, MAC y RTT (Latencia).
- [x] **Sileo Notifications:** Sistema de alertas elásticas de llamadas entrantes.
- [ ] **Waveform Recordings:** Reproductor de grabaciones con ondas de audio.
- [ ] **WebRTC Integration:** Teléfono embebido directamente en la interfaz.

---
Desarrollado por **Infratec** | *Innovación en Telecomunicaciones*
