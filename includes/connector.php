<?php
/**
 * Main connector class for Pulse Analytics for WordPress.
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

if ( ! class_exists( __NAMESPACE__ . '\GA_Connector' ) ) {

	class GA_Connector {

		private static $instance = null;

		// Plugin version constant.
		const PLUGIN_VERSION = '1.0.2';

		private function __construct() {
			$this->register_hooks();
		}

		private function register_hooks(): void {
			add_action( 'admin_menu', array( $this, 'ga_add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
			add_filter( 'admin_footer_text', array( __CLASS__, 'remove_admin_footer' ) );
			add_filter( 'update_footer', array( __CLASS__, 'remove_admin_footer' ), 99 );
			// gtag injection is owned by PulseAnalytics_GA4_Gtag::init() only (#32 ISSUE-006).

			// Activation hooks should point to the main plugin file.
            if ( class_exists( '\Sm_Pulse_Analytics\GA_Auth' ) ) {
                register_activation_hook( SM_PULSE_ANALYTICS_PLUGIN_FILE, array( GA_Auth::class, 'activate' ) );
                register_deactivation_hook( SM_PULSE_ANALYTICS_PLUGIN_FILE, array( GA_Auth::class, 'deactivate' ) );
			}

			if ( class_exists( '\PulseAnalytics_GSC_API' ) ) {
				\PulseAnalytics_GSC_API::init();
			}

			add_action(
				'admin_init',
				function () {
					// Auto-refresh token if expiring in < 5 mins.
					if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' ) ) {
						$auth_options = PulseAnalytics_GA4_OAuth::get_auth_tokens();
						$expires_at   = (int) ( $auth_options['expires_at'] ?? 0 );
						$expires_in   = $expires_at > 0 ? ( $expires_at - time() ) : 0;
						if ( $expires_at > 0 && $expires_in < 300 && ! empty( $auth_options['refresh_token'] ) ) {
							PulseAnalytics_GA4_OAuth::refresh_access_token();
						}
					}

					// Forced DB Migration Check (Priority 5).
					$this->maybe_update_database();
				},
				5
			);

			add_action(
				'admin_post_sm_pulse_analytics_refresh_access_token',
				function () {
					if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'You do not have permission to perform this action', 'smackcoders-pulse-analytics-for-woocommerce' ) );
					}
					check_admin_referer( 'sm_pulse_analytics_refresh_access_token' );
					if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' ) ) {
						PulseAnalytics_GA4_OAuth::refresh_access_token();
					} elseif ( class_exists( '\Sm_Pulse_Analytics\GA_Auth' ) ) {
						GA_Auth::refresh_access_token();
					}
					wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics&token_refreshed=1' ) );
					exit;
				}
			);

			add_action(
				'admin_post_sm_pulse_analytics_disconnect_google_analytics',
				function () {
					if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'You do not have permission to perform this action', 'smackcoders-pulse-analytics-for-woocommerce' ) );
					}
					check_admin_referer( 'sm_pulse_analytics_disconnect_google_analytics' );
					if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth' ) ) {
						PulseAnalytics_GA4_OAuth::disconnect();
					} elseif ( class_exists( 'PulseAnalytics_GA4_OAuth' ) ) {
						\PulseAnalytics_GA4_OAuth::disconnect();
					} elseif ( class_exists( '\Sm_Pulse_Analytics\GA_Auth' ) ) {
						GA_Auth::update_options(
							array(
								'access_token'  => '',
								'refresh_token' => '',
								'expires_at'    => '',
							)
						);
						delete_option( 'sm_pulse_analytics_auth' );
					}
					wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics&disconnected=1' ) );
					exit;
				}
			);

			add_action(
				'admin_post_sm_pulse_analytics_refresh_ga4_discoveries',
				function () {
					if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'You do not have permission to perform this action', 'smackcoders-pulse-analytics-for-woocommerce' ) );
					}
					check_admin_referer( 'sm_pulse_analytics_refresh_ga4_discoveries' );
					if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' ) ) {
						PulseAnalytics_GA4_Admin_API::fetch_and_cache_discoveries();
					}
					wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics&ga4_refreshed=1' ) );
					exit;
				}
			);

			add_action(
				'admin_post_sm_pulse_analytics_apply_ga4_discovery',
				function () {
					if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'You do not have permission to perform this action', 'smackcoders-pulse-analytics-for-woocommerce' ) );
					}
					check_admin_referer( 'sm_pulse_analytics_apply_ga4_discovery' );
					$choice_key = isset( $_POST['sm_pulse_analytics_ga4_discovery_choice'] )
						? sanitize_text_field( wp_unslash( $_POST['sm_pulse_analytics_ga4_discovery_choice'] ) )
						: '';
					if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Admin_API' ) ) {
						$parsed = PulseAnalytics_GA4_Admin_API::parse_choice_key( $choice_key );
						if ( null === $parsed ) {
							set_transient(
								'sm_pulse_analytics_settings_error',
								__( 'Please select a valid GA4 property from the list.', 'smackcoders-pulse-analytics-for-woocommerce' ),
								45
							);
						} else {
							$result = PulseAnalytics_GA4_Admin_API::apply_selection(
								$parsed['property_id'],
								$parsed['measurement_id'],
								$parsed['stream_id']
							);
							if ( is_wp_error( $result ) ) {
								set_transient( 'sm_pulse_analytics_settings_error', $result->get_error_message(), 45 );
							} else {
								wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics&ga4_ids_applied=1' ) );
								exit;
							}
						}
					}
					wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' ) );
					exit;
				}
			);

			// AJAX handle for manual data sync.
			add_action( 'wp_ajax_sm_pulse_analytics_manual_sync', array( $this, 'ajax_manual_sync' ) );

			// AJAX handle for clearing cached analytics data.
			add_action( 'wp_ajax_sm_pulse_analytics_clear_cache', array( $this, 'ajax_clear_cache' ) );

			// Universal Loader Injection.
			add_action( 'admin_footer', array( $this, 'inject_global_loader' ) );
		}

		public function ajax_clear_cache() {
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
			if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
			}

			global $wpdb;
			$table_snapshots = \Sm_Pulse_Analytics\PulseAnalytics_Storage::table_name( 'snapshots' );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_snapshots ) ) === $table_snapshots ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_snapshots ) );
			}

			wp_send_json_success( array( 'message' => __( 'Analytics cache cleared successfully!', 'smackcoders-pulse-analytics-for-woocommerce' ) ) );
		}

		public function ajax_manual_sync() {
			// Security: verify nonce and capability.
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
			if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to sync data.', 'smackcoders-pulse-analytics-for-woocommerce' ) ), 403 );
			}

			// Load aggregator.
			if ( ! function_exists( 'sm_pulse_analytics_process_data_aggregation' ) ) {
				require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/aggregator.php';
			}

			// Run aggregation.
			sm_pulse_analytics_process_data_aggregation();

			$synced_at = time();
			update_option( 'sm_pulse_analytics_last_manual_sync', $synced_at );

			wp_send_json_success(
				array(
					'message'   => __( 'Data synced successfully!', 'smackcoders-pulse-analytics-for-woocommerce' ),
					'last_sync' => $synced_at,
				)
			);
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function ga_add_admin_menu() {
			$view_cap   = current_user_can( 'sm_pulse_analytics_view_reports' ) ? 'sm_pulse_analytics_view_reports' : 'manage_options';
			$manage_cap = current_user_can( 'sm_pulse_analytics_manage_settings' ) ? 'sm_pulse_analytics_manage_settings' : 'manage_options';

			add_menu_page(
				__( 'Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ),
				__( 'Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ),
				$view_cap,
				'pulse-analytics',
				array( $this, 'ga_settings_page' ),
				'dashicons-chart-area',
				80
			);

			add_submenu_page(
				'pulse-analytics',
				__( 'Audience & Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
				__( 'Audience & Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
				$view_cap,
				'pulse-analytics-link-report',
				array( $this, 'sm_pulse_analytics_link_report_page' )
			);

			add_submenu_page(
				'pulse-analytics',
				__( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
				__( 'Affiliate Links', 'smackcoders-pulse-analytics-for-woocommerce' ),
				$view_cap,
				'pulse-analytics-affiliate-links',
				array( $this, 'sm_pulse_analytics_affiliate_links_page' )
			);

			add_submenu_page(
				'pulse-analytics',
				__( 'Forms Conversion', 'smackcoders-pulse-analytics-for-woocommerce' ),
				__( 'Forms Conversion', 'smackcoders-pulse-analytics-for-woocommerce' ),
				$view_cap,
				'pulse-analytics-forms',
				array( $this, 'sm_pulse_analytics_forms_report_page' )
			);

			add_submenu_page(
				'pulse-analytics',
				__( 'Pulse Analytics Settings', 'smackcoders-pulse-analytics-for-woocommerce' ),
				__( 'Settings', 'smackcoders-pulse-analytics-for-woocommerce' ),
				$manage_cap,
				'sm-pulse-analytics-settings',
				array( Admin_UI::class, 'render_page' )
			);

			// Hidden OAuth return URL (registered so Google redirect is allowed; handler in PulseAnalytics_GA4_OAuth).
			add_submenu_page(
				null,
				__( 'Google Analytics OAuth', 'smackcoders-pulse-analytics-for-woocommerce' ),
				'',
				$manage_cap,
				'sm-pulse-analytics-gsc-oauth',
				array( $this, 'sm_pulse_analytics_ga4_oauth_callback_page' )
			);
		}

		/**
		 * OAuth return screen — token exchange runs on load-admin_page_sm-pulse-analytics-gsc-oauth.
		 */
		public function sm_pulse_analytics_ga4_oauth_callback_page() {
			wp_safe_redirect( admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=analytics' ) );
			exit;
		}


		public function enqueue_admin_assets( $hook ) {
			$allowed_pages = array(
				'sm-pulse-analytics-settings',
				'sm-pulse-analytics-gsc-oauth',
				'pulse-analytics',
				'pulse-analytics-forms',
				'pulse-analytics-link-report',
				'pulse-analytics-affiliate-links',
			);
			$allowed_pages = apply_filters( 'sm_pulse_analytics_allowed_admin_pages', $allowed_pages );

			$query_args = array();
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $query_args );
			}
			$page = isset( $query_args['page'] ) ? sanitize_text_field( wp_unslash( $query_args['page'] ) ) : '';

			$is_allowed = in_array( $page, $allowed_pages, true )
				|| ( ! empty( $page ) && (
					0 === stripos( $page, 'smackcoders-pulse-analytics-for-woocommerce' )
					|| 0 === stripos( $page, 'pulse-analytics' )
					|| 0 === stripos( $page, 'sm-pulse-analytics' )
				) );

			if ( empty( $page ) || ! $is_allowed ) {
				return;
			}

			$plugin_version = self::PLUGIN_VERSION;

			wp_enqueue_style(
				'flatpickr-css',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/flatpickr.min.css',
				array(),
				$plugin_version
			);

			wp_enqueue_script(
				'flatpickr-js',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/flatpickr.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_style(
				'seo-insights-style',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/styles.css',
				array(),
				$plugin_version,
				'all'
			);

			$design_system_css = file_exists( SM_PULSE_ANALYTICS_PLUGIN_DIR . 'assets/css/pulse-analytics-design-system.css' )
				? 'assets/css/pulse-analytics-design-system.css'
				: 'assets/css/pulse-analytics-design-system.css';

			wp_enqueue_style(
				'pulse-analytics-design-system',
				SM_PULSE_ANALYTICS_PLUGIN_URL . $design_system_css,
				array(),
				$plugin_version
			);

			wp_enqueue_style(
				'pulse-analytics-dashboard-css',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/dashboard.css',
				array(),
				$plugin_version
			);

			wp_enqueue_style(
				'pulse-analytics-settings-css',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/settings.css',
				array(),
				$plugin_version
			);

			wp_enqueue_style(
				'pulse-analytics-tailwind',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/tailwind.min.css',
				array(),
				$plugin_version
			);

			wp_enqueue_script(
				'chartjs',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/chart.min.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_script(
				'pulse-analytics-chart-tooltip-helper',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/chart-tooltip-helper.js',
				array( 'chartjs' ),
				$plugin_version,
				true
			);

			wp_enqueue_script(
				'pulse-analytics-sortablejs',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/Sortable.min.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_script(
				'pulse-analytics-admin-fetch-patch',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/admin-fetch-patch.js',
				array(),
				$plugin_version,
				true
			);

			$dashboard_script_handle = ( 'pulse-analytics' === $page ) ? 'pulse-analytics-dashboard-js' : 'seo-insights-dashboard';
			$dashboard_script_file   = ( 'pulse-analytics' === $page ) ? 'assets/js/dashboard.js' : 'assets/js/script.js';

			wp_enqueue_script(
				$dashboard_script_handle,
				SM_PULSE_ANALYTICS_PLUGIN_URL . $dashboard_script_file,
				array( 'chartjs', 'pulse-analytics-chart-tooltip-helper', 'flatpickr-js', 'pulse-analytics-sortablejs', 'pulse-analytics-admin-fetch-patch' ),
				$plugin_version,
				true
			);

			if ( 'pulse-analytics' === $page ) {
				wp_enqueue_script(
					'pulse-analytics-dashboard-modal-js',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/dashboard-modal.js',
					array( 'pulse-analytics-dashboard-js' ),
					$plugin_version,
					true
				);
			}

			$currency_symbol = self::get_currency_symbol();

			$dashboard_localize = array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'rest_url'        => rest_url( 'pulse-analytics/v1' ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'is_pro_active'   => function_exists( 'sm_pulse_analytics_is_pro_active' ) && sm_pulse_analytics_is_pro_active(),
				'last_sync'       => (int) get_option( 'sm_pulse_analytics_last_manual_sync', 0 ),
				'customGoals'     => get_option( 'sm_pulse_analytics_custom_goals', array() ),
				'currency_symbol' => $currency_symbol,
			);
			wp_localize_script( $dashboard_script_handle, 'pulseAnalyticsAjax', $dashboard_localize );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';

			if ( 'pulse-analytics' === $page || 'sm-pulse-analytics-settings' === $page ) {
				wp_enqueue_style(
					'pulse-analytics-dashboard-css',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/dashboard.css',
					array( 'pulse-analytics-design-system' ),
					$plugin_version
				);

				if ( 'links' === $step ) {
					wp_enqueue_script(
						'pulse-analytics-settings-links-js',
						SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/settings-links.js',
						array( 'jquery' ),
						$plugin_version,
						true
					);
				}
				if ( 'ecommerce' === $step ) {
					wp_enqueue_style(
						'pulse-analytics-settings-ecommerce',
						SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/settings-ecommerce.css',
						array( 'pulse-analytics-settings-css' ),
						$plugin_version
					);

					wp_enqueue_script(
						'pulse-analytics-settings-ecommerce-js',
						SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/settings-ecommerce.js',
						array( 'jquery' ),
						$plugin_version,
						true
					);
				}
				if ( 'analytics' === $step ) {
					wp_enqueue_script(
						'pulse-analytics-admin-settings-helpers',
						SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/admin-settings-helpers.js',
						array(),
						$plugin_version,
						true
					);
					wp_localize_script(
						'pulse-analytics-admin-settings-helpers',
						'pulseAnalyticsSettings',
						array(
							'disconnectConfirm'   => __( 'Revoke Google Analytics credentials? Live dashboard reports will stop until you reconnect.', 'smackcoders-pulse-analytics-for-woocommerce' ),
							'redirectUriCopied' => __( 'Redirect URI copied to clipboard!', 'smackcoders-pulse-analytics-for-woocommerce' ),
						)
					);
				}
			}

			if ( 'pulse-analytics-forms' === $page ) {
				wp_enqueue_style(
					'pulse-analytics-forms-report',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/forms-report.css',
					array( 'pulse-analytics-design-system' ),
					$plugin_version
				);

				wp_enqueue_script(
					'chartjs',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/chart.min.js',
					array(),
					$plugin_version,
					true
				);

				wp_enqueue_script(
					'pulse-analytics-chart-tooltip-helper',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/chart-tooltip-helper.js',
					array( 'chartjs' ),
					$plugin_version,
					true
				);

				wp_enqueue_script(
					'pulse-analytics-forms-report-js',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/forms-report.js',
					array( 'jquery', 'chartjs', 'pulse-analytics-chart-tooltip-helper' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'pulse-analytics-forms-report-js',
					'PulseAnalyticsVars',
					array(
						'rest_url' => rest_url(),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					)
				);
			}

			if ( 'pulse-analytics-link-report' === $page ) {
				wp_enqueue_style(
					'pulse-analytics-link-report-css',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/css/link-report.css',
					array( 'pulse-analytics-design-system' ),
					$plugin_version
				);

				wp_enqueue_script(
					'pulse-analytics-link-report-js',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/link-report.js',
					array( 'chartjs' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'pulse-analytics-link-report-js',
					'PulseAnalyticsVars',
					array(
						'rest_url' => rest_url(),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					)
				);
			}



			/**
			 * Pro add-on enqueues CSS/JS for Pro-only report screens.
			 *
			 * @param string $page           Current admin ?page= slug.
			 * @param string $step           Settings wizard step when applicable.
			 * @param string $plugin_version Plugin version.
			 */
			do_action( 'sm_pulse_analytics_enqueue_pro_admin_assets', $page, $step, $plugin_version );
		}

		/**
		 * Legacy no-op — gtag is injected solely by PulseAnalytics_GA4_Gtag (#32 ISSUE-006).
		 */
		public function inject_gtag_snippet() {
			// Intentionally empty to prevent duplicate GA4 snippets.
		}

		public function enqueue_frontend_assets() {
			/**
			 * Allow PRO modules (e.g. AMP Tracking) to skip standard frontend trackers (#43).
			 *
			 * @param bool $skip Whether to skip enqueueing frontend tracking assets.
			 */
			if ( apply_filters( 'sm_pulse_analytics_skip_frontend_tracking', false ) ) {
				return;
			}

			if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' )
				&& ! \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::is_tracking_enabled() ) {
				return;
			}

			$plugin_version = self::PLUGIN_VERSION;

			wp_enqueue_script(
				'pulse-analytics-frontend-tracking',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/frontend-tracking.js',
				array(),
				$plugin_version,
				true
			);

			wp_localize_script(
				'pulse-analytics-frontend-tracking',
				'SmPulseAnalyticsGoals',
				array(
					'goals' => get_option( 'sm_pulse_analytics_custom_goals', array() ),
				)
			);

			$pulse_settings = get_option( 'sm_pulse_analytics_settings', array() );
			$download_exts  = isset( $pulse_settings['download_file_extensions'] ) ? (string) $pulse_settings['download_file_extensions'] : 'pdf, zip, docx, xlsx, csv, txt, mp3, mp4, epub';
			$affiliate_paths = array();
			if ( ! empty( $pulse_settings['affiliate_links'] ) && is_array( $pulse_settings['affiliate_links'] ) ) {
				foreach ( $pulse_settings['affiliate_links'] as $row ) {
					$path = isset( $row['path'] ) ? trim( (string) $row['path'] ) : '';
					if ( '' !== $path ) {
						$affiliate_paths[] = $path;
					}
				}
			}
			if ( empty( $affiliate_paths ) ) {
				$affiliate_paths = array( '/go/', '/recommend/', '/affiliate/' );
			}

			$frontend_localize = array(
				'rest_url'               => rest_url( 'pulse-analytics/v1' ),
				'nonce'                  => wp_create_nonce( 'wp_rest' ),
				'affiliate_pro_tracking' => (
					class_exists( '\Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Config' )
					&& \Sm_Pulse_Analytics\AffiliateLinkModule\Affiliate_Link_Config::is_tracking_enabled()
				),
				'enable_download_tracking' => ! isset( $pulse_settings['enable_download_tracking'] ) || '1' === (string) $pulse_settings['enable_download_tracking'],
				'download_extensions'      => array_values(
					array_filter(
						array_map(
							function ( $ext ) {
								return strtolower( ltrim( trim( (string) $ext ), '.' ) );
							},
							preg_split( '/[\s,]+/', $download_exts ) ?: array()
						)
					)
				),
				'affiliate_paths' => $affiliate_paths,
			);
			wp_localize_script( 'pulse-analytics-frontend-tracking', 'pulseAnalyticsAjax', $frontend_localize );

			// Session heartbeat script – tracks active sessions accurately.
			wp_enqueue_script(
				'pulse-analytics-session-heartbeat',
				SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/session-heartbeat.js',
				array(),
				$plugin_version,
				true
			);

			$session_id = isset( $_COOKIE['sm_pulse_analytics_sid'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['sm_pulse_analytics_sid'] ) )
				: '';

			wp_localize_script(
				'pulse-analytics-session-heartbeat',
				'SmPulseAnalyticsSession',
				array(
					'rest_url'           => rest_url( 'pulse-analytics/v1' ),
					'nonce'              => wp_create_nonce( 'wp_rest' ),
					'session_id'         => $session_id,
					'heartbeat_interval' => 30,
					'user_id'            => get_current_user_id(),
				)
			);

			if (
				class_exists( '\Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Config' )
				&& \Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Config::is_tracking_enabled()
			) {
				wp_enqueue_script(
					'pulse-analytics-forms-tracking',
					SM_PULSE_ANALYTICS_PLUGIN_URL . 'assets/js/forms-tracking.js',
					array( 'pulse-analytics-frontend-tracking' ),
					$plugin_version,
					true
				);
				wp_localize_script(
					'pulse-analytics-forms-tracking',
					'SmPulseAnalyticsForms',
					\Sm_Pulse_Analytics\PulseAnalyticsCore\Forms_Config::get_frontend_config()
				);
			}
		}

		public function sm_pulse_analytics_realtime_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_realtime_report' );
		}

		public function sm_pulse_analytics_traffic_overview_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_traffic_overview' );
		}

		public function sm_pulse_analytics_ecommerce_overview_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_ecommerce_overview' );
		}

		public function sm_pulse_analytics_campaign_url_tracking_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_campaign_url_tracking' );
		}

		public function sm_pulse_analytics_link_report_page() {
			sm_pulse_analytics_render_admin_header();
			require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/link-report.php';
		}

		public function sm_pulse_analytics_affiliate_links_page() {
			sm_pulse_analytics_render_admin_header();
			include SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/affiliate-link/admin/affiliate-links-report.php';
		}

		public function sm_pulse_analytics_site_performance_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_site_performance' );
		}

		public function sm_pulse_analytics_user_journey_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_user_journey' );
			/** @deprecated 1.0.2 Use sm_pulse_analytics_render_user_journey */
			do_action( 'sm_pulse_analytics_user_journey_report' );
		}

		public function sm_pulse_analytics_render_goals_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_custom_events_report' );
		}

		public function sm_pulse_analytics_search_console_page() {
			sm_pulse_analytics_render_pro_report_page( 'sm_pulse_analytics_render_search_console' );
		}

		public function sm_pulse_analytics_forms_report_page() {
			sm_pulse_analytics_render_admin_header();
			require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/forms-report.php';
		}

		public function ga_settings_page() {
			sm_pulse_analytics_render_admin_header();
			require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/dashboard.php';
		}

		public static function detect_source() {
			$query_args = array();
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $query_args );
			}
			if ( isset( $query_args['utm_source'] ) && ! empty( $query_args['utm_source'] ) ) {
				return sanitize_text_field( wp_unslash( $query_args['utm_source'] ) );
			}

			if ( isset( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );

				if ( strpos( $referrer, 'youtube.com' ) !== false || strpos( $referrer, 'youtu.be' ) !== false ) {
					return 'youtube';
				}
				if ( strpos( $referrer, 'facebook.com' ) !== false || strpos( $referrer, 'fb.com' ) !== false || strpos( $referrer, 'facebook.me' ) !== false ) {
					return 'facebook';
				}
				if ( strpos( $referrer, 'instagram.com' ) !== false || strpos( $referrer, 'ig.me' ) !== false ) {
					return 'instagram';
				}
				if ( strpos( $referrer, 'twitter.com' ) !== false || strpos( $referrer, 't.co' ) !== false || strpos( $referrer, 'x.com' ) !== false ) {
					return 'twitter';
				}
				if ( strpos( $referrer, 'linkedin.com' ) !== false || strpos( $referrer, 'lnkd.in' ) !== false ) {
					return 'linkedin';
				}
				if ( strpos( $referrer, 'whatsapp.com' ) !== false || strpos( $referrer, 'wa.me' ) !== false ) {
					return 'whatsapp';
				}
				if ( strpos( $referrer, 'pinterest.com' ) !== false || strpos( $referrer, 'pin.it' ) !== false ) {
					return 'pinterest';
				}
				if ( strpos( $referrer, 'tiktok.com' ) !== false ) {
					return 'tiktok';
				}
				if ( strpos( $referrer, 'google.' ) !== false ) {
					return 'google';
				}
				if ( strpos( $referrer, home_url() ) !== false ) {
					return 'internal';
				}

				if ( preg_match( '/(l\.facebook\.com|m\.facebook\.com|lm\.facebook\.com|instagram\.com|t\.co|lnkd\.in|youtube\.com)/', $referrer ) ) {
					$parts = wp_parse_url( $referrer );
					return isset( $parts['host'] ) ? $parts['host'] : 'referral';
				}

				return 'referral';
			}
			return 'direct';
		}

		public static function detect_exit_page() {
			if ( function_exists( 'is_cart' ) && is_cart() ) {
				return 'cart';
			}
			if ( function_exists( 'is_checkout' ) && is_checkout() ) {
				return 'checkout';
			}
			if ( function_exists( 'is_product' ) && is_product() ) {
				return 'product';
			}
			if ( function_exists( 'is_shop' ) && is_shop() ) {
				return 'shop';
			}
			if ( is_singular( 'download' ) ) {
				return 'download';
			}
			if ( is_front_page() || is_home() ) {
				return 'home';
			}
			if ( is_singular( 'post' ) ) {
				return 'post';
			}
			if ( is_page() ) {
				return 'page';
			}
			return 'processing';
		}

		public function maybe_update_database() {
			if ( function_exists( '\Sm_Pulse_Analytics\sm_pulse_analytics_maybe_upgrade_db' ) ) {
				\Sm_Pulse_Analytics\sm_pulse_analytics_maybe_upgrade_db();
			}
		}

		public static function get_currency_symbol() {
			if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_Site_Profile' ) ) {
				return \Sm_Pulse_Analytics\PulseAnalytics_Site_Profile::get_currency_symbol();
			}
			return function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
		}

		public function inject_global_loader() {
			if ( ! self::is_plugin_admin_page() ) {
				return;
			}
			?>
			<div id="PulseAnalytics-global-loader" class="PulseAnalytics-loader-overlay">
				<div class="PulseAnalytics-spinner"></div>
				<p class="PulseAnalytics-loader-text">Loading...</p>
			</div>
			<?php
		}

		/**
		 * Check whether the current admin screen belongs to Pulse Analytics.
		 *
		 * @return bool
		 */
		public static function is_plugin_admin_page(): bool {
			if ( ! is_admin() ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			if ( empty( $page ) ) {
				return false;
			}

			$allowed_pages = array(
				'pulse-analytics',
				'sm-pulse-analytics-settings',
				'sm-pulse-analytics-gsc',
				'pulse-analytics-forms',
				'pulse-analytics-forms',
				'pulse-analytics-realtime',
				'pulse-analytics-traffic-overview',
				'pulse-analytics-ecommerce-overview',
				'pulse-analytics-search-console',
				'pulse-analytics-campaign-url-tracking',
				'pulse-analytics-campaigns',
				'pulse-analytics-site-performance',
				'pulse-analytics-user-journey',
				'pulse-analytics-link-report',
				'pulse-analytics-link-report',
				'pulse-analytics-affiliate-links',
				'pulse-analytics-ai-analytics',
				'pulse-analytics-exceptions',
				'pulse-analytics-exceptions',
				'pulse-analytics-media',
				'pulse-analytics-popular-posts',
				'pulse-analytics-popular-posts',
				'pulse-analytics-site-notes',
				'pulse-analytics-amp',
			);

			return in_array( $page, $allowed_pages, true )
				|| 0 === stripos( $page, 'smackcoders-pulse-analytics-for-woocommerce' )
				|| 0 === stripos( $page, 'pulse-analytics' ) || 0 === stripos( $page, 'sm-pulse-analytics' )
				|| 0 === stripos( $page, 'pulse-analytics' )
				|| 0 === stripos( $page, 'sm-pulse-analytics' );
		}

		/**
		 * Remove default WordPress admin footer text on Pulse Analytics plugin menu pages.
		 *
		 * @param string $text Existing footer text.
		 * @return string
		 */
		public static function remove_admin_footer( string $text = '' ): string {
			if ( self::is_plugin_admin_page() ) {
				return '';
			}
			return $text;
		}
	}
}

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/installation/install.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/installation/uninstall.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-ga-reporter.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/class-admin-ui.php';

require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/newsletter.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/helpers.php';
require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/dashboard-ui.php';

GA_Connector::get_instance();
