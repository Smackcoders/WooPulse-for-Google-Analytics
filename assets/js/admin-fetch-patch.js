/**
 * Admin REST fetch nonce patch and menu navigation loader.
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */
(function () {
	if (typeof window.originalSmPulseAnalyticsFetchPatch === 'undefined') {
		window.originalSmPulseAnalyticsFetchPatch = window.fetch;
		window.fetch = function () {
			var args = Array.prototype.slice.call(arguments);
			var url = args[0];
			var options = args[1] || {};
			var urlStr = typeof url === 'string' ? url : (url && url.url ? url.url : '');

			if (
				urlStr.indexOf('/wp-json/sm-pulse-analytics/') !== -1
				|| urlStr.indexOf('rest_route=/sm-pulse-analytics/') !== -1
				|| urlStr.indexOf('/wp-json/wp_asa/') !== -1
				|| urlStr.indexOf('rest_route=/wp_asa/') !== -1
			) {
				options.credentials = 'same-origin';
				options.headers = options.headers || {};
				if (typeof pulseAnalyticsAjax !== 'undefined' && pulseAnalyticsAjax.nonce) {
					var paNonce = pulseAnalyticsAjax.nonce;
					if (options.headers instanceof Headers) {
						options.headers.append('X-WP-Nonce', paNonce);
					} else {
						options.headers = options.headers || {};
						options.headers['X-WP-Nonce'] = paNonce;
					}
				}
				args[1] = options;
			}
			return window.originalSmPulseAnalyticsFetchPatch.apply(window, args);
		};
	}

	document.addEventListener('DOMContentLoaded', function () {
		var menuItems = document.querySelectorAll('#toplevel_page_pulse-analytics a');
		menuItems.forEach(function (item) {
			item.addEventListener('click', function (e) {
				if (e.button === 0 && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
					if (window.SmPulseAnalytics && typeof window.SmPulseAnalytics.showLoader === 'function') {
						window.SmPulseAnalytics.showLoader('Navigating...');
					}
				}
			});
		});
	});
})();
