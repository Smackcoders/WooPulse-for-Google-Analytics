<?php
/**
 * Report builder PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access report builder.' );
}

// Available GA4 metrics and dimensions (allowlist).
$StorePulse_available_metrics = array(
	array(
		'name'  => 'sessions',
		'label' => 'Sessions',
	),
	array(
		'name'  => 'activeUsers',
		'label' => 'Active Users',
	),
	array(
		'name'  => 'screenPageViews',
		'label' => 'Page Views',
	),
	array(
		'name'  => 'totalUsers',
		'label' => 'Total Users',
	),
	array(
		'name'  => 'newUsers',
		'label' => 'New Users',
	),
	array(
		'name'  => 'eventCount',
		'label' => 'Event Count',
	),
	array(
		'name'  => 'conversions',
		'label' => 'Conversions',
	),
	array(
		'name'  => 'totalRevenue',
		'label' => 'Total Revenue',
	),
	array(
		'name'  => 'averageSessionDuration',
		'label' => 'Avg Session Duration',
	),
	array(
		'name'  => 'bounceRate',
		'label' => 'Bounce Rate',
	),
);

$StorePulse_available_dimensions = array(
	array(
		'name'  => 'date',
		'label' => 'Date',
	),
	array(
		'name'  => 'country',
		'label' => 'Country',
	),
	array(
		'name'  => 'city',
		'label' => 'City',
	),
	array(
		'name'  => 'deviceCategory',
		'label' => 'Device Category',
	),
	array(
		'name'  => 'browser',
		'label' => 'Browser',
	),
	array(
		'name'  => 'operatingSystem',
		'label' => 'Operating System',
	),
	array(
		'name'  => 'source',
		'label' => 'Source',
	),
	array(
		'name'  => 'medium',
		'label' => 'Medium',
	),
	array(
		'name'  => 'campaignName',
		'label' => 'Campaign',
	),
	array(
		'name'  => 'pagePath',
		'label' => 'Page Path',
	),
	array(
		'name'  => 'pageTitle',
		'label' => 'Page Title',
	),
	array(
		'name'  => 'eventName',
		'label' => 'Event Name',
	),
);
?>

<div class="wrap" id="StorePulse-pro-report-builder"
	style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		<!-- Left: Available Metrics & Dimensions -->
		<div class="lg:col-span-1 space-y-4">
			<!-- Metrics -->
			<div class="bg-white p-4 rounded-lg shadow border border-gray-100">
				<h3 class="text-[1rem] font-bold text-gray-800 mb-3">Available Metrics</h3>
				<div id="metrics-list" class="space-y-2">
					<?php foreach ( $StorePulse_available_metrics as $StorePulse_metric ) : ?>
						<div class="metric-item p-2 bg-gray-50 rounded border border-gray-200 cursor-move hover:bg-indigo-50 transition-colors"
							draggable="true" data-type="metric" data-name="<?php echo esc_attr( $StorePulse_metric['name'] ); ?>">
							<span class="text-xs font-medium text-gray-700"><?php echo esc_html( $StorePulse_metric['label'] ); ?></span>
							<span class="text-[10px] text-gray-400 block"><?php echo esc_html( $StorePulse_metric['name'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Dimensions -->
			<div class="bg-white p-4 rounded-lg shadow border border-gray-100">
				<h3 class="text-[1rem] font-bold text-gray-800 mb-3">Available Dimensions</h3>
				<div id="dimensions-list" class="space-y-2">
					<?php foreach ( $StorePulse_available_dimensions as $StorePulse_dimension ) : ?>
						<div class="dimension-item p-2 bg-gray-50 rounded border border-gray-200 cursor-move hover:bg-indigo-50 transition-colors"
							draggable="true" data-type="dimension" data-name="<?php echo esc_attr( $StorePulse_dimension['name'] ); ?>">
							<span class="text-xs font-medium text-gray-700"><?php echo esc_html( $StorePulse_dimension['label'] ); ?></span>
							<span class="text-[10px] text-gray-400 block"><?php echo esc_html( $StorePulse_dimension['name'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- Center: Report Builder -->
		<div class="lg:col-span-2 space-y-4">
			<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
				<h3 class="text-[1rem] font-bold text-gray-800 mb-4">Report Configuration</h3>

				<!-- Date Range -->
				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Date Range</label>
					<div class="flex gap-2">
						<input type="date" id="start-date" class="flex-1 p-2 border border-gray-200 rounded text-sm"
							value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-7 days' ) ) ); ?>">
						<span class="self-center text-gray-400">to</span>
						<input type="date" id="end-date" class="flex-1 p-2 border border-gray-200 rounded text-sm"
							value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
					</div>
				</div>

				<!-- Selected Metrics -->
				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Selected Metrics</label>
					<div id="selected-metrics"
						class="min-h-[60px] p-3 border-2 border-dashed border-gray-200 rounded bg-gray-50">
						<p class="text-xs text-gray-400 text-center">Drag metrics here</p>
					</div>
				</div>

				<!-- Selected Dimensions -->
				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Selected Dimensions</label>
					<div id="selected-dimensions"
						class="min-h-[60px] p-3 border-2 border-dashed border-gray-200 rounded bg-gray-50">
						<p class="text-xs text-gray-400 text-center">Drag dimensions here</p>
					</div>
				</div>

				<!-- Report Name (for saving) -->
				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Report Name (Optional)</label>
					<input type="text" id="report-name" class="w-full p-2 border border-gray-200 rounded text-sm"
						placeholder="My Custom Report">
				</div>

				<!-- Actions -->
				<div class="flex gap-2">
					<button id="run-report"
						class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded font-medium hover:bg-indigo-700 transition-colors">
						Run Report
					</button>
					<button id="save-report"
						class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition-colors">
						Save Report
					</button>
				</div>
			</div>

			<!-- Results -->
			<div id="report-results" class="bg-white p-6 rounded-lg shadow border border-gray-100 hidden">
				<h3 class="text-[1rem] font-bold text-gray-800 mb-4">Report Results</h3>
				<div id="results-content" class="overflow-x-auto">
					<!-- Results will be rendered here -->
				</div>
			</div>
		</div>
	</div>
</div>
