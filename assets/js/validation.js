/**
 * Workido - Form Validation Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            let isValid = true;

            // Validate Mobile Numbers (Indian / International 10 digits standard)
            const mobileInput = form.querySelector('input[type="tel"], input[name="mobile_number"], input[name="client_contact"]');
            if (mobileInput && mobileInput.value.trim() !== '') {
                const phoneClean = mobileInput.value.replace(/[^0-9]/g, '');
                if (phoneClean.length < 10 || phoneClean.length > 15) {
                    mobileInput.setCustomValidity('Please enter a valid mobile number (10 to 15 digits).');
                    isValid = false;
                } else {
                    mobileInput.setCustomValidity('');
                }
            }

            // Validate Date ranges: Start Date <= Expected Completion Date
            const startDateInput = form.querySelector('input[name="start_date"]');
            const endDateInput = form.querySelector('input[name="expected_completion_date"]');
            if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
                if (new Date(startDateInput.value) > new Date(endDateInput.value)) {
                    endDateInput.setCustomValidity('Expected completion date cannot be earlier than start date.');
                    isValid = false;
                } else {
                    endDateInput.setCustomValidity('');
                }
            }

            if (!form.checkValidity() || !isValid) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
});
