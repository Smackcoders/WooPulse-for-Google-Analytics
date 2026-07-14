<?php
/**
 * Admin UI class for StorePulse analytics plugin settings.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_UI {

	const SETTINGS_OPTION_NAME = 'storepulse_settings';
	const AUTH_OPTION_NAME     = 'StorePulse_auth';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_StorePulse_clear_all_settings', array( __CLASS__, 'handle_clear_all_settings' ) );
	}

	public static function add_admin_menu() {
		// Menu is handled by GA_Connector in connector.php.
	}

	public static function enqueue_assets( $hook ) {
		// Redundant enqueues removed. Admin assets are now centrally handled in includes/connector.php.
		/*
		$allowed_hooks = [
			'toplevel_page_wp-seo-insights',
			'woo-pulse_page_wp-seo-insights',
		];
		$page = $_GET['page'] ?? '';
		$is_settings_page = in_array($page, ['wp-seo-insights', 'ga-settings', 'StorePulse_goals', 'StorePulse-logs', 'StorePulse-campaigns']);

		if (!in_array($hook, $allowed_hooks) && !$is_settings_page) {
			return;
		}

		wp_enqueue_style('wp-seo-insights-css', GA_PLUGIN_URL . 'assets/css/styles.css', [], '1.2.1');
		wp_enqueue_script('wp-seo-insights-js', GA_PLUGIN_URL . 'assets/js/script.js', ['jquery'], '1.2.1', true);
		wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
		*/
	}

	public static function render_page() {
		// Define wizard steps.
		$steps = array(
			'help'          => '1. Help & Guidance',
			'general'       => '2. General Settings',
			'analytics'     => '3. Analytics Configuration',
			'events'        => '4. Events & Goals',
			'advanced'      => '5. Advanced Settings',
			'authenticated' => '6. Authorized Connection',
		);

		// Determine current step.
		$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'help';
		if ( ! array_key_exists( $current_step, $steps ) ) {
			$current_step = 'help'; // Default to help if invalid step.
		}

		// Get options for rendering.
		$options      = get_option( self::SETTINGS_OPTION_NAME, array() );
		$auth_options = get_option( self::AUTH_OPTION_NAME, array() );

		// Handle Form Submissions.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['storepulse_settings'] ) && check_admin_referer( 'save_storepulse_settings' ) ) {
			$raw_submitted  = wp_unslash( $_POST['storepulse_settings'] );
			$clean_settings = get_option( self::SETTINGS_OPTION_NAME, array() );
			$old_settings   = $clean_settings; // For audit logging.
			$error_message  = '';

			foreach ( $raw_submitted as $key => $val ) {
				$old_value = $old_settings[ $key ] ?? '';
				
				if ( 'client_secret' === $key ) {
					// Use a safer sanitizer that doesn't strip valid secret characters.
					$new_value = trim( wp_strip_all_tags( (string) $val ) );
				} else {
					$new_value = sanitize_text_field( $val );
				}

				// Audit Logging.
				if ( function_exists( 'StorePulse_log_audit' ) && $old_value !== $new_value && ! empty( $new_value ) ) {
					StorePulse_log_audit(
						'Settings Updated',
						array(
							'setting'   => $key,
							'old_value' => $old_value,
							'new_value' => $new_value,
						)
					);
				}
				$clean_settings[ $key ] = $new_value;
			}

			// Specific Handling based on current step.
			if ( 'analytics' === $current_step ) {
				$clean_settings['enable_realtime'] = isset( $raw_submitted['enable_realtime'] ) ? '1' : '0';
				
				if ( isset( $_POST['storepulse_ga4_measurement_id'] ) ) {
					$meas_id = sanitize_text_field( wp_unslash( $_POST['storepulse_ga4_measurement_id'] ) );
					if ( empty( $meas_id ) || preg_match( '/^G-[A-Za-z0-9]+$/i', $meas_id ) ) {
						update_option( 'storepulse_ga4_measurement_id', strtoupper( $meas_id ) );
					} else {
						$error_message = 'Invalid GA4 Measurement ID — it must start with G- followed by alphanumeric characters.';
					}
				}
				if ( isset( $_POST['storepulse_psi_api_key'] ) ) {
					$psi_key = sanitize_text_field( wp_unslash( $_POST['storepulse_psi_api_key'] ) );
					update_option( 'storepulse_psi_api_key', $psi_key );
				}
				if ( isset( $_POST['storepulse_psi_override_url'] ) ) {
					$override_url = esc_url_raw( wp_unslash( $_POST['storepulse_psi_override_url'] ) );
					update_option( 'storepulse_psi_override_url', $override_url );
				}
			} elseif ( 'events' === $current_step ) {
				$clean_settings['enable_goals'] = isset( $raw_submitted['enable_goals'] ) ? '1' : '0';
			} elseif ( 'advanced' === $current_step ) {
				$clean_settings['debug_logging']    = isset( $raw_submitted['debug_logging'] ) ? '1' : '0';
				$clean_settings['ip_anonymization'] = isset( $raw_submitted['ip_anonymization'] ) ? '1' : '0';
				$clean_settings['demo_mode']        = isset( $raw_submitted['demo_mode'] ) ? '1' : '0';
			}

			update_option( self::SETTINGS_OPTION_NAME, $clean_settings );

			if ( ! empty( $error_message ) ) {
				set_transient( 'storepulse_settings_error', $error_message, 45 );
				$page_val = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'wp-seo-insights';
				wp_safe_redirect( admin_url( 'admin.php?page=' . $page_val . '&step=' . $current_step ) );
				exit;
			}

			if ( isset( $_POST['save_and_connect'] ) ) {
				$client_id = $clean_settings['client_id'] ?? '';
				if ( ! empty( $client_id ) ) {
					$auth_url = GA_Auth::get_auth_url( $client_id );
					// wp_redirect (not wp_safe_redirect) is required here because
					// the OAuth URL is an external domain (accounts.google.com).
					wp_redirect( esc_url_raw( $auth_url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}
			} else {
				echo '<div class="notice notice-success is-dismissible"><p><strong>Settings Saved Successfully!</strong></p></div>';
			}
			$options = $clean_settings; // Update options for current render.
		}

		if ( isset( $_GET['cleared'] ) && '1' == $_GET['cleared'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>All settings and tokens have been cleared and reset to defaults.</strong></p></div>';
		}

		$transient_error = get_transient( 'storepulse_settings_error' );
		if ( false !== $transient_error ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> ' . esc_html( $transient_error ) . '</p></div>';
			delete_transient( 'storepulse_settings_error' );
		}

		// Global Pulse Header.
		StorePulse_render_admin_header();
		?>
		<div class="wrap" id="StorePulse-settings-v2" style="margin-top: 24px; max-width: 1000px; margin-left: auto; margin-right: auto; padding-bottom: 50px;">
			<style>
				#StorePulse-settings-v2, #StorePulse-settings-v2 *, 
				#StorePulse-settings-v2 h2, #StorePulse-settings-v2 h3, 
				#StorePulse-settings-v2 p, #StorePulse-settings-v2 span, 
				#StorePulse-settings-v2 a, #StorePulse-settings-v2 button, 
				#StorePulse-settings-v2 input { 
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important; 
				}
				
				#StorePulse-settings-v2 {
					max-width: 1000px !important;
					margin: 15px auto !important;
					padding: 0 20px;
				}
				.settings-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 24px; padding: 32px; }
				.settings-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
				.settings-header h2 { font-size: 18px; font-weight: 600; color: #333; margin: 0; line-height: 1.2; }
				.settings-description { color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 24px; }
				
				.icon-circle {
					width: 44px;
					height: 44px;
					background: #f5f3ff;
					border-radius: 12px;
					display: flex;
					align-items: center;
					justify-content: center;
					flex-shrink: 0;
				}
				
				.step-item { position: relative; padding-left: 64px; margin-bottom: 32px; }
				.step-item:last-child { margin-bottom: 0; }
				.step-item::before { content: ''; position: absolute; left: 19px; top: 40px; bottom: -16px; width: 2px; background: #f1f5f9; }
				.step-item:last-child::before { display: none; }
				.step-number { position: absolute; left: 0; top: 0; width: 40px; height: 40px; background: #3d4fdb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; z-index: 2; box-shadow: 0 0 0 6px white; }
				.step-title { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 8px; }
				.step-desc { font-size: 13px; color: #666; line-height: 1.5; }
				.btn-premium { background: #3d4fdb; color: white !important; border-radius: 8px; padding: 12px 24px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
				.btn-premium:hover { background: #313ea5; transform: translateY(-1px); }
				.btn-outline { background: white; color: #3d4fdb !important; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 24px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
				.btn-outline:hover { background: #f8fafc; border-color: #3d4fdb; }
				.form-table th { font-weight: 600; font-size: 13px; color: #1d2327; width: 240px; vertical-align: top; padding-top: 20px; }
				.form-table tr { border-bottom: 1px solid #f1f5f9; }
				.form-table tr:last-child { border-bottom: none; }
				.form-table td { padding-top: 20px; padding-bottom: 20px; font-size: 13px; color: #1d2327; }
				.regular-text, .large-text { border: 1px solid #c3c4c7; border-radius: 4px; padding: 8px 12px; font-size: 13px; background: #ffffff; transition: all 0.2s; color: #1d2327; }
				.regular-text:focus, .large-text:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
				.wptip .dashicons { color: #72aee6; font-size: 16px; margin-left: 4px; }

				/* Responsive: premium stacked layout on tablet/mobile widths, matching Audie Data Migrator's breakpoints. */
				.w2ssyn-subtabs-container { flex-wrap: nowrap !important; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin; }
				.w2ssyn-subtabs-container .w2ssyn-subtab { white-space: nowrap; flex-shrink: 0; }

				@media (max-width: 782px) {
					#StorePulse-settings-v2 { padding: 0 12px; }
					.settings-card { padding: 20px; border-radius: 12px; margin-bottom: 16px; }
					.settings-header { gap: 12px; margin-bottom: 16px; }
					.settings-header h2 { font-size: 16px; }
					.icon-circle { width: 36px; height: 36px; }
					.settings-description { margin-bottom: 16px; }

					.form-table th { width: auto; display: block; padding: 16px 0 4px; }
					.form-table td { display: block; padding: 0 0 16px; }
					.regular-text, .large-text { width: 100% !important; max-width: 100% !important; box-sizing: border-box; }

					.step-item { padding-left: 52px; margin-bottom: 24px; }
					.step-number { width: 32px; height: 32px; font-size: 13px; }
					.step-item::before { left: 15px; }

					.mt-12.flex.items-center.gap-4 { flex-direction: column; align-items: stretch; gap: 12px; }
					.btn-premium, .btn-outline { width: 100%; justify-content: center; box-sizing: border-box; }
				}
			</style>

			<div class="StorePulse-wizard-main">

				<?php if ( 'authenticated' !== $current_step ) : ?>
					<form method="POST" action="">
						<?php wp_nonce_field( 'save_storepulse_settings' ); ?>
						<input type="hidden" name="storepulse_settings[current_step]" value="<?php echo esc_attr( $current_step ); ?>">
				<?php endif; ?>

				<?php
				// HELP & GUIDANCE.
				if ( 'help' === $current_step ) :
					?>
					<!-- Welcome Card -->
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
							</div>
							<h2>Welcome to Pulse Analytics</h2>
						</div>
						<p class="settings-description">
							Pulse Analytics is your all-in-one WordPress analytics companion. It helps you understand your website traffic, track user behavior, monitor eCommerce performance, and make data-driven decisions — all from within your WordPress dashboard. This guide will walk you through the initial setup so you can start collecting meaningful insights right away.
						</p>
					</div>

					<!-- Setup Guide -->
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4 4 4"></path><path d="m16 10-4 4-4-4"></path><path d="m8 14 4-4 4 4"></path></svg>
							</div>
							<h2>Getting Started Guide</h2>
						</div>
						<div class="steps-container">
							<div class="step-item">
								<div class="step-number">1</div>
								<h3 class="step-title">Check General Settings</h3>
								<p class="step-desc">Navigate to the General Settings tab and review your basic configuration. Make sure your site URL is correct, your timezone is set properly, and user roles with access to analytics are configured. These foundational settings ensure accurate data collection from the start.</p>
							</div>
							<div class="step-item">
								<div class="step-number">2</div>
								<h3 class="step-title">Connect Analytics</h3>
								<p class="step-desc">Go to the Analytics Configuration tab and connect your analytics provider. You can authorize your Google Analytics account or use the built-in Pulse tracking. Follow the on-screen instructions to complete the authorization flow and verify data is being received.</p>
							</div>
							<div class="step-item">
								<div class="step-number">3</div>
								<h3 class="step-title">Enable Events</h3>
								<p class="step-desc">Visit the Events & Goals tab to enable event tracking. Turn on automatic tracking for clicks, form submissions, scroll depth, and file downloads. You can also set up custom goals to measure conversions and key user actions specific to your website.</p>
							</div>
							<div class="step-item">
								<div class="step-number">4</div>
								<h3 class="step-title">Verify Connection</h3>
								<p class="step-desc">Head to the Real-time tab to verify that your connection is working. You should see live visitor data within a few minutes. If data isn't appearing, check the Authorized Connection tab to ensure your credentials are valid and the tracking code is properly installed.</p>
							</div>
						</div>
					</div>

					<!-- Support Resources -->
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
							</div>
							<h2>Support Resources</h2>
						</div>
						<p class="settings-description">Need more help? We've got you covered. Browse our full documentation for detailed guides, reach out to our support team for personalized assistance, or jump straight into the General Settings to begin configuring your plugin.</p>
						<div class="flex flex-wrap gap-4 mt-8">
							<a href="#" class="btn-premium">Read Full Documentation</a>
							<a href="#" class="btn-outline">Contact Support Team</a>
							<a href="?page=wp-seo-insights&step=general" class="btn-outline">Move to General Settings &rarr;</a>
						</div>
					</div>

					<?php
					// GENERAL TAB.
				elseif ( 'general' === $current_step ) :
					?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
							</div>
							<h2>Environment Status & Basic Settings</h2>
						</div>
						<p class="settings-description" style="margin-bottom: 40px;">
							Pulse Analytics automatically detects your store's environment to ensure data accuracy. It is highly recommended that these values match your Google Analytics 4 property settings exactly. Discrepancies in timezone or currency can lead to mismatched revenue reports and incorrect daily traffic peaks.
						</p>
						
						<table class="form-table">
							<tr>
								<th>Store Timezone</th>
								<td>
									<input type="text" class="regular-text" value="
									<?php
										$offset  = get_option( 'gmt_offset' );
										$hours   = (int) $offset;
										$minutes = abs( $offset - $hours ) * 60;
										echo esc_html( sprintf( 'UTC %+03d:%02d', $hours, $minutes ) );
									?>
									" readonly />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										Your store is currently operating in this timezone. Pulse Analytics uses this to sync your sales data with Google Analytics. Please verify that your GA4 property is also set to this same timezone in the Google Analytics admin panel.
									</p>
								</td>
							</tr>
							<tr>
								<th>Store Currency</th>
								<td>
									<input type="text" class="regular-text" value="<?php echo esc_attr( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'INR' ); ?>" readonly />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										This is the primary currency detected from your WooCommerce settings. All revenue metrics on your dashboard (Total Revenue, AOV) will be displayed in this currency.
									</p>
								</td>
							</tr>
						</table>
						<div class="mt-12 flex items-center gap-4">
							<button type="submit" class="btn-premium">Save Basic Settings</button>
							<a href="?page=wp-seo-insights&step=analytics" class="btn-outline">Next: Analytics Configuration &rarr;</a>
						</div>
					</div>

					<?php
					// ANALYTICS TAB.
				elseif ( 'analytics' === $current_step ) :
					$measurement_id = get_option( 'storepulse_ga4_measurement_id', '' );
					if ( empty( $measurement_id ) ) :
						?>
						<div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
							<p style="margin: 0; font-size: 14px; color: #b45309;">
								<strong style="font-weight: 700; color: #92400e;">Measurement ID Missing:</strong> Goals and Links event tracking to GA4 requires a GA4 Measurement ID to be configured below.
							</p>
						</div>
					<?php endif; ?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
							</div>
							<h2>Google Analytics Connection</h2>
						</div>
						<p class="settings-description" style="margin-bottom: 40px;">
							To securely fetch data from your Google Analytics 4 property, you need to create an OAuth 2.0 bridge. This requires a <strong>Google Client ID</strong> and <strong>Secret</strong> from the 
							<a href="https://console.cloud.google.com/" target="_blank" style="color: #6366f1; text-decoration: underline;">Google Cloud Console</a>. This ensures that your tracking data remains private and only accessible by your authorized administrator.
						</p>
						
						<table class="form-table">
							<tr>
								<th>Real-time Tracking</th>
								<td>
									<label class="switch-toggle" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
										<input type="checkbox" name="storepulse_settings[enable_realtime]" value="1" <?php checked( isset( $options['enable_realtime'] ) && '1' === $options['enable_realtime'] ); ?> style="width: 18px; height: 18px; margin: 0; cursor: pointer;" />
										<span style="font-weight: 500; color: #1e293b;">Enable real-time active visitors tracking</span>
									</label>
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										When enabled, Pulse Analytics will fetch active visitors from your GA4 property to display on the dashboard.
									</p>
								</td>
							</tr>
							<tr>
								<th>Google Client ID <span class="wptip" data-tip="Look for 'OAuth 2.0 Client IDs' in your Google Cloud project.">
									<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; cursor:help;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
								</span></th>
								<td>
									<input type="text" name="storepulse_settings[client_id]" class="large-text" value="<?php echo esc_attr( $options['client_id'] ?? '' ); ?>" style="width: 100%; max-width: 500px;" placeholder="e.g. 1234567-abcdef.apps.googleusercontent.com" />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										This is your application's public identity. Think of it as a username that tells Google which app is trying to connect. You can find this under <strong>APIs & Services > Credentials</strong> in your Google Cloud project.
									</p>
									<div class="mt-3 p-3 bg-indigo-50 border border-indigo-100 rounded text-sm text-indigo-900 leading-relaxed" style="max-width: 500px;">
										Ensure you have added exactly <code><?php echo esc_url( admin_url( 'admin.php?page=wp-seo-insights' ) ); ?></code> to the <strong>Authorized redirect URIs</strong> in your Google Cloud Console.
									</div>
								</td>
							</tr>
							<tr>
								<th>Google Client Secret</th>
								<td>
									<div style="position: relative; max-width: 500px;">
										<input type="password" id="ga_client_secret" name="storepulse_settings[client_secret]" value="<?php echo esc_attr( $options['client_secret'] ?? '' ); ?>" style="width: 100%;" placeholder="Enter your private client secret" />
										<button type="button" onclick="toggleSecretVisibility('ga_client_secret')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
											<svg id="eye_icon_ga_client_secret" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
										</button>
									</div>
									<script>
										function toggleSecretVisibility(id) {
											const input = document.getElementById(id);
											const icon = document.getElementById('eye_icon_' + id);
											if (input.type === 'password') {
												input.type = 'text';
												icon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" y1="2" x2="22" y2="22"></line>';
											} else {
												input.type = 'password';
												icon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>';
											}
										}
									</script>
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										<strong>Handle with care!</strong> This is your application's private password. It allows Pulse Analytics to securely authenticate with Google's servers. Never share this secret with unauthorized people.
									</p>
								</td>
							</tr>
							<tr>
								<th>GA4 Property ID</th>
								<td>
									<input type="text" name="storepulse_settings[property_id]" class="regular-text" value="<?php echo esc_attr( $options['property_id'] ?? '' ); ?>" placeholder="e.g. 987654321" />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										The <strong>numeric ID</strong> of your specific GA4 property. You can find this in your Google Analytics dashboard under <strong>Admin > Property Settings > Property Details</strong>. Note: This is different from your Measurement ID (which starts with G-).
									</p>
								</td>
							</tr>
							<tr>
								<th>GA4 Measurement ID</th>
								<td>
									<input type="text" name="storepulse_ga4_measurement_id" class="regular-text" value="<?php echo esc_attr( $measurement_id ); ?>" placeholder="e.g. G-ABC1234567" pattern="^G-[A-Za-z0-9]+$" title="Must start with G- followed by alphanumeric characters." />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										Your GA4 Measurement ID (starts with G-). This is injected into your site's frontend for Goals and Links tracking.
									</p>
								</td>
							</tr>
							<tr>
								<th>PageSpeed Insights API Key</th>
								<td>
									<div style="position: relative; max-width: 500px;">
										<input type="password" id="psi_api_key" name="storepulse_psi_api_key" value="<?php echo esc_attr( get_option( 'storepulse_psi_api_key', '' ) ); ?>" style="width: 100%;" placeholder="Enter your PageSpeed API key" />
										<button type="button" onclick="toggleSecretVisibility('psi_api_key')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
											<svg id="eye_icon_psi_api_key" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
										</button>
									</div>
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										Your Google PageSpeed Insights API Key. Adding this prevents rate-limiting issues when checking Core Web Vitals. <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Learn how to generate one here</a>.
									</p>
								</td>
							</tr>
							<tr>
								<th>Public Site URL Override</th>
								<td>
									<input type="text" name="storepulse_psi_override_url" class="regular-text" value="<?php echo esc_attr( get_option( 'storepulse_psi_override_url', '' ) ); ?>" placeholder="e.g. https://yourdomain.com" style="width: 100%; max-width: 500px;" />
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										<strong>Only needed for local/staging environments.</strong> If your WordPress is running on <code>localhost</code> but your live site is on a public domain, enter the public URL here. Core Web Vitals and PageSpeed Insights will use this URL instead.
									</p>
								</td>
							</tr>
						</table>
						<div class="mt-12 flex items-center gap-4">
							<button type="submit" name="save_and_connect" class="btn-premium">Save & Authorize Google Connection</button>
							<a href="?page=wp-seo-insights&step=events" class="btn-outline">Go to Events & Goals &rarr;</a>
						</div>
					</div>

					<?php
					// EVENTS TAB.
				elseif ( 'events' === $current_step ) :
					?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
							</div>
							<h2>Ecommerce Tracking & Goals</h2>
						</div>
						<p class="settings-description" style="margin-bottom: 40px;">
							Configure exactly how your customer interactions are tracked. Enabling Enhanced Ecommerce allows Pulse Analytics to send mission-critical data like orders, product views, and cart additions directly to the Google Analytics Measurement Protocol.
						</p>
						
						<div class="bg-indigo-50/50 p-8 rounded-xl border border-indigo-100/50 mb-8">
							<label class="flex items-center gap-3 cursor-pointer">
								<input type="checkbox" name="storepulse_settings[enable_goals]" value="1" <?php checked( '1', $options['enable_goals'] ?? '0' ); ?> <?php echo ! class_exists( 'WooCommerce' ) ? 'disabled' : ''; ?> class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
								<span class="text-base font-bold text-gray-900">Enable Enhanced Ecommerce Tracking</span>
							</label>
							<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
								<div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded text-sm font-medium">
									⚠️ WooCommerce must be installed and activated to use Enhanced Ecommerce Tracking.
								</div>
							<?php endif; ?>
							<p class="text-sm text-gray-600 mt-4 leading-relaxed">
								When enabled, the following events will be automatically tracked without any extra setup:
								<ul class="mt-4 space-y-2 text-sm text-gray-500" style="list-style: disc; padding-left: 20px;">
									<li><strong>Purchase Events:</strong> Sends order total, tax, shipping, and products when a customer finishes checkout.</li>
									<li><strong>Add to Cart:</strong> Tracks which products are being added to the shopping basket.</li>
									<li><strong>Begin Checkout:</strong> Identifies when a customer starts the payment process.</li>
									<li><strong>Product Views:</strong> Measures interest in individual products on your store.</li>
								</ul>
							</p>
						</div>

						<div class="mt-12 flex items-center gap-4">
							<button type="submit" class="btn-premium">Save Tracking Settings</button>
							<a href="?page=wp-seo-insights&step=advanced" class="btn-outline">Advanced Settings &rarr;</a>
						</div>
					</div>

					<?php
					// ADVANCED TAB.
				elseif ( 'advanced' === $current_step ) :
					?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
							</div>
							<h2>Advanced System Configuration</h2>
						</div>
						<p class="settings-description">
							Fine-tune the technical behavior of the plugin. These settings are typically used for privacy compliance and troubleshooting purposes.
						</p>
						
						<table class="form-table">
							<tr>
								<th>Privacy & GDPR Compliance</th>
								<td>
									<!-- CONSUMED IN: includes/connector.php (inject_gtag_snippet) -->
									<label class="flex items-center gap-2">
										<input type="checkbox" name="storepulse_settings[ip_anonymization]" value="1" <?php checked( '1', $options['ip_anonymization'] ?? '0' ); ?>>
										<strong>Anonymize IP addresses</strong>
									</label>
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										If enabled, the last part of your visitors' IP addresses will be removed before the data is sent to Google. This is a recommended step to comply with GDPR and other strict data protection laws.
									</p>
								</td>
							</tr>
							<tr>
								<th>Testing & Demo Mode</th>
								<td>
									<label class="flex items-center gap-2">
										<input type="checkbox" name="storepulse_settings[demo_mode]" value="1" <?php checked( '1', $options['demo_mode'] ?? '0' ); ?>>
										<strong>Enable Sample Data</strong>
									</label>
									<p class="description" style="margin-top: 10px; color: #666; font-size: 13px;">
										If your Google Analytics property is brand new and has no data yet, enable this to see sample visualizations in the Demographic reports. Turn this off once your real traffic starts appearing.
									</p>
								</td>
							</tr>
						</table>
						<div class="mt-12 flex items-center gap-4">
							<button type="submit" class="btn-premium">Update Advanced Settings</button>
						</div>
					</div>

					<!-- Danger Zone (Red Box) -->
					<div class="settings-card" style="border-left: 4px solid #ef4444; background-color: #fef2f2;">
						<div class="settings-header">
							<svg class="text-red-500" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
							<h2 style="color: #ef4444; font-size: 20px; font-weight: 600;">Danger Zone</h2>
						</div>
						<p class="settings-description" style="color: #b91c1c; font-weight: 500;">
							Resetting the plugin will erase all saved settings, authentication tokens, and valid configurations. This action is irreversible.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('WARNING: Are you sure you want to reset all data?');">
							<?php wp_nonce_field( 'StorePulse_clear_all_settings', '_wpnonce' ); ?>
							<input type="hidden" name="action" value="StorePulse_clear_all_settings">
							<button type="submit" class="btn-premium" style="background-color: #ef4444 !important;">
								<svg class="mr-1 inline-block" style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Reset All Plugin Data
							</button>
						</form>
					</div>
				<?php endif; ?>

				<?php
				if ( 'advanced' !== $current_step && 'authenticated' !== $current_step ) {
					echo '</form>';}
				?>

				<?php
				// AUTHENTICATED TAB.
				if ( 'authenticated' === $current_step ) :
					$is_authenticated  = ! empty( $auth_options['access_token'] );
					$expiry            = (int) ( $auth_options['token_expiry'] ?? 0 );
					$remaining_minutes = max( 0, floor( ( $expiry - time() ) / 60 ) );
					?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
							</div>
							<h2>Authorized Account Status</h2>
						</div>
						
						<div class="p-6 rounded-xl mb-12 flex items-center gap-6 <?php echo $is_authenticated ? 'bg-green-50 border border-green-100 text-green-700' : 'bg-red-50 border border-red-100 text-red-700'; ?>">
							<?php if ( $is_authenticated ) : ?>
								<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
							<?php else : ?>
								<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
							<?php endif; ?>
							<div>
								<h3 class="text-[1rem] font-bold m-0"><?php echo $is_authenticated ? 'Connected & Secure' : 'Not Connected'; ?></h3>
								<p class="text-sm opacity-80"><?php echo $is_authenticated ? 'Your site is successfully authenticated with Google Analytics.' : 'Please configure your connection in the Analytics tab.'; ?></p>
							</div>
						</div>

						<table class="form-table">
							<tr>
								<th>Property ID</th>
								<td><span style="font-weight: 600; color: #1e293b;"><?php echo esc_html( $options['property_id'] ?? 'N/A' ); ?></span></td>
							</tr>
							<tr>
								<th>Token Status</th>
								<td>
									<?php if ( $is_authenticated ) : ?>
										<span class="text-green-600 font-bold">Expires in <?php echo esc_html( $remaining_minutes ); ?>m</span>
									<?php else : ?>
										<span class="text-red-500 italic">Inactive</span>
									<?php endif; ?>
								</td>
							</tr>
						</table>

						<?php if ( $is_authenticated ) : ?>
							<div class="mt-12 flex items-center gap-4">
								<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=refresh_access_token' ) ); ?>" class="btn-premium">Manual Token Refresh</a>
								<button type="button" id="disconnect-analytics-btn" data-url="<?php echo esc_url( admin_url( 'admin-post.php?action=disconnect_google_analytics' ) ); ?>" class="btn-outline" style="border-color: #f87171; color: #ef4444 !important;">Disconnect Account</button>
							</div>
						<?php endif; ?>
					</div>
					<script>
						document.getElementById('disconnect-analytics-btn')?.addEventListener('click', function() {
							if(confirm('Disconnect Google Analytics? This will disable tracking.')) window.location.href = this.getAttribute('data-url');
						});
					</script>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle "Clear All Settings & Tokens" action.
	 * Clears plugin settings, auth tokens
	 * , and optionally custom goals.
	 */
	public static function handle_clear_all_settings() {
		// Check nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'StorePulse_clear_all_settings' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check capability.
		if ( ! current_user_can( 'StorePulse_manage_settings' ) ) {
			wp_die( 'You do not have permission to perform this action' );
		}

		// Clear options.
		delete_option( self::SETTINGS_OPTION_NAME );
		delete_option( self::AUTH_OPTION_NAME );
		delete_option( 'StorePulse_custom_goals' );

		// Redirect with success message.
		wp_safe_redirect( admin_url( 'admin.php?page=wp-seo-insights&step=advanced&cleared=1' ) );
		exit;
	}
}
