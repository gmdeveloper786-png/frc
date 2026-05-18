@extends('layouts.app')
@section('title', 'Edit Assessment')
@section('page-title', 'Edit Assessment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <form action="{{ route('assessments.update', $assessment) }}" method="POST" class="form-frc" id="assessmentForm">
        @csrf @method('PUT')
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-clipboard-list" style="color:var(--teal);"></i> Assessment Details</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Date <span style="color:var(--danger)">*</span></label>
                    @php
                        $assessmentDateMin = $assessment->date->startOfDay()->lt(now()->startOfDay())
                            ? $assessment->date->format('Y-m-d')
                            : now()->format('Y-m-d');
                    @endphp
                    <input type="date" name="date" id="assessDate" value="{{ old('date', $assessment->date->format('Y-m-d')) }}"
                        min="{{ $assessmentDateMin }}"
                        class="form-control @error('date') is-invalid @enderror" onchange="updateDay(this.value)">
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Day (auto)</label>
                    <input type="text" id="dayDisplay" value="{{ $assessment->day }}" class="form-control" readonly style="background:var(--bg-light);">
                </div>
                <div class="col-md-4">
                    <label>Time <span style="color:var(--danger)">*</span></label>
                    <input type="time" name="time" value="{{ old('time', \Carbon\Carbon::parse($assessment->time)->format('H:i')) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Branch <span style="color:var(--danger)">*</span></label>
                    <select name="branch_id" id="assessmentBranchSelect" class="form-control">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $assessment->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach(['draft','publish'] as $s)
                            <option value="{{ $s }}" {{ old('status', $assessment->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-user-doctor" style="color:var(--teal);"></i> Therapist</div>
            @error('therapist_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
            <div class="row g-3">
                <div class="col-12">
                    <label>Therapist</label>
                    <select name="therapist_id" id="assessmentTherapistSelect" class="form-control">
                        <option value="">Select branch first</option>
                    </select>
                    <div id="assessmentTherapistHint" style="display:none;margin-top:8px;font-size:13px;color:var(--danger);"></div>
                    <small style="color:var(--text-muted);font-size:12px;display:block;margin-top:6px;">Required when status is Published. Therapists are listed for the selected branch only.</small>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-children" style="color:var(--teal);"></i> Children</div>
            @include('partials.approved-child-checkboxes-field', [
                'initialChildren' => $initialChildren,
                'selectedIds' => old('child_ids', $assessment->children->pluck('id')->toArray()),
            ])
        </div>
        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Update</button>
            <a href="{{ route('assessments.index') }}" class="btn-outline-teal">Cancel</a>
        </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const assessmentTherapistOld = @json(old('therapist_id', $assessment->therapist_id));
const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
function updateDay(dateStr) {
    if (!dateStr) return;
    const d = new Date(dateStr + 'T12:00:00');
    document.getElementById('dayDisplay').value = days[d.getDay()];
}
function therapistOptionLabel(t) {
    return t.full_name || '';
}

async function reloadAssessmentTherapists() {
    const form = document.getElementById('assessmentForm');
    if (!form) return;
    const branchSel = form.querySelector('[name="branch_id"]');
    const therapistSel = document.getElementById('assessmentTherapistSelect');
    const hint = document.getElementById('assessmentTherapistHint');
    if (!branchSel || !therapistSel || !hint) return;

    hint.style.display = 'none';
    hint.textContent = '';

    const prev = therapistSel.value || (assessmentTherapistOld != null ? String(assessmentTherapistOld) : '');
    const branchId = branchSel.value;

    therapistSel.innerHTML = '<option value="">Loading...</option>';

    if (!branchId) {
        therapistSel.innerHTML = '<option value="">Select branch first</option>';
        return;
    }

    try {
        const res = await fetch(`/ajax/branches/${branchId}/therapists`, {
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
            hint.textContent = 'No active therapist is assigned to this branch.';
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
    const b = form.querySelector('[name="branch_id"]');
    if (b) b.addEventListener('change', reloadAssessmentTherapists);
    reloadAssessmentTherapists();
});

</script>
@include('partials.approved-child-picker-scripts', [
    'pickerMode' => 'checkboxes',
    'initialChildren' => $initialChildren,
    'pickerId' => 'approvedChildPicker',
])
@endpush
