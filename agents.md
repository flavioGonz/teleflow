# 🎧 AGENTS Module - Teleflow

## Overview
The **Agents Module** provides real-time monitoring and management of call center agents/extensions with performance metrics, status tracking, and network diagnostics.

---

## 📊 Agent Data Structure

### Agent Object
```json
{
  "ext": "1001",
  "name": "Alex Thompson",
  "status": "ONLINE|BUSY|OFFLINE|AWAY|BREAK",
  "ip": "192.168.1.120",
  "mac": "40:80:34:6C:C9:84",
  "rtt": "12ms",
  "avatar": "uploads/avatars/1001.jpg",
  "live_call": {
    "duration": "02:45",
    "type": "INCOMING|OUTGOING",
    "direction": "Incoming Call|After Call Work|Available"
  },
  "performance": {
    "total_calls": 42,
    "avg_aht": "03:12",
    "acw_time": "01:30",
    "handled_calls": 42
  }
}
```

---

## 🔄 Agent Status States

| Status | Description | Color | Selectable |
|--------|-------------|-------|-----------|
| **ONLINE** | Agent available to receive calls | 🟢 Green | No (system-driven) |
| **BUSY** | Currently on an active call | 🟡 Amber/Yellow | No (system-driven) |
| **OFFLINE** | Not registered or offline | ⚫ Gray | Manual toggle |
| **AWAY** | Manually marked away (break/lunch) | 🔵 Blue | Yes |
| **BREAK** | On scheduled break | 🟠 Orange | Yes |

---

## 📈 Performance Metrics

### Key Performance Indicators (KPIs)

#### Agent Level
- **Total Calls (Daily)**: Total number of handled calls
- **Average Handle Time (AHT)**: Mean duration per call (Call + ACW)
- **ACW Time**: After-Call-Work duration (wrap-up time)
- **Calls Per Hour**: Throughput metric
- **Queue Adherence**: % time in schedule vs. actual

#### Queue Level (Summary)
- **Queue Status**: Number of calls waiting
- **Average Wait Time (AWT)**: Mean wait time in queue
- **Service Level**: % calls answered within SL threshold (e.g., 80% in 20s)
- **Abandon Rate**: % of calls abandoned while waiting
- **ASA (Average Speed of Answer)**: Average time to agent connection

---

## 🌐 Network Monitoring

### Connection Details
- **IP Address**: Agent's current client IP (softphone/deskphone)
- **MAC Address**: Network interface MAC address
- **RTT (Round Trip Time)**: Latency in milliseconds
- **Device Type**: 
  - 📞 Deskphone (PJSIP endpoint)
  - 💻 Softphone (SIP client)

### Network Quality Thresholds
- ✅ **Good**: RTT < 50ms, Loss < 1%
- ⚠️ **Fair**: RTT 50-100ms, Loss 1-5%
- ❌ **Poor**: RTT > 100ms, Loss > 5%

---

## 👥 Agent List View

### Columns Display
1. **Agent / Interno**
   - Avatar (auto-generated or custom upload)
   - Extension Number (e.g., #1001)
   - Full Name

2. **Status Badge**
   - Visual indicator with color
   - Text status label
   - (Optional) Duration in current state

3. **Live Call**
   - Duration (HH:MM:SS)
   - Call type/direction
   - Current activity (Incoming, After Call Work, Available)

4. **Daily Performance**
   - Total Calls Handled
   - Separator line |
   - Average AHT (mm:ss)

5. **Network (IP/MAC)**
   - IP Address (pink/magenta color)
   - MAC Address (purple color)
   - Shows "---" if offline/unavailable

---

## 🎯 Agent Actions & Features

### Individual Agent Actions (Row Click/Expand)
- [ ] **View Detailed Stats**: Historical performance, charts
- [ ] **Call Management**: 
  - Whisper (supervisor intervention)
  - Barge-in (3-way join)
  - Hang-up call
- [ ] **Manual Status**: Force status change (AWAY, BREAK, BACK)
- [ ] **Take Call**: Supervisor intervention for stalled calls
- [ ] **Send Chat**: Internal messaging
- [ ] **Notes**: Store notes about agent performance

### Bulk Actions
- [ ] **Filter & Search**: By name, extension, IP, status, department
- [ ] **Department Filter**: Group agents by team
- [ ] **Team Filter**: Sub-groups within departments
- [ ] **Status Filter**: Show only specific status
- [ ] **Export Report**: CSV/PDF of agent metrics

---

## 🔌 API Endpoints

### GET `/api?action=get_full_data`
Returns comprehensive agent & call data

**Response:**
```json
{
  "system": {
    "cpu": 15,
    "uptime": "15 days"
  },
  "pbx": {
    "extensions": [
      {
        "ext": "1001",
        "name": "Alex Thompson",
        "status": "ONLINE",
        "ip": "192.168.1.120",
        "rtt": "12ms",
        "avatar": "uploads/avatars/1001.jpg"
      }
    ],
    "calls": [
      {
        "from": "1001",
        "to": "5554443333",
        "duration": "02:45"
      }
    ],
    "recordings": [],
    "queues": [
      {
        "name": "support",
        "waiting": 12
      }
    ]
  },
  "summary": {
    "queue": 1,
    "wait": "0:45",
    "abandon": "2.4%"
  }
}
```

### POST `/api?action=agent_status` (To be implemented)
```json
{
  "ext": "1001",
  "status": "AWAY|BREAK|ONLINE"
}
```

### POST `/api?action=call_control` (To be implemented)
```json
{
  "action": "hangup|whisper|barge",
  "call_id": "PJSIP/1001-xyz",
  "supervisor": "1000"
}
```

---

## 🎨 UI Components

### Agent Status Badge Component
```jsx
<StatusBadge 
  status="ONLINE" 
  color="green" 
  icon="circle" 
  duration="02:45"
  glow={true}
/>
```

### Agent Avatar Component
```jsx
<Avatar 
  url="uploads/avatars/1001.jpg" 
  fallback="AT" 
  size="md"
  online={true}
/>
```

### Performance Metric Component
```jsx
<PerformanceMetric
  title="Daily Performance"
  calls={42}
  aht="03:12"
  trend="↓ 5%"
/>
```

---

## 📱 Agent Dashboard Features (Phase 2)

### Personal Agent Dashboard
- Own calls today
- Time logged in
- Personal performance vs. team average
- Goals & targets
- Break/lunch tracking
- Upcoming calls preview

### Supervisor Overview
- Team heatmap (busy distribution)
- Call quality metrics
- Real-time queue management
- Agent workload balancing
- Performance coaching data

---

## 🔐 Permissions

| Action | Supervisor | Manager | Agent |
|--------|-----------|---------|-------|
| View all agents | ✅ | ✅ | ❌ |
| View own stats | ✅ | ✅ | ✅ |
| Change agent status | ✅ | ✅ | ❌ |
| Intercept calls | ✅ | ✅ | ❌ |
| Export reports | ✅ | ✅ | ❌ |
| Change ACW time | ✅ | ❌ | ❌ |

---

## 🚀 Implementation Roadmap

- [x] **V1.0**: Basic agent list, status, live calls, performance summary
- [ ] **V1.1**: Detailed agent metrics, network diagnostics
- [ ] **V1.2**: Call control features (whisper, barge, hangup)
- [ ] **V2.0**: Agent dashboard, personal stats, quality coaching
- [ ] **V2.1**: Skill-based routing, team assignments
- [ ] **V3.0**: AI-powered recommendations, real-time coaching

---

## 📚 Related Modules
- **CallCenter**: Queue management & distribution
- **Recordings**: Call recordings & playback
- **Reports**: Historical performance analytics
- **Notifications**: Real-time alerts & dashboards

---

*Last Updated: 2026-03-05*
