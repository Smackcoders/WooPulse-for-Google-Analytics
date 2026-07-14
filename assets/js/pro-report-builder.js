(function () {
    const restBase = typeof StorePulseReportBuilder !== 'undefined' ? StorePulseReportBuilder.restBase : '';
    const nonce = typeof StorePulseReportBuilder !== 'undefined' ? StorePulseReportBuilder.nonce : '';
    let selectedMetrics = [];
    let selectedDimensions = [];

    // Drag and drop handlers
    document.querySelectorAll('.metric-item, .dimension-item').forEach(item => {
        item.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                type: this.dataset.type,
                name: this.dataset.name,
                label: this.querySelector('span').textContent
            }));
        });
    });

    // Drop zones
    const metricsZone = document.getElementById('selected-metrics');
    const dimensionsZone = document.getElementById('selected-dimensions');

    if (metricsZone && dimensionsZone) {
        [metricsZone, dimensionsZone].forEach(zone => {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('border-indigo-400', 'bg-indigo-50');
            });

            zone.addEventListener('dragleave', function (e) {
                this.classList.remove('border-indigo-400', 'bg-indigo-50');
            });

            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('border-indigo-400', 'bg-indigo-50');

                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                const isMetricsZone = this.id === 'selected-metrics';

                if ((isMetricsZone && data.type === 'metric') || (!isMetricsZone && data.type === 'dimension')) {
                    const name = data.name;
                    const label = data.label;

                    if (isMetricsZone) {
                        if (!selectedMetrics.includes(name)) {
                            selectedMetrics.push(name);
                            addItem(this, name, label, 'metric');
                        }
                    } else {
                        if (!selectedDimensions.includes(name)) {
                            selectedDimensions.push(name);
                            addItem(this, name, label, 'dimension');
                        }
                    }
                }
            });
        });
    }

    function addItem(container, name, label, type) {
        if (container.querySelector('p.text-gray-400')) {
            container.innerHTML = '';
        }

        const item = document.createElement('div');
        item.className = 'inline-block bg-indigo-100 text-indigo-700 px-3 py-1 rounded mr-2 mb-2 text-xs';
        item.innerHTML = `${label} <button class="ml-2 text-indigo-500 hover:text-indigo-700 remove-item-btn" data-type="${type}" data-name="${name}">×</button>`;
        container.appendChild(item);

        item.querySelector('.remove-item-btn').addEventListener('click', function() {
            removeItem(this.dataset.type, this.dataset.name);
        });
    }

    function removeItem(type, name) {
        if (type === 'metric') {
            selectedMetrics = selectedMetrics.filter(m => m !== name);
            updateSelectedDisplay('selected-metrics', selectedMetrics, 'metric');
        } else {
            selectedDimensions = selectedDimensions.filter(d => d !== name);
            updateSelectedDisplay('selected-dimensions', selectedDimensions, 'dimension');
        }
    }

    function updateSelectedDisplay(containerId, items, type) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        if (items.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-400 text-center">Drag ' + type + 's here</p>';
        } else {
            items.forEach(name => {
                const label = name.charAt(0).toUpperCase() + name.slice(1).replace(/([A-Z])/g, ' $1');
                addItem(container, name, label, type);
            });
        }
    }

    // Run report
    const runBtn = document.getElementById('run-report');
    if (runBtn) {
        runBtn.addEventListener('click', function () {
            if (selectedMetrics.length === 0) {
                alert('Please select at least one metric');
                return;
            }

            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            if (!startDateInput || !endDateInput) return;

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            const reportBody = {
                dateRanges: [{
                    startDate: startDate,
                    endDate: endDate
                }],
                metrics: selectedMetrics.map(m => ({ name: m })),
                dimensions: selectedDimensions.map(d => ({ name: d })),
            };

            this.disabled = true;
            this.textContent = 'Running...';

            fetch(restBase + 'reports/custom', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify(reportBody)
            })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    this.textContent = 'Run Report';

                    if (data.error) {
                        alert('Error: ' + data.message);
                        return;
                    }

                    displayResults(data.data);
                })
                .catch(err => {
                    this.disabled = false;
                    this.textContent = 'Run Report';
                    alert('Error running report: ' + err.message);
                });
        });
    }

    // Save report
    const saveBtn = document.getElementById('save-report');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const reportNameInput = document.getElementById('report-name');
            if (!reportNameInput) return;

            const reportName = reportNameInput.value.trim();
            if (!reportName) {
                alert('Please enter a report name');
                return;
            }

            if (selectedMetrics.length === 0) {
                alert('Please select at least one metric');
                return;
            }

            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            if (!startDateInput || !endDateInput) return;

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            const reportBody = {
                title: reportName,
                save: true,
                dateRanges: [{
                    startDate: startDate,
                    endDate: endDate
                }],
                metrics: selectedMetrics.map(m => ({ name: m })),
                dimensions: selectedDimensions.map(d => ({ name: d })),
            };

            fetch(restBase + 'reports/custom', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify(reportBody)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.message);
                    } else {
                        alert('Report saved successfully!');
                    }
                })
                .catch(err => {
                    alert('Error saving report: ' + err.message);
                });
        });
    }

    function displayResults(data) {
        const resultsDiv = document.getElementById('report-results');
        const contentDiv = document.getElementById('results-content');
        if (!resultsDiv || !contentDiv) return;

        resultsDiv.classList.remove('hidden');

        if (!data || !data.rows || data.rows.length === 0) {
            contentDiv.innerHTML = '<p class="text-gray-400">No data available for the selected criteria.</p>';
            return;
        }

        // Build table
        let html = '<table class="w-full text-sm border-collapse StorePulse-alternating"><thead class="bg-gray-50"><tr>';

        // Headers
        if (data.dimensionHeaders) {
            data.dimensionHeaders.forEach(h => {
                html += `<th class="p-3 text-left border border-gray-200">${h.name}</th>`;
            });
        }
        if (data.metricHeaders) {
            data.metricHeaders.forEach(h => {
                html += `<th class="p-3 text-left border border-gray-200">${h.name}</th>`;
            });
        }

        html += '</tr></thead><tbody>';

        // Rows
        data.rows.forEach(row => {
            html += '<tr class="odd:bg-blue-50 even:bg-white hover:bg-gray-100 transition-colors">';
            if (row.dimensionValues) {
                row.dimensionValues.forEach(v => {
                    html += `<td class="p-3 border border-gray-200">${v.value}</td>`;
                });
            }
            if (row.metricValues) {
                row.metricValues.forEach(v => {
                    html += `<td class="p-3 border border-gray-200">${v.value}</td>`;
                });
            }
            html += '</tr>';
        });

        html += '</tbody></table>';
        contentDiv.innerHTML = html;
    }
})();
