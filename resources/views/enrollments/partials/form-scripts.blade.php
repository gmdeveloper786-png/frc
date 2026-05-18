@php
    $isEdit = filter_var($isEdit ?? false, FILTER_VALIDATE_BOOLEAN);
    $excludeEnrollmentId = $excludeEnrollmentId ?? null;
    $initialSchedules = $initialSchedules ?? [];
    $initialServiceId = $initialServiceId ?? null;
    $therapistIdInit = $isEdit && isset($enrollment) ? $enrollment->therapist_id : null;
    $therapistNameInit = $therapistNameInit ?? ($isEdit && isset($enrollment) ? $enrollment->therapist?->full_name : null);
@endphp
<script>
window.FRC_HIGH_DISCOUNT_THRESHOLD = {{ (float) ($frc['high_discount_threshold'] ?? 50) }};
let rowIndex = 0;
let availableDays = [];
let availableSlots = [];
/** Keys: lowercaseDay|trimmedSlot — matches server occupied pairs */
let occupiedSlotKeys = new Set();

const isEdit = @json($isEdit);
const excludeEnrollmentId = @json($excludeEnrollmentId);
const initialSchedules = @json($initialSchedules);
const therapistIdInit = @json($therapistIdInit);
const therapistNameInit = @json($therapistNameInit);
const initialServiceId = @json($initialServiceId);

function occupiedSlotKey(day, slot) {
    return `${String(day || '').trim().toLowerCase()}|${String(slot || '').trim()}`;
}

function isSlotOccupied(day, slot) {
    return occupiedSlotKeys.has(occupiedSlotKey(day, slot));
}

async function loadTherapists(branchId, opts = {}) {
    const reset = opts.resetSchedules !== false;
    const sel = document.getElementById('therapistSelect');
    const svcSel = document.getElementById('serviceSelect');
    sel.innerHTML = '<option value="">Loading...</option>';
    if (!branchId) {
        sel.innerHTML = '<option value="">Select Branch First</option>';
        if (reset) resetSchedules();
        return;
    }
    const serviceIds = opts.serviceIds ?? (svcSel && svcSel.value ? [svcSel.value] : []);
    if (!serviceIds.length) {
        sel.innerHTML = '<option value="">Select service first</option>';
        if (reset) resetSchedules();
        return;
    }

    const qs = new URLSearchParams();
    qs.set('service_match', 'any');
    serviceIds.forEach(id => qs.append('service_ids[]', id));

    const res = await fetch(`/ajax/branches/${branchId}/therapists?${qs}`, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        console.error('Therapists fetch failed', res.status, data);
        sel.innerHTML = '<option value="">Unable to load therapists — refresh & try again</option>';
        return;
    }
    sel.innerHTML = '<option value="">Select Therapist</option>';
    (data.data || []).forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.full_name;
        sel.appendChild(opt);
    });
    if (reset) resetSchedules();
}

function ensureTherapistSelected() {
    if (!therapistIdInit) return;
    const sel = document.getElementById('therapistSelect');
    if (!sel) return;
    const tid = String(therapistIdInit);
    if (!Array.from(sel.options).some(o => o.value === tid)) {
        const opt = document.createElement('option');
        opt.value = tid;
        opt.textContent = therapistNameInit || ('Therapist #' + tid);
        sel.appendChild(opt);
    }
    sel.value = tid;
}

function selectOptionIfMissing(selectEl, value, label) {
    if (!selectEl || value == null || value === '') return;
    const v = String(value);
    if (!Array.from(selectEl.options).some(o => o.value === v)) {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = label || v;
        selectEl.appendChild(opt);
    }
    selectEl.value = v;
}

function onEnrollmentServiceChange() {
    const b = document.getElementById('branchSelect');
    if (b?.value) loadTherapists(b.value);
}

/** Re-fetch occupied slots when child changes (same calendar slot cannot overlap across therapists). */
function onChildSelectChange() {
    const therapistId = document.getElementById('therapistSelect')?.value;
    if (therapistId) {
        loadScheduleOptions({ resetSchedules: false });
    }
}

async function loadScheduleOptions(opts = {}) {
    const reset = opts.resetSchedules !== false;
    const therapistId = document.getElementById('therapistSelect').value;
    if (!therapistId) {
        occupiedSlotKeys = new Set();
        return;
    }
    try {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        };
        const occParams = new URLSearchParams();
        if (excludeEnrollmentId != null && excludeEnrollmentId !== '') {
            occParams.set('exclude_enrollment', String(excludeEnrollmentId));
        }
        const childEl = document.getElementById('childSelect') || document.querySelector('input[name="child_id"]');
        if (childEl && childEl.value) {
            occParams.set('child_id', String(childEl.value));
        }
        const occQuery = occParams.toString();
        const occUrl = `/ajax/therapists/${therapistId}/occupied-slots` + (occQuery ? `?${occQuery}` : '');
        const [dRes, sRes, oRes] = await Promise.all([
            fetch(`/ajax/therapists/${therapistId}/available-days`, { credentials: 'same-origin', headers }),
            fetch(`/ajax/therapists/${therapistId}/available-slots`, { credentials: 'same-origin', headers }),
            fetch(occUrl, { credentials: 'same-origin', headers }),
        ]);
        if (!dRes.ok || !sRes.ok) {
            console.error('Schedule options fetch failed', dRes.status, sRes.status);
        }
        const dJson = await dRes.json().catch(() => ({}));
        const sJson = await sRes.json().catch(() => ({}));
        const oJson = await oRes.json().catch(() => ({}));
        availableDays = dJson.data || [];
        availableSlots = sJson.data || [];
        occupiedSlotKeys = new Set();
        for (const o of (oJson.data || [])) {
            occupiedSlotKeys.add(occupiedSlotKey(o.day, o.time_slot));
        }
        if (reset) resetSchedules();
    } catch(e) { console.error(e); }
}

function resetSchedules() {
    document.getElementById('scheduleRows').innerHTML = '';
    rowIndex = 0;
    addScheduleRow();
}

/** Same day + time_slot must not appear twice */
function schedulePairTaken(skipDaySelectId, day, slot) {
    if (!day || !slot) return false;
    const rows = document.querySelectorAll('#scheduleRows .schedule-row');
    for (const row of rows) {
        const dSel = row.querySelector('select[id^="daySelect"]');
        const sSel = row.querySelector('select[id^="slotSelect"]');
        if (!dSel || !sSel) continue;
        if (dSel.id === skipDaySelectId) continue;
        if (dSel.value === day && sSel.value === slot) return true;
    }
    return false;
}

function isBreakSlot(slotValue) {
    if (!slotValue) return false;
    const found = (availableSlots || []).find(s => String(s.slot) === String(slotValue));
    return !!(found && (found.disabled === true || found.disabled === 1 || found.disabled === 'true'));
}

function onScheduleSlotChange(idx) {
    const daySel = document.getElementById('daySelect' + idx);
    const slotSel = document.getElementById('slotSelect' + idx);
    if (!daySel || !slotSel) return;
    const day = daySel.value;
    const slot = slotSel.value;
    if (!day || !slot) {
        recalculate();
        return;
    }
    if (isBreakSlot(slot)) {
        alert('This is a therapist break time — this slot cannot be booked.');
        slotSel.value = '';
        recalculate();
        return;
    }
    if (isSlotOccupied(day, slot)) {
        alert('This slot is unavailable (therapist busy or another program is booked at this time) — please choose another slot.');
        slotSel.value = '';
        recalculate();
        return;
    }
    if (schedulePairTaken(daySel.id, day, slot)) {
        alert('The same day and time slot cannot be added more than once.');
        slotSel.value = '';
        recalculate();
        return;
    }
    recalculate();
}

function addScheduleRow() {
    const idx = rowIndex++;
    const row = document.createElement('div');
    row.className = 'schedule-row';
    row.innerHTML = `
        <div>
            <label>Day</label>
            <select name="schedules[${idx}][day]" class="form-control" id="daySelect${idx}" onchange="onScheduleDayChange(${idx})">
                <option value="">Select Day</option>
                ${availableDays.map(d => `<option value="${d}">${d}</option>`).join('')}
            </select>
        </div>
        <div>
            <label>Time Slot</label>
            <select name="schedules[${idx}][time_slot]" class="form-control" id="slotSelect${idx}" onchange="onScheduleSlotChange(${idx})">
                <option value="">Select Day First</option>
            </select>
        </div>
        <div>
            <button type="button" onclick="removeRow(this)" style="background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;margin-top:20px;"><i class="fa-solid fa-minus"></i></button>
        </div>
    `;
    document.getElementById('scheduleRows').appendChild(row);
    recalculate();
}

function onScheduleDayChange(idx) {
    updateSlots(idx);
}

function updateSlots(idx) {
    const daySel = document.getElementById('daySelect' + idx);
    const selectedDay = daySel ? daySel.value : '';
    const slotSel = document.getElementById('slotSelect' + idx);
    slotSel.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select slot';
    slotSel.appendChild(placeholder);

    const list = availableSlots || [];
    if (list.length === 0) {
        const none = document.createElement('option');
        none.value = '';
        none.textContent = 'No slots available';
        none.disabled = true;
        slotSel.appendChild(none);
    } else {
        for (const s of list) {
            const opt = document.createElement('option');
            opt.value = s.slot || '';
            const disBreak = s.disabled === true || s.disabled === 1 || s.disabled === 'true';
            const disBooked = !!(selectedDay && isSlotOccupied(selectedDay, s.slot || ''));
            const dis = disBreak || disBooked;
            opt.disabled = dis;
            let label = s.slot || '';
            if (disBreak) label += ' (Break)';
            else if (disBooked) label += ' (Booked)';
            opt.textContent = label;
            if (dis) {
                opt.style.color = '#888';
                opt.style.background = '#f5f5f5';
            }
            slotSel.appendChild(opt);
        }
    }
    slotSel.onchange = () => onScheduleSlotChange(idx);
    recalculate();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.schedule-row');
    if (rows.length <= 1) return;
    btn.closest('.schedule-row').remove();
    recalculate();
}

/** Mirrors App\Services\FeeCalculationService::calculateTotalSessions */
function calculateTotalSessions(baseCount, repeatWeekly, durationValue, durationUnit) {
    const base = Math.max(0, parseInt(baseCount, 10) || 0);
    if (!repeatWeekly || !durationValue || durationValue < 1 || !durationUnit) {
        return base;
    }
    let weeks;
    switch (durationUnit) {
        case 'weekly':  weeks = durationValue; break;
        case 'monthly': weeks = durationValue * 4; break;
        case 'yearly':  weeks = durationValue * 52; break;
        default:        weeks = durationValue;
    }
    return base * weeks;
}

function recalculate() {
    const price   = parseFloat(document.getElementById('pricePerSession').value) || 0;
    const discPct = parseFloat(document.getElementById('discountPct').value) || 0;
    const baseRows = document.querySelectorAll('#scheduleRows .schedule-row').length;

    const repeatCb = document.getElementById('repeatWeekly');
    const repeat = repeatCb && repeatCb.checked;
    const dvRaw = document.getElementById('durationValue');
    const dv = dvRaw ? parseInt(dvRaw.value, 10) : NaN;
    const unitEl = document.getElementById('durationUnit');
    const unit = unitEl ? unitEl.value : 'weekly';

    const sessions = calculateTotalSessions(baseRows, repeat, dv, unit);

    const subtotal = Math.round(price * sessions * 100) / 100;
    const discAmt  = Math.round(subtotal * (discPct / 100) * 100) / 100;
    const total    = Math.round((subtotal - discAmt) * 100) / 100;

    document.getElementById('calcSessions').textContent = sessions;
    document.getElementById('calcSubtotal').textContent = subtotal.toLocaleString('en', { minimumFractionDigits: 2 });
    document.getElementById('calcDiscount').textContent = discAmt.toLocaleString('en', { minimumFractionDigits: 2 });
    document.getElementById('calcTotal').textContent    = total.toLocaleString('en', { minimumFractionDigits: 2 });
}

function checkHighDiscount() {
    const pct = parseFloat(document.getElementById('discountPct').value) || 0;
    const threshold = window.FRC_HIGH_DISCOUNT_THRESHOLD ?? 50;
    document.getElementById('discountSection').style.display = pct > threshold ? 'block' : 'none';
}

function toggleDuration() {
    const cb = document.getElementById('repeatWeekly');
    document.getElementById('durationSection').style.display = cb.checked ? 'block' : 'none';
}

const dayNameToIso = {
    monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6, sunday: 7,
};

function formatDateHint(d) {
    if (!d || Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

function firstMatchingSessionDate(startYmd, dayName) {
    const iso = dayNameToIso[String(dayName || '').trim().toLowerCase()];
    if (!startYmd || !iso) return null;
    const start = new Date(startYmd + 'T12:00:00');
    if (Number.isNaN(start.getTime())) return null;
    for (let i = 0; i < 14; i++) {
        const c = new Date(start);
        c.setDate(start.getDate() + i);
        const dow = c.getDay() === 0 ? 7 : c.getDay();
        if (dow === iso) return c;
    }
    return null;
}

function updateFirstSessionHint() {
    const hint = document.getElementById('firstSessionHint');
    const startInput = document.getElementById('scheduleStartDate');
    if (!hint || !startInput) return;
    const startYmd = startInput.value;
    const daySel = document.querySelector('#scheduleRows select[name*="[day]"]');
    const day = daySel?.value || '';
    const first = firstMatchingSessionDate(startYmd, day);
    if (first && day) {
        hint.textContent = `The first ${day} session: ${formatDateHint(first)}. The weekly repeat will follow after that.`;
    } else if (startYmd) {
        hint.textContent = 'Select a day — the first session will be on the first matching weekday after the selected date.';
    } else {
        hint.textContent = 'The first session will be on the first matching weekday after the selected date; the weekly repeat will follow after that.';
    }
}

async function applyInitialSchedulesFromEdit() {
    const container = document.getElementById('scheduleRows');
    if (!container || !initialSchedules.length) {
        if (container && !container.querySelector('.schedule-row')) {
            addScheduleRow();
        }
        recalculate();
        return;
    }
    container.innerHTML = '';
    rowIndex = 0;
    for (const row of initialSchedules) {
        addScheduleRow();
        const idx = rowIndex - 1;
        const d = document.getElementById('daySelect' + idx);
        selectOptionIfMissing(d, row.day || '', row.day || '');
        updateSlots(idx);
        const slot = document.getElementById('slotSelect' + idx);
        selectOptionIfMissing(slot, row.time_slot || '', row.time_slot || '');
    }
    recalculate();
    updateFirstSessionHint();
}

document.addEventListener('DOMContentLoaded', async function() {
    const b = document.getElementById('branchSelect');
    const svc = document.getElementById('serviceSelect');
    const form = document.getElementById('enrollForm');
    const startDate = document.getElementById('scheduleStartDate');
    if (startDate) {
        startDate.addEventListener('change', updateFirstSessionHint);
        updateFirstSessionHint();
    }
    document.getElementById('scheduleRows')?.addEventListener('change', (e) => {
        if (e.target?.name?.includes('[day]')) updateFirstSessionHint();
    });

    if (isEdit && b?.value && therapistIdInit) {
        if (svc && initialServiceId != null) svc.value = String(initialServiceId);
        await loadTherapists(b.value, { resetSchedules: false });
        ensureTherapistSelected();
        await loadScheduleOptions({ resetSchedules: false });
        await applyInitialSchedulesFromEdit();
        checkHighDiscount();
        const cb = document.getElementById('repeatWeekly');
        if (cb && cb.checked) toggleDuration();
        recalculate();
    } else if (b?.value && svc?.value) {
        await loadTherapists(b.value);
    }

    form.addEventListener('submit', function(e) {
        const seen = new Set();
        for (const row of document.querySelectorAll('#scheduleRows .schedule-row')) {
            const d = row.querySelector('select[name*="[day]"]');
            const s = row.querySelector('select[name*="[time_slot]"]');
            if (!d || !s || !d.value || !s.value) continue;
            if (typeof isBreakSlot === 'function' && isBreakSlot(s.value)) {
                e.preventDefault();
                alert('You cannot select a break time slot — please choose another slot.');
                return false;
            }
            if (typeof isSlotOccupied === 'function' && isSlotOccupied(d.value, s.value)) {
                e.preventDefault();
                alert('This slot is already occupied by another program — please choose another day / time.');
                return false;
            }
            const key = d.value + '|' + s.value;
            if (seen.has(key)) {
                e.preventDefault();
                alert('The same day and time slot cannot be added more than once.');
                return false;
            }
            seen.add(key);
        }
    });
});

// Init
recalculate();
checkHighDiscount();
if (document.getElementById('repeatWeekly').checked) toggleDuration();
</script>
