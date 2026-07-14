-- StorePulse Analytics Database Schema and Sample Data
-- This SQL file is a REFERENCE SCHEMA ONLY.
-- 
-- IMPORTANT: The "wp_" prefix shown here is a PLACEHOLDER.
-- In actual code, use $wpdb->prefix instead of hardcoded "wp_".
-- 
-- The single source of truth for table creation is:
-- includes/installation/install.php (function StorePulse_create_tables())
-- 
-- This file is provided for reference and documentation purposes only.
-- DO NOT run this SQL file directly in production.
-- Tables are created automatically via WordPress activation hook.

------------------------------------------------------------
-- Table: wp_storepulse_events
-- Purpose: Stores raw WooCommerce event data (orders, refunds, etc.)
-- Note: "wp_" is placeholder - use $wpdb->prefix in code
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_events` (
  `event_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(50) NOT NULL,
  `order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `event_data` JSON NOT NULL,
  `utm_source` VARCHAR(100) DEFAULT NULL,
  `utm_medium` VARCHAR(100) DEFAULT NULL,
  `utm_campaign` VARCHAR(100) DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT 'direct',
  `exit_page` VARCHAR(50) DEFAULT 'other',
  `event_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_event_timestamp` (`event_timestamp`),
  KEY `idx_source` (`source`),
  KEY `idx_exit_page` (`exit_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for wp_storepulse_events
INSERT INTO `wp_storepulse_events` 
  (`event_type`, `order_id`, `user_id`, `event_data`, `utm_source`, `utm_medium`, `utm_campaign`, `event_timestamp`) 
VALUES
  ('order_completed', 12345, 1, '{"total": 99.99, "items": [{"product_id": 111, "quantity": 2}]}', 'google', 'cpc', 'spring_sale', NOW());

------------------------------------------------------------
-- Table: wp_storepulse_aggregated_metrics
-- Purpose: Stores aggregated analytics data (sessions, orders, revenue, etc.)
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_aggregated_metrics` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `aggregation_date` DATE NOT NULL,
  `sessions` INT(11) NOT NULL DEFAULT 0,
  `orders` INT(11) NOT NULL DEFAULT 0,
  `revenue` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_aggregation_date` (`aggregation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample aggregated metrics for today
INSERT INTO `wp_storepulse_aggregated_metrics`
  (`aggregation_date`, `sessions`, `orders`, `revenue`, `conversion_rate`)
VALUES
  (CURDATE(), 1000, 50, 2500.00, 5.00);

------------------------------------------------------------
-- Table: wp_storepulse_reports
-- Purpose: Stores saved report configurations for both pre-built and custom reports.
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_reports` (
  `report_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `report_title` VARCHAR(255) NOT NULL,
  `report_type` VARCHAR(50) NOT NULL DEFAULT 'prebuilt',
  `filters` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample report configuration
INSERT INTO `wp_storepulse_reports`
  (`user_id`, `report_title`, `report_type`, `filters`)
VALUES
  (1, 'Monthly Sales Report', 'custom', '{"date_from": "2025-01-01", "date_to": "2025-01-31", "metrics": ["revenue", "orders"]}');

------------------------------------------------------------
-- Table: wp_storepulse_settings
-- Purpose: Stores plugin settings and configuration data.
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `option_name` VARCHAR(100) NOT NULL,
  `option_value` TEXT NOT NULL,
  `autoload` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_option_name` (`option_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample settings data
INSERT INTO `wp_storepulse_settings`
  (`option_name`, `option_value`, `autoload`)
VALUES
  ('StorePulse_dashboard_layout', '{"widgets": ["sessions", "revenue", "orders"]}', 1),
  ('StorePulse_google_analytics_auth', '{"client_id": "your_client_id", "client_secret": "your_client_secret"}', 0);

------------------------------------------------------------
-- Table: wp_storepulse_logs
-- Purpose: Logs plugin actions such as sync events and errors.
-- Note: "wp_" is placeholder - use $wpdb->prefix in code
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_logs` (
  `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `source` VARCHAR(50) DEFAULT 'unknown',
  `exit_page` VARCHAR(50) DEFAULT 'unknown',
  `order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample log entry
INSERT INTO `wp_storepulse_logs`
  (`event_type`, `message`)
VALUES
  ('sync', 'Google Analytics sync completed successfully.');

------------------------------------------------------------
-- Table: wp_storepulse_campaigns
-- Purpose: Stores campaign and UTM tracking information.
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_campaigns` (
  `campaign_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_name` VARCHAR(255) NOT NULL,
  `utm_source` VARCHAR(100) DEFAULT NULL,
  `utm_medium` VARCHAR(100) DEFAULT NULL,
  `utm_campaign` VARCHAR(100) DEFAULT NULL,
  `click_count` INT(11) NOT NULL DEFAULT 0,
  `conversion_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample campaign record
INSERT INTO `wp_storepulse_campaigns`
  (`campaign_name`, `utm_source`, `utm_medium`, `utm_campaign`)
VALUES
  ('Spring Sale Campaign', 'google', 'cpc', 'spring_sale');

------------------------------------------------------------
-- Table: wp_storepulse_sync
-- Purpose: Manages sync operations for external integrations (e.g., GA sync).
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_sync` (
  `sync_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sync_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `last_run` DATETIME DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  PRIMARY KEY (`sync_id`),
  KEY `idx_sync_type` (`sync_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample sync record
INSERT INTO `wp_storepulse_sync`
  (`sync_type`, `status`, `last_run`)
VALUES
  ('google_analytics', 'completed', NOW());

------------------------------------------------------------
-- Table: wp_storepulse_newsletter_subs
-- Purpose: Stores newsletter subscription data.
-- Note: "wp_" is placeholder - use $wpdb->prefix in code
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_newsletter_subs` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `subscribed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

------------------------------------------------------------
-- Table: wp_storepulse_aggregates
-- Purpose: Stores aggregated daily metrics.
-- Note: "wp_" is placeholder - use $wpdb->prefix in code
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_storepulse_aggregates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `aggregate_date` DATE NOT NULL,
  `sessions` INT DEFAULT 0,
  `revenue` FLOAT DEFAULT 0,
  `conversion_rate` FLOAT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aggregate_date` (`aggregate_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;