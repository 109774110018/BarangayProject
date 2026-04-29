-- ============================================================
--  Barangay System — Auto Backup
--  Database : barangay_db
--  Generated: 2026-04-28 00:34:52
--  Schedule : daily
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;

-- Table: `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` VALUES
('1', 'admin1', 'admin123', 'Barangay Captain', '2026-04-23 18:13:24'),
('2', 'admin2', 'admin456', 'Barangay Secretary', '2026-04-23 18:13:24');

-- Table: `backup_log`
DROP TABLE IF EXISTS `backup_log`;
CREATE TABLE `backup_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(100) NOT NULL,
  `filesize` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'success',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `backup_log` VALUES
('1', 'backup_2026_04_28_001300.sql', '4136', 'success', '2026-04-28 06:13:00');

-- Table: `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `notif_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES
('3', 'REQ-DD4DEA', 'Status updated to \'Done\' by Barangay Captain on 2026-04-23 20:03', '2026-04-23 20:03:31');

-- Table: `records`
DROP TABLE IF EXISTS `records`;
CREATE TABLE `records` (
  `record_id` varchar(20) NOT NULL,
  `record_type` enum('request','complaint') NOT NULL,
  `resident_id` varchar(10) NOT NULL,
  `category` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('Pending','Approved','Done') DEFAULT 'Pending',
  `date_submitted` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `records_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `records` VALUES
('REQ-DD4DEA', 'request', 'B4083662', 'Barangay Clearance', '', 'Done', '2026-04-23 20:01:36');

-- Table: `resident_accounts`
DROP TABLE IF EXISTS `resident_accounts`;
CREATE TABLE `resident_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `resident_id` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `resident_id` (`resident_id`),
  CONSTRAINT `resident_accounts_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `resident_accounts` VALUES
('1', 'Julian', 'carl09', 'Julian Malolos', 'San Gregorio', '12132112', 'B4083662', '2026-04-23 19:35:42');

-- Table: `residents`
DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents` (
  `resident_id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  PRIMARY KEY (`resident_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `residents` VALUES
('B4083662', 'Julian Malolos', 'San Gregorio', '12132112');

SET FOREIGN_KEY_CHECKS=1;
