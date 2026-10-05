<?php
/**
 * Google Analytics Admin API — discover GA4 properties and web streams.
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch GA4 property / measurement IDs via Analytics Admin API after OAuth.
 */
final class PulseAnalytics_GA4_Admin_API {

	const DISCOVERED_OPTION = 'sm_pulse_analytics_ga4_discovered';
	const ERROR_TRANSIENT   = 'sm_pulse_analytics_ga4_discovery_error';
	const ADMIN_API_BASE    = 'https://analyticsadmin.googleapis.com/v1beta';

	/**
	 * Fetch discoveries from Google and cache them.
	 *
	 * @return array{success:bool, discoveries:array, error:string}
	 */
	public static function fetch_and_cache_discoveries() {
		delete_transient( self::ERROR_TRANSIENT );

		$access_token = '';
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_OAuth' ) ) {
			$access_token = PulseAnalytics_GA4_OAuth::get_access_token();
		}

		if ( empty( $access_token ) ) {
			$error = __( 'Connect Google Analytics first, then refresh the property list.', 'smackcoders-pulse-analytics-for-woocommerce' );
			set_transient( self::ERROR_TRANSIENT, $error, 5 * MINUTE_IN_SECONDS );
			return array(
				'success'      => false,
				'discoveries'  => array(),
				'error'        => $error,
			);
		}

		$result = self::fetch_discoveries( $access_token );
		if ( ! empty( $result['error'] ) ) {
			set_transient( self::ERROR_TRANSIENT, $result['error'], 5 * MINUTE_IN_SECONDS );
			return array(
				'success'     => false,
				'discoveries' => array(),
				'error'       => $result['error'],
			);
		}

		self::cache_discoveries( $result['discoveries'] );

		return array(
			'success'     => true,
			'discoveries' => $result['discoveries'],
			'error'       => '',
		);
	}

	/**
	 * Call Analytics Admin API and return normalized discoveries.
	 *
	 * @param string|null $access_token OAuth access token.
	 * @return array{discoveries:array, error:string}
	 */
	public static function fetch_discoveries( $access_token = null ) {
		if ( null === $access_token ) {
			$access_token = class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_OAuth' )
				? PulseAnalytics_GA4_OAuth::get_access_token()
				: '';
		}

		if ( empty( $access_token ) ) {
			return array(
				'discoveries' => array(),
				'error'       => __( 'Missing OAuth access token.', 'smackcoders-pulse-analytics-for-woocommerce' ),
			);
		}

		$summaries = self::api_get_paginated(
			$access_token,
			self::ADMIN_API_BASE . '/accountSummaries',
			'accountSummaries'
		);

		if ( is_wp_error( $summaries ) ) {
			return array(
				'discoveries' => array(),
				'error'       => self::format_api_error( $summaries ),
			);
		}

		$discoveries = array();

		foreach ( $summaries as $summary ) {
			if ( ! is_array( $summary ) ) {
				continue;
			}

			$account_name = sanitize_text_field( (string) ( $summary['displayName'] ?? '' ) );
			$properties   = isset( $summary['propertySummaries'] ) && is_array( $summary['propertySummaries'] )
				? $summary['propertySummaries']
				: array();

			foreach ( $properties as $property_summary ) {
				if ( ! is_array( $property_summary ) ) {
					continue;
				}

				$property_id = self::parse_property_id_from_resource( (string) ( $property_summary['property'] ?? '' ) );
				if ( '' === $property_id ) {
					continue;
				}

				$streams_response = self::api_get_paginated(
					$access_token,
					self::ADMIN_API_BASE . '/properties/' . rawurlencode( $property_id ) . '/dataStreams',
					'dataStreams'
				);

				if ( is_wp_error( $streams_response ) ) {
					continue;
				}

				$streams = array();
				foreach ( $streams_response as $stream ) {
					if ( ! is_array( $stream ) ) {
						continue;
					}
					if ( ( $stream['type'] ?? '' ) !== 'WEB_DATA_STREAM' ) {
						continue;
					}

					$web = isset( $stream['webStreamData'] ) && is_array( $stream['webStreamData'] )
						? $stream['webStreamData']
						: array();

					$measurement_id = strtoupper( sanitize_text_field( (string) ( $web['measurementId'] ?? '' ) ) );
					if ( '' === $measurement_id || ! preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id ) ) {
						continue;
					}

					$streams[] = array(
						'stream_id'      => self::parse_stream_id_from_resource( (string) ( $stream['name'] ?? '' ) ),
						'measurement_id' => $measurement_id,
						'default_uri'    => esc_url_raw( (string) ( $web['defaultUri'] ?? '' ) ),
					);
				}

				if ( empty( $streams ) ) {
					continue;
				}

				$discoveries[] = array(
					'account_display_name'  => $account_name,
					'property_id'           => $property_id,
					'property_display_name' => sanitize_text_field( (string) ( $property_summary['displayName'] ?? '' ) ),
					'streams'               => $streams,
				);
			}
		}

		if ( empty( $discoveries ) ) {
			return array(
				'discoveries' => array(),
				'error'       => __( 'No GA4 web properties were found on this Google account. Create a GA4 property with a web data stream, or enter IDs manually below.', 'smackcoders-pulse-analytics-for-woocommerce' ),
			);
		}

		return array(
			'discoveries' => $discoveries,
			'error'       => '',
		);
	}

	/**
	 * @param array $discoveries Discovery list.
	 * @return bool
	 */
	public static function cache_discoveries( $discoveries ) {
		if ( ! is_array( $discoveries ) ) {
			$discoveries = array();
		}
		return update_option( self::DISCOVERED_OPTION, $discoveries, false );
	}

	/**
	 * @return array
	 */
	public static function get_cached_discoveries() {
		$cached = get_option( self::DISCOVERED_OPTION, array() );
		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Clear cached discoveries (e.g. on disconnect).
	 *
	 * @return void
	 */
	public static function clear_cached_discoveries() {
		delete_option( self::DISCOVERED_OPTION );
		delete_transient( self::ERROR_TRANSIENT );
	}

	/**
	 * Flatten discoveries into picker choices.
	 *
	 * @param array $discoveries Discovery list.
	 * @return array<int, array{key:string,label:string,property_id:string,measurement_id:string,stream_id:string,default_uri:string}>
	 */
	public static function flatten_choices( $discoveries ) {
		$choices = array();
		if ( ! is_array( $discoveries ) ) {
			return $choices;
		}

		foreach ( $discoveries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$property_id   = (string) ( $entry['property_id'] ?? '' );
			$account_name  = (string) ( $entry['account_display_name'] ?? '' );
			$property_name = (string) ( $entry['property_display_name'] ?? '' );
			$streams       = isset( $entry['streams'] ) && is_array( $entry['streams'] ) ? $entry['streams'] : array();

			foreach ( $streams as $stream ) {
				if ( ! is_array( $stream ) ) {
					continue;
				}

				$measurement_id = (string) ( $stream['measurement_id'] ?? '' );
				$stream_id      = (string) ( $stream['stream_id'] ?? '' );
				$default_uri    = (string) ( $stream['default_uri'] ?? '' );
				$key            = self::build_choice_key( $property_id, $measurement_id, $stream_id );

				$choices[] = array(
					'key'             => $key,
					'label'           => sprintf(
						/* translators: 1: Google account name, 2: GA4 property name, 3: measurement ID, 4: numeric property ID */
						__( '%1$s — %2$s — %3$s (Property %4$s)', 'smackcoders-pulse-analytics-for-woocommerce' ),
						$account_name ? $account_name : __( 'Account', 'smackcoders-pulse-analytics-for-woocommerce' ),
						$property_name ? $property_name : $property_id,
						$measurement_id,
						$property_id
					),
					'property_id'     => $property_id,
					'measurement_id'  => $measurement_id,
					'stream_id'       => $stream_id,
					'default_uri'     => $default_uri,
				);
			}
		}

		return $choices;
	}

	/**
	 * @param array  $discoveries Discovery list.
	 * @param string $site_url    Site URL to match (optional).
	 * @return string Choice key or empty string.
	 */
	public static function find_best_match_choice( $discoveries, $site_url = '' ) {
		$choices = self::flatten_choices( $discoveries );
		if ( empty( $choices ) ) {
			return '';
		}

		$needle = self::normalize_url_for_match( $site_url ? $site_url : home_url() );
		if ( '' !== $needle ) {
			foreach ( $choices as $choice ) {
				$haystack = self::normalize_url_for_match( (string) ( $choice['default_uri'] ?? '' ) );
				if ( '' !== $haystack && $haystack === $needle ) {
					return (string) $choice['key'];
				}
			}
		}

		$configured_property = class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' )
			? PulseAnalytics_GA4_Config::get_property_id()
			: '';
		$configured_measurement = class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' )
			? PulseAnalytics_GA4_Config::get_measurement_id()
			: '';

		if ( $configured_property && $configured_measurement ) {
			foreach ( $choices as $choice ) {
				if ( $choice['property_id'] === $configured_property && $choice['measurement_id'] === strtoupper( $configured_measurement ) ) {
					return (string) $choice['key'];
				}
			}
		}

		return (string) $choices[0]['key'];
	}

	/**
	 * Parse a picker choice key into IDs.
	 *
	 * @param string $choice_key Encoded choice key.
	 * @return array{property_id:string,measurement_id:string,stream_id:string}|null
	 */
	public static function parse_choice_key( $choice_key ) {
		$parts = explode( '|', (string) $choice_key, 3 );
		if ( count( $parts ) < 2 ) {
			return null;
		}

		$property_id    = sanitize_text_field( $parts[0] );
		$measurement_id = strtoupper( sanitize_text_field( $parts[1] ) );
		$stream_id      = isset( $parts[2] ) ? sanitize_text_field( $parts[2] ) : '';

		if ( ! preg_match( '/^\d{6,15}$/', $property_id ) ) {
			return null;
		}
		if ( ! preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id ) ) {
			return null;
		}

		return array(
			'property_id'    => $property_id,
			'measurement_id' => $measurement_id,
			'stream_id'      => $stream_id,
		);
	}

	/**
	 * Persist selected property / measurement / stream IDs.
	 *
	 * @param string $property_id    Numeric GA4 property ID.
	 * @param string $measurement_id GA4 measurement ID (G-...).
	 * @param string $stream_id      Optional data stream ID.
	 * @return true|\WP_Error
	 */
	public static function apply_selection( $property_id, $measurement_id, $stream_id = '' ) {
		$property_id    = sanitize_text_field( (string) $property_id );
		$measurement_id = strtoupper( sanitize_text_field( (string) $measurement_id ) );
		$stream_id      = sanitize_text_field( (string) $stream_id );

		if ( ! preg_match( '/^\d{6,15}$/', $property_id ) ) {
			return new \WP_Error(
				'invalid_property_id',
				__( 'Invalid GA4 Property ID.', 'smackcoders-pulse-analytics-for-woocommerce' )
			);
		}

		if ( ! preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id ) ) {
			return new \WP_Error(
				'invalid_measurement_id',
				__( 'Invalid GA4 Measurement ID.', 'smackcoders-pulse-analytics-for-woocommerce' )
			);
		}

		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' ) ) {
			$config_data = array(
				'property_id'    => $property_id,
				'measurement_id' => $measurement_id,
			);
			if ( '' !== $stream_id ) {
				$config_data['stream_id'] = $stream_id;
			}
			if ( ! PulseAnalytics_GA4_Config::save_config( $config_data ) ) {
				return new \WP_Error(
					'wp_config_write_failed',
					__( 'Could not write GA4 property settings to wp-config.php. Make wp-config.php writable, then try again.', 'smackcoders-pulse-analytics-for-woocommerce' )
				);
			}
		}

		return true;
	}

	/**
	 * @param string $property_id    Numeric property ID.
	 * @param string $measurement_id Measurement ID.
	 * @param string $stream_id      Stream ID.
	 * @return string
	 */
	public static function build_choice_key( $property_id, $measurement_id, $stream_id = '' ) {
		return implode(
			'|',
			array(
				sanitize_text_field( (string) $property_id ),
				strtoupper( sanitize_text_field( (string) $measurement_id ) ),
				sanitize_text_field( (string) $stream_id ),
			)
		);
	}

	/**
	 * Extract numeric property ID from `properties/123456789`.
	 *
	 * @param string $resource Resource name.
	 * @return string
	 */
	public static function parse_property_id_from_resource( $resource ) {
		if ( preg_match( '/^properties\/(\d{6,15})$/', (string) $resource, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * Extract stream ID from `properties/123/dataStreams/456`.
	 *
	 * @param string $resource Resource name.
	 * @return string
	 */
	public static function parse_stream_id_from_resource( $resource ) {
		if ( preg_match( '/\/dataStreams\/(\d+)$/', (string) $resource, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * Normalize URL host/path for property matching.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function normalize_url_for_match( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return rtrim( strtolower( $url ), '/' );
		}

		$host = strtolower( (string) $parts['host'] );
		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		$path = isset( $parts['path'] ) ? untrailingslashit( strtolower( (string) $parts['path'] ) ) : '';
		return $host . $path;
	}

	/**
	 * @param string $access_token Access token.
	 * @param string $url          Request URL.
	 * @param string $items_key    Response array key.
	 * @return array|\WP_Error
	 */
	private static function api_get_paginated( $access_token, $url, $items_key ) {
		$items        = array();
		$page_token   = '';
		$request_url  = $url;
		$attempts     = 0;

		while ( $attempts < 20 ) {
			++$attempts;
			if ( '' !== $page_token ) {
				$request_url = add_query_arg( 'pageToken', rawurlencode( $page_token ), $url );
			}

			$response = wp_remote_get(
				$request_url,
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

			if ( 403 === $code ) {
				return new \WP_Error(
					'admin_api_forbidden',
					__( 'Google Analytics Admin API access denied. Enable the Google Analytics Admin API in your Google Cloud project, then try again.', 'smackcoders-pulse-analytics-for-woocommerce' )
				);
			}

			if ( $code < 200 || $code >= 300 ) {
				$message = is_array( $body ) && ! empty( $body['error']['message'] )
					? (string) $body['error']['message']
					: __( 'Unexpected response from Google Analytics Admin API.', 'smackcoders-pulse-analytics-for-woocommerce' );
				return new \WP_Error( 'admin_api_error', $message );
			}

			if ( is_array( $body ) && ! empty( $body[ $items_key ] ) && is_array( $body[ $items_key ] ) ) {
				$items = array_merge( $items, $body[ $items_key ] );
			}

			$page_token = is_array( $body ) && ! empty( $body['nextPageToken'] )
				? (string) $body['nextPageToken']
				: '';

			if ( '' === $page_token ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * @param \WP_Error $error Error object.
	 * @return string
	 */
	private static function format_api_error( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return __( 'Unable to fetch GA4 properties from Google.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}
		return $error->get_error_message();
	}
}
