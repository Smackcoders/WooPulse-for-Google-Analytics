<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking installation (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/class-affiliate-link-config.php';

class Install {

	public static function check_dependencies() {
		return true;
	}

	public static function activate() {
		if ( false === get_option( Affiliate_Link_Config::OPTION_KEY ) ) {
			Affiliate_Link_Config::update_settings( Affiliate_Link_Config::default_settings() );
		}
	}
}
