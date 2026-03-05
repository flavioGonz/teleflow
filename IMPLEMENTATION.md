# 🚀 IMPLEMENTATION GUIDE - Teleflow Agents & Memory

## Quick Start

Este guide explica cómo implementar los módulos **Agents** y **Memory** en Teleflow.

---

## 📋 Estructura de Archivos

```
teleflow/
├── api/
│   ├── index.php              # API principal (existente)
│   ├── agents.php             # [NUEVO] Operaciones de agentes
│   └── memory.php             # [NUEVO] Sistema de contexto
├── uploads/
│   └── avatars/               # Fotos de agentes
├── js/
│   ├── agents.js              # [NUEVO] Lógica de agentes
│   └── memory.js              # [NUEVO] Lógica de memoria
├── dashboard-agents.html      # [NUEVO] Dashboard de agentes
├── agents.md                  # [NUEVO] Documentación
├── memory.md                  # [NUEVO] Documentación
└── index.php                  # Frontend principal
```

---

## 🔧 Step 1: Extender API PHP

### `/api/agents.php` - Operaciones de Agentes

```php
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$ext = $_GET['ext'] ?? '';

// Conectar a base de datos
try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'password');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Connection Failed']);
    exit;
}

// GET: Obtener datos de un agente
if ($action === 'get_agent') {
    $pjsip_info = shell_exec("/usr/sbin/asterisk -rx 'pjsip show endpoint $ext'");
    
    $agent_data = [
        'ext' => $ext,
        'status' => strpos($pjsip_info, 'Endpoint') ? 'ONLINE' : 'OFFLINE',
        'info' => $pjsip_info
    ];
    
    echo json_encode($agent_data);
    exit;
}

// POST: Cambiar estado de agente
if ($action === 'set_agent_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar data
    if (!$data || !isset($data['ext']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }
    
    // Aplicar cambio en Asterisk
    $status = $data['status'];
    
    if ($status === 'AWAY') {
        shell_exec("/usr/sbin/asterisk -rx 'devstate change Custom:$ext AWAY'");
    } else if ($status === 'BACK_ONLINE') {
        shell_exec("/usr/sbin/asterisk -rx 'devstate change Custom:$ext AVAILABLE'");
    }
    
    echo json_encode(['success' => true, 'agent' => $ext, 'new_status' => $status]);
    exit;
}

// GET: Listar todos los agentes con performance
if ($action === 'list_agents') {
    $query = $db->query("
        SELECT 
            a.ext, a.name, a.status,
            COUNT(c.id) as total_calls,
            AVG(c.duration) as avg_duration
        FROM agents a
        LEFT JOIN cdr c ON a.ext = c.src AND c.calldate >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        GROUP BY a.ext
        ORDER BY a.name
    ");
    
    $agents = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($agents);
    exit;
}
?>
```

### `/api/memory.php` - Sistema de Memoria

```php
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user'])) {
    http_response_code(403);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $db = new PDO('mysql:host=localhost;dbname=asterisk', 'root', 'password');
    $redis = new Redis();
    $redis->connect('localhost', 6379);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

// GET: Obtener contexto de cliente
if ($action === 'get_customer_context') {
    $phone = $_GET['phone'] ?? '';
    
    // Buscar en Redis primero (caché)
    $cached = $redis->get("customer:$phone:context");
    if ($cached) {
        echo $cached;
        exit;
    }
    
    // Buscar en base de datos
    $query = $db->prepare("
        SELECT * FROM customers WHERE phone = ?
    ");
    $query->execute([$phone]);
    $customer = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }
    
    // Obtener historial de últimas 5 llamadas
    $calls_query = $db->prepare("
        SELECT * FROM cdr 
        WHERE src = ? OR dst = ?
        ORDER BY calldate DESC
        LIMIT 5
    ");
    $calls_query->execute([$phone, $phone]);
    $recent_calls = $calls_query->fetchAll(PDO::FETCH_ASSOC);
    
    $context = [
        'customer' => $customer,
        'recent_calls' => $recent_calls
    ];
    
    // Guardar en caché por 30 minutos
    $redis->setex("customer:$phone:context", 1800, json_encode($context));
    
    echo json_encode($context);
    exit;
}

// POST: Guardar nota de agente
if ($action === 'save_agent_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $insert = $db->prepare("
        INSERT INTO agent_notes (agent_ext, customer_phone, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    $insert->execute([
        $data['agent_ext'],
        $data['customer_phone'],
        $data['note']
    ]);
    
    // Invalidar caché
    $redis->del("customer:{$data['customer_phone']}:context");
    
    echo json_encode(['success' => true, 'note_id' => $db->lastInsertId()]);
    exit;
}

// POST: Guardar sentimiento de llamada
if ($action === 'save_sentiment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $update = $db->prepare("
        UPDATE cdr 
        SET sentiment = ?, quality_score = ?
        WHERE callid = ?
    ");
    
    $update->execute([
        $data['sentiment'],
        $data['quality_score'],
        $data['call_id']
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}
?>
```

---

## 🎨 Step 2: Frontend Integration

### `/js/agents.js`

```javascript
// Cargar datos de agentes cada 5 segundos
const REFRESH_INTERVAL = 5000;

async function loadAgents() {
    try {
        const response = await fetch('/api/agents.php?action=list_agents');
        const agents = await response.json();
        
        updateAgentsList(agents);
        updateSummaryMetrics(agents);
    } catch (error) {
        console.error('Error loading agents:', error);
    }
}

function updateAgentsList(agents) {
    const list = document.getElementById('agentsList');
    list.innerHTML = agents.map(agent => `
        <div class="agent-row" onclick="showAgentModal('${agent.ext}')">
            <div class="agent-name">#${agent.ext}</div>
            <div class="agent-status" data-status="${agent.status}">${agent.status}</div>
            <div class="agent-calls">${agent.total_calls}</div>
            <div class="agent-aht">${formatDuration(agent.avg_duration)}</div>
        </div>
    `).join('');
}

function updateSummaryMetrics(agents) {
    const onlineCount = agents.filter(a => a.status === 'ONLINE').length;
    const totalCalls = agents.reduce((sum, a) => sum + a.total_calls, 0);
    
    document.querySelector('.metric-online').textContent = onlineCount;
    document.querySelector('.metric-calls').textContent = totalCalls;
}

function changeAgentStatus(ext, newStatus) {
    const data = { ext, status: newStatus };
    
    fetch('/api/agents.php?action=set_agent_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json())
      .then(res => {
          if (res.success) loadAgents();
      });
}

// Auto-refresh
setInterval(loadAgents, REFRESH_INTERVAL);
loadAgents(); // Cargar inmediatamente
```

### `/js/memory.js`

```javascript
// Sistema de contexto de llamadas

class CallMemory {
    constructor() {
        this.currentCall = null;
        this.customerContext = null;
    }
    
    async loadCustomerContext(phone) {
        const response = await fetch(`/api/memory.php?action=get_customer_context&phone=${phone}`);
        this.customerContext = await response.json();
        
        this.displayContext();
        return this.customerContext;
    }
    
    displayContext() {
        if (!this.customerContext) return;
        
        const panel = document.getElementById('customer-context');
        const customer = this.customerContext.customer;
        
        panel.innerHTML = `
            <div class="context-header">
                <h3>${customer.name}</h3>
                <span class="vip-badge" style="display: ${customer.lifetime_value > 1000 ? 'block' : 'none'}">VIP</span>
            </div>
            <div class="context-details">
                <p>Teléfono: ${customer.phone}</p>
                <p>Valor Vitalicio: $${customer.lifetime_value}</p>
                <p>Última Interacción: ${new Date(customer.last_interaction).toLocaleString()}</p>
            </div>
            <div class="recent-calls">
                <h4>Últimas Llamadas</h4>
                ${this.contextContext.recent_calls.map(call => `
                    <div class="call-record">
                        <small>${new Date(call.calldate).toLocaleString()}</small>
                        <div>${call.disposition}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    async saveNote(agentExt, customerPhone, noteText) {
        const data = {
            agent_ext: agentExt,
            customer_phone: customerPhone,
            note: noteText
        };
        
        const response = await fetch('/api/memory.php?action=save_agent_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        return await response.json();
    }
    
    async saveSentiment(callId, sentiment, qualityScore) {
        const data = { call_id: callId, sentiment, quality_score: qualityScore };
        
        await fetch('/api/memory.php?action=save_sentiment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    }
}

// Instancia global
window.callMemory = new CallMemory();
```

---

## 🗄️ Step 3: Database Schema

### Crear tablas en MySQL

```sql
-- Tabla de agentes
CREATE TABLE IF NOT EXISTS agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ext VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100),
    department VARCHAR(50),
    team VARCHAR(50),
    hire_date DATE,
    status VARCHAR(20) DEFAULT 'OFFLINE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100),
    lifetime_value DECIMAL(10, 2) DEFAULT 0,
    dnc BOOLEAN DEFAULT FALSE,
    last_interaction TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de notas de agentes
CREATE TABLE IF NOT EXISTS agent_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_ext VARCHAR(10),
    customer_phone VARCHAR(20),
    content TEXT,
    tags VARCHAR(255),
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_ext) REFERENCES agents(ext),
    FOREIGN KEY (customer_phone) REFERENCES customers(phone)
);

-- Tabla de sentimientos de llamadas
ALTER TABLE cdr ADD COLUMN sentiment VARCHAR(20);
ALTER TABLE cdr ADD COLUMN quality_score INT;
ALTER TABLE cdr ADD COLUMN resolution_status VARCHAR(20);
ALTER TABLE cdr ADD COLUMN notes TEXT;

-- Índices para performance
CREATE INDEX idx_agent_calls ON cdr(src, calldate);
CREATE INDEX idx_customer_calls ON cdr(dst, calldate);
CREATE INDEX idx_agent_notes_date ON agent_notes(created_at);
```

---

## 🔑 Step 4: Configuración Asterisk/PJSIP

### Webhooks para eventos en tiempo real

En `/etc/asterisk/ari.conf`:

```ini
[general]
enabled = yes
bindaddr = 127.0.0.1
bindport = 8088

[teleflow]
type = user
read_only = no
password = teleflow_secret
```

En PHP, webhook listener:

```php
<?php
// /api/webhook.php - Recibir eventos de Asterisk
$data = json_decode(file_get_contents('php://input'), true);

// Events: ChannelStateChange, ChannelCreated, BridgeCreated, etc.
if ($data['type'] === 'ChannelStateChange') {
    $channel = $data['channel']['name'];
    // Actualizar estado en tiempo real
}

if ($data['type'] === 'ChannelDestroyed') {
    // Registrar fin de llamada
}
?>
```

---

## 📊 Step 5: Testing

### Test API endpoints

```bash
# Listar agentes
curl http://localhost/api/agents.php?action=list_agents

# Obtener contexto de cliente
curl http://localhost/api/memory.php?action=get_customer_context&phone=5554443333

# Cambiar estado de agente
curl -X POST http://localhost/api/agents.php?action=set_agent_status \
  -H "Content-Type: application/json" \
  -d '{"ext":"1001","status":"AWAY"}'

# Guardar nota
curl -X POST http://localhost/api/memory.php?action=save_agent_note \
  -H "Content-Type: application/json" \
  -d '{"agent_ext":"1001","customer_phone":"5554443333","note":"Resolved billing issue"}'
```

---

## 🚀 Step 6: Deployment

### En producción (pbx01.infratec.com.uy):

1. **Clone el repo actualizado:**
   ```bash
   cd /var/www/html/teleflow
   git pull origin main
   ```

2. **Actualizar permisos:**
   ```bash
   chown -R apache:apache /var/www/html/teleflow
   chmod -R 755 /var/www/html/teleflow
   ```

3. **Ejecutar migrations SQL:**
   ```bash
   mysql -u root -p asterisk < schema.sql
   ```

4. **Reiniciar servicios:**
   ```bash
   systemctl restart httpd
   systemctl restart asterisk
   ```

5. **Verificar logs:**
   ```bash
   tail -f /var/log/asterisk/full
   ```

---

## 📚 Recursos Adicionales

- [agents.md](agents.md) - Documentación completa del módulo
- [memory.md](memory.md) - Sistema de contexto y memoria
- [dashboard-agents.html](dashboard-agents.html) - UI del dashboard

---

## 🆘 Troubleshooting

| Problema | Solución |
|----------|----------|
| API returns 403 | Verificar sesión `$_SESSION['tf_user']` |
| Agentes sin datos | Revisar `/usr/sbin/asterisk -rx 'pjsip show endpoints'` |
| Redis connection failed | Ejecutar `systemctl start redis-server` |
| Llamadas no se registran | Verificar CDR en MySQL: `SELECT * FROM cdr LIMIT 1;` |

---

*Last Updated: 2026-03-05*
