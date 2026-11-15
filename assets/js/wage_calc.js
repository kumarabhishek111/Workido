/**
 * Workido - Live Wage Calculation Engine
 */

document.addEventListener('DOMContentLoaded', function () {
    const workerSelect = document.getElementById('worker_id');
    const paymentTypeSelect = document.getElementById('payment_type');
    const dailyWageInput = document.getElementById('daily_wage');
    const workingDaysInput = document.getElementById('working_days');
    const additionalPaymentInput = document.getElementById('additional_payment');
    const deductionsInput = document.getElementById('deductions');
    const totalPayableInput = document.getElementById('total_payable');
    const amountPaidInput = document.getElementById('amount_paid');
    const paymentStatusSelect = document.getElementById('payment_status');

    // Preview Elements
    const previewBaseAmount = document.getElementById('preview_base_amount');
    const previewAdditional = document.getElementById('preview_additional');
    const previewDeductions = document.getElementById('preview_deductions');
    const previewTotalPayable = document.getElementById('preview_total_payable');
    const previewPendingBalance = document.getElementById('preview_pending_balance');
    const dailyWageGroup = document.getElementById('daily_wage_group');
    const workingDaysGroup = document.getElementById('working_days_group');

    if (!paymentTypeSelect) return;

    // Format currency helper
    function fmt(val) {
        return '₹ ' + (parseFloat(val) || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Worker Selection Change - Auto fill daily wage
    if (workerSelect) {
        workerSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.dailyWage) {
                if (dailyWageInput && (!dailyWageInput.value || dailyWageInput.value == '0.00' || dailyWageInput.value == '0')) {
                    dailyWageInput.value = parseFloat(selectedOpt.dataset.dailyWage).toFixed(2);
                }
            }
            recalculateWages();
        });
    }

    // Toggle Payment Type UI
    function handlePaymentTypeChange() {
        const type = paymentTypeSelect.value;
        if (type === 'Fixed Price') {
            if (workingDaysGroup) {
                workingDaysGroup.style.display = 'none';
            }
            if (dailyWageGroup) {
                const label = dailyWageGroup.querySelector('label');
                if (label) label.innerText = 'Agreed Fixed Price Amount (₹) *';
            }
        } else {
            if (workingDaysGroup) {
                workingDaysGroup.style.display = 'block';
            }
            if (dailyWageGroup) {
                const label = dailyWageGroup.querySelector('label');
                if (label) label.innerText = 'Daily Wage Rate (₹) *';
            }
        }
        recalculateWages();
    }

    paymentTypeSelect.addEventListener('change', handlePaymentTypeChange);

    // Live Calculation Function
    function recalculateWages() {
        const type = paymentTypeSelect ? paymentTypeSelect.value : 'Daily Rate';
        const dailyWage = parseFloat(dailyWageInput ? dailyWageInput.value : 0) || 0;
        const workingDays = parseFloat(workingDaysInput ? workingDaysInput.value : 0) || 0;
        const additional = parseFloat(additionalPaymentInput ? additionalPaymentInput.value : 0) || 0;
        const deductions = parseFloat(deductionsInput ? deductionsInput.value : 0) || 0;

        let baseAmount = 0;
        if (type === 'Daily Rate') {
            baseAmount = dailyWage * workingDays;
        } else {
            baseAmount = dailyWage; // Represents agreed fixed payment
        }

        let totalPayable = (baseAmount + additional) - deductions;
        if (totalPayable < 0) totalPayable = 0;

        if (totalPayableInput) {
            totalPayableInput.value = totalPayable.toFixed(2);
        }

        // Paid amount & pending balance
        const amountPaid = parseFloat(amountPaidInput ? amountPaidInput.value : 0) || 0;
        const pendingBalance = Math.max(0, totalPayable - amountPaid);

        // Update preview badges
        if (previewBaseAmount) previewBaseAmount.innerText = fmt(baseAmount);
        if (previewAdditional) previewAdditional.innerText = '+ ' + fmt(additional);
        if (previewDeductions) previewDeductions.innerText = '- ' + fmt(deductions);
        if (previewTotalPayable) previewTotalPayable.innerText = fmt(totalPayable);
        if (previewPendingBalance) previewPendingBalance.innerText = fmt(pendingBalance);

        // Auto update payment status dropdown if appropriate
        if (paymentStatusSelect && document.activeElement !== paymentStatusSelect) {
            if (amountPaid >= totalPayable && totalPayable > 0) {
                paymentStatusSelect.value = 'Paid';
            } else if (amountPaid > 0 && amountPaid < totalPayable) {
                paymentStatusSelect.value = 'Partially Paid';
            } else if (amountPaid === 0) {
                paymentStatusSelect.value = 'Pending';
            }
        }
    }

    // Attach listeners to input triggers
    [dailyWageInput, workingDaysInput, additionalPaymentInput, deductionsInput, amountPaidInput].forEach(function (el) {
        if (el) {
            el.addEventListener('input', recalculateWages);
            el.addEventListener('change', recalculateWages);
        }
    });

    // Run initial calculation and type setup
    handlePaymentTypeChange();
    recalculateWages();
});
