<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

/**
 * GA4 Configuration & Options Storage Helper (Single Source of Truth).
 *
 * All GA4 secrets: encrypted wp-config constants via PulseAnalytics_Secure_Credentials.
 *
 * @package Sm_Pulse_Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PulseAnalytics_GA4_Config {

	const OPTION_KEY = 'sm_pulse_analytics_ga4_config';

	/**
	 * Get stored GA4 config array from single DB option (non-secret fields).
	 *
	 * @return array
	 */
	public static function get_config() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			$legacy = get_option( 'sm_pulse_analytics_ga4_config', array() );
			if ( is_array( $legacy ) && ! empty( $legacy ) ) {
				update_option( self::OPTION_KEY, $legacy, false );
				$stored = $legacy;
			}
		}
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Get decrypted Measurement ID.
	 *
	 * @return string
	 */
	public static function get_measurement_id() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			$id = self::sanitize_measurement_id(
				PulseAnalytics_Secure_Credentials::get_ga4_measurement_id()
			);
			if ( '' !== $id ) {
				return $id;
			}
		}

		$config = self::get_config();
		$id     = $config['measurement_id'] ?? '';
		if ( ! empty( $id ) && function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			$id = sm_pulse_analytics_decrypt( $id );
		}
		$id = self::sanitize_measurement_id( $id );
		if ( '' !== $id ) {
			self::save_config( array( 'measurement_id' => $id ) );
			return $id;
		}

		$legacy_option = get_option( 'sm_pulse_analytics_ga4_measurement_id', '' );
		if ( is_string( $legacy_option ) && '' !== $legacy_option ) {
			if ( function_exists( 'sm_pulse_analytics_decrypt' ) ) {
				$legacy_option = sm_pulse_analytics_decrypt( $legacy_option );
			}
			$legacy_option = self::sanitize_measurement_id( $legacy_option );
			if ( '' !== $legacy_option ) {
				self::save_config( array( 'measurement_id' => $legacy_option ) );
				return $legacy_option;
			}
		}

		$settings = get_option( 'sm_pulse_analytics_settings', array() );
		$fallback = is_array( $settings ) ? (string) ( $settings['measurement_id'] ?? '' ) : '';
		if ( '' === $fallback ) {
			return '';
		}
		if ( function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			$fallback = sm_pulse_analytics_decrypt( $fallback );
		}
		$fallback = self::sanitize_measurement_id( $fallback );
		if ( '' !== $fallback ) {
			self::save_config( array( 'measurement_id' => $fallback ) );
		}
		return $fallback;
	}

	/**
	 * Normalize and validate a GA4 Measurement ID for display/API use.
	 *
	 * @param string $value Raw or decrypted measurement ID.
	 * @return string Valid G- measurement ID, or empty string.
	 */
	private static function sanitize_measurement_id( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		$value = sanitize_text_field( trim( $value ) );

		if ( '' === $value || 0 === stripos( $value, 'sp_enc:' ) ) {
			return '';
		}

		$value = strtoupper( $value );

		if ( ! preg_match( '/^G-[A-Z0-9]+$/', $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Normalize and validate a GA4 Property ID for display/API use.
	 *
	 * @param string $value Raw or decrypted property ID.
	 * @return string Valid numeric property ID, or empty string.
	 */
	private static function sanitize_property_id( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		$value = sanitize_text_field( trim( $value, '{}' ) );

		if ( '' === $value || 0 === strpos( $value, 'sp_enc:' ) ) {
			return '';
		}

		if ( ! preg_match( '/^\d{6,15}$/', $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Get decrypted Property ID.
	 *
	 * @return string
	 */
	public static function get_property_id() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			$id = self::sanitize_property_id(
				PulseAnalytics_Secure_Credentials::get_ga4_property_id()
			);
			if ( '' !== $id ) {
				return $id;
			}
		}

		$config = self::get_config();
		$id     = $config['property_id'] ?? '';
		if ( ! empty( $id ) && function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			$id = sm_pulse_analytics_decrypt( $id );
		}
		$id = self::sanitize_property_id( $id );
		if ( '' !== $id ) {
			self::save_config( array( 'property_id' => $id ) );
			return $id;
		}

		$settings = get_option( 'sm_pulse_analytics_settings', array() );
		$fallback = is_array( $settings ) ? (string) ( $settings['property_id'] ?? '' ) : '';
		if ( '' === $fallback ) {
			return '';
		}
		if ( function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			$fallback = sm_pulse_analytics_decrypt( $fallback );
		}
		$fallback = self::sanitize_property_id( $fallback );
		if ( '' !== $fallback ) {
			self::save_config( array( 'property_id' => $fallback ) );
		}
		return $fallback;
	}

	/**
	 * Get API Secret from encrypted wp-config constant only.
	 *
	 * @return string
	 */
	public static function get_api_secret() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			return PulseAnalytics_Secure_Credentials::get_ga4_api_secret();
		}
		return '';
	}

	/**
	 * Get decrypted Stream ID.
	 *
	 * @return string
	 */
	public static function get_stream_id() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			$id = sanitize_text_field( PulseAnalytics_Secure_Credentials::get_ga4_stream_id() );
			if ( '' !== $id ) {
				return $id;
			}
		}

		$config = self::get_config();
		$id     = $config['stream_id'] ?? '';
		if ( ! empty( $id ) && function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			$id = sm_pulse_analytics_decrypt( $id );
		}
		$id = ! empty( $id ) ? sanitize_text_field( $id ) : '';
		if ( '' !== $id ) {
			self::save_config( array( 'stream_id' => $id ) );
		}
		return $id;
	}

	/**
	 * Whether OAuth app credentials are available from wp-config.php.
	 *
	 * @return bool
	 */
	public static function has_oauth_app_credentials() {
		return class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' )
			&& PulseAnalytics_Secure_Credentials::has_ga4_oauth_credentials();
	}

	/**
	 * Whether OAuth client ID is stored in wp-config.php.
	 *
	 * @return bool
	 */
	public static function is_client_id_in_wp_config() {
		return class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' )
			&& PulseAnalytics_Secure_Credentials::is_usable( PulseAnalytics_Secure_Credentials::CONST_GA4_CLIENT_ID );
	}

	/**
	 * Whether OAuth client secret is stored in wp-config.php.
	 *
	 * @return bool
	 */
	public static function is_client_secret_in_wp_config() {
		return class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' )
			&& PulseAnalytics_Secure_Credentials::is_usable( PulseAnalytics_Secure_Credentials::CONST_GA4_CLIENT_SECRET );
	}

	/**
	 * Get OAuth Client ID from wp-config.php.
	 *
	 * @return string
	 */
	public static function get_client_id() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			return PulseAnalytics_Secure_Credentials::get_ga4_client_id();
		}
		return '';
	}

	/**
	 * Get OAuth Client Secret from wp-config.php.
	 *
	 * @return string
	 */
	public static function get_client_secret() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			return PulseAnalytics_Secure_Credentials::get_ga4_client_secret();
		}
		return '';
	}

	/**
	 * Whether frontend GA4 tracking (gtag) should run.
	 *
	 * Default: enabled when unset and a Measurement ID exists (BC).
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		$options = get_option( 'sm_pulse_analytics_settings', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		if ( array_key_exists( 'enable_tracking', $options ) ) {
			$enabled = ( '1' === (string) $options['enable_tracking'] || true === $options['enable_tracking'] );
		} else {
			$enabled = '' !== self::get_measurement_id();
		}

		/**
		 * Filter whether frontend tracking is enabled.
		 *
		 * @param bool $enabled Whether tracking is enabled.
		 */
		return (bool) apply_filters( 'sm_pulse_analytics_tracking_enabled', $enabled );
	}

	/**
	 * Save GA4 IDs to encrypted wp-config.php constants.
	 *
	 * @param array $data Configuration data key-value pairs.
	 * @return bool
	 */
	public static function save_config( $data = array() ) {
		if ( ! class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			return false;
		}

		$data = is_array( $data ) ? $data : array();
		unset( $data['api_secret'], $data['client_id'], $data['client_secret'] );

		$sec = '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials';
		$ok  = true;

		if ( isset( $data['property_id'] ) && '' !== (string) $data['property_id'] ) {
			$ok = $sec::write_constant( $sec::CONST_GA4_PROPERTY_ID, (string) $data['property_id'] ) && $ok;
		}
		if ( isset( $data['measurement_id'] ) && '' !== (string) $data['measurement_id'] ) {
			$ok = $sec::write_constant( $sec::CONST_GA4_MEASUREMENT_ID, (string) $data['measurement_id'] ) && $ok;
		}
		if ( isset( $data['stream_id'] ) && '' !== (string) $data['stream_id'] ) {
			$ok = $sec::write_constant( $sec::CONST_GA4_STREAM_ID, (string) $data['stream_id'] ) && $ok;
		}

		if ( $ok ) {
			self::clear_db_ga4_secrets();
		}

		return $ok;
	}

	/**
	 * Remove GA4 secrets from database options after wp-config persistence.
	 *
	 * @return void
	 */
	private static function clear_db_ga4_secrets() {
		$config = get_option( self::OPTION_KEY, array() );
		if ( is_array( $config ) ) {
			unset( $config['property_id'], $config['measurement_id'], $config['stream_id'] );
			update_option( self::OPTION_KEY, $config, false );
		}

		delete_option( 'sm_pulse_analytics_ga4_measurement_id' );

		$settings = get_option( 'sm_pulse_analytics_settings', array() );
		if ( is_array( $settings ) ) {
			unset( $settings['property_id'], $settings['measurement_id'] );
			update_option( 'sm_pulse_analytics_settings', $settings, false );
		}
	}

	/**
	 * Check if GA4 debug mode is enabled via constant or settings.
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {
		if ( defined( 'GA4_DEBUG_MODE' ) && GA4_DEBUG_MODE ) {
			return true;
		}
		if ( defined( 'SM_PULSE_ANALYTICS_GA4_DEBUG_MODE' ) && SM_PULSE_ANALYTICS_GA4_DEBUG_MODE ) {
			return true;
		}
		if ( defined( 'SM_PULSE_ANALYTICS_DEBUG_MODE' ) ) {
			return (bool) SM_PULSE_ANALYTICS_DEBUG_MODE;
		}

		$options = get_option( 'sm_pulse_analytics_settings', array() );
		if ( ! empty( $options['enable_debug_mode'] ) || ! empty( $options['debug_mode'] ) ) {
			return true;
		}

		$ga4_options = get_option( 'sm_pulse_analytics_ga4', array() );
		if ( ! empty( $ga4_options['debug_mode'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether debug mode is forced by a wp-config constant.
	 *
	 * @return bool
	 */
	public static function is_debug_mode_locked_by_constant() {
		return ( defined( 'GA4_DEBUG_MODE' ) && GA4_DEBUG_MODE )
			|| ( defined( 'SM_PULSE_ANALYTICS_GA4_DEBUG_MODE' ) && SM_PULSE_ANALYTICS_GA4_DEBUG_MODE )
			|| ( defined( 'SM_PULSE_ANALYTICS_DEBUG_MODE' ) && SM_PULSE_ANALYTICS_DEBUG_MODE );
	}
}

class_alias( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config', 'Sm_Pulse_Analytics_GA4_Config' );
