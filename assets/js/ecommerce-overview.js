document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.currency_symbol : '₹';
    let ecommerceData = [];
    let sortColumn = 'revenue';
    let sortDirection = 'desc';

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    function toLocalDateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Initialize date picker
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#ecommerce-date-range", {
            mode: "range",
            dateFormat: "d/m/Y",
            maxDate: "today",
            defaultDate: [startDate, endDate],
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    startDate = selectedDates[0];
                    endDate = selectedDates[1];
                    loadEcommerceData();
                }
            }
        });
    }

    // Filter change handlers
    const filterCampaign = document.getElementById('filter-campaign');
    const filterMedium = document.getElementById('filter-medium');
    const filterSource = document.getElementById('filter-source');
    
    if (filterCampaign) filterCampaign.addEventListener('change', loadEcommerceData);
    if (filterMedium) filterMedium.addEventListener('change', loadEcommerceData);
    if (filterSource) filterSource.addEventListener('change', loadEcommerceData);

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(loadEcommerceData, 300));
    }

    // Reset filters
    const resetBtn = document.getElementById('wpec-reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (filterCampaign) filterCampaign.value = 'all';
            if (filterMedium) filterMedium.value = 'all';
            if (filterSource) filterSource.value = 'all';
            if (searchInput) searchInput.value = '';
            loadEcommerceData();
        });
    }

    // Sort handlers
    document.querySelectorAll('[data-sort]').forEach(th => {
        th.addEventListener('click', function () {
            const col = this.getAttribute('data-sort');
            if (sortColumn === col) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = col;
                sortDirection = 'desc';
            }
            updateSortIndicators();
            renderTable();
        });
    });

    const exportBtn = document.getElementById('export-btn');
    if (exportBtn) exportBtn.addEventListener('click', exportToCSV);

    function updateSortIndicators() {
        document.querySelectorAll('.sort-indicator').forEach(ind => { ind.textContent = '↕'; });
        const activeTh = document.querySelector(`[data-sort="${sortColumn}"]`);
        if (activeTh) {
            activeTh.querySelector('.sort-indicator').textContent = sortDirection === 'asc' ? '↑' : '↓';
        }
    }

    function loadEcommerceData() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading eCommerce data...');
        }
        const errorEl = document.getElementById('ecommerce-error');
        const errorMsg = document.getElementById('ecommerce-error-message');
        const emptyEl = document.getElementById('ecommerce-empty');
        const tbody = document.getElementById('ecommerce-table-body');

        if (errorEl) errorEl.classList.add('hidden');
        if (emptyEl) emptyEl.classList.add('hidden');

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const campaign = filterCampaign ? filterCampaign.value : 'all';
        const medium = filterMedium ? filterMedium.value : 'all';
        const source = filterSource ? filterSource.value : 'all';
        const search = searchInput ? searchInput.value : '';

        let url = `${restBase}/reports/ecommerce-overview?startDate=${startStr}&endDate=${endStr}`;
        if (campaign !== 'all') url += `&campaign=${encodeURIComponent(campaign)}`;
        if (medium !== 'all') url += `&medium=${encodeURIComponent(medium)}`;
        if (source !== 'all') url += `&source=${encodeURIComponent(source)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(async res => {
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(`${res.status} ${err.message || res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                ecommerceData = Array.isArray(data) ? data : [];
                renderTable();
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (errorMsg) errorMsg.textContent = 'Failed to load eCommerce data: ' + err.message;
                if (errorEl) errorEl.classList.remove('hidden');
                if (tbody) tbody.innerHTML = emptyRow('Error: ' + err.message);
                console.error('Fetch error:', err);
            });
    }

    function emptyRow(msg) {
        return `<tr><td class="wpec-tbl-empty" colspan="9">
            <div class="wpec-tbl-empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 14H10C10.5523 14 11 14.4477 11 15C11 15.5523 11.4477 16 12 16C12.5523 16 13 15.5523 13 15C13 14.4477 13.4477 14 14 14H20M4 14V17C4 18.6569 5.34315 20 7 20H17C18.6569 20 20 18.6569 20 17V14M4 14L4.82353 7.41176C4.94508 6.43936 5.77259 5.7 6.75336 5.7H17.2466C18.2274 5.7 19.0549 6.43936 19.1765 7.41176L20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="wpec-tbl-empty-text">${msg}</div>
        </td></tr>`;
    }

    function renderTable() {
        const tbody = document.getElementById('ecommerce-table-body');
        const emptyEl = document.getElementById('ecommerce-empty');
        const rowCnt = document.getElementById('wpec-row-count');

        if (!tbody) return;

        if (ecommerceData.length === 0) {
            tbody.innerHTML = emptyRow('No records to display.');
            if (emptyEl) emptyEl.classList.remove('hidden');
            if (rowCnt) rowCnt.textContent = '0 results';
            return;
        }

        if (emptyEl) emptyEl.classList.add('hidden');
        if (rowCnt) rowCnt.textContent = ecommerceData.length + ' result' + (ecommerceData.length !== 1 ? 's' : '');

        const sorted = [...ecommerceData].sort((a, b) => {
            let aVal = a[sortColumn], bVal = b[sortColumn];
            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase(); bVal = bVal.toLowerCase();
                return sortDirection === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
            return sortDirection === 'asc' ? (aVal > bVal ? 1 : -1) : (aVal < bVal ? 1 : -1);
        });

        tbody.innerHTML = sorted.map(row => `
            <tr>
                <td>
                    <div class="wpec-camp-name">${escapeHtml(row.campaign || 'Direct')}</div>
                    <div class="wpec-camp-meta">${escapeHtml(row.source)} / ${escapeHtml(row.medium)}</div>
                </td>
                <td>${(row.users || 0).toLocaleString()}</td>
                <td>${(row.sessions || 0).toLocaleString()}</td>
                <td>${(row.engaged_sessions || 0).toLocaleString()}</td>
                <td>${(row.bounce_rate || 0).toFixed(2)}%</td>
                <td>${(row.purchases || 0).toLocaleString()}</td>
                <td class="wpec-revenue">${currencySymbol}${(row.revenue || 0).toFixed(2)}</td>
                <td>${(row.revenue_percent || 0).toFixed(2)}%</td>
                <td>${(row.conversion_rate || 0).toFixed(2)}%</td>
            </tr>
        `).join('');
    }

    function exportToCSV() {
        if (ecommerceData.length === 0) { alert('No data to export'); return; }
        const headers = ['Campaign', 'Source', 'Medium', 'Users', 'Sessions', 'Engaged Sessions', 'Bounce Rate', 'Purchases', 'Revenue', 'Revenue %', 'Conversion Rate'];
        const rows = ecommerceData.map(row => [
            row.campaign || 'Direct', row.source || 'direct', row.medium || 'none',
            row.users || 0, row.sessions || 0, row.engaged_sessions || 0,
            (row.bounce_rate || 0).toFixed(2) + '%', row.purchases || 0,
            (row.revenue || 0).toFixed(2),
            (row.revenue_percent || 0).toFixed(2) + '%',
            (row.conversion_rate || 0).toFixed(2) + '%'
        ]);
        const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${c}"`).join(','))].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ecommerce-overview-${toLocalDateString(new Date())}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function debounce(func, wait) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => func(...args), wait); };
    }

    function escapeHtml(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    updateSortIndicators();
    loadEcommerceData();
});
