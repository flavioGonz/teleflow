# 💾 MEMORY Module - Teleflow Context System

## Overview
The **Memory Module** provides a persistent context layer that stores agent interactions, customer data, call history, and system state. This enables intelligent call routing, supervisor insights, and AI-powered supervisory features.

---

## 📋 Memory Architecture

### Memory Layers

#### 1. Real-Time Memory (Session)
- **Duration**: Active while agent is logged in
- **Scope**: Current shift/session
- **Data**: Recent calls, current status, active interactions
- **Storage**: RAM/Cache (Redis)

```json
{
  "session_id": "sess_1001_20260305_0800",
  "agent_ext": "1001",
  "login_time": "2026-03-05T08:00:00Z",
  "current_state": "ONLINE",
  "state_since": "2026-03-05T09:30:15Z",
  "active_call": {
    "call_id": "PJSIP/1001-xyz",
    "started": "2026-03-05T09:45:00Z",
    "customer": "5554443333",
    "direction": "INCOMING"
  },
  "recent_calls": [
    {
      "timestamp": "2026-03-05T09:30:00Z",
      "duration": "00:05:30",
      "customer": "5554443322",
      "notes": "Billing inquiry"
    }
  ]
}
```

#### 2. Daily Memory (Shift)
- **Duration**: During agent's shift
- **Reset**: At end of shift
- **Data**: All calls, performance metrics, mood/attitude flags
- **Storage**: Database (MySQL)

```json
{
  "shift_id": "shift_1001_20260305",
  "agent_ext": "1001",
  "date": "2026-03-05",
  "shift_start": "2026-03-05T08:00:00Z",
  "shift_end": null,
  "total_calls": 23,
  "total_talk_time": "01:45:30",
  "total_acw_time": "00:18:00",
  "avg_aht": "00:04:15",
  "quality_score": 92,
  "mood_flags": ["positive", "cooperative"],
  "incidents": []
}
```

#### 3. Customer Memory (CRM)
- **Duration**: Permanent (linked to customer)
- **Scope**: All interactions with that customer
- **Data**: Preferences, history, notes, sentiment
- **Storage**: Database (MySQL)

```json
{
  "customer_id": "cust_5554443333",
  "phone": "5554443333",
  "name": "John Smith",
  "segment": "VIP|STANDARD|AT-RISK",
  "lifetime_value": 2500.00,
  "interaction_count": 45,
  "last_interaction": "2026-03-05T09:30:00Z",
  "preferred_language": "es",
  "dnc": false,
  "notes": [
    {
      "timestamp": "2026-03-04T14:20:00Z",
      "agent": "1002",
      "text": "Customer upset about recent billing. Req. refund."
    }
  ],
  "history": [
    {
      "date": "2026-03-04",
      "agent": "1002",
      "topic": "BILLING",
      "resolution": "REFUND_ISSUED",
      "sentiment": "NEGATIVE"
    }
  ]
}
```

#### 4. Agent Memory (Agent Profile)
- **Duration**: Permanent (agent lifetime)
- **Scope**: Agent capabilities, preferences, compliance
- **Data**: Skills, certifications, adherence history
- **Storage**: Database (MySQL)

```json
{
  "agent_id": "agent_1001",
  "ext": "1001",
  "name": "Alex Thompson",
  "hire_date": "2021-06-15",
  "department": "SUPPORT",
  "team": "TIER_2",
  "skills": [
    {
      "skill": "TECHNICAL_SUPPORT",
      "proficiency": 9,
      "certified": true
    },
    {
      "skill": "BILLING",
      "proficiency": 7,
      "certified": false
    }
  ],
  "quality_metrics": {
    "avg_quality_score": 88,
    "complaints_30days": 1,
    "training_need": "COMPLAINT_HANDLING"
  },
  "adherence": {
    "schedule_adherence_30days": 94.5,
    "break_adherence": 98.2
  }
}
```

#### 5. System Memory (Configuration)
- **Duration**: Persistent
- **Scope**: System settings, routing rules
- **Data**: Queue configs, schedules, IVR flows
- **Storage**: Database + File cache

---

## 🔄 Memory Flow During Calls

### Call Initiation
```
Customer Call → IVR Routes → Queue
                     ↓
                Fetch Customer Memory
                  (history, notes, sentiment)
                     ↓
                Route to Best Available Agent
                  (skills, workload, language)
                     ↓
              Pop-up: Customer Context on Agent Screen
              (recent history, VIP status, open issues)
```

### During Call
```
Agent receives call
     ↓
Customer Memory displays with:
  - Last interaction date & resolution
  - Known issues
  - Sentiment history
  - Preferred language
  - Special notes ("VIP", "Angry", "Technical")
     ↓
Agent can add notes in real-time
     ↓
Call is recorded with metadata
```

### Post-Call
```
Agent enters ACW notes
     ↓
System auto-generates call summary:
  - Topic/category
  - Resolution
  - Sentiment (positive/negative/neutral)
  - Follow-up required?
     ↓
Updates daily memory (shift stats)
     ↓
Closes session/real-time memory
     ↓
Archives to customer history
```

---

## 📊 Memory Data Models

### Call Record (Permanent Archive)
```json
{
  "call_id": "call_20260305_001234",
  "timestamp": "2026-03-05T09:40:00Z",
  "direction": "INCOMING|OUTGOING",
  "agent_ext": "1001",
  "customer_phone": "5554443333",
  "customer_id": "cust_5554443333",
  "duration": "00:05:30",
  "talk_time": "00:04:45",
  "acw_time": "00:00:45",
  "topic":"TECHNICAL_SUPPORT|BILLING|SALES",
  "resolution": "RESOLVED|ESCALATED|CALLBACK",
  "sentiment": "POSITIVE|NEUTRAL|NEGATIVE",
  "quality_score": 92,
  "recording_file": "rec_20260305_001234.wav",
  "notes": "Fixed DNS issue, customer satisfied",
  "supervisor_notes": ""
}
```

### Agent Note / Interaction
```json
{
  "note_id": "note_20260305_001",
  "timestamp": "2026-03-05T09:45:00Z",
  "agent_ext": "1001",
  "customer_id": "cust_5554443333",
  "type": "CALL|CHAT|EMAIL|CALLBACK",
  "content": "Customer called regarding service issue. Adjusted bandwidth limit.",
  "priority": "NORMAL|HIGH|URGENT",
  "tags": ["TECHNICAL", "RESOLVED"],
  "follow_up_required": false,
  "follow_up_date": null
}
```

---

## 🔐 Memory Access Control

### Permission Model
```
SUPERVISOR: Can view all memory, add private notes
MANAGER: Can view team memory, reporting only
AGENT: Can view own session, customer notes during calls
```

### Data Sanitization
- PCI Compliance: Payment card data never stored
- PII: Last 4 digits only for sensitive fields  
- Encryption: Customer notes encrypted at rest

---

## 🤖 Memory-Powered Features

### 1. Intelligent Call Routing
```
Call arrives → Look up customer memory
           ↓
    Check sentiment history
           ↓
    Route to:
    - Same agent (if positive history)
    - Skill-matched available agent
    - Escalation queue (if repeated issue)
```

### 2. Agent Performance Coaching
```
End of shift → Review all calls for agent
           ↓
    Flag negative sentiment calls
           ↓
    Identify training needs
           ↓
    Generate coaching points
           ↓
    Notify supervisor
```

### 3. VIP / At-Risk Detection
```
Customer calls → Check lifetime value
             ↓
    If VIP: Route to senior agent, add priority
    If At-Risk: Flag for retention specialist
    If Angry Pattern: Route to supervisor
```

### 4. Call Recommendations (AI-Ready)
```
During call → Agent types notes
          ↓
    ML model analyzes call sentiment in real-time
          ↓
    Suggests next best action:
    - Offer discount
    - Suggest product upsell
    - Schedule callback
    - Escalate to specialist
```

### 5. Quality Assurance Automation
```
Call ends → Auto-analyze:
       ↓
    - Resolution achieved?
    - Customer satisfied?
    - Compliance (greeting, etc)?
       ↓
    Assign quality score (1-100)
       ↓
    If low: Flag for QA review, notify supervisor
```

---

## 💾 Storage Implementation

### Redis (Real-Time Cache)
```
Key: session:{agent_ext}:{shift_date}
Value: Current session state (TTL: 12 hours)

Key: acw:{call_id}
Value: ACW notes temporary storage (TTL: 2 hours)

Key: customer:{phone}:recent_calls
Value: Last 10 calls (TTL: 30 days)
```

### MySQL (Persistent)
```
Tables:
- cdr: Call records (linked to customers)
- agent_notes: All notes with metadata
- customer_data: Customer profiles & history
- shift_summary: Daily agent performance
- call_interactions: Detailed call data
- quality_scores: QA results per call
```

### Files (Recordings)
```
Structure:
/recordings/{year}/{month}/{day}/
  call_20260305_001234.wav
  call_20260305_001234.metadata.json
```

---

## 🎯 API Endpoints

### GET `/api?action=get_customer_memory&phone=5554443333`
```json
{
  "customer_id": "cust_5554443333",
  "name": "John Smith",
  "recent_calls": 5,
  "last_call": "2026-03-04T14:20:00Z",
  "sentiment_trend": "NEGATIVE",
  "vip_status": false,
  "notes": [
    {
      "date": "2026-03-04",
      "agent": "1002",
      "note": "Upset about billing"
    }
  ]
}
```

### GET `/api?action=get_agent_session&ext=1001`
```json
{
  "session_id": "sess_1001_20260305",
  "agent": "Alex Thompson",
  "logged_in": "2026-03-05T08:00:00Z",
  "current_state": "ONLINE",
  "calls_today": 23,
  "talk_time": "01:45:30",
  "acw_time": "00:18:00"
}
```

### POST `/api?action=add_agent_note`
```json
{
  "customer_id": "cust_5554443333",
  "agent_ext": "1001",
  "note": "Technical issue resolved. Customer satisfied.",
  "follow_up": false
}
```

### POST `/api?action=save_sentiment`
```json
{
  "call_id": "call_20260305_001234",
  "sentiment": "POSITIVE",
  "auto_generated": true,
  "confidence": 0.94
}
```

---

## 📈 Reporting with Memory Data

### Agent Memory Reports
- Daily calls by topic
- Sentiment trends (last 30 days)
- Common customer complaints
- Quality improvement tracking

### Customer Intelligence
- Churn risk score
- Lifetime value trends
- Service preference analysis
- Next best action recommendations

---

## 🚀 Implementation Roadmap

- [x] **V1.0**: Basic session memory, real-time call data
- [ ] **V1.1**: Customer memory, call history tracking
- [ ] **V1.2**: Agent notes system, sentiment tagging
- [ ] **V2.0**: Advanced routing based on memory
- [ ] **V2.1**: Quality automation, coaching features
- [ ] **V3.0**: AI-powered recommendations, predictive routing

---

## 🔗 Related Modules
- **Agents**: Real-time agent monitoring
- **CallCenter**: Queue & call distribution
- **Recordings**: Call playback & analysis
- **Reports**: Data-driven insights

---

*Last Updated: 2026-03-05*
