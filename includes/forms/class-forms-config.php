<?php
/**
 * Forms conversion tracking configuration.
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

class Forms_Config {

	const OPTION_KEY = 'sm_pulse_analytics_forms_settings';

	/**
	 * Event-scoped GA4 custom dimensions required for per-form reporting.
	 *
	 * @return string[]
	 */
	public static function required_custom_dimensions() {
		return array( 'form_id', 'form_provider', 'form_name' );
	}

	/**
	 * Default supported providers.
	 *
	 * @return string[]
	 */
	public static function default_providers() {
		return array(
			'wpforms',
			'gravityforms',
			'contact-form-7',
			'formidable',
			'ninja-forms',
			'forminator',
			'elementor',
			'divi',
			'woocommerce',
			'comment',
			'generic',
		);
	}

	/**
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled'             => true,
			'enabled_providers'   => self::default_providers(),
			'excluded_forms'      => array(),
			'conversion_settings' => array(
				'track_views'       => true,
				'track_starts'      => true,
				'track_submissions' => true,
			),
		);

		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, $defaults );

		if ( ! is_array( $settings['enabled_providers'] ) ) {
			$settings['enabled_providers'] = self::default_providers();
		}

		// Ensure WooCommerce checkout and Forminator remain available as primary providers.
		if ( ! in_array( 'forminator', $settings['enabled_providers'], true ) ) {
			$settings['enabled_providers'][] = 'forminator';
		}
		if ( ! in_array( 'woocommerce', $settings['enabled_providers'], true ) ) {
			$settings['enabled_providers'][] = 'woocommerce';
		}

		$settings['enabled_providers'] = array_values( array_unique( array_map( 'sanitize_key', $settings['enabled_providers'] ) ) );

		return $settings;
	}

	/**
	 * @param array $settings Settings array.
	 */
	public static function update_settings( array $settings ) {
		$current = self::get_settings();

		if ( isset( $settings['enabled'] ) ) {
			$current['enabled'] = (bool) $settings['enabled'];
		}
		if ( isset( $settings['enabled_providers'] ) && is_array( $settings['enabled_providers'] ) ) {
			$current['enabled_providers'] = array_map( 'sanitize_key', $settings['enabled_providers'] );
		}
		if ( isset( $settings['excluded_forms'] ) && is_array( $settings['excluded_forms'] ) ) {
			$current['excluded_forms'] = array_map( 'sanitize_text_field', $settings['excluded_forms'] );
		}
		if ( isset( $settings['conversion_settings'] ) && is_array( $settings['conversion_settings'] ) ) {
			$current['conversion_settings'] = wp_parse_args( $settings['conversion_settings'], $current['conversion_settings'] );
		}

		update_option( self::OPTION_KEY, $current, false );
	}

	/**
	 * Check if form tracking is enabled natively in FREE.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		$settings = self::get_settings();
		return ! empty( $settings['enabled'] );
	}

	/**
	 * @param string $provider Provider slug.
	 */
	public static function is_provider_enabled( $provider ) {
		$settings = self::get_settings();
		$provider = sanitize_key( $provider );
		return in_array( $provider, $settings['enabled_providers'], true );
	}

	/**
	 * @param string $form_key Stable form identifier.
	 */
	public static function is_form_excluded( $form_key ) {
		$settings = self::get_settings();
		$form_key = sanitize_text_field( $form_key );
		return in_array( $form_key, $settings['excluded_forms'], true );
	}

	/**
	 * Payload for frontend localization.
	 *
	 * @return array
	 */
	public static function get_frontend_config() {
		$settings = self::get_settings();
		return array(
			'enabled'           => self::is_tracking_enabled(),
			'debug_mode'        => class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) && \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::is_debug_mode(),
			'providers'         => array_values( $settings['enabled_providers'] ),
			'excluded_forms'    => array_values( $settings['excluded_forms'] ),
			'track_views'       => ! empty( $settings['conversion_settings']['track_views'] ),
			'track_starts'      => ! empty( $settings['conversion_settings']['track_starts'] ),
			'track_submissions' => ! empty( $settings['conversion_settings']['track_submissions'] ),
			'page_id'           => get_queried_object_id(),
			'page_title'        => wp_get_document_title(),
			'post_type'         => get_post_type( get_queried_object_id() ) ?: '',
			'tracking_mode'     => 'ga4_gtag',
		);
	}
}
