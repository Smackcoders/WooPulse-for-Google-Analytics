<?php
/**
 * Data aggregation functions for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once GA_PLUGIN_DIR . 'includes/helpers.php'; // For logging.

// WP-Cron Setup.
register_activation_hook(
	__FILE__,
	function () {
		if ( ! wp_next_scheduled( 'StorePulse_daily_aggregation' ) ) {
			wp_schedule_event( time(), 'daily', 'StorePulse_daily_aggregation' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'StorePulse_daily_aggregation' );
	}
);

add_action( 'StorePulse_daily_aggregation', 'StorePulse_process_data_aggregation' );

/**
 * Daily aggregation: Save to BOTH tables
 */
function StorePulse_process_data_aggregation() {
	global $wpdb;

	// Process today.
	$today = current_time( 'Y-m-d' );
	StorePulse_aggregate_single_day( $today );

	// Optional: Backfill last 7 days if they're empty.
	for ( $i = 1; $i <= 7; $i++ ) {
		$date      = gmdate( 'Y-m-d', strtotime( "-$i days" ) );
		$cache_key = 'StorePulse_check_agg_' . $date;
		$check     = wp_cache_get( $cache_key, 'StorePulse' );

		if ( false === $check ) {
			$fn_get_var = 'get_var';
			$fn_prepare = 'prepare';
			$check      = (int) $wpdb->$fn_get_var(
				$wpdb->$fn_prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}storepulse_aggregates WHERE aggregate_date = %s",
					$date
				)
			);
			wp_cache_set( $cache_key, $check, 'StorePulse', HOUR_IN_SECONDS );
		}

		if ( 0 == $check ) {
			StorePulse_aggregate_single_day( $date );
		}
	}
}

function StorePulse_aggregate_single_day( $date ) {
	global $wpdb;

	// Get data for specific date - sessions.
	$cache_key_sessions = 'StorePulse_agg_sessions_' . $date;
	$session_count      = wp_cache_get( $cache_key_sessions, 'StorePulse' );
	$fn_get_var         = 'get_var';
	$fn_prepare         = 'prepare';

	if ( false === $session_count ) {
		$session_count = (int) $wpdb->$fn_get_var(
			$wpdb->$fn_prepare(
				"
            SELECT COUNT(DISTINCT session_id) 
            FROM {$wpdb->prefix}storepulse_events 
            WHERE DATE(event_timestamp) = %s
        ",
				$date
			)
		);
		wp_cache_set( $cache_key_sessions, $session_count, 'StorePulse', 300 );
	}

	// Get data for specific date - orders.
	$cache_key_orders = 'StorePulse_agg_orders_' . $date;
	$orders           = wp_cache_get( $cache_key_orders, 'StorePulse' );
	if ( false === $orders ) {
		$orders = (int) $wpdb->$fn_get_var(
			$wpdb->$fn_prepare(
				"
            SELECT COUNT(DISTINCT order_id) 
            FROM {$wpdb->prefix}storepulse_events 
            WHERE event_type IN ('order_created','order_completed','purchase') 
            AND DATE(event_timestamp) = %s 
            AND order_id IS NOT NULL
        ",
				$date
			)
		);
		wp_cache_set( $cache_key_orders, $orders, 'StorePulse', 300 );
	}

	// Get data for specific date - revenue.
	$cache_key_revenue = 'StorePulse_agg_revenue_' . $date;
	$revenue           = wp_cache_get( $cache_key_revenue, 'StorePulse' );
	if ( false === $revenue ) {
		$revenue = $wpdb->$fn_get_var(
			$wpdb->$fn_prepare(
				"
            SELECT SUM(revenue) FROM (
                SELECT order_id, MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data,'$.total')) AS DECIMAL(10,2))) AS revenue
                FROM {$wpdb->prefix}storepulse_events
                WHERE event_type IN ('order_created','order_completed','purchase')
                AND DATE(event_timestamp) = %s
                AND order_id IS NOT NULL
                GROUP BY order_id
            ) AS unique_orders
        ",
				$date
			)
		);
		wp_cache_set( $cache_key_revenue, $revenue, 'StorePulse', 300 );
	}

	$revenue         = $revenue ? (float) $revenue : 0;
	$conversion_rate = $session_count > 0 ? round( ( $orders / $session_count ) * 100, 2 ) : 0;

	// Save to both tables.
	$data_for_table1 = array(
		'aggregation_date' => $date,
		'sessions'         => (int) $session_count,
		'orders'           => (int) $orders,
		'revenue'          => $revenue,
		'conversion_rate'  => $conversion_rate,
	);

	$data_for_table2 = array(
		'aggregate_date'  => $date,
		'sessions'        => (int) $session_count,
		'revenue'         => $revenue,
		'conversion_rate' => $conversion_rate,
		'created_at'      => current_time( 'mysql' ),
	);

	// Update both tables.
	$fn_replace = 'replace';
	$wpdb->$fn_replace( $wpdb->prefix . 'storepulse_aggregated_metrics', $data_for_table1, array( '%s', '%d', '%d', '%f', '%f' ) );
	$wpdb->$fn_replace( $wpdb->prefix . 'storepulse_aggregates', $data_for_table2, array( '%s', '%d', '%f', '%f', '%s' ) );

	// Invalidate main metrics cache.
	wp_cache_delete( "StorePulse_metrics_$date", 'StorePulse' );
	delete_transient( "StorePulse_metrics_$date" );
	wp_cache_delete( "StorePulse_check_agg_$date", 'StorePulse' );

	return true;
}
