@php
    $branchCityMap = $enrollmentPricing['branch_city_map'] ?? [];
    $citySessionPrices = $enrollmentPricing['city_session_prices'] ?? [];
@endphp
<script nonce="{{ $cspNonce }}">
window.FRC_HIGH_DISCOUNT_THRESHOLD = {{ (float) ($frc['high_discount_threshold'] ?? 50) }};
const branchCityMap = @json($branchCityMap);
const citySessionPrices = @json($citySessionPrices);

function therapistOptionLabel(t) {
    const name = (t.full_name || '').trim();
    const email = (t.email || '').trim();
    return email ? `${name} — ${email}` : name;
}

function therapistOptionLabelFromParts(name, email) {
    const n = (name || '').trim();
    const e = (email || '').trim();
    return e ? `${n} — ${e}` : n;
}

function occupiedSlotKey(day, slot) {
    return `${String(day || '').trim().toLowerCase()}|${String(slot || '').trim()}`;
}

function formatMoneyAmount(amount) {
    const value = Math.round(Number(amount) || 0);
    return value.toLocaleString('en', { maximumFractionDigits: 0, minimumFractionDigits: 0 });
}

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

const dayNameToIso = {
    monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6, sunday: 7,
};
const jsWeekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function dayNameFromYmd(ymd) {
    const d = new Date(ymd + 'T12:00:00');
    if (Number.isNaN(d.getTime())) return '';
    return jsWeekdayNames[d.getDay()] || '';
}

function formatDateHint(d) {
    if (!d || Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
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

function getSelectedChildIds() {
    const hidden = document.querySelector('#enrollmentChildPickerHidden');
    if (!hidden) return [];
    return Array.from(hidden.querySelectorAll('input[name="child_ids[]"]'))
        .map(el => el.value)
        .filter(Boolean);
}

function getSelectedChildLabels() {
    const selectedWrap = document.getElementById('enrollmentChildPickerSelected');
    if (selectedWrap) {
        const names = Array.from(selectedWrap.querySelectorAll('.badge span'))
            .map(el => el.textContent.trim())
            .filter(Boolean);
        if (names.length) return names;
    }
    const ids = getSelectedChildIds();
    return ids.length ? ids.map(id => `Child #${id}`) : [];
}

function syncLockedChildrenDisplays() {
    const labels = getSelectedChildLabels();
    const text = labels.length
        ? labels.join(', ')
        : 'Same children as the first enrollment — select children above.';
    document.querySelectorAll('[data-locked-children-display] .enrollment-locked-children-list').forEach(el => {
        el.textContent = text;
    });
}

function updateSubmitLabel() {
    const extraCount = document.querySelectorAll('[data-extra-block]').length;
    const label = document.querySelector('[data-submit-label]');
    if (!label) return;
    label.textContent = extraCount > 0
        ? `Create ${extraCount + 1} Enrollments`
        : 'Create Enrollment';
    updateMultiEnrollmentBanner();
}

function updateMultiEnrollmentBanner() {
    const banner = document.getElementById('multiEnrollmentBanner');
    if (!banner) return;
    const extraCount = document.querySelectorAll('[data-extra-block]').length;
    if (extraCount < 1) {
        banner.style.display = 'none';
        return;
    }
    banner.style.display = 'flex';
    const title = document.getElementById('multiEnrollmentBannerTitle');
    if (title) {
        title.textContent = `Creating ${extraCount + 1} enrollments for the same child`;
    }
}

function getScheduleRowValues(row) {
    return {
        day: row.querySelector('[data-day-select]')?.value || '',
        slot: row.querySelector('[data-slot-select]')?.value || '',
    };
}

function refreshOtherBlocksSlotDropdowns(currentBlockEl) {
    (window.enrollmentBlockControllers || []).forEach(c => {
        if (c.blockEl === currentBlockEl) return;
        if (typeof c.refreshSlotDropdowns === 'function') {
            c.refreshSlotDropdowns();
        }
    });
}

/** Same day+slot selected in another enrollment block on this form. */
function isSlotTakenOnForm(day, slot, excludeBlockEl, excludeRow) {
    const key = occupiedSlotKey(day, slot);
    for (const block of document.querySelectorAll('[data-enrollment-block]')) {
        for (const row of block.querySelectorAll('[data-schedule-row]')) {
            if (block === excludeBlockEl && row === excludeRow) continue;
            const values = getScheduleRowValues(row);
            if (values.day && values.slot && occupiedSlotKey(values.day, values.slot) === key) return true;
        }
    }
    return false;
}

function refreshAllBlocksSlotDropdowns() {
    (window.enrollmentBlockControllers || []).forEach(c => {
        if (typeof c.refreshSlotDropdowns === 'function') {
            c.refreshSlotDropdowns();
        }
    });
}

function ensureTherapistSelected(blockEl) {
    const oldId = blockEl?.dataset?.oldTherapistId;
    if (!oldId) return;
    const sel = blockEl.querySelector('[data-therapist-select]');
    if (!sel) return;
    const tid = String(oldId);
    if (!Array.from(sel.options).some(o => o.value === tid)) {
        const opt = document.createElement('option');
        opt.value = tid;
        opt.textContent = 'Therapist #' + tid;
        sel.appendChild(opt);
    }
    sel.value = tid;
}

function resolveScheduleFieldPrefix(blockEl) {
    const attr = blockEl?.getAttribute('data-schedule-prefix');
    if (attr && attr !== 'extra_enrollments[__INDEX__][schedules]') {
        return attr;
    }
    if (blockEl?.hasAttribute('data-extra-block')) {
        const idx = blockEl.getAttribute('data-block-index') || '1';
        return `extra_enrollments[${idx}][schedules]`;
    }
    return 'schedules';
}

function syncScheduleRowFieldNames(blockEl) {
    if (!blockEl) return;
    const prefix = resolveScheduleFieldPrefix(blockEl);
    blockEl.setAttribute('data-schedule-prefix', prefix);
    blockEl.querySelectorAll('[data-schedule-row]').forEach((row, idx) => {
        const daySel = row.querySelector('[data-day-select]');
        const slotSel = row.querySelector('[data-slot-select]');
        if (daySel) daySel.name = `${prefix}[${idx}][day]`;
        if (slotSel) slotSel.name = `${prefix}[${idx}][time_slot]`;
    });
}

function prepareScheduleFieldsForSubmit() {
    document.querySelectorAll('[data-enrollment-block]').forEach(block => {
        syncScheduleRowFieldNames(block);
        block.querySelectorAll('[data-schedule-row]').forEach(row => {
            const daySel = row.querySelector('[data-day-select]');
            const slotSel = row.querySelector('[data-slot-select]');
            if (!daySel?.value || !slotSel?.value) return;
            const dayOpt = daySel.options[daySel.selectedIndex];
            const slotOpt = slotSel.options[slotSel.selectedIndex];
            if (dayOpt?.disabled) dayOpt.disabled = false;
            if (slotOpt?.disabled) slotOpt.disabled = false;
        });
    });
}

function isExtraBlockBlank(block) {
    const branch = block.querySelector('[data-branch-select]')?.value;
    const service = block.querySelector('[data-service-select]')?.value;
    const therapist = block.querySelector('[data-therapist-select]')?.value;
    if (branch || service || therapist) return false;
    for (const row of block.querySelectorAll('[data-schedule-row]')) {
        const values = getScheduleRowValues(row);
        if (values.day || values.slot) return false;
    }
    return true;
}

function cullBlankExtraEnrollmentBlocks() {
    let removed = false;
    document.querySelectorAll('[data-extra-block]').forEach(block => {
        if (isExtraBlockBlank(block)) {
            window.enrollmentBlockControllers = window.enrollmentBlockControllers.filter(c => c.blockEl !== block);
            block.remove();
            removed = true;
        }
    });
    if (removed) {
        reindexExtraEnrollmentBlocks();
        refreshAllBlocksSlotDropdowns();
    }
}

function validateAllBlocksHaveSchedules() {
    const blocks = document.querySelectorAll('[data-enrollment-block]');
    let extraOrdinal = 0;
    for (let i = 0; i < blocks.length; i++) {
        const block = blocks[i];
        let label;
        if (block.hasAttribute('data-extra-block')) {
            extraOrdinal += 1;
            label = `Enrollment ${extraOrdinal + 1}`;
        } else {
            label = 'the first enrollment';
        }
        let hasCompleteRow = false;
        for (const row of block.querySelectorAll('[data-schedule-row]')) {
            const values = getScheduleRowValues(row);
            if (values.day && values.slot) {
                hasCompleteRow = true;
                break;
            }
        }
        if (!hasCompleteRow) {
            alert(`Please select a session day and time slot for ${label}. Scroll down if you added another enrollment.`);
            block.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return false;
        }
    }
    return true;
}

function createEnrollmentBlockController(blockEl) {
    let rowIndex = blockEl.querySelectorAll('[data-schedule-row]').length || 0;
    let availableDays = [];
    let availableSlots = [];
    let occupiedSlotKeys = new Set();

    const q = (sel) => blockEl.querySelector(sel);

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
        const branchEl = q('[data-branch-select]');
        const priceEl = q('[data-price-per-session]');
        const hint = q('[data-session-price-hint]');
        if (!branchEl || !priceEl) return;
        const price = sessionPriceForBranch(branchEl.value);
        const city = branchCityMap[branchEl.value] ?? branchCityMap[String(branchEl.value)];
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

    function resetSchedules() {
        const container = q('[data-schedule-rows]');
        if (!container) return;
        container.innerHTML = '';
        rowIndex = 0;
        addScheduleRow();
        syncScheduleRowFieldNames(blockEl);
    }

    function repopulateScheduleDayOptions() {
        const rows = blockEl.querySelectorAll('[data-schedule-row]');
        if (!rows.length) {
            addScheduleRow();
            return;
        }
        rows.forEach(row => {
            const daySel = row.querySelector('[data-day-select]');
            if (!daySel) return;
            const current = daySel.value || '';
            daySel.innerHTML = '<option value="">Select Day</option>';
            for (const d of availableDays) {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                daySel.appendChild(opt);
            }
            if (current) {
                selectOptionIfMissing(daySel, current, current);
            }
            if (daySel.value) {
                updateSlots(daySel);
            }
        });
    }

    async function loadTherapists(opts = {}) {
        const reset = opts.resetSchedules !== false;
        const branchEl = q('[data-branch-select]');
        const svcEl = q('[data-service-select]');
        const sel = q('[data-therapist-select]');
        applySessionPriceForBranch();
        if (!sel) return;
        sel.innerHTML = '<option value="">Loading...</option>';
        const branchId = branchEl?.value;
        if (!branchId) {
            sel.innerHTML = '<option value="">Select Branch First</option>';
            if (reset) resetSchedules();
            return;
        }
        const serviceIds = svcEl?.value ? [svcEl.value] : [];
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
            sel.innerHTML = '<option value="">Unable to load therapists — refresh & try again</option>';
            return;
        }
        sel.innerHTML = '<option value="">Select Therapist</option>';
        (data.data || []).forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = therapistOptionLabel(t);
            sel.appendChild(opt);
        });
        ensureTherapistSelected(blockEl);
        if (reset) {
            resetSchedules();
        }
    }

    async function loadScheduleOptions(opts = {}) {
        const reset = opts.resetSchedules !== false;
        const therapistId = q('[data-therapist-select]')?.value;
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
            const childIds = getSelectedChildIds();
            if (childIds[0]) {
                occParams.set('child_id', String(childIds[0]));
            }
            const occQuery = occParams.toString();
            const occUrl = `/ajax/therapists/${therapistId}/occupied-slots` + (occQuery ? `?${occQuery}` : '');
            const [dRes, sRes, oRes] = await Promise.all([
                fetch(`/ajax/therapists/${therapistId}/available-days`, { credentials: 'same-origin', headers }),
                fetch(`/ajax/therapists/${therapistId}/available-slots`, { credentials: 'same-origin', headers }),
                fetch(occUrl, { credentials: 'same-origin', headers }),
            ]);
            const dJson = await dRes.json().catch(() => ({}));
            const sJson = await sRes.json().catch(() => ({}));
            const oJson = await oRes.json().catch(() => ({}));
            availableDays = dJson.data || [];
            availableSlots = sJson.data || [];
            occupiedSlotKeys = new Set();
            for (const o of (oJson.data || [])) {
                occupiedSlotKeys.add(occupiedSlotKey(o.day, o.time_slot));
            }
            if (reset) {
                resetSchedules();
            } else {
                repopulateScheduleDayOptions();
            }
            if (q('[data-schedule-start-date]')?.value) {
                syncStartDateToScheduleDay({ autoAdd: reset });
            } else {
                updateFirstSessionHint();
            }
            refreshOtherBlocksSlotDropdowns(blockEl);
        } catch (e) {
            console.error(e);
        }
    }

    function isSlotOccupied(day, slot, excludeRow) {
        if (occupiedSlotKeys.has(occupiedSlotKey(day, slot))) return true;
        return isSlotTakenOnForm(day, slot, blockEl, excludeRow || null);
    }

    function findAvailableDayMatch(dayName) {
        const lower = String(dayName || '').trim().toLowerCase();
        if (!lower) return null;
        return (availableDays || []).find(d => String(d).trim().toLowerCase() === lower) || null;
    }

    function getSelectedScheduleDays() {
        const days = [];
        blockEl.querySelectorAll('[data-schedule-rows] [data-day-select]').forEach(sel => {
            if (sel.value) days.push(sel.value);
        });
        return days;
    }

    function scheduleHasDay(dayName) {
        const lower = String(dayName || '').trim().toLowerCase();
        return getSelectedScheduleDays().some(d => String(d).trim().toLowerCase() === lower);
    }

    function schedulePairTaken(skipSelect, day, slot) {
        if (!day || !slot) return false;
        for (const row of blockEl.querySelectorAll('[data-schedule-row]')) {
            const dSel = row.querySelector('[data-day-select]');
            const sSel = row.querySelector('[data-slot-select]');
            if (!dSel || !sSel || dSel === skipSelect) continue;
            if (dSel.value === day && sSel.value === slot) return true;
        }
        return false;
    }

    function isBreakSlot(slotValue) {
        if (!slotValue) return false;
        const found = (availableSlots || []).find(s => String(s.slot) === String(slotValue));
        return !!(found && (found.disabled === true || found.disabled === 1 || found.disabled === 'true'));
    }

    function recalculate() {
        const price = parseFloat(q('[data-price-per-session]')?.value) || 0;
        const discPct = parseFloat(q('[data-discount-pct]')?.value) || 0;
        const baseRows = blockEl.querySelectorAll('[data-schedule-row]').length;
        const repeatCb = q('[data-repeat-weekly]');
        const repeat = repeatCb && repeatCb.checked;
        const dvRaw = q('[data-duration-value]');
        const dv = dvRaw ? parseInt(dvRaw.value, 10) : NaN;
        const unitEl = q('[data-duration-unit]');
        const unit = unitEl ? unitEl.value : 'weekly';
        const sessions = calculateTotalSessions(baseRows, repeat, dv, unit);
        const subtotal = Math.round(price * sessions);
        const discAmt = Math.round(subtotal * (discPct / 100));
        const total = Math.max(0, subtotal - discAmt);
        const setText = (sel, val) => { const el = q(sel); if (el) el.textContent = val; };
        setText('[data-calc-sessions]', sessions);
        setText('[data-calc-subtotal]', formatMoneyAmount(subtotal));
        setText('[data-calc-discount]', formatMoneyAmount(discAmt));
        setText('[data-calc-total]', formatMoneyAmount(total));
    }

    function checkHighDiscount() {
        const pct = parseFloat(q('[data-discount-pct]')?.value) || 0;
        const threshold = window.FRC_HIGH_DISCOUNT_THRESHOLD ?? 50;
        const section = q('[data-discount-section]');
        if (section) section.style.display = pct > threshold ? 'block' : 'none';
    }

    function toggleDuration() {
        const cb = q('[data-repeat-weekly]');
        const section = q('[data-duration-section]');
        if (section && cb) section.style.display = cb.checked ? 'block' : 'none';
    }

    function updateSlots(daySelect) {
        const row = daySelect.closest('[data-schedule-row]');
        const slotSel = row?.querySelector('[data-slot-select]');
        if (!slotSel) return;
        const selectedDay = daySelect.value;
        const previousValue = slotSel.value;
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
                const disBooked = !!(selectedDay && isSlotOccupied(selectedDay, s.slot || '', row));
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
        if (previousValue && selectedDay && !isBreakSlot(previousValue)) {
            selectOptionIfMissing(slotSel, previousValue, previousValue);
            const selectedOpt = slotSel.options[slotSel.selectedIndex];
            if (selectedOpt?.value === previousValue && !isSlotOccupied(selectedDay, previousValue, row)) {
                selectedOpt.disabled = false;
            }
        }
        recalculate();
        updateFirstSessionHint();
    }

    function refreshSlotDropdowns() {
        blockEl.querySelectorAll('[data-schedule-row]').forEach(row => {
            const daySel = row.querySelector('[data-day-select]');
            const slotSel = row.querySelector('[data-slot-select]');
            if (daySel?.value && slotSel?.value && isSlotOccupied(daySel.value, slotSel.value, row)) {
                slotSel.value = '';
            }
            if (daySel?.value) updateSlots(daySel);
        });
    }

    async function applyInitialSchedulesFromDom() {
        for (const row of blockEl.querySelectorAll('[data-schedule-row]')) {
            const daySel = row.querySelector('[data-day-select]');
            const slotSel = row.querySelector('[data-slot-select]');
            if (!daySel?.value) continue;
            const initialSlot = slotSel?.value
                || slotSel?.querySelector('option[selected]')?.value
                || '';
            updateSlots(daySel);
            if (initialSlot) {
                selectOptionIfMissing(slotSel, initialSlot, initialSlot);
                const selectedOpt = slotSel?.options[slotSel.selectedIndex];
                if (selectedOpt?.value === initialSlot) {
                    selectedOpt.disabled = false;
                }
            }
        }
        recalculate();
        updateFirstSessionHint();
    }

    function onScheduleSlotChange(daySelect, slotSelect) {
        const row = daySelect.closest('[data-schedule-row]');
        const day = daySelect.value;
        const slot = slotSelect.value;
        if (!day || !slot) {
            recalculate();
            updateFirstSessionHint();
            refreshOtherBlocksSlotDropdowns(blockEl);
            return;
        }
        if (isBreakSlot(slot)) {
            alert('This is a therapist break time — this slot cannot be booked.');
            slotSelect.value = '';
            recalculate();
            updateFirstSessionHint();
            refreshOtherBlocksSlotDropdowns(blockEl);
            return;
        }
        if (isSlotOccupied(day, slot, row)) {
            const onForm = isSlotTakenOnForm(day, slot, blockEl, row);
            alert(onForm
                ? 'This day and time is already selected in another enrollment above — choose a different slot.'
                : 'This slot is unavailable (therapist busy or another program is booked at this time) — please choose another slot.');
            slotSelect.value = '';
            recalculate();
            updateFirstSessionHint();
            refreshOtherBlocksSlotDropdowns(blockEl);
            return;
        }
        if (schedulePairTaken(daySelect, day, slot)) {
            alert('The same day and time slot cannot be added more than once.');
            slotSelect.value = '';
            recalculate();
            updateFirstSessionHint();
            refreshOtherBlocksSlotDropdowns(blockEl);
            return;
        }
        recalculate();
        updateFirstSessionHint();
        refreshOtherBlocksSlotDropdowns(blockEl);
    }

    function bindScheduleRow(row) {
        const daySel = row.querySelector('[data-day-select]');
        const slotSel = row.querySelector('[data-slot-select]');
        const removeBtn = row.querySelector('[data-remove-row]');
        daySel?.addEventListener('change', () => {
            updateSlots(daySel);
            updateFirstSessionHint();
        });
        slotSel?.addEventListener('change', () => onScheduleSlotChange(daySel, slotSel));
        removeBtn?.addEventListener('click', () => {
            const rows = blockEl.querySelectorAll('[data-schedule-row]');
            if (rows.length <= 1) return;
            row.remove();
            recalculate();
            updateFirstSessionHint();
            refreshOtherBlocksSlotDropdowns(blockEl);
        });
    }

    function scheduleFieldPrefix() {
        return resolveScheduleFieldPrefix(blockEl);
    }

    function addScheduleRow() {
        const idx = rowIndex++;
        const prefix = scheduleFieldPrefix();
        const row = document.createElement('div');
        row.className = 'schedule-row';
        row.setAttribute('data-schedule-row', '');
        row.innerHTML = `
            <div>
                <label>Day</label>
                <select name="${prefix}[${idx}][day]" class="form-control" data-day-select>
                    <option value="">Select Day</option>
                    ${availableDays.map(d => `<option value="${d}">${d}</option>`).join('')}
                </select>
            </div>
            <div>
                <label>Time Slot</label>
                <select name="${prefix}[${idx}][time_slot]" class="form-control" data-slot-select>
                    <option value="">Select Day First</option>
                </select>
            </div>
            <div>
                <button type="button" data-remove-row style="background:var(--danger);color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;margin-top:20px;" title="Remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>
            </div>
        `;
        q('[data-schedule-rows]').appendChild(row);
        bindScheduleRow(row);
        syncScheduleRowFieldNames(blockEl);
        recalculate();
    }

    function isScheduleStartAligned() {
        const startInput = q('[data-schedule-start-date]');
        if (!startInput?.value) return true;
        const matchedDay = findAvailableDayMatch(dayNameFromYmd(startInput.value));
        if (!matchedDay || !scheduleHasDay(matchedDay)) return false;
        for (const row of blockEl.querySelectorAll('[data-schedule-row]')) {
            const d = row.querySelector('[data-day-select]');
            const s = row.querySelector('[data-slot-select]');
            if (d?.value === matchedDay && s?.value) return true;
        }
        return false;
    }

    function updateEnrollmentSaveState() {
        const form = document.getElementById('enrollForm');
        const btn = form?.querySelector('[data-submit-enrollment]');
        if (!btn) return;
        const allAligned = window.enrollmentBlockControllers.every(c => c.isScheduleStartAligned());
        btn.disabled = !allAligned;
        btn.setAttribute('aria-disabled', allAligned ? 'false' : 'true');
        btn.title = allAligned ? '' : 'Fix the start date and session schedule in each enrollment before saving.';
        if (allAligned) btn.removeAttribute('title');
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

    function syncStartDateToScheduleDay(opts = {}) {
        const autoAdd = opts.autoAdd !== false;
        const startInput = q('[data-schedule-start-date]');
        if (!startInput?.value) {
            updateFirstSessionHint();
            return;
        }
        const startYmd = startInput.value;
        const matchedDay = findAvailableDayMatch(dayNameFromYmd(startYmd));
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
        const rows = blockEl.querySelectorAll('[data-schedule-row]');
        let placed = false;
        for (const row of rows) {
            const dSel = row.querySelector('[data-day-select]');
            if (dSel && !dSel.value) {
                selectOptionIfMissing(dSel, matchedDay, matchedDay);
                updateSlots(dSel);
                placed = true;
                break;
            }
        }
        if (!placed) {
            addScheduleRow();
            const rowsAfter = blockEl.querySelectorAll('[data-schedule-row]');
            const lastRow = rowsAfter[rowsAfter.length - 1];
            const dSel = lastRow?.querySelector('[data-day-select]');
            if (dSel) {
                selectOptionIfMissing(dSel, matchedDay, matchedDay);
                updateSlots(dSel);
            }
        }
        recalculate();
        updateFirstSessionHint();
    }

    function updateFirstSessionHint() {
        const hint = q('[data-first-session-hint]');
        const startInput = q('[data-schedule-start-date]');
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
        hint.textContent = sameCalendarDay
            ? `First session is on: ${formatDateHint(startDate)} (${matchedDay}).`
            : `The first session: ${formatDateHint(anchorFirst)}.`;
        updateEnrollmentSaveState();
    }

    function validateScheduleStartAlignment() {
        const startInput = q('[data-schedule-start-date]');
        if (!startInput?.value) return true;
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

    function validateSchedulesOnSubmit() {
        if (!validateScheduleStartAlignment()) return false;
        const seen = new Set();
        for (const row of blockEl.querySelectorAll('[data-schedule-row]')) {
            const d = row.querySelector('[data-day-select]');
            const s = row.querySelector('[data-slot-select]');
            if (!d || !s || !d.value || !s.value) continue;
            if (isBreakSlot(s.value)) {
                alert('You cannot select a break time slot — please choose another slot.');
                return false;
            }
            if (isSlotOccupied(d.value, s.value, row)) {
                alert('This slot is already occupied — please choose another day / time.');
                return false;
            }
            const key = d.value + '|' + s.value;
            if (seen.has(key)) {
                alert('The same day and time slot cannot be added more than once.');
                return false;
            }
            seen.add(key);
        }
        return true;
    }

    function initExistingScheduleRows() {
        blockEl.querySelectorAll('[data-schedule-row]').forEach(row => bindScheduleRow(row));
        rowIndex = blockEl.querySelectorAll('[data-schedule-row]').length;
    }

    async function init() {
        syncScheduleRowFieldNames(blockEl);
        initExistingScheduleRows();
        const branchEl = q('[data-branch-select]');
        const svcEl = q('[data-service-select]');
        const therapistEl = q('[data-therapist-select]');

        branchEl?.addEventListener('change', () => {
            applySessionPriceForBranch();
            if (branchEl.tagName === 'SELECT' && branchEl.value) {
                loadTherapists({ resetSchedules: false });
            }
        });
        svcEl?.addEventListener('change', () => {
            if (branchEl?.value) loadTherapists({ resetSchedules: false });
        });
        therapistEl?.addEventListener('change', () => loadScheduleOptions({ resetSchedules: true }));
        q('[data-add-schedule-row]')?.addEventListener('click', addScheduleRow);
        q('[data-repeat-weekly]')?.addEventListener('change', () => {
            toggleDuration();
            recalculate();
        });
        q('[data-duration-value]')?.addEventListener('input', recalculate);
        q('[data-duration-unit]')?.addEventListener('change', recalculate);
        q('[data-discount-pct]')?.addEventListener('input', () => {
            recalculate();
            checkHighDiscount();
        });
        q('[data-schedule-start-date]')?.addEventListener('change', () => syncStartDateToScheduleDay({ autoAdd: true }));
        blockEl.querySelector('[data-schedule-rows]')?.addEventListener('change', (e) => {
            if (e.target?.matches('[data-day-select], [data-slot-select]')) {
                updateFirstSessionHint();
            }
        });

        if (branchEl?.value) {
            applySessionPriceForBranch();
        }
        if (branchEl?.value && svcEl?.value) {
            await loadTherapists({ resetSchedules: false });
            if (q('[data-therapist-select]')?.value) {
                await loadScheduleOptions({ resetSchedules: false });
                await applyInitialSchedulesFromDom();
            }
        }

        if (branchEl?.value && svcEl?.value && q('[data-therapist-select]')?.value && availableDays.length === 0) {
            await loadScheduleOptions({ resetSchedules: false });
            await applyInitialSchedulesFromDom();
        }

        recalculate();
        checkHighDiscount();
        if (q('[data-repeat-weekly]')?.checked) toggleDuration();
        syncStartDateToScheduleDay({ autoAdd: false });
        updateFirstSessionHint();
    }

    return {
        blockEl,
        init,
        loadScheduleOptions,
        isScheduleStartAligned,
        validateSchedulesOnSubmit,
        refreshSlotDropdowns,
    };
}

window.enrollmentBlockControllers = [];

function initEnrollmentBlock(blockEl) {
    const controller = createEnrollmentBlockController(blockEl);
    window.enrollmentBlockControllers.push(controller);
    return controller.init();
}

function removeEnrollmentBlock(blockEl) {
    window.enrollmentBlockControllers = window.enrollmentBlockControllers.filter(c => c.blockEl !== blockEl);
    blockEl.remove();
    reindexExtraEnrollmentBlocks();
    refreshAllBlocksSlotDropdowns();
    const btn = document.querySelector('[data-submit-enrollment]');
    if (btn && window.enrollmentBlockControllers.length) {
        const allAligned = window.enrollmentBlockControllers.every(c => c.isScheduleStartAligned());
        btn.disabled = !allAligned;
    }
}

function reindexExtraEnrollmentBlocks() {
    document.querySelectorAll('[data-extra-block]').forEach((block, i) => {
        reindexFieldNames(block, i + 1);
    });
    updateSubmitLabel();
}

function reindexFieldNames(blockEl, index) {
    blockEl.setAttribute('data-block-index', String(index));
    blockEl.setAttribute('data-schedule-prefix', `extra_enrollments[${index}][schedules]`);
    blockEl.querySelectorAll('[name]').forEach(input => {
        if (!input.name.includes('extra_enrollments[')) return;
        input.name = input.name.replace(/extra_enrollments\[[^\]]+\]/, `extra_enrollments[${index}]`);
    });
    syncScheduleRowFieldNames(blockEl);
    const title = blockEl.querySelector('.enrollment-block-header .form-section-title');
    if (title) {
        title.innerHTML = '<i class="fa-solid fa-file-medical" style="color:var(--teal);"></i> Enrollment ' + (index + 1);
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    reindexExtraEnrollmentBlocks();
    cullBlankExtraEnrollmentBlocks();

    const blocks = document.querySelectorAll('[data-enrollment-block]');
    for (const block of blocks) {
        await initEnrollmentBlock(block);
    }

    document.getElementById('addAnotherEnrollmentBtn')?.addEventListener('click', async function () {
        const childIds = getSelectedChildIds();
        if (!childIds.length) {
            alert('Please select at least one child in the first enrollment before adding another.');
            return;
        }

        const template = document.getElementById('extraEnrollmentBlockTemplate');
        if (!template) return;

        let html = template.innerHTML.replace(/__INDEX__/g, '0');
        html = html.replace(/extra_enrollments\[__INDEX__\]/g, 'extra_enrollments[0]');
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const blockEl = wrapper.firstElementChild;
        if (!blockEl) return;

        document.getElementById('enrollmentBlocks').appendChild(blockEl);
        reindexExtraEnrollmentBlocks();
        syncLockedChildrenDisplays();

        blockEl.querySelector('[data-remove-enrollment-block]')?.addEventListener('click', () => {
            removeEnrollmentBlock(blockEl);
        });

        await initEnrollmentBlock(blockEl);
        refreshAllBlocksSlotDropdowns();
        blockEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.querySelectorAll('[data-remove-enrollment-block]').forEach(btn => {
        btn.addEventListener('click', () => {
            const blockEl = btn.closest('[data-enrollment-block]');
            if (blockEl) removeEnrollmentBlock(blockEl);
        });
    });

    document.querySelectorAll('[data-child-select-sync]').forEach(sel => {
        sel.addEventListener('change', () => {
            syncLockedChildrenDisplays();
            window.enrollmentBlockControllers.forEach(c => {
                if (c.blockEl.hasAttribute('data-extra-block')) return;
                c.loadScheduleOptions({ resetSchedules: false });
            });
        });
    });

    const pickerHidden = document.querySelector('#enrollmentChildPickerHidden');
    if (pickerHidden) {
        new MutationObserver(() => {
            syncLockedChildrenDisplays();
            window.enrollmentBlockControllers.forEach(c => {
                c.loadScheduleOptions({ resetSchedules: false });
            });
        }).observe(pickerHidden, { childList: true, subtree: true });
    }

    syncLockedChildrenDisplays();
    updateSubmitLabel();

    const errorBlock = document.querySelector('.enrollment-block-has-error');
    if (errorBlock) {
        errorBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('enrollForm')?.addEventListener('submit', function (e) {
        const childIds = getSelectedChildIds();
        if (!childIds.length) {
            e.preventDefault();
            alert('Please select at least one child.');
            return false;
        }
        cullBlankExtraEnrollmentBlocks();
        prepareScheduleFieldsForSubmit();
        if (!validateAllBlocksHaveSchedules()) {
            e.preventDefault();
            return false;
        }
        for (const controller of window.enrollmentBlockControllers) {
            if (!controller.validateSchedulesOnSubmit()) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
