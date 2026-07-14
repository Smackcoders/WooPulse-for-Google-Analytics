<?php
/**
 * Session tracker for StorePulse analytics plugin.
 *
 * Tracks active frontend sessions keyed by cookie session-ID (UUID).
 * Stores session_start, last_heartbeat and display_id so that:
 *  - Real-time counts use heartbeat recency, not page-load recency.
 *  - Session duration is calculated server-side when JS sends /session/end.
 *  - The identifier shown in reports is User-ID (logged-in) or cookie SID (guest).
 *
 * @package StorePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StorePulse_Session_Tracker {

	private static $instance = null;

	// Seconds without heartbeat before a session is considered inactive (real-time).
	const HEARTBEAT_TIMEOUT = 30;

	// Seconds without heartbeat before a session is considered ended on the backend.
	const SESSION_HEARTBEAT_EXPIRY = 60; // 30 seconds + 30 seconds buffer
	const NEW_SESSION_INACTIVITY_THRESHOLD = 120; // 120 seconds of inactivity to generate a new session ID

	// Seconds after last_active before cleaning up the session record (5 min).
	const SESSION_EXPIRY = 300;

	private function __construct() {
		add_action( 'init', array( $this, 'run_status_check' ), 5 );
		add_action( 'init', array( $this, 'track_active_sessions' ) );
		add_action( 'wp_login', array( $this, 'link_anon_to_user' ), 10, 2 );
		add_action( 'user_register', array( $this, 'link_anon_to_user_register' ), 10, 1 );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Run status check on init hook to end expired sessions (inactive > 60s)
	 */
	public function run_status_check() {
		self::garbage_collect_sessions();
	}

	/**
	 * Called on every frontend page load.
	 * Creates or refreshes the session record stored in the StorePulse_active_sessions option.
	 */
	public function track_active_sessions() {
		// Only track real frontend page requests.
		if (
			is_admin() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( defined( 'DOING_CRON' ) && DOING_CRON ) ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( defined( 'WP_CLI' ) && WP_CLI ) ||
			( false !== strpos( $_SERVER['REQUEST_URI'] ?? '', '/wp-json/' ) ) || // phpcs:ignore
			! empty( $_GET['rest_route'] ) // phpcs:ignore
		) {
			return;
		}

		$user_id = get_current_user_id();
		$active_sessions = get_option( 'StorePulse_active_sessions', array() );

		// Resolve / generate cookie Anonymous ID
		$anon_id = '';
		if ( ! isset( $_COOKIE['_store_tracker'] ) ) {
			$anon_id = wp_generate_uuid4();
			setcookie( '_store_tracker', $anon_id, time() + ( 86400 * 365 ), '/', '', is_ssl(), true ); // 1 year
			$_COOKIE['_store_tracker'] = $anon_id;
		} else {
			$anon_id = sanitize_text_field( wp_unslash( $_COOKIE['_store_tracker'] ) );
		}

		// ── 1. Resolve / generate cookie session-ID ─────────────────────────
		$session_id = '';
		$is_new_session = false;

		if ( ! isset( $_COOKIE['StorePulse_sid'] ) ) {
			if ( $user_id > 0 ) {
				$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
			} else {
				$session_id = wp_generate_uuid4();
			}
			$is_new_session = true;
		} else {
			$session_id = sanitize_text_field( wp_unslash( $_COOKIE['StorePulse_sid'] ) );

			// Check for logout transition
			$is_logout_transition = false;
			if ( $user_id == 0 ) {
				if ( 0 === strpos( $session_id, 'user-' ) ) {
					$is_logout_transition = true;
				} elseif ( isset( $active_sessions[ $session_id ] ) && $active_sessions[ $session_id ]['user_id'] > 0 ) {
					$is_logout_transition = true;
				}
			}

			// Check for switch-user transition
			$is_user_switch_transition = false;
			if ( $user_id > 0 && isset( $active_sessions[ $session_id ] ) && $active_sessions[ $session_id ]['user_id'] > 0 && $active_sessions[ $session_id ]['user_id'] != $user_id ) {
				$is_user_switch_transition = true;
			}

			if ( $is_logout_transition || $is_user_switch_transition ) {
				// Invalidate and end the old session
				$old_session_id = $session_id;
				if ( isset( $active_sessions[ $old_session_id ] ) ) {
					$duration = current_time( 'timestamp' ) - strtotime( $active_sessions[ $old_session_id ]['session_start'] );
					self::end_session( $old_session_id, $duration );
					unset( $active_sessions[ $old_session_id ] );
				}
				
				// Generate a brand new session ID
				if ( $user_id > 0 ) {
					$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
				} else {
					$session_id = wp_generate_uuid4();
				}
				$is_new_session = true;
				update_option( 'StorePulse_active_sessions', $active_sessions );
			} else {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$session_ended_in_db = (bool) $wpdb->get_var( $wpdb->prepare(
					"SELECT 1 FROM {$wpdb->prefix}storepulse_events WHERE session_id = %s AND event_type = 'session_end' LIMIT 1",
					$session_id
				) );

				if ( $session_ended_in_db ) {
					if ( isset( $active_sessions[ $session_id ] ) ) {
						unset( $active_sessions[ $session_id ] );
						update_option( 'StorePulse_active_sessions', $active_sessions );
					}
					if ( $user_id > 0 ) {
						$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
					} else {
						$session_id = wp_generate_uuid4();
					}
					$is_new_session = true;
				} elseif ( isset( $active_sessions[ $session_id ] ) ) {
					// Handle login transition
					if ( $user_id > 0 && $active_sessions[ $session_id ]['user_id'] == 0 ) {
						global $wpdb;
						// Update events
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->update(
							$wpdb->prefix . 'storepulse_events',
							array( 'user_id' => $user_id ),
							array( 'session_id' => $session_id )
						);

						// Update audit logs
						$table_logs = $wpdb->prefix . 'storepulse_logs';
						$user_info  = get_userdata( $user_id );
						if ( $user_info ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->update(
								$table_logs,
								array(
									'user_id'    => $user_id,
									'user_name'  => $user_info->display_name,
									'user_email' => $user_info->user_email,
									'user_role'  => ! empty( $user_info->roles ) ? $user_info->roles[0] : '',
									'is_guest'   => 0,
								),
								array( 'session_id' => $session_id )
							);
						}
						
						$active_sessions[ $session_id ]['user_id']    = $user_id;
						$active_sessions[ $session_id ]['user_login'] = $user_info ? $user_info->user_login : 'Logged-in User';
						$active_sessions[ $session_id ]['display_id'] = (string) $user_id;
						update_option( 'StorePulse_active_sessions', $active_sessions );
					}

					$last_activity_ts = max(
						strtotime( $active_sessions[ $session_id ]['last_heartbeat'] ?? '0' ),
						strtotime( $active_sessions[ $session_id ]['last_active'] ?? '0' )
					);
					$time_since_last_activity = current_time( 'timestamp' ) - $last_activity_ts;

					if ( $time_since_last_activity > self::SESSION_HEARTBEAT_EXPIRY ) {
						$old_session_id = $session_id;
						if ( isset( $active_sessions[ $old_session_id ] ) ) {
							$duration = current_time( 'timestamp' ) - strtotime( $active_sessions[ $old_session_id ]['session_start'] );
							self::end_session( $old_session_id, $duration );
						}
						unset( $active_sessions[ $session_id ] );
						update_option( 'StorePulse_active_sessions', $active_sessions );

						if ( $user_id > 0 ) {
							$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
						} else {
							$session_id = wp_generate_uuid4();
						}
						$is_new_session = true;
					} elseif ( $time_since_last_activity > self::NEW_SESSION_INACTIVITY_THRESHOLD ) {
						$old_session_id = $session_id;
						if ( isset( $active_sessions[ $old_session_id ] ) ) {
							$duration = current_time( 'timestamp' ) - strtotime( $active_sessions[ $old_session_id ]['session_start'] );
							self::end_session( $old_session_id, $duration );
						}
						unset( $active_sessions[ $session_id ] );
						update_option( 'StorePulse_active_sessions', $active_sessions );

						if ( $user_id > 0 ) {
							$session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
						} else {
							$session_id = wp_generate_uuid4();
						}
						$is_new_session = true;
					}
				} else {
					// If the user transitioned to logged in, and we did not update their previous events yet
					if ( $user_id > 0 ) {
						global $wpdb;
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->update(
							$wpdb->prefix . 'storepulse_events',
							array( 'user_id' => $user_id ),
							array( 'session_id' => $session_id, 'user_id' => null )
						);

						$table_logs = $wpdb->prefix . 'storepulse_logs';
						$user_info  = get_userdata( $user_id );
						if ( $user_info ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->update(
								$table_logs,
								array(
									'user_id'    => $user_id,
									'user_name'  => $user_info->display_name,
									'user_email' => $user_info->user_email,
									'user_role'  => ! empty( $user_info->roles ) ? $user_info->roles[0] : '',
									'is_guest'   => 0,
								),
								array( 'session_id' => $session_id )
							);
						}
					}
					$is_new_session = true;
				}
			}
		}

		// Set the cookie for new sessions or renewed sessions
		if ( $is_new_session ) {
			setcookie( 'StorePulse_sid', $session_id, time() + ( 86400 * 1 ), '/', '', is_ssl(), true );
			$_COOKIE['StorePulse_sid'] = $session_id; // Update superglobal for current request
		}

		// ── 2. Throttle to once per second per session ───────────────────────
		$active_sessions = get_option( 'StorePulse_active_sessions', array() );

		if ( ! $is_new_session && isset( $active_sessions[ $session_id ] ) ) {
			$last_active_ts = strtotime( $active_sessions[ $session_id ]['last_active'] );
			if ( current_time( 'timestamp' ) - $last_active_ts < 1 ) {
				return;
			}
		}

		// ── 3. Geo lookup (cached) ──────────────────────────────────────────
		$ip      = StorePulse_get_visitor_ip();
		$geo_key = 'StorePulse_geo_' . md5( $ip );
		$geo     = get_transient( $geo_key );

		if ( false === $geo ) {
			$geo = $this->fetch_geo( $ip );
			set_transient( $geo_key, $geo, HOUR_IN_SECONDS );
		}

		if ( ! is_array( $geo ) ) {
			$geo = array( 'country' => 'Unknown', 'city' => 'Unknown' );
		}

		// ── 4. Build session record ─────────────────────────────────────────
		$user_info  = $user_id ? get_userdata( $user_id ) : null;
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: 'Unknown';

		$display_id = $user_id ? (string) $user_id : $session_id;

		$now = current_time( 'mysql' );

		if ( $is_new_session || ! isset( $active_sessions[ $session_id ] ) ) {
			$session_start = $now;
		} else {
			$session_start = $active_sessions[ $session_id ]['session_start'] ?? $now;
		}

		$session_data = array(
			'anon_id'        => $anon_id,
			'user_agent'     => $user_agent,
			'ip_hash'        => md5( $ip ),
			'country'        => $geo['country'] ?? 'Unknown',
			'city'           => $geo['city'] ?? 'Unknown',
			'last_active'    => $now,
			'last_heartbeat' => $active_sessions[ $session_id ]['last_heartbeat'] ?? $now,
			'session_start'  => $session_start,
			'cookie_sid'     => $session_id,
			'display_id'     => $display_id,
			'user_id'        => $user_id ? $user_id : 0,
			'user_login'     => $user_info ? $user_info->user_login : 'Guest',
		);

		// ── 5. Save current session ──────────────────────────────────────────
		$active_sessions[ $session_id ] = $session_data;
		update_option( 'StorePulse_active_sessions', $active_sessions );

		// ── 6. Run Garbage Collection on all active sessions ─────────────────
		self::garbage_collect_sessions();
	}

	/**
	 * Garbage Collect active sessions: end sessions inactive for > 60 seconds
	 */
	public static function garbage_collect_sessions() {
		global $wpdb;
		$active_sessions = get_option( 'StorePulse_active_sessions', array() );
		if ( empty( $active_sessions ) || ! is_array( $active_sessions ) ) {
			return;
		}

		$changed = false;
		$now_ts  = current_time( 'timestamp' );
		$table_events = $wpdb->prefix . 'storepulse_events';

		foreach ( $active_sessions as $sid => $data ) {
			$last_hb = max(
				isset( $data['last_heartbeat'] ) ? strtotime( $data['last_heartbeat'] ) : 0,
				isset( $data['last_active'] ) ? strtotime( $data['last_active'] ) : 0
			);
			$time_since_hb = $now_ts - $last_hb;

			// If no heartbeat/activity for more than the expiry threshold, end the session
			if ( $time_since_hb > self::SESSION_HEARTBEAT_EXPIRY ) {
				// Check if the session has any events in the DB
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$has_events = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
					"SELECT 1 FROM $table_events WHERE session_id = %s LIMIT 1", // phpcs:ignore
					$sid
				) );

				if ( $has_events ) {
					// Check if session_end already exists to avoid duplicate ends
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$already_ended = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
						"SELECT 1 FROM $table_events WHERE session_id = %s AND event_type = 'session_end' LIMIT 1", // phpcs:ignore
						$sid
					) );

					if ( ! $already_ended ) {
						$end_ts = $last_hb > 0 ? $last_hb : $now_ts;
						$duration = $end_ts - strtotime( $data['session_start'] );
						$duration = max( 0, min( (int) $duration, 7200 ) );

						$event_data = wp_json_encode(
							array(
								'session_id' => $sid,
								'duration'   => $duration,
							)
						);

						$end_time_mysql = ( ! empty( $data['last_heartbeat'] ) && ( empty( $data['last_active'] ) || $data['last_heartbeat'] >= $data['last_active'] ) ) ? $data['last_heartbeat'] : ( $data['last_active'] ?? current_time( 'mysql' ) );

						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->insert(
							$table_events,
							array(
								'event_type'      => 'session_end',
								'session_id'      => $sid,
								'anon_id'         => $data['anon_id'] ?? '',
								'user_id'         => ! empty( $data['user_id'] ) ? $data['user_id'] : null,
								'event_data'      => $event_data,
								'event_timestamp' => $end_time_mysql,
							)
						);
					}
				}

				unset( $active_sessions[ $sid ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'StorePulse_active_sessions', $active_sessions );
		}
	}

	/**
	 * Update last_heartbeat for a session (called by REST endpoint).
	 *
	 * @param string $session_id Cookie session-ID.
	 * @param int    $user_id    User ID from the heartbeat call.
	 * @return bool
	 */
	public static function update_heartbeat( $session_id, $user_id = 0 ) {
		if ( empty( $session_id ) ) {
			return false;
		}

		global $wpdb;
		$active_sessions = get_option( 'StorePulse_active_sessions', array() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session_ended = (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}storepulse_events WHERE session_id = %s AND event_type = 'session_end' LIMIT 1",
			$session_id
		) );

		if ( ! isset( $active_sessions[ $session_id ] ) || $session_ended ) {
			if ( isset( $active_sessions[ $session_id ] ) ) {
				unset( $active_sessions[ $session_id ] );
			}

			// Generate a brand new session ID since this one has expired or ended
			if ( $user_id > 0 ) {
				$new_session_id = 'user-' . $user_id . '-' . wp_generate_uuid4();
			} else {
				$new_session_id = wp_generate_uuid4();
			}

			$user_info  = $user_id ? get_userdata( $user_id ) : null;
			$display_id = $user_id ? (string) $user_id : $new_session_id;
			$now        = current_time( 'mysql' );
			$anon_id    = isset( $_COOKIE['_store_tracker'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['_store_tracker'] ) ) : wp_generate_uuid4();

			$active_sessions[ $new_session_id ] = array(
				'anon_id'        => $anon_id,
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown',
				'ip_hash'        => md5( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ), // phpcs:ignore
				'country'        => 'Unknown',
				'city'           => 'Unknown',
				'last_active'    => $now,
				'last_heartbeat' => $now,
				'session_start'  => $now,
				'cookie_sid'     => $new_session_id,
				'display_id'     => $display_id,
				'user_id'        => $user_id ? $user_id : 0,
				'user_login'     => $user_info ? $user_info->user_login : 'Guest',
			);

			update_option( 'StorePulse_active_sessions', $active_sessions );

			// Set the cookie for current request & client browser
			setcookie( 'StorePulse_sid', $new_session_id, time() + ( 86400 * 1 ), '/', '', is_ssl(), true );
			$_COOKIE['StorePulse_sid'] = $new_session_id;

			return $new_session_id;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_info  = $user_id ? get_userdata( $user_id ) : null;
		$display_id = $user_id ? (string) $user_id : $session_id;

		$active_sessions[ $session_id ]['last_heartbeat'] = current_time( 'mysql' );
		$active_sessions[ $session_id ]['last_active']    = current_time( 'mysql' );
		
		// Update user ID if they logged in
		if ( $user_id > 0 && empty( $active_sessions[ $session_id ]['user_id'] ) ) {
			$active_sessions[ $session_id ]['user_id']    = $user_id;
			$active_sessions[ $session_id ]['user_login'] = $user_info ? $user_info->user_login : 'Logged-in User';
			$active_sessions[ $session_id ]['display_id'] = $display_id;

			$wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'storepulse_events', // phpcs:ignore
				array( 'user_id' => $user_id ), // phpcs:ignore
				array( 'session_id' => $session_id ) // phpcs:ignore
			);
		}

		update_option( 'StorePulse_active_sessions', $active_sessions );
		self::garbage_collect_sessions();
		return true;
	}

	/**
	 * End a session: record duration event, remove from active list.
	 *
	 * @param string $session_id Cookie session-ID.
	 * @param int    $duration   Duration in seconds reported by the browser.
	 * @return bool
	 */
	public static function end_session( $session_id, $duration ) {
		if ( empty( $session_id ) ) {
			return false;
		}

		// Clamp to a sane range (0 – 2 hours).
		$duration = max( 0, min( (int) $duration, 7200 ) );

		$active_sessions = get_option( 'StorePulse_active_sessions', array() );

		$end_time_mysql = current_time( 'mysql' );

		// If we have stored start time, prefer server-calculated duration.
		if ( isset( $active_sessions[ $session_id ] ) ) {
			$sess = $active_sessions[ $session_id ];
			$last_hb = max(
				isset( $sess['last_heartbeat'] ) ? strtotime( $sess['last_heartbeat'] ) : 0,
				isset( $sess['last_active'] ) ? strtotime( $sess['last_active'] ) : 0
			);
			if ( $last_hb > 0 ) {
				$end_time_mysql = ( ! empty( $sess['last_heartbeat'] ) && ( empty( $sess['last_active'] ) || $sess['last_heartbeat'] >= $sess['last_active'] ) ) ? $sess['last_heartbeat'] : ( $sess['last_active'] ?? current_time( 'mysql' ) );
				$server_dur = $last_hb - strtotime( $sess['session_start'] );
				if ( $server_dur >= 0 && $server_dur <= 7200 ) {
					$duration = $server_dur;
				}
			} else {
				$server_dur = current_time( 'timestamp' )
					- strtotime( $sess['session_start'] );
				// Use server duration if it's larger and within range.
				if ( $server_dur > 0 && $server_dur <= 7200 ) {
					$duration = $server_dur;
				}
			}
		}

		// Store a session_end event only if the session has other events to avoid empty sessions in DB.
		global $wpdb;
		$table_events = $wpdb->prefix . 'storepulse_events';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_events = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT 1 FROM $table_events WHERE session_id = %s LIMIT 1", // phpcs:ignore
			$session_id
		) );

		if ( $has_events ) {
			// Check if session_end already exists to avoid duplicate ends
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$already_ended = (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				"SELECT 1 FROM $table_events WHERE session_id = %s AND event_type = 'session_end' LIMIT 1", // phpcs:ignore
				$session_id
			) );

			if ( ! $already_ended ) {
				$event_data = wp_json_encode(
					array(
						'session_id' => $session_id,
						'duration'   => $duration,
					)
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$table_events,
					array(
						'event_type'      => 'session_end',
						'session_id'      => $session_id,
						'anon_id'         => $active_sessions[ $session_id ]['anon_id'] ?? StorePulse_get_anon_id(),
						'user_id'         => ( ! empty( $active_sessions[ $session_id ]['user_id'] ) ? $active_sessions[ $session_id ]['user_id'] : null ) ?: ( get_current_user_id() ?: null ),
						'event_data'      => $event_data,
						'event_timestamp' => $end_time_mysql,
					)
				);
			}
		}

		// Remove from active sessions.
		unset( $active_sessions[ $session_id ] );
		update_option( 'StorePulse_active_sessions', $active_sessions );

		return true;
	}

	/**
	 * Return only sessions with a recent heartbeat.
	 * Used by the Real-Time page.
	 *
	 * @param int $timeout_seconds Max seconds since last heartbeat. Default 90.
	 * @return array
	 */
	public static function get_realtime_sessions( $timeout_seconds = self::HEARTBEAT_TIMEOUT ) {
		$all     = get_option( 'StorePulse_active_sessions', array() );
		$now     = current_time( 'timestamp' );
		$active  = array();

		foreach ( $all as $sid => $data ) {
			// Prefer last_heartbeat; fall back to last_active for older records.
			$hb_ts = isset( $data['last_heartbeat'] )
				? strtotime( $data['last_heartbeat'] )
				: strtotime( $data['last_active'] ?? '0' );

			if ( $now - $hb_ts <= $timeout_seconds ) {
				$active[ $sid ] = $data;
			}
		}

		// Group and de-duplicate by unique User ID (if logged in) or Anonymous ID (if guest).
		$grouped = array();
		foreach ( $active as $sid => $data ) {
			$uid     = ! empty( $data['user_id'] ) ? (int) $data['user_id'] : 0;
			$anon_id = $data['anon_id'] ?? '';
			
			$key = $uid > 0 ? 'user-' . $uid : 'anon-' . $anon_id;

			$hb_ts = isset( $data['last_heartbeat'] )
				? strtotime( $data['last_heartbeat'] )
				: strtotime( $data['last_active'] ?? '0' );

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array(
					'sid'   => $sid,
					'hb_ts' => $hb_ts,
					'data'  => $data,
				);
			} else {
				// Retain the session with the most recent activity.
				if ( $hb_ts > $grouped[ $key ]['hb_ts'] ) {
					$grouped[ $key ] = array(
						'sid'   => $sid,
						'hb_ts' => $hb_ts,
						'data'  => $data,
					);
				}
			}
		}

		$unique_active = array();
		foreach ( $grouped as $item ) {
			$unique_active[ $item['sid'] ] = $item['data'];
		}

		return $unique_active;
	}

	/* ────────────────────────────── private ───────────────────────── */

	private function fetch_geo( $ip ) {
		// Primary: ip-api.com
		$response = wp_remote_get(
			"https://ip-api.com/json/{$ip}?fields=country,city",
			array( 'timeout' => 5 )
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $data['country'] ) ) {
				return array(
					'country' => $data['country'],
					'city'    => $data['city'] ?? 'Unknown',
				);
			}
		}

		// Fallback: ipapi.co
		$fallback = wp_remote_get(
			"https://ipapi.co/{$ip}/json/",
			array( 'timeout' => 5 )
		);

		if ( ! is_wp_error( $fallback ) && 200 === wp_remote_retrieve_response_code( $fallback ) ) {
			$fd = json_decode( wp_remote_retrieve_body( $fallback ), true );
			if ( isset( $fd['country_name'] ) ) {
				return array(
					'country' => $fd['country_name'],
					'city'    => $fd['city'] ?? 'Unknown',
				);
			}
		}

		return array( 'country' => 'Unknown', 'city' => 'Unknown' );
	}

	/**
	 * Link Anonymous ID to User ID on login
	 */
	public function link_anon_to_user( $user_login, $user ) {
		if ( ! $user instanceof \WP_User ) {
			return;
		}
		$this->perform_identity_stitch( $user->ID );
	}

	/**
	 * Link Anonymous ID to User ID on registration
	 */
	public function link_anon_to_user_register( $user_id ) {
		$this->perform_identity_stitch( $user_id );
	}

	private function perform_identity_stitch( $user_id ) {
		if ( empty( $user_id ) ) {
			return;
		}

		$anon_id = isset( $_COOKIE['_store_tracker'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['_store_tracker'] ) ) : '';
		if ( empty( $anon_id ) ) {
			return;
		}

		global $wpdb;
		$table_identities = $wpdb->prefix . 'storepulse_identities';
		$table_events     = $wpdb->prefix . 'storepulse_events';

		// Check if mapping already exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
			"SELECT * FROM $table_identities WHERE anon_id = %s AND user_id = %d", // phpcs:ignore
			$anon_id,
			$user_id
		) );

		$now = current_time( 'mysql' );

		if ( ! $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_identities,
				array(
					'anon_id'           => $anon_id,
					'user_id'           => $user_id,
					'first_linked_at'   => $now,
					'last_seen_as_user' => $now,
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_identities,
				array( 'last_seen_as_user' => $now ),
				array( 'anon_id' => $anon_id, 'user_id' => $user_id )
			);
		}

		// Retroactively associate all past guest activity for this anon_id with the real user
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_events,
			array( 'user_id' => $user_id ),
			array( 'anon_id' => $anon_id )
		);

		// Also update the audit logs table (wp_storepulse_logs) for this session
		$table_logs = $wpdb->prefix . 'storepulse_logs';
		$session_id = StorePulse_get_session_id();
		$user_info  = get_userdata( $user_id );
		if ( $user_info ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_logs,
				array(
					'user_id'    => $user_id,
					'user_name'  => $user_info->display_name,
					'user_email' => $user_info->user_email,
					'user_role'  => ! empty( $user_info->roles ) ? $user_info->roles[0] : '',
					'is_guest'   => 0,
				),
				array( 'session_id' => $session_id )
			);
		}

		// Remove any premature session_end event recorded during login inactivity/redirect
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table_events,
			array(
				'session_id' => $session_id,
				'event_type' => 'session_end',
			)
		);

		// Restore/update the session in active sessions list so it is not considered expired
		$active_sessions = get_option( 'StorePulse_active_sessions', array() );
		if ( ! is_array( $active_sessions ) ) {
			$active_sessions = array();
		}

		$display_id = (string) $user_id;
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: 'Unknown';
		$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; // phpcs:ignore

		if ( ! isset( $active_sessions[ $session_id ] ) ) {
			$active_sessions[ $session_id ] = array(
				'anon_id'        => $anon_id,
				'user_agent'     => $user_agent,
				'ip_hash'        => md5( $ip ),
				'country'        => 'Unknown',
				'city'           => 'Unknown',
				'last_active'    => $now,
				'last_heartbeat' => $now,
				'session_start'  => $now,
				'cookie_sid'     => $session_id,
				'display_id'     => $display_id,
				'user_id'        => $user_id,
				'user_login'     => $user_info ? $user_info->user_login : 'Logged-in User',
			);
		} else {
			$active_sessions[ $session_id ]['user_id']       = $user_id;
			$active_sessions[ $session_id ]['user_login']    = $user_info ? $user_info->user_login : 'Logged-in User';
			$active_sessions[ $session_id ]['display_id']    = $display_id;
			$active_sessions[ $session_id ]['last_active']   = $now;
			$active_sessions[ $session_id ]['last_heartbeat'] = $now;
		}
		update_option( 'StorePulse_active_sessions', $active_sessions );
	}
}

// Initialize tracker.
StorePulse_Session_Tracker::get_instance();
