-- ============================================================
--  Barangay System — Auto Backup
--  Database : barangay_db
--  Generated: 2026-04-30 12:13:26
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES
('12', 'REQ-BC798D', 'Status updated to \'Approved\' by Barangay Captain on 2026-04-28 06:42', '2026-04-28 12:42:26'),
('13', 'REQ-BC798D', 'Status updated to \'Done\' by Barangay Captain on 2026-04-28 06:42', '2026-04-28 12:42:56'),
('15', 'REQ-465E95', 'Status updated to \'Done\' by Barangay Captain on 2026-04-29 06:53', '2026-04-29 12:53:53'),
('16', 'CMP-F024B4', 'Status updated to \'Done\' by Barangay Captain on 2026-04-29 06:54', '2026-04-29 12:54:02');

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
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `records_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `records` VALUES
('CMP-360D16', 'complaint', 'B4083662', 'Illegal Business', 'urgent', 'Pending', '2026-04-30 18:12:51', '0', NULL, NULL, NULL),
('CMP-F024B4', 'complaint', 'RES-AABF6B', 'Garbage / Sanitation', 'Ang kalat', 'Done', '2026-04-29 12:51:59', '0', NULL, NULL, NULL),
('REQ-331DBD', 'request', 'B4083662', 'Business Permit', 'business', 'Pending', '2026-04-30 18:13:07', '0', NULL, NULL, NULL),
('REQ-465E95', 'request', 'RES-AABF6B', 'Barangay ID', 'Urgent, for valid id', 'Done', '2026-04-29 12:52:20', '0', NULL, NULL, NULL),
('REQ-BC798D', 'request', 'RES-B430AE', 'Community Tax Certificate (CEDULA)', 'need for business', 'Done', '2026-04-28 12:41:47', '0', NULL, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `resident_accounts` VALUES
('1', 'Julian', 'carl09', 'Julian Malolos', 'San Gregorio', '12132112', 'B4083662', '2026-04-23 19:35:42'),
('3', 'leb', '123456789', 'lebb', 'purok 1 brgy V-B', '0928838282', 'RES-B430AE', '2026-04-28 12:38:35'),
('4', 'SK Chairman', 'jthood123', 'David Esteban', 'JoelTown', '982874434', 'RES-AABF6B', '2026-04-29 12:45:30');

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
('B4083662', 'Julian Malolos', 'San Gregorio', '12132112'),
('RES-06D8EA', 'Lebrone', '0588, San Gregorio Homes', '38743897489'),
('RES-AABF6B', 'David Esteban', 'JoelTown', '982874434'),
('RES-B430AE', 'lebb', 'purok 1 brgy V-B', '0928838282');

SET FOREIGN_KEY_CHECKS=1;
