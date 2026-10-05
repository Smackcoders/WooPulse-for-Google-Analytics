/**
 * Frontend GA4 gtag loader config (localized).
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */
(function () {
	var cfg = window.pulseAnalyticsGtag || null;
	if (!cfg || !cfg.measurementId) {
		return;
	}

	window.dataLayer = window.dataLayer || [];
	function gtag() {
		dataLayer.push(arguments);
	}
	window.gtag = window.gtag || gtag;

	gtag('js', new Date());
	gtag('config', cfg.measurementId, cfg.configParams || {});

	if (cfg.sendManualPageView) {
		gtag('event', 'page_view', cfg.pageViewParams || {});
	}
})();
