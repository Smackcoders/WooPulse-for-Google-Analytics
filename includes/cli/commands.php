<?php
/**
 * Pulse Analytics Free WP-CLI commands (#54).
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics\CLI;

use Sm_Pulse_Analytics\PulseAnalytics_GA4_Config;
use Sm_Pulse_Analytics\PulseAnalytics_MonsterInsights_Migration;
use Sm_Pulse_Analytics\PulseAnalytics_Site_Profile;
use Sm_Pulse_Analytics\PulseAnalytics_Storage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared CLI helpers.
 */
trait PulseAnalytics_CLI_Helpers {

	/**
	 * @return bool
	 */
	protected function ensure_manage_settings() {
		if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			\WP_CLI::error( 'Insufficient permissions. Run with --user=<admin>.' );
			return false;
		}
		return true;
	}

	/**
	 * @param string $prefix Option/transient prefix fragment.
	 * @return int
	 */
	protected function delete_transients_like( $prefix ) {
		global $wpdb;
		$like = '_transient_' . $wpdb->esc_like( $prefix ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$count = 0;
		foreach ( (array) $keys as $key ) {
			$name = str_replace( '_transient_', '', $key );
			if ( delete_transient( $name ) ) {
				++$count;
			}
		}
		return $count;
	}
}

/**
 * Plugin status overview.
 */
class Status_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Show Pulse Analytics Free status.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, json, yaml, csv
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics status
	 *     wp pulse-analytics status --format=json
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$measurement_id = class_exists( PulseAnalytics_GA4_Config::class ) ? PulseAnalytics_GA4_Config::get_measurement_id() : '';
		$property_id    = class_exists( PulseAnalytics_GA4_Config::class ) ? PulseAnalytics_GA4_Config::get_property_id() : '';
		$api_secret     = class_exists( PulseAnalytics_GA4_Config::class ) ? PulseAnalytics_GA4_Config::get_api_secret() : '';
		$oauth_token    = get_option( 'sm_pulse_analytics_ga4_access_token', '' );

		$rows = array(
			array( 'key' => 'plugin_version', 'value' => defined( 'SM_PULSE_ANALYTICS_VERSION' ) ? SM_PULSE_ANALYTICS_VERSION : 'unknown' ),
			array( 'key' => 'db_version', 'value' => get_option( PulseAnalytics_Storage::DB_VERSION_OPTION, 'none' ) ),
			array( 'key' => 'pro_active', 'value' => function_exists( 'sm_pulse_analytics_is_pro_active' ) && sm_pulse_analytics_is_pro_active() ? 'yes' : 'no' ),
			array( 'key' => 'ga4_measurement_id', 'value' => $measurement_id ? 'set' : 'missing' ),
			array( 'key' => 'ga4_property_id', 'value' => $property_id ? 'set' : 'missing' ),
			array( 'key' => 'ga4_api_secret', 'value' => $api_secret ? 'set' : 'missing' ),
			array( 'key' => 'ga4_oauth', 'value' => ! empty( $oauth_token ) ? 'connected' : 'disconnected' ),
			array( 'key' => 'site_type', 'value' => function_exists( 'sm_pulse_analytics_get_site_type' ) ? sm_pulse_analytics_get_site_type() : 'unknown' ),
			array( 'key' => 'commerce', 'value' => function_exists( 'sm_pulse_analytics_has_commerce' ) && sm_pulse_analytics_has_commerce() ? 'yes' : 'no' ),
			array( 'key' => 'monsterinsights_detected', 'value' => class_exists( PulseAnalytics_MonsterInsights_Migration::class ) && PulseAnalytics_MonsterInsights_Migration::detect_source() ? 'yes' : 'no' ),
			array( 'key' => 'last_manual_sync', 'value' => get_option( 'sm_pulse_analytics_last_manual_sync', 0 ) ? gmdate( 'c', (int) get_option( 'sm_pulse_analytics_last_manual_sync' ) ) : 'never' ),
		);

		if ( class_exists( PulseAnalytics_Site_Profile::class ) ) {
			$platforms = PulseAnalytics_Site_Profile::get_active_platforms();
			$rows[]    = array( 'key' => 'commerce_platforms', 'value' => ! empty( $platforms ) ? implode( ',', $platforms ) : 'none' );
		}

		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array( 'key', 'value' ) );
	}
}

/**
 * Health check / doctor command.
 */
class Doctor_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Run Pulse Analytics health checks.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics doctor
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$issues = 0;

		foreach ( PulseAnalytics_Storage::table_suffixes() as $suffix ) {
			global $wpdb;
			$table  = PulseAnalytics_Storage::table_name( $suffix );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			if ( $exists ) {
				\WP_CLI::log( "OK  table {$table}" );
			} else {
				\WP_CLI::warning( "MISSING table {$table} — run: wp pulse-analytics db install" );
				++$issues;
			}
		}

		if ( ! class_exists( PulseAnalytics_GA4_Config::class ) || '' === PulseAnalytics_GA4_Config::get_measurement_id() ) {
			\WP_CLI::warning( 'GA4 Measurement ID is not configured.' );
			++$issues;
		} else {
			\WP_CLI::log( 'OK  GA4 Measurement ID configured' );
		}

		if ( function_exists( 'sm_pulse_analytics_is_pro_active' ) && ! sm_pulse_analytics_is_pro_active() ) {
			\WP_CLI::log( 'INFO  PRO Core is not active (Free-only mode).' );
		}

		if ( $issues > 0 ) {
			\WP_CLI::error( sprintf( '%d issue(s) found.', $issues ), false );
		} else {
			\WP_CLI::success( 'All checks passed.' );
		}
	}
}

/**
 * Database commands.
 */
class DB_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Verify Pulse Analytics database tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics db verify
	 *
	 * @when after_wp_load
	 */
	public function verify( $args, $assoc_args ) {
		global $wpdb;
		$rows = array();
		foreach ( PulseAnalytics_Storage::table_suffixes() as $suffix ) {
			$table   = PulseAnalytics_Storage::table_name( $suffix );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count   = $exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ) : 0;
			$rows[]  = array(
				'table'  => $table,
				'exists' => $exists ? 'yes' : 'no',
				'rows'   => $count,
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'table', 'exists', 'rows' ) );
	}

	/**
	 * Create or upgrade Pulse Analytics tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics db install
	 *
	 * @when after_wp_load
	 */
	public function install( $args, $assoc_args ) {
		if ( ! $this->ensure_manage_settings() ) {
			return;
		}
		if ( ! function_exists( '\Sm_Pulse_Analytics\sm_pulse_analytics_create_tables' ) ) {
			require_once SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/installation/install.php';
		}
		\Sm_Pulse_Analytics\sm_pulse_analytics_create_tables();
		\Sm_Pulse_Analytics\sm_pulse_analytics_register_capabilities();
		\WP_CLI::success( 'Database tables installed/upgraded.' );
	}
}

/**
 * Cache commands.
 */
class Cache_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Clear Pulse Analytics API snapshot cache.
	 *
	 * ## OPTIONS
	 *
	 * [--transients]
	 * : Also delete sm_pulse_analytics_* transients.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics cache clear --yes
	 *
	 * @when after_wp_load
	 */
	public function clear( $args, $assoc_args ) {
		if ( ! $this->ensure_manage_settings() ) {
			return;
		}

		\WP_CLI::confirm( 'Clear Pulse Analytics snapshot cache?', $assoc_args );

		global $wpdb;
		$table = PulseAnalytics_Storage::table_name( 'snapshots' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table ) );
			\WP_CLI::log( "Truncated {$table}" );
		}

		if ( ! empty( $assoc_args['transients'] ) ) {
			$deleted = $this->delete_transients_like( 'sm_pulse_analytics_' );
			$deleted += $this->delete_transients_like( 'woopulse_' );
			\WP_CLI::log( "Deleted {$deleted} transients." );
		}

		\WP_CLI::success( 'Cache cleared.' );
	}
}

/**
 * Manual data sync.
 */
class Sync_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Run Pulse Analytics manual data aggregation sync.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics sync
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		if ( ! $this->ensure_manage_settings() ) {
			return;
		}

		$aggregator = SM_PULSE_ANALYTICS_PLUGIN_DIR . 'includes/aggregator.php';
		if ( ! function_exists( 'sm_pulse_analytics_process_data_aggregation' ) && file_exists( $aggregator ) ) {
			require_once $aggregator;
		}

		if ( ! function_exists( 'sm_pulse_analytics_process_data_aggregation' ) ) {
			\WP_CLI::warning( 'Aggregation routine not available in this build.' );
			update_option( 'sm_pulse_analytics_last_manual_sync', time(), false );
			\WP_CLI::success( 'Sync timestamp updated.' );
			return;
		}

		sm_pulse_analytics_process_data_aggregation();
		update_option( 'sm_pulse_analytics_last_manual_sync', time(), false );
		\WP_CLI::success( 'Data sync completed.' );
	}
}

/**
 * Migration commands.
 */
class Migrate_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Run MonsterInsights → Pulse Analytics migration.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Re-import aggregated snapshots.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics migrate monsterinsights --yes
	 *
	 * @when after_wp_load
	 */
	public function monsterinsights( $args, $assoc_args ) {
		if ( ! $this->ensure_manage_settings() ) {
			return;
		}

		if ( ! class_exists( PulseAnalytics_MonsterInsights_Migration::class ) ) {
			\WP_CLI::error( 'MonsterInsights migration is not available.' );
		}

		if ( ! PulseAnalytics_MonsterInsights_Migration::detect_source() ) {
			\WP_CLI::error( 'No MonsterInsights settings or aggregated data detected.' );
		}

		\WP_CLI::confirm( 'Import MonsterInsights settings and aggregated data into Pulse Analytics?', $assoc_args );

		$force  = ! empty( $assoc_args['force'] );
		$result = PulseAnalytics_MonsterInsights_Migration::run_migration( $force );

		update_option(
			PulseAnalytics_MonsterInsights_Migration::STATUS_OPTION,
			array(
				'completed_at'      => current_time( 'mysql', true ),
				'source_hash'       => $result['source_hash'],
				'migrated_settings' => $result['migrated_settings'],
				'skipped_settings'  => $result['skipped_settings'],
				'migrated_data'     => $result['migrated_data'],
				'skipped_data'      => $result['skipped_data'],
				'warnings'          => $result['warnings'],
			),
			false
		);

		delete_option( PulseAnalytics_MonsterInsights_Migration::PROMPT_OPTION );

		if ( function_exists( 'sm_pulse_analytics_log_audit' ) ) {
			sm_pulse_analytics_log_audit(
				'MonsterInsights migration completed (WP-CLI)',
				array(
					'migrated_settings' => count( $result['migrated_settings'] ),
					'migrated_data'     => count( $result['migrated_data'] ),
				)
			);
		}

		\WP_CLI::log( 'Migrated settings: ' . implode( ', ', (array) $result['migrated_settings'] ) );
		\WP_CLI::log( 'Imported data options: ' . count( (array) $result['migrated_data'] ) );
		if ( ! empty( $result['warnings'] ) ) {
			foreach ( (array) $result['warnings'] as $warning ) {
				\WP_CLI::warning( (string) $warning );
			}
		}
		\WP_CLI::success( 'MonsterInsights migration finished.' );
	}
}

/**
 * Local telemetry commands.
 */
class Telemetry_Command {

	use PulseAnalytics_CLI_Helpers;

	/**
	 * Count telemetry rows by kind.
	 *
	 * ## OPTIONS
	 *
	 * [--kind=<kind>]
	 * : Filter by telemetry kind.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics telemetry count
	 *     wp pulse-analytics telemetry count --kind=purchase
	 *
	 * @when after_wp_load
	 */
	public function count( $args, $assoc_args ) {
		if ( ! PulseAnalytics_Storage::events_table_exists() ) {
			\WP_CLI::error( 'Telemetry table missing. Run: wp pulse-analytics db install' );
		}

		global $wpdb;
		$table = PulseAnalytics_Storage::table_name( 'telemetry' );
		$kind  = isset( $assoc_args['kind'] ) ? sanitize_key( $assoc_args['kind'] ) : '';

		if ( $kind ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE kind = %s", $kind ) );
			\WP_CLI::log( "{$kind}: {$count}" );
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT kind, COUNT(*) AS total FROM {$table} GROUP BY kind ORDER BY total DESC", ARRAY_A );
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'kind', 'total' ) );
	}

	/**
	 * List recent telemetry rows.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Number of rows (default 20).
	 *
	 * [--kind=<kind>]
	 * : Filter by kind.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pulse-analytics telemetry recent --limit=10
	 *
	 * @when after_wp_load
	 */
	public function recent( $args, $assoc_args ) {
		if ( ! PulseAnalytics_Storage::events_table_exists() ) {
			\WP_CLI::error( 'Telemetry table missing.' );
		}

		global $wpdb;
		$table = PulseAnalytics_Storage::table_name( 'telemetry' );
		$limit = isset( $assoc_args['limit'] ) ? max( 1, (int) $assoc_args['limit'] ) : 20;
		$kind  = isset( $assoc_args['kind'] ) ? sanitize_key( $assoc_args['kind'] ) : '';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $kind ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, kind, recorded_at, entity_type, entity_id FROM {$table} WHERE kind = %s ORDER BY id DESC LIMIT %d",
					$kind,
					$limit
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, kind, recorded_at, entity_type, entity_id FROM {$table} ORDER BY id DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		}
		// phpcs:enable

		\WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'kind', 'recorded_at', 'entity_type', 'entity_id' ) );
	}
}
