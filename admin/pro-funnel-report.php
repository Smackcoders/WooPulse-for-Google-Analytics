<?php
/**
 * Funnel report PRO feature for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check PRO license.
if ( ! function_exists( 'StorePulse_is_pro_active' ) ) {
	require_once GA_PLUGIN_DIR . 'includes/helpers.php';
}

if ( ! StorePulse_is_pro_active() ) {
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access funnel report.' );
}

$StorePulse_funnel_id          = isset( $_GET['funnel_id'] ) ? sanitize_text_field( wp_unslash( $_GET['funnel_id'] ) ) : '';
$StorePulse_funnel_definitions = get_option( 'StorePulse_funnel_definitions', array() );

if ( empty( $StorePulse_funnel_id ) || ! isset( $StorePulse_funnel_definitions[ $StorePulse_funnel_id ] ) ) {
	wp_safe_redirect( admin_url( 'admin.php?page=StorePulse-pro-funnel-builder' ) );
	exit;
}

// Security: Verify nonce for reports.
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'view_funnel_report_' . $StorePulse_funnel_id ) ) {
	wp_die( 'Invalid security token. Please access the report from the Funnel Builder.' );
}

$StorePulse_current_funnel = $StorePulse_funnel_definitions[ $StorePulse_funnel_id ];
?>

<div class="wrap" id="StorePulse-pro-funnel-report" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<!-- Date Range Filter -->
	<div class="bg-white p-4 rounded-lg shadow border border-gray-100 mb-6">
		<div class="flex gap-4 items-end">
			<div class="flex-1">
				<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Date Range</label>
				<div class="flex gap-2">
					<input type="date" id="start-date" class="flex-1 p-2 border border-gray-200 rounded text-sm" 
							value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>">
					<span class="self-center text-gray-400">to</span>
					<input type="date" id="end-date" class="flex-1 p-2 border border-gray-200 rounded text-sm" 
							value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
				</div>
			</div>
			<button id="load-report" class="bg-indigo-600 text-white px-6 py-2 rounded font-medium hover:bg-indigo-700">
				Load Report
			</button>
		</div>
	</div>

	<!-- Funnel Visualization -->
	<div id="funnel-results" class="bg-white p-6 rounded-lg shadow border border-gray-100">
		<div class="text-center text-gray-400 py-12">
			<p>Select a date range and click "Load Report" to view funnel data</p>
		</div>
	</div>
</div>

