<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

/**
 * GA4 AJAX Request Router & Redirect Handler.
 *
 * @package Sm_Pulse_Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PulseAnalytics_GA4_API {

	public static function init() {
		add_action( 'wp_ajax_sm_pulse_analytics_ga4_save_config', array( __CLASS__, 'handle_save_config' ) );
		add_action( 'wp_ajax_sm_pulse_analytics_ga4_disconnect', array( __CLASS__, 'handle_disconnect' ) );
	}

	public static function handle_save_config() {
		check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid security token.' ), 403 );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'sm_pulse_analytics_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}

		$measurement_id = filter_input( INPUT_POST, 'measurement_id', FILTER_SANITIZE_SPECIAL_CHARS );
		$property_id    = filter_input( INPUT_POST, 'property_id', FILTER_SANITIZE_SPECIAL_CHARS );
		$api_secret     = filter_input( INPUT_POST, 'api_secret', FILTER_SANITIZE_SPECIAL_CHARS );
		$stream_id       = filter_input( INPUT_POST, 'stream_id', FILTER_SANITIZE_SPECIAL_CHARS );

		\PulseAnalytics_GA4_Config::save_config(
			array(
				'measurement_id' => $measurement_id,
				'property_id'    => $property_id,
				'stream_id'      => $stream_id,
			)
		);

		if ( ! empty( $api_secret ) ) {
			delete_option( 'sm_pulse_analytics_ga4_api_secret' );
		}

		wp_send_json_success(
			array(
				'message' => 'GA4 settings saved successfully!',
			)
		);
	}

	public static function handle_disconnect() {
		check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid security token.' ), 403 );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'sm_pulse_analytics_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}

		\PulseAnalytics_GA4_OAuth::disconnect();
		wp_send_json_success( array( 'message' => 'Disconnected successfully.' ) );
	}
}

class_alias( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_API', 'Sm_Pulse_Analytics_GA4_API' );
