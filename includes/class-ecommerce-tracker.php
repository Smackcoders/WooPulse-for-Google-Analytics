<?php
/**
 * Enhanced Ecommerce event tracker for StorePulse analytics plugin.
 * Handles GA4 client-side events for WooCommerce.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Enhanced_Ecommerce_Tracker {

	private static $instance = null;

	private function __construct() {
		// Only run if the user has enabled enhanced ecommerce tracking.
		$settings = get_option( 'storepulse_settings', array() );
		if ( empty( $settings['enable_goals'] ) || '1' !== $settings['enable_goals'] ) {
			return;
		}

		add_action( 'woocommerce_before_single_product', array( $this, 'track_view_item' ), 20 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 20, 6 );
		add_action( 'woocommerce_thankyou', array( $this, 'track_purchase' ), 20, 1 );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Checks if the GA4 measurement ID is configured.
	 */
	private function has_measurement_id() {
		$measurement_id = get_option( 'storepulse_ga4_measurement_id', '' );
		return ! empty( $measurement_id );
	}

	/**
	 * Helper to format a WC Product into a GA4 Item object.
	 */
	private function format_ga4_item( $product, $quantity = 1 ) {
		return array(
			'item_id'   => (string) $product->get_id(),
			'item_name' => $product->get_name(),
			'price'     => (float) $product->get_price(),
			'quantity'  => (int) $quantity,
		);
	}

	/**
	 * Output GA4 view_item event on single product pages.
	 */
	public function track_view_item() {
		if ( ! $this->has_measurement_id() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$item = $this->format_ga4_item( $product );
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

		$event_data = array(
			'currency' => $currency,
			'value'    => $item['price'],
			'items'    => array( $item ),
		);

		wc_enqueue_js( "
			if (typeof gtag === 'function') {
				gtag('event', 'view_item', " . wp_json_encode( $event_data ) . ");
			}
		" );
	}

	/**
	 * Output GA4 add_to_cart event.
	 */
	public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( ! $this->has_measurement_id() ) {
			return;
		}

		$id_to_load = $variation_id ? $variation_id : $product_id;
		$product = wc_get_product( $id_to_load );
		if ( ! $product ) {
			return;
		}

		$item = $this->format_ga4_item( $product, $quantity );
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

		$event_data = array(
			'currency' => $currency,
			'value'    => $item['price'] * $quantity,
			'items'    => array( $item ),
		);

		wc_enqueue_js( "
			if (typeof gtag === 'function') {
				gtag('event', 'add_to_cart', " . wp_json_encode( $event_data ) . ");
			}
		" );
	}

	/**
	 * Output GA4 purchase event on the thank you page.
	 */
	public function track_purchase( $order_id ) {
		if ( ! $order_id || ! $this->has_measurement_id() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Order status guard: only fire for processing or completed.
		if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
			return; // Silently skip
		}

		// Deduplication guard.
		if ( $order->get_meta( '_storepulse_ga4_purchase_tracked' ) ) {
			return; // Already tracked
		}

		$items = array();
		foreach ( $order->get_items() as $item_id => $order_item ) {
			$product = $order_item->get_product();
			if ( $product ) {
				$items[] = $this->format_ga4_item( $product, $order_item->get_quantity() );
			}
		}

		$event_data = array(
			'transaction_id' => (string) $order->get_id(),
			'value'          => (float) $order->get_total(),
			'tax'            => (float) $order->get_total_tax(),
			'shipping'       => (float) $order->get_shipping_total(),
			'currency'       => $order->get_currency(),
			'items'          => $items,
		);

		// Output the script in the browser.
		wc_enqueue_js( "
			if (typeof gtag === 'function') {
				gtag('event', 'purchase', " . wp_json_encode( $event_data ) . ");
			}
		" );

		// Mark as tracked to prevent duplicates on refresh.
		$order->update_meta_data( '_storepulse_ga4_purchase_tracked', '1' );
		$order->save();
	}
}

WC_Enhanced_Ecommerce_Tracker::get_instance();
