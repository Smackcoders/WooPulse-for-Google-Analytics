<?php
/**
 * Central storage helpers for Pulse Analytics tables (v2 schema).
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

class PulseAnalytics_Storage {

	const DB_VERSION_OPTION = 'sm_pulse_analytics_db_version';
	const DB_VERSION        = '2.0.0';

	/**
	 * Active table suffixes (v2).
	 *
	 * @return string[]
	 */
	public static function table_suffixes() {
		return array( 'telemetry', 'audit', 'snapshots' );
	}

	/**
	 * Legacy MonsterInsights-style tables removed in v2.
	 *
	 * @return string[]
	 */
	public static function legacy_table_suffixes() {
		return array(
			'events',
			'logs',
			'cache',
			'aggregated_metrics',
			'reports',
			'settings',
			'campaigns',
			'sync',
			'newsletter_subs',
			'aggregates',
			'notes',
			'identities',
		);
	}

	/**
	 * Fully qualified table name.
	 *
	 * @param string $suffix telemetry|audit|snapshots (events/logs/cache map for BC).
	 * @return string
	 */
	public static function table_name( $suffix ) {
		$map = array(
			'events'  => 'telemetry',
			'logs'    => 'audit',
			'cache'   => 'snapshots',
		);
		if ( isset( $map[ $suffix ] ) ) {
			$suffix = $map[ $suffix ];
		}

		global $wpdb;
		$prefix = ( defined( 'SM_PULSE_ANALYTICS_TABLE_PREFIX' ) && SM_PULSE_ANALYTICS_TABLE_PREFIX ) ? SM_PULSE_ANALYTICS_TABLE_PREFIX : 'sm_pulse_analytics_';
		return $wpdb->prefix . $prefix . $suffix;
	}

	/**
	 * Whether the telemetry table exists.
	 */
	public static function events_table_exists() {
		global $wpdb;
		$table = self::table_name( 'telemetry' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Append telemetry row.
	 *
	 * Accepts legacy keys (event_type, event_data, order_id, utm_*) for callers.
	 */
	public static function insert_event( array $args ) {
		if ( empty( $args['event_type'] ) && empty( $args['kind'] ) ) {
			return false;
		}
		if ( ! self::events_table_exists() ) {
			return false;
		}

		global $wpdb;

		$payload = $args['event_data'] ?? $args['payload'] ?? array();
		if ( ! is_array( $payload ) ) {
			$payload = array( 'value' => $payload );
		}

		$entity_id = null;
		if ( isset( $args['order_id'] ) ) {
			$entity_id = (int) $args['order_id'];
		} elseif ( isset( $args['entity_id'] ) ) {
			$entity_id = (int) $args['entity_id'];
		}

		$entity_type = $args['entity_type'] ?? ( $entity_id ? 'order' : null );
		$user_id     = $args['user_id'] ?? $args['actor_id'] ?? get_current_user_id();

		$row = array(
			'recorded_at'   => ! empty( $args['event_timestamp'] ) ? $args['event_timestamp'] : ( $args['recorded_at'] ?? current_time( 'mysql', true ) ),
			'kind'          => sanitize_key( $args['kind'] ?? $args['event_type'] ),
			'payload'       => wp_json_encode( $payload ),
		);

		if ( ! empty( $args['session_key'] ) ) {
			$row['session_key'] = sanitize_text_field( $args['session_key'] );
		}
		if ( $entity_type ) {
			$row['entity_type'] = sanitize_key( $entity_type );
		}
		if ( $entity_id > 0 ) {
			$row['entity_id'] = $entity_id;
		}
		if ( $user_id > 0 ) {
			$row['actor_id'] = (int) $user_id;
		}
		if ( ! empty( $args['utm_source'] ) || ! empty( $args['attr_source'] ) ) {
			$row['attr_source'] = sanitize_text_field( $args['utm_source'] ?? $args['attr_source'] );
		}
		if ( ! empty( $args['utm_medium'] ) || ! empty( $args['attr_medium'] ) ) {
			$row['attr_medium'] = sanitize_text_field( $args['utm_medium'] ?? $args['attr_medium'] );
		}
		if ( ! empty( $args['utm_campaign'] ) || ! empty( $args['attr_campaign'] ) ) {
			$row['attr_campaign'] = sanitize_text_field( $args['utm_campaign'] ?? $args['attr_campaign'] );
		}

		$formats = array();
		foreach ( $row as $value ) {
			$formats[] = is_int( $value ) ? '%d' : '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( self::table_name( 'telemetry' ), $row, $formats );
		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Append audit row. Accepts legacy keys (event_type, message, source).
	 */
	public static function insert_log( $event_type, $message, array $context = array() ) {
		global $wpdb;
		$table = self::table_name( 'audit' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}

		$ctx = array();
		if ( ! empty( $context['exit_page'] ) ) {
			$ctx['exit_page'] = $context['exit_page'];
		}
		if ( ! empty( $context['order_id'] ) ) {
			$ctx['order_id'] = (int) $context['order_id'];
		}
		if ( ! empty( $context['details'] ) ) {
			$ctx['details'] = $context['details'];
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'recorded_at' => current_time( 'mysql', true ),
				'category'    => sanitize_key( $event_type ),
				'summary'     => wp_strip_all_tags( (string) $message ),
				'component'   => sanitize_text_field( $context['source'] ?? $context['component'] ?? 'system' ),
				'context'     => ! empty( $ctx ) ? wp_json_encode( $ctx ) : null,
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
		// phpcs:enable

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Read cached API snapshot.
	 */
	public static function get_snapshot( $lookup_key, $provider = 'internal' ) {
		global $wpdb;
		$table = self::table_name( 'snapshots' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return null;
		}

		$row = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT payload FROM {$table} WHERE lookup_key = %s AND provider = %s AND valid_until > UTC_TIMESTAMP() LIMIT 1",
				$lookup_key,
				$provider
			)
		);
		// phpcs:enable

		if ( empty( $row ) ) {
			return null;
		}

		return maybe_unserialize( $row );
	}

	/**
	 * Store API snapshot.
	 */
	public static function set_snapshot( $lookup_key, $payload, $ttl_seconds, $provider = 'internal' ) {
		global $wpdb;
		$table = self::table_name( 'snapshots' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}

		$valid_until = gmdate( 'Y-m-d H:i:s', time() + max( 60, (int) $ttl_seconds ) );
		$res = (bool) $wpdb->replace(
			$table,
			array(
				'lookup_key'  => $lookup_key,
				'provider'    => sanitize_key( $provider ),
				'payload'     => maybe_serialize( $payload ),
				'valid_until' => $valid_until,
				'recorded_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
		// phpcs:enable
		return $res;
	}

	/**
	 * Delete snapshots by provider name.
	 *
	 * @param string $provider Provider slug (e.g. 'ecommerce_reports').
	 * @return int|bool Number of deleted rows or false on error.
	 */
	public static function delete_snapshots_by_provider( $provider ) {
		global $wpdb;
		$table = self::table_name( 'snapshots' );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}

		$res = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE provider = %s',
				$table,
				sanitize_key( $provider )
			)
		);
		// phpcs:enable
		return $res;
	}

	/**
	 * Copy legacy v1 tables into v2 then drop legacy.
	 */
	public static function migrate_legacy_tables() {
		global $wpdb;
		$base = ( defined( 'SM_PULSE_ANALYTICS_TABLE_PREFIX' ) && SM_PULSE_ANALYTICS_TABLE_PREFIX ) ? SM_PULSE_ANALYTICS_TABLE_PREFIX : 'sm_pulse_analytics_';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$old_events = $wpdb->prefix . $base . 'events';
		$new_telem  = self::table_name( 'telemetry' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_events ) ) === $old_events ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i (recorded_at, kind, entity_type, entity_id, actor_id, attr_source, attr_medium, attr_campaign, payload)
					SELECT event_timestamp, event_type,
						CASE WHEN order_id IS NOT NULL AND order_id > 0 THEN 'order' ELSE NULL END,
						order_id, user_id, utm_source, utm_medium, utm_campaign, event_data
					FROM %i",
					$new_telem,
					$old_events
				)
			);
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $old_events ) );
		}

		$old_logs = $wpdb->prefix . $base . 'logs';
		$new_audit = self::table_name( 'audit' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_logs ) ) === $old_logs ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i (recorded_at, category, summary, component, context)
					SELECT created_at, event_type, message, source,
						CONCAT('{\"exit_page\":\"', IFNULL(exit_page,''), '\",\"order_id\":', IFNULL(order_id,0), '}')
					FROM %i",
					$new_audit,
					$old_logs
				)
			);
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $old_logs ) );
		}

		$old_cache = $wpdb->prefix . $base . 'cache';
		$new_snap  = self::table_name( 'snapshots' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_cache ) ) === $old_cache ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i (lookup_key, provider, payload, valid_until, recorded_at)
					SELECT cache_key, IFNULL(cache_group,'internal'), cache_value, expires_at, IFNULL(created_at, UTC_TIMESTAMP())
					FROM %i",
					$new_snap,
					$old_cache
				)
			);
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $old_cache ) );
		}
		// phpcs:enable
	}

	/**
	 * Drop tables by suffix list.
	 *
	 * @param string[] $suffixes Table suffixes without wpdb prefix.
	 */
	public static function drop_tables( array $suffixes ) {
		global $wpdb;
		$base = ( defined( 'SM_PULSE_ANALYTICS_TABLE_PREFIX' ) && SM_PULSE_ANALYTICS_TABLE_PREFIX ) ? SM_PULSE_ANALYTICS_TABLE_PREFIX : 'sm_pulse_analytics_';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
		foreach ( $suffixes as $suffix ) {
			$table = $wpdb->prefix . $base . $suffix;
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
		}
		// phpcs:enable
	}

	/**
	 * Get configured telemetry data retention days (default 30 days, 0 = keep forever).
	 *
	 * @return int
	 */
	public static function get_telemetry_retention_days() {
		return (int) get_option( 'sm_pulse_analytics_telemetry_retention_days', 30 );
	}

	/**
	 * Prune raw transient telemetry events older than configured retention days.
	 *
	 * @param int|null $days Optional override days.
	 * @return int Number of pruned rows.
	 */
	public static function prune_expired_telemetry( $days = null ) {
		if ( null === $days ) {
			$days = self::get_telemetry_retention_days();
		}
		$days = (int) $days;

		if ( $days <= 0 ) {
			return 0; // Retention set to Keep Forever
		}

		if ( ! self::events_table_exists() ) {
			return 0;
		}

		global $wpdb;
		$table       = self::table_name( 'telemetry' );
		$cutoff_date = gmdate( 'Y-m-d 00:00:00', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE recorded_at < %s AND kind IN ('journey_page_view', 'page_view', 'session_ping', 'page_engagement', 'video_progress', 'video_start', 'video_view')",
				$table,
				$cutoff_date
			)
		);
		// phpcs:enable

		return (int) $deleted;
	}

	/**
	 * Schedule and initialize daily telemetry pruning WP-Cron.
	 */
	public static function init_cron_hooks() {
		add_action( 'sm_pulse_analytics_daily_telemetry_prune', array( __CLASS__, 'prune_expired_telemetry' ) );
		if ( ! wp_next_scheduled( 'sm_pulse_analytics_daily_telemetry_prune' ) ) {
			wp_schedule_event( time() + 3600, 'daily', 'sm_pulse_analytics_daily_telemetry_prune' );
		}
	}
}

add_action( 'init', array( '\Sm_Pulse_Analytics\PulseAnalytics_Storage', 'init_cron_hooks' ) );

class_alias( '\Sm_Pulse_Analytics\PulseAnalytics_Storage', 'Sm_Pulse_Analytics_Storage' );
