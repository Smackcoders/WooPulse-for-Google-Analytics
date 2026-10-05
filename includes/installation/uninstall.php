<?php
/**
 * Plugin uninstall cleanup for Pulse Analytics.
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

/**
 * Drop all Pulse Analytics tables (v2 + legacy) for current site.
 */
function sm_pulse_analytics_drop_tables() {
	$all = array_merge(
		PulseAnalytics_Storage::table_suffixes(),
		PulseAnalytics_Storage::legacy_table_suffixes()
	);
	PulseAnalytics_Storage::drop_tables( $all );

	global $wpdb;
	$option_patterns = array(
		'sm_pulse_analytics_',
		'_transient_sm_pulse_analytics_',
		'_transient_timeout_sm_pulse_analytics_',
		'_transient_PulseAnalytics_',
		'_transient_timeout_PulseAnalytics_',
	);
	foreach ( $option_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $pattern ) . '%'
			)
		);
	}

	// Drop any leftover legacy storepulse_ tables if rename did not run.
	$legacy_base = $wpdb->prefix . 'storepulse_';
	foreach ( array( 'telemetry', 'audit', 'snapshots', 'events', 'logs', 'cache' ) as $suffix ) {
		$table = $legacy_base . $suffix;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
	}

	delete_option( PulseAnalytics_Storage::DB_VERSION_OPTION );
}

function sm_pulse_analytics_uninstall() {
	if ( is_multisite() ) {
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
			sm_pulse_analytics_drop_tables();
			restore_current_blog();
		}
	} else {
		sm_pulse_analytics_drop_tables();
	}
}
