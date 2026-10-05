<?php
/**
 * Helper functions for Pulse Analytics for WordPress.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sm_pulse_analytics_get_encryption_key' ) ) {
	/**
	 * Derive secure AES-256 key from WordPress salts.
	 *
	 * @return string
	 */
	function sm_pulse_analytics_get_encryption_key() {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'sm_pulse_analytics_fallback_secret_key_salt';
		$salt .= defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : 'sm_pulse_analytics_logged_in_salt';
		return hash_pbkdf2( 'sha256', $salt, 'sm_pulse_analytics_salt_2026', 1000, 32, true );
	}
}

if ( ! function_exists( 'sm_pulse_analytics_encrypt' ) ) {
	/**
	 * Encrypt plain text using AES-256-CBC.
	 *
	 * @param string $plain_text Plain text to encrypt.
	 * @return string Encrypted string prefixed with sp_enc:.
	 */
	function sm_pulse_analytics_encrypt( $plain_text ) {
		if ( empty( $plain_text ) ) {
			return '';
		}

		// If already encrypted, return as is.
		if ( 0 === strpos( $plain_text, 'sp_enc:' ) ) {
			return $plain_text;
		}

		$key       = sm_pulse_analytics_get_encryption_key();
		$iv        = openssl_random_pseudo_bytes( 16 );
		$encrypted = openssl_encrypt( $plain_text, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			return $plain_text;
		}

		return 'sp_enc:' . base64_encode( $iv ) . ':' . $encrypted;
	}
}

if ( ! function_exists( 'sm_pulse_analytics_decrypt' ) ) {
	/**
	 * Decrypt AES-256-CBC encrypted string.
	 * Returns unencrypted legacy strings safely.
	 *
	 * @param string $cipher_text Encrypted text.
	 * @return string Decrypted plain text.
	 */
	function sm_pulse_analytics_decrypt( $cipher_text ) {
		if ( empty( $cipher_text ) ) {
			return '';
		}

		// Legacy unencrypted plain text fallback.
		if ( 0 !== strpos( $cipher_text, 'sp_enc:' ) ) {
			return $cipher_text;
		}

		$parts = explode( ':', substr( $cipher_text, 7 ), 2 );
		if ( count( $parts ) !== 2 ) {
			return $cipher_text;
		}

		$key       = sm_pulse_analytics_get_encryption_key();
		$iv        = base64_decode( $parts[0] );
		$encrypted = $parts[1];

		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );

		return ( false !== $decrypted ) ? $decrypted : $cipher_text;
	}
}

if ( ! function_exists( 'sm_pulse_analytics_get_pro_upgrade_url' ) ) {
	/**
	 * Get the Pro Upgrade URL for buttons across the plugin.
	 *
	 * @return string
	 */
	function sm_pulse_analytics_get_pro_upgrade_url() {
		if ( defined( 'SM_PULSE_ANALYTICS_PRO_UPGRADE_URL' ) && ! empty( SM_PULSE_ANALYTICS_PRO_UPGRADE_URL ) ) {
			return SM_PULSE_ANALYTICS_PRO_UPGRADE_URL;
		}
		return 'https://www.smackcoders.com/ga4-analytics-plugin-for-wordpress.html?utm_source=pulse_analytics&utm_medium=plugin&utm_campaign=upgrade_to_pro&utm_content=upgrade_button';
	}
}




/**
 * Check if PRO license is active.
 *
 * Checks for PRO license via option or constant.
 * Can be overridden with PulseAnalytics_PRO_LICENSE constant in wp-config.php.
 *
 * @return bool True if PRO license is active, false otherwise.
 */
function sm_pulse_analytics_is_pro_active() {
	return class_exists( '\Sm_Pulse_Analytics\PulseAnalyticsCore\Core' )
		|| class_exists( 'PulseAnalytics_Core' )
		|| class_exists( '\Sm_Pulse_Analytics\ProCore\Pro_Core' )
		|| class_exists( 'PulseAnalytics_Pro_Core' )
		|| defined( 'SM_PULSE_ANALYTICS_PRO_CORE_DIR' )
		|| defined( 'SM_PULSE_ANALYTICS_PRO_CORE_VERSION' )
		|| defined( 'SM_PULSE_ANALYTICS_CORE_DIR' )
		|| defined( 'SM_PULSE_ANALYTICS_CORE_VERSION' );
}

/**
 * Admin wrapper CSS classes for report/settings screens.
 *
 * @param string $extra Optional space-separated class names.
 * @return string
 */
function sm_pulse_analytics_get_admin_ui_classes( $extra = '' ) {
	$classes = array( 'pulse-analytics-ui' );
	if ( sm_pulse_analytics_is_pro_active() ) {
		$classes[] = 'sp-mode-pro';
	}
	if ( '' !== (string) $extra ) {
		$classes = array_merge( $classes, preg_split( '/\s+/', trim( (string) $extra ) ) );
	}
	return implode( ' ', array_unique( array_filter( $classes ) ) );
}

/**
 * Allowed Pro report action hooks (prefixed; extensible via filter).
 *
 * @return string[]
 */
function sm_pulse_analytics_get_pro_report_hooks() {
	$hooks = array(
		'sm_pulse_analytics_render_realtime_report',
		'sm_pulse_analytics_render_traffic_overview',
		'sm_pulse_analytics_render_ecommerce_overview',
		'sm_pulse_analytics_render_campaign_url_tracking',
		'sm_pulse_analytics_render_site_performance',
		'sm_pulse_analytics_render_user_journey',
		'sm_pulse_analytics_render_custom_events_report',
		'sm_pulse_analytics_render_search_console',
	);

	/**
	 * Filter registered Pro report render hooks.
	 *
	 * @param string[] $hooks Prefixed action hook names.
	 */
	return apply_filters( 'sm_pulse_analytics_pro_report_hooks', $hooks );
}

/**
 * Render a Pro report screen shell (header + action hook for Pro add-on UI).
 *
 * @param string $hook_name Action fired after the shared admin header renders.
 */
function sm_pulse_analytics_render_pro_report_page( $hook_name ) {
	sm_pulse_analytics_render_admin_header();

	if ( ! is_string( $hook_name ) || ! in_array( $hook_name, sm_pulse_analytics_get_pro_report_hooks(), true ) ) {
		return;
	}

	switch ( $hook_name ) {
		case 'sm_pulse_analytics_render_realtime_report':
			do_action( 'sm_pulse_analytics_render_realtime_report' );
			break;
		case 'sm_pulse_analytics_render_traffic_overview':
			do_action( 'sm_pulse_analytics_render_traffic_overview' );
			break;
		case 'sm_pulse_analytics_render_ecommerce_overview':
			do_action( 'sm_pulse_analytics_render_ecommerce_overview' );
			break;
		case 'sm_pulse_analytics_render_campaign_url_tracking':
			do_action( 'sm_pulse_analytics_render_campaign_url_tracking' );
			break;
		case 'sm_pulse_analytics_render_site_performance':
			do_action( 'sm_pulse_analytics_render_site_performance' );
			break;
		case 'sm_pulse_analytics_render_user_journey':
			do_action( 'sm_pulse_analytics_render_user_journey' );
			break;
		case 'sm_pulse_analytics_render_custom_events_report':
			do_action( 'sm_pulse_analytics_render_custom_events_report' );
			break;
		case 'sm_pulse_analytics_render_search_console':
			do_action( 'sm_pulse_analytics_render_search_console' );
			break;
	}
}

/**
 * Whether a bundled PRO feature module is enabled in settings (#48).
 *
 * @param string $slug Feature slug from the PRO feature registry.
 * @return bool
 */
function sm_pulse_analytics_pro_feature_is_enabled( $slug ) {
	if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalyticsCore\Pro_Feature_Manager' ) ) {
		return \Sm_Pulse_Analytics\PulseAnalyticsCore\Pro_Feature_Manager::is_enabled( $slug );
	}

	return true;
}

/**
 * Whether the eCommerce PRO feature module is available.
 *
 * @return bool
 */
function sm_pulse_analytics_is_ecommerce_addon_active() {
	return class_exists( 'PulseAnalytics_Ecommerce_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\EcommerceModule\Ecommerce_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_ECOMMERCE_DIR' ) || defined( 'SM_PULSE_ANALYTICS_ECOMMERCE_ADDON_DIR' );
}

/**
 * Whether the PPC Ads Tracking PRO feature module is available (#33).
 *
 * @return bool
 */
function sm_pulse_analytics_is_ppc_tracking_addon_active() {
	return class_exists( 'PulseAnalytics_PPC_Tracking_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\PpcTrackingModule\PPC_Tracking_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_PPC_TRACKING_DIR' ) || defined( 'SM_PULSE_ANALYTICS_PPC_TRACKING_ADDON_DIR' );
}

/**
 * Whether the EU Compliance & Privacy PRO feature module is available (#34).
 *
 * @return bool
 */
function sm_pulse_analytics_is_eu_compliance_addon_active() {
	return class_exists( 'PulseAnalytics_EU_Compliance_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\EuComplianceModule\EU_Compliance_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_EU_COMPLIANCE_DIR' ) || defined( 'SM_PULSE_ANALYTICS_EU_COMPLIANCE_ADDON_DIR' );
}

/**
 * Whether the Performance Optimization PRO feature module is available (#35).
 *
 * @return bool
 */
function sm_pulse_analytics_is_performance_addon_active() {
	return class_exists( 'PulseAnalytics_Performance_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\PerformanceModule\Performance_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_PERFORMANCE_DIR' ) || defined( 'SM_PULSE_ANALYTICS_PERFORMANCE_ADDON_DIR' );
}

/**
 * Whether the Page Insights PRO feature module is available (#36).
 *
 * @return bool
 */
function sm_pulse_analytics_is_page_insights_addon_active() {
	return class_exists( 'PulseAnalytics_Page_Insights_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\PageInsightsModule\Page_Insights_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_PAGE_INSIGHTS_DIR' ) || defined( 'SM_PULSE_ANALYTICS_PAGE_INSIGHTS_ADDON_DIR' );
}

/**
 * Whether the Advanced Reporting & AI Analytics PRO feature module is available (#37).
 *
 * @return bool
 */
function sm_pulse_analytics_is_advanced_reporting_addon_active() {
	return class_exists( 'PulseAnalytics_Advanced_Reporting_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\AdvancedReportingModule\Advanced_Reporting_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_ADVANCED_REPORTING_DIR' ) || defined( 'SM_PULSE_ANALYTICS_ADVANCED_REPORTING_ADDON_DIR' );
}

/**
 * Whether the Site Notes PRO feature module is available (#38).
 *
 * @return bool
 */
function sm_pulse_analytics_is_site_notes_addon_active() {
	return class_exists( 'PulseAnalytics_Site_Notes_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\SiteNotesModule\Site_Notes_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_SITE_NOTES_DIR' ) || defined( 'SM_PULSE_ANALYTICS_SITE_NOTES_ADDON_DIR' );
}

/**
 * Whether the Affiliate Link Tracking PRO feature module is available (#39).
 *
 * @return bool
 */
function sm_pulse_analytics_is_affiliate_link_addon_active() {
	return class_exists( 'PulseAnalytics_Affiliate_Link_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_DIR' ) || defined( 'SM_PULSE_ANALYTICS_AFFILIATE_LINK_ADDON_DIR' );
}

/**
 * Whether the Exceptions Reporting PRO feature module is available (#40).
 *
 * @return bool
 */
function sm_pulse_analytics_is_exceptions_addon_active() {
	return class_exists( 'PulseAnalytics_Exceptions_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\ExceptionsModule\Exceptions_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_EXCEPTIONS_DIR' ) || defined( 'SM_PULSE_ANALYTICS_EXCEPTIONS_ADDON_DIR' );
}

/**
 * Whether the Popular Posts PRO feature module is available (#41).
 *
 * @return bool
 */
function sm_pulse_analytics_is_popular_posts_addon_active() {
	return class_exists( 'PulseAnalytics_Popular_Posts_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\PopularPostsModule\Popular_Posts_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_POPULAR_POSTS_DIR' ) || defined( 'SM_PULSE_ANALYTICS_POPULAR_POSTS_ADDON_DIR' );
}

/**
 * Whether the Media Tracking PRO feature module is available (#42).
 *
 * @return bool
 */
function sm_pulse_analytics_is_media_tracking_addon_active() {
	return class_exists( 'PulseAnalytics_Media_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\MediaModule\Media_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_MEDIA_DIR' ) || defined( 'SM_PULSE_ANALYTICS_MEDIA_ADDON_DIR' );
}

/**
 * Whether the AMP Tracking PRO feature module is available (#43).
 *
 * @return bool
 */
function sm_pulse_analytics_is_amp_tracking_addon_active() {
	return class_exists( 'PulseAnalytics_Amp_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\AmpModule\Amp_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_AMP_DIR' ) || defined( 'SM_PULSE_ANALYTICS_AMP_ADDON_DIR' );
}

/**
 * Whether the Campaign Builder PRO feature module is available (#44).
 *
 * @return bool
 */
function sm_pulse_analytics_is_campaign_builder_addon_active() {
	return class_exists( 'PulseAnalytics_Campaign_Builder_Module' )
		|| class_exists( '\Sm_Pulse_Analytics\CampaignBuilderModule\Campaign_Builder_Module' )
		|| defined( 'SM_PULSE_ANALYTICS_CAMPAIGN_BUILDER_DIR' ) || defined( 'SM_PULSE_ANALYTICS_CAMPAIGN_BUILDER_ADDON_DIR' );
}

/**
 * Whether the Clarity Heatmaps PRO feature module is available (#45).
 *
 * @return bool
 */
function sm_pulse_analytics_is_clarity_heatmaps_addon_active() {
	return false;
}

/**
 * Write an audit log entry to sm_pulse_analytics_audit.
 *
 * @param string $action  Human-readable action label.
 * @param array  $details Optional context (secrets are redacted).
 */
function sm_pulse_analytics_log_audit( $action, $details = array() ) {
	if ( ! class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Storage' ) ) {
		return;
	}

	$safe = array();
	foreach ( (array) $details as $key => $value ) {
		$key_lower = strtolower( (string) $key );
		if ( false !== strpos( $key_lower, 'secret' ) || false !== strpos( $key_lower, 'password' ) || false !== strpos( $key_lower, 'key' ) ) {
			$safe[ $key ] = '[redacted]';
		} else {
			$safe[ $key ] = is_scalar( $value ) ? $value : wp_json_encode( $value );
		}
	}

	\Sm_Pulse_Analytics\PulseAnalytics_Storage::insert_log(
		'audit',
		sanitize_text_field( $action ) . ( ! empty( $safe ) ? ' — ' . wp_json_encode( $safe ) : '' ),
		array(
			'source' => 'settings',
		)
	);
}

/**
 * Shared traffic date range picker markup for admin report headers.
 *
 * @param string $wrap_class Optional extra classes for the outer wrap element.
 */
function sm_pulse_analytics_render_traffic_date_range( $wrap_class = '' ) {
	$wrap_class = trim( 'sp-daterange-wrap relative rounded-xl border border-gray-200 bg-white flex items-center h-[36px] px-3 cursor-pointer hover:bg-gray-50 transition-all duration-200 sp-shadow-xs ' . $wrap_class );
	?>
	<div id="trafficDateRangeWrap" class="<?php echo esc_attr( $wrap_class ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
			stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 sp-daterange-icon">
			<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
			<line x1="16" y1="2" x2="16" y2="6"></line>
			<line x1="8" y1="2" x2="8" y2="6"></line>
			<line x1="3" y1="10" x2="21" y2="10"></line>
		</svg>
		<select id="traffic-date-range-select" class="bg-transparent text-[#1e293b] font-medium outline-none cursor-pointer border-0 text-[13px] py-0 pr-3 sp-traffic-date-select">
			<option value="7daysAgo"><?php esc_html_e( 'Last 7 Days', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="30daysAgo" selected><?php esc_html_e( 'Last 30 Days', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="90daysAgo"><?php esc_html_e( 'Last 90 Days', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			<option value="custom"><?php esc_html_e( 'Custom Range...', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
		</select>
		<input type="text" id="traffic-custom-date-picker" class="hidden bg-white text-[#1e293b] text-xs font-medium px-2 py-0.5 rounded border border-slate-300 outline-none ml-2" placeholder="<?php esc_attr_e( 'Select custom range', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" />
		<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
			stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ml-1 pointer-events-none sp-daterange-chevron sp-ml-4">
			<polyline points="6 9 12 15 18 9"></polyline>
		</svg>
	</div>
	<?php
}

/**
 * Render the unified Pulse Analytics header and navigation.
 */
function sm_pulse_analytics_render_admin_header() {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$version      = '1.0.1'; // Ideally fetched from a central place.

	// Free-only nav. Pro injects extra items via sm_pulse_analytics_admin_nav_items.
	$nav_items = array(
		'pulse-analytics'               => array(
			'label' => __( 'Dashboard', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'svg'   => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
		),
		'pulse-analytics-link-report'   => array(
			'label' => __( 'Audience & Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'svg'   => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
		),
		'pulse-analytics-affiliate-links'    => array(
			'label' => __( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'svg'   => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
		),
		'pulse-analytics-forms'         => array(
			'label' => __( 'Forms Conversion', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'svg'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
		),
		'sm-pulse-analytics-settings'                  => array(
			'label' => __( 'Settings', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'svg'   => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
		),
	);

	$nav_items = apply_filters( 'sm_pulse_analytics_admin_nav_items', $nav_items );
	if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) ) {
		$nav_items = \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::filter_nav_items( $nav_items );
	}

	// Keep Settings last.
	$settings = null;
	if ( isset( $nav_items['sm-pulse-analytics-settings'] ) ) {
		$settings = $nav_items['sm-pulse-analytics-settings'];
		unset( $nav_items['sm-pulse-analytics-settings'] );
		$nav_items['sm-pulse-analytics-settings'] = $settings;
	}

	?>
	<div class="w2ssyn-layout-wrapper sp-font-stack">
		<div id="smackws-core-tabs" class="smackws-tabs w2ssyn-sidebar">
			<div class="w2ssyn-sidebar-brand">
				<div class="w2ssyn-logo-box">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
					</svg>
				</div>
				<div class="w2ssyn-brand-text">
					<span class="w2ssyn-brand-title"><?php esc_html_e( 'Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</div>
			</div>

			<nav class="w2ssyn-sidebar-nav sp-sidebar-nav">
				<?php
				foreach ( $nav_items as $slug => $item ) :
					$isActive    = ( $current_page === $slug );
					$is_settings = ( 'sm-pulse-analytics-settings' === $slug );
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="smack-tab<?php echo esc_attr( $isActive ? ' active' : '' ); ?><?php echo esc_attr( $is_settings ? ' smack-tab-settings' : '' ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
						<svg class="sp-nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<?php
							$svg_allowed = array(
								'svg'      => array(
									'xmlns'       => true,
									'width'       => true,
									'height'      => true,
									'viewbox'     => true,
									'fill'        => true,
									'stroke'      => true,
									'stroke-width'=> true,
									'stroke-linecap' => true,
									'stroke-linejoin' => true,
									'class'       => true,
									'aria-hidden' => true,
									'role'        => true,
									'focusable'   => true,
								),
								'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true ),
								'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true ),
								'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ),
								'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ),
								'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true ),
								'g'        => array( 'fill' => true, 'stroke' => true ),
							);
							echo wp_kses( (string) ( $item['svg'] ?? '' ), $svg_allowed );
							?>
						</svg>
						<span class="sp-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div id="smackws-cores-tabcontent" class="w2ssyn-main-layout">
			<div class="smack-tab-pane">
				<?php
				global $sm_pulse_analytics_captured_notices;
				$notices_html = ! empty( $sm_pulse_analytics_captured_notices ) ? wp_kses_post( $sm_pulse_analytics_captured_notices ) : '';
				echo '<div class="pulse-analytics-notices-area sp-notices-area"><div id="pulse-analytics-admin-notices-container"></div>' . $notices_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd above.
				$sm_pulse_analytics_captured_notices = '';
				?>
				<?php
				if ( 'sm-pulse-analytics-settings' === $current_page ) :
					$wizard_steps = apply_filters(
						'sm_pulse_analytics_settings_steps',
						array(
							'help'                    => __( 'Help & Guidance', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'general'                 => __( 'General Settings', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'analytics'               => __( 'Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'links'                   => __( 'Downloads & Link Paths', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'affiliate-link-tracking' => __( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'monsterinsights-import'  => __( 'MonsterInsights Import', 'smackcoders-pulse-analytics-for-woocommerce' ),
						)
					);
					$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'help';
					?>
					<div class="sp-page-header">
						<div>
							<h2 class="sp-page-header__title"><?php esc_html_e( 'Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
							<p class="sp-page-header__desc"><?php esc_html_e( 'Configure Google Analytics integration and advanced tracking options.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
						</div>
					</div>
					<div class="pulse-analytics-ui">
						<div class="sp-settings-layout">
							<nav class="sp-settings-nav sp-card" aria-label="<?php esc_attr_e( 'Settings sections', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
								<?php
								foreach ( $wizard_steps as $step_key => $step_label ) :
									$is_active_step = ( $current_step === $step_key );
									?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=' . $step_key ) ); ?>"
										class="<?php echo esc_attr( $is_active_step ? 'active' : '' ); ?>">
										<?php echo esc_html( $step_label ); ?>
									</a>
								<?php endforeach; ?>
							</nav>
							<div class="sp-settings-content">
				<?php endif; ?>

				<?php if ( in_array( $current_page, array( 'pulse-analytics', 'sm_pulse_analytics_goals', 'pulse-analytics-forms', 'pulse-analytics-forms', 'pulse-analytics-realtime', 'pulse-analytics-traffic-overview', 'pulse-analytics-search-console', 'pulse-analytics-ecommerce-overview', 'pulse-analytics-campaign-url-tracking', 'pulse-analytics-campaigns', 'pulse-analytics-heatmaps', 'pulse-analytics-visitor-recordings', 'pulse-analytics-link-report', 'pulse-analytics-link-report', 'pulse-analytics-core-web-vitals', 'pulse-analytics-user-journey', 'pulse-analytics-advanced-reports', 'pulse-analytics-ai-analytics', 'pulse-analytics-affiliate-links', 'pulse-analytics-amp', 'pulse-analytics-exceptions', 'pulse-analytics-exceptions', 'pulse-analytics-media', 'pulse-analytics-site-performance', 'pulse-analytics-site-performance', 'pulse-analytics-popular-posts', 'pulse-analytics-popular-posts', 'pulse-analytics-site-notes' ), true ) ) : 
					$sm_pulse_analytics_ts_end   = current_time( 'timestamp' );
					$sm_pulse_analytics_ts_start = strtotime( '-30 days', $sm_pulse_analytics_ts_end );

					if ( wp_date( 'Y', $sm_pulse_analytics_ts_start ) === wp_date( 'Y', $sm_pulse_analytics_ts_end ) ) {
						$sm_pulse_analytics_default_range = wp_date( 'M j', $sm_pulse_analytics_ts_start ) . ' – ' . wp_date( 'M j, Y', $sm_pulse_analytics_ts_end );
					} else {
						$sm_pulse_analytics_default_range = wp_date( 'M j, Y', $sm_pulse_analytics_ts_start ) . ' – ' . wp_date( 'M j, Y', $sm_pulse_analytics_ts_end );
					}

					if ( 'sm_pulse_analytics_goals' === $current_page ) {
						$page_title = 'Custom Events Report';
						$page_desc  = 'Track custom events, form conversions, and user interaction milestones in GA4.';
					} elseif ( 'pulse-analytics-forms' === $current_page || 'pulse-analytics-forms' === $current_page ) {
						$page_title = 'Forms Conversion';
						$page_desc  = 'Track views, starts, and submissions across popular form plugins.';
					} elseif ( 'pulse-analytics-realtime' === $current_page ) {
						$page_title = 'Real-Time Report';
						$page_desc  = 'Monitor active site visitors, pageviews per minute, and live traffic stream.';
					} elseif ( 'pulse-analytics-traffic-overview' === $current_page ) {
						$page_title = 'Traffic Overview Report';
						$page_desc  = 'Analyze visitor trends, acquisition channels, landing pages, and traffic sources.';
					} elseif ( 'pulse-analytics-search-console' === $current_page ) {
						$page_title = 'Search Console Report';
						$page_desc  = 'Monitor search queries, organic impressions, clicks, ranking keywords, and average CTR.';
					} elseif ( 'pulse-analytics-ecommerce-overview' === $current_page ) {
						$page_title = 'eCommerce Report';
						$page_desc  = 'Track store revenue, conversion funnels, checkout drop-offs, and product performance.';
					} elseif ( 'pulse-analytics-campaign-url-tracking' === $current_page || 'pulse-analytics-campaigns' === $current_page ) {
						$page_title = 'Campaign & UTM URL Builder';
						$page_desc  = __( 'Create tracking URLs, manage saved campaigns, and view GA4 campaign performance.', 'smackcoders-pulse-analytics-for-woocommerce' );
					} elseif ( 'pulse-analytics-heatmaps' === $current_page ) {
						$page_title = 'Website Heatmaps';
						$page_desc  = __( 'Microsoft Clarity click, scroll, and interaction heatmaps — without local event storage.', 'smackcoders-pulse-analytics-for-woocommerce' );
					} elseif ( 'pulse-analytics-visitor-recordings' === $current_page ) {
						$page_title = 'Visitor Session Recording & Replay';
						$page_desc  = __( 'Microsoft Clarity session replays and visitor journey analysis — without local recording storage.', 'smackcoders-pulse-analytics-for-woocommerce' );
					} elseif ( 'pulse-analytics-link-report' === $current_page || 'pulse-analytics-link-report' === $current_page ) {
						$page_title = 'Audience & Links';
						$page_desc  = 'Analyze top inbound links, outbound affiliate clicks, and referral audience.';
					} elseif ( 'pulse-analytics-core-web-vitals' === $current_page ) {
						$page_title = 'Core Web Vitals';
						$page_desc  = 'Monitor Google PageSpeed performance, LCP, CLS, INP, and site speed metrics.';
					} elseif ( 'pulse-analytics-user-journey' === $current_page ) {
						$page_title = 'User Journey Report';
						$page_desc  = __( 'Visualize visitor paths across pages before key conversions.', 'smackcoders-pulse-analytics-for-woocommerce' );
					} elseif ( 'pulse-analytics-advanced-reports' === $current_page ) {
						$page_title = 'Advanced Reports';
						$page_desc  = 'Flexible GA4 reporting with period comparison, filters, dimension breakdowns, and automatic insights.';
					} elseif ( 'pulse-analytics-ai-analytics' === $current_page ) {
						$page_title = 'Pulse Analytics AI';
						$page_desc  = 'Ask about your website analytics in plain language powered by GA4 data.';
					} elseif ( 'pulse-analytics-affiliate-links' === $current_page ) {
						$page_title = 'Affiliate Link Tracking';
						$page_desc  = 'Outbound and affiliate link clicks leaving your site merged from GA4 events and local telemetry.';
					} elseif ( 'pulse-analytics-amp' === $current_page ) {
						$page_title = 'AMP Tracking';
						$page_desc  = 'AMP page traffic from GA4 — users, sessions, pageviews, engagement, and comparison with standard pages.';
					} elseif ( 'pulse-analytics-exceptions' === $current_page || 'pulse-analytics-exceptions' === $current_page ) {
						$page_title = 'Exceptions Report';
						$page_desc  = 'Monitor JavaScript exceptions and GA4 metric anomalies.';
					} elseif ( 'pulse-analytics-media' === $current_page ) {
						$page_title = 'Media Tracking';
						$page_desc  = 'Video engagement from GA4 — starts, progress milestones, and completion rates for embedded players.';
					} elseif ( 'pulse-analytics-site-performance' === $current_page || 'pulse-analytics-site-performance' === $current_page ) {
						$page_title = 'Site Performance';
						$page_desc  = 'MonsterInsights-style site speed reporting powered by Google PageSpeed Insights.';
					} elseif ( 'pulse-analytics-popular-posts' === $current_page || 'pulse-analytics-popular-posts' === $current_page ) {
						$page_title = 'Popular Posts';
						$page_desc  = 'GA4-powered content rankings mapped to WordPress posts and pages.';
					} elseif ( 'pulse-analytics-site-notes' === $current_page ) {
						$page_title = 'Site Notes';
						$page_desc  = 'Annotate your analytics timeline with marketing, content, and technical events.';
					} else {
						$page_title = 'Dashboard Overview';
						$page_desc  = __( 'Monitor traffic, engagement, and acquisition metrics for your website.', 'smackcoders-pulse-analytics-for-woocommerce' );
					}
				?>
				<div class="sp-page-header">
					<div>
						<h2 id="<?php echo ( 'pulse-analytics-traffic-overview' === $current_page ) ? 'traffic-tab-title' : ''; ?>" class="sp-page-header__title"><?php echo esc_html( $page_title ); ?></h2>
						<p class="sp-page-header__desc"><?php echo esc_html( $page_desc ); ?></p>
					</div>
					<?php if ( 'pulse-analytics-realtime' === $current_page ) : ?>
						<div class="relative rounded-xl border border-gray-200 bg-white flex items-center h-[34px] px-3.5 sp-shadow-xs">
							<span class="relative flex h-2 w-2 mr-2 sp-mr-8">
								<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
								<span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
							</span>
							<span class="text-[13px] font-semibold text-slate-700">Live Active Telemetry</span>
						</div>
					<?php elseif ( 'pulse-analytics-core-web-vitals' === $current_page ) : ?>
						<div class="flex items-center gap-3">
							<div class="pulse-analytics-segmented-bar sp-h-34">
								<button id="device-mobile" type="button" class="pulse-analytics-segmented-tab device-toggle" title="<?php esc_attr_e( 'Mobile View', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
								</button>
								<button id="device-desktop" type="button" class="pulse-analytics-segmented-tab device-toggle active" title="<?php esc_attr_e( 'Desktop View', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12.01" y2="21"></line></svg>
								</button>
							</div>
							<button id="btn-refresh-cwv" type="button" class="sp-btn-primary sp-h-34">
								<svg class="w-3.5 h-3.5 sp-icon-14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
								<span class="btn-text">Run Audit</span>
							</button>
						</div>
					<?php elseif ( 'pulse-analytics-traffic-overview' === $current_page ) : ?>
						<div class="flex items-center gap-3">
							<!-- <button type="button" class="PulseAnalytics-toolbar-btn bg-white text-gray-700 px-3.5 py-1.5 rounded-xl text-xs font-semibold border border-slate-200 hover:bg-slate-50 transition-all shadow-sm flex items-center gap-2 cursor-pointer sp-h-34">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
								Export PDF Report
							</button> -->
							<?php sm_pulse_analytics_render_traffic_date_range(); ?>
						</div>
					<?php elseif ( in_array( $current_page, array( 'pulse-analytics-affiliate-links', 'pulse-analytics-campaigns', 'pulse-analytics-amp' ), true ) ) : ?>
						<?php sm_pulse_analytics_render_traffic_date_range(); ?>
					<?php elseif ( 'pulse-analytics-search-console' === $current_page ) : ?>
						<?php sm_pulse_analytics_render_traffic_date_range(); ?>
					<?php elseif ( 'pulse-analytics-ecommerce-overview' === $current_page ) : ?>
						<div class="flex items-center gap-3">
							<?php sm_pulse_analytics_render_traffic_date_range(); ?>
						</div>
					<?php elseif ( 'pulse-analytics-campaign-url-tracking' === $current_page ) : ?>
						<?php /* Date range lives in the page toolbar (#transactions-date-range). */ ?>

					<?php elseif ( 'pulse-analytics-heatmaps' === $current_page || 'pulse-analytics-visitor-recordings' === $current_page ) : ?>
						<?php /* No date picker — Clarity hosts behavior analytics externally. */ ?>
					<?php elseif ( 'pulse-analytics-forms' === $current_page || 'pulse-analytics-forms' === $current_page ) : ?>
						<?php sm_pulse_analytics_render_traffic_date_range(); ?>
					<?php elseif ( 'pulse-analytics-link-report' === $current_page || 'pulse-analytics-link-report' === $current_page ) : ?>
						<?php sm_pulse_analytics_render_traffic_date_range(); ?>
					<?php elseif ( 'pulse-analytics' === $current_page ) : ?>
						<div class="flex items-center gap-2 flex-wrap">
							<?php sm_pulse_analytics_render_traffic_date_range(); ?>
						</div>
					<?php else : ?>
						<?php sm_pulse_analytics_render_traffic_date_range(); ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
	<?php
	// Ensure the wrapper gets closed in the footer
	if ( ! has_action( 'in_admin_footer', 'sm_pulse_analytics_render_admin_footer_tags' ) ) {
		add_action( 'in_admin_footer', 'sm_pulse_analytics_render_admin_footer_tags' );
	}
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
}

function sm_pulse_analytics_render_admin_footer_tags() {
	$screen = get_current_screen();
	$screen_id = $screen ? strtolower( $screen->id ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : '';

	if (
		( ! empty( $screen_id ) && (
			strpos( $screen_id, 'smackcoders-pulse-analytics-for-woocommerce' ) !== false ||
			strpos( $screen_id, 'pulse-analytics' ) !== false ||
			strpos( $screen_id, 'sm-pulse-analytics' ) !== false
		) ) ||
		( ! empty( $page ) && (
			strpos( $page, 'smackcoders-pulse-analytics-for-woocommerce' ) !== false ||
			strpos( $page, 'pulse-analytics' ) !== false ||
			strpos( $page, 'sm-pulse-analytics' ) !== false
		) )
	) {
		echo '</div></div></div><!-- .w2ssyn-main-layout -->';
	}
}


global $sm_pulse_analytics_captured_notices;
$sm_pulse_analytics_captured_notices = '';

if ( ! function_exists( 'sm_pulse_analytics_begin_admin_notice_capture' ) ) {
	/**
	 * Start buffering core admin notices on Pulse Analytics screens.
	 *
	 * Paired with sm_pulse_analytics_end_admin_notice_capture() on the same request.
	 */
	function sm_pulse_analytics_begin_admin_notice_capture() {
		if ( ! sm_pulse_analytics_is_plugin_page() || ! empty( $GLOBALS['sm_pulse_analytics_notice_buffering'] ) ) {
			return;
		}
		$GLOBALS['sm_pulse_analytics_notice_buffering'] = true;
		ob_start();
	}
}

if ( ! function_exists( 'sm_pulse_analytics_end_admin_notice_capture' ) ) {
	/**
	 * Finish buffering and store notices for re-display inside the plugin chrome.
	 */
	function sm_pulse_analytics_end_admin_notice_capture() {
		global $sm_pulse_analytics_captured_notices;
		if ( empty( $GLOBALS['sm_pulse_analytics_notice_buffering'] ) ) {
			return;
		}
		$GLOBALS['sm_pulse_analytics_notice_buffering'] = false;
		if ( ob_get_level() > 0 ) {
			$sm_pulse_analytics_captured_notices .= ob_get_clean();
		}
	}
}

if ( ! function_exists( 'sm_pulse_analytics_is_plugin_page' ) ) {
	function sm_pulse_analytics_is_plugin_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : '';
		return ! empty( $page ) && (
			strpos( $page, 'pulse-analytics' ) !== false
			|| strpos( $page, 'sm-pulse-analytics' ) !== false
		);
	}
}

add_action( 'admin_notices', 'sm_pulse_analytics_begin_admin_notice_capture', -999999 );
add_action( 'all_admin_notices', 'sm_pulse_analytics_end_admin_notice_capture', 999999 );

/**
 * Allowed HTML for dashboard filter markup (Pro KPI/widgets/tabs/panels).
 *
 * @return array<string, array<string, bool>>
 */
function sm_pulse_analytics_dashboard_allowed_html() {
	$attrs = array(
		'class'            => true,
		'id'               => true,
		'style'            => true,
		'type'             => true,
		'role'             => true,
		'aria-hidden'      => true,
		'aria-label'       => true,
		'data-widget-id'   => true,
		'data-drilldown'   => true,
		'data-trend-tab'   => true,
		'data-ecom-metric' => true,
		'width'            => true,
		'height'           => true,
		'viewbox'          => true,
		'xmlns'            => true,
		'fill'             => true,
		'stroke'           => true,
		'stroke-width'     => true,
		'stroke-linecap'   => true,
		'stroke-linejoin'  => true,
		'd'                => true,
		'points'           => true,
		'cx'               => true,
		'cy'               => true,
		'r'                => true,
		'x'                => true,
		'y'                => true,
		'x1'               => true,
		'y1'               => true,
		'x2'               => true,
		'y2'               => true,
		'rx'               => true,
		'ry'               => true,
	);

	return array(
		'div'      => $attrs,
		'span'     => $attrs,
		'p'        => $attrs,
		'h2'       => $attrs,
		'h3'       => $attrs,
		'button'   => $attrs,
		'table'    => $attrs,
		'thead'    => $attrs,
		'tbody'    => $attrs,
		'tr'       => $attrs,
		'th'       => $attrs,
		'td'       => $attrs,
		'canvas'   => $attrs,
		'svg'      => $attrs,
		'path'     => $attrs,
		'polyline' => $attrs,
		'circle'   => $attrs,
		'line'     => $attrs,
		'rect'     => $attrs,
		'g'        => $attrs,
	);
}


