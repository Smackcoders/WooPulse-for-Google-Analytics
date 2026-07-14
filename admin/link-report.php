<?php
/**
 * Link report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="StorePulse-links-page">

	<div class="wrap" id="StorePulse-links-v2" style="margin-top: 10px;">
		<div class="mb-5">
			<div class="flex items-center justify-between w-full">
				<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Link Report</h2>
				<div class="flex items-center gap-3">
					<div class="relative w-[280px]">
						<div
							class="StorePulse-date-box relative flex items-center bg-white border border-slate-200 rounded-xl px-4 h-[44px] cursor-pointer hover:border-indigo-300 transition-all shadow-sm">
							<div class="mr-3 text-slate-400 shrink-0">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
									fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
									stroke-linejoin="round">
									<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
									<line x1="16" y1="2" x2="16" y2="6"></line>
									<line x1="8" y1="2" x2="8" y2="6"></line>
									<line x1="3" y1="10" x2="21" y2="10"></line>
								</svg>
							</div>
							<input type="text" id="link-date-range" placeholder="Select Date Range" readonly
								class="bg-transparent border-none p-0 m-0 h-full w-full outline-none text-[13px] font-semibold text-slate-600 cursor-pointer" />
							<div class="ml-2 text-slate-400">
								<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
									fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
									stroke-linejoin="round">
									<polyline points="6 9 12 15 18 9"></polyline>
								</svg>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<!-- Row 1: Inbound & Outbound Links -->
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
			<!-- Inbound Links -->
			<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
				<div class="p-5 border-b border-gray-100 flex items-center gap-3">
					<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="m11 17-5-5 5-5"></path>
							<path d="M18 12H6"></path>
						</svg>
					</div>
					<h2 class="text-[14px] font-bold text-slate-800 m-0">Inbound Links</h2>
				</div>
				<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<thead class="bg-slate-50/50">
							<tr>
								<th
									class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
									Links</th>
								<th
									class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
									Clicks</th>
							</tr>
						</thead>
						<tbody id="inbound-links-body" class="divide-y divide-slate-50">
							<tr>
								<td colspan="2" class="px-6 py-10 text-center text-slate-300 italic text-[13px]">
									Fetching links...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Outbound Links -->
			<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
				<div class="p-5 border-b border-gray-100 flex items-center gap-3">
					<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="m13 7 5 5-5 5"></path>
							<path d="M6 12h12"></path>
						</svg>
					</div>
					<h2 class="text-[14px] font-bold text-slate-800 m-0">Outbound Links</h2>
				</div>
				<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<thead class="bg-slate-50/50">
							<tr>
								<th
									class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
									Links</th>
								<th
									class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
									Clicks</th>
							</tr>
						</thead>
						<tbody id="outbound-links-body" class="divide-y divide-slate-50">
							<tr>
								<td colspan="2" class="px-6 py-10 text-center text-slate-300 italic text-[13px]">
									Fetching links...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Demo Mode Notice -->
		<?php
		$storepulse_settings = get_option( 'storepulse_settings', array() );
		if ( isset( $storepulse_settings['demo_mode'] ) && '1' === $storepulse_settings['demo_mode'] ) :
			?>
			<div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
				<div class="flex items-center">
					<div class="flex-shrink-0">
						<svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
					</div>
					<div class="ml-3">
						<p class="text-sm text-blue-700">
							<strong>Demo Mode Active:</strong> You are currently viewing sample data for demographics. Real
							data
							will be shown once enough traffic is collected by Google Analytics.
						</p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Row 2: Interested Fields (Age) & Demographics (Gender) -->
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
			<!-- Age / Interested Fields -->
			<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
				<div class="p-5 border-b border-gray-100 flex items-center gap-3">
					<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
							<circle cx="9" cy="7" r="4"></circle>
							<path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
							<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
						</svg>
					</div>
					<h2 class="text-[14px] font-bold text-slate-800 m-0">Interested fields</h2>
				</div>
				<div class="p-6 relative">
					<div id="age-no-data"
						class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-90 z-10 p-6 text-center">
						<div class="StorePulse-empty-state-icon">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
								<path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
							</svg>
						</div>
						<p class="text-gray-400 font-medium text-sm">No Age data available.</p>
						<p class="text-xs text-gray-400 mt-2">Note: Demographics require "Google Signals" to be enabled in GA4.</p>
					</div>
					<div class="h-56">
						<canvas id="age-chart"></canvas>
					</div>
					<div class="mt-2 text-center">
						<span class="text-sm text-gray-400 font-medium">Age</span>
					</div>
				</div>
			</div>

			<!-- Gender / Demographics -->
			<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
				<div class="p-5 border-b border-gray-100 flex items-center gap-3">
					<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
							<path d="M22 12A10 10 0 0 0 12 2v10z"></path>
						</svg>
					</div>
					<h2 class="text-[14px] font-bold text-slate-800 m-0">Demographics</h2>
				</div>
				<div class="p-6 relative">
					<div id="gender-no-data"
						class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-90 z-10 p-6 text-center">
						<div class="StorePulse-empty-state-icon">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
								<path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
								<path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
							</svg>
						</div>
						<p class="text-gray-400 font-medium text-sm">No Gender data available.</p>
						<p class="text-xs text-gray-400 mt-2">Enable "Google Signals" in your GA4 property to track demographics.</p>
					</div>
					<div class="h-56 flex items-center justify-center">
						<canvas id="gender-chart" style="max-width: 220px; max-height: 220px;"></canvas>
					</div>
					<div class="mt-2 text-center">
						<span class="text-sm text-gray-400 font-medium">Gender</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Row 3: Affiliate Links (Full Width) -->
		<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
			<div class="p-5 border-b border-gray-100 flex items-center gap-3">
				<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
						<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
					</svg>
				</div>
				<h2 class="text-[14px] font-bold text-slate-800 m-0">Affiliate links</h2>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-gray-50">
						<tr>
							<th
								class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
								Links</th>
							<th
								class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
								Clicks</th>
						</tr>
					</thead>
					<tbody id="affiliate-links-body" class="divide-y divide-gray-50">
						<tr>
							<td colspan="2" class="px-4 py-8 text-center text-gray-400">Fetching links...</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Row 4: Downloadable Links (Full Width) -->
		<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
			<div class="p-5 border-b border-gray-100 flex items-center gap-3">
				<div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="7 10 12 15 17 10"></polyline>
						<line x1="12" y1="15" x2="12" y2="3"></line>
					</svg>
				</div>
				<h2 class="text-[14px] font-bold text-slate-800 m-0">Downloadable Links</h2>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-gray-50">
						<tr>
							<th
								class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
								Links</th>
							<th
								class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
								Clicks</th>
						</tr>
					</thead>
					<tbody id="downloadable-links-body" class="divide-y divide-gray-50">
						<tr>
							<td colspan="2" class="px-4 py-8 text-center text-gray-400">Fetching links...</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

</div>