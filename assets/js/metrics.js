const pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};
/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
document.addEventListener('DOMContentLoaded', function() {
    const restBase = Object.keys(pulseAjax).length ? pulseAjax.rest_url : '/wp-json/wp_asa/v1';
    let start = new Date(); start.setDate(start.getDate() - 30);
    let stStr = start.toISOString().split('T')[0];
    let enStr = new Date().toISOString().split('T')[0];

    // Simple flatpickr for this view
    if (typeof flatpickr !== 'undefined' && document.getElementById('metricsDateRange')) {
        flatpickr("#metricsDateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [stStr, enStr],
            onClose: function(selectedDates) {
                if (selectedDates.length === 2) {
                    stStr = selectedDates[0].toISOString().split('T')[0];
                    enStr = selectedDates[1].toISOString().split('T')[0];
                    refreshMetrics();
                }
            }
        });
    }

    function refreshMetrics() {
        const badge = document.getElementById('metrics-status-badge');
        if (badge) {
            badge.style.background = '#f0f0f1';
            badge.style.color = '#646970';
            badge.textContent = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'Syncing...', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'Syncing...');
        }

        const urlParams = `?startDate=${stStr}&endDate=${enStr}`;

        // 1. Summary
        fetch(`${restBase}/daily-metrics${urlParams}`)
            .then(res => res.json())
            .then(response => {
                const body = document.getElementById('metricsSummaryBody');
                if (!body || !response.data) return;
                const total = response.data.reduce((a, b) => a + b, 0);
                body.innerHTML = `
                    <tr><td><strong>Total Sessions</strong></td><td class="sp-td-right-bold">${total.toLocaleString()}</td></tr>
                    <tr><td><strong>Avg. Daily Sessions</strong></td><td class="sp-td-right">${Math.round(total / response.data.length).toLocaleString()}</td></tr>
                `;
                if (badge) {
                    badge.style.background = '#dcfce7'; 
                    badge.style.color = '#166534';
                    badge.textContent = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'GA4 Data Live', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'GA4 Data Live');
                }
            }).catch(e => console.error(e));

        // 2. Top Pages
        fetch(`${restBase}/top-pages${urlParams}`)
            .then(res => res.json())
            .then(data => {
                const body = document.getElementById('metricsPagesBody');
                if (!body) return;
                if (!Array.isArray(data) || !data.length) {
                    const noDataText = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'No page data found.', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'No page data found.');
                    body.innerHTML = `<tr><td class="sp-table-empty" colspan="3">${noDataText}</td></tr>`;
                    return;
                }
                body.innerHTML = data.slice(0, 10).map(row => `
                    <tr>
                        <td class="sp-td-mono">${row.page}</td>
                        <td class="sp-td-right-bold">${parseInt(row.pageViews).toLocaleString()}</td>
                        <td class="sp-td-right">${parseInt(row.newUsers || 0).toLocaleString()}</td>
                    </tr>
                `).join('');
            });

        // 3. Sources
        fetch(`${restBase}/source-medium${urlParams}`)
            .then(res => res.json())
            .then(data => {
                const body = document.getElementById('metricsSourcesBody');
                if (!body) return;
                if (!Array.isArray(data) || !data.length) {
                    const noDataText = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'No source data found.', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'No source data found.');
                    body.innerHTML = `<tr><td class="sp-table-empty" colspan="2">${noDataText}</td></tr>`;
                    return;
                }
                body.innerHTML = data.slice(0, 5).map(row => `
                    <tr>
                        <td><strong>${row.sourceMedium}</strong></td>
                        <td class="sp-td-right-bold sp-td-accent">${parseInt(row.sessions).toLocaleString()}</td>
                    </tr>
                `).join('');
            });
    }

    refreshMetrics();
});
