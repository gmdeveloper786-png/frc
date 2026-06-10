<script nonce="{{ $cspNonce }}">
const assessmentTodayDate = @json(now()->format('Y-m-d'));

function syncAssessmentTimeMin() {
    const dateInput = document.getElementById('assessDate');
    const timeInput = document.getElementById('assessTime');
    if (!dateInput || !timeInput) return;

    if (dateInput.value === assessmentTodayDate) {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        timeInput.min = `${hh}:${mm}`;
        if (timeInput.value && timeInput.value < timeInput.min) {
            timeInput.value = timeInput.min;
        }
    } else {
        timeInput.removeAttribute('min');
    }
}

function updateAssessmentDay(dateStr) {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayDisplay = document.getElementById('dayDisplay');
    if (!dateStr || !dayDisplay) return;
    const d = new Date(dateStr + 'T12:00:00');
    dayDisplay.value = days[d.getDay()];
    syncAssessmentTimeMin();
}

document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('assessDate');
    const timeInput = document.getElementById('assessTime');
    if (dateInput) {
        dateInput.addEventListener('change', function () {
            updateAssessmentDay(this.value);
        });
    }
    if (timeInput) {
        timeInput.addEventListener('change', syncAssessmentTimeMin);
        timeInput.addEventListener('input', syncAssessmentTimeMin);
    }
    syncAssessmentTimeMin();
    if (dateInput?.value) {
        updateAssessmentDay(dateInput.value);
    }
});
</script>
