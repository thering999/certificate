/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 80043
Source Host           : 127.0.0.1:3306
Source Database       : certificate_db

Target Server Type    : MYSQL
Target Server Version : 80043
File Encoding         : 65001

Date: 2025-10-21 15:43:39
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for api_keys
-- ----------------------------
DROP TABLE IF EXISTS `api_keys`;
CREATE TABLE `api_keys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_used` datetime DEFAULT NULL,
  `rate_limit` int DEFAULT '1000',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_api_key` (`api_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of api_keys
-- ----------------------------

-- ----------------------------
-- Table structure for audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_audit_user_action` (`user_id`,`action`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of audit_logs
-- ----------------------------

-- ----------------------------
-- Table structure for batch_jobs
-- ----------------------------
DROP TABLE IF EXISTS `batch_jobs`;
CREATE TABLE `batch_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `job_type` enum('import','export','generate','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `total_items` int NOT NULL DEFAULT '0',
  `processed_items` int NOT NULL DEFAULT '0',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `progress_percentage` int DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_batch_user_status` (`user_id`,`status`),
  CONSTRAINT `batch_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of batch_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for certificate_designs
-- ----------------------------
DROP TABLE IF EXISTS `certificate_designs`;
CREATE TABLE `certificate_designs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `design_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bg_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_elements` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `certificate_designs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of certificate_designs
-- ----------------------------
INSERT INTO `certificate_designs` VALUES ('7', '12', 'Design 20/10/2568 21:22:26', 'custom', 'templete/Green White Simple Modern Recognition Certificate.png', '[{\"x\": 363, \"y\": 262, \"id\": \"text_1\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"{name}\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}, {\"x\": 434, \"y\": 129, \"id\": \"text_2\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"ปปปป\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}]', '2025-10-20 14:22:27');
INSERT INTO `certificate_designs` VALUES ('13', '13', 'Design 21/10/2568 10:49:36', 'custom', 'templete/Green White Simple Modern Recognition Certificate.png', '[{\"x\": 355, \"y\": 265, \"id\": \"text_1\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"{name}\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}, {\"x\": 413, \"y\": 117, \"id\": \"text_2\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"ปปปปปป\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}]', '2025-10-21 03:49:38');
INSERT INTO `certificate_designs` VALUES ('14', '13', 'Design 21/10/2568 10:50:25', 'custom', 'assets/default_bg.jpg', '[{\"x\": 320, \"y\": 255, \"id\": \"text_1\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"{name}\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}, {\"x\": 428, \"y\": 106, \"id\": \"text_2\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"แแแแแ\", \"fontSize\": 40, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"medium\", \"textDecoration\": \"none\"}]', '2025-10-21 03:50:26');
INSERT INTO `certificate_designs` VALUES ('15', '13', 'Design 21/10/2568 14:37:46', 'gov_standard', 'assets/default_bg.jpg', '[{\"x\": 493, \"y\": 78, \"id\": \"text_ministry\", \"align\": \"center\", \"color\": \"#1a237e\", \"content\": \"กระทรวงศึกษาธิการ\", \"fontSize\": 28, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"600\", \"textShadow\": \"none\", \"textDecoration\": \"none\"}, {\"x\": 383, \"y\": 175, \"id\": \"text_title\", \"align\": \"center\", \"color\": \"#c62828\", \"content\": \"ใบประกาศเกียรติคุณ\", \"fontSize\": 48, \"fontFamily\": \"Sarabun\", \"fontWeight\": \"700\", \"textShadow\": \"light\", \"textDecoration\": \"none\"}, {\"x\": 440, \"y\": 393, \"id\": \"text_name\", \"align\": \"center\", \"color\": \"#1565c0\", \"content\": \"ขอมอบให้แก่\\\\n{name}\", \"fontSize\": 36, \"fontFamily\": \"TH Sarabun New\", \"fontWeight\": \"500\", \"textShadow\": \"none\", \"textDecoration\": \"none\"}]', '2025-10-21 07:37:47');

-- ----------------------------
-- Table structure for certificate_names
-- ----------------------------
DROP TABLE IF EXISTS `certificate_names`;
CREATE TABLE `certificate_names` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  `verification_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `organization_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verification_code` (`verification_code`),
  KEY `idx_cert_status_user` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of certificate_names
-- ----------------------------
INSERT INTO `certificate_names` VALUES ('1', 'นายสมชาย ใจดี', 'active', null, null, null, '2025-10-21 07:37:16', '2025-10-20 13:29:07', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('2', 'นางสาวสุดา สวยงาม', 'active', null, null, null, '2025-10-20 13:29:07', '2025-10-20 13:29:07', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('3', 'นายประเสริฐ ขยัน', 'active', null, null, null, '2025-10-20 13:29:07', '2025-10-20 13:29:07', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('4', 'นางสมหญิง ไทยยุติธรรม', 'active', null, null, null, '2025-10-20 13:29:07', '2025-10-20 13:29:07', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('5', 'นายสมชาย ใจดี', 'active', null, null, null, '2025-10-21 07:37:16', '2025-10-20 13:29:33', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('6', 'นางสาวสุดา สวยงาม', 'active', null, null, null, '2025-10-20 13:29:33', '2025-10-20 13:29:33', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('7', 'นายประเสริฐ ขยัน', 'active', null, null, null, '2025-10-20 13:29:33', '2025-10-20 13:29:33', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('8', 'นางสมหญิง ไทยยุติธรรม', 'active', null, null, null, '2025-10-20 13:29:33', '2025-10-20 13:29:33', null, null, null, '13');
INSERT INTO `certificate_names` VALUES ('9', 'นางสาวสุดา สวยงาม', 'active', null, null, null, '2025-10-21 07:56:34', '2025-10-21 07:56:34', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('10', 'นายประเสริฐ ขยัน', 'active', null, null, null, '2025-10-21 07:56:41', '2025-10-21 07:56:41', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('11', 'นางสมหญิง ไทยยุติธรรม', 'active', null, null, null, '2025-10-21 07:56:47', '2025-10-21 07:56:47', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('12', 'นายสมชาย ใจดี', 'active', null, null, null, '2025-10-21 08:27:40', '2025-10-21 08:27:40', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('13', 'นางสาวสุดา สวยงาม', 'active', null, null, null, '2025-10-21 08:27:40', '2025-10-21 08:27:40', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('14', 'นายประเสริฐ ขยัน', 'active', null, null, null, '2025-10-21 08:27:40', '2025-10-21 08:27:40', null, null, null, '12');
INSERT INTO `certificate_names` VALUES ('15', 'นางสมหญิง ไทยยุติธรรม', 'active', null, null, null, '2025-10-21 08:27:40', '2025-10-21 08:27:40', null, null, null, '12');

-- ----------------------------
-- Table structure for certificate_templates
-- ----------------------------
DROP TABLE IF EXISTS `certificate_templates`;
CREATE TABLE `certificate_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `version` int DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `preview_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_count` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of certificate_templates
-- ----------------------------

-- ----------------------------
-- Table structure for export_history
-- ----------------------------
DROP TABLE IF EXISTS `export_history`;
CREATE TABLE `export_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `export_type` enum('csv','excel','json','pdf','zip') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_records` int NOT NULL DEFAULT '0',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_export_user_date` (`user_id`,`created_at`),
  CONSTRAINT `export_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of export_history
-- ----------------------------

-- ----------------------------
-- Table structure for file_storage
-- ----------------------------
DROP TABLE IF EXISTS `file_storage`;
CREATE TABLE `file_storage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `export_history_id` int NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checksum` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_export_history_id` (`export_history_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `file_storage_ibfk_1` FOREIGN KEY (`export_history_id`) REFERENCES `export_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of file_storage
-- ----------------------------

-- ----------------------------
-- Table structure for organizations
-- ----------------------------
DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of organizations
-- ----------------------------

-- ----------------------------
-- Table structure for scheduled_tasks
-- ----------------------------
DROP TABLE IF EXISTS `scheduled_tasks`;
CREATE TABLE `scheduled_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_type` enum('export','backup','cleanup','report','sync') COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_time` time NOT NULL,
  `scheduled_day` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `task_config` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_next_run` (`next_run`),
  CONSTRAINT `scheduled_tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of scheduled_tasks
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `last_login` datetime DEFAULT NULL,
  `login_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `api_quota_limit` int DEFAULT '10000',
  `api_quota_used` int DEFAULT '0',
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('11', 'user', '$2y$10$KHDjwI5XZmnG37y3uzKsmeF5YQSF9.BBxvqKrjPfYHHGsVuzE0g3y', 'user', null, '0', '1', '10000', '0', null, null, 'user@gmail.com');
INSERT INTO `users` VALUES ('12', 'test', '$2y$10$DM0i3ZXUHCi6SLjISh/qKetJHR6rqsxllBQNvo0uNPfIHqjZk04pW', 'user', null, '0', '1', '10000', '0', null, null, null);
INSERT INTO `users` VALUES ('13', 'admin', '$2y$10$vaoWmtBBqRLv/tl4RQ4UruiMwKu3eYYyKMzs4QCFxnkgG0yJ6sdrm', 'admin', null, '0', '1', '10000', '0', null, null, 'habusaya@gmail.com');

-- ----------------------------
-- Table structure for webhooks
-- ----------------------------
DROP TABLE IF EXISTS `webhooks`;
CREATE TABLE `webhooks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `event_type` enum('export_completed','import_completed','cert_created','cert_deleted','batch_completed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `webhook_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `retry_count` int DEFAULT '3',
  `last_triggered` datetime DEFAULT NULL,
  `last_response` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `webhooks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of webhooks
-- ----------------------------

-- ----------------------------
-- View structure for export_summary
-- ----------------------------
DROP VIEW IF EXISTS `export_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `export_summary` AS select `eh`.`user_id` AS `user_id`,count(0) AS `total_exports`,sum(`eh`.`total_records`) AS `total_records_exported`,sum(`eh`.`file_size`) AS `total_size`,max(`eh`.`created_at`) AS `last_export`,group_concat(distinct `eh`.`export_type` separator ',') AS `export_types` from `export_history` `eh` group by `eh`.`user_id` ;

-- ----------------------------
-- View structure for user_activity
-- ----------------------------
DROP VIEW IF EXISTS `user_activity`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_activity` AS select `al`.`user_id` AS `user_id`,count(0) AS `total_actions`,count(distinct `al`.`action`) AS `unique_actions`,max(`al`.`created_at`) AS `last_activity`,group_concat(distinct `al`.`action` separator ',') AS `action_types` from `audit_logs` `al` group by `al`.`user_id` ;
