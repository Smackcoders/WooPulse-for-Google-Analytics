<?php
/**
 * Forms Conversion report admin page.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="<?php echo esc_attr( sm_pulse_analytics_get_admin_ui_classes( 'animate-fade-in relative' ) ); ?>" id="PulseAnalytics-forms-page">
	<div id="PulseAnalytics-forms-v2">

		<div class="sp-hidden" id="forms-data-notice"></div>

		<div class="sp-kpi-grid mb-6">
			<div class="sp-kpi">
				<div class="sp-kpi-label"><?php esc_html_e( 'Form Impressions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div class="sp-kpi-value" id="forms-kpi-views">0</div>
			</div>
			<div class="sp-kpi">
				<div class="sp-kpi-label"><?php esc_html_e( 'Form Starts', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div class="sp-kpi-value" id="forms-kpi-starts">0</div>
			</div>
			<div class="sp-kpi">
				<div class="sp-kpi-label"><?php esc_html_e( 'Conversions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div class="sp-kpi-value" id="forms-kpi-submissions">0</div>
			</div>
			<div class="sp-kpi">
				<div class="sp-kpi-label"><?php esc_html_e( 'Conversion Rate', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div class="sp-kpi-value" id="forms-kpi-conv">0%</div>
			</div>
		</div>

		<div class="sp-card mb-6">
			<div class="sp-section-toolbar">
				<h3 class="sp-section-title"><?php esc_html_e( 'Form Activity Trend', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
				<div class="sp-metrics-dropdown-wrap sp-relative">
					<button type="button" id="sp-metrics-dropdown-btn" class="button button-secondary sp-metrics-dropdown-btn">
						<span><?php esc_html_e( 'Metrics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
					</button>
					<div class="sp-metrics-dropdown-menu sp-hidden" id="sp-metrics-dropdown-menu">
						<label class="sp-metric-option">
							<input type="checkbox" class="sp-metric-checkbox" value="views" checked>
							<span class="sp-metric-swatch sp-metric-swatch--views"></span>
							<?php esc_html_e( 'Impressions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</label>
						<label class="sp-metric-option">
							<input type="checkbox" class="sp-metric-checkbox" value="starts" checked>
							<span class="sp-metric-swatch sp-metric-swatch--starts"></span>
							<?php esc_html_e( 'Form Starts', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</label>
						<label>
							<input type="checkbox" class="sp-metric-checkbox" value="submissions" checked>
							<span class="sp-metric-swatch sp-metric-swatch--submissions"></span>
							<?php esc_html_e( 'Conversions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</label>
					</div>
				</div>
			</div>
			<div class="sp-chart-wrap-md">
				<canvas id="forms-trend-chart"></canvas>
			</div>
		</div>

		<div class="sp-card">
			<div class="sp-section-toolbar sp-section-toolbar--no-wrap">
				<h3 class="sp-section-title"><?php esc_html_e( 'Forms', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
				<div class="sp-loading-indicator sp-hidden" id="forms-loading-indicator">
					<svg class="forms-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					<span><?php esc_html_e( 'Fetching forms data…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</div>
			</div>
			<div class="sp-table-wrap">
				<table class="sp-table forms-report-table">
					<thead>
						<tr>
							<th class="col-name"><?php esc_html_e( 'Form Name or ID', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
							<th class="col-num"><?php esc_html_e( 'Impressions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
							<th class="col-num"><?php esc_html_e( 'Form Starts', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
							<th class="col-num"><?php esc_html_e( 'Conversions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
							<th class="col-rate"><?php esc_html_e( 'Conversion Rate', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody id="forms-report-body">
						<tr>
							<td class="sp-table-empty sp-table-empty--lg" colspan="5">
								<div class="sp-loading-inline">
									<svg class="forms-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
										<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
									</svg>
									<span><?php esc_html_e( 'Loading forms data…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="sp-card mt-6 sp-hidden" id="forms-detail-panel">
			<h3 class="sp-section-title"><?php esc_html_e( 'Form Details', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
			<div id="forms-detail-content"></div>
		</div>

	</div>
</div>
