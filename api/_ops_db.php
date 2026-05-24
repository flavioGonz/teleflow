<?php
/**
 * api/_ops_db.php
 *
 * Helper común: PDO a teleflow + creación/migración idempotente del schema
 * Operaciones (clients, disuasion_groups, nvr_devices, etc.).
 *
 * Se incluye desde clients.php, disuasion.php, nvr.php.
 */
function ops_db(): PDO {
    global $DB_HOST, $DB_USER, $DB_PASS, $TF_DB_NAME;
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$TF_DB_NAME;charset=utf8", $DB_USER, $DB_PASS,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Maestro de clientes (idempotente)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        address VARCHAR(255) NULL,
        contact VARCHAR(120) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Asociación polimórfica cliente ↔ recurso (ext / paging / nvr / queue)
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_assoc (
        client_id INT NOT NULL,
        kind ENUM('ext','paging','nvr','queue') NOT NULL,
        ref_id VARCHAR(32) NOT NULL,
        PRIMARY KEY (client_id, kind, ref_id),
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        INDEX idx_ref (kind, ref_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Grupos de disuasión custom (extra a los paging_config de FreePBX)
    $pdo->exec("CREATE TABLE IF NOT EXISTS disuasion_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        page_code VARCHAR(20) NOT NULL,
        icon VARCHAR(32) DEFAULT 'campaign',
        color VARCHAR(32) DEFAULT '#f59e0b',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // NVRs
    $pdo->exec("CREATE TABLE IF NOT EXISTS nvr_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        model VARCHAR(64) NULL,
        ip VARCHAR(64) NOT NULL,
        port_http INT DEFAULT 80,
        port_rtsp INT DEFAULT 554,
        channels_total INT DEFAULT 0,
        username VARCHAR(64) NULL,
        password VARCHAR(120) NULL,
        snmp_community VARCHAR(64) DEFAULT 'public',
        snmp_version VARCHAR(8) DEFAULT '2c',
        client_id INT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_check_at TIMESTAMP NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Canales (cámaras) por NVR
    $pdo->exec("CREATE TABLE IF NOT EXISTS nvr_channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nvr_id INT NOT NULL,
        channel_no INT NOT NULL,
        name VARCHAR(120) NULL,
        ext VARCHAR(8) NULL,
        rtsp_url VARCHAR(500) NULL,
        recording TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_nvr_ch (nvr_id, channel_no),
        FOREIGN KEY (nvr_id) REFERENCES nvr_devices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Discos individuales — poblado por SNMP poller (Fase 2)
    $pdo->exec("CREATE TABLE IF NOT EXISTS nvr_disks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nvr_id INT NOT NULL,
        disk_no INT NOT NULL,
        capacity_gb INT NULL,
        free_gb INT NULL,
        smart_status VARCHAR(32) NULL,
        temperature_c INT NULL,
        health ENUM('ok','warn','fail','unknown') DEFAULT 'unknown',
        last_check_at TIMESTAMP NULL,
        UNIQUE KEY uk_nvr_disk (nvr_id, disk_no),
        FOREIGN KEY (nvr_id) REFERENCES nvr_devices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return $pdo;
}

/** PDO a la BD asterisk (FreePBX) — solo lectura desde aquí */
function pbx_db_ro(): PDO {
    global $DB_HOST, $DB_USER, $DB_PASS, $PBX_DB_NAME;
    return new PDO("mysql:host=$DB_HOST;dbname=$PBX_DB_NAME;charset=utf8", $DB_USER, $DB_PASS,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/** Auth guard común */
function ops_auth_or_403(): void {
    if (empty($_SESSION['tf_user'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'forbidden']);
        exit;
    }
}
