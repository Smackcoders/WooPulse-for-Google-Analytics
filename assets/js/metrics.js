document.addEventListener('DOMContentLoaded', function() {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/wp_asa/v1';
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
            badge.textContent = 'Syncing...';
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
                    <tr><td><strong>Total Sessions</strong></td><td style="text-align: right; font-weight: 600;">${total.toLocaleString()}</td></tr>
                    <tr><td><strong>Avg. Daily Sessions</strong></td><td style="text-align: right;">${Math.round(total / response.data.length).toLocaleString()}</td></tr>
                `;
                if (badge) {
                    badge.style.background = '#dcfce7'; 
                    badge.style.color = '#166534';
                    badge.textContent = 'GA4 Data Live';
                }
            }).catch(e => console.error(e));

        // 2. Top Pages
        fetch(`${restBase}/top-pages${urlParams}`)
            .then(res => res.json())
            .then(data => {
                const body = document.getElementById('metricsPagesBody');
                if (!body) return;
                if (!Array.isArray(data) || !data.length) {
                    body.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 20px;">No page data found.</td></tr>';
                    return;
                }
                body.innerHTML = data.slice(0, 10).map(row => `
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;">${row.page}</td>
                        <td style="text-align: right; font-weight: 600;">${parseInt(row.pageViews).toLocaleString()}</td>
                        <td style="text-align: right;">${parseInt(row.newUsers || 0).toLocaleString()}</td>
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
                    body.innerHTML = '<tr><td colspan="2" style="text-align:center; padding: 20px;">No source data found.</td></tr>';
                    return;
                }
                body.innerHTML = data.slice(0, 5).map(row => `
                    <tr>
                        <td><strong>${row.sourceMedium}</strong></td>
                        <td style="text-align: right; font-weight: 600; color: #2271b1;">${parseInt(row.sessions).toLocaleString()}</td>
                    </tr>
                `).join('');
            });
    }

    refreshMetrics();
});
