<?php
/**
 * Metrics display for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$storepulse_settings    = get_option( 'storepulse_settings', array() );
$StorePulse_property_id = $storepulse_settings['property_id'] ?? '';
?>

<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Goals</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2">Monitor your site's conversion goals and raw metrics.</p>
</div>

<div class="StorePulse-metrics-view" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; color: #3c434a;">

	<!-- Active Connectivity Check -->
	<div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 30px;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h3 style="margin: 0; font-size: 1.3em;">GA4 Performance Tables</h3>
			<div id="metrics-status-badge" style="background: #f0f0f1; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970;">
				Initializing Data...
			</div>
		</div>

		<div style="display: flex; gap: 15px; margin-bottom: 25px; align-items: center;">
			<div style="background: #f0f0f1; border: 1px solid #dcdcde; padding: 5px 12px; border-radius: 4px; display: flex; align-items: center;">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; color: #2271b1;">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
				<input type="text" id="metricsDateRange" placeholder="Select Date Range" style="border: none; background: transparent; font-size: 13px; width: 200px; padding: 0; box-shadow: none;" readonly>
			</div>
			<p class="description" style="margin: 0;">Select a range to refresh the tables below.</p>
		</div>

		<!-- Metric Table 1: Traffic & Engagement -->
		<h4 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 15px; font-weight: 600;">📈 Site Traffic Summary</h4>
		<table class="wp-list-table widefat fixed striped" style="margin-bottom: 30px;">
			<thead>
				<tr>
					<th style="font-weight: 600;">Metric Name</th>
					<th style="font-weight: 600; text-align: right;">Total Value</th>
				</tr>
			</thead>
			<tbody id="metricsSummaryBody">
				<tr><td colspan="2" style="padding: 20px; text-align: center; color: #8c8f94;">Loading GA4 summary...</td></tr>
			</tbody>
		</table>

		<!-- Metric Table 2: Top Pages (GA4 Content) -->
		<h4 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 15px; font-weight: 600;">📄 Top Content Performance (GA4)</h4>
		<table class="wp-list-table widefat fixed striped" style="margin-bottom: 30px;">
			<thead>
				<tr>
					<th style="font-weight: 600; width: 60%;">Page Path</th>
					<th style="font-weight: 600; text-align: right;">Screen Views</th>
					<th style="font-weight: 600; text-align: right;">Users</th>
				</tr>
			</thead>
			<tbody id="metricsPagesBody">
				<tr><td colspan="3" style="padding: 20px; text-align: center; color: #8c8f94;">Fetching page data...</td></tr>
			</tbody>
		</table>

		<!-- Metric Table 3: Traffic Sources -->
		<h4 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 15px; font-weight: 600;">🔗 Traffic Acquisition (Source / Medium)</h4>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="font-weight: 600; width: 70%;">Source / Medium</th>
					<th style="font-weight: 600; text-align: right;">Sessions</th>
				</tr>
			</thead>
			<tbody id="metricsSourcesBody">
				<tr><td colspan="2" style="padding: 20px; text-align: center; color: #8c8f94;">Identifying traffic sources...</td></tr>
			</tbody>
		</table>
	</div>

</div>

