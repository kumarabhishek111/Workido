/**
 * Workido - Global Application Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggleBtn && appSidebar) {
        let backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop d-none';
        document.body.appendChild(backdrop);

        sidebarToggleBtn.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
            backdrop.classList.toggle('d-none');
        });

        backdrop.addEventListener('click', function () {
            appSidebar.classList.remove('show');
            backdrop.classList.add('d-none');
        });
    }

    // 2. Auto-dismiss Flash Alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            } catch (e) {
                alert.style.display = 'none';
            }
        }, 5000);
    });

    // 3. Client-Side Instant Table Search / Filtering
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        const tableSelector = input.getAttribute('data-table-search');
        const targetTable = document.querySelector(tableSelector);

        if (targetTable) {
            input.addEventListener('keyup', function () {
                const query = this.value.toLowerCase().trim();
                const rows = targetTable.querySelectorAll('tbody tr');

                rows.forEach(function (row) {
                    // Ignore empty or no-record rows
                    if (row.classList.contains('no-filter')) return;
                    const text = row.innerText.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });

    // 4. Confirm Delete Handler
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this record? This action cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});
