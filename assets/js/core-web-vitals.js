document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    let currentDevice = 'mobile';
    let gaugeChart = null;
    let requestInFlight = false;
    let lastRequestTime = 0;
    const cooldownMs = 3000;

    document.querySelectorAll('.device-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            if (requestInFlight) return;
            
            const now = Date.now();
            if (now - lastRequestTime < cooldownMs) {
                const mainErrorEl = document.getElementById('cwv-main-error');
                const mainErrorText = document.getElementById('cwv-main-error-text');
                const contentEl = document.getElementById('cwv-content');
                if (mainErrorEl && mainErrorText && contentEl) {
                    contentEl.classList.add('hidden');
                    mainErrorEl.classList.remove('hidden');
                    mainErrorText.textContent = "Rate limit reached — please wait a moment before checking again.";
                }
                return;
            }
            document.querySelectorAll('.device-toggle').forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-sm');
                b.classList.add('text-slate-500', 'hover:text-slate-800');
                b.style.color = '';
            });
            this.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
            this.classList.remove('text-slate-500', 'hover:text-slate-800');
            this.style.color = 'white';

            currentDevice = this.id === 'device-mobile' ? 'mobile' : 'desktop';
            
            const label = document.getElementById('current-device-label');
            if(label) label.textContent = currentDevice;

            loadCoreWebVitals();
        });
    });

    let lastRefreshTime = 0;
    const refreshCooldownMs = 60000;
    const refreshBtn = document.getElementById('btn-refresh-cwv');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            if (requestInFlight) return;
            const now = Date.now();
            if (now - lastRefreshTime < refreshCooldownMs) {
                return;
            }
            lastRefreshTime = now;
            
            refreshBtn.disabled = true;
            const btnText = refreshBtn.querySelector('.btn-text');
            const originalText = btnText.textContent;
            let countdown = 60;
            btnText.textContent = `Wait ${countdown}s`;
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(timer);
                    refreshBtn.disabled = false;
                    btnText.textContent = originalText;
                } else {
                    btnText.textContent = `Wait ${countdown}s`;
                }
            }, 1000);
            
            loadCoreWebVitals(true);
        });
    }

    function loadCoreWebVitals(forceRefresh = false) {
        if (requestInFlight) return;
        requestInFlight = true;
        lastRequestTime = Date.now();

        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Analyzing Web Vitals...');
        }
        const errorEl = document.getElementById('cwv-error');
        const errorMsg = document.getElementById('cwv-error-message');

        errorEl.classList.add('hidden');

        let attempts = 0;
        const maxAttempts = 30; // 90 seconds timeout (30 * 3s)
        const pollInterval = 3000;

        function fetchWithPoll() {
            const forceQuery = (attempts === 0 && forceRefresh) ? '&force_refresh=1' : '';
            fetch(`${restBase}/core-web-vitals?device=${currentDevice}${forceQuery}`)
                .then(res => res.json())
                .then(data => {
                    // Handle WP_Error response
                    if (data.code && data.message) {
                        data.error = data.message;
                    }

                    if (data.status === 'pending') {
                        attempts++;
                        if (attempts >= maxAttempts) {
                            throw new Error("Analysis timed out after 90 seconds. Please try again later.");
                        }
                        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
                            window.StorePulse.showLoader('Analyzing your site... this can take up to a minute.');
                        }
                        setTimeout(fetchWithPoll, pollInterval);
                        return; // wait for next poll, keep requestInFlight = true
                    }

                    if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                        window.StorePulse.hideLoader();
                    }

                    const contentEl = document.getElementById('cwv-content');
                    const mainErrorEl = document.getElementById('cwv-main-error');
                    const mainErrorText = document.getElementById('cwv-main-error-text');

                    if (data.error && !data.is_demo) {
                        if (contentEl && mainErrorEl && mainErrorText) {
                            contentEl.classList.add('hidden');
                            mainErrorEl.classList.remove('hidden');
                            
                            if (data.code === 'site_not_publicly_reachable') {
                                mainErrorText.textContent = "Core Web Vitals requires your site to be publicly accessible. This looks like a local/staging environment or your site may be in maintenance mode.";
                            } else {
                                mainErrorText.textContent = "Unable to fetch live performance data: " + data.error + " Your GA4/PageSpeed API quota may be exceeded, or this site may not be publicly reachable.";
                            }
                        } else {
                            errorMsg.textContent = data.error;
                            errorEl.classList.remove('hidden');
                        }
                        requestInFlight = false;
                        return;
                    }

                    if (!data || !data.metrics) {
                        throw new Error(data && data.message ? data.message : "Invalid response from server. Performance data is unavailable.");
                    }

                    // Remove old logic since demo mode is now strictly separated
                    if (data.metrics && data.metrics.is_demo) {
                        let demoBanner = document.getElementById('cwv-demo-banner');
                        if (!demoBanner) {
                            demoBanner = document.createElement('div');
                            demoBanner.id = 'cwv-demo-banner';
                            demoBanner.className = 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4 rounded shadow-sm text-sm font-bold flex items-center';
                            demoBanner.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> DEMO DATA — not your real site. Disable Demo Mode in settings to see live data.';
                            if (contentEl) contentEl.insertBefore(demoBanner, contentEl.firstChild);
                        }
                    } else {
                        let demoBanner = document.getElementById('cwv-demo-banner');
                        if (demoBanner) demoBanner.remove();
                    }

                    if (contentEl && mainErrorEl) {
                        contentEl.classList.remove('hidden');
                        mainErrorEl.classList.add('hidden');
                    }
                    
                    const lastCheckedEl = document.getElementById('cwv-last-checked');
                    if (lastCheckedEl && data.last_checked_msg) {
                        lastCheckedEl.textContent = data.last_checked_msg;
                    }

                    renderMetrics(data.metrics);
                    renderGauge(data.metrics);
                    renderImprovements(data.improvements || {}, data.metrics);
                    requestInFlight = false;
                })
                .catch(err => {
                    if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                        window.StorePulse.hideLoader();
                    }
                    const contentEl = document.getElementById('cwv-content');
                    const mainErrorEl = document.getElementById('cwv-main-error');
                    const mainErrorText = document.getElementById('cwv-main-error-text');
                    
                    const suffix = err.message.includes('timed out') ? '' : '. The server may be unreachable.';
                    if (contentEl && mainErrorEl && mainErrorText) {
                        contentEl.classList.add('hidden');
                        mainErrorEl.classList.remove('hidden');
                        mainErrorText.textContent = "Failed to load Core Web Vitals: " + err.message + suffix;
                    } else {
                        errorMsg.textContent = 'Failed to load Core Web Vitals: ' + err.message + suffix;
                        errorEl.classList.remove('hidden');
                    }
                    requestInFlight = false;
                });
        }

        fetchWithPoll();
    }

    function renderMetrics(metrics) {
        // FCP: < 1.8s = good, 1.8-3s = warning, >3s = poor
        updateMetric('fcp', metrics.fcp, 's', metrics.fcp < 1.8, metrics.fcp < 3);

        // LCP: < 2.5s = good, 2.5-4s = warning, >4s = poor
        updateMetric('lcp', metrics.lcp, 's', metrics.lcp < 2.5, metrics.lcp < 4);

        // CLS: < 0.1 = good, 0.1-0.25 = warning, >0.25 = poor
        updateMetric('cls', metrics.cls, '', metrics.cls < 0.1, metrics.cls < 0.25);

        // TBT: < 200ms = good, 200-600ms = warning, >600ms = poor
        updateMetric('tbt', metrics.tbt, 'ms', metrics.tbt < 200, metrics.tbt < 600);

        // SI: < 3.4s = good, 3.4-5.8s = warning, >5.8s = poor
        updateMetric('si', metrics.si, 's', metrics.si < 3.4, metrics.si < 5.8);
    }

    function updateMetric(id, value, unit, isGood, isWarning) {
        const valueEl = document.getElementById(`${id}-value`);
        const iconEl = document.getElementById(`${id}-icon`);

        valueEl.textContent = value.toFixed(id === 'cls' ? 3 : 1) + (unit ? unit : '');
        
        valueEl.classList.remove('text-emerald-500', 'text-amber-500', 'text-red-500');

        if (isGood) {
            iconEl.className = 'status-icon status-good';
            valueEl.style.color = '#00c875';
        } else if (isWarning) {
            iconEl.className = 'status-icon status-warning';
            valueEl.style.color = '#ffb020';
        } else {
            iconEl.className = 'status-icon status-poor';
            valueEl.style.color = '#f44336';
        }
    }

    function renderGauge(metrics) {
        const ctx = document.getElementById('performance-gauge');
        if (!ctx) return;

        if (gaugeChart) {
            gaugeChart.destroy();
        }

        const score = Math.round(metrics.score);
        const scoreEl = document.getElementById('gauge-score');
        scoreEl.textContent = score;

        let scoreColor = '#f44336';
        if (score >= 90) scoreColor = '#00c875';
        else if (score >= 50) scoreColor = '#ffb020';

        scoreEl.style.color = '#1e293b';

        gaugeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, Math.max(0, 100 - score)],
                    backgroundColor: [scoreColor, '#f1f5f9'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '85%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }

    function renderImprovements(improvements, metrics) {
        const listEl = document.getElementById('improvements-list');
        const metricNames = {
            'fcp': 'First Contentful Paint',
            'lcp': 'Largest Contentful Paint',
            'cls': 'Cumulative Layout Shift',
            'tbt': 'Total Blocking Time',
            'si': 'Speed Index'
        };

        const metricDetails = {
            'fcp': 'First Contentful Paint measures the time from when the page starts loading to when any part of the page\'s content is rendered on the screen. A fast FCP helps reassure the user that the page is loading.',
            'lcp': 'Largest Contentful Paint measures when the largest content element (e.g. a hero image or heading text) becomes visible within the viewport. This indicates when the main content of the page has likely loaded.',
            'cls': 'Cumulative Layout Shift measures the visual stability of a page. It quantifies how much content unexpectedly moves around while loading, which can be frustrating for users.',
            'tbt': 'Total Blocking Time measures the total amount of time that a page is blocked from responding to user input, such as mouse clicks, screen taps, or keyboard presses.',
            'si': 'Speed Index measures how quickly the contents of a page are visibly populated during load. It is a useful overall indicator of perceived load speed.'
        };

        listEl.innerHTML = Object.keys(metricNames).map(key => {
            const imp = improvements[key] || { status: 'passed', recommendations: [] };
            const isPassed = imp.status === 'passed' || imp.status === 'good';
            const isPoor = imp.status === 'error' || imp.status === 'poor';
            const isWarning = !isPassed && !isPoor;
            const value = metrics[key].toFixed(key === 'cls' ? 3 : 1) + (key === 'tbt' ? 'ms' : 's');

            return `
            <div class="improvement-row group border-b border-slate-100 last:border-0 rounded-none bg-white hover:bg-slate-50" id="row-${key}">
                <div class="improvement-header px-6 py-4 flex items-center justify-between cursor-pointer" onclick="toggleRow('${key}')">
                    <div class="flex items-center gap-4">
                        <div class="w-6 h-6 flex items-center justify-center rounded-full border border-slate-100 flex-shrink-0">
                            <span class="status-icon ${isPassed ? 'status-good' : (isPoor ? 'status-poor' : 'status-warning')}" style="width: 14px; height: 14px; font-size: 8px;"></span>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-700 m-0">${metricNames[key]}</h3>
                    </div>
                    <div class="flex items-center gap-4 pl-4">
                        <span class="text-sm font-bold text-slate-800 tracking-tight">${value}</span>
                    </div>
                </div>
                <div class="improvement-content px-6 pb-6 pt-0 hidden group-[.active]:block">
                    <div class="bg-indigo-50 bg-opacity-30 p-5 rounded-xl border border-indigo-50">
                        <p class="text-[13px] text-slate-600 leading-relaxed m-0">
                            ${metricDetails[key]} ${isPassed ? '<span class="font-bold ml-1" style="color: #00c875;">✓</span>' : ''}
                        </p>
                        ${!isPassed && imp.recommendations.length > 0 ?
                    `<div class="mt-4 pt-4 border-t border-indigo-100">
                                <p class="text-[9px] font-semibold text-indigo-500 uppercase tracking-widest mb-3 m-0">Recommendations:</p>
                                <ul class="space-y-2 m-0 p-0" style="list-style-type: none;">
                                    ${imp.recommendations.map(rec => `
                                        <li class="flex gap-3 text-[13px] text-slate-600 leading-relaxed">
                                            <span class="text-indigo-400">•</span>
                                            <span>${escapeHtml(rec)}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>` : ''
                }
                    </div>
                </div>
            </div>
            `;
        }).join('');
    }

    window.toggleRow = function (key) {
        const row = document.getElementById(`row-${key}`);
        const isActive = row.classList.contains('active');

        // Close others
        document.querySelectorAll('.improvement-row').forEach(r => r.classList.remove('active'));

        if (!isActive) {
            row.classList.add('active');
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial load
    loadCoreWebVitals();
});
