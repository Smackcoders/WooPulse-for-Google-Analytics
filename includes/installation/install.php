<?php
/**
 * Plugin installation and database setup for Pulse Analytics.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sm_pulse_analytics_register_capabilities() {
	$roles = array(
		'administrator' => array(
			'sm_pulse_analytics_view_reports'    => true,
			'sm_pulse_analytics_manage_settings' => true,
		),
		'shop_manager'  => array(
			'sm_pulse_analytics_view_reports'    => true,
			'sm_pulse_analytics_manage_settings' => false,
		),
		'editor'        => array(
			'sm_pulse_analytics_view_reports'    => true,
			'sm_pulse_analytics_manage_settings' => false,
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

/**
 * Create or upgrade Pulse Analytics v2 tables.
 */
function sm_pulse_analytics_create_tables() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate   = $wpdb->get_charset_collate();
	$table_telemetry   = PulseAnalytics_Storage::table_name( 'telemetry' );
	$table_audit       = PulseAnalytics_Storage::table_name( 'audit' );
	$table_snapshots   = PulseAnalytics_Storage::table_name( 'snapshots' );

	$sql_telemetry = "CREATE TABLE {$table_telemetry} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		recorded_at DATETIME NOT NULL,
		kind VARCHAR(32) NOT NULL,
		session_key VARCHAR(64) DEFAULT NULL,
		entity_type VARCHAR(32) DEFAULT NULL,
		entity_id BIGINT(20) UNSIGNED DEFAULT NULL,
		actor_id BIGINT(20) UNSIGNED DEFAULT NULL,
		attr_source VARCHAR(100) DEFAULT NULL,
		attr_medium VARCHAR(100) DEFAULT NULL,
		attr_campaign VARCHAR(100) DEFAULT NULL,
		payload LONGTEXT NOT NULL,
		PRIMARY KEY  (id),
		KEY idx_session_rec (session_key, recorded_at),
		KEY idx_kind_recorded (kind, recorded_at),
		KEY idx_attr_campaign_recorded (attr_campaign, recorded_at),
		KEY idx_entity (entity_type, entity_id),
		KEY idx_recorded_at (recorded_at)
	) {$charset_collate};";

	$sql_audit = "CREATE TABLE {$table_audit} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		recorded_at DATETIME NOT NULL,
		category VARCHAR(32) NOT NULL,
		summary TEXT NOT NULL,
		component VARCHAR(32) DEFAULT 'system',
		context LONGTEXT DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY idx_category (category),
		KEY idx_recorded_at (recorded_at),
		KEY idx_component (component)
	) {$charset_collate};";

	$sql_snapshots = "CREATE TABLE {$table_snapshots} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		lookup_key VARCHAR(191) NOT NULL,
		provider VARCHAR(32) NOT NULL DEFAULT 'internal',
		payload LONGTEXT NOT NULL,
		valid_until DATETIME NOT NULL,
		recorded_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY uniq_lookup_provider (lookup_key, provider),
		KEY idx_valid_until (valid_until),
		KEY idx_provider (provider)
	) {$charset_collate};";

	dbDelta( $sql_telemetry );
	dbDelta( $sql_audit );
	dbDelta( $sql_snapshots );

	PulseAnalytics_Storage::migrate_legacy_tables();

	update_option( PulseAnalytics_Storage::DB_VERSION_OPTION, PulseAnalytics_Storage::DB_VERSION );
}

function sm_pulse_analytics_maybe_upgrade_db() {
	$installed = get_option( PulseAnalytics_Storage::DB_VERSION_OPTION, '0' );
	if ( version_compare( (string) $installed, PulseAnalytics_Storage::DB_VERSION, '<' ) ) {
		sm_pulse_analytics_create_tables();
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\sm_pulse_analytics_maybe_upgrade_db', 5 );

function sm_pulse_analytics_activation_hook() {
	if ( is_multisite() && is_network_admin() ) {
		if ( function_exists( 'get_sites' ) ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
		} else {
			$sites = array( get_current_blog_id() );
		}

		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );
			sm_pulse_analytics_create_tables();
			if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_MonsterInsights_Migration' ) ) {
				PulseAnalytics_MonsterInsights_Migration::flag_activation_prompt();
			}
			restore_current_blog();
		}
	} else {
		sm_pulse_analytics_create_tables();
	}

	sm_pulse_analytics_register_capabilities();

	if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Site_Profile' ) ) {
		PulseAnalytics_Site_Profile::sync_profile();
	}

	if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_MonsterInsights_Migration' ) ) {
		PulseAnalytics_MonsterInsights_Migration::flag_activation_prompt();
	}
}

function sm_pulse_analytics_deactivation_hook() {
	wp_clear_scheduled_hook( 'sm_pulse_analytics_run_data_aggregation' );

	$roles = array( 'administrator', 'shop_manager', 'editor' );
	$caps  = array( 'sm_pulse_analytics_view_reports', 'sm_pulse_analytics_manage_settings' );

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
