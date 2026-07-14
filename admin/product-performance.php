<?php
/**
 * Product performance report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter options.
global $wpdb;
?>


<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
	<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Product Performance</h2>
	<p class="text-sm text-slate-500 mt-1 mb-2">Analyze individual product views, carts, and revenue</p>
</div>

<div class="StorePulse-integrated-controls px-4 md:px-6" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; width: 100%; box-sizing: border-box;">
	<div class="flex flex-wrap items-center gap-3">
		<!-- Date Range -->
		<div class="relative">
			<input type="text" id="product-date-range"
				class="rounded-[7px] text-sm bg-white text-slate-600 outline-none w-56 transition-colors shadow-none"
				style="padding-left: 36px; height: 38px; border: 1px solid #dde1e8; padding-right: 12px;"
				placeholder="Select date range" />
			<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400" style="color: #94a3b8;">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
			</div>
		</div>

		<!-- Compare -->
		<select
			class="rounded-[7px] text-sm bg-white text-slate-600 px-3 pr-8 outline-none cursor-pointer appearance-none transition-colors shadow-none"
			style="height: 38px; border: 1px solid #dde1e8; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto;">
			<option>Compare With...</option>
			<option>Previous Period</option>
			<option>Previous Year</option>
		</select>
	</div>

	<!-- Export Button -->
	<button id="product-export-btn" class="StorePulse-btn-export" style="display: inline-flex; flex-direction: row; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2); margin: 0;">
		<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
			<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
			<polyline points="7 10 12 15 17 10"></polyline>
			<line x1="12" y1="15" x2="12" y2="3"></line>
		</svg>
		<span>Export CSV</span>
	</button>
</div>

<div class="wrap" id="StorePulse-product-v2" style="margin-top: 24px;">
	<!-- Table Container -->
	<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
		<div class="overflow-x-auto">
			<table id="product-performance-table" class="w-full text-sm StorePulse-alternating">
				<thead class="bg-gray-50 border-b border-gray-200">
					<tr>
						<th class="px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="name">
							Product Name <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="views">
							Views <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="add_to_cart">
							Add To Cart <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="checkout">
							Checkout <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="qty">
							Purchases <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="revenue">
							Revenue <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
						<th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest cursor-pointer group"
							data-sort="conv_rate">
							Conv. Rate <span
								class="sort-indicator opacity-0 group-hover:opacity-100 transition-opacity ml-1">↕</span>
						</th>
					</tr>
				</thead>
				<tbody id="product-performance-body" class="bg-white">
					<tr>
						<td colspan="7" class="px-6 py-8 text-center text-slate-500">Loading data...</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>


	<!-- Error State -->
	<div id="product-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
		<p id="product-error-message"></p>
	</div>
</div>
