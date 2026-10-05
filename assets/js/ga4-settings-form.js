/**
 * GA4 settings form AJAX save.
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('pulse-analytics-ga4-settings-form');
		if (!form) {
			return;
		}
		var cfg = window.pulseAnalyticsGa4Settings || {};
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var msg = document.getElementById('pulse-analytics-ga4-msg');
			var formData = new FormData(form);
			formData.append('action', cfg.action || 'sm_pulse_analytics_ga4_save_config');
			formData.append('nonce', cfg.nonce || '');

			if (msg) {
				msg.className = 'mt-3 text-xs text-indigo-600 font-semibold';
				msg.textContent = cfg.savingText || 'Saving GA4 settings...';
				msg.classList.remove('hidden');
			}

			fetch(cfg.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : ''), {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
				.then(function (res) {
					return res.json();
				})
				.then(function (data) {
					if (!msg) {
						return;
					}
					if (data.success) {
						msg.className = 'mt-3 text-xs text-emerald-600 font-semibold';
						msg.textContent = '✓ ' + ((data.data && data.data.message) || cfg.savedText || 'GA4 settings saved!');
					} else {
						msg.className = 'mt-3 text-xs text-rose-600 font-semibold';
						msg.textContent =
							'✕ ' + ((data.data && data.data.message) || cfg.errorText || 'Error saving settings.');
					}
				})
				.catch(function () {
					if (msg) {
						msg.className = 'mt-3 text-xs text-rose-600 font-semibold';
						msg.textContent = '✕ ' + (cfg.errorText || 'Error saving settings.');
					}
				});
		});
	});
})();
