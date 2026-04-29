-- ============================================================
--  Barangay System — Database Setup (Updated with Soft Delete)
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS barangay_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE barangay_db;

-- ── Admins ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO admins (username, password, full_name) VALUES
    ('admin1', 'admin123', 'Barangay Captain'),
    ('admin2', 'admin456', 'Barangay Secretary');

-- ── Residents ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS residents (
    resident_id VARCHAR(10)  PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    address     VARCHAR(200) NOT NULL,
    contact     VARCHAR(20)  NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Resident Accounts ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS resident_accounts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    address     VARCHAR(200) NOT NULL,
    contact     VARCHAR(20)  NOT NULL,
    resident_id VARCHAR(10)  UNIQUE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id)
        ON DELETE SET NULL
);

-- ── Records (Requests & Complaints) ─────────────────────────
CREATE TABLE IF NOT EXISTS records (
    record_id      VARCHAR(20)  PRIMARY KEY,
    record_type    ENUM('request','complaint') NOT NULL,
    category       VARCHAR(100) NOT NULL,
    details        TEXT,
    status         ENUM('Pending','Approved','Done','Rejected') DEFAULT 'Pending',
    resident_id    VARCHAR(10)  NOT NULL,
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Soft delete fields
    is_deleted     TINYINT(1) DEFAULT 0,
    deleted_at     DATETIME NULL,
    deleted_by     VARCHAR(100) NULL,
    delete_reason  VARCHAR(255) NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id)
        ON DELETE CASCADE
);

-- ── Notifications ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    record_id  VARCHAR(20) NOT NULL,
    message    TEXT        NOT NULL,
    notif_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES records(record_id)
        ON DELETE CASCADE
);

-- Migration: Add soft delete columns if they don't exist yet
ALTER TABLE records ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE records ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE records ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL;
ALTER TABLE records ADD COLUMN IF NOT EXISTS delete_reason VARCHAR(255) NULL;
