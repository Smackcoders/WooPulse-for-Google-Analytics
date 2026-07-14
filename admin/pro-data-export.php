<?php
/**
 * Data export PRO feature for StorePulse analytics plugin.
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
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access data export.' );
}

// Handle export request.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['StorePulse_export'] ) && check_admin_referer( 'StorePulse_export' ) ) {
	$StorePulse_format     = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : 'csv';
	$StorePulse_start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
	$StorePulse_end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : gmdate( 'Y-m-d' );
	$StorePulse_data_type  = isset( $_POST['data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['data_type'] ) ) : 'events';

	$StorePulse_export_data = StorePulse_generate_export_data( $StorePulse_data_type, $StorePulse_start_date, $StorePulse_end_date );

	// Check if export_data is an array (valid result, even if empty)
	// Empty arrays are valid - they just mean no data for the date range
	// The export functions handle empty arrays correctly (CSV shows "No data available", JSON/XML return empty structure).
	if ( is_array( $StorePulse_export_data ) ) {
		StorePulse_download_export( $StorePulse_export_data, $StorePulse_format, $StorePulse_data_type, $StorePulse_start_date, $StorePulse_end_date );
		exit;
	} else {
		// Only show error if export_data is not an array (null, false, etc.).
		echo '<div class="notice notice-error"><p>Error generating export data.</p></div>';
	}
}

$StorePulse_rest_base = rest_url( 'StorePulse/v1/' );
$StorePulse_nonce     = wp_create_nonce( 'wp_rest' );
?>

<div class="wrap" id="StorePulse-pro-data-export"
	style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<!-- Export Form -->
		<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
			<h3 class="text-sm font-bold text-gray-800 mb-4">Export Configuration</h3>

			<form method="POST" id="export-form">
				<?php wp_nonce_field( 'StorePulse_export' ); ?>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Data Type</label>
					<select name="data_type" id="data_type" class="w-full p-2 border border-gray-200 rounded text-sm"
						required>
						<option value="events">Events</option>
						<option value="orders">Orders</option>
						<option value="sessions">Sessions</option>
						<option value="campaigns">Campaigns</option>
						<option value="custom_report">Custom Report</option>
					</select>
				</div>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Date Range</label>
					<div class="flex gap-2">
						<input type="date" name="start_date" id="start_date" required
							class="flex-1 p-2 border border-gray-200 rounded text-sm"
							value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>">
						<span class="self-center text-gray-400">to</span>
						<input type="date" name="end_date" id="end_date" required
							class="flex-1 p-2 border border-gray-200 rounded text-sm" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
					</div>
				</div>

				<div class="mb-4">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Export Format</label>
					<select name="format" id="format" class="w-full p-2 border border-gray-200 rounded text-sm"
						required>
						<option value="csv">CSV</option>
						<option value="json">JSON</option>
						<option value="xml">XML</option>
					</select>
				</div>

				<div class="mb-4" id="custom-report-options" style="display: none;">
					<label class="block text-xs font-bold text-gray-400 uppercase mb-2">Report ID</label>
					<input type="text" name="report_id" id="report_id"
						class="w-full p-2 border border-gray-200 rounded text-sm" placeholder="Enter saved report ID">
				</div>

				<button type="submit" name="StorePulse_export"
					class="w-full bg-[#6366f1] text-white px-4 py-2 rounded font-medium hover:bg-[#4f46e5]">
					Export Data
				</button>
			</form>
		</div>

		<!-- Scheduled Exports -->
		<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
			<h3 class="text-sm font-bold text-gray-800 mb-4">Scheduled Exports</h3>

			<?php
			$StorePulse_scheduled = get_option( 'StorePulse_scheduled_exports', array() );
			if ( empty( $StorePulse_scheduled ) ) :
				?>
				<p class="text-gray-400 text-sm text-center py-8">No scheduled exports. Use the API to schedule exports.</p>
			<?php else : ?>
				<div class="space-y-3">
					<?php foreach ( $StorePulse_scheduled as $StorePulse_schedule ) : ?>
						<div class="p-3 border border-gray-200 rounded">
							<div class="flex justify-between items-start">
								<div>
									<div class="font-semibold text-sm"><?php echo esc_html( $StorePulse_schedule['name'] ?? 'Unnamed' ); ?></div>
									<div class="text-xs text-gray-500"><?php echo esc_html( $StorePulse_schedule['frequency'] ?? 'daily' ); ?></div>
								</div>
								<button class="text-red-600 hover:text-red-700 text-sm">Delete</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>


<?php

/**
 * Download export file in specified format.
 */
function StorePulse_download_export( $data, $format, $data_type, $start_date, $end_date ) {
	$filename = sprintf(
		'StorePulse_%s_%s_to_%s.%s',
		$data_type,
		$start_date,
		$end_date,
		$format
	);

	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	switch ( $format ) {
		case 'csv':
			StorePulse_export_csv( $data );
			break;
		case 'json':
			StorePulse_export_json( $data );
			break;
		case 'xml':
			StorePulse_export_xml( $data );
			break;
	}
}

function StorePulse_export_csv( $data ) {
	if ( empty( $data ) ) {
		echo "No data available\n";
		return;
	}

	$out_stream = fopen( 'php://output', 'w' );
	if ( ! $out_stream ) {
		return;
	}

	// Output headers.
	fputcsv( $out_stream, array_keys( $data[0] ) );

	// Output data rows.
	foreach ( $data as $row ) {
		$flat_row = array();
		foreach ( $row as $value ) {
			$flat_row[] = ( is_array( $value ) || is_object( $value ) ) ? wp_json_encode( $value ) : $value;
		}
		fputcsv( $out_stream, $flat_row );
	}
}

function StorePulse_export_json( $data ) {
	header( 'Content-Type: application/json' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT );
}

function StorePulse_export_xml( $data ) {
	header( 'Content-Type: application/xml' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<data>' . "\n";

	foreach ( $data as $row ) {
		echo '  <record>' . "\n";
		foreach ( $row as $key => $value ) {
			$safe_key   = preg_replace( '/[^a-z0-9_]/i', '_', $key );
			$safe_value = is_array( $value ) || is_object( $value ) ? wp_json_encode( $value ) : $value;
			echo '    <' . esc_html( $safe_key ) . '>' . esc_html( $safe_value ) . '</' . esc_html( $safe_key ) . ">\n";
		}
		echo '  </record>' . "\n";
	}

	echo '</data>';
}
?>
