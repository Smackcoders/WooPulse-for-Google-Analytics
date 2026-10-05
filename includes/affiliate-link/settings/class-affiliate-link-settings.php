<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking settings and admin menu (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter( 'sm_pulse_analytics_settings_steps', array( $this, 'register_settings_steps' ), 22 );
		add_action( 'sm_pulse_analytics_render_settings_tab_affiliate-link-tracking', array( $this, 'render_settings_tab' ) );
		add_filter( 'sm_pulse_analytics_admin_nav_items', array( $this, 'register_nav_item' ), 22 );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
		// Submenu page registered centrally in connector.php.
	}

	/**
	 * @param array<string, string> $steps Settings steps.
	 * @return array<string, string>
	 */
	public function register_settings_steps( $steps ) {
		if ( ! is_array( $steps ) ) {
			$steps = array();
		}

		if ( isset( $steps['affiliate-link-tracking'] ) ) {
			return $steps;
		}

		$new = array();
		foreach ( $steps as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'links' === $key || 'site-notes' === $key ) {
				$new['affiliate-link-tracking'] = __( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' );
			}
		}

		if ( ! isset( $new['affiliate-link-tracking'] ) ) {
			$new['affiliate-link-tracking'] = __( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		return $new;
	}

	/**
	 * @param array<string, array<string, string>> $items Nav items.
	 * @return array<string, array<string, string>>
	 */
	public function register_nav_item( $items ) {
		if ( ! Affiliate_Link_Config::is_active() ) {
			return $items;
		}

		$link_item = array(
			'pulse-analytics-affiliate-links' => array(
				'label' => __( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'svg'   => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
			),
		);

		$out = array();
		foreach ( $items as $slug => $item ) {
			$out[ $slug ] = $item;
			if ( 'pulse-analytics-site-notes' === $slug ) {
				$out = array_merge( $out, $link_item );
			}
		}

		if ( ! isset( $out['pulse-analytics-affiliate-links'] ) ) {
			$out = array_merge( $out, $link_item );
		}

		return $out;
	}

	public function register_admin_page() {
		add_submenu_page(
			'pulse-analytics',
			__( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
			__( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'sm_pulse_analytics_view_reports',
			'pulse-analytics-affiliate-links',
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page() {
		if ( function_exists( 'sm_pulse_analytics_render_admin_header' ) ) {
			sm_pulse_analytics_render_admin_header();
		}
		include __DIR__ . '/../admin/affiliate-links-report.php';
	}


	public function render_settings_tab() {
		include __DIR__ . '/../admin/settings-affiliate-link-tracking.php';
	}

	public function maybe_save_settings() {
		if ( ! is_admin() || ! Affiliate_Link_Config::user_can_manage() ) {
			return;
		}
		if ( ! isset( $_POST['sm_pulse_analytics_affiliate_link_settings'] ) || ! check_admin_referer( 'save_sm_pulse_analytics_settings' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '';
		if ( 'affiliate-link-tracking' !== $step ) {
			return;
		}

		$raw = map_deep( wp_unslash( $_POST['sm_pulse_analytics_affiliate_link_settings'] ), 'sanitize_textarea_field' );
		if ( ! is_array( $raw ) ) {
			return;
		}

		$settings = array(
			'enabled'           => ! empty( $raw['enabled'] ),
			'track_affiliate'   => ! empty( $raw['track_affiliate'] ),
			'track_outbound'    => ! empty( $raw['track_outbound'] ),
			'respect_consent'   => ! empty( $raw['respect_consent'] ),
			'affiliate_prefixes'=> self::lines_to_array( $raw['affiliate_prefixes'] ?? '' ),
			'affiliate_domains' => self::lines_to_array( $raw['affiliate_domains'] ?? '' ),
			'excluded_domains'  => self::lines_to_array( $raw['excluded_domains'] ?? '' ),
			'excluded_prefixes' => self::lines_to_array( $raw['excluded_prefixes'] ?? '' ),
			'ignored_selectors' => self::lines_to_array( $raw['ignored_selectors'] ?? '' ),
			'partner_map'       => self::parse_partner_map( $raw['partner_map'] ?? '' ),
		);

		Affiliate_Link_Config::update_settings( $settings );

		$page_val = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'sm-pulse-analytics-settings';
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => $page_val,
					'step'             => 'affiliate-link-tracking',
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @param string $text Multiline text.
	 * @return array<int, string>
	 */
	private static function lines_to_array( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		return array_values(
			array_filter(
				array_map( 'trim', is_array( $lines ) ? $lines : array() )
			)
		);
	}

	/**
	 * @param string $text domain|name lines.
	 * @return array<int, array{domain: string, name: string}>
	 */
	private static function parse_partner_map( $text ) {
		$rows = array();
		foreach ( self::lines_to_array( $text ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( ! empty( $parts[0] ) ) {
				$rows[] = array(
					'domain' => strtolower( $parts[0] ),
					'name'   => $parts[1] ?? $parts[0],
				);
			}
		}
		return $rows;
	}
}
