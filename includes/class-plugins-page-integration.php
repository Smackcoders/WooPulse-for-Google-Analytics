<?php
/**
 * Integration for WordPress admin plugins.php screen.
 *
 * Adds action links, row meta, and Pro upgrade tab to wp-admin/plugins.php.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\PulseAnalytics_Plugins_Page' ) ) {

	/**
	 * Class PulseAnalytics_Plugins_Page
	 */
	class PulseAnalytics_Plugins_Page {

		/**
		 * Initialize hooks for plugins.php.
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			$plugin_file = SM_PULSE_ANALYTICS_PLUGIN_FILE;
			$basename    = plugin_basename( $plugin_file );

			add_action(
				'admin_enqueue_scripts',
				function ( $hook ) {
					if ( 'plugins.php' === $hook ) {
						add_thickbox();
					}
				}
			);

			add_filter( 'plugin_action_links_' . $basename, array( __CLASS__, 'filter_action_links' ) );
			add_filter( 'plugin_row_meta', array( __CLASS__, 'filter_row_meta' ), 10, 2 );
			add_filter( 'views_plugins', array( __CLASS__, 'filter_views_plugins' ) );
			add_filter( 'views_plugins-network', array( __CLASS__, 'filter_views_plugins' ) );
			add_filter( 'plugins_api', array( __CLASS__, 'filter_plugins_api' ), 10, 3 );
		}

		/**
		 * Add action links in the plugin row on plugins.php.
		 *
		 * @param array $links Array of action links.
		 * @return array
		 */
		public static function filter_action_links( $links ) {
			if ( ! is_array( $links ) ) {
				$links = array();
			}

			$is_pro_active = function_exists( 'sm_pulse_analytics_is_pro_active' ) && sm_pulse_analytics_is_pro_active();

			$settings_url  = admin_url( 'admin.php?page=sm-pulse-analytics-settings' );
			$settings_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'smackcoders-pulse-analytics-for-woocommerce' )
			);

			array_unshift( $links, $settings_link );

			if ( ! $is_pro_active ) {
				$upgrade_url  = SM_PULSE_ANALYTICS_PRO_UPGRADE_URL;

				$upgrade_link = sprintf(
					'<a class="sp-link-danger" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( $upgrade_url ),
					esc_html__( 'Upgrade to Pro', 'smackcoders-pulse-analytics-for-woocommerce' )
				);

				$links['upgrade_pro'] = $upgrade_link;
			}

			return $links;
		}

		/**
		 * Add row meta links below the plugin description on plugins.php.
		 *
		 * @param array  $links Plugin row meta links.
		 * @param string $file  Plugin basename.
		 * @return array
		 */
		public static function filter_row_meta( $links, $file ) {
			$plugin_file = SM_PULSE_ANALYTICS_PLUGIN_FILE;
			$basename    = plugin_basename( $plugin_file );

			if ( $file !== $basename ) {
				return $links;
			}

			if ( ! is_array( $links ) ) {
				$links = array();
			}

			$target_slug = SM_PULSE_ANALYTICS_PLUGIN_SLUG;

			$has_view_details = false;
			foreach ( $links as $key => $link ) {
				if ( is_string( $link ) && false !== strpos( $link, 'plugin-information' ) ) {
					$links[ $key ]   = preg_replace( '/([?&]plugin=)[^&"\'\s]+/', '${1}' . rawurlencode( $target_slug ), $link );
					$has_view_details = true;
				}
			}

			$view_details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $target_slug ) . '&TB_iframe=true&width=772&height=550' );

			$row_meta = array();

			if ( ! $has_view_details ) {
				$row_meta['view_details'] = sprintf(
					'<a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s" data-title="%3$s">%4$s</a>',
					esc_url( $view_details_url ),
					/* translators: %s: Plugin name */
					esc_attr( sprintf( __( 'More information about %s', 'smackcoders-pulse-analytics-for-woocommerce' ), 'WP Pulse Analytics' ) ),
					esc_attr( 'WP Pulse Analytics' ),
					esc_html__( 'View details', 'smackcoders-pulse-analytics-for-woocommerce' )
				);
			}

			$row_meta['docs']    = sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( SM_PULSE_ANALYTICS_DOCS_URL ),
				esc_html__( 'Docs', 'smackcoders-pulse-analytics-for-woocommerce' )
			);
			$row_meta['support'] = sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( SM_PULSE_ANALYTICS_SUPPORT_URL ),
				esc_html__( 'Support', 'smackcoders-pulse-analytics-for-woocommerce' )
			);

			return array_merge( $links, $row_meta );
		}

		/**
		 * Filter plugins_api to route plugin information requests to official WordPress.org slug.
		 *
		 * @param false|object|array $res    API response.
		 * @param string             $action Action name.
		 * @param object             $args   API arguments.
		 * @return false|object|array
		 */
		public static function filter_plugins_api( $res, $action, $args ) {
			if ( 'plugin_information' === $action && is_object( $args ) && isset( $args->slug ) ) {
				if ( SM_PULSE_ANALYTICS_PLUGIN_SLUG === $args->slug ) {
					$args->slug = SM_PULSE_ANALYTICS_PLUGIN_SLUG;
				}
			}

			return $res;
		}

		/**
		 * Add 'Upgrade to Pro' tab to top filter views bar on plugins.php.
		 *
		 * @param array $views Views links array.
		 * @return array
		 */
		public static function filter_views_plugins( $views ) {
			if ( ! is_array( $views ) ) {
				return $views;
			}

			$is_pro_active = function_exists( 'sm_pulse_analytics_is_pro_active' ) && sm_pulse_analytics_is_pro_active();

			if ( ! $is_pro_active ) {
				$upgrade_url = SM_PULSE_ANALYTICS_PRO_UPGRADE_URL;

				$views['upgrade_pro'] = sprintf(
					'<a class="sp-link-danger" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s <span class="dashicons dashicons-external sp-dashicon-external"></span></a>',
					esc_url( $upgrade_url ),
					esc_html__( 'Upgrade to Pro', 'smackcoders-pulse-analytics-for-woocommerce' )
				);
			}

			return $views;
		}
	}
}
