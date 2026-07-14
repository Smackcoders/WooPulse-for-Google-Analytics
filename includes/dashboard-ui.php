<?php
/**
 * Dashboard UI for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

// Redundant menu registration removed. Main menu is handled in includes/connector.php.
// add_action('admin_menu', function () {
// add_menu_page('StorePulse Dashboard', 'StorePulse', 'manage_options', 'StorePulse-dashboard', 'StorePulse_render_dashboard');
// });.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function StorePulse_render_dashboard() {
	$metrics = StorePulse_get_aggregated_metrics();
	?>
	<div class="wrap" style="max-width: 1000px; margin: 15px auto; padding: 0 20px;">
		<h1 style="font-size: 24px; font-weight: 600; color: #1d2327; margin-bottom: 24px;">StorePulse Dashboard</h1>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
			<div class="settings-card" style="margin-bottom: 0;">
				<h2 style="font-size: 16px; font-weight: 600; color: #50575e; margin: 0;">Sessions</h2>
				<p style="font-size: 32px; font-weight: 700; color: #3d4fdb; margin: 8px 0 0 0; line-height: 1.2;"><?php echo esc_html( $metrics['sessions'] ); ?></p>
			</div>
			<div class="settings-card" style="margin-bottom: 0;">
				<h2 style="font-size: 16px; font-weight: 600; color: #50575e; margin: 0;">Revenue</h2>
				<p style="font-size: 32px; font-weight: 700; color: #00a32a; margin: 8px 0 0 0; line-height: 1.2;">$<?php echo number_format( $metrics['revenue'], 2 ); ?></p>
			</div>
			<div class="settings-card" style="margin-bottom: 0;">
				<h2 style="font-size: 16px; font-weight: 600; color: #50575e; margin: 0;">Conversion Rate</h2>
				<p style="font-size: 32px; font-weight: 700; color: #2271b1; margin: 8px 0 0 0; line-height: 1.2;"><?php echo esc_html( round( $metrics['conversion_rate'], 2 ) ); ?>%</p>
			</div>
		</div>
		<p style="font-size: 14px; color: #a7aaad; margin-top: 24px;">Last updated: <?php echo esc_html( current_time( 'F j, Y g:i a' ) ); ?></p>
	</div>
	<?php
}
