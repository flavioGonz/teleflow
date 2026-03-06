# <div align="center"><img src="https://raw.githubusercontent.com/google/material-design-icons/master/png/communication/sensors/materialicons/48dp/2x/baseline_sensors_white_48dp.png" width="80" /><br>TeleFlow : Next-Gen PBX Control</div>

<div align="center">
  
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/badge/Version-18.2.0--stable-blue)](https://github.com/flavioGonz/teleflow)
[![Platform](https://img.shields.io/badge/Platform-Issabel_|_Asterisk-orange)](https://www.issabel.org/)
[![UI](https://img.shields.io/badge/UI-React_|_Tailwind-61dafb)](https://react.dev/)

</div>

---

## 🌟 Visión General
**TeleFlow** es una plataforma de gestión y monitoreo para sistemas de telefonía basados en **Asterisk/Issabel**, diseñada con una estética moderna, fluida y profesional. Orientada a Call Centers de alto rendimiento y entornos de portería inteligente, TeleFlow transforma la compleja administración de una PBX en una experiencia visual intuitiva y potente.

> [!IMPORTANT]
> TeleFlow no es solo un dashboard, es un ecosistema completo que integra WebRTC, diseño visual de IVR y diagnóstico SIP en tiempo real.

---

## 🚀 Características Principales

### 📊 1. Dashboard de Control Central
Visualización inmediata de la salud del sistema y métricas críticas de operación.
*   **Métricas de Hardware:** Uso de CPU, RAM y Almacenamiento en tiempo real.
*   **KPIs Telefónicos:** Conexiones activas, uptime del servidor y conteo de llamadas del día.
*   **Actividad Reciente:** Listado rápido de extensiones registradas y últimas grabaciones procesadas.

### 👥 2. Gestión Inteligente de Extensiones
Panel ABM (Alta, Baja, Modificación) avanzado con capacidades de monitoreo de red.
*   **Filtros Interactivos:** Segmentación instantánea por estado (Online, En Llamada, Offline).
*   **Diagnóstico de Red:** Visualización de IP de origen, MAC Address y latencia (RTT).
*   **Identificación de Dispositivos:** Distinción automática entre Softphones, Deskphones y Tablets.
*   **Side Drawer Pro:** Edición de parámetros técnicos (Secret, CallerID, Video, DTMF) sin perder el contexto de la lista.

### 📞 3. Cloud Softphone (WebRTC HD / PWA)
Un teléfono profesional premium integrado directamente en el navegador con experiencia nativa de iPhone.
*   **Tecnología SIP.js 0.20.0:** Conexión segura optimizada para Asterisk PJSIP vía WSS.
*   **Video HD & Audio:** Soporte para videollamadas con interfaz iOS-Style, desenfoque de fondo y controles de cristal.
*   **Audio Visualizer:** Onda animada en tiempo real (Real-time Decibel Meter) integrada en la cabecera de la llamada.
*   **Haptics & Sound:** Vibraciones táctiles (Haptic Feedback) y sonidos de ringback/llamada entrante para una experiencia física.
*   **Gestión de Dispositivos:** Conmutación rápida entre cámaras (Flip Camera) y control de altavoz (Speakerphone).
*   **Notificaciones PWA:** Soporte completo para "Add to Home Screen" con notificaciones nativas y ejecución en segundo plano.
*   **Dialpad Táctico:** Teclado numérico con efectos de presión, historial detallado y avatares dinámicos.

### 🔗 4. Call Center & Monitoreo en Vivo
Herramientas críticas para supervisores y gestores de tráfico telefónico.
*   **Llamadas en Vivo:** Monitorización con timers dinámicos y detalles de origen/destino.
*   **Gestión de Colas:** Supervisión de llamadas en espera y estrategias de distribución (Ringall, RoundRobin, etc.).
*   **Grupos de Timbrado:** Configuración visual de grupos con estados dinámicos de sus miembros.
*   **CDR Avanzado:** Historial detallado con filtros por fecha y búsqueda rápida.

### 🎨 5. Visual IVR Designer
Diseñador de flujos interactivos para menús de voz.
*   **Interfaz Drag & Drop:** Basado en React Flow para una construcción intuitiva.
*   **Nodos de Acción:** Reproducción de audios, menús de opciones e inserción en colas.

### 📈 6. Reportes Analíticos con Chart.js
Análisis de datos para la toma de decisiones informadas.
*   **Tendencias Diarias:** Gráficas de volumen de llamadas (Contestadas vs Fallidas).
*   **Top Performance:** Rankings de internos más activos y destinos más frecuentes.

### 🛡️ 7. Diagnóstico & Debug SIP
Panel de control para administradores de sistemas.
*   **Logger en Tiempo Real:** Visualización directa de eventos de Asterisk PJSIP/SIP.
*   **Filtros de Seguridad:** Detección de intentos de registro fallidos y errores de autenticación.

---

## 📸 Capturas de Pantalla (Preview)

| Dashboard Principal | Softphone WebRTC |
| :---: | :---: |
| ![Dashboard Placeholder](https://via.placeholder.com/600x400/0f0f1a/ffffff?text=Dashboard+TeleFlow) | ![Softphone Placeholder](https://via.placeholder.com/600x400/0f0f1a/ffffff?text=WebRTC+Softphone) |

| Designer IVR | Gestión de Extensiones |
| :---: | :---: |
| ![IVR Designer Placeholder](https://via.placeholder.com/600x400/0f0f1a/ffffff?text=IVR+Designer) | ![Extensions Placeholder](https://via.placeholder.com/600x400/0f0f1a/ffffff?text=Extensions+Panel) |

---

## 🛠️ Requisitos Técnicos

### Servidor (Core)
*   **PBX:** Issabel 4+, Asterisk 13/16/18+ (PJSIP recomendado).
*   **OS:** CentOS 7 / Rocky Linux / Oracle Linux (Soportados por Issabel).
*   **Web:** Apache 2.4+ / Nginx.
*   **PHP:** 7.4 o superior (con `pdo_mysql`, `sqlite3`).
*   **Base de datos:** MySQL (Base de datos Asterisk) y SQLite (para gestión de ACL).

### Red / Seguridad
*   Certificado SSL Válido (Obligatorio para WebRTC/WSS).
*   Puertos abiertos: `80/443 (HTTP/S)`, `5060/5061 (SIP)`, `8089/WSS Proxy`.

---

## ⚙️ Instalación

1.  **Clonar repositorio:**
    ```bash
    cd /var/www/html/
    git clone https://github.com/flavioGonz/teleflow.git
    chown -R asterisk:asterisk teleflow
    ```

2.  **Configuración de Base de Datos:**
    Asegúrate de que la API tenga acceso a las tablas de Asterisk. El archivo de configuración principal de la API se encuentra en `api/index.php`.

3.  **Habilitar WebSockets en Asterisk:**
    En `http_additional.conf` o `http_custom.conf`:
    ```ini
    [general]
    enabled=yes
    bindaddr=0.0.0.0
    bindport=8088
    tlsenable=yes
    tlsbindaddr=0.0.0.0:8089
    tlscertfile=/etc/asterisk/keys/asterisk.pem
    ```

4.  **Configuración de Proxy WSS (Apache):**
    Añade esto a tu VirtualHost SSL:
    ```apache
    ProxyPass /ws wss://127.0.0.1:8089/ws
    ProxyPassReverse /ws wss://127.0.0.1:8089/ws
    ```

5.  **Acceso:**
    Navega a `https://tu-servidor/teleflow` e inicia sesión con tus credenciales de administrador de Issabel.

---

## 📱 PWA & Mobile
TeleFlow es 100% responsive y está optimizado como **Progressive Web App (PWA)**.
*   **Instalable:** En iPhone (Add to Home Screen) y Android/Desktop.
*   **Modo Oscuro:** Adaptación automática según preferencia del sistema o toggle manual.

---

## 🤝 Contribuciones
¡Las contribuciones son bienvenidas! Por favor, abre un Issue o envía un Pull Request para mejorar TeleFlow.

---

## 📄 Licencia
Este proyecto está bajo la Licencia MIT - mira el archivo [LICENSE](LICENSE) para detalles.

---

<div align="center">
Desarrollado con ❤️ por <b>Infratec Uruguay</b>
</div>
