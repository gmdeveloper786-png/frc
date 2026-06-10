@php
    $isEdit = filter_var($isEdit ?? false, FILTER_VALIDATE_BOOLEAN);
    $excludeEnrollmentId = $excludeEnrollmentId ?? null;
    $initialSchedules = $initialSchedules ?? [];
    $initialServiceId = $initialServiceId ?? null;
    $therapistIdInit = $isEdit && isset($enrollment) ? $enrollment->therapist_id : null;
    $therapistNameInit = $therapistNameInit ?? ($isEdit && isset($enrollment) ? $enrollment->therapist?->full_name : null);
    $branchCityMap = $enrollmentPricing['branch_city_map'] ?? [];
    $citySessionPrices = $enrollmentPricing['city_session_prices'] ?? [];
@endphp
<script nonce="{{ $cspNonce }}">
window.FRC_HIGH_DISCOUNT_THRESHOLD = {{ (float) ($frc['high_discount_threshold'] ?? 50) }};
const branchCityMap = @json($branchCityMap);
const citySessionPrices = @json($citySessionPrices);
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
    applySessionPriceForBranch();
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

function sessionPriceForBranch(branchId) {
    if (!branchId) return null;
    const city = branchCityMap[branchId] ?? branchCityMap[String(branchId)];
    if (!city) return null;
    let price = citySessionPrices[city];
    if (price == null) {
        const key = Object.keys(citySessionPrices).find(k => k.toLowerCase() === String(city).toLowerCase());
        if (key) price = citySessionPrices[key];
    }
    return price != null ? Number(price) : null;
}

function applySessionPriceForBranch() {
    const b = document.getElementById('branchSelect');
    const priceEl = document.getElementById('pricePerSession');
    const hint = document.getElementById('sessionPriceHint');
    if (!b || !priceEl) return;
    const price = sessionPriceForBranch(b.value);
    const city = branchCityMap[b.value] ?? branchCityMap[String(b.value)];
    if (price == null) {
        priceEl.value = 0;
        if (hint) {
            hint.textContent = city
                ? `No session price configured for ${city} in System Settings — set it under System Settings → City session pricing.`
                : 'Select a branch to load the session price.';
        }
        recalculate();
        return;
    }
    priceEl.value = price;
    if (hint) {
        hint.textContent = `Set from ${city} city rate (PKR ${price.toLocaleString('en-PK')} per session). Change via System Settings.`;
    }
    recalculate();
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
        const firstChildHidden = document.querySelector('#enrollmentChildPickerHidden input[name="child_ids[]"]')
            || document.querySelector('.approved-child-picker-hidden input[name="child_ids[]"]');
        const childIdVal = (childEl && childEl.value) ? childEl.value : (firstChildHidden ? firstChildHidden.value : '');
        if (childIdVal) {
            occParams.set('child_id', String(childIdVal));
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
        if (document.getElementById('scheduleStartDate')?.value) {
            syncStartDateToScheduleDay({ autoAdd: reset });
        }
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

function bindScheduleRow(row, idx) {
    const daySel = row.querySelector(`#daySelect${idx}`) || row.querySelector('select[name*="[day]"]');
    const slotSel = row.querySelector(`#slotSelect${idx}`) || row.querySelector('select[name*="[time_slot]"]');
    const removeBtn = row.querySelector('[data-remove-row]');
    if (daySel) {
        daySel.addEventListener('change', () => onScheduleDayChange(idx));
    }
    if (slotSel) {
        slotSel.addEventListener('change', () => onScheduleSlotChange(idx));
    }
    removeBtn?.addEventListener('click', () => removeRow(removeBtn));
}

function addScheduleRow() {
    const idx = rowIndex++;
    const row = document.createElement('div');
    row.className = 'schedule-row';
    row.innerHTML = `
        <div>
            <label>Day</label>
            <select name="schedules[${idx}][day]" class="form-control" id="daySelect${idx}">
                <option value="">Select Day</option>
                ${availableDays.map(d => `<option value="${d}">${d}</option>`).join('')}
            </select>
        </div>
        <div>
            <label>Time Slot</label>
            <select name="schedules[${idx}][time_slot]" class="form-control" id="slotSelect${idx}">
                <option value="">Select Day First</option>
            </select>
        </div>
        <div>
            <button type="button" data-remove-row style="background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;margin-top:20px;"><i class="fa-solid fa-minus"></i></button>
        </div>
    `;
    document.getElementById('scheduleRows').appendChild(row);
    bindScheduleRow(row, idx);
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

    const subtotal = Math.round(price * sessions);
    const discAmt  = Math.round(subtotal * (discPct / 100));
    const total    = Math.max(0, subtotal - discAmt);

    document.getElementById('calcSessions').textContent = sessions;
    document.getElementById('calcSubtotal').textContent = formatMoneyAmount(subtotal);
    document.getElementById('calcDiscount').textContent = formatMoneyAmount(discAmt);
    document.getElementById('calcTotal').textContent    = formatMoneyAmount(total);
}

function formatMoneyAmount(amount) {
    const value = Math.round(Number(amount) || 0);

    return value.toLocaleString('en', { maximumFractionDigits: 0, minimumFractionDigits: 0 });
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
const jsWeekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function formatDateHint(d) {
    if (!d || Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function dayNameFromYmd(ymd) {
    const d = new Date(ymd + 'T12:00:00');
    if (Number.isNaN(d.getTime())) return '';
    return jsWeekdayNames[d.getDay()] || '';
}

function findAvailableDayMatch(dayName) {
    const lower = String(dayName || '').trim().toLowerCase();
    if (!lower) return null;
    return (availableDays || []).find(d => String(d).trim().toLowerCase() === lower) || null;
}

function getSelectedScheduleDays() {
    const days = [];
    document.querySelectorAll('#scheduleRows select[name*="[day]"]').forEach(sel => {
        if (sel.value) days.push(sel.value);
    });
    return days;
}

function scheduleHasDay(dayName) {
    const lower = String(dayName || '').trim().toLowerCase();
    return getSelectedScheduleDays().some(d => String(d).trim().toLowerCase() === lower);
}

function isScheduleStartAligned() {
    const startInput = document.getElementById('scheduleStartDate');
    if (!startInput?.value) {
        return true;
    }
    const matchedDay = findAvailableDayMatch(dayNameFromYmd(startInput.value));
    return !!matchedDay && scheduleHasDay(matchedDay);
}

function updateEnrollmentSaveState() {
    const form = document.getElementById('enrollForm');
    const btn = form?.querySelector('button[type="submit"]');
    if (!btn) {
        return;
    }
    const aligned = isScheduleStartAligned();
    btn.disabled = !aligned;
    btn.setAttribute('aria-disabled', aligned ? 'false' : 'true');
    if (!aligned) {
        btn.title = 'Fix the start date and session schedule before saving.';
    } else {
        btn.removeAttribute('title');
    }
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

/** When start date changes, auto-add that weekday to schedule if therapist works that day. */
function syncStartDateToScheduleDay(opts = {}) {
    const autoAdd = opts.autoAdd !== false;
    const startInput = document.getElementById('scheduleStartDate');
    if (!startInput?.value) {
        updateFirstSessionHint();
        return;
    }

    const startYmd = startInput.value;
    const startDayLabel = dayNameFromYmd(startYmd);
    const matchedDay = findAvailableDayMatch(startDayLabel);

    if (!matchedDay) {
        updateFirstSessionHint();
        return;
    }

    if (scheduleHasDay(matchedDay)) {
        updateFirstSessionHint();
        return;
    }

    if (!autoAdd) {
        updateFirstSessionHint();
        return;
    }

    const rows = document.querySelectorAll('#scheduleRows .schedule-row');
    let placed = false;
    for (const row of rows) {
        const dSel = row.querySelector('select[name*="[day]"]');
        if (dSel && !dSel.value) {
            const idx = parseInt(String(dSel.id).replace('daySelect', ''), 10);
            selectOptionIfMissing(dSel, matchedDay, matchedDay);
            updateSlots(idx);
            placed = true;
            break;
        }
    }

    if (!placed) {
        addScheduleRow();
        const idx = rowIndex - 1;
        const dSel = document.getElementById('daySelect' + idx);
        selectOptionIfMissing(dSel, matchedDay, matchedDay);
        updateSlots(idx);
    }

    recalculate();
    updateFirstSessionHint();
}

function updateFirstSessionHint() {
    const hint = document.getElementById('firstSessionHint');
    const startInput = document.getElementById('scheduleStartDate');
    if (!hint || !startInput) return;

    const startYmd = startInput.value;
    if (!startYmd) {
        hint.textContent = 'Pick a start date — if the therapist works that day, it will be added to the schedule automatically.';
        updateEnrollmentSaveState();
        return;
    }

    const startDate = new Date(startYmd + 'T12:00:00');
    const startDayLabel = dayNameFromYmd(startYmd);
    const matchedDay = findAvailableDayMatch(startDayLabel);
    const selectedDays = getSelectedScheduleDays();

    if (!matchedDay) {
        hint.textContent = `${startDayLabel} is not a working day for this therapist — choose another start date or therapist.`;
        updateEnrollmentSaveState();
        return;
    }

    if (!scheduleHasDay(matchedDay)) {
        hint.textContent = `Start date is ${formatDateHint(startDate)} (${matchedDay}). Change the date again to auto-add ${matchedDay}, or select ${matchedDay} in the day list below.`;
        updateEnrollmentSaveState();
        return;
    }

    const anchorFirst = firstMatchingSessionDate(startYmd, matchedDay);
    const sameCalendarDay = anchorFirst
        && anchorFirst.getFullYear() === startDate.getFullYear()
        && anchorFirst.getMonth() === startDate.getMonth()
        && anchorFirst.getDate() === startDate.getDate();

    let text = sameCalendarDay
        ? `First session is on: ${formatDateHint(startDate)} (${matchedDay}).`
        : `The first session: ${formatDateHint(anchorFirst)}.`;

    const otherParts = selectedDays
        .filter(d => String(d).trim().toLowerCase() !== String(matchedDay).trim().toLowerCase())
        .map(day => {
            const first = firstMatchingSessionDate(startYmd, day);
            return first ? `first ${day}: ${formatDateHint(first)}` : null;
        })
        .filter(Boolean);

    if (otherParts.length){
        text += ' Weekly repeat follows from this date.';
    }

    hint.textContent = text;
    updateEnrollmentSaveState();
}

function validateScheduleStartAlignment() {
    const startInput = document.getElementById('scheduleStartDate');
    if (!startInput?.value) {
        return true;
    }

    const startYmd = startInput.value;
    const startDate = new Date(startYmd + 'T12:00:00');
    const startDayLabel = dayNameFromYmd(startYmd);
    const matchedDay = findAvailableDayMatch(startDayLabel);

    if (!matchedDay) {
        alert(`${startDayLabel} is not a working day for this therapist. Choose another start date or therapist.`);
        startInput.focus();
        return false;
    }

    if (!scheduleHasDay(matchedDay)) {
        alert(
            `The schedule must include ${matchedDay} because the first session starts on ${formatDateHint(startDate)}. ` +
            'Change the start date or add that day to the schedule.'
        );
        return false;
    }

    return true;
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
    const therapistSel = document.getElementById('therapistSelect');
    const repeatWeekly = document.getElementById('repeatWeekly');

    if (b) {
        b.addEventListener('change', () => {
            applySessionPriceForBranch();
            if (b.tagName === 'SELECT' && b.value) {
                loadTherapists(b.value);
            }
        });
    }
    svc?.addEventListener('change', onEnrollmentServiceChange);
    therapistSel?.addEventListener('change', loadScheduleOptions);
    document.getElementById('addScheduleRowBtn')?.addEventListener('click', addScheduleRow);
    repeatWeekly?.addEventListener('change', () => {
        toggleDuration();
        recalculate();
    });
    document.getElementById('durationValue')?.addEventListener('input', recalculate);
    document.getElementById('durationUnit')?.addEventListener('change', recalculate);
    document.getElementById('discountPct')?.addEventListener('input', function () {
        recalculate();
        checkHighDiscount();
    });
    const firstScheduleRow = document.querySelector('#scheduleRows .schedule-row');
    if (firstScheduleRow) {
        bindScheduleRow(firstScheduleRow, 0);
    }

    document.querySelectorAll('[data-child-select-sync]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (typeof onChildSelectChange === 'function') {
                onChildSelectChange();
            }
        });
    });
    if (b?.value) {
        applySessionPriceForBranch();
    }
    const form = document.getElementById('enrollForm');
    const startDate = document.getElementById('scheduleStartDate');
    if (startDate) {
        startDate.addEventListener('change', () => syncStartDateToScheduleDay({ autoAdd: true }));
        syncStartDateToScheduleDay({ autoAdd: false });
    }
    document.getElementById('scheduleRows')?.addEventListener('change', (e) => {
        if (e.target?.name?.includes('[day]') || e.target?.name?.includes('[time_slot]')) {
            updateFirstSessionHint();
        }
    });

    if (isEdit && b?.value && therapistIdInit) {
        if (svc && initialServiceId != null) svc.value = String(initialServiceId);
        await loadTherapists(b.value, { resetSchedules: false });
        ensureTherapistSelected();
        await loadScheduleOptions({ resetSchedules: false });
        await applyInitialSchedulesFromEdit();
        checkHighDiscount();
        if (repeatWeekly && repeatWeekly.checked) toggleDuration();
        recalculate();
    } else if (b?.value && svc?.value) {
        await loadTherapists(b.value);
    }

    form.addEventListener('submit', function(e) {
        if (!validateScheduleStartAlignment()) {
            e.preventDefault();
            return false;
        }

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
