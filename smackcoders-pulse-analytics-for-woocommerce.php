<?php
/**
 * Plugin Name: Smackcoders Pulse Analytics | Google Analytics 4 Dashboard
 * Plugin URI:  https://www.smackcoders.com/documentation/wp-pulse-analytics
 * Description: Analytics dashboard for WordPress sites, blogs, and online stores. Connect Google Analytics 4 for traffic insights; unlock revenue, behavior, and commerce reports with WP Pulse Analytics Pro.
 * Version: 1.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Smackcoders
 * Author URI: https://www.smackcoders.com/wordpress.html?utm_source=pulse_analytics&utm_medium=plugin&utm_content=support_resource
 * Text Domain: smackcoders-pulse-analytics-for-woocommerce
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Sm_Pulse_Analytics
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SM_PULSE_ANALYTICS_PLUGIN_FILE' ) ) {
	define( 'SM_PULSE_ANALYTICS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_VERSION' ) ) {
	define( 'SM_PULSE_ANALYTICS_VERSION', '1.0' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_PLUGIN_DIR' ) ) {
	define( 'SM_PULSE_ANALYTICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_PLUGIN_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_LOG_PATH' ) ) {
	$sm_pulse_analytics_upload  = wp_upload_dir( null, false );
	$sm_pulse_analytics_log_dir = trailingslashit( $sm_pulse_analytics_upload['basedir'] ) . 'pulse-analytics';
	if ( ! is_dir( $sm_pulse_analytics_log_dir ) ) {
		wp_mkdir_p( $sm_pulse_analytics_log_dir );
	}
	define( 'SM_PULSE_ANALYTICS_LOG_PATH', trailingslashit( $sm_pulse_analytics_log_dir ) . 'pulse-analytics.log' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_PRO_UPGRADE_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_PRO_UPGRADE_URL', 'https://www.smackcoders.com/documentation/wp-pulse-analytics?utm_source=pulse_analytics&utm_medium=plugin&utm_campaign=upgrade_to_pro&utm_content=upgrade_button' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_PLUGIN_SITE_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_PLUGIN_SITE_URL', 'https://www.smackcoders.com/documentation/wp-pulse-analytics?utm_source=pulse_analytics&utm_medium=plugin_row&utm_campaign=plugin_site' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_AUTHOR_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_AUTHOR_URL', 'https://www.smackcoders.com/wordpress.html?utm_source=pulse_analytics&utm_medium=plugin&utm_content=support_resource' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_DOCS_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_DOCS_URL', 'https://www.smackcoders.com/documentation/wp-pulse-analytics?utm_source=pulse_analytics&utm_medium=plugin&utm_content=support_resource' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_SUPPORT_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_SUPPORT_URL', 'https://www.smackcoders.com/contact-us.html?utm_source=pulse_analytics&utm_medium=plugin&utm_campaign=contact_us&utm_content=support_resource' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_PLUGIN_SLUG' ) ) {
	define( 'SM_PULSE_ANALYTICS_PLUGIN_SLUG', 'smackcoders-pulse-analytics-for-woocommerce' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_TEXT_DOMAIN' ) ) {
	define( 'SM_PULSE_ANALYTICS_TEXT_DOMAIN', 'smackcoders-pulse-analytics-for-woocommerce' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_TABLE_PREFIX' ) ) {
	define( 'SM_PULSE_ANALYTICS_TABLE_PREFIX', 'sm_pulse_analytics_' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_DEBUG_MODE' ) ) {
	define( 'SM_PULSE_ANALYTICS_GA4_DEBUG_MODE', false );
}

if ( ! function_exists( __NAMESPACE__ . '\sm_pulse_analytics_get_pro_upgrade_url' ) ) {
	/**
	 * Get the Pro Upgrade URL for buttons across the plugin.
	 *
	 * @return string
	 */
	function sm_pulse_analytics_get_pro_upgrade_url() {
		if ( defined( 'SM_PULSE_ANALYTICS_PRO_UPGRADE_URL' ) && ! empty( SM_PULSE_ANALYTICS_PRO_UPGRADE_URL ) ) {
			return SM_PULSE_ANALYTICS_PRO_UPGRADE_URL;
		}
		return 'https://www.smackcoders.com/ga4-analytics-plugin-for-wordpress.html?utm_source=pulse_analytics&utm_medium=plugin&utm_campaign=upgrade_to_pro&utm_content=upgrade_button';
	}
}

/**
 * Declare WooCommerce compatibility:
 * - HPOS (High-Performance Order Storage / Custom Order Tables)
 * - Cart & Checkout Blocks
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-secure-credentials.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/config.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/oauth.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/admin-api.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/gtag.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/api.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/ga4/measurement-protocol.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-pulse-analytics-storage.php';

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/forms/class-forms-config.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/forms/class-forms-dummy-data.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/forms/class-forms-reporter.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/forms/class-forms-tracker.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/forms/api.php';

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-i18n.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-site-profile.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/helpers.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-prefix-migrator.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-monsterinsights-migration.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-plugins-page-integration.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/cli/register.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/connector.php';

\Sm_Pulse_Analytics\PulseAnalytics_I18n::init();
\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::init();
\Sm_Pulse_Analytics\PulseAnalytics_Plugins_Page::init();
\Sm_Pulse_Analytics\PulseAnalytics_Prefix_Migrator::init();

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/installation/install.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/installation/uninstall.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\sm_pulse_analytics_activation_hook' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\sm_pulse_analytics_deactivation_hook' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\sm_pulse_analytics_uninstall' );

PulseAnalytics_GA4_Gtag::init();
PulseAnalytics_GA4_OAuth::init();
PulseAnalytics_GA4_API::init();

global $sm_pulse_analytics_ga_connector;
$sm_pulse_analytics_ga_connector = GA_Connector::get_instance();

if ( ! defined( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_DIR' ) ) {
	define( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_DIR', SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/affiliate-link/' );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_URL' ) ) {
	define( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_URL', SM_PULSE_ANALYTICS_PLUGIN_URL );
}
if ( ! defined( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_FREE_LOADED' ) ) {
	define( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_FREE_LOADED', true );
}

require_once SM_PULSE_ANALYTICS_AFFILIATE_LINK_DIR . 'installation/install.php';
require_once SM_PULSE_ANALYTICS_AFFILIATE_LINK_DIR . 'class-affiliate-link-module.php';

\Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Module::init();
