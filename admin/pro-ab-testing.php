<?php
/**
 * A/B testing PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access A/B testing insights.' );
}

?>

<div class="wrap" id="StorePulse-pro-ab-testing"
	style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

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
			<button id="load-experiments"
				class="bg-indigo-600 text-white px-6 py-2 rounded font-medium hover:bg-indigo-700">
				Load Experiments
			</button>
		</div>
	</div>

	<!-- Experiments List -->
	<div id="experiments-content" class="bg-white p-6 rounded-lg shadow border border-gray-100">
		<div class="text-center text-gray-400 py-12">
			<p>Select a date range and click "Load Experiments" to view A/B test data</p>
		</div>
	</div>
</div>
