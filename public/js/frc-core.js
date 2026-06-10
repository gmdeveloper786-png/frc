/**
 * Shared UI helpers: confirm dialogs, optional fetch CSRF header.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var passToggle = event.target.closest('[data-pass-toggle]');
        if (passToggle) {
            event.preventDefault();
            var inputId = passToggle.getAttribute('data-pass-toggle');
            var iconId = passToggle.getAttribute('data-pass-icon');
            var input = inputId ? document.getElementById(inputId) : null;
            var icon = iconId ? document.getElementById(iconId) : passToggle.querySelector('i');
            if (!input) {
                return;
            }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
                icon.classList.toggle('fa-regular', !show);
                icon.classList.toggle('fa-solid', show);
            }
            passToggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            return;
        }

        var printTrigger = event.target.closest('[data-print-page]');
        if (printTrigger) {
            event.preventDefault();
            window.print();
            return;
        }

        var trigger = event.target.closest('[data-confirm]');
        if (!trigger) {
            return;
        }

        var form = trigger.closest('form');
        if (!form) {
            return;
        }

        var message = trigger.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && typeof window.fetch === 'function') {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var headers = new Headers(init.headers || {});
            if (!headers.has('X-CSRF-TOKEN')) {
                headers.set('X-CSRF-TOKEN', csrfMeta.getAttribute('content') || '');
            }
            init.headers = headers;

            return nativeFetch(input, init);
        };
    }
})();
