<?php
/**
 * eCommerce insights PRO feature for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check PRO license.
if ( ! function_exists( 'StorePulse_is_pro_active' ) ) {
	require_once GA_PLUGIN_DIR . 'includes/helpers.php';
}

if ( ! StorePulse_is_pro_active() ) {
	wp_die( 'This feature requires Pulse Analytics PRO. Please upgrade to access eCommerce insights.' );
}

?>

<div id="StorePulse-pro-ei">

	<!-- Page Header -->
	<div class="wpei-page-header">
		<div class="wpei-header-left">
			<div class="wpei-header-icon">💎</div>
			<div class="wpei-header-text">
				<h1>eCommerce Insights</h1>
				<p>Customer Lifetime Value, Segmentation & Attribution Analysis</p>
			</div>
		</div>
		<div class="wpei-pro-badge">⚡ PRO Feature</div>
	</div>

	<!-- Date Range Filter -->
	<div class="wpei-filter-card">
		<div class="wpei-date-group">
			<label>Date Range</label>
			<div class="wpei-date-inputs">
				<input type="date" id="start-date"
					value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-90 days' ) ) ); ?>">
				<span class="wpei-sep">→</span>
				<input type="date" id="end-date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
			</div>
		</div>
		<button id="load-insights" class="wpei-load-btn">
			<span>🔍</span> Load Insights
		</button>
	</div>

	<!-- Tabs card -->
	<div class="wpei-tabs-card">
		<div class="wpei-tabs-nav">
			<button class="tab-button active" data-tab="clv">
				<span>📈</span> Customer Lifetime Value
			</button>
			<button class="tab-button" data-tab="segmentation">
				<span>🎯</span> Segmentation
			</button>
			<button class="tab-button" data-tab="attribution">
				<span>🔗</span> Attribution
			</button>
		</div>

		<!-- CLV Tab -->
		<div id="tab-clv" class="tab-content">
			<div id="clv-content">
				<div class="wpei-tab-empty">
					<div class="wpei-tab-empty-icon">📈</div>
					<p>Click <strong>Load Insights</strong> to view Customer Lifetime Value data</p>
				</div>
			</div>
		</div>

		<!-- Segmentation Tab -->
		<div id="tab-segmentation" class="tab-content hidden">
			<div id="segmentation-content">
				<div class="wpei-tab-empty">
					<div class="wpei-tab-empty-icon">🎯</div>
					<p>Click <strong>Load Insights</strong> to view Customer Segmentation data</p>
				</div>
			</div>
		</div>

		<!-- Attribution Tab -->
		<div id="tab-attribution" class="tab-content hidden">
			<div id="attribution-content">
				<div class="wpei-tab-empty">
					<div class="wpei-tab-empty-icon">🔗</div>
					<p>Click <strong>Load Insights</strong> to view Attribution data</p>
				</div>
			</div>
		</div>

	</div><!-- .wpei-tabs-card -->

</div><!-- #StorePulse-pro-ei -->
