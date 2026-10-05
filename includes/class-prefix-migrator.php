<?php
/**
 * One-time migration of options, capabilities, and transients to sm_pulse_analytics_* keys.
 *
 * @package Sm_Pulse_Analytics
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrates legacy option keys, capabilities, transients, and DB table names.
 */
class PulseAnalytics_Prefix_Migrator {

	const OPTION_FLAG       = 'sm_pulse_analytics_prefix_migrated';
	const TABLES_FLAG       = 'sm_pulse_analytics_tables_renamed';

	/**
	 * Option key map: old => new.
	 *
	 * @return array<string, string>
	 */
	public static function option_map() {
		return array(
			'storepulse_settings'                 => 'sm_pulse_analytics_settings',
			'storepulse_last_manual_sync'         => 'sm_pulse_analytics_last_manual_sync',
			'storepulse_ga4_measurement_id'       => 'sm_pulse_analytics_ga4_measurement_id',
			'storepulse_ga4_config'               => 'sm_pulse_analytics_ga4_config',
			'storepulse_gsc_settings'             => 'sm_pulse_analytics_gsc_settings',
			'storepulse_psi_api_key'              => 'sm_pulse_analytics_psi_api_key',
			'storepulse_psi_override_url'         => 'sm_pulse_analytics_psi_override_url',
			'StorePulse_ga4_auth_tokens'          => 'sm_pulse_analytics_ga4_auth_tokens',
			'StorePulse_ga4_access_token'         => 'sm_pulse_analytics_ga4_access_token',
			'StorePulse_ga4_api_secret'           => 'sm_pulse_analytics_ga4_api_secret',
			'StorePulse_custom_goals'             => 'sm_pulse_analytics_custom_goals',
			'StorePulse_dashboard_layout'         => 'sm_pulse_analytics_dashboard_layout',
			'StorePulse_telemetry_retention_days' => 'sm_pulse_analytics_telemetry_retention_days',
			'StorePulse_auth'                     => 'sm_pulse_analytics_auth',
			'store_ecommerce'                     => 'sm_pulse_analytics_ecommerce',
			'store_ga4'                           => 'sm_pulse_analytics_ga4',
		);
	}

	/**
	 * Capability map: old => new.
	 *
	 * @return array<string, string>
	 */
	public static function capability_map() {
		return array(
			'StorePulse_view_reports'    => 'sm_pulse_analytics_view_reports',
			'StorePulse_manage_settings' => 'sm_pulse_analytics_manage_settings',
		);
	}

	/**
	 * Legacy DB table suffixes that may exist under storepulse_ prefix.
	 *
	 * @return string[]
	 */
	public static function table_suffixes() {
		return array(
			'telemetry',
			'audit',
			'snapshots',
			'events',
			'logs',
			'cache',
		);
	}

	/**
	 * Hook migration on plugins_loaded.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_migrate' ), 3 );
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_rename_tables' ), 4 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_legacy_admin_page' ), 5 );
	}

	/**
	 * Deprecated admin menu slugs from pre-1.0.2 builds (lowercase keys).
	 *
	 * @return array<string, string> Legacy slug => current slug.
	 */
	public static function legacy_admin_page_map() {
		return array(
			'ga-settings'                   => 'pulse-analytics',
			'storepulse-core-web-vitals'    => 'pulse-analytics-site-performance',
			'storepulse-site-performance'   => 'pulse-analytics-site-performance',
			'storepulse-popular-posts'      => 'pulse-analytics-popular-posts',
			'storepulse-link-report'        => 'pulse-analytics-link-report',
		);
	}

	/**
	 * Redirect bookmarked legacy admin URLs to current pulse-analytics slugs.
	 */
	public static function maybe_redirect_legacy_admin_page() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';

		if ( in_array( $page, array( 'sm-pulse-analytics-settings', 'pulse-analytics' ), true ) && 'authenticated' === $step ) {
			wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' ) );
			exit;
		}

		if ( '' === $page ) {
			return;
		}

		$redirects = self::legacy_admin_page_map();
		$page_key  = strtolower( $page );
		if ( ! isset( $redirects[ $page_key ] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . rawurlencode( $redirects[ $page_key ] ) ) );
		exit;
	}

	/**
	 * Run options/caps/transients migration once per site.
	 */
	public static function maybe_migrate() {
		if ( get_option( self::OPTION_FLAG ) ) {
			return;
		}

		self::migrate_options();
		self::migrate_capabilities();
		self::migrate_transients();

		update_option( self::OPTION_FLAG, SM_PULSE_ANALYTICS_VERSION, false );
	}

	/**
	 * Rename wp_*storepulse_* tables to wp_*sm_pulse_analytics_* (idempotent).
	 */
	public static function maybe_rename_tables() {
		if ( get_option( self::TABLES_FLAG ) ) {
			return;
		}

		self::rename_legacy_tables();
		update_option( self::TABLES_FLAG, SM_PULSE_ANALYTICS_VERSION, false );
	}

	/**
	 * Copy old option values to new keys when new key is empty.
	 */
	private static function migrate_options() {
		foreach ( self::option_map() as $old => $new ) {
			$existing = get_option( $new, null );
			if ( null !== $existing && false !== $existing && '' !== $existing && array() !== $existing ) {
				continue;
			}
			$old_val = get_option( $old, null );
			if ( null === $old_val || false === $old_val ) {
				continue;
			}
			update_option( $new, $old_val, false );
		}
	}

	/**
	 * Grant new caps to roles that had legacy caps.
	 */
	private static function migrate_capabilities() {
		$roles = array( 'administrator', 'shop_manager', 'editor' );
		$map   = self::capability_map();

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $map as $old => $new ) {
				if ( $role->has_cap( $old ) && ! $role->has_cap( $new ) ) {
					$role->add_cap( $new );
				}
			}
		}
	}

	/**
	 * Best-effort transient key remaps for known prefixes.
	 */
	private static function migrate_transients() {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}

		$like_patterns = array(
			'_transient_storepulse_%',
			'_transient_timeout_storepulse_%',
			'_transient_StorePulse_%',
			'_transient_timeout_StorePulse_%',
		);

		foreach ( $like_patterns as $like ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
					$like
				),
				ARRAY_A
			);
			if ( empty( $rows ) ) {
				continue;
			}
			foreach ( $rows as $row ) {
				$name = $row['option_name'];
				$new  = str_replace(
					array( '_transient_storepulse_', '_transient_timeout_storepulse_', '_transient_StorePulse_', '_transient_timeout_StorePulse_' ),
					array( '_transient_sm_pulse_analytics_', '_transient_timeout_sm_pulse_analytics_', '_transient_sm_pulse_analytics_', '_transient_timeout_sm_pulse_analytics_' ),
					$name
				);
				if ( $new === $name ) {
					continue;
				}
				if ( false === get_option( $new, false ) ) {
					update_option( $new, $row['option_value'], false );
				}
			}
		}
	}

	/**
	 * RENAME TABLE from storepulse_ to sm_pulse_analytics_ when target missing.
	 */
	private static function rename_legacy_tables() {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}

		$old_base = 'storepulse_';
		$new_base = ( defined( 'SM_PULSE_ANALYTICS_TABLE_PREFIX' ) && SM_PULSE_ANALYTICS_TABLE_PREFIX )
			? SM_PULSE_ANALYTICS_TABLE_PREFIX
			: 'sm_pulse_analytics_';

		if ( $old_base === $new_base ) {
			return;
		}

		foreach ( self::table_suffixes() as $suffix ) {
			$old = $wpdb->prefix . $old_base . $suffix;
			$new = $wpdb->prefix . $new_base . $suffix;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old ) ) === $old;
			if ( ! $old_exists ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new ) ) === $new;
			if ( $new_exists ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $old, $new ) );
		}
	}
}
