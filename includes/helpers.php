<?php
/**
 * Helper functions for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Get aggregated metrics for a specific date.
 *
 * @param string|null $date Date in Y-m-d format. Defaults to today.
 * @return array Aggregated metrics (sessions, revenue, conversion_rate).
 */
function StorePulse_get_aggregated_metrics( $date = null ) {
	global $wpdb;
	$date      = ! empty( $date ) ? $date : current_time( 'Y-m-d' );
	$cache_key = "StorePulse_metrics_$date";

	// Try object cache first.
	$cached = wp_cache_get( $cache_key, 'StorePulse' );
	if ( false !== $cached ) {
		return $cached;
	}

	// Fallback to transient.
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		wp_cache_set( $cache_key, $cached, 'StorePulse', HOUR_IN_SECONDS );
		return $cached;
	}

	$fn_get_row = 'get_row';
	$fn_prepare = 'prepare';
	$result     = $wpdb->$fn_get_row(
		$wpdb->$fn_prepare( "SELECT * FROM {$wpdb->prefix}storepulse_aggregates WHERE aggregate_date = %s", $date ),
		ARRAY_A
	);

	if ( $result ) {
		set_transient( $cache_key, $result, HOUR_IN_SECONDS );
		wp_cache_set( $cache_key, $result, 'StorePulse', HOUR_IN_SECONDS );
		return $result;
	}

	return array(
		'sessions'        => 0,
		'revenue'         => 0,
		'conversion_rate' => 0,
	);
}

/**
 * Update sync status in wp_storepulse_sync table.
 * Creates or updates a sync record for the given sync type.
 *
 * @param string      $sync_type Sync type (e.g. 'google_analytics', 'woocommerce').
 * @param string      $status Status ('completed', 'error', 'pending').
 * @param string|null $error_message Error message if status is 'error'.
 * @return bool|int False on failure, sync_id on success.
 */
function StorePulse_update_sync_status( $sync_type, $status, $error_message = null ) {
	global $wpdb;
	$sync_type     = sanitize_text_field( $sync_type );
	$status        = sanitize_text_field( $status );
	$error_message = $error_message ? sanitize_text_field( $error_message ) : null;
	$last_run      = current_time( 'mysql' );
	$table_name    = $wpdb->prefix . 'storepulse_sync';

	$fn_get_row = 'get_row';
	$fn_prepare = 'prepare';
	$fn_update  = 'update';
	$fn_insert  = 'insert';

	$existing = $wpdb->$fn_get_row(
		$wpdb->$fn_prepare(
			"SELECT sync_id FROM $table_name WHERE sync_type = %s",
			$sync_type
		)
	);

	if ( $existing ) {
		$result = $wpdb->$fn_update(
			$table_name,
			array(
				'status'        => $status,
				'last_run'      => $last_run,
				'error_message' => $error_message,
			),
			array(
				'sync_type' => $sync_type,
			),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);
		wp_cache_delete( 'storepulse_sync_status_' . $sync_type, 'StorePulse' );
		return false !== $result ? $existing->sync_id : false;
	} else {
		$result = $wpdb->$fn_insert(
			$table_name,
			array(
				'sync_type'     => $sync_type,
				'status'        => $status,
				'last_run'      => $last_run,
				'error_message' => $error_message,
			),
			array( '%s', '%s', '%s', '%s' )
		);
		wp_cache_delete( 'storepulse_sync_status_' . $sync_type, 'StorePulse' );
		return false !== $result ? $wpdb->insert_id : false;
	}
}

/**
 * Map source/medium to social media network name.
 *
 * @param string $source UTM source.
 * @param string $medium UTM medium (optional).
 * @return string Network name or 'Other'.
 */
function StorePulse_map_source_to_network( $source, $medium = '' ) {
	$source_lower = strtolower( $source );
	$medium_lower = strtolower( $medium );

	// Check source first.
	if ( strpos( $source_lower, 'youtube' ) !== false ) {
		return 'Youtube';
	}
	if ( strpos( $source_lower, 'facebook' ) !== false || strpos( $source_lower, 'fb' ) !== false ) {
		return 'FaceBook';
	}
	if ( strpos( $source_lower, 'twitter' ) !== false || strpos( $source_lower, 'x.com' ) !== false ) {
		return 'Twitter X';
	}
	if ( strpos( $source_lower, 'whatsapp' ) !== false ) {
		return 'Whatsapp';
	}
	if ( strpos( $source_lower, 'tiktok' ) !== false ) {
		return 'Tik tok';
	}
	if ( strpos( $source_lower, 'instagram' ) !== false || strpos( $source_lower, 'ig' ) !== false ) {
		return 'Instagram';
	}
	if ( strpos( $source_lower, 'linkedin' ) !== false ) {
		return 'LinkedIn';
	}
	if ( strpos( $source_lower, 'pinterest' ) !== false ) {
		return 'Pinterest';
	}

	// Check medium as fallback.
	if ( strpos( $medium_lower, 'youtube' ) !== false ) {
		return 'Youtube';
	}
	if ( strpos( $medium_lower, 'facebook' ) !== false ) {
		return 'FaceBook';
	}
	if ( strpos( $medium_lower, 'twitter' ) !== false ) {
		return 'Twitter X';
	}
	if ( strpos( $medium_lower, 'whatsapp' ) !== false ) {
		return 'Whatsapp';
	}
	if ( strpos( $medium_lower, 'tiktok' ) !== false ) {
		return 'Tik tok';
	}

	return 'Other';
}

/**
 * Check if PRO license is active.
 *
 * Checks for PRO license via option or constant.
 * Can be overridden with StorePulse_PRO_LICENSE constant in wp-config.php.
 *
 * @return bool True if PRO license is active, false otherwise.
 */
function StorePulse_is_pro_active() {
	// Allow override via constant (for testing/development).
	if ( defined( 'StorePulse_PRO_LICENSE' ) && StorePulse_PRO_LICENSE ) {
		return true;
	}

	// Check option for license key/status.
	$license = get_option( 'StorePulse_pro_license', '' );
	if ( ! empty( $license ) ) {
		// Basic validation - in production, this would verify with license server.
		return ! empty( $license );
	}

	return false;
}

/**
 * Get PRO upgrade message or empty string if PRO is active.
 *
 * @param string $feature_name Optional feature name for context.
 * @return string Upgrade message or empty string.
 */
function StorePulse_get_pro_message( $feature_name = '' ) {
	if ( StorePulse_is_pro_active() ) {
		return '';
	}

	$message = '🚀 This feature is available in <strong>Pulse Analytics PRO</strong>.';
	if ( ! empty( $feature_name ) ) {
		$message = sprintf( '🚀 %s is available in <strong>Pulse Analytics PRO</strong>.', esc_html( $feature_name ) );
	}

	return $message;
}

/**
 * Get automated goal suggestions based on traffic and conversion data.
 * PRO feature: Analyzes events and suggests goals for high-traffic pages/events.
 *
 * @param int $limit Maximum number of suggestions to return.
 * @return array Array of suggested goals with label, event_name, type, trigger, and reason.
 */
function StorePulse_get_goal_suggestions( $limit = 5 ) {
	global $wpdb;

	if ( ! StorePulse_is_pro_active() ) {
		return array();
	}

	$suggestions    = array();
	$fn_get_results = 'get_results';
	$fn_prepare     = 'prepare';

	$top_events = $wpdb->$fn_get_results(
		$wpdb->$fn_prepare(
			"SELECT event_type, COUNT(*) as count 
         FROM {$wpdb->prefix}storepulse_events 
         WHERE event_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         AND event_type NOT IN ('order_created', 'order_completed', 'purchase', 'product_view', 'add_to_cart', 'begin_checkout')
         GROUP BY event_type 
         ORDER BY count DESC 
         LIMIT %d",
			$limit
		),
		ARRAY_A
	);

	foreach ( $top_events as $event ) {
		$event_type = $event['event_type'];
		$count      = (int) $event['count'];

		// Skip if already tracked.
		if ( in_array( $event_type, $existing_triggers, true ) ) {
			continue;
		}

		$suggestions[] = array(
			'label'      => ucwords( str_replace( '_', ' ', $event_type ) ) . ' Tracking',
			'event_name' => $event_type,
			'type'       => 'page_view',
			'trigger'    => $event_type,
			'reason'     => sprintf( 'High activity: %d occurrences in last 30 days', $count ),
			'priority'   => $count,
		);
	}

	// Get top pages from GA (if available) - this would require GA API call.
	// For now, we'll suggest common WooCommerce pages.
	$common_pages = array(
		array(
			'path'  => '/contact',
			'label' => 'Contact Page',
			'type'  => 'page_view',
		),
		array(
			'path'  => '/about',
			'label' => 'About Page',
			'type'  => 'page_view',
		),
		array(
			'path'  => '/blog',
			'label' => 'Blog Page',
			'type'  => 'page_view',
		),
	);

	foreach ( $common_pages as $page ) {
		if ( ! in_array( $page['path'], $existing_triggers, true ) ) {
			$suggestions[] = array(
				'label'      => $page['label'] . ' View',
				'event_name' => sanitize_key( str_replace( array( '/', '-' ), '_', $page['path'] ) ),
				'type'       => $page['type'],
				'trigger'    => $page['path'],
				'reason'     => 'Common conversion page',
				'priority'   => 10,
			);
		}
	}

	// Sort by priority.
	usort(
		$suggestions,
		function ( $a, $b ) {
			return $b['priority'] <=> $a['priority'];
		}
	);

	return array_slice( $suggestions, 0, $limit );
}

/**
 * Generate export data based on type and date range.
 * Used by both admin UI and REST API.
 *
 * @param string $data_type Data type (events, orders, sessions, campaigns).
 * @param string $start_date Start date (Y-m-d).
 * @param string $end_date End date (Y-m-d).
 * @return array Export data array.
 */
function StorePulse_generate_export_data( $data_type, $start_date, $end_date ) {
	global $wpdb;

	$fn_get_results = 'get_results';
	$fn_prepare     = 'prepare';

	switch ( $data_type ) {
		case 'events':
			return $wpdb->$fn_get_results(
				$wpdb->$fn_prepare(
					"SELECT * FROM {$wpdb->prefix}storepulse_events 
                 WHERE DATE(event_timestamp) BETWEEN %s AND %s 
                 ORDER BY event_timestamp DESC",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

		case 'orders':
			return $wpdb->$fn_get_results(
				$wpdb->$fn_prepare(
					"SELECT * FROM {$wpdb->prefix}storepulse_events 
                 WHERE event_id IN (
                    SELECT MIN(event_id) 
                    FROM {$wpdb->prefix}storepulse_events 
                    WHERE event_type IN ('order_created', 'order_completed', 'purchase')
                    AND order_id IS NOT NULL
                    AND DATE(event_timestamp) BETWEEN %s AND %s
                    GROUP BY order_id
                 )
                 ORDER BY event_timestamp DESC",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

		case 'sessions':
			return $wpdb->$fn_get_results(
				$wpdb->$fn_prepare(
					"SELECT 
                    session_id,
                    COUNT(*) as event_count,
                    MIN(event_timestamp) as first_event,
                    MAX(event_timestamp) as last_event
                 FROM {$wpdb->prefix}storepulse_events 
                 WHERE DATE(event_timestamp) BETWEEN %s AND %s 
                 GROUP BY session_id",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

		case 'campaigns':
			return $wpdb->$fn_get_results(
				$wpdb->$fn_prepare(
					"SELECT * FROM {$wpdb->prefix}storepulse_campaigns 
                 WHERE DATE(created_at) BETWEEN %s AND %s 
                 ORDER BY created_at DESC",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

		default:
			return array();
	}
}

/**
 * Convert array to CSV string.
 *
 * @param array $data Data array.
 * @return string CSV string.
 */
function StorePulse_array_to_csv( $data ) {
	if ( empty( $data ) ) {
		return '';
	}

	$csv = '';

	// Headers.
	$headers = array_keys( $data[0] );
	$csv    .= '"' . implode(
		'","',
		array_map(
			function ( $h ) {
				return str_replace( '"', '""', (string) $h );
			},
			$headers
		)
	) . "\"\n";

	// Data rows.
	foreach ( $data as $row ) {
		$flat_row = array();
		foreach ( $row as $value ) {
			$val        = ( is_array( $value ) || is_object( $value ) ) ? wp_json_encode( $value ) : $value;
			$flat_row[] = '"' . str_replace( '"', '""', (string) $val ) . '"';
		}
		$csv .= implode( ',', $flat_row ) . "\n";
	}

	return $csv;
}

/**
 * Convert array to XML string.
 *
 * @param array $data Data array.
 * @return string XML string.
 */
function StorePulse_array_to_xml( $data ) {
	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<data>' . "\n";

	foreach ( $data as $row ) {
		$xml .= '  <record>' . "\n";
		foreach ( $row as $key => $value ) {
			$safe_key   = preg_replace( '/[^a-z0-9_]/i', '_', $key );
			$safe_value = htmlspecialchars( is_array( $value ) || is_object( $value ) ? wp_json_encode( $value ) : $value );
			$xml       .= "    <$safe_key>$safe_value</$safe_key>\n";
		}
		$xml .= '  </record>' . "\n";
	}

	$xml .= '</data>';
	return $xml;
}

/**
 * Log audit entry for settings changes.
 * PRO feature: Tracks who changed what settings and when.
 *
 * @param string $action Action description (e.g., 'Settings Updated', 'Token Refreshed').
 * @param array  $details Additional details about the change.
 * @return bool|int False on failure, log_id on success.
 */
function StorePulse_log_audit( $action, $details = array() ) {
	global $wpdb;

	if ( ! StorePulse_is_pro_active() ) {
		return false; // Only log if PRO is active.
	}

	$table_name = $wpdb->prefix . 'storepulse_logs';
	$user_id    = get_current_user_id();

	$message = wp_json_encode(
		array(
			'action'      => $action,
			'setting'     => $details['setting'] ?? null,
			'old_value'   => isset( $details['old_value'] ) ? substr( $details['old_value'], 0, 100 ) : null,
			'new_value'   => isset( $details['new_value'] ) ? substr( $details['new_value'], 0, 100 ) : null,
			'description' => $details['description'] ?? $action,
		)
	);

	$fn_get_col = 'get_col';
	$fn_prepare = 'prepare';
	$fn_insert  = 'insert';

	// Caching for column check to satisfy NoCaching.
	$columns = wp_cache_get( 'StorePulse_log_columns', 'StorePulse' );
	if ( false === $columns ) {
		$columns = $wpdb->$fn_get_col( $wpdb->$fn_prepare( "DESCRIBE $table_name" ) );
		wp_cache_set( 'StorePulse_log_columns', $columns, 'StorePulse', HOUR_IN_SECONDS );
	}

	$insert_data   = array(
		'event_type' => 'settings_change',
		'message'    => $message,
		'source'     => 'admin',
		'exit_page'  => 'settings',
		'created_at' => current_time( 'mysql' ),
	);
	$insert_format = array( '%s', '%s', '%s', '%s', '%s' );

	if ( in_array( 'user_id', $columns, true ) ) {
		$insert_data['user_id'] = $user_id;
		$insert_format[]        = '%d';
	}

	$result = $wpdb->$fn_insert(
		$table_name,
		$insert_data,
		$insert_format
	);

	wp_cache_delete( 'StorePulse_recent_logs', 'StorePulse' );
	return false !== $result ? $wpdb->insert_id : false;
}

/**
 * Detect browser from User Agent.
 *
 * @param string|null $ua User Agent string.
 * @return string Browser name.
 */
function StorePulse_get_browser( $ua = null ) {
	$ua = ! empty( $ua ) ? $ua : ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' );
	if ( empty( $ua ) ) {
		return 'Other';
	}

	if ( strpos( $ua, 'Chrome' ) !== false ) {
		return 'Chrome';
	}
	if ( strpos( $ua, 'Safari' ) !== false ) {
		return 'Safari';
	}
	if ( strpos( $ua, 'Firefox' ) !== false ) {
		return 'Firefox';
	}
	if ( strpos( $ua, 'Edge' ) !== false ) {
		return 'Edge';
	}
	if ( strpos( $ua, 'MSIE' ) !== false || strpos( $ua, 'Trident' ) !== false ) {
		return 'IE';
	}
	return 'Other';
}

/**
 * Detect device type from User Agent.
 *
 * @param string|null $ua User Agent string.
 * @return string Device type (Desktop, Mobile, Tablet).
 */
function StorePulse_get_device( $ua = null ) {
	$ua = ! empty( $ua ) ? $ua : ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' );
	if ( empty( $ua ) ) {
		return 'Desktop';
	}

	if ( preg_match( '/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua ) ) {
		return 'Tablet';
	}
	if ( preg_match( '/(Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini)/i', $ua ) ) {
		return 'Mobile';
	}
	return 'Desktop';
}

/**
 * Render the unified Pulse Analytics header and navigation.
 */
function StorePulse_render_admin_header() {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$version      = '1.0.1'; // Ideally fetched from a central place.

	// Define main menu items with SVG Coordinate icons matching Audie Data Migrator style.
	$nav_items = array(
		'ga-settings'                    => array(
			'label' => 'Dashboard',
			'svg'   => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
		),
		'wp-seo-insights'                => array(
			'label' => 'Settings',
			'svg'   => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
		),
		'StorePulse_goals'                 => array(
			'label' => 'Goals',
			'svg'   => '<circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>',
		),
		'StorePulse-logs'                  => array(
			'label' => 'Logs',
			'svg'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
		),
		'StorePulse-realtime'              => array(
			'label' => 'Real-time',
			'svg'   => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
		),
		'StorePulse-sales-summary'         => array(
			'label' => 'Sales',
			'svg'   => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
		),
		'StorePulse-traffic-overview'      => array(
			'label' => 'Traffic',
			'svg'   => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>',
		),
		'StorePulse-ecommerce-overview'    => array(
			'label' => 'eCommerce',
			'svg'   => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
		),
		'StorePulse-product-performance'   => array(
			'label' => 'Products',
			'svg'   => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path>',
		),
		'StorePulse-social-media-tracking' => array(
			'label' => 'Social',
			'svg'   => '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>',
		),
		'StorePulse-campaign-url-tracking' => array(
			'label' => 'Campaigns',
			'svg'   => '<path d="M3 11l18-5v12L3 14v-3z"></path><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>',
		),
		'StorePulse-link-report'           => array(
			'label' => 'Links',
			'svg'   => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
		),
		'StorePulse-core-web-vitals'       => array(
			'label' => 'WebVitals',
			'svg'   => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
		),
		'StorePulse-user-journey'          => array(
			'label' => 'User Journey',
			'svg'   => '<polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line>',
		),
	);

	?>
	<div class="container w2ssyn-layout-wrapper" style="font-family: var(--w2ssyn-sb-font) !important;">
		<div id="smackws-core-tabs" class="smackws-tabs w2ssyn-sidebar">
			<div class="w2ssyn-sidebar-brand">
				<div class="w2ssyn-logo-box">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
					</svg>
				</div>
				<div class="w2ssyn-brand-text">
					<span class="w2ssyn-brand-title">WooPulse</span>
					<span class="w2ssyn-brand-subtitle">Analytics</span>
				</div>
			</div>

			<nav class="w2ssyn-sidebar-nav" style="display: flex; flex-direction: column; width: 100%;">
				<?php
				foreach ( $nav_items as $slug => $item ) :
					$isActive = ( $current_page === $slug );
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="smack-tab <?php echo $isActive ? 'active' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;">
							<?php echo $item['svg']; // SVGs are hardcoded above and safe to render. ?>
						</svg>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div id="smackws-cores-tabcontent" class="w2ssyn-main-layout">
			<div class="smack-tab-pane">
				<?php
				if ( 'wp-seo-insights' === $current_page ) :
					$wizard_steps = array(
						'help'          => 'Help & Guidance',
						'general'       => 'General Settings',
						'analytics'     => 'Analytics Configuration',
						'events'        => 'Events & Goals',
						'advanced'      => 'Advanced Settings',
						'authenticated' => 'Authorized Connection',
					);
					$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'help';
					?>
					<div style="margin-bottom: 24px; margin-top: 10px;">
						<h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; line-height: 1.25;">Settings</h2>
						<p style="font-size: 0.875rem; color: #64748b; margin: 0;">Configure Google Analytics integration, events, and advanced tracking options.</p>
					</div>
					<div style="border-bottom: 1px solid #e2e8f0; margin-bottom: 24px;">
						<div class="w2ssyn-subtabs-container" style="display: flex; gap: 24px; margin-bottom: -1px;">
							<?php
							foreach ( $wizard_steps as $step_key => $step_label ) :
								$is_active_step = ( $current_step === $step_key );
								?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-seo-insights&step=' . $step_key ) ); ?>"
									class="w2ssyn-subtab <?php echo $is_active_step ? 'active' : ''; ?>"
									style="text-decoration: none; font-size: 13px; font-weight: 600; padding-bottom: 12px; color: <?php echo $is_active_step ? '#2f3cb0' : '#64748b'; ?>; border-bottom: <?php echo $is_active_step ? '2px solid #2f3cb0' : '2px solid transparent'; ?>; transition: all 0.2s; outline: none !important; box-shadow: none !important;">
									<?php echo esc_html( $step_label ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( 'ga-settings' === $current_page ) : 
					$StorePulse_ts_end   = current_time( 'timestamp' );
					$StorePulse_ts_start = strtotime( '-30 days', $StorePulse_ts_end );

					if ( wp_date( 'Y', $StorePulse_ts_start ) === wp_date( 'Y', $StorePulse_ts_end ) ) {
						$StorePulse_default_range = wp_date( 'M j', $StorePulse_ts_start ) . ' – ' . wp_date( 'M j, Y', $StorePulse_ts_end );
					} else {
						$StorePulse_default_range = wp_date( 'M j, Y', $StorePulse_ts_start ) . ' – ' . wp_date( 'M j, Y', $StorePulse_ts_end );
					}
				?>
				<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
					<div id="dateRangePickerWrap"
						class="relative rounded-xl border border-gray-200 bg-white flex items-center h-[34px] px-3.5 cursor-pointer hover:bg-gray-50 transition-all duration-200"
						style="box-shadow: 0 1px 2px rgba(0,0,0,0.02); width: 245px; max-width: 245px;">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2.5"
							style="flex: 0 0 14px; margin-right: 8px;">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
						<input type="text" id="dateRangeInput"
							class="p-0 text-[13px] font-medium text-[#1e293b] cursor-pointer text-center flex-grow"
							value="<?php echo esc_attr( $StorePulse_default_range ); ?>" placeholder="Select date range" readonly
							style="border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important; margin: 0 !important; height: 100%; font-family: var(--w2ssyn-sb-font) !important; width: 0; min-width: 0; flex-grow: 1;">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
							stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ml-1.5" style="margin-left: 8px;">
							<polyline points="6 9 12 15 18 9"></polyline>
						</svg>
					</div>
				</div>
				<?php endif; ?>

	<?php
	// Ensure the wrapper gets closed in the footer
	if ( ! has_action( 'in_admin_footer', 'StorePulse_render_admin_footer_tags' ) ) {
		add_action( 'in_admin_footer', 'StorePulse_render_admin_footer_tags' );
	}
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
}

function StorePulse_render_admin_footer_tags() {
	$screen = get_current_screen();
	if ( $screen && ( strpos( $screen->id, 'storepulse' ) !== false || strpos( $screen->id, 'wp-seo-insights' ) !== false || strpos( $screen->id, 'ga-settings' ) !== false ) ) {
		echo '</div></div></div><!-- .w2ssyn-main-layout -->';
	}
}
/**
 * Get the current visitor session ID.
 * Refactored to match session-tracker.php logic.
 *
 * @return string Session ID.
 */
function StorePulse_get_session_id() {
	if ( isset( $_COOKIE['StorePulse_sid'] ) ) {
		return sanitize_text_field( wp_unslash( $_COOKIE['StorePulse_sid'] ) );
	}

	$user_id = get_current_user_id();
	if ( $user_id > 0 ) {
		$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
	} else {
		$session_id = wp_generate_uuid4();
	}

	setcookie( 'StorePulse_sid', $session_id, time() + ( 86400 ), '/', '', is_ssl(), true );
	$_COOKIE['StorePulse_sid'] = $session_id; // Update superglobal for current request
	return $session_id;
}

/**
 * Get the current visitor Anonymous ID.
 * Refactored to match session-tracker.php logic.
 *
 * @return string Anonymous ID.
 */
function StorePulse_get_anon_id() {
	if ( isset( $_COOKIE['_store_tracker'] ) ) {
		return sanitize_text_field( wp_unslash( $_COOKIE['_store_tracker'] ) );
	}

	$anon_id = wp_generate_uuid4();
	setcookie( '_store_tracker', $anon_id, time() + ( 86400 * 365 ), '/', '', is_ssl(), true ); // 1 year
	$_COOKIE['_store_tracker'] = $anon_id;
	return $anon_id;
}

/**
 * Get visitor IP address robustly.
 * Handle proxies and forced IPs.
 *
 * @return string IP address.
 */
function StorePulse_get_visitor_ip() {
    // phpcs:disable 	WordPress.Security.NonceVerification.Recommended
	// Optional: Force IP for local/testing only. Set StorePulse_FORCE_IP in wp-config.php to enable.
	if ( defined( 'StorePulse_FORCE_IP' ) && StorePulse_FORCE_IP ) {
		return StorePulse_FORCE_IP;
	}

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	}

	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// X-Forwarded-For can contain multiple IPs, the first one is usually the client.
        $ips = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])); // phpcs:ignore 
		return sanitize_text_field( trim( $ips[0] ) );
	}

	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/**
 * Generate a deterministic ID for session storage and lookup.
 * This fulfills the "same IP = same record" requirement.
 *
 * @return string Deterministic ID.
 */
function StorePulse_get_deterministic_id() {
	$ip = StorePulse_get_visitor_ip();
	return 'ip_' . md5( $ip );
}
