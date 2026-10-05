<?php
/**
 * GA4 Measurement Protocol — server-side event dispatch.
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

class PulseAnalytics_GA4_Measurement_Protocol {

	/**
	 * Send a single GA4 event via Measurement Protocol.
	 *
	 * @param string $event_name GA4 event name (e.g. purchase, add_to_cart).
	 * @param array  $params     Event parameters.
	 * @param string $client_id  Optional client ID; generated when empty.
	 * @return bool
	 */
	public static function send_event( $event_name, array $params = array(), $client_id = '' ) {
		if ( ! PulseAnalytics_GA4_Config::is_tracking_enabled() ) {
			return false;
		}

		$measurement_id = PulseAnalytics_GA4_Config::get_measurement_id();
		$api_secret     = PulseAnalytics_GA4_Config::get_api_secret();

		if ( empty( $measurement_id ) || empty( $api_secret ) || empty( $event_name ) ) {
			return false;
		}

		if ( empty( $client_id ) ) {
			$client_id = self::get_client_id();
		}

		$params = apply_filters( 'sm_pulse_analytics_ga4_mp_event_params', $params, $event_name );
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$query_args = array(
			'measurement_id' => rawurlencode( $measurement_id ),
			'api_secret'     => rawurlencode( $api_secret ),
		);
		if ( PulseAnalytics_GA4_Config::is_debug_mode() ) {
			$query_args['debug_mode'] = 1;
		}

		$url = add_query_arg( $query_args, 'https://www.google-analytics.com/mp/collect' );

		$body = array(
			'client_id' => (string) $client_id,
			'events'    => array(
				array(
					'name'   => sanitize_key( $event_name ),
					'params' => $params,
				),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 8,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	/**
	 * Resolve a stable client ID for server-side hits.
	 *
	 * @return string
	 */
	public static function get_client_id() {
		if ( ! empty( $_COOKIE['sm_pulse_analytics_sid'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['sm_pulse_analytics_sid'] ) );
		}

		if ( ! empty( $_COOKIE['_ga'] ) ) {
			$raw   = sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) );
			$parts = explode( '.', $raw );
			$count = count( $parts );
			$cid   = '';
			if ( $count >= 4 ) {
				$cid = $parts[ $count - 2 ] . '.' . $parts[ $count - 1 ];
			} elseif ( 2 === $count ) {
				$cid = $parts[0] . '.' . $parts[1];
			}
			if ( ! empty( $cid ) ) {
				if ( ! headers_sent() ) {
					setcookie( 'sm_pulse_analytics_sid', $cid, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), false );
				}
				$_COOKIE['sm_pulse_analytics_sid'] = $cid;
				return $cid;
			}
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$sid = WC()->session->get( 'sm_pulse_analytics_client_id', '' );
			if ( ! empty( $sid ) ) {
				$_COOKIE['sm_pulse_analytics_sid'] = $sid;
				return (string) $sid;
			}
		}

		$sid = wp_generate_uuid4();
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'sm_pulse_analytics_client_id', $sid );
		}
		if ( ! headers_sent() ) {
			setcookie( 'sm_pulse_analytics_sid', $sid, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), false );
		}
		$_COOKIE['sm_pulse_analytics_sid'] = $sid;

		return $sid;
	}
}
