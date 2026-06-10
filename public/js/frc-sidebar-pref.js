/**
 * Apply saved sidebar collapse state before paint (loaded in <head>).
 */
(function () {
    try {
        if (localStorage.getItem('frc.sidebar.collapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    } catch (e) {
        /* localStorage unavailable */
    }
})();
