# Integración con la PBX (Asterisk / Issabel)

## PBX en producción

- **Host**: `10.1.1.7`
- **Stack**: Issabel 5 con Asterisk + chan_sip.
- **AMI**: puerto 5038, usuario `teleflow`, password en `/etc/asterisk/teleflow_agi.conf` y en `config.php` de la app web.
- **MariaDB**: usuario `tfremote` con permisos read/write a `teleflow`, read a `asterisk`/`asteriskcdrdb`, RW a `call_center.agent`.

## chan_sip vs PJSIP

Issabel viene con **chan_sip activo** (Use Count > 0) y chan_pjsip cargado pero sin uso. Esto importa para cualquier integración AMI.

### Formatos de member en colas

Cuando `QueueStatus` reporta miembros, vienen en 2 formatos:

| Tipo | Ejemplo |
|------|---------|
| **Dynamic** (registrados vía AGI `*7700`) | `Location: SIP/<ext>` con `Name: SIP/<ext>` |
| **Static** (config Issabel queues.conf) | `Location: Local/<ext>@from-queue/n` con `Name: <nombre humano>` |

### Rule importante para QueuePause / QueueRemove

Al ejecutar AMI `QueuePause` o `QueueRemove`, **el campo `Interface` debe coincidir exactamente con el `Location` reportado por QueueStatus**.

No construir `SIP/$ext` por hardcode. Hacer match de regexes:

```php
if (preg_match('/^(SIP|PJSIP)\/' . preg_quote($ext, '/') . '$/', $loc) ||
    preg_match('/^Local\/' . preg_quote($ext, '/') . '@/', $loc)) {
    // Usar $loc como Interface, no $ext
}
```

Los 3 endpoints que ya lo hacen bien: `agent_pause_commit.php`, `agent_unpause_commit.php`, `agent_logout_commit.php`.

## Feature codes (Asterisk dialplan)

Definidos en `dist/extensions_teleflow.conf`. Cuando se modifica, hay que sincronizar a `/etc/asterisk/extensions_custom.conf` en la PBX y hacer `asterisk -rx 'dialplan reload'`.

| Código | Acción | Backend |
|--------|--------|---------|
| `*7700` | Login agente con prefs guardadas | `teleflow_agent_login.agi` → valida creds → `agent_commit.php` hace QueueAdd |
| `*7700*N` | Login a cola mapeada al dígito N | Idem, `agent_commit.php` resuelve N vía tabla `queue_shortcut` |
| `*7700*N*M*K` | Login simultáneo a múltiples colas | Idem, parsea el patrón |
| `*7700*QUEUE` | Login a cola por ID literal | Idem, compat |
| `*7701` | Logout total | `agent_logout_commit.php` |
| `*7702` | Pausar (DTMF para motivo) | `agent_pause_commit.php` |
| `*7703` | Despausar | `agent_unpause_commit.php` |

### AGI vs HTTP

El flujo de `*7700` es híbrido:
1. **AGI** (`teleflow_agent_login.agi`) ejecuta dentro del proceso Asterisk, valida credenciales contra MySQL.
2. Si OK, reproduce el audio `teleflow-acceso-correcto.wav`.
3. Llama al endpoint HTTP `agent_commit.php` (via `System(curl ...)` en background) para que el QueueAdd corra fuera del canal de la llamada.

Razón: AMI `QueueAdd` desde dentro del AGI bloquea al canal mientras Asterisk procesa. Hacerlo via HTTP en background libera al canal rápido.

## Tablas MySQL relevantes

### `teleflow.queue_shortcut`
```sql
CREATE TABLE queue_shortcut (
  digit TINYINT PRIMARY KEY,
  queue VARCHAR(32) NOT NULL,
  label VARCHAR(80),
  active TINYINT(1) DEFAULT 1
);
```
Mapeo dígito → cola para atajos del `*7700*N`. CRUD desde UI **Configuración → Atajos \*7700**.

### `teleflow.ext_meta`
```sql
ext (PK) | tipo (cliente/horizon) | rtsp_url | rtsp_label | is_bocina | door_dtmf_code | notes | avatar
```
Metadata por extensión que extiende lo que Issabel guarda en `asterisk.users`.

### `teleflow.agent_sessions`
```sql
session_id (PK) | agent_ext | agent_number | login_time | logout_time | shift_date | status | total_calls | total_talk_time | total_pause_time
```
Sesiones de agentes. Insertada por `agent_commit.php` al login, actualizada por el hub realtime.

### `teleflow.agent_pauses`
```sql
id (PK) | session_id | agent_ext | agent_number | pause_type_code | pause_start | pause_end | duration_seconds
```

### `teleflow.agent_queue_pref`
```sql
agent_number | queue | penalty
```
Prefs guardadas para que `*7700` y el portal web pre-seleccionen colas en el modal del paso 2.

### `teleflow.door_events`
```sql
id (PK) | ext | dtmf | actor_user | actor_kind | snapshot | occurred_at
```
Log de aperturas DTMF + foto RTSP capturada en el momento.

### `call_center.agent` (DB de Issabel)
```sql
id (PK) | type | number | name | password | estatus
```
Lista maestra de agentes. CRUD desde UI **Configuración → Agentes** (endpoint `agents_crud.php`).

## Audios custom

Los archivos `.wav` viven en `dist/teleflow-*.wav` y se copian a `/var/lib/asterisk/sounds/custom/` en la PBX.

| Archivo | Cuándo se reproduce |
|---------|---------------------|
| `teleflow-pide-numero.wav` | Tras `*7700`, antes del `Read AGENT_NUM` |
| `teleflow-pide-clave.wav` | Después del número, antes del `Read AGENT_PASS` |
| `teleflow-acceso-correcto.wav` | Credenciales válidas |
| `teleflow-acceso-denegado.wav` | Credenciales inválidas |
| `teleflow-pide-motivo.wav` | Tras `*7702`, para elegir motivo |
| `teleflow-pausa-activada.wav` | Confirma pausa registrada |
| `teleflow-pausa-finalizada.wav` | Tras `*7703` |
| `teleflow-logout-ok.wav` | Tras `*7701` |
| `teleflow-no-logueado.wav` | Si intenta acción sin login |

## Variables de entorno del AGI

`/etc/asterisk/teleflow_agi.conf` (formato `.ini`):

```ini
DB_HOST = 10.1.1.7
DB_USER = tfremote
DB_PASS = ...
AMI_HOST = 127.0.0.1
AMI_PORT = 5038
AMI_USER = teleflow
AMI_PASS = ...
```

**Plantilla** en `dist/teleflow_agi.conf.example` (sin secretos).
