<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Index Optimization
 * Adds performance indexes to v2 Pulse tables
 */
class Woopulse_Index_Optimization {

	/**
	 * Add performance indexes to all tables
	 * Safe to run multiple times (checks if index exists)
	 */
	public static function add_performance_indexes() {
		global $wpdb;

		$telemetry = PulseAnalytics_Storage::table_name( 'telemetry' );
		$audit     = PulseAnalytics_Storage::table_name( 'audit' );
		$snapshots = PulseAnalytics_Storage::table_name( 'snapshots' );

		self::add_index_if_not_exists( $telemetry, 'idx_telemetry_actor_id', 'actor_id' );
		self::add_index_if_not_exists( $telemetry, 'idx_telemetry_attr_medium', 'attr_medium' );
		self::add_index_if_not_exists( $telemetry, 'idx_telemetry_attr_source', 'attr_source' );

		self::add_composite_index_if_not_exists(
			$telemetry,
			'idx_telemetry_kind_recorded',
			array( 'kind', 'recorded_at' )
		);

		self::add_index_if_not_exists( $audit, 'idx_audit_recorded_at', 'recorded_at' );
		self::add_composite_index_if_not_exists(
			$audit,
			'idx_audit_category_recorded',
			array( 'category', 'recorded_at' )
		);

		self::add_index_if_not_exists( $snapshots, 'idx_snapshots_valid_until', 'valid_until' );
		self::add_composite_index_if_not_exists(
			$snapshots,
			'idx_snapshots_provider_valid',
			array( 'provider', 'valid_until' )
		);
	}

	/**
	 * Add index if it doesn't exist
	 *
	 * @param string $table_name  Table name.
	 * @param string $index_name  Index name.
	 * @param string $column_name Column name.
	 * @return bool True if index was added, false otherwise.
	 */
	private static function add_index_if_not_exists( $table_name, $index_name, $column_name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $table_exists !== $table_name ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$index_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.statistics
				WHERE table_schema = %s
				AND table_name = %s
				AND index_name = %s',
				DB_NAME,
				$table_name,
				$index_name
			)
		);

		if ( $index_exists > 0 ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE %i ADD INDEX %i (%i)',
				$table_name,
				$index_name,
				$column_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		return false !== $result;
	}

	/**
	 * Add composite index if it doesn't exist.
	 *
	 * All bundled composite indexes use exactly two columns.
	 *
	 * @param string   $table_name Table name.
	 * @param string   $index_name Index name.
	 * @param string[] $columns    Column names (must be exactly two).
	 * @return bool True if index was added, false otherwise.
	 */
	private static function add_composite_index_if_not_exists( $table_name, $index_name, $columns ) {
		global $wpdb;

		if ( ! is_array( $columns ) || 2 !== count( $columns ) ) {
			return false;
		}

		$first_column  = $columns[0];
		$second_column = $columns[1];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $table_exists !== $table_name ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_columns = $wpdb->get_col( $wpdb->prepare( 'DESCRIBE %i', $table_name ) );
		foreach ( $columns as $column ) {
			if ( ! in_array( $column, $table_columns, true ) ) {
				return false;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$index_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.statistics
				WHERE table_schema = %s
				AND table_name = %s
				AND index_name = %s',
				DB_NAME,
				$table_name,
				$index_name
			)
		);

		if ( $index_exists > 0 ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query(
			$wpdb->prepare(
				'ALTER TABLE %i ADD INDEX %i (%i, %i)',
				$table_name,
				$index_name,
				$first_column,
				$second_column
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		return false !== $result;
	}
}
