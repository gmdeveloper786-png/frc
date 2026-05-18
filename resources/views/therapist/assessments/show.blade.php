@extends('layouts.app')
@section('title', 'Assessment')
@section('page-title', 'Assessment Detail')

@section('content')
<div class="row g-3">
    <div class="col-md-5 min-w-0">
        <div class="card-frc">
            <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:16px;">Schedule</h6>
            <table style="width:100%;font-size:14px;">
                <tr><td style="color:var(--text-muted);padding:7px 0;">Date</td><td style="font-weight:500;">{{ $assessment->date->format('d M Y') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Day</td><td>{{ $assessment->day }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Time</td><td>{{ \Carbon\Carbon::parse($assessment->time)->format('h:i A') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Branch</td><td>{{ $assessment->branch?->name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Status</td><td><span class="badge-status badge-{{ $assessment->status === 'cancelled' ? 'cancelled' : $assessment->status }}">{{ $assessment->status === 'cancelled' ? 'Cancelled' : ucfirst($assessment->status) }}</span></td></tr>
            </table>
            @if($assessment->status === 'cancelled')
                <hr style="border-color:var(--border-soft);">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:8px;">Cancellation</h6>
                <table style="width:100%;font-size:14px;">
                    <tr><td style="color:var(--text-muted);padding:7px 0;">Cancelled at</td><td>{{ $assessment->cancelled_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:7px 0;">Cancelled by</td><td>{{ $assessment->cancelledBy?->full_name ?? '—' }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:7px 0;vertical-align:top;">Cancellation reason</td><td style="white-space:pre-wrap;">{{ $assessment->cancellation_reason ?: '—' }}</td></tr>
                </table>
            @endif
        </div>

        @if($assessment->status === 'publish')
            <div class="card-frc mt-3">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:12px;"><i class="fa-solid fa-check-double me-2" style="color:var(--teal);"></i>Mark completed</h6>
                <form action="{{ route('therapist.assessments.complete', $assessment) }}" method="POST" class="form-frc">
                    @csrf
                    <label style="font-size:13px;color:var(--text-muted);">Completion summary (optional)</label>
                    <textarea name="assessment_notes" class="form-control mb-2" rows="3" placeholder="Brief summary for records">{{ old('assessment_notes') }}</textarea>
                    <button type="submit" class="btn-teal" onclick="return confirm('Mark this assessment as completed?')"><i class="fa-solid fa-check"></i> Complete</button>
                </form>
            </div>
        @endif
    </div>

    <div class="col-md-7 min-w-0">
        <div class="card-frc mb-3">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-children me-2" style="color:var(--teal);"></i>Assigned children</h6>
            </div>
            @if($assessment->children->isEmpty())
                <div class="empty-state" style="padding:20px;"><p>No children listed.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table-frc">
                        <thead><tr><th>Name</th><th>Age</th><th>Gender</th></tr></thead>
                        <tbody>
                            @foreach($assessment->children as $child)
                                <tr>
                                    <td style="font-weight:500;">{{ $child->full_name }}</td>
                                    <td>{{ $child->age ? $child->age.'y' : '—' }}</td>
                                    <td>{{ $child->gender ? ucfirst($child->gender) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($assessment->status === 'publish')
            <div class="card-frc mb-3" id="add-note">
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
                    <button type="submit" class="btn-teal"><i class="fa-solid fa-floppy-disk"></i> Save note</button>
                </form>
            </div>
        @endif

        <div class="card-frc">
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

                                <form action="{{ route('therapist.assessments.notes.destroy', [$assessment, $note]) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this structured note permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:13px;padding:6px 14px;border-radius:8px;">
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
            <a href="{{ route('therapist.assessments.index') }}" class="btn-outline-teal"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
</div>
@endsection
