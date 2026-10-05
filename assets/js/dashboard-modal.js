/**
 * Pulse Analytics for WordPress — Free plugin asset.
 * Dashboard PRO Feature Modal Controller.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	function openSmPulseAnalyticsDashboardModal() {
		var modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
		if (!modal) return;
		modal.classList.remove('hidden');
		modal.classList.add('flex');
		setTimeout(function () {
			var dialog = modal.querySelector('.animate-pro-modal-in');
			if (dialog) {
				dialog.classList.remove('scale-95', 'opacity-0');
				dialog.classList.add('scale-100', 'opacity-100');
			}
		}, 10);
	}

	function closeSmPulseAnalyticsDashboardModal() {
		var modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
		if (!modal) return;
		var dialog = modal.querySelector('.animate-pro-modal-in');
		if (dialog) {
			dialog.classList.remove('scale-100', 'opacity-100');
			dialog.classList.add('scale-95', 'opacity-0');
		}
		setTimeout(function () {
			modal.classList.remove('flex');
			modal.classList.add('hidden');
		}, 200);
	}

	document.addEventListener('click', function (e) {
		var proTag = e.target.closest('.open-dashboard-pro-modal, .pulse-analytics-pro-badge-pill, .pulse-analytics-pro-mini-tag, .sp-pro-gated .sp-pro-blur-target');
		if (proTag) {
			var dash = document.getElementById('PulseAnalytics-dashboard-v2');
			if (dash && dash.contains(proTag)) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				openSmPulseAnalyticsDashboardModal();
				return;
			}
		}

		if (e.target.closest('.pulse-analytics-dashboard-modal-close')) {
			e.preventDefault();
			closeSmPulseAnalyticsDashboardModal();
			return;
		}

		var modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
		if (modal && e.target === modal) {
			e.preventDefault();
			closeSmPulseAnalyticsDashboardModal();
			return;
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			var modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
			if (modal && !modal.classList.contains('hidden')) {
				closeSmPulseAnalyticsDashboardModal();
			}
		}
	});
});
