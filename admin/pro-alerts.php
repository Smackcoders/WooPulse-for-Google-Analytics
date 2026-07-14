<?php
/**
 * Alerts PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access proactive alerts.' );
}

// Handle form submissions.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['StorePulse_save_alert'] ) && check_admin_referer( 'StorePulse_save_alert' ) ) {
	$StorePulse_alerts   = get_option( 'StorePulse_alerts', array() );
	$StorePulse_alert_id = isset( $_POST['alert_id'] ) ? sanitize_text_field( wp_unslash( $_POST['alert_id'] ) ) : '';

	$StorePulse_alert = array(
		'id'         => ! empty( $StorePulse_alert_id ) ? $StorePulse_alert_id : uniqid(),
		'name'       => isset( $_POST['alert_name'] ) ? sanitize_text_field( wp_unslash( $_POST['alert_name'] ) ) : '',
		'metric'     => isset( $_POST['metric'] ) ? sanitize_text_field( wp_unslash( $_POST['metric'] ) ) : '',
		'threshold'  => isset( $_POST['threshold'] ) ? floatval( $_POST['threshold'] ) : 0,
		'condition'  => isset( $_POST['condition'] ) ? sanitize_text_field( wp_unslash( $_POST['condition'] ) ) : '',
		'enabled'    => isset( $_POST['enabled'] ) ? 1 : 0,
		'created_at' => current_time( 'mysql' ),
	);

	if ( $StorePulse_alert_id && isset( $StorePulse_alerts[ $StorePulse_alert_id ] ) ) {
		$StorePulse_alerts[ $StorePulse_alert_id ] = $StorePulse_alert;
		$StorePulse_message                      = 'Alert updated successfully!';
	} else {
		$StorePulse_alerts[ $StorePulse_alert['id'] ] = $StorePulse_alert;
		$StorePulse_message                         = 'Alert created successfully!';
	}

	update_option( 'StorePulse_alerts', $StorePulse_alerts );
	echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html( $StorePulse_message ) . '</p></div>';
}

// Handle delete.
if ( isset( $_GET['delete_alert'] ) ) {
	$StorePulse_delete_alert_id = sanitize_text_field( wp_unslash( $_GET['delete_alert'] ) );
	if ( check_admin_referer( 'delete_alert_' . $StorePulse_delete_alert_id ) ) {
		$StorePulse_alerts = get_option( 'StorePulse_alerts', array() );
		if ( isset( $StorePulse_alerts[ $StorePulse_delete_alert_id ] ) ) {
			unset( $StorePulse_alerts[ $StorePulse_delete_alert_id ] );
			update_option( 'StorePulse_alerts', $StorePulse_alerts );
			echo '<div class="notice notice-info is-dismissible"><p>🗑️ Alert deleted.</p></div>';
		}
	}
}

$StorePulse_alerts    = get_option( 'StorePulse_alerts', array() );
$StorePulse_rest_base = rest_url( 'StorePulse/v1/' );
$StorePulse_nonce     = wp_create_nonce( 'wp_rest' );
?>

<div class="wrap" id="StorePulse-pro-alerts" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<!-- Alert Form -->
		<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
			<h3 class="text-sm font-bold text-gray-800 mb-4">Create Alert</h3>
			
			<form method="POST" id="alert-form">
				<?php wp_nonce_field( 'StorePulse_save_alert' ); ?>
				
				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Alert Name</label>
					<input type="text" name="alert_name" required
							class="w-full p-2 border border-gray-200 rounded text-sm"
							placeholder="e.g. Low Conversion Rate Alert">
				</div>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Metric</label>
					<select name="metric" class="w-full p-2 border border-gray-200 rounded text-sm" required>
						<option value="sessions">Sessions</option>
						<option value="conversion_rate">Conversion Rate</option>
						<option value="revenue">Revenue</option>
						<option value="avg_order_value">Average Order Value</option>
					</select>
				</div>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Condition</label>
					<select name="condition" class="w-full p-2 border border-gray-200 rounded text-sm" required>
						<option value="below">Below</option>
						<option value="above">Above</option>
					</select>
				</div>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Threshold</label>
					<input type="number" name="threshold" step="0.01" required
							class="w-full p-2 border border-gray-200 rounded text-sm"
							placeholder="e.g. 2.5">
				</div>

				<div class="mb-4">
					<label class="flex items-center">
						<input type="checkbox" name="enabled" value="1" checked class="mr-2">
						<span class="text-sm text-gray-700">Enable alert</span>
					</label>
				</div>

				<button type="submit" name="StorePulse_save_alert"
						class="w-full bg-indigo-600 text-white px-4 py-2 rounded font-medium hover:bg-indigo-700">
					Save Alert
				</button>
			</form>
		</div>

		<!-- Active Alerts -->
		<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
			<h3 class="text-sm font-bold text-gray-800 mb-4">Active Alerts</h3>
			
			<div id="alerts-list" class="space-y-3">
				<?php if ( empty( $StorePulse_alerts ) ) : ?>
					<p class="text-gray-400 text-sm text-center py-8">No alerts configured. Create one using the form on the left.</p>
				<?php else : ?>
					<?php foreach ( $StorePulse_alerts as $StorePulse_alert ) : ?>
						<div class="p-4 border border-gray-200 rounded">
							<div class="flex justify-between items-start mb-2">
								<div>
									<div class="font-semibold text-gray-800"><?php echo esc_html( $StorePulse_alert['name'] ); ?></div>
									<div class="text-xs text-gray-500">
										<?php echo esc_html( $StorePulse_alert['metric'] ); ?> 
										<?php echo esc_html( $StorePulse_alert['condition'] ); ?> 
										<?php echo esc_html( $StorePulse_alert['threshold'] ); ?>
									</div>
								</div>
								<div class="flex gap-2">
									<span class="text-xs px-2 py-1 rounded <?php echo $StorePulse_alert['enabled'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
										<?php echo $StorePulse_alert['enabled'] ? 'Active' : 'Inactive'; ?>
									</span>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=StorePulse-pro-alerts&delete_alert=' . $StorePulse_alert['id'] ), 'delete_alert_' . $StorePulse_alert['id'] ) ); ?>"
										class="text-red-600 hover:text-red-700 text-sm"
										onclick="return confirm('Delete this alert?')">Delete</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Note about Heatmaps and Session Recordings -->
	<div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
		<p class="text-sm text-blue-800">
			<strong>Note:</strong> Heatmaps and session recordings require third-party service integration. 
			The alert system is fully functional and will notify you via email when thresholds are breached.
		</p>
	</div>
</div>
