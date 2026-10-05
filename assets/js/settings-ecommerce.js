/**
 * Pulse Analytics for WordPress — Free plugin asset.
 * Settings: eCommerce Tracking View Controller.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
(function ($) {
	'use strict';

	$(document).ready(function () {
		$('input[name="sm_pulse_analytics_settings[enable_woocommerce_tracking]"]').on('change', function () {
			if ($(this).is(':checked')) {
				$('input[name="sm_pulse_analytics_settings[enable_edd_tracking]"]').prop('checked', false);
			}
		});

		$('input[name="sm_pulse_analytics_settings[enable_edd_tracking]"]').on('change', function () {
			if ($(this).is(':checked')) {
				$('input[name="sm_pulse_analytics_settings[enable_woocommerce_tracking]"]').prop('checked', false);
			}
		});
	});
})(jQuery);
