<?php
/**
 * Pulse Analytics - Settings: eCommerce Tracking View
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$sm_pulse_analytics_has_woocommerce   = ( class_exists( 'WooCommerce' ) || defined( 'WC_PLUGIN_FILE' ) || ( function_exists( 'sm_pulse_analytics_is_woocommerce_active' ) && sm_pulse_analytics_is_woocommerce_active() ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) );
$sm_pulse_analytics_has_edd           = ( class_exists( 'Easy_Digital_Downloads' ) || defined( 'EDD_VERSION' ) || ( function_exists( 'sm_pulse_analytics_is_edd_active' ) && sm_pulse_analytics_is_edd_active() ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) );
$sm_pulse_analytics_has_memberpress   = ( class_exists( 'MeprAppCtrl' ) || class_exists( 'MeprTransaction' ) || defined( 'MEPR_VERSION' ) || ( function_exists( 'sm_pulse_analytics_is_memberpress_active' ) && sm_pulse_analytics_is_memberpress_active() ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'memberpress/memberpress.php' ) ) );
$sm_pulse_analytics_has_givewp        = ( class_exists( 'Give' ) || function_exists( 'give_get_payments' ) || defined( 'GIVE_VERSION' ) || ( function_exists( 'sm_pulse_analytics_is_givewp_active' ) && sm_pulse_analytics_is_givewp_active() ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'give/give.php' ) ) );
$sm_pulse_analytics_has_any_commerce  = $sm_pulse_analytics_has_woocommerce || $sm_pulse_analytics_has_edd || $sm_pulse_analytics_has_memberpress || $sm_pulse_analytics_has_givewp;

$sm_pulse_analytics_store_ecommerce_opt         = get_option( 'sm_pulse_analytics_ecommerce', array( 'enable' => true, 'ecommerce' => array( 'woocommerce' ) ) );
$sm_pulse_analytics_enable_ecommerce_tracking   = ( isset( $sm_pulse_analytics_store_ecommerce_opt['enable'] ) && true === (bool) $sm_pulse_analytics_store_ecommerce_opt['enable'] ) ? '1' : ( $options['enable_ecommerce_tracking'] ?? '1' );
$sm_pulse_analytics_active_ecommerces            = (array) ( $sm_pulse_analytics_store_ecommerce_opt['ecommerce'] ?? array() );
$sm_pulse_analytics_enable_woocommerce_tracking = ( $sm_pulse_analytics_has_woocommerce && in_array( 'woocommerce', $sm_pulse_analytics_active_ecommerces, true ) ) ? '1' : '0';
$sm_pulse_analytics_enable_edd_tracking         = ( $sm_pulse_analytics_has_edd && in_array( 'edd', $sm_pulse_analytics_active_ecommerces, true ) ) ? '1' : '0';
$sm_pulse_analytics_enable_memberpress_tracking = ( $sm_pulse_analytics_has_memberpress && in_array( 'memberpress', $sm_pulse_analytics_active_ecommerces, true ) ) ? '1' : '0';
$sm_pulse_analytics_enable_givewp_tracking      = ( $sm_pulse_analytics_has_givewp && in_array( 'givewp', $sm_pulse_analytics_active_ecommerces, true ) ) ? '1' : '0';
?>

<div class="sp-relative" id="pulse-analytics-ecommerce-settings-wrapper">


	<div>
			<div class="settings-card sp-settings-card-padded-lg">

				<div class="sp-settings-card-header">
					<div class="sp-flex-center-gap-14">
						<div class="icon-circle">
							<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="9" cy="21" r="1"></circle>
								<circle cx="20" cy="21" r="1"></circle>
								<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
							</svg>
						</div>
						<div>
							<h2 class="sp-settings-card-title"><?php esc_html_e( 'eCommerce Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
					</div>

					<div class="sp-flex-gap-12">
						<label class="pulse-analytics-switch">
							<input type="checkbox" name="sm_pulse_analytics_settings[enable_ecommerce_tracking]" value="1" <?php checked( '1', $sm_pulse_analytics_enable_ecommerce_tracking ); ?> <?php disabled( ! $sm_pulse_analytics_has_any_commerce ); ?> />
							<span class="pulse-analytics-slider"></span>
						</label>
						<span class="sp-label-semibold"><?php esc_html_e( 'Enable eCommerce Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
					</div>
				</div>

				<?php if ( ! $sm_pulse_analytics_has_any_commerce ) : ?>
					<div class="sp-info-dashed-box">
						<?php esc_html_e( 'No commerce plugin detected. Install WooCommerce or Easy Digital Downloads to enable purchase tracking, or switch your site profile to Website mode if you only need traffic analytics.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
					</div>
				<?php endif; ?>

				<div class="pulse-analytics-integrations-list">
					<div class="pulse-analytics-integration-row">
						<label class="pulse-analytics-switch">
							<input type="checkbox" name="sm_pulse_analytics_settings[enable_woocommerce_tracking]" value="1" <?php checked( '1', $sm_pulse_analytics_enable_woocommerce_tracking ); ?> <?php disabled( ! $sm_pulse_analytics_has_woocommerce ); ?> />
							<span class="pulse-analytics-slider"></span>
						</label>
						<div class="sp-body-text">
							<?php if ( $sm_pulse_analytics_has_woocommerce ) : ?>
								<strong>WooCommerce</strong> — <?php esc_html_e( 'physical & variable product stores', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							<?php else : ?>
								<strong>WooCommerce</strong> — <?php esc_html_e( 'not installed', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							<?php endif; ?>
						</div>
					</div>

					<div class="pulse-analytics-integration-row">
						<label class="pulse-analytics-switch">
							<input type="checkbox" name="sm_pulse_analytics_settings[enable_edd_tracking]" value="1" <?php checked( '1', $sm_pulse_analytics_enable_edd_tracking ); ?> <?php disabled( ! $sm_pulse_analytics_has_edd ); ?> />
							<span class="pulse-analytics-slider"></span>
						</label>
						<div class="sp-body-text">
							<?php if ( $sm_pulse_analytics_has_edd ) : ?>
								<strong>Easy Digital Downloads</strong> — <?php esc_html_e( 'digital downloads & license sales', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							<?php else : ?>
								<strong>Easy Digital Downloads</strong> — <?php esc_html_e( 'not installed', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							<?php endif; ?>
						</div>
					</div>

					<?php
					$sm_pulse_analytics_all_sm_pulse_analytics_settings = get_option( 'sm_pulse_analytics_settings', array() );
					$sm_pulse_analytics_abandon_timeout         = isset( $sm_pulse_analytics_all_sm_pulse_analytics_settings['cart_abandon_timeout'] ) ? (string) $sm_pulse_analytics_all_sm_pulse_analytics_settings['cart_abandon_timeout'] : '24';
					?>
					<div class="pulse-analytics-integration-row sp-integration-col">
						<div>
							<strong class="sp-label-strong"><?php esc_html_e( 'Cart Abandonment Session Timeout', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong>
							<p class="sp-desc-mt-4-inline">
								<?php esc_html_e( 'Select the inactivity duration before an uncompleted cart session is classified as abandoned.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</p>
						</div>
						<div>
							<select class="sp-select-sm" name="sm_pulse_analytics_settings[cart_abandon_timeout]" <?php disabled( ! $sm_pulse_analytics_has_any_commerce ); ?>>
								<option value="1" <?php selected( $sm_pulse_analytics_abandon_timeout, '1' ); ?>><?php esc_html_e( '1 hour', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
								<option value="6" <?php selected( $sm_pulse_analytics_abandon_timeout, '6' ); ?>><?php esc_html_e( '6 hours', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
								<option value="12" <?php selected( $sm_pulse_analytics_abandon_timeout, '12' ); ?>><?php esc_html_e( '12 hours', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
								<option value="24" <?php selected( $sm_pulse_analytics_abandon_timeout, '24' ); ?>><?php esc_html_e( '24 hours (Default)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
								<option value="48" <?php selected( $sm_pulse_analytics_abandon_timeout, '48' ); ?>><?php esc_html_e( '48 hours', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
								<option value="72" <?php selected( $sm_pulse_analytics_abandon_timeout, '72' ); ?>><?php esc_html_e( '72 hours', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
							</select>
						</div>
					</div>
				</div>

				<div class="sp-form-actions">
					<button type="submit" class="btn-premium"><?php esc_html_e( 'Save eCommerce Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
					<a href="?page=sm-pulse-analytics-settings&step=links" class="btn-outline"><?php esc_html_e( 'Affiliate & Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> &rarr;</a>
				</div>

			</div>
		</div>

</div>
