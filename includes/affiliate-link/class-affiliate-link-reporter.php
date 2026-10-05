<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * GA4 + local telemetry affiliate report fetcher (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Reporter {

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array<string, mixed>
	 */
	public static function fetch_period( $start_date, $end_date ) {
		$ga4_rows   = self::fetch_ga4_events( $start_date, $end_date );
		$local_rows = self::fetch_local_events( $start_date, $end_date );

		$links    = self::merge_rows( $ga4_rows['affiliate'] ?? array(), $local_rows['affiliate'] ?? array() );
		$outbound = self::merge_rows( $ga4_rows['outbound'] ?? array(), $local_rows['outbound'] ?? array() );
		$sources  = self::merge_source_rows( $ga4_rows['sources'] ?? array(), $local_rows['sources'] ?? array() );

		return array(
			'top_links' => self::format_link_rows( $links ),
			'partners'  => self::group_by_partner( $links ),
			'sources'   => self::format_source_rows( $sources ),
			'outbound'  => self::format_link_rows( $outbound ),
		);
	}

	/**
	 * @param string $start_date Start.
	 * @param string $end_date   End.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function fetch_ga4_events( $start_date, $end_date ) {
		$out = array(
			'affiliate' => array(),
			'outbound'  => array(),
			'sources'   => array(),
		);

		if ( ! class_exists( '\Sm_Pulse_Analytics\GA_Reporter' ) && ! class_exists( 'PulseAnalytics_GA_Reporter' ) ) {
			return $out;
		}

		$reporter = class_exists( '\Sm_Pulse_Analytics\GA_Reporter' ) ? '\Sm_Pulse_Analytics\GA_Reporter' : 'PulseAnalytics_GA_Reporter';

		$res = $reporter::run_custom_report(
			array(
				'dateRanges' => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'eventName',
						'inListFilter' => array(
							'values' => array( 'affiliate_link_click', 'outbound_link_click', 'click' ),
						),
					),
				),
				'dimensions' => array(
					array( 'name' => 'eventName' ),
					array( 'name' => 'linkUrl' ),
					array( 'name' => 'pagePath' ),
				),
				'metrics'    => array(
					array( 'name' => 'eventCount' ),
				),
				'limit'      => 500,
			)
		);

		if ( is_wp_error( $res ) || ! empty( $res['error'] ) || empty( $res['rows'] ) ) {
			return $out;
		}

		foreach ( $res['rows'] as $row ) {
			$event  = $row['dimensionValues'][0]['value'] ?? '';
			$url    = $row['dimensionValues'][1]['value'] ?? '';
			$source = $row['dimensionValues'][2]['value'] ?? '';
			$clicks = (int) ( $row['metricValues'][0]['value'] ?? 0 );

			if ( '' === $url || '(not set)' === $url ) {
				continue;
			}

			$type = 'outbound_link_click' === $event ? 'outbound' : 'affiliate';
			if ( 'click' === $event ) {
				$type = Link_Classifier::classify( $url );
				if ( Link_Classifier::TYPE_INTERNAL === $type || Link_Classifier::TYPE_IGNORED === $type ) {
					continue;
				}
				$type = Link_Classifier::TYPE_AFFILIATE === $type ? 'affiliate' : 'outbound';
			}

			$key = md5( $url );
			if ( ! isset( $out[ $type ][ $key ] ) ) {
				$out[ $type ][ $key ] = array(
					'url'    => Link_Classifier::sanitize_report_url( $url ),
					'clicks' => 0,
				);
			}
			$out[ $type ][ $key ]['clicks'] += $clicks;

			if ( '' !== $source && '(not set)' !== $source ) {
				$sk = md5( $source );
				if ( ! isset( $out['sources'][ $sk ] ) ) {
					$out['sources'][ $sk ] = array(
						'path'   => $source,
						'clicks' => 0,
					);
				}
				$out['sources'][ $sk ]['clicks'] += $clicks;
			}
		}

		return array(
			'affiliate' => array_values( $out['affiliate'] ),
			'outbound'  => array_values( $out['outbound'] ),
			'sources'   => array_values( $out['sources'] ),
		);
	}

	/**
	 * @param string $start_date Start.
	 * @param string $end_date   End.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function fetch_local_events( $start_date, $end_date ) {
		$out = array(
			'affiliate' => array(),
			'outbound'  => array(),
			'sources'   => array(),
		);

		if ( ! class_exists( '\Sm_Pulse_Analytics\GA_Reporter' ) && ! class_exists( 'PulseAnalytics_GA_Reporter' ) ) {
			return $out;
		}

		$reporter = class_exists( '\Sm_Pulse_Analytics\GA_Reporter' ) ? '\Sm_Pulse_Analytics\GA_Reporter' : 'PulseAnalytics_GA_Reporter';
		if ( ! method_exists( $reporter, 'aggregate_link_click_events' ) ) {
			return $out;
		}

		$local = $reporter::aggregate_link_click_events( $start_date, $end_date );

		foreach ( array( 'affiliate', 'outbound' ) as $type ) {
			foreach ( $local[ $type ] ?? array() as $row ) {
				$url = $row['link'] ?? '';
				if ( '' === $url ) {
					continue;
				}
				$key = md5( $url );
				if ( ! isset( $out[ $type ][ $key ] ) ) {
					$out[ $type ][ $key ] = array(
						'url'    => Link_Classifier::sanitize_report_url( $url ),
						'clicks' => 0,
					);
				}
				$out[ $type ][ $key ]['clicks'] += (int) ( $row['clicks'] ?? 0 );
			}
		}

		return array(
			'affiliate' => array_values( $out['affiliate'] ),
			'outbound'  => array_values( $out['outbound'] ),
			'sources'   => array(),
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $a First set.
	 * @param array<int, array<string, mixed>> $b Second set.
	 * @return array<int, array<string, mixed>>
	 */
	private static function merge_rows( array $a, array $b ) {
		$map = array();
		foreach ( array_merge( $a, $b ) as $row ) {
			$url = $row['url'] ?? '';
			if ( '' === $url ) {
				continue;
			}
			$key = md5( $url );
			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = array(
					'url'    => $url,
					'clicks' => 0,
				);
			}
			$map[ $key ]['clicks'] += (int) ( $row['clicks'] ?? 0 );
		}
		$merged = array_values( $map );
		usort(
			$merged,
			function ( $x, $y ) {
				return $y['clicks'] <=> $x['clicks'];
			}
		);
		return $merged;
	}

	/**
	 * @param array<int, array<string, mixed>> $a First.
	 * @param array<int, array<string, mixed>> $b Second.
	 * @return array<int, array<string, mixed>>
	 */
	private static function merge_source_rows( array $a, array $b ) {
		$map = array();
		foreach ( array_merge( $a, $b ) as $row ) {
			$path = $row['path'] ?? '';
			if ( '' === $path ) {
				continue;
			}
			if ( ! isset( $map[ $path ] ) ) {
				$map[ $path ] = array(
					'path'   => $path,
					'clicks' => 0,
				);
			}
			$map[ $path ]['clicks'] += (int) ( $row['clicks'] ?? 0 );
		}
		$merged = array_values( $map );
		usort(
			$merged,
			function ( $x, $y ) {
				return $y['clicks'] <=> $x['clicks'];
			}
		);
		return $merged;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Raw rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function format_link_rows( array $rows ) {
		$out = array();
		foreach ( array_slice( $rows, 0, 50 ) as $row ) {
			$url    = $row['url'] ?? '';
			$domain = Link_Classifier::extract_domain( $url );
			$out[]  = array(
				'url'    => $url,
				'label'  => self::link_label( $url ),
				'domain' => $domain,
				'clicks' => (int) ( $row['clicks'] ?? 0 ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Source rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function format_source_rows( array $rows ) {
		$out = array();
		foreach ( array_slice( $rows, 0, 25 ) as $row ) {
			$out[] = array(
				'path'   => $row['path'] ?? '',
				'clicks' => (int) ( $row['clicks'] ?? 0 ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $links Link rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function group_by_partner( array $links ) {
		$map = array();
		foreach ( $links as $row ) {
			$domain  = Link_Classifier::extract_domain( $row['url'] ?? '' );
			$partner = Affiliate_Link_Config::partner_name_for_domain( $domain );
			if ( ! isset( $map[ $partner ] ) ) {
				$map[ $partner ] = array(
					'partner' => $partner,
					'domain'  => $domain,
					'clicks'  => 0,
					'links'   => 0,
				);
			}
			$map[ $partner ]['clicks'] += (int) ( $row['clicks'] ?? 0 );
			++$map[ $partner ]['links'];
		}
		$merged = array_values( $map );
		usort(
			$merged,
			function ( $a, $b ) {
				return $b['clicks'] <=> $a['clicks'];
			}
		);
		return array_slice( $merged, 0, 25 );
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	private static function link_label( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( $path && '/' !== $path ) {
			$segments = array_filter( explode( '/', trim( $path, '/' ) ) );
			if ( ! empty( $segments ) ) {
				$last = end( $segments );
				return ucwords( str_replace( array( '-', '_' ), ' ', $last ) );
			}
		}
		return Link_Classifier::extract_domain( $url ) ?: $url;
	}
}
