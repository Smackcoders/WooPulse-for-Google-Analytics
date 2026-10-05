<?php
/**
 * DUMMY DATA GENERATOR FOR FORMS CONVERSION REPORTING
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics\PulseAnalyticsCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Forms_Dummy_Data {

	/**
	 * Normalize date strings or relative preset identifiers to YYYY-MM-DD bounds.
	 *
	 * @param string $start_date Start date or preset string.
	 * @param string $end_date   End date.
	 * @return array{0: string, 1: string}
	 */
	public static function normalize_dates( $start_date = '', $end_date = '' ) {
		$start_date = trim( (string) $start_date );
		$end_date   = trim( (string) $end_date );

		$lower_start = strtolower( $start_date );
		if ( false !== strpos( $lower_start, '7' ) ) {
			return array( gmdate( 'Y-m-d', strtotime( '-6 days' ) ), gmdate( 'Y-m-d' ) );
		}
		if ( false !== strpos( $lower_start, '90' ) ) {
			return array( gmdate( 'Y-m-d', strtotime( '-89 days' ) ), gmdate( 'Y-m-d' ) );
		}
		if ( false !== strpos( $lower_start, '30' ) || false !== strpos( $lower_start, 'month' ) ) {
			return array( gmdate( 'Y-m-d', strtotime( '-29 days' ) ), gmdate( 'Y-m-d' ) );
		}

		if ( empty( $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			$start_date = gmdate( 'Y-m-d', strtotime( '-29 days' ) );
		}
		if ( empty( $end_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			$end_date = gmdate( 'Y-m-d' );
		}

		if ( strtotime( $start_date ) > strtotime( $end_date ) ) {
			$tmp        = $start_date;
			$start_date = $end_date;
			$end_date   = $tmp;
		}

		return array( $start_date, $end_date );
	}

	/**
	 * Returns dynamic dummy report data for Forms Conversion UI (page=pulse-analytics-forms).
	 *
	 * @param string $start_date Y-m-d or relative preset string.
	 * @param string $end_date   Y-m-d.
	 * @return array<string, mixed>
	 */
	public static function get_dummy_report( $start_date = '', $end_date = '' ) {
		list( $start_date, $end_date ) = self::normalize_dates( $start_date, $end_date );

		// Generate daily trend for form views, starts, and submissions for the requested date span
		$trend_labels = array();
		$views_trend  = array();
		$starts_trend = array();
		$submits_trend= array();

		$cursor = strtotime( $start_date . ' UTC' );
		$end_ts = strtotime( $end_date . ' UTC' );

		if ( false !== $cursor && false !== $end_ts ) {
			while ( $cursor <= $end_ts ) {
				$date_str       = gmdate( 'Y-m-d', $cursor );
				$trend_labels[] = $date_str;

				// Deterministic daily values based on date string hash for smooth curves
				$seed    = abs( crc32( $date_str ) );
				$views   = 400 + ( $seed % 280 );
				$starts  = (int) round( $views * ( 0.55 + ( ( $seed % 15 ) / 100 ) ) );
				$submits = (int) round( $starts * ( 0.32 + ( ( $seed % 12 ) / 100 ) ) );

				$views_trend[]  = $views;
				$starts_trend[] = $starts;
				$submits_trend[]= $submits;

				$cursor = strtotime( '+1 day', $cursor );
			}
		}

		$tot_views   = array_sum( $views_trend );
		$tot_starts  = array_sum( $starts_trend );
		$tot_submits = array_sum( $submits_trend );

		$overall_conv = $tot_views > 0 ? min( 100.0, round( ( $tot_submits / $tot_views ) * 100, 1 ) ) . '%' : '0.0%';

		// Proportional breakdown across form providers
		$form_defs = array(
			array(
				'id'       => 'woocommerce_checkout',
				'name'     => 'WooCommerce Checkout Form',
				'provider' => 'WooCommerce',
				'weight'   => 0.43,
				'page'     => '/checkout/',
			),
			array(
				'id'       => 'wpforms_contact',
				'name'     => 'Contact Us Inquiry Form',
				'provider' => 'WPForms',
				'weight'   => 0.22,
				'page'     => '/contact-us/',
			),
			array(
				'id'       => 'gravity_newsletter',
				'name'     => 'Newsletter Subscription Form',
				'provider' => 'Gravity Forms',
				'weight'   => 0.19,
				'pages'    => array(
					array( 'url' => '/', 'ratio' => 0.6 ),
					array( 'url' => '/blog/', 'ratio' => 0.4 ),
				),
			),
			array(
				'id'       => 'ninja_quote_request',
				'name'     => 'Custom Quote Request Form',
				'provider' => 'Ninja Forms',
				'weight'   => 0.10,
				'page'     => '/services/',
			),
			array(
				'id'       => 'elementor_reg_form',
				'name'     => 'Member Registration Form',
				'provider' => 'Elementor Forms',
				'weight'   => 0.06,
				'page'     => '/register/',
			),
		);

		$rows = array();
		foreach ( $form_defs as $def ) {
			$f_views   = (int) round( $tot_views * $def['weight'] );
			$f_starts  = (int) round( $tot_starts * $def['weight'] );
			$f_submits = (int) round( $tot_submits * $def['weight'] );
			$f_conv    = $f_views > 0 ? min( 100.0, round( ( $f_submits / $f_views ) * 100, 1 ) ) . '%' : '0.0%';

			$top_pages = array();
			if ( isset( $def['pages'] ) && is_array( $def['pages'] ) ) {
				foreach ( $def['pages'] as $p ) {
					$top_pages[] = array(
						'page_url' => $p['url'],
						'count'    => (int) round( $f_submits * $p['ratio'] ),
					);
				}
			} elseif ( isset( $def['page'] ) ) {
				$top_pages[] = array(
					'page_url' => $def['page'],
					'count'    => $f_submits,
				);
			}

			$rows[] = array(
				'form_id'       => $def['id'],
				'form_name'     => $def['name'],
				'form_provider' => $def['provider'],
				'views'         => $f_views,
				'starts'        => $f_starts,
				'submissions'   => $f_submits,
				'conv_rate'     => $f_conv,
				'top_pages'     => $top_pages,
			);
		}

		return array(
			'kpis'              => array(
				'views'       => $tot_views,
				'starts'      => $tot_starts,
				'submissions' => $tot_submits,
				'conv_rate'   => $overall_conv,
			),
			'rows'              => $rows,
			'trend'             => array(
				'labels'      => $trend_labels,
				'values'      => $submits_trend,
				'views'       => $views_trend,
				'starts'      => $starts_trend,
				'submissions' => $submits_trend,
			),
			'sources'           => array(
				'ga4_forms' => count( $rows ),
				'mode'      => 'dummy',
			),
			'custom_dimensions' => array(
				'required'  => class_exists( __NAMESPACE__ . '\\Forms_Config' ) ? Forms_Config::required_custom_dimensions() : array( 'form_id', 'form_provider', 'form_name' ),
				'available' => true,
			),
			'is_dummy'          => true,
		);
	}
}
