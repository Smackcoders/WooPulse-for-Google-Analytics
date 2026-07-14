<?php
/**
 * Funnel builder PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access funnel builder.' );
}

// Handle form submissions.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['StorePulse_save_funnel'] ) && check_admin_referer( 'StorePulse_save_funnel' ) ) {
	$StorePulse_funnels     = get_option( 'StorePulse_funnel_definitions', array() );
	$StorePulse_funnel_id   = isset( $_POST['funnel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['funnel_id'] ) ) : '';
	$StorePulse_funnel_name = isset( $_POST['funnel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['funnel_name'] ) ) : '';
	$StorePulse_steps       = isset( $_POST['steps'] ) && is_array( $_POST['steps'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['steps'] ) ) : array();

	$StorePulse_funnel_data = array(
		'id'         => ! empty( $StorePulse_funnel_id ) ? $StorePulse_funnel_id : uniqid(),
		'name'       => $StorePulse_funnel_name,
		'steps'      => $StorePulse_steps,
		'created_at' => current_time( 'mysql' ),
		'updated_at' => current_time( 'mysql' ),
	);

	if ( $StorePulse_funnel_id && isset( $StorePulse_funnels[ $StorePulse_funnel_id ] ) ) {
		$StorePulse_funnels[ $StorePulse_funnel_id ] = $StorePulse_funnel_data;
		$StorePulse_message                        = 'Funnel updated successfully!';
	} else {
		$StorePulse_funnels[ $StorePulse_funnel_data['id'] ] = $StorePulse_funnel_data;
		$StorePulse_message                                = 'Funnel created successfully!';
	}

	update_option( 'StorePulse_funnel_definitions', $StorePulse_funnels );
	echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html( $StorePulse_message ) . '</p></div>';
}

// Handle delete.
if ( isset( $_GET['delete_funnel'] ) ) {
	$StorePulse_delete_funnel_id = sanitize_text_field( wp_unslash( $_GET['delete_funnel'] ) );
	if ( check_admin_referer( 'delete_funnel_' . $StorePulse_delete_funnel_id ) ) {
		$StorePulse_funnels = get_option( 'StorePulse_funnel_definitions', array() );
		if ( isset( $StorePulse_funnels[ $StorePulse_delete_funnel_id ] ) ) {
			unset( $StorePulse_funnels[ $StorePulse_delete_funnel_id ] );
			update_option( 'StorePulse_funnel_definitions', $StorePulse_funnels );
			echo '<div class="notice notice-info is-dismissible"><p>🗑️ Funnel deleted.</p></div>';
		}
	}
}

// Handle edit.
$StorePulse_editing_funnel = null;
if ( isset( $_GET['edit_funnel'] ) ) {
	$StorePulse_edit_funnel_id = sanitize_text_field( wp_unslash( $_GET['edit_funnel'] ) );
	$StorePulse_funnels        = get_option( 'StorePulse_funnel_definitions', array() );
	$StorePulse_editing_funnel = $StorePulse_funnels[ $StorePulse_edit_funnel_id ] ?? null;
}

$StorePulse_funnels          = get_option( 'StorePulse_funnel_definitions', array() );
$StorePulse_available_events = array(
	'product_view'     => 'Product View',
	'add_to_cart'      => 'Add to Cart',
	'begin_checkout'   => 'Begin Checkout',
	'order_completed'  => 'Purchase',
	'view_item'        => 'View Item',
	'view_item_list'   => 'View Item List',
	'remove_from_cart' => 'Remove from Cart',
);
?>

<div class="wrap" id="StorePulse-pro-funnel-builder" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<!-- Left: Funnel Builder Form -->
		<div class="lg:col-span-1">
			<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
				<h3 class="text-sm font-bold text-gray-800 mb-4">
					<?php echo $StorePulse_editing_funnel ? 'Edit Funnel' : 'Create New Funnel'; ?>
				</h3>

				<form method="POST" id="funnelBuilderForm">
					<?php wp_nonce_field( 'StorePulse_save_funnel' ); ?>
					<?php if ( $StorePulse_editing_funnel ) : ?>
						<input type="hidden" name="funnel_id" value="<?php echo esc_attr( $StorePulse_editing_funnel['id'] ); ?>">
					<?php endif; ?>

					<div class="mb-4">
						<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Funnel Name</label>
						<input type="text" name="funnel_name" id="funnel_name" required
								class="w-full p-2 border border-gray-200 rounded text-sm"
								value="<?php echo esc_attr( $StorePulse_editing_funnel['name'] ?? '' ); ?>"
								placeholder="e.g. Checkout Funnel">
					</div>

					<div class="mb-4">
						<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Funnel Steps</label>
						<div id="funnel-steps" class="space-y-2">
							<?php if ( $StorePulse_editing_funnel && ! empty( $StorePulse_editing_funnel['steps'] ) ) : ?>
								<?php foreach ( $StorePulse_editing_funnel['steps'] as $StorePulse_index => $StorePulse_step ) : ?>
									<div class="step-item flex gap-2">
										<select name="steps[]" class="flex-1 p-2 border border-gray-200 rounded text-sm" required>
											<?php foreach ( $StorePulse_available_events as $StorePulse_value => $StorePulse_label ) : ?>
												<option value="<?php echo esc_attr( $StorePulse_value ); ?>" <?php echo selected( $StorePulse_step, $StorePulse_value, false ); ?>>
													<?php echo esc_html( $StorePulse_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="remove-step px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200" onclick="removeStep(this)">×</button>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="step-item flex gap-2">
									<select name="steps[]" class="flex-1 p-2 border border-gray-200 rounded text-sm" required>
										<?php foreach ( $StorePulse_available_events as $StorePulse_value => $StorePulse_label ) : ?>
											<option value="<?php echo esc_attr( $StorePulse_value ); ?>"><?php echo esc_html( $StorePulse_label ); ?></option>
										<?php endforeach; ?>
										</select>
									</div>
							<?php endif; ?>
						</div>
						<button type="button" id="add-step" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700">
							+ Add Step
						</button>
					</div>

					<div class="flex gap-2">
						<button type="submit" name="StorePulse_save_funnel"
								class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded font-medium hover:bg-indigo-700">
							<?php echo $StorePulse_editing_funnel ? 'Update Funnel' : 'Create Funnel'; ?>
						</button>
						<?php if ( $StorePulse_editing_funnel ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=StorePulse-pro-funnel-builder' ) ); ?>"
								class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
								Cancel
							</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Right: Saved Funnels List -->
		<div class="lg:col-span-1">
			<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
				<h3 class="text-sm font-bold text-gray-800 mb-4">Saved Funnels</h3>
				
				<?php if ( empty( $StorePulse_funnels ) ) : ?>
					<p class="text-gray-400 text-sm text-center py-8">No funnels created yet. Create one using the form on the left.</p>
				<?php else : ?>
					<div class="space-y-3">
						<?php foreach ( $StorePulse_funnels as $StorePulse_funnel ) : ?>
							<div class="p-4 border border-gray-200 rounded hover:bg-gray-50">
								<div class="flex justify-between items-start mb-2">
									<h4 class="font-semibold text-gray-800"><?php echo esc_html( $StorePulse_funnel['name'] ); ?></h4>
									<div class="flex gap-2">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=StorePulse-pro-funnel-builder&edit_funnel=' . $StorePulse_funnel['id'] ) ); ?>"
											class="text-blue-600 hover:text-blue-700 text-sm">Edit</a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=StorePulse-pro-funnel-builder&delete_funnel=' . $StorePulse_funnel['id'] ), 'delete_funnel_' . $StorePulse_funnel['id'] ) ); ?>"
											class="text-red-600 hover:text-red-700 text-sm"
											onclick="return confirm('Delete this funnel?')">Delete</a>
										<?php
										$StorePulse_report_url = admin_url( 'admin.php?page=StorePulse-pro-funnel-report&funnel_id=' . $StorePulse_funnel['id'] );
										$StorePulse_report_url = wp_nonce_url( $StorePulse_report_url, 'view_funnel_report_' . $StorePulse_funnel['id'] );
										?>
										<a href="<?php echo esc_url( $StorePulse_report_url ); ?>"
											class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">View Report</a>
									</div>
								</div>
								<div class="text-xs text-gray-500">
									Steps: 
									<?php
									echo esc_html(
										implode(
											' → ',
											array_map(
												function ( $StorePulse_step ) use ( $StorePulse_available_events ) {
													return $StorePulse_available_events[ $StorePulse_step ] ?? $StorePulse_step;
												},
												$StorePulse_funnel['steps'] ?? array()
											)
										)
									);
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

