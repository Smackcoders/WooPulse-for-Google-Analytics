<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate link report service (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Service {

	const CACHE_PREFIX = 'pulse_affiliate_link_report_';

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @param bool   $compare    Compare previous period.
	 * @param bool   $force      Skip cache.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_report( $start_date, $end_date, $compare = true, $force = false ) {
		if ( ! Affiliate_Link_Config::is_active() ) {
			return new \WP_Error( 'inactive', __( 'Affiliate Link Tracking is not enabled.', 'smackcoders-pulse-analytics-for-woocommerce' ) );
		}

		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			return new \WP_Error( 'invalid_dates', __( 'Invalid date range.', 'smackcoders-pulse-analytics-for-woocommerce' ) );
		}

		$cache_key = self::CACHE_PREFIX . md5( $start_date . $end_date . ( $compare ? '1' : '0' ) );
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				$cached['from_cache'] = true;
				return $cached;
			}
		}

		$current = Affiliate_Link_Reporter::fetch_period( $start_date, $end_date );
		$result  = array(
			'date_range' => array(
				'start' => $start_date,
				'end'   => $end_date,
			),
			'summary'    => self::build_summary( $current ),
			'top_links'  => $current['top_links'] ?? array(),
			'partners'   => $current['partners'] ?? array(),
			'sources'    => $current['sources'] ?? array(),
			'outbound'   => $current['outbound'] ?? array(),
			'from_cache' => false,
		);

		if ( $compare ) {
			$days     = max( 1, (int) round( ( strtotime( $end_date ) - strtotime( $start_date ) ) / DAY_IN_SECONDS ) + 1 );
			$prev_end = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $start_date ) ) );
			$prev_start = gmdate( 'Y-m-d', strtotime( '-' . $days . ' days', strtotime( $start_date ) ) );
			$previous   = Affiliate_Link_Reporter::fetch_period( $prev_start, $prev_end );
			$result['comparison'] = array(
				'date_range' => array( 'start' => $prev_start, 'end' => $prev_end ),
				'summary'    => self::compare_summaries( $result['summary'], self::build_summary( $previous ) ),
			);
		}

		set_transient( $cache_key, $result, Affiliate_Link_Config::CACHE_TTL );
		return $result;
	}

	/**
	 * @param array<string, mixed> $period Period data.
	 * @return array<string, mixed>
	 */
	private static function build_summary( array $period ) {
		$affiliate_clicks = 0;
		$outbound_clicks  = 0;
		foreach ( $period['top_links'] ?? array() as $row ) {
			$affiliate_clicks += (int) ( $row['clicks'] ?? 0 );
		}
		foreach ( $period['outbound'] ?? array() as $row ) {
			$outbound_clicks += (int) ( $row['clicks'] ?? 0 );
		}

		$top_link    = $period['top_links'][0] ?? null;
		$top_partner = $period['partners'][0] ?? null;

		return array(
			'affiliate_clicks'  => $affiliate_clicks,
			'outbound_clicks'   => $outbound_clicks,
			'unique_links'      => count( $period['top_links'] ?? array() ),
			'top_link'          => $top_link['label'] ?? '—',
			'top_link_clicks'   => (int) ( $top_link['clicks'] ?? 0 ),
			'top_partner'       => $top_partner['partner'] ?? '—',
			'top_partner_clicks'=> (int) ( $top_partner['clicks'] ?? 0 ),
		);
	}

	/**
	 * @param array<string, mixed> $current  Current summary.
	 * @param array<string, mixed> $previous Previous summary.
	 * @return array<string, mixed>
	 */
	private static function compare_summaries( array $current, array $previous ) {
		$out = array();
		foreach ( array( 'affiliate_clicks', 'outbound_clicks' ) as $key ) {
			$cur  = (float) ( $current[ $key ] ?? 0 );
			$prev = (float) ( $previous[ $key ] ?? 0 );
			$delta = $cur - $prev;
			$pct   = 0.0 === $prev ? ( 0.0 === $cur ? 0.0 : 100.0 ) : ( $delta / $prev ) * 100;
			$out[ $key ] = array(
				'current'   => $cur,
				'previous'  => $prev,
				'percent'   => round( $pct, 1 ),
				'direction' => $delta > 0 ? 'up' : ( $delta < 0 ? 'down' : 'flat' ),
			);
		}
		return $out;
	}
}
