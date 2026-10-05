<?php
/**
 * Forms tracking bootstrap.
 *
 * Form analytics are dispatched exclusively via frontend GA4 gtag events.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Sm_Pulse_Analytics\PulseAnalyticsCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Forms_Tracker {

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( ! Forms_Config::is_tracking_enabled() ) {
			return;
		}

		// pulse_form_* events are sent via gtag on the frontend.
		// Successful submissions are captured by assets/js/forms-tracking.js.
	}
}
