/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
/**
 * Pulse Analytics Free - Link Tracking Settings Controller
 * Handles interactive repeater row additions & deletions for link paths.
 *
 * @package Sm_Pulse_Analytics
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		var $container = $('#pulse-analytics-links-settings-container');
		if (!$container.length) return;

		// Ensure container is unblurred and fully interactive
		$container.css({
			'filter': 'none',
			'pointer-events': 'auto',
			'user-select': 'auto',
			'opacity': '1'
		});
		$('#pulse-analytics-links-pro-modal').remove();

		// Add new affiliate path row
		$(document).on('click', '#add-affiliate-path-btn', function (e) {
			e.preventDefault();
			var idx = $('#affiliate-link-paths-container .affiliate-row').length;
			var newRow = '<div class="affiliate-row sp-affiliate-grid-row">' +
				'<div><input class="sp-input-lg" type="text" name="sm_pulse_analytics_settings[affiliate_links][' + idx + '][path]" value="" placeholder="/go/" /></div>' +
				'<div><input class="sp-input-lg" type="text" name="sm_pulse_analytics_settings[affiliate_links][' + idx + '][label]" value="affiliate" placeholder="affiliate" /></div>' +
				'<div class="sp-text-center"><button type="button" class="remove-affiliate-row sp-btn-icon-danger"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button></div>' +
				'</div>';
			$('#affiliate-link-paths-container').append(newRow);
		});

		// Remove affiliate path row
		$(document).on('click', '.remove-affiliate-row', function (e) {
			e.preventDefault();
			if ($('#affiliate-link-paths-container .affiliate-row').length > 1) {
				$(this).closest('.affiliate-row').remove();
			} else {
				$(this).closest('.affiliate-row').find('input').val('');
			}
		});
	});

})(jQuery);
