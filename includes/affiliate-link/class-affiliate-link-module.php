<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking Module main loader (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Module {

	private static $instance = null;

	public static function init() {
		add_action(
			'plugins_loaded',
			function () {
				if ( Install::check_dependencies() ) {
					self::get_instance();
				}
			},
			10
		);
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once __DIR__ . '/class-affiliate-link-config.php';
		require_once __DIR__ . '/class-link-classifier.php';
		require_once __DIR__ . '/class-affiliate-link-reporter.php';
		require_once __DIR__ . '/class-affiliate-link-service.php';
		require_once __DIR__ . '/class-affiliate-link-api.php';
		require_once __DIR__ . '/class-affiliate-link-integrations.php';
		require_once __DIR__ . '/settings/class-affiliate-link-settings.php';

		Affiliate_Link_API::init();
		Affiliate_Link_Integrations::init();
		Affiliate_Link_Settings::get_instance();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 28 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_frontend_assets() {
		if ( is_admin() || ! Affiliate_Link_Config::is_tracking_enabled() ) {
			return;
		}

		wp_enqueue_script(
			'pulse-analytics-affiliate-link-tracker',
			SM_PULSE_ANALYTICS_AFFILIATE_LINK_URL . 'assets/js/affiliate-link-tracker.js',
			array(),
			'1.0.0',
			true
		);

		wp_localize_script(
			'pulse-analytics-affiliate-link-tracker',
			'SmPulseAnalyticsAffiliateLinks',
			Affiliate_Link_Config::get_frontend_config()
		);
	}

	public function enqueue_admin_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'pulse-analytics-affiliate-links' !== $page ) {
			return;
		}
		if ( ! Affiliate_Link_Config::is_active() || ! Affiliate_Link_Config::user_can_view() ) {
			return;
		}

		wp_enqueue_style(
			'pulse-analytics-affiliate-links',
			SM_PULSE_ANALYTICS_AFFILIATE_LINK_URL . 'assets/css/affiliate-links.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'pulse-analytics-affiliate-links-report',
			SM_PULSE_ANALYTICS_AFFILIATE_LINK_URL . 'assets/js/affiliate-links-report.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'pulse-analytics-affiliate-links-report',
			'smPulseAnalyticsAffiliateLinksVars',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'sm_pulse_analytics_affiliate_link_nonce' ),
				'settings_url' => admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=affiliate-link-tracking' ),
				'i18n'         => array(
					'loading' => __( 'Loading affiliate link report…', 'smackcoders-pulse-analytics-for-woocommerce' ),
					'error'   => __( 'Unable to load affiliate link data.', 'smackcoders-pulse-analytics-for-woocommerce' ),
					'noData'  => __( 'No link clicks recorded for this period.', 'smackcoders-pulse-analytics-for-woocommerce' ),
				),
			)
		);
	}
}

if ( ! class_exists( 'PulseAnalytics_Affiliate_Link_Module' ) ) {
	class_alias( '\Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Module', 'Sm_Pulse_Analytics_Affiliate_Link_Module' );
}
if ( ! class_exists( 'PulseAnalytics_Affiliate_Link_Addon' ) ) {
	class_alias( '\Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Module', 'Sm_Pulse_Analytics_Affiliate_Link_Addon' );
}
