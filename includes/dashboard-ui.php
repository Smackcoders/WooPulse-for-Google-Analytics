<?php
/**
 * Dashboard UI for Pulse Analytics.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

// Redundant menu registration removed. Main menu is handled in includes/connector.php.
// add_action('admin_menu', function () {
// add_menu_page('Pulse Analytics Dashboard', 'smackcoders-pulse-analytics-for-woocommerce', 'manage_options', 'pulse-analytics', 'sm_pulse_analytics_render_dashboard');
// });.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function sm_pulse_analytics_render_dashboard() {
	$metrics = sm_pulse_analytics_get_aggregated_metrics();
	?>
	<div class="wrap sp-dashboard-legacy-wrap">
		<h1 class="sp-dashboard-legacy-title">Pulse Analytics Dashboard</h1>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
			<div class="settings-card sp-mb-0">
				<h2 class="sp-kpi-label">Sessions</h2>
				<p class="sp-kpi-value sp-kpi-value--indigo"><?php echo esc_html( $metrics['sessions'] ); ?></p>
			</div>
			<div class="settings-card sp-mb-0">
				<h2 class="sp-kpi-label">Revenue</h2>
				<p class="sp-kpi-value sp-kpi-value--green">$<?php echo number_format( $metrics['revenue'], 2 ); ?></p>
			</div>
			<div class="settings-card sp-mb-0">
				<h2 class="sp-kpi-label">Conversion Rate</h2>
				<p class="sp-kpi-value sp-kpi-value--blue"><?php echo esc_html( round( $metrics['conversion_rate'], 2 ) ); ?>%</p>
			</div>
		</div>
		<p class="sp-dashboard-updated">Last updated: <?php echo esc_html( current_time( 'F j, Y g:i a' ) ); ?></p>
	</div>
	<?php
}
