<?php
/**
 * Activity logs display for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$StorePulse_sources = wp_cache_get( 'storepulse_logs_sources_list', 'StorePulse' );
if ( false === $StorePulse_sources ) {
	$StorePulse_fn_get_col = 'get_col';
	$StorePulse_fn_prepare = 'prepare';
	$StorePulse_sources    = $wpdb->$StorePulse_fn_get_col( $wpdb->$StorePulse_fn_prepare( "SELECT DISTINCT source FROM {$wpdb->prefix}storepulse_logs WHERE source != %s AND source IS NOT NULL", '' ) );
	wp_cache_set( 'storepulse_logs_sources_list', $StorePulse_sources, 'StorePulse', HOUR_IN_SECONDS );
}

$StorePulse_db_exit_pages = wp_cache_get( 'storepulse_logs_exit_pages_list', 'StorePulse' );
if ( false === $StorePulse_db_exit_pages ) {
	$StorePulse_fn_get_col    = 'get_col';
	$StorePulse_fn_prepare    = 'prepare';
	$StorePulse_db_exit_pages = $wpdb->$StorePulse_fn_get_col( $wpdb->prepare( "SELECT DISTINCT exit_page FROM {$wpdb->prefix}storepulse_logs WHERE exit_page != %s AND exit_page IS NOT NULL", '' ) );
	wp_cache_set( 'storepulse_logs_exit_pages_list', $StorePulse_db_exit_pages, 'StorePulse', HOUR_IN_SECONDS );
}
$StorePulse_predefined_pages = array( 'home', 'shop', 'product', 'cart', 'checkout', 'myaccount', 'processing' );
$StorePulse_exit_pages       = array_unique( array_merge( $StorePulse_predefined_pages, $StorePulse_db_exit_pages ) );
sort( $StorePulse_exit_pages );
?>


<div class=" animate-fade-in px-4">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Logs</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2" >Real-time monitoring of site activity and user journeys.</p>
</div>

<div class="px-4 mb-8">
	<div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-4">
		<form id="logs-filter-form" class="flex flex-wrap items-end w-full gap-4">
			<input type="hidden" name="date_from" id="date_from">
			<input type="hidden" name="date_to" id="date_to">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">

			<div class="relative flex-1 min-w-[160px]">
				<select name="source_filter" class="StorePulse-clean-select w-full">
					<option value="">All Sources</option>
					<?php foreach ( $StorePulse_sources as $StorePulse_src ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_src ); ?>"><?php echo esc_html( ucfirst( $StorePulse_src ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="relative flex-1 min-w-[180px]">
				<select name="exit_filter" class="StorePulse-clean-select w-full">
					<option value="">All Exit Pages</option>
					<?php foreach ( $StorePulse_exit_pages as $StorePulse_exit_page ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_exit_page ); ?>"><?php echo esc_html( ucfirst( $StorePulse_exit_page ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="relative flex-[1.5] min-w-[240px]">
				<div class="StorePulse-date-box relative flex items-center">
					<div class="mr-2 text-slate-400 shrink-0">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
					</div>
					<input type="text" id="StorePulse-logs-range-special" placeholder="Select Date Range" readonly />
					<div class="ml-2 text-slate-400">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="6 9 12 15 18 9"></polyline>
						</svg>
					</div>
				</div>
			</div>

			<div class="relative flex-[1.5] min-w-[200px]">
				<input type="text" name="s" placeholder="Search ID..." class="StorePulse-clean-input w-full">
				<div class="StorePulse-search-icon-wrap">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"></circle>
						<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					</svg>
				</div>
			</div>

			<button type="button" id="logs-apply-btn"
				class="flex-none h-[42px] bg-indigo-600 hover:bg-indigo-700 text-white px-6 rounded-[10px] transition-all shadow-sm active:scale-95 flex items-center justify-center gap-2 font-semibold text-[13px] shrink-0">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
				</svg>
				Filter
			</button>
		</form>
	</div>
</div>

<div class="px-4 pb-20" id="StorePulse-logs-v2">
	<div class="bg-white rounded-[24px] shadow-sm border border-slate-100 overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full text-left border-collapse min-w-[1050px]">
				<thead>
					<tr class="bg-slate-50/40">
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em]"
							style="width: 8%;">ID</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em] text-center"
							style="width: 15%;">Action</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em]"
							style="width: 15%;">User</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em]"
							style="width: 12%;">Source</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em]"
							style="width: 12%;">Page</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em]"
							style="width: 25%;">Message</th>
						<th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.1em] text-right"
							style="width: 13%;">Date</th>
					</tr>
				</thead>
				<tbody id="logs-table-body" class="divide-y divide-slate-50">
					<tr>
						<td colspan="7" class="text-center py-32">
							<div class="animate-pulse text-slate-400">Syncing Records...</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="bg-white px-6 py-6 border-t border-slate-100 flex items-center justify-between">
			<div class="text-[13px] text-slate-400 font-medium">
				Showing <span id="log-start">0</span>–<span id="log-end">0</span> of <span id="log-count">0</span>
				entries
			</div>
			<div class="flex items-center gap-2">
				<button
					class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-600 text-white text-[12px] font-bold shadow-sm">1</button>
			</div>
		</div>
	</div>
</div>
<!-- Log Details Modal -->
<div id="log-details-modal" class="fixed inset-0 z-[100000] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
	<div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
		<div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" id="close-modal-bg"></div>
		<span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
		
		<div class="inline-block align-bottom bg-white rounded-[32px] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-100">
			<!-- Modal Header -->
			<div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
				<div>
					<h3 class="text-xl font-bold text-slate-900" id="modal-title">Log Details</h3>
					<p class="text-sm text-slate-500 mt-1" id="modal-log-id">Viewing detailed information for Log #0</p>
				</div>
				<button type="button" class="close-log-modal p-2 rounded-xl hover:bg-slate-100 text-slate-400 transition-all">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
			</div>

			<!-- Modal Content -->
			<div class="px-8 py-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-8" id="log-details-content">
					<!-- Data will be injected here via JS -->
					<div class="col-span-2 text-center py-12">
						<div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
						<p class="text-slate-400 font-medium">Fetching log metadata...</p>
					</div>
				</div>
			</div>

			<!-- Modal Footer -->
			<div class="px-8 py-6 border-t border-slate-100 bg-slate-50/30 flex justify-end">
				<button type="button" class="close-log-modal px-6 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">Close Details</button>
			</div>
		</div>
	</div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.detail-section { @apply mb-6; }
.detail-title { @apply text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2; }
.detail-grid { @apply grid grid-cols-2 gap-x-4 gap-y-3 bg-slate-50/50 rounded-2xl p-5 border border-slate-100; }
.detail-label { @apply text-[11px] font-semibold text-slate-500; }
.detail-value { @apply text-[13px] font-bold text-slate-900 truncate; }
</style>
