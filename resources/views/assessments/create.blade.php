@extends('layouts.app')
@section('title', 'Schedule Assessment')
@section('page-title', 'Schedule Assessment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <form action="{{ route('assessments.store') }}" method="POST" class="form-frc" id="assessmentForm">
        @csrf
        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-clipboard-list" style="color:var(--teal);"></i> Assessment Details</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="date" id="assessDate" value="{{ old('date') }}"
                        min="{{ now()->format('Y-m-d') }}"
                        class="form-control @error('date') is-invalid @enderror">
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label>Day (auto)</label>
                    <input type="text" id="dayDisplay" class="form-control" readonly placeholder="Auto-calculated" style="background:var(--bg-light);">
                </div>
                <div class="col-md-4">
                    <label>Time <span style="color:var(--danger)">*</span></label>
                    <input type="time" name="time" id="assessTime" value="{{ old('time') }}" class="form-control @error('time') is-invalid @enderror">
                    @error('time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Branch <span style="color:var(--danger)">*</span></label>
                    @if($branches->count() === 1)
                        @php $onlyBranch = $branches->first(); @endphp
                        <input type="hidden" name="branch_id" id="assessmentBranchSelect" value="{{ $onlyBranch->id }}">
                        <input type="text" class="form-control" value="{{ $onlyBranch->displayLabel() }}" readonly>
                    @else
                        <select name="branch_id" id="assessmentBranchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->displayLabel() }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="publish" {{ old('status') == 'publish' ? 'selected' : '' }}>Published</option>
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
                    <small style="color:var(--text-muted);font-size:12px;display:block;margin-top:6px;">Required when status is Published. Draft assessments may leave therapist unset. Therapists are listed for the selected branch only.</small>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fa-solid fa-children" style="color:var(--teal);"></i> Assign Children (Optional)</div>
            @include('partials.approved-child-checkboxes-field', [
                'initialChildren' => $initialChildren,
                'selectedIds' => old('child_ids', []),
            ])
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-teal"><i class="fa-solid fa-check"></i> Create Assessment</button>
            <a href="{{ route('assessments.index') }}" class="btn-outline-teal">Cancel</a>
        </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const assessmentTherapistOld = @json(old('therapist_id'));
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
@include('assessments.partials.assessment-datetime-scripts')
@include('partials.approved-child-picker-scripts', [
    'pickerMode' => 'checkboxes',
    'initialChildren' => $initialChildren,
    'pickerId' => 'approvedChildPicker',
])
@endpush
