<?php
/**
 * Google Analytics authentication class for StorePulse plugin.
 *
 * @package StorePulse
 */

namespace SmackCoders\WGA;

class GA_Auth {

	const AUTH_OPTION_NAME     = 'StorePulse_auth';
	const SETTINGS_OPTION_NAME = 'storepulse_settings';

	// Activate and deactivate methods to manage options.
	public static function activate() {
		add_option( self::AUTH_OPTION_NAME, array() );
	}

	public static function deactivate() {
		delete_option( self::AUTH_OPTION_NAME );
	}

	// Get stored options, such as access_token, refresh_token.
	public static function get_options() {
		return get_option( self::AUTH_OPTION_NAME, array() );
	}

	// Update the options (add new values without overwriting the old ones).
	public static function update_options( $data ) {
		update_option( self::AUTH_OPTION_NAME, array_merge( self::get_options(), $data ) );
	}

	// Generate Google OAuth authorization URL.
	public static function get_auth_url( $client_id ) {
		$redirect_uri = admin_url( 'admin.php?page=wp-seo-insights' );
		$scope        = 'https://www.googleapis.com/auth/analytics.readonly';
		$state        = wp_create_nonce( 'StorePulse_google_auth' );
		return "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id={$client_id}&redirect_uri=" . urlencode( $redirect_uri ) . '&scope=' . urlencode( $scope ) . '&access_type=offline&prompt=consent&state=' . urlencode( $state );
	}

	// Handle OAuth callback after user authenticates with Google.
	public static function handle_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$get_code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$get_page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$get_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		if ( empty( $get_code ) || 'wp-seo-insights' !== $get_page || ! wp_verify_nonce( $get_state, 'StorePulse_google_auth' ) ) {
			return;
		}

		$code     = $get_code;
		$settings = get_option( self::SETTINGS_OPTION_NAME, array() );

		if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) ) {
			return;
		}

		// Exchange the authorization code for an access token and refresh token.
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
					'redirect_uri'  => admin_url( 'admin.php?page=wp-seo-insights' ),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			self::update_options(
				array(
					'access_token'  => $body['access_token'],
					'refresh_token' => $body['refresh_token'],
					'token_expiry'  => time() + 3600, // Access token expires in 1 hour.
				)
			);

			// Redirect to the Authenticated tab (Status section).
			wp_safe_redirect( admin_url( 'admin.php?page=wp-seo-insights&step=authenticated' ) );
			exit;
		}
	}

	// Refresh the access token using the stored refresh token.
	public static function refresh_access_token() {
		$options  = self::get_options();
		$settings = get_option( self::SETTINGS_OPTION_NAME, array() );

		if ( empty( $options['refresh_token'] ) ) {
			return array( 'error' => 'Refresh token is missing.' );
		}

		if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) ) {
			return array( 'error' => 'Client credentials missing.' );
		}

		// Make a request to refresh the access token.
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
					'refresh_token' => $options['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			// Update the stored access token and its expiration time.
			self::update_options(
				array(
					'access_token' => $body['access_token'],
					'token_expiry' => time() + ( $body['expires_in'] ?? 3600 ), // Default to 1 hour if not provided.
				)
			);
			return array( 'access_token' => $body['access_token'] );
		}

		return array( 'error' => 'Failed to refresh access token.' );
	}

	// Get a valid access token, refresh it if needed.
	public static function get_access_token() {
		$options = self::get_options();

		// Check if the access token exists and has not expired.
		if ( empty( $options['access_token'] ) || time() > $options['token_expiry'] ) {
			// Token is expired, try to refresh it.
			$result = self::refresh_access_token();
			if ( isset( $result['access_token'] ) ) {
				return $result['access_token'];
			} else {
				return array( 'error' => 'Failed to refresh access token.' );
			}
		}

		// Return the valid access token.
		return $options['access_token'];
	}
}
