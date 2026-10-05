<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

/**
 * Dedicated Client-Side GA4 gtag.js Header Script Injector.
 *
 * @package Sm_Pulse_Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PulseAnalytics_GA4_Gtag {

	/**
	 * Guard against double registration of the injector (#32 ISSUE-006).
	 *
	 * @var bool
	 */
	private static $hooked = false;

	public static function init() {
		if ( self::$hooked ) {
			return;
		}
		self::$hooked = true;
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_snippet' ), 5 );
	}

	public static function enqueue_snippet() {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}

		/**
		 * Skip standard gtag on AMP (or other suppressed) requests (#43 / #32).
		 *
		 * @param bool $skip Whether to skip frontend tracking.
		 */
		if ( apply_filters( 'sm_pulse_analytics_skip_frontend_tracking', false ) ) {
			return;
		}

		if ( ! PulseAnalytics_GA4_Config::is_tracking_enabled() ) {
			return;
		}

		$measurement_id = PulseAnalytics_GA4_Config::get_measurement_id();
		if ( empty( $measurement_id ) ) {
			return;
		}

		if ( ! empty( $GLOBALS['sm_pulse_analytics_gtag_rendered'] ) ) {
			return;
		}
		$GLOBALS['sm_pulse_analytics_gtag_rendered'] = true;
		$rendered = true;

		$is_debug = PulseAnalytics_GA4_Config::is_debug_mode();

		$page_dimensions = apply_filters( 'sm_pulse_analytics_ga4_page_dimensions', array() );
		if ( ! is_array( $page_dimensions ) ) {
			$page_dimensions = array();
		}

		$config_params = apply_filters(
			'sm_pulse_analytics_ga4_gtag_config_params',
			array(
				'anonymize_ip'   => true,
				'debug_mode'     => $is_debug,
				'send_page_view' => empty( $page_dimensions ) ? true : false,
			)
		);
		if ( ! is_array( $config_params ) ) {
			$config_params = array(
				'anonymize_ip'   => true,
				'debug_mode'     => $is_debug,
				'send_page_view' => empty( $page_dimensions ) ? true : false,
			);
		}

		if ( $is_debug ) {
			$config_params['_dbg'] = 1;
		}

		$send_manual_pageview = ! empty( $page_dimensions ) || ( isset( $config_params['send_page_view'] ) && false === $config_params['send_page_view'] );
		if ( $send_manual_pageview ) {
			$config_params['send_page_view'] = false;
		}

		$page_view_params = $page_dimensions;
		if ( empty( $page_view_params['page_location'] ) && ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$scheme = is_ssl() ? 'https://' : 'http://';
			$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
			$uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$page_view_params['page_location'] = esc_url_raw( $scheme . $host . $uri );
		}

		if ( empty( $page_view_params['page_title'] ) ) {
			$page_view_params['page_title'] = wp_get_document_title();
		}

		$gtag_src = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $measurement_id );
		wp_enqueue_script(
			'pulse-analytics-gtag-js',
			$gtag_src,
			array(),
			'1.0.0',
			false
		);
		wp_script_add_data( 'pulse-analytics-gtag-js', 'async', true );

		wp_enqueue_script(
			'pulse-analytics-gtag-bootstrap',
			SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/ga4-gtag-bootstrap.js',
			array( 'pulse-analytics-gtag-js' ),
			defined( 'SM_PULSE_ANALYTICS_VERSION' ) ? SM_PULSE_ANALYTICS_VERSION : '1.0.1',
			false
		);
		wp_localize_script(
			'pulse-analytics-gtag-bootstrap',
			'pulseAnalyticsGtag',
			array(
				'measurementId'      => $measurement_id,
				'configParams'       => $config_params,
				'sendManualPageView' => (bool) $send_manual_pageview,
				'pageViewParams'     => $page_view_params,
			)
		);
	}
}

class_alias( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Gtag', 'Sm_Pulse_Analytics_GA4_Gtag' );
