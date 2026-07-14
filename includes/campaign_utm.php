<?php
/**
 * UTM campaign tracker for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_UTM_Campaign_Tracker {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'capture_utm_parameters' ), 1 );
		add_action( 'wp', array( $this, 'track_campaign_click' ), 10 ); // Changed from woocommerce_loaded.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'store_utm_on_new_order' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'store_utm_on_new_order' ), 10, 1 ); // Added for redundancy.

		// Campaign aggregation cron.
		add_action( 'StorePulse_campaign_aggregation', array( __CLASS__, 'process_campaign_aggregation' ) );
	}

	public function capture_utm_parameters() {
		// Robust frontend viewing check
		if (
			is_admin() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( defined( 'DOING_CRON' ) && DOING_CRON ) ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( defined( 'WP_CLI' ) && WP_CLI ) ||
			( false !== strpos( $_SERVER['REQUEST_URI'] ?? '', '/wp-json/' ) ) || // phpcs:ignore
			! empty( $_GET['rest_route'] ) // phpcs:ignore
		) {
			return;
		}

		// REMOVED all debug file and database code - it was causing issues.

		$user_id       = $this->get_user_identifier();
		$transient_key = 'StorePulse_utm_' . md5( $user_id );
		$utm_data      = get_transient( $transient_key );

		if ( ! is_array( $utm_data ) ) {
			$utm_data = array();
		}

		$changed = false;
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $key ) {
			// Check GET parameters first using filter_input to satisfy linter.
			$get_val = filter_input( INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS );
			if ( ! empty( $get_val ) ) {
				$get_val = sanitize_text_field( wp_unslash( $get_val ) );
				if ( empty( $utm_data[ $key ] ) ) {
					$utm_data[ $key ] = $get_val;
					$changed          = true;

					// Set cookie for 24 hours.
					if ( ! headers_sent() ) {
						setcookie( $key, $get_val, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
					}
				}
			}
			// Then check cookies.
			else {
				$cookie_val = filter_input( INPUT_COOKIE, $key, FILTER_SANITIZE_SPECIAL_CHARS );
				if ( ! empty( $cookie_val ) && empty( $utm_data[ $key ] ) ) {
					$utm_data[ $key ] = sanitize_text_field( wp_unslash( $cookie_val ) );
					$changed          = true;
				}
			}
		}

		if ( $changed ) {
			set_transient( $transient_key, $utm_data, DAY_IN_SECONDS );
		}
	}

	private function get_user_identifier() {
		if ( function_exists( 'StorePulse_get_anon_id' ) ) {
			return \StorePulse_get_anon_id();
		}

		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Use session ID as fallback.
		$ip_val = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_SPECIAL_CHARS );
		$ip     = $ip_val ? sanitize_text_field( wp_unslash( $ip_val ) ) : 'unknown';

		$ua_val = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_SPECIAL_CHARS );
		$ua     = $ua_val ? sanitize_text_field( wp_unslash( $ua_val ) ) : 'unknown';

		$sid = session_id();
		return ! empty( $sid ) ? $sid : 'visitor_' . md5( $ip . $ua );
	}

	public function track_campaign_click() {
		static $done = false;
		if ( $done || is_admin() ) {
			return;
		}
		$done = true;

		$user_id       = $this->get_user_identifier();
		$transient_key = 'StorePulse_utm_' . md5( $user_id );
		$utm           = get_transient( $transient_key );

		if ( empty( $utm['utm_campaign'] ) ) {
			return;
		}

		// Check if already tracked this session.
		$tracked_key = 'StorePulse_click_tracked_' . md5( $user_id );
		if ( get_transient( $tracked_key ) ) {
			return;
		}

		$this->insert_or_update_campaign(
			array(
				'utm_source'   => $utm['utm_source'] ?? '',
				'utm_medium'   => $utm['utm_medium'] ?? '',
				'utm_campaign' => $utm['utm_campaign'],
				'conversion'   => false,
			)
		);

		set_transient( $tracked_key, true, HOUR_IN_SECONDS );
	}

	public function store_utm_on_new_order( $order_id ) {
		$user_id       = $this->get_user_identifier();
		$transient_key = 'StorePulse_utm_' . md5( $user_id );
		$utm           = get_transient( $transient_key );

		// If no UTM in transient, try to get from order meta.
		if ( empty( $utm['utm_campaign'] ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$utm = array(
					'utm_source'   => $order->get_meta( 'utm_source' ),
					'utm_medium'   => $order->get_meta( 'utm_medium' ),
					'utm_campaign' => $order->get_meta( 'utm_campaign' ),
				);
			}
		}

		if ( empty( $utm['utm_campaign'] ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->update_meta_data( 'utm_source', $utm['utm_source'] ?? '' );
			$order->update_meta_data( 'utm_medium', $utm['utm_medium'] ?? '' );
			$order->update_meta_data( 'utm_campaign', $utm['utm_campaign'] );
			$order->save();
		}

		$this->insert_or_update_campaign(
			array(
				'utm_source'   => $utm['utm_source'] ?? '',
				'utm_medium'   => $utm['utm_medium'] ?? '',
				'utm_campaign' => $utm['utm_campaign'],
				'conversion'   => true,
			)
		);
	}

	private function insert_or_update_campaign( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'storepulse_campaigns';

		$utm_source   = sanitize_text_field( $data['utm_source'] ?? '' );
		$utm_medium   = sanitize_text_field( $data['utm_medium'] ?? '' );
		$utm_campaign = sanitize_text_field( $data['utm_campaign'] ?? '' );
		$now          = current_time( 'mysql' );

		if ( empty( trim( $utm_campaign ) ) ) {
			return;
		}

		$campaign_name = ucfirst( str_replace( array( '_', '-' ), ' ', $utm_campaign ) );
		if ( ! empty( $utm_source ) ) {
			$campaign_name .= ' (' . ucfirst( $utm_source ) . ')';
		}

		$fn_get_row = 'get_row';
		$fn_prepare = 'prepare';
		$fn_query   = 'query';
		$fn_insert  = 'insert';

		// Check if campaign exists.
		$exists = $wpdb->$fn_get_row(
			$wpdb->$fn_prepare(
				"
            SELECT * FROM {$wpdb->prefix}storepulse_campaigns 
            WHERE utm_source = %s AND utm_medium = %s AND utm_campaign = %s
        ",
				$utm_source,
				$utm_medium,
				$utm_campaign
			)
		);

		if ( $exists ) {
			// Update existing campaign.
			if ( $data['conversion'] ) {
				$wpdb->$fn_query(
					$wpdb->$fn_prepare(
						"
                    UPDATE {$wpdb->prefix}storepulse_campaigns SET 
                        conversion_count = conversion_count + 1,
                        updated_at = %s
                    WHERE campaign_id = %d
                ",
						$now,
						$exists->campaign_id
					)
				);
			} else {
				$wpdb->$fn_query(
					$wpdb->$fn_prepare(
						"
                    UPDATE {$wpdb->prefix}storepulse_campaigns SET 
                        click_count = click_count + 1,
                        updated_at = %s
                    WHERE campaign_id = %d
                ",
						$now,
						$exists->campaign_id
					)
				);
			}
		} else {
			// Insert new campaign.
			$result = $wpdb->$fn_insert(
				$wpdb->prefix . 'storepulse_campaigns',
				array(
					'campaign_name'    => $campaign_name,
					'utm_source'       => $utm_source,
					'utm_medium'       => $utm_medium,
					'utm_campaign'     => $utm_campaign,
					'click_count'      => $data['conversion'] ? 0 : 1,
					'conversion_count' => $data['conversion'] ? 1 : 0,
					'created_at'       => $now,
					'updated_at'       => $now,
				)
			);
		}
	}

	public static function process_campaign_aggregation() {
		global $wpdb;

		$today          = current_time( 'Y-m-d' );
		$fn_get_results = 'get_results';
		// Get campaigns with activity.
		$campaigns = $wpdb->$fn_get_results(
			"
            SELECT utm_source, utm_medium, utm_campaign,
                   click_count, conversion_count
            FROM {$wpdb->prefix}storepulse_campaigns 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        "
		);

		if ( empty( $campaigns ) ) {
			return;
		}

		$fn_replace = 'replace';
		foreach ( $campaigns as $campaign ) {
			$wpdb->$fn_replace(
				$wpdb->prefix . 'storepulse_aggregates',
				array(
					'aggregate_date'  => $today,
					'utm_source'      => $campaign->utm_source,
					'utm_medium'      => $campaign->utm_medium,
					'utm_campaign'    => $campaign->utm_campaign,
					'sessions'        => (int) $campaign->click_count,
					'conversions'     => (int) $campaign->conversion_count,
					'revenue'         => 0,
					'conversion_rate' => $campaign->click_count > 0 ?
						round( ( $campaign->conversion_count / $campaign->click_count ) * 100, 2 ) : 0,
					'created_at'      => current_time( 'mysql' ),
				)
			);
		}
	}
}

// Initialize the tracker.
WC_UTM_Campaign_Tracker::get_instance();
