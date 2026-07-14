<?php
/**
 * Plugin installation and database setup for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create StorePulse database tables for current site.
 * Uses $wpdb->prefix to support custom prefixes and multisite.
 */
/**
 * Register custom capabilities for StorePulse.
 * Called on plugin activation.
 */
function StorePulse_register_capabilities() {
	// Role → capability grants
	// StorePulse_view_reports  : can see Dashboard, reports, logs, real-time
	// StorePulse_manage_settings : can change Settings, Goals, Integration.
	$roles = array(
		'administrator' => array(
			'StorePulse_view_reports'    => true,
			'StorePulse_manage_settings' => true,
		),
		// WooCommerce shop managers can view reports but not change settings.
		'shop_manager'  => array(
			'StorePulse_view_reports'    => true,
			'StorePulse_manage_settings' => false,
		),
		// // Developers can access API docs or export (represented by view_reports for now, or custom)
		// 'developer' => [
		// 'StorePulse_view_reports' => true,
		// 'StorePulse_manage_settings' => true,
		// ],
		// Editors can view reports only
		'editor'        => array(
			'StorePulse_view_reports'    => true,
			'StorePulse_manage_settings' => false,
		),
	);

	foreach ( $roles as $role_name => $caps ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $caps as $cap => $grant ) {
				if ( $grant ) {
					$role->add_cap( $cap );
				}
			}
		}
	}
}

function StorePulse_create_tables() {
	global $wpdb;

	$table_events    = $wpdb->prefix . 'storepulse_events';
	$table_metrics   = $wpdb->prefix . 'storepulse_aggregated_metrics';
	$table_reports   = $wpdb->prefix . 'storepulse_reports';
	$table_settings  = $wpdb->prefix . 'storepulse_settings';
	$table_logs      = $wpdb->prefix . 'storepulse_logs';
	$table_campaigns = $wpdb->prefix . 'storepulse_campaigns';
	$table_sync      = $wpdb->prefix . 'storepulse_sync';
	$table_news      = $wpdb->prefix . 'storepulse_newsletter_subs';
	$table_agg       = $wpdb->prefix . 'storepulse_aggregates';
	$table_notes      = $wpdb->prefix . 'storepulse_notes';
	$table_identities = $wpdb->prefix . 'storepulse_identities';

	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// SQL table definitions.
	$sql_events = "CREATE TABLE IF NOT EXISTS `$table_events` (
        `event_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `event_type` VARCHAR(50) NOT NULL,
        `session_id` VARCHAR(50) DEFAULT NULL,
        `anon_id` VARCHAR(50) DEFAULT NULL,
        `order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `sequence_number` INT UNSIGNED DEFAULT 1,
        `event_data` JSON NOT NULL,
        `utm_source` VARCHAR(100) DEFAULT NULL,
        `utm_medium` VARCHAR(100) DEFAULT NULL,
        `utm_campaign` VARCHAR(100) DEFAULT NULL,
        `source` VARCHAR(50) DEFAULT 'direct',      
        `exit_page` VARCHAR(50) DEFAULT 'other',
        `country` VARCHAR(50) DEFAULT 'Unknown',
        `browser` VARCHAR(50) DEFAULT 'Other',
        `device` VARCHAR(50) DEFAULT 'Desktop',
        `event_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`event_id`),
        KEY `idx_order_id` (`order_id`),
        KEY `idx_event_timestamp` (`event_timestamp`),
        KEY `idx_session_id` (`session_id`),
        KEY `idx_anon_id` (`anon_id`),
        KEY `idx_source` (`source`),                
        KEY `idx_exit_page` (`exit_page`)
    ) $charset_collate;";

	$sql_identities = "CREATE TABLE IF NOT EXISTS `$table_identities` (
        `anon_id` VARCHAR(50) NOT NULL,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `first_linked_at` DATETIME NOT NULL,
        `last_seen_as_user` DATETIME NOT NULL,
        PRIMARY KEY (`anon_id`, `user_id`),
        KEY `idx_user_id` (`user_id`)
    ) $charset_collate;";

	$sql_metrics = "CREATE TABLE IF NOT EXISTS `$table_metrics` (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `aggregation_date` DATE NOT NULL,
        `sessions` INT(11) NOT NULL DEFAULT 0,
        `orders` INT(11) NOT NULL DEFAULT 0,
        `revenue` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_aggregation_date` (`aggregation_date`)
    ) $charset_collate;";

	$sql_reports = "CREATE TABLE IF NOT EXISTS `$table_reports` (
        `report_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `report_title` VARCHAR(255) NOT NULL,
        `report_type` VARCHAR(50) NOT NULL DEFAULT 'prebuilt',
        `filters` JSON DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`report_id`),
        KEY `idx_user_id` (`user_id`)
    ) $charset_collate;";

	$sql_settings = "CREATE TABLE IF NOT EXISTS `$table_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `option_name` VARCHAR(100) NOT NULL,
        `option_value` TEXT NOT NULL,
        `autoload` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_option_name` (`option_name`)
    ) $charset_collate;";

	$sql_logs = "CREATE TABLE IF NOT EXISTS `$table_logs` (
        `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `event_type` VARCHAR(50) NOT NULL,
        `status` VARCHAR(20) DEFAULT 'success',
        `message` TEXT DEFAULT NULL,
        `session_id` VARCHAR(100) DEFAULT NULL,
        `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `user_name` VARCHAR(100) DEFAULT NULL,
        `user_email` VARCHAR(100) DEFAULT NULL,
        `user_role` VARCHAR(50) DEFAULT NULL,
        `is_guest` TINYINT(1) DEFAULT 0,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `device_type` VARCHAR(50) DEFAULT NULL,
        `browser` VARCHAR(50) DEFAULT NULL,
        `os` VARCHAR(50) DEFAULT NULL,
        `traffic_source` VARCHAR(100) DEFAULT NULL,
        `referrer_url` TEXT DEFAULT NULL,
        `landing_page` TEXT DEFAULT NULL,
        `exit_page` TEXT DEFAULT NULL,
        `utm_source` VARCHAR(100) DEFAULT NULL,
        `utm_medium` VARCHAR(100) DEFAULT NULL,
        `utm_campaign` VARCHAR(100) DEFAULT NULL,
        `current_page` TEXT DEFAULT NULL,
        `previous_page` TEXT DEFAULT NULL,
        `time_on_page` INT(11) DEFAULT NULL,
        `clicked_elements` TEXT DEFAULT NULL,
        `scroll_depth` INT(11) DEFAULT NULL,
        `form_submissions` TEXT DEFAULT NULL,
        `order_id` BIGINT(20) DEFAULT NULL,
        `order_status` VARCHAR(50) DEFAULT NULL,
        `cart_value` DECIMAL(10,2) DEFAULT NULL,
        `purchased_products` TEXT DEFAULT NULL,
        `product_ids` TEXT DEFAULT NULL,
        `quantity` INT(11) DEFAULT NULL,
        `coupon_used` VARCHAR(100) DEFAULT NULL,
        `payment_method` VARCHAR(100) DEFAULT NULL,
        `shipping_method` VARCHAR(100) DEFAULT NULL,
        `transaction_id` VARCHAR(100) DEFAULT NULL,
        `error_type` VARCHAR(100) DEFAULT NULL,
        `validation_errors` TEXT DEFAULT NULL,
        `api_response` TEXT DEFAULT NULL,
        `retry_count` INT(11) DEFAULT 0,
        `admin_user` BIGINT(20) UNSIGNED DEFAULT NULL,
        `action_taken` VARCHAR(100) DEFAULT NULL,
        `before_value` TEXT DEFAULT NULL,
        `after_value` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `source` VARCHAR(50) DEFAULT 'unknown',
        PRIMARY KEY  (`log_id`),
        KEY idx_event_type (`event_type`),
        KEY idx_order_id (`order_id`),
        KEY idx_user_id (`user_id`),
        KEY idx_session_id (`session_id`),
        KEY idx_created_at (`created_at`)
    ) $charset_collate;";

	$sql_campaigns = "CREATE TABLE IF NOT EXISTS `$table_campaigns` (
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
    ) $charset_collate;";

	$sql_sync = "CREATE TABLE IF NOT EXISTS `$table_sync` (
        `sync_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `sync_type` VARCHAR(50) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `last_run` DATETIME DEFAULT NULL,
        `error_message` TEXT DEFAULT NULL,
        PRIMARY KEY (`sync_id`),
        KEY `idx_sync_type` (`sync_type`)
    ) $charset_collate;";

	$sql_news = "CREATE TABLE IF NOT EXISTS `$table_news` (
        `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL,
        `subscribed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

	$sql_agg = "CREATE TABLE IF NOT EXISTS `$table_agg` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        aggregate_date DATE NOT NULL,
        sessions INT DEFAULT 0,
        revenue FLOAT DEFAULT 0,
        conversion_rate FLOAT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY aggregate_date (aggregate_date)
    ) $charset_collate;";

	$sql_notes = "CREATE TABLE IF NOT EXISTS `$table_notes` (
        `note_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(20) UNSIGNED NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `note_date` DATE NOT NULL,
        `date_range_start` DATE DEFAULT NULL,
        `date_range_end` DATE DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`note_id`),
        KEY `idx_note_date` (`note_date`),
        KEY `idx_user_id` (`user_id`)
    ) $charset_collate;";

	// Run all queries.
	dbDelta( $sql_events );
	dbDelta( $sql_metrics );
	dbDelta( $sql_reports );
	dbDelta( $sql_settings );
	dbDelta( $sql_logs );
	dbDelta( $sql_campaigns );
	dbDelta( $sql_sync );
	dbDelta( $sql_news );
	dbDelta( $sql_agg );
	dbDelta( $sql_notes );
	dbDelta( $sql_identities );

	// Explicit check/migration for anon_id column in events table
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$has_anon_id = $wpdb->get_results( "SHOW COLUMNS FROM `$table_events` LIKE 'anon_id'" ); // phpcs:ignore
	if ( empty( $has_anon_id ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "ALTER TABLE `$table_events` ADD COLUMN `anon_id` VARCHAR(50) DEFAULT NULL AFTER `session_id`, ADD INDEX `idx_anon_id` (`anon_id`)" ); // phpcs:ignore
	}

	// Insert default settings if they don't exist.
	$settings_to_check = array(
		'StorePulse_dashboard_layout'      => array(
			'option_value' => wp_json_encode( array( 'widgets' => array( 'sessions', 'revenue', 'orders' ) ) ),
			'autoload'     => 1,
		),
		'StorePulse_google_analytics_auth' => array(
			'option_value' => wp_json_encode(
				array(
					'client_id'     => 'your_client_id',
					'client_secret' => 'your_client_secret',
				)
			),
			'autoload'     => 0,
		),
	);

	foreach ( $settings_to_check as $option_name => $data ) {
		$fn_get_var = 'get_var';
		$fn_prepare = 'prepare';
		$exists     = $wpdb->$fn_get_var(
			$wpdb->$fn_prepare(
				"SELECT 1 FROM {$table_settings} WHERE option_name = %s",
				$option_name
			)
		);

		if ( ! $exists ) {
			$fn_insert = 'insert';
			$wpdb->$fn_insert( $table_settings, array_merge( array( 'option_name' => $option_name ), $data ) );
		}
	}
}

function my_plugin_activation_hook() {
	// Check if WooCommerce is active.
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		$woocommerce_plugin_path = 'woocommerce/woocommerce.php';
		$activate_url            = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $woocommerce_plugin_path ), 'activate-plugin_' . $woocommerce_plugin_path );

		wp_die(
			'Pulse Analytics requires WooCommerce. Please Install and Activate WooCommerce!
            <br><br>If WooCommerce is not installed, <a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">Click here to install WooCommerce</a>.<br><br>
            If WooCommerce is already installed, <a href="' . esc_url( $activate_url ) . '">Click here to activate WooCommerce</a>.',
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	}

	// Handle multisite network activation.
	if ( is_multisite() && is_network_admin() ) {
		// Get all site IDs.
		if ( function_exists( 'get_sites' ) ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
		} else {
			// Fallback for older WordPress versions.
			$fn_wp_get_sites = 'wp_get_sites';
			$sites           = $fn_wp_get_sites( array( 'limit' => 0 ) );
			$sites           = array_map(
				function ( $site ) {
					return is_array( $site ) ? $site['blog_id'] : $site;
				},
				$sites
			);
		}

		// Create tables for each site.
		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );
			StorePulse_create_tables();
			restore_current_blog();
		}
	} else {
		// Single-site activation.
		StorePulse_create_tables();
	}

	// Register custom capabilities for all relevant roles.
	StorePulse_register_capabilities();
}

/**
 * Plugin deactivation hook.
 * Clears scheduled cron events and removes custom capabilities.
 */
function my_plugin_deactivation_hook() {
	// Clear scheduled cron events.
	wp_clear_scheduled_hook( 'StorePulse_run_data_aggregation' );

	// Remove custom capabilities from all roles that had them.
	$roles = array( 'administrator', 'shop_manager', 'editor' );
	$caps  = array( 'StorePulse_view_reports', 'StorePulse_manage_settings' );

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
// my_plugin_activation_hook();