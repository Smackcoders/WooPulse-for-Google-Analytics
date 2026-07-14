document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    const currencySymbol = typeof seoInsightsAjax !== 'undefined' && seoInsightsAjax.currency_symbol ? seoInsightsAjax.currency_symbol : '₹';

    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);

    // Initialize date picker
    if (typeof flatpickr !== 'undefined') {
        const dateInput = document.getElementById("journey-date-range");
        if (dateInput) {
            flatpickr(dateInput, {
                mode: "range",
                dateFormat: "d-m-Y",
                maxDate: "today",
                defaultDate: [startDate, endDate],
                onClose: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        startDate = selectedDates[0];
                        endDate = selectedDates[1];
                        loadUserJourney();
                    }
                }
            });
        }
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDateTime(timestamp) {
        const d = new Date(timestamp);
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const month = months[d.getMonth()];
        const date = d.getDate();
        const year = d.getFullYear();
        let hours = d.getHours();
        const minutes = d.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${month} ${date},${year} ${hours}.${minutes}${ampm}`;
    }

    function formatDuration(seconds) {
        if (seconds < 60) {
            return `${seconds}s`;
        }
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        if (remainingSeconds === 0) {
            return `${minutes}m`;
        }
        return `${minutes}m ${remainingSeconds}s`;
    }

    function loadUserJourney() {
        const tableBody = document.getElementById('session-journey-body'); // Changed ID
        const emptyEl = document.getElementById('journey-empty');
        const searchInput = document.getElementById('journey-search');

        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading user journeys...');
        }
        if (emptyEl) emptyEl.classList.add('hidden');
        if (tableBody) tableBody.classList.add('opacity-40');

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const search = searchInput ? searchInput.value : '';

        const url = `${restBase}/user-journey?startDate=${startStr}&endDate=${endStr}&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (tableBody) tableBody.classList.remove('opacity-40');

                if (data.sessions && data.sessions.length > 0) {
                    renderSessions(data.sessions); // New function call
                } else {
                    if (tableBody) tableBody.innerHTML = '';
                    if (emptyEl) emptyEl.classList.remove('hidden');
                }
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (tableBody) tableBody.classList.remove('opacity-40');
                console.error('Error loading user journey:', err);
            });
    }

    function renderSessions(sessions) {
        const tableBody = document.getElementById('session-journey-body');
        if (!tableBody) return;
        tableBody.innerHTML = sessions.map(session => `
            <tr class="session-summary transition-all hover:bg-slate-50/50 border-b border-slate-50 last:border-0" data-session-id="${session.session_id}">
                <td class="px-6 py-4 text-sm font-bold text-indigo-600 text-left">
                    <button class="toggle-details flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        ${session.session_id.substring(0, 8)}..
                    </button>
                </td>
                <td class="px-6 py-4 text-left whitespace-nowrap">
                    ${session.user_type === 'Guest'
                        ? `<span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-500 text-[11px] font-medium">Guest</span>`
                        : `<span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[11px] font-semibold">${session.user_type}</span>`
                    }
                </td>
                <td class="px-6 py-4 text-[13px] text-slate-500 text-left whitespace-nowrap">${formatDateTime(session.session_start)}</td>
                <td class="px-6 py-4 text-[13px] text-slate-500 text-left whitespace-nowrap">${formatDateTime(session.session_end)}</td>
                <td class="px-6 py-4 text-[13px] font-semibold text-slate-700 text-left">${formatDuration(session.session_duration)}</td>
                <td class="px-6 py-4 text-sm text-left">
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="journey-chip">Total Events: ${session.events.length}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-base font-bold text-slate-900 text-right">${session.total_order > 0 ? currencySymbol + parseFloat(session.total_order).toFixed(2) : '—'}</td>
            </tr>
            <tr class="session-details hidden" id="details-${session.session_id}">
                <td colspan="7" class="p-4 bg-slate-50">
                    <div class="p-4 border border-slate-200 rounded-lg bg-white">
                        <div class="mb-4">
                            <span class="text-xs font-semibold text-slate-400 uppercase">Anonymous ID:</span>
                            <code class="ml-2 px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-xs font-mono">${session.anon_id || 'N/A'}</code>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Session Events:</h4>
                        <ul class="list-disc pl-5 text-sm text-slate-600">
                            ${session.events.map(event => `
                                <li class="mb-2">
                                    <span class="font-medium text-slate-800">${formatDateTime(event.timestamp)}:</span>
                                    <span class="text-indigo-600">${event.label}</span>
                                    ${event.page_url ? `<a href="${event.page_url}" target="_blank" class="text-blue-500 hover:underline ml-2 text-xs">(${event.page_url.length > 50 ? event.page_url.substring(0, 50) + '...' : event.page_url})</a>` : ''}
                                    ${event.utm_source ? `<span class="ml-2 text-xs text-gray-500">(UTM: ${event.utm_source}/${event.utm_medium}/${event.utm_campaign})</span>` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');

        // Add event listeners for toggling details
        tableBody.querySelectorAll('.toggle-details').forEach(button => {
            button.addEventListener('click', function() {
                const sessionId = this.closest('tr').dataset.sessionId;
                const detailsRow = document.getElementById(`details-${sessionId}`);
                detailsRow.classList.toggle('hidden');
                this.querySelector('svg').classList.toggle('rotate-90');
            });
        });
    }

    // CSV Export Handler
    const exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const startStr = toLocalDateString(startDate);
            const endStr = toLocalDateString(endDate);
            const search = document.getElementById('journey-search').value;

            const url = `${restBase}/user-journey?startDate=${startStr}&endDate=${endStr}&search=${encodeURIComponent(search)}&format=csv`;

            const btn = this;
            const originalContent = btn.innerHTML;
            btn.textContent = '⏳ Exporting...';
            btn.disabled = true;

            fetch(url)
                .then(res => {
                    if (!res.ok) throw new Error('Export failed');
                    return res.blob();
                })
                .then(blob => {
                    const downloadUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = `user-journey-export-${toLocalDateString(new Date())}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(downloadUrl);

                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                })
                .catch(err => {
                    alert('Export failed: ' + err.message);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
        });
    }

    // Event listeners
    let searchTimeout;
    const searchInput = document.getElementById('journey-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadUserJourney, 500);
        });
    }

    // Initial load
    loadUserJourney();
});