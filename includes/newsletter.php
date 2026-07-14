<?php
/**
 * Newsletter integration for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle newsletter signup after order is created.
add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\\StorePulse_process_newsletter_signup', 10, 1 );
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( $order, $request ) {
		StorePulse_process_newsletter_signup( $order->get_id() );
	},
	10,
	2
);

// Inject the checkbox via JS to support both Classic and Block Checkouts.
add_action( 'wp_footer', __NAMESPACE__ . '\\StorePulse_inject_newsletter_js' );

function StorePulse_inject_newsletter_js() {
	if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			function injectCheckbox() {
				if (document.getElementById('StorePulse-newsletter-wrapper')) return;

				// Find a good place to inject (Supports both Classic and Block Checkout)
				let targetLocation = document.querySelector('.wc-block-checkout__payment-method') || 
									document.querySelector('.wc-block-checkout__terms') ||
									document.querySelector('#customer_details');
				
				if (!targetLocation) {
					// Fallback to pushing it just above the place order button area
					targetLocation = document.querySelector('.wc-block-checkout__actions') || 
									document.querySelector('#payment');
				}

				if (targetLocation) {
					const wrapper = document.createElement('div');
					wrapper.id = 'StorePulse-newsletter-wrapper';
					wrapper.style.margin = '20px 0';
					wrapper.style.padding = '15px';
					wrapper.style.background = '#f9fafb';
					wrapper.style.border = '1px solid #e5e7eb';
					wrapper.style.borderRadius = '8px';
					
					wrapper.innerHTML = `
						<label style="display: flex; align-items: center; cursor: pointer; font-weight: 500; font-size: 14px; color: #374151;">
							<input type="checkbox" id="storepulse_newsletter_subscribe" style="margin-right: 10px; width: 18px; height: 18px;">
							Stay Updated! Subscribe to our newsletter.
						</label>
					`;
					
					targetLocation.parentNode.insertBefore(wrapper, targetLocation);

					// Handle Cookie state
					const checkbox = document.getElementById('storepulse_newsletter_subscribe');
					checkbox.addEventListener('change', function() {
						if (this.checked) {
							document.cookie = "StorePulse_newsletter_optin=1; path=/; max-age=86400";
						} else {
							document.cookie = "StorePulse_newsletter_optin=0; path=/; max-age=0"; // Delete cookie
						}
					});
				} else {
					// If DOM is still rendering (React), retry in 500ms
					setTimeout(injectCheckbox, 500);
				}
			}
			
			// Attempt injection
			injectCheckbox();
			
			// Retry for React-based blocks that load async
			setTimeout(injectCheckbox, 1000);
			setTimeout(injectCheckbox, 3000);
		});
	</script>
	<?php
}

/**
 * Process newsletter signup and log events + goal match
 */
function StorePulse_process_newsletter_signup( $order_id ) {
	// Check if cookie was set by frontend JS.
	if ( ! isset( $_COOKIE['StorePulse_newsletter_optin'] ) || '1' != $_COOKIE['StorePulse_newsletter_optin'] ) {
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
	$subs_table = $wpdb->prefix . 'storepulse_newsletter_subs';
	$logs_table = $wpdb->prefix . 'storepulse_logs';

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

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->insert(
		$logs_table,
		array(
			'event_type' => 'Newsletter_Signup',
			'message'    => 'Newsletter Subcribed in Checkout Page.',
			'source'     => $source,
			'exit_page'  => $exit_page,
			'order_id'   => $order_id,
			'created_at' => current_time( 'mysql' ),
		)
	);

	// Match with custom goals.
	$goals = get_option( 'StorePulse_custom_goals', array() );
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
				$goal_log = array(
					'event_type' => 'Goal_Matched',
					'message'    => 'Defined Custom Goal Matched Successfully.',
					'created_at' => current_time( 'mysql' ),
				);

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert( $logs_table, $goal_log );
				break; // optional: stop after first match.
			}
		}
	}
}