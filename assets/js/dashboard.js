const pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};
function __(text) {
	return (window.wp && window.wp.i18n && window.wp.i18n.__) ? window.wp.i18n.__(text, 'smackcoders-pulse-analytics-for-woocommerce') : text;
}
/**
 * Pulse Analytics — Dedicated Free Dashboard JS
 * Handles Free traffic metrics, parallel async calls, Segmented Tabs, Metrics Popover multi-line chart filtering, date picker, Chart.js rendering, and Site Notes integration.
 */
function redirectWpNotices() {
    const wpbody = document.getElementById('wpbody-content') || document.body;
    const isPluginPage = document.querySelector(
        '.pulse-analytics-ui, .smack-tab-pane, #smackws-cores-tabcontent, #PulseAnalytics-dashboard-v2, #PulseAnalytics-settings-v2, .sp-dashboard-container, .wrap[id^="PulseAnalytics-"]'
    ) || document.getElementById('pulse-analytics-admin-notices-container');

    if (!isPluginPage) {
        return;
    }

    let container = document.getElementById('pulse-analytics-admin-notices-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'pulse-analytics-admin-notices-container';

        const noticeArea = document.querySelector('.pulse-analytics-notices-area');
        const tabPane = document.querySelector('.smack-tab-pane');
        const pluginWrap = document.querySelector(
            '.pulse-analytics-ui, #PulseAnalytics-dashboard-v2, #PulseAnalytics-settings-v2, .sp-dashboard-container, .wrap'
        );

        if (noticeArea) {
            noticeArea.appendChild(container);
        } else if (tabPane) {
            tabPane.prepend(container);
        } else if (pluginWrap) {
            pluginWrap.prepend(container);
        } else {
            wpbody.prepend(container);
        }
    }

    const notices = wpbody.querySelectorAll(
        '.notice, .updated, .error, .notice-success, .notice-error, .notice-warning, .notice-info, .settings-error'
    );

    notices.forEach(function (notice) {
        if (
            notice.id === 'pulse-analytics-admin-notices-container' ||
            notice.classList.contains('pulse-analytics-notices-area') ||
            container.contains(notice) ||
            notice.closest('#pulse-analytics-admin-notices-container') ||
            notice.closest('.pulse-analytics-pro-modal, .sp-modal')
        ) {
            return;
        }

        container.appendChild(notice);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    redirectWpNotices();
    setTimeout(function () { redirectWpNotices(); }, 200);
    setTimeout(function () { redirectWpNotices(); }, 800);

    if (typeof window.MutationObserver !== 'undefined') {
        const wpbody = document.getElementById('wpbody-content') || document.body;
        const noticeObserver = new MutationObserver(function (mutations) {
            let shouldRedirect = false;
            for (let i = 0; i < mutations.length; i++) {
                const added = mutations[i].addedNodes;
                for (let j = 0; j < added.length; j++) {
                    const node = added[j];
                    if (node.nodeType === 1) {
                        if (
                            node.classList &&
                            (node.classList.contains('notice') ||
                             node.classList.contains('updated') ||
                             node.classList.contains('error') ||
                             node.classList.contains('notice-success') ||
                             node.classList.contains('notice-error') ||
                             node.classList.contains('notice-warning') ||
                             node.classList.contains('notice-info') ||
                             node.classList.contains('settings-error'))
                        ) {
                            if (!node.closest('#pulse-analytics-admin-notices-container')) {
                                shouldRedirect = true;
                                break;
                            }
                        }
                        if (node.querySelector && node.querySelector('.notice, .updated, .error, .notice-success, .notice-error, .notice-warning, .notice-info, .settings-error')) {
                            shouldRedirect = true;
                            break;
                        }
                    }
                }
                if (shouldRedirect) break;
            }
            if (shouldRedirect) {
                redirectWpNotices();
            }
        });
        noticeObserver.observe(wpbody, { childList: true, subtree: true });
    }

    const dashboardContainer = document.getElementById('PulseAnalytics-dashboard-v2') || document.querySelector('.sp-dashboard-container');
    if (!dashboardContainer) {
        return;
    }

    const restUrl = (Object.keys(pulseAjax).length && pulseAjax.rest_url) ? pulseAjax.rest_url : '/wp-json/pulse-analytics/v1';
    const nonce = (Object.keys(pulseAjax).length && pulseAjax.nonce) ? pulseAjax.nonce : '';    function getMenuKey() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || '';
        if (page.includes('traffic')) return 'traffic';
        if (page.includes('gsc') || page.includes('search-console')) return 'gsc';
        if (page.includes('ecommerce')) return 'ecommerce';
        if (page.includes('site-notes')) return 'site_notes';
        if (page.includes('performance')) return 'performance';
        return 'dashboard';
    }

    function getSessionCalendarKey() {
        return 'session_pa_' + getMenuKey() + '_calendar';
    }

    function getSavedCalendarSession() {
        try {
            const raw = sessionStorage.getItem(getSessionCalendarKey());
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && parsed.start && parsed.end) {
                    return parsed;
                }
            }
        } catch (e) {}
        return null;
    }

    function saveCalendarSession(start, end) {
        try {
            sessionStorage.setItem(getSessionCalendarKey(), JSON.stringify({ start: start, end: end }));
        } catch (e) {}
    }

    let currentStartDate = '30daysAgo';
    let currentEndDate = 'yesterday';

    const savedCal = getSavedCalendarSession();
    if (savedCal) {
        currentStartDate = savedCal.start;
        currentEndDate = savedCal.end;
    }

    let lastDashboardData = null;
    let activeTab = 'traffic';

    const metricColors = {
        sessions: { border: '#3B82F6', bg: 'rgba(59, 130, 246, 0.08)', label: __('Sessions') },
        engagedSessions: { border: '#10B981', bg: 'rgba(16, 185, 129, 0.08)', label: __('Engaged Sessions') },
        keyEventRate: { border: '#F59E0B', bg: 'rgba(245, 158, 11, 0.08)', label: __('Key Event Rate') },
        totalUsers: { border: '#8B5CF6', bg: 'rgba(139, 92, 246, 0.08)', label: __('Total Users') },
        newUsers: { border: '#EC4899', bg: 'rgba(236, 72, 153, 0.08)', label: __('New Users') },
        returningUsers: { border: '#6366F1', bg: 'rgba(99, 102, 241, 0.08)', label: __('Returning Users') },
        engagementRate: { border: '#14B8A6', bg: 'rgba(20, 184, 166, 0.08)', label: __('Engagement Rate') },
        sessionDuration: { border: '#6366F1', bg: 'rgba(99, 102, 241, 0.08)', label: __('Avg. Session Duration') },
        pageviewsPerUser: { border: '#F97316', bg: 'rgba(249, 115, 22, 0.08)', label: __('Pageviews / User') },
        bounceRate: { border: '#EF4444', bg: 'rgba(239, 68, 68, 0.08)', label: __('Bounce Rate') },
        purchases: { border: '#06B6D4', bg: 'rgba(6, 182, 212, 0.08)', label: __('Purchases') },
        revenue: { border: '#84CC16', bg: 'rgba(132, 204, 22, 0.08)', label: __('Revenue') },
        aov: { border: '#A855F7', bg: 'rgba(168, 85, 247, 0.08)', label: __('Average Order Value') }
    };

    function getSpinnerHtml() {
        return '<span class="inline-flex items-center justify-center text-indigo-500 animate-spin"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>';
    }

    function formatDuration(seconds) {
        seconds = Math.round(Number(seconds) || 0);
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins + 'm ' + secs + 's';
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(Number(num) || 0);
    }

    function showLoadingState() {
        const spinner = getSpinnerHtml();
        const valueSelectors = [
            '#live-visitors-count', '#kpi-sessions', '#kpi-pageviews',
            '#kpi-duration', '#kpi-engagement', '#vt-new-pct', '#vt-ret-pct',
            '#dv-mob-pct', '#dv-desk-pct', '#dv-tab-pct'
        ];

        valueSelectors.forEach(sel => {
            const el = document.querySelector(sel);
            if (el) el.innerHTML = spinner;
        });

        const chartWrap = document.getElementById('trendChartWrap');
        if (chartWrap) {
            chartWrap.classList.add('relative');
            let overlay = chartWrap.querySelector('.summary-loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'summary-loading-overlay absolute inset-0 bg-white/75 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-lg';
                overlay.innerHTML = getSpinnerHtml();
                chartWrap.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        }

        const pagesBody = document.getElementById('overviewTopPagesBody');
        if (pagesBody) pagesBody.innerHTML = '<tr><td colspan="2" class="p-6 text-center text-gray-400">' + spinner + '</td></tr>';

        const countriesBody = document.getElementById('overviewTopCountriesBody');
        if (countriesBody) countriesBody.innerHTML = '<tr><td colspan="2" class="p-6 text-center text-gray-400">' + spinner + '</td></tr>';

        const sourcesBody = document.getElementById('sourceMediumBody');
        if (sourcesBody) sourcesBody.innerHTML = '<tr><td colspan="2" class="p-6 text-center text-gray-400">' + spinner + '</div>';

        const browserWrap = document.getElementById('browserChartWrap');
        if (browserWrap) browserWrap.innerHTML = '<div class="p-6 text-center text-gray-400">' + spinner + '</div>';
    }

    function getKeyEventsTypesHtml() {
        let types = (lastDashboardData && Array.isArray(lastDashboardData.key_event_types)) ? lastDashboardData.key_event_types : [
            { name: 'Purchases', event: 'purchase', desc: 'eCommerce completed orders' },
            { name: 'Form Submissions', event: 'generate_lead', desc: 'Lead forms & contact entries' },
            { name: 'Outbound Clicks', event: 'click', desc: 'External & affiliate link clicks' },
            { name: 'File Downloads', event: 'file_download', desc: 'Documents & media downloads' }
        ];

        let itemsHtml = types.map(t => {
            return '<li><span>' + t.name + '</span><code>' + t.event + '</code></li>';
        }).join('');

        return '<div class="sp-popover-title">Key Event Types Included</div><ul>' + itemsHtml + '</ul>';
    }

    function showKeyEventPopover(evt) {
        let popover = document.getElementById('sp-key-event-popover');
        if (!popover) {
            popover = document.createElement('div');
            popover.id = 'sp-key-event-popover';
            document.body.appendChild(popover);
        }
        popover.innerHTML = getKeyEventsTypesHtml();

        const clientX = (evt && evt.native) ? evt.native.clientX : (evt ? (evt.clientX || 0) : 0);
        const clientY = (evt && evt.native) ? evt.native.clientY : (evt ? (evt.clientY || 0) : 0);
        const pageX = (evt && evt.native) ? evt.native.pageX : (clientX + window.scrollX);
        const pageY = (evt && evt.native) ? evt.native.pageY : (clientY + window.scrollY);

        popover.style.left = Math.max(10, pageX - 120) + 'px';
        popover.style.top = Math.max(10, pageY - (popover.offsetHeight || 140) - 12) + 'px';
        popover.classList.add('sp-visible');
    }

    function hideKeyEventPopover() {
        const popover = document.getElementById('sp-key-event-popover');
        if (popover) {
            popover.classList.remove('sp-visible');
        }
    }

    function renderLineChart(labels, datasets, datesYmd) {
        const ctx = document.getElementById('lineChart');
        if (!ctx) return;

        if (window.lineChartInstance && typeof window.lineChartInstance.destroy === 'function') {
            window.lineChartInstance.destroy();
        }

        if (typeof Chart === 'undefined') return;

        const hasRightAxis = datasets && datasets.some(d => d.yAxisID === 'y1');

        window.lineChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels || [],
                datasets: datasets || []
            },
            plugins: [{
                id: 'smPulseAnalyticsDynamicCrosshair',
                afterDraw: function (chart) {
                    if (window.SmPulseAnalyticsChartTooltip && window.SmPulseAnalyticsChartTooltip.crosshairPlugin) {
                        window.SmPulseAnalyticsChartTooltip.crosshairPlugin.afterDraw(chart);
                    }
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: datasets && datasets.length > 1,
                        onHover: function (evt, legendItem) {
                            if (legendItem && legendItem.text && legendItem.text.includes('Key Event')) {
                                showKeyEventPopover(evt);
                            } else {
                                hideKeyEventPopover();
                            }
                        },
                        onLeave: function () {
                            hideKeyEventPopover();
                        }
                    },
                    tooltip: {
                        enabled: false,
                        position: 'nearest',
                        external: function (context) {
                            if (window.SmPulseAnalyticsChartTooltip && typeof window.SmPulseAnalyticsChartTooltip.createExternalTooltip === 'function') {
                                window.SmPulseAnalyticsChartTooltip.createExternalTooltip(function (chart, dp) {
                                    const dataset = chart.data.datasets[dp.datasetIndex];
                                    return dataset ? (dataset.label || 'Value') : 'Value';
                                })(context);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: '#F3F4F6' },
                        ticks: {
                            precision: 0,
                            callback: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: hasRightAxis,
                        position: 'right',
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: {
                            callback: function (val) {
                                return Math.round(val) + '%';
                            }
                        }
                    }
                }
            }
        });

        // Attach date array for Site Notes overlay
        if (datesYmd && Array.isArray(datesYmd)) {
            window.lineChartInstance.$smPulseAnalyticsChartDates = datesYmd;
        }

        if (window.smPulseAnalyticsSiteNotesChart && typeof window.smPulseAnalyticsSiteNotesChart.refreshChartOverlays === 'function') {
            window.smPulseAnalyticsSiteNotesChart.refreshChartOverlays();
        }
    }

    function updateKpiSummary(tabName) {
        const kpi = (lastDashboardData && lastDashboardData.kpi) ? lastDashboardData.kpi : {};
        const lbl1 = document.getElementById('summary-label-1');
        const val1 = document.getElementById('summary-sessions');
        const lbl2 = document.getElementById('summary-label-2');
        const val2 = document.getElementById('summary-engaged');
        const lbl3 = document.getElementById('summary-label-3');
        const val3 = document.getElementById('summary-keyevent');

        if (!lbl1 || !val1 || !lbl2 || !val2 || !lbl3 || !val3) return;

        const totalSessions = Number(kpi.sessions) || 0;
        const engagementRate = Number(kpi.engagement_rate) || 0;

        if (tabName === 'engagement') {
            let totalEngagedSessions = 0;
            let avgEngageRate = engagementRate * 100;
            let avgBounceRate = (1 - engagementRate) * 100;

            if (lastDashboardData && lastDashboardData.daily_engagement && Array.isArray(lastDashboardData.daily_engagement.engagedSessions)) {
                totalEngagedSessions = lastDashboardData.daily_engagement.engagedSessions.reduce((a, b) => a + (Number(b) || 0), 0);
            } else {
                totalEngagedSessions = Math.round(totalSessions * engagementRate);
            }

            if (lastDashboardData && lastDashboardData.daily_engagement && Array.isArray(lastDashboardData.daily_engagement.engagementRate) && lastDashboardData.daily_engagement.engagementRate.length > 0) {
                const rates = lastDashboardData.daily_engagement.engagementRate.map(v => Number(v) || 0);
                avgEngageRate = rates.reduce((a, b) => a + b, 0) / rates.length;
            }

            if (lastDashboardData && lastDashboardData.daily_engagement && Array.isArray(lastDashboardData.daily_engagement.bounceRate) && lastDashboardData.daily_engagement.bounceRate.length > 0) {
                const bounces = lastDashboardData.daily_engagement.bounceRate.map(v => Number(v) || 0);
                avgBounceRate = bounces.reduce((a, b) => a + b, 0) / bounces.length;
            }

            lbl1.textContent = __('Engaged Sessions');
            val1.textContent = formatNumber(totalEngagedSessions);

            lbl2.textContent = __('Engagement Rate');
            val2.textContent = avgEngageRate.toFixed(1) + '%';

            lbl3.textContent = __('Bounce Rate');
            val3.textContent = avgBounceRate.toFixed(1) + '%';
        } else if (tabName === 'referrals') {
            let referralSessions = 0;
            let topReferralDomain = '(none)';

            if (lastDashboardData && lastDashboardData.daily_referrals) {
                if (Array.isArray(lastDashboardData.daily_referrals.top_5_sources) && lastDashboardData.daily_referrals.top_5_sources.length > 0) {
                    topReferralDomain = lastDashboardData.daily_referrals.top_5_sources[0];
                } else if (Array.isArray(lastDashboardData.daily_referrals.top_sources) && lastDashboardData.daily_referrals.top_sources.length > 0) {
                    topReferralDomain = lastDashboardData.daily_referrals.top_sources[0];
                }

                if (Array.isArray(lastDashboardData.daily_referrals.referral_sessions)) {
                    referralSessions = lastDashboardData.daily_referrals.referral_sessions.reduce((a, b) => a + (Number(b) || 0), 0);
                } else if (lastDashboardData.daily_referrals.series && Array.isArray(lastDashboardData.daily_referrals.series.total)) {
                    referralSessions = lastDashboardData.daily_referrals.series.total.reduce((a, b) => a + (Number(b) || 0), 0);
                }
            } else if (lastDashboardData && Array.isArray(lastDashboardData.top_referrals) && lastDashboardData.top_referrals.length > 0) {
                topReferralDomain = lastDashboardData.top_referrals[0].referrer || '(none)';
                referralSessions = lastDashboardData.top_referrals.reduce((a, r) => a + (Number(r.sessions) || 0), 0);
            }

            const referralShare = totalSessions > 0 ? ((referralSessions / totalSessions) * 100).toFixed(1) + '%' : '0.0%';

            lbl1.textContent = __('Top Referral Source');
            val1.textContent = topReferralDomain;

            lbl2.textContent = __('Referral Sessions');
            val2.textContent = formatNumber(referralSessions);

            lbl3.textContent = __('Referral Share');
            val3.textContent = referralShare;
        } else if (tabName === 'ecommerce') {
            let totalRev = 0;
            let totalOrders = 0;
            let aov = 0;
            let convRate = 0;

            if (lastDashboardData && lastDashboardData.daily_ecommerce) {
                const revArr = lastDashboardData.daily_ecommerce.revenue || [];
                const ordArr = lastDashboardData.daily_ecommerce.orders || [];
                totalRev = revArr.reduce((a, b) => a + (Number(b) || 0), 0);
                totalOrders = ordArr.reduce((a, b) => a + (Number(b) || 0), 0);
                aov = totalOrders > 0 ? (totalRev / totalOrders) : 0;
            }

            if (totalSessions > 0) {
                convRate = (totalOrders / totalSessions) * 100;
            }

            const currSymbol = window.PulseAnalytics_currency_symbol || '$';
            const ecomRev = document.getElementById('ecom-summary-revenue');
            const ecomOrd = document.getElementById('ecom-summary-transactions');
            const ecomAov = document.getElementById('ecom-summary-aov');
            const ecomConv = document.getElementById('ecom-summary-conversion');

            if (ecomRev) ecomRev.textContent = currSymbol + formatNumber(totalRev.toFixed(2));
            if (ecomOrd) ecomOrd.textContent = formatNumber(totalOrders);
            if (ecomAov) ecomAov.textContent = currSymbol + formatNumber(aov.toFixed(2));
            if (ecomConv) ecomConv.textContent = convRate.toFixed(2) + '%';
        } else {
            // Default: traffic tab
            let totalUsers = (kpi && kpi.total_users !== undefined) ? kpi.total_users : 0;
            let newUsers = (kpi && kpi.new_users !== undefined) ? kpi.new_users : 0;

            if (!totalUsers && lastDashboardData && lastDashboardData.daily && Array.isArray(lastDashboardData.daily.totalUsers)) {
                totalUsers = lastDashboardData.daily.totalUsers.reduce((a, b) => a + (Number(b) || 0), 0);
            }
            if (!newUsers && lastDashboardData && lastDashboardData.daily && Array.isArray(lastDashboardData.daily.newUsers)) {
                newUsers = lastDashboardData.daily.newUsers.reduce((a, b) => a + (Number(b) || 0), 0);
            }

            lbl1.textContent = __('Sessions');
            val1.textContent = formatNumber(totalSessions);

            lbl2.textContent = __('Total Users');
            val2.textContent = formatNumber(totalUsers);

            lbl3.textContent = __('New Users');
            val3.textContent = formatNumber(newUsers);
        }
    }

    function renderEcommerceTrendChart(labels, datasets, datesYmd) {
        const ctx = document.getElementById('ecommerceTrendChart');
        if (!ctx) return;

        if (typeof Chart === 'undefined') return;

        const existingChart = Chart.getChart(ctx) || Chart.getChart('ecommerceTrendChart') || window.ecommerceChartInstance;
        if (existingChart && typeof existingChart.destroy === 'function') {
            existingChart.destroy();
        }

        const hasRightAxis = datasets && datasets.some(d => d.yAxisID === 'y1');

        window.ecommerceChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels || [],
                datasets: datasets || []
            },
            plugins: [{
                id: 'smPulseAnalyticsDynamicCrosshairEcom',
                afterDraw: function (chart) {
                    if (window.SmPulseAnalyticsChartTooltip && window.SmPulseAnalyticsChartTooltip.crosshairPlugin) {
                        window.SmPulseAnalyticsChartTooltip.crosshairPlugin.afterDraw(chart);
                    }
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: datasets && datasets.length > 1
                    },
                    tooltip: {
                        enabled: false,
                        position: 'nearest',
                        external: function (context) {
                            if (window.SmPulseAnalyticsChartTooltip && typeof window.SmPulseAnalyticsChartTooltip.createExternalTooltip === 'function') {
                                window.SmPulseAnalyticsChartTooltip.createExternalTooltip(function (chart, dp) {
                                    const dataset = chart.data.datasets[dp.datasetIndex];
                                    return dataset ? (dataset.label || 'Value') : 'Value';
                                })(context);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: '#F3F4F6' },
                        ticks: {
                            callback: function (val) {
                                return (window.PulseAnalytics_currency_symbol || '$') + Math.round(val);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: hasRightAxis,
                        position: 'right',
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: {
                            precision: 0,
                            callback: function (val) {
                                return Math.round(val);
                            }
                        }
                    }
                }
            }
        });

        if (datesYmd && Array.isArray(datesYmd)) {
            window.ecommerceChartInstance.$smPulseAnalyticsChartDates = datesYmd;
        }
    }

    function updateChartForTab(tabName) {
        activeTab = tabName;
        const trendChartWrap = document.getElementById('trendChartWrap');
        const trendKpiSummary = document.getElementById('trendKpiSummary');
        const trendPanelEcommerce = document.getElementById('trendPanelEcommerce');
        const metricsDropdownContainer = document.getElementById('metricsDropdownContainer');

        let datasets = [];

        if (tabName === 'ecommerce') {
            if (trendChartWrap) trendChartWrap.classList.add('hidden');
            if (trendKpiSummary) trendKpiSummary.classList.add('hidden');
            if (metricsDropdownContainer) metricsDropdownContainer.classList.add('hidden');
            if (trendPanelEcommerce) trendPanelEcommerce.classList.remove('hidden');

            updateKpiSummary('ecommerce');

            if (!lastDashboardData || !lastDashboardData.daily_ecommerce) return;

            const labels = lastDashboardData.daily_ecommerce.labels || (lastDashboardData.daily ? lastDashboardData.daily.labels : []);
            const datesYmd = lastDashboardData.daily_ecommerce.dates_ymd || (lastDashboardData.daily ? lastDashboardData.daily.dates_ymd : labels);
            const revenue = (lastDashboardData.daily_ecommerce.revenue || []).map(v => Number(v) || 0);
            const orders = (lastDashboardData.daily_ecommerce.orders || []).map(v => Number(v) || 0);
            const aov = (lastDashboardData.daily_ecommerce.aov || []).map(v => Number(v) || 0);

            const activeMetric = window.activeEcomMetric || 'all';

            const dsRevenue = {
                label: 'Revenue',
                data: revenue,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'currency'
            };
            const dsOrders = {
                label: 'Orders',
                data: orders,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1',
                unitType: 'count'
            };
            const dsAov = {
                label: 'Average Order Value (AOV)',
                data: aov,
                borderColor: '#8B5CF6',
                backgroundColor: 'rgba(139, 92, 246, 0.08)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'currency'
            };

            if (activeMetric === 'revenue') {
                datasets = [dsRevenue];
            } else if (activeMetric === 'orders') {
                dsOrders.yAxisID = 'y';
                datasets = [dsOrders];
            } else if (activeMetric === 'aov') {
                datasets = [dsAov];
            } else {
                dsOrders.fill = false;
                dsAov.fill = false;
                datasets = [dsRevenue, dsOrders, dsAov];
            }

            renderEcommerceTrendChart(labels, datasets, datesYmd);
            return;
        }

        if (trendChartWrap) trendChartWrap.classList.remove('hidden');
        if (trendKpiSummary) trendKpiSummary.classList.remove('hidden');
        if (metricsDropdownContainer) metricsDropdownContainer.classList.add('hidden');
        if (trendPanelEcommerce) trendPanelEcommerce.classList.add('hidden');

        updateKpiSummary(tabName);

        if (!lastDashboardData || !lastDashboardData.daily) return;

        const labels = lastDashboardData.daily.labels || [];
        const datesYmd = lastDashboardData.daily.dates_ymd || labels;

        if (tabName === 'referrals') {
            const dailyRef = lastDashboardData.daily_referrals || {};
            const refSessions = (dailyRef.referral_sessions || []).map(v => Number(v) || 0);
            const refUsers = (dailyRef.referral_users || []).map(v => Number(v) || 0);
            const top5Sources = Array.isArray(dailyRef.top_5_sources) ? dailyRef.top_5_sources : (Array.isArray(dailyRef.top_sources) ? dailyRef.top_sources : []);
            const top5Series = dailyRef.top_5_series || (dailyRef.series || {});

            const sourceColors = [
                { border: '#8B5CF6', bg: 'rgba(139, 92, 246, 0.08)' },
                { border: '#F59E0B', bg: 'rgba(245, 158, 11, 0.08)' },
                { border: '#EC4899', bg: 'rgba(236, 72, 153, 0.08)' },
                { border: '#06B6D4', bg: 'rgba(6, 182, 212, 0.08)' },
                { border: '#6366F1', bg: 'rgba(99, 102, 241, 0.08)' }
            ];

            datasets = [
                {
                    label: 'Referral Sessions',
                    data: refSessions.length > 0 ? refSessions : labels.map(() => 0),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y',
                    unitType: 'count'
                },
                {
                    label: 'Referral Users',
                    data: refUsers.length > 0 ? refUsers : labels.map(() => 0),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y',
                    unitType: 'count'
                }
            ];

            top5Sources.forEach((srcDomain, idx) => {
                const srcData = (top5Series[srcDomain] && Array.isArray(top5Series[srcDomain]))
                    ? top5Series[srcDomain].map(v => Number(v) || 0)
                    : labels.map(() => 0);
                const colorStyle = sourceColors[idx % sourceColors.length];

                datasets.push({
                    label: srcDomain,
                    data: srcData,
                    borderColor: colorStyle.border,
                    backgroundColor: colorStyle.bg,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y',
                    unitType: 'count'
                });
            });

            renderLineChart(labels, datasets, datesYmd);
            return;
        }

        if (tabName === 'engagement') {
            const dailyEngage = lastDashboardData.daily_engagement || {};
            const engagedSessions = (dailyEngage.engagedSessions || []).map(v => Number(v) || 0);
            const engagementRate = (dailyEngage.engagementRate || []).map(v => Number(v) || 0);
            const bounceRate = (dailyEngage.bounceRate || []).map(v => Number(v) || 0);

            datasets = [
                {
                    label: 'Engaged Sessions',
                    data: engagedSessions.length > 0 ? engagedSessions : labels.map(() => 0),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y',
                    unitType: 'count'
                },
                {
                    label: 'Engagement Rate',
                    data: engagementRate.length > 0 ? engagementRate : labels.map(() => 0),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1',
                    unitType: 'rate'
                },
                {
                    label: 'Bounce Rate',
                    data: bounceRate.length > 0 ? bounceRate : labels.map(() => 0),
                    borderColor: '#F43F5E',
                    backgroundColor: 'rgba(244, 63, 94, 0.08)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1',
                    unitType: 'rate'
                }
            ];

            renderLineChart(labels, datasets, datesYmd);
            return;
        }

        // Default: tabName === 'traffic'
        const dailyTraffic = lastDashboardData.daily || {};
        const sessions = (dailyTraffic.sessions || dailyTraffic.data || []).map(v => Number(v) || 0);
        const totalUsers = (dailyTraffic.totalUsers || []).map(v => Number(v) || 0);
        const newUsers = (dailyTraffic.newUsers || []).map(v => Number(v) || 0);
        const pageviews = (dailyTraffic.pageviews || []).map(v => Number(v) || 0);

        datasets = [
            {
                label: 'Sessions',
                data: sessions,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'count'
            },
            {
                label: 'Total Users',
                data: totalUsers.length > 0 ? totalUsers : sessions.map(v => Math.round(v * 0.9)),
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                fill: false,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'count'
            },
            {
                label: 'New Users',
                data: newUsers.length > 0 ? newUsers : sessions.map(v => Math.round(v * 0.6)),
                borderColor: '#8B5CF6',
                backgroundColor: 'rgba(139, 92, 246, 0.08)',
                fill: false,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'count'
            },
            {
                label: 'Pageviews',
                data: pageviews.length > 0 ? pageviews : sessions.map(v => Math.round(v * 2.1)),
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.08)',
                fill: false,
                tension: 0.3,
                yAxisID: 'y',
                unitType: 'count'
            }
        ];

        renderLineChart(labels, datasets, datesYmd);
    }
    window.updateChartForTab = updateChartForTab;

    function renderNewVsReturning(visitorTypes) {
        const newPctEl = document.getElementById('vt-new-pct');
        const retPctEl = document.getElementById('vt-ret-pct');
        const progNew = document.getElementById('vt-progress-new');
        const progRet = document.getElementById('vt-progress-ret');

        let newCount = 0;
        let retCount = 0;

        if (visitorTypes && Array.isArray(visitorTypes.data)) {
            newCount = Number(visitorTypes.data[0]) || 0;
            retCount = Number(visitorTypes.data[1]) || 0;
        }

        const total = newCount + retCount;
        const newPct = total > 0 ? Math.round((newCount / total) * 100) : 0;
        const retPct = total > 0 ? (100 - newPct) : 0;

        if (newPctEl) newPctEl.textContent = newPct + '%';
        if (retPctEl) retPctEl.textContent = retPct + '%';
        if (progNew) progNew.style.width = newPct + '%';
        if (progRet) progRet.style.width = retPct + '%';
    }

    function renderDeviceTypes(devices) {
        const mobEl = document.getElementById('dv-mob-pct');
        const deskEl = document.getElementById('dv-desk-pct');
        const tabEl = document.getElementById('dv-tab-pct');

        let mob = 0, desk = 0, tab = 0;

        if (devices && Array.isArray(devices.labels) && Array.isArray(devices.data)) {
            devices.labels.forEach((label, idx) => {
                const l = String(label).toLowerCase();
                const val = Number(devices.data[idx]) || 0;
                if (l.includes('mobile')) mob += val;
                else if (l.includes('desktop')) desk += val;
                else if (l.includes('tablet')) tab += val;
            });
        }

        const total = mob + desk + tab;
        const mobPct = total > 0 ? Math.round((mob / total) * 100) : 0;
        const deskPct = total > 0 ? Math.round((desk / total) * 100) : 0;
        const tabPct = total > 0 ? (100 - mobPct - deskPct) : 0;

        if (mobEl) mobEl.textContent = mobPct + '%';
        if (deskEl) deskEl.textContent = deskPct + '%';
        if (tabEl) tabEl.textContent = Math.max(0, tabPct) + '%';
    }

    function renderBrowsers(browsers) {
        const wrap = document.getElementById('browserChartWrap');
        if (!wrap) return;

        if (!browsers || !Array.isArray(browsers.labels) || browsers.labels.length === 0) {
            wrap.innerHTML = '<p class="text-center text-xs text-gray-400 py-4">' + __('No browser data available') + '</p>';
            return;
        }

        let total = 0;
        browsers.data.forEach(v => { total += (Number(v) || 0); });

        let html = '';
        browsers.labels.slice(0, 4).forEach((b, idx) => {
            const val = Number(browsers.data[idx]) || 0;
            const pct = total > 0 ? Math.round((val / total) * 100) : 0;
            html += '<div class="space-y-1">' +
                '<div class="flex justify-between text-xs font-semibold text-gray-700">' +
                '<span>' + b + '</span><span>' + pct + '%</span></div>' +
                '<div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">' +
                '<div class="h-full bg-indigo-500 rounded-full sp-progress-bar-fill" data-progress="' + pct + '"></div></div></div>';
        });

        wrap.innerHTML = html;
        wrap.querySelectorAll('.sp-progress-bar-fill[data-progress]').forEach(function (el) {
            el.style.width = el.getAttribute('data-progress') + '%';
            el.removeAttribute('data-progress');
        });
    }

    function renderTopPages(pages) {
        const tbody = document.getElementById('overviewTopPagesBody');
        if (!tbody) return;

        if (!Array.isArray(pages) || pages.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="p-4 text-center text-gray-400 text-xs">' + __('No page data available') + '</td></tr>';
            return;
        }

        let html = '';
        pages.slice(0, 5).forEach(p => {
            const path = p.page || '/';
            const views = formatNumber(p.pageViews || 0);
            html += '<tr class="border-b border-gray-50 hover:bg-gray-50/50"><td class="p-3 text-xs text-gray-700 font-medium truncate max-w-xs" title="' + path + '">' + path + '</td><td class="p-3 text-xs font-bold text-gray-900 text-right">' + views + '</td></tr>';
        });

        tbody.innerHTML = html;
    }

    function renderTopCountries(countries) {
        const tbody = document.getElementById('overviewTopCountriesBody');
        if (!tbody) return;

        if (!Array.isArray(countries) || countries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="p-4 text-center text-gray-400 text-xs">' + __('No country data available') + '</td></tr>';
            return;
        }

        let html = '';
        countries.slice(0, 5).forEach(c => {
            const countryName = c.country || 'Unknown';
            const count = formatNumber(c.visitors || 0);
            html += '<tr class="border-b border-gray-50 hover:bg-gray-50/50"><td class="p-3 text-xs text-gray-700 font-medium truncate max-w-xs">' + countryName + '</td><td class="p-3 text-xs font-bold text-gray-900 text-right">' + count + '</td></tr>';
        });

        tbody.innerHTML = html;
    }

    function renderTrafficSources(sources) {
        const tbody = document.getElementById('sourceMediumBody');
        if (!tbody) return;

        if (!Array.isArray(sources) || sources.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="p-4 text-center text-gray-400 text-xs">' + __('No traffic sources available') + '</td></tr>';
            return;
        }

        let html = '';
        sources.slice(0, 5).forEach(s => {
            const sourceName = s.sourceMedium || s.source || 'Direct / None';
            const count = formatNumber(s.sessions || s.users || 0);
            html += '<tr class="border-b border-gray-50 hover:bg-gray-50/50"><td class="p-3 text-xs text-gray-700 font-medium truncate max-w-xs">' + sourceName + '</td><td class="p-3 text-xs font-bold text-gray-900 text-right">' + count + '</td></tr>';
        });

        tbody.innerHTML = html;
    }

    function renderFullTopPages(pages) {
        const fullBody = document.getElementById('fullTopPagesBody');
        if (!fullBody) return;

        if (!Array.isArray(pages) || pages.length === 0) {
            fullBody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-400">' + __('No page data available') + '</td></tr>';
            return;
        }

        let html = '';
        pages.forEach(function (row) {
            const views = formatNumber(row.pageViews || 0);
            const sessions = formatNumber(row.engagedSessions || 0);
            const newUsers = formatNumber(row.newUsers || 0);
            const bounce = (parseFloat(row.bounceRate || 0) * 100).toFixed(0);
            html += '<tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">' +
                '<td class="p-5 font-semibold text-gray-700">' + (row.page || '/') + '</td>' +
                '<td class="p-5 text-center font-bold text-blue-600">' + views + '</td>' +
                '<td class="p-5 text-center text-gray-600">' + sessions + '</td>' +
                '<td class="p-5 text-center text-gray-600">' + newUsers + '</td>' +
                '<td class="p-5 text-center text-gray-400 font-semibold">' + bounce + '%</td>' +
                '</tr>';
        });
        fullBody.innerHTML = html;
    }

    function renderFullTopCountries(countries) {
        const fullBody = document.getElementById('fullTopCountriesBody');
        if (!fullBody) return;

        if (!Array.isArray(countries) || countries.length === 0) {
            fullBody.innerHTML = '<tr><td colspan="2" class="p-8 text-center text-gray-400">' + __('No country data available') + '</td></tr>';
            return;
        }

        let html = '';
        countries.forEach(function (row, index) {
            html += '<tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">' +
                '<td class="p-4 font-semibold text-gray-700">' +
                '<span class="inline-block w-5 h-5 bg-green-100 text-green-700 text-[10px] text-center leading-5 rounded-full mr-2">' + (index + 1) + '</span>' +
                (row.country || 'Unknown') +
                '</td>' +
                '<td class="p-4 text-right font-bold text-green-600">' + formatNumber(row.visitors || 0) + '</td>' +
                '</tr>';
        });
        fullBody.innerHTML = html;
    }

    function renderFullSourceMedium(sources) {
        const tbody = document.querySelector('#sourceMediumTable tbody');
        if (!tbody) return;

        if (!Array.isArray(sources) || sources.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="p-8 text-center text-gray-400">' + __('No traffic sources available') + '</td></tr>';
            return;
        }

        let html = '';
        sources.forEach(function (row) {
            html += '<tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">' +
                '<td class="p-5 font-semibold text-gray-700">' + (row.sourceMedium || row.source || 'Direct / None') + '</td>' +
                '<td class="p-5 text-right font-bold text-purple-600">' + formatNumber(row.sessions || row.users || 0) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
    }

    function setupViewSelector() {
        const viewSelector = document.getElementById('viewSelector');
        if (!viewSelector || viewSelector.dataset.bound === '1') {
            return;
        }
        viewSelector.dataset.bound = '1';
        viewSelector.addEventListener('change', function () {
            const sections = ['overviewSection', 'topPagesSection', 'topCountriesSection', 'sourceMediumSection'];
            sections.forEach(function (sectionId) {
                const sectionEl = document.getElementById(sectionId);
                if (sectionEl) {
                    sectionEl.classList.add('hidden');
                }
            });
            const selectedSection = document.getElementById(this.value);
            if (selectedSection) {
                selectedSection.classList.remove('hidden');
            }
        });
    }

    function loadFreeDashboardData(startDate, endDate) {
        showLoadingState();
        saveCalendarSession(startDate, endDate);

        // Broadcast parallel async date range change event immediately so PRO call starts at the same time
        const rangeEvent = new CustomEvent('pulse-analytics:date-range-changed', {
            detail: { startDate: startDate, endDate: endDate }
        });
        document.dispatchEvent(rangeEvent);

        const endpoint = restUrl.replace(/\/$/, '') + '/dashboard/free?startDate=' + encodeURIComponent(startDate) + '&endDate=' + encodeURIComponent(endDate);

        fetch(endpoint, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    lastDashboardData = data;
                    const kpi = data.kpi || {};
                    const connection = data.connection || {};

                    let notice = document.getElementById('pulse-analytics-connection-notice');
                    if (connection.ready === false) {
                        if (!notice) {
                            const wrap = document.getElementById('PulseAnalytics-dashboard-v2');
                            if (wrap) {
                                notice = document.createElement('div');
                                notice.id = 'pulse-analytics-connection-notice';
                                notice.className = 'mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4';
                                notice.innerHTML = '<p class="m-0 text-sm font-semibold text-amber-900"></p><p class="m-0 mt-1 text-xs text-amber-800/90"></p>';
                                wrap.insertBefore(notice, wrap.firstChild);
                            }
                        }
                        if (notice) {
                            notice.style.display = '';
                            const title = notice.querySelector('p.text-sm') || notice.querySelector('p');
                            const body = notice.querySelectorAll('p')[1];
                            if (title) title.textContent = 'Connect Google Analytics to see live metrics';
                            if (body) body.textContent = connection.message || 'Connect GA4 OAuth and set a numeric Property ID under Analytics settings.';
                        }
                    } else if (notice) {
                        notice.style.display = 'none';
                    }

                    // Update Free KPI Cards
                    const realtimeEl = document.getElementById('live-visitors-count');
                    if (realtimeEl) realtimeEl.textContent = formatNumber(data.realtime_visitors || 0);

                    const sessionsEl = document.getElementById('kpi-sessions');
                    if (sessionsEl) sessionsEl.textContent = formatNumber(kpi.sessions || 0);

                    const sessionsChangeEl = document.getElementById('kpi-sessions-delta');
                    if (sessionsChangeEl) sessionsChangeEl.textContent = kpi.sessions_change || '— vs last period';

                    const pageviewsEl = document.getElementById('kpi-pageviews');
                    if (pageviewsEl) pageviewsEl.textContent = formatNumber(kpi.pageviews || 0);

                    const pageviewsRatioEl = document.getElementById('kpi-pageviews-ratio');
                    if (pageviewsRatioEl) pageviewsRatioEl.textContent = kpi.pageviews_change || '— pages/session';

                    const durationEl = document.getElementById('kpi-duration');
                    if (durationEl) durationEl.textContent = formatDuration(kpi.avg_session_duration || 0);

                    const durationDeltaEl = document.getElementById('kpi-duration-delta');
                    if (durationDeltaEl) durationDeltaEl.textContent = kpi.duration_change || '— vs last period';

                    const engagementEl = document.getElementById('kpi-engagement');
                    if (engagementEl) engagementEl.textContent = (Number(kpi.engagement_rate) * 100 || 0).toFixed(1) + '%';

                    const engagementDeltaEl = document.getElementById('kpi-engagement-delta');
                    if (engagementDeltaEl) engagementDeltaEl.textContent = kpi.engagement_change || '— vs last period';

                    // Remove Summary Overlay
                    const chartWrap = document.getElementById('trendChartWrap');
                    if (chartWrap) {
                        const overlay = chartWrap.querySelector('.summary-loading-overlay');
                        if (overlay) overlay.style.display = 'none';
                    }

                    // Summary Counters under Segmented Bar
                    const sumSessions = document.getElementById('summary-sessions');
                    if (sumSessions) sumSessions.textContent = formatNumber(kpi.sessions || 0);

                    const sumEngaged = document.getElementById('summary-engaged');
                    if (sumEngaged) sumEngaged.textContent = formatNumber(Math.round((kpi.sessions || 0) * (kpi.engagement_rate || 0)));

                    const sumKeyEvent = document.getElementById('summary-keyevent');
                    if (sumKeyEvent) sumKeyEvent.textContent = (Number(kpi.engagement_rate) * 100 || 0).toFixed(1) + '%';

                    // Render Trend Line Chart
                    updateChartForTab(activeTab);

                    // Render Breakdown Widgets
                    renderNewVsReturning(data.visitor_types);
                    renderDeviceTypes(data.devices);
                    renderBrowsers(data.browsers);
                    renderTopPages(data.top_pages);
                    renderTopCountries(data.top_countries);
                    renderTrafficSources(data.sources);
                    renderFullTopPages(data.top_pages);
                    renderFullTopCountries(data.top_countries);
                    renderFullSourceMedium(data.sources);

                    // Dispatch Custom Event
                    window.$smPulseAnalyticsChartDates = { startDate: startDate, endDate: endDate };
                    const customEvent = new CustomEvent('pulse-analytics:dashboard-loaded', {
                        detail: { startDate: startDate, endDate: endDate, gaData: data }
                    });
                    document.dispatchEvent(customEvent);
                }
            })
            .catch(err => {
                console.error('Pulse Analytics Free Dashboard Fetch Error:', err);
            });
    }

    // Segmented Tabs Event Binding
    const segmentedTabs = document.querySelectorAll('#trendSegmentedTabs button');
    segmentedTabs.forEach(btn => {
        btn.addEventListener('click', function () {
            segmentedTabs.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-trend-tab') || 'traffic';
            updateChartForTab(tabName);
        });
    });

    // eCommerce Subtab Metric Binding
    const ecomSubtabs = document.querySelectorAll('#ecomMetricSubtabs button');
    ecomSubtabs.forEach(btn => {
        btn.addEventListener('click', function () {
            ecomSubtabs.forEach(b => {
                b.classList.remove('active', 'bg-white', 'text-indigo-600', 'shadow-sm');
                b.classList.add('text-gray-600');
            });
            this.classList.add('active', 'bg-white', 'text-indigo-600', 'shadow-sm');
            this.classList.remove('text-gray-600');
            window.activeEcomMetric = this.getAttribute('data-ecom-metric') || 'all';
            updateChartForTab('ecommerce');
        });
    });

    function formatDateLocal(d) {
        if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function formatDateRangeDisplay(start, end) {
        if (!start) return '';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const sM = months[start.getMonth()];
        const sD = start.getDate();
        const sY = start.getFullYear();
        if (!end) {
            return `${sM} ${sD}, ${sY}`;
        }
        const eM = months[end.getMonth()];
        const eD = end.getDate();
        const eY = end.getFullYear();
        if (sY === eY) {
            if (sM === eM && sD === eD) {
                return `${sM} ${sD}, ${sY}`;
            }
            return `${sM} ${sD} – ${eM} ${eD}, ${eY}`;
        }
        return `${sM} ${sD}, ${sY} – ${eM} ${eD}, ${eY}`;
    }

    // Initialize Flatpickr Date Picker on #dateRangeInput if present or fallback wrapper
    // Unified Date Picker handles dates now.

    // Make the entire box clickable (including icons)
    const datePickerWrap = document.getElementById('dateRangePickerWrap');
    if (datePickerWrap && !datePickerWrap.dataset.clickBound) {
        datePickerWrap.dataset.clickBound = "true";
        datePickerWrap.addEventListener('click', function (e) {
            const inputEl = document.getElementById('dateRangeInput');
            const fp = inputEl ? inputEl._flatpickr || window.SmPulseAnalyticsDatePicker : null;
            if (fp && typeof fp.open === 'function') {
                fp.open();
            }
        });
    }

    // Expose for external triggers (e.g., site-notes date picker)
    window.loadFreeDashboardData = loadFreeDashboardData;

    /* ================= DASHBOARD LAYOUT, SYNC & SORTABLE ================= */
    const DEFAULT_LAYOUT = [
        'live_visitors', 'sessions', 'page_views', 'revenue',
        'conversion_rate', 'avg_order_value', 'session_duration', 'engagement_rate',
        'top_products', 'funnel', 'traffic_chart', 'visitor_types',
        'devices', 'browsers', 'top_pages', 'top_countries'
    ];

    function showDashboardNotification(message, type) {
        const existing = document.getElementById('sm-pulse-analytics-notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.id = 'sm-pulse-analytics-notification';
        let bgColor = 'bg-blue-500';
        if (type === 'success') bgColor = 'bg-green-500';
        if (type === 'error') bgColor = 'bg-red-500';

        notification.className = 'fixed bottom-6 right-6 p-4 rounded-xl shadow-2xl text-white flex items-center gap-3 ' + bgColor;
        notification.style.zIndex = '999999';
        notification.innerHTML = '<span class="text-sm font-semibold">' + message + '</span>';
        document.body.appendChild(notification);

        if (type !== 'info') {
            setTimeout(function () {
                notification.remove();
            }, 3000);
        }
    }

    function applyDashboardLayout(layout) {
        const kpiContainer = document.getElementById('kpi-cards-container');
        const mainContainer = document.getElementById('overviewSection');
        const allWidgets = document.querySelectorAll('[data-widget-id]');
        const widgetMap = new Map(Array.from(allWidgets).map(function (w) {
            return [w.getAttribute('data-widget-id'), w];
        }));

        layout.forEach(function (widgetId) {
            const widget = widgetMap.get(widgetId);
            if (!widget) return;
            const currentTarget = widget.closest('#kpi-cards-container') ? kpiContainer : mainContainer;
            if (currentTarget) {
                currentTarget.appendChild(widget);
            }
        });
    }

    function loadDashboardLayout() {
        const settingsUrl = restUrl.replace(/\/$/, '') + '/settings';
        fetch(settingsUrl, {
            credentials: 'same-origin',
            headers: nonce ? { 'X-WP-Nonce': nonce } : {}
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.dashboard_layout && Array.isArray(data.dashboard_layout) && data.dashboard_layout.length) {
                    applyDashboardLayout(data.dashboard_layout);
                }
            })
            .catch(function (err) {
                console.error('Failed to load dashboard layout:', err);
            });
    }

    window.saveDashboardLayout = function (isReset) {
        isReset = !!isReset;
        const kpiContainer = document.getElementById('kpi-cards-container');
        const mainContainer = document.getElementById('overviewSection');

        let layout = [];
        if (kpiContainer) {
            layout = layout.concat(Array.from(kpiContainer.querySelectorAll('[data-widget-id]')).map(function (w) {
                return w.getAttribute('data-widget-id');
            }));
        }
        if (mainContainer) {
            layout = layout.concat(Array.from(mainContainer.querySelectorAll('[data-widget-id]')).map(function (w) {
                return w.getAttribute('data-widget-id');
            }));
        }

        if (!isReset && layout.length === 0) return;

        const settingsUrl = restUrl.replace(/\/$/, '') + '/settings';
        fetch(settingsUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify({ dashboard_layout: isReset ? [] : layout })
        })
            .then(function (res) { return res.json(); })
            .then(function () {
                if (isReset) {
                    showDashboardNotification('Dashboard reset to default', 'success');
                    applyDashboardLayout(DEFAULT_LAYOUT);
                } else {
                    showDashboardNotification('Dashboard layout updated', 'success');
                }
            })
            .catch(function (err) {
                console.error('Failed to save dashboard layout:', err);
                showDashboardNotification('Failed to save layout', 'error');
            });
    };

    function showConfirmationModal(message, onConfirm) {
        const existing = document.getElementById('sm-pulse-analytics-confirm-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'sm-pulse-analytics-confirm-modal';
        modal.className = 'fixed inset-0 flex items-center justify-center p-4 bg-gray-900 bg-opacity-60';
        modal.style.zIndex = '999999';
        modal.innerHTML =
            '<div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">' +
            '<h3 class="text-lg font-bold text-gray-900 m-0 mb-2">Are you sure?</h3>' +
            '<p class="text-sm text-gray-500 mb-6">' + message + '</p>' +
            '<div class="flex gap-3">' +
            '<button type="button" id="confirm-no" class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-bold">No, Cancel</button>' +
            '<button type="button" id="confirm-yes" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold">Yes, Continue</button>' +
            '</div></div>';

        document.body.appendChild(modal);

        function close() { modal.remove(); }
        modal.querySelector('#confirm-no').addEventListener('click', close);
        modal.querySelector('#confirm-yes').addEventListener('click', function () {
            close();
            if (typeof onConfirm === 'function') onConfirm();
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) close();
        });
    }

    window.resetDashboardLayout = function () {
        showConfirmationModal(
            'This will revert your dashboard widgets to their original order. Customizations will be lost.',
            function () { window.saveDashboardLayout(true); }
        );
    };

    const resetBtn = document.getElementById('resetLayoutBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', window.resetDashboardLayout);
    }

    setupViewSelector();

    if (typeof Sortable !== 'undefined') {
        const overviewEl = document.getElementById('overviewSection');
        if (overviewEl) {
            Sortable.create(overviewEl, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'bg-blue-50',
                onEnd: function () {
                    if (typeof window.saveDashboardLayout === 'function') window.saveDashboardLayout();
                }
            });
        }
        const kpiEl = document.getElementById('kpi-cards-container');
        if (kpiEl) {
            Sortable.create(kpiEl, {
                animation: 150,
                handle: '.cursor-move-kpi',
                ghostClass: 'bg-blue-100',
                onEnd: function () {
                    if (typeof window.saveDashboardLayout === 'function') window.saveDashboardLayout();
                }
            });
        }
    }

    loadDashboardLayout();

    document.addEventListener('pulse-analytics:date-range-changed', function (evt) {
        if (evt && evt.detail && evt.detail.startDate && evt.detail.endDate) {
            if (evt.detail.startDate !== currentStartDate || evt.detail.endDate !== currentEndDate) {
                currentStartDate = evt.detail.startDate;
                currentEndDate = evt.detail.endDate;
                loadFreeDashboardData(currentStartDate, currentEndDate);
            }
        }
    });

    // Initial Load
    loadFreeDashboardData(currentStartDate, currentEndDate);
});

  /* ================= DATE PICKER (Unified Header) ================= */
  const selectEl = document.getElementById('traffic-date-range-select');
  const customPickerInput = document.getElementById('traffic-custom-date-picker');

  if (selectEl) {
    function dispatchDateChange(start, end) {
      currentStartDate = start;
      currentEndDate = end;

      try {
        sessionStorage.setItem('sm_pulse_analytics_calendar', JSON.stringify({ start: start, end: end }));
      } catch (e) {}

      const customEvent = new CustomEvent('pulse-analytics:date-range-changed', {
        detail: { startDate: start, endDate: end }
      });
      document.dispatchEvent(customEvent);

      if (typeof jQuery !== 'undefined') {
        jQuery(document).trigger('pulse-analytics:date-range-changed', [start, end]);
      }
    }

    selectEl.addEventListener('change', function () {
      var val = this.value;
      if (val === 'custom') {
        if (customPickerInput) {
          customPickerInput.classList.remove('hidden');
          if (typeof flatpickr !== 'undefined' && !customPickerInput._flatpickr) {
            flatpickr(customPickerInput, {
              mode: 'range',
              dateFormat: 'Y-m-d',
              maxDate: 'today',
              onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                  dispatchDateChange(
                    flatpickr.formatDate(selectedDates[0], 'Y-m-d'),
                    flatpickr.formatDate(selectedDates[1], 'Y-m-d')
                  );
                }
              }
            });
          }
          if (customPickerInput._flatpickr) {
            customPickerInput._flatpickr.open();
          }
        }
      } else {
        if (customPickerInput) {
          customPickerInput.classList.add('hidden');
        }
        var days = 30;
        if (val === '7daysAgo') days = 7;
        else if (val === '90daysAgo') days = 90;
        
        var endD = new Date();
        var startD = new Date();
        startD.setDate(endD.getDate() - days);
        
        var startStr = startD.getFullYear() + '-' + String(startD.getMonth() + 1).padStart(2, '0') + '-' + String(startD.getDate()).padStart(2, '0');
        var endStr = endD.getFullYear() + '-' + String(endD.getMonth() + 1).padStart(2, '0') + '-' + String(endD.getDate()).padStart(2, '0');
        
        dispatchDateChange(startStr, endStr);
      }
    });
  }
