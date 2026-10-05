<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking AJAX handlers (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_API {

	public static function init() {
		add_action( 'wp_ajax_sm_pulse_analytics_get_affiliate_link_report', array( __CLASS__, 'ajax_get_report' ) );
	}

	public static function ajax_get_report() {
		if ( ! Affiliate_Link_Config::user_can_view() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
		}
		check_ajax_referer( 'sm_pulse_analytics_affiliate_link_nonce', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$start  = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$end    = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$compare = ! empty( $_POST['compare'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$force   = ! empty( $_POST['force'] );

		if ( ! $start || ! $end ) {
			$preset = sanitize_key( wp_unslash( $_POST['date_range'] ?? '30days' ) );
			$resolved = self::resolve_preset( $preset );
			$start    = $resolved['start'];
			$end      = $resolved['end'];
		}

		$data = Affiliate_Link_Service::get_report( $start, $end, $compare, $force );
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		$data = apply_filters( 'sm_pulse_analytics_affiliate_link_report_payload', $data, array(
			'start_date' => $start,
			'end_date'   => $end,
			'compare'    => $compare,
		) );

		wp_send_json_success( $data );
	}

	/**
	 * @param string $preset Preset key.
	 * @return array{start: string, end: string}
	 */
	private static function resolve_preset( $preset ) {
		$end = gmdate( 'Y-m-d' );
		switch ( $preset ) {
			case '7days':
				$start = gmdate( 'Y-m-d', strtotime( '-6 days' ) );
				break;
			case '90days':
				$start = gmdate( 'Y-m-d', strtotime( '-89 days' ) );
				break;
			default:
				$start = gmdate( 'Y-m-d', strtotime( '-29 days' ) );
		}
		return array(
			'start' => $start,
			'end'   => $end,
		);
	}
}
