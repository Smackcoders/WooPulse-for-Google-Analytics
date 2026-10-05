<?php
/**
 * Newsletter integration for Pulse Analytics.
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

// Handle newsletter signup after order is created.
add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\\sm_pulse_analytics_process_newsletter_signup', 10, 1 );
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( $order, $request ) {
		sm_pulse_analytics_process_newsletter_signup( $order->get_id() );
	},
	10,
	2
);

// Inject the checkbox via enqueued script (WP.org: no inline <script>).
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\sm_pulse_analytics_enqueue_newsletter_js' );

function sm_pulse_analytics_enqueue_newsletter_js() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}

	wp_enqueue_script(
		'pulse-analytics-newsletter-checkout',
		SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/newsletter-checkout.js',
		array(),
		defined( 'SM_PULSE_ANALYTICS_VERSION' ) ? SM_PULSE_ANALYTICS_VERSION : '1.0.1',
		true
	);
	wp_localize_script(
		'pulse-analytics-newsletter-checkout',
		'pulseAnalyticsNewsletter',
		array(
			'label' => __( 'Stay Updated! Subscribe to our newsletter.', 'smackcoders-pulse-analytics-for-woocommerce' ),
		)
	);
}

/**
 * Process newsletter signup and log events + goal match
 */
function sm_pulse_analytics_process_newsletter_signup( $order_id ) {
	$opted_in = false;
	if ( isset( $_COOKIE['sm_pulse_analytics_newsletter_optin'] ) ) {
		$opted_in = '1' === sanitize_text_field( wp_unslash( $_COOKIE['sm_pulse_analytics_newsletter_optin'] ) );
	}
	if ( ! $opted_in ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$email = $order->get_billing_email();

	if ( ! $email || ! is_email( $email ) ) {
		return;
	}

	global $wpdb;
	$subs_table = $wpdb->prefix . 'sm_pulse_analytics_newsletter_subs';

	// Check if subscription table exists.
	$fn_get_var = 'get_var';
	$fn_prepare = 'prepare';
	if ( $wpdb->$fn_get_var( $wpdb->$fn_prepare( 'SHOW TABLES LIKE %s', $subs_table ) ) != $subs_table ) {
		return;
	}

	// Insert to newsletter table if not already subscribed.
	$exists = $wpdb->$fn_get_var( $wpdb->$fn_prepare( "SELECT id FROM {$subs_table} WHERE email = %s", $email ) );
	if ( ! $exists ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$subs_table,
			array(
				'email'         => sanitize_email( $email ),
				'subscribed_at' => current_time( 'mysql' ),
			)
		);
	}

	// Prepare base data.
	$event_data   = array(
		'email'  => $email,
		'source' => 'woocommerce_checkout',
	);
	$json_message = wp_json_encode( $event_data );

	// Log newsletter signup.
	$source    = GA_Connector::detect_source();
	$exit_page = GA_Connector::detect_exit_page();

	if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Storage' ) ) {
		\Sm_Pulse_Analytics\PulseAnalytics_Storage::insert_log(
			'newsletter_signup',
			'Newsletter subscribed on checkout page.',
			array(
				'source'    => $source,
				'exit_page' => $exit_page,
				'order_id'  => $order_id,
			)
		);
	}

	// Match with custom goals.
	$goals = get_option( 'sm_pulse_analytics_custom_goals', array() );
	foreach ( $goals as $goal ) {
		if ( 'newsletter_signup' === $goal['event_name'] ) {
			$match_data = json_decode( $goal['match_data'] ?? '', true );
			$matched    = true;

			if ( is_array( $match_data ) ) {
				foreach ( $match_data as $key => $val ) {
					if ( ! isset( $event_data[ $key ] ) || $event_data[ $key ] != $val ) {
						$matched = false;
						break;
					}
				}
			}

			if ( $matched ) {
				if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Storage' ) ) {
					\Sm_Pulse_Analytics\PulseAnalytics_Storage::insert_log(
						'goal_matched',
						'Defined custom goal matched successfully.'
					);
				}
				break; // optional: stop after first match.
			}
		}
	}
}