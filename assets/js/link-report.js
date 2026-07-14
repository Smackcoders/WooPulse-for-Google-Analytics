document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    let ageChart = null;
    let genderChart = null;

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    if (typeof flatpickr !== 'undefined' && document.getElementById('link-date-range')) {
        flatpickr('#link-date-range', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            defaultDate: [startDate, endDate],
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    startDate = selectedDates[0];
                    endDate = selectedDates[1];
                    loadAllData();
                }
            }
        });
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function loadAllData() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading link reports...');
        }
        Promise.all([
            loadLinks('inbound'),
            loadLinks('outbound'),
            loadLinks('affiliate'),
            loadLinks('downloadable'),
            loadDemographics()
        ]).finally(() => {
            if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                window.StorePulse.hideLoader();
            }
        });
    }

    function loadLinks(linkType) {
        const tbody = document.getElementById(`${linkType}-links-body`);
        if (!tbody) return Promise.resolve();

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const url = `${restBase}/reports/links?link_type=${linkType}&startDate=${startStr}&endDate=${endStr}`;

        return fetch(url)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data && (data.code || data.error)) {
                    tbody.innerHTML = `<tr><td colspan="2" class="px-4 py-6 text-center text-red-500 font-medium">Unable to load link data — please try again.</td></tr>`;
                    return;
                }

                if (!data || !Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center">
                                <div class="StorePulse-empty-state-icon flex justify-center text-gray-300 mb-3">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
                                        <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-500 font-medium text-[13px]">No data available yet.</span>
                            </td>
                        </tr>`;
                    return;
                }
                tbody.innerHTML = data.slice(0, 10).map((link, i) => `
            <tr>
                <td class="px-4 py-3 text-gray-700 text-xs">
                    <a href="${escapeHtml(link.link_url)}" target="_blank"
                       class="text-blue-600 hover:text-blue-800 hover:underline block max-w-sm truncate"
                       title="${escapeHtml(link.link_url)}">
                        ${escapeHtml(link.link_url)}
                    </a>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">${Number(link.clicks).toLocaleString()}</td>
            </tr>
        `).join('');
            })
            .catch((err) => {
                tbody.innerHTML = `<tr><td colspan="2" class="px-4 py-6 text-center text-red-500 font-medium">Unable to load link data — please try again.</td></tr>`;
            });
    }

    function loadDemographics() {
        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        return fetch(`${restBase}/reports/demographics?startDate=${startStr}&endDate=${endStr}`)
            .then(res => res.json())
            .then(data => {
                renderAgeChart(data.age || []);
                renderGenderChart(data.gender || []);
            })
            .catch(() => {
                renderAgeChart([]);
                renderGenderChart([]);
            });
    }

    function renderAgeChart(ageData) {
        const ctx = document.getElementById('age-chart');
        const noData = document.getElementById('age-no-data');
        if (!ctx) return;
        if (ageChart) ageChart.destroy();

        if (!ageData || ageData.length === 0) {
            if (noData) noData.classList.remove('hidden');
            return;
        }
        if (noData) noData.classList.add('hidden');

        const labels = ageData.map(d => d.age_group);
        const values = ageData.map(d => d.users);

        if (typeof Chart !== 'undefined') {
            ageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Users',
                        data: values,
                        backgroundColor: [
                            'rgba(147, 197, 253, 0.7)',
                            'rgba(96,  165, 250, 0.7)',
                            'rgba(59,  130, 246, 0.8)',
                            'rgba(37,   99, 235, 0.7)',
                            'rgba(147, 197, 253, 0.5)',
                        ],
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } }
                    }
                }
            });
        }
    }

    function renderGenderChart(genderData) {
        const ctx = document.getElementById('gender-chart');
        const noData = document.getElementById('gender-no-data');
        if (!ctx) return;
        if (genderChart) genderChart.destroy();

        if (!genderData || genderData.length === 0) {
            if (noData) noData.classList.remove('hidden');
            return;
        }
        if (noData) noData.classList.add('hidden');

        const labels = genderData.map(d => d.gender);
        const values = genderData.map(d => d.users);
        const total = values.reduce((a, b) => a + b, 0);

        if (typeof Chart !== 'undefined') {
            genderChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['rgba(236,72,153,0.85)', 'rgba(59,130,246,0.8)', 'rgba(209,213,219,0.8)'],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 11 }, color: '#6b7280', padding: 12, boxWidth: 12 }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const v = ctx.parsed;
                                    const pct = total > 0 ? ((v / total) * 100).toFixed(1) : 0;
                                    return ` ${ctx.label}: ${v.toLocaleString()} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    loadAllData();
});
