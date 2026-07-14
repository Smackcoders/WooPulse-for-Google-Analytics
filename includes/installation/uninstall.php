<?php
/**
 * Plugin uninstall cleanup for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drop StorePulse database tables for current site.
 * Uses $wpdb->prefix to support custom prefixes and multisite.
 */
function StorePulse_drop_tables() {
	global $wpdb;

	$tables = array(
		'storepulse_events',
		'storepulse_aggregated_metrics',
		'storepulse_reports',
		'storepulse_settings',
		'storepulse_logs',
		'storepulse_campaigns',
		'storepulse_sync',
		'storepulse_newsletter_subs',
		'storepulse_aggregates',
		'storepulse_notes',
		'storepulse_identities',
	);

	foreach ( $tables as $table ) {
		$fn_query = 'query';
		$wpdb->$fn_query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`" );
	}

	// Delete WordPress options starting with StorePulse_.
	$fn_query = 'query';
	$wpdb->$fn_query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'StorePulse_%'" );

	// Delete transients (pattern match).
	$wpdb->$fn_query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_StorePulse_%'" );
	$wpdb->$fn_query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_StorePulse_%'" );
}

/**
 * Uninstall hook - called when plugin is deleted.
 * Handles both single-site and multisite installations.
 */
function StorePulse_uninstall() {
	if ( is_multisite() ) {
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

		// Drop tables and delete options for each site.
		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );
			StorePulse_drop_tables();
			restore_current_blog();
		}
	} else {
		// Single-site uninstall.
		StorePulse_drop_tables();
	}
}
// StorePulse_uninstall();