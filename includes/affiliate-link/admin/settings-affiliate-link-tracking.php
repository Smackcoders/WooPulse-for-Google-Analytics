<?php
/**
 * Pulse Analytics - Settings: Affiliate Link Tracking
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

use Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_pulse_analytics_al_settings = Affiliate_Link_Config::get_settings();

if ( ! function_exists( 'sm_pulse_analytics_al_lines' ) ) {
	function sm_pulse_analytics_al_lines( $arr ) {
		return esc_textarea( implode( "\n", (array) $arr ) );
	}
}

$sm_pulse_analytics_partner_lines = array();
foreach ( $sm_pulse_analytics_al_settings['partner_map'] ?? array() as $sm_pulse_analytics_row ) {
	if ( ! empty( $sm_pulse_analytics_row['domain'] ) ) {
		$sm_pulse_analytics_partner_lines[] = $sm_pulse_analytics_row['domain'] . '|' . ( $sm_pulse_analytics_row['name'] ?? $sm_pulse_analytics_row['domain'] );
	}
}
?>

<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<?php if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) : ?>
	<div class="sp-success-banner">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
		<span><?php esc_html_e( 'Affiliate Link Tracking settings saved successfully.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
	</div>
<?php endif; ?>

<div class="settings-card">
	<div class="settings-header">
		<div class="icon-circle">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
				<polyline points="15 3 21 3 21 9"></polyline>
				<line x1="10" y1="14" x2="21" y2="3"></line>
			</svg>
		</div>
		<h2><?php esc_html_e( 'Affiliate & Outbound Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
	</div>
	<p class="settings-description">
		<?php esc_html_e( 'Configure automatic classification of affiliate and outbound link clicks across your website to report referral engagement in Google Analytics 4.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
	</p>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Master Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<label class="sp-inline-checkbox-label">
					<input type="checkbox" name="sm_pulse_analytics_affiliate_link_settings[enabled]" value="1" <?php checked( ! empty( $sm_pulse_analytics_al_settings['enabled'] ) ); ?> class="sp-checkbox-md" />
					<span><?php esc_html_e( 'Enable Affiliate Link Tracking Engine', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</label>
				<p class="description sp-desc sp-desc--slate sp-mt-6">
					<?php esc_html_e( 'Activates front-end client link monitoring and click event classification.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Affiliate Click Events', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<label class="sp-inline-checkbox-label">
					<input type="checkbox" name="sm_pulse_analytics_affiliate_link_settings[track_affiliate]" value="1" <?php checked( ! empty( $sm_pulse_analytics_al_settings['track_affiliate'] ) ); ?> class="sp-checkbox-md" />
					<span><?php esc_html_e( 'Fire affiliate_link_click GA4 events', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</label>
				<p class="description sp-desc sp-desc--slate sp-mt-6">
					<?php esc_html_e( 'Sends structured partner, destination domain, and affiliate campaign metadata to GA4.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Outbound Click Events', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<label class="sp-inline-checkbox-label">
					<input type="checkbox" name="sm_pulse_analytics_affiliate_link_settings[track_outbound]" value="1" <?php checked( ! empty( $sm_pulse_analytics_al_settings['track_outbound'] ) ); ?> class="sp-checkbox-md" />
					<span><?php esc_html_e( 'Fire outbound_link_click GA4 events', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</label>
				<p class="description sp-desc sp-desc--slate sp-mt-6">
					<?php esc_html_e( 'Automatically records external links leaving your domain.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Privacy & Consent', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<label class="sp-inline-checkbox-label">
					<input type="checkbox" name="sm_pulse_analytics_affiliate_link_settings[respect_consent]" value="1" <?php checked( ! empty( $sm_pulse_analytics_al_settings['respect_consent'] ) ); ?> class="sp-checkbox-md" />
					<span><?php esc_html_e( 'Respect User Privacy & Cookie Consent', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				</label>
				<p class="description sp-desc sp-desc--slate sp-mt-6">
					<?php esc_html_e( 'Wait for user privacy / EU compliance consent before recording link telemetry.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-affiliate-prefixes"><?php esc_html_e( 'Affiliate URL Prefixes', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-affiliate-prefixes" name="sm_pulse_analytics_affiliate_link_settings[affiliate_prefixes]" rows="3"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_al_settings['affiliate_prefixes'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'One prefix per line, e.g. /go/, /out/, /recommend/, /recommends/', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-affiliate-domains"><?php esc_html_e( 'Affiliate Domains', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-affiliate-domains" name="sm_pulse_analytics_affiliate_link_settings[affiliate_domains]" rows="3"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_al_settings['affiliate_domains'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'Specific outbound domains treated strictly as affiliate links (one per line).', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-partner-map"><?php esc_html_e( 'Partner Name Mappings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-partner-map" name="sm_pulse_analytics_affiliate_link_settings[partner_map]" rows="3"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_partner_lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'Format one per line: domain|Partner Name (e.g. amazon.com|Amazon Associates)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-excluded-domains"><?php esc_html_e( 'Excluded Domains', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-excluded-domains" name="sm_pulse_analytics_affiliate_link_settings[excluded_domains]" rows="3"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_al_settings['excluded_domains'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'External domains excluded from link tracking (e.g. facebook.com, twitter.com, linkedin.com).', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-excluded-prefixes"><?php esc_html_e( 'Excluded URL Prefixes', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-excluded-prefixes" name="sm_pulse_analytics_affiliate_link_settings[excluded_prefixes]" rows="2"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_al_settings['excluded_prefixes'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'Internal or external URL prefixes that should be ignored during link click tracking.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="sp-al-ignored-selectors"><?php esc_html_e( 'Ignored CSS Selectors', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></label></th>
			<td>
				<textarea class="sp-textarea-mono" id="sp-al-ignored-selectors" name="sm_pulse_analytics_affiliate_link_settings[ignored_selectors]" rows="2"><?php echo sm_pulse_analytics_al_lines( $sm_pulse_analytics_al_settings['ignored_selectors'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
				<p class="description sp-desc sp-desc--slate sp-desc-sm sp-mt-6"><?php esc_html_e( 'CSS classes or selectors for links that should be excluded (e.g. .ignore-pulse-tracking).', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
			</td>
		</tr>
	</table>

	<div class="sp-form-actions">
		<button type="submit" name="sm_pulse_analytics_save_affiliate_settings" value="1" class="btn-premium">
			<?php esc_html_e( 'Save Affiliate Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</button>
	</div>
</div>
