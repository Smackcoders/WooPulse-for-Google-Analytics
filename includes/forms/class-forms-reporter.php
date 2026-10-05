<?php
/**
 * Forms conversion report — GA4 Data API & fallback reporting.
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

class Forms_Reporter {

	/**
	 * GA4 event names mapped to the report metric they contribute to.
	 * `pulse_form_*` are pre-rename historical names and must keep counting.
	 */
	const FORM_EVENT_METRIC_MAP = array(
		'form_view'         => 'views',
		'form_impression'   => 'views',
		'pulse_form_view'   => 'views',
		'form_start'        => 'starts',
		'pulse_form_start'  => 'starts',
		'form_submit'       => 'submissions',
		'pulse_form_submit' => 'submissions',
	);

	const FORM_EVENT_NAMES = array(
		'form_view',
		'form_impression',
		'pulse_form_view',
		'form_start',
		'pulse_form_start',
		'form_submit',
		'pulse_form_submit',
	);

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public static function fetch_report( $start_date, $end_date ) {
		$start_date = sanitize_text_field( $start_date );
		$end_date   = sanitize_text_field( $end_date );

		$cache_key = 'pulse_forms_report_v7_' . md5( $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['kpis'] ) ) {
			return $cached;
		}

		// Empty totals array = GA4 unreachable. Zero-valued totals = no form events.
		$ga_totals = self::fetch_ga4_event_totals( $start_date, $end_date );

		if ( empty( $ga_totals ) ) {
			if ( self::demo_fallback_enabled() && class_exists( __NAMESPACE__ . '\\Forms_Dummy_Data' ) ) {
				return Forms_Dummy_Data::get_dummy_report( $start_date, $end_date );
			}
			return self::empty_report( 'ga4_unavailable' );
		}

		$ga_rows = self::fetch_ga4_form_events( $start_date, $end_date );
		$rows    = self::format_ga_rows( $ga_rows );
		$trend   = self::fetch_submission_trend( $start_date, $end_date );

		$total_views   = 0;
		$total_starts  = 0;
		$total_submits = 0;
		foreach ( $rows as $row ) {
			$total_views   += (int) ( $row['views'] ?? 0 );
			$total_starts  += (int) ( $row['starts'] ?? 0 );
			$total_submits += (int) ( $row['submissions'] ?? 0 );
		}

		// Prefer GA4 event-level totals when custom dimensions are missing or incomplete.
		$total_views   = max( $total_views, (int) ( $ga_totals['views'] ?? 0 ) );
		$total_starts  = max( $total_starts, (int) ( $ga_totals['starts'] ?? 0 ) );
		$total_submits = max( $total_submits, (int) ( $ga_totals['submissions'] ?? 0 ) );

		if ( empty( $rows ) && ( $total_views > 0 || $total_starts > 0 || $total_submits > 0 ) ) {
			$rows = array(
				array(
					'form_id'       => 'site_forms',
					'form_name'     => 'Active Site Forms',
					'form_provider' => '(not set)',
					'views'         => $total_views,
					'starts'        => $total_starts,
					'submissions'   => $total_submits,
					'conv_rate'     => $total_views > 0 ? min( 100.0, round( ( $total_submits / $total_views ) * 100, 1 ) ) . '%' : ( $total_submits > 0 ? '100.0%' : '0.0%' ),
				),
			);
		} elseif ( count( $rows ) === 1 ) {
			$rows[0]['views']       = max( (int) $rows[0]['views'], $total_views );
			$rows[0]['starts']      = max( (int) $rows[0]['starts'], $total_starts );
			$rows[0]['submissions'] = max( (int) $rows[0]['submissions'], $total_submits );
			$v                      = (int) $rows[0]['views'];
			$submits                = (int) $rows[0]['submissions'];
			if ( $v > 0 ) {
				$rows[0]['conv_rate'] = min( 100.0, round( ( $submits / $v ) * 100, 1 ) ) . '%';
			} else {
				$rows[0]['conv_rate'] = $submits > 0 ? '100.0%' : '0.0%';
			}
			if ( empty( $rows[0]['form_provider'] ) || in_array( strtolower( $rows[0]['form_provider'] ), array( 'notset', '(not set)', 'generic', 'wordpress' ), true ) ) {
				$rows[0]['form_provider'] = '(not set)';
			}
		}

		$report = array(
			'kpis'              => array(
				'views'       => $total_views,
				'starts'      => $total_starts,
				'submissions' => $total_submits,
				'conv_rate'   => $total_views > 0 ? min( 100.0, round( ( $total_submits / $total_views ) * 100, 1 ) ) . '%' : ( $total_submits > 0 ? '100.0%' : '0.0%' ),
			),
			'rows'              => $rows,
			'trend'             => $trend,
			'sources'           => array(
				'ga4_forms' => count( $ga_rows ),
				'mode'      => 'ga4_only',
			),
			'custom_dimensions' => array(
				'required'  => Forms_Config::required_custom_dimensions(),
				'available' => ! empty( $ga_rows ),
			),
			'is_dummy'          => false,
			'notice'            => '',
		);

		set_transient( $cache_key, $report, 5 * MINUTE_IN_SECONDS );

		return $report;
	}

	/**
	 * Demo data is only a stand-in for an unreachable GA4 connection.
	 *
	 * @return bool
	 */
	private static function demo_fallback_enabled() {
		return (bool) apply_filters( 'sm_pulse_analytics_forms_demo_data', true );
	}

	/**
	 * @param string $notice Machine-readable reason surfaced to the UI.
	 * @return array
	 */
	private static function empty_report( $notice = '' ) {
		return array(
			'kpis'              => array(
				'views'       => 0,
				'starts'      => 0,
				'submissions' => 0,
				'conv_rate'   => '0.0%',
			),
			'rows'              => array(),
			'trend'             => array(
				'labels'      => array(),
				'values'      => array(),
				'views'       => array(),
				'starts'      => array(),
				'submissions' => array(),
			),
			'sources'           => array(
				'ga4_forms' => 0,
				'mode'      => 'ga4_only',
			),
			'custom_dimensions' => array(
				'required'  => Forms_Config::required_custom_dimensions(),
				'available' => false,
			),
			'is_dummy'          => false,
			'notice'            => sanitize_key( $notice ),
		);
	}

	/**
	 * Format GA4 form aggregates into report table rows.
	 *
	 * @param array<int, array> $ga GA4 rows.
	 * @return array<int, array>
	 */
	public static function format_ga_rows( $ga ) {
		return self::merge_sources( array(), $ga );
	}

	/**
	 * @param array<string, array> $local Local aggregates keyed by provider:form_id.
	 * @param array<int, array>    $ga    GA4 rows.
	 * @return array<int, array>
	 */
	public static function merge_sources( $local, $ga ) {
		$merged = is_array( $local ) ? $local : array();

		foreach ( $ga as $ga_row ) {
			$form_id  = sanitize_text_field( $ga_row['form_id'] ?? '' );
			$provider = sanitize_key( $ga_row['form_provider'] ?? 'generic' );
			if ( '' === $form_id ) {
				continue;
			}

			$key = $provider . ':' . $form_id;
			if ( ! isset( $merged[ $key ] ) ) {
				$merged[ $key ] = array(
					'form_id'       => $form_id,
					'form_name'     => sanitize_text_field( $ga_row['form_name'] ?? $form_id ),
					'form_provider' => $provider,
					'views'         => 0,
					'starts'        => 0,
					'submissions'   => 0,
					'top_pages'     => array(),
				);
			}

			$ga_name = sanitize_text_field( $ga_row['form_name'] ?? '' );
			if ( $ga_name && ( $merged[ $key ]['form_name'] === $form_id || 'generic' === $merged[ $key ]['form_provider'] ) ) {
				$merged[ $key ]['form_name'] = $ga_name;
			}
			if ( 'generic' !== $provider ) {
				$merged[ $key ]['form_provider'] = $provider;
			}

			$merged[ $key ]['views']       = max( (int) $merged[ $key ]['views'], (int) ( $ga_row['views'] ?? 0 ) );
			$merged[ $key ]['starts']      = max( (int) $merged[ $key ]['starts'], (int) ( $ga_row['starts'] ?? 0 ) );
			$merged[ $key ]['submissions'] = max( (int) $merged[ $key ]['submissions'], (int) ( $ga_row['submissions'] ?? 0 ) );
		}

		$rows = array_values( $merged );
		foreach ( $rows as $i => $row ) {
			$views   = (int) ( $row['views'] ?? 0 );
			$submits = (int) ( $row['submissions'] ?? 0 );
			if ( $views > 0 ) {
				$rows[ $i ]['conv_rate'] = min( 100.0, round( ( $submits / $views ) * 100, 1 ) ) . '%';
			} else {
				$rows[ $i ]['conv_rate'] = $submits > 0 ? '100.0%' : '0.0%';
			}
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return ( $b['submissions'] ?? 0 ) <=> ( $a['submissions'] ?? 0 );
			}
		);

		return $rows;
	}

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array<int, array>
	 */
	private static function fetch_ga4_form_events( $start_date, $end_date ) {
		$dimension_sets = array(
			array( 'eventName', 'customEvent:form_id', 'customEvent:form_provider', 'customEvent:form_name' ),
			array( 'eventName', 'customEvent:form_id', 'customEvent:form_provider' ),
			array( 'eventName', 'customEvent:form_id' ),
			array( 'eventName', 'pagePath' ),
		);

		foreach ( $dimension_sets as $dimension_names ) {
			$res = self::run_ga4_forms_query( $start_date, $end_date, $dimension_names );
			if ( self::is_ga_error( $res ) || empty( $res['rows'] ) ) {
				continue;
			}

			$parsed = self::parse_ga4_form_rows( $res['rows'], $dimension_names );
			if ( ! empty( $parsed ) ) {
				return array_values( $parsed );
			}
		}

		return array();
	}

	/**
	 * Event-level totals from GA4.
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array{views:int,starts:int,submissions:int}|array{} Empty array when GA4 is unavailable.
	 */
	private static function fetch_ga4_event_totals( $start_date, $end_date ) {
		$res = self::run_ga4_forms_query( $start_date, $end_date, array( 'eventName' ) );
		if ( self::is_ga_error( $res ) ) {
			return array();
		}

		$totals = array(
			'views'       => 0,
			'starts'      => 0,
			'submissions' => 0,
		);

		foreach ( ( $res['rows'] ?? array() ) as $row ) {
			$metric = self::event_metric_key( $row['dimensionValues'][0]['value'] ?? '' );
			if ( '' === $metric ) {
				continue;
			}
			$totals[ $metric ] += (int) ( $row['metricValues'][0]['value'] ?? 0 );
		}

		return $totals;
	}

	/**
	 * @param array<int, array> $rows             GA4 rows.
	 * @param string[]          $dimension_names Dimension order.
	 * @return array<string, array>
	 */
	private static function parse_ga4_form_rows( array $rows, array $dimension_names ) {
		$forms = array();

		foreach ( $rows as $row ) {
			$dims  = $row['dimensionValues'] ?? array();
			$count = (int) ( $row['metricValues'][0]['value'] ?? 0 );
			if ( $count <= 0 ) {
				continue;
			}

			$map = array();
			foreach ( $dimension_names as $index => $name ) {
				$map[ $name ] = $dims[ $index ]['value'] ?? '';
			}

			$event_name = $map['eventName'] ?? '';
			$metric     = self::event_metric_key( $event_name );
			if ( '' === $metric ) {
				continue;
			}

			$provider  = '';
			$form_name = '';
			$form_id   = sanitize_text_field( $map['customEvent:form_id'] ?? '' );
			$page_path = sanitize_text_field( $map['pagePath'] ?? '' );

			if ( ( '' === $form_id || '(not set)' === $form_id ) && ! empty( $page_path ) ) {
				$form_id   = 'page:' . $page_path;
				$form_name = 'Form on ' . $page_path;
				$provider  = '(not set)';
			} elseif ( '' === $form_id || '(not set)' === $form_id ) {
				$form_id   = 'site_forms';
				$form_name = 'Active Site Forms';
				$provider  = '(not set)';
			}

			$provider = sanitize_text_field( $map['customEvent:form_provider'] ?? $provider );
			if ( '' === $provider || 'notset' === $provider || '(not set)' === $provider || 'generic' === $provider || 'wordpress' === $provider ) {
				$provider = '(not set)';
			}

			$form_name = sanitize_text_field( $map['customEvent:form_name'] ?? $form_name );
			if ( '' === $form_name || '(not set)' === $form_name ) {
				$form_name = 'Active Site Forms';
			}

			$key = $provider . ':' . $form_id;
			if ( ! isset( $forms[ $key ] ) ) {
				$forms[ $key ] = array(
					'form_id'       => $form_id,
					'form_name'     => $form_name,
					'form_provider' => $provider,
					'views'         => 0,
					'starts'        => 0,
					'submissions'   => 0,
				);
			}

			$forms[ $key ][ $metric ] += $count;
		}

		return $forms;
	}

	/**
	 * @param string   $start_date      Y-m-d.
	 * @param string   $end_date        Y-m-d.
	 * @param string[] $dimension_names GA4 dimension API names.
	 * @return array|\WP_Error
	 */
	private static function run_ga4_forms_query( $start_date, $end_date, array $dimension_names ) {
		$reporter_class = self::get_ga_reporter_class();
		if ( ! $reporter_class ) {
			return array( 'error' => 'ga_unavailable' );
		}

		$dimensions = array();
		foreach ( $dimension_names as $name ) {
			$dimensions[] = array( 'name' => $name );
		}

		return $reporter_class::run_custom_report(
			array(
				'dateRanges'      => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensions'      => $dimensions,
				'metrics'         => array( array( 'name' => 'eventCount' ) ),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'eventName',
						'inListFilter' => array(
							'values' => self::FORM_EVENT_NAMES,
						),
					),
				),
				'limit'           => 500,
			)
		);
	}

	/**
	 * @return class-string|null
	 */
	private static function get_ga_reporter_class() {
		if ( class_exists( '\Sm_Pulse_Analytics\GA_Reporter' ) ) {
			return '\Sm_Pulse_Analytics\GA_Reporter';
		}
		if ( class_exists( 'PulseAnalytics_GA_Reporter' ) ) {
			return 'PulseAnalytics_GA_Reporter';
		}
		return null;
	}

	/**
	 * @param mixed $res GA4 response.
	 * @return bool
	 */
	private static function is_ga_error( $res ) {
		return is_wp_error( $res ) || ( is_array( $res ) && ! empty( $res['error'] ) );
	}

	/**
	 * Map a GA4 event name to the report metric it feeds.
	 *
	 * @param string $event_name GA4 eventName dimension value.
	 * @return string One of views|starts|submissions, or '' when unrecognised.
	 */
	private static function event_metric_key( $event_name ) {
		$event_name = strtolower( trim( (string) $event_name ) );
		return isset( self::FORM_EVENT_METRIC_MAP[ $event_name ] )
			? self::FORM_EVENT_METRIC_MAP[ $event_name ]
			: '';
	}

	/**
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array{labels: string[], values: int[]}
	 */
	private static function fetch_submission_trend( $start_date, $end_date ) {
		$reporter_class = self::get_ga_reporter_class();
		if ( ! $reporter_class ) {
			return array(
				'labels' => array(),
				'values' => array(),
			);
		}

		$res = $reporter_class::run_custom_report(
			array(
				'dateRanges'      => array(
					array(
						'startDate' => $start_date,
						'endDate'   => $end_date,
					),
				),
				'dimensions'      => array( array( 'name' => 'date' ), array( 'name' => 'eventName' ) ),
				'metrics'         => array( array( 'name' => 'eventCount' ) ),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'eventName',
						'inListFilter' => array(
							'values' => self::FORM_EVENT_NAMES,
						),
					),
				),
				'orderBys'        => array(
					array(
						'dimension' => array( 'dimensionName' => 'date' ),
					),
				),
			)
		);

		$date_map    = array();
		$views_map   = array();
		$starts_map  = array();
		$submits_map = array();

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			try {
				$period = new \DatePeriod(
					new \DateTime( $start_date ),
					new \DateInterval( 'P1D' ),
					( new \DateTime( $end_date ) )->modify( '+1 day' )
				);
				foreach ( $period as $dt ) {
					$d                 = $dt->format( 'Y-m-d' );
					$date_map[ $d ]    = $d;
					$views_map[ $d ]   = 0;
					$starts_map[ $d ]  = 0;
					$submits_map[ $d ] = 0;
				}
			} catch ( \Exception $e ) {
				$date_map[ $start_date ]    = $start_date;
				$date_map[ $end_date ]      = $end_date;
				$views_map[ $start_date ]   = 0;
				$views_map[ $end_date ]     = 0;
				$starts_map[ $start_date ]  = 0;
				$starts_map[ $end_date ]    = 0;
				$submits_map[ $start_date ] = 0;
				$submits_map[ $end_date ]   = 0;
			}
		}

		if ( ! self::is_ga_error( $res ) && ! empty( $res['rows'] ) ) {
			foreach ( $res['rows'] as $row ) {
				$raw_date   = $row['dimensionValues'][0]['value'] ?? '';
				$event_name = $row['dimensionValues'][1]['value'] ?? '';
				$count      = (int) ( $row['metricValues'][0]['value'] ?? 0 );

				if ( preg_match( '/^\d{8}$/', $raw_date ) ) {
					$raw_date = substr( $raw_date, 0, 4 ) . '-' . substr( $raw_date, 4, 2 ) . '-' . substr( $raw_date, 6, 2 );
				}

				if ( ! isset( $views_map[ $raw_date ] ) ) {
					$date_map[ $raw_date ]    = $raw_date;
					$views_map[ $raw_date ]   = 0;
					$starts_map[ $raw_date ]  = 0;
					$submits_map[ $raw_date ] = 0;
				}

				$metric = self::event_metric_key( $event_name );
				if ( 'views' === $metric ) {
					$views_map[ $raw_date ] += $count;
				} elseif ( 'starts' === $metric ) {
					$starts_map[ $raw_date ] += $count;
				} elseif ( 'submissions' === $metric ) {
					$submits_map[ $raw_date ] += $count;
				}
			}
		}

		return array(
			'labels'      => array_values( $date_map ),
			'values'      => array_values( $submits_map ),
			'views'       => array_values( $views_map ),
			'starts'      => array_values( $starts_map ),
			'submissions' => array_values( $submits_map ),
		);
	}
}
