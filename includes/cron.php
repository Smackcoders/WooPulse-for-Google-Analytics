<?php
/**
 * StorePulse Cron Jobs
 *
 * @package StorePulse_For_Google_Analytics
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Make sure aggregator.php is loaded.
require_once __DIR__ . '/aggregator.php';
// Schedule cron jobs on plugin activation.
register_activation_hook( __FILE__, 'StorePulse_activate_cron' );
function StorePulse_activate_cron() {
	// Clear any existing schedules first.
	wp_clear_scheduled_hook( 'StorePulse_daily_aggregation' );
	wp_clear_scheduled_hook( 'StorePulse_campaign_aggregation' );

	// Set new schedules.
	wp_schedule_event( time(), 'daily', 'StorePulse_daily_aggregation' );
	wp_schedule_event( time(), 'daily', 'StorePulse_campaign_aggregation' );

	// error_log('🔄 StorePulse Crons scheduled during activation');.
}

// Clear cron jobs on plugin deactivation.
register_deactivation_hook( __FILE__, 'StorePulse_deactivate_cron' );
function StorePulse_deactivate_cron() {
	wp_clear_scheduled_hook( 'StorePulse_daily_aggregation' );
	wp_clear_scheduled_hook( 'StorePulse_campaign_aggregation' );
	// error_log('🔄 StorePulse Crons cleared during deactivation');.
}

// Ensure cron jobs are scheduled (runs on every page load).
add_action( 'init', 'StorePulse_ensure_cron_schedules', 20 );
function StorePulse_ensure_cron_schedules() {
	if ( ! wp_next_scheduled( 'StorePulse_daily_aggregation' ) ) {
		wp_schedule_event( time(), 'daily', 'StorePulse_daily_aggregation' );
		// error_log('🔄 StorePulse Daily Cron scheduled via init');.
	}

	if ( ! wp_next_scheduled( 'StorePulse_campaign_aggregation' ) ) {
		wp_schedule_event( time(), 'daily', 'StorePulse_campaign_aggregation' );
		// error_log('🔄 StorePulse Campaign Cron scheduled via init');.
	}
}

// Hook your aggregation functions.
add_action( 'StorePulse_daily_aggregation', 'StorePulse_process_data_aggregation' );
add_action( 'StorePulse_campaign_aggregation', array( 'SmackCoders\WGA\WC_UTM_Campaign_Tracker', 'process_campaign_aggregation' ) );
