-- RentVault schema. Import via Hostinger hPanel → Databases → phpMyAdmin → Import,
-- or let install.php run it for you.

CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_tokens (
    token_hash CHAR(64)     NOT NULL PRIMARY KEY,
    admin_id   INT UNSIGNED NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vehicles (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(32)  NULL UNIQUE,          -- "vehicle1", "vehicle2", ...
    name       VARCHAR(120) NOT NULL,             -- e.g. "Maruti Swift Dzire"
    reg_number VARCHAR(32)  NOT NULL UNIQUE,      -- e.g. "KL07AB1234"
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id    INT UNSIGNED NOT NULL,
    doc_type      ENUM('photo','insurance','rc','pollution') NOT NULL,
    stored_name   VARCHAR(64)  NOT NULL,          -- random file name inside /storage
    original_name VARCHAR(255) NOT NULL,
    mime          VARCHAR(64)  NOT NULL,
    size_bytes    INT UNSIGNED NOT NULL,
    uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vehicle_doc (vehicle_id, doc_type),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
