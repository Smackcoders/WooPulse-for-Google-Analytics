<?php
/**
 * MonsterInsights → Pulse Analytics migration (#51).
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

class PulseAnalytics_MonsterInsights_Migration {

	const STATUS_OPTION  = 'sm_pulse_analytics_migration_monsterinsights';
	const PROMPT_OPTION  = 'sm_pulse_analytics_migration_mi_show_prompt';
	const SNAPSHOT_TTL   = 315360000; // ~10 years for imported historical aggregates.
	const PROVIDER       = 'monsterinsights-import';

	/** @var bool */
	private static $booted = false;

	public static function init() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_filter( 'sm_pulse_analytics_settings_steps', array( __CLASS__, 'register_settings_step' ), 8 );
		add_action( 'sm_pulse_analytics_render_settings_tab_monsterinsights-import', array( __CLASS__, 'render_settings_tab' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_run_migration' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_dismiss_prompt' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_activation_notice' ) );
	}

	/**
	 * Whether MonsterInsights is installed, active, or left settings/data behind.
	 *
	 * @return bool
	 */
	public static function detect_source() {
		$scan = self::scan_available();
		return ! empty( $scan['plugin_active'] ) || ! empty( $scan['settings_found'] ) || ! empty( $scan['aggregate_options'] );
	}

	/**
	 * Scan MonsterInsights settings and local aggregated report options.
	 *
	 * @return array<string, mixed>
	 */
	public static function scan_available() {
		global $wpdb;

		$mi_settings = get_option( 'monsterinsights_settings', array() );
		if ( ! is_array( $mi_settings ) ) {
			$mi_settings = array();
		}

		$aggregate_options = array();
		$like_patterns     = array(
			'monsterinsights_over_time',
			'monsterinsights_report_%',
			'monsterinsights_popular_posts_%',
			'monsterinsights_eu_%',
		);

		foreach ( $like_patterns as $pattern ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, LENGTH(option_value) AS size_bytes FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				),
				ARRAY_A
			);
			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( ! empty( $row['option_name'] ) ) {
						$aggregate_options[ $row['option_name'] ] = (int) ( $row['size_bytes'] ?? 0 );
					}
				}
			}
		}

		$plugin_active = self::is_mi_plugin_active();

		return array(
			'plugin_active'     => $plugin_active,
			'settings_found'    => ! empty( $mi_settings ),
			'settings'          => $mi_settings,
			'aggregate_options' => $aggregate_options,
			'mi_version'        => get_option( 'monsterinsights_current_version', '' ),
			'completed'         => self::is_completed(),
			'last_migration'    => self::get_status(),
		);
	}

	/**
	 * @return bool
	 */
	public static function is_mi_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$paths = array(
			'google-analytics-for-wordpress/googleanalytics.php',
			'google-analytics-premium/googleanalytics-premium.php',
		);

		foreach ( $paths as $path ) {
			if ( is_plugin_active( $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function is_completed() {
		$status = self::get_status();
		return ! empty( $status['completed_at'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_status() {
		$stored = get_option( self::STATUS_OPTION, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Flag migration prompt after plugin activation.
	 */
	public static function flag_activation_prompt() {
		if ( self::detect_source() && ! self::is_completed() ) {
			update_option( self::PROMPT_OPTION, 1, false );
		}
	}

	/**
	 * @param array<string, string> $steps Settings steps.
	 * @return array<string, string>
	 */
	public static function register_settings_step( $steps ) {
		if ( ! self::detect_source() ) {
			return $steps;
		}

		if ( ! is_array( $steps ) ) {
			$steps = array();
		}

		$new = array();
		foreach ( $steps as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'help' === $key ) {
				$new['monsterinsights-import'] = __( 'Import from MonsterInsights', 'smackcoders-pulse-analytics-for-woocommerce' );
			}
		}

		if ( ! isset( $new['monsterinsights-import'] ) ) {
			$new['monsterinsights-import'] = __( 'Import from MonsterInsights', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		return $new;
	}

	public static function render_settings_tab() {
		include SM_PULSE_ANALYTICS_PLUGIN_DIR . 'admin/settings/settings-monsterinsights-migration.php';
	}

	public static function maybe_dismiss_prompt() {
		if ( ! is_admin() || ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['sm_pulse_analytics_dismiss_mi_prompt'] ) && check_admin_referer( 'sm_pulse_analytics_dismiss_mi_prompt' ) ) {
			delete_option( self::PROMPT_OPTION );
			wp_safe_redirect( remove_query_arg( array( 'sm_pulse_analytics_dismiss_mi_prompt', '_wpnonce' ) ) );
			exit;
		}
	}

	public static function render_activation_notice() {
		if ( ! is_admin() || ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! get_option( self::PROMPT_OPTION ) || self::is_completed() || ! self::detect_source() ) {
			return;
		}

		$url = admin_url( 'admin.php?page=sm-pulse-analytics-settings&step=monsterinsights-import' );
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'sm_pulse_analytics_dismiss_mi_prompt', '1' ),
			'sm_pulse_analytics_dismiss_mi_prompt'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>:</strong>
				<?php esc_html_e( 'MonsterInsights settings or aggregated report data were detected on this site.', 'smackcoders-pulse-analytics-for-woocommerce' ); ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Import into Pulse Analytics', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="sp-ml-8"><?php esc_html_e( 'Dismiss', 'smackcoders-pulse-analytics-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}

	public static function maybe_run_migration() {
		if ( ! is_admin() || ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['sm_pulse_analytics_mi_migration_run'] ) || ! check_admin_referer( 'save_sm_pulse_analytics_settings' ) ) {
			return;
		}

		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '';
		if ( 'monsterinsights-import' !== $step ) {
			return;
		}

		$force = ! empty( $_POST['sm_pulse_analytics_mi_migration_force'] );
		$result = self::run_migration( $force );

		update_option(
			self::STATUS_OPTION,
			array(
				'completed_at'      => current_time( 'mysql' ),
				'source_hash'       => $result['source_hash'],
				'migrated_settings' => $result['migrated_settings'],
				'skipped_settings'  => $result['skipped_settings'],
				'migrated_data'     => $result['migrated_data'],
				'skipped_data'      => $result['skipped_data'],
				'warnings'          => $result['warnings'],
			),
			false
		);

		delete_option( self::PROMPT_OPTION );

		if ( function_exists( 'sm_pulse_analytics_log_audit' ) ) {
			sm_pulse_analytics_log_audit(
				'MonsterInsights migration completed',
				array(
					'migrated_settings' => count( $result['migrated_settings'] ),
					'migrated_data'     => count( $result['migrated_data'] ),
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'sm-pulse-analytics-settings',
					'step'             => 'monsterinsights-import',
					'migration-done'   => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Execute settings + aggregated data migration.
	 *
	 * @param bool $force Re-import snapshots even if previously migrated.
	 * @return array<string, mixed>
	 */
	public static function run_migration( $force = false ) {
		$scan   = self::scan_available();
		$mi     = is_array( $scan['settings'] ) ? $scan['settings'] : array();
		$result = array(
			'source_hash'       => self::build_source_hash( $scan ),
			'migrated_settings' => array(),
			'skipped_settings'  => array(),
			'migrated_data'     => array(),
			'skipped_data'      => array(),
			'warnings'          => array(),
		);

		if ( ! empty( $scan['completed'] ) && ! $force ) {
			$prev = self::get_status();
			if ( ! empty( $prev['source_hash'] ) && $prev['source_hash'] === $result['source_hash'] ) {
				$result['warnings'][] = __( 'Migration already completed for this MonsterInsights data. Enable “Re-import data” to run again.', 'smackcoders-pulse-analytics-for-woocommerce' );
				return $result;
			}
		}

		$result = array_merge( $result, self::migrate_settings( $mi ) );
		$data_result = self::migrate_aggregate_options( $scan['aggregate_options'], $force );
		$result['migrated_data'] = $data_result['migrated_data'];
		$result['skipped_data']  = $data_result['skipped_data'];

		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Site_Profile' ) ) {
			PulseAnalytics_Site_Profile::sync_profile();
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $scan Scan payload.
	 * @return string
	 */
	private static function build_source_hash( array $scan ) {
		return md5(
			wp_json_encode(
				array(
					'settings' => $scan['settings'] ?? array(),
					'data'     => array_keys( $scan['aggregate_options'] ?? array() ),
					'version'  => $scan['mi_version'] ?? '',
				)
			)
		);
	}

	/**
	 * @param array<string, mixed> $mi MonsterInsights settings array.
	 * @return array<string, mixed>
	 */
	private static function migrate_settings( array $mi ) {
		$migrated = array();
		$skipped  = array();
		$warnings = array();

		$pulse_settings = get_option( 'sm_pulse_analytics_settings', array() );
		if ( ! is_array( $pulse_settings ) ) {
			$pulse_settings = array();
		}

		$ga4_updates = array();

		$measurement_id = self::mi_string( $mi, array( 'v4_id', 'measurement_id', 'tracking_id' ) );
		if ( $measurement_id && '' === PulseAnalytics_GA4_Config::get_measurement_id() ) {
			$ga4_updates['measurement_id'] = $measurement_id;
			update_option( 'sm_pulse_analytics_ga4_measurement_id', $measurement_id, false );
			$migrated[] = 'measurement_id';
		} elseif ( $measurement_id ) {
			$skipped['measurement_id'] = __( 'Pulse Analytics already has a Measurement ID.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		$property_id = self::mi_string( $mi, array( 'v4_property_id', 'property_id' ) );
		if ( $property_id && '' === PulseAnalytics_GA4_Config::get_property_id() ) {
			$ga4_updates['property_id'] = preg_replace( '/[^0-9]/', '', $property_id );
			$migrated[] = 'property_id';
		} elseif ( $property_id ) {
			$skipped['property_id'] = __( 'Pulse Analytics already has a Property ID.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		$api_secret = self::mi_string( $mi, array( 'measurement_protocol_secret', 'mp_secret', 'api_secret' ) );
		if ( $api_secret && '' === PulseAnalytics_GA4_Config::get_api_secret() ) {
			$ga4_updates['api_secret'] = $api_secret;
			update_option( 'sm_pulse_analytics_ga4_api_secret', $api_secret, false );
			$migrated[] = 'api_secret';
		} elseif ( $api_secret ) {
			$skipped['api_secret'] = __( 'Pulse Analytics already has an API Secret.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		if ( ! empty( $ga4_updates ) && class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' ) ) {
			PulseAnalytics_GA4_Config::save_config( $ga4_updates );
		}

		if ( self::mi_bool( $mi, array( 'anonymize_ips', 'anonymize_ip' ) ) && empty( $pulse_settings['ip_anonymization'] ) ) {
			$pulse_settings['ip_anonymization'] = 1;
			$migrated[] = 'ip_anonymization';
		}

		if ( self::mi_bool( $mi, array( 'enable_ecommerce', 'enhanced_ecommerce', 'enhanced_link_attribution' ) ) ) {
			$ecommerce = get_option( 'sm_pulse_analytics_ecommerce', array() );
			if ( ! is_array( $ecommerce ) ) {
				$ecommerce = array();
			}
			if ( empty( $ecommerce['enable'] ) ) {
				$ecommerce['enable'] = 1;
				update_option( 'sm_pulse_analytics_ecommerce', $ecommerce, false );
				$migrated[] = 'ecommerce_tracking';
			} else {
				$skipped['ecommerce_tracking'] = __( 'eCommerce tracking already enabled in Pulse Analytics.', 'smackcoders-pulse-analytics-for-woocommerce' );
			}
		}

		if ( self::mi_bool( $mi, array( 'download_tracking', 'enable_downloads', 'file_download_tracking' ) ) ) {
			if ( empty( $pulse_settings['enable_download_tracking'] ) ) {
				$pulse_settings['enable_download_tracking'] = 1;
				$migrated[] = 'download_tracking';
			}
			$extensions = self::mi_string( $mi, array( 'download_extensions', 'file_download_extensions' ) );
			if ( $extensions && empty( $pulse_settings['download_file_extensions'] ) ) {
				$pulse_settings['download_file_extensions'] = $extensions;
				$migrated[] = 'download_extensions';
			}
		}

		$affiliate_links = array();
		if ( ! empty( $mi['affiliate_links'] ) && is_array( $mi['affiliate_links'] ) ) {
			$affiliate_links = $mi['affiliate_links'];
		} elseif ( ! empty( $mi['affiliate_link_paths'] ) ) {
			$affiliate_links = is_array( $mi['affiliate_link_paths'] ) ? $mi['affiliate_link_paths'] : array_map( 'trim', explode( ',', (string) $mi['affiliate_link_paths'] ) );
		}

		if ( ! empty( $affiliate_links ) && empty( $pulse_settings['affiliate_links'] ) ) {
			$pulse_settings['affiliate_links'] = array_values(
				array_filter(
					array_map(
						static function ( $path ) {
							return sanitize_text_field( (string) $path );
						},
						$affiliate_links
					)
				)
			);
			$migrated[] = 'affiliate_links';
		} elseif ( ! empty( $affiliate_links ) ) {
			$skipped['affiliate_links'] = __( 'Affiliate link paths already configured in Pulse Analytics.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		update_option( 'sm_pulse_analytics_settings', $pulse_settings, false );

		self::migrate_pro_module_toggles( $mi, $migrated, $skipped );

		if ( empty( $measurement_id ) && empty( $mi ) ) {
			$warnings[] = __( 'No MonsterInsights GA4 Measurement ID found — connect Google Analytics manually after migration.', 'smackcoders-pulse-analytics-for-woocommerce' );
		}

		$unsupported = array( 'custom_dimensions', 'user_id_tracking', 'demographics', 'ads_tracking', 'facebook_instant_articles' );
		foreach ( $unsupported as $key ) {
			if ( self::mi_bool( $mi, array( $key ) ) ) {
				$skipped[ $key ] = __( 'No direct Pulse Analytics equivalent — configure manually if needed.', 'smackcoders-pulse-analytics-for-woocommerce' );
			}
		}

		return array(
			'migrated_settings' => $migrated,
			'skipped_settings'  => $skipped,
			'warnings'          => $warnings,
		);
	}

	/**
	 * @param array<string, mixed> $mi       MI settings.
	 * @param array<int, string>   $migrated Migrated keys (by ref).
	 * @param array<string, string> $skipped Skipped keys (by ref).
	 */
	private static function migrate_pro_module_toggles( array $mi, array &$migrated, array &$skipped ) {
		$map = array(
			'media-tracking' => array( 'enable_media_tracking', 'video_tracking', 'youtube_tracking' ),
			'amp-tracking'   => array( 'enable_amp', 'amp_tracking' ),
			'eu-compliance'  => array( 'enable_gdpr', 'gdpr_enable', 'enable_privacy_guard' ),
			'popular-posts'  => array( 'enable_popular_posts', 'popular_posts' ),
			'affiliate-link' => array( 'affiliate_links_enabled', 'enable_affiliate_links' ),
			'performance'    => array( 'enable_perf', 'page_insights' ),
			'page-insights'  => array( 'enable_page_insights', 'page_insights' ),
		);

		$suggested = array();
		foreach ( $map as $slug => $keys ) {
			if ( self::mi_bool( $mi, $keys ) ) {
				$suggested[ $slug ] = true;
				$migrated[]         = 'pro_feature:' . $slug;
			}
		}

		/**
		 * Free emits suggested Pro module toggles from MonsterInsights import.
		 * Pro listens and applies via its feature manager.
		 *
		 * @param array<string, bool> $suggested Feature slug => enabled.
		 */
		do_action( 'sm_pulse_analytics_monsterinsights_pro_toggles', $suggested );
	}

	/**
	 * @param array<string, int> $aggregate_options Option name => byte size.
	 * @param bool               $force             Re-import snapshots.
	 * @return array<string, mixed>
	 */
	private static function migrate_aggregate_options( array $aggregate_options, $force ) {
		$migrated = array();
		$skipped  = array();

		if ( empty( $aggregate_options ) || ! class_exists( __NAMESPACE__ . '\PulseAnalytics_Storage' ) ) {
			return array(
				'migrated_data' => $migrated,
				'skipped_data'  => $skipped,
			);
		}

		foreach ( array_keys( $aggregate_options ) as $option_name ) {
			$value = get_option( $option_name, null );
			if ( null === $value || '' === $value ) {
				$skipped[ $option_name ] = __( 'Empty option value.', 'smackcoders-pulse-analytics-for-woocommerce' );
				continue;
			}

			$lookup = 'mi:' . sanitize_key( str_replace( 'monsterinsights_', '', $option_name ) );

			if ( ! $force ) {
				$existing = PulseAnalytics_Storage::get_snapshot( $lookup, self::PROVIDER );
				if ( null !== $existing ) {
					$skipped[ $option_name ] = __( 'Already imported.', 'smackcoders-pulse-analytics-for-woocommerce' );
					continue;
				}
			}

			$payload = array(
				'source'       => 'monsterinsights',
				'option_name'  => $option_name,
				'imported_at'  => current_time( 'mysql', true ),
				'data'         => $value,
			);

			if ( PulseAnalytics_Storage::set_snapshot( $lookup, $payload, self::SNAPSHOT_TTL, self::PROVIDER ) ) {
				$migrated[] = $option_name;
			} else {
				$skipped[ $option_name ] = __( 'Could not write Pulse Analytics snapshot.', 'smackcoders-pulse-analytics-for-woocommerce' );
			}
		}

		return array(
			'migrated_data' => $migrated,
			'skipped_data'  => $skipped,
		);
	}

	/**
	 * @param array<string, mixed> $mi   Settings array.
	 * @param array<int, string>   $keys Candidate keys.
	 * @return string
	 */
	private static function mi_string( array $mi, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $mi[ $key ] ) && is_scalar( $mi[ $key ] ) ) {
				return sanitize_text_field( (string) $mi[ $key ] );
			}
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $mi   Settings array.
	 * @param array<int, string>   $keys Candidate keys.
	 * @return bool
	 */
	private static function mi_bool( array $mi, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $mi[ $key ] ) ) {
				return true;
			}
		}
		return false;
	}
}

PulseAnalytics_MonsterInsights_Migration::init();
