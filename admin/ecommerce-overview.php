<?php
/**
 * eCommerce overview report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter options from database safely via the GA_Reporter class.
$storepulse_campaigns = \SmackCoders\WGA\GA_Reporter::get_filter_campaigns();
$StorePulse_mediums   = \SmackCoders\WGA\GA_Reporter::get_filter_mediums();
$StorePulse_sources   = \SmackCoders\WGA\GA_Reporter::get_filter_sources();
?>

<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">eCommerce Overview</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2">Track purchases, revenue, and conversion metrics</p>
</div>

<div id="wpec-root" class="wrap">

	<!-- ── Filter Card ── -->
	<div class="wpec-filter-card">
		<div class="wpec-filter-row">

			<!-- Campaign -->
			<div class="wpec-fc wpec-fc--campaign">
				<label for="filter-campaign">Campaign</label>
				<select id="filter-campaign">
					<option value="all">All Campaigns</option>
					<?php foreach ( $storepulse_campaigns as $StorePulse_camp ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_camp ); ?>"><?php echo esc_html( $StorePulse_camp ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Medium -->
			<div class="wpec-fc wpec-fc--medium">
				<label for="filter-medium">Medium</label>
				<select id="filter-medium">
					<option value="all">All Mediums</option>
					<?php foreach ( $StorePulse_mediums as $StorePulse_med ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_med ); ?>"><?php echo esc_html( $StorePulse_med ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Source -->
			<div class="wpec-fc wpec-fc--source">
				<label for="filter-source">Source</label>
				<select id="filter-source">
					<option value="all">All Sources</option>
					<?php foreach ( $StorePulse_sources as $StorePulse_src ) : ?>
						<option value="<?php echo esc_attr( $StorePulse_src ); ?>"><?php echo esc_html( $StorePulse_src ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Date Range -->
			<div class="wpec-fc wpec-fc--daterange">
				<label>Date Range</label>
				<div class="wpec-fc-date-wrap">
					<input type="text" id="ecommerce-date-range" class="wpec-fc-date-input"
						placeholder="Select date range" readonly />
					<div class="wpec-fc-date-cal">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
					</div>
				</div>
			</div>

			<!-- Search -->
			<div class="wpec-fc wpec-fc--search">
				<label for="search-input">Search</label>
				<div class="wpec-fc-search-wrap">
					<input type="text" id="search-input" class="wpec-fc-search-input"
						placeholder="Search campaigns, mediums, sources..." />
					<div class="wpec-fc-search-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="11" cy="11" r="8"></circle>
							<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
						</svg>
					</div>
				</div>
			</div>

			<!-- Export CSV -->
			<div class="wpec-fc wpec-fc--export">
				<button id="export-btn">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="7 10 12 15 17 10"></polyline>
						<line x1="12" y1="15" x2="12" y2="3"></line>
					</svg>
					Export CSV
				</button>
			</div>

		</div>
	</div><!-- .wpec-filter-card -->

	<!-- ── Error ── -->
	<div id="ecommerce-error" class="hidden">
		<span>⚠</span>
		<p id="ecommerce-error-message" style="margin:0;"></p>
	</div>

	<!-- ── Empty State Card ── -->
	<div id="ecommerce-empty" class="hidden">
		<div class="wpec-empty-icon-circle">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#4f46e5" stroke-width="1.8"
				stroke-linecap="round" stroke-linejoin="round">
				<circle cx="11" cy="11" r="8" />
				<line x1="21" y1="21" x2="16.65" y2="16.65" />
				<line x1="8.5" y1="8.5" x2="13.5" y2="13.5" />
				<line x1="13.5" y1="8.5" x2="8.5" y2="13.5" />
			</svg>
		</div>
		<div class="wpec-empty-title">No data found for the selected filters</div>
		<div class="wpec-empty-sub">Try adjusting your date range or removing filters</div>
		<button class="wpec-reset-link" id="wpec-reset-btn">
			<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="1 4 1 10 7 10" />
				<path d="M3.51 15a9 9 0 1 0 .49-3.34" />
			</svg>
			Reset all filters
		</button>
	</div>

	<!-- ── Campaign Performance Table ── -->
	<div class="wpec-table-outer">
		<div class="wpec-card-header">
			<h3 class="wpec-perf-title">Campaign Performance</h3>
			<span class="wpec-perf-count" id="wpec-row-count">0 results</span>
		</div>
		<div class="wpec-table-scroll">
			<table id="ecommerce-table">
				<thead>
					<tr>
						<th data-sort="campaign">CAMPAIGN <span class="sort-indicator">↕</span></th>
						<th data-sort="users">USERS <span class="sort-indicator">↕</span></th>
						<th data-sort="sessions">SESSIONS <span class="sort-indicator">↕</span></th>
						<th data-sort="engaged_sessions">ENGAGED SESSIONS <span class="sort-indicator">↕</span></th>
						<th data-sort="bounce_rate">BOUNCE RATE <span class="sort-indicator">↕</span></th>
						<th data-sort="purchases">PURCHASES <span class="sort-indicator">↕</span></th>
						<th data-sort="revenue">REVENUE <span class="sort-indicator">↕</span></th>
						<th data-sort="revenue_percent">REVENUE % <span class="sort-indicator">↕</span></th>
						<th data-sort="conversion_rate">CONVERSION RATE <span class="sort-indicator">↕</span></th>
					</tr>
				</thead>
				<tbody id="ecommerce-table-body">
					<tr>
						<td class="wpec-tbl-empty" colspan="9">
							<div class="wpec-tbl-empty-icon">
								<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 14H10C10.5523 14 11 14.4477 11 15C11 15.5523 11.4477 16 12 16C12.5523 16 13 15.5523 13 15C13 14.4477 13.4477 14 14 14H20M4 14V17C4 18.6569 5.34315 20 7 20H17C18.6569 20 20 18.6569 20 17V14M4 14L4.82353 7.41176C4.94508 6.43936 5.77259 5.7 6.75336 5.7H17.2466C18.2274 5.7 19.0549 6.43936 19.1765 7.41176L20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<div class="wpec-tbl-empty-text">No records to display</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

</div><!-- #wpec-root -->
