<?php
/**
 * Link report for Pulse Analytics.
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

<div class="<?php echo esc_attr( sm_pulse_analytics_get_admin_ui_classes( 'wrap animate-fade-in relative' ) ); ?>" id="PulseAnalytics-links-page">

	<!-- ── Tab Bar ───────────────────────────────────────────────── -->
	<div class="pulse-analytics-segmented-bar mb-4 relative z-10 sp-pointer-auto">
		<button type="button" class="pulse-analytics-segmented-tab links-subtab active" data-target="links-tab-outbound">
			<?php esc_html_e( 'Outbound Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</button>
		<button type="button" class="pulse-analytics-segmented-tab links-subtab" data-target="links-tab-downloads">
			<?php esc_html_e( 'File Downloads', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</button>
		<button type="button" class="pulse-analytics-segmented-tab links-subtab" data-target="links-tab-demo">
			<?php esc_html_e( 'Demographics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</button>
	</div>

	<div>
		<?php
		
		?>
		<div id="PulseAnalytics-links-v2">

			<!-- ═══════════════════════════════════════════════════════ -->
			<!-- Tab 1: Outbound Links                                   -->
			<!-- ═══════════════════════════════════════════════════════ -->
			<div id="links-tab-outbound" class="links-tab-content">

				<!-- Demo-data notice (shown by JS when falling back to sample data) -->
				<div id="link-outbound-demo-notice" class="sp-card mb-4 hidden sp-notice-info">
					<p class="text-sm text-blue-700 m-0">
						<strong><?php esc_html_e( 'GA4 unavailable:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Connect Google under Settings → Analytics Configuration to load live link clicks. Showing sample data until then.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="sp-grid-2 mb-6">
					<!-- Inbound Links -->
					<div class="sp-card">
						<h3 class="sp-section-title"><?php esc_html_e( 'Inbound Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
						<div class="sp-table-wrap">
							<table class="sp-table w-full text-left border-collapse text-xs">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Source URL', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
										<th class="text-right"><?php esc_html_e( 'Clicks', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									</tr>
								</thead>
								<tbody id="inbound-links-body">
									<tr><td colspan="2" class="text-center text-slate-400 italic py-8"><?php esc_html_e( 'Loading…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td></tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Outbound Links -->
					<div class="sp-card">
						<h3 class="sp-section-title"><?php esc_html_e( 'Outbound Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
						<div class="sp-table-wrap">
							<table class="sp-table w-full text-left border-collapse text-xs">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Destination URL', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
										<th class="text-right"><?php esc_html_e( 'Clicks', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									</tr>
								</thead>
								<tbody id="outbound-links-body">
									<tr><td colspan="2" class="text-center text-slate-400 italic py-8"><?php esc_html_e( 'Loading…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td></tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- Affiliate Links (full width) -->
				<div class="sp-card">
					<h3 class="sp-section-title"><?php esc_html_e( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
					<div class="sp-table-wrap">
						<table class="sp-table w-full text-left border-collapse text-xs">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Affiliate URL', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<th class="text-right"><?php esc_html_e( 'Clicks', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody id="affiliate-links-body">
								<tr><td colspan="2" class="text-center text-slate-400 italic py-8"><?php esc_html_e( 'Loading…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div><!-- /links-tab-outbound -->

			<!-- ═══════════════════════════════════════════════════════ -->
			<!-- Tab 2: File Downloads                                   -->
			<!-- ═══════════════════════════════════════════════════════ -->
			<div id="links-tab-downloads" class="links-tab-content hidden">

				<!-- Demo-data notice (shown by JS when falling back to sample data) -->
				<div id="link-downloads-demo-notice" class="sp-card mb-4 hidden sp-notice-info">
					<p class="text-sm text-blue-700 m-0">
						<strong><?php esc_html_e( 'GA4 unavailable:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Connect Google under Settings → Analytics Configuration to load live download events. Showing sample data until then.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="sp-card">
					<h3 class="sp-section-title"><?php esc_html_e( 'Downloadable Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
					<div class="sp-table-wrap">
						<table class="sp-table w-full text-left border-collapse text-xs">
							<thead>
								<tr>
									<th><?php esc_html_e( 'File URL', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<th class="text-right"><?php esc_html_e( 'Downloads', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody id="downloadable-links-body">
								<tr><td colspan="2" class="text-center text-slate-400 italic py-8"><?php esc_html_e( 'Loading…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div><!-- /links-tab-downloads -->

			<!-- ═══════════════════════════════════════════════════════ -->
			<!-- Tab 3: Demographics                                     -->
			<!-- ═══════════════════════════════════════════════════════ -->
			<div id="links-tab-demo" class="links-tab-content hidden">

				<!-- GA4 Signals info notice (shown by JS when data is sample) -->
				<div id="link-demo-sample-notice" class="sp-card mb-4 hidden sp-notice-warning">
					<p class="text-sm text-yellow-700 m-0">
						<strong><?php esc_html_e( 'No demographics yet:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Age and gender require Google Signals in your GA4 property plus enough traffic. Connect Analytics if reports are empty.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="sp-grid-2">
					<!-- Age Distribution (was "Interested Fields") -->
					<div class="sp-card relative">
						<h3 class="sp-section-title"><?php esc_html_e( 'Age Distribution', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
						<div id="age-no-data" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-90 z-10 p-6 text-center rounded-2xl">
							<div class="PulseAnalytics-empty-state-icon">
								<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
									<path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
									<path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
								</svg>
							</div>
							<p class="text-gray-400 font-medium text-sm"><?php esc_html_e( 'No Age data available.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
							<p class="text-xs text-gray-400 mt-2"><?php esc_html_e( 'Demographics require Google Signals in GA4.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
						</div>
						<div class="sp-chart-wrap-sm">
							<canvas id="age-chart"></canvas>
						</div>
						<p class="text-center text-sm text-slate-400 font-medium mt-2 mb-0"><?php esc_html_e( 'Age Bracket', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
					</div>

					<!-- Gender (Demographics) -->
					<div class="sp-card relative">
						<h3 class="sp-section-title"><?php esc_html_e( 'Gender', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
						<div id="gender-no-data" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-90 z-10 p-6 text-center rounded-2xl">
							<div class="PulseAnalytics-empty-state-icon">
								<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
									<path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
									<path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
								</svg>
							</div>
							<p class="text-gray-400 font-medium text-sm"><?php esc_html_e( 'No Gender data available.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
							<p class="text-xs text-gray-400 mt-2"><?php esc_html_e( 'Enable Google Signals in your GA4 property.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
						</div>
						<div class="h-56 flex items-center justify-center">
							<canvas class="sp-chart-canvas-sm" id="gender-chart"></canvas>
						</div>
						<p class="text-center text-sm text-slate-400 font-medium mt-2 mb-0"><?php esc_html_e( 'Gender', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div><!-- /links-tab-demo -->

		</div><!-- /#PulseAnalytics-links-v2 -->
	</div>
</div>
