<?php
/**
 * Site profile: website vs store, and optional commerce platform detection.
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
	 * Detects site type and supported commerce platforms (WooCommerce, EDD, etc.).
	 */
	class PulseAnalytics_Site_Profile {

		public const OPTION_KEY = 'sm_pulse_analytics_site_profile';

		/** @var string Manual/auto site mode stored in settings. */
		public const MODE_AUTO     = 'auto';
		public const MODE_WEBSITE  = 'website';
		public const MODE_STORE    = 'store';

		/** @var string Resolved site type after detection. */
		public const TYPE_WEBSITE = 'website';
		public const TYPE_STORE   = 'store';

		/** @var string Supported commerce platform slugs. */
		public const PLATFORM_WOOCOMMERCE = 'woocommerce';
		public const PLATFORM_EDD         = 'edd';
		public const PLATFORM_MEMBERPRESS = 'memberpress';
		public const PLATFORM_GIVEWP      = 'givewp';

		/**
		 * Register hooks.
		 */
		public static function init(): void {
			add_action( 'plugins_loaded', array( __CLASS__, 'sync_profile' ), 20 );
			add_action( 'activated_plugin', array( __CLASS__, 'sync_profile' ), 20 );
			add_action( 'deactivated_plugin', array( __CLASS__, 'sync_profile' ), 20 );
			add_action( 'admin_menu', array( __CLASS__, 'maybe_hide_commerce_menus' ), 999 );
		}

		/**
		 * Remove commerce-only submenu entries on website profiles.
		 */
		public static function maybe_hide_commerce_menus(): void {
			if ( self::has_commerce() ) {
				return;
			}

			foreach ( self::get_commerce_nav_slugs() as $slug ) {
				remove_submenu_page( 'pulse-analytics', $slug );
			}
		}

		/**
		 * Persist detected platforms for REST/UI consumption.
		 */
		public static function sync_profile(): void {
			update_option(
				self::OPTION_KEY,
				array(
					'site_type'            => self::resolve_site_type(),
					'site_mode'            => self::get_site_mode(),
					'platforms'            => self::get_active_platforms(),
					'has_commerce'         => self::has_commerce(),
					'currency_code'        => self::get_currency_code(),
					'currency_symbol'      => self::get_currency_symbol(),
					'last_synced'          => current_time( 'mysql', true ),
				),
				false
			);
		}

		/**
		 * User-selected or auto site mode from settings.
		 *
		 * @return string auto|website|store
		 */
		public static function get_site_mode(): string {
			$settings = get_option( 'sm_pulse_analytics_settings', array() );
			$mode     = isset( $settings['site_mode'] ) ? sanitize_key( (string) $settings['site_mode'] ) : self::MODE_AUTO;

			if ( ! in_array( $mode, array( self::MODE_AUTO, self::MODE_WEBSITE, self::MODE_STORE ), true ) ) {
				return self::MODE_AUTO;
			}

			return $mode;
		}

		/**
		 * Resolved site type used for UI gating.
		 *
		 * @return string website|store
		 */
		public static function resolve_site_type(): string {
			$mode = self::get_site_mode();

			if ( self::MODE_WEBSITE === $mode ) {
				return self::TYPE_WEBSITE;
			}
			if ( self::MODE_STORE === $mode ) {
				return self::TYPE_STORE;
			}

			return self::detect_commerce_platforms() ? self::TYPE_STORE : self::TYPE_WEBSITE;
		}

		/**
		 * @return string website|store
		 */
		public static function get_site_type(): string {
			$cached = get_option( self::OPTION_KEY, array() );
			if ( ! empty( $cached['site_type'] ) ) {
				return (string) $cached['site_type'];
			}
			return self::resolve_site_type();
		}

		/**
		 * @return bool
		 */
		public static function has_commerce(): bool {
			if ( self::TYPE_WEBSITE === self::get_site_type() ) {
				return false;
			}
			return ! empty( self::get_active_platforms() );
		}

		/**
		 * Slugs for nav items that require a commerce platform.
		 *
		 * @return string[]
		 */
		public static function get_commerce_nav_slugs(): array {
			return array(
				'pulse-analytics-ecommerce-overview',
				'pulse-analytics-campaign-url-tracking',
				'pulse-analytics-user-journey',
			);
		}

		/**
		 * Filter admin nav items based on site profile.
		 *
		 * @param array<string, array<string, string>> $nav_items Nav map.
		 * @return array<string, array<string, string>>
		 */
		public static function filter_nav_items( array $nav_items ): array {
			if ( self::has_commerce() ) {
				return $nav_items;
			}

			foreach ( self::get_commerce_nav_slugs() as $slug ) {
				unset( $nav_items[ $slug ] );
			}

			return $nav_items;
		}

		/**
		 * Detect installed/active commerce plugins.
		 *
		 * @return string[] Platform slugs.
		 */
		public static function detect_commerce_platforms(): array {
			$platforms = array();

			if ( self::is_woocommerce_active() ) {
				$platforms[] = self::PLATFORM_WOOCOMMERCE;
			}
			if ( self::is_edd_active() ) {
				$platforms[] = self::PLATFORM_EDD;
			}
			if ( self::is_memberpress_active() ) {
				$platforms[] = self::PLATFORM_MEMBERPRESS;
			}
			if ( self::is_givewp_active() ) {
				$platforms[] = self::PLATFORM_GIVEWP;
			}

			return $platforms;
		}

		/**
		 * Platforms enabled in settings and currently active.
		 *
		 * @return string[]
		 */
		public static function get_active_platforms(): array {
			$detected = self::detect_commerce_platforms();
			if ( empty( $detected ) ) {
				return array();
			}

			$store_ecommerce = get_option( 'sm_pulse_analytics_ecommerce', array() );
			$enabled         = (array) ( $store_ecommerce['ecommerce'] ?? $detected );

			return array_values( array_intersect( $detected, $enabled ) );
		}

		/**
		 * @param string $platform Platform slug.
		 * @return bool
		 */
		public static function is_platform_active( string $platform ): bool {
			return in_array( $platform, self::get_active_platforms(), true );
		}

		/**
		 * @return bool
		 */
		public static function is_woocommerce_active(): bool {
			return class_exists( 'WooCommerce' )
				|| ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) );
		}

		/**
		 * @return bool
		 */
		public static function is_edd_active(): bool {
			return class_exists( 'Easy_Digital_Downloads' )
				|| defined( 'EDD_VERSION' )
				|| ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) );
		}

		/**
		 * @return bool
		 */
		public static function is_memberpress_active(): bool {
			return class_exists( 'MeprAppCtrl' )
				|| class_exists( 'MeprTransaction' )
				|| defined( 'MEPR_VERSION' )
				|| ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'memberpress/memberpress.php' ) );
		}

		/**
		 * @return bool
		 */
		public static function is_givewp_active(): bool {
			return class_exists( 'Give' )
				|| function_exists( 'give_get_payments' )
				|| defined( 'GIVE_VERSION' )
				|| ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'give/give.php' ) );
		}

		/**
		 * @return string ISO currency code.
		 */
		public static function get_currency_code(): string {
			if ( self::is_woocommerce_active() && function_exists( 'get_woocommerce_currency' ) ) {
				return (string) get_woocommerce_currency();
			}
			if ( self::is_edd_active() && function_exists( 'edd_get_currency' ) ) {
				return (string) edd_get_currency();
			}
			if ( self::is_memberpress_active() && class_exists( 'MeprOptions' ) ) {
				$mepr_options = \MeprOptions::fetch();
				if ( ! empty( $mepr_options->currency_code ) ) {
					return strtoupper( (string) $mepr_options->currency_code );
				}
			}
			if ( self::is_givewp_active() && function_exists( 'give_get_currency' ) ) {
				return strtoupper( (string) give_get_currency() );
			}

			$settings = get_option( 'sm_pulse_analytics_settings', array() );
			if ( ! empty( $settings['currency_code'] ) ) {
				return strtoupper( sanitize_text_field( (string) $settings['currency_code'] ) );
			}

			return 'USD';
		}

		/**
		 * @return string Display symbol.
		 */
		public static function get_currency_symbol(): string {
			if ( self::is_woocommerce_active() && function_exists( 'get_woocommerce_currency_symbol' ) ) {
				return html_entity_decode( (string) get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
			}
			if ( self::is_edd_active() && function_exists( 'edd_currency_filter' ) ) {
				return html_entity_decode( (string) edd_currency_filter( '' ), ENT_QUOTES, 'UTF-8' );
			}

			$code = self::get_currency_code();
			$map  = array(
				'USD' => '$',
				'EUR' => '€',
				'GBP' => '£',
				'INR' => '₹',
				'JPY' => '¥',
				'AUD' => 'A$',
				'CAD' => 'C$',
			);

			return $map[ $code ] ?? $code . ' ';
		}

		/**
		 * Human label for site type.
		 *
		 * @return string
		 */
		public static function get_site_type_label(): string {
			return self::TYPE_STORE === self::get_site_type()
				? __( 'Online store', 'smackcoders-pulse-analytics-for-woocommerce' )
				: __( 'Website / blog', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		/**
		 * REST-ready platform integration payload.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_integration_payload(): array {
			$platforms = array(
				self::PLATFORM_WOOCOMMERCE => array(
					'active'   => self::is_woocommerce_active(),
					'enabled'  => self::is_platform_active( self::PLATFORM_WOOCOMMERCE ),
					'label'    => 'WooCommerce',
					'status'   => self::is_woocommerce_active() ? 'active' : 'inactive',
				),
				self::PLATFORM_EDD         => array(
					'active'   => self::is_edd_active(),
					'enabled'  => self::is_platform_active( self::PLATFORM_EDD ),
					'label'    => 'Easy Digital Downloads',
					'status'   => self::is_edd_active() ? 'active' : 'inactive',
				),
				self::PLATFORM_MEMBERPRESS => array(
					'active'   => self::is_memberpress_active(),
					'enabled'  => self::is_platform_active( self::PLATFORM_MEMBERPRESS ),
					'label'    => 'MemberPress',
					'status'   => self::is_memberpress_active() ? 'active' : 'inactive',
				),
				self::PLATFORM_GIVEWP      => array(
					'active'   => self::is_givewp_active(),
					'enabled'  => self::is_platform_active( self::PLATFORM_GIVEWP ),
					'label'    => 'GiveWP',
					'status'   => self::is_givewp_active() ? 'active' : 'inactive',
				),
			);

			return array(
				'site_type'       => self::get_site_type(),
				'site_mode'       => self::get_site_mode(),
				'has_commerce'    => self::has_commerce(),
				'platforms'       => $platforms,
				'currency_code'   => self::get_currency_code(),
				'currency_symbol' => self::get_currency_symbol(),
			);
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_get_site_type' ) ) {
		function sm_pulse_analytics_get_site_type() {
			return PulseAnalytics_Site_Profile::get_site_type();
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_has_commerce' ) ) {
		function sm_pulse_analytics_has_commerce() {
			return PulseAnalytics_Site_Profile::has_commerce();
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_is_woocommerce_active' ) ) {
		function sm_pulse_analytics_is_woocommerce_active() {
			return PulseAnalytics_Site_Profile::is_woocommerce_active();
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_is_edd_active' ) ) {
		function sm_pulse_analytics_is_edd_active() {
			return PulseAnalytics_Site_Profile::is_edd_active();
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_is_memberpress_active' ) ) {
		function sm_pulse_analytics_is_memberpress_active() {
			return PulseAnalytics_Site_Profile::is_memberpress_active();
		}
	}

	if ( ! function_exists( 'Sm_Pulse_Analytics\sm_pulse_analytics_is_givewp_active' ) ) {
		function sm_pulse_analytics_is_givewp_active() {
			return PulseAnalytics_Site_Profile::is_givewp_active();
		}
	}
}

namespace {
	if ( ! function_exists( 'sm_pulse_analytics_get_site_type' ) ) {
		function sm_pulse_analytics_get_site_type() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::get_site_type();
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_has_commerce' ) ) {
		function sm_pulse_analytics_has_commerce() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::has_commerce();
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_is_woocommerce_active' ) ) {
		function sm_pulse_analytics_is_woocommerce_active() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::is_woocommerce_active();
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_is_edd_active' ) ) {
		function sm_pulse_analytics_is_edd_active() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::is_edd_active();
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_is_memberpress_active' ) ) {
		function sm_pulse_analytics_is_memberpress_active() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::is_memberpress_active();
		}
	}

	if ( ! function_exists( 'sm_pulse_analytics_is_givewp_active' ) ) {
		function sm_pulse_analytics_is_givewp_active() {
			return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::is_givewp_active();
		}
	}
}
