<?php
/**
 * Main dashboard view for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<div class="notice notice-error">
        <p>WooCommerce is required for Pulse Analytics.</p>
      </div>';
	return;
}

// Get store metrics from WooCommerce.

$StorePulse_args   = array(
	'status' => array( 'wc-completed', 'wc-processing' ),
	'limit'  => -1,
);
$StorePulse_orders = wc_get_orders( $StorePulse_args );

$StorePulse_total_revenue = 0;
$StorePulse_total_orders  = count( $StorePulse_orders );

foreach ( $StorePulse_orders as $StorePulse_order ) {
	$StorePulse_total_revenue += $StorePulse_order->get_total();
}

$StorePulse_avg_order_value = $StorePulse_total_orders > 0 ? $StorePulse_total_revenue / $StorePulse_total_orders : 0;
$StorePulse_currency_symbol = \SmackCoders\WGA\GA_Connector::get_currency_symbol();
?>

<div class="wrap" id="StorePulse-dashboard-v2" style="font-family: var(--w2ssyn-sb-font) !important;">

	<!-- 🔹 TOP FILTER BAR Integrated -->
	<div class="StorePulse-dashboard-toolbar flex items-center justify-between mb-6 mt-4">
	<div class="flex items-center">
		<div class="StorePulse-toolbar-select-wrap relative flex items-center" style="width: fit-content;">
		<!-- Grid Icon -->
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="absolute left-3 text-gray-400 pointer-events-none z-10">
			<rect x="3" y="3" width="7" height="7"></rect>
			<rect x="14" y="3" width="7" height="7"></rect>
			<rect x="14" y="14" width="7" height="7"></rect>
			<rect x="3" y="14" width="7" height="7"></rect>
		</svg>

		<select id="viewSelector"
			class="StorePulse-toolbar-input font-medium text-gray-700 bg-white focus:outline-none cursor-pointer transition-all text-sm"
			style="padding: 0 36px 0 36px !important; appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background: white !important; min-width: 210px;">
			<option value="overviewSection">Dashboard Overview</option>
			<option value="topCountriesSection">Top Countries</option>
			<option value="topPagesSection">Top Pages</option>
			<option value="sourceMediumSection">Source / Medium</option>
		</select>

		<!-- Single Chevron Icon -->
		<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8"
			stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
			class="absolute right-3 pointer-events-none z-10">
			<polyline points="6 9 12 15 18 9"></polyline>
		</svg>
		</div>
	</div>

	<div class="flex items-center gap-4">
		<button id="manualSyncBtn"
		class="StorePulse-toolbar-btn bg-white text-gray-700 px-3 py-2 rounded text-sm font-medium transition-colors flex items-center gap-2"
		style="height: 38px;">
		<svg class="mr-1" style="width: 14px; height: 14px; color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Sync Data
		</button>

		<button id="resetLayoutBtn"
		class="StorePulse-toolbar-btn bg-white text-gray-700 px-3 py-2 rounded text-sm font-medium transition-colors flex items-center gap-2"
		style="height: 38px;">
		<svg class="mr-1" style="width: 14px; height: 14px; color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg> Reset Layout
		</button>
	</div>
	</div>

	<!-- Subtle PRO Notice -->
	<!-- <div
	class="mb-6 flex items-center justify-between rounded-xl shadow-sm border transition-all duration-300"
	style="background-color: #f7f9ff; border-color: #e2e8f0; padding: 16px 24px;">
	<div class="flex items-center">
		<div class="mr-4 flex items-center justify-center rounded-full" style="background-color: #ebf0ff; width: 32px; height: 32px;">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
			<path d="m12 3 1.912 5.813a2 2 0 0 1-1.275 1.275L21 12l-5.813 1.912a2 2 0 0 1-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 1-1.275-1.275L3 12l5.813-1.912a2 2 0 0 1 1.275-1.275L12 3Z"/>
		</svg>
		</div>
		<p class="text-[14px] font-medium" style="color: #6366f1; margin: 0;">
		Unlock advanced insights like Customer Lifetime Value and Heatmaps
		</p>
	</div>
	<a href="#"
		class="text-[13px] text-white px-6 py-2.5 rounded-lg hover:opacity-90 transition-all font-semibold shadow-sm"
		style="background-color: #6366f1; text-decoration: none;">
		Upgrade Now
	</a>
	</div> -->

	<!-- KPI CARDS -->
	<div id="kpi-cards-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10 sortable-kpi">
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-xl hover:ring-1 hover:ring-indigo-100 transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="live_visitors" style="border-bottom: 3px solid #4ade80;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">
			Real-time
		</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tight leading-none m-0 p-0" id="live-visitors-count">0
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-500 font-medium">Active visitors now</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="sessions" data-drilldown="sessions" style="border-bottom: 3px solid #60a5fa;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">
			Sessions
		</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-sessions">0</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">+14.2% vs last period</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="page_views" style="border-bottom: 3px solid #c084fc;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">
			Page Views
		</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-pageviews">0</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">2.98 pages/session</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="revenue" data-drilldown="revenue" style="border-bottom: 3px solid #34d399;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">
			Revenue
		</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-revenue">
			<?php echo esc_html( $StorePulse_currency_symbol ); ?><?php echo esc_html( number_format( $StorePulse_total_revenue, 2 ) ); ?>
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">+8.7% vs last period</span>
		</div>
		</div>
	</div>

	<!-- Additional KPIs hidden on first row load or placed below -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="conversion_rate" data-drilldown="conversion_rate" style="border-bottom: 3px solid #fbbf24;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">Conv. Rate</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-conversion">0.00%
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">Industry avg: 2.5%</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="avg_order_value" style="border-bottom: 3px solid #f472b6;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">Avg Order</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-aov">
			<?php echo esc_html( $StorePulse_currency_symbol ); ?><?php echo esc_html( number_format( $StorePulse_avg_order_value, 2 ) ); ?>
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">+$4.20 vs last period</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="session_duration" style="border-bottom: 3px solid #2dd4bf;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">Avg. Session</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-duration">0m 0s
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">+12s vs last period</span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden"
		data-widget-id="engagement_rate" style="border-bottom: 3px solid #fb7185;">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0">Engage Rate</h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-engagement">0.0%
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium">+3.1% vs last period</span>
		</div>
		</div>
	</div>
	</div>

	<!-- ================= OVERVIEW SECTION ================= -->
	<!-- Converted to grid container where each direct child is a draggable widget -->
	<div id="overviewSection"
	class="max-w-[1440px] mx-auto grid grid-cols-1 xl:grid-cols-6 gap-6 sortable-dashboard pb-12">

	<!-- Top Selling Products -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card draggable-item h-full col-span-1 xl:col-span-3"
		data-widget-id="top_products">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<path
				d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
			</path>
			<polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
			<line x1="12" y1="22.08" x2="12" y2="12"></line>
			</svg>
			Top Selling Products
		</div>
		</h2>
		<div class="overflow-x-auto">
		<table class="w-full text-sm">
			<thead>
			<tr class="text-[9px] text-gray-400 font-medium uppercase tracking-widest border-b border-gray-50">
				<th class="pb-3 text-left font-semibold uppercase">Product Name</th>
				<th class="pb-3 text-center font-semibold uppercase">Qty Sold</th>
				<th class="pb-3 text-right font-semibold uppercase">Revenue</th>
			</tr>
			</thead>
			<tbody id="topProductsBody" class="divide-y divide-gray-50"> 
			<?php
			$StorePulse_product_sales = array();
			foreach ( $StorePulse_orders as $StorePulse_order ) {
				foreach ( $StorePulse_order->get_items() as $StorePulse_item ) {
					$StorePulse_name  = $StorePulse_item->get_name();
					$StorePulse_qty   = $StorePulse_item->get_quantity();
					$StorePulse_total = $StorePulse_item->get_total();
					if ( ! isset( $StorePulse_product_sales[ $StorePulse_name ] ) ) {
						$StorePulse_product_sales[ $StorePulse_name ] = array(
							'qty'     => 0,
							'revenue' => 0,
						);
					}
					$StorePulse_product_sales[ $StorePulse_name ]['qty']     += $StorePulse_qty;
					$StorePulse_product_sales[ $StorePulse_name ]['revenue'] += $StorePulse_total;
				}
			}
			uasort( $StorePulse_product_sales, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );
			foreach ( array_slice( $StorePulse_product_sales, 0, 5, true ) as $StorePulse_name => $StorePulse_data ) {
				?>
				<tr class="group transition-colors hover:bg-gray-50/50">
				<td class=" py-3.5 text-gray-600 font-medium">
					<?php echo esc_html( $StorePulse_name ); ?>
				</td>
				<td class="py-3.5 text-center text-gray-500"><?php echo esc_html( $StorePulse_data['qty'] ); ?>
				</td>
				<td class="py-3.5 text-right font-semibold text-gray-900 tracking-tight">
					<?php echo esc_html( $StorePulse_currency_symbol ) . esc_html( number_format( $StorePulse_data['revenue'], 0 ) ); ?>
				</td>
				</tr>
				<?php
			}
			if ( empty( $StorePulse_product_sales ) ) {
				// Fetch actual top 5 products from WooCommerce based on total_sales.
				$StorePulse_top_products = wc_get_products(
					array(
						'limit'    => 5,
						'status'   => 'publish',
						'orderby'  => 'meta_value_num',
						'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'order'        => 'DESC',
					)
				);

				if ( ! empty( $StorePulse_top_products ) ) {
					foreach ( $StorePulse_top_products as $StorePulse_product ) {
						$StorePulse_sales   = (int) $StorePulse_product->get_total_sales();
						$StorePulse_revenue = $StorePulse_sales * (float) $StorePulse_product->get_price();
						echo '<tr class="group transition-colors hover:bg-gray-50/50">
                          <td class="py-3.5 text-gray-600 font-medium">' . esc_html( $StorePulse_product->get_name() ) . '</td>
                          <td class="py-3.5 text-center text-gray-500">' . esc_html( $StorePulse_sales ) . '</td>
                          <td class="py-3.5 text-right font-semibold text-gray-900 tracking-tight">' . esc_html( $StorePulse_currency_symbol ) . esc_html( number_format( $StorePulse_revenue, 0 ) ) . '</td>
                        </tr>';
					}
				} else {
					echo '<tr><td colspan="3" class="py-4 text-center text-gray-500 font-medium">No products available.</td></tr>';
				}
			}
			?>
			</tbody>
		</table>
		</div>
	</div>

	<!-- Colorful Shopping Funnel -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card draggable-item h-full col-span-1 xl:col-span-3"
		data-widget-id="funnel">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"></path>
			</svg>
			Shopping Funnel
		</div>
		</h2>
		<div class="space-y-6" id="funnel-container">
		<!-- Product Views -->
		<div class="relative">
			<div class="flex justify-between items-center mb-1.5 px-0.5">
			<span class="text-[12px] font-medium text-gray-600">Product Views</span>
			<span class="text-[13px] font-bold text-gray-900" id="funnel-views">0</span>
			</div>
			<div class="h-1.5 w-full bg-blue-50 rounded-full overflow-hidden">
			<div id="funnel-bar-views" style="width:0%" class="h-full bg-blue-600 transition-all duration-1000">
			</div>
			</div>
		</div>
		<!-- Add to Cart -->
		<div class="relative">
			<div class="flex justify-between items-center mb-1.5 px-0.5">
			<span class="text-[12px] font-medium text-gray-600">Add to Cart</span>
			<span class="text-[13px] font-bold text-gray-900" id="funnel-cart">0</span>
			</div>
			<div class="h-1.5 w-full bg-blue-50 rounded-full overflow-hidden">
			<div id="funnel-bar-cart" style="width:0%"
				class="h-full bg-blue-600 transition-all duration-1000 opacity-80">
			</div>
			</div>
		</div>
		<!-- Begin Checkout -->
		<div class="relative">
			<div class="flex justify-between items-center mb-1.5 px-0.5">
			<span class="text-[12px] font-medium text-gray-600">Begin Checkout</span>
			<span class="text-[13px] font-bold text-gray-900" id="funnel-checkout">0</span>
			</div>
			<div class="h-1.5 w-full bg-blue-50 rounded-full overflow-hidden">
			<div id="funnel-bar-checkout" style="width:0%"
				class="h-full bg-blue-600 transition-all duration-1000 opacity-60">
			</div>
			</div>
		</div>
		<!-- Purchases -->
		<div class="relative">
			<div class="flex justify-between items-center mb-1.5 px-0.5">
			<span class="text-[12px] font-medium text-gray-600">Purchases</span>
			<span class="text-[13px] font-bold text-gray-900" id="funnel-purchase">0</span>
			</div>
			<div class="h-1.5 w-full bg-blue-50 rounded-full overflow-hidden">
			<div id="funnel-bar-purchase" style="width:0%"
				class="h-full bg-blue-600 transition-all duration-1000 opacity-40">
			</div>
			</div>
		</div>
		</div>
		<div class="mt-8 pt-4 border-t border-gray-50 flex items-center justify-between">
		<div class="flex items-center text-[11px] text-gray-400 font-medium">
			<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5">
			<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
			<polyline points="17 6 23 6 23 12"></polyline>
			</svg>
			5.3% overall conversion rate
		</div>
		</div>
	</div>

	<!-- Traffic Overview (Full Width) -->
	<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card draggable-item col-span-1 xl:col-span-6"
		data-widget-id="traffic_chart">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<path d="M3 3v18h18"></path>
			<path d="m19 9-5 5-4-4-3 3"></path>
			</svg>
			Store Traffic Trends
		</div>
		</h2>
		<div style="height:280px; width:100%; position:relative;">
		<canvas id="lineChart"></canvas>
		</div>
	</div>

	<!-- New vs Returning -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="visitor_types">
		<h3 class="text-[13px] font-bold mb-8 text-gray-800 text-center cursor-move">New vs Returning</h3>
		<div id="visitorsChartWrap" class="relative">
		<div class="flex justify-between items-end mb-6 relative">
			<div class="flex-1 text-center">
			<span class="block text-xl font-extrabold text-indigo-600 tracking-tighter mb-1" id="vt-new-pct">0%</span>
			<span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">New</span>
			</div>
			<div class="w-[1px] h-10 bg-gray-50 mx-4"></div>
			<div class="flex-1 text-center">
			<span class="block text-xl font-extrabold text-gray-800 tracking-tighter mb-1" id="vt-ret-pct">0%</span>
			<span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Returning</span>
			</div>
		</div>
		<div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden flex">
			<div id="vt-progress-new" class="h-full bg-indigo-500 transition-all duration-1000" style="width:0%"></div>
			<div id="vt-progress-ret" class="h-full bg-indigo-200 transition-all duration-1000" style="width:0%"></div>
		</div>
		</div>
	</div>

	<!-- Device Type -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="devices">
		<h3 class="text-[13px] font-bold mb-8 text-gray-800 text-center cursor-move">Device Type</h3>
		<div class="flex justify-between items-end h-[110px]">
		<div class="flex-1 text-center group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="mx-auto mb-3 opacity-60">
			<rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
			<line x1="12" y1="18" x2="12.01" y2="18"></line>
			</svg>
			<span class="block text-lg font-bold text-gray-800 mb-0.5" id="dv-mob-pct">0%</span>
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Mobile</span>
		</div>
		<div class="flex-1 text-center group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
			stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="mx-auto mb-3 opacity-60">
			<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
			<line x1="8" y1="21" x2="16" y2="21"></line>
			<line x1="12" y1="17" x2="12" y2="21"></line>
			</svg>
			<span class="block text-lg font-bold text-gray-800 mb-0.5" id="dv-desk-pct">0%</span>
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Desktop</span>
		</div>
		<div class="flex-1 text-center group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
			stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="mx-auto mb-3 opacity-60">
			<rect x="5" y="3" width="14" height="18" rx="2" ry="2"></rect>
			<line x1="12" y1="18" x2="12.01" y2="18"></line>
			</svg>
			<span class="block text-lg font-bold text-gray-800 mb-0.5" id="dv-tab-pct">0%</span>
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Tablet</span>
		</div>
		</div>
	</div>

	<!-- Browser Used -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="browsers">
		<h3 class="text-[13px] font-bold mb-6 text-gray-800 text-center cursor-move">Browser Used</h3>
		<div id="browserChartWrap" class="space-y-5">
		<!-- Re-designed by JS -->
		</div>
	</div>

	<!-- Overview Top Pages -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card draggable-item h-full col-span-1 xl:col-span-3"
		data-widget-id="top_pages">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
			<polyline points="14 2 14 8 20 8"></polyline>
			<line x1="16" y1="13" x2="8" y2="13"></line>
			<line x1="16" y1="17" x2="8" y2="17"></line>
			<polyline points="10 9 9 9 8 9"></polyline>
			</svg>
			Top Pages
		</div>
		</h2>
		<div class="overflow-hidden rounded-xl border border-gray-50">
		<table class="w-full text-sm">
			<thead
			class="bg-gray-50/50 text-gray-400 font-medium uppercase text-[10px] tracking-widest border-b border-gray-50">
			<tr>
				<th class="p-4 text-left font-bold uppercase">Page Path</th>
				<th class="p-4 text-right font-bold uppercase">Views</th>
			</tr>
			</thead>
			<tbody id="overviewTopPagesBody" class="divide-y divide-gray-50">
			<tr>
				<td colspan="2" class="text-center p-8 text-gray-400">Loading data...</td>
			</tr>
			</tbody>
		</table>
		</div>
	</div>

	<!-- Overview Top Countries -->
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card draggable-item h-full col-span-1 xl:col-span-3"
		data-widget-id="top_countries">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<circle cx="12" cy="12" r="10"></circle>
			<line x1="2" y1="12" x2="22" y2="12"></line>
			<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
			</svg>
			Visitor Geography
		</div>
		</h2>
		<div class="overflow-hidden rounded-xl border border-gray-50">
		<table class="w-full text-sm">
			<thead
			class="bg-gray-50/50 text-gray-400 font-medium uppercase text-[10px] tracking-widest border-b border-gray-50">
			<tr>
				<th class="p-4 text-left font-bold uppercase">Country</th>
				<th class="p-4 text-right font-bold uppercase">Visitors</th>
			</tr>
			</thead>
			<tbody id="overviewTopCountriesBody" class="divide-y divide-gray-50">
			<tr>
				<td colspan="2" class="text-center p-8 text-gray-400">Loading data...</td>
			</tr>
			</tbody>
		</table>
		</div>
	</div>

	</div> <!-- END OVERVIEW -->

	<!-- ================= SEPARATE SECTIONS ================= -->

	<div id="topPagesSection" class="hidden space-y-8 animate-fade-in">
	<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card">
		<h2 class="text-lg font-semibold mb-6 text-gray-800">📑 Detailed Top Pages</h2>
		<div class="overflow-x-auto rounded-xl border border-gray-50">
		<table class="w-full text-sm">
			<thead class="bg-blue-50 text-blue-800 font-bold uppercase text-[10px]">
			<tr>
				<th class="p-4 text-left">URL Path</th>
				<th class="p-4 text-center">Views</th>
				<th class="p-4 text-center">Sessions</th>
				<th class="p-4 text-center">New Users</th>
				<th class="p-4 text-center">Bounce %</th>
			</tr>
			</thead>
			<tbody id="fullTopPagesBody" class="divide-y divide-gray-50"></tbody>
		</table>
		</div>
	</div>
	</div>

	<div id="topCountriesSection" class="hidden space-y-8 animate-fade-in text-[13px]">
	<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card">
		<h2 class="text-[15px] font-semibold mb-6 text-gray-800">🌍 All Countries</h2>
		<div class="overflow-x-auto rounded-xl border border-gray-50">
		<table class="w-full">
			<thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
			<tr>
				<th class="p-4 text-left">Country</th>
				<th class="p-4 text-right">Total Visitors</th>
			</tr>
			</thead>
			<tbody id="fullTopCountriesBody" class="divide-y divide-gray-50"></tbody>
		</table>
		</div>
	</div>
	</div>

	<div id="sourceMediumSection" class="hidden space-y-8 animate-fade-in text-[13px]">
	<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 StorePulse-soft-card">
		<h2 class="text-[15px] font-semibold mb-6 text-gray-800">🔗 Traffic Sources</h2>
		<div class="overflow-x-auto rounded-xl border border-gray-50">
		<table id="sourceMediumTable" class="w-full">
			<thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
			<tr>
				<th class="p-4 text-left">Source / Medium</th>
				<th class="p-4 text-right">Total Sessions</th>
			</tr>
			</thead>
			<tbody class="divide-y divide-gray-50"></tbody>
		</table>
		</div>
	</div>
	</div>

</div>

