<?php
/**
 * Audit logs PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access audit logs.' );
}

$StorePulse_audit_logs = \SmackCoders\WGA\GA_Reporter::get_audit_logs( '', '', $event_type_filter, '', 100 );
$StorePulse_users      = \SmackCoders\WGA\GA_Reporter::get_audit_log_users();
?>

<div class="wrap" id="StorePulse-pro-audit-logs"
	style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<!-- Filters -->
	<div class="bg-white p-4 rounded-lg shadow border border-gray-100 mb-6">
		<form method="GET" class="flex gap-4 items-end">
			<input type="hidden" name="page" value="StorePulse-pro-audit-logs">
			<?php wp_nonce_field( 'StorePulse_filter_audit_logs', 'StorePulse_audit_nonce', false ); ?>

			<div class="flex-1">
				<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Date From</label>
				<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>"
					class="w-full p-2 border border-gray-200 rounded text-sm">
			</div>

			<div class="flex-1">
				<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Date To</label>
				<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>"
					class="w-full p-2 border border-gray-200 rounded text-sm">
			</div>

			<div class="flex-1">
				<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Search</label>
				<input type="text" name="event_type" value="<?php echo esc_attr( $event_type_filter ?? '' ); ?>"
					placeholder="Search changes..." class="w-full p-2 border border-gray-200 rounded text-sm">
			</div>

			<div class="flex-1">
				<label class="block text-xs font-bold text-gray-400 uppercase mb-2">User</label>
				<select name="user_id" class="w-full p-2 border border-gray-200 rounded text-sm">
					<option value="">All Users</option>
					<?php foreach ( $StorePulse_users as $StorePulse_user ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_user['ID'] ); ?>" <?php echo selected( $user_filter ?? 0, $StorePulse_user['ID'], false ); ?>>
							<?php echo esc_html( ! empty( $StorePulse_user['display_name'] ) ? $StorePulse_user['display_name'] : $StorePulse_user['user_login'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
				Filter
			</button>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=StorePulse-pro-audit-logs' ) ); ?>"
				class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
				Clear
			</a>
		</form>
	</div>

	<!-- Audit Log Table -->
	<div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
		<table class="w-full text-sm StorePulse-alternating">
			<thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-widest">
				<tr>
					<th class="p-4 text-left">Date & Time</th>
					<th class="p-4 text-left">User</th>
					<th class="p-4 text-left">Change</th>
					<th class="p-4 text-left">Details</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-50">
				<?php if ( empty( $StorePulse_audit_logs ) ) : ?>
					<tr>
						<td colspan="4" class="p-12 text-center text-gray-400">
							No audit logs found. Settings changes will be logged here.
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $StorePulse_audit_logs as $StorePulse_log ) : ?>
						<?php
						$StorePulse_user_obj     = get_user_by( 'id', $StorePulse_log['user_id'] ?? 0 );
						$StorePulse_user_name    = $StorePulse_user_obj ? ( ! empty( $StorePulse_user_obj->display_name ) ? $StorePulse_user_obj->display_name : $StorePulse_user_obj->user_login ) : 'System';
						$StorePulse_message_data = json_decode( $StorePulse_log['message'] ?? '{}', true );
						if ( ! is_array( $StorePulse_message_data ) ) {
							$StorePulse_message_data = array( 'description' => $StorePulse_log['message'] ?? 'Settings changed' );
						}
						?>
						<tr class="odd:bg-blue-50 even:bg-white hover:bg-gray-100 transition-colors">
							<td class="p-4">
								<div class="font-medium text-gray-800">
									<?php echo esc_html( gmdate( 'M j, Y', strtotime( $StorePulse_log['created_at'] ) ) ); ?>
								</div>
								<div class="text-xs text-gray-500">
									<?php echo esc_html( gmdate( 'g:i A', strtotime( $StorePulse_log['created_at'] ) ) ); ?>
								</div>
							</td>
							<td class="p-4">
								<div class="font-medium text-gray-700"><?php echo esc_html( $StorePulse_user_name ); ?></div>
							</td>
							<td class="p-4">
								<div class="font-semibold text-gray-800">
									<?php echo esc_html( $StorePulse_message_data['action'] ?? 'Settings Updated' ); ?>
								</div>
							</td>
							<td class="p-4">
								<div class="text-xs text-gray-600">
									<?php if ( isset( $StorePulse_message_data['setting'] ) ) : ?>
										<strong><?php echo esc_html( $StorePulse_message_data['setting'] ); ?></strong>
										<?php if ( isset( $StorePulse_message_data['old_value'] ) && isset( $StorePulse_message_data['new_value'] ) ) : ?>
											<br>
											<span class="text-red-600"><?php echo esc_html( substr( $StorePulse_message_data['old_value'], 0, 50 ) ); ?></span>
											<span class="mx-2">→</span>
											<span class="text-green-600"><?php echo esc_html( substr( $StorePulse_message_data['new_value'], 0, 50 ) ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										<?php echo esc_html( $StorePulse_message_data['description'] ?? '' ); ?>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>