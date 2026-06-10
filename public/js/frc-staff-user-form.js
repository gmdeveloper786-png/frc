/**
 * Super Admin staff user form: show branch field only for Admin role.
 */
(function () {
    'use strict';

    var roleSelect = document.getElementById('staffRoleSelect');
    var branchField = document.getElementById('staffBranchField');
    var branchSelect = document.getElementById('staffBranchSelect');

    if (!roleSelect || !branchField) {
        return;
    }

    function syncBranchField() {
        var isAdmin = roleSelect.value === 'admin';
        branchField.style.display = isAdmin ? '' : 'none';

        if (branchSelect) {
            branchSelect.required = isAdmin;
            if (!isAdmin) {
                branchSelect.value = '';
            }
        }
    }

    roleSelect.addEventListener('change', syncBranchField);
    syncBranchField();
})();
