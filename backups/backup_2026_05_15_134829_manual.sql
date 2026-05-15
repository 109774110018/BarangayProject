-- ============================================================
--  Barangay San Rafael — Database Backup
--  Database : barangay_db
--  Generated: 2026-05-15 13:48:29
--  Trigger  : manual
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
('1', 'admin1', '$2y$10$i7wKJ7orNMhH4WqYH/aU2.lf9ZB.L3lvF7fZJiUrEyn4llOrHpkE2', 'Barangay Captain', '2026-04-23 18:13:24'),
('2', 'admin2', 'admin456', 'Barangay Secretary', '2026-04-23 18:13:24');

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
('RES-B430AE', 'lebb', 'purok 1 brgy V-B', '0928838282'),
('RES-DCC0B3', 'John', 'San Rafael', '09997867676');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `resident_accounts` VALUES
('1', 'Julian', '$2y$10$gR21tj0n8FBqwHx0DzBsROocbC/aTLWwHpcxkkbRE9b1Ljoi6LtF2', 'Julian Malolos', 'San Gregorio', '12132112', 'B4083662', '2026-04-23 19:35:42'),
('3', 'leb', '123456789', 'lebb', 'purok 1 brgy V-B', '0928838282', 'RES-B430AE', '2026-04-28 12:38:35'),
('4', 'SK Chairman', 'jthood123', 'David Esteban', 'JoelTown', '982874434', 'RES-AABF6B', '2026-04-29 12:45:30'),
('5', 'Konsehal', '$2y$10$gcauL55I36jwKMbwDYxPyO4oj1odGn6T7rjk810lALmy/F36T01qC', 'John', 'San Rafael', '09997867676', 'RES-DCC0B3', '2026-05-11 09:11:25');

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
('CMP-146790', 'complaint', 'B4083662', 'Garbage / Sanitation', 'Tagal kumuha ng basura!', 'Approved', '2026-05-14 09:30:25', '0', NULL, NULL, NULL),
('CMP-23589C', 'complaint', 'RES-DCC0B3', 'Illegal Parking', 'Urgent', 'Pending', '2026-05-11 09:12:02', '0', NULL, NULL, NULL),
('CMP-F024B4', 'complaint', 'RES-AABF6B', 'Garbage / Sanitation', 'Ang kalat', 'Done', '2026-04-29 12:51:59', '0', NULL, NULL, NULL),
('REQ-2D0DBB', 'request', 'RES-DCC0B3', 'Business Permit', '', 'Pending', '2026-05-11 09:11:46', '0', NULL, NULL, NULL),
('REQ-5116F0', 'request', 'B4083662', 'Other Document', 'urgent', 'Approved', '2026-05-01 19:11:49', '0', NULL, NULL, NULL),
('REQ-98F01D', 'request', 'B4083662', 'Certificate of Indigency', '', '', '2026-05-14 09:28:57', '0', NULL, NULL, NULL),
('REQ-BC798D', 'request', 'RES-B430AE', 'Community Tax Certificate (CEDULA)', 'need for business', 'Done', '2026-04-28 12:41:47', '0', NULL, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES
('12', 'REQ-BC798D', 'Status updated to \'Approved\' by Barangay Captain on 2026-04-28 06:42', '2026-04-28 12:42:26'),
('13', 'REQ-BC798D', 'Status updated to \'Done\' by Barangay Captain on 2026-04-28 06:42', '2026-04-28 12:42:56'),
('16', 'CMP-F024B4', 'Status updated to \'Done\' by Barangay Captain on 2026-04-29 06:54', '2026-04-29 12:54:02'),
('21', 'REQ-5116F0', 'Status updated to \'Approved\' by Barangay Captain on 2026-05-07 07:58', '2026-05-07 13:58:14'),
('22', 'CMP-146790', 'Status updated to \'Done\' by Barangay Captain on 2026-05-14 03:40', '2026-05-14 09:40:50'),
('23', 'CMP-146790', 'Status updated to \'Approved\' by Barangay Captain on 2026-05-14 07:34', '2026-05-14 13:34:57'),
('24', 'REQ-98F01D', 'Status updated to \'Rejected\' by Barangay Captain on 2026-05-14 07:38', '2026-05-14 13:38:56');

-- Table: `archives`
DROP TABLE IF EXISTS `archives`;
CREATE TABLE `archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(50) DEFAULT NULL,
  `resident_id` varchar(50) DEFAULT NULL,
  `resident_name` varchar(200) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `record_type` varchar(20) DEFAULT NULL,
  `category` varchar(200) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id` (`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `archives` VALUES
('1', 'CMP-0A4D6C', 'B4083662', 'Julian Malolos', '12132112', 'complaint', 'Noise Complaint', 'urgent', 'Pending', '2026-05-07 13:41:20', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('2', 'CMP-360D16', 'B4083662', 'Julian Malolos', '12132112', 'complaint', 'Illegal Business', 'urgent', 'Done', '2026-04-30 18:12:51', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('3', 'CMP-729FF5', 'B4083662', 'Julian Malolos', '12132112', 'complaint', 'Illegal Construction', 's', 'Pending', '2026-05-04 20:05:11', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('4', 'REQ-106196', 'B4083662', 'Julian Malolos', '12132112', 'request', 'Community Tax Certificate (CEDULA)', '', 'Approved', '2026-05-07 13:55:29', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('5', 'REQ-331DBD', 'B4083662', 'Julian Malolos', '12132112', 'request', 'Business Permit', 'business', 'Done', '2026-04-30 18:13:07', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('6', 'REQ-4909E1', 'B4083662', 'Julian Malolos', '12132112', 'request', 'Certificate of Indigency', 'valid id', 'Pending', '2026-04-30 19:28:52', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('7', 'REQ-655E9B', 'B4083662', 'Julian Malolos', '12132112', 'request', 'Other Document', 'd', 'Pending', '2026-05-04 20:04:54', 'Barangay Captain', '', '2026-05-11 09:08:29'),
('8', 'REQ-465E95', 'RES-AABF6B', 'David Esteban', '982874434', 'request', 'Barangay ID', 'Urgent, for valid id', 'Done', '2026-04-29 12:52:20', 'Barangay Captain', '', '2026-05-11 09:08:29');

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

SET FOREIGN_KEY_CHECKS=1;
