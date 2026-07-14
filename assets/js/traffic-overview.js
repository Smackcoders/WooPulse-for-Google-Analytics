document.addEventListener('DOMContentLoaded', function () {
    const restBase = typeof seoInsightsAjax !== 'undefined' ? seoInsightsAjax.rest_url : '/wp-json/StorePulse/v1';
    
    const buildUrl = (endpoint, params = {}) => {
        const base = restBase.endsWith('/') ? restBase.slice(0, -1) : restBase;
        const separator = base.includes('?') ? '&' : '?';
        const urlParams = new URLSearchParams(params).toString();
        const finalEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
        return `${base}${finalEndpoint}${urlParams ? separator + urlParams : ''}`;
    };

    let trafficChart = null;
    let currentMetric = 'sessions';
    let endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    let startDate = new Date();
    startDate.setDate(endDate.getDate() - 30);
    startDate.setHours(0, 0, 0, 0);
    let compareEnabled = false;

    function updateDateDisplay(start, end) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        const display = document.getElementById('display-date-range');
        if (display) {
            display.innerText = `${start.toLocaleDateString('en-US', options)} — ${end.toLocaleDateString('en-US', options)}`;
        }
    }
    updateDateDisplay(startDate, endDate);

    // Date picker connectivity
    const dateInput = document.getElementById("traffic-date-range");
    const dateBtn = document.getElementById("traffic-date-btn");
    if (dateBtn && dateInput) {
        dateBtn.addEventListener('click', () => { if (dateInput._flatpickr) dateInput._flatpickr.open(); });
    }

    if (typeof flatpickr !== 'undefined') {
        const dateRangeEl = document.getElementById("traffic-date-range");
        if (dateRangeEl) {
            flatpickr(dateRangeEl, {
                mode: "range",
                dateFormat: "Y-m-d",
                maxDate: "today",
                defaultDate: [startDate, endDate],
                onClose: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        startDate = selectedDates[0];
                        endDate = selectedDates[1];
                        updateDateDisplay(startDate, endDate);
                        loadTrafficData();
                    }
                }
            });
        }
    }

    // Compare button toggle
    const compareBtn = document.getElementById('compare-btn-ui');
    const compareCheck = document.getElementById('compare-toggle');
    if (compareBtn && compareCheck) {
        compareBtn.addEventListener('click', () => {
            compareCheck.checked = !compareCheck.checked;
            compareEnabled = compareCheck.checked;
            compareBtn.classList.toggle('border-indigo-500', compareEnabled);
            compareBtn.classList.toggle('text-indigo-600', compareEnabled);
            loadTrafficData();
        });
    }

    // Metric tabs toggle
    document.querySelectorAll('.metric-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.metric-toggle').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            currentMetric = this.id === 'metric-sessions' ? 'sessions' : 'pageviews';
            updateChartTitle();
            loadTrafficData();
        });
    });

    function updateChartTitle() {
        const title = document.getElementById('chart-title');
        const legendLabel = document.getElementById('legend-label');
        const mainLabel = currentMetric === 'sessions' ? 'Sessions' : 'Pageviews';
        
        if (title) title.textContent = mainLabel + ' Over Time';
        if (legendLabel) legendLabel.textContent = mainLabel;
    }

    function toLocalDateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    let fetchedTrafficData = null;

    function loadTrafficData() {
        const errorEl = document.getElementById('traffic-error');
        const errorMsg = document.getElementById('traffic-error-message');

        if (window.StorePulse && typeof window.StorePulse.showLoader === 'function') {
            window.StorePulse.showLoader('Loading traffic data...');
        }
        if (errorEl) errorEl.classList.add('hidden');

        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const url = buildUrl('traffic-overview', { 
            metric: currentMetric, 
            startDate: startStr, 
            endDate: endStr, 
            compare: compareEnabled 
        });

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (data.error) {
                    if (errorMsg) errorMsg.textContent = data.error;
                    if (errorEl) errorEl.classList.remove('hidden');
                    return;
                }
                fetchedTrafficData = data;
                renderChart(data);
                loadNotes();
            })
            .catch(err => {
                if (window.StorePulse && typeof window.StorePulse.hideLoader === 'function') {
                    window.StorePulse.hideLoader();
                }
                if (errorMsg) errorMsg.textContent = 'Failed to load traffic data: ' + err.message;
                if (errorEl) errorEl.classList.remove('hidden');
            });
    }

    function renderChart(data) {
        const ctx = document.getElementById('traffic-overview-chart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (trafficChart) { trafficChart.destroy(); }

        const color = currentMetric === 'sessions' ? '#6366f1' : '#10b981';
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, hexToRgba(color, 0.15));
        gradient.addColorStop(1, hexToRgba(color, 0));

        const datasets = [{
            label: currentMetric === 'sessions' ? 'Sessions' : 'Pageviews',
            data: data.current.data,
            borderColor: color,
            backgroundColor: gradient,
            fill: true,
            tension: 0.5,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: color,
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 3,
            borderWidth: 3
        }];

        if (data.comparison) {
            datasets.push({
                label: 'Previous Period',
                data: data.comparison.data,
                borderColor: '#94a3b8',
                backgroundColor: 'transparent',
                fill: false,
                tension: 0.5,
                pointRadius: 0,
                pointHoverRadius: 4,
                borderWidth: 2,
                borderDash: [5, 5]
            });
        }

        const labels = data.current.labels.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        trafficChart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleColor: '#94a3b8',
                        bodyColor: '#f8fafc',
                        bodyFont: { weight: 'bold' },
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 }, maxTicksLimit: 10 }
                    },
                    y: { 
                        border: { display: false },
                        grid: { color: '#f1f5f9' },
                        ticks: { 
                            color: '#94a3b8', 
                            font: { size: 11 },
                            callback: (v) => v.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    // Notes functionality
    let notes = [];
    let editingNoteId = null;

    function openNoteDetail(note) {
        const titleEl = document.getElementById('note-detail-title');
        const dateEl = document.getElementById('note-detail-date');
        const descEl = document.getElementById('note-detail-description');
        const modalEl = document.getElementById('note-detail-modal');

        if (titleEl) titleEl.textContent = note.title;
        if (dateEl) dateEl.textContent = new Date(note.note_date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        if (descEl) descEl.textContent = note.description || 'No description provided.';

        // Setup actions
        const editBtn = document.getElementById('note-detail-edit');
        if (editBtn) {
            editBtn.onclick = () => {
                if (modalEl) modalEl.classList.add('hidden');
                editNote(note.note_id);
            };
        }

        const deleteBtn = document.getElementById('note-detail-delete');
        if (deleteBtn) {
            deleteBtn.onclick = () => {
                if (confirm('Are you sure you want to delete this note?')) {
                    if (modalEl) modalEl.classList.add('hidden');
                    deleteNote(note.note_id);
                }
            };
        }

        if (modalEl) modalEl.classList.remove('hidden');
    }

    const detailCloseBtn = document.getElementById('note-detail-close');
    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', () => {
            const modalEl = document.getElementById('note-detail-modal');
            if (modalEl) modalEl.classList.add('hidden');
        });
    }

    function openCreateNoteForm(date = null) {
        editingNoteId = null;
        const titleEl = document.getElementById('note-form-title');
        const formEl = document.getElementById('note-form');
        const idEl = document.getElementById('note-id');
        const dateEl = document.getElementById('note-date');
        const modalEl = document.getElementById('note-form-modal');

        if (titleEl) titleEl.textContent = 'Create Note';
        if (formEl) formEl.reset();
        if (idEl) idEl.value = '';
        if (dateEl) dateEl.value = date || toLocalDateString(new Date());
        if (modalEl) modalEl.classList.remove('hidden');
    }

    // Open Notes Viewer
    const notesViewerBtn = document.getElementById('notes-viewer-btn');
    if (notesViewerBtn) {
        notesViewerBtn.addEventListener('click', function () {
            openCreateNoteForm();
        });
    }

    // Close Notes Modal
    const notesModalCloseBtn = document.getElementById('notes-modal-close');
    if (notesModalCloseBtn) {
        notesModalCloseBtn.addEventListener('click', function () {
            const modalEl = document.getElementById('notes-modal');
            if (modalEl) modalEl.classList.add('hidden');
        });
    }

    // Create Note button
    const createNoteBtn = document.getElementById('create-note-btn');
    if (createNoteBtn) {
        createNoteBtn.addEventListener('click', function () {
            openCreateNoteForm();
        });
    }

    // Close Note Form Modal
    const noteFormCancelBtn = document.getElementById('note-form-cancel');
    if (noteFormCancelBtn) {
        noteFormCancelBtn.addEventListener('click', function () {
            const modalEl = document.getElementById('note-form-modal');
            if (modalEl) modalEl.classList.add('hidden');
        });
    }

    // Note Form Submit
    const noteForm = document.getElementById('note-form');
    if (noteForm) {
        noteForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveNote();
        });
    }

    function loadNotes() {
        const startStr = toLocalDateString(startDate);
        const endStr = toLocalDateString(endDate);
        const url = buildUrl('notes', { startDate: startStr, endDate: endStr });

        fetch(url)
            .then(res => res.json())
            .then(data => {
                notes = data;
                renderNotesList();
                updateChartWithNotes();
            })
            .catch(err => {
                console.error('Failed to load notes:', err);
            });
    }

    function renderNotesList(filteredNotes = null) {
        const listEl = document.getElementById('notes-list');
        const emptyEl = document.getElementById('notes-empty');
        const displayNotes = filteredNotes || notes;

        if (!listEl) return;

        if (displayNotes.length === 0) {
            listEl.innerHTML = '';
            if (emptyEl) emptyEl.classList.remove('hidden');
            return;
        }

        if (emptyEl) emptyEl.classList.add('hidden');
        listEl.innerHTML = displayNotes.map(note => `
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800">${escapeHtml(note.title)}</h4>
                    <p class="text-sm text-gray-500 mt-1">${note.note_date}</p>
                    ${note.description ? `<p class="text-sm text-gray-600 mt-2">${escapeHtml(note.description.substring(0, 100))}${note.description.length > 100 ? '...' : ''}</p>` : ''}
                </div>
                <div class="flex gap-2 ml-4">
                    <button onclick="editNote(${note.note_id})" class="text-blue-500 hover:text-blue-700 text-sm">Edit</button>
                    <button onclick="deleteNote(${note.note_id})" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                </div>
            </div>
        </div>
    `).join('');
    }

    function saveNote() {
        const noteId = document.getElementById('note-id').value;
        const title = document.getElementById('note-title').value;
        const description = document.getElementById('note-description').value;
        const noteDate = document.getElementById('note-date').value;

        const data = {
            title: title,
            description: description,
            note_date: noteDate
        };

        const url = noteId ? buildUrl(`notes/${noteId}`) : buildUrl('notes');
        const method = noteId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw err; });
                }
                return res.json();
            })
            .then(data => {
                const modalEl = document.getElementById('note-form-modal');
                if (modalEl) modalEl.classList.add('hidden');
                loadNotes();
            })
            .catch(err => {
                console.error('Save error:', err);
                alert('Failed to save note: ' + (err.message || 'Unknown error'));
            });
    }

    window.editNote = function (noteId) {
        const note = notes.find(n => n.note_id == noteId);
        if (!note) return;

        editingNoteId = noteId;
        const titleEl = document.getElementById('note-form-title');
        const idEl = document.getElementById('note-id');
        const titleInput = document.getElementById('note-title');
        const descInput = document.getElementById('note-description');
        const dateInput = document.getElementById('note-date');
        const modalEl = document.getElementById('note-form-modal');

        if (titleEl) titleEl.textContent = 'Edit Note';
        if (idEl) idEl.value = noteId;
        if (titleInput) titleInput.value = note.title;
        if (descInput) descInput.value = note.description || '';
        if (dateInput) dateInput.value = note.note_date;
        if (modalEl) modalEl.classList.remove('hidden');
    };

    window.deleteNote = function (noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;

        fetch(buildUrl(`notes/${noteId}`), {
            method: 'DELETE'
        })
            .then(res => res.json())
            .then(data => {
                loadNotes();
            })
            .catch(err => {
                alert('Failed to delete note: ' + err.message);
            });
    };

    function updateChartWithNotes() {
        if (!trafficChart) return;
        trafficChart.update();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial load
    updateChartTitle();
    loadTrafficData();
    loadNotes();
});
