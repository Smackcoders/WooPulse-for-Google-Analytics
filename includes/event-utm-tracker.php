<?php
/**
 * UTM event tracker for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_UTM_Tracker {

	private static $instance = null;

	private function __construct() {
		// Core tracking hooks.
		add_action( 'init', array( $this, 'capture_utm_parameters' ), 1 );

		// Capture UTMs when order is processed.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'store_utm_parameters' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'store_utm_parameters' ), 10, 1 );

		// Local Funnel Tracking.
		add_action( 'template_redirect', array( $this, 'track_funnel_pages' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6 );

		// Track order status changes.
		add_action( 'woocommerce_order_status_changed', array( $this, 'store_order_event' ), 10, 4 );

		// Frontend script for cookie persistence.
		add_action( 'wp_footer', array( $this, 'embed_tracking_scripts' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Primary logging function for Audit Logs
	 */
	public function StorePulse_log( $event_type, $message, $source, $exit_page, $order_id = null ) {
		global $wpdb;
		$table_logs = $wpdb->prefix . 'storepulse_logs';

		$user_id_raw = get_current_user_id();
		$user_id     = ! empty( $user_id_raw ) ? $user_id_raw : null;
		$session_id  = StorePulse_get_session_id();
		$ip          = StorePulse_get_visitor_ip();
		$ua          = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$browser = function_exists( 'SmackCoders\WGA\StorePulse_get_browser' ) ? StorePulse_get_browser( $ua ) : 'Other';
		$device  = function_exists( 'SmackCoders\WGA\StorePulse_get_device' ) ? StorePulse_get_device( $ua ) : 'Desktop';

		$insert_data = array(
			'event_type'     => sanitize_text_field( $event_type ),
			'message'        => sanitize_textarea_field( $message ),
			'source'         => sanitize_text_field( $source ),
			'traffic_source' => sanitize_text_field( $source ), // Map source to traffic_source.
			'exit_page'      => sanitize_text_field( $exit_page ),
			'order_id'       => $order_id ? (int) $order_id : null,
			'user_id'        => $user_id,
			'session_id'     => $session_id,
			'ip_address'     => $ip,
			'browser'        => $browser,
			'device_type'    => $device,
			'created_at'     => current_time( 'mysql' ),
			'referrer_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
			'utm_source'     => isset( $_COOKIE['utm_source'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_source'] ) ) : '',
			'utm_medium'     => isset( $_COOKIE['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_medium'] ) ) : '',
			'utm_campaign'   => isset( $_COOKIE['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_campaign'] ) ) : '',
		);

		// Capture user info if logged in.
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$insert_data['user_name']  = $user->display_name;
				$insert_data['user_email'] = $user->user_email;
				$insert_data['user_role']  = ! empty( $user->roles ) ? $user->roles[0] : '';
			}
			$insert_data['is_guest'] = 0;
		} else {
			$insert_data['is_guest'] = 1;
		}

		// If it's a WooCommerce order, fetch more details.
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$insert_data['order_status']    = $order->get_status();
				$insert_data['cart_value']      = $order->get_total();
				$insert_data['payment_method']  = $order->get_payment_method_title();
				$insert_data['shipping_method'] = $order->get_shipping_method();
				$insert_data['transaction_id']  = $order->get_transaction_id();

				// If not already set by logged in user.
				if ( empty( $insert_data['user_name'] ) ) {
					$insert_data['user_name']  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
					$insert_data['user_email'] = $order->get_billing_email();
				}
			}
		}

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $table_logs, $insert_data );
	}

	/**
	 * Capture UTM parameters and store in cookies
	 */
	public function capture_utm_parameters() {
		if (
			is_admin() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( defined( 'DOING_CRON' ) && DOING_CRON ) ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( defined( 'WP_CLI' ) && WP_CLI ) ||
			( false !== strpos( $_SERVER['REQUEST_URI'] ?? '', '/wp-json/' ) ) || // phpcs:ignore
			! empty( $_GET['rest_route'] ) // phpcs:ignore
		) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$utm_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );

		foreach ( $utm_keys as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
				// Save cookie for 24 hours.
				setcookie( $key, $value, time() + 86400, COOKIEPATH, '' );
				$_COOKIE[ $key ] = $value;
			}
		}
	}

	/**
	 * Embed frontend JS for cookie management
	 */
	public function embed_tracking_scripts() {
		?>
		<script>
			(function () {
				const params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
				const query = new URLSearchParams(window.location.search);
				params.forEach(param => {
					const value = query.get(param);
					// Removed client-side cookie setting with max-age. Server-side will handle.
				});
			})();
		</script>
		<?php
	}

	/**
	 * Store attribution data when order is placed
	 */
	public function store_utm_parameters( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'storepulse_events';

		// Check if already logged for this order.
		$fn_get_var = 'get_var';
		$fn_prepare = 'prepare';
		$existing   = $wpdb->$fn_get_var(
			$wpdb->$fn_prepare(
				"SELECT event_id FROM $table_name WHERE order_id = %d AND event_type = 'order_created'",
				$order_id
			)
		);
		if ( $existing ) {
			return;
		}

		$utm_keys = array( 'utm_source', 'utm_medium', 'utm_campaign' );
		$utm_data = array();

		foreach ( $utm_keys as $key ) {
			// Cookie first, fallback to basic sanitize.
			$value            = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';
			$utm_data[ $key ] = $value;
			update_post_meta( $order_id, "_{$key}", $value );
		}

		$source    = GA_Connector::detect_source();
		$exit_page = GA_Connector::detect_exit_page();

		$session_id = StorePulse_get_session_id();
		$event_data = wp_json_encode(
			array(
				'session_id'     => $session_id,
				'total'          => $order->get_total(),
				'currency'       => $order->get_currency(),
				'payment_method' => $order->get_payment_method(),
				'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'items_count'    => count( $order->get_items() ),
			)
		);

		$final_utm_source = ! empty( $utm_data['utm_source'] ) ? $utm_data['utm_source'] : $source;
		$final_utm_medium = ! empty( $utm_data['utm_medium'] ) ? $utm_data['utm_medium'] : ( 'direct' !== $source ? 'referral' : 'none' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'event_type'      => 'order_created',
				'session_id'      => $session_id,
				'anon_id'         => StorePulse_get_anon_id(),
				'order_id'        => $order_id,
				'user_id'         => ! empty( $order->get_customer_id() ) ? $order->get_customer_id() : null,
				'event_data'      => $event_data,
				'utm_source'      => $final_utm_source,
				'utm_medium'      => $final_utm_medium,
				'utm_campaign'    => $utm_data['utm_campaign'],
				'source'          => $source,
				'exit_page'       => $exit_page,
				'event_timestamp' => current_time( 'mysql' ),
			)
		);

		$this->StorePulse_log(
			'Order_Created',
			"Order #$order_id has Created Successfully.",
			$source,
			$exit_page,
			$order_id
		);

		// Update WooCommerce sync status.
		if ( ! function_exists( 'StorePulse_update_sync_status' ) ) {
			require_once GA_PLUGIN_DIR . 'includes/helpers.php';
		}
		StorePulse_update_sync_status( 'woocommerce', 'completed' );
	}

	/**
	 * Store logs when order status changes
	 */
	public function store_order_event( $order_id, $old_status, $new_status, $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'storepulse_events';
		$event_type = 'order_' . $new_status;

		$source    = GA_Connector::detect_source();
		$exit_page = GA_Connector::detect_exit_page();

		$this->StorePulse_log(
			$event_type,
			"Order #$order_id status changed to '$new_status'.",
			$source,
			$exit_page,
			$order_id
		);
	}

	public function track_funnel_pages() {
		// 1. Core Funnel Pages
		if ( is_product() ) {
			global $post;
			$product      = wc_get_product( $post->ID );
			$product_name = $product ? $product->get_name() : null;
			$this->log_funnel_event( 'product_view', $product_name );
		} elseif ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			if ( WC()->cart && ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product = $cart_item['data'];
					if ( $_product ) {
						$this->log_funnel_event( 'begin_checkout', $_product->get_name() );
					}
				}
			} else {
				$this->log_funnel_event( 'begin_checkout' );
			}
		}

		// 2. Global Page View Logging (for Fallback Reports)
		$this->log_funnel_event( 'page_view' );
	}

	public function track_add_to_cart( $cart_item_key, $product_id = 0, $quantity = 0, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		$product      = wc_get_product( $product_id );
		$product_name = $product ? $product->get_name() : null;
		$this->log_funnel_event( 'add_to_cart', $product_name );
	}

	private function log_funnel_event( $event_type, $product_name = null ) {
		global $wpdb;
		$table_events = $wpdb->prefix . 'storepulse_events';

		$session_id = StorePulse_get_session_id();
		if ( empty( $session_id ) ) {
			return;
		}

		$current_page = GA_Connector::detect_exit_page();

		// If page detection returns processing (e.g. during AJAX add-to-cart requests), try to detect using the Referer URL.
		if ( 'processing' === $current_page ) {
			$referer = wp_get_referer();
			if ( $referer ) {
				$referer_path = wp_parse_url( $referer, PHP_URL_PATH );
				if ( $referer_path ) {
					$referer_path = trim( $referer_path, '/' );
					if ( empty( $referer_path ) ) {
						$current_page = 'home';
					} elseif ( false !== strpos( $referer_path, 'cart' ) ) {
						$current_page = 'cart';
					} elseif ( false !== strpos( $referer_path, 'checkout' ) ) {
						$current_page = 'checkout';
					} elseif ( false !== strpos( $referer_path, 'shop' ) ) {
						$current_page = 'shop';
					} else {
						// Check if it's a product page (often contains /product/ slug)
						if ( false !== strpos( $referer_path, 'product/' ) || false !== strpos( $referer_path, 'product-category/' ) ) {
							$current_page = 'product';
						} else {
							$current_page = 'product'; // Default fallback for product actions like add_to_cart
						}
					}
				}
			}
		}

		// Do not track generic page views or admin actions classified as 'processing'
		if ( 'processing' === $current_page && 'add_to_cart' !== $event_type ) {
			return;
		}

		// Do not track generic page_view on product/checkout pages since they have specific events
		if ( 'page_view' === $event_type && ( 'product' === $current_page || 'checkout' === $current_page ) ) {
			return;
		}

		// Get current UTMs for deduplication
		$utm_s_current = isset( $_COOKIE['utm_source'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_source'] ) ) : '';
		$utm_m_current = isset( $_COOKIE['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_medium'] ) ) : '';
		$utm_c_current = isset( $_COOKIE['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_campaign'] ) ) : '';

		// Deduplicate: only block consecutive duplicate events in the last 5 seconds.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable
		$last_event = $wpdb->get_row( $wpdb->prepare(
			"SELECT event_type, exit_page, utm_source, utm_medium, utm_campaign, event_timestamp FROM $table_events 
			 WHERE session_id = %s 
			 ORDER BY event_timestamp DESC, event_id DESC LIMIT 1",
			$session_id
		) );
		// phpcs:enable

		if (
			$last_event &&
			$last_event->event_type === $event_type &&
			$last_event->exit_page === $current_page &&
			$last_event->utm_source === $utm_s_current &&
			$last_event->utm_medium === $utm_m_current &&
			$last_event->utm_campaign === $utm_c_current
		) {
			$time_diff = current_time( 'timestamp' ) - strtotime( $last_event->event_timestamp );
			if ( $time_diff < 5 ) {
				return;
			}
		}

		$source    = GA_Connector::detect_source();
		$exit_page = $current_page;

		// Capture Basic Browser/Device Info.
		$ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$browser = StorePulse_get_browser( $ua );
		$device  = StorePulse_get_device( $ua );

		$event_data = array(
			'session_id' => $session_id,
			'browser'    => $browser,
			'device'     => $device,
			'user_agent' => substr( $ua, 0, 255 ),
		);
		if ( $product_name ) {
			$event_data['product_name'] = $product_name;
		}

		$utm_s = isset( $_COOKIE['utm_source'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_source'] ) ) : '';
		$utm_m = isset( $_COOKIE['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_medium'] ) ) : '';
		$utm_c = isset( $_COOKIE['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['utm_campaign'] ) ) : '';

		$final_utm_source   = ! empty( $utm_s ) ? $utm_s : $source;
		$final_utm_medium   = ! empty( $utm_m ) ? $utm_m : ( 'direct' !== $source ? 'referral' : 'none' );
		$final_utm_campaign = ! empty( $utm_c ) ? $utm_c : '';

		// Calculate next sequence number for this session
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$max_seq = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT MAX(sequence_number) FROM $table_events WHERE session_id = %s", // phpcs:ignore
			$session_id
		) );
		$sequence_number = $max_seq + 1;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_events,
			array(
				'event_type'      => $event_type,
				'session_id'      => $session_id,
				'anon_id'         => StorePulse_get_anon_id(),
				'user_id'         => ! empty( get_current_user_id() ) ? get_current_user_id() : null,
				'order_id'        => null,
				'sequence_number' => $sequence_number,
				'event_data'      => wp_json_encode( $event_data ),
				'utm_source'      => $final_utm_source,
				'utm_medium'      => $final_utm_medium,
				'utm_campaign'    => $final_utm_campaign,
				'source'          => $source,
				'exit_page'       => $exit_page,
				'country'         => $this->get_session_country( $session_id ),
				'browser'         => $browser,
				'device'          => $device,
				'event_timestamp' => current_time( 'mysql' ),
			)
		);
	}

	private function get_session_country( $session_id ) {
		$active_sessions  = get_option( 'StorePulse_active_sessions', array() );
		return $active_sessions[ $session_id ]['country'] ?? 'Unknown';
	}
}

WC_UTM_Tracker::get_instance();