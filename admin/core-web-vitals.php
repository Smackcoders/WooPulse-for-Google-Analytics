<?php
/**
 * Core Web Vitals report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<div class="wrap" id="StorePulse-vitals-v2" style="margin-top: 20px;">
	<div class="mb-6 flex justify-between items-center">
		<div>
			<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Core Web Vitals</h2>
			<p class="text-sm text-slate-500" style="margin-top: 4px; margin-bottom: 0;">
				Monitor and optimize your site performance 
				<span id="cwv-last-checked" class="ml-2 text-xs text-slate-400 font-medium"></span>
			</p>
		</div>
		<div class="flex items-center gap-3">
			<span class="text-[12px] font-medium text-slate-500">Analyze Device</span>
			<div class="flex bg-slate-100 p-1 rounded-lg border border-slate-200 shadow-sm">
				<button id="device-mobile"
					class="device-toggle flex items-center gap-2 px-4 py-1.5 rounded-md font-bold text-[11px] transition-all bg-indigo-600 text-white shadow-sm" style="color: white;">
					<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M14 0H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V2c0-1.1-.9-2-2-2zM9 1h2v1H9V1zm1 17.5c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5z"></path></svg> Mobile
				</button>
				<button id="device-desktop"
					class="device-toggle flex items-center gap-2 px-4 py-1.5 rounded-md font-bold text-[11px] transition-all text-slate-500 hover:text-slate-800">
					<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M19 14H1v-2h18v2zM1 10V2h18v8H1zm9 6h4v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V16z"></path></svg> Desktop
				</button>
			</div>
			<button id="btn-refresh-cwv" class="flex items-center gap-2 px-3 py-1.5 rounded-md text-[12px] font-semibold transition-all bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
				<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
				<span class="btn-text">Refresh Now</span>
			</button>
		</div>
	</div>

	<?php
	$psi_api_key = get_option( 'storepulse_psi_api_key', '' );
	if ( empty( $psi_api_key ) ) :
		?>
		<div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
			<p style="margin: 0; font-size: 14px; color: #b45309;">
				<strong style="font-weight: 700; color: #92400e;">Optimize Your Web Vitals Tracking:</strong> You are currently using the anonymous tier of the Google PageSpeed Insights API, which may lead to quota exceeded errors. <a href="?page=wp-seo-insights&step=analytics" style="color: #92400e; text-decoration: underline;">Add an API Key in Analytics Configuration</a> to ensure reliable tracking.
			</p>
		</div>
	<?php endif; ?>

	<!-- Main Error State -->
	<div id="cwv-main-error" class="hidden mb-6 bg-red-50 border border-red-200 rounded-xl p-6">
		<div class="flex items-start gap-4">
			<div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-1">
				<span class="text-xl">⚠️</span>
			</div>
			<div>
				<h3 class="text-lg font-bold text-red-800 m-0 mb-1">Performance Data Unavailable</h3>
				<p id="cwv-main-error-text" class="text-sm text-red-700 m-0 leading-relaxed"></p>
			</div>
		</div>
	</div>

	<div id="cwv-content">
	<!-- Report Insights (Matching Traffic Overview) -->
	<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 px-0 overflow-hidden">
		<div class="border-b border-slate-100 px-6 py-4 flex items-center gap-3 bg-slate-50">
			<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
			<h2 class="text-base font-bold text-slate-800 font-sans tracking-tight m-0">Report Insights</h2>
		</div>
		<div class="p-6">
			<!-- Action Scheduler Notice (Premium UI) -->
			<div style="background-color: #FFFDF0; border: 1.5px solid #FEF3C7; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
				<svg style="width: 24px; height: 24px; color: #D97706; margin-top: 2px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
				</svg>
				<div>
					<h4 style="font-size: 15px; font-weight: 700; color: #92400E; margin: 0 0 4px 0; line-height: 1.2;">Action Scheduler Notice</h4>
					<p style="font-size: 13px; color: #B45309; margin: 0; font-weight: 500;">Background tasks are running. Performance data will refresh automatically once completed.</p>
				</div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				<div class="pr-6 md:border-r border-slate-100">
					<h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2 m-0">
						<span class="w-2.5 h-2.5 rounded-full" style="background-color: #00c875;"></span>
						Overall Performance
					</h3>
					<div class="text-4xl font-bold tracking-tight mt-2 mb-3" style="color: #00c875;">Excellent</div>
					<p class="text-xs text-slate-500 leading-relaxed mb-5 m-0">
						Your site passes all Core Web Vitals assessments. All metrics are within the <span class="font-bold" style="color: #00c875;">good</span> threshold range.
					</p>
					<div class="flex items-center gap-3 text-xs font-semibold text-slate-500 mt-4 mb-2">
						<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color: #00c875;"></span> Good</div>
						<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color: #ffb020;"></span> Needs Work</div>
						<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color: #f44336;"></span> Poor</div>
					</div>
				</div>

				<div class="px-2 md:border-r border-slate-100">
					<h3 class="text-sm font-bold text-slate-700 mb-6 flex items-center gap-2 m-0">
						<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
						Mobile vs Desktop
					</h3>
					<div class="flex items-center justify-between text-xs text-slate-600 mb-4">
						<span>Mobile Score</span>
						<div class="flex items-center gap-3">
							<div class="w-20 h-2 rounded-full" style="background-color: #00c875;"></div>
							<span class="font-bold text-slate-800 w-6 text-right">100</span>
						</div>
					</div>
					<div class="flex items-center justify-between text-xs text-slate-600 mb-6">
						<span>Desktop Score</span>
						<div class="flex items-center gap-3">
							<div class="w-20 h-2 rounded-full" style="background-color: #00c875;"></div>
							<span class="font-bold text-slate-800 w-6 text-right">100</span>
						</div>
					</div>
					<p class="text-[11px] text-slate-400 leading-relaxed m-0">
						Both devices are performing optimally with no significant gaps.
					</p>
				</div>

				<div class="pl-2">
					<h3 class="text-sm font-bold text-slate-700 mb-5 flex items-center gap-2 m-0">
						<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
						Metric Glossary
					</h3>
					<ul class="text-[11px] text-slate-500 leading-relaxed space-y-3 m-0 p-0" style="list-style-type: none;">
						<li class="flex gap-2"><span class="font-bold text-slate-700 w-8">FCP</span> <span class="text-slate-400">—</span> Time to first content render</li>
						<li class="flex gap-2"><span class="font-bold text-slate-700 w-8">LCP</span> <span class="text-slate-400">—</span> Largest element render time</li>
						<li class="flex gap-2"><span class="font-bold text-slate-700 w-8">TBT</span> <span class="text-slate-400">—</span> Main thread blocking time</li>
						<li class="flex gap-2"><span class="font-bold text-slate-700 w-8">CLS</span> <span class="text-slate-400">—</span> Visual stability score</li>
						<li class="flex gap-2"><span class="font-bold text-slate-700 w-8">SI</span> <span class="text-slate-400">—</span> Content visibility speed</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
		<!-- Left Card -->
		<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 flex flex-col items-center justify-center min-h-[350px] lg:col-span-2">
			<div class="relative w-48 h-48 mb-6 mt-4">
				<canvas id="performance-gauge"></canvas>
				<div class="absolute inset-0 flex flex-col items-center justify-center pt-2">
					<div id="gauge-score" class="text-5xl font-bold text-slate-800 tracking-tighter leading-none m-0">100</div>
					<div class="text-[10px] font-medium text-slate-400 mt-1 text-center w-full">out of 100</div>
				</div>
			</div>
			<div class="text-center mt-2">
				<h2 class="text-lg font-bold text-slate-800 mb-2 tracking-tight m-0">Performance Score</h2>
				<p class="text-slate-400 text-xs px-4 leading-relaxed m-0 mb-5">Your page is performing at peak efficiency across all Core Web Vitals metrics.</p>
				<div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#f0fdf6] border border-[#d1fae5] rounded-full text-[10px] font-bold shadow-sm uppercase tracking-wide" style="color: #00c875;">
					<span class="status-icon rounded-full flex items-center justify-center text-white" style="width: 12px; height: 12px; font-size: 8px; background-color: #00c875;">✓</span> All checks passed
				</div>
			</div>
		</div>

		<!-- Right Card -->
		<div class="bg-white rounded-xl shadow-sm border border-slate-200 lg:col-span-3 flex flex-col p-0">
			<div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between bg-white rounded-t-xl">
				<div class="flex items-center gap-3">
					<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
					<h2 class="text-base font-bold text-slate-800 m-0 tracking-tight">Metric Breakdown</h2>
				</div>
				<div id="current-device-label" class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest">Mobile</div>
			</div>
			<div class="flex flex-col flex-1 pb-2">
				<div class="flex items-center justify-between py-4 px-6 border-b border-slate-50">
					<div class="flex items-center gap-4">
						<div class="w-8 h-8 rounded border border-slate-100 flex items-center justify-center flex-shrink-0">
							<span id="fcp-icon" class="status-icon text-emerald-500"></span>
						</div>
						<div>
							<div class="text-sm font-semibold text-slate-700 m-0">First Contentful Paint</div>
							<div class="text-[10px] text-slate-400 font-medium tracking-wide">FCP</div>
						</div>
					</div>
					<div id="fcp-value" class="text-lg font-bold text-emerald-500 tracking-tight text-right min-w-[64px]"></div>
				</div>
				<div class="flex items-center justify-between py-4 px-6 border-b border-slate-50">
					<div class="flex items-center gap-4">
						<div class="w-8 h-8 rounded border border-slate-100 flex items-center justify-center flex-shrink-0">
							<span id="lcp-icon" class="status-icon text-emerald-500"></span>
						</div>
						<div>
							<div class="text-sm font-semibold text-slate-700 m-0">Largest Contentful Paint</div>
							<div class="text-[10px] text-slate-400 font-medium tracking-wide">LCP</div>
						</div>
					</div>
					<div id="lcp-value" class="text-lg font-bold text-emerald-500 tracking-tight text-right min-w-[64px]"></div>
				</div>
				<div class="flex items-center justify-between py-4 px-6 border-b border-slate-50">
					<div class="flex items-center gap-4">
						<div class="w-8 h-8 rounded border border-slate-100 flex items-center justify-center flex-shrink-0">
							<span id="tbt-icon" class="status-icon text-emerald-500"></span>
						</div>
						<div>
							<div class="text-sm font-semibold text-slate-700 m-0">Total Blocking Time</div>
							<div class="text-[10px] text-slate-400 font-medium tracking-wide">TBT</div>
						</div>
					</div>
					<div id="tbt-value" class="text-lg font-bold text-emerald-500 tracking-tight text-right min-w-[64px]"></div>
				</div>
				<div class="flex items-center justify-between py-4 px-6 border-b border-slate-50">
					<div class="flex items-center gap-4">
						<div class="w-8 h-8 rounded border border-slate-100 flex items-center justify-center flex-shrink-0">
							<span id="cls-icon" class="status-icon text-emerald-500"></span>
						</div>
						<div>
							<div class="text-sm font-semibold text-slate-700 m-0">Cumulative Layout Shift</div>
							<div class="text-[10px] text-slate-400 font-medium tracking-wide">CLS</div>
						</div>
					</div>
					<div id="cls-value" class="text-lg font-bold text-emerald-500 tracking-tight text-right min-w-[64px]"></div>
				</div>
				<div class="flex items-center justify-between py-4 px-6 border-b border-slate-50">
					<div class="flex items-center gap-4">
						<div class="w-8 h-8 rounded border border-slate-100 flex items-center justify-center flex-shrink-0">
							<span id="si-icon" class="status-icon text-emerald-500"></span>
						</div>
						<div>
							<div class="text-sm font-semibold text-slate-700 m-0">Speed Index</div>
							<div class="text-[10px] text-slate-400 font-medium tracking-wide">SI</div>
						</div>
					</div>
					<div id="si-value" class="text-lg font-bold text-emerald-500 tracking-tight text-right min-w-[64px]"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- How to Improve Section -->
	<div class="mb-12 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
		<div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
			<div class="flex items-center gap-3">
				<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"></path>
				</svg>
				<h2 class="text-base font-bold text-slate-800 m-0 tracking-tight">Optimization Opportunities</h2>
			</div>
			<span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-semibold rounded-full uppercase tracking-widest">Recommendations</span>
		</div>
		<div id="improvements-list" class="flex flex-col m-0 p-0">
			<!-- Improvements dynamically loaded -->
		</div>
	</div>


	</div>

	<!-- Error Popup -->
	<div id="cwv-error"
		class="hidden fixed bottom-8 right-8 bg-red-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 animate-bounce">
		<span class="text-lg">⚠️</span>
		<div>
			<p class="font-bold text-sm">Error Loading Metrics</p>
			<p id="cwv-error-message" class="text-xs opacity-90"></p>
		</div>
		<button onclick="document.getElementById('cwv-error').classList.add('hidden')"
			class="ml-4 opacity-50 hover:opacity-100">&times;</button>
	</div>
</div>

