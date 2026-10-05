<?php
/**
 * Forms report API helper.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PulseAnalytics_Forms_API {

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public static function get_forms_report_data( $start_date = '', $end_date = '' ) {
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Reporter' ) ) {
			if ( empty( $start_date ) || empty( $end_date ) ) {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = wp_date( 'Y-m-d', strtotime( '-30 days' ) );
			}
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
}

// Backward-compatibility alias if PRO or third-party code references PulseAnalytics_Pro_Forms_API
