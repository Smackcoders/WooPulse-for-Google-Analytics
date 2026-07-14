<?php
/**
 * User journey report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>

<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">User Journey</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2">Track customer paths from first visit to purchase</p>
</div>

<div class="StorePulse-integrated-controls" style="background: white; border-radius: 12px; border: 1px solid #eef2f6; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
	<div class="flex flex-col lg:flex-row gap-6 items-end w-full">
		<div class="flex flex-wrap gap-5 flex-1 w-full">
			<!-- Date Range -->
			<div class="flex flex-col gap-1.5 min-w-[190px]">
				<label class="text-[11px] font-semibold uppercase tracking-widest px-1" style="color: #aaa;">Date Range</label>
				<div class="StorePulse-date-wrapper relative">
					<input type="text" id="journey-date-range"
						class="w-full border border-slate-200 rounded-lg py-2 text-sm bg-white outline-none focus:border-indigo-500 transition-all font-medium text-slate-700"
						placeholder="mm/dd/yyyy" style="padding-right: 40px; height: 42px; border-radius: 8px;" />
					<div class="absolute pointer-events-none text-slate-400"
						style="right: 14px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; color: #94a3b8;">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
							<line x1="16" y1="2" x2="16" y2="6"></line>
							<line x1="8" y1="2" x2="8" y2="6"></line>
							<line x1="3" y1="10" x2="21" y2="10"></line>
						</svg>
					</div>
				</div>
			</div>

			<!-- Search -->
			<div class="flex flex-col gap-1.5 flex-1 min-w-[320px]">
				<label class="text-[11px] font-semibold uppercase tracking-widest px-1" style="color: #aaa;">Search</label>
				<div class="relative group">
					<input type="text" id="journey-search"
						class="w-full border border-slate-200 rounded-lg text-sm bg-white py-2 outline-none focus:border-indigo-500 transition-all font-medium text-slate-700"
						style="padding-left: 40px; height: 42px; border-radius: 8px;"
						placeholder="Search Session ID, User Type, Event Label..." />
					<div class="absolute pointer-events-none text-slate-400"
						style="left: 14px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; color: #94a3b8;">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="11" cy="11" r="8"></circle>
							<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
						</svg>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Export Button -->
		<button id="btn-export-csv"
			class="px-5 py-2 bg-[#6366f1] text-white rounded-lg hover:bg-[#4f46e5] active:scale-95 transition-all text-sm font-semibold flex items-center justify-center gap-2 whitespace-nowrap shadow-sm shadow-indigo-200"
			style="height: 42px; background: #6366f1; border: none; cursor: pointer;">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
				<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
				<polyline points="7 10 12 15 17 10"></polyline>
				<line x1="12" y1="15" x2="12" y2="3"></line>
			</svg> Export CSV
		</button>
	</div>
</div>

<div class="wrap" id="StorePulse-journey-v2" style="margin-top: 24px;">

	<!-- Session Journey Table -->
	<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
		<table class="w-full text-left border-collapse">
			<thead>
				<tr class="bg-slate-50/50 border-b border-slate-100">
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 10%;">Session ID</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 10%;">User Type</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 15%;">Session Start</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 15%;">Session End</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 10%;">Duration</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-left"
						style="width: 30%;">Events Summary</th>
					<th class="px-6 py-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest text-right"
						style="width: 10%;">Total Order</th>
				</tr>
			</thead>
			<tbody id="session-journey-body" class="divide-y divide-gray-50">
				<!-- Session rows will be injected here -->
			</tbody>
		</table>


		<!-- Empty State -->
		<div id="journey-empty" class="hidden py-12 text-center">
			<span class="text-4xl mb-4 block">📦</span>
			<p class="text-sm font-bold text-gray-400 uppercase tracking-widest">No Sessions Found</p>
		</div>
	</div>
</div>
