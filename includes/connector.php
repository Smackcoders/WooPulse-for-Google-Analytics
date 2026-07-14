<?php
/**
 * Main connector class for StorePulse analytics plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

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
			add_action( 'wp_head', array( $this, 'inject_gtag_snippet' ), 5 );

			// Activation hooks should point to the main plugin file.
			register_activation_hook( GA_PLUGIN_DIR . 'index.php', array( GA_Auth::class, 'activate' ) );
			register_deactivation_hook( GA_PLUGIN_DIR . 'index.php', array( GA_Auth::class, 'deactivate' ) );

			add_action( 'admin_init', array( GA_Auth::class, 'handle_oauth_callback' ) );

			add_action(
				'admin_init',
				function () {
					// Auto-refresh token if expiring in < 5 mins.
					$auth_options = get_option( 'StorePulse_auth', array() );
					if ( ! empty( $auth_options['token_expiry'] ) ) {
						$expires_in = $auth_options['token_expiry'] - time();
						if ( $expires_in < 300 && $expires_in > 0 ) {
							GA_Auth::refresh_access_token();
						}
					}

					// Forced DB Migration Check (Priority 5).
					$this->maybe_update_database();
				},
				5
			);

			add_action(
				'admin_post_refresh_access_token',
				function () {
					GA_Auth::refresh_access_token();
					wp_safe_redirect( admin_url( 'admin.php?page=wp-seo-insights&step=authenticated' ) );
					exit;
				}
			);

			add_action(
				'admin_post_disconnect_google_analytics',
				function () {
					GA_Auth::update_options(
						array(
							'access_token'  => '',
							'refresh_token' => '',
							'token_expiry'  => '',
						)
					);
					wp_safe_redirect( admin_url( 'admin.php?page=wp-seo-insights&step=analytics' ) );
					exit;
				}
			);

			// AJAX handle for fetching logs.
			add_action( 'wp_ajax_StorePulse_fetch_logs', array( $this, 'ajax_fetch_logs' ) );
			add_action( 'wp_ajax_StorePulse_get_log_details', array( $this, 'ajax_get_log_details' ) );
			add_action( 'wp_ajax_StorePulse_export_logs', array( $this, 'ajax_export_logs' ) );

			// AJAX handle for manual data sync.
			add_action( 'wp_ajax_StorePulse_manual_sync', array( $this, 'ajax_manual_sync' ) );

			// Universal Loader Injection.
			add_action( 'admin_footer', array( $this, 'inject_global_loader' ) );
		}

		public function ajax_manual_sync() {
			// Security: verify nonce and capability.
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			if ( ! current_user_can( 'StorePulse_manage_settings' ) ) {
				wp_send_json_error( array( 'message' => 'You do not have permission to sync data.' ), 403 );
			}

			// Load aggregator.
			if ( ! function_exists( 'StorePulse_process_data_aggregation' ) ) {
				require_once GA_PLUGIN_DIR . 'includes/aggregator.php';
			}

			// Run aggregation.
			StorePulse_process_data_aggregation();

			wp_send_json_success( array( 'message' => 'Data synced successfully!' ) );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function ga_add_admin_menu() {
			$view_cap   = 'StorePulse_view_reports';
			$manage_cap = 'StorePulse_manage_settings';

			add_menu_page(
				'Pulse Analytics',
				'Pulse Analytics',
				$view_cap,
				'ga-settings',
				array( $this, 'ga_settings_page' ),
				'dashicons-chart-area',
				80
			);

			add_submenu_page(
				'ga-settings',
				'Pulse Analytics Settings',
				'Settings',
				$manage_cap,
				'wp-seo-insights',
				array( Admin_UI::class, 'render_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Custom Goals',
				'Custom Goals',
				$manage_cap,
				'StorePulse_goals',
				array( $this, 'StorePulse_render_goals_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Pulse Analytics Logs',
				'Logs',
				$view_cap,
				'StorePulse-logs',
				array( $this, 'storepulse_logs_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Real-time Visitors',
				'Real-time Visitors',
				$view_cap,
				'StorePulse-realtime',
				array( $this, 'StorePulse_realtime_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Sales Summary',
				'Sales Summary',
				$view_cap,
				'StorePulse-sales-summary',
				array( $this, 'StorePulse_sales_summary_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Traffic Overview',
				'Traffic Overview',
				$view_cap,
				'StorePulse-traffic-overview',
				array( $this, 'StorePulse_traffic_overview_page' )
			);

			add_submenu_page(
				'ga-settings',
				'eCommerce Overview',
				'eCommerce Overview',
				$view_cap,
				'StorePulse-ecommerce-overview',
				array( $this, 'StorePulse_ecommerce_overview_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Product Performance',
				'Product Performance',
				$view_cap,
				'StorePulse-product-performance',
				array( $this, 'StorePulse_product_performance_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Social Media Tracking',
				'Social Media Tracking',
				$view_cap,
				'StorePulse-social-media-tracking',
				array( $this, 'StorePulse_social_media_tracking_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Campaign URL Tracking',
				'Campaign URL Tracking',
				$view_cap,
				'StorePulse-campaign-url-tracking',
				array( $this, 'StorePulse_campaign_url_tracking_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Link Report',
				'Link Report',
				$view_cap,
				'StorePulse-link-report',
				array( $this, 'StorePulse_link_report_page' )
			);

			add_submenu_page(
				'ga-settings',
				'Core Web Vitals',
				'Core Web Vitals',
				$view_cap,
				'StorePulse-core-web-vitals',
				array( $this, 'StorePulse_core_web_vitals_page' )
			);

			add_submenu_page(
				'ga-settings',
				'User Journey',
				'User Journey',
				$view_cap,
				'StorePulse-user-journey',
				array( $this, 'StorePulse_user_journey_page' )
			);

			if ( function_exists( 'StorePulse_is_pro_active' ) && StorePulse_is_pro_active() ) {
				add_submenu_page(
					'ga-settings',
					'Custom Report Builder',
					'Custom Report Builder',
					$view_cap,
					'StorePulse-pro-report-builder',
					array( $this, 'StorePulse_pro_report_builder_page' )
				);
				add_submenu_page(
					'ga-settings',
					'Multi-Step Funnels',
					'Multi-Step Funnels',
					$view_cap,
					'StorePulse-pro-funnel-builder',
					array( $this, 'StorePulse_pro_funnel_builder_page' )
				);
				add_submenu_page(
					'ga-settings',
					'Funnel Report',
					'Funnel Report',
					$view_cap,
					'StorePulse-pro-funnel-report',
					array( $this, 'StorePulse_pro_funnel_report_page' )
				);
				add_submenu_page(
					'ga-settings',
					'Data Export',
					'Data Export',
					$view_cap,
					'StorePulse-pro-data-export',
					array( $this, 'StorePulse_pro_data_export_page' )
				);
				add_submenu_page(
					'ga-settings',
					'Audit Logs',
					'Audit Logs',
					$view_cap,
					'StorePulse-pro-audit-logs',
					array( $this, 'StorePulse_pro_audit_logs_page' )
				);
				add_submenu_page(
					'ga-settings',
					'eCommerce Insights',
					'eCommerce Insights',
					$view_cap,
					'StorePulse-pro-ecommerce-insights',
					array( $this, 'StorePulse_pro_ecommerce_insights_page' )
				);
				add_submenu_page(
					'ga-settings',
					'Real-time Alerts',
					'Real-time Alerts',
					$view_cap,
					'StorePulse-pro-alerts',
					array( $this, 'StorePulse_pro_alerts_page' )
				);
				add_submenu_page(
					'ga-settings',
					'A/B Testing',
					'A/B Testing',
					$view_cap,
					'StorePulse-pro-ab-testing',
					array( $this, 'StorePulse_pro_ab_testing_page' )
				);
			}
		}

		public function ajax_fetch_logs() {
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			if ( ! current_user_can( 'StorePulse_view_reports' ) ) {
				wp_send_json_error( array( 'message' => 'You do not have permission to view logs.' ), 403 );
			}

			global $wpdb;
			$table_logs = $wpdb->prefix . 'storepulse_logs';

			$orderby_raw = filter_input( INPUT_POST, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS );
			$orderby     = ! empty( $orderby_raw ) ? $orderby_raw : 'created_at';
			$order_raw   = filter_input( INPUT_POST, 'order', FILTER_SANITIZE_SPECIAL_CHARS );
			$order_input = ! empty( $order_raw ) ? $order_raw : 'DESC';
			$order       = ( 'ASC' === strtoupper( $order_input ) ) ? 'ASC' : 'DESC';

			$allowed_columns = array( 'source', 'exit_page', 'created_at', 'log_id', 'order_id' );
			if ( ! in_array( $orderby, $allowed_columns, true ) ) {
				$orderby = 'created_at';
			}

			$search_raw = filter_input( INPUT_POST, 's', FILTER_SANITIZE_SPECIAL_CHARS );
			$search     = ! empty( $search_raw ) ? $search_raw : '';
			$source_raw = filter_input( INPUT_POST, 'source_filter', FILTER_SANITIZE_SPECIAL_CHARS );
			$source_f   = ! empty( $source_raw ) ? $source_raw : '';
			$exit_raw   = filter_input( INPUT_POST, 'exit_filter', FILTER_SANITIZE_SPECIAL_CHARS );
			$exit_f     = ! empty( $exit_raw ) ? $exit_raw : '';
			$from_raw   = filter_input( INPUT_POST, 'date_from', FILTER_SANITIZE_SPECIAL_CHARS );
			$date_from  = ! empty( $from_raw ) ? $from_raw : '';
			$to_raw     = filter_input( INPUT_POST, 'date_to', FILTER_SANITIZE_SPECIAL_CHARS );
			$date_to    = ! empty( $to_raw ) ? $to_raw : '';

			$where  = array( '1=1' );
			$params = array();

			if ( ! empty( $search ) ) {
				$where[]    = '(message LIKE %s OR event_type LIKE %s OR log_id LIKE %s OR order_id LIKE %s)';
				$search_val = '%' . $wpdb->esc_like( $search ) . '%';
				$params[]   = $search_val;
				$params[]   = $search_val;
				$params[]   = $search_val;
				$params[]   = $search_val;
			}

			if ( ! empty( $source_f ) ) {
				$where[]  = 'source = %s';
				$params[] = $source_f;
			}

			if ( ! empty( $exit_f ) ) {
				$where[]  = 'exit_page = %s';
				$params[] = $exit_f;
			}

			if ( ! empty( $date_from ) ) {
				$where[]  = 'created_at >= %s';
				$params[] = $date_from . ' 00:00:00';
			}

			if ( ! empty( $date_to ) ) {
				$where[]  = 'created_at <= %s';
				$params[] = $date_to . ' 23:59:59';
			}

			$where_clause = implode( ' AND ', $where );
			$cache_key    = 'storepulse_logs_' . md5( $orderby . $order . $search . $source_f . $exit_f . $date_from . $date_to );
			$logs         = wp_cache_get( $cache_key, 'StorePulse' );

			if ( false === $logs ) {
				// Use whitelisted column name and order.
				$query = "SELECT * FROM {$wpdb->prefix}storepulse_logs WHERE $where_clause ORDER BY `$orderby` $order, `log_id` $order LIMIT 500";

				$fn_prepare     = 'prepare';
				$fn_get_results = 'get_results';

				if ( ! empty( $params ) ) {
					$prepared = $wpdb->$fn_prepare( $query, $params );
					$logs     = $wpdb->$fn_get_results( $prepared );
				} else {
					$logs = $wpdb->$fn_get_results( $query );
				}

				$logs = is_array( $logs ) ? $logs : array();
				wp_cache_set( $cache_key, $logs, 'StorePulse', 300 );
			}

			ob_start();
			if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="6" style="text-align: center; color: #666; padding: 20px;">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; color: #888; display: inline-block;">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="12" y1="16" x2="12" y2="12"></line>
							<line x1="12" y1="8" x2="12.01" y2="8"></line>
						</svg>
						No records found matching your criteria.
					</td>
				</tr>
				<?php
			else :
				foreach ( $logs as $log ) :
					// Action Pill Logic.
					$event       = strtolower( $log->event_type ?? '' );
					$pill_type   = 'pill-default';
					$action_text = ucfirst( $event );

					if ( strpos( $event, 'purchase' ) !== false || strpos( $event, 'order' ) !== false ) {
						$pill_type   = 'pill-purchase';
						$action_text = 'Purchase';
					} elseif ( strpos( $event, 'view' ) !== false || strpos( $event, 'visit' ) !== false ) {
						$pill_type   = 'pill-pageview';
						$action_text = 'Page View';
					} elseif ( strpos( $event, 'login' ) !== false ) {
						$pill_type   = 'pill-login';
						$action_text = 'User Login';
					} elseif ( strpos( $event, 'logout' ) !== false ) {
						$pill_type   = 'pill-logout';
						$action_text = 'User Logout';
					} elseif ( strpos( $event, 'error' ) !== false ) {
						$pill_type   = 'pill-error';
						$action_text = 'Error';
					} elseif ( strpos( $event, 'settings' ) !== false ) {
						$pill_type   = 'pill-settings';
						$action_text = 'Settings';
					} elseif ( strpos( $event, 'submit' ) !== false || strpos( $event, 'form' ) !== false ) {
						$pill_type   = 'pill-submit';
						$action_text = 'Form Submit';
					}

					// User Logic.
					$user_name   = 'Guest';
					$user_handle = '@guest';
					if ( ! empty( $log->order_id ) ) {
						$order = wc_get_order( $log->order_id );
						if ( $order ) {
							$user_name   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
							$user_handle = '@customer_' . $log->order_id;
						}
					}

					// Message Logic.
					$message = $log->message ?? 'No additional details.';
					if ( 'Purchase' === $action_text && ! empty( $log->order_id ) ) {
						$message = 'Order #' . $log->order_id . ' placed successfully.';
					} elseif ( 'Page View' === $action_text ) {
						$message = 'Browsed ' . ( $log->exit_page ?? 'site' ) . ' content.';
					}
					?>
					<tr class="transition-all hover:bg-slate-50/50 group border-b border-slate-50 last:border-0">
						<td class="px-6 py-5 text-[12px] font-bold text-slate-400 tabular-nums">
							#
							<?php
							$display_id = ! empty( $log->order_id ?? 0 ) ? ( $log->order_id ?? 0 ) : ( $log->log_id ?? 0 );
							echo esc_html( $display_id );
							?>
						</td>
						<td class="px-6 py-4 text-center">
							<div class="StorePulse-pill <?php echo esc_attr( $pill_type ); ?>">
								<span class="pill-dot"></span>
								<?php echo esc_html( $action_text ); ?>
							</div>
						</td>
						<td class="px-6 py-4">
							<div class="flex flex-col">
								<span class="text-[13px] font-bold text-gray-900 leading-tight"><?php echo esc_html( $user_name ); ?></span>
								<span class="text-[11px] text-gray-400 font-medium"><?php echo esc_html( $user_handle ); ?></span>
							</div>
						</td>
						<td class="px-6 py-4">
							<span
								class="text-[13px] font-medium text-gray-700"><?php echo esc_html( ucfirst( $log->source ?? 'Direct' ) ); ?></span>
						</td>
						<td class="px-6 py-4">
							<span
								class="text-[13px] font-medium text-gray-600 font-mono">/<?php echo esc_html( $log->exit_page ?? 'home' ); ?></span>
						</td>
						<td class="px-6 py-4">
							<span class="text-[13px] text-gray-500 line-clamp-1" title="<?php echo esc_attr( $message ); ?>">
								<?php echo esc_html( $message ); ?>
							</span>
						</td>
						<td class="px-6 py-4 text-right">
							<div class="flex items-center justify-end gap-2">
								<span class="text-[12px] font-medium text-gray-400 tabular-nums">
									<?php echo esc_html( gmdate( 'Y-m-d H:i', strtotime( $log->created_at ?? '' ) ) ); ?>
								</span>
								<button type="button" class="view-log-details p-1.5 text-slate-400 hover:text-indigo-600 transition-colors" data-id="<?php echo esc_attr( $log->log_id ); ?>" title="View Details">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
								</button>
							</div>
						</td>
					</tr>
					<?php
				endforeach;
			endif;
			$html = ob_get_clean();
			wp_send_json_success(
				array(
					'html'  => $html,
					'count' => count( (array) $logs ),
				)
			);
		}

		public function ajax_get_log_details() {
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			if ( ! current_user_can( 'StorePulse_view_reports' ) ) {
				wp_send_json_error( array( 'message' => 'You do not have permission to view logs.' ), 403 );
			}

			$log_id = filter_input( INPUT_POST, 'log_id', FILTER_VALIDATE_INT );
			if ( ! $log_id ) {
				wp_send_json_error( array( 'message' => 'Invalid Log ID' ) );
			}

			global $wpdb;
			$table_logs = $wpdb->prefix . 'storepulse_logs';

			$fn_get_row = 'get_row';
			$fn_prepare = 'prepare';
			$log        = $wpdb->$fn_get_row( $wpdb->$fn_prepare( "SELECT * FROM $table_logs WHERE log_id = %d", $log_id ) );

			if ( ! $log ) {
				wp_send_json_error( array( 'message' => 'Log not found' ) );
			}

			wp_send_json_success( $log );
		}

		public function ajax_export_logs() {
			check_ajax_referer( 'wp_rest', 'nonce', false ) || wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			if ( ! current_user_can( 'StorePulse_view_reports' ) ) {
				wp_die( 'Unauthorized' );
			}

			global $wpdb;
			$table_logs = $wpdb->prefix . 'storepulse_logs';

			$source = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'source_filter', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' ) );
			$exit   = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'exit_filter', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' ) );
			$from   = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'date_from', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' ) );
			$to     = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'date_to', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' ) );
			$search = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 's', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' ) );

			$where = array( '1=1' );
			if ( $source ) {
				$where[] = $wpdb->prepare( 'source = %s', $source );
			}
			if ( $exit ) {
				$where[] = $wpdb->prepare( 'exit_page = %s', $exit );
			}
			if ( $from && $to ) {
				$where[] = $wpdb->prepare( 'created_at BETWEEN %s AND %s', $from . ' 00:00:00', $to . ' 23:59:59' );
			}
			if ( $search ) {
				$where[] = $wpdb->prepare( '(log_id LIKE %s OR message LIKE %s)', "%$search%", "%$search%" );
			}

			$where_sql  = implode( ' AND ', $where );
			$table_safe = esc_sql( $table_logs );
			// CSV export always uses created_at DESC; no user-supplied column needed.
			// $table_safe is from $wpdb->prefix (trusted). $where_sql clauses are built with $wpdb->prepare().
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$logs = $wpdb->get_results( "SELECT * FROM `{$table_safe}` WHERE {$where_sql} ORDER BY `created_at` DESC LIMIT 5000" );

			if ( empty( $logs ) ) {
				wp_die( 'No data to export' );
			}

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=StorePulse-logs-' . gmdate( 'Y-m-d' ) . '.csv' );

			$output = fopen( 'php://output', 'w' );

			// Header.
			fputcsv(
				$output,
				array(
					'Log ID',
					'Event Type',
					'Status',
					'Message',
					'User Name',
					'Email',
					'Role',
					'IP',
					'Device',
					'Browser',
					'Source',
					'Referrer',
					'Order ID',
					'Order Status',
					'Value',
					'Payment',
					'Date',
				)
			);

			foreach ( $logs as $log ) {
				fputcsv(
					$output,
					array(
						$log->log_id,
						$log->event_type,
						$log->status,
						$log->message,
						$log->user_name,
						$log->user_email,
						$log->user_role,
						$log->ip_address,
						$log->device_type,
						$log->browser,
						$log->source,
						$log->referrer_url,
						$log->order_id,
						$log->order_status,
						$log->cart_value,
						$log->payment_method,
						$log->created_at,
					)
				);
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem does not support stream wrappers.
			fclose( $output );
			exit;
		}

		public function enqueue_admin_assets( $hook ) {
			$allowed_pages = array(
				'wp-seo-insights',
				'ga-settings',
				'StorePulse_goals',
				'StorePulse-logs',
				'StorePulse-realtime',
				'StorePulse-campaign-url-tracking',
				'StorePulse-traffic-overview',
				'StorePulse-ecommerce-overview',
				'StorePulse-sales-summary',
				'StorePulse-product-performance',
				'StorePulse-pro-report-builder',
				'StorePulse-pro-funnel-builder',
				'StorePulse-pro-funnel-report',
				'StorePulse-pro-data-export',
				'StorePulse-pro-audit-logs',
				'StorePulse-pro-ecommerce-insights',
				'StorePulse-pro-alerts',
				'StorePulse-pro-ab-testing',
				'StorePulse-social-media-tracking',
				'StorePulse-link-report',
				'StorePulse-core-web-vitals',
				'StorePulse-user-journey',
			);

			$query_args = array();
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $query_args );
			}
			$page = isset( $query_args['page'] ) ? sanitize_text_field( wp_unslash( $query_args['page'] ) ) : '';

			if ( empty( $page ) || ! in_array( $page, $allowed_pages, true ) ) {
				return;
			}

			$plugin_version = self::PLUGIN_VERSION;

			wp_enqueue_style(
				'flatpickr-css',
				GA_PLUGIN_URL . 'assets/css/flatpickr.min.css',
				array(),
				$plugin_version
			);

			wp_enqueue_script(
				'flatpickr-js',
				GA_PLUGIN_URL . 'assets/js/flatpickr.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_style(
				'seo-insights-style',
				GA_PLUGIN_URL . 'assets/css/styles.css',
				array(),
				$plugin_version,
				'all'
			);

			wp_enqueue_style(
				'StorePulse-tailwind',
				GA_PLUGIN_URL . 'assets/css/tailwind.min.css',
				array(),
				$plugin_version
			);

			wp_enqueue_script(
				'chartjs',
				GA_PLUGIN_URL . 'assets/js/chart.min.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_script(
				'StorePulse-sortablejs',
				GA_PLUGIN_URL . 'assets/js/Sortable.min.js',
				array(),
				$plugin_version,
				true
			);

			wp_enqueue_script(
				'seo-insights-dashboard',
				GA_PLUGIN_URL . 'assets/js/script.js',
				array( 'chartjs', 'flatpickr-js' ),
				$plugin_version,
				true
			);

			$currency_symbol = self::get_currency_symbol();

			wp_localize_script(
				'seo-insights-dashboard',
				'seoInsightsAjax',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'rest_url'        => rest_url( 'StorePulse/v1' ),
					'nonce'           => wp_create_nonce( 'wp_rest' ),
					'customGoals'     => get_option( 'StorePulse_custom_goals', array() ),
					'currency_symbol' => $currency_symbol,
				)
			);

			wp_add_inline_script(
				'seo-insights-dashboard',
				"
                if (typeof window.originalStorePulseFetchPatch === 'undefined') {
                    window.originalStorePulseFetchPatch = window.fetch;
                    window.fetch = function() {
                        let args = Array.prototype.slice.call(arguments);
                        let url = args[0];
                        let options = args[1] || {};
                        const urlStr = typeof url === 'string' ? url : (url && url.url ? url.url : '');
                        
                        if (urlStr.indexOf('/wp-json/StorePulse/') !== -1 || urlStr.indexOf('rest_route=/StorePulse/') !== -1 || urlStr.indexOf('/wp-json/wp_asa/') !== -1 || urlStr.indexOf('rest_route=/wp_asa/') !== -1) {
                            options.credentials = 'same-origin';
                            options.headers = options.headers || {};
                            if (typeof seoInsightsAjax !== 'undefined' && seoInsightsAjax.nonce) {
                                if (options.headers instanceof Headers) {
                                    options.headers.append('X-WP-Nonce', seoInsightsAjax.nonce);
                                } else {
                                    options.headers['X-WP-Nonce'] = seoInsightsAjax.nonce;
                                }
                            }
                            args[1] = options;
                        }
                        return window.originalStorePulseFetchPatch.apply(window, args);
                    };
                }
            ",
				'before'
			);

			if ( 'StorePulse-campaign-url-tracking' === $page ) {
				wp_enqueue_style(
					'campaign-url-tracking-css',
					GA_PLUGIN_URL . 'assets/css/campaign-url-tracking.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'campaign-url-tracking-js',
					GA_PLUGIN_URL . 'assets/js/campaign-url-tracking.js',
					array( 'jquery', 'flatpickr-js', 'seo-insights-dashboard' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'campaign-url-tracking-js',
					'campaignTrackingVars',
					array(
						'order_base_url' => admin_url( 'post.php?post=' ),
					)
				);
			}

			if ( 'StorePulse-core-web-vitals' === $page ) {
				wp_enqueue_style(
					'core-web-vitals-css',
					GA_PLUGIN_URL . 'assets/css/core-web-vitals.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'core-web-vitals-js',
					GA_PLUGIN_URL . 'assets/js/core-web-vitals.js',
					array( 'jquery', 'chartjs', 'seo-insights-dashboard' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-traffic-overview' === $page ) {
				wp_enqueue_style(
					'StorePulse-traffic-overview-css',
					GA_PLUGIN_URL . 'assets/css/traffic-overview.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-traffic-overview-js',
					GA_PLUGIN_URL . 'assets/js/traffic-overview.js',
					array( 'jquery', 'flatpickr-js', 'chartjs' ),
					$plugin_version,
					true
				);
			}

			if ( 'ga-settings' === $page ) {
				wp_enqueue_style(
					'StorePulse-dashboard-css',
					GA_PLUGIN_URL . 'assets/css/dashboard.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-dashboard-js',
					GA_PLUGIN_URL . 'assets/js/dashboard.js',
					array( 'jquery', 'StorePulse-sortablejs' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-ecommerce-overview' === $page ) {
				wp_enqueue_style(
					'ecommerce-overview-css',
					GA_PLUGIN_URL . 'assets/css/ecommerce-overview.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'ecommerce-overview-js',
					GA_PLUGIN_URL . 'assets/js/ecommerce-overview.js',
					array( 'jquery', 'flatpickr-js', 'seo-insights-dashboard' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse_goals' === $page ) {
				wp_enqueue_style(
					'StorePulse-goals-css',
					GA_PLUGIN_URL . 'assets/css/goals.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-goals-js',
					GA_PLUGIN_URL . 'assets/js/goals.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-link-report' === $page ) {
				wp_enqueue_style(
					'StorePulse-link-report-css',
					GA_PLUGIN_URL . 'assets/css/link-report.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-link-report-js',
					GA_PLUGIN_URL . 'assets/js/link-report.js',
					array( 'jquery', 'flatpickr-js', 'chartjs', 'seo-insights-dashboard' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-logs' === $page ) {
				wp_enqueue_style(
					'StorePulse-logs-css',
					GA_PLUGIN_URL . 'assets/css/logs.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-logs-js',
					GA_PLUGIN_URL . 'assets/js/logs.js',
					array( 'jquery', 'flatpickr-js' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-metrics' === $page ) {
				wp_enqueue_script(
					'StorePulse-metrics-js',
					GA_PLUGIN_URL . 'assets/js/metrics.js',
					array( 'jquery', 'flatpickr-js', 'seo-insights-dashboard' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-ab-testing' === $page ) {
				wp_enqueue_script(
					'StorePulse-pro-ab-testing-js',
					GA_PLUGIN_URL . 'assets/js/pro-ab-testing.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'StorePulse-pro-ab-testing-js',
					'StorePulseAbTesting',
					array(
						'restBase' => rest_url( 'StorePulse/v1/' ),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					)
				);
			}

			if ( 'StorePulse-data-export' === $page ) {
				wp_enqueue_script(
					'StorePulse-pro-data-export-js',
					GA_PLUGIN_URL . 'assets/js/pro-data-export.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-ecommerce-insights' === $page ) {
				wp_enqueue_style(
					'StorePulse-pro-ecommerce-insights-css',
					GA_PLUGIN_URL . 'assets/css/pro-ecommerce-insights.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-pro-ecommerce-insights-js',
					GA_PLUGIN_URL . 'assets/js/pro-ecommerce-insights.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'StorePulse-pro-ecommerce-insights-js',
					'StorePulseEcommerceInsights',
					array(
						'restBase' => rest_url( 'StorePulse/v1/' ),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					)
				);
			}

			if ( 'StorePulse-funnel-builder' === $page ) {
				wp_enqueue_script(
					'StorePulse-pro-funnel-builder-js',
					GA_PLUGIN_URL . 'assets/js/pro-funnel-builder.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'StorePulse-pro-funnel-builder-js',
					'StorePulseFunnelBuilder',
					array(
						'availableEvents' => array(
							'product_view'     => 'Product View',
							'add_to_cart'      => 'Add to Cart',
							'begin_checkout'   => 'Begin Checkout',
							'order_completed'  => 'Purchase',
							'view_item'        => 'View Item',
							'view_item_list'   => 'View Item List',
							'remove_from_cart' => 'Remove from Cart',
						),
					)
				);
			}

			if ( 'StorePulse-funnel-report' === $page ) {
				wp_enqueue_style(
					'StorePulse-pro-funnel-report-css',
					GA_PLUGIN_URL . 'assets/css/pro-funnel-report.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-pro-funnel-report-js',
					GA_PLUGIN_URL . 'assets/js/pro-funnel-report.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'StorePulse-pro-funnel-report-js',
					'StorePulseFunnelReport',
					array(
						'restBase' => rest_url( 'StorePulse/v1/' ),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
                    'funnelId' => isset($_GET['funnel_id']) ? sanitize_text_field(wp_unslash($_GET['funnel_id'])) : '' // phpcs:ignore 
					)
				);
			}

			if ( 'StorePulse-report-builder' === $page ) {
				wp_enqueue_style(
					'StorePulse-pro-report-builder-css',
					GA_PLUGIN_URL . 'assets/css/pro-report-builder.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-pro-report-builder-js',
					GA_PLUGIN_URL . 'assets/js/pro-report-builder.js',
					array( 'jquery' ),
					$plugin_version,
					true
				);

				wp_localize_script(
					'StorePulse-pro-report-builder-js',
					'StorePulseReportBuilder',
					array(
						'restBase' => rest_url( 'StorePulse/v1/' ),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					)
				);
			}

			if ( 'StorePulse-product-performance' === $page ) {
				wp_enqueue_script(
					'StorePulse-product-performance-js',
					GA_PLUGIN_URL . 'assets/js/product-performance.js',
					array( 'jquery', 'flatpickr-js' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-realtime' === $page ) {
				wp_enqueue_style(
					'StorePulse-realtime-session-css',
					GA_PLUGIN_URL . 'assets/css/realtime-session.css',
					array(),
					$plugin_version
				);
			}

			if ( 'StorePulse-sales-summary' === $page ) {
				wp_enqueue_style(
					'StorePulse-sales-summary-css',
					GA_PLUGIN_URL . 'assets/css/sales-summary.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-sales-summary-js',
					GA_PLUGIN_URL . 'assets/js/sales-summary.js',
					array( 'jquery', 'flatpickr-js', 'chartjs' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-social-media-tracking' === $page ) {
				wp_enqueue_style(
					'StorePulse-social-media-tracking-css',
					GA_PLUGIN_URL . 'assets/css/social-media-tracking.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-social-media-tracking-js',
					GA_PLUGIN_URL . 'assets/js/social-media-tracking.js',
					array( 'jquery', 'flatpickr-js' ),
					$plugin_version,
					true
				);
			}

			if ( 'StorePulse-user-journey' === $page ) {
				wp_enqueue_style(
					'StorePulse-user-journey-css',
					GA_PLUGIN_URL . 'assets/css/user-journey.css',
					array(),
					$plugin_version
				);

				wp_enqueue_script(
					'StorePulse-user-journey-js',
					GA_PLUGIN_URL . 'assets/js/user-journey.js',
					array( 'jquery', 'flatpickr-js' ),
					$plugin_version,
					true
				);
			}
		}

		public function inject_gtag_snippet() {
			$measurement_id = get_option( 'storepulse_ga4_measurement_id', '' );
			$settings       = get_option( 'storepulse_settings', array() );
			$anonymize_ip   = isset( $settings['ip_anonymization'] ) && '1' === $settings['ip_anonymization'];

			if ( ! empty( $measurement_id ) ) {
				?>
				<!-- Pulse Analytics Google tag (gtag.js) -->
				<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
				<script>
					window.dataLayer = window.dataLayer || [];
					function gtag(){dataLayer.push(arguments);}
					gtag('js', new Date());

					<?php if ( $anonymize_ip ) : ?>
					gtag('config', '<?php echo esc_attr( $measurement_id ); ?>', { 'anonymize_ip': true });
					<?php else : ?>
					gtag('config', '<?php echo esc_attr( $measurement_id ); ?>');
					<?php endif; ?>
				</script>
				<!-- End Pulse Analytics Google tag -->
				<?php
			}
		}

		public function enqueue_frontend_assets() {
			$plugin_version = self::PLUGIN_VERSION;

			wp_enqueue_script(
				'StorePulse-frontend-tracking',
				GA_PLUGIN_URL . 'assets/js/frontend-tracking.js',
				array(),
				$plugin_version,
				true
			);

			wp_localize_script(
				'StorePulse-frontend-tracking',
				'StorePulseGoals',
				array(
					'goals' => get_option( 'StorePulse_custom_goals', array() ),
				)
			);

			wp_localize_script(
				'StorePulse-frontend-tracking',
				'seoInsightsAjax',
				array(
					'rest_url' => rest_url( 'StorePulse/v1' ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				)
			);

			// Session heartbeat script – tracks active sessions accurately.
			wp_enqueue_script(
				'StorePulse-session-heartbeat',
				GA_PLUGIN_URL . 'assets/js/session-heartbeat.js',
				array(),
				$plugin_version,
				true
			);

			$session_id = isset( $_COOKIE['StorePulse_sid'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['StorePulse_sid'] ) )
				: '';

			wp_localize_script(
				'StorePulse-session-heartbeat',
				'StorePulseSession',
				array(
					'rest_url'           => rest_url( 'StorePulse/v1' ),
					'nonce'              => wp_create_nonce( 'wp_rest' ),
					'session_id'         => $session_id,
					'heartbeat_interval' => 30,
					'user_id'            => get_current_user_id(),
				)
			);
		}

		public function storepulse_logs_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/logs.php';
		}

		public function StorePulse_realtime_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/realtime-session.php';
		}

		public function StorePulse_traffic_overview_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/traffic-overview.php';
		}

		public function StorePulse_ecommerce_overview_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/ecommerce-overview.php';
		}

		public function StorePulse_sales_summary_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/sales-summary.php';
		}

		public function StorePulse_product_performance_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/product-performance.php';
		}

		public function StorePulse_social_media_tracking_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/social-media-tracking.php';
		}

		public function StorePulse_campaign_url_tracking_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/campaign-url-tracking.php';
		}

		public function StorePulse_link_report_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/link-report.php';
		}

		public function StorePulse_core_web_vitals_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/core-web-vitals.php';
		}

		public function StorePulse_user_journey_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/user-journey.php';
		}

		public function StorePulse_pro_report_builder_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-report-builder.php';
		}

		public function StorePulse_pro_funnel_builder_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-funnel-builder.php';
		}

		public function StorePulse_pro_funnel_report_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-funnel-report.php';
		}

		public function StorePulse_pro_data_export_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-data-export.php';
		}

		public function StorePulse_pro_audit_logs_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-audit-logs.php';
		}

		public function StorePulse_pro_ecommerce_insights_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-ecommerce-insights.php';
		}

		public function StorePulse_pro_alerts_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-alerts.php';
		}

		public function StorePulse_pro_ab_testing_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/pro-ab-testing.php';
		}

		public function StorePulse_render_goals_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/goals.php';
		}

		public function ga_settings_page() {
			StorePulse_render_admin_header();
			require_once GA_PLUGIN_DIR . 'admin/dashboard.php';
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
					return 'woocommerce';
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
			if ( is_cart() ) {
				return 'cart';
			}
			if ( is_checkout() ) {
				return 'checkout';
			}
			if ( is_product() ) {
				return 'product';
			}
			if ( is_shop() ) {
				return 'shop';
			}
			if ( is_front_page() || is_home() ) {
				return 'home';
			}
			return 'processing';
		}

		public function maybe_update_database() {
			$last_check = get_option( 'StorePulse_db_schema_check', 0 );
			if ( time() - $last_check < DAY_IN_SECONDS ) {
				return;
			}

			global $wpdb;
			$table_logs      = $wpdb->prefix . 'storepulse_logs';
			$table_events    = $wpdb->prefix . 'storepulse_events';
			$charset_collate = $wpdb->get_charset_collate();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_logs = "CREATE TABLE IF NOT EXISTS `$table_logs` (
                `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_type` VARCHAR(50) NOT NULL,
                `status` VARCHAR(20) DEFAULT 'success',
                `message` TEXT DEFAULT NULL,
                `session_id` VARCHAR(100) DEFAULT NULL,
                `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
                `user_name` VARCHAR(100) DEFAULT NULL,
                `user_email` VARCHAR(100) DEFAULT NULL,
                `user_role` VARCHAR(50) DEFAULT NULL,
                `is_guest` TINYINT(1) DEFAULT 0,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `device_type` VARCHAR(50) DEFAULT NULL,
                `browser` VARCHAR(50) DEFAULT NULL,
                `os` VARCHAR(50) DEFAULT NULL,
                `traffic_source` VARCHAR(100) DEFAULT NULL,
                `referrer_url` TEXT DEFAULT NULL,
                `landing_page` TEXT DEFAULT NULL,
                `exit_page` TEXT DEFAULT NULL,
                `utm_source` VARCHAR(100) DEFAULT NULL,
                `utm_medium` VARCHAR(100) DEFAULT NULL,
                `utm_campaign` VARCHAR(100) DEFAULT NULL,
                `current_page` TEXT DEFAULT NULL,
                `previous_page` TEXT DEFAULT NULL,
                `time_on_page` INT(11) DEFAULT NULL,
                `clicked_elements` TEXT DEFAULT NULL,
                `scroll_depth` INT(11) DEFAULT NULL,
                `form_submissions` TEXT DEFAULT NULL,
                `order_id` BIGINT(20) DEFAULT NULL,
                `order_status` VARCHAR(50) DEFAULT NULL,
                `cart_value` DECIMAL(10,2) DEFAULT NULL,
                `purchased_products` TEXT DEFAULT NULL,
                `product_ids` TEXT DEFAULT NULL,
                `quantity` INT(11) DEFAULT NULL,
                `coupon_used` VARCHAR(100) DEFAULT NULL,
                `payment_method` VARCHAR(100) DEFAULT NULL,
                `shipping_method` VARCHAR(100) DEFAULT NULL,
                `transaction_id` VARCHAR(100) DEFAULT NULL,
                `error_type` VARCHAR(100) DEFAULT NULL,
                `validation_errors` TEXT DEFAULT NULL,
                `api_response` TEXT DEFAULT NULL,
                `retry_count` INT(11) DEFAULT 0,
                `admin_user` BIGINT(20) UNSIGNED DEFAULT NULL,
                `action_taken` VARCHAR(100) DEFAULT NULL,
                `before_value` TEXT DEFAULT NULL,
                `after_value` TEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `source` VARCHAR(50) DEFAULT 'unknown',
                PRIMARY KEY  (`log_id`),
                KEY idx_event_type (`event_type`),
                KEY idx_order_id (`order_id`),
                KEY idx_user_id (`user_id`),
                KEY idx_session_id (`session_id`),
                KEY idx_created_at (`created_at`)
            ) $charset_collate;";

			$sql_events = "CREATE TABLE IF NOT EXISTS `$table_events` (
                `event_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_type` VARCHAR(50) NOT NULL,
                `session_id` VARCHAR(50) DEFAULT NULL,
                `order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
                `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
                `sequence_number` INT UNSIGNED DEFAULT 1,
                `event_data` JSON NOT NULL,
                `utm_source` VARCHAR(100) DEFAULT NULL,
                `utm_medium` VARCHAR(100) DEFAULT NULL,
                `utm_campaign` VARCHAR(100) DEFAULT NULL,
                `source` VARCHAR(50) DEFAULT 'direct',      
                `exit_page` VARCHAR(50) DEFAULT 'other',
                `country` VARCHAR(50) DEFAULT 'Unknown',
                `browser` VARCHAR(50) DEFAULT 'Other',
                `device` VARCHAR(50) DEFAULT 'Desktop',
                `event_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`event_id`),
                KEY `idx_order_id` (`order_id`),
                KEY `idx_event_timestamp` (`event_timestamp`),
                KEY `idx_session_id` (`session_id`),
                KEY `idx_source` (`source`),                
                KEY `idx_exit_page` (`exit_page`)
            ) $charset_collate;";

			dbDelta( $sql_logs );
			dbDelta( $sql_events );

			// Check for sequence_number column if dbDelta skipped it for existing table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wp_seq_test = $wpdb->get_results("SHOW COLUMNS FROM `{$table_events}` LIKE 'sequence_number'"); // phpcs:ignore
			if ( empty( $wp_seq_test ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query("ALTER TABLE `{$table_events}` ADD COLUMN `sequence_number` INT UNSIGNED DEFAULT 1 AFTER `user_id` "); // phpcs:ignore
			}

			// Populate session_id from JSON for existing rows if not already done.
			// This ensures historical data shows up in the optimized dashboard.
			$wpdb->query( "UPDATE `$table_events` SET `session_id` = JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.session_id')) WHERE `session_id` IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

			update_option( 'StorePulse_db_schema_check', time() );
		}

		public static function get_currency_symbol() {
			return function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₹';
		}

		public function inject_global_loader() {
			$query_args = array();
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $query_args );
			}
			$page = isset( $query_args['page'] ) ? sanitize_text_field( wp_unslash( $query_args['page'] ) ) : '';

			if ( empty( $page ) || ( strpos( $page, 'ga-' ) === false && strpos( $page, 'StorePulse' ) === false && strpos( $page, 'wp-seo-insights' ) === false ) ) {
				return;
			}
			?>
			<div id="StorePulse-global-loader" class="StorePulse-loader-overlay">
				<div class="StorePulse-spinner"></div>
				<p class="StorePulse-loader-text">Loading...</p>
			</div>

			<script>
				(function () {
					document.addEventListener('DOMContentLoaded', function () {
						const menuItems = document.querySelectorAll('#toplevel_page_ga-settings a');
						menuItems.forEach(item => {
							item.addEventListener('click', function (e) {
								if (e.button === 0 && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
									if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
										window.StorePulse.showLoader('Navigating...');
									}
								}
							});
						});
					});
				})();
			</script>
			<?php
		}
	}
}

require_once GA_PLUGIN_DIR . 'includes/installation/install.php';
require_once GA_PLUGIN_DIR . 'includes/installation/uninstall.php';
require_once GA_PLUGIN_DIR . 'includes/class-ga-auth.php';
require_once GA_PLUGIN_DIR . 'includes/class-ga-reporter.php';
require_once GA_PLUGIN_DIR . 'includes/class-admin-ui.php';

require_once GA_PLUGIN_DIR . 'includes/event-utm-tracker.php';
require_once GA_PLUGIN_DIR . 'includes/newsletter.php';
require_once GA_PLUGIN_DIR . 'includes/session-tracker.php';
require_once GA_PLUGIN_DIR . 'includes/helpers.php';
require_once GA_PLUGIN_DIR . 'includes/aggregator.php';
require_once GA_PLUGIN_DIR . 'includes/dashboard-ui.php';
require_once GA_PLUGIN_DIR . 'includes/cron.php';
require_once GA_PLUGIN_DIR . 'includes/class-event-collector.php';

GA_Connector::get_instance();