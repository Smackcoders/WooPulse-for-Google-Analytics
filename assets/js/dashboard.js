jQuery(document).ready(function ($) {
  // Initialize Dashboard Grid Sortable (Main widgets)
  const el = document.getElementById('overviewSection');
  if (el) {
    Sortable.create(el, {
      animation: 150,
      handle: '.cursor-move',
      ghostClass: 'bg-blue-50',
      onEnd: function (evt) {
        if (typeof window.saveDashboardLayout === 'function') {
          window.saveDashboardLayout();
        }
      }
    });
  }

  // Initialize KPI Sortable (KPI cards)
  const kpiEl = document.getElementById('kpi-cards-container');
  if (kpiEl) {
    Sortable.create(kpiEl, {
      animation: 150,
      handle: '.cursor-move-kpi',
      ghostClass: 'bg-blue-100',
      onEnd: function () {
        if (typeof window.saveDashboardLayout === 'function') {
          window.saveDashboardLayout();
        }
      }
    });
  }
});
