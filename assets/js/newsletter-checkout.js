/**
 * Newsletter opt-in checkbox for WooCommerce checkout (classic + blocks).
 *
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 */
(function () {
	function injectCheckbox() {
		if (document.getElementById('PulseAnalytics-newsletter-wrapper')) {
			return;
		}

		var targetLocation =
			document.querySelector('.wc-block-checkout__payment-method') ||
			document.querySelector('.wc-block-checkout__terms') ||
			document.querySelector('#customer_details');

		if (!targetLocation) {
			targetLocation =
				document.querySelector('.wc-block-checkout__actions') ||
				document.querySelector('#payment');
		}

		if (!targetLocation) {
			setTimeout(injectCheckbox, 500);
			return;
		}

		var cfg = window.pulseAnalyticsNewsletter || {};
		var label = cfg.label || 'Stay Updated! Subscribe to our newsletter.';
		var wrapper = document.createElement('div');
		wrapper.id = 'PulseAnalytics-newsletter-wrapper';
		wrapper.style.margin = '20px 0';
		wrapper.style.padding = '15px';
		wrapper.style.background = '#f9fafb';
		wrapper.style.border = '1px solid #e5e7eb';
		wrapper.style.borderRadius = '8px';

		wrapper.innerHTML =
			'<label class="sp-newsletter-label">' +
			'<input class="sp-checkbox-md sp-mr-10" type="checkbox" id="sm_pulse_analytics_newsletter_subscribe" />' +
			'<span></span></label>';
		wrapper.querySelector('span').textContent = label;

		targetLocation.parentNode.insertBefore(wrapper, targetLocation);

		var checkbox = document.getElementById('sm_pulse_analytics_newsletter_subscribe');
		if (checkbox) {
			checkbox.addEventListener('change', function () {
				if (this.checked) {
					document.cookie = 'sm_pulse_analytics_newsletter_optin=1; path=/; max-age=86400';
				} else {
					document.cookie = 'sm_pulse_analytics_newsletter_optin=0; path=/; max-age=0';
				}
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		injectCheckbox();
		setTimeout(injectCheckbox, 1000);
		setTimeout(injectCheckbox, 3000);
	});
})();
