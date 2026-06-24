@php
    $assessmentTherapistOld = $assessmentTherapistOld ?? old('therapist_id');
    $initialServiceId = $initialServiceId ?? old('service_id');
@endphp
<script nonce="{{ $cspNonce }}">
const assessmentTherapistOld = @json($assessmentTherapistOld);
const assessmentServiceIdInit = @json($initialServiceId);

function therapistOptionLabel(t) {
    const name = (t.full_name || '').trim();
    const email = (t.email || '').trim();
    return email ? `${name} — ${email}` : name;
}

async function reloadAssessmentTherapists() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;
    const branchSel = form.querySelector('[name="branch_id"]');
    const serviceSel = document.getElementById('assessmentServiceSelect');
    const therapistSel = document.getElementById('assessmentTherapistSelect');
    const hint = document.getElementById('assessmentTherapistHint');
    if (!branchSel || !therapistSel || !hint) return;

    hint.style.display = 'none';
    hint.textContent = '';

    const prev = therapistSel.value || (assessmentTherapistOld != null ? String(assessmentTherapistOld) : '');
    const branchId = branchSel.value;
    const serviceId = serviceSel ? serviceSel.value : '';

    therapistSel.innerHTML = '<option value="">Loading...</option>';

    if (!branchId) {
        therapistSel.innerHTML = '<option value="">Select branch first</option>';
        return;
    }

    if (!serviceId) {
        therapistSel.innerHTML = '<option value="">Select service first</option>';
        return;
    }

    const qs = new URLSearchParams();
    qs.set('service_match', 'any');
    qs.append('service_ids[]', serviceId);

    try {
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
            therapistSel.innerHTML = '<option value="">Unable to load therapists</option>';
            return;
        }
        const list = data.data || [];
        if (!list.length) {
            therapistSel.innerHTML = '<option value="">No therapist available</option>';
            hint.style.display = 'block';
            hint.textContent = 'No therapist offers this service at the selected branch.';
            return;
        }
        therapistSel.innerHTML = '<option value="">Select Therapist</option>';
        list.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = therapistOptionLabel(t);
            therapistSel.appendChild(opt);
        });
        if (prev && Array.from(therapistSel.options).some(o => o.value === String(prev))) {
            therapistSel.value = String(prev);
        }
    } catch (e) {
        console.error(e);
        therapistSel.innerHTML = '<option value="">Error loading therapists</option>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;
    const branchSel = form.querySelector('[name="branch_id"]');
    const serviceSel = document.getElementById('assessmentServiceSelect');
    if (branchSel) branchSel.addEventListener('change', reloadAssessmentTherapists);
    if (serviceSel) serviceSel.addEventListener('change', reloadAssessmentTherapists);
    if (serviceSel && assessmentServiceIdInit != null && assessmentServiceIdInit !== '') {
        serviceSel.value = String(assessmentServiceIdInit);
    }
    reloadAssessmentTherapists();
});
</script>
