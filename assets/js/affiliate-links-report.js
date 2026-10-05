/**
 * @package Sm_Pulse_Analytics
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
(function ($) {
	'use strict';

function __(text) {
	return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(text, 'smackcoders-pulse-analytics-for-woocommerce') : text;
}


	var vars = window.smPulseAnalyticsAffiliateLinksVars || {};
	var currentTab = 'top_links';

	function loadReport(force) {
		$('#sp-al-error').hide();
		$('#sp-al-loading').show();
		$('#sp-al-metrics, #sp-al-table').addClass('sp-section-loading');

		var postData = {
			action: 'sm_pulse_analytics_get_affiliate_link_report',
			nonce: vars.nonce,
			compare: 1, // Default compare
			force: force ? 1 : 0
		};

		var selectEl = document.getElementById('traffic-date-range-select');
		var customInput = document.getElementById('traffic-custom-date-picker');
		
		if (selectEl) {
			var val = selectEl.value;
			if (val === 'custom' && customInput && customInput._flatpickr) {
				var dates = customInput._flatpickr.selectedDates || [];
				if (dates.length === 2 && typeof flatpickr !== 'undefined') {
					postData.start_date = flatpickr.formatDate(dates[0], 'Y-m-d');
					postData.end_date = flatpickr.formatDate(dates[1], 'Y-m-d');
				}
			} else {
				// Map global presets to affiliate link presets
				if (val === '7daysAgo') postData.date_range = '7days';
				else if (val === '90daysAgo') postData.date_range = '90days';
				else postData.date_range = '30days';
			}
		} else {
			postData.date_range = '30days';
		}

		$.post(vars.ajax_url, postData)
			.done(function (res) {
				$('#sp-al-loading').hide();
				$('#sp-al-metrics, #sp-al-table').removeClass('sp-section-loading');
				if (!res.success) {
					$('#sp-al-error').text((res.data && res.data.message) || vars.i18n.error).show();
					return;
				}
				renderReport(res.data || {});
			})
			.fail(function () {
				$('#sp-al-loading').hide();
				$('#sp-al-metrics, #sp-al-table').removeClass('sp-section-loading');
				$('#sp-al-error').text(vars.i18n.error).show();
			});
	}

	function renderReport(data) {
		var summary = data.summary || {};
		var comparison = (data.comparison && data.comparison.summary) || {};
		var $metrics = $('#sp-al-metrics').empty();

		var cards = [
			{ key: 'affiliate_clicks', label: __('Affiliate Clicks'), comp: comparison.affiliate_clicks },
			{ key: 'outbound_clicks', label: __('Outbound Clicks'), comp: comparison.outbound_clicks },
			{ key: 'unique_links', label: __('Unique Links') },
			{ key: 'top_partner', label: __('Top Partner'), format: 'text' }
		];

		cards.forEach(function (card) {
			var val = summary[card.key];
			var change = '';
			if (card.comp && typeof card.comp.percent !== 'undefined') {
				var arrow = card.comp.direction === 'up' ? '↑' : (card.comp.direction === 'down' ? '↓' : '→');
				change = '<span class="sp-al-change sp-al-' + card.comp.direction + '">' + arrow + ' ' + Math.abs(card.comp.percent) + '%</span>';
			}
			var isText = card.format === 'text';
			var display = isText ? (val || '—') : Number(val || 0).toLocaleString();
			var valueClass = 'sp-al-metric-value' + (isText ? ' sp-al-text-value' : '');
			var safeTitle = isText && val ? $('<div>').text(val).html() : '';
			var safeDisplay = isText && val ? $('<div>').text(display).html() : display;

			$metrics.append(
				'<div class="sp-al-metric' + (isText ? ' sp-al-metric-partner' : '') + '">' +
				'<p class="sp-al-metric-label">' + card.label + '</p>' +
				'<p class="' + valueClass + '" title="' + safeTitle + '">' + safeDisplay + '</p>' +
				change +
				'</div>'
			);
		});

		renderTable(currentTab, data);
	}

	function renderTable(tab, data) {
		var rows = [];
		var headers = [];

		switch (tab) {
			case 'partners':
				headers = [__('Partner'), __('Domain'), __('Links'), __('Clicks')];
				(data.partners || []).forEach(function (row) {
					rows.push([row.partner, row.domain, row.links, row.clicks]);
				});
				break;
			case 'sources':
				headers = [__('Page Path'), __('Clicks')];
				(data.sources || []).forEach(function (row) {
					rows.push([row.path, row.clicks]);
				});
				break;
			case 'outbound':
				headers = [__('Link'), __('Domain'), __('Clicks')];
				(data.outbound || []).forEach(function (row) {
					rows.push([row.label || row.url, row.domain, row.clicks]);
				});
				break;
			default:
				headers = [__('Link'), __('Domain'), __('Clicks')];
				(data.top_links || []).forEach(function (row) {
					rows.push([row.label || row.url, row.domain, row.clicks]);
				});
		}

		var $table = $('#sp-al-table');
		$table.find('thead, tbody').empty();

		if (!rows.length) {
			$table.find('tbody').append('<tr><td colspan="' + headers.length + '">' + vars.i18n.noData + '</td></tr>');
			return;
		}

		var $head = $('<tr></tr>');
		headers.forEach(function (h) {
			$head.append('<th>' + h + '</th>');
		});
		$table.find('thead').append($head);

		rows.forEach(function (row) {
			var $tr = $('<tr></tr>');
			row.forEach(function (cell) {
				$tr.append('<td>' + (cell != null ? cell : '') + '</td>');
			});
			$table.find('tbody').append($tr);
		});
	}

	$(function () {
		$('.sp-al-tab').on('click', function () {
			currentTab = $(this).data('tab');
			$('.sp-al-tab').removeClass('active');
			$(this).addClass('active');
			loadReport(false);
		});

		// Initialize Global Date Picker Listener and Flatpickr for custom ranges
		var selectEl = document.getElementById('traffic-date-range-select');
		var customPickerInput = document.getElementById('traffic-custom-date-picker');

		if (selectEl) {
			selectEl.addEventListener('change', function () {
				var val = this.value;
				if (val === 'custom') {
					if (customPickerInput) {
						customPickerInput.classList.remove('hidden');
						if (typeof flatpickr !== 'undefined' && !customPickerInput._flatpickr) {
							flatpickr(customPickerInput, {
								mode: 'range',
								dateFormat: 'Y-m-d',
								onClose: function (selectedDates) {
									if (selectedDates.length === 2) {
										loadReport(false);
									}
								}
							});
						}
						if (customPickerInput._flatpickr) {
							customPickerInput._flatpickr.open();
						}
					}
				} else {
					if (customPickerInput) {
						customPickerInput.classList.add('hidden');
					}
					loadReport(false);
				}
			});
		}

		loadReport(false);
	});
})(jQuery);
