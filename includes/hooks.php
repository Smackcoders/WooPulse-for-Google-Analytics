<?php
/**
 * Extension hooks reference for Pulse Analytics.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render additional telemetry fields on Analytics Configuration (Section 1).
 *
 * Pro can inject Search Console Site URL or other extension fields here.
 *
 * @param array $context {
 *     @type bool   $oauth_connected Whether GA4 OAuth session is active.
 *     @type string $measurement_id  Current GA4 Measurement ID.
 *     @type string $gsc_site_url    Legacy GSC site URL hint (empty in free).
 * }
 */
// do_action( 'sm_pulse_analytics_analytics_settings_telemetry_fields', $context );

/**
 * Persist extension settings when Analytics Configuration is saved.
 *
 * @param string $step Current settings step slug (e.g. 'analytics').
 * @param array  $post Sanitized $_POST payload from the settings form.
 */
// do_action( 'sm_pulse_analytics_analytics_settings_save', $step, $sanitized_post );

/**
 * Render Search Console settings card on the GA4 settings tab.
 *
 * @param array $context Optional context for Pro GSC UI.
 */
// do_action( 'sm_pulse_analytics_ga4_settings_tab_gsc', $context );

/**
 * Pro add-on report screens (register menus + hook these actions):
 *
 * - sm_pulse_analytics_render_realtime_report
 * - sm_pulse_analytics_render_traffic_overview
 * - sm_pulse_analytics_render_ecommerce_overview
 * - sm_pulse_analytics_render_campaign_url_tracking
 * - sm_pulse_analytics_render_site_performance
 * - sm_pulse_analytics_render_user_journey
 * - sm_pulse_analytics_render_custom_events_report
 * - sm_pulse_analytics_render_search_console
 *
 * Page callbacks live on GA_Connector (e.g. sm_pulse_analytics_search_console_page()).
 * Assets: hook sm_pulse_analytics_enqueue_pro_admin_assets ( $page, $step, $version ).
 * Nav: filter sm_pulse_analytics_admin_nav_items.
 */
