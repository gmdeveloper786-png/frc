/**
 * App shell: desktop sidebar collapse + mobile drawer.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'frc.sidebar.collapsed';
    var html = document.documentElement;
    var sidebar = document.getElementById('frcSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    var toggleBtn = document.getElementById('sidebarToggle');

    function isMobile() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function setCollapsed(collapsed) {
        html.classList.toggle('sidebar-collapsed', collapsed);
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (e) {
            /* ignore */
        }
    }

    function openMobileSidebar() {
        if (!sidebar) {
            return;
        }
        sidebar.classList.add('open');
        if (backdrop) {
            backdrop.classList.add('show');
        }
        document.body.classList.add('sidebar-mobile-open');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
    }

    function closeMobileSidebar() {
        if (!sidebar) {
            return;
        }
        sidebar.classList.remove('open');
        if (backdrop) {
            backdrop.classList.remove('show');
        }
        document.body.classList.remove('sidebar-mobile-open');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
    }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            if (isMobile()) {
                return;
            }
            setCollapsed(!html.classList.contains('sidebar-collapsed'));
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (!isMobile()) {
                return;
            }
            if (sidebar && sidebar.classList.contains('open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeMobileSidebar();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
})();
