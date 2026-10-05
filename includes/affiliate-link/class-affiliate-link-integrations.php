<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Site Notes + Advanced Reporting / AI integrations (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Integrations {

	public static function init() {
		add_filter( 'sm_pulse_analytics_report_payload', array( __CLASS__, 'attach_to_advanced_report' ), 12, 2 );
		add_filter( 'sm_pulse_analytics_ai_analytics_context', array( __CLASS__, 'attach_ai_context' ), 12, 3 );
	}

	/**
	 * @param array<string, mixed> $payload Report payload.
	 * @param array<string, mixed> $config  Request config.
	 * @return array<string, mixed>
	 */
	public static function attach_to_advanced_report( $payload, $config ) {
		if ( ! Affiliate_Link_Config::is_active() || ! Affiliate_Link_Config::user_can_view() ) {
			return $payload;
		}

		$start = $payload['date_range']['start'] ?? ( $config['start_date'] ?? '' );
		$end   = $payload['date_range']['end'] ?? ( $config['end_date'] ?? '' );
		if ( ! $start || ! $end ) {
			return $payload;
		}

		$report = Affiliate_Link_Service::get_report( $start, $end, false );
		if ( is_wp_error( $report ) ) {
			return $payload;
		}

		$payload['affiliate_links'] = array(
			'summary'   => $report['summary'] ?? array(),
			'top_links' => array_slice( $report['top_links'] ?? array(), 0, 5 ),
			'partners'  => array_slice( $report['partners'] ?? array(), 0, 5 ),
		);

		return $payload;
	}

	/**
	 * @param array<string, mixed> $context AI context.
	 * @param array<string, mixed> $intent  Intent.
	 * @param array<string, mixed> $config  Config.
	 * @return array<string, mixed>
	 */
	public static function attach_ai_context( $context, $intent, $config ) {
		if ( ! Affiliate_Link_Config::is_active() || ! Affiliate_Link_Config::user_can_view() ) {
			return $context;
		}

		$start = $config['start_date'] ?? '';
		$end   = $config['end_date'] ?? '';

		if ( class_exists( '\Sm_Pulse_Analytics\AdvancedReportingModule\Report_Query_Builder' ) ) {
			$resolved = \Sm_Pulse_Analytics\AdvancedReportingModule\Report_Query_Builder::resolve_date_range(
				$config['date_range'] ?? '30days',
				$start,
				$end
			);
			$start = $resolved['start'];
			$end   = $resolved['end'];
		}

		if ( ! $start || ! $end ) {
			return $context;
		}

		$report = Affiliate_Link_Service::get_report( $start, $end, false );
		if ( is_wp_error( $report ) ) {
			return $context;
		}

		$summary = $report['summary'] ?? array();
		$context['affiliate_links'] = array(
			'affiliate_clicks' => (int) ( $summary['affiliate_clicks'] ?? 0 ),
			'outbound_clicks'  => (int) ( $summary['outbound_clicks'] ?? 0 ),
			'top_link'         => $summary['top_link'] ?? '',
			'top_partner'      => $summary['top_partner'] ?? '',
			'top_links'        => array_slice( $report['top_links'] ?? array(), 0, 5 ),
		);

		if ( ! empty( $context['summary_text'] ) && in_array( $intent['type'] ?? '', array( 'traffic_change', 'link_performance' ), true ) ) {
			$context['summary_text'] .= ' ' . sprintf(
				/* translators: 1: affiliate clicks, 2: top link label */
				__( 'Affiliate link tracking recorded %1$d affiliate clicks in this period. Top link: %2$s.', 'smackcoders-pulse-analytics-for-woocommerce' ),
				(int) ( $summary['affiliate_clicks'] ?? 0 ),
				$summary['top_link'] ?? '—'
			);
		}

		unset( $intent );
		return $context;
	}
}
