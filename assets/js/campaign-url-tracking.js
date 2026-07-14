document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.currency_symbol : '₹';
    let transactionsData = [];
    let currentPage = 1;
    let totalPages = 1;
    let total = 0;
    let sortColumn = 'purchase_date';
    let sortDirection = 'desc';

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    // Initialize date picker
    if (typeof flatpickr !== 'undefined') {
        const formatDynamicRange = (selectedDates, instance) => {
            if (selectedDates.length === 2) {
                const d1 = selectedDates[0];
                const d2 = selectedDates[1];
                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                let result = "";
                if (d1.getFullYear() === d2.getFullYear()) {
                    result = monthNames[d1.getMonth()] + " " + d1.getDate() + " – " + monthNames[d2.getMonth()] + " " + d2.getDate() + ", " + d1.getFullYear();
                } else {
                    result = monthNames[d1.getMonth()] + " " + d1.getDate() + ", " + d1.getFullYear() + " – " + monthNames[d2.getMonth()] + " " + d2.getDate() + ", " + d2.getFullYear();
                }

                // Use requestAnimationFrame to ensure we update AFTER flatpickr's own formatting
                requestAnimationFrame(() => {
                    if (instance.altInput) {
                        instance.altInput.value = result;
                    }
                });
            }
        };

        flatpickr("#transactions-date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "M j, Y",
            altInputClass: "wcut-date-input",
            locale: { rangeSeparator: " – " },
            maxDate: "today",
            defaultDate: [startDate, endDate],
            onReady: function (selectedDates, dateStr, instance) {
                formatDynamicRange(selectedDates, instance);
            },
            onValueUpdate: function (selectedDates, dateStr, instance) {
                formatDynamicRange(selectedDates, instance);
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    startDate = selectedDates[0];
                    endDate = selectedDates[1];
                    currentPage = 1;
                    loadTransactions();
                }
            }
        });
    }

    // Filter change handlers
    document.getElementById('filter-campaign').addEventListener('change', function () { currentPage = 1; loadTransactions(); });
    document.getElementById('filter-medium').addEventListener('change', function () { currentPage = 1; loadTransactions(); });
    document.getElementById('filter-source').addEventListener('change', function () { currentPage = 1; loadTransactions(); });
    document.getElementById('search-input').addEventListener('input', debounce(function () { currentPage = 1; loadTransactions(); }, 300));

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

    // Pagination handlers
    document.getElementById('prev-page').addEventListener('click', function () {
        if (currentPage > 1) { currentPage--; loadTransactions(); }
    });
    document.getElementById('next-page').addEventListener('click', function () {
        if (currentPage < totalPages) { currentPage++; loadTransactions(); }
    });

    function updateSortIndicators() {
        document.querySelectorAll('.sort-indicator').forEach(ind => { ind.textContent = '↕'; });
        const activeTh = document.querySelector(`[data-sort="${sortColumn}"]`);
        if (activeTh) {
            activeTh.querySelector('.sort-indicator').textContent = sortDirection === 'asc' ? '↑' : '↓';
        }
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        return d.getFullYear() + '-'
            + (d.getMonth() + 1).toString().padStart(2, '0') + '-'
            + d.getDate().toString().padStart(2, '0');
    }

    function loadTransactions() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading transactions...');
        }
        const errorEl = document.getElementById('transactions-error');
        const errorMsg = document.getElementById('transactions-error-message');
        const tbody = document.getElementById('transactions-table-body');

        errorEl.classList.add('hidden');
        tbody.innerHTML = `<tr><td class="wcut-tbl-empty" colspan="6">
            <div class="wcut-empty-sub">Loading transactions...</div></td></tr>`;

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const campaign = document.getElementById('filter-campaign').value;
        const medium = document.getElementById('filter-medium').value;
        const source = document.getElementById('filter-source').value;
        const search = document.getElementById('search-input').value;

        let url = `${restBase}/reports/transactions?startDate=${startStr}&endDate=${endStr}&page=${currentPage}&per_page=20`;
        if (campaign !== 'all') url += `&campaign=${encodeURIComponent(campaign)}`;
        if (medium !== 'all') url += `&medium=${encodeURIComponent(medium)}`;
        if (source !== 'all') url += `&source=${encodeURIComponent(source)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                transactionsData = data.transactions || [];
                total = data.total || 0;
                totalPages = data.total_pages || 1;
                renderTable();
                updatePagination();
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                errorMsg.textContent = 'Failed to load transactions: ' + err.message;
                errorEl.classList.remove('hidden');
                tbody.innerHTML = emptyRow('Failed to load data', '');
            });
    }

    function emptyRow(title, sub) {
        return `<tr><td class="wcut-tbl-empty" colspan="6">
            <div class="wcut-empty-circle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="#4f46e5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
            </div>
            <div class="wcut-empty-title">${title}</div>
            ${sub ? `<div class="wcut-empty-sub">${sub}</div>` : ''}
        </td></tr>`;
    }

    function renderTable() {
        const tbody = document.getElementById('transactions-table-body');

        if (transactionsData.length === 0) {
            tbody.innerHTML = emptyRow(
                'No transactions found for the selected filters.',
                'Try adjusting your filters or date range'
            );
            return;
        }

        const sorted = [...transactionsData].sort((a, b) => {
            let aVal = a[sortColumn];
            let bVal = b[sortColumn];
            if (sortColumn === 'purchase_date') {
                aVal = new Date(aVal); bVal = new Date(bVal);
            } else if (sortColumn === 'order_total' || sortColumn === 'transaction_id') {
                aVal = parseFloat(aVal); bVal = parseFloat(bVal);
            } else {
                aVal = String(aVal).toLowerCase(); bVal = String(bVal).toLowerCase();
            }
            return sortDirection === 'asc' ? (aVal > bVal ? 1 : -1) : (aVal < bVal ? 1 : -1);
        });

        const orderBase = typeof campaignTrackingVars !== 'undefined' ? campaignTrackingVars.order_base_url : '';
        tbody.innerHTML = sorted.map(txn => {
            const orderUrl = orderBase + txn.transaction_id + '&action=edit';
            return `<tr>
                <td><a href="${orderUrl}" class="wcut-txn-link" target="_blank">#${txn.transaction_id}</a></td>
                <td>${formatDate(txn.purchase_date)}</td>
                <td><span class="wcut-campaign">${escapeHtml(txn.campaign || 'Direct')}</span></td>
                <td><span class="wcut-medium">${escapeHtml(txn.medium || '-')}</span></td>
                <td>${escapeHtml(txn.source || '-')}</td>
                <td class="wcut-total">${currencySymbol}${parseFloat(txn.order_total).toFixed(2)}</td>
            </tr>`;
        }).join('');
    }

    function updatePagination() {
        const info = document.getElementById('pagination-info');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');

        const start = total === 0 ? 0 : (currentPage - 1) * 20 + 1;
        const end = Math.min(currentPage * 20, total);
        info.textContent = `Showing ${start}-${end} of ${total} transactions`;

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
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

    // CSV Export Handler
    document.getElementById('btn-export-csv').addEventListener('click', function () {
        const btn = this;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span>⏳</span> Exporting...';
        btn.disabled = true;

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const campaign = document.getElementById('filter-campaign').value;
        const medium = document.getElementById('filter-medium').value;
        const source = document.getElementById('filter-source').value;
        const search = document.getElementById('search-input').value;

        let url = `${restBase}/reports/transactions?startDate=${startStr}&endDate=${endStr}&format=csv`;
        if (campaign !== 'all') url += `&campaign=${encodeURIComponent(campaign)}`;
        if (medium !== 'all') url += `&medium=${encodeURIComponent(medium)}`;
        if (source !== 'all') url += `&source=${encodeURIComponent(source)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        fetch(url, {
            headers: { 'X-WP-Nonce': typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.nonce : '' }
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
                a.download = `campaign_transactions_${startStr}_to_${endStr}.csv`;
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

    // Initial load
    updateSortIndicators();
    loadTransactions();
});
