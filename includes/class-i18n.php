<?php
/**
 * Internationalization bootstrap for Pulse Analytics (Free).
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics {

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Loads translations and registers script translation paths.
	 */
	class PulseAnalytics_I18n {

		/** @var string WordPress.org text domain (matches plugin folder slug). */
		public const TEXT_DOMAIN = SM_PULSE_ANALYTICS_TEXT_DOMAIN;

		/**
		 * Register hooks.
		 */
		public static function init(): void {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'set_script_translations' ), 100 );
		}

		/**
		 * Enable wp.i18n for bundled admin scripts (JED JSON in languages/).
		 *
		 * @param string $hook_suffix Current admin page hook.
		 */
		public static function set_script_translations( $hook_suffix ): void {
			unset( $hook_suffix );

			if ( ! function_exists( 'wp_set_script_translations' ) ) {
				return;
			}

			$path    = SM_PULSE_ANALYTICS_PLUGIN_DIR . 'languages';
			$handles = array(
				'pulse-analytics-dashboard-js',
				'pulse-analytics-settings-links-js',
				'pulse-analytics-link-report-js',
				'pulse-analytics-dashboard-modal-js',
				'pulse-analytics-settings-ecommerce-js',
				'pulse-analytics-forms-report-js',
			);

			foreach ( $handles as $handle ) {
				wp_set_script_translations( $handle, self::TEXT_DOMAIN, $path );
			}
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_translate' ) ) {
		/**
		 * @param string $text Message (must already be translated at the call site).
		 * @return string
		 */
		function sm_pulse_analytics_translate( $text ) {
			return is_string( $text ) ? $text : '';
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_translate_esc_html' ) ) {
		/**
		 * @param string $text Message (must already be translated at the call site).
		 * @return string
		 */
		function sm_pulse_analytics_translate_esc_html( $text ) {
			return esc_html( is_string( $text ) ? $text : '' );
		}
	}
}

namespace {

	if ( ! function_exists( 'sm_pulse_analytics_translate' ) && ! function_exists( 'sm_pulse_analytics_translate' ) ) {
		/**
		 * Pass-through helper — do not call __() with variables (WP.org i18n rule).
		 * Prefer __( 'Literal', 'smackcoders-pulse-analytics-for-woocommerce' ) at call sites.
		 *
		 * @param string $text Message.
		 * @return string
		 */
		function sm_pulse_analytics_translate( $text ) {
			return is_string( $text ) ? $text : '';
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_translate_esc_html' ) && ! function_exists( 'sm_pulse_analytics_translate_esc_html' ) ) {
		/**
		 * Escape helper without variable gettext.
		 *
		 * @param string $text Message.
		 * @return string
		 */
		function sm_pulse_analytics_translate_esc_html( $text ) {
			return esc_html( is_string( $text ) ? $text : '' );
		}
	}
}
