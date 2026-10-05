<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GA_Reporter {

	/* ================= DATE HELPER ================= */
	private static function get_date_range( $start = null, $end = null ) {
		$startDate = $start ?: '7daysAgo';
		$endDate   = $end ?: 'yesterday';

		return array(
			'startDate' => $startDate,
			'endDate'   => $endDate,
		);
	}

	/* ================= GENERIC REPORT CALL ================= */
	/**
	 * Run a custom report against Google Analytics Data API.
	 *
	/**
	 * Get configured GA4 Property ID from all possible storage locations.
	 *
	 * @return string
	 */
	public static function get_property_id() {
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) ) {
			return \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::get_property_id();
		}
		return '';
	}

	/**
	 * Run custom report against Google Analytics 4 Data API.
	 *
	 * @param array $body Report request body with dimensions, metrics, dateRanges, etc.
	 * @return array GA API response data or error array on failure.
	 */
	public static function run_custom_report( $body ) {
		$access_token = GA_Auth::get_access_token();
		$property_id  = self::get_property_id();

		if ( is_array( $access_token ) && isset( $access_token['error'] ) ) {
			return $access_token;
		}

		if ( empty( $access_token ) || empty( $property_id ) ) {
			return array( 'error' => 'Missing access token or property ID. Please check settings.' );
		}

		if ( isset( $body['dimensions'] ) && is_array( $body['dimensions'] ) ) {
			$body['dimensions'] = array_values(
				array_filter(
					$body['dimensions'],
					function( $d ) {
						return is_array( $d ) && ! empty( $d['name'] );
					}
				)
			);
			if ( empty( $body['dimensions'] ) ) {
				unset( $body['dimensions'] );
			}
		}

		if ( isset( $body['metrics'] ) && is_array( $body['metrics'] ) ) {
			$body['metrics'] = array_values(
				array_filter(
					$body['metrics'],
					function( $m ) {
						return is_array( $m ) && ! empty( $m['name'] );
					}
				)
			);
		}

		$url = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error'   => true,
				'code'    => 'ga_api_error',
				'message' => $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) ) {
			return array(
				'error'   => true,
				'code'    => 'ga_error',
				'message' => $data['error']['message'] ?? 'Unknown GA API error',
			);
		}

		return $data;
	}

	public static function run_realtime_report( $body ) {
		$access_token = GA_Auth::get_access_token();
		$property_id  = self::get_property_id();

		if ( is_array( $access_token ) && isset( $access_token['error'] ) ) {
			return $access_token;
		}
		if ( empty( $access_token ) || empty( $property_id ) ) {
			return array( 'error' => 'Missing config' );
		}

		$url = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runRealtimeReport";

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $decoded['error'] ) ) {
			if ( isset( $decoded['error']['code'] ) && 401 == $decoded['error']['code'] ) {
				$new_token = GA_Auth::refresh_access_token();
				if ( $new_token ) {
					$retry_response = wp_remote_post(
						$url,
						array(
							'timeout' => 30,
							'headers' => array(
								'Authorization' => 'Bearer ' . $new_token,
								'Content-Type'  => 'application/json',
							),
							'body'    => json_encode( $body ),
						)
					);
					if ( ! is_wp_error( $retry_response ) ) {
						return json_decode( wp_remote_retrieve_body( $retry_response ), true );
					}
				}
			}
		}

		return $decoded;
	}

	/* ================= FETCH FUNCTIONS ================= */

	public static function fetch_daily_metrics( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'date' ) ),
				'metrics'    => array(
					array( 'name' => 'sessions' ),
					array( 'name' => 'totalUsers' ),
					array( 'name' => 'newUsers' ),
					array( 'name' => 'screenPageViews' ),
				),
				'orderBys'   => array(
					array(
						'dimension' => array( 'dimensionName' => 'date' ),
						'desc'      => false,
					),
				),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_daily_ecommerce_metrics( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'date' ) ),
				'metrics'    => array(
					array( 'name' => 'purchaseRevenue' ),
					array( 'name' => 'transactions' ),
				),
				'orderBys'   => array(
					array(
						'dimension' => array( 'dimensionName' => 'date' ),
						'desc'      => false,
					),
				),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_visitor_types( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'newVsReturning' ) ),
				'metrics'    => array( array( 'name' => 'totalUsers' ) ),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_device_categories( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'deviceCategory' ) ),
				'metrics'    => array( array( 'name' => 'totalUsers' ) ),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_browsers( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'browser' ) ),
				'metrics'    => array( array( 'name' => 'totalUsers' ) ),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_top_pages( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'pagePath' ) ),
				'metrics'    => array(
					array( 'name' => 'screenPageViews' ),
					array( 'name' => 'engagedSessions' ),
					array( 'name' => 'newUsers' ),
					array( 'name' => 'bounceRate' ),
				),
				'dateRanges' => array( $range ),
				'limit'      => 10,
			)
		);
	}

	public static function fetch_top_countries( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'country' ) ),
				'metrics'    => array( array( 'name' => 'totalUsers' ) ),
				'dateRanges' => array( $range ),
				'limit'      => 10,
			)
		);
	}

	public static function fetch_source_medium( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'sourceMedium' ) ),
				'metrics'    => array( array( 'name' => 'sessions' ) ),
				'dateRanges' => array( $range ),
				'limit'      => 10,
			)
		);
	}

	public static function fetch_daily_referral_metrics( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions'      => array(
					array( 'name' => 'date' ),
					array( 'name' => 'sessionSource' ),
				),
				'metrics'         => array(
					array( 'name' => 'sessions' ),
					array( 'name' => 'totalUsers' ),
				),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'sessionMedium',
						'stringFilter' => array(
							'matchType' => 'EXACT',
							'value'     => 'referral',
						),
					),
				),
				'orderBys'        => array(
					array(
						'dimension' => array( 'dimensionName' => 'date' ),
						'desc'      => false,
					),
				),
				'dateRanges'      => array( $range ),
			)
		);
	}

	public static function fetch_daily_engagement_metrics( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'date' ) ),
				'metrics'    => array(
					array( 'name' => 'engagedSessions' ),
					array( 'name' => 'engagementRate' ),
					array( 'name' => 'bounceRate' ),
				),
				'orderBys'   => array(
					array(
						'dimension' => array( 'dimensionName' => 'date' ),
						'desc'      => false,
					),
				),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_top_referrals( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions'      => array( array( 'name' => 'sessionSource' ) ),
				'metrics'         => array(
					array( 'name' => 'sessions' ),
					array( 'name' => 'engagedSessions' ),
				),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'sessionMedium',
						'stringFilter' => array(
							'matchType' => 'EXACT',
							'value'     => 'referral',
						),
					),
				),
				'orderBys'        => array(
					array(
						'metric' => array( 'metricName' => 'sessions' ),
						'desc'   => true,
					),
				),
				'dateRanges'      => array( $range ),
				'limit'           => 10,
			)
		);
	}


	public static function fetch_funnel_report( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'metrics'    => array(
					array( 'name' => 'eventCount' ),
				),
				'dimensions' => array( array( 'name' => 'eventName' ) ),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_kpi_metrics( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'metrics'    => array(
					array( 'name' => 'sessions' ),
					array( 'name' => 'purchaseRevenue' ),
					array( 'name' => 'sessionConversionRate' ),
					array( 'name' => 'ecommercePurchases' ),
					array( 'name' => 'screenPageViews' ),
					array( 'name' => 'averageSessionDuration' ),
					array( 'name' => 'engagementRate' ),
					array( 'name' => 'totalUsers' ),
					array( 'name' => 'newUsers' ),
					array( 'name' => 'activeUsers' ),
				),
				'dateRanges' => array( $range ),
			)
		);
	}

	public static function fetch_top_products( $startDate = null, $endDate = null ) {
		$range = self::get_date_range( $startDate, $endDate );
		return self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'itemName' ) ),
				'metrics'    => array(
					array( 'name' => 'itemsPurchased' ),
					array( 'name' => 'itemRevenue' ),
				),
				'dateRanges' => array( $range ),
				'limit'      => 10,
			)
		);
	}

	private static function get_previous_date_range( $start = null, $end = null ) {
		$start_str = $start ?: '30daysAgo';
		$end_str   = $end ?: 'yesterday';

		$start_ts = strtotime( $start_str );
		$end_ts   = strtotime( $end_str );

		if ( ! $start_ts || ! $end_ts ) {
			$start_ts = strtotime( '-30 days' );
			$end_ts   = strtotime( '-1 day' );
		}

		if ( $start_ts > $end_ts ) {
			$tmp      = $start_ts;
			$start_ts = $end_ts;
			$end_ts   = $tmp;
		}

		$days_diff     = max( 1, (int) round( ( $end_ts - $start_ts ) / 86400 ) + 1 );
		$prev_end_ts   = $start_ts - 86400;
		$prev_start_ts = $prev_end_ts - ( ( $days_diff - 1 ) * 86400 );

		return array(
			'startDate' => gmdate( 'Y-m-d', $prev_start_ts ),
			'endDate'   => gmdate( 'Y-m-d', $prev_end_ts ),
		);
	}

	/* ================= REST ROUTES & PERMISSIONS ================= */

	public static function rest_permission_check_read() {
		if ( ! current_user_can( 'sm_pulse_analytics_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view Pulse Analytics reports.', 'smackcoders-pulse-analytics-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	public static function rest_permission_check_write() {
		if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to modify Pulse Analytics settings.', 'smackcoders-pulse-analytics-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	public static function register_dual_route( $route, $args ) {
		register_rest_route( 'pulse-analytics/v1', $route, $args );
		register_rest_route( 'sm-pulse-analytics/v1', $route, $args );
	}

	public static function register_api_routes() {
		// READ (GET) routes — Free plugin ships only fully unlocked local endpoints (WP.org Guideline 5).
		self::register_dual_route( '/daily-metrics', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_daily_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/visitor-types', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_visitor_types' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/devices', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_device_categories' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/browsers', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_browsers' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/top-pages', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_top_pages' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/top-countries', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_top_countries' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/source-medium', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_source_medium' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/daily-referrals', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_daily_referral_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/daily-engagement', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_daily_engagement_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/daily-ecommerce', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_daily_ecommerce_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/top-referrals', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_top_referrals' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/funnel', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_funnel_report' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/realtime', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_realtime_visitors' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/kpi-metrics', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_kpi_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/dashboard/free', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_free_dashboard' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/dashboard/pro', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_pro_dashboard' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/wc-metrics', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_wc_metrics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/reports/compare', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_get_reports_compare' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/link-click', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_link_click' ), 'permission_callback' => '__return_true' ) );
		self::register_dual_route( '/form-event', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_form_event' ), 'permission_callback' => '__return_true' ) );
		self::register_dual_route( '/page-engagement', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_page_engagement' ), 'permission_callback' => '__return_true' ) );
		self::register_dual_route( '/session-ping', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_session_ping' ), 'permission_callback' => '__return_true' ) );
		self::register_dual_route( '/reports/link-clicks/free', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_link_clicks_report_free' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/reports/demographics', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_demographics_report' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/reports/forms', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_forms_report' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );

		self::register_dual_route( '/integration/woocommerce', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_integration_woocommerce' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/integration/commerce', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_integration_commerce' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/integration/google-analytics', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_integration_google_analytics' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/reports', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_get_reports' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/reports/(?P<id>[a-zA-Z0-9_-]+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_get_report_detail' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ), 'args' => array( 'id' => array( 'required' => true, 'type' => 'string' ) ) ) );

		// WRITE (POST) routes
		self::register_dual_route( '/settings', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'handle_get_settings' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_read' ) ) );
		self::register_dual_route( '/settings', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_post_settings' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_write' ) ) );
		self::register_dual_route( '/reports/custom', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'handle_post_custom_report' ), 'permission_callback' => array( __CLASS__, 'rest_permission_check_write' ) ) );
	}

	/* ================= REST HANDLERS ================= */

	/**
	 * Public REST endpoint for active page engagement duration tracking (sendBeacon / unload).
	 */
	public static function handle_page_engagement( \WP_REST_Request $request ) {
		$params           = $request->get_json_params();
		$session_id       = '';
		$visitor_id       = '';
		$page_path        = '';
		$duration_seconds = 0;

		if ( is_array( $params ) ) {
			$session_id       = sanitize_text_field( $params['session_id'] ?? '' );
			$visitor_id       = sanitize_text_field( $params['visitor_id'] ?? '' );
			$page_path        = sanitize_text_field( $params['page_path'] ?? '' );
			$duration_seconds = max( 0, (int) ( $params['duration_seconds'] ?? 0 ) );
		}

		if ( empty( $session_id ) || empty( $page_path ) || $duration_seconds <= 0 ) {
			return rest_ensure_response( array( 'success' => true, 'updated' => false ) );
		}

		$inserted = PulseAnalytics_Storage::insert_event(
			array(
				'event_type'  => 'page_engagement',
				'session_key' => $session_id,
				'event_data'  => array(
					'page_path'        => $page_path,
					'duration_seconds' => $duration_seconds,
					'visitor_id'       => $visitor_id,
					'session_id'       => $session_id,
				),
				'source'      => 'journey_engagement',
			)
		);

		return rest_ensure_response( array( 'success' => true, 'updated' => (bool) $inserted ) );
	}

	/**
	 * Public REST endpoint for session ping / heartbeat.
	 */
	public static function handle_session_ping( \WP_REST_Request $request ) {
		return rest_ensure_response( array( 'success' => true, 'ping' => 'pong' ) );
	}

	public static function handle_daily_metrics( \WP_REST_Request $request ) {
		$startDate = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$report = self::fetch_daily_metrics( $startDate, $endDate );

		$current = strtotime( $startDate );
		$last    = strtotime( $endDate );

		if ( $current > $last ) {
			$last = $current;
		}

		$all_dates = array();
		while ( $current <= $last ) {
			$all_dates[ gmdate( 'Y-m-d', $current ) ] = array(
				'sessions'   => 0,
				'totalUsers' => 0,
				'newUsers'   => 0,
				'pageviews'  => 0,
			);
			$current = strtotime( '+1 day', $current );
		}

		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$ga_date_str = $row['dimensionValues'][0]['value'];
				$parsed_date = gmdate( 'Y-m-d', strtotime( $ga_date_str ) );

				if ( isset( $all_dates[ $parsed_date ] ) ) {
					$raw_total = (int) ( $row['metricValues'][1]['value'] ?? 0 );
					$raw_new   = (int) ( $row['metricValues'][2]['value'] ?? 0 );
					$all_dates[ $parsed_date ] = array(
						'sessions'   => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
						'totalUsers' => max( $raw_total, $raw_new ),
						'newUsers'   => $raw_new,
						'pageviews'  => (int) ( $row['metricValues'][3]['value'] ?? 0 ),
					);
				}
			}
		}

		$dates      = array();
		$dates_ymd  = array();
		$sessions   = array();
		$totalUsers = array();
		$newUsers   = array();
		$pageviews  = array();

		foreach ( $all_dates as $date => $vals ) {
			$dates[]      = gmdate( 'M j', strtotime( $date ) );
			$dates_ymd[]  = $date;
			$sessions[]   = $vals['sessions'];
			$totalUsers[] = $vals['totalUsers'];
			$newUsers[]   = $vals['newUsers'];
			$pageviews[]  = $vals['pageviews'];
		}

		return array(
			'labels'     => $dates,
			'dates_ymd'  => $dates_ymd,
			'sessions'   => $sessions,
			'totalUsers' => $totalUsers,
			'newUsers'   => $newUsers,
			'pageviews'  => $pageviews,
			'data'       => $sessions,
		);
	}

	public static function handle_visitor_types( \WP_REST_Request $request ) {
		$start  = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end    = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' );
		$report = self::fetch_visitor_types( $start, $end );

		$types = array(
			'New'       => 0,
			'Returning' => 0,
		);
		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$type = ucfirst( strtolower( $row['dimensionValues'][0]['value'] ?? '' ) );
				if ( isset( $types[ $type ] ) ) {
					$types[ $type ] = (int) $row['metricValues'][0]['value'];
				}
			}
		}

		return array(
			'labels' => array( 'New', 'Returning' ),
			'data'   => array( (int) $types['New'], (int) $types['Returning'] ),
		);
	}

	public static function handle_device_categories( \WP_REST_Request $request ) {
		$start     = $request->get_param( 'startDate' );
		$end       = $request->get_param( 'endDate' );
		$ga_report = self::format_simple_report( self::fetch_device_categories( $start, $end ) );
		return is_wp_error( $ga_report ) ? array(
			'labels' => array(),
			'data'   => array(),
		) : $ga_report;
	}

	public static function handle_browsers( \WP_REST_Request $request ) {
		$start     = $request->get_param( 'startDate' );
		$end       = $request->get_param( 'endDate' );
		$ga_report = self::format_simple_report( self::fetch_browsers( $start, $end ) );
		return is_wp_error( $ga_report ) ? array(
			'labels' => array(),
			'data'   => array(),
		) : $ga_report;
	}

	public static function handle_top_pages( \WP_REST_Request $request ) {
		$start  = $request->get_param( 'startDate' );
		$end    = $request->get_param( 'endDate' );
		$report = self::fetch_top_pages( $start, $end );
		$pages  = array();
		foreach ( $report['rows'] ?? array() as $row ) {
			$pages[] = array(
				'page'            => $row['dimensionValues'][0]['value'],
				'pageViews'       => $row['metricValues'][0]['value'],
				'engagedSessions' => $row['metricValues'][1]['value'],
				'newUsers'        => $row['metricValues'][2]['value'],
				'bounceRate'      => $row['metricValues'][3]['value'],
			);
		}

		return $pages;
	}

	public static function handle_top_countries( \WP_REST_Request $request ) {
		$start     = $request->get_param( 'startDate' );
		$end       = $request->get_param( 'endDate' );
		$report    = self::fetch_top_countries( $start, $end );
		$countries = array();
		foreach ( $report['rows'] ?? array() as $row ) {
			$countries[] = array(
				'country'  => $row['dimensionValues'][0]['value'],
				'visitors' => $row['metricValues'][0]['value'],
			);
		}

		return $countries;
	}

	public static function handle_source_medium( \WP_REST_Request $request ) {
		$start  = $request->get_param( 'startDate' );
		$end    = $request->get_param( 'endDate' );
		$report = self::fetch_source_medium( $start, $end );

		$results = array();
		foreach ( $report['rows'] ?? array() as $row ) {
			$results[] = array(
				'sourceMedium' => $row['dimensionValues'][0]['value'],
				'sessions'     => $row['metricValues'][0]['value'],
			);
		}

		return $results;
	}

	public static function handle_daily_referral_metrics( \WP_REST_Request $request ) {
		$startDate = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$report = self::fetch_daily_referral_metrics( $startDate, $endDate );

		$current = strtotime( $startDate );
		$last    = strtotime( $endDate );

		if ( $current > $last ) {
			$last = $current;
		}

		$date_keys = array();
		while ( $current <= $last ) {
			$date_keys[] = gmdate( 'Y-m-d', $current );
			$current     = strtotime( '+1 day', $current );
		}

		$source_totals = array();
		$raw_data      = array();

		foreach ( $date_keys as $dk ) {
			$raw_data[ $dk ] = array(
				'sessions' => 0,
				'users'    => 0,
				'sources'  => array(),
			);
		}

		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$ga_date_str   = $row['dimensionValues'][0]['value'] ?? '';
				$source_domain = $row['dimensionValues'][1]['value'] ?? '(unknown)';
				$parsed_date   = gmdate( 'Y-m-d', strtotime( $ga_date_str ) );
				$sessions_cnt  = (int) ( $row['metricValues'][0]['value'] ?? 0 );
				$users_cnt     = (int) ( $row['metricValues'][1]['value'] ?? 0 );

				if ( isset( $raw_data[ $parsed_date ] ) ) {
					$raw_data[ $parsed_date ]['sessions'] += $sessions_cnt;
					$raw_data[ $parsed_date ]['users']    += $users_cnt;

					if ( ! isset( $raw_data[ $parsed_date ]['sources'][ $source_domain ] ) ) {
						$raw_data[ $parsed_date ]['sources'][ $source_domain ] = 0;
					}
					$raw_data[ $parsed_date ]['sources'][ $source_domain ] += $sessions_cnt;

					if ( ! isset( $source_totals[ $source_domain ] ) ) {
						$source_totals[ $source_domain ] = 0;
					}
					$source_totals[ $source_domain ] += $sessions_cnt;
				}
			}
		}

		arsort( $source_totals );
		$top_5_sources = array_slice( array_keys( $source_totals ), 0, 5 );

		$dates             = array();
		$dates_ymd         = array();
		$referral_sessions = array();
		$referral_users    = array();
		$top_5_series      = array();

		foreach ( $top_5_sources as $src ) {
			$top_5_series[ $src ] = array();
		}

		foreach ( $date_keys as $dk ) {
			$dates[]             = gmdate( 'M j', strtotime( $dk ) );
			$dates_ymd[]         = $dk;
			$referral_sessions[] = $raw_data[ $dk ]['sessions'];
			$referral_users[]    = $raw_data[ $dk ]['users'];

			foreach ( $top_5_sources as $src ) {
				$top_5_series[ $src ][] = isset( $raw_data[ $dk ]['sources'][ $src ] ) ? $raw_data[ $dk ]['sources'][ $src ] : 0;
			}
		}

		return array(
			'labels'            => $dates,
			'dates_ymd'         => $dates_ymd,
			'referral_sessions' => $referral_sessions,
			'referral_users'    => $referral_users,
			'top_5_sources'     => $top_5_sources,
			'top_5_series'      => $top_5_series,
			// Backwards compatibility keys
			'top_sources'       => $top_5_sources,
			'series'            => array_merge( $top_5_series, array( 'total' => $referral_sessions ) ),
		);
	}

	public static function handle_daily_engagement_metrics( \WP_REST_Request $request ) {
		$startDate = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$report = self::fetch_daily_engagement_metrics( $startDate, $endDate );

		$current = strtotime( $startDate );
		$last    = strtotime( $endDate );

		if ( $current > $last ) {
			$last = $current;
		}

		$all_dates = array();
		while ( $current <= $last ) {
			$all_dates[ gmdate( 'Y-m-d', $current ) ] = array(
				'engagedSessions' => 0,
				'engagementRate'  => 0.0,
				'bounceRate'      => 0.0,
			);
			$current = strtotime( '+1 day', $current );
		}

		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$ga_date_str = $row['dimensionValues'][0]['value'];
				$parsed_date = gmdate( 'Y-m-d', strtotime( $ga_date_str ) );

				if ( isset( $all_dates[ $parsed_date ] ) ) {
					$all_dates[ $parsed_date ] = array(
						'engagedSessions' => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
						'engagementRate'  => round( (float) ( $row['metricValues'][1]['value'] ?? 0 ) * 100, 2 ),
						'bounceRate'      => round( (float) ( $row['metricValues'][2]['value'] ?? 0 ) * 100, 2 ),
					);
				}
			}
		}

		$dates           = array();
		$dates_ymd       = array();
		$engagedSessions = array();
		$engagementRate  = array();
		$bounceRate      = array();

		foreach ( $all_dates as $date => $vals ) {
			$dates[]           = gmdate( 'M j', strtotime( $date ) );
			$dates_ymd[]       = $date;
			$engagedSessions[] = $vals['engagedSessions'];
			$engagementRate[]  = $vals['engagementRate'];
			$bounceRate[]      = $vals['bounceRate'];
		}

		return array(
			'labels'          => $dates,
			'dates_ymd'       => $dates_ymd,
			'engagedSessions' => $engagedSessions,
			'engagementRate'  => $engagementRate,
			'bounceRate'      => $bounceRate,
		);
	}

	public static function handle_daily_ecommerce_metrics( \WP_REST_Request $request ) {
		$startDate = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$report = self::fetch_daily_ecommerce_metrics( $startDate, $endDate );

		$current = strtotime( $startDate );
		$last    = strtotime( $endDate );

		if ( $current > $last ) {
			$last = $current;
		}

		$all_dates = array();
		while ( $current <= $last ) {
			$all_dates[ gmdate( 'Y-m-d', $current ) ] = array(
				'revenue' => 0.0,
				'orders'  => 0,
			);
			$current = strtotime( '+1 day', $current );
		}

		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$ga_date_str = $row['dimensionValues'][0]['value'];
				$parsed_date = gmdate( 'Y-m-d', strtotime( $ga_date_str ) );

				if ( isset( $all_dates[ $parsed_date ] ) ) {
					$all_dates[ $parsed_date ] = array(
						'revenue' => (float) ( $row['metricValues'][0]['value'] ?? 0.0 ),
						'orders'  => (int) ( $row['metricValues'][1]['value'] ?? 0 ),
					);
				}
			}
		}

		$dates   = array();
		$dates_ymd = array();
		$revenue = array();
		$orders  = array();
		$aov     = array();

		foreach ( $all_dates as $date => $vals ) {
			$dates[]     = gmdate( 'M j', strtotime( $date ) );
			$dates_ymd[] = $date;
			$rev_val     = round( $vals['revenue'], 2 );
			$ord_val     = $vals['orders'];
			$aov_val     = $ord_val > 0 ? round( $rev_val / $ord_val, 2 ) : 0.0;

			$revenue[] = $rev_val;
			$orders[]  = $ord_val;
			$aov[]     = $aov_val;
		}

		return array(
			'labels'    => $dates,
			'dates_ymd' => $dates_ymd,
			'revenue'   => $revenue,
			'orders'    => $orders,
			'aov'       => $aov,
		);
	}

	public static function handle_top_referrals( \WP_REST_Request $request ) {
		$start  = $request->get_param( 'startDate' );
		$end    = $request->get_param( 'endDate' );
		$report = self::fetch_top_referrals( $start, $end );

		$results = array();
		if ( ! isset( $report['error'] ) && ! empty( $report['rows'] ) ) {
			foreach ( $report['rows'] as $row ) {
				$results[] = array(
					'referrer' => $row['dimensionValues'][0]['value'] ?? '(unknown)',
					'sessions' => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
					'engaged'  => (int) ( $row['metricValues'][1]['value'] ?? 0 ),
				);
			}
		}

		return $results;
	}

	public static function handle_funnel_report( \WP_REST_Request $request ) {
		$start_date = $request->get_param( 'startDate' ) ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date   = $request->get_param( 'endDate' ) ?? gmdate( 'Y-m-d' );
		$report     = self::fetch_funnel_report( $start_date, $end_date );

		$views    = 0;
		$cart     = 0;
		$checkout = 0;
		$purchase = 0;

		foreach ( $report['rows'] ?? array() as $row ) {
			$event_name = $row['dimensionValues'][0]['value'] ?? '';
			$count      = (int) ( $row['metricValues'][0]['value'] ?? 0 );
			if ( 'page_view' === $event_name || 'view_item' === $event_name ) {
				$views += $count;
			} elseif ( 'add_to_cart' === $event_name ) {
				$cart += $count;
			} elseif ( 'begin_checkout' === $event_name ) {
				$checkout += $count;
			} elseif ( 'purchase' === $event_name || 'ecommerce_purchase' === $event_name ) {
				$purchase += $count;
			}
		}

		return array(
			'views'    => $views,
			'cart'     => $cart,
			'checkout' => $checkout,
			'purchase' => $purchase,
		);
	}

	public static function handle_kpi_metrics( \WP_REST_Request $request = null ) {
		$startDate = $request ? $request->get_param( 'startDate' ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request ? $request->get_param( 'endDate' ) : gmdate( 'Y-m-d' );
		$report    = self::fetch_kpi_metrics( $startDate, $endDate );

		$metrics = array(
			'sessions'             => 0,
			'revenue'              => 0.0,
			'conversion_rate'      => 0.0,
			'transactions'         => 0,
			'pageviews'            => 0,
			'avg_session_duration' => 0.0,
			'engagement_rate'      => 0.0,
			'total_users'          => 0,
			'new_users'            => 0,
			'active_users'         => 0,
		);

		if ( ! isset( $report['error'] ) && isset( $report['rows'][0]['metricValues'] ) ) {
			$values                          = $report['rows'][0]['metricValues'];
			$metrics['sessions']             = (int) ( $values[0]['value'] ?? 0 );
			$metrics['revenue']              = (float) ( $values[1]['value'] ?? 0 );
			$metrics['conversion_rate']      = (float) ( $values[2]['value'] ?? 0 );
			$metrics['transactions']         = (int) ( $values[3]['value'] ?? 0 );
			$metrics['pageviews']            = (int) ( $values[4]['value'] ?? 0 );
			$metrics['avg_session_duration'] = (float) ( $values[5]['value'] ?? 0 );
			$metrics['engagement_rate']      = (float) ( $values[6]['value'] ?? 0 );
			$metrics['total_users']          = (int) ( $values[7]['value'] ?? 0 );
			$metrics['new_users']            = (int) ( $values[8]['value'] ?? 0 );
			$metrics['active_users']         = (int) ( $values[9]['value'] ?? 0 );
		}

		return $metrics;
	}

	public static function handle_wc_metrics( \WP_REST_Request $request = null ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'error' => 'WooCommerce not active' );
		}

		$startDate = $request ? $request->get_param( 'startDate' ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request ? $request->get_param( 'endDate' ) : gmdate( 'Y-m-d' );
		$full      = $request ? $request->get_param( 'full' ) === 'true' : false;

		$args   = array(
			'status'       => array( 'completed', 'processing', 'on-hold' ),
			'date_created' => $startDate . '...' . $endDate,
			'limit'        => -1,
		);
		$orders = wc_get_orders( $args );

		$total_revenue = 0;
		$total_orders  = count( $orders );
		$product_sales = array();

		foreach ( $orders as $order ) {
			$total_revenue += (float) $order->get_total();
			foreach ( $order->get_items() as $item ) {
				$name  = $item->get_name();
				$qty   = $item->get_quantity();
				$total = (float) $item->get_total();
				if ( ! isset( $product_sales[ $name ] ) ) {
					$product_sales[ $name ] = array(
						'qty'     => 0,
						'revenue' => 0,
					);
				}
				$product_sales[ $name ]['qty']     += $qty;
				$product_sales[ $name ]['revenue'] += $total;
			}
		}

		uasort(
			$product_sales,
			function ( $a, $b ) {
				if ( $a['revenue'] == $b['revenue'] ) {
					return 0;
				}
				return ( $a['revenue'] > $b['revenue'] ) ? -1 : 1;
			}
		);

		$limit        = $full ? count( $product_sales ) : 5;
		$top_products = array();

		foreach ( array_slice( $product_sales, 0, $limit, true ) as $name => $data ) {
			$top_products[] = array(
				'name'        => $name,
				'qty'         => $data['qty'],
				'revenue'     => number_format( $data['revenue'], 2 ),
				'views'       => 0,
				'add_to_cart' => 0,
				'checkout'    => 0,
			);
		}

		return array(
			'revenue'      => $total_revenue,
			'orders'       => $total_orders,
			'avg_order'    => $total_orders > 0 ? $total_revenue / $total_orders : 0,
			'top_products' => $top_products,
		);
	}

	public static function handle_realtime_visitors( \WP_REST_Request $request = null ) {
		$report = self::run_realtime_report(
			array(
				'metrics' => array( array( 'name' => 'activeUsers' ) ),
			)
		);

		$active = 0;
		if ( ! isset( $report['error'] ) && isset( $report['rows'][0]['metricValues'][0]['value'] ) ) {
			$active = (int) $report['rows'][0]['metricValues'][0]['value'];
		}
		return $active;
	}

	/**
	 * Unified REST / AJAX handler for Free Dashboard data.
	 */
	public static function handle_free_dashboard( \WP_REST_Request $request = null ) {
		if ( ! $request ) {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ( isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
				}
				return new \WP_Error( 'rest_forbidden', __( 'Invalid security token.', 'smackcoders-pulse-analytics-for-woocommerce' ), array( 'status' => 403 ) );
			}
			if ( ! current_user_can( 'sm_pulse_analytics_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
				}
				return new \WP_Error( 'rest_forbidden', __( 'Insufficient permissions.', 'smackcoders-pulse-analytics-for-woocommerce' ), array( 'status' => 403 ) );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
			$start_param = isset( $_GET['startDate'] ) ? sanitize_text_field( wp_unslash( $_GET['startDate'] ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
			$end_param   = isset( $_GET['endDate'] ) ? sanitize_text_field( wp_unslash( $_GET['endDate'] ) ) : gmdate( 'Y-m-d' );

			$request = new \WP_REST_Request( 'GET', '/pulse-analytics/v1/dashboard/free' );
			$request->set_param( 'startDate', $start_param );
			$request->set_param( 'endDate', $end_param );
		}

		$startDate = $request->get_param( 'startDate' ) ? sanitize_text_field( (string) $request->get_param( 'startDate' ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ? sanitize_text_field( (string) $request->get_param( 'endDate' ) ) : gmdate( 'Y-m-d' );

		$realtime_active  = self::handle_realtime_visitors( $request );
		$kpi              = self::handle_kpi_metrics( $request );
		$daily            = self::handle_daily_metrics( $request );
		$daily_referrals  = self::handle_daily_referral_metrics( $request );
		$daily_engagement = self::handle_daily_engagement_metrics( $request );
		$daily_ecommerce  = self::handle_daily_ecommerce_metrics( $request );
		$sources          = self::handle_source_medium( $request );
		$top_referrals    = self::handle_top_referrals( $request );
		$devices          = self::handle_device_categories( $request );
		$browsers         = self::handle_browsers( $request );
		$visitor_types    = self::handle_visitor_types( $request );
		$top_pages        = self::handle_top_pages( $request );
		$top_countries    = self::handle_top_countries( $request );

		// Enforce strict top 5 limits for Free widgets
		$top_pages_5     = is_array( $top_pages ) ? array_slice( $top_pages, 0, 5 ) : array();
		$top_countries_5 = is_array( $top_countries ) ? array_slice( $top_countries, 0, 5 ) : array();

		// Compute Previous Period Comparison
		$prevRange  = self::get_previous_date_range( $startDate, $endDate );
		$prevReq    = new \WP_REST_Request( 'GET', '/pulse-analytics/v1/kpi-metrics' );
		$prevReq->set_param( 'startDate', $prevRange['startDate'] );
		$prevReq->set_param( 'endDate', $prevRange['endDate'] );
		$prevKpi    = self::handle_kpi_metrics( $prevReq );

		$currSessions  = (int) ( $kpi['sessions'] ?? 0 );
		$prevSessions  = (int) ( $prevKpi['sessions'] ?? 0 );
		$sessionsDelta = $prevSessions > 0 ? round( ( ( $currSessions - $prevSessions ) / $prevSessions ) * 100, 1 ) : 0;
		$sessionsStr   = ( $sessionsDelta >= 0 ? '+' : '' ) . $sessionsDelta . '% vs last period';

		$currPageviews = (int) ( $kpi['pageviews'] ?? 0 );
		$pvPerSession  = $currSessions > 0 ? round( $currPageviews / $currSessions, 2 ) : 0;
		$pageviewsStr  = number_format( $pvPerSession, 2 ) . ' pages/session';

		$currDuration  = (float) ( $kpi['avg_session_duration'] ?? 0 );
		$prevDuration  = (float) ( $prevKpi['avg_session_duration'] ?? 0 );
		$durDiffSec    = (int) round( $currDuration - $prevDuration );
		$durationStr   = ( $durDiffSec >= 0 ? '+' : '' ) . $durDiffSec . 's vs last period';

		$currEngage    = (float) ( $kpi['engagement_rate'] ?? 0 );
		$prevEngage    = (float) ( $prevKpi['engagement_rate'] ?? 0 );
		$engageDelta   = $prevEngage > 0 ? round( ( ( $currEngage - $prevEngage ) / $prevEngage ) * 100, 1 ) : round( $currEngage * 100, 1 );
		$engageStr     = ( $engageDelta >= 0 ? '+' : '' ) . $engageDelta . '% vs last period';

		$has_token    = false;
		$has_property = false;
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_OAuth' ) ) {
			$has_token = PulseAnalytics_GA4_OAuth::is_connected();
		}
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' ) ) {
			$has_property = '' !== PulseAnalytics_GA4_Config::get_property_id();
		}
		$ready = $has_token && $has_property;
		$connection_message = '';
		if ( ! $ready ) {
			$connection_message = __( 'Connect GA4 OAuth and set a numeric Property ID under Analytics settings to load live dashboard metrics.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		return rest_ensure_response(
			array(
				'success'           => true,
				'connection'        => array(
					'oauth_connected' => (bool) $has_token,
					'has_property_id' => (bool) $has_property,
					'ready'           => (bool) $ready,
					'message'         => $connection_message,
				),
				'realtime_visitors' => (int) $realtime_active,
				'kpi'               => array(
					'sessions'             => $currSessions,
					'sessions_change'      => $sessionsStr,
					'pageviews'            => $currPageviews,
					'pageviews_change'     => $pageviewsStr,
					'avg_session_duration' => $currDuration,
					'duration_change'      => $durationStr,
					'engagement_rate'      => $currEngage,
					'engagement_change'    => $engageStr,
				),
				'daily'             => is_wp_error( $daily ) ? array( 'labels' => array(), 'dates_ymd' => array(), 'sessions' => array(), 'totalUsers' => array(), 'newUsers' => array(), 'pageviews' => array() ) : $daily,
				'daily_referrals'   => is_wp_error( $daily_referrals ) ? array( 'labels' => array(), 'dates_ymd' => array(), 'referral_sessions' => array(), 'referral_users' => array(), 'top_5_sources' => array(), 'top_5_series' => array() ) : $daily_referrals,
				'daily_engagement'  => is_wp_error( $daily_engagement ) ? array( 'labels' => array(), 'dates_ymd' => array(), 'engagedSessions' => array(), 'engagementRate' => array(), 'bounceRate' => array() ) : $daily_engagement,
				'daily_ecommerce'   => is_wp_error( $daily_ecommerce ) ? array( 'labels' => array(), 'dates_ymd' => array(), 'revenue' => array(), 'orders' => array(), 'aov' => array() ) : $daily_ecommerce,
				'sources'           => is_wp_error( $sources ) ? array() : $sources,
				'top_referrals'     => is_wp_error( $top_referrals ) ? array() : $top_referrals,
				'devices'           => is_wp_error( $devices ) ? array( 'labels' => array(), 'data' => array() ) : $devices,
				'browsers'          => is_wp_error( $browsers ) ? array( 'labels' => array(), 'data' => array() ) : $browsers,
				'visitor_types'     => is_wp_error( $visitor_types ) ? array( 'labels' => array( 'New', 'Returning' ), 'data' => array( 0, 0 ) ) : $visitor_types,
				'top_pages'         => $top_pages_5,
				'top_countries'     => $top_countries_5,
				'key_event_types'   => self::get_active_key_event_types(),
			)
		);
	}

	/**
	 * Helper to return configured and default Key Event types for dashboard UI legend tooltips.
	 */
	public static function get_active_key_event_types() {
		$types           = array();
		$seen_categories = array();

		// 1. Fetch saved Custom Goals from WordPress option
		$custom_goals = get_option( 'sm_pulse_analytics_custom_goals', array() );
		if ( ! empty( $custom_goals ) && is_array( $custom_goals ) ) {
			foreach ( $custom_goals as $goal ) {
				if ( isset( $goal['active'] ) && false === (bool) $goal['active'] ) {
					continue;
				}
				$g_name = ! empty( $goal['label'] ) ? sanitize_text_field( $goal['label'] ) : ( ! empty( $goal['name'] ) ? sanitize_text_field( $goal['name'] ) : ( $goal['event_name'] ?? '' ) );
				$g_evt  = ! empty( $goal['event_name'] ) ? sanitize_text_field( $goal['event_name'] ) : 'custom_goal';
				$g_cat  = 'goal_' . ( $goal['id'] ?? $g_evt );

				if ( ! empty( $g_name ) && ! in_array( $g_cat, $seen_categories, true ) ) {
					$seen_categories[] = $g_cat;
					$types[]           = array(
						'name'  => $g_name,
						'event' => $g_evt,
						'desc'  => 'Custom Goal',
					);
				}
			}
		}

		// 2. Query dynamic event names from GA4 Data API
		$ga_events = self::run_custom_report(
			array(
				'dimensions' => array( array( 'name' => 'eventName' ) ),
				'metrics'    => array( array( 'name' => 'keyEvents' ), array( 'name' => 'eventCount' ) ),
				'dateRanges' => array( array( 'startDate' => '30daysAgo', 'endDate' => 'today' ) ),
				'limit'      => 30,
			)
		);

		if ( ! is_wp_error( $ga_events ) && ! isset( $ga_events['error'] ) && ! empty( $ga_events['rows'] ) && is_array( $ga_events['rows'] ) ) {
			$system_ignore = array( 'page_view', 'session_start', 'user_engagement', 'first_visit', 'scroll' );
			foreach ( $ga_events['rows'] as $row ) {
				$evt_name  = $row['dimensionValues'][0]['value'] ?? '';
				$key_count = (int) ( $row['metricValues'][0]['value'] ?? 0 );

				if ( empty( $evt_name ) || in_array( $evt_name, $system_ignore, true ) ) {
					continue;
				}

				// Map to normalized category key to prevent duplicate entries
				$cat_key         = $evt_name;
				$formatted_title = ucwords( str_replace( array( '_', '-' ), ' ', $evt_name ) );
				$display_evt     = $evt_name;

				if ( 'pulse_form_submit' === $evt_name || 'form_submit' === $evt_name ) {
					$cat_key         = 'form_submissions';
					$formatted_title = 'Form Submissions';
					$display_evt     = 'form_submit';
				} elseif ( 'purchase' === $evt_name ) {
					$cat_key         = 'purchases';
					$formatted_title = 'Purchases';
					$display_evt     = 'purchase';
				} elseif ( 'outbound_link_click' === $evt_name || 'affiliate_link_click' === $evt_name ) {
					$cat_key         = 'link_clicks';
					$formatted_title = 'Link Clicks';
					$display_evt     = 'link_click';
				} elseif ( 'file_download' === $evt_name ) {
					$cat_key         = 'file_downloads';
					$formatted_title = 'File Downloads';
					$display_evt     = 'file_download';
				}

				if ( in_array( $cat_key, $seen_categories, true ) ) {
					continue;
				}

				if ( $key_count > 0 || in_array( $evt_name, array( 'purchase', 'pulse_form_submit', 'form_submit', 'outbound_link_click', 'affiliate_link_click', 'file_download' ), true ) ) {
					$seen_categories[] = $cat_key;
					$types[]           = array(
						'name'  => $formatted_title,
						'event' => $display_evt,
						'desc'  => 'Tracked Event',
					);
				}
			}
		}

		// 3. Fallback for Active Site Modules if GA4 returned no events
		if ( empty( $types ) ) {
			if ( class_exists( 'WooCommerce' ) ) {
				$types[] = array( 'name' => 'Purchases', 'event' => 'purchase', 'desc' => 'WooCommerce Orders' );
			}
			$types[] = array( 'name' => 'Form Submissions', 'event' => 'form_submit', 'desc' => 'Form Submissions' );
			$types[] = array( 'name' => 'Link Clicks', 'event' => 'link_click', 'desc' => 'Link Clicks' );
		}

		return $types;
	}

	/**
	 * Unified REST / AJAX handler for PRO Dashboard data.
	 */
	public static function handle_pro_dashboard( \WP_REST_Request $request = null ) {
		if ( ! $request ) {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ( isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
				}
				return new \WP_Error( 'rest_forbidden', __( 'Invalid security token.', 'smackcoders-pulse-analytics-for-woocommerce' ), array( 'status' => 403 ) );
			}
			if ( ! current_user_can( 'sm_pulse_analytics_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
				}
				return new \WP_Error( 'rest_forbidden', __( 'Insufficient permissions.', 'smackcoders-pulse-analytics-for-woocommerce' ), array( 'status' => 403 ) );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
			$start_param = isset( $_GET['startDate'] ) ? sanitize_text_field( wp_unslash( $_GET['startDate'] ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
			$end_param   = isset( $_GET['endDate'] ) ? sanitize_text_field( wp_unslash( $_GET['endDate'] ) ) : gmdate( 'Y-m-d' );

			$request = new \WP_REST_Request( 'GET', '/pulse-analytics/v1/dashboard/pro' );
			$request->set_param( 'startDate', $start_param );
			$request->set_param( 'endDate', $end_param );
		}

		$startDate = $request->get_param( 'startDate' ) ? sanitize_text_field( (string) $request->get_param( 'startDate' ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$endDate   = $request->get_param( 'endDate' ) ? sanitize_text_field( (string) $request->get_param( 'endDate' ) ) : gmdate( 'Y-m-d' );

		$funnel_data     = self::handle_funnel_report( $request );
		$kpi_free        = self::handle_kpi_metrics( $request );
		$daily_ecommerce = self::handle_daily_ecommerce_metrics( $request );

		$revenue      = 0.0;
		$orders       = 0;
		$top_products = array();

		if ( is_array( $daily_ecommerce ) && isset( $daily_ecommerce['revenue'] ) && is_array( $daily_ecommerce['revenue'] ) ) {
			$revenue = (float) array_sum( $daily_ecommerce['revenue'] );
			$orders  = (int) array_sum( $daily_ecommerce['orders'] ?? array() );
		}

		// Direct GA4 Top Products
		$ga_items = self::fetch_top_products( $startDate, $endDate );
		if ( ! isset( $ga_items['error'] ) && ! empty( $ga_items['rows'] ) && is_array( $ga_items['rows'] ) ) {
			foreach ( array_slice( $ga_items['rows'], 0, 10 ) as $row ) {
				$top_products[] = array(
					'name'    => $row['dimensionValues'][0]['value'] ?? 'Product',
					'qty'     => (int) ( $row['metricValues'][0]['value'] ?? 0 ),
					'revenue' => number_format( (float) ( $row['metricValues'][1]['value'] ?? 0 ), 2 ),
				);
			}
		}

		$sessions        = (int) ( $kpi_free['sessions'] ?? 0 );
		$aov             = $orders > 0 ? $revenue / $orders : 0.0;
		$conversion_rate = $sessions > 0 ? ( $orders / $sessions ) * 100 : 0.0;

		// Previous Period Comparison for PRO metrics directly from GA4
		$prevRange     = self::get_previous_date_range( $startDate, $endDate );
		$prevReq       = new \WP_REST_Request( 'GET', '/pulse-analytics/v1/dashboard/pro' );
		$prevReq->set_param( 'startDate', $prevRange['startDate'] );
		$prevReq->set_param( 'endDate', $prevRange['endDate'] );
		$prevKpiFree   = self::handle_kpi_metrics( $prevReq );
		$prevDailyEcom = self::handle_daily_ecommerce_metrics( $prevReq );

		$prevRevenue = ( is_array( $prevDailyEcom ) && isset( $prevDailyEcom['revenue'] ) ) ? (float) array_sum( $prevDailyEcom['revenue'] ) : 0.0;
		$prevOrders  = ( is_array( $prevDailyEcom ) && isset( $prevDailyEcom['orders'] ) ) ? (int) array_sum( $prevDailyEcom['orders'] ) : 0;

		$prevAov      = $prevOrders > 0 ? $prevRevenue / $prevOrders : 0.0;
		$prevSessions = (int) ( $prevKpiFree['sessions'] ?? 0 );
		$prevConvRate = $prevSessions > 0 ? ( $prevOrders / $prevSessions ) * 100 : 0.0;

		$revDelta     = $prevRevenue > 0 ? round( ( ( $revenue - $prevRevenue ) / $prevRevenue ) * 100, 1 ) : ( $revenue > 0 ? 100.0 : 0 );
		$revChangeStr = ( $revDelta >= 0 ? '+' : '' ) . $revDelta . '% vs last period';

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : ( function_exists( '\Sm_Pulse_Analytics\GA_Connector::get_currency_symbol' ) ? \Sm_Pulse_Analytics\GA_Connector::get_currency_symbol() : '$' );
		$aovDiff         = $aov - $prevAov;
		$aovChangeStr    = ( $aovDiff >= 0 ? '+' : '-' ) . $currency_symbol . number_format( abs( $aovDiff ), 2 ) . ' vs last period';

		$ordersDelta     = $prevOrders > 0 ? round( ( ( $orders - $prevOrders ) / $prevOrders ) * 100, 1 ) : ( $orders > 0 ? 100.0 : 0 );
		$ordersChangeStr = ( $ordersDelta >= 0 ? '+' : '' ) . $ordersDelta . '% vs last period';

		$convDelta     = $prevConvRate > 0 ? round( ( ( $conversion_rate - $prevConvRate ) / $prevConvRate ) * 100, 1 ) : ( $conversion_rate > 0 ? 100.0 : 0 );
		$convChangeStr = ( $convDelta >= 0 ? '+' : '' ) . $convDelta . '% vs last period';

		$formatted_rev = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $revenue ) ) ) : $currency_symbol . number_format( $revenue, 2 );
		$formatted_aov = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $aov ) ) ) : $currency_symbol . number_format( $aov, 2 );

		return rest_ensure_response(
			array(
				'success'         => true,
				'currency_symbol' => $currency_symbol,
				'kpi'             => array(
					'revenue'           => $revenue,
					'revenue_formatted' => $formatted_rev,
					'revenue_change'    => $revChangeStr,
					'conversion_rate'   => round( $conversion_rate, 2 ),
					'conversion_change' => $convChangeStr,
					'aov'               => $aov,
					'aov_formatted'     => $formatted_aov,
					'aov_change'        => $aovChangeStr,
					'orders'            => $orders,
					'orders_change'     => $ordersChangeStr,
				),
				'daily_ecommerce' => is_wp_error( $daily_ecommerce ) ? array( 'labels' => array(), 'dates_ymd' => array(), 'revenue' => array(), 'orders' => array(), 'aov' => array() ) : $daily_ecommerce,
				'top_products'    => $top_products,
				'funnel'          => is_wp_error( $funnel_data ) ? array() : $funnel_data,
			)
		);
	}

	public static function format_simple_report( $report ) {
		if ( isset( $report['error'] ) ) {
			$error_code    = isset( $report['code'] ) ? $report['code'] : 'ga_error';
			$error_message = isset( $report['message'] ) ? $report['message'] : $report['error'];
			return new \WP_Error( $error_code, $error_message, array( 'status' => 500 ) );
		}
		$labels = array();
		$data   = array();
		foreach ( $report['rows'] ?? array() as $row ) {
			$labels[] = $row['dimensionValues'][0]['value'];
			$data[]   = (int) $row['metricValues'][0]['value'];
		}
		return array(
			'labels' => $labels,
			'data'   => $data,
		);
	}

	public static function handle_get_settings( \WP_REST_Request $request ) {
		$layout = get_option( 'sm_pulse_analytics_dashboard_layout', array() );

		if ( empty( $layout ) ) {
			$layout = array(
				'live_visitors',
				'sessions',
				'page_views',
				'revenue',
				'conversion_rate',
				'avg_order_value',
				'session_duration',
				'engagement_rate',
				'top_products',
				'funnel',
				'traffic_chart',
				'visitor_types',
				'devices',
				'browsers',
				'top_pages',
				'top_countries',
				'source_medium',
			);
		}

		$retention = (int) get_option( 'sm_pulse_analytics_telemetry_retention_days', 30 );

		return rest_ensure_response(
			array(
				'dashboard_layout'         => $layout,
				'telemetry_retention_days' => $retention,
			)
		);
	}

	public static function handle_post_settings( \WP_REST_Request $request ) {
		$body = $request->get_json_params();

		if ( isset( $body['dashboard_layout'] ) && is_array( $body['dashboard_layout'] ) ) {
			$sanitized_layout = array();
			foreach ( $body['dashboard_layout'] as $widget_id ) {
				if ( is_string( $widget_id ) ) {
					$sanitized_layout[] = sanitize_key( $widget_id );
				}
			}
			update_option( 'sm_pulse_analytics_dashboard_layout', array_values( $sanitized_layout ) );
		}

		if ( isset( $body['telemetry_retention_days'] ) ) {
			$days = (int) $body['telemetry_retention_days'];
			update_option( 'sm_pulse_analytics_telemetry_retention_days', max( 0, $days ) );
		}

		$layout    = get_option( 'sm_pulse_analytics_dashboard_layout', array() );
		$retention = (int) get_option( 'sm_pulse_analytics_telemetry_retention_days', 30 );

		return rest_ensure_response(
			array(
				'dashboard_layout'         => $layout,
				'telemetry_retention_days' => $retention,
				'message'                  => 'Settings updated successfully',
			)
		);
	}

	public static function handle_integration_woocommerce( \WP_REST_Request $request ) {
		unset( $request );
		$is_active = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' )
			? PulseAnalytics_Site_Profile::is_woocommerce_active()
			: ( class_exists( 'WooCommerce' ) || is_plugin_active( 'woocommerce/woocommerce.php' ) );
		return rest_ensure_response( array(
			'active'    => $is_active,
			'last_sync' => null,
			'status'    => $is_active ? 'active' : 'inactive',
		) );
	}

	public static function handle_integration_commerce( \WP_REST_Request $request ) {
		unset( $request );
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) ) {
			return rest_ensure_response( PulseAnalytics_Site_Profile::get_integration_payload() );
		}

		$woo_active = class_exists( 'WooCommerce' );
		return rest_ensure_response(
			array(
				'site_type'    => $woo_active ? 'store' : 'website',
				'has_commerce' => $woo_active,
				'platforms'    => array(
					'woocommerce' => array(
						'active'  => $woo_active,
						'enabled' => $woo_active,
						'label'   => 'WooCommerce',
						'status'  => $woo_active ? 'active' : 'inactive',
					),
				),
			)
		);
	}

	public static function handle_integration_google_analytics( \WP_REST_Request $request ) {
		$auth     = get_option( 'sm_pulse_analytics_auth', array() );
		$settings = get_option( 'sm_pulse_analytics_settings', array() );

		$has_access_token  = ! empty( $auth['access_token'] );
		$has_refresh_token = ! empty( $auth['refresh_token'] );
		$has_property_id   = ! empty( $settings['property_id'] );
		$connected         = $has_access_token && ( $has_refresh_token || $has_property_id );

		$response = array(
			'connected'   => $connected,
			'property_id' => $settings['property_id'] ?? null,
			'last_sync'   => null,
			'status'      => $connected ? 'connected' : 'disconnected',
		);

		if ( $connected ) {
			$response['has_access_token']  = $has_access_token;
			$response['has_refresh_token'] = $has_refresh_token;
			if ( isset( $auth['token_expiry'] ) ) {
				$response['token_expires_at'] = gmdate( 'Y-m-d H:i:s', $auth['token_expiry'] );
			}
		}

		return rest_ensure_response( $response );
	}

	public static function handle_get_reports( \WP_REST_Request $request ) {
		$prebuilt_reports = array(
			array( 'id' => 'daily_metrics', 'title' => 'Daily Metrics', 'type' => 'prebuilt' ),
			array( 'id' => 'top_pages', 'title' => 'Top Pages', 'type' => 'prebuilt' ),
			array( 'id' => 'top_countries', 'title' => 'Top Countries', 'type' => 'prebuilt' ),
			array( 'id' => 'source_medium', 'title' => 'Source / Medium', 'type' => 'prebuilt' ),
			array( 'id' => 'funnel', 'title' => 'Shopping Funnel', 'type' => 'prebuilt' ),
			array( 'id' => 'kpi_metrics', 'title' => 'KPI Metrics', 'type' => 'prebuilt' ),
			array( 'id' => 'wc_metrics', 'title' => 'WooCommerce Metrics', 'type' => 'prebuilt' ),
		);

		return rest_ensure_response( array(
			'prebuilt' => $prebuilt_reports,
			'saved'    => array(),
			'all'      => $prebuilt_reports,
		) );
	}

	public static function handle_get_report_detail( \WP_REST_Request $request ) {
		$report_id  = $request->get_param( 'id' );
		$start_date = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' );

		$prebuilt_reports = array(
			'daily_metrics' => array( __CLASS__, 'handle_daily_metrics' ),
			'top_pages'     => array( __CLASS__, 'handle_top_pages' ),
			'top_countries' => array( __CLASS__, 'handle_top_countries' ),
			'source_medium' => array( __CLASS__, 'handle_source_medium' ),
			'funnel'        => array( __CLASS__, 'handle_funnel_report' ),
			'kpi_metrics'   => array( __CLASS__, 'handle_kpi_metrics' ),
			'wc_metrics'    => array( __CLASS__, 'handle_wc_metrics' ),
		);

		if ( isset( $prebuilt_reports[ $report_id ] ) ) {
			$_GET['startDate'] = $start_date;
			$_GET['endDate']   = $end_date;

			$result = call_user_func( $prebuilt_reports[ $report_id ], $request );

			return rest_ensure_response( array(
				'id'         => $report_id,
				'type'       => 'prebuilt',
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'data'       => $result,
			) );
		}

		return new \WP_Error( 'invalid_report_id', 'Invalid report ID', array( 'status' => 400 ) );
	}

	public static function handle_post_custom_report( \WP_REST_Request $request ) {
		$body = $request->get_params();

		if ( empty( $body['dateRanges'] ) || ! is_array( $body['dateRanges'] ) || empty( $body['metrics'] ) || ! is_array( $body['metrics'] ) ) {
			return new \WP_Error( 'invalid_request', 'dateRanges and metrics are required arrays', array( 'status' => 400 ) );
		}

		$ga_body = array(
			'dateRanges' => array_map( function ( $r ) {
				return array(
					'startDate' => sanitize_text_field( $r['startDate'] ?? '' ),
					'endDate'   => sanitize_text_field( $r['endDate'] ?? '' ),
				);
			}, $body['dateRanges'] ),
			'metrics'    => array_map( function ( $m ) {
				return array( 'name' => sanitize_text_field( is_array( $m ) ? ( $m['name'] ?? '' ) : $m ) );
			}, $body['metrics'] ),
		);

		if ( ! empty( $body['dimensions'] ) && is_array( $body['dimensions'] ) ) {
			$ga_body['dimensions'] = array_map( function ( $d ) {
				return array( 'name' => sanitize_text_field( is_array( $d ) ? ( $d['name'] ?? '' ) : $d ) );
			}, $body['dimensions'] );
		}

		$result = self::run_custom_report( $ga_body );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'ga_api_error', is_string( $result['error'] ) ? $result['error'] : 'GA API error', array( 'status' => 502 ) );
		}

		return rest_ensure_response( array(
			'data'  => array(
				'rows'          => $result['rows'] ?? array(),
				'rowCount'      => $result['rowCount'] ?? 0,
				'columnHeaders' => $result['dimensionHeaders'] ?? array(),
				'metricHeaders' => $result['metricHeaders'] ?? array(),
			),
			'saved' => false,
		) );
	}

	public static function handle_get_reports_compare( \WP_REST_Request $request ) {
		$start_date = $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' );

		$start_ts  = strtotime( $start_date );
		$days_diff = max( 1, ceil( ( strtotime( $end_date ) - $start_ts ) / DAY_IN_SECONDS ) );

		$compare_end   = gmdate( 'Y-m-d', $start_ts - 86400 );
		$compare_start = gmdate( 'Y-m-d', $start_ts - ( $days_diff * 86400 ) - 86400 );

		$ga_body = array(
			'dateRanges' => array(
				array( 'startDate' => $start_date, 'endDate' => $end_date ),
				array( 'startDate' => $compare_start, 'endDate' => $compare_end ),
			),
			'metrics'    => array(
				array( 'name' => 'sessions' ),
				array( 'name' => 'totalUsers' ),
				array( 'name' => 'purchaseRevenue' ),
			),
		);

		$result = self::run_custom_report( $ga_body );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'ga_api_error', is_string( $result['error'] ) ? $result['error'] : 'GA API error', array( 'status' => 502 ) );
		}

		return rest_ensure_response( array(
			'current_period'    => array( 'start' => $start_date, 'end' => $end_date ),
			'comparison_period' => array( 'start' => $compare_start, 'end' => $compare_end ),
			'data'              => $result['rows'] ?? array(),
		) );
	}

	/**
	 * Public endpoint for frontend link-click tracking.
	 */
	public static function handle_link_click( \WP_REST_Request $request ) {
		$params    = $request->get_json_params();
		$link_type = '';
		$link_url  = '';
		$page_url  = '';
		$session_id = '';

		if ( is_array( $params ) ) {
			$link_type  = sanitize_text_field( $params['link_type'] ?? $params['type'] ?? '' );
			$link_url   = esc_url_raw( $params['link_url'] ?? $params['url'] ?? '' );
			$page_url   = esc_url_raw( $params['page_url'] ?? '' );
			$session_id = sanitize_text_field( $params['session_id'] ?? '' );
		}

		if ( empty( $link_type ) || empty( $link_url ) ) {
			return new \WP_Error( 'invalid_request', 'link_type and link_url are required.', array( 'status' => 400 ) );
		}

		$inserted = PulseAnalytics_Storage::insert_event(
			array(
				'event_type'  => 'link_click',
				'event_data'  => array(
					'link_type'  => $link_type,
					'link_url'   => $link_url,
					'page_url'   => $page_url,
					'session_id' => $session_id,
				),
				'source'      => $link_type,
				'exit_page'   => 'other',
			)
		);

		if ( false === $inserted && ! PulseAnalytics_Storage::events_table_exists() ) {
			return rest_ensure_response( array( 'success' => true, 'stored' => false ) );
		}

		return rest_ensure_response( array( 'success' => true, 'stored' => (bool) $inserted ) );
	}

	/**
	 * Legacy form-event endpoint (#30).
	 *
	 * Form analytics are tracked exclusively via GA4 gtag on the frontend.
	 * This route no longer writes high-frequency telemetry to the local database.
	 */
	public static function handle_form_event( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new \WP_Error( 'invalid_request', 'Invalid JSON body.', array( 'status' => 400 ) );
		}

		$event_name = sanitize_key( $params['event_name'] ?? '' );
		$allowed    = array( 'pulse_form_view', 'pulse_form_start', 'pulse_form_submit' );
		if ( ! in_array( $event_name, $allowed, true ) ) {
			return new \WP_Error( 'invalid_event', 'Unsupported form event.', array( 'status' => 400 ) );
		}

		$form_id = sanitize_text_field( $params['form_id'] ?? '' );
		if ( '' === $form_id ) {
			return new \WP_Error( 'invalid_request', 'form_id is required.', array( 'status' => 400 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'stored'  => false,
				'mode'    => 'ga4_gtag_only',
				'message' => 'Form events are tracked in GA4 only; local telemetry storage is disabled.',
			)
		);
	}

	/**
	 * Aggregate form conversion rows from local telemetry.
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public static function aggregate_form_events( $start_date, $end_date ) {
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Reporter' ) ) {
			return \Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Reporter::fetch_report( $start_date, $end_date );
		}

		return array(
			'kpis'  => array(
				'views'       => 0,
				'starts'      => 0,
				'submissions' => 0,
				'conv_rate'   => '0.0%',
			),
			'rows'  => array(),
			'trend' => array(
				'labels' => array(),
				'values' => array(),
			),
		);
	}

	/**
	 * PRO: Forms conversion report JSON.
	 */
	public static function handle_forms_report( \WP_REST_Request $request ) {
		$start_date = sanitize_text_field( $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$end_date   = sanitize_text_field( $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' ) );

		return rest_ensure_response( self::aggregate_form_events( $start_date, $end_date ) );
	}

	/**
	 * Aggregate link_click rows from local sm_pulse_analytics_telemetry (frontend REST ingest).
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array{outbound: array, affiliate: array, downloads: array, inbound: array}
	 */
	public static function aggregate_link_click_events( $start_date, $end_date ) {
		global $wpdb;

		$empty = array(
			'outbound'  => array(),
			'affiliate' => array(),
			'downloads' => array(),
			'inbound'   => array(),
		);

		$table = PulseAnalytics_Storage::table_name( 'events' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return $empty;
		}

		$start_dt = $start_date . ' 00:00:00';
		$end_dt   = $end_date . ' 23:59:59';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT payload FROM {$table} WHERE kind = %s AND recorded_at >= %s AND recorded_at <= %s",
				'link_click',
				$start_dt,
				$end_dt
			),
			ARRAY_A
		);
		// phpcs:enable

		if ( empty( $rows ) ) {
			return $empty;
		}

		$buckets = array(
			'outbound'     => array(),
			'affiliate'    => array(),
			'downloadable' => array(),
			'inbound'      => array(),
		);

		foreach ( $rows as $row ) {
			$payload = json_decode( $row['payload'] ?? '', true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$url  = $payload['link_url'] ?? '';
			$type = $payload['link_type'] ?? 'outbound';
			if ( empty( $url ) ) {
				continue;
			}
			if ( 'downloadable' === $type ) {
				$key = 'downloadable';
			} elseif ( 'affiliate' === $type ) {
				$key = 'affiliate';
			} else {
				$key = 'outbound';
			}
			if ( ! isset( $buckets[ $key ][ $url ] ) ) {
				$buckets[ $key ][ $url ] = 0;
			}
			++$buckets[ $key ][ $url ];

			$page_url = $payload['page_url'] ?? '';
			if ( '' !== $page_url ) {
				$page_path = wp_parse_url( $page_url, PHP_URL_PATH );
				if ( ! is_string( $page_path ) || '' === $page_path ) {
					$page_path = $page_url;
				}
				if ( ! isset( $buckets['inbound'][ $page_path ] ) ) {
					$buckets['inbound'][ $page_path ] = 0;
				}
				++$buckets['inbound'][ $page_path ];
			}
		}

		$result = array();
		foreach ( $buckets as $bucket_key => $url_counts ) {
			$out_key            = ( 'downloadable' === $bucket_key ) ? 'downloads' : $bucket_key;
			$result[ $out_key ] = array();
			foreach ( $url_counts as $url => $count ) {
				$result[ $out_key ][] = array(
					'link'   => $url,
					'clicks' => $count,
				);
			}
			usort(
				$result[ $out_key ],
				function ( $a, $b ) {
					return $b['clicks'] - $a['clicks'];
				}
			);
		}

		return array(
			'outbound'  => $result['outbound'] ?? array(),
			'affiliate' => $result['affiliate'] ?? array(),
			'downloads' => $result['downloads'] ?? array(),
			'inbound'   => $result['inbound'] ?? array(),
		);
	}

	/**
	 * Free Audience & Links report: merge GA4 events + local telemetry.
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public static function fetch_link_clicks_report( $start_date, $end_date ) {
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );

		$cache_key = 'pulse_link_clicks_v3_' . md5( $start_date . '|' . $end_date . '|' . self::get_property_id() );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['source'] ) ) {
			return $cached;
		}

		$local     = self::aggregate_link_click_events( $start_date, $end_date );
		$ga4       = self::fetch_ga4_link_click_buckets( $start_date, $end_date );
		$ga4_ok    = ! empty( $ga4['ok'] );
		$ga4_empty = empty( $ga4['outbound'] ) && empty( $ga4['affiliate'] ) && empty( $ga4['downloads'] ) && empty( $ga4['inbound'] );
		$local_empty = empty( $local['outbound'] ) && empty( $local['affiliate'] ) && empty( $local['downloads'] ) && empty( $local['inbound'] );

		if ( ! $ga4_ok && $local_empty ) {
			$report = array(
				'outbound'  => array(),
				'affiliate' => array(),
				'downloads' => array(),
				'inbound'   => array(),
				'source'    => 'unavailable',
				'is_demo'   => false,
			);
			set_transient( $cache_key, $report, 5 * MINUTE_IN_SECONDS );
			return $report;
		}

		$outbound  = self::merge_link_url_rows( $ga4['outbound'] ?? array(), $local['outbound'] ?? array() );
		$affiliate = self::merge_link_url_rows( $ga4['affiliate'] ?? array(), $local['affiliate'] ?? array() );
		$downloads = self::merge_link_url_rows( $ga4['downloads'] ?? array(), $local['downloads'] ?? array() );
		$inbound   = self::merge_link_url_rows( $ga4['inbound'] ?? array(), $local['inbound'] ?? array() );

		$has_ga4   = ! $ga4_empty;
		$has_local = ! $local_empty;
		if ( $has_ga4 && $has_local ) {
			$source = 'mixed';
		} elseif ( $has_ga4 ) {
			$source = 'ga4';
		} elseif ( $has_local ) {
			$source = 'local';
		} else {
			$source = $ga4_ok ? 'ga4' : 'unavailable';
		}

		$report = array(
			'outbound'  => $outbound,
			'affiliate' => $affiliate,
			'downloads' => $downloads,
			'inbound'   => $inbound,
			'source'    => $source,
			'is_demo'   => false,
		);

		set_transient( $cache_key, $report, 10 * MINUTE_IN_SECONDS );
		return $report;
	}

	/**
	 * Fetch GA4 outbound / affiliate / download / inbound buckets.
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array{ok:bool,outbound:array,affiliate:array,downloads:array,inbound:array}
	 */
	private static function fetch_ga4_link_click_buckets( $start_date, $end_date ) {
		$out = array(
			'ok'        => false,
			'outbound'  => array(),
			'affiliate' => array(),
			'downloads' => array(),
			'inbound'   => array(),
		);

		$access_token = class_exists( __NAMESPACE__ . '\\GA_Auth' ) ? GA_Auth::get_access_token() : '';
		$property_id  = self::get_property_id();
		if ( ( is_array( $access_token ) && isset( $access_token['error'] ) ) || empty( $access_token ) || empty( $property_id ) ) {
			return $out;
		}

		$click_res = self::run_custom_report(
			array(
				'dateRanges'      => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'eventName',
						'inListFilter' => array(
							'values' => array( 'affiliate_link_click', 'outbound_link_click', 'click', 'file_download' ),
						),
					),
				),
				'dimensions'      => array(
					array( 'name' => 'eventName' ),
					array( 'name' => 'linkUrl' ),
					array( 'name' => 'pagePath' ),
					array( 'name' => 'fileName' ),
				),
				'metrics'         => array(
					array( 'name' => 'eventCount' ),
				),
				'limit'           => 500,
			)
		);

		// Retry without fileName if the custom dimension is unavailable.
		if ( ! empty( $click_res['error'] ) ) {
			$click_res = self::run_custom_report(
				array(
					'dateRanges'      => array(
						array(
							'startDate' => $start_date,
							'endDate'   => $end_date,
						),
					),
					'dimensionFilter' => array(
						'filter' => array(
							'fieldName'    => 'eventName',
							'inListFilter' => array(
								'values' => array( 'affiliate_link_click', 'outbound_link_click', 'click', 'file_download' ),
							),
						),
					),
					'dimensions'      => array(
						array( 'name' => 'eventName' ),
						array( 'name' => 'linkUrl' ),
						array( 'name' => 'pagePath' ),
					),
					'metrics'         => array(
						array( 'name' => 'eventCount' ),
					),
					'limit'           => 500,
				)
			);
		}

		if ( ! empty( $click_res['error'] ) || is_wp_error( $click_res ) ) {
			return $out;
		}

		$out['ok'] = true;
		$maps      = array(
			'outbound'  => array(),
			'affiliate' => array(),
			'downloads' => array(),
			'inbound'   => array(),
		);

		$rows = $click_res['rows'] ?? array();
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		foreach ( $rows as $row ) {
			$event     = $row['dimensionValues'][0]['value'] ?? '';
			$link_url  = $row['dimensionValues'][1]['value'] ?? '';
			$page_path = $row['dimensionValues'][2]['value'] ?? '';
			$file_name = $row['dimensionValues'][3]['value'] ?? '';
			$clicks    = (int) ( $row['metricValues'][0]['value'] ?? 0 );

			if ( $clicks < 1 ) {
				continue;
			}

			$url = $link_url;
			if ( ( '' === $url || '(not set)' === $url ) && '' !== $file_name && '(not set)' !== $file_name ) {
				$url = $file_name;
			}
			if ( '' === $url || '(not set)' === $url ) {
				continue;
			}

			$type = self::classify_ga4_link_event( $event, $url );
			if ( '' === $type ) {
				continue;
			}

			$url = self::sanitize_link_report_url( $url );
			if ( ! isset( $maps[ $type ][ $url ] ) ) {
				$maps[ $type ][ $url ] = 0;
			}
			$maps[ $type ][ $url ] += $clicks;

			if ( '' !== $page_path && '(not set)' !== $page_path ) {
				if ( ! isset( $maps['inbound'][ $page_path ] ) ) {
					$maps['inbound'][ $page_path ] = 0;
				}
				$maps['inbound'][ $page_path ] += $clicks;
			}
		}

		foreach ( $maps as $bucket => $url_counts ) {
			$list = array();
			foreach ( $url_counts as $url => $count ) {
				$list[] = array(
					'link'   => $url,
					'clicks' => $count,
				);
			}
			usort(
				$list,
				function ( $a, $b ) {
					return $b['clicks'] - $a['clicks'];
				}
			);
			$out[ $bucket ] = $list;
		}

		return $out;
	}

	/**
	 * @param string $event GA4 event name.
	 * @param string $url   Link URL.
	 * @return string outbound|affiliate|downloads|''
	 */
	private static function classify_ga4_link_event( $event, $url ) {
		if ( 'file_download' === $event ) {
			return 'downloads';
		}
		if ( 'outbound_link_click' === $event ) {
			return 'outbound';
		}
		if ( 'affiliate_link_click' === $event ) {
			return 'affiliate';
		}
		if ( 'click' === $event ) {
			if ( class_exists( '\Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier' ) ) {
				$type = \Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier::classify( $url );
				if ( \Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier::TYPE_INTERNAL === $type
					|| \Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier::TYPE_IGNORED === $type ) {
					return '';
				}
				return \Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier::TYPE_AFFILIATE === $type ? 'affiliate' : 'outbound';
			}
			return 'outbound';
		}
		return '';
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	private static function sanitize_link_report_url( $url ) {
		if ( class_exists( '\Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier' ) ) {
			return \Sm_Pulse_Analytics\AffiliateLinkModule\Link_Classifier::sanitize_report_url( $url );
		}
		return esc_url_raw( $url ) ?: (string) $url;
	}

	/**
	 * Merge two link buckets keyed by URL (sum clicks).
	 *
	 * @param array $a First set of {link,clicks}.
	 * @param array $b Second set.
	 * @return array
	 */
	private static function merge_link_url_rows( array $a, array $b ) {
		$map = array();
		foreach ( array_merge( $a, $b ) as $row ) {
			$url = $row['link'] ?? ( $row['url'] ?? '' );
			if ( '' === $url ) {
				continue;
			}
			if ( ! isset( $map[ $url ] ) ) {
				$map[ $url ] = 0;
			}
			$map[ $url ] += (int) ( $row['clicks'] ?? 0 );
		}
		$out = array();
		foreach ( $map as $url => $clicks ) {
			$out[] = array(
				'link'   => $url,
				'clicks' => $clicks,
			);
		}
		usort(
			$out,
			function ( $x, $y ) {
				return $y['clicks'] <=> $x['clicks'];
			}
		);
		return array_slice( $out, 0, 100 );
	}

	/**
	 * Free: link-click report from GA4 + local telemetry.
	 */
	public static function handle_link_clicks_report_free( \WP_REST_Request $request ) {
		$start_date = sanitize_text_field( $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$end_date   = sanitize_text_field( $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' ) );
		return rest_ensure_response( self::fetch_link_clicks_report( $start_date, $end_date ) );
	}

	/**
	 * PRO: link-click report (local events merged in Pro fetch layer).
	 */
	public static function handle_link_clicks_report( \WP_REST_Request $request ) {

		$start_date = sanitize_text_field( $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$end_date   = sanitize_text_field( $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' ) );

			return rest_ensure_response( self::fetch_link_clicks_report( $start_date, $end_date ) );
	}

	/**
	 * Demographics report (Age & Gender) from GA4 / Pro fetch.
	 */
	public static function handle_demographics_report( \WP_REST_Request $request ) {
		$start_date = sanitize_text_field( $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$end_date   = sanitize_text_field( $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' ) );

		return rest_ensure_response( self::fetch_demographics_report( $start_date, $end_date ) );
	}

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public static function fetch_demographics_report( $start_date, $end_date ) {
		$empty_age = array(
			'labels' => array( '18-24', '25-34', '35-44', '45-54', '55-64', '65+' ),
			'values' => array( 0, 0, 0, 0, 0, 0 ),
		);
		$empty_gender = array(
			'labels' => array( 'Female', 'Male', 'Unknown' ),
			'values' => array( 0, 0, 0 ),
		);

			$cache_key = 'pulse_demographics_v1_' . md5( $start_date . '|' . $end_date . '|' . self::get_property_id() );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['source'] ) ) {
			return $cached;
		}

		$access_token = class_exists( __NAMESPACE__ . '\\GA_Auth' ) ? GA_Auth::get_access_token() : '';
		$property_id  = self::get_property_id();
		if ( ( is_array( $access_token ) && isset( $access_token['error'] ) ) || empty( $access_token ) || empty( $property_id ) ) {
			$report = array(
				'age'     => $empty_age,
				'gender'  => $empty_gender,
				'source'  => 'unavailable',
				'is_demo' => false,
			);
			set_transient( $cache_key, $report, 5 * MINUTE_IN_SECONDS );
			return $report;
		}

		$age_res = self::run_custom_report(
			array(
				'dateRanges' => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensions' => array(
					array( 'name' => 'userAgeBracket' ),
				),
				'metrics'    => array(
					array( 'name' => 'sessions' ),
				),
				'limit'      => 20,
			)
		);

		$gender_res = self::run_custom_report(
			array(
				'dateRanges' => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensions' => array(
					array( 'name' => 'userGender' ),
				),
				'metrics'    => array(
					array( 'name' => 'sessions' ),
				),
				'limit'      => 10,
			)
		);

		$age_failed    = ! empty( $age_res['error'] ) || is_wp_error( $age_res );
		$gender_failed = ! empty( $gender_res['error'] ) || is_wp_error( $gender_res );

		if ( $age_failed && $gender_failed ) {
			$report = array(
				'age'     => $empty_age,
				'gender'  => $empty_gender,
				'source'  => 'unavailable',
				'is_demo' => false,
			);
			set_transient( $cache_key, $report, 5 * MINUTE_IN_SECONDS );
			return $report;
		}

		$age_order = array( '18-24', '25-34', '35-44', '45-54', '55-64', '65+' );
		$age_map   = array_fill_keys( $age_order, 0 );
		if ( ! $age_failed && ! empty( $age_res['rows'] ) ) {
			foreach ( $age_res['rows'] as $row ) {
				$label = $row['dimensionValues'][0]['value'] ?? '';
				$val   = (int) ( $row['metricValues'][0]['value'] ?? 0 );
				if ( isset( $age_map[ $label ] ) ) {
					$age_map[ $label ] += $val;
				} elseif ( '65+' === $label || false !== strpos( $label, '65' ) ) {
					$age_map['65+'] += $val;
				}
			}
		}

		$gender_map = array(
			'female'  => 0,
			'male'    => 0,
			'unknown' => 0,
		);
		if ( ! $gender_failed && ! empty( $gender_res['rows'] ) ) {
			foreach ( $gender_res['rows'] as $row ) {
				$label = strtolower( (string) ( $row['dimensionValues'][0]['value'] ?? '' ) );
				$val   = (int) ( $row['metricValues'][0]['value'] ?? 0 );
				if ( 'female' === $label ) {
					$gender_map['female'] += $val;
				} elseif ( 'male' === $label ) {
					$gender_map['male'] += $val;
				} else {
					$gender_map['unknown'] += $val;
				}
			}
		}

		$age = array(
			'labels' => $age_order,
			'values' => array_values( $age_map ),
		);
		$gender = array(
			'labels' => array( 'Female', 'Male', 'Unknown' ),
			'values' => array( $gender_map['female'], $gender_map['male'], $gender_map['unknown'] ),
		);

		$sum = array_sum( $age['values'] ) + array_sum( $gender['values'] );
		$report = array(
			'age'     => $age,
			'gender'  => $gender,
			'source'  => $sum > 0 ? 'ga4' : 'unavailable',
			'is_demo' => false,
		);
		set_transient( $cache_key, $report, 10 * MINUTE_IN_SECONDS );
		return $report;
	}

	/**
	 * Campaign URL tracking — WooCommerce orders with UTM attribution (PRO).
	 */
	public static function handle_transactions( \WP_REST_Request $request ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return rest_ensure_response(
				array(
					'transactions' => array(),
					'total'        => 0,
					'page'         => 1,
					'per_page'     => 50,
				)
			);
		}

		$start_date = sanitize_text_field( $request->get_param( 'startDate' ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$end_date   = sanitize_text_field( $request->get_param( 'endDate' ) ?: gmdate( 'Y-m-d' ) );
		$campaign   = sanitize_text_field( $request->get_param( 'campaign' ) ?: 'all' );
		$medium     = sanitize_text_field( $request->get_param( 'medium' ) ?: 'all' );
		$source     = sanitize_text_field( $request->get_param( 'source' ) ?: 'all' );
		$search     = sanitize_text_field( $request->get_param( 'search' ) ?: '' );
		$page       = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );
		$per_page   = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 50 ) ) );

		$query_args = array(
			'type'     => 'shop_order',
			'status'   => array( 'wc-completed', 'wc-processing' ),
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'objects',
		);

		if ( $start_date && $end_date ) {
			$query_args['date_created'] = $start_date . '...' . $end_date;
		}

		if ( ! empty( $search ) && is_numeric( $search ) ) {
			$query_args['include'] = array( (int) $search );
		}

		$utm_order_ids = self::get_order_ids_matching_utm_filters( $campaign, $medium, $source );
		if ( is_array( $utm_order_ids ) ) {
			if ( empty( $utm_order_ids ) ) {
				return rest_ensure_response(
					array(
						'transactions' => array(),
						'total'        => 0,
						'page'         => $page,
						'per_page'     => $per_page,
					)
				);
			}
			if ( isset( $query_args['include'] ) ) {
				$query_args['include'] = array_values( array_intersect( $query_args['include'], $utm_order_ids ) );
			} else {
				$query_args['include'] = $utm_order_ids;
			}
		}

		$result = wc_get_orders( $query_args );
		$orders = ( is_object( $result ) && isset( $result->orders ) ) ? $result->orders : (array) $result;
		$total  = ( is_object( $result ) && isset( $result->total ) ) ? (int) $result->total : count( $orders );

		$order_ids = array();
		foreach ( $orders as $order ) {
			if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
				$order_ids[] = $order->get_id();
			}
		}

		$utm_map = self::get_utm_data_for_orders( $order_ids );
		$rows    = array();

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
				continue;
			}

			$order_id = $order->get_id();
			$utm      = $utm_map[ $order_id ] ?? array(
				'utm_campaign' => '',
				'utm_medium'   => '',
				'utm_source'   => '',
			);

			$campaign_val = $utm['utm_campaign'] ?: '--';
			$medium_val   = $utm['utm_medium'] ?: '--';
			$source_val   = $utm['utm_source'] ?: '--';

			if ( ! empty( $search ) && ! is_numeric( $search ) ) {
				$haystack = strtolower( $order_id . ' ' . $campaign_val . ' ' . $medium_val . ' ' . $source_val );
				if ( strpos( $haystack, strtolower( $search ) ) === false ) {
					continue;
				}
			}

			$rows[] = array(
				'id'       => (string) $order_id,
				'date'     => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'F j, Y g:i a' ) : '',
				'campaign' => $campaign_val,
				'medium'   => $medium_val,
				'source'   => $source_val,
				'total'    => html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total() ) ) ),
				'order_url' => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
			);
		}

		return rest_ensure_response(
			array(
				'transactions' => $rows,
				'total'        => $total,
				'page'         => $page,
				'per_page'     => $per_page,
			)
		);
	}

	/**
	 * @param string $campaign Campaign filter or "all".
	 * @param string $medium   Medium filter or "all".
	 * @param string $source   Source filter or "all".
	 * @return int[]|null Order IDs to restrict query, or null when no UTM filters apply.
	 */
	private static function get_order_ids_matching_utm_filters( $campaign, $medium, $source ) {
		if ( ( ! $campaign || 'all' === $campaign ) && ( ! $medium || 'all' === $medium ) && ( ! $source || 'all' === $source ) ) {
			return null;
		}

		global $wpdb;
		$table = PulseAnalytics_Storage::table_name( 'events' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array();
		}

		$where  = array( "entity_type = 'order'", 'entity_id IS NOT NULL', 'entity_id > 0' );
		$params = array();

		if ( $campaign && 'all' !== $campaign ) {
			$where[]  = 'attr_campaign = %s';
			$params[] = $campaign;
		}
		if ( $medium && 'all' !== $medium ) {
			$where[]  = 'attr_medium = %s';
			$params[] = $medium;
		}
		if ( $source && 'all' !== $source ) {
			$where[]  = 'attr_source = %s';
			$params[] = $source;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$sql = "SELECT DISTINCT entity_id FROM {$table} WHERE " . implode( ' AND ', $where );

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		$ids = $wpdb->get_col( $sql );
		// phpcs:enable
		return array_map( 'intval', $ids ?: array() );
	}

	/**
	 * @param int[] $order_ids WooCommerce order IDs.
	 * @return array<int, array{utm_campaign:string, utm_medium:string, utm_source:string}>
	 */
	private static function get_utm_data_for_orders( array $order_ids ) {
		if ( empty( $order_ids ) ) {
			return array();
		}

		global $wpdb;
		$table = PulseAnalytics_Storage::table_name( 'events' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array();
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
		$sql          = "SELECT entity_id AS order_id, attr_campaign AS utm_campaign, attr_medium AS utm_medium, attr_source AS utm_source
			FROM {$table}
			WHERE entity_type = 'order' AND entity_id IN ({$placeholders})
			ORDER BY id DESC";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $order_ids ), ARRAY_A );
		// phpcs:enable
		$map     = array();

		foreach ( $results as $row ) {
			$oid = (int) $row['order_id'];
			if ( isset( $map[ $oid ] ) ) {
				continue;
			}
			$map[ $oid ] = array(
				'utm_campaign' => (string) ( $row['utm_campaign'] ?? '' ),
				'utm_medium'   => (string) ( $row['utm_medium'] ?? '' ),
				'utm_source'   => (string) ( $row['utm_source'] ?? '' ),
			);
		}

		return $map;
	}

	public static function get_filter_campaigns() {
		return array();
	}

	public static function get_filter_mediums() {
		return array();
	}

	public static function get_filter_sources() {
		return array();
	}
}

add_action( 'rest_api_init', array( GA_Reporter::class, 'register_api_routes' ) );

class_alias( '\Sm_Pulse_Analytics\GA_Reporter', 'Sm_Pulse_Analytics_GA_Reporter' );
