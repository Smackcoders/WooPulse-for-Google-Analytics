<?php
/**
 * Traffic overview report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Traffic</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2">Analyze your website's traffic and visitor volume.</p>
</div>

<div class="StorePulse-integrated-controls px-4">
	<div class="flex flex-wrap items-center justify-between w-full">
		<!-- Tabs on the left -->
		<div class="flex items-center gap-1">
			<button id="metric-sessions" class="metric-toggle metric-tab active">
				Sessions
			</button>
			<button id="metric-pageviews" class="metric-toggle metric-tab">
				Pageviews
			</button>
		</div>

		<!-- Controls on the right -->
		<div class="flex items-center gap-3">
			<!-- Date Range -->
			<div class="relative">
				<button id="traffic-date-btn" class="flex items-center gap-2 bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
						<line x1="16" y1="2" x2="16" y2="6"></line>
						<line x1="8" y1="2" x2="8" y2="6"></line>
						<line x1="3" y1="10" x2="21" y2="10"></line>
					</svg>
					<span id="display-date-range">Last 30 Days</span>
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
						<polyline points="6 9 12 15 18 9"></polyline>
					</svg>
				</button>
				<input type="text" id="traffic-date-range" class="absolute inset-0 opacity-0 cursor-pointer" />
			</div>

			<!-- Compare -->
			<button id="compare-btn-ui" class="flex items-center gap-2 bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
					<polyline points="16 3 21 3 21 8"></polyline>
					<line x1="4" y1="20" x2="21" y2="3"></line>
					<polyline points="21 16 21 21 16 21"></polyline>
					<line x1="15" y1="15" x2="21" y2="21"></line>
					<line x1="4" y1="4" x2="9" y2="9"></line>
				</svg>
				Compare
				<input type="checkbox" id="compare-toggle" class="sr-only">
			</button>

			<!-- Notes -->
			<button id="notes-viewer-btn" class="flex items-center gap-2 bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
					<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
					<polyline points="14 2 14 8 20 8"></polyline>
					<line x1="16" y1="13" x2="8" y2="13"></line>
					<line x1="16" y1="17" x2="8" y2="17"></line>
					<polyline points="10 9 9 9 8 9"></polyline>
				</svg>
				Notes
			</button>
		</div>
	</div>
</div>

<div class="wrap" id="StorePulse-traffic-v2" style="margin-top: 24px; padding: 0 16px;">

	<!-- Report Insights Card -->
	<div class="bg-white p-8 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-gray-100 mb-8">
		<div class="flex items-center gap-2 mb-6">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500">
				<path d="M9 18h6"></path>
				<path d="M10 22h4"></path>
				<path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A6 6 0 1 0 7.5 11.5c.76.76 1.23 1.52 1.41 2.5"></path>
			</svg>
			<h2 class="text-base font-bold text-gray-800">Report Insights</h2>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-12">
			<div>
				<h3 class="text-sm font-bold text-gray-800 mb-2">Sessions & Pageviews</h3>
				<p class="text-sm text-gray-400 leading-relaxed">
					Sessions represent individual visits to your website. A single session may include multiple pageviews, events, and interactions. Tracking sessions helps you understand overall visitor engagement.
				</p>
			</div>
			<div>
				<h3 class="text-sm font-bold text-gray-800 mb-2">Trends & Patterns</h3>
				<p class="text-sm text-gray-400 leading-relaxed">
					Trends show how your traffic changes over time. Look for patterns in peak hours, weekday vs weekend traffic, and seasonal changes to optimize your content strategy.
				</p>
			</div>
		</div>
	</div>

	<!-- Chart Container -->
	<div class="bg-white p-8 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-gray-100 relative mb-8">
		<div class="flex items-center justify-between mb-8">
			<h2 class="text-lg font-bold text-gray-800" style="font-size: 1rem;" id="chart-title">Sessions Over Time</h2>
			<div id="chart-legend-top" class="flex items-center gap-2">
				<span class="w-3 h-0.5 bg-indigo-500 rounded-full"></span>
				<span id="legend-label" class="text-xs text-gray-400 font-medium">Sessions</span>
			</div>
		</div>

		<div class="h-[400px] w-full">
			<canvas id="traffic-overview-chart"></canvas>
		</div>
	</div>

	<!-- Error State -->
	<div id="traffic-error" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 text-red-700 mb-8">
		<div class="flex items-center gap-3">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="12" cy="12" r="10"></circle>
				<line x1="15" y1="9" x2="9" y2="15"></line>
				<line x1="9" y1="9" x2="15" y2="15"></line>
			</svg>
			<p id="traffic-error-message" class="font-medium"></p>
		</div>
	</div>
</div>

<!-- Notes Viewer Modal -->
<div id="notes-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
	<div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
		<!-- Modal Header -->
		<div class="flex items-center justify-between p-6 border-b border-gray-200">
			<h2 class="text-xl font-semibold text-gray-800">Notes Viewer</h2>
			<button id="notes-modal-close" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
		</div>

		<!-- Modal Content -->
		<div class="p-6 overflow-y-auto flex-1">
			<div id="notes-list" class="space-y-4">
				<!-- Notes will be loaded here -->
			</div>
			<div id="notes-empty" class="text-center py-8 text-gray-500">
				<p>No notes found for this date range.</p>
			</div>
		</div>

		<!-- Modal Footer -->
		<div class="p-6 border-t border-gray-200 flex justify-end gap-2">
			<button id="create-note-btn"
				class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
				+ Create Note
			</button>
		</div>
	</div>
</div>

<!-- Create/Edit Note Modal -->
<div id="note-form-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
	<!-- ... existing form ... -->
	<div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
		<div class="p-6">
			<h3 class="text-lg font-semibold mb-4" id="note-form-title">Create Note</h3>
			<form id="note-form">
				<input type="hidden" id="note-id" value="">
				<div class="mb-4">
					<label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
					<input type="text" id="note-title" required
						class="w-full border border-gray-300 rounded-lg px-3 py-2"
						placeholder="e.g. Plugin update v1.2.0">
				</div>
				<div class="mb-4">
					<label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
					<textarea id="note-description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"
						placeholder="Add details about this event..."></textarea>
				</div>
				<div class="mb-4">
					<label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
					<input type="date" id="note-date" required
						class="w-full border border-gray-300 rounded-lg px-3 py-2">
				</div>
				<div class="mb-4 hidden">
					<div class="flex-1">
						<label class="block text-sm font-medium text-gray-700 mb-1">Start Date (optional)</label>
						<input type="date" id="note-date-start"
							class="w-full border border-gray-300 rounded-lg px-3 py-2">
					</div>
					<div class="flex-1">
						<label class="block text-sm font-medium text-gray-700 mb-1">End Date (optional)</label>
						<input type="date" id="note-date-end"
							class="w-full border border-gray-300 rounded-lg px-3 py-2">
					</div>
				</div>
				<div class="flex justify-end gap-2">
					<button type="button" id="note-form-cancel"
						class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
					<button type="submit"
						class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Note Detail Viewer Modal -->
<div id="note-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center">
	<div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
		<div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
			<h3 class="text-lg font-bold text-gray-800">Note Details</h3>
			<button id="note-detail-close" class="text-gray-400 hover:text-gray-600">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
					</path>
				</svg>
			</button>
		</div>
		<div class="p-6" id="note-detail-content">
			<div class="mb-4">
				<p id="note-detail-date" class="text-sm font-medium text-blue-600 mb-1"></p>
				<h4 id="note-detail-title" class="text-xl font-bold text-gray-900 leading-tight"></h4>
			</div>
			<div class="prose prose-sm max-w-none text-gray-600 mb-6">
				<p id="note-detail-description" class="whitespace-pre-wrap"></p>
			</div>
			<div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
				<button id="note-detail-delete"
					class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
					Delete
				</button>
				<button id="note-detail-edit"
					class="px-6 py-2 text-sm font-medium bg-blue-500 text-white hover:bg-blue-600 rounded-lg shadow-sm transition-all">
					Edit Note
				</button>
			</div>
		</div>
	</div>
</div>
