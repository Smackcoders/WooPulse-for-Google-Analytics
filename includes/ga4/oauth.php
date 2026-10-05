<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GA_Auth {
	public static function get_access_token() {
		return PulseAnalytics_GA4_OAuth::get_access_token();
	}

	public static function get_auth_url( $client_id = '', $include_edit = true ) {
		return PulseAnalytics_GA4_OAuth::get_auth_url( $include_edit );
	}

	public static function refresh_access_token() {
		return PulseAnalytics_GA4_OAuth::refresh_access_token();
	}

	public static function update_options( $options = array() ) {
		if ( ! is_array( $options ) ) {
			return false;
		}
		$access  = (string) ( $options['access_token'] ?? '' );
		$refresh = (string) ( $options['refresh_token'] ?? '' );
		$expires = (int) ( $options['expires_at'] ?? 0 );
		if ( function_exists( 'sm_pulse_analytics_decrypt' ) ) {
			if ( '' !== $access && 0 === strpos( $access, 'sp_enc:' ) ) {
				$access = sm_pulse_analytics_decrypt( $access );
			}
			if ( '' !== $refresh && 0 === strpos( $refresh, 'sp_enc:' ) ) {
				$refresh = sm_pulse_analytics_decrypt( $refresh );
			}
		}
		return PulseAnalytics_GA4_OAuth::save_auth_tokens( $access, $refresh, $expires );
	}

	public static function activate() {}
	public static function deactivate() {}
}

class PulseAnalytics_GA4_OAuth {

	const AUTH_OPTION         = 'sm_pulse_analytics_ga4_auth_tokens';
	const OAUTH_ERROR_TRANSIENT = 'sm_pulse_analytics_oauth_error';

	/**
	 * Prevent duplicate token exchange when both load-* and admin_init fire.
	 *
	 * @var bool
	 */
	private static $redirect_callback_handled = false;

	public static function init() {
		add_action( 'load-admin_page_sm-pulse-analytics-gsc-oauth', array( __CLASS__, 'handle_redirect_callback' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'handle_redirect_callback' ), 1 );
	}

	public static function get_auth_tokens() {
		return self::resolve_auth_tokens();
	}

	/**
	 * Load auth tokens from wp-config.php, migrating legacy database storage when needed.
	 *
	 * @return array{access_token?:string,refresh_token?:string,expires_at?:int}
	 */
	private static function resolve_auth_tokens() {
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			$access  = PulseAnalytics_Secure_Credentials::get_ga4_access_token();
			$refresh = PulseAnalytics_Secure_Credentials::get_ga4_refresh_token();
			if ( '' !== $access || '' !== $refresh ) {
				return array(
					'access_token'  => $access,
					'refresh_token' => $refresh,
					'expires_at'    => PulseAnalytics_Secure_Credentials::get_ga4_token_expires_at(),
				);
			}
		}

		$legacy_keys = array(
			self::AUTH_OPTION,
			'StorePulse_ga4_auth_tokens',
			'sm_pulse_analytics_auth',
		);

		foreach ( $legacy_keys as $legacy_key ) {
			$legacy = get_option( $legacy_key, array() );
			if ( ! is_array( $legacy ) || ( empty( $legacy['access_token'] ) && empty( $legacy['refresh_token'] ) ) ) {
				continue;
			}

			$access  = (string) $legacy['access_token'];
			$refresh = (string) ( $legacy['refresh_token'] ?? '' );
			$expires = (int) ( $legacy['expires_at'] ?? 0 );

			if ( 'sm_pulse_analytics_auth' === $legacy_key && $expires <= 0 && ! empty( $legacy['token_expiry'] ) ) {
				$expires = (int) $legacy['token_expiry'];
			}

			if ( function_exists( 'sm_pulse_analytics_decrypt' ) ) {
				if ( 0 === strpos( $access, 'sp_enc:' ) ) {
					$access = sm_pulse_analytics_decrypt( $access );
				}
				if ( '' !== $refresh && 0 === strpos( $refresh, 'sp_enc:' ) ) {
					$refresh = sm_pulse_analytics_decrypt( $refresh );
				}
			}

			if ( '' !== $access ) {
				self::save_auth_tokens( $access, $refresh, $expires );
				self::clear_auth_token_options();
				return array(
					'access_token'  => $access,
					'refresh_token' => $refresh,
					'expires_at'    => $expires,
				);
			}
		}

		return array();
	}

	/**
	 * Persist OAuth tokens to encrypted wp-config.php constants.
	 *
	 * @param string $access_token  Plaintext access token.
	 * @param string $refresh_token Plaintext refresh token.
	 * @param int    $expires_at    Unix expiry timestamp.
	 * @return bool
	 */
	public static function save_auth_tokens( $access_token, $refresh_token, $expires_at ) {
		if ( ! class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			return false;
		}

		$saved = PulseAnalytics_Secure_Credentials::save_ga4_auth_tokens(
			(string) $access_token,
			(string) $refresh_token,
			(int) $expires_at
		);

		if ( $saved ) {
			self::clear_auth_token_options();
		}

		return $saved;
	}

	/**
	 * Remove OAuth token rows from the database after wp-config persistence.
	 *
	 * @return void
	 */
	private static function clear_auth_token_options() {
		delete_option( self::AUTH_OPTION );
		delete_option( 'sm_pulse_analytics_auth' );
		delete_option( 'StorePulse_ga4_auth_tokens' );
	}

	public static function refresh_access_token() {
		$tokens        = self::get_auth_tokens();
		$refresh_token = $tokens['refresh_token'] ?? '';
		$client_id     = self::get_client_id();
		$client_secret = self::get_client_secret();

		if ( empty( $refresh_token ) || empty( $client_id ) || empty( $client_secret ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 30,
				'body'    => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['access_token'] ) ) {
				$new_access_token = sanitize_text_field( $data['access_token'] );
				$expires_at       = time() + ( isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600 );

				if ( self::save_auth_tokens( $new_access_token, $refresh_token, $expires_at ) ) {
					/**
					 * Fired after a silent token refresh persists new credentials.
					 * No credentials are passed — listeners read via get_access_token().
					 */
					do_action( 'sm_pulse_analytics_ga4_oauth_tokens_saved' );
					return $new_access_token;
				}
			}
		}
		return false;
	}

	public static function get_access_token() {
		$tokens     = self::get_auth_tokens();
		$expires_at = (int) ( $tokens['expires_at'] ?? 0 );

		if ( empty( $tokens['access_token'] ) || ( $expires_at > 0 && time() >= ( $expires_at - 60 ) ) ) {
			$refreshed = self::refresh_access_token();
			if ( $refreshed ) {
				return $refreshed;
			}
		}

		return $tokens['access_token'] ?? '';
	}

	public static function is_connected() {
		$state = self::get_connection_state();
		return ! empty( $state['oauth_connected'] ) && ! empty( $state['token_valid'] );
	}

	/**
	 * Unified GA4 OAuth + configuration state for admin UI.
	 *
	 * @param bool $attempt_refresh Whether to try refreshing an expired token first.
	 * @return array{
	 *   oauth_connected: bool,
	 *   token_valid: bool,
	 *   token_expired: bool,
	 *   token_minutes_left: int,
	 *   has_property_id: bool,
	 *   has_measurement_id: bool,
	 *   is_ready: bool,
	 *   property_id: string,
	 *   measurement_id: string
	 * }
	 */
	public static function get_connection_state( $attempt_refresh = false ) {
		if ( $attempt_refresh ) {
			self::get_access_token();
		}

		$tokens         = self::get_auth_tokens();
		$expires_at     = (int) ( $tokens['expires_at'] ?? 0 );
		$oauth_connected = ! empty( $tokens['access_token'] );
		$token_expired  = $oauth_connected && $expires_at > 0 && time() >= ( $expires_at - 60 );
		$token_valid    = $oauth_connected && ! $token_expired;

		$property_id    = class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' )
			? PulseAnalytics_GA4_Config::get_property_id()
			: '';
		$measurement_id = class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' )
			? PulseAnalytics_GA4_Config::get_measurement_id()
			: '';

		$has_property_id    = '' !== $property_id;
		$has_measurement_id = '' !== $measurement_id;
		$token_minutes_left = ( $oauth_connected && $expires_at > 0 && ! $token_expired )
			? max( 0, (int) floor( ( $expires_at - time() ) / 60 ) )
			: 0;

		return array(
			'oauth_connected'    => $oauth_connected,
			'token_valid'        => $token_valid,
			'token_expired'      => $token_expired,
			'token_minutes_left' => $token_minutes_left,
			'has_property_id'    => $has_property_id,
			'has_measurement_id' => $has_measurement_id,
			'is_ready'           => $token_valid && $has_property_id,
			'property_id'        => $property_id,
			'measurement_id'     => $measurement_id,
		);
	}

	/**
	 * Human-readable token status for settings UI.
	 *
	 * @param array $state Connection state from get_connection_state().
	 * @return string
	 */
	/**
	 * Whether OAuth access or refresh tokens are stored.
	 *
	 * @return bool
	 */
	public static function has_stored_credentials() {
		$tokens = self::get_auth_tokens();
		return ! empty( $tokens['access_token'] ) || ! empty( $tokens['refresh_token'] );
	}

	/**
	 * Map Google OAuth error codes to admin-friendly messages.
	 *
	 * @param string $error_code Google error code.
	 * @return string
	 */
	private static function format_oauth_error_message( $error_code ) {
		$messages = array(
			'invalid_client'       => __( 'Invalid OAuth client credentials. Check your Client ID and Client Secret in wp-config.php.', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'redirect_uri_mismatch' => __( 'Redirect URI mismatch. Add the Authorized Redirect URI shown on this page to your Google Cloud OAuth client.', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'invalid_grant'        => __( 'Authorization code expired or already used. Click Sign in with Google again.', 'smackcoders-pulse-analytics-for-woocommerce' ),
			'access_denied'        => __( 'Google sign-in was cancelled or denied.', 'smackcoders-pulse-analytics-for-woocommerce' ),
		);

		$error_code = sanitize_key( (string) $error_code );
		if ( isset( $messages[ $error_code ] ) ) {
			return $messages[ $error_code ];
		}

		return __( 'Google OAuth connection failed. Verify your credentials and redirect URI, then try again.', 'smackcoders-pulse-analytics-for-woocommerce' );
	}

	public static function get_token_status_label( $state ) {
		if ( empty( $state['oauth_connected'] ) ) {
			return __( 'Inactive', 'smackcoders-pulse-analytics-for-woocommerce' );
		}
		if ( ! empty( $state['token_expired'] ) ) {
			return __( 'Expired — click Refresh Token', 'smackcoders-pulse-analytics-for-woocommerce' );
		}
		if ( ! empty( $state['token_minutes_left'] ) ) {
			return sprintf(
				/* translators: %d: minutes until token expiry */
				__( 'Expires in %d min', 'smackcoders-pulse-analytics-for-woocommerce' ),
				(int) $state['token_minutes_left']
			);
		}
		return __( 'Active', 'smackcoders-pulse-analytics-for-woocommerce' );
	}

	public static function get_client_id() {
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) ) {
			return \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::get_client_id();
		}
		return '';
	}

	public static function get_client_secret() {
		if ( class_exists( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_Config' ) ) {
			return \Sm_Pulse_Analytics\PulseAnalytics_GA4_Config::get_client_secret();
		}
		return '';
	}

	public static function get_redirect_uri() {
		return admin_url( 'admin.php?page=sm-pulse-analytics-gsc-oauth' );
	}

	public static function get_auth_url( $include_edit = false ) {
		$client_id = self::get_client_id();
		if ( empty( $client_id ) ) {
			return '#';
		}
		$redirect_uri = self::get_redirect_uri();
		$scopes_arr   = array(
			'https://www.googleapis.com/auth/analytics.readonly',
		);

		/**
		 * Filter the GA4 OAuth scope array before building the auth URL.
		 * PRO hooks here to append 'analytics.edit' when active.
		 * Do NOT add credential-bearing data to this filter.
		 *
		 * @param string[] $scopes_arr Current scope strings.
		 */
		$scopes_arr = (array) apply_filters( 'sm_pulse_analytics_ga4_oauth_scopes', $scopes_arr );
		$scopes_arr = array_values( array_unique( array_filter( array_map( 'strval', $scopes_arr ) ) ) );

		$scopes = implode( ' ', $scopes_arr );

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(
			array(
				'client_id'     => $client_id,
				'redirect_uri'  => $redirect_uri,
				'response_type' => 'code',
				'scope'         => $scopes,
				'access_type'   => 'offline',
				'prompt'        => 'consent',
				'state'         => wp_create_nonce( 'sm_pulse_analytics_ga4_oauth' ),
			)
		);
	}

	public static function handle_redirect_callback() {
		if ( self::$redirect_callback_handled ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		$is_oauth_return = ( 'sm-pulse-analytics-gsc-oauth' === $page || 'ga4_callback' === $step );

		if ( $is_oauth_return && ! empty( $code ) ) {
			self::$redirect_callback_handled = true;
			if ( ! current_user_can( 'sm_pulse_analytics_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to connect Google Analytics.', 'smackcoders-pulse-analytics-for-woocommerce' ), 403 );
			}
			if ( empty( $state ) || ! wp_verify_nonce( $state, 'sm_pulse_analytics_ga4_oauth' ) ) {
				wp_die( esc_html__( 'Invalid OAuth state. Please try connecting again.', 'smackcoders-pulse-analytics-for-woocommerce' ), 403 );
			}

			$redirect_args = array(
				'page' => 'sm-pulse-analytics-settings',
				'step' => 'analytics',
			);

			if ( ! class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Config' )
				|| ! PulseAnalytics_GA4_Config::has_oauth_app_credentials() ) {
				set_transient(
					self::OAUTH_ERROR_TRANSIENT,
					__( 'OAuth client credentials are missing or unreadable from wp-config.php.', 'smackcoders-pulse-analytics-for-woocommerce' ),
					45
				);
				$redirect_args['ga4_oauth_error'] = '1';
				wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
				exit;
			}

			$client_id     = self::get_client_id();
			$client_secret = self::get_client_secret();
			$redirect_uri  = self::get_redirect_uri();

			$response = wp_remote_post(
				'https://oauth2.googleapis.com/token',
				array(
					'timeout' => 30,
					'body'    => array(
						'code'          => $code,
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'redirect_uri'  => $redirect_uri,
						'grant_type'    => 'authorization_code',
					),
				)
			);

			$discovery_result = array(
				'success'     => false,
				'discoveries' => array(),
			);
			$oauth_success    = false;

			if ( is_wp_error( $response ) ) {
				set_transient(
					self::OAUTH_ERROR_TRANSIENT,
					$response->get_error_message(),
					45
				);
			} else {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $data['access_token'] ) ) {
					$access_token  = sanitize_text_field( $data['access_token'] );
					$existing      = self::get_auth_tokens();
					$refresh_token = ! empty( $data['refresh_token'] ) ? sanitize_text_field( $data['refresh_token'] ) : ( $existing['refresh_token'] ?? '' );
					$expires_at    = time() + ( isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600 );

					if ( self::save_auth_tokens( $access_token, $refresh_token, $expires_at ) ) {
						/**
						 * Fired after new GA4 OAuth tokens are encrypted and persisted.
						 * IMPORTANT: No credentials are passed as arguments.
						 * Listeners that need the token must call:
						 *   PulseAnalytics_GA4_OAuth::get_access_token()
						 */
						do_action( 'sm_pulse_analytics_ga4_oauth_tokens_saved' );

						if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Admin_API' ) ) {
							$discovery_result = PulseAnalytics_GA4_Admin_API::fetch_and_cache_discoveries();
						}
						$oauth_success = true;
					} else {
						set_transient(
							self::OAUTH_ERROR_TRANSIENT,
							__( 'Could not write OAuth tokens to wp-config.php. Make wp-config.php writable, then connect again.', 'smackcoders-pulse-analytics-for-woocommerce' ),
							45
						);
					}
				} else {
					$error_code = ! empty( $data['error'] ) ? (string) $data['error'] : 'unknown';
					set_transient(
						self::OAUTH_ERROR_TRANSIENT,
						self::format_oauth_error_message( $error_code ),
						45
					);
				}
			}

			if ( $oauth_success ) {
				$redirect_args['ga4_connected'] = '1';
				if ( ! empty( $discovery_result['success'] ) && ! empty( $discovery_result['discoveries'] ) ) {
					$redirect_args['ga4_pick'] = '1';
				}
			} else {
				$redirect_args['ga4_oauth_error'] = '1';
			}

			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public static function disconnect() {
		self::clear_auth_token_options();
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_Secure_Credentials' ) ) {
			PulseAnalytics_Secure_Credentials::clear_ga4_auth_tokens();
		}
		if ( class_exists( __NAMESPACE__ . '\PulseAnalytics_GA4_Admin_API' ) ) {
			PulseAnalytics_GA4_Admin_API::clear_cached_discoveries();
		}
		return true;
	}
}

class_alias( '\Sm_Pulse_Analytics\PulseAnalytics_GA4_OAuth', 'Sm_Pulse_Analytics_GA4_OAuth' );
class_alias( '\Sm_Pulse_Analytics\GA_Auth', 'Sm_Pulse_Analytics_GA_Auth' );


