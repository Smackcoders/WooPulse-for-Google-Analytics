function applyTemplate(label, event, type, trigger) {
    const typeEl = document.getElementById('g_type');
    if (document.getElementById('g_label')) document.getElementById('g_label').value = label;
    if (document.getElementById('g_event')) document.getElementById('g_event').value = event;
    if (typeEl) typeEl.value = type;
    if (document.getElementById('g_trigger')) document.getElementById('g_trigger').value = trigger;

    // Trigger change event to update hints
    if (typeEl) typeEl.dispatchEvent(new Event('change'));

    // Highlight effect
    const form = document.getElementById('goalBuilderForm');
    if (form) {
        form.classList.add('ring-2', 'ring-indigo-500');
        setTimeout(() => form.classList.remove('ring-2', 'ring-indigo-500'), 1000);
    }
}

function applySuggestion(suggestion) {
    applyTemplate(suggestion.label, suggestion.event_name, suggestion.type, suggestion.trigger);
    // Scroll to form
    const form = document.getElementById('goalBuilderForm');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const typeEl = document.getElementById('g_type');
    if (typeEl) {
        typeEl.addEventListener('change', function (e) {
            const hint = document.getElementById('triggerHint');
            const placeholders = {
                'page_view': 'e.g. /thank-you',
                'click': 'e.g. button.add-to-cart',
                'form_submit': 'e.g. form#contact-form'
            };
            const hints = {
                'page_view': 'Matches if the URL contains this text. Great for tracking visits to specific pages.',
                'click': 'Matches if the clicked element has this CSS class or ID. Great for tracking button clicks.',
                'form_submit': 'Matches if the form has this CSS ID or class. Great for tracking newsletter or contact signups.'
            };
            if (hint) hint.textContent = hints[e.target.value] || '';
            const triggerEl = document.getElementById('g_trigger');
            if (triggerEl) triggerEl.placeholder = placeholders[e.target.value] || '';
        });
    }
});
