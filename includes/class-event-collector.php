<?php
/**
 * Event collector class for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Event_Collector {


	public function __construct() {

		add_action( 'woocommerce_order_status_completed', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_failed', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'capture_event' ) );
		add_action( 'woocommerce_order_status_pending', array( $this, 'capture_event' ) );
	}

	public function capture_event( $order_id ) {
		global $wpdb;
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$event_type      = 'order_' . $order->get_status();
		$event_timestamp = current_time( 'mysql' );

		// Prepare data for JSON file and DB.
		$event_data = array(
			'event_type' => $event_type,
			'event_time' => $event_timestamp,
			'order_id'   => $order->get_id(),
			'status'     => $order->get_status(),
			'total'      => $order->get_total(),
			'currency'   => $order->get_currency(),
			'customer'   => array(
				'email' => $order->get_billing_email(),
			),
			'utm'        => array(
				'utm_source'   => $order->get_meta( 'utm_source' ),
				'utm_medium'   => $order->get_meta( 'utm_medium' ),
				'utm_campaign' => $order->get_meta( 'utm_campaign' ),
			),
		);

		// Store in JSON file (legacy/backup).
		$this->store_json( $event_data );

		// Capture Basic Browser/Device Info.
		$ua         = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$browser    = StorePulse_get_browser( $ua );
		$device     = StorePulse_get_device( $ua );
		$session_id = StorePulse_get_session_id();

		// Store in Database (Primary for reports).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$res = $wpdb->insert(
			$wpdb->prefix . 'storepulse_events',
			array(
				'event_type'      => $event_type,
				'session_id'      => $session_id,
				'anon_id'         => \StorePulse_get_anon_id(),
				'order_id'        => $order->get_id(),
				'user_id'         => ! empty( $order->get_customer_id() ) ? $order->get_customer_id() : ( \get_current_user_id() ?: null ),
				'event_data'      => wp_json_encode( $event_data ),
				'utm_source'      => $order->get_meta( 'utm_source' ),
				'utm_medium'      => $order->get_meta( 'utm_medium' ),
				'utm_campaign'    => $order->get_meta( 'utm_campaign' ),
				'source'          => 'woocommerce',
				'browser'         => $browser,
				'device'          => $device,
				'event_timestamp' => $event_timestamp,
			)
		);

		// error_log('after DB inserted'.PHP_EOL,3,LOGPATH);.
		if ( is_wp_error( $res ) ) {
			// error_log('StorePulse: Failed to insert event: ' . $res->get_error_message(),3,LOGPATH);.
		}

		// Send Webhook if configured.
		$this->send_webhook( $event_data );
	}

	private function store_json( $data ) {
		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/StorePulse-events.json';

		$existing = array();
		if ( file_exists( $file_path ) ) {
			$content  = file_get_contents( $file_path );
			$existing = json_decode( $content, true );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}
		}

		$existing[] = $data;
		// Keep only last 1000 events in JSON to prevent file bloat.
		if ( count( $existing ) > 1000 ) {
			$existing = array_slice( $existing, -1000 );
		}
		file_put_contents( $file_path, wp_json_encode( $existing, JSON_PRETTY_PRINT ) );
	}

	private function send_webhook( $data ) {
		$webhook_url = get_option( 'StorePulse_webhook_url' );
		if ( ! $webhook_url ) {
			return;
		}

		wp_remote_post(
			$webhook_url,
			array(
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => wp_json_encode( $data ),
				'method'   => 'POST',
				'blocking' => false, // Non-blocking to avoid slowing down order processing.
			)
		);
	}
}

new Event_Collector();
