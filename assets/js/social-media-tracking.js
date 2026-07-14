document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.currency_symbol : '₹';

    let socialData = [];

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    // Network dot color mapping
    const networkDotClass = {
        'facebook': 'wsmt-dot--facebook',
        'instagram': 'wsmt-dot--instagram',
        'twitter': 'wsmt-dot--twitter',
        'twitter / x': 'wsmt-dot--twitter',
        'twitter/x': 'wsmt-dot--twitter',
        'x': 'wsmt-dot--x',
        'linkedin': 'wsmt-dot--linkedin',
        'pinterest': 'wsmt-dot--pinterest',
        'youtube': 'wsmt-dot--youtube',
        'tiktok': 'wsmt-dot--tiktok',
        'reddit': 'wsmt-dot--reddit',
    };
    function getDotClass(name) {
        return networkDotClass[(name || '').toLowerCase().trim()] || 'wsmt-dot--default';
    }

    // flatpickr instance
    let customDatePicker;

    // Date preset handler
    const datePreset = document.getElementById('social-date-preset');
    if (datePreset) {
        datePreset.addEventListener('change', function () {
            const val = this.value;
            if (val === 'custom') {
                if (customDatePicker) {
                    customDatePicker.open();
                }
                return;
            }
            const days = parseInt(val);
            endDate = new Date(); endDate.setHours(0, 0, 0, 0);
            startDate = new Date(); startDate.setDate(endDate.getDate() - days); startDate.setHours(0, 0, 0, 0);
            loadSocialData();
        });
    }

    // flatpickr for custom date range (hidden input)
    if (typeof flatpickr !== 'undefined') {
        const dateInput = document.getElementById("social-date-range");
        if (dateInput) {
            customDatePicker = flatpickr(dateInput, {
                mode: "range",
                dateFormat: "Y-m-d",
                maxDate: "today",
                defaultDate: [startDate, endDate],
                positionElement: document.getElementById('social-date-preset'),
                onClose: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        startDate = selectedDates[0];
                        endDate = selectedDates[1];
                        if (datePreset) datePreset.value = 'custom';
                        loadSocialData();
                    }
                }
            });
        }
    }

    // Compare period change
    const comparePeriodEl = document.getElementById('compare-period');
    if (comparePeriodEl) {
        comparePeriodEl.addEventListener('change', loadSocialData);
    }

    // View toggle buttons
    const viewTableBtn = document.getElementById('wsmt-view-table');
    if (viewTableBtn) {
        viewTableBtn.addEventListener('click', function () {
            this.classList.add('active');
            const viewEmptyBtn = document.getElementById('wsmt-view-empty');
            if (viewEmptyBtn) viewEmptyBtn.classList.remove('active');
            renderTable();
        });
    }

    const viewEmptyBtn = document.getElementById('wsmt-view-empty');
    if (viewEmptyBtn) {
        viewEmptyBtn.addEventListener('click', function () {
            this.classList.add('active');
            const viewTableBtn = document.getElementById('wsmt-view-table');
            if (viewTableBtn) viewTableBtn.classList.remove('active');
            showEmptyState('No social media data', 'No data found for the selected period.');
        });
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        return d.getFullYear() + '-'
            + (d.getMonth() + 1).toString().padStart(2, '0') + '-'
            + d.getDate().toString().padStart(2, '0');
    }

    function loadSocialData() {
        const errorEl = document.getElementById('social-error');
        const errorMsg = document.getElementById('social-error-message');
        const tbody = document.getElementById('social-table-body');

        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading social media data...');
        }
        if (errorEl) errorEl.classList.add('hidden');

        if (tbody) {
            tbody.innerHTML = `<tr><td class="wsmt-tbl-empty" colspan="9">
                <div class="wsmt-empty-sub">Loading...</div></td></tr>`;
        }

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const comparePeriod = document.getElementById('compare-period') ? document.getElementById('compare-period').value : 'none';

        const url = `${restBase}/reports/social-media-tracking?startDate=${startStr}&endDate=${endStr}&comparePeriod=${comparePeriod}`;

        fetch(url)
            .then(res => res.text())
            .then(text => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                let data;
                try { data = JSON.parse(text); }
                catch (e) {
                    if (errorMsg) errorMsg.textContent = 'API returned an invalid response. Please check PHP error logs.';
                    if (errorEl) errorEl.classList.remove('hidden');
                    console.error('Social media API raw response:', text);
                    return;
                }
                socialData = data;
                renderTable();
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (errorMsg) errorMsg.textContent = 'Failed to load social media data: ' + err.message;
                if (errorEl) errorEl.classList.remove('hidden');
            });
    }

    function showEmptyState(title, sub) {
        const tbody = document.getElementById('social-table-body');
        if (!tbody) return;
        tbody.innerHTML = `<tr><td class="wsmt-tbl-empty" colspan="9">
            <div class="wsmt-empty-circle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="#4f46e5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div class="wsmt-empty-title">${title}</div>
            <div class="wsmt-empty-sub">${sub}</div>
        </td></tr>`;
    }

    function renderTable() {
        const tbody = document.getElementById('social-table-body');
        if (!tbody) return;

        const comparePeriodEl = document.getElementById('compare-period');
        const comparePeriod = comparePeriodEl ? comparePeriodEl.value : 'none';
        const showComparison = comparePeriod !== 'none';

        if (!socialData || socialData.length === 0) {
            showEmptyState('No social media data found', 'No data found for the selected period.');
            return;
        }

        let html = '';

        socialData.forEach((network, idx) => {
            const current = network.current;
            const comparison = network.comparison;
            const change = network.change;
            const dotClass = getDotClass(network.network);

            // Main data row
            html += `<tr>
                <td>
                    <div class="wsmt-network-cell">
                        <span class="wsmt-dot ${dotClass}"></span>
                        ${escapeHtml(network.network)}
                    </div>
                </td>
                <td>${current.users.toLocaleString()}</td>
                <td>${current.sessions.toLocaleString()}</td>
                <td>${current.engaged_sessions.toLocaleString()}</td>
                <td>${current.bounce_rate.toFixed(2)}%</td>
                <td>${current.purchases.toLocaleString()}</td>
                <td class="wsmt-revenue">${currencySymbol}${formatRevenue(current.revenue)}</td>
                <td>${current.revenue_percent.toFixed(2)}%</td>
                <td>${current.conversion_rate.toFixed(2)}%</td>
            </tr>`;

            // Comparison + change rows (only if compare is active)
            if (showComparison && comparison) {
                const comparePeriodLabel = `${formatDate(network.comparison_period.start)} – ${formatDate(network.comparison_period.end)}`;
                html += `<tr>
                    <td class="wsmt-compare-label" colspan="9">
                        vs. ${comparePeriodLabel}
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="wsmt-compare-val">${comparison.users.toLocaleString()}</td>
                    <td class="wsmt-compare-val">${comparison.sessions.toLocaleString()}</td>
                    <td class="wsmt-compare-val">${comparison.engaged_sessions.toLocaleString()}</td>
                    <td class="wsmt-compare-val">${comparison.bounce_rate.toFixed(2)}%</td>
                    <td class="wsmt-compare-val">${comparison.purchases.toLocaleString()}</td>
                    <td class="wsmt-compare-val">${currencySymbol}${formatRevenue(comparison.revenue)}</td>
                    <td class="wsmt-compare-val">—</td>
                    <td class="wsmt-compare-val">${comparison.conversion_rate.toFixed(2)}%</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="${changeClass(change.users)}">${formatChange(change.users)}%</td>
                    <td class="${changeClass(change.sessions)}">${formatChange(change.sessions)}%</td>
                    <td class="${changeClass(change.engaged_sessions)}">${formatChange(change.engaged_sessions)}%</td>
                    <td class="${changeClass(-change.bounce_rate)}">${formatChange(-change.bounce_rate)}%</td>
                    <td class="${changeClass(change.purchases)}">${formatChange(change.purchases)}%</td>
                    <td class="${changeClass(change.revenue)}">${formatChange(change.revenue)}%</td>
                    <td class="wsmt-change-neutral">—</td>
                    <td class="${changeClass(change.conversion_rate)}">${formatChange(change.conversion_rate)}%</td>
                </tr>`;
            }

            // Section divider between networks
            if (idx < socialData.length - 1) {
                html += `<tr class="wsmt-section-divider"><td colspan="9"></td></tr>`;
            }
        });

        tbody.innerHTML = html;
    }

    function formatRevenue(val) {
        if (typeof val === 'number') return val.toFixed(2);
        return parseFloat(String(val).replace(/,/g, '')).toFixed(2) || '0.00';
    }
    function formatChange(val) {
        if (val === null || val === undefined) return 'N/A';
        const sign = val >= 0 ? '+' : '';
        return sign + val.toFixed(2);
    }
    function changeClass(val) {
        if (val > 0) return 'wsmt-change-up';
        if (val < 0) return 'wsmt-change-down';
        return 'wsmt-change-neutral';
    }
    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // CSV Export handler
    const exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span>⏳</span> Exporting...';
            btn.disabled = true;

            const startStr = startDate.toISOString().split('T')[0];
            const endStr = endDate.toISOString().split('T')[0];
            const comparePeriod = document.getElementById('compare-period') ? document.getElementById('compare-period').value : 'none';
            const url = `${restBase}/reports/social-media-tracking?startDate=${startStr}&endDate=${endStr}&comparePeriod=${comparePeriod}&format=csv`;

            fetch(url, {
                headers: {
                    'X-WP-Nonce': typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.nonce : ''
                }
            })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `social_media_tracking_${startStr}_to_${endStr}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                })
                .catch(err => {
                    console.error('Export failed:', err);
                    alert('Export failed: ' + err.message);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                });
        });
    }

    // Initial load
    loadSocialData();
});
