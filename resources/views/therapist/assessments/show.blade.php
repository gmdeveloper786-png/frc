@extends('layouts.app')
@section('title', 'Assessment')
@section('page-title', 'Assessment Detail')

@push('styles')
<style>
.therapist-assessment-show-page .therapist-assessment-info-table {
    width: 100%;
    font-size: 14px;
}
.therapist-assessment-show-page .therapist-assessment-info-table td {
    padding: 7px 0;
    vertical-align: top;
}
.therapist-assessment-show-page .therapist-assessment-info-table td:first-child {
    color: var(--text-muted);
    width: 38%;
    padding-right: 12px;
}
.therapist-assessment-show-page .therapist-assessment-child-cell {
    white-space: normal;
    min-width: 8rem;
}
@media (max-width: 575.98px) {
    .therapist-assessment-show-page .therapist-assessment-info-table tr {
        display: block;
        margin-bottom: 10px;
    }
    .therapist-assessment-show-page .therapist-assessment-info-table tr:last-child {
        margin-bottom: 0;
    }
    .therapist-assessment-show-page .therapist-assessment-info-table td {
        display: block;
        width: 100% !important;
        padding: 0;
    }
    .therapist-assessment-show-page .therapist-assessment-info-table td:first-child {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .therapist-assessment-show-page .therapist-assessment-info-table td:last-child {
        font-weight: 500;
        color: var(--navy);
    }
    .therapist-assessment-show-page .card-frc .card-header-frc {
        margin-bottom: 12px;
        padding-bottom: 10px;
    }
    .therapist-assessment-show-page .therapist-assessment-back {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<div class="row g-3 therapist-assessment-show-page">
    <div class="col-12 col-md-5 min-w-0">
        <div class="card-frc card-frc--panel">
            <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:16px;">Schedule</h6>
            <table class="therapist-assessment-info-table">
                <tr><td>Date</td><td style="font-weight:500;">{{ $assessment->date->format('d M Y') }}</td></tr>
                <tr><td>Day</td><td>{{ $assessment->day }}</td></tr>
                <tr><td>Time</td><td>{{ \Carbon\Carbon::parse($assessment->time)->format('h:i A') }}</td></tr>
                <tr><td>Branch</td><td>{{ $assessment->branch?->name ?? '—' }}</td></tr>
                <tr><td>Service</td><td>{{ $assessment->services->pluck('name')->join(', ') ?: '—' }}</td></tr>
                <tr><td>Status</td><td><span class="badge-status badge-{{ $assessment->status === 'cancelled' ? 'cancelled' : $assessment->status }}">{{ $assessment->statusLabel() }}</span></td></tr>
                @if($assessment->status === 'completed')
                    <tr><td>Completed</td><td>{{ $assessment->completed_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    @if($assessment->completedBy)
                        <tr><td>Completed by</td><td>{{ $assessment->completedBy->full_name }}</td></tr>
                    @endif
                @endif
            </table>
            @if($assessment->status === 'completed' && $assessment->assessment_notes)
                <hr style="border-color:var(--border-soft);">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:8px;">Completion notes</h6>
                <p style="font-size:14px;color:var(--text-muted);white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;max-width:100%;">{{ $assessment->assessment_notes }}</p>
            @endif
            @if($assessment->status === 'cancelled')
                <hr style="border-color:var(--border-soft);">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:8px;">Cancellation</h6>
                <table class="therapist-assessment-info-table">
                    <tr><td>Cancelled at</td><td>{{ $assessment->cancelled_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    <tr><td>Cancelled by</td><td>{{ $assessment->cancelledBy?->full_name ?? '—' }}</td></tr>
                    <tr><td>Cancellation reason</td><td style="white-space:pre-wrap;">{{ $assessment->cancellation_reason ?: '—' }}</td></tr>
                </table>
            @endif
        </div>

        @if($assessment->status === 'publish')
            <div class="card-frc card-frc--panel mt-3">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:12px;"><i class="fa-solid fa-check-double me-2" style="color:var(--teal);"></i>Mark completed</h6>
                <form action="{{ route('therapist.assessments.complete', $assessment) }}" method="POST" class="form-frc">
                    @csrf
                    <label style="font-size:13px;color:var(--text-muted);">Completion summary (optional)</label>
                    <textarea name="assessment_notes" class="form-control mb-2" rows="3" placeholder="Brief summary for records">{{ old('assessment_notes') }}</textarea>
                    <div class="frc-form-actions" style="margin-top:0;padding-top:0;border-top:none;">
                        <button type="submit" class="btn-teal" data-confirm="Mark this assessment as completed?"><i class="fa-solid fa-check"></i> Complete</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-12 col-md-7 min-w-0">
        <div class="card-frc card-frc--panel mb-3">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-children me-2" style="color:var(--teal);"></i>Assigned children</h6>
            </div>
            @if($assessment->children->isEmpty())
                <div class="empty-state" style="padding:20px;"><p>No children listed.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table-frc">
                        <thead><tr><th>Name</th><th>Age</th><th>Gender</th><th>Actions</th></tr></thead>
                        <tbody>
                            @foreach($assessment->children as $child)
                                <tr>
                                    <td class="therapist-assessment-child-cell" style="font-weight:500;">
                                        {{ $child->full_name }}
                                        @if($child->gr_number)
                                            <span style="display:block;font-size:12px;color:var(--text-muted);font-weight:500;font-family:monospace;">GR No: {{ $child->gr_number }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $child->age ? $child->age.'y' : '—' }}</td>
                                    <td>{{ $child->gender ? ucfirst($child->gender) : '—' }}</td>
                                    <td>
                                        <a href="{{ route('therapist.children.show', $child) }}" class="btn-outline-teal btn-sm-frc">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($assessment->status === 'publish')
            <div class="card-frc card-frc--panel mb-3" id="add-note">
                <div class="card-header-frc">
                    <h6 class="card-title-frc"><i class="fa-solid fa-note-sticky me-2" style="color:var(--teal);"></i>Add structured note</h6>
                </div>
                <form action="{{ route('therapist.assessments.notes', $assessment) }}" method="POST" class="form-frc">
                    @csrf
                    <div class="mb-2">
                        <label>Observation</label>
                        <textarea name="observation" class="form-control" rows="2">{{ old('observation') }}</textarea>
                    </div>
                    <div class="mb-2">
                        <label>Child response</label>
                        <textarea name="child_response" class="form-control" rows="2">{{ old('child_response') }}</textarea>
                    </div>
                    <div class="mb-2">
                        <label>Initial recommendation</label>
                        <textarea name="initial_recommendation" class="form-control" rows="2">{{ old('initial_recommendation') }}</textarea>
                    </div>
                    <div class="mb-2">
                        <label>Additional notes</label>
                        <textarea name="additional_notes" class="form-control" rows="2">{{ old('additional_notes') }}</textarea>
                    </div>
                    <div class="mb-2">
                        <label>Note status</label>
                        <select name="status" class="form-control">
                            <option value="draft">Draft</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="frc-form-actions" style="margin-top:0;padding-top:0;border-top:none;">
                        <button type="submit" class="btn-teal"><i class="fa-solid fa-floppy-disk"></i> Save note</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card-frc card-frc--panel">
            <div class="card-header-frc card-header-frc--stack-sm">
                <h6 class="card-title-frc">Your structured notes</h6>
                <p class="small text-muted mb-0" style="min-width:0;">You can view, edit, or delete only notes you created. Parents and children do not see these.</p>
            </div>
            @if($structuredNotesVisible->isEmpty())
                <div class="empty-state" style="padding:20px;"><p>No structured notes yet.</p></div>
            @else
                <div class="structured-notes-list" style="display:flex;flex-direction:column;gap:12px;min-width:0;">
                    @foreach($structuredNotesVisible as $note)
                        <div class="structured-note-card" style="padding:12px;background:var(--bg-light);border-radius:10px;border-left:3px solid var(--teal);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;min-width:0;">
                                <span class="badge-status badge-{{ $note->status === 'completed' ? 'approved' : 'draft' }}">{{ str_replace('_',' ', $note->status) }}</span>
                                <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;">{{ $note->created_at?->format('d M Y H:i') }}</span>
                            </div>
                            <div class="structured-note-body">
                                @if($note->observation)<p><strong>Observation:</strong> {{ $note->observation }}</p>@endif
                                @if($note->child_response)<p><strong>Child response:</strong> {{ $note->child_response }}</p>@endif
                                @if($note->initial_recommendation)<p><strong>Recommendation:</strong> {{ $note->initial_recommendation }}</p>@endif
                                @if($note->additional_notes)<p style="margin-bottom:0;"><strong>Additional:</strong> {{ $note->additional_notes }}</p>@endif
                            </div>

                            <div class="structured-note-actions mt-2 pt-2" style="border-top:1px solid var(--border-soft);">
                            <details class="small mb-0">
                                <summary style="cursor:pointer;color:var(--teal-dark);">Edit this note</summary>
                                <form action="{{ route('therapist.assessments.notes.update', [$assessment, $note]) }}" method="POST" class="form-frc mt-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-2">
                                        <label class="small">Observation</label>
                                        <textarea name="observation" class="form-control form-control-sm" rows="2">{{ old('observation', $note->observation) }}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small">Child response</label>
                                        <textarea name="child_response" class="form-control form-control-sm" rows="2">{{ old('child_response', $note->child_response) }}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small">Initial recommendation</label>
                                        <textarea name="initial_recommendation" class="form-control form-control-sm" rows="2">{{ old('initial_recommendation', $note->initial_recommendation) }}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small">Additional notes</label>
                                        <textarea name="additional_notes" class="form-control form-control-sm" rows="2">{{ old('additional_notes', $note->additional_notes) }}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="draft" @selected($note->status !== 'completed')>Draft</option>
                                            <option value="completed" @selected($note->status === 'completed')>Completed</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-teal" style="font-size:12px;padding:6px 12px;">Save changes</button>
                                </form>
                            </details>

                                <form action="{{ route('therapist.assessments.notes.destroy', [$assessment, $note]) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:13px;padding:6px 14px;border-radius:8px;" data-confirm="Delete this structured note permanently?">
                                        <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i> Delete note
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-3">
            <a href="{{ route('therapist.assessments.index') }}" class="btn-outline-teal therapist-assessment-back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
</div>
@endsection
