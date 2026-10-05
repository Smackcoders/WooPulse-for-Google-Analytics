<?php
/**
 * Register Pulse Analytics Free WP-CLI commands.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/commands.php';

/**
 * Bootstrap CLI when WP-CLI is available.
 */
function sm_pulse_analytics_cli_bootstrap() {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		return;
	}

	$commands = array(
		'status'    => Status_Command::class,
		'doctor'    => Doctor_Command::class,
		'db'        => DB_Command::class,
		'cache'     => Cache_Command::class,
		'sync'      => Sync_Command::class,
		'migrate'   => Migrate_Command::class,
		'telemetry' => Telemetry_Command::class,
	);

	foreach ( $commands as $subcommand => $class ) {
		\WP_CLI::add_command( 'pulse-analytics ' . $subcommand, $class );
		\WP_CLI::add_command( 'sm-pulse-analytics ' . $subcommand, $class );
	}
}

add_action( 'cli_init', __NAMESPACE__ . '\\sm_pulse_analytics_cli_bootstrap' );
