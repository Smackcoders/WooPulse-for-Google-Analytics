document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.currency_symbol : '₹';
    let salesChart = null;

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    function updateDateDisplay(start, end) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        const display = document.getElementById('display-date-range');
        if (display) {
            display.innerText = `${start.toLocaleDateString('en-US', options)} — ${end.toLocaleDateString('en-US', options)}`;
        }
    }
    updateDateDisplay(startDate, endDate);

    // Initialize date picker
    const dateInput = document.getElementById("sales-date-range");
    const dateBtn = document.getElementById("sales-date-range-btn");

    if (dateBtn && dateInput) {
        dateBtn.addEventListener('click', () => {
            if (dateInput._flatpickr) {
                dateInput._flatpickr.open();
            }
        });
    }

    if (typeof flatpickr !== 'undefined' && dateInput) {
        flatpickr(dateInput, {
            mode: "range",
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: [startDate, endDate],
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    startDate = selectedDates[0];
                    endDate = selectedDates[1];
                    updateDateDisplay(startDate, endDate);
                    loadSalesData();
                }
            }
        });
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Shared state for export
    let currentSalesData = null;
    let currentTransactions = [];
    let currentChartLabels = [];
    let currentChartData = [];

    function loadSalesData() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading sales data...');
        }
        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);

        const normalizedBase = restBase.endsWith('/') ? restBase.slice(0, -1) : restBase;
        const buildUrl = (endpoint, params = {}) => {
            const urlParams = new URLSearchParams(params).toString();
            const separator = normalizedBase.includes('?') ? '&' : '?';
            return `${normalizedBase}${endpoint}${separator}${urlParams}`;
        };

        const params = { startDate: startStr, endDate: endStr };

        const wcPromise = fetch(buildUrl('/wc-metrics', params)).then(r => r.ok ? r.json() : {});
        const gaPromise = fetch(buildUrl('/kpi-metrics', params)).then(r => r.ok ? r.json() : {});
        const chartPromise = fetch(buildUrl('/daily-metrics', params)).then(r => r.ok ? r.json() : {});
        const transPromise = fetch(buildUrl('/reports/transactions', { ...params, limit: 5 })).then(r => r.ok ? r.json() : {});

        Promise.all([wcPromise, gaPromise, chartPromise, transPromise])
            .then(([data, gaData, chartData, transData]) => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                data = data || {};
                gaData = gaData || {};
                chartData = chartData || {};
                transData = transData || {};

                currentSalesData = data;
                const gaSessions = gaData.sessions || 0;
                const wcOrders = data.orders || 0;
                const convRate = gaSessions > 0 ? (wcOrders / gaSessions) * 100 : 0;
                currentSalesData._corrected_conversion_rate = convRate;

                const safeParseFloat = (val) => parseFloat(String(val).replace(/,/g, '')) || 0;
                
                const revenueEl = document.getElementById('sales-total-revenue');
                if (revenueEl) revenueEl.textContent = currencySymbol + safeParseFloat(data.revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const ordersEl = document.getElementById('sales-total-orders');
                if (ordersEl) ordersEl.textContent = wcOrders.toLocaleString();
                
                const aovEl = document.getElementById('sales-aov');
                if (aovEl) aovEl.textContent = currencySymbol + safeParseFloat(data.avg_order).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const convEl = document.getElementById('sales-conversion-rate');
                if (convEl) convEl.textContent = convRate.toFixed(2) + '%';

                // Chart Data
                currentChartLabels = chartData.labels || [];
                currentChartData = chartData.data || [];
                renderChart(currentChartLabels, currentChartData);

                // Top Products rendering
                const topProductsEl = document.getElementById('sales-top-products');
                const topProducts = data.top_products || [];
                if (topProductsEl) {
                    if (topProducts && topProducts.length > 0) {
                        topProductsEl.classList.remove('justify-center', 'items-center');
                        topProductsEl.classList.add('space-y-4', 'w-full');
                        topProductsEl.innerHTML = topProducts.map(p => `
                        <div class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0 hover:bg-gray-50 transition-colors p-2 rounded-lg">
                            <div class="flex-1 min-w-0 pr-4">
                                <p class="font-semibold text-gray-800 truncate text-[0.75rem]" title="${p.name}">${p.name}</p>
                                <p class="text-[0.75rem] text-gray-400 font-medium uppercase tracking-wider">${p.qty} units sold</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 whitespace-nowrap text-[0.75rem]">${currencySymbol}${safeParseFloat(p.revenue).toLocaleString()}</p>
                            </div>
                        </div>
                    `).join('');
                    } else {
                        topProductsEl.classList.add('justify-center', 'items-center');
                        topProductsEl.innerHTML = `
                        <div class="text-center">
                            <div class="mb-4 inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-50 border border-gray-100">
                                <svg class="text-gray-300" style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                            </div>
                            <p class="text-gray-400 text-sm">No top products found</p>
                        </div>`;
                    }
                }

                // Transactions rendering
                const transEl = document.getElementById('sales-recent-transactions');
                const transactions = transData.transactions || [];
                currentTransactions = transactions;
                if (transEl) {
                    if (transactions && transactions.length > 0) {
                        transEl.classList.remove('justify-center', 'items-center');
                        transEl.classList.add('space-y-4', 'w-full');
                        transEl.innerHTML = transactions.map(t => `
                        <div class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0 hover:bg-gray-50 transition-colors p-2 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-[0.75rem]">Order #${t.order_id}</p>
                                    <p class="text-[0.75rem] text-gray-400 font-medium">${t.date}</p>
                                </div>
                            </div>
                            <p class="font-bold text-emerald-500 whitespace-nowrap text-[0.75rem]">+${currencySymbol}${safeParseFloat(t.revenue).toLocaleString()}</p>
                        </div>
                    `).join('');
                    } else {
                        transEl.classList.add('justify-center', 'items-center');
                        transEl.innerHTML = `
                        <div class="text-center">
                            <div class="mb-4 inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-50 border border-gray-100">
                                <svg class="text-gray-300" style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <p class="text-gray-400 text-sm">No recent transactions found</p>
                        </div>`;
                    }
                }
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                console.error('Failed to load sales data:', err);
            });
    }

    function renderChart(labels, data) {
        const canvas = document.getElementById('sales-summary-chart');
        if (!canvas || typeof Chart === 'undefined') return;

        if (salesChart) { salesChart.destroy(); }

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        try {
            salesChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels.map(l => {
                        const d = new Date(l);
                        return isNaN(d) ? l : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        borderColor: '#10b981',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.5, // High tension for "wavy" look
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#10b981',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleColor: '#94a3b8',
                            bodyColor: '#f8fafc',
                            bodyFont: { weight: 'bold' },
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    return currencySymbol + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7, color: '#94a3b8', font: { size: 11 } }
                        },
                        y: {
                            border: { display: false },
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
                                callback: (value) => currencySymbol + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Chart creation failed:', e);
        }
    }

    // ===== EXPORT BUTTON — Download CSV =====
    const exportBtn = document.getElementById('sales-export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const startStr = toLocalDateString(startDate);
            const endStr = toLocalDateString(endDate);

            let csv = '';

            // Header
            csv += 'Sales Summary Report\n';
            csv += `Date Range,${startStr} to ${endStr}\n\n`;

            // KPI Section
            if (currentSalesData) {
                const safeParseFloatExp = (val) => parseFloat(String(val).replace(/,/g, '')) || 0;
                csv += 'KPI,Value\n';
                csv += `Total Revenue,${currencySymbol}${safeParseFloatExp(currentSalesData.revenue).toFixed(2)}\n`;
                csv += `Total Orders,${currentSalesData.orders || 0}\n`;
                csv += `Avg. Order Value,${currencySymbol}${safeParseFloatExp(currentSalesData.avg_order).toFixed(2)}\n`;
                csv += `Conversion Rate,${parseFloat(currentSalesData._corrected_conversion_rate || 0).toFixed(2)}%\n\n`;
            }

            // Daily Revenue
            if (currentChartLabels.length > 0) {
                csv += 'Daily Revenue\n';
                csv += 'Date,Revenue\n';
                currentChartLabels.forEach((label, i) => {
                    csv += `${label},${currentChartData[i] || 0}\n`;
                });
                csv += '\n';
            }

            // Top Products
            if (currentSalesData && currentSalesData.top_products && currentSalesData.top_products.length > 0) {
                csv += 'Top Products\n';
                csv += 'Product Name,Qty Sold,Revenue\n';
                currentSalesData.top_products.forEach(p => {
                    csv += `"${p.name}",${p.qty},${currencySymbol}${p.revenue}\n`;
                });
                csv += '\n';
            }

            // Recent Transactions
            if (currentTransactions.length > 0) {
                csv += 'Recent Transactions\n';
                csv += 'Order ID,Date,Revenue\n';
                currentTransactions.forEach(t => {
                    csv += `#${t.order_id},${t.date},${currencySymbol}${t.revenue}\n`;
                });
            }

            // Trigger CSV download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `sales-summary-${startStr}-to-${endStr}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    // Initial load
    loadSalesData();
});
