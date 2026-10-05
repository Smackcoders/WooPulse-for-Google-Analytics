<?php
/**
 * Pulse Analytics - Settings: Downloads & Link Paths
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_pulse_analytics_enable_affiliate = $options['enable_affiliate_tracking'] ?? '1';
$sm_pulse_analytics_affiliate_rows   = $options['affiliate_links'] ?? array(
	array(
		'path'  => '/go/',
		'label' => 'affiliate',
	),
	array(
		'path'  => '/recommend/',
		'label' => 'affiliate',
	),
);

if ( empty( $sm_pulse_analytics_affiliate_rows ) || ! is_array( $sm_pulse_analytics_affiliate_rows ) ) {
	$sm_pulse_analytics_affiliate_rows = array(
		array(
			'path'  => '/go/',
			'label' => 'affiliate',
		),
		array(
			'path'  => '/recommend/',
			'label' => 'affiliate',
		),
	);
}

$sm_pulse_analytics_enable_downloads    = $options['enable_download_tracking'] ?? '1';
$sm_pulse_analytics_download_extensions = $options['download_file_extensions'] ?? 'pdf, zip, docx, xlsx, csv, txt, mp3, mp4, epub';
$sm_pulse_analytics_pro_affiliate       = function_exists( 'sm_pulse_analytics_is_affiliate_link_addon_active' ) && sm_pulse_analytics_is_affiliate_link_addon_active();
?>

<div class="sp-relative" id="pulse-analytics-links-settings-container">
	<?php if ( $sm_pulse_analytics_pro_affiliate ) : ?>
		<div class="settings-card sp-settings-card-padded">
			<div class="sp-card-section-header">
				<h2 class="sp-card-section-title"><?php esc_html_e( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
			</div>
			<p class="sp-card-section-desc">
				<?php esc_html_e( 'Affiliate and outbound click tracking is configured in the dedicated Affiliate Link Tracking settings tab. Use the Affiliate Links report in the main menu for performance data.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</p>
			<p class="sp-m-0">
				<a class="btn-premium" href="<?php echo esc_url( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=affiliate-link-tracking' ) ); ?>">
					<?php esc_html_e( 'Open Affiliate Link Tracking Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</a>
				<a class="btn-outline sp-ml-8" href="<?php echo esc_url( admin_url( 'admin.php?page=pulse-analytics-affiliate-links' ) ); ?>">
					<?php esc_html_e( 'View Affiliate Links Report', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="settings-card sp-settings-card-padded">
			<div class="sp-card-section-header">
				<h2 class="sp-card-section-title"><?php esc_html_e( 'Link Paths', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
			</div>

			<p class="sp-card-section-desc sp-card-section-desc--flex">
				<?php esc_html_e( 'Track internal redirect paths (for example /go/) as outbound / affiliate clicks.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
			</p>

			<div class="sp-affiliate-grid-header">
				<div><?php esc_html_e( 'Path (example: /go/)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div><?php esc_html_e( 'Label (example: aff)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></div>
				<div></div>
			</div>

			<div id="affiliate-link-paths-container">
				<?php foreach ( $sm_pulse_analytics_affiliate_rows as $sm_pulse_analytics_idx => $sm_pulse_analytics_row ) : ?>
					<div class="affiliate-row sp-affiliate-grid-row">
						<div>
							<input type="text" name="sm_pulse_analytics_settings[affiliate_links][<?php echo esc_attr( $sm_pulse_analytics_idx ); ?>][path]" value="<?php echo esc_attr( $sm_pulse_analytics_row['path'] ?? '' ); ?>" placeholder="<?php esc_attr_e( '/go/', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" class="sp-input-lg" />
						</div>
						<div>
							<input type="text" name="sm_pulse_analytics_settings[affiliate_links][<?php echo esc_attr( $sm_pulse_analytics_idx ); ?>][label]" value="<?php echo esc_attr( $sm_pulse_analytics_row['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'affiliate', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" class="sp-input-lg" />
						</div>
						<div class="sp-text-center">
							<button type="button" class="remove-affiliate-row sp-btn-icon-danger">
								<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="sp-mt-20">
				<button class="sp-btn-add-row" type="button" id="add-affiliate-path-btn">
					<?php esc_html_e( 'Add Another Link Path', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="settings-card sp-settings-card-padded sp-mt-24">
		<div class="sp-card-section-header">
			<h2 class="sp-card-section-title"><?php esc_html_e( 'Downloadable Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
		</div>
		<label class="sp-checkbox-row">
			<input type="checkbox" name="sm_pulse_analytics_settings[enable_download_tracking]" value="1" <?php checked( '1', $sm_pulse_analytics_enable_downloads ); ?> class="sp-checkbox-md">
			<span><?php esc_html_e( 'Enable Downloadable Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
		</label>
		<div>
			<label class="sp-field-label-sm"><?php esc_html_e( 'Tracked File Extensions (comma separated)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label>
			<input type="text" name="sm_pulse_analytics_settings[download_file_extensions]" value="<?php echo esc_attr( $sm_pulse_analytics_download_extensions ); ?>" placeholder="<?php esc_attr_e( 'e.g. pdf, zip, docx, csv, mp3, mp4', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" class="sp-input-lg sp-input-mono" />
			<p class="sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'File downloads matching these file extensions will be tracked automatically.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
		</div>
	</div>

	<?php $sm_pulse_analytics_retention_days = (int) get_option( 'sm_pulse_analytics_telemetry_retention_days', 30 ); ?>
	<div class="settings-card sp-settings-card-padded sp-mt-24">
		<div class="sp-card-section-header">
			<h2 class="sp-card-section-title"><?php esc_html_e( 'Telemetry Data Retention & Storage Pruning', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
		</div>
		<p class="sp-card-section-desc sp-mb-16">
			<?php esc_html_e( 'Control how long raw visitor engagement telemetry is stored in the database before automatic cleanup. Completed eCommerce order journeys are permanently saved to WooCommerce order meta and will never be affected.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</p>
		<div>
			<label class="sp-field-label-sm"><?php esc_html_e( 'Telemetry Retention Period', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label>
			<select class="sp-select-lg" name="sm_pulse_analytics_settings[telemetry_retention_days]">
				<option value="30" <?php selected( 30, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( '30 Days (Recommended Default)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
				<option value="60" <?php selected( 60, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( '60 Days', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
				<option value="90" <?php selected( 90, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( '90 Days (3 Months)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
				<option value="180" <?php selected( 180, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( '180 Days (6 Months)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
				<option value="365" <?php selected( 365, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( '365 Days (1 Year)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
				<option value="0" <?php selected( 0, $sm_pulse_analytics_retention_days ); ?>><?php esc_html_e( 'Keep Forever (Disable Pruning)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
			</select>
			<p class="sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'Automatic daily WP-Cron cleanup purges raw transient page view events older than this limit.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
		</div>
	</div>

	<div class="sp-form-actions">
		<button type="submit" class="btn-premium"><?php esc_html_e( 'Save Link Tracking Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
		<a href="?page=sm-pulse-analytics-settings&step=affiliate-link-tracking" class="btn-outline"><?php esc_html_e( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> &rarr;</a>
	</div>
</div>
