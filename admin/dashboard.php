<?php
/**
 * Main dashboard view for Pulse Analytics.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_pulse_analytics_ga_property_id = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' )
	? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::get_property_id()
	: '';
$sm_pulse_analytics_ga_settings_url = admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' );

$sm_pulse_analytics_oauth_connected = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' )
	? \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::is_connected()
	: false;
$sm_pulse_analytics_ga_ready = $sm_pulse_analytics_oauth_connected && ! empty( $sm_pulse_analytics_ga_property_id );

?>

<div class="<?php echo esc_attr( sm_pulse_analytics_get_admin_ui_classes( 'wrap' ) ); ?>" id="PulseAnalytics-dashboard-v2">

	<?php if ( ! $sm_pulse_analytics_ga_ready ) : ?>
	<div id="pulse-analytics-connection-notice" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
		<div>
			<p class="m-0 text-sm font-semibold text-amber-900"><?php esc_html_e( 'Connect Google Analytics to see live metrics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			<p class="m-0 mt-1 text-xs text-amber-800/90">
				<?php
				esc_html_e(
					'Dashboard KPIs load from the GA4 Data API. Save a numeric Property ID and complete Google OAuth under Analytics settings. Zeros usually mean the connection is missing or the property has no data yet.',
					'smackcoders-pulse-analytics-for-woocommerce'
				);
				?>
			</p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' ) ); ?>" class="text-xs font-semibold text-amber-900 hover:text-amber-950"><?php esc_html_e( 'Open Analytics settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> &rarr;</a>
	</div>
	<?php endif; ?>

	<!-- 🔹 TOP FILTER BAR Integrated -->
	<div class="PulseAnalytics-dashboard-toolbar sp-toolbar flex items-center justify-between mb-6 mt-4">
	<div class="flex items-center gap-3 flex-wrap">
		<div class="PulseAnalytics-toolbar-select-wrap relative flex items-center sp-w-fit">
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
			class="PulseAnalytics-toolbar-input font-medium text-gray-700 bg-white focus:outline-none cursor-pointer transition-all text-sm sp-toolbar-select sp-toolbar-select--wide"
		>
			<option value="overviewSection"><?php esc_html_e( 'Dashboard Overview', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="topCountriesSection"><?php esc_html_e( 'Top Countries', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="topPagesSection"><?php esc_html_e( 'Top Pages', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="sourceMediumSection"><?php esc_html_e( 'Source / Medium', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
		</select>

		<!-- Single Chevron Icon -->
		<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8"
			stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
			class="absolute right-3 pointer-events-none z-10">
			<polyline points="6 9 12 15 18 9"></polyline>
		</svg>
		</div>
		<div class="PulseAnalytics-toolbar-select-wrap relative flex items-center sp-w-fit">
			<?php if ( ! empty( $sm_pulse_analytics_ga_property_id ) ) : ?>
			<select id="gaPropertySelector" class="PulseAnalytics-toolbar-input font-medium text-gray-700 bg-white focus:outline-none cursor-default text-sm sp-toolbar-select" disabled title="<?php esc_attr_e( 'Connected GA4 property', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
				<option><?php echo esc_html( sprintf( /* translators: %s: GA4 property ID */ __( 'GA4 Property — %s', 'smackcoders-pulse-analytics-for-woocommerce' ), $sm_pulse_analytics_ga_property_id ) ); ?></option>
			</select>
			<?php else : ?>
			<a id="gaPropertySelector" href="<?php echo esc_url( $sm_pulse_analytics_ga_settings_url ); ?>" class="PulseAnalytics-toolbar-input sp-toolbar-select sp-toolbar-select--link font-medium text-amber-800 bg-amber-50 border border-amber-200 focus:outline-none text-sm no-underline inline-flex items-center" title="<?php esc_attr_e( 'Add your numeric GA4 Property ID under Analytics settings to load dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
				<?php esc_html_e( 'GA4 Property — Not configured', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="flex items-center gap-4">
		<button id="resetLayoutBtn"
		class="PulseAnalytics-toolbar-btn bg-white text-gray-700 px-3 py-2 rounded text-sm font-medium transition-colors flex items-center gap-2 sp-h-38"
	>
		<svg class="mr-1 sp-icon-14 sp-icon-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg> Reset Layout
		</button>
	</div>
	</div>

	<!-- KPI CARDS -->
	<div id="kpi-cards-container" class="sp-kpi-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10 sortable-kpi">
	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card group hover:-translate-y-2 hover:shadow-xl hover:ring-1 hover:ring-indigo-100 transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden sp-widget-accent sp-widget-accent--green"
		data-widget-id="live_visitors">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0"><?php esc_html_e( 'Real-time', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tight leading-none m-0 p-0" id="live-visitors-count">0
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-500 font-medium"><?php esc_html_e( 'Active visitors now', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden sp-widget-accent sp-widget-accent--blue"
		data-widget-id="sessions" data-drilldown="sessions">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0"><?php esc_html_e( 'Sessions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-sessions">0</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium" id="kpi-sessions-delta"><?php esc_html_e( '— vs last period', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden sp-widget-accent sp-widget-accent--purple"
		data-widget-id="page_views">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0"><?php esc_html_e( 'Page Views', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-pageviews">0</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium" id="kpi-pageviews-ratio"><?php esc_html_e( '— pages/session', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden sp-widget-accent sp-widget-accent--teal"
		data-widget-id="session_duration">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0"><?php esc_html_e( 'Avg. Session', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-duration">0m 0s
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium" id="kpi-duration-delta"><?php esc_html_e( '— vs last period', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>

	<div
		class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card group hover:-translate-y-2 hover:shadow-2xl transition-all duration-500 cursor-move-kpi h-full relative overflow-hidden sp-widget-accent sp-widget-accent--rose"
		data-widget-id="engagement_rate">
		<div
		class="abs-drag-handle opacity-0 group-hover:opacity-100 transition-opacity absolute top-2 right-2 text-gray-300 z-10">
		⋮⋮</div>
		<div class="flex justify-between items-start relative z-10">
		<h3 class="text-[11px] font-medium text-gray-400 uppercase tracking-widest leading-none m-0 p-0"><?php esc_html_e( 'Engage Rate', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		</div>
		<div class="mt-3 relative z-10">
		<p class="text-xl font-extrabold text-gray-900 tracking-tighter leading-none m-0 p-0" id="kpi-engagement">0.0%
		</p>
		<div class="flex items-center mt-2.5 text-[12px]">
			<span class="text-gray-400 font-medium" id="kpi-engagement-delta"><?php esc_html_e( '— vs last period', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>
	</div>

	<!-- ================= OVERVIEW SECTION ================= -->
	<!-- Converted to grid container where each direct child is a draggable widget -->
	<div id="overviewSection"
	class="max-w-[1440px] mx-auto grid grid-cols-1 xl:grid-cols-6 gap-6 sortable-dashboard pb-12">

	<!-- Traffic Overview (Full Width) -->
	<div class="sp-card bg-white rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card draggable-item col-span-1 xl:col-span-6 overflow-hidden"
		data-widget-id="traffic_chart">
		
		<!-- 1. Top Segmented Tab Navigation Bar (Full Width) -->
		<div class="pulse-analytics-segmented-bar" id="trendSegmentedTabs">
			<button type="button" class="pulse-analytics-segmented-tab active" data-trend-tab="traffic">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
				<?php esc_html_e( 'Traffic', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</button>
			<button type="button" class="pulse-analytics-segmented-tab" data-trend-tab="engagement">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 1 0 7.75"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
				<?php esc_html_e( 'Engagement', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</button>
			<button type="button" class="pulse-analytics-segmented-tab" data-trend-tab="referrals">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
				<?php esc_html_e( 'Referrals', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</button>
		</div>

		<!-- Main Content Wrap -->
		<div class="p-6">
			<!-- Sub-header Row: Left Metric KPI Counters & Right Dropdown Button -->
			<div class="flex items-center justify-between mb-4 relative z-20">
				<!-- Left: Metric KPI summary display -->
				<div class="flex items-center gap-6" id="trendKpiSummary">
					<div class="flex flex-col">
						<span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider" id="summary-label-1"><?php esc_html_e( 'Sessions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
						<span class="text-xl font-extrabold text-gray-900 leading-none mt-1" id="summary-sessions">0</span>
					</div>
					<div class="h-8 w-[1px] bg-gray-200"></div>
					<div class="flex flex-col">
						<span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider" id="summary-label-2"><?php esc_html_e( 'Engaged Sessions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
						<span class="text-xl font-extrabold text-gray-700 leading-none mt-1" id="summary-engaged">0</span>
					</div>
					<div class="h-8 w-[1px] bg-gray-200"></div>
					<div class="flex flex-col">
						<span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider" id="summary-label-3"><?php esc_html_e( 'Key Event Rate', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
						<span class="text-xl font-extrabold text-gray-700 leading-none mt-1" id="summary-keyevent">0.0%</span>
					</div>
				</div>
			</div>

			<!-- Line Chart Container -->
			<div class="sp-chart-wrap-lg" id="trendChartWrap">
				<canvas id="lineChart"></canvas>
			</div>
		</div>
	</div>

	<!-- New vs Returning -->
	<div
		class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="visitor_types">
		<h3 class="text-[13px] font-bold mb-8 text-gray-800 text-center cursor-move"><?php esc_html_e( 'New vs Returning', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		<div id="visitorsChartWrap" class="relative">
		<div class="flex justify-between items-end mb-6 relative">
			<div class="flex-1 text-center">
			<span class="block text-xl font-extrabold text-indigo-600 tracking-tighter mb-1" id="vt-new-pct">0%</span>
			<span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider"><?php esc_html_e( 'New', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
			</div>
			<div class="w-[1px] h-10 bg-gray-50 mx-4"></div>
			<div class="flex-1 text-center">
			<span class="block text-xl font-extrabold text-gray-800 tracking-tighter mb-1" id="vt-ret-pct">0%</span>
			<span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider"><?php esc_html_e( 'Returning', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
			</div>
		</div>
		<div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden flex">
			<div id="vt-progress-new" class="h-full bg-indigo-500 transition-all duration-1000 sp-progress-zero"></div>
			<div id="vt-progress-ret" class="h-full bg-indigo-200 transition-all duration-1000 sp-progress-zero"></div>
		</div>
		</div>
	</div>

	<!-- Device Type -->
	<div
		class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="devices">
		<h3 class="text-[13px] font-bold mb-8 text-gray-800 text-center cursor-move"><?php esc_html_e( 'Device Type', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		<div class="flex justify-between items-end h-[110px]">
		<div class="flex-1 text-center group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="mx-auto mb-3 opacity-60">
			<rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
			<line x1="12" y1="18" x2="12.01" y2="18"></line>
			</svg>
			<span class="block text-lg font-bold text-gray-800 mb-0.5" id="dv-mob-pct">0%</span>
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider"><?php esc_html_e( 'Mobile', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
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
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider"><?php esc_html_e( 'Desktop', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		<div class="flex-1 text-center group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
			stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
			class="mx-auto mb-3 opacity-60">
			<rect x="5" y="3" width="14" height="18" rx="2" ry="2"></rect>
			<line x1="12" y1="18" x2="12.01" y2="18"></line>
			</svg>
			<span class="block text-lg font-bold text-gray-800 mb-0.5" id="dv-tab-pct">0%</span>
			<span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider"><?php esc_html_e( 'Tablet', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</div>
		</div>
	</div>

	<!-- Browser Used -->
	<div
		class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card h-full draggable-item col-span-1 xl:col-span-2"
		data-widget-id="browsers">
		<h3 class="text-[13px] font-bold mb-6 text-gray-800 text-center cursor-move"><?php esc_html_e( 'Browser Used', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		<div id="browserChartWrap" class="space-y-5">
		<!-- Re-designed by JS -->
		</div>
	</div>

	<!-- Overview Top Pages -->
	<div
		class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card draggable-item h-full col-span-1 xl:col-span-3"
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
			<?php esc_html_e( 'Top Pages', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</div>
		</h2>
		<div class="sp-table-wrap overflow-hidden rounded-xl border border-gray-50">
		<table class="w-full text-sm sp-table">
			<thead
			class="bg-gray-50/50 text-gray-400 font-medium uppercase text-[10px] tracking-widest border-b border-gray-50">
			<tr>
				<th class="p-4 text-left font-bold uppercase"><?php esc_html_e( 'Page Path', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="p-4 text-right font-bold uppercase"><?php esc_html_e( 'Views', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody id="overviewTopPagesBody" class="divide-y divide-gray-50">
			<tr>
				<td colspan="2" class="text-center p-8 text-gray-400"><?php esc_html_e( 'Loading data...', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td>
			</tr>
			</tbody>
		</table>
		</div>
	</div>

	<!-- Overview Top Countries -->
	<div
		class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card draggable-item h-full col-span-1 xl:col-span-3"
		data-widget-id="top_countries">
		<h2 class="text-[14px] font-semibold mb-6 flex justify-between items-center text-gray-800 cursor-move group">
		<div class="flex items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
			<circle cx="12" cy="12" r="10"></circle>
			<line x1="2" y1="12" x2="22" y2="12"></line>
			<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
			</svg>
			<?php esc_html_e( 'Visitor Geography', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</div>
		</h2>
		<div class="sp-table-wrap overflow-hidden rounded-xl border border-gray-50">
		<table class="w-full text-sm sp-table">
			<thead
			class="bg-gray-50/50 text-gray-400 font-medium uppercase text-[10px] tracking-widest border-b border-gray-50">
			<tr>
				<th class="p-4 text-left font-bold uppercase"><?php esc_html_e( 'Country', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="p-4 text-right font-bold uppercase"><?php esc_html_e( 'Visitors', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody id="overviewTopCountriesBody" class="divide-y divide-gray-50">
			<tr>
				<td colspan="2" class="text-center p-8 text-gray-400"><?php esc_html_e( 'Loading data...', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></td>
			</tr>
			</tbody>
		</table>
		</div>
	</div>



	</div> <!-- END OVERVIEW -->

	<!-- ================= SEPARATE SECTIONS ================= -->

	<div id="topPagesSection" class="hidden sp-dashboard-section animate-fade-in">
	<div class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card">
		<h2 class="sp-section-title text-lg font-semibold mb-6 text-gray-800"><?php esc_html_e( 'Top Pages', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
		<div class="sp-table-wrap overflow-x-auto rounded-xl border border-gray-50">
		<table class="w-full text-sm sp-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'URL Path', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-center"><?php esc_html_e( 'Views', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-center"><?php esc_html_e( 'Sessions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-center"><?php esc_html_e( 'New Users', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-center"><?php esc_html_e( 'Bounce %', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody id="fullTopPagesBody"></tbody>
		</table>
		</div>
	</div>
	</div>

	<div id="topCountriesSection" class="hidden sp-dashboard-section animate-fade-in">
	<div class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card">
		<h2 class="sp-section-title text-lg font-semibold mb-6 text-gray-800"><?php esc_html_e( 'Top Countries', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
		<div class="sp-table-wrap overflow-x-auto rounded-xl border border-gray-50">
		<table class="w-full sp-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Country', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-right"><?php esc_html_e( 'Total Visitors', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody id="fullTopCountriesBody"></tbody>
		</table>
		</div>
	</div>
	</div>

	<div id="sourceMediumSection" class="hidden sp-dashboard-section animate-fade-in">
	<div class="sp-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 pulse-analytics-soft-card">
		<h2 class="sp-section-title text-lg font-semibold mb-6 text-gray-800"><?php esc_html_e( 'Source / Medium', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
		<div class="sp-table-wrap overflow-x-auto rounded-xl border border-gray-50">
		<table id="sourceMediumTable" class="w-full sp-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Source / Medium', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
				<th class="text-right"><?php esc_html_e( 'Total Sessions', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
		</div>
	</div>
	</div>


</div>

