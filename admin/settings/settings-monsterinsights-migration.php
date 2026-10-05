<?php
/**
 * MonsterInsights migration settings tab (#51).
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

use Sm_Pulse_Analytics\PulseAnalytics_MonsterInsights_Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_pulse_analytics_scan        = PulseAnalytics_MonsterInsights_Migration::scan_available();
$sm_pulse_analytics_status      = PulseAnalytics_MonsterInsights_Migration::get_status();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sm_pulse_analytics_just_done   = isset( $_GET['migration-done'] ) && '1' === $_GET['migration-done'];
$sm_pulse_analytics_aggregate_n = count( $sm_pulse_analytics_scan['aggregate_options'] );
?>

<div class="settings-card sp-migration-card">
	<h2 class="sp-mt-0"><?php esc_html_e( 'Import from MonsterInsights', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Migrate compatible MonsterInsights settings and locally stored aggregated report data into Pulse Analytics. Live GA4 history remains in your Google Analytics property — this imports WordPress-side configuration and cached MonsterInsights summaries.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>

	<?php if ( $sm_pulse_analytics_just_done ) : ?>
		<div class="notice notice-success inline sp-mb-16">
			<p><?php esc_html_e( 'Migration finished. Review the summary below and verify Analytics Configuration.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
		</div>
	<?php endif; ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'MonsterInsights plugin', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<?php if ( ! empty( $sm_pulse_analytics_scan['plugin_active'] ) ) : ?>
					<span class="sp-text-active"><?php esc_html_e( 'Active', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				<?php else : ?>
					<span class="sp-text-warning-bold"><?php esc_html_e( 'Not active (legacy data detected)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $sm_pulse_analytics_scan['mi_version'] ) ) : ?>
					<p class="description"><?php echo esc_html( sprintf( /* translators: %s: version */ __( 'Detected version: %s', 'smackcoders-pulse-analytics-for-woocommerce' ), $sm_pulse_analytics_scan['mi_version'] ) ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<?php if ( ! empty( $sm_pulse_analytics_scan['settings_found'] ) ) : ?>
					<?php esc_html_e( 'MonsterInsights settings option found.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'No monsterinsights_settings option found.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Aggregated data', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
			<td>
				<?php if ( $sm_pulse_analytics_aggregate_n > 0 ) : ?>
					<?php
					$sm_pulse_analytics_is_already_imported = ( ! empty( $sm_pulse_analytics_status['source_hash'] ) && $sm_pulse_analytics_status['source_hash'] === PulseAnalytics_MonsterInsights_Migration::build_source_hash( $sm_pulse_analytics_scan ) );
					if ( $sm_pulse_analytics_is_already_imported ) {
						echo esc_html(
							sprintf(
								/* translators: %d: number of options */
								_n( '%d local report/cache option available (already imported).', '%d local report/cache options available (already imported).', $sm_pulse_analytics_aggregate_n, 'smackcoders-pulse-analytics-for-woocommerce' ),
								$sm_pulse_analytics_aggregate_n
							)
						);
					} else {
						echo esc_html(
							sprintf(
								/* translators: %d: number of options */
								_n( '%d local report/cache option ready to import.', '%d local report/cache options ready to import.', $sm_pulse_analytics_aggregate_n, 'smackcoders-pulse-analytics-for-woocommerce' ),
								$sm_pulse_analytics_aggregate_n
							)
						);
					}
					?>
					<ul class="sp-scroll-list">
						<?php foreach ( array_keys( $sm_pulse_analytics_scan['aggregate_options'] ) as $sm_pulse_analytics_option_name ) : ?>
							<li><code><?php echo esc_html( $sm_pulse_analytics_option_name ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php esc_html_e( 'No MonsterInsights aggregated report options found in the database.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<div class="notice notice-info inline sp-my-16">
		<p><?php esc_html_e( 'Pulse Analytics will not overwrite existing GA4 credentials or link paths. Empty targets are filled from MonsterInsights. Aggregated imports are stored in Pulse Analytics snapshots and skipped on repeat runs unless you choose re-import.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
	</div>

	<p>
		<label>
			<input type="checkbox" name="sm_pulse_analytics_mi_migration_force" value="1" />
			<?php esc_html_e( 'Re-import aggregated data (overwrite previously imported MonsterInsights snapshots)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</label>
	</p>

	<p class="submit sp-mt-8">
		<button type="submit" class="btn-premium" name="sm_pulse_analytics_mi_migration_run" value="1">
			<?php esc_html_e( 'Start Migration', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
		</button>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' ) ); ?>"><?php esc_html_e( 'Review Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></a>
	</p>

	<?php if ( ! empty( $sm_pulse_analytics_status['completed_at'] ) ) : ?>
		<hr class="sp-hr" />
		<h3 class="sp-subheading"><?php esc_html_e( 'Last migration summary', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: datetime */
					__( 'Completed: %s', 'smackcoders-pulse-analytics-for-woocommerce' ),
					$sm_pulse_analytics_status['completed_at']
				)
			);
			?>
		</p>

		<?php if ( ! empty( $sm_pulse_analytics_status['migrated_settings'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Migrated settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong></p>
			<ul class="sp-list-indent">
				<?php foreach ( (array) $sm_pulse_analytics_status['migrated_settings'] as $sm_pulse_analytics_item ) : ?>
					<li><code><?php echo esc_html( (string) $sm_pulse_analytics_item ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $sm_pulse_analytics_status['migrated_data'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Imported aggregated data', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong></p>
			<ul class="sp-scroll-list sp-scroll-list--short">
				<?php foreach ( (array) $sm_pulse_analytics_status['migrated_data'] as $sm_pulse_analytics_item ) : ?>
					<li><code><?php echo esc_html( (string) $sm_pulse_analytics_item ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $sm_pulse_analytics_status['skipped_settings'] ) || ! empty( $sm_pulse_analytics_status['skipped_data'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Skipped items', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong></p>
			<ul class="sp-list-indent sp-list-muted">
				<?php foreach ( (array) ( $sm_pulse_analytics_status['skipped_settings'] ?? array() ) as $sm_pulse_analytics_key => $sm_pulse_analytics_reason ) : ?>
					<li><code><?php echo esc_html( (string) $sm_pulse_analytics_key ); ?></code> — <?php echo esc_html( (string) $sm_pulse_analytics_reason ); ?></li>
				<?php endforeach; ?>
				<?php foreach ( (array) ( $sm_pulse_analytics_status['skipped_data'] ?? array() ) as $sm_pulse_analytics_key => $sm_pulse_analytics_reason ) : ?>
					<li><code><?php echo esc_html( (string) $sm_pulse_analytics_key ); ?></code> — <?php echo esc_html( (string) $sm_pulse_analytics_reason ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $sm_pulse_analytics_status['warnings'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Warnings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong></p>
			<ul class="sp-list-indent sp-list-warning">
				<?php foreach ( (array) $sm_pulse_analytics_status['warnings'] as $sm_pulse_analytics_warning ) : ?>
					<li><?php echo esc_html( (string) $sm_pulse_analytics_warning ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
</div>
