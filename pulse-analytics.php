<?php
/**
 * Plugin Name: StorePulse Analytics
 * Description: Analytics dashboard for WordPress and WooCommerce stores with real-time visitors, revenue tracking, campaign attribution, conversion funnels, and custom event tracking.
 * Version: 1.0.1
 * Author: Smackcoders
 * Author URI: https://www.smackcoders.com/wordpress.html
 * Plugin URI: https://www.smackcoders.com/wordpress.html
 * Text Domain: store-pulse-analytics
 * License: GPL2
 * Requires Plugins: woocommerce
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Tested up to: 7.0
 * WC requires at least: 8.0
 * WC tested up to: 9.8
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOGPATH', WP_CONTENT_DIR . '/StorePulse.log' );
/**
 * Declare WooCommerce compatibility:
 * - HPOS (High-Performance Order Storage / Custom Order Tables)
 * - Cart & Checkout Blocks
 * Must run on 'before_woocommerce_init' hook.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			// High-Performance Order Storage (HPOS) compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);

			// Cart & Checkout Blocks compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

require_once GA_PLUGIN_DIR . 'includes/connector.php';
require_once GA_PLUGIN_DIR . 'includes/campaign_utm.php';
require_once GA_PLUGIN_DIR . 'includes/class-ecommerce-tracker.php';

require_once GA_PLUGIN_DIR . 'includes/installation/install.php';
require_once GA_PLUGIN_DIR . 'includes/installation/uninstall.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\my_plugin_activation_hook' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\my_plugin_deactivation_hook' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\StorePulse_uninstall' );

global $StorePulse_ga_connector;
$StorePulse_ga_connector = GA_Connector::get_instance();
