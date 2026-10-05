<?php
/**
 * Admin UI class for Pulse Analytics settings.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_UI {

	const SETTINGS_OPTION_NAME = 'sm_pulse_analytics_settings';
	const AUTH_OPTION_NAME     = 'sm_pulse_analytics_auth';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function add_admin_menu() {
		// Menu is handled by GA_Connector in connector.php.
	}

	public static function enqueue_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'pulse-analytics' === $page || 'sm-pulse-analytics-settings' === $page || 0 === stripos( $page, 'smackcoders-pulse-analytics-for-woocommerce' ) ) {
			wp_enqueue_style(
				'pulse-analytics-settings-css',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/settings.css',
				array(),
				defined( 'SM_PULSE_ANALYTICS_VERSION' ) ? SM_PULSE_ANALYTICS_VERSION : '1.0.0'
			);
		}
	}

	public static function render_page() {
		// Define wizard steps.
		$steps = apply_filters(
			'sm_pulse_analytics_settings_steps',
			array(
				'help'                    => __( 'Help & Guidance', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'general'                 => __( 'General Settings', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'analytics'               => __( 'Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'links'                   => __( 'Downloads & Link Paths', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'affiliate-link-tracking' => __( 'Affiliate Link Tracking', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'ecommerce'               => __( 'eCommerce', 'smackcoders-pulse-analytics-for-woocommerce' ),
			)
		);

		// Determine current step.
		$current_step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'help';
		if ( 'authenticated' === $current_step ) {
			$current_step = 'analytics';
		}
		if ( ! array_key_exists( $current_step, $steps ) ) {
			$current_step = 'help'; // Default to help if invalid step.
		}

		// Get options for rendering.
		$options      = get_option( self::SETTINGS_OPTION_NAME, array() );
		$auth_options = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' )
			? PulseAnalytics_GA4_OAuth::get_auth_tokens()
			: get_option( self::AUTH_OPTION_NAME, array() );

		// Handle Form Submissions.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['sm_pulse_analytics_settings'] ) && check_admin_referer( 'save_sm_pulse_analytics_settings' ) ) {
			$allowed_keys = array(
				'current_step',
				'site_mode',
				'currency_code',
				'client_id',
				'property_id',
				'enable_tracking',
				'enable_debug_mode',
				'enable_ecommerce_tracking',
				'enable_woocommerce_tracking',
				'enable_edd_tracking',
				'enable_memberpress_tracking',
				'enable_givewp_tracking',
				'enable_download_tracking',
				'download_file_extensions',
				'affiliate_links',
				'cart_abandon_timeout',
				'telemetry_retention_days',
			);
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_submitted  = (array) wp_unslash( $_POST['sm_pulse_analytics_settings'] );
			$clean_settings = get_option( self::SETTINGS_OPTION_NAME, array() );
			$old_settings   = $clean_settings; // For audit logging.
			$error_message     = '';
			$oauth_creds_saved = false;

			foreach ( $raw_submitted as $key => $val ) {
				$key = sanitize_key( (string) $key );
				if ( ! in_array( $key, $allowed_keys, true ) ) {
					continue;
				}
				// Secrets never land in options — handled below via wp-config snippets.
				if ( in_array( $key, array( 'client_id', 'client_secret' ), true ) ) {
					continue;
				}
				$old_value = $old_settings[ $key ] ?? '';

				if ( is_array( $val ) ) {
					$new_value = array_map(
						function ( $item ) {
							if ( is_array( $item ) ) {
								return array_map( 'sanitize_text_field', $item );
							}
							return sanitize_text_field( $item );
						},
						$val
					);
				} else {
					$new_value = sanitize_text_field( $val );
				}

				// Audit Logging.
				if ( function_exists( 'sm_pulse_analytics_log_audit' ) && $old_value !== $new_value && ! empty( $new_value ) ) {
					sm_pulse_analytics_log_audit(
						'Settings Updated',
						array(
							'setting'   => $key,
							'old_value' => is_scalar( $old_value ) ? $old_value : wp_json_encode( $old_value ),
							'new_value' => is_scalar( $new_value ) ? $new_value : wp_json_encode( $new_value ),
						)
					);
				}
				$clean_settings[ $key ] = $new_value;
			}

			// Strip legacy secret keys from settings option.
			unset( $clean_settings['client_secret'], $clean_settings['client_id'] );

			// Specific Handling based on current step.
			if ( 'analytics' === $current_step ) {
				if ( isset( $_POST['sm_pulse_analytics_ga4_measurement_id'] ) ) {
					$meas_id = sanitize_text_field( wp_unslash( $_POST['sm_pulse_analytics_ga4_measurement_id'] ) );
					if ( empty( $meas_id ) || preg_match( '/^G-[A-Za-z0-9]+$/i', $meas_id ) ) {
						if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) ) {
							if ( '' !== $meas_id && ! \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::save_config( array( 'measurement_id' => strtoupper( $meas_id ) ) ) ) {
								$error_message = __( 'Could not write the GA4 Measurement ID to wp-config.php. Check file permissions.', 'smackcoders-pulse-analytics-for-woocommerce' );
							}
						}
					} else {
						$error_message = __( 'Invalid GA4 Measurement ID — it must start with G- followed by alphanumeric characters.', 'smackcoders-pulse-analytics-for-woocommerce' );
					}
				}

				// Secrets → encrypted wp-config.php only.
				$pending_client_id     = '';
				$pending_client_secret = '';
				if ( ! empty( $raw_submitted['client_id'] ) ) {
					$pending_client_id = trim( wp_strip_all_tags( (string) $raw_submitted['client_id'] ) );
				}
				if ( isset( $_POST['sm_pulse_analytics_settings']['client_secret'] ) || isset( $raw_submitted['client_secret'] ) ) {
					$pending_client_secret = isset( $raw_submitted['client_secret'] )
						? trim( wp_strip_all_tags( (string) $raw_submitted['client_secret'] ) )
						: trim( wp_strip_all_tags( (string) wp_unslash( $_POST['sm_pulse_analytics_settings']['client_secret'] ) ) );
				}
				if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials' )
					&& ( '' !== $pending_client_id || '' !== $pending_client_secret ) ) {
					$sec               = '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials';
					$wp_config_written = true;
					if ( '' !== $pending_client_id ) {
						$wp_config_written = $sec::write_constant( $sec::CONST_GA4_CLIENT_ID, $pending_client_id ) && $wp_config_written;
					}
					if ( '' !== $pending_client_secret ) {
						$wp_config_written = $sec::write_constant( $sec::CONST_GA4_CLIENT_SECRET, $pending_client_secret ) && $wp_config_written;
					}
					if ( $wp_config_written ) {
						delete_option( 'sm_pulse_analytics_ga4_oauth_app_credentials' );
						$oauth_creds_saved = true;
					} else {
						set_transient(
							'sm_pulse_analytics_settings_error',
							__( 'Could not write OAuth credentials to wp-config.php. Make wp-config.php writable, then save again.', 'smackcoders-pulse-analytics-for-woocommerce' ),
							45
						);
					}
				}
				if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials' ) ) {
					$sec = '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials';
					if ( isset( $_POST['sm_pulse_analytics_ga4_api_secret'] ) ) {
						$api_sec = trim( wp_strip_all_tags( (string) wp_unslash( $_POST['sm_pulse_analytics_ga4_api_secret'] ) ) );
						if ( '' !== $api_sec && ! $sec::write_constant( $sec::CONST_GA4_API_SECRET, $api_sec ) ) {
							set_transient(
								'sm_pulse_analytics_settings_error',
								__( 'Could not write the GA4 API Secret to wp-config.php. Check file permissions or add the encrypted define manually.', 'smackcoders-pulse-analytics-for-woocommerce' ),
								45
							);
						}
						delete_option( 'sm_pulse_analytics_ga4_api_secret' );
					}
				}
				$sanitized_post = map_deep( wp_unslash( $_POST ), 'sanitize_text_field' );
				do_action( 'sm_pulse_analytics_analytics_settings_save', 'analytics', $sanitized_post );
			} elseif ( 'ecommerce' === $current_step ) {
				$is_master_enable                               = isset( $raw_submitted['enable_ecommerce_tracking'] ) && '1' === (string) $raw_submitted['enable_ecommerce_tracking'];
				$is_woo_enable                                  = isset( $raw_submitted['enable_woocommerce_tracking'] ) && '1' === (string) $raw_submitted['enable_woocommerce_tracking'];
				$is_edd_enable                                  = isset( $raw_submitted['enable_edd_tracking'] ) && '1' === (string) $raw_submitted['enable_edd_tracking'];
				$is_memberpress_enable                          = isset( $raw_submitted['enable_memberpress_tracking'] ) && '1' === (string) $raw_submitted['enable_memberpress_tracking'];
				$is_givewp_enable                               = isset( $raw_submitted['enable_givewp_tracking'] ) && '1' === (string) $raw_submitted['enable_givewp_tracking'];
				$clean_settings['enable_ecommerce_tracking']   = $is_master_enable ? '1' : '0';
				$clean_settings['enable_woocommerce_tracking'] = $is_woo_enable ? '1' : '0';
				$clean_settings['enable_edd_tracking']         = $is_edd_enable ? '1' : '0';
				$clean_settings['enable_memberpress_tracking'] = $is_memberpress_enable ? '1' : '0';
				$clean_settings['enable_givewp_tracking']      = $is_givewp_enable ? '1' : '0';

				$ecommerce_platforms = array();
				if ( $is_woo_enable && class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) && PulseAnalytics_Site_Profile::is_woocommerce_active() ) {
					$ecommerce_platforms[] = 'woocommerce';
				} elseif ( $is_woo_enable && class_exists( 'WooCommerce' ) ) {
					$ecommerce_platforms[] = 'woocommerce';
				}
				if ( $is_edd_enable && class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) && PulseAnalytics_Site_Profile::is_edd_active() ) {
					$ecommerce_platforms[] = 'edd';
				} elseif ( $is_edd_enable && ( class_exists( 'Easy_Digital_Downloads' ) || defined( 'EDD_VERSION' ) ) ) {
					$ecommerce_platforms[] = 'edd';
				}
				if ( $is_memberpress_enable && class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) && PulseAnalytics_Site_Profile::is_memberpress_active() ) {
					$ecommerce_platforms[] = 'memberpress';
				}
				if ( $is_givewp_enable && class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) && PulseAnalytics_Site_Profile::is_givewp_active() ) {
					$ecommerce_platforms[] = 'givewp';
				}

				update_option(
					'sm_pulse_analytics_ecommerce',
					array(
						'enable'    => $is_master_enable && ! empty( $ecommerce_platforms ),
						'ecommerce' => $ecommerce_platforms,
					)
				);
			} elseif ( 'general' === $current_step ) {
				if ( isset( $raw_submitted['site_mode'] ) ) {
					$mode = sanitize_key( (string) $raw_submitted['site_mode'] );
					if ( in_array( $mode, array( 'auto', 'website', 'store' ), true ) ) {
						$clean_settings['site_mode'] = $mode;
					}
				}
				if ( isset( $raw_submitted['currency_code'] ) ) {
					$clean_settings['currency_code'] = strtoupper( sanitize_text_field( (string) $raw_submitted['currency_code'] ) );
				}
				$clean_settings['enable_tracking']   = isset( $raw_submitted['enable_tracking'] ) ? '1' : '0';
				if ( ! class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) || ! \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::is_debug_mode_locked_by_constant() ) {
					$clean_settings['enable_debug_mode'] = isset( $raw_submitted['enable_debug_mode'] ) ? '1' : '0';
				}
			}

			if ( isset( $_POST['sm_pulse_analytics_psi_override_url'] ) ) {
				$override_url     = esc_url_raw( wp_unslash( $_POST['sm_pulse_analytics_psi_override_url'] ) );
				$old_override_url = get_option( 'sm_pulse_analytics_psi_override_url', '' );
				update_option( 'sm_pulse_analytics_psi_override_url', $override_url );
				if ( function_exists( 'sm_pulse_analytics_log_audit' ) && $old_override_url !== $override_url ) {
					sm_pulse_analytics_log_audit( 'PSI URL Override Updated', array( 'field' => 'sm_pulse_analytics_psi_override_url' ) );
				}
			}

			if ( 'links' === $current_step ) {
				$clean_settings['enable_download_tracking'] = isset( $raw_submitted['enable_download_tracking'] ) ? '1' : '0';
				if ( isset( $raw_submitted['telemetry_retention_days'] ) ) {
					$retention_days = max( 0, (int) $raw_submitted['telemetry_retention_days'] );
					$clean_settings['telemetry_retention_days'] = $retention_days;
					update_option( 'sm_pulse_analytics_telemetry_retention_days', $retention_days );
				}
			}

			if ( 'analytics' === $current_step && ! empty( $clean_settings['property_id'] ) ) {
				$submitted_property_id = trim( (string) $clean_settings['property_id'] );
				if ( ! preg_match( '/^\d{6,15}$/', $submitted_property_id ) ) {
					$error_message = __( 'Invalid GA4 Property ID — enter the numeric ID from Admin > Property Details (for example, 987654321).', 'smackcoders-pulse-analytics-for-woocommerce' );
				} else {
					$clean_settings['property_id'] = $submitted_property_id;
				}
			}

			update_option( self::SETTINGS_OPTION_NAME, $clean_settings );

			if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) ) {
				\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::sync_profile();
			}

			if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) ) {
				\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::save_config(
					array(
						'property_id' => $clean_settings['property_id'] ?? '',
					)
				);
			}

			if ( ! empty( $error_message ) ) {
				set_transient( 'sm_pulse_analytics_settings_error', $error_message, 45 );
				$page_val = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'pulse-analytics';
				wp_safe_redirect( admin_url( 'admin.php?page=' . $page_val . '&step=' . $current_step ) );
				exit;
			}

			if ( isset( $_POST['save_and_connect'] ) ) {
				$has_oauth_creds = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' )
					? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::has_oauth_app_credentials()
					: ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials' )
						&& \Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials::has_ga4_oauth_credentials() );
				if ( $has_oauth_creds ) {
					$auth_url = GA_Auth::get_auth_url();
					if ( ! empty( $auth_url ) && '#' !== $auth_url ) {
						// wp_redirect (not wp_safe_redirect) is required here because
						// the OAuth URL is an external domain (accounts.google.com).
						wp_redirect( esc_url_raw( $auth_url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
						exit;
					}
				}
				set_transient(
					'sm_pulse_analytics_settings_error',
					__( 'OAuth credentials are incomplete. Enter both Client ID and Client Secret, save, then try Save & Connect again.', 'smackcoders-pulse-analytics-for-woocommerce' ),
					45
				);
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => 'sm-pulse-analytics-settings',
							'step' => $current_step,
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			$redirect_args = array(
				'page' => 'sm-pulse-analytics-settings',
				'step' => $current_step,
			);
			if ( $oauth_creds_saved ) {
				$redirect_args['oauth_creds_saved'] = '1';
			} else {
				$redirect_args['settings-updated'] = '1';
			}
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( isset( $_GET['ga4_connected'] ) && '1' == $_GET['ga4_connected'] ) {
			if ( isset( $_GET['ga4_pick'] ) && '1' === $_GET['ga4_pick'] ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Google Analytics connected successfully.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong> ' . esc_html__( 'Choose your GA4 property below to save the Property ID and Measurement ID.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Google Analytics connected successfully.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
			}
		}
		if ( isset( $_GET['ga4_ids_applied'] ) && '1' === $_GET['ga4_ids_applied'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'GA4 Property ID and Measurement ID saved from your Google account.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
		}
		if ( isset( $_GET['ga4_refreshed'] ) && '1' === $_GET['ga4_refreshed'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'GA4 property list refreshed from Google.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
		}
		if ( isset( $_GET['token_refreshed'] ) && '1' == $_GET['token_refreshed'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Access token refreshed.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
		}
		if ( isset( $_GET['disconnected'] ) && '1' == $_GET['disconnected'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Google Analytics disconnected.', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
		}
		if ( isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Settings Saved Successfully!', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong></p></div>';
		}
		if ( isset( $_GET['oauth_creds_saved'] ) && '1' === $_GET['oauth_creds_saved'] ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'OAuth credentials saved to wp-config.php (encrypted).', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong> ';
			echo esc_html__( 'Click Sign in with Google (or Save & Connect) to authorize your account and complete setup.', 'smackcoders-pulse-analytics-for-woocommerce' );
			echo '</p></div>';
		}
		if ( isset( $_GET['ga4_oauth_error'] ) && '1' === $_GET['ga4_oauth_error'] ) {
			$oauth_error = get_transient( \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::OAUTH_ERROR_TRANSIENT );
			if ( false !== $oauth_error ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Google OAuth Error:', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong> ' . esc_html( $oauth_error ) . '</p></div>';
				delete_transient( \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::OAUTH_ERROR_TRANSIENT );
			}
		}

		$transient_error = get_transient( 'sm_pulse_analytics_settings_error' );
		if ( false !== $transient_error ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error:', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong> ' . esc_html( $transient_error ) . '</p></div>';
			delete_transient( 'sm_pulse_analytics_settings_error' );
		}

		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' ) ) {
			$discovery_error = get_transient( \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::ERROR_TRANSIENT );
			if ( false !== $discovery_error ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'GA4 property discovery:', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong> ' . esc_html( $discovery_error ) . '</p></div>';
				delete_transient( \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::ERROR_TRANSIENT );
			}
		}

		// Global Pulse Header.
		sm_pulse_analytics_render_admin_header();
		?>
		<div class="pulse-analytics-ui wrap sp-settings-wrap" id="PulseAnalytics-settings-v2">
			<div class="sp-card sp-settings-panel">

			<div class="PulseAnalytics-wizard-main">

				<form method="POST" action="">
					<?php wp_nonce_field( 'save_sm_pulse_analytics_settings' ); ?>
					<input type="hidden" name="sm_pulse_analytics_settings[current_step]" value="<?php echo esc_attr( $current_step ); ?>">

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
							<h2><?php esc_html_e( 'Welcome to Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
						<p class="settings-description">
							<?php esc_html_e( 'Pulse Analytics is your all-in-one WordPress analytics companion. It helps you understand your website traffic, track user behavior, monitor eCommerce performance, and make data-driven decisions — all from within your WordPress dashboard. This guide will walk you through the initial setup so you can start collecting meaningful insights right away.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</p>
					</div>

					<!-- Setup Guide -->
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4 4 4"></path><path d="m16 10-4 4-4-4"></path><path d="m8 14 4-4 4 4"></path></svg>
							</div>
							<h2><?php esc_html_e( 'Getting Started Guide', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
						<div class="steps-container">
							<div class="step-item">
								<div class="step-number">1</div>
								<h3 class="step-title"><?php esc_html_e( 'Check General Settings', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
								<p class="step-desc"><?php esc_html_e( 'Navigate to the General Settings tab and review your basic configuration. Make sure your site URL is correct, your timezone is set properly, and user roles with access to analytics are configured. These foundational settings ensure accurate data collection from the start.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
							</div>
							<div class="step-item">
								<div class="step-number">2</div>
								<h3 class="step-title"><?php esc_html_e( 'Connect Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
								<p class="step-desc"><?php esc_html_e( 'Go to the Analytics Configuration tab and connect your analytics provider. You can authorize your Google Analytics account or use the built-in Pulse tracking. Follow the on-screen instructions to complete the authorization flow and verify data is being received.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
							</div>
							<div class="step-item">
								<div class="step-number">3</div>
								<h3 class="step-title"><?php esc_html_e( 'Verify Connection', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h3>
								<p class="step-desc"><?php esc_html_e( 'Head to the Dashboard to verify that your connection is working. You should see live visitor data within a few minutes. If data is not appearing, check Analytics Configuration to ensure your credentials are valid and the tracking code is properly installed.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Support Resources -->
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
							</div>
							<h2><?php esc_html_e( 'Support Resources', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
						<p class="settings-description"><?php esc_html_e( 'Need more help? We have got you covered. Browse our full documentation for detailed guides, reach out to our support team for personalized assistance, or jump straight into the General Settings to begin configuring your plugin.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
						<div class="flex flex-wrap gap-4 mt-8">
							<a href="<?php echo esc_url( SM_PULSE_ANALYTICS_DOCS_URL ); ?>" target="_blank" class="btn-premium">
								<?php esc_html_e( 'Read Full Documentation', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</a>
							<a href="<?php echo esc_url( SM_PULSE_ANALYTICS_SUPPORT_URL ); ?>" target="_blank" class="btn-outline">
								<?php esc_html_e( 'Contact Support Team', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</a>
							<a href="?page=sm-pulse-analytics-settings&step=general" class="btn-outline">
								<?php esc_html_e( 'Move to General Settings →', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</a>
						</div>
					</div>

					<?php
					// GENERAL TAB.
				elseif ( 'general' === $current_step ) :
					$site_mode         = $options['site_mode'] ?? 'auto';
					$currency_code     = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' )
						? \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::get_currency_code()
						: ( $options['currency_code'] ?? 'USD' );
					$detected_platforms = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' )
						? \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::detect_commerce_platforms()
						: array();
					$resolved_site_type = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' )
						? \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::get_site_type_label()
						: '';
					?>
					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
							</div>
							<h2><?php esc_html_e( 'Site Profile & Environment', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
						<p class="settings-description sp-mb-32">
							<?php esc_html_e( 'Pulse Analytics works on blogs, business websites, and online stores. Choose how the plugin should treat your site — commerce reports appear when a supported store plugin is detected or when you select Online store mode.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</p>

						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Site type', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<select name="sm_pulse_analytics_settings[site_mode]" class="regular-text">
										<option value="auto" <?php selected( $site_mode, 'auto' ); ?>><?php esc_html_e( 'Auto-detect (recommended)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
										<option value="website" <?php selected( $site_mode, 'website' ); ?>><?php esc_html_e( 'Website / blog (traffic only)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
										<option value="store" <?php selected( $site_mode, 'store' ); ?>><?php esc_html_e( 'Online store (commerce reports)', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></option>
									</select>
									<p class="description sp-mt-10">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: resolved site type label */
												__( 'Currently resolved as: %s', 'smackcoders-pulse-analytics-for-woocommerce' ),
												$resolved_site_type
											)
										);
										?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Commerce platforms', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<div class="sp-flex-wrap-gap-8">
										<?php
										$platform_labels = array(
											'woocommerce' => 'WooCommerce',
											'edd'         => 'Easy Digital Downloads',
											'memberpress' => 'MemberPress',
											'givewp'      => 'GiveWP',
										);
										if ( empty( $detected_platforms ) ) :
											?>
											<span>
												<?php esc_html_e( 'None detected', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
											</span>
											<?php
										else :
											foreach ( $detected_platforms as $platform ) :
												?>
												<span class="sp-badge sp-badge--success">
													<?php echo esc_html( $platform_labels[ $platform ] ?? $platform ); ?>
												</span>
												<?php
											endforeach;
										endif;
										?>
									</div>
									<p class="description sp-mt-10">
										<?php esc_html_e( 'Install WooCommerce, Easy Digital Downloads, MemberPress, or GiveWP to enable revenue KPIs, eCommerce Overview, and purchase tracking.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Site timezone', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<input type="text" class="regular-text" value="
									<?php
										$offset  = get_option( 'gmt_offset' );
										$hours   = (int) $offset;
										$minutes = abs( $offset - $hours ) * 60;
										echo esc_html( sprintf( 'UTC %+03d:%02d', $hours, $minutes ) );
									?>
									" readonly />
									<p class="description sp-desc sp-desc--muted sp-mt-10">
										<?php esc_html_e( 'Used to align daily traffic and conversion reports with your WordPress timezone.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Reporting currency', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<?php if ( ! empty( $detected_platforms ) ) : ?>
										<input type="text" class="regular-text" value="<?php echo esc_attr( $currency_code ); ?>" readonly />
										<p class="description sp-desc sp-desc--muted sp-mt-10">
											<?php esc_html_e( 'Detected from your active store plugin.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</p>
									<?php else : ?>
										<input type="text" name="sm_pulse_analytics_settings[currency_code]" class="regular-text" value="<?php echo esc_attr( $currency_code ); ?>" maxlength="3" />
										<p class="description sp-desc sp-desc--muted sp-mt-10">
											<?php esc_html_e( 'ISO code used when no store plugin is active (e.g. USD, EUR, INR).', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Start tracking', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<?php
									$enable_tracking = array_key_exists( 'enable_tracking', $options )
										? ( '1' === (string) $options['enable_tracking'] )
										: true;
									?>
									<label class="sp-inline-checkbox-label">
										<input type="checkbox" name="sm_pulse_analytics_settings[enable_tracking]" value="1" <?php checked( $enable_tracking ); ?> class="sp-checkbox-md" />
										<span><?php esc_html_e( 'Inject GA4 gtag on the public site', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
									</label>
									<p class="description sp-desc sp-desc--slate sp-mt-6">
										<?php esc_html_e( 'When disabled, Pulse Analytics will not enqueue the Google tag (gtag.js) or related frontend tracking scripts.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'GA4 DebugView', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
								<td>
									<?php
									$debug_locked = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' )
										&& \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::is_debug_mode_locked_by_constant();
									$debug_on     = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' )
										? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::is_debug_mode()
										: ! empty( $options['enable_debug_mode'] );
									?>
									<label class="sp-inline-checkbox-label">
										<input type="checkbox" name="sm_pulse_analytics_settings[enable_debug_mode]" value="1" <?php checked( $debug_on ); ?> <?php disabled( $debug_locked ); ?> class="sp-checkbox-md" />
										<span><?php esc_html_e( 'Enable DebugView', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
									</label>
									<p class="description sp-desc sp-desc--slate sp-mt-6">
										<?php
										if ( $debug_locked ) {
											esc_html_e( 'Forced on via wp-config.php (SM_PULSE_ANALYTICS_GA4_DEBUG_MODE). Events are sent with debug_mode for GA4 Admin → DebugView.', 'smackcoders-pulse-analytics-for-woocommerce' );
										} else {
											esc_html_e( 'Send events with debug_mode so they appear in GA4 Admin → DebugView (use with the GA Debugger extension or preview).', 'smackcoders-pulse-analytics-for-woocommerce' );
										}
										?>
									</p>
								</td>
							</tr>
						</table>
						<div class="mt-12 flex items-center gap-4">
							<button type="submit" class="btn-premium"><?php esc_html_e( 'Save site profile', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
							<a href="?page=sm-pulse-analytics-settings&step=analytics" class="btn-outline"><?php esc_html_e( 'Next: Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> &rarr;</a>
						</div>
					</div>

					<?php
				elseif ( 'analytics' === $current_step ) :
					$ga4_connection = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' )
						? \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::get_connection_state( true )
						: array(
							'oauth_connected'    => false,
							'token_valid'        => false,
							'token_expired'      => false,
							'token_minutes_left' => 0,
							'has_property_id'    => false,
							'has_measurement_id' => false,
							'is_ready'           => false,
							'property_id'        => '',
							'measurement_id'     => '',
						);
					$oauth_connected     = ! empty( $ga4_connection['oauth_connected'] );
					$ga4_is_ready        = ! empty( $ga4_connection['is_ready'] );
					$ga4_ids_saved       = ! empty( $ga4_connection['has_property_id'] ) && ! empty( $ga4_connection['has_measurement_id'] );
					$ga4_setup_complete  = ! empty( $ga4_connection['token_valid'] ) && $ga4_ids_saved;
					$display_property_id = (string) ( $ga4_connection['property_id'] ?? '' );
					$measurement_id      = (string) ( $ga4_connection['measurement_id'] ?? '' );
					$token_status_label  = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' )
						? \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::get_token_status_label( $ga4_connection )
						: __( 'Inactive', 'smackcoders-pulse-analytics-for-woocommerce' );
					$refresh_token_url   = wp_nonce_url(
						admin_url( 'admin-post.php?action=sm_pulse_analytics_refresh_access_token' ),
						'sm_pulse_analytics_refresh_access_token'
					);
					$disconnect_url      = wp_nonce_url(
						admin_url( 'admin-post.php?action=sm_pulse_analytics_disconnect_google_analytics' ),
						'sm_pulse_analytics_disconnect_google_analytics'
					);
					$sec_class             = '\Sm_Pulse_Analytics\PulseAnalytics_Secure_Credentials';
					$config_class          = '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config';
					$api_secret_usable     = class_exists( $sec_class )
						&& $sec_class::is_usable( $sec_class::CONST_GA4_API_SECRET );
					$client_id_usable      = class_exists( $config_class )
						? ( '' !== $config_class::get_client_id() )
						: ( class_exists( $sec_class ) && $sec_class::is_usable( $sec_class::CONST_GA4_CLIENT_ID ) );
					$client_sec_usable     = class_exists( $config_class )
						? ( '' !== $config_class::get_client_secret() )
						: ( class_exists( $sec_class ) && $sec_class::is_usable( $sec_class::CONST_GA4_CLIENT_SECRET ) );
					$client_id_decrypt_failed = class_exists( $sec_class )
						&& $sec_class::is_configured( $sec_class::CONST_GA4_CLIENT_ID )
						&& ! $sec_class::is_usable( $sec_class::CONST_GA4_CLIENT_ID );
					$client_sec_decrypt_failed = class_exists( $sec_class )
						&& $sec_class::is_configured( $sec_class::CONST_GA4_CLIENT_SECRET )
						&& ! $sec_class::is_usable( $sec_class::CONST_GA4_CLIENT_SECRET );
					$has_oauth_credentials = class_exists( $config_class )
						? $config_class::has_oauth_app_credentials()
						: ( class_exists( $sec_class ) && $sec_class::has_ga4_oauth_credentials() );
					$can_revoke_credentials = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' )
						&& \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::has_stored_credentials();
					$ga4_discoveries       = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' )
						? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::get_cached_discoveries()
						: array();
					if ( $oauth_connected && empty( $ga4_discoveries ) && class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' ) ) {
						\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::fetch_and_cache_discoveries();
						$ga4_discoveries = \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::get_cached_discoveries();
					}
					$ga4_discovery_choices = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' )
						? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::flatten_choices( $ga4_discoveries )
						: array();
					$ga4_selected_choice   = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' )
						? \Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API::find_best_match_choice( $ga4_discoveries )
						: '';
					$ga4_selected_label    = '';
					if ( $ga4_ids_saved ) {
						foreach ( $ga4_discovery_choices as $choice ) {
							if (
								(string) ( $choice['property_id'] ?? '' ) === $display_property_id
								&& strtoupper( (string) ( $choice['measurement_id'] ?? '' ) ) === strtoupper( $measurement_id )
							) {
								$ga4_selected_label = (string) $choice['label'];
								break;
							}
						}
						if ( '' === $ga4_selected_label ) {
							$ga4_selected_label = sprintf(
								/* translators: 1: measurement ID, 2: numeric property ID */
								__( '%1$s (Property %2$s)', 'smackcoders-pulse-analytics-for-woocommerce' ),
								$measurement_id,
								$display_property_id
							);
						}
					}
					$show_ga4_property_picker = $oauth_connected && (
						! $ga4_ids_saved
						|| ( isset( $_GET['ga4_change_property'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['ga4_change_property'] ) ) )
					);
					$banner_status = $ga4_setup_complete ? 'complete' : ( $oauth_connected ? 'connected' : 'warning' );
					?>
					<!-- GA4 Connection Status Banner -->
					<div class="settings-card sp-ga4-banner sp-ga4-banner--<?php echo esc_attr( $banner_status ); ?>">
						<div class="sp-flex-between-wrap">
							<div class="sp-flex-center-gap-14">
								<div class="sp-banner-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
										<?php if ( $ga4_setup_complete ) : ?>
											<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
											<polyline points="22 4 12 14.01 9 11.01"></polyline>
										<?php elseif ( $oauth_connected ) : ?>
											<circle cx="12" cy="12" r="10"></circle>
											<line x1="12" y1="8" x2="12" y2="12"></line>
											<line x1="12" y1="16" x2="12.01" y2="16"></line>
										<?php else : ?>
											<circle cx="12" cy="12" r="10"></circle>
											<line x1="12" y1="8" x2="12" y2="12"></line>
											<line x1="12" y1="16" x2="12.01" y2="16"></line>
										<?php endif; ?>
									</svg>
								</div>
								<div>
									<h4 class="sp-banner-title">
										<?php
										if ( $ga4_setup_complete ) {
											esc_html_e( 'Google Analytics Status: Configured', 'smackcoders-pulse-analytics-for-woocommerce' );
										} elseif ( $oauth_connected ) {
											esc_html_e( 'Google Analytics Status: Connected — select property', 'smackcoders-pulse-analytics-for-woocommerce' );
										} elseif ( $ga4_ids_saved ) {
											esc_html_e( 'Google Analytics Status: IDs saved — OAuth required', 'smackcoders-pulse-analytics-for-woocommerce' );
										} else {
											echo esc_html(
												sprintf(
													/* translators: %s: connection status */
													__( 'Google Analytics Status: %s', 'smackcoders-pulse-analytics-for-woocommerce' ),
													__( 'Not Connected', 'smackcoders-pulse-analytics-for-woocommerce' )
												)
											);
										}
										?>
									</h4>
									<p class="sp-banner-text">
										<?php if ( $ga4_setup_complete ) : ?>
											<?php esc_html_e( 'Your Google account is connected and GA4 property settings are saved for live dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										<?php elseif ( $oauth_connected ) : ?>
											<?php esc_html_e( 'Your Google account is connected. Choose your GA4 property below to save the Property ID and Measurement ID.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										<?php elseif ( $ga4_ids_saved ) : ?>
											<?php if ( $has_oauth_credentials ) : ?>
												<?php esc_html_e( 'Property ID and Measurement ID are saved. Sign in with Google below to activate live dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
											<?php else : ?>
												<?php esc_html_e( 'Property ID and Measurement ID are saved, but Google OAuth is not active. Add OAuth credentials to wp-config.php and sign in with Google for live dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
											<?php endif; ?>
										<?php else : ?>
											<?php
											printf(
												/* translators: %s: button label */
												esc_html__( 'Add your Google Cloud OAuth credentials below, then click %s. GA4 Property ID and Measurement ID can be chosen after you connect.', 'smackcoders-pulse-analytics-for-woocommerce' ),
												'<strong>' . esc_html__( 'Sign in with Google', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong>'
											);
											?>
										<?php endif; ?>
									</p>
								</div>
							</div>
						</div>
					</div>

					<?php if ( $show_ga4_property_picker ) : ?>
					</form>
					<div class="settings-card sp-ga4-picker-card">
						<h4 class="sp-card-heading sp-card-heading--indigo"><?php esc_html_e( 'Select GA4 property', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h4>
						<p class="sp-card-text sp-card-text--indigo">
							<?php esc_html_e( 'Choose the GA4 property and web stream for this site, then click Use selected property to save.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</p>
						<?php if ( ! empty( $ga4_discovery_choices ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sp-mb-12">
							<?php wp_nonce_field( 'sm_pulse_analytics_apply_ga4_discovery' ); ?>
							<input type="hidden" name="action" value="sm_pulse_analytics_apply_ga4_discovery" />
							<select id="sm_pulse_analytics_ga4_discovery_choice" name="sm_pulse_analytics_ga4_discovery_choice" class="regular-text sp-input-max-680 sp-mb-12">
								<?php foreach ( $ga4_discovery_choices as $choice ) : ?>
									<option value="<?php echo esc_attr( $choice['key'] ); ?>" <?php selected( $ga4_selected_choice, $choice['key'] ); ?>>
										<?php echo esc_html( $choice['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="sp-flex-gap-12-wrap">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Use selected property', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
							</div>
						</form>
						<?php else : ?>
							<p class="sp-card-text sp-card-text--indigo sp-mb-12">
								<?php esc_html_e( 'No GA4 properties were loaded yet. Click Refresh from Google to fetch your account properties.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</p>
						<?php endif; ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sp-m-0">
							<?php wp_nonce_field( 'sm_pulse_analytics_refresh_ga4_discoveries' ); ?>
							<input type="hidden" name="action" value="sm_pulse_analytics_refresh_ga4_discoveries" />
							<button type="submit" class="button"><?php esc_html_e( 'Refresh from Google', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
						</form>
					</div>
					<form method="POST" action="">
						<?php wp_nonce_field( 'save_sm_pulse_analytics_settings' ); ?>
						<input type="hidden" name="sm_pulse_analytics_settings[current_step]" value="<?php echo esc_attr( $current_step ); ?>" />
					<?php endif; ?>

					<?php if ( $oauth_connected && empty( $measurement_id ) ) : ?>
						<div class="sp-warning-callout">
							<p class="sp-warning-callout__text">
								<strong class="sp-warning-callout__strong"><?php esc_html_e( 'Measurement ID Missing:', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></strong> <?php esc_html_e( 'Choose a GA4 property above or enter a Measurement ID below.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</p>
						</div>
					<?php endif; ?>

					<?php
					$auth_url = class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' ) ? \Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth::get_auth_url() : '';
					?>

					<div class="settings-card">
						<div class="settings-header">
							<div class="icon-circle">
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
							</div>
							<h2><?php esc_html_e( 'Google Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></h2>
						</div>
						<p class="settings-description sp-mb-24">
							<?php esc_html_e( 'Configure GA4 Measurement ID, API Secret, and Google Cloud OAuth credentials for live dashboard reporting.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
						</p>

						<!-- Section 1: Essential GA4 Telemetry -->
						<div class="sp-step-panel sp-step-panel--muted">
							<h3 class="sp-step-heading">
								<span class="sp-step-number sp-step-number--primary">1</span>
								<?php esc_html_e( 'Essential GA4 Telemetry Setup', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</h3>
							<p class="sp-step-desc">
								<?php if ( $oauth_connected ) : ?>
									<?php esc_html_e( 'Configure your Measurement ID and API Secret for frontend tracking and server-side events.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'GA4 Measurement ID and API Secret fields appear after you sign in with Google.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
								<?php endif; ?>
							</p>

							<table class="form-table sp-mt-0">
								<?php if ( $oauth_connected && $ga4_ids_saved && ! $show_ga4_property_picker ) : ?>
								<tr>
									<th><?php esc_html_e( 'Selected GA4 property', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<td>
										<p class="sp-field-label"><?php echo esc_html( $ga4_selected_label ); ?></p>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sm-pulse-analytics-settings', 'step' => 'analytics', 'ga4_change_property' => '1' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary sp-mt-4">
											<?php esc_html_e( 'Change property', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</a>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( $oauth_connected ) : ?>
								<tr>
									<th><?php esc_html_e( 'GA4 Property ID', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<td>
										<input type="text" id="ga4-property-id-input" name="sm_pulse_analytics_settings[property_id]" class="regular-text sp-input-max-500" value="<?php echo esc_attr( $display_property_id ); ?>" placeholder="e.g. 987654321" <?php echo ( $ga4_ids_saved && ! $show_ga4_property_picker ) ? 'readonly="readonly"' : ''; ?> />
										<p class="description sp-desc sp-desc--muted sp-mt-8">
											<?php esc_html_e( 'The numeric ID of your GA4 property (found under Admin > Property Details). Required by GA4 Data API.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'GA4 Measurement ID', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> <span class="sp-required">*</span></th>
									<td>
										<input type="text" id="ga4-measurement-id-input" name="sm_pulse_analytics_ga4_measurement_id" class="regular-text sp-input-max-500" value="<?php echo esc_attr( $measurement_id ); ?>" placeholder="<?php echo esc_attr__( 'e.g. G-ABC1234567', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" pattern="^G-[A-Za-z0-9]+$" title="<?php echo esc_attr__( 'Must start with G- followed by alphanumeric characters.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>" <?php echo ( $ga4_ids_saved && ! $show_ga4_property_picker ) ? 'readonly="readonly"' : ''; ?> />
										<p class="description sp-desc sp-desc--muted sp-mt-8">
											<?php
											printf(
												/* translators: %s: path in GA4 Admin */
												esc_html__( 'Your GA4 Measurement ID (starts with G-). Found in %s. Injected into frontend pages.', 'smackcoders-pulse-analytics-for-woocommerce' ),
												'<strong>' . esc_html__( 'GA4 Admin > Data Streams > Web Stream Details', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong>'
											);
											?>
										</p>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Measurement Protocol API Secret', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<td>
										<?php if ( $api_secret_usable ) : ?>
											<p class="sp-status-msg sp-status-msg--success"><?php esc_html_e( 'Saved in wp-config.php', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
											<p class="description sp-desc sp-desc--muted sp-m-0">
												<?php esc_html_e( 'To replace it, enter a new secret below, save, then update the define() in wp-config.php.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
											</p>
										<?php endif; ?>
										<div class="sp-field-relative sp-mt-8">
											<input class="sp-w-full" type="password" id="sm_pulse_analytics_ga4_api_secret" name="sm_pulse_analytics_ga4_api_secret" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $api_secret_usable ? __( 'Enter new secret to rotate…', 'smackcoders-pulse-analytics-for-woocommerce' ) : __( 'e.g. G9icPBGrRSKApeK0cedcYbE17g', 'smackcoders-pulse-analytics-for-woocommerce' ) ); ?>" />
											<button type="button" class="sp-toggle-secret sp-toggle-secret-btn" data-target="sm_pulse_analytics_ga4_api_secret">
												<svg id="eye_icon_sm_pulse_analytics_ga4_api_secret" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
											</button>
										</div>
										<p class="description sp-desc sp-desc--muted sp-mt-8">
											<?php
											printf(
												/* translators: 1: opening strong, 2: closing strong, 3: path in GA4 admin */
												esc_html__( 'Stored in wp-config.php. Enables reliable %1$sserver-side tracking%2$s for purchases and background events. Retrieve from %3$s.', 'smackcoders-pulse-analytics-for-woocommerce' ),
												'<strong>',
												'</strong>',
												'<strong>' . esc_html__( 'GA4 Admin > Data Streams > Measurement Protocol API Secrets', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong>'
											);
											?>
										</p>
									</td>
								</tr>
								<?php endif; ?>
								<?php
								do_action(
									'sm_pulse_analytics_analytics_settings_telemetry_fields',
									array(
										'oauth_connected' => $oauth_connected,
										'measurement_id'  => $measurement_id,
										'gsc_site_url'    => '',
									)
								);
								?>
							</table>
						</div>

						<!-- Section 2: Admin Dashboard Reports & Google Cloud OAuth (Optional) -->
						<div class="sp-step-panel sp-step-panel--white">
							<h3 class="sp-step-heading">
								<span class="sp-step-number sp-step-number--gray">2</span>
								Admin Dashboard Reports & Google Cloud OAuth (Optional)
							</h3>
							<p class="sp-step-desc">
								<?php esc_html_e( 'Optional. Fill out your Google Cloud OAuth app credentials to fetch live Analytics reports, real-time active visitors, and traffic overview widgets inside your WordPress dashboard.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
							</p>
							<?php if ( ! $has_oauth_credentials ) : ?>
								<p class="sp-step-desc sp-step-desc--warning">
									<?php esc_html_e( 'Enter your Google Cloud Client ID and Client Secret below, then save and sign in with Google.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
								</p>
							<?php endif; ?>

							<table class="form-table sp-mt-0">
								<tr>
									<th><?php esc_html_e( 'Google Client ID', 'smackcoders-pulse-analytics-for-woocommerce' ); ?> <span class="wptip" data-tip="<?php echo esc_attr__( 'Look for OAuth 2.0 Client IDs in your Google Cloud project.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>">
										<svg class="sp-help-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
									</span></th>
									<td>
										<?php if ( $client_id_usable ) : ?>
											<p class="sp-status-msg sp-status-msg--success">
												<?php
												esc_html_e( 'Saved in wp-config.php', 'smackcoders-pulse-analytics-for-woocommerce' );
												?>
											</p>
										<?php elseif ( $client_id_decrypt_failed ) : ?>
											<p class="sp-status-msg sp-status-msg--warning"><?php esc_html_e( 'Credential found in wp-config.php but could not be decrypted. Re-enter Client ID/Secret and update wp-config.php.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
										<?php endif; ?>
										<input type="text" name="sm_pulse_analytics_settings[client_id]" class="large-text sp-input-max-500" value="" autocomplete="off" placeholder="<?php echo esc_attr( $client_id_usable ? __( 'Enter new Client ID to rotate…', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'e.g. 1234567-abcdef.apps.googleuserconten...' ); ?>" />
										<p class="description sp-desc sp-desc--muted sp-mt-8">
											<?php esc_html_e( 'OAuth 2.0 Client ID from Google Cloud Console (APIs & Services → Credentials). Saved encrypted when you submit this form.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Google Client Secret', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
									<td>
										<?php if ( $client_sec_usable ) : ?>
											<p class="sp-status-msg sp-status-msg--success">
												<?php
												esc_html_e( 'Saved in wp-config.php', 'smackcoders-pulse-analytics-for-woocommerce' );
												?>
											</p>
										<?php elseif ( $client_sec_decrypt_failed ) : ?>
											<p class="sp-status-msg sp-status-msg--warning"><?php esc_html_e( 'Credential found in wp-config.php but could not be decrypted. Re-enter Client ID/Secret and update wp-config.php.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></p>
										<?php endif; ?>
										<div class="sp-field-relative">
											<input class="sp-w-full" type="password" id="ga_client_secret" name="sm_pulse_analytics_settings[client_secret]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $client_sec_usable ? __( 'Enter new secret to rotate…', 'smackcoders-pulse-analytics-for-woocommerce' ) : __( 'Enter your private client secret', 'smackcoders-pulse-analytics-for-woocommerce' ) ); ?>" />
											<button type="button" class="sp-toggle-secret sp-toggle-secret-btn" data-target="ga_client_secret">
												<svg id="eye_icon_ga_client_secret" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
											</button>
										</div>
										<p class="description sp-desc sp-desc--muted sp-mt-8">
											<?php esc_html_e( 'Used to authenticate with the GA4 Data API. Saved encrypted when you submit this form.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</p>
									</td>
								</tr>
							</table>
						</div>

						<!-- VERY BOTTOM BAR: Copy Redirect URI + Action Buttons -->
						<div class="sp-oauth-footer">
							<!-- Copyable Authorized Redirect URI at Bottom -->
							<div class="sp-redirect-uri-box">
								<label class="sp-block-label">
									<?php esc_html_e( 'Authorized Redirect URI (Copy to Google Cloud Console):', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
								</label>
								<p class="sp-redirect-uri-desc">
									<?php
									printf(
										/* translators: %s: section label */
										esc_html__( 'Add this exact URI under %s in your Google Cloud Console OAuth 2.0 Client ID settings.', 'smackcoders-pulse-analytics-for-woocommerce' ),
										'<strong>' . esc_html__( 'Authorized redirect URIs', 'smackcoders-pulse-analytics-for-woocommerce' ) . '</strong>'
									);
									?>
								</p>
								<div class="sp-flex-gap-8">
									<input type="text" readonly class="sp-select-on-click sp-monospace-input sp-w-full" value="<?php echo esc_attr( admin_url( 'admin.php?page=sm-pulse-analytics-gsc-oauth' ) ); ?>" />
									<button type="button" class="sp-copy-uri-btn" data-copy-uri="<?php echo esc_attr( admin_url( 'admin.php?page=sm-pulse-analytics-gsc-oauth' ) ); ?>"><?php esc_html_e( 'Copy Redirect URI', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
								</div>
							</div>

							<!-- Action Buttons at Very Bottom -->
							<div class="sp-flex-gap-12-wrap-center">
								<button type="submit" class="btn-premium sp-btn-pad-lg">
									<?php esc_html_e( 'Save Analytics Configuration', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
								</button>
								<?php if ( $has_oauth_credentials && ! $oauth_connected ) : ?>
									<button type="submit" name="save_and_connect" value="1" class="btn-outline sp-btn-pad-md">
										<?php esc_html_e( 'Save & Connect', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
									</button>
									<?php if ( ! empty( $auth_url ) && '#' !== $auth_url ) : ?>
										<a href="<?php echo esc_url( $auth_url ); ?>" class="btn-outline sp-btn-pad-md sp-no-underline">
											<?php esc_html_e( 'Sign in with Google →', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
										</a>
									<?php endif; ?>
								<?php elseif ( $ga4_setup_complete ) : ?>
									<span class="sp-text-success">
										<?php esc_html_e( 'Google Analytics is connected.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>

						<?php
						$connection_status = $ga4_setup_complete ? 'complete' : ( $oauth_connected ? 'connected' : ( $ga4_ids_saved ? 'warning' : 'error' ) );
						$connection_box_title  = $ga4_setup_complete
							? __( 'Connected & Secure', 'smackcoders-pulse-analytics-for-woocommerce' )
							: ( $oauth_connected
								? __( 'Setup incomplete', 'smackcoders-pulse-analytics-for-woocommerce' )
								: ( $ga4_ids_saved
									? __( 'OAuth required', 'smackcoders-pulse-analytics-for-woocommerce' )
									: __( 'Not Connected', 'smackcoders-pulse-analytics-for-woocommerce' ) ) );
						$connection_box_text   = $ga4_setup_complete
							? __( 'Your site is authenticated with Google Analytics and a GA4 property is configured for live dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' )
							: ( $oauth_connected
								? __( 'Google account connected. Select a GA4 property and click Use selected property to finish setup.', 'smackcoders-pulse-analytics-for-woocommerce' )
								: ( $ga4_ids_saved
									? ( $has_oauth_credentials
										? __( 'GA4 IDs are saved. Sign in with Google to activate live dashboard reports.', 'smackcoders-pulse-analytics-for-woocommerce' )
										: __( 'GA4 IDs are saved but OAuth is inactive. Add credentials to wp-config.php and sign in with Google.', 'smackcoders-pulse-analytics-for-woocommerce' ) )
									: __( 'Save your Google Cloud OAuth credentials above, then sign in with Google.', 'smackcoders-pulse-analytics-for-woocommerce' ) ) );
						$token_status_class = ! empty( $ga4_connection['token_expired'] ) ? 'sp-token-status--expired' : 'sp-token-status--active';
						?>
						<!-- Connection Status -->
						<div class="sp-connection-box sp-connection-box--<?php echo esc_attr( $connection_status ); ?>">
							<div class="sp-flex-start-wrap">
								<div class="sp-flex-1-min-220">
									<h3 class="sp-connection-title">
										<?php echo esc_html( $connection_box_title ); ?>
									</h3>
									<p class="sp-connection-text">
										<?php echo esc_html( $connection_box_text ); ?>
									</p>
									<table class="form-table sp-m-0">
										<?php if ( $oauth_connected ) : ?>
										<tr>
											<th class="sp-compact-th sp-compact-th--wide"><?php esc_html_e( 'Property ID', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
											<td class="sp-compact-td sp-compact-td--bold"><?php echo esc_html( $display_property_id ? $display_property_id : __( 'Not configured', 'smackcoders-pulse-analytics-for-woocommerce' ) ); ?></td>
										</tr>
										<?php endif; ?>
										<tr>
											<th class="sp-compact-th"><?php esc_html_e( 'Token Status', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></th>
											<td class="sp-compact-td">
												<?php if ( $oauth_connected ) : ?>
													<span class="sp-token-status <?php echo esc_attr( $token_status_class ); ?>">
														<?php echo esc_html( $token_status_label ); ?>
													</span>
												<?php else : ?>
													<span class="sp-token-inactive"><?php esc_html_e( 'Inactive', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></span>
												<?php endif; ?>
											</td>
										</tr>
									</table>
								</div>
								<?php if ( $oauth_connected || $can_revoke_credentials ) : ?>
									<div class="sp-flex-gap-10-wrap">
										<a href="<?php echo esc_url( $refresh_token_url ); ?>" class="btn-premium sp-btn-pad-sm sp-no-underline"><?php esc_html_e( 'Refresh Token', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></a>
										<?php if ( $can_revoke_credentials ) : ?>
											<button type="button" id="revoke-analytics-credentials-btn" data-url="<?php echo esc_url( $disconnect_url ); ?>" class="btn-outline sp-btn-revoke"><?php esc_html_e( 'Revoke Credentials', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></button>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
						</div>
					</div>

					<?php
					// ECOMMERCE TAB.
				elseif ( 'ecommerce' === $current_step ) :
					include SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/settings/settings-ecommerce.php';
					// LINKS & AFFILIATE TAB.
				elseif ( 'links' === $current_step ) :
					include SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/settings/settings-links.php';
				endif;
				?>

				<?php do_action( 'sm_pulse_analytics_render_settings_tab_' . $current_step ); ?>

				<?php
				echo '</form>';
				?>

			</div>

			</div><!-- .sp-card.sp-settings-panel -->

			</div><!-- .sp-settings-content -->
			</div><!-- .sp-settings-layout -->
			</div><!-- .pulse-analytics-ui -->

		</div><!-- #PulseAnalytics-settings-v2 -->
		<?php
	}
}
