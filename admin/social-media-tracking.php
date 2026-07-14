<?php
/**
 * Social media tracking for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<div class=" animate-fade-in px-4">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Social Media</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2" >Analyze your inbound traffic and conversions from social networks.</p>
</div>

<!-- Hidden flatpickr anchor -->
<input type="text" id="social-date-range" />

<div id="wsmt-root" class="wrap">

	<!-- ── Filter Toolbar ── -->
	<div class="wsmt-toolbar">
		<div class="wsmt-toolbar-left">

			<!-- Date Range preset -->
			<div class="wsmt-filter-group">
				<span class="wsmt-filter-label">Date Range</span>
				<select id="social-date-preset">
					<option value="7">Last 7 Days</option>
					<option value="14">Last 14 Days</option>
					<option value="30" selected>Last 30 Days</option>
					<option value="90">Last 90 Days</option>
					<option value="custom">Custom Range</option>
				</select>
			</div>

			<!-- Compare With -->
			<div class="wsmt-filter-group">
				<span class="wsmt-filter-label">Compare With</span>
				<select id="compare-period">
					<option value="none">None</option>
					<option value="previous_period">Previous Period (same length)</option>
					<option value="previous_month">Previous Month</option>
					<option value="previous_year">Previous Year</option>
				</select>
			</div>

		</div>
		<div class="wsmt-toolbar-right">
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
	</div>

	<!-- ── Error ── -->
	<div id="social-error" class="hidden">
		<span>⚠</span>
		<p id="social-error-message" style="margin:0;"></p>
	</div>

	<!-- ── Section Card ── -->
	<div class="wsmt-section">

		<!-- Section header -->
		<div class="wsmt-section-header">
			<div class="wsmt-section-title">
				<!-- shuffle/social icon -->
				<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none"
					stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="18" cy="5" r="3"></circle>
					<circle cx="6" cy="12" r="3"></circle>
					<circle cx="18" cy="19" r="3"></circle>
					<line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
					<line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
				</svg>
				Social Media Traffic
			</div>
			<div class="wsmt-section-actions">
				<button class="wsmt-action-btn active" id="wsmt-view-table">Table</button>
				<button class="wsmt-action-btn" id="wsmt-view-empty">Show Empty State</button>
			</div>
		</div>

		<!-- Table -->
		<div class="wsmt-table-scroll">
			<table id="social-table">
				<thead>
					<tr>
						<th>Network</th>
						<th>Users</th>
						<th>Sessions</th>
						<th>Engaged Sessions</th>
						<th>Bounce Rate</th>
						<th>Purchase</th>
						<th>Revenue</th>
						<th>Revenue %</th>
						<th>Conversion Rate</th>
					</tr>
				</thead>
				<tbody id="social-table-body">
					<tr>
						<td class="wsmt-tbl-empty" colspan="9">
							<div class="wsmt-empty-circle">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#4f46e5"
									stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
									<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
									<circle cx="9" cy="7" r="4" />
									<path d="M23 21v-2a4 4 0 00-3-3.87" />
									<path d="M16 3.13a4 4 0 010 7.75" />
								</svg>
							</div>
							<div class="wsmt-empty-title">Loading social media data...</div>
							<div class="wsmt-empty-sub">Please wait</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

	</div><!-- .wsmt-section -->

</div><!-- #wsmt-root -->
