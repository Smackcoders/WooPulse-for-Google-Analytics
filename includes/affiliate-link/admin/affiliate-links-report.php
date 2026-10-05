<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking report page (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="pulse-analytics-ui" id="pulse-analytics-affiliate-links-page">
	<div class="sp-toolbar-right">
		<!-- Global Date Picker from header will control this report -->
	</div>

	<div id="sp-al-error" class="sp-al-notice sp-al-error sp-hidden"></div>
	<div id="sp-al-loading" class="sp-al-loading sp-hidden">
		<span class="sp-al-spinner"></span>
		<span class="sp-al-loading-text"><?php esc_html_e( 'Loading data…', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
	</div>

	<div id="sp-al-metrics" class="sp-al-metrics"></div>

	<div class="sp-al-tabs">
		<button type="button" class="sp-al-tab active" data-tab="top_links"><?php esc_html_e( 'Top Links', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
		<button type="button" class="sp-al-tab" data-tab="partners"><?php esc_html_e( 'Partners', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
		<button type="button" class="sp-al-tab" data-tab="sources"><?php esc_html_e( 'Source Pages', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
		<button type="button" class="sp-al-tab" data-tab="outbound"><?php esc_html_e( 'Outbound', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
	</div>

	<table class="widefat striped" id="sp-al-table">
		<thead></thead>
		<tbody></tbody>
	</table>
</div>
