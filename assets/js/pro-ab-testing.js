(function () {
    const restBase = typeof StorePulseAbTesting !== 'undefined' ? StorePulseAbTesting.restBase : '';
    const nonce = typeof StorePulseAbTesting !== 'undefined' ? StorePulseAbTesting.nonce : '';

    const loadBtn = document.getElementById('load-experiments');
    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            this.disabled = true;
            this.textContent = 'Loading...';

            fetch(`${restBase}pro/ab-testing?startDate=${startDate}&endDate=${endDate}`, {
                headers: {
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin'
            })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    this.textContent = 'Load Experiments';

                    if (data.error) {
                        document.getElementById('experiments-content').innerHTML =
                            '<div class="text-center text-red-600 py-12"><p>Error: ' + data.message + '</p></div>';
                        return;
                    }

                    displayExperiments(data);
                })
                .catch(err => {
                    this.disabled = false;
                    this.textContent = 'Load Experiments';
                    document.getElementById('experiments-content').innerHTML =
                        '<div class="text-center text-red-600 py-12"><p>Error loading experiments: ' + err.message + '</p></div>';
                });
        });
    }

    function displayExperiments(data) {
        const container = document.getElementById('experiments-content');
        if (!container) return;

        if (!data.experiments || data.experiments.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-400 py-12"><p>No A/B test data available. Tag your experiments with variant parameters.</p></div>';
            return;
        }

        let html = '<table class="w-full text-sm StorePulse-alternating"><thead class="bg-gray-50"><tr>';
        html += '<th class="p-3 text-left">Experiment</th>';
        html += '<th class="p-3 text-left">Variant</th>';
        html += '<th class="p-3 text-left">Sessions</th>';
        html += '<th class="p-3 text-left">Conversions</th>';
        html += '<th class="p-3 text-left">Conversion Rate</th>';
        html += '</tr></thead><tbody>';

        data.experiments.forEach(exp => {
            html += `<tr class="odd:bg-blue-50 even:bg-white hover:bg-gray-100 transition-colors"><td class="p-3 font-semibold">${exp.experiment_name}</td>`;
            html += `<td class="p-3"><span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded">${exp.variant}</span></td>`;
            html += `<td class="p-3">${exp.sessions}</td>`;
            html += `<td class="p-3">${exp.conversions}</td>`;
            html += `<td class="p-3 font-bold ${exp.conversion_rate > 2 ? 'text-green-600' : 'text-gray-600'}">${exp.conversion_rate}%</td></tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }
})();
