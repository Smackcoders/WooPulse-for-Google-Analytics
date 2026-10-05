/**
 * Pulse Analytics for WordPress — Free plugin asset.
 *
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 */
// Global Pulse Analytics Namespace for shared utilities
window.SmPulseAnalytics = {
  showLoader: function (text = 'Loading Data...') {
    let loader = document.getElementById('sm-pulse-analytics-global-loader');
    if (!loader) {
      loader = document.createElement('div');
      loader.id = 'sm-pulse-analytics-global-loader';
      loader.className = 'PulseAnalytics-loader-overlay';
      loader.innerHTML = `
        <p class="PulseAnalytics-loader-header">Loading...</p>
        <div class="PulseAnalytics-spinner"></div>
        <p class="PulseAnalytics-loader-text">${text}</p>
      `;
      document.body.appendChild(loader);
    } else {
      const textEl = loader.querySelector('.PulseAnalytics-loader-text');
      if (textEl) textEl.textContent = text;
    }
    document.body.classList.add('sm-pulse-analytics-loading-active');
    loader.classList.add('is-active');

    // Safety timeout: automatically hide after 15 seconds if still active
    // This prevents the user from being stuck if a request hangs or fails silently
    if (this._safetyTimeout) clearTimeout(this._safetyTimeout);
    this._safetyTimeout = setTimeout(() => {
      this.hideLoader();
    }, 15000);
  },
  hideLoader: function () {
    if (this._safetyTimeout) clearTimeout(this._safetyTimeout);
    const loader = document.getElementById('sm-pulse-analytics-global-loader');
    if (loader) {
      // Add a small delay to ensure data is actually visible before hiding
      setTimeout(() => {
        loader.classList.remove('is-active');
        document.body.classList.remove('sm-pulse-analytics-loading-active');
      }, 500);
    }
    window._isFetchingData = false;
  },
  redirectWpNotices: function () {
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
};


document.addEventListener('DOMContentLoaded', function () {
  const pulseAjax = (typeof pulseAnalyticsAjax !== 'undefined') ? pulseAnalyticsAjax : {};


  // Redirect WP Admin notices to top of plugin pages
  if (window.SmPulseAnalytics && typeof window.SmPulseAnalytics.redirectWpNotices === 'function') {
    window.SmPulseAnalytics.redirectWpNotices();
    setTimeout(function () { window.SmPulseAnalytics.redirectWpNotices(); }, 200);
    setTimeout(function () { window.SmPulseAnalytics.redirectWpNotices(); }, 800);

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
          window.SmPulseAnalytics.redirectWpNotices();
        }
      });
      noticeObserver.observe(wpbody, { childList: true, subtree: true });
    }
  }

  window.SmPulseAnalyticsProActive = !!(Object.keys(pulseAjax).length && pulseAjax.is_pro_active);

  // Visibility Toggle for Password Fields
  const togglePasswords = document.querySelectorAll('.toggle-password');
  togglePasswords.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const targetInput = document.getElementById(targetId);
      if (!targetInput) return;

      if (targetInput.type === 'password') {
        targetInput.type = 'text';
        this.classList.remove('dashicons-hidden');
        this.classList.add('dashicons-visibility');
      } else {
        targetInput.type = 'password';
        this.classList.remove('dashicons-visibility');
        this.classList.add('dashicons-hidden');
      }
    });
  });

  function toLocalDateString(date) {
    const d = new Date(date);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split('T')[0];
  }

  function formatDateRangeDisplay(instance, start, end) {
    if (!start) return '';
    if (!end) {
      return instance ? instance.formatDate(start, 'M j, Y') : start.toDateString();
    }
    if (start.getFullYear() === end.getFullYear()) {
      return (instance ? instance.formatDate(start, 'M j') : '') + ' – ' + (instance ? instance.formatDate(end, 'M j, Y') : '');
    }
    return (instance ? instance.formatDate(start, 'M j, Y') : '') + ' – ' + (instance ? instance.formatDate(end, 'M j, Y') : '');
  }

  function getPreviousPeriodDates(startStr, endStr) {
    const start = new Date(startStr);
    const end = new Date(endStr);
    const currentDays = Math.round((end - start) / (24 * 60 * 60 * 1000)) + 1;
    const prevEnd = new Date(start.getTime() - 24 * 60 * 60 * 1000);
    const prevStart = new Date(prevEnd.getTime() - (currentDays - 1) * 24 * 60 * 60 * 1000);
    return {
      prevStartDate: toLocalDateString(prevStart),
      prevEndDate: toLocalDateString(prevEnd)
    };
  }

  function updateSessionsDelta(current, previous) {
    const el = document.getElementById('kpi-sessions-delta');
    if (!el) return;
    if (!previous || previous === 0) {
      el.textContent = '— vs last period';
      el.className = 'text-gray-400 font-medium';
      return;
    }
    const changePercent = ((current - previous) / previous) * 100;
    const isPositive = changePercent >= 0;
    const sign = isPositive ? '+' : '';
    el.textContent = `${sign}${changePercent.toFixed(1)}% vs last period`;
    el.className = (isPositive ? 'text-green-600' : 'text-red-600') + ' font-medium';
  }

  function updatePagesPerSession(pageviews, sessions) {
    const el = document.getElementById('kpi-pageviews-ratio');
    if (!el) return;
    if (!sessions || sessions === 0) {
      el.textContent = '— pages/session';
      return;
    }
    const ratio = parseFloat(pageviews) / parseFloat(sessions);
    el.textContent = ratio.toFixed(2) + ' pages/session';
  }

  function formatRelativeSyncTime(timestamp) {
    if (!timestamp) return '';
    const secs = Math.floor(Date.now() / 1000) - timestamp;
    if (secs < 60) return 'just now';
    if (secs < 3600) return Math.floor(secs / 60) + 'm ago';
    if (secs < 86400) return Math.floor(secs / 3600) + 'h ago';
    return Math.floor(secs / 86400) + 'd ago';
  }

  function updateSyncButtonLabel() {
    const label = document.getElementById('manualSyncBtnLabel');
    if (!label) return;
    const ts = (Object.keys(pulseAjax).length && pulseAjax.last_sync) ? pulseAjax.last_sync : 0;
    label.textContent = ts ? 'Sync Data · ' + formatRelativeSyncTime(ts) : 'Sync Data';
  }

  let startDate = new Date();
  startDate.setDate(startDate.getDate() - 30); // Default to last 30 days
  startDate = toLocalDateString(startDate);
  let endDate = toLocalDateString(new Date());

  /* ================= DATE PICKER (Unified Header) ================= */
  /* ================= DATE PICKER (Unified Header) ================= */
  const selectEl = document.getElementById('traffic-date-range-select');
  const customPickerInput = document.getElementById('traffic-custom-date-picker');

  if (selectEl) {
    function dispatchDateChange(start, end) {
      startDate = start;
      endDate = end;

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

      if (document.getElementById('PulseAnalytics-dashboard-v2') && typeof reloadAllData === 'function') {
        reloadAllData();
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

  // Only run dashboard-specific logic if we are on the dashboard
  const dashboardV2 = document.getElementById('PulseAnalytics-dashboard-v2');
  if (!dashboardV2) {
    // If not on dashboard, we might still have some global initializations but exit here for widget logic
    return;
  }

  // Chart instances (to prevent overlap)
  let lineChartInstance;
  let visitorsChartInstance;
  let deviceChartInstance;
  let browserChartInstance;

  let totalSessions = 0; // State variable to sync sessions across widgets
  let currentSessionsReported = 0;
  let currentOrdersReported = 0;
  let currentWCOrders = 0;
  let currentFunnelData = { views: 0, cart: 0, checkout: 0, purchase: 0 };

  const restBase = (typeof pulseAnalyticsAjax !== 'undefined' && pulseAnalyticsAjax.rest_url)
    ? pulseAnalyticsAjax.rest_url
    : (Object.keys(pulseAjax).length ? pulseAjax.rest_url : '/wp-json/pulse-analytics/v1');
  const currencySymbol = Object.keys(pulseAjax).length && pulseAjax.currency_symbol ? pulseAjax.currency_symbol : '₹';
  console.log('Pulse Analytics: restBase is', restBase);

  // Helper to build REST API URLs robustly
  const buildRestUrl = (endpoint, params = {}) => {
    const base = restBase.endsWith('/') ? restBase.slice(0, -1) : restBase;
    const separator = base.includes('?') ? '&' : '?';
    const queryString = new URLSearchParams(params).toString();
    const finalEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
    return `${base}${finalEndpoint}${queryString ? separator + queryString : ''}`;
  };

  /**
   * Helper for robust JSON fetching
   * Detects cases where server returns HTML (e.g. 403 Forbidden) instead of JSON
   */
  const fetchJson = (url, options = {}) => {
    return fetch(url, options)
      .then(res => {
        const contentType = res.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
          return res.json();
        } else {
          return res.text().then(text => {
            if (text.trim().startsWith('<')) {
              console.error(`Pulse Analytics: REST API at ${url} returned HTML instead of JSON. Status: ${res.status}`);
            }
            throw new Error(`Invalid Response: Expected JSON, got ${contentType || 'plain text'}`);
          });
        }
      });
  };

  const DEFAULT_LAYOUT = [
    'live_visitors', 'sessions', 'page_views', 'revenue',
    'conversion_rate', 'avg_order_value', 'session_duration', 'engagement_rate',
    'top_products', 'funnel', 'traffic_chart', 'visitor_types',
    'devices', 'browsers', 'top_pages', 'top_countries'
  ];

  /* ================= DASHBOARD LAYOUT & DRAG-AND-DROP ================= */

  // Load and apply saved dashboard layout
  function loadDashboardLayout() {
    const settingsUrl = restBase.replace('sm-pulse-analytics/v1', 'wp_asa/v1') + '/settings';
    fetch(settingsUrl)
      .then(res => res.json())
      .then(data => {
        if (data.dashboard_layout && Array.isArray(data.dashboard_layout)) {
          applyDashboardLayout(data.dashboard_layout);
        }
      })
      .catch(err => {
        console.error('Failed to load dashboard layout:', err);
      });
  }

  // Apply saved layout order to all widgets across containers
  function applyDashboardLayout(layout) {
    const kpiContainer = document.getElementById('kpi-cards-container');
    const mainContainer = document.getElementById('overviewSection');

    const allWidgets = document.querySelectorAll('[data-widget-id]');
    const widgetMap = new Map(Array.from(allWidgets).map(w => [w.getAttribute('data-widget-id'), w]));

    layout.forEach(widgetId => {
      const widget = widgetMap.get(widgetId);
      if (widget) {
        // Find which container it belongs to based on its current parent or classes
        // Actually, just append to the container that currently holds it to maintain separation
        const currentTarget = widget.closest('#kpi-cards-container') ? kpiContainer : mainContainer;
        if (currentTarget) {
          currentTarget.appendChild(widget);
        }
      }
    });
  }

  // Save dashboard layout (exposed for SortableJS)
  window.saveDashboardLayout = function (isReset = false) {
    const kpiContainer = document.getElementById('kpi-cards-container');
    const mainContainer = document.getElementById('overviewSection');

    let layout = [];
    if (kpiContainer) {
      layout = layout.concat(Array.from(kpiContainer.querySelectorAll('[data-widget-id]')).map(w => w.getAttribute('data-widget-id')));
    }
    if (mainContainer) {
      layout = layout.concat(Array.from(mainContainer.querySelectorAll('[data-widget-id]')).map(w => w.getAttribute('data-widget-id')));
    }

    if (!isReset && layout.length === 0) return;

    const settingsUrl = restBase.replace('sm-pulse-analytics/v1', 'wp_asa/v1') + '/settings';

    fetch(settingsUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Object.keys(pulseAjax).length ? pulseAjax.nonce : ''
      },
      body: JSON.stringify({ dashboard_layout: isReset ? [] : layout })
    })
      .then(res => res.json())
      .then(data => {
        console.log('Dashboard layout saved:', data);
        if (isReset) {
          showNotification('Dashboard reset to default', 'success');
          applyDashboardLayout(DEFAULT_LAYOUT);
        } else {
          showNotification('Dashboard layout updated', 'success');
        }
      })
      .catch(err => {
        console.error('Failed to save dashboard layout:', err);
        showNotification('Failed to save layout', 'error');
      });
  };

  // Custom Confirmation Modal (Premium Yes/No)
  function showConfirmationModal(message, onConfirm) {
    // Remove existing if any
    const existing = document.getElementById('PulseAnalytics-confirm-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'sm-pulse-analytics-confirm-modal';
    modal.className = 'fixed inset-0 flex items-center justify-center p-4 bg-gray-900 bg-opacity-60 backdrop-blur-sm animate-fade-in';
    modal.style.zIndex = '999999';

    modal.innerHTML = `
      <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 transform transition-all animate-slide-in-up">
        <div class="flex items-center gap-4 mb-3">
          <div class="bg-blue-50 w-12 h-12 rounded-full flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900 m-0 sp-mb-0">Are you sure?</h3>
        </div>
        <p class="text-sm text-gray-500 mb-6">${message}</p>
        <div class="flex gap-3">
          <button id="confirm-no" class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-bold transition-colors">No, Cancel</button>
          <button id="confirm-yes" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all">Yes, Reset</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    const close = () => {
      modal.classList.add('animate-fade-out');
      setTimeout(() => modal.remove(), 300);
    };

    document.getElementById('confirm-no').addEventListener('click', close);
    document.getElementById('confirm-yes').addEventListener('click', () => {
      close();
      onConfirm();
    });

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) close();
    });
  }

  // Reset Dashboard Layout
  window.resetDashboardLayout = function () {
    showConfirmationModal(
      'This will revert your dashboard widgets to their original order. Customizations will be lost.',
      () => window.saveDashboardLayout(true)
    );
  };

  // Attach Reset Button event
  const resetBtn = document.getElementById('resetLayoutBtn');
  if (resetBtn) {
    resetBtn.addEventListener('click', window.resetDashboardLayout);
  }

  // Attach Manual Sync Button event
  const syncBtn = document.getElementById('manualSyncBtn');
  if (syncBtn) {
    updateSyncButtonLabel();
    syncBtn.addEventListener('click', async function () {
      console.log('Pulse Analytics: Sync button clicked');

      syncBtn.disabled = true;
      const labelEl = document.getElementById('manualSyncBtnLabel');
      if (labelEl) {
        labelEl.textContent = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'Syncing...', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'Syncing...');
      }

      showNotification('Synchronizing data with Google Analytics...', 'info');

      try {
        const url = Object.keys(pulseAjax).length ? pulseAjax.ajax_url : '/wp-admin/admin-ajax.php';
        const formData = new FormData();
        formData.append('action', 'sm_pulse_analytics_manual_sync');
        formData.append('nonce', Object.keys(pulseAjax).length ? pulseAjax.nonce : '');

        const response = await fetch(url, {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          throw new Error(`Server returned status ${response.status}`);
        }

        const data = await response.json();
        console.log('Pulse Analytics: Sync result:', data);

        if (data.success) {
          if (Object.keys(pulseAjax).length && data.data && data.data.last_sync) {
            pulseAjax.last_sync = data.data.last_sync;
          }
          showNotification('Data synchronized successfully!', 'success');
          await reloadAllData(); // Refresh the counts and charts
        } else {
          showNotification('Sync failed: ' + (data.data.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        console.error('Pulse Analytics: Sync exception:', err);
        showNotification('Sync failed. InfinityFree might be blocking the request. Please try again.', 'error');
      } finally {
        syncBtn.disabled = false;
        updateSyncButtonLabel();
      }
    });
  }

  // Show notification (improved)
  function showNotification(message, type) {
    // Remove existing notification if any
    const existing = document.getElementById('PulseAnalytics-notification');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.id = 'sm-pulse-analytics-notification';
    let bgColor = 'bg-blue-600';
    if (type === 'success') bgColor = 'bg-green-500';
    if (type === 'error') bgColor = 'bg-red-500';
    if (type === 'info') bgColor = 'bg-blue-500';

    notification.className = `fixed bottom-6 right-6 p-4 rounded-xl shadow-2xl text-white flex items-center gap-3 animate-slide-in-up ${bgColor}`;
    notification.style.zIndex = '999999';

    let iconSvg = '';
    if (type === 'success') {
      iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    } else if (type === 'error') {
      iconSvg = '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    } else {
      iconSvg = '<svg class="w-5 h-5 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    }

    notification.innerHTML = `
      ${iconSvg}
      <span class="text-sm font-semibold">${message}</span>
    `;

    document.body.appendChild(notification);

    if (type !== 'info') {
      setTimeout(() => {
        notification.classList.add('animate-fade-out');
        setTimeout(() => notification.remove(), 500);
      }, 3000);
    }
  }

  // Initialize layout on page load
  loadDashboardLayout();

  // Unified date picker is initialized in the header section above.

  /* ================= STORE TRAFFIC TRENDS TABS & FILTERS ================= */
  let currentActiveTab = 'traffic';

  document.addEventListener('click', function (e) {
    // 1. Tab switching
    const tabBtn = e.target.closest('[data-trend-tab]');
    if (tabBtn) {
      const tab = tabBtn.getAttribute('data-trend-tab');
      currentActiveTab = tab;

      document.querySelectorAll('[data-trend-tab]').forEach(b => b.classList.remove('active'));
      tabBtn.classList.add('active');

      if (typeof window.updateChartForTab === 'function') {
        window.updateChartForTab(tab);
      } else if (typeof updateChartForTab === 'function') {
        updateChartForTab(tab);
      }
      return;
    }

    // 4. Dashboard PRO Modal trigger
    const proTag = e.target.closest('.open-dashboard-pro-modal, .pulse-analytics-pro-badge-pill, .pulse-analytics-pro-mini-tag, .sp-pro-gated .sp-pro-blur-target');
    if (proTag) {
      const dash = document.getElementById('PulseAnalytics-dashboard-v2');
      if (dash && dash.contains(proTag)) {
        e.preventDefault();
        e.stopPropagation();
        const modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
        if (modal) {
          modal.classList.remove('hidden');
          modal.classList.add('flex');
          setTimeout(() => {
            const dialog = modal.querySelector('.animate-pro-modal-in');
            if (dialog) {
              dialog.classList.remove('scale-95', 'opacity-0');
              dialog.classList.add('scale-100', 'opacity-100');
            }
          }, 10);
        }
        return;
      }
    }

    // 5. Dashboard PRO Modal Close
    if (e.target.closest('.pulse-analytics-dashboard-modal-close')) {
      e.preventDefault();
      const modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
      if (modal) {
        const dialog = modal.querySelector('.animate-pro-modal-in');
        if (dialog) {
          dialog.classList.remove('scale-100', 'opacity-100');
          dialog.classList.add('scale-95', 'opacity-0');
        }
        setTimeout(() => {
          modal.classList.remove('flex');
          modal.classList.add('hidden');
        }, 200);
      }
      return;
    }

    // 6. Click on backdrop to close
    const proModal = document.getElementById('pulse-analytics-dashboard-pro-modal');
    if (proModal && e.target === proModal) {
      e.preventDefault();
      const dialog = proModal.querySelector('.animate-pro-modal-in');
      if (dialog) {
        dialog.classList.remove('scale-100', 'opacity-100');
        dialog.classList.add('scale-95', 'opacity-0');
      }
      setTimeout(() => {
        proModal.classList.remove('flex');
        proModal.classList.add('hidden');
      }, 200);
      return;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
      if (modal && !modal.classList.contains('hidden')) {
        const dialog = modal.querySelector('.animate-pro-modal-in');
        if (dialog) {
          dialog.classList.remove('scale-100', 'opacity-100');
          dialog.classList.add('scale-95', 'opacity-0');
        }
        setTimeout(() => {
          modal.classList.remove('flex');
          modal.classList.add('hidden');
        }, 200);
      }
    }
  });

  window.resetTrendFilters = function () {
    const popover = document.getElementById('metricsPopover');
    if (!popover) return;
    const activePanel = popover.querySelector('[data-popover-panel="' + currentActiveTab + '"]');
    if (!activePanel) return;

    const defaults = {
      traffic: ['sessions', 'engagedSessions', 'keyEventRate'],
      engagement: ['sessions', 'engagedSessions', 'engagementRate'],
      referrals: ['sessions', 'engagedSessions', 'keyEventRate']
    };
    const defaultList = defaults[currentActiveTab] || ['sessions'];
    activePanel.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.checked = defaultList.includes(cb.value);
    });
  };

  window.applyTrendFilters = function () {
    const popover = document.getElementById('metricsPopover');
    if (popover) popover.classList.add('hidden');
    if (typeof loadLineChart === 'function') {
      loadLineChart();
    }
  };

  /* ================= LINE CHART ================= */
  function loadLineChart() {
    const url = buildRestUrl('daily-metrics', { startDate, endDate });

    return fetchJson(url)
      .then(response => {
        if (!response || response.error || response.code) {
          console.error('Line Chart Error:', response ? response.error || response.code : 'Empty response');
          return;
        }
        const { labels, data } = response;
        const el = document.getElementById('lineChart');
        if (!el || !data) return;

        if (lineChartInstance) lineChartInstance.destroy();

        // Build gradient fill
        const ctx = el.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
        gradient.addColorStop(0.6, 'rgba(59, 130, 246, 0.08)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.00)');

        lineChartInstance = new Chart(el, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Sessions',
              data,
              borderColor: '#6366f1',
              backgroundColor: gradient,
              fill: true,
              tension: 0.45,
              pointRadius: 5,
              pointHoverRadius: 7,
              pointBackgroundColor: '#fff',
              pointBorderColor: '#6366f1',
              pointBorderWidth: 2,
              borderWidth: 2.5
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                ticks: { color: '#9ca3af', font: { size: 10.5, weight: '600' }, maxTicksLimit: 5 }
              },
              x: {
                grid: { display: false },
                ticks: { color: '#9ca3af', font: { size: 10.5, weight: '600' } }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#0f172a',
                bodyColor: '#334155',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 10,
                boxPadding: 4,
                usePointStyle: true,
                padding: 10,
                cornerRadius: 8,
                displayColors: false
              }
            }
          }
        });

        lineChartInstance.$smPulseAnalyticsChartDates = labels;
        window.lineChartInstance = lineChartInstance;
        if (typeof jQuery !== 'undefined') {
          jQuery(document).trigger('sm-pulse-analytics:traffic-overview-loaded');
        }

        totalSessions = (Array.isArray(data) && data.length > 0) ? data.reduce((a, b) => a + b, 0) : 0;
        updateUI(); // Renamed from refreshVisualStates to be clearer
      })
      .catch(err => console.error('Error loading daily metrics:', err));
  }

  function updateUI() {
    // 1. Update Sessions KPI
    const finalSessions = Math.max(totalSessions, currentSessionsReported);
    const sessionsEl = document.getElementById('kpi-sessions');
    if (sessionsEl) sessionsEl.textContent = finalSessions.toLocaleString();

    // 2. Update Conversion Rate
    const convEl = document.getElementById('kpi-conversion');
    if (convEl) {
      const conversionRate = finalSessions > 0 ? (currentOrdersReported / finalSessions) * 100 : 0;
      convEl.textContent = conversionRate.toFixed(2) + '%';
    }

    // 3. Update Funnel Bars
    const funnelBaseline = Math.max(finalSessions, 10);
    const fields = ['views', 'cart', 'checkout', 'purchase'];

    fields.forEach(f => {
      const bar = document.getElementById(`funnel-bar-${f}`);
      const val = (currentFunnelData && typeof currentFunnelData[f] !== 'undefined') ? currentFunnelData[f] : 0;

      if (bar) {
        const pct = (val / funnelBaseline) * 100;
        bar.style.width = Math.min(100, Math.max(0.5, pct)) + '%';
        bar.textContent = val.toLocaleString();
      }
    });

    const funnelSummary = document.getElementById('funnel-conversion-summary');
    if (funnelSummary && funnelBaseline > 0) {
      const purchases = (currentFunnelData && typeof currentFunnelData.purchase !== 'undefined') ? currentFunnelData.purchase : 0;
      const overallRate = (purchases / funnelBaseline) * 100;
      funnelSummary.textContent = overallRate.toFixed(1) + '% overall conversion rate';
    }
  }

  /* ================= GLOBAL PAGE LOADER (Bridge) ================= */
  function showPageLoader() {
    window.SmPulseAnalytics.showLoader();
  }

  function hidePageLoader() {
    window.SmPulseAnalytics.hideLoader();
  }

  /* =============== DONUT CHARTS =============== */

  // Plugin 1: Draw total count in the EXACT center of donut
  const centerTextPlugin = {
    id: 'centerText',
    beforeDraw: function (chart) {
      if (chart.config.type !== 'doughnut') return;

      const { chartArea, ctx } = chart;
      if (!chartArea) return;

      // Use chartArea for true center of the donut (excludes legend area)
      const centerX = (chartArea.left + chartArea.right) / 2;
      const centerY = (chartArea.top + chartArea.bottom) / 2;
      const areaH = chartArea.bottom - chartArea.top;

      ctx.save();

      // Responsive font: based on chart area height (not total canvas)
      const numFontSize = Math.max(20, areaH * 0.22);
      const labelFontSize = Math.max(10, areaH * 0.09);

      let sum = 0;
      if (chart.config.data.datasets.length > 0) {
        sum = chart.config.data.datasets[0].data.reduce((a, b) => a + b, 0);
      }

      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      // "Total" label slightly above center
      ctx.font = `500 ${labelFontSize}px Inter, -apple-system, sans-serif`;
      ctx.fillStyle = '#94a3b8';
      ctx.fillText('Total', centerX, centerY - numFontSize * 0.6);

      // Big bold number at center
      ctx.font = `bold ${numFontSize}px Inter, -apple-system, sans-serif`;
      ctx.fillStyle = '#1e293b';
      ctx.fillText(sum.toLocaleString(), centerX, centerY + labelFontSize * 0.5);

      ctx.restore();
    }
  };

  // Plugin 2: Draw individual segment counts just outside the arc
  const outerSegmentLabelsPlugin = {
    id: 'outerSegmentLabels',
    afterDatasetsDraw: function (chart) {
      if (chart.config.type !== 'doughnut') return;

      const { ctx, data } = chart;
      const dataset = chart.getDatasetMeta(0);
      if (!dataset || !dataset.data) return;

      const values = data.datasets[0].data;
      const colors = data.datasets[0].backgroundColor;
      const total = values.reduce((a, b) => a + b, 0);
      if (total === 0) return;

      // ✅ Only show pills if there are multiple active categories
      // Single category = full circle = pill always overlaps legend → skip
      const activeCategories = values.filter(v => v > 0).length;
      if (activeCategories <= 1) return;

      ctx.save();

      dataset.data.forEach((arc, i) => {
        const val = values[i];
        if (!val || val === 0) return;

        // Get the midpoint angle of this arc segment
        const startAngle = arc.startAngle;
        const endAngle = arc.endAngle;
        const midAngle = (startAngle + endAngle) / 2;

        // Position: just outside the outer radius
        const outerRadius = arc.outerRadius;
        const labelRadius = outerRadius + 18;

        const x = arc.x + Math.cos(midAngle) * labelRadius;
        const y = arc.y + Math.sin(midAngle) * labelRadius;

        // Draw pill background
        const label = val.toLocaleString();
        const fontSize = Math.max(10, outerRadius / 7);
        ctx.font = `bold ${fontSize}px Inter, sans-serif`;
        const textWidth = ctx.measureText(label).width;
        const padX = 5, padY = 3;
        const pillW = textWidth + padX * 2;
        const pillH = fontSize + padY * 2;
        const pillX = x - pillW / 2;
        const pillY = y - pillH / 2;

        // Pill shape
        ctx.beginPath();
        ctx.roundRect(pillX, pillY, pillW, pillH, pillH / 2);
        ctx.fillStyle = Array.isArray(colors) ? (colors[i] || '#64748b') : '#64748b';
        ctx.globalAlpha = 0.92;
        ctx.fill();
        ctx.globalAlpha = 1;

        // Label text
        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, x, y);
      });

      ctx.restore();
    }
  };

  Chart.register(centerTextPlugin, outerSegmentLabelsPlugin);

  const getDonutOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    layout: {
      padding: { top: 28, left: 28, right: 28, bottom: 8 }
    },
    plugins: {
      legend: {
        position: 'bottom',
        align: 'center',
        labels: {
          usePointStyle: true,
          pointStyle: 'circle',
          padding: 20,      // Horizontal space between items in a row
          boxWidth: 8,
          font: { size: 11, weight: '600', family: 'Inter, sans-serif' },
          color: '#374151'
        }
      }
    },
    elements: {
      arc: {
        borderWidth: 3,
        borderColor: '#ffffff',
        borderRadius: 4
      }
    }
  });

  /* Helper: populate a horizontal bar-stat widget (Refined for Browser list) */
  function renderStatBars(wrapId, items) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;
    const total = items.reduce((s, i) => s + i.value, 0);
    if (total === 0) return;
    wrap.innerHTML = items.slice(0, 4).map((item, idx) => {
      const pct = ((item.value / total) * 100).toFixed(0);
      const isFirst = idx === 0;
      return `
        <div class="space-y-2">
          <div class="flex justify-between items-center text-[12px]">
            <span class="text-gray-700 font-medium">${item.label}</span>
            <span class="text-gray-900 font-bold">${pct}%</span>
          </div>
          <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden">
            <div class="h-full bg-indigo-600 transition-all duration-1000 sp-progress-bar-fill ${isFirst ? '' : 'opacity-30'}" data-progress="${pct}"></div>
          </div>
        </div>`;
    }).join('');
    wrap.querySelectorAll('.sp-progress-bar-fill[data-progress]').forEach(function (el) {
      el.style.width = el.getAttribute('data-progress') + '%';
      el.removeAttribute('data-progress');
    });
  }

  function loadDonuts() {
    const params = { startDate, endDate };
    const COLORS_VT = ['#3b82f6', '#6366f1', '#22c55e', '#f59e0b'];
    const COLORS_DV = ['#a855f7', '#ec4899', '#f59e0b', '#14b8a6'];
    const COLORS_BR = ['#3b82f6', '#f59e0b', '#22c55e', '#ec4899', '#8b5cf6'];

    // Visitor Types → Refined Bar
    const p1 = fetchJson(buildRestUrl('visitor-types', params))
      .then(res => {
        if (!res || !res.data) return;
        const { labels, data } = res;
        const total = data.reduce((s, v) => s + v, 0);
        if (total === 0) return;

        labels.forEach((l, i) => {
          const val = data[i] || 0;
          const pct = ((val / total) * 100).toFixed(0);
          if (l === 'New') {
            const pctEl = document.getElementById('vt-new-pct');
            const barEl = document.getElementById('vt-progress-new');
            if (pctEl) pctEl.textContent = pct + '%';
            if (barEl) barEl.style.width = pct + '%';
          } else if (l === 'Returning') {
            const pctEl = document.getElementById('vt-ret-pct');
            const barEl = document.getElementById('vt-progress-ret');
            if (pctEl) pctEl.textContent = pct + '%';
            if (barEl) barEl.style.width = pct + '%';
          }
        });
      }).catch(e => console.error('Visitor types failed:', e));

    // Devices → Refined Pillars
    const p2 = fetchJson(buildRestUrl('devices', params))
      .then(res => {
        if (!res || !res.data) return;
        const { labels, data } = res;
        const deviceMap = { 'Mobile': 'dv-mob', 'Desktop': 'dv-desk', 'Tablet': 'dv-tab' };
        const total = (data || []).reduce((s, v) => s + v, 0);
        (labels || []).forEach((label, i) => {
          const val = data[i] || 0;
          const pct = total > 0 ? ((val / total) * 100).toFixed(0) : 0;
          const key = deviceMap[label];
          if (!key) return;
          const pctEl = document.getElementById(`${key}-pct`);
          if (pctEl) pctEl.textContent = pct + '%';
        });
      }).catch(e => console.error('Devices failed:', e));

    // Browsers → dynamic bar chart
    const p3 = fetchJson(buildRestUrl('browsers', params))
      .then(res => {
        if (!res || res.error || res.code || !res.data) return;
        const { labels, data } = res;
        const items = (labels || []).map((l, i) => ({ label: l, value: data[i] || 0, color: COLORS_BR[i] || '#64748b' }));
        renderStatBars('browserChartWrap', items);
      }).catch(e => console.error('Browsers failed:', e));

    return Promise.all([p1, p2, p3]);
  }

  /* ================= SHOPPING FUNNEL ================= */

  function loadFunnelReport() {
    return fetchJson(buildRestUrl('funnel', { startDate, endDate }))
      .then(data => {
        if (data.error || data.code) return;
        const fields = ['views', 'cart', 'checkout', 'purchase'];

        currentFunnelData = {
          views: data.views || 0,
          cart: data.cart || 0,
          checkout: data.checkout || 0,
          purchase: data.purchase || 0
        };

        updateUI();
      })
      .catch(e => console.error("Funnel failed:", e));
  }

  /* ================= REAL-TIME VISITORS ================= */
  let realtimeMinuteChartInstance;

  function initRealtimeMinuteChart() {
    const el = document.getElementById('realtimeMinuteChart');
    if (!el) return;

    if (realtimeMinuteChartInstance) realtimeMinuteChartInstance.destroy();

    const ctx = el.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
    gradient.addColorStop(0.7, 'rgba(59, 130, 246, 0.08)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.00)');

    const labels = ['30m ago', '25m ago', '20m ago', '15m ago', '10m ago', '5m ago', '0m ago'];
    const dataPoints = [24, 38, 45, 62, 53, 78, 85];

    realtimeMinuteChartInstance = new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'Pageviews / min', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'Pageviews / min'),
          data: dataPoints,
          borderColor: '#3b82f6',
          backgroundColor: gradient,
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#3b82f6',
          pointBorderWidth: 2,
          borderWidth: 2.5
        }]
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
          legend: { display: false },
          tooltip: {
            enabled: false,
            position: 'nearest',
            external: function (context) {
              if (window.SmPulseAnalyticsChartTooltip && typeof window.SmPulseAnalyticsChartTooltip.createExternalTooltip === 'function') {
                window.SmPulseAnalyticsChartTooltip.createExternalTooltip((typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'Pageviews / min', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'Pageviews / min'))(context);
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Pageview Count',
              color: '#64748b',
              font: { size: 11, weight: '600' }
            },
            ticks: { precision: 0, color: '#94a3b8', font: { size: 10 } },
            grid: { color: 'rgba(241, 245, 249, 0.8)' }
          },
          x: {
            title: {
              display: true,
              text: 'Minutes Ago (5m intervals)',
              color: '#64748b',
              font: { size: 11, weight: '600' }
            },
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10 } }
          }
        }
      }
    });
  }

  function loadLiveVisitorsCount() {
    return fetchJson(buildRestUrl('realtime'))
      .then(data => {
        const el = document.getElementById('live-visitors-count');
        if (el) {
          const count = typeof data === 'number' ? data : parseInt(data, 10);
          el.textContent = Number.isFinite(count) ? count.toLocaleString() : '0';
        }
      })
      .catch(err => {
        console.error('Error loading live visitors count:', err);
      });
  }

  function loadRealTimeData() {
    initRealtimeMinuteChart();
    return loadLiveVisitorsCount();
  }

  function loadSessionsPeriodComparison(currentSessions) {
    const { prevStartDate, prevEndDate } = getPreviousPeriodDates(startDate, endDate);
    return fetchJson(buildRestUrl('kpi-metrics', { startDate: prevStartDate, endDate: prevEndDate }))
      .then(prevData => {
        updateSessionsDelta(currentSessions, prevData.sessions || 0);
      })
      .catch(() => {
        updateSessionsDelta(currentSessions, 0);
      });
  }

  /* ================= TABLES ================= */
  function loadTables() {
    const params = { startDate, endDate };

    // Top Pages
    const p1 = fetchJson(buildRestUrl('top-pages', params))
      .then(data => {
        const overviewBody = document.getElementById('overviewTopPagesBody');
        const fullBody = document.getElementById('fullTopPagesBody');

        const renderFullRow = row => `
          <tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">
            <td class="p-5 font-semibold text-gray-700">${row.page}</td>
            <td class="p-5 text-center font-bold text-blue-600">${parseInt(row.pageViews).toLocaleString()}</td>
            <td class="p-5 text-center text-gray-600">${parseInt(row.engagedSessions).toLocaleString()}</td>
            <td class="p-5 text-center text-gray-600">${parseInt(row.newUsers).toLocaleString()}</td>
            <td class="p-5 text-center text-gray-400 font-semibold">${(parseFloat(row.bounceRate) * 100).toFixed(0)}%</td>
          </tr>`;

        const renderOverviewRow = row => `
          <tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">
            <td class="p-4 font-semibold text-gray-700 text-xs truncate max-w-[150px]" title="${row.page}">${row.page}</td>
            <td class="p-4 text-right font-bold text-blue-600">${parseInt(row.pageViews).toLocaleString()}</td>
          </tr>`;

        const isOk = Array.isArray(data) && data.length > 0;
        const noDataText = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'No data found.', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'No data found.');

        if (overviewBody) {
          overviewBody.innerHTML = isOk ? data.slice(0, 5).map(renderOverviewRow).join('') : `<tr><td colspan="2" class="p-8 text-center text-gray-400">${noDataText}</td></tr>`;
        }
        if (fullBody) {
          fullBody.innerHTML = isOk ? data.map(renderFullRow).join('') : `<tr><td colspan="5" class="p-8 text-center text-gray-400">${noDataText}</td></tr>`;
        }
      }).catch(e => console.error("Top Pages failed:", e));

    // Top Countries
    const p2 = fetchJson(buildRestUrl('top-countries', params))
      .then(data => {
        const overviewBody = document.getElementById('overviewTopCountriesBody');
        const fullBody = document.getElementById('fullTopCountriesBody');

        const renderRow = (row, i) => `
          <tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">
            <td class="p-4 font-semibold text-gray-700">
                <span class="inline-block w-5 h-5 bg-green-100 text-green-700 text-[10px] text-center leading-5 rounded-full mr-2">${i + 1}</span>
                ${row.country}
            </td>
            <td class="p-4 text-right font-bold text-green-600">${parseInt(row.visitors || 0).toLocaleString()}</td>
          </tr>`;

        const isOk = Array.isArray(data) && data.length > 0;
        const noDataText = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'No data found.', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'No data found.');

        if (overviewBody) {
          overviewBody.innerHTML = isOk ? data.slice(0, 5).map((r, i) => renderRow(r, i)).join('') : `<tr><td colspan="2" class="p-8 text-center text-gray-400">${noDataText}</td></tr>`;
        }
        if (fullBody) {
          fullBody.innerHTML = isOk ? data.map((r, i) => renderRow(r, i)).join('') : `<tr><td colspan="2" class="p-8 text-center text-gray-400">${noDataText}</td></tr>`;
        }
      }).catch(e => console.error("Top Countries failed:", e));

    // Source/Medium
    const p3 = fetchJson(buildRestUrl('source-medium', params))
      .then(data => {
        const tbody = document.querySelector('#sourceMediumTable tbody');
        if (!tbody) return;

        if (!Array.isArray(data) || !data.length) {
          const noDataText = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ? wp.i18n.__( 'No data found.', 'smackcoders-pulse-analytics-for-woocommerce' ) : 'No data found.');
          tbody.innerHTML = `<tr><td colspan="2" class="p-8 text-center text-gray-400">${noDataText}</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map(row => `
          <tr class="odd:bg-blue-50 even:bg-white transition-colors hover:bg-gray-100">
            <td class="p-5 font-semibold text-gray-700">${row.sourceMedium}</td>
            <td class="p-5 text-right font-bold text-purple-600">${parseInt(row.sessions).toLocaleString()}</td>
          </tr>`).join('');
      }).catch(e => console.error("Source Medium failed:", e));

    return Promise.all([p1, p2, p3]);
  }

  /* ================= KPI METRICS (Sessions, Page Views, Duration, Engagement) ================= */
  function loadKPIMetrics() {
    const params = { startDate, endDate };

    return fetchJson(buildRestUrl('kpi-metrics', params))
      .then(gaData => {
        if (gaData.error || gaData.code) {
          console.error('Metric fetch partial error:', gaData.error || gaData.code);
        }

        currentSessionsReported = gaData.sessions || 0;

        const pageViewsEl = document.getElementById('kpi-pageviews');
        if (pageViewsEl) {
          pageViewsEl.textContent = parseInt(gaData.pageviews || 0, 10).toLocaleString();
        }

        const durationEl = document.getElementById('kpi-duration');
        if (durationEl) {
          const totalSec = Math.round(gaData.avg_session_duration || 0);
          const mins = Math.floor(totalSec / 60);
          const secs = totalSec % 60;
          durationEl.textContent = `${mins}m ${secs}s`;
        }

        const engageEl = document.getElementById('kpi-engagement');
        if (engageEl) {
          const rate = parseFloat(gaData.engagement_rate || 0);
          engageEl.textContent = (rate * 100).toFixed(1) + '%';
        }

        updatePagesPerSession(gaData.pageviews || 0, gaData.sessions || 0);
        loadSessionsPeriodComparison(gaData.sessions || 0);

        const sessionsEl = document.getElementById('kpi-sessions');
        if (sessionsEl) {
          sessionsEl.textContent = currentSessionsReported.toLocaleString();
        }

        if (typeof jQuery !== 'undefined') {
          jQuery(document).trigger('pulse-analytics:dashboard-loaded', [{ startDate, endDate, gaData }]);
        }

        return Promise.resolve(gaData);
      })
      .catch(e => console.error('KPI Metrics sync failed:', e));
  }

  async function reloadFreeTrafficData(silent = false) {
    if (!silent) showPageLoader();

    const tasks = [
      loadLineChart(),
      loadKPIMetrics(),
      loadDonuts(),
      loadTables(),
      loadLiveVisitorsCount()
    ];

    Promise.allSettled(tasks).then(() => {
      if (!silent) hidePageLoader();
    }).catch(err => {
      console.error('Pulse Analytics: Free traffic load error:', err);
      if (!silent) hidePageLoader();
    });
  }

  /* ================= RELOAD ALL (Delegates to Free Traffic Loader + Dispatches Event) ================= */
  async function reloadAllData(silent = false) {
    return reloadFreeTrafficData(silent);
  }

  /* ================= SECTION SWITCHING ================= */
  const viewSelector = document.getElementById('viewSelector');
if (viewSelector) {
  viewSelector.addEventListener('change', function () {
    const sections = ['overviewSection', 'topPagesSection', 'topCountriesSection', 'sourceMediumSection'];
    sections.forEach(s => {
      const sectionEl = document.getElementById(s);
      if (sectionEl) sectionEl.classList.add('hidden');
    });

    const selectedSection = document.getElementById(this.value);
    if (selectedSection) {
      selectedSection.classList.remove('hidden');
    }
  });
}

/* ================= DRILL-DOWN FUNCTIONALITY (PRO) ================= */
function setupDrillDown() {
  const drillDownElements = document.querySelectorAll('[data-drilldown]');
  drillDownElements.forEach(el => {
    el.addEventListener('click', function (e) {
      // If clicking on PRO badge or inside modal, do not trigger drilldown navigation
      if (e.target.closest('.open-dashboard-pro-modal, .pulse-analytics-pro-badge-pill, .pulse-analytics-pro-mini-tag, #pulse-analytics-dashboard-pro-modal, .pulse-analytics-dashboard-modal-close')) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      // In Free mode, if card has a PRO badge, open the PRO popup modal instead of redirecting
      const hasProBadge = this.querySelector('.pulse-analytics-pro-badge-pill, .pulse-analytics-pro-mini-tag');
      if (hasProBadge) {
        e.preventDefault();
        e.stopPropagation();
        const modal = document.getElementById('pulse-analytics-dashboard-pro-modal');
        if (modal) {
          modal.classList.remove('hidden');
          modal.classList.add('flex');
          setTimeout(() => {
            const dialog = modal.querySelector('.animate-pro-modal-in');
            if (dialog) {
              dialog.classList.remove('scale-95', 'opacity-0');
              dialog.classList.add('scale-100', 'opacity-100');
            }
          }, 10);
        }
        return;
      }

      const metric = this.getAttribute('data-drilldown');

      // Pro drill-down pages (free falls back to in-dashboard modal).
      const proActive = !!(window.pulseAnalyticsAjax && window.pulseAnalyticsAjax.is_pro_active);
      if (proActive && metric === 'sessions') {
        window.location.href = 'admin.php?page=pulse-analytics-traffic-overview';
        return;
      }
      if (proActive && (metric === 'revenue' || metric === 'avg_order_value' || metric === 'conversion_rate')) {
        window.location.href = 'admin.php?page=pulse-analytics-ecommerce-overview';
        return;
      }

      const dimension = this.getAttribute('data-dimension') || 'deviceCategory';
      openDrillDownModal(metric, dimension);
    });
  });
}

function openDrillDownModal(metric, dimension) {
  // Create modal for drill-down view
  const modal = document.createElement('div');
  modal.id = 'drilldown-modal';
  modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
  modal.innerHTML = `
      <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-800">${metric.charAt(0).toUpperCase() + metric.slice(1)} by ${dimension}</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl drilldown-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
          <div id="drilldown-content" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="text-gray-500 mt-2">Loading breakdown...</p>
          </div>
        </div>
      </div>
    `;
  document.body.appendChild(modal);

  modal.querySelector('.drilldown-modal-close')?.addEventListener('click', () => modal.remove());
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.remove();
    }
  });

  // Fetch drill-down data
  const dateRange = document.getElementById('dateRangeInput')?.value || '';
  const [startDate, endDate] = dateRange ? dateRange.split(' to ') : [
    toLocalDateString(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)),
    toLocalDateString(new Date())
  ];

  fetch(`${restBase}/reports/custom`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': Object.keys(pulseAjax).length ? pulseAjax.nonce : ''
    },
    body: JSON.stringify({
      dateRanges: [{ startDate, endDate }],
      metrics: [{ name: metric === 'conversion_rate' ? 'conversions' : metric }],
      dimensions: [{ name: dimension }]
    })
  })
    .then(res => res.json())
    .then(data => {
      const content = document.getElementById('drilldown-content');
      if (data.error || data.code || !data.data || !data.data.rows) {
        content.innerHTML = '<p class="text-red-600">Error loading breakdown data</p>';
        return;
      }

      let html = '<table class="w-full text-sm"><thead class="bg-gray-50"><tr>';
      html += `<th class="p-3 text-left">${dimension}</th>`;
      html += `<th class="p-3 text-right">${metric}</th>`;
      html += '</tr></thead><tbody>';

      data.data.rows.forEach(row => {
        const dimValue = row.dimensionValues?.[0]?.value || 'Unknown';
        const metricValue = row.metricValues?.[0]?.value || '0';
        html += `<tr class="border-b"><td class="p-3">${dimValue}</td><td class="p-3 text-right font-semibold">${metricValue}</td></tr>`;
      });

      html += '</tbody></table>';
      content.innerHTML = html;
    })
    .catch(err => {
      document.getElementById('drilldown-content').innerHTML = '<p class="text-red-600">Error: ' + err.message + '</p>';
    });
}

/* ================= PERIOD COMPARISON (PRO) ================= */
function setupPeriodComparison() {
  const compareToggle = document.getElementById('dashboard-compare-toggle');
  const comparePeriodType = document.getElementById('compare-period-type');

  if (!compareToggle) return;

  compareToggle.addEventListener('change', function () {
    if (this.checked) {
      comparePeriodType.style.display = 'block';
      loadKPIMetricsWithComparison();
    } else {
      comparePeriodType.style.display = 'none';
      loadKPIMetrics();
    }
  });

  if (comparePeriodType) {
    comparePeriodType.addEventListener('change', function () {
      if (compareToggle.checked) {
        loadKPIMetricsWithComparison();
      }
    });
  }
}

function loadKPIMetricsWithComparison() {
  const compareType = document.getElementById('compare-period-type')?.value || 'week';
  const dateRange = document.getElementById('dateRangeInput')?.value || '';
  let startDate, endDate;

  if (dateRange && dateRange.includes(' to ')) {
    [startDate, endDate] = dateRange.split(' to ');
  } else {
    endDate = toLocalDateString(new Date());
    startDate = toLocalDateString(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000));
  }

  // Calculate comparison period (shifted back by same number of days)
  const currentDays = Math.round((new Date(endDate) - new Date(startDate)) / (24 * 60 * 60 * 1000)) + 1;
  const prevEndDate = toLocalDateString(new Date(new Date(startDate).getTime() - 24 * 60 * 60 * 1000));
  const prevStartDate = toLocalDateString(new Date(new Date(prevEndDate).getTime() - currentDays * 24 * 60 * 60 * 1000));

  // Fetch comparison data
  Promise.all([
    fetch(`${restBase}/reports/compare?period=${compareType}&startDate=${startDate}&endDate=${endDate}&metrics=sessions,totalUsers`).then(r => r.json()),
    fetch(`${restBase}/wc-metrics?startDate=${startDate}&endDate=${endDate}`).then(r => r.json()),
    fetch(`${restBase}/wc-metrics?startDate=${prevStartDate}&endDate=${prevEndDate}`).then(r => r.json())
  ])
    .then(([compareData, wcData, prevWcData]) => {
      if (compareData.error) {
        console.error('Comparison error:', compareData);
        return;
      }

      // Extract comparison values from GA response
      const currentMetrics = compareData.current?.metrics || {};
      const previousMetrics = compareData.previous?.metrics || {};

      const sessionsCurrent = currentMetrics.sessions || 0;
      const sessionsPrevious = previousMetrics.sessions || 0;

      const revenueCurrent = wcData.revenue || 0;
      const revenuePrevious = prevWcData.revenue || 0;
      const ordersCurrent = wcData.orders || 0;
      const ordersPrevious = prevWcData.orders || 0;

      // Calculate conversion rates
      const convRateCurrent = sessionsCurrent > 0 ? (ordersCurrent / sessionsCurrent * 100) : 0;
      const convRatePrevious = sessionsPrevious > 0 ? (ordersPrevious / sessionsPrevious * 100) : 0;

      // Calculate AOV
      const aovCurrent = ordersCurrent > 0 ? (revenueCurrent / ordersCurrent) : 0;
      const aovPrevious = ordersPrevious > 0 ? (revenuePrevious / ordersPrevious) : 0;

      // Update comparison indicators
      updateComparisonIndicator('kpi-sessions-comparison', sessionsCurrent, sessionsPrevious);
      updateComparisonIndicator('kpi-revenue-comparison', revenueCurrent, revenuePrevious);
      updateComparisonIndicator('kpi-conversion-comparison', convRateCurrent, convRatePrevious);
      updateComparisonIndicator('kpi-aov-comparison', aovCurrent, aovPrevious);
    })
    .catch(err => console.error('Comparison fetch failed:', err));
}

function updateComparisonIndicator(elementId, current, previous, change) {
  const el = document.getElementById(elementId);
  if (!el) return;

  if (previous === 0) {
    el.innerHTML = '<span class="text-gray-500">No previous data</span>';
    el.style.display = 'block';
    return;
  }

  const changePercent = change !== undefined ? change : ((current - previous) / previous * 100);
  const isPositive = changePercent >= 0;
  const arrow = isPositive ? '↑' : '↓';
  const color = isPositive ? 'text-green-600' : 'text-red-600';
  const periodType = document.getElementById('compare-period-type')?.value || 'period';
  const periodLabel = periodType === 'week' ? 'week' : periodType === 'month' ? 'month' : 'period';

  el.innerHTML = `<span class="${color} font-medium">${arrow} ${Math.abs(changePercent).toFixed(1)}% vs previous ${periodLabel}</span>`;
  el.style.display = 'block';
}

// Sortable Drag & Drop UI Setup
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

// Initial Data Load
if (document.getElementById('PulseAnalytics-dashboard-v2')) {
  reloadAllData();

  if (window.SmPulseAnalyticsProActive) {
    setInterval(function () {
      console.log('Pulse Analytics: Auto-refreshing dashboard PRO data...');
      reloadAllData(true);
      if (document.getElementById('dashboard-compare-toggle')?.checked) {
        loadKPIMetricsWithComparison();
      }
    }, 60000);

    setInterval(loadRealTimeData, 20000);
  } else {
    setInterval(function () {
      reloadFreeTrafficData(true);
    }, 120000);
    setInterval(loadLiveVisitorsCount, 60000);
  }
}

const dashboardDatePreset = document.getElementById('dashboardDatePreset');
if (dashboardDatePreset) {
  dashboardDatePreset.addEventListener('change', function () {
    const preset = this.value;
    if (preset === 'custom') {
      if (window.SmPulseAnalyticsDatePicker) {
        window.SmPulseAnalyticsDatePicker.open();
      }
      return;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let start;
    let end;
    switch (preset) {
      case 'today':
        start = new Date(today);
        end = new Date(today);
        break;
      case 'yesterday':
        end = new Date(today);
        end.setDate(end.getDate() - 1);
        start = new Date(end);
        break;
      case '7':
        end = new Date(today);
        start = new Date(today);
        start.setDate(start.getDate() - 6);
        break;
      case '30':
        end = new Date(today);
        start = new Date(today);
        start.setDate(start.getDate() - 29);
        break;
      case '90':
        end = new Date(today);
        start = new Date(today);
        start.setDate(start.getDate() - 89);
        break;
      default:
        return;
    }
    startDate = toLocalDateString(start);
    endDate = toLocalDateString(end);
    if (window.SmPulseAnalyticsDatePicker) {
      window.SmPulseAnalyticsDatePicker.setDate([start, end], false);
      window.SmPulseAnalyticsDatePicker.input.value = formatDateRangeDisplay(window.SmPulseAnalyticsDatePicker, start, end);
    }
    if (typeof reloadAllData === 'function') {
      reloadAllData();
    }
  });
}

setupDrillDown();
setupPeriodComparison();
});