# Teleflow ↔ Asterisk AMI — Setup y diagnóstico

Esta guía explica cómo conectar una instancia de **Teleflow Horizon** a una PBX
**Issabel / Asterisk** vía AMI (Asterisk Manager Interface), y cómo diagnosticar
problemas en instancias nuevas.

## Arquitectura

```
┌─────────────────────┐   AMI (TCP 5038)    ┌──────────────────────┐
│  Teleflow VM        │ ──────────────────► │  PBX (Issabel)       │
│  Apache + PHP 8.3   │     Login + cmd     │  Asterisk 18 + FPBX  │
│  Realtime hub Node  │ ◄────────────────── │  manager.conf user   │
└─────────────────────┘     Events           └──────────────────────┘
```

Teleflow nunca habla con Asterisk via CLI/SSH/dialplan: **todo va por AMI** (y
MySQL directo para datos). El usuario AMI dedicado se llama `teleflow` por
convención y tiene permisos acotados.

---

## 1. Configuración en la PBX

### 1.1 Habilitar el daemon AMI

**Archivo:** `/etc/asterisk/manager.conf`

```ini
[general]
enabled = yes
bindaddr = 0.0.0.0          ; o IP específica si querés limitar interfaces
port = 5038
#include manager_general_additional.conf
```

> En Issabel/FreePBX moderno suele venir habilitado por defecto. Confirmar con
> `asterisk -rx "manager show settings"` → debe decir `Manager (AMI): Yes`.

### 1.2 Crear el usuario AMI dedicado

**⚠️ IMPORTANTE:** Crear el bloque en `manager_custom.conf` (NO en
`manager_additional.conf` que FreePBX regenera al hacer "Apply Config").

**Archivo:** `/etc/asterisk/manager_custom.conf` (créalo si no existe)

```ini
; Teleflow Horizon — usuario AMI dedicado (no usar admin master)
[teleflow]
secret = REEMPLAZAR_CON_PASSWORD_FUERTE
deny  = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
permit = <IP_DE_LA_VM_TELEFLOW>/255.255.255.255

read  = system,call,log,verbose,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate,message
write = system,call,log,verbose,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate,message
```

**Sustituí:**
- `REEMPLAZAR_CON_PASSWORD_FUERTE` — generá con `openssl rand -base64 24`
- `<IP_DE_LA_VM_TELEFLOW>` — IP LAN del server donde corre Teleflow

### 1.3 Recargar manager (sin reiniciar Asterisk)

```bash
asterisk -rx "manager reload"
```

### 1.4 Verificar que el usuario está cargado

```bash
asterisk -rx "manager show user teleflow"
```

**Esperado:**
```
username: teleflow
            secret: <Set>
               ACL: yes
         read perm: system,call,log,verbose,command,agent,user,config,...
        write perm: system,call,log,verbose,command,agent,user,config,...
ACL: (unnamed)
  0:  deny - 0.0.0.0/0.0.0.0
  1: allow - 127.0.0.1/255.255.255.255
  2: allow - <IP_DE_LA_VM_TELEFLOW>/255.255.255.255
```

### 1.5 Verificar que el puerto está escuchando

```bash
ss -tlnp | grep :5038
# Esperado: LISTEN ... 0.0.0.0:5038 ... users:(("asterisk",...))
```

### 1.6 Firewall

Si el PBX tiene firewall activo, permitir entrada en TCP 5038 desde la IP de
Teleflow:

```bash
# firewalld
firewall-cmd --add-rich-rule='rule family="ipv4" source address="<IP_TELEFLOW>" port port="5038" protocol="tcp" accept' --permanent
firewall-cmd --reload

# iptables
iptables -I INPUT -p tcp -s <IP_TELEFLOW> --dport 5038 -j ACCEPT
```

---

## 2. Configuración en Teleflow

**Archivo:** `/var/www/teleflow/config.php`

```php
<?php
$DB_HOST       = '<IP_PBX>';                              // mismo host que MySQL del PBX
$DB_USER       = 'tfremote';
$DB_PASS       = '<PASSWORD_MYSQL>';

$TF_DB_NAME    = 'teleflow';
$PBX_DB_NAME   = 'asterisk';
$CDR_DB_NAME   = 'asteriskcdrdb';

$AMI_HOST      = '<IP_PBX>';                              // típicamente igual a DB_HOST
$AMI_PORT      = 5038;
$AMI_USER      = 'teleflow';                              // ← el username del bloque [teleflow]
$AMI_PASS      = 'REEMPLAZAR_CON_PASSWORD_FUERTE';        // ← mismo `secret =` de manager.conf

$RECORDINGS_PATH = '/var/cache/teleflow/recordings';
```

> Después de editar `config.php`, reiniciar el realtime hub:
> ```
> systemctl restart teleflow-realtime
> ```

---

## 3. Checklist diagnóstico

Si Teleflow no conecta a AMI, ejecutá estos tests **en orden** desde la VM
Teleflow. El primero que falle te dice dónde está el problema.

### Test 1 — Conectividad de red

```bash
ping -c 2 <IP_PBX>
```

❌ **Falla:** problema de red base. Revisar routing, VLANs, gateways.

### Test 2 — Puerto TCP 5038 alcanzable

```bash
nc -zv <IP_PBX> 5038
# o:
timeout 3 bash -c '</dev/tcp/<IP_PBX>/5038' && echo OK || echo NO
```

❌ **`Connection refused`:** AMI no está habilitado (`enabled = no`) o `bindaddr` excluye esta interfaz.

❌ **`Connection timed out`:** firewall bloqueando entre Teleflow y PBX.

### Test 3 — Handshake AMI manual

```bash
(
  printf 'Action: Login\r\nUsername: teleflow\r\nSecret: <PASSWORD>\r\nEvents: off\r\n\r\n'
  printf 'Action: Logoff\r\n\r\n'
  sleep 1
) | nc -w 3 <IP_PBX> 5038
```

✅ **Respuesta esperada:**
```
Asterisk Call Manager/7.0.3
Response: Success
Message: Authentication accepted

Response: Goodbye
Message: Thanks for all the fish.
```

❌ **`Response: Error / Message: Permission denied`:**
   → La IP de Teleflow NO está en `permit=` del bloque `[teleflow]`.
   → Solución: agregar IP y `manager reload`.

❌ **`Response: Error / Message: Authentication failed`:**
   → Password equivocado. Revisar que `secret=` del manager.conf coincida exactamente con `$AMI_PASS` (cuidado con espacios al principio/final).

❌ **`Response: Error / Message: Authorization failed - no such manager user`:**
   → El bloque `[teleflow]` no existe o se borró.
   → Solución: agregar a `manager_custom.conf` y `manager reload`.

❌ **No responde nada / timeout:**
   → Verificar `bindaddr` (puede estar en `127.0.0.1` solamente) y firewall.

### Test 4 — Verificar user cargado en Asterisk RAM

En la PBX:

```bash
asterisk -rx "manager show user teleflow"
```

Debe mostrar la ACL con `allow - <IP_TELEFLOW>/...`. Si no aparece la IP, agregar
y `manager reload`. Si el comando dice `Manager user 'teleflow' does not exist`,
el bloque no se cargó (revisar que esté en `manager_custom.conf` Y que `manager
reload` se haya ejecutado).

### Test 5 — Test desde PHP-FPM

A veces el problema no es AMI sino que el PHP del Teleflow no puede salir por TCP
(SELinux/AppArmor/network namespace):

```bash
php -r '
$f = @stream_socket_client("tcp://<IP_PBX>:5038", $e, $s, 3);
if (!$f) { echo "FAIL: $s\n"; exit(1); }
fwrite($f, "Action: Login\r\nUsername: teleflow\r\nSecret: <PASSWORD>\r\nEvents: off\r\n\r\n");
echo fread($f, 1024);
fclose($f);
'
```

Si CLI funciona pero web no, revisar:
- SELinux: `setsebool -P httpd_can_network_connect 1`
- AppArmor: perfil de apache2/php-fpm
- `disable_functions` en `/etc/php/8.x/fpm/php.ini` no debe incluir `stream_socket_client`

### Test 6 — Verificar en logs del realtime hub

```bash
journalctl -u teleflow-realtime -f --since "1 minute ago"
```

Debe mostrar:
```
[ami] connected
[ami] event=FullyBooted ...
[realtime] connected
```

Si dice `connect ECONNREFUSED` o `auth failed`, volver al Test 3.

---

## 4. Causas comunes en instancias nuevas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `nc` da `connection refused` | AMI no habilitado o `bindaddr` mal | `manager.conf` → `enabled = yes`, `bindaddr = 0.0.0.0`, `manager reload` |
| `nc` da timeout | Firewall PBX o red | Abrir TCP 5038 desde IP Teleflow |
| `Permission denied` en login | IP Teleflow no en `permit=` | Agregar `permit = <IP>/255.255.255.255` al bloque del user |
| `Authentication failed` | Password mismatch | Verificar que `secret=` y `$AMI_PASS` son **idénticos byte-a-byte** |
| `no such manager user` | Bloque `[teleflow]` no existe | Crear en `manager_custom.conf` |
| Funcionó y luego dejó | `retrieve_conf` regeneró `manager_additional.conf` y borró el user | Mover bloque a `manager_custom.conf` (Issabel respeta ese) |
| Read/write sin permisos | `read=` o `write=` faltan categorías | Usar la lista completa de arriba |
| Conecta pero no llegan events | Event filter del user | Quitar `eventfilter=` excesivos, o usar `Events: on` en login |
| Conecta pero queries fallan | Falta `write=command` | Agregar `command` a `write=` (necesario para `Action: Command`) |
| Web Teleflow no conecta pero `nc` sí | SELinux/AppArmor del PHP-FPM | `setsebool -P httpd_can_network_connect 1` |
| Conexión OK pero CDR vacíos | Falta `read=cdr,reporting` | Agregar al `read=` |

---

## 5. Notas sobre Issabel/FreePBX

Issabel/FreePBX tiene varios archivos `manager_*.conf`:

| Archivo | Quién lo edita | Para qué |
|---|---|---|
| `manager.conf` | Issabel installer | Base — incluye los demás |
| `manager_general_additional.conf` | FreePBX (auto) | `[general]` extra |
| `manager_additional.conf` | FreePBX (auto, **REGENERADO**) | Users de FOP2, etc. **No tocar** |
| `manager_custom.conf` | **Vos / yo** | Users custom (e.g. teleflow). **Persistente** |

Regla: cualquier user que crees a mano va a `manager_custom.conf`. Si lo ponés
en `manager_additional.conf`, FreePBX te lo borra al primer "Apply Config".

---

## 6. Referencia de comandos útiles

```bash
# Ver settings actuales del manager
asterisk -rx "manager show settings"

# Ver users configurados
asterisk -rx "manager show users"

# Ver detalle de un user (ACL incluida)
asterisk -rx "manager show user teleflow"

# Ver conexiones AMI activas
asterisk -rx "manager show connected"

# Recargar config (sin restart Asterisk)
asterisk -rx "manager reload"
# o equivalente desde la UI:
fwconsole reload

# Probar conexión desde la VM Teleflow
(printf 'Action: Login\r\nUsername: teleflow\r\nSecret: <PASS>\r\nEvents: off\r\n\r\nAction: Ping\r\n\r\nAction: Logoff\r\n\r\n'; sleep 2) | nc -w 5 <IP_PBX> 5038
```

---

## 7. Permisos mínimos requeridos por Teleflow

Para que Teleflow funcione completo necesita estos permisos AMI:

| Permiso | Para qué lo usa Teleflow |
|---|---|
| `system` | `core show uptime`, info del servidor |
| `call` | Eventos `Newchannel`, `Hangup`, `Bridge` (callcenter live) |
| `log` | Logs de Asterisk para debug |
| `verbose` | Output verbose del CLI |
| `command` | `Action: Command` (necesario para `sip show peers`, etc.) |
| `agent` | Estado de agentes (login/logout/pause) |
| `user` | Info de extensiones |
| `config` | Lectura de configs (`SIPshowpeer`, etc.) |
| `dtmf` | Eventos DTMF para apertura de puertas |
| `reporting` | CDR aggregations |
| `cdr` | Acceso a CDR |
| `dialplan` | Originate, transfer, etc. |
| `originate` | `Action: Originate` (vocear paging, transfer) |
| `message` | Sólo en `write` — para futuros features de SMS/MMS |

Quitar cualquiera de estos puede romper alguna feature. Si querés ser más
restrictivo, podés sacar `message` sin impacto inmediato.

---

## 8. Troubleshooting rápido (TL;DR)

```bash
# Desde la VM Teleflow:
nc -zv <IP_PBX> 5038                                       # ¿llega TCP?
(printf 'Action: Login\r\nUsername: teleflow\r\nSecret: <PASS>\r\nEvents: off\r\n\r\nAction: Logoff\r\n\r\n'; sleep 1) | nc -w 3 <IP_PBX> 5038

# En la PBX:
asterisk -rx "manager show user teleflow"                  # ¿ACL OK?
ss -tlnp | grep :5038                                       # ¿escucha?
tail -f /var/log/asterisk/full | grep -i manager           # logs en vivo
```

Si los 3 primeros dan OK pero Teleflow sigue sin conectar, mirar:

```bash
# En Teleflow:
journalctl -u teleflow-realtime -n 50                       # qué dice el hub
sudo -u www-data php /var/www/teleflow/<test>.php           # ¿PHP-FPM puede salir TCP?
```

---

**Última actualización:** 2026-05-24
**Versión Teleflow:** Horizon
**Versión PBX testeada:** Issabel 5 + Asterisk 18.19
