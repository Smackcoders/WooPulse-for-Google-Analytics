<?php
/**
 * Encrypted wp-config.php credential storage (private crypto).
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */

namespace Sm_Pulse_Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secrets live only as encrypted constants in wp-config.php.
 * Encrypt/decrypt are private — external classes cannot call them.
 */
final class PulseAnalytics_Secure_Credentials {

	const PREFIX = 'sp_cfg:';

	const CONFIG_BLOCK_MARKER = '// Sm Pulse Analytics — encrypted credentials';

	const CONST_GA4_CLIENT_ID        = 'SM_PULSE_ANALYTICS_GA4_CLIENT_ID';
	const CONST_GA4_CLIENT_SECRET    = 'SM_PULSE_ANALYTICS_GA4_CLIENT_SECRET';
	const CONST_GA4_API_SECRET       = 'SM_PULSE_ANALYTICS_GA4_API_SECRET';
	const CONST_GA4_ACCESS_TOKEN     = 'SM_PULSE_ANALYTICS_GA4_ACCESS_TOKEN';
	const CONST_GA4_REFRESH_TOKEN    = 'SM_PULSE_ANALYTICS_GA4_REFRESH_TOKEN';
	const CONST_GA4_TOKEN_EXPIRES_AT = 'SM_PULSE_ANALYTICS_GA4_TOKEN_EXPIRES_AT';
	const CONST_GA4_PROPERTY_ID      = 'SM_PULSE_ANALYTICS_GA4_PROPERTY_ID';
	const CONST_GA4_MEASUREMENT_ID   = 'SM_PULSE_ANALYTICS_GA4_MEASUREMENT_ID';
	const CONST_GA4_STREAM_ID        = 'SM_PULSE_ANALYTICS_GA4_STREAM_ID';

	/**
	 * Plaintext overrides for credentials written during the current request.
	 *
	 * @var array<string, string>
	 */
	private static $runtime_values = array();

	/**
	 * Optional wp-config path override (PHPUnit only).
	 *
	 * @var string
	 */
	private static $test_wp_config_path = '';

	/**
	 * Whether a credential constant is defined and non-empty.
	 *
	 * @param string $constant Constant name.
	 * @return bool
	 */
	public static function is_configured( $constant ) {
		$raw = self::read_constant( (string) $constant );
		return '' !== $raw;
	}

	/**
	 * Decrypted plaintext for a wp-config constant.
	 *
	 * @param string $constant Constant name.
	 * @return string
	 */
	public static function get_decrypted_value( $constant ) {
		return self::get_decrypted( (string) $constant );
	}

	/**
	 * Whether a constant is defined and decrypts to a non-empty value.
	 *
	 * @param string $constant Constant name.
	 * @return bool
	 */
	public static function is_usable( $constant ) {
		return '' !== self::get_decrypted_value( $constant );
	}

	/**
	 * Whether GA4 OAuth client ID and secret are readable from wp-config.php.
	 *
	 * @return bool
	 */
	public static function has_ga4_oauth_credentials() {
		return self::is_usable( self::CONST_GA4_CLIENT_ID )
			&& self::is_usable( self::CONST_GA4_CLIENT_SECRET );
	}

	/**
	 * Build a ready-to-paste define() line for wp-config.php.
	 *
	 * @param string $constant Constant name.
	 * @param string $plain    Plaintext secret.
	 * @return string
	 */
	public static function build_wp_config_define( $constant, $plain ) {
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		$plain    = (string) $plain;
		if ( '' === $constant || '' === $plain ) {
			return '';
		}
		$cipher = self::encrypt( $plain );
		if ( '' === $cipher ) {
			return '';
		}
		$escaped = str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $cipher );
		return "define( '{$constant}', '{$escaped}' );";
	}

	/**
	 * Absolute path to wp-config.php when discoverable.
	 *
	 * @return string
	 */
	public static function get_wp_config_path() {
		if ( '' !== self::$test_wp_config_path ) {
			return self::$test_wp_config_path;
		}

		if ( ! defined( 'ABSPATH' ) ) {
			return '';
		}

		$local = ABSPATH . 'wp-config.php';
		if ( is_readable( $local ) ) {
			return $local;
		}

		$parent = dirname( ABSPATH ) . '/wp-config.php';
		if ( is_readable( $parent ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $parent;
		}

		return '';
	}

	/**
	 * Override wp-config path for automated tests.
	 *
	 * @param string $path Absolute path to a writable config file.
	 * @return void
	 */
	public static function set_test_wp_config_path( $path ) {
		self::$test_wp_config_path = (string) $path;
	}

	/**
	 * Reset in-memory credential overrides between automated tests.
	 *
	 * @return void
	 */
	public static function reset_runtime_state() {
		self::$runtime_values = array();
	}

	/**
	 * Whether a constant may be written to wp-config.php by this plugin.
	 *
	 * @param string $constant Constant name.
	 * @return bool
	 */
	public static function is_writable_constant( $constant ) {
		$allowed = array(
			self::CONST_GA4_CLIENT_ID,
			self::CONST_GA4_CLIENT_SECRET,
			self::CONST_GA4_API_SECRET,
			self::CONST_GA4_ACCESS_TOKEN,
			self::CONST_GA4_REFRESH_TOKEN,
			self::CONST_GA4_PROPERTY_ID,
			self::CONST_GA4_MEASUREMENT_ID,
			self::CONST_GA4_STREAM_ID,
		);

		return in_array( preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) ), $allowed, true );
	}

	/**
	 * Whether a plain (non-encrypted) integer constant may be written to wp-config.php.
	 *
	 * @param string $constant Constant name.
	 * @return bool
	 */
	public static function is_writable_plain_constant( $constant ) {
		return self::CONST_GA4_TOKEN_EXPIRES_AT === preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
	}

	/**
	 * Whether a config file path is writable (WP_Filesystem in production; temp path in PHPUnit).
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private static function is_config_path_writable( $path ) {
		$path = (string) $path;
		if ( '' === $path ) {
			return false;
		}

		if ( '' !== self::$test_wp_config_path && $path === self::$test_wp_config_path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- PHPUnit temp wp-config only.
			return is_writable( $path );
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			add_filter(
				'filesystem_method',
				static function () {
					return 'direct';
				}
			);
			if ( false === WP_Filesystem( false, false, true ) ) {
				return false;
			}
		}

		return ! empty( $wp_filesystem ) && is_object( $wp_filesystem ) && $wp_filesystem->is_writable( $path );
	}

	/**
	 * Encrypt and persist a credential constant in wp-config.php.
	 *
	 * @param string      $constant Constant name.
	 * @param string      $plain    Plaintext secret.
	 * @param string|null $path     Optional wp-config path (for tests).
	 * @return bool
	 */
	public static function write_constant( $constant, $plain, $path = null ) {
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		$plain    = (string) $plain;
		if ( '' === $constant || '' === $plain || ! self::is_writable_constant( $constant ) ) {
			return false;
		}

		$line = self::build_wp_config_define( $constant, $plain );
		if ( '' === $line ) {
			return false;
		}

		if ( null === $path ) {
			$path = self::get_wp_config_path();
		}
		$path = (string) $path;
		if ( '' === $path || ! self::is_config_path_writable( $path ) ) {
			return false;
		}

		if ( ! self::upsert_define_line_in_file( $path, $constant, $line ) ) {
			return false;
		}

		self::set_runtime_value( $constant, $plain );
		self::define_runtime_constant( $constant, $plain );

		return true;
	}

	/**
	 * Persist a plain integer constant in wp-config.php (not encrypted).
	 *
	 * @param string      $constant Constant name.
	 * @param int         $value    Integer value.
	 * @param string|null $path     Optional wp-config path (for tests).
	 * @return bool
	 */
	public static function write_plain_constant( $constant, $value, $path = null ) {
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		$value    = (int) $value;
		if ( '' === $constant || ! self::is_writable_plain_constant( $constant ) ) {
			return false;
		}

		$line = "define( '{$constant}', {$value} );";
		if ( null === $path ) {
			$path = self::get_wp_config_path();
		}
		$path = (string) $path;
		if ( '' === $path || ! self::is_config_path_writable( $path ) ) {
			return false;
		}

		if ( ! self::upsert_plain_define_line_in_file( $path, $constant, $line ) ) {
			return false;
		}

		self::set_runtime_value( $constant, (string) $value );
		if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_TOKEN_EXPIRES_AT' ) ) {
			define( 'SM_PULSE_ANALYTICS_GA4_TOKEN_EXPIRES_AT', $value );
		}

		return true;
	}

	/**
	 * Remove a credential constant from wp-config.php.
	 *
	 * @param string $constant Constant name.
	 * @return bool
	 */
	public static function remove_constant( $constant ) {
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		if ( '' === $constant ) {
			return false;
		}

		unset( self::$runtime_values[ $constant ] );

		$path = self::get_wp_config_path();
		if ( '' === $path || ! self::is_config_path_writable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return false;
		}

		$quoted_pattern = "/\\n?define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*['\"][^'\"\\\\]*(?:\\\\.[^'\"]*)*['\"]\\s*\\)\\s*;/";
		$plain_pattern  = "/\\n?define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*\\d+\\s*\\)\\s*;/";
		$updated        = preg_replace( $quoted_pattern, '', $contents, 1 );
		if ( is_string( $updated ) ) {
			$updated = preg_replace( $plain_pattern, '', $updated, 1 );
		}

		if ( ! is_string( $updated ) || $updated === $contents ) {
			return false;
		}

		return false !== file_put_contents( $path, $updated, LOCK_EX );
	}

	/**
	 * Persist GA4 OAuth tokens in wp-config.php.
	 *
	 * @param string $access_token  Plaintext access token.
	 * @param string $refresh_token   Plaintext refresh token.
	 * @param int    $expires_at      Unix expiry timestamp.
	 * @return bool
	 */
	public static function save_ga4_auth_tokens( $access_token, $refresh_token, $expires_at ) {
		$ok = true;
		if ( '' !== (string) $access_token ) {
			$ok = self::write_constant( self::CONST_GA4_ACCESS_TOKEN, (string) $access_token ) && $ok;
		}
		if ( '' !== (string) $refresh_token ) {
			$ok = self::write_constant( self::CONST_GA4_REFRESH_TOKEN, (string) $refresh_token ) && $ok;
		}
		if ( (int) $expires_at > 0 ) {
			$ok = self::write_plain_constant( self::CONST_GA4_TOKEN_EXPIRES_AT, (int) $expires_at ) && $ok;
		}
		return $ok;
	}

	/**
	 * Remove GA4 OAuth token constants from wp-config.php.
	 *
	 * @return void
	 */
	public static function clear_ga4_auth_tokens() {
		self::remove_constant( self::CONST_GA4_ACCESS_TOKEN );
		self::remove_constant( self::CONST_GA4_REFRESH_TOKEN );
		self::remove_constant( self::CONST_GA4_TOKEN_EXPIRES_AT );
		unset(
			self::$runtime_values[ self::CONST_GA4_ACCESS_TOKEN ],
			self::$runtime_values[ self::CONST_GA4_REFRESH_TOKEN ],
			self::$runtime_values[ self::CONST_GA4_TOKEN_EXPIRES_AT ]
		);
	}

	/**
	 * Replace or append a define() line in a config file.
	 *
	 * @param string $path     Config file path.
	 * @param string $constant Constant name.
	 * @param string $line     Full define() line.
	 * @return bool
	 */
	public static function upsert_define_line_in_file( $path, $constant, $line ) {
		$path     = (string) $path;
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		$line     = trim( (string) $line );
		if ( '' === $path || '' === $constant || '' === $line || ! self::is_config_path_writable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return false;
		}

		$pattern = "/define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*['\"][^'\"\\\\]*(?:\\\\.[^'\"]*)*['\"]\\s*\\)\\s*;/";
		if ( preg_match( $pattern, $contents ) ) {
			$updated = preg_replace( $pattern, $line, $contents, 1 );
		} else {
			$marker   = "/* That's all, stop editing!";
			$stop_pos = strpos( $contents, $marker );
			$pulse_pos = strpos( $contents, self::CONFIG_BLOCK_MARKER );
			if ( false !== $pulse_pos ) {
				$insert_at = false !== $stop_pos ? $stop_pos : strlen( $contents );
				$updated   = substr( $contents, 0, $insert_at ) . $line . "\n" . substr( $contents, $insert_at );
			} else {
				$block = "\n\n" . self::CONFIG_BLOCK_MARKER . "\n" . $line . "\n";
				if ( false !== $stop_pos ) {
					$updated = substr( $contents, 0, $stop_pos ) . $block . substr( $contents, $stop_pos );
				} else {
					$updated = rtrim( $contents ) . $block;
				}
			}
		}

		if ( ! is_string( $updated ) || $updated === $contents ) {
			return false;
		}

		return false !== file_put_contents( $path, $updated, LOCK_EX );
	}

	/**
	 * Replace or append a plain integer define() line in a config file.
	 *
	 * @param string $path     Config file path.
	 * @param string $constant Constant name.
	 * @param string $line     Full define() line.
	 * @return bool
	 */
	public static function upsert_plain_define_line_in_file( $path, $constant, $line ) {
		$path     = (string) $path;
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		$line     = trim( (string) $line );
		if ( '' === $path || '' === $constant || '' === $line || ! self::is_config_path_writable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return false;
		}

		$pattern = "/define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*\\d+\\s*\\)\\s*;/";
		if ( preg_match( $pattern, $contents ) ) {
			$updated = preg_replace( $pattern, $line, $contents, 1 );
		} else {
			$marker    = "/* That's all, stop editing!";
			$stop_pos  = strpos( $contents, $marker );
			$pulse_pos = strpos( $contents, self::CONFIG_BLOCK_MARKER );
			if ( false !== $pulse_pos ) {
				$insert_at = false !== $stop_pos ? $stop_pos : strlen( $contents );
				$updated   = substr( $contents, 0, $insert_at ) . $line . "\n" . substr( $contents, $insert_at );
			} else {
				$block = "\n\n" . self::CONFIG_BLOCK_MARKER . "\n" . $line . "\n";
				if ( false !== $stop_pos ) {
					$updated = substr( $contents, 0, $stop_pos ) . $block . substr( $contents, $stop_pos );
				} else {
					$updated = rtrim( $contents ) . $block;
				}
			}
		}

		if ( ! is_string( $updated ) || $updated === $contents ) {
			return false;
		}

		return false !== file_put_contents( $path, $updated, LOCK_EX );
	}

	/**
	 * @param string $constant Constant name.
	 * @param string $plain    Plaintext value for the current request.
	 * @return void
	 */
	private static function set_runtime_value( $constant, $plain ) {
		self::$runtime_values[ preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) ) ] = (string) $plain;
	}

	/**
	 * Make a newly written wp-config constant available in the current request.
	 *
	 * @param string $constant Constant name.
	 * @param string $plain    Plaintext secret.
	 * @return void
	 */
	private static function define_runtime_constant( $constant, $plain ) {
		$cipher = self::encrypt( (string) $plain );
		if ( '' === $cipher ) {
			return;
		}

		switch ( $constant ) {
			case self::CONST_GA4_CLIENT_ID:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_CLIENT_ID' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_CLIENT_ID', $cipher );
				}
				break;
			case self::CONST_GA4_CLIENT_SECRET:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_CLIENT_SECRET' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_CLIENT_SECRET', $cipher );
				}
				break;
			case self::CONST_GA4_API_SECRET:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_API_SECRET' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_API_SECRET', $cipher );
				}
				break;
			case self::CONST_GA4_ACCESS_TOKEN:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_ACCESS_TOKEN' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_ACCESS_TOKEN', $cipher );
				}
				break;
			case self::CONST_GA4_REFRESH_TOKEN:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_REFRESH_TOKEN' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_REFRESH_TOKEN', $cipher );
				}
				break;
			case self::CONST_GA4_PROPERTY_ID:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_PROPERTY_ID' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_PROPERTY_ID', $cipher );
				}
				break;
			case self::CONST_GA4_MEASUREMENT_ID:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_MEASUREMENT_ID' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_MEASUREMENT_ID', $cipher );
				}
				break;
			case self::CONST_GA4_STREAM_ID:
				if ( ! defined( 'SM_PULSE_ANALYTICS_GA4_STREAM_ID' ) ) {
					define( 'SM_PULSE_ANALYTICS_GA4_STREAM_ID', $cipher );
				}
				break;
		}
	}

	/**
	 * @return string
	 */
	public static function get_ga4_client_id() {
		return self::get_decrypted( self::CONST_GA4_CLIENT_ID );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_client_secret() {
		return self::get_decrypted( self::CONST_GA4_CLIENT_SECRET );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_api_secret() {
		return self::get_decrypted( self::CONST_GA4_API_SECRET );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_access_token() {
		return self::get_decrypted( self::CONST_GA4_ACCESS_TOKEN );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_refresh_token() {
		return self::get_decrypted( self::CONST_GA4_REFRESH_TOKEN );
	}

	/**
	 * @return int
	 */
	public static function get_ga4_token_expires_at() {
		$constant = self::CONST_GA4_TOKEN_EXPIRES_AT;
		if ( isset( self::$runtime_values[ $constant ] ) ) {
			return (int) self::$runtime_values[ $constant ];
		}
		if ( '' !== self::$test_wp_config_path && is_readable( self::$test_wp_config_path ) ) {
			return (int) self::read_constant_from_file( self::$test_wp_config_path, $constant );
		}
		if ( defined( $constant ) ) {
			return (int) constant( $constant );
		}
		return 0;
	}

	/**
	 * @return string
	 */
	public static function get_ga4_property_id() {
		return self::get_decrypted( self::CONST_GA4_PROPERTY_ID );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_measurement_id() {
		return self::get_decrypted( self::CONST_GA4_MEASUREMENT_ID );
	}

	/**
	 * @return string
	 */
	public static function get_ga4_stream_id() {
		return self::get_decrypted( self::CONST_GA4_STREAM_ID );
	}

	/**
	 * Decrypt constant value for plugin use.
	 *
	 * @param string $constant Constant name.
	 * @return string
	 */
	private static function get_decrypted( $constant ) {
		$constant = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) );
		if ( isset( self::$runtime_values[ $constant ] ) ) {
			return sanitize_text_field( self::$runtime_values[ $constant ] );
		}

		$raw = self::read_constant( $constant );
		if ( '' === $raw ) {
			return '';
		}
		// Allow plain constant values for non-encrypted setups, but prefer sp_cfg:.
		if ( 0 === strpos( $raw, self::PREFIX ) ) {
			$plain = self::decrypt( $raw );
			return sanitize_text_field( $plain );
		}
		return sanitize_text_field( $raw );
	}

	/**
	 * @param string $name Constant name.
	 * @return string
	 */
	private static function read_constant( $name ) {
		$name = preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $name ) );
		if ( '' !== self::$test_wp_config_path && is_readable( self::$test_wp_config_path ) ) {
			$from_file = self::read_constant_from_file( self::$test_wp_config_path, $name );
			if ( '' !== $from_file ) {
				return $from_file;
			}
			if ( self::is_plugin_managed_constant( $name ) ) {
				return '';
			}
		}

		if ( ! defined( $name ) ) {
			return '';
		}
		$val = constant( $name );
		if ( ! is_string( $val ) && ! is_numeric( $val ) ) {
			return '';
		}
		return trim( (string) $val );
	}

	/**
	 * Parse a define() value from a config file without relying on PHP constants.
	 *
	 * @param string $path     Config file path.
	 * @param string $constant Constant name.
	 * @return string
	 */
	/**
	 * @param string $constant Constant name.
	 * @return bool
	 */
	private static function is_plugin_managed_constant( $constant ) {
		$managed = array(
			self::CONST_GA4_CLIENT_ID,
			self::CONST_GA4_CLIENT_SECRET,
			self::CONST_GA4_API_SECRET,
			self::CONST_GA4_ACCESS_TOKEN,
			self::CONST_GA4_REFRESH_TOKEN,
			self::CONST_GA4_TOKEN_EXPIRES_AT,
			self::CONST_GA4_PROPERTY_ID,
			self::CONST_GA4_MEASUREMENT_ID,
			self::CONST_GA4_STREAM_ID,
		);

		return in_array( preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $constant ) ), $managed, true );
	}

	/**
	 * @param string $path     Config file path.
	 * @param string $constant Constant name.
	 * @return string
	 */
	private static function read_constant_from_file( $path, $constant ) {
		$contents = file_get_contents( (string) $path );
		if ( false === $contents ) {
			return '';
		}

		$quoted_pattern = "/define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*['\"]((?>[^'\"\\\\]|\\\\.)*)['\"]\\s*\\)\\s*;/";
		if ( preg_match( $quoted_pattern, $contents, $matches ) ) {
			return stripcslashes( (string) $matches[1] );
		}

		$plain_pattern = "/define\\s*\\(\\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\\s*,\\s*(\\d+)\\s*\\)\\s*;/";
		if ( preg_match( $plain_pattern, $contents, $matches ) ) {
			return (string) (int) $matches[1];
		}

		return '';
	}

	/**
	 * Key from site name + admin email + WP salts.
	 *
	 * @return string 32-byte binary key.
	 */
	private static function derive_key() {
		$site_name   = (string) get_bloginfo( 'name' );
		$admin_email = (string) get_option( 'admin_email', '' );
		$auth        = defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : '';
		$secure      = defined( 'SECURE_AUTH_KEY' ) ? (string) SECURE_AUTH_KEY : '';
		$material    = $site_name . '|' . $admin_email . '|' . $auth . '|' . $secure;
		return hash_hmac( 'sha256', $material, 'sm_pulse_analytics_cfg_v1', true );
	}

	/**
	 * @param string $plain Plaintext.
	 * @return string Cipher with sp_cfg: prefix, or empty on failure.
	 */
	private static function encrypt( $plain ) {
		$plain = (string) $plain;
		if ( '' === $plain ) {
			return '';
		}
		if ( 0 === strpos( $plain, self::PREFIX ) ) {
			return $plain;
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$key = self::derive_key();
		$iv  = openssl_random_pseudo_bytes( 16 );
		if ( false === $iv ) {
			return '';
		}
		$encrypted = openssl_encrypt( $plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $encrypted ) {
			return '';
		}
		return self::PREFIX . base64_encode( $iv ) . ':' . base64_encode( $encrypted );
	}

	/**
	 * @param string $cipher Ciphertext with sp_cfg: prefix.
	 * @return string Plaintext or empty on failure.
	 */
	private static function decrypt( $cipher ) {
		$cipher = (string) $cipher;
		if ( 0 !== strpos( $cipher, self::PREFIX ) ) {
			return '';
		}
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$payload = substr( $cipher, strlen( self::PREFIX ) );
		$parts   = explode( ':', $payload, 2 );
		if ( 2 !== count( $parts ) ) {
			return '';
		}
		$iv  = base64_decode( $parts[0], true );
		$bin = base64_decode( $parts[1], true );
		if ( false === $iv || false === $bin || 16 !== strlen( $iv ) ) {
			return '';
		}
		$key  = self::derive_key();
		$plain = openssl_decrypt( $bin, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return false === $plain ? '' : $plain;
	}
}
