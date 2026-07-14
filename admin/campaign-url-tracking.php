<?php
/**
 * Campaign URL tracking for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter options from database.
global $wpdb;
$StorePulse_table_events = $wpdb->prefix . 'storepulse_events';

$StorePulse_fn_get_col = 'get_col';
$StorePulse_fn_prepare = 'prepare';

$storepulse_campaigns = wp_cache_get( 'storepulse_campaigns_list', 'StorePulse' );
if ( false === $storepulse_campaigns ) {
	$storepulse_campaigns = $wpdb->$StorePulse_fn_get_col( $wpdb->$StorePulse_fn_prepare( "SELECT DISTINCT utm_campaign FROM {$wpdb->prefix}storepulse_events WHERE utm_campaign IS NOT NULL AND utm_campaign != %s ORDER BY utm_campaign", '' ) );
	wp_cache_set( 'storepulse_campaigns_list', $storepulse_campaigns, 'StorePulse', HOUR_IN_SECONDS );
}

$StorePulse_mediums = wp_cache_get( 'StorePulse_mediums_list', 'StorePulse' );
if ( false === $StorePulse_mediums ) {
	$StorePulse_mediums = $wpdb->$StorePulse_fn_get_col( $wpdb->$StorePulse_fn_prepare( "SELECT DISTINCT utm_medium FROM {$wpdb->prefix}storepulse_events WHERE utm_medium IS NOT NULL AND utm_medium != %s ORDER BY utm_medium", '' ) );
	wp_cache_set( 'StorePulse_mediums_list', $StorePulse_mediums, 'StorePulse', HOUR_IN_SECONDS );
}

$StorePulse_sources = wp_cache_get( 'StorePulse_sources_list', 'StorePulse' );
if ( false === $StorePulse_sources ) {
	$StorePulse_sources = $wpdb->$StorePulse_fn_get_col( $wpdb->$StorePulse_fn_prepare( "SELECT DISTINCT utm_source FROM {$wpdb->prefix}storepulse_events WHERE utm_source IS NOT NULL AND utm_source != %s ORDER BY utm_source", '' ) );
	wp_cache_set( 'StorePulse_sources_list', $StorePulse_sources, 'StorePulse', HOUR_IN_SECONDS );
}
?>

<div class=" animate-fade-in px-4">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Campaigns</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2" >Track the performance and ROI of your marketing campaigns.</p>
</div>

<div id="wcut-root" class="wrap">

	<!-- ── Filter Card ── -->
	<div class="wcut-filter-card">

		<!-- Top: Filters label + Export CSV -->
		<div class="wcut-filter-top">
			<div class="wcut-filters-label">
				<!-- blue sliders icon -->
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
					stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<line x1="4" y1="21" x2="4" y2="14" />
					<line x1="4" y1="10" x2="4" y2="3" />
					<line x1="12" y1="21" x2="12" y2="12" />
					<line x1="12" y1="8" x2="12" y2="3" />
					<line x1="20" y1="21" x2="20" y2="16" />
					<line x1="20" y1="12" x2="20" y2="3" />
					<line x1="2" y1="14" x2="6" y2="14" />
					<line x1="10" y1="8" x2="14" y2="8" />
					<line x1="18" y1="16" x2="22" y2="16" />
				</svg>
				<span style="color: #1d2327; font-weight: 700;">Filters</span>
			</div>
			<button id="btn-export-csv">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="7 10 12 15 17 10"></polyline>
					<line x1="12" y1="15" x2="12" y2="3"></line>
				</svg>
				Export CSV
			</button>
		</div>

		<!-- Filter row: Campaign | Medium | Source | Date -->
		<div class="wcut-filter-row">

			<!-- Campaign -->
			<div class="wcut-filter-col">
				<div class="wcut-camp-wrap">
					<span class="wcut-camp-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="m3 11 18-5v12L3 14v-3z"></path>
							<path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>
						</svg>
					</span>
					<select id="filter-campaign">
						<option value="all">All Campaigns</option>
						<?php
						if ( is_array( $storepulse_campaigns ) ) :
							foreach ( $storepulse_campaigns as $StorePulse_camp ) :
								?>
								<option value="<?php echo esc_attr( $StorePulse_camp ); ?>">
									<?php echo esc_html( $StorePulse_camp ); ?>
								</option>
								<?php
							endforeach;
endif;
						?>
					</select>
				</div>
			</div>

			<!-- Medium -->
			<div class="wcut-filter-col">
				<div class="wcut-camp-wrap">
					<span class="wcut-camp-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polygon points="12 2 2 7 12 12 22 7 12 2" />
							<polyline points="2 17 12 22 22 17" />
							<polyline points="2 12 12 17 22 12" />
						</svg>
					</span>
					<select id="filter-medium">
						<option value="all">All Mediums</option>
						<?php
						if ( is_array( $StorePulse_mediums ) ) :
							foreach ( $StorePulse_mediums as $StorePulse_med ) :
								?>
								<option value="<?php echo esc_attr( $StorePulse_med ); ?>">
									<?php echo esc_html( $StorePulse_med ); ?>
								</option>
								<?php
							endforeach;
endif;
						?>
					</select>
				</div>
			</div>

			<!-- Source -->
			<div class="wcut-filter-col">
				<div class="wcut-camp-wrap">
					<span class="wcut-camp-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10" />
							<line x1="2" y1="12" x2="22" y2="12" />
							<path
								d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
						</svg>
					</span>
					<select id="filter-source">
						<option value="all">All Sources</option>
						<?php
						if ( is_array( $StorePulse_sources ) ) :
							foreach ( $StorePulse_sources as $StorePulse_src ) :
								?>
								<option value="<?php echo esc_attr( $StorePulse_src ); ?>">
									<?php echo esc_html( $StorePulse_src ); ?>
								</option>
								<?php
							endforeach;
endif;
						?>
					</select>
				</div>
			</div>

			<!-- Date Range -->
			<div class="wcut-filter-col">
				<div class="wcut-date-wrap">
					<input type="text" id="transactions-date-range" class="wcut-date-input"
						placeholder="Select date range" readonly />
					<span class="wcut-camp-icon" style="left: 10px;">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
					</span>
					<span class="wcut-date-arrow">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
							stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="6 9 12 15 18 9"></polyline>
						</svg>
					</span>
				</div>
			</div>

		</div>

		<!-- Search row -->
		<div class="wcut-search-row">
			<span class="wcut-search-icon" style="display:flex;align-items:center;color:#94a3b8;">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="11" cy="11" r="8"></circle>
					<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
				</svg>
			</span>
			<input type="text" id="search-input" placeholder="Search by Transaction ID..." />
		</div>

	</div><!-- .wcut-filter-card -->

	<!-- ── Error ── -->
	<div id="transactions-error" class="hidden">
		<span>⚠</span>
		<p id="transactions-error-message" style="margin:0;"></p>
	</div>

	<!-- ── Table Card ── -->
	<div class="wcut-table-card">
		<div class="wcut-table-scroll">
			<table id="transactions-table">
				<thead>
					<tr>
						<th data-sort="transaction_id">Transaction ID <span class="sort-indicator">↑</span></th>
						<th data-sort="purchase_date">Purchase Date <span class="sort-indicator">↕</span></th>
						<th data-sort="campaign">Campaign <span class="sort-indicator">↕</span></th>
						<th data-sort="medium">Medium <span class="sort-indicator">↕</span></th>
						<th data-sort="source">Source <span class="sort-indicator">↕</span></th>
						<th data-sort="order_total">Order Total <span class="sort-indicator">↕</span></th>
					</tr>
				</thead>
				<tbody id="transactions-table-body">
					<tr>
						<td class="wcut-tbl-empty" colspan="6">
							<div class="wcut-empty-sub">Loading transactions...</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<div id="pagination">
			<span id="pagination-info">Showing 1-0 of 0 transactions</span>
			<div class="wcut-pag-btns">
				<button class="wcut-pag-btn" id="prev-page" disabled>‹ Previous</button>
				<span class="wcut-pag-sep">|</span>
				<button class="wcut-pag-btn" id="next-page" disabled>Next ›</button>
			</div>
		</div>
	</div>

</div><!-- #wcut-root -->
