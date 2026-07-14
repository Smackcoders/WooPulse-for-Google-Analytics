jQuery(document).ready(function ($) {
    function fetchLogs() {
        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') window.StorePulse.showLoader();
        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'StorePulse_fetch_logs', ...Object.fromEntries(new FormData($('#logs-filter-form')[0])) },
            success: function (res) {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') window.StorePulse.hideLoader();
                if (res.success) {
                    $('#logs-table-body').html(res.data.html);
                    $('#log-count').text(res.data.count);
                    $('#log-start').text(res.data.count > 0 ? 1 : 0);
                    $('#log-end').text(res.data.count);
                }
            }
        });
    }

    const applyBtn = document.getElementById('logs-apply-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', fetchLogs);
    }

    let endD = new Date();
    let startD = new Date(); startD.setDate(endD.getDate() - 30);
    function toISO(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function formatRange(s, e) {
        const m = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        if (s.getFullYear() === e.getFullYear()) {
            return `${m[s.getMonth()]} ${s.getDate()} – ${m[e.getMonth()]} ${e.getDate()}, ${s.getFullYear()}`;
        }
        return `${m[s.getMonth()]} ${s.getDate()}, ${s.getFullYear()} – ${m[e.getMonth()]} ${e.getDate()}, ${e.getFullYear()}`;
    }

    $("#date_from").val(toISO(startD)); $("#date_to").val(toISO(endD));

    if (typeof flatpickr !== 'undefined' && document.getElementById('StorePulse-logs-range-special')) {
        const fp = flatpickr("#StorePulse-logs-range-special", {
            mode: "range", dateFormat: "M j, Y", maxDate: "today", defaultDate: [startD, endD],
            onValueUpdate: function (sel) {
                if (sel.length === 2) {
                    $("#date_from").val(toISO(sel[0])); $("#date_to").val(toISO(sel[1]));
                    $('#StorePulse-logs-range-special').val(formatRange(sel[0], sel[1]));
                }
            }
        });
        $('#StorePulse-logs-range-special').val(formatRange(startD, endD));
    }
    fetchLogs();

    // Log Details Modal Logic
    $(document).on('click', '.view-log-details', function() {
        const logId = $(this).data('id');
        $('#modal-log-id').text('Viewing detailed information for Log #' + logId);
        $('#log-details-modal').removeClass('hidden');
        $('#log-details-content').html(`
            <div class="col-span-2 text-center py-12">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
                <p class="text-slate-400 font-medium">Fetching log metadata...</p>
            </div>
        `);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'StorePulse_get_log_details',
                log_id: logId,
                nonce: $('#logs-filter-form [name="nonce"]').val()
            },
            success: function(res) {
                if (res.success) {
                    const log = res.data;
                    let html = '';

                    const sections = [
                        {
                            title: 'Basic Log Information',
                            fields: [
                                { label: 'Log ID', value: '#' + log.log_id },
                                { label: 'Event Type', value: log.event_type },
                                { label: 'Status', value: log.status },
                                { label: 'Timestamp', value: log.created_at },
                                { label: 'Session ID', value: log.session_id }
                            ]
                        },
                        {
                            title: 'User Information',
                            fields: [
                                { label: 'User Name', value: log.user_name || 'Guest' },
                                { label: 'User ID', value: log.user_id || 'N/A' },
                                { label: 'Email', value: log.user_email || 'N/A' },
                                { label: 'Role', value: log.user_role || 'Visitor' },
                                { label: 'IP Address', value: log.ip_address || 'Unknown' }
                            ]
                        },
                        {
                            title: 'Device & Browser',
                            fields: [
                                { label: 'Device', value: log.device_type || 'Desktop' },
                                { label: 'Browser', value: log.browser || 'Other' },
                                { label: 'OS', value: log.os || 'Unknown' }
                            ]
                        },
                        {
                            title: 'Traffic & Source',
                            fields: [
                                { label: 'Source', value: log.source || 'Direct' },
                                { label: 'Referrer', value: log.referrer_url || 'None' },
                                { label: 'UTM Source', value: log.utm_source || 'None' },
                                { label: 'UTM Medium', value: log.utm_medium || 'None' },
                                { label: 'UTM Campaign', value: log.utm_campaign || 'None' }
                            ]
                        },
                        {
                            title: 'WooCommerce Data',
                            fields: [
                                { label: 'Order ID', value: log.order_id ? '#' + log.order_id : 'N/A' },
                                { label: 'Order Status', value: log.order_status || 'N/A' },
                                { label: 'Cart Value', value: log.cart_value ? (window.seoInsightsAjax ? window.seoInsightsAjax.currency_symbol : '$') + log.cart_value : '0.00' },
                                { label: 'Payment', value: log.payment_method || 'N/A' },
                                { label: 'Transaction ID', value: log.transaction_id || 'N/A' }
                            ]
                        },
                        {
                            title: 'Page Activity',
                            fields: [
                                { label: 'Exit Page', value: log.exit_page || 'Home' },
                                { label: 'Current Page', value: log.current_page || 'N/A' },
                                { label: 'Time on Page', value: log.time_on_page ? log.time_on_page + 's' : '0s' }
                            ]
                        }
                    ];

                    sections.forEach(sec => {
                        html += `
                            <div class="detail-section">
                                <div class="detail-title">${sec.title}</div>
                                <div class="detail-grid">
                                    ${sec.fields.map(f => `
                                        <div>
                                            <div class="detail-label">${f.label}</div>
                                            <div class="detail-value" title="${f.value}">${f.value}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    });

                    // Add full message if present
                    if (log.message) {
                        html += `
                            <div class="detail-section col-span-2">
                                <div class="detail-title">Log Message / Description</div>
                                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100 text-[13px] text-slate-700 leading-relaxed whitespace-pre-wrap">${log.message}</div>
                            </div>
                        `;
                    }

                    $('#log-details-content').html(html);
                } else {
                    $('#log-details-content').html(`<div class="col-span-2 text-red-500 py-8">${res.data.message}</div>`);
                }
            }
        });
    });

    $('.close-log-modal, #close-modal-bg').on('click', function() {
        $('#log-details-modal').addClass('hidden');
    });

    $('#logs-export-btn').on('click', function() {
        const form = $('#logs-filter-form');
        const formData = new FormData(form[0]);
        formData.append('action', 'StorePulse_export_logs');

        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = ajaxurl;

        for (const [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            tempForm.appendChild(input);
        }

        document.body.appendChild(tempForm);
        tempForm.submit();
        document.body.removeChild(tempForm);
    });
});
