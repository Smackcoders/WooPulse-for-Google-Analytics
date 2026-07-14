document.addEventListener('DOMContentLoaded', function () {
    const dataTypeSelect = document.getElementById('data_type');
    if (dataTypeSelect) {
        dataTypeSelect.addEventListener('change', function () {
            const customOptions = document.getElementById('custom-report-options');
            if (customOptions) {
                if (this.value === 'custom_report') {
                    customOptions.style.display = 'block';
                } else {
                    customOptions.style.display = 'none';
                }
            }
        });
    }
});
