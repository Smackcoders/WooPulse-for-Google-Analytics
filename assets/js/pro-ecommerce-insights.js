(function () {
    const restBase = typeof StorePulseEcommerceInsights !== 'undefined' ? StorePulseEcommerceInsights.restBase : '';
    const nonce = typeof StorePulseEcommerceInsights !== 'undefined' ? StorePulseEcommerceInsights.nonce : '';
    let currentTab = 'clv';

    // Tab switching
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-button').forEach(b => {
                b.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

            this.classList.add('active');
            currentTab = this.dataset.tab;
            const targetTab = document.getElementById('tab-' + currentTab);
            if (targetTab) {
                targetTab.classList.remove('hidden');
            }
        });
    });

    // Load insights
    const loadBtn = document.getElementById('load-insights');
    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            if (!startDateInput || !endDateInput) return;

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            this.disabled = true;
            this.innerHTML = '<span>⏳</span> Loading...';

            fetch(`${restBase}pro/ecommerce-insights?startDate=${startDate}&endDate=${endDate}&type=${currentTab}`, {
                headers: {
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin'
            })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<span>🔍</span> Load Insights';

                    if (data.error) {
                        alert('Error: ' + data.message);
                        return;
                    }

                    if (currentTab === 'clv') {
                        displayCLV(data);
                    } else if (currentTab === 'segmentation') {
                        displaySegmentation(data);
                    } else if (currentTab === 'attribution') {
                        displayAttribution(data);
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.innerHTML = '<span>🔍</span> Load Insights';
                    alert('Error loading insights: ' + err.message);
                });
        });
    }

    function displayCLV(data) {
        const container = document.getElementById('clv-content');
        if (!container) return;

        if (!data.customers || data.customers.length === 0) {
            container.innerHTML = '<div class="wpei-tab-empty"><div class="wpei-tab-empty-icon">📭</div><p>No customer data available for the selected period.</p></div>';
            return;
        }

        let html = '<div class="wpei-stat-grid">';
        html += `<div class="wpei-stat-card blue"><div class="wpei-stat-label">Total Customers</div><div class="wpei-stat-value">${data.summary.total_customers || 0}</div></div>`;
        html += `<div class="wpei-stat-card green"><div class="wpei-stat-label">Avg CLV</div><div class="wpei-stat-value">${data.summary.avg_clv || 0}</div></div>`;
        html += `<div class="wpei-stat-card purple"><div class="wpei-stat-label">Repeat Purchase Rate</div><div class="wpei-stat-value">${data.summary.repeat_rate || 0}%</div></div>`;
        html += '</div>';

        html += '<div style="overflow-x:auto;"><table class="wpei-inner-table"><thead><tr>';
        html += '<th>Customer</th><th>Orders</th><th>Total Revenue</th><th>CLV</th>';
        html += '</tr></thead><tbody>';

        data.customers.slice(0, 20).forEach(customer => {
            html += `<tr><td>${customer.email || 'Guest'}</td>`;
            html += `<td>${customer.order_count}</td>`;
            html += `<td>${customer.total_revenue}</td>`;
            html += `<td class="wpei-clv-bold">${customer.clv}</td></tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function displaySegmentation(data) {
        const container = document.getElementById('segmentation-content');
        if (!container) return;

        if (!data.segments || data.segments.length === 0) {
            container.innerHTML = '<div class="wpei-tab-empty"><div class="wpei-tab-empty-icon">📭</div><p>No segmentation data available for the selected period.</p></div>';
            return;
        }

        let html = '<div class="wpei-segment-grid">';
        data.segments.forEach(segment => {
            html += `<div class="wpei-segment-card">`;
            html += `<h3>${segment.name}</h3>`;
            html += `<div class="wpei-segment-stats">`;
            html += `<div><div class="wpei-seg-stat-label">Customers</div><div class="wpei-seg-stat-value">${segment.count}</div></div>`;
            html += `<div><div class="wpei-seg-stat-label">Revenue</div><div class="wpei-seg-stat-value">${segment.revenue}</div></div>`;
            html += `<div><div class="wpei-seg-stat-label">Avg Order Value</div><div class="wpei-seg-stat-value">${segment.avg_order_value}</div></div>`;
            html += `</div></div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function displayAttribution(data) {
        const container = document.getElementById('attribution-content');
        if (!container) return;

        if (!data.attribution || data.attribution.length === 0) {
            container.innerHTML = '<div class="wpei-tab-empty"><div class="wpei-tab-empty-icon">📭</div><p>No attribution data available for the selected period.</p></div>';
            return;
        }

        let html = '<div style="overflow-x:auto;"><table class="wpei-inner-table"><thead><tr>';
        html += '<th>Source / Medium</th><th>First Touch</th><th>Last Touch</th><th>Linear</th>';
        html += '</tr></thead><tbody>';

        data.attribution.forEach(attr => {
            html += `<tr><td><strong>${attr.source_medium}</strong></td>`;
            html += `<td>${attr.first_touch}</td>`;
            html += `<td>${attr.last_touch}</td>`;
            html += `<td>${attr.linear}</td></tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
})();
