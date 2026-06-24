@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-document-remove]').forEach(function (input) {
        const card = input.closest('[data-document-card]');
        if (!card) return;

        const syncMarked = function () {
            card.classList.toggle('frc-document-card--marked', input.checked);
        };

        input.addEventListener('change', syncMarked);
        syncMarked();
    });

    const fileInput = document.querySelector('[data-document-upload-input]');
    const queue = document.querySelector('[data-document-upload-queue]');
    const zone = document.querySelector('[data-document-upload-zone]');

    if (!fileInput || !queue || !zone) return;

    const renderQueue = function () {
        queue.innerHTML = '';
        const files = Array.from(fileInput.files || []);

        if (files.length === 0) {
            queue.hidden = true;
            zone.classList.remove('frc-file-upload__zone--has-files');
            return;
        }

        queue.hidden = false;
        zone.classList.add('frc-file-upload__zone--has-files');

        files.forEach(function (file) {
            const item = document.createElement('li');
            item.className = 'frc-file-upload__queue-item';

            const icon = document.createElement('i');
            icon.className = 'fa-solid ' + (
                file.type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file-image'
            );
            icon.setAttribute('aria-hidden', 'true');

            const name = document.createElement('span');
            name.className = 'frc-file-upload__queue-name';
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'frc-file-upload__queue-size';
            size.textContent = file.size < 1024 * 1024
                ? Math.max(1, Math.round(file.size / 1024)) + ' KB'
                : (file.size / (1024 * 1024)).toFixed(1) + ' MB';

            item.appendChild(icon);
            item.appendChild(name);
            item.appendChild(size);
            queue.appendChild(item);
        });
    };

    fileInput.addEventListener('change', renderQueue);

    ['dragenter', 'dragover'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
            event.preventDefault();
            zone.classList.add('frc-file-upload__zone--drag');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
            event.preventDefault();
            zone.classList.remove('frc-file-upload__zone--drag');
        });
    });

    zone.addEventListener('drop', function (event) {
        if (!event.dataTransfer?.files?.length) return;
        fileInput.files = event.dataTransfer.files;
        renderQueue();
    });
});
</script>
@endpush
