<?php
/**
 * Sales summary report for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$StorePulse_currency_symbol = \SmackCoders\WGA\GA_Connector::get_currency_symbol();
?>


<div class="wrap" id="StorePulse-sales-v2">
	<div class=" animate-fade-in px-4" style="margin-bottom: 24px;">
		<h2 class="text-2xl font-bold text-slate-900 leading-tight m-0">Sales</h2>
		<p class="text-sm text-slate-500 mt-1 mb-2">Monitor your store's total revenue, transactions, and top-selling items.</p>
	</div>

	<!-- Integration Controls Toolbar -->
	<div class="StorePulse-header-toolbar">
		<div class="flex items-center">
			<!-- Date Range Selector -->
			<div class="relative">
				<button id="sales-date-range-btn"
					class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-all">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
						<line x1="16" y1="2" x2="16" y2="6"></line>
						<line x1="8" y1="2" x2="8" y2="6"></line>
						<line x1="3" y1="10" x2="21" y2="10"></line>
					</svg>
					<span id="display-date-range">Loading dates...</span>
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
						<polyline points="6 9 12 15 18 9"></polyline>
					</svg>
				</button>
				<input type="text" id="sales-date-range" class="absolute inset-0 opacity-0 cursor-pointer"
					style="width: 100%; height: 100%;" />
			</div>
		</div>

		<!-- Export Button (Ensured Visible) -->
		<button id="sales-export-btn"
			class="flex items-center gap-2 text-white px-5 py-2 rounded-lg font-bold text-sm shadow-sm hover:opacity-90 transition-all">
			<!-- <svg ...> -->
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="7 10 12 15 17 10"></polyline>
					<line x1="12" y1="15" x2="12" y2="3"></line>
				</svg>
			<span>Export Report</span>
		</button>
	</div>

	<!-- KPI Cards -->
	<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
		<!-- Total Revenue -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 hover:shadow-md transition-shadow">
			<h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Total Revenue</h3>
			<p class="text-[28px] font-bold text-gray-900 leading-tight" id="sales-total-revenue" style="font-size: 1.125rem;">
				<?php echo esc_html( $StorePulse_currency_symbol ); ?>0.00
			</p>
			<div class="flex items-center gap-1 mt-2 text-[13px] font-medium text-green-500">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
					<polyline points="16 7 22 7 22 13"></polyline>
				</svg>
				<span id="revenue-trend">+0.0% vs last period</span>
			</div>
		</div>

		<!-- Total Orders -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 hover:shadow-md transition-shadow">
			<h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Total Orders</h3>
			<p class="text-[28px] font-bold text-gray-900 leading-tight" id="sales-total-orders" style="font-size: 1.125rem;">0</p>
			<div class="flex items-center gap-1 mt-2 text-[13px] font-medium text-green-500">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
					<polyline points="16 7 22 7 22 13"></polyline>
				</svg>
				<span id="orders-trend">+0.0% vs last period</span>
			</div>
		</div>

		<!-- Avg Order Value -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 hover:shadow-md transition-shadow">
			<h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Avg Order Value</h3>
			<p class="text-[28px] font-bold text-gray-900 leading-tight" id="sales-aov" style="font-size: 1.125rem;">
				<?php echo esc_html( $StorePulse_currency_symbol ); ?>0.00
			</p>
			<div class="flex items-center gap-1 mt-2 text-[13px] font-medium text-gray-400">
				<span id="aov-trend">Per transaction</span>
			</div>
		</div>

		<!-- Conversion Rate -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 hover:shadow-md transition-shadow">
			<h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Conversion Rate</h3>
			<p class="text-[28px] font-bold text-gray-900 leading-tight" id="sales-conversion-rate" style="font-size: 1.125rem;">0.00%</p>
			<div class="flex items-center gap-1 mt-2 text-[13px] font-medium text-green-500">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
					<polyline points="16 7 22 7 22 13"></polyline>
				</svg>
				<span id="conversion-trend">+0.0% vs last period</span>
			</div>
		</div>
	</div>

	<!-- Chart -->
	<div class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 mb-8">
		<div class="flex items-center justify-between mb-6">
			<h3 class="text-base font-semibold text-gray-800" >Revenue Trends</h3>
			<div class="flex items-center gap-2">
				<span class="w-3 h-0.5 bg-emerald-500 rounded-full"></span>
				<span class="text-xs text-gray-400 font-medium">Revenue</span>
			</div>
		</div>
		<div class="h-80 w-full">
			<canvas id="sales-summary-chart"></canvas>
		</div>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<!-- Top Products -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 flex flex-col min-h-[400px]">
			<h3 class="text-base font-semibold text-gray-800 mb-6" >Top Products</h3>
			<div id="sales-top-products" class="flex-1 flex flex-col justify-center items-center">
				<div class="text-center">
					<div
						class="mb-4 inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-50 border border-gray-100">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300">
							<polyline points="21 8 21 21 3 21 3 8"></polyline>
							<rect x="1" y="3" width="22" height="5"></rect>
							<line x1="10" y1="12" x2="14" y2="12"></line>
						</svg>
					</div>
					<p class="text-gray-400 text-sm">No top products found</p>
				</div>
			</div>
			<div class="mt-6">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=StorePulse-product-performance' ) ); ?>"
					class="text-[#7c3aed] hover:underline text-sm font-medium inline-flex items-center gap-1">
					View Full Product Report <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
				</a>
			</div>
		</div>

		<!-- Recent Transactions -->
		<div
			class="bg-white p-6 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-slate-200 flex flex-col min-h-[400px]">
			<h3 class="text-base font-semibold text-gray-800 mb-6">Recent Transactions</h3>
			<div id="sales-recent-transactions" class="flex-1 flex flex-col justify-center items-center">
				<div class="text-center">
					<div
						class="mb-4 inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-50 border border-gray-100">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300">
							<circle cx="9" cy="21" r="1"></circle>
							<circle cx="20" cy="21" r="1"></circle>
							<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
						</svg>
					</div>
					<p class="text-gray-400 text-sm">No recent transactions found</p>
				</div>
			</div>
			<div class="mt-6">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=StorePulse-ecommerce-overview' ) ); ?>"
					class="text-[#7c3aed] hover:underline text-sm font-medium inline-flex items-center gap-1">
					View Detailed Data <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
				</a>
			</div>
		</div>
	</div>
</div>
