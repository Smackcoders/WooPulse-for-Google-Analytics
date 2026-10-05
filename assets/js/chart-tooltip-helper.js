/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
/**
 * Pulse Analytics - Chart Tooltip & Crosshair Helper
 * Provides custom hover tooltips with percentage change indicators and vertical crosshair guide lines.
 */
(function () {
	'use strict';

function __(text) {
	return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(text, 'smackcoders-pulse-analytics-for-woocommerce') : text;
}


	function getOrCreateTooltip(chart) {
		var container = chart.canvas.parentNode;
		if (container) {
			container.style.position = 'relative';
		}

		var tooltipEl = container ? container.querySelector('.pulse-analytics-chart-tooltip') : null;

		if (!tooltipEl) {
			tooltipEl = document.createElement('div');
			tooltipEl.className = 'pulse-analytics-chart-tooltip';
			tooltipEl.style.background = '#ffffff';
			tooltipEl.style.borderRadius = '10px';
			tooltipEl.style.border = '1px solid #e2e8f0';
			tooltipEl.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05)';
			tooltipEl.style.color = '#0f172a';
			tooltipEl.style.opacity = '0';
			tooltipEl.style.pointerEvents = 'none';
			tooltipEl.style.position = 'absolute';
			tooltipEl.style.transition = 'opacity .15s ease, transform .1s ease, left .1s ease, top .1s ease';
			tooltipEl.style.padding = '12px 16px';
			tooltipEl.style.zIndex = '100';
			tooltipEl.style.minWidth = '130px';
			tooltipEl.style.fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";

			if (container) {
				container.appendChild(tooltipEl);
			}
		}

		return tooltipEl;
	}

	function externalTooltipHandler(context, getMetricLabel) {
		var chart = context.chart;
		var tooltip = context.tooltip;
		var tooltipEl = getOrCreateTooltip(chart);

		if (tooltip.opacity === 0) {
			tooltipEl.style.opacity = '0';
			return;
		}

		if (tooltip.body) {
			var dataPoints = tooltip.dataPoints;
			if (!dataPoints || !dataPoints.length) {
				tooltipEl.style.opacity = '0';
				return;
			}

			var dp = dataPoints[0];
			var dataIndex = dp.dataIndex;
			var dataset = chart.data.datasets[dp.datasetIndex];
			var dataArray = dataset.data || [];
			var rawVal = dp.raw !== undefined ? dp.raw : dataArray[dataIndex];
			var currentValue = typeof rawVal === 'number' ? rawVal : parseFloat(rawVal) || 0;
			var title = dp.label || (tooltip.title && tooltip.title[0]) || '';
			if (typeof title === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(title)) {
				var dateParts = title.split('-');
				var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
				var mIdx = parseInt(dateParts[1], 10) - 1;
				var dNum = parseInt(dateParts[2], 10);
				if (mIdx >= 0 && mIdx < 12 && !isNaN(dNum)) {
					title = months[mIdx] + ' ' + dNum;
				}
			}

			// Dynamic Metric Label
			var metricName = (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__('Value', 'smackcoders-pulse-analytics-for-woocommerce') : 'Value';
			if (typeof getMetricLabel === 'function') {
				metricName = getMetricLabel(chart, dp);
			} else if (typeof getMetricLabel === 'string') {
				metricName = getMetricLabel;
			} else if (dataset.label) {
				metricName = dataset.label;
			}

			// Percentage Change Calculation (vs Previous Point)
			var percentHtml = '';
			if (dataIndex > 0) {
				var prevRaw = dataArray[dataIndex - 1];
				var prevValue = typeof prevRaw === 'number' ? prevRaw : parseFloat(prevRaw) || 0;

				if (prevValue === 0 && currentValue > 0) {
					percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--up"><span>↑</span> <span>100%</span></div>';
				} else if (prevValue === 0 && currentValue === 0) {
					percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--neutral"><span>—</span> <span>0%</span></div>';
				} else {
					var changePct = Math.round(((currentValue - prevValue) / prevValue) * 100);
					if (changePct > 0) {
						percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--up"><span>↑</span> <span>' + changePct + '%</span></div>';
					} else if (changePct < 0) {
						percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--down"><span>↓</span> <span>' + Math.abs(changePct) + '%</span></div>';
					} else {
						percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--neutral"><span>—</span> <span>0%</span></div>';
					}
				}
			} else {
				percentHtml = '<div class="sp-tooltip-change sp-tooltip-change--neutral"><span>—</span> <span>0%</span></div>';
			}

			// Format display value according to unitType metadata if present
			var displayValue = currentValue.toLocaleString();
			if (dataset && dataset.unitType === 'rate') {
				displayValue = Number(currentValue).toFixed(1) + '%';
			} else if (dataset && dataset.unitType === 'duration') {
				if (typeof window.formatDuration === 'function') {
					displayValue = window.formatDuration(currentValue);
				} else {
					var sTotal = Math.round(currentValue);
					var mins = Math.floor(sTotal / 60);
					var secs = sTotal % 60;
					displayValue = mins + 'm ' + secs + 's';
				}
			}

			var innerHtml =
				'<div class="sp-tooltip-label">' +
				title +
				'</div>' +
				'<div class="sp-tooltip-value">' +
				displayValue +
				'</div>' +
				'<div class="sp-tooltip-sub">' +
				metricName +
				'</div>' +
				percentHtml;

			tooltipEl.innerHTML = innerHtml;
		}

		var positionX = chart.canvas.offsetLeft;
		var positionY = chart.canvas.offsetTop;

		tooltipEl.style.opacity = '1';

		var tooltipWidth = tooltipEl.offsetWidth || 140;
		var tooltipHeight = tooltipEl.offsetHeight || 120;
		var chartWidth = chart.width;
		var chartHeight = chart.height;

		var leftPos = positionX + tooltip.caretX;
		if (tooltip.caretX + tooltipWidth + 16 > chartWidth) {
			leftPos = positionX + tooltip.caretX - tooltipWidth - 12;
		} else {
			leftPos = positionX + tooltip.caretX + 12;
		}

		var topPos = positionY + tooltip.caretY - (tooltipHeight / 2);
		if (topPos < positionY) topPos = positionY + 4;
		if (topPos + tooltipHeight > positionY + chartHeight) {
			topPos = positionY + chartHeight - tooltipHeight - 4;
		}

		tooltipEl.style.left = leftPos + 'px';
		tooltipEl.style.top = topPos + 'px';
	}

	var crosshairPlugin = {
		id: 'smPulseAnalyticsCrosshair',
		afterDraw: function (chart) {
			if (chart.tooltip && chart.tooltip._active && chart.tooltip._active.length) {
				var activePoint = chart.tooltip._active[0];
				var ctx = chart.ctx;
				var x = activePoint.element.x;
				var topY = chart.scales && chart.scales.y ? chart.scales.y.top : 0;
				var bottomY = chart.scales && chart.scales.y ? chart.scales.y.bottom : chart.height;

				ctx.save();
				ctx.beginPath();
				ctx.setLineDash([4, 4]);
				ctx.moveTo(x, topY);
				ctx.lineTo(x, bottomY);
				ctx.lineWidth = 1;
				ctx.strokeStyle = '#cbd5e1';
				ctx.stroke();
				ctx.restore();
			}
		}
	};

	window.SmPulseAnalyticsChartTooltip = {
		createExternalTooltip: function (getMetricLabel) {
			return function (context) {
				externalTooltipHandler(context, getMetricLabel);
			};
		},
		crosshairPlugin: crosshairPlugin
	};
})();
