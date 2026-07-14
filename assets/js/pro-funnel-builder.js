(function() {
    const stepsContainer = document.getElementById('funnel-steps');
    const addStepBtn = document.getElementById('add-step');
    
    // Use localized availableEvents if available
    const availableEvents = typeof StorePulseFunnelBuilder !== 'undefined' ? StorePulseFunnelBuilder.availableEvents : {};

    if (addStepBtn && stepsContainer) {
        addStepBtn.addEventListener('click', function() {
            const stepDiv = document.createElement('div');
            stepDiv.className = 'step-item flex gap-2';
            
            const select = document.createElement('select');
            select.name = 'steps[]';
            select.className = 'flex-1 p-2 border border-gray-200 rounded text-sm';
            select.required = true;
            
            Object.keys(availableEvents).forEach(value => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = availableEvents[value];
                select.appendChild(option);
            });
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-step px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200';
            removeBtn.textContent = '×';
            removeBtn.onclick = function() { removeStep(this); };
            
            stepDiv.appendChild(select);
            stepDiv.appendChild(removeBtn);
            stepsContainer.appendChild(stepDiv);
        });
    }

    window.removeStep = function(btn) {
        if (stepsContainer && stepsContainer.children.length > 1) {
            btn.closest('.step-item').remove();
        } else if (stepsContainer) {
            alert('At least one step is required');
        }
    };
})();
