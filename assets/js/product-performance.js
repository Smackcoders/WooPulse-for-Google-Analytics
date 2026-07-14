document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.currency_symbol : '₹';
    let productData = [];
    let sortColumn = 'revenue';
    let sortDirection = 'desc';

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    // Initialize date picker
    if (typeof flatpickr !== 'undefined') {
        const dateInput = document.getElementById('product-date-range');
        if (dateInput) {
            flatpickr(dateInput, {
                mode: "range",
                dateFormat: "Y-m-d",
                maxDate: "today",
                defaultDate: [startDate, endDate],
                onClose: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        startDate = selectedDates[0];
                        endDate = selectedDates[1];
                        loadProductData();
                    }
                }
            });
        }
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

    // Export handler
    const exportBtn = document.getElementById('product-export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToCSV);
    }

    function updateSortIndicators() {
        document.querySelectorAll('.sort-indicator').forEach(ind => {
            ind.classList.remove('opacity-100');
            ind.classList.add('opacity-0');
            ind.textContent = '↕';
        });
        const activeTh = document.querySelector(`[data-sort="${sortColumn}"]`);
        if (activeTh) {
            const indicator = activeTh.querySelector('.sort-indicator');
            if (indicator) {
                indicator.classList.remove('opacity-0');
                indicator.classList.add('opacity-100');
                indicator.textContent = sortDirection === 'asc' ? '↑' : '↓';
            }
        }
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function loadProductData() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading product data...');
        }
        const errorEl = document.getElementById('product-error');
        const errorMsg = document.getElementById('product-error-message');
        const tableBody = document.getElementById('product-performance-body');

        if (errorEl) errorEl.classList.add('hidden');
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">Loading...</td></tr>';

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);

        fetch(`${restBase}/wc-metrics?startDate=${startStr}&endDate=${endStr}&full=true`)
            .then(res => res.json())
            .then(data => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (data.error) throw new Error(data.error);

                productData = (data.top_products || []).map(val => {
                    let purchases = parseInt(val.qty) || 0;
                    let views = parseInt(val.views) || 0;
                    let add_to_cart = parseInt(val.add_to_cart) || 0;
                    let checkout = parseInt(val.checkout) || 0;

                    let conv_rate = views > 0 ? ((purchases / views) * 100).toFixed(1) : parseFloat(0).toFixed(1);

                    return {
                        ...val,
                        views,
                        add_to_cart,
                        checkout,
                        conv_rate
                    };
                });

                renderTable();
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (errorMsg) errorMsg.textContent = 'Failed to load product data: ' + err.message;
                if (errorEl) errorEl.classList.remove('hidden');
            });
    }

    function renderTable() {
        const tbody = document.getElementById('product-performance-body');
        if (!tbody) return;

        const searchInput = document.getElementById('product-search');
        const search = searchInput ? searchInput.value.toLowerCase() : '';

        let filtered = productData.filter(p => !search || p.name.toLowerCase().includes(search));

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr>
                <td colspan="7" class="px-6 py-20 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">No product performance data</h3>
                    <p class="text-sm text-slate-500 mb-1">No data found for the selected date range</p>
                    <p class="text-xs text-slate-400">Try adjusting filters or tracking settings</p>
                </td>
            </tr>`;
            return;
        }

        // Sort data
        const sorted = [...filtered].sort((a, b) => {
            let aVal = a[sortColumn];
            let bVal = b[sortColumn];

            if (['revenue', 'avg_price', 'views', 'add_to_cart', 'checkout', 'qty', 'conv_rate'].includes(sortColumn)) {
                aVal = parseFloat(aVal.toString().replace(/[^0-9.-]+/g, "")) || 0;
                bVal = parseFloat(bVal.toString().replace(/[^0-9.-]+/g, "")) || 0;
            }

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        tbody.innerHTML = sorted.map(row => `
        <tr class="hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-700">${escapeHtml(row.name)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-500">${row.views.toLocaleString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-500">${row.add_to_cart.toLocaleString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-500">${row.checkout.toLocaleString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-800">${row.qty.toLocaleString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-black text-slate-800">${currencySymbol}${row.revenue}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold text-slate-500">${row.conv_rate}%</td>
        </tr>
    `).join('');
    }

    function exportToCSV() {
        if (productData.length === 0) {
            alert('No data to export');
            return;
        }

        const headers = ['Product Name', 'Quantity Sold', 'Revenue'];
        const csv = [
            headers.join(','),
            ...productData.map(row => [
                `"${row.name}"`,
                row.qty,
                row.revenue.toString().replace(/,/g, '')
            ].join(','))
        ].join('\n');

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `product-performance-${toLocalDateString(new Date())}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial load
    updateSortIndicators();
    loadProductData();
});
