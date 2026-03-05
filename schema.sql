-- ============================================
-- TELEFLOW: Database Schema for Agents & Memory
-- ============================================
-- Created: 2026-03-05
-- Compatible with: MySQL 5.7+, MariaDB 10.2+
-- ============================================

-- Asegurar que usamos la base de datos asterisk
USE asterisk;

-- =============================================
-- TABLA 1: Agentes/Internos
-- =============================================
CREATE TABLE IF NOT EXISTS `agents` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `ext` VARCHAR(10) UNIQUE NOT NULL COMMENT 'Extension number (e.g., 1001)',
  `name` VARCHAR(100) NOT NULL,
  `department` VARCHAR(50) COMMENT 'SUPPORT, SALES, BILLING, etc.',
  `team` VARCHAR(50) COMMENT 'TIER_1, TIER_2, SPECIALISTS, etc.',
  `hire_date` DATE,
  `status` VARCHAR(20) DEFAULT 'OFFLINE' COMMENT 'ONLINE, BUSY, OFFLINE, AWAY, BREAK',
  `phone` VARCHAR(20),
  `email` VARCHAR(100),
  `avatar_url` VARCHAR(255),
  `active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ext` (`ext`),
  INDEX `idx_department` (`department`),
  INDEX `idx_team` (`team`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 2: Clientes (CRM)
-- =============================================
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `phone` VARCHAR(20) UNIQUE NOT NULL,
  `alt_phone` VARCHAR(20),
  `name` VARCHAR(100),
  `email` VARCHAR(100),
  `segment` VARCHAR(20) COMMENT 'VIP, STANDARD, AT_RISK',
  `lifetime_value` DECIMAL(10, 2) DEFAULT 0,
  `total_interactions` INT DEFAULT 0,
  `dnc` BOOLEAN DEFAULT FALSE COMMENT 'Do Not Call',
  `preferred_language` VARCHAR(10) DEFAULT 'es',
  `last_interaction` TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_phone` (`phone`),
  INDEX `idx_segment` (`segment`),
  INDEX `idx_lifetime_value` (`lifetime_value` DESC),
  FULLTEXT `idx_name_email` (`name`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 3: Notas de Agentes
-- =============================================
CREATE TABLE IF NOT EXISTS `agent_notes` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `agent_ext` VARCHAR(10) NOT NULL,
  `customer_phone` VARCHAR(20),
  `call_id` VARCHAR(50),
  `content` LONGTEXT NOT NULL,
  `note_type` VARCHAR(20) DEFAULT 'GENERAL' COMMENT 'GENERAL, COMPLAINT, FOLLOW_UP, QUALITY',
  `tags` VARCHAR(255),
  `follow_up_required` BOOLEAN DEFAULT FALSE,
  `follow_up_date` DATE,
  `priority` VARCHAR(20) DEFAULT 'NORMAL' COMMENT 'LOW, NORMAL, HIGH, URGENT',
  `resolved` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_ext`) REFERENCES `agents`(`ext`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_phone`) REFERENCES `customers`(`phone`) ON DELETE SET NULL,
  INDEX `idx_agent_date` (`agent_ext`, `created_at` DESC),
  INDEX `idx_customer_date` (`customer_phone`, `created_at` DESC),
  INDEX `idx_follow_up` (`follow_up_required`, `follow_up_date`),
  FULLTEXT `idx_content` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 4: Extensión de CDR (Ampliación de registros existentes)
-- =============================================
-- Ejecutar estos ALTER si la tabla cdr ya existe
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `sentiment` VARCHAR(20) COMMENT 'POSITIVE, NEUTRAL, NEGATIVE, UNKNOWN' DEFAULT 'UNKNOWN';
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `quality_score` INT COMMENT 'Quality rating 0-100' DEFAULT NULL;
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `resolution_status` VARCHAR(20) COMMENT 'RESOLVED, ESCALATED, CALLBACK, ABANDONED' DEFAULT NULL;
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `notes` LONGTEXT;
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `supervisor_notes` LONGTEXT;
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `tags` VARCHAR(255);
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `customer_id` INT;
ALTER TABLE `cdr` ADD COLUMN IF NOT EXISTS `agent_sentiment` VARCHAR(20) COMMENT 'Agent mood during call';
ALTER TABLE `cdr` ADD INDEX IF NOT EXISTS `idx_sentiment` (`sentiment`);
ALTER TABLE `cdr` ADD INDEX IF NOT EXISTS `idx_quality_score` (`quality_score`);
ALTER TABLE `cdr` ADD INDEX IF NOT EXISTS `idx_src_date` (`src`, `calldate` DESC);
ALTER TABLE `cdr` ADD INDEX IF NOT EXISTS `idx_dst_date` (`dst`, `calldate` DESC);

-- Si es tabla nueva CDR, crear el registro completo:
CREATE TABLE IF NOT EXISTS `cdr` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `callid` VARCHAR(50) UNIQUE,
  `calldate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `src` VARCHAR(20),
  `dst` VARCHAR(20),
  `duration` INT,
  `billsec` INT,
  `disposition` VARCHAR(50),
  `amaflags` VARCHAR(50),
  `channel` VARCHAR(50),
  `dcontext` VARCHAR(50),
  `dstchannel` VARCHAR(50),
  `lastapp` VARCHAR(50),
  `lastdata` VARCHAR(255),
  `accountcode` VARCHAR(50),
  `uniqueid` VARCHAR(50),
  `userfield` VARCHAR(255),
  `recordingfile` VARCHAR(255),
  `sentiment` VARCHAR(20) DEFAULT 'UNKNOWN',
  `quality_score` INT DEFAULT NULL,
  `resolution_status` VARCHAR(20),
  `notes` LONGTEXT,
  `supervisor_notes` LONGTEXT,
  `tags` VARCHAR(255),
  `customer_id` INT,
  `agent_sentiment` VARCHAR(20),
  INDEX `idx_calldate` (`calldate` DESC),
  INDEX `idx_src_date` (`src`, `calldate` DESC),
  INDEX `idx_dst_date` (`dst`, `calldate` DESC),
  INDEX `idx_sentiment` (`sentiment`),
  INDEX `idx_quality_score` (`quality_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 5: Sesiones de Agentes (Real-time)
-- =============================================
CREATE TABLE IF NOT EXISTS `agent_sessions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `session_id` VARCHAR(50) UNIQUE NOT NULL,
  `agent_ext` VARCHAR(10) NOT NULL,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL,
  `shift_date` DATE NOT NULL,
  `status` VARCHAR(20) DEFAULT 'ACTIVE' COMMENT 'ACTIVE, BREAK, LUNCH, AWAY, CLOSED',
  `total_calls` INT DEFAULT 0,
  `total_talk_time` INT DEFAULT 0,
  `total_acw_time` INT DEFAULT 0,
  `mood_flags` VARCHAR(255) COMMENT 'JSON array: positive, negative, cooperative, etc.',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_ext`) REFERENCES `agents`(`ext`) ON DELETE CASCADE,
  INDEX `idx_agent_date` (`agent_ext`, `shift_date` DESC),
  INDEX `idx_active` (`status`, `logout_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 6: Historial de Estado de Agentes
-- =============================================
CREATE TABLE IF NOT EXISTS `agent_status_history` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `agent_ext` VARCHAR(10) NOT NULL,
  `old_status` VARCHAR(20),
  `new_status` VARCHAR(20) NOT NULL,
  `duration_seconds` INT,
  `reason` VARCHAR(255),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_ext`) REFERENCES `agents`(`ext`) ON DELETE CASCADE,
  INDEX `idx_agent_date` (`agent_ext`, `timestamp` DESC),
  INDEX `idx_timestamp` (`timestamp` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 7: Métricas de Performance Diaria
-- =============================================
CREATE TABLE IF NOT EXISTS `agent_daily_metrics` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `agent_ext` VARCHAR(10) NOT NULL,
  `metric_date` DATE NOT NULL,
  `total_calls_handled` INT DEFAULT 0,
  `total_talk_time` INT DEFAULT 0 COMMENT 'segundos',
  `total_acw_time` INT DEFAULT 0,
  `avg_aht` INT DEFAULT 0,
  `avg_quality_score` INT DEFAULT 0,
  `completed_calls` INT DEFAULT 0,
  `abandoned_calls` INT DEFAULT 0,
  `complaints` INT DEFAULT 0,
  `schedule_adherence_pct` DECIMAL(5, 2) DEFAULT 0,
  `login_time` TIME,
  `logout_time` TIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_agent_date` (`agent_ext`, `metric_date`),
  FOREIGN KEY (`agent_ext`) REFERENCES `agents`(`ext`) ON DELETE CASCADE,
  INDEX `idx_date` (`metric_date` DESC),
  INDEX `idx_agent_date` (`agent_ext`, `metric_date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 8: Habilidades de Agentes
-- =============================================
CREATE TABLE IF NOT EXISTS `agent_skills` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `agent_ext` VARCHAR(10) NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  `proficiency_level` INT COMMENT '1-10',
  `certified` BOOLEAN DEFAULT FALSE,
  `last_training` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_ext`) REFERENCES `agents`(`ext`) ON DELETE CASCADE,
  UNIQUE KEY `unique_agent_skill` (`agent_ext`, `skill_name`),
  INDEX `idx_skill` (`skill_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 9: Queues/Colas
-- =============================================
CREATE TABLE IF NOT EXISTS `queues_config` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `queue_name` VARCHAR(50) UNIQUE NOT NULL,
  `description` VARCHAR(255),
  `agents_ext` VARCHAR(255) COMMENT 'JSON array of extensions',
  `max_wait_time` INT DEFAULT 300,
  `strategy` VARCHAR(50) DEFAULT 'ringall' COMMENT 'ringall, leastrecent, fewestcalls, random, etc.',
  `sl_threshold` INT DEFAULT 20 COMMENT 'Service Level threshold in seconds',
  `sl_target_pct` INT DEFAULT 80 COMMENT 'Service Level target percentage',
  `active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_queue` (`queue_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA 10: Eventos de Cola en Tiempo Real
-- =============================================
CREATE TABLE IF NOT EXISTS `queue_events` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `queue_name` VARCHAR(50) NOT NULL,
  `event_type` VARCHAR(50) COMMENT 'CALL_RECEIVED, CALL_CONNECTED, CALL_ABANDONED, AGENT_LOGIN, AGENT_LOGOUT',
  `agent_ext` VARCHAR(10),
  `caller_id` VARCHAR(20),
  `wait_time` INT,
  `talk_time` INT,
  `event_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`queue_name`) REFERENCES `queues_config`(`queue_name`),
  INDEX `idx_queue_date` (`queue_name`, `event_timestamp` DESC),
  INDEX `idx_timestamp` (`event_timestamp` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- VISTAS (Views) para reportes
-- =============================================

-- Vista 1: Resumen de agentes activos hoy
CREATE OR REPLACE VIEW `v_agents_summary_today` AS
SELECT 
    a.ext,
    a.name,
    a.department,
    a.team,
    a.status,
    COALESCE(m.total_calls_handled, 0) as calls_today,
    COALESCE(m.avg_aht, 0) as avg_aht,
    COALESCE(m.avg_quality_score, 0) as quality_score,
    m.metric_date
FROM agents a
LEFT JOIN agent_daily_metrics m ON a.ext = m.agent_ext AND m.metric_date = CURDATE()
WHERE a.active = TRUE
ORDER BY a.name;

-- Vista 2: Histórico de sentimientos por agent
CREATE OR REPLACE VIEW `v_sentiment_by_agent` AS
SELECT 
    c.src as agent_ext,
    c.sentiment,
    COUNT(*) as count,
    ROUND(AVG(c.quality_score), 2) as avg_quality,
    DATE(c.calldate) as call_date
FROM cdr c
WHERE c.sentiment IS NOT NULL AND c.sentiment != 'UNKNOWN'
GROUP BY c.src, c.sentiment, DATE(c.calldate);

-- Vista 3: Performance semanal por agente
CREATE OR REPLACE VIEW `v_weekly_agent_performance` AS
SELECT 
    agent_ext,
    WEEK(metric_date) as week_num,
    YEAR(metric_date) as year,
    SUM(total_calls_handled) as total_calls,
    AVG(avg_aht) as avg_aht,
    AVG(avg_quality_score) as avg_quality,
    AVG(schedule_adherence_pct) as avg_adherence
FROM agent_daily_metrics
WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 13 WEEK)
GROUP BY agent_ext, WEEK(metric_date), YEAR(metric_date);

-- =============================================
-- PROCEDIMIENTOS ALMACENADOS
-- =============================================

-- SP 1: Generar métricas diarias de agentes
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_generate_daily_metrics`()
BEGIN
    INSERT INTO agent_daily_metrics 
    (agent_ext, metric_date, total_calls_handled, completed_calls, avg_quality_score)
    SELECT 
        c.src,
        DATE(c.calldate),
        COUNT(*),
        SUM(CASE WHEN c.disposition = 'ANSWERED' THEN 1 ELSE 0 END),
        ROUND(AVG(c.quality_score), 0)
    FROM cdr c
    WHERE DATE(c.calldate) = CURDATE()
    GROUP BY c.src
    ON DUPLICATE KEY UPDATE
        total_calls_handled = VALUES(total_calls_handled),
        completed_calls = VALUES(completed_calls),
        avg_quality_score = VALUES(avg_quality_score);
END//
DELIMITER ;

-- SP 2: Registrar cambio de estado
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_log_status_change`(
    IN p_agent_ext VARCHAR(10),
    IN p_old_status VARCHAR(20),
    IN p_new_status VARCHAR(20),
    IN p_duration INT,
    IN p_reason VARCHAR(255)
)
BEGIN
    INSERT INTO agent_status_history 
    (agent_ext, old_status, new_status, duration_seconds, reason)
    VALUES (p_agent_ext, p_old_status, p_new_status, p_duration, p_reason);
END//
DELIMITER ;

-- =============================================
-- INSERTS de ejemplo
-- =============================================

LOCK TABLES `agents` WRITE;
INSERT IGNORE INTO `agents` (ext, name, department, team, hire_date, status, email) VALUES
('1001', 'Alex Thompson', 'SUPPORT', 'TIER_2', '2021-06-15', 'ONLINE', 'alex@infratec.com'),
('1002', 'Marco Rossi', 'SUPPORT', 'TIER_2', '2021-08-20', 'BUSY', 'marco@infratec.com'),
('1004', 'Sarah Jenkins', 'BILLING', 'TIER_1', '2022-01-10', 'OFFLINE', 'sarah@infratec.com'),
('1005', 'Diana Prince', 'SALES', 'SPECIALISTS', '2020-11-05', 'ONLINE', 'diana@infratec.com');
UNLOCK TABLES;

LOCK TABLES `customers` WRITE;
INSERT IGNORE INTO `customers` (phone, name, segment, lifetime_value, preferred_language) VALUES
('5554443333', 'John Smith', 'VIP', 2500.00, 'es'),
('5554443322', 'Maria Garcia', 'STANDARD', 800.00, 'es'),
('5554443311', 'Robert Brown', 'AT_RISK', 150.00, 'en');
UNLOCK TABLES;

-- =============================================
-- Indices adicionales para performance
-- =============================================
CREATE INDEX IF NOT EXISTS idx_agent_note_customer ON agent_notes(customer_phone, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_session_logout ON agent_sessions(logout_time, agent_ext);
CREATE INDEX IF NOT EXISTS idx_daily_metrics_agent ON agent_daily_metrics(agent_ext, metric_date DESC);

-- =============================================
-- STATS Y VERIFICACIÓN
-- =============================================
-- Para verificar que todo se creó correctamente:
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'asterisk' AND TABLE_NAME LIKE 'agent%';
-- SELECT VIEW_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = 'asterisk' AND VIEW_NAME LIKE 'v_%';

-- ============================================
-- FIN DEL SCHEMA
-- ============================================
