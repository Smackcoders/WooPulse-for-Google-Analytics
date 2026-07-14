<?php
/**
 * Helper action functions for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action(
	'wp_ajax_StorePulse_get_ga_overview',
	function () {
		$data = \SmackCoders\WGA\GA_Reporter::fetch_daily_metrics();
		wp_send_json( $data );
	}
);

add_action(
	'wp_ajax_StorePulse_get_top_pages',
	function () {
		$data = \SmackCoders\WGA\GA_Reporter::fetch_top_pages();
		wp_send_json( $data );
	}
);

add_action(
	'wp_ajax_StorePulse_get_top_countries',
	function () {
		$data = \SmackCoders\WGA\GA_Reporter::fetch_top_countries();
		wp_send_json( $data );
	}
);
