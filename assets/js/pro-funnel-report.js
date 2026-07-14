(function() {
    // Use localized values if available
    const restBase = typeof StorePulseFunnelReport !== 'undefined' ? StorePulseFunnelReport.restBase : '';
    const funnelId = typeof StorePulseFunnelReport !== 'undefined' ? StorePulseFunnelReport.funnelId : '';
    const nonce = typeof StorePulseFunnelReport !== 'undefined' ? StorePulseFunnelReport.nonce : '';

    const loadBtn = document.getElementById('load-report');
    if (loadBtn) {
        loadBtn.addEventListener('click', function() {
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            if (!startDateInput || !endDateInput) return;

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            this.disabled = true;
            this.textContent = 'Loading...';

            fetch(`${restBase}reports/funnel/${funnelId}?startDate=${startDate}&endDate=${endDate}`, {
                headers: {
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                this.textContent = 'Load Report';

                if (data.error) {
                    document.getElementById('funnel-results').innerHTML = 
                        '<div class="text-center text-red-600 py-12"><p>Error: ' + data.message + '</p></div>';
                    return;
                }

                displayFunnel(data);
            })
            .catch(err => {
                this.disabled = false;
                this.textContent = 'Load Report';
                document.getElementById('funnel-results').innerHTML = 
                    '<div class="text-center text-red-600 py-12"><p>Error loading report: ' + err.message + '</p></div>';
            });
        });

        // Auto-load on page load
        loadBtn.click();
    }

    function displayFunnel(data) {
        const container = document.getElementById('funnel-results');
        if (!container) return;
        
        if (!data.steps || data.steps.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-400 py-12"><p>No data available for the selected date range</p></div>';
            return;
        }

        const maxCount = Math.max(...data.steps.map(s => s.count));
        
        let html = '<div class="space-y-4">';
        
        data.steps.forEach((step, index) => {
            const width = maxCount > 0 ? (step.count / maxCount) * 100 : 0;
            const dropoff = index > 0 ? data.steps[index - 1].count > 0 
                ? ((data.steps[index - 1].count - step.count) / data.steps[index - 1].count * 100).toFixed(1)
                : 0 : 0;
            
            html += `
                <div class="funnel-step">
                    <div class="flex justify-between items-center mb-2">
                        <div class="font-semibold text-gray-800">${step.step}</div>
                        <div class="text-sm text-gray-600">
                            <span class="font-bold">${step.count.toLocaleString()}</span>
                            ${index > 0 ? `<span class="text-red-600 ml-2">(${dropoff}% drop-off)</span>` : ''}
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                        <div class="bg-indigo-600 h-8 rounded-full transition-all duration-500 flex items-center justify-center text-white text-xs font-medium" 
                             style="width: ${width}%">
                            ${step.count > 0 ? step.count.toLocaleString() : ''}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">${step.percentage.toFixed(1)}% of initial</div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
})();
