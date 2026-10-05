/**
 * Forms Conversion Report Controller.
 *
 * @package Sm_Pulse_Analytics
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */

(function ($) {
	'use strict';
	var pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};


	var trendChart = null;
	var currentTrendData = null;
	var formsRequest = null;
	var activeFetchRange = '';

	function __(text) {
		return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(text, 'smackcoders-pulse-analytics-for-woocommerce') : text;
	}

	function escapeHtml(str) {
		return String(str || '').replace(/[&<>"']/g, function (m) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
		});
	}

	function spinnerSvg() {
		return '<svg class="forms-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">' +
			'<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
			'<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
			'</svg>';
	}

	function showSpinner() {
		$('#forms-loading-indicator').css('display', 'inline-flex');
		var spinnerHtml = '<tr><td class="sp-table-empty sp-table-empty--lg" colspan="5">' +
			'<div class="sp-loading-inline">' +
			spinnerSvg() +
			'<span>' + __('Loading forms data…') + '</span>' +
			'</div></td></tr>';
		$('#forms-report-body').html(spinnerHtml);
	}

	function hideSpinner() {
		$('#forms-loading-indicator').hide();
	}

	function resolveDateRange(startDate, endDate) {
		if (!startDate || !endDate) {
			try {
				var saved = sessionStorage.getItem('sm_pulse_analytics_calendar');
				if (saved) {
					var parsed = JSON.parse(saved);
					if (parsed && parsed.start && parsed.end) {
						startDate = parsed.start;
						endDate = parsed.end;
					}
				}
			} catch (e) {}
		}

		if (!startDate || !endDate) {
			var urlParams = new URLSearchParams(window.location.search);
			startDate = urlParams.get('startDate') || urlParams.get('from') || '';
			endDate = urlParams.get('endDate') || urlParams.get('to') || '';
		}

		return { startDate: startDate || '', endDate: endDate || '' };
	}

	function fetchFormsData(startDate, endDate) {
		var range = resolveDateRange(startDate, endDate);
		startDate = range.startDate;
		endDate = range.endDate;

		var rangeKey = startDate + '|' + endDate;
		// Skip duplicate range while a request for it is in flight (header fires
		// both a native CustomEvent and a jQuery trigger for the same change).
		if (rangeKey && rangeKey === activeFetchRange) {
			return;
		}

		if (formsRequest && typeof formsRequest.abort === 'function') {
			formsRequest.abort();
		}

		activeFetchRange = rangeKey;
		showSpinner();

		var baseUrl = '';
		if (window.PulseAnalyticsVars && window.PulseAnalyticsVars.rest_url) {
			baseUrl = window.PulseAnalyticsVars.rest_url.replace(/\/$/, '') + '/pulse-analytics/v1/reports/forms';
		} else if (pulseAjax && window.pulseAjax.rest_url) {
			baseUrl = window.pulseAjax.rest_url.replace(/\/$/, '') + '/reports/forms';
		} else if (window.wpApiSettings && window.wpApiSettings.root) {
			baseUrl = window.wpApiSettings.root.replace(/\/$/, '') + '/pulse-analytics/v1/reports/forms';
		} else {
			baseUrl = '/wp-json/pulse-analytics/v1/reports/forms';
		}

		var nonce = (window.PulseAnalyticsVars && window.PulseAnalyticsVars.nonce)
			? window.PulseAnalyticsVars.nonce
			: (pulseAjax && window.pulseAjax.nonce)
				? window.pulseAjax.nonce
				: (window.wpApiSettings ? window.wpApiSettings.nonce : '');

		var params = {};
		if (startDate && endDate) {
			params.startDate = startDate;
			params.endDate = endDate;
		}

		formsRequest = $.ajax({
			url: baseUrl,
			method: 'GET',
			data: params,
			beforeSend: function (xhr) {
				if (nonce) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				}
			},
			success: function (res) {
				hideSpinner();
				renderNotice(res);
				if (res && res.kpis) {
					renderKpis(res.kpis);
				}
				if (res && res.rows) {
					renderTable(res.rows);
				}
				if (res && res.trend) {
					renderTrend(res.trend);
				}
			},
			error: function (xhr, status) {
				if (status === 'abort') {
					return;
				}
				hideSpinner();
				$('#forms-report-body').html('<tr><td class="sp-table-empty sp-table-empty--muted" colspan="5">' + __('Error loading forms data.') + '</td></tr>');
			},
			complete: function (xhr, status) {
				formsRequest = null;
				if (status !== 'abort') {
					// Keep activeFetchRange so a late duplicate event for the same
					// dates is ignored; clear only when a new range is requested.
				}
			}
		});
	}

	function renderNotice(res) {
		var el = document.getElementById('forms-data-notice');
		if (!el) return;

		var kpis = (res && res.kpis) || {};
		var hasData = Number(kpis.views || 0) || Number(kpis.starts || 0) || Number(kpis.submissions || 0);
		var message = '';
		var tone = 'info';

		if (res && res.is_dummy) {
			tone = 'warn';
			message = __('Showing sample demo data. Google Analytics 4 could not be reached, so no real form events were loaded.');
		} else if (res && res.notice === 'ga4_unavailable') {
			tone = 'warn';
			message = __('Google Analytics 4 is not connected. Connect a GA4 property in settings to see form conversion data.');
		} else if (!hasData) {
			message = __('Connected to GA4, but no form events were recorded in this period. New events can take up to 24–48 hours to appear in GA4 reporting.');
		}

		if (!message) {
			el.style.display = 'none';
			el.textContent = '';
			return;
		}

		var palette = tone === 'warn'
			? { border: '#f59e0b', bg: '#fffbeb', color: '#92400e' }
			: { border: '#6366f1', bg: '#eef2ff', color: '#3730a3' };

		el.style.cssText = 'display:block;margin:0 0 16px;padding:10px 14px;border-left:4px solid ' +
			palette.border + ';background:' + palette.bg + ';color:' + palette.color + ';font-size:13px;border-radius:4px;';
		el.textContent = message;
	}

	function renderKpis(kpis) {
		$('#forms-kpi-views').text(Number(kpis.views || 0).toLocaleString());
		$('#forms-kpi-starts').text(Number(kpis.starts || 0).toLocaleString());
		$('#forms-kpi-submissions').text(Number(kpis.submissions || 0).toLocaleString());
		$('#forms-kpi-conv').text(kpis.conv_rate || '0.0%');
	}

	function renderTable(rows) {
		var body = document.getElementById('forms-report-body');
		if (!body) return;

		if (!rows || !rows.length) {
			body.innerHTML = '<tr><td class="sp-table-empty sp-table-empty--muted" colspan="5">' + __('No form activity recorded for this period.') + '</td></tr>';
			return;
		}

		body.innerHTML = rows.map(function (r, idx) {
			var displayName = r.form_name || r.form_id || __('Form #') + (idx + 1);
			return '<tr class="forms-row sp-cursor-pointer" data-index="' + idx + '">' +
				'<td class="col-name">' + escapeHtml(displayName) + '</td>' +
				'<td class="col-num">' + Number(r.views || 0).toLocaleString() + '</td>' +
				'<td class="col-num">' + Number(r.starts || 0).toLocaleString() + '</td>' +
				'<td class="col-num">' + Number(r.submissions || 0).toLocaleString() + '</td>' +
				'<td class="col-rate">' + (r.conv_rate || '0%') + '</td>' +
				'</tr>';
		}).join('');

		window.SmPulseAnalyticsFormsRows = rows;
		$('.forms-row').off('click').on('click', function () {
			var idx = parseInt($(this).data('index'), 10);
			showDetail(window.SmPulseAnalyticsFormsRows[idx]);
		});
	}

	function showDetail(row) {
		if (!row) return;
		var panel = document.getElementById('forms-detail-panel');
		var content = document.getElementById('forms-detail-content');
		if (!panel || !content) return;

		var pagesHtml = '';
		if (row.top_pages && row.top_pages.length) {
			pagesHtml = '<ul class="sp-detail-list">' +
				row.top_pages.map(function (p) {
					return '<li>' + escapeHtml(p.page_url) + ' (' + p.count + ')</li>';
				}).join('') + '</ul>';
		} else {
			pagesHtml = '<p class="sp-detail-muted">No page breakdown available.</p>';
		}

		content.innerHTML =
			'<p class="sp-mb-6"><strong>Form Name:</strong> ' + escapeHtml(row.form_name || row.form_id) + '</p>' +
			'<p class="sp-mb-6"><strong>Form ID:</strong> ' + escapeHtml(row.form_id) + '</p>' +
			'<p class="sp-mb-6"><strong>Impressions:</strong> ' + Number(row.views || 0).toLocaleString() + '</p>' +
			'<p class="sp-mb-6"><strong>Form Starts:</strong> ' + Number(row.starts || 0).toLocaleString() + '</p>' +
			'<p class="sp-mb-6"><strong>Conversions:</strong> ' + Number(row.submissions || 0).toLocaleString() + '</p>' +
			'<p class="sp-mb-6"><strong>Conversion Rate:</strong> ' + (row.conv_rate || '0%') + '</p>' +
			'<h4 class="sp-detail-subheading">Top pages containing this form</h4>' + pagesHtml;

		panel.style.display = 'block';
	}

	function renderTrend(trend) {
		if (trend) {
			currentTrendData = trend;
		} else {
			trend = currentTrendData;
		}
		if (!trend) return;

		var canvas = document.getElementById('forms-trend-chart');
		if (!canvas) return;

		if (typeof Chart === 'undefined') return;

		if (trendChart) {
			trendChart.destroy();
		}

		var activeMetrics = [];
		$('.sp-metric-checkbox:checked').each(function () {
			activeMetrics.push($(this).val());
		});

		var metricStyles = {
			views: {
				label: __('Form Impressions'),
				borderColor: '#6366f1',
				backgroundColor: 'rgba(99, 102, 241, 0.08)'
			},
			starts: {
				label: __('Form Starts'),
				borderColor: '#f59e0b',
				backgroundColor: 'rgba(245, 158, 11, 0.08)'
			},
			submissions: {
				label: __('Conversions'),
				borderColor: '#10b981',
				backgroundColor: 'rgba(16, 185, 129, 0.08)'
			}
		};

		var datasets = [];
		if (activeMetrics.indexOf('views') !== -1) {
			datasets.push({
				label: metricStyles.views.label,
				data: trend.views || [],
				borderColor: metricStyles.views.borderColor,
				backgroundColor: metricStyles.views.backgroundColor,
				borderWidth: 2,
				fill: true,
				tension: 0.35,
				pointRadius: 4,
				pointHoverRadius: 5,
				pointBackgroundColor: '#ffffff',
				pointBorderColor: metricStyles.views.borderColor,
				pointBorderWidth: 2
			});
		}
		if (activeMetrics.indexOf('starts') !== -1) {
			datasets.push({
				label: metricStyles.starts.label,
				data: trend.starts || [],
				borderColor: metricStyles.starts.borderColor,
				backgroundColor: metricStyles.starts.backgroundColor,
				borderWidth: 2,
				fill: true,
				tension: 0.35,
				pointRadius: 4,
				pointHoverRadius: 5,
				pointBackgroundColor: '#ffffff',
				pointBorderColor: metricStyles.starts.borderColor,
				pointBorderWidth: 2
			});
		}
		if (activeMetrics.indexOf('submissions') !== -1) {
			datasets.push({
				label: metricStyles.submissions.label,
				data: trend.submissions || trend.values || [],
				borderColor: metricStyles.submissions.borderColor,
				backgroundColor: metricStyles.submissions.backgroundColor,
				borderWidth: 2,
				fill: true,
				tension: 0.35,
				pointRadius: 4,
				pointHoverRadius: 5,
				pointBackgroundColor: '#ffffff',
				pointBorderColor: metricStyles.submissions.borderColor,
				pointBorderWidth: 2
			});
		}

		var ctx = canvas.getContext('2d');
		trendChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: trend.labels || [],
				datasets: datasets
			},
			plugins: [{
				id: 'smPulseAnalyticsFormsCrosshair',
				afterDraw: function (chart) {
					if (window.SmPulseAnalyticsChartTooltip && window.SmPulseAnalyticsChartTooltip.crosshairPlugin) {
						window.SmPulseAnalyticsChartTooltip.crosshairPlugin.afterDraw(chart);
					}
				}
			}],
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					mode: 'nearest',
					intersect: false
				},
				plugins: {
					legend: { display: false },
					tooltip: {
						enabled: false,
						position: 'nearest',
						external: function (context) {
							if (window.SmPulseAnalyticsChartTooltip && typeof window.SmPulseAnalyticsChartTooltip.createExternalTooltip === 'function') {
								window.SmPulseAnalyticsChartTooltip.createExternalTooltip(function (chart, dp) {
									var dataset = chart.data.datasets[dp.datasetIndex];
									return dataset ? (dataset.label || __('Value')) : __('Value');
								})(context);
							}
						}
					}
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: { color: '#94a3b8', font: { size: 11 } }
					},
					y: {
						beginAtZero: true,
						grid: { color: '#f1f5f9' },
						ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } }
					}
				}
			}
		});
	}

	$(document).ready(function () {
		fetchFormsData();

		// Metrics dropdown menu toggle
		$('#sp-metrics-dropdown-btn').on('click', function (e) {
			e.stopPropagation();
			$('#sp-metrics-dropdown-menu').toggle();
		});

		$(document).on('click', function () {
			$('#sp-metrics-dropdown-menu').hide();
		});

		$('.sp-metric-checkbox').on('change', function () {
			renderTrend();
		});

		// Header date picker (script.js) already owns #traffic-date-range-select and
		// dispatches both a native CustomEvent and a jQuery trigger. Listen once via
		// the native event only to avoid duplicate forms API requests.
		document.addEventListener('pulse-analytics:date-range-changed', function (e) {
			if (e.detail && e.detail.startDate && e.detail.endDate) {
				fetchFormsData(e.detail.startDate, e.detail.endDate);
			}
		});
	});
})(jQuery);
