/**
 * Admin settings helpers (secret visibility + disconnect confirm + GA4 picker autofill).
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */
(function () {
	window.pulseAnalyticsToggleSecretVisibility = function (id) {
		var input = document.getElementById(id);
		var icon = document.getElementById('eye_icon_' + id);
		if (!input || !icon) {
			return;
		}
		if (input.type === 'password') {
			input.type = 'text';
			icon.innerHTML =
				'<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" y1="2" x2="22" y2="22"></line>';
		} else {
			input.type = 'password';
			icon.innerHTML =
				'<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>';
		}
	};

	function pulseAnalyticsApplyGa4DiscoveryChoice(selectEl) {
		if (!selectEl || !selectEl.value) {
			return;
		}
		var parts = selectEl.value.split('|');
		if (parts.length < 2) {
			return;
		}
		var propertyInput = document.getElementById('ga4-property-id-input');
		var measurementInput = document.getElementById('ga4-measurement-id-input');
		if (propertyInput) {
			propertyInput.value = parts[0] || '';
		}
		if (measurementInput) {
			measurementInput.value = parts[1] || '';
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.sp-toggle-secret[data-target]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = btn.getAttribute('data-target');
				if (targetId) {
					window.pulseAnalyticsToggleSecretVisibility(targetId);
				}
			});
		});

		document.querySelectorAll('.sp-select-on-click').forEach(function (input) {
			input.addEventListener('click', function () {
				input.select();
			});
		});

		document.querySelectorAll('.sp-copy-uri-btn[data-copy-uri]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var uri = btn.getAttribute('data-copy-uri') || '';
				if (!uri || !navigator.clipboard) {
					return;
				}
				navigator.clipboard.writeText(uri).then(function () {
					var copiedMsg =
						(window.pulseAnalyticsSettings && window.pulseAnalyticsSettings.redirectUriCopied) ||
						'Redirect URI copied to clipboard!';
					window.alert(copiedMsg);
				});
			});
		});

		var disconnectConfirm =
			(window.pulseAnalyticsSettings && window.pulseAnalyticsSettings.disconnectConfirm) ||
			'Revoke Google Analytics credentials? Live dashboard reports will stop until you reconnect.';

		document.querySelectorAll('#revoke-analytics-credentials-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (window.confirm(disconnectConfirm)) {
					window.location.href = this.getAttribute('data-url');
				}
			});
		});

		var discoverySelect = document.getElementById('sm_pulse_analytics_ga4_discovery_choice');
		if (discoverySelect) {
			pulseAnalyticsApplyGa4DiscoveryChoice(discoverySelect);
			discoverySelect.addEventListener('change', function () {
				pulseAnalyticsApplyGa4DiscoveryChoice(this);
			});
		}
	});
})();
