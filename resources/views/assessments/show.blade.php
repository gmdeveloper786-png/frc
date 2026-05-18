@extends('layouts.app')
@section('title', 'Assessment Details')
@section('page-title', 'Assessment Details')

@section('content')
<div class="row g-3">
    <div class="col-md-4 min-w-0">
        <div class="card-frc">
            <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:16px;">Assessment Info</h6>
            <table class="assessment-info-table" style="width:100%;font-size:14px;table-layout:fixed;">
                <colgroup><col style="width:38%;"><col></colgroup>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Date</td><td style="font-weight:500;">{{ $assessment->date->format('d M Y') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Day</td><td>{{ $assessment->day }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Time</td><td>{{ date('g:i A', strtotime($assessment->time)) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Branch</td><td>{{ $assessment->branch?->name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Therapist</td><td>{{ $assessment->therapist?->full_name ?? ($assessment->status === 'draft' ? 'Not assigned' : '—') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:7px 0;">Status</td><td><span class="badge-status badge-{{ $assessment->status === 'cancelled' ? 'cancelled' : $assessment->status }}">{{ $assessment->status === 'cancelled' ? 'Cancelled' : ucfirst($assessment->status) }}</span></td></tr>
                @if($assessment->status === 'completed')
                    <tr><td style="color:var(--text-muted);padding:7px 0;">Completed</td><td>{{ $assessment->completed_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    @if($assessment->completedBy)<tr><td style="color:var(--text-muted);padding:7px 0;">Completed by</td><td>{{ $assessment->completedBy->full_name }}</td></tr>@endif
                @endif
                @if($assessment->status === 'cancelled')
                    <tr><td style="color:var(--text-muted);padding:7px 0;">Cancelled at</td><td>{{ $assessment->cancelled_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    @if($assessment->cancelledBy)<tr><td style="color:var(--text-muted);padding:7px 0;">Cancelled by</td><td>{{ $assessment->cancelledBy->full_name }}</td></tr>@endif
                    @if($assessment->cancellation_reason)<tr><td style="color:var(--text-muted);padding:7px 0;vertical-align:top;">Cancellation reason</td><td style="white-space:pre-wrap;">{{ $assessment->cancellation_reason }}</td></tr>@endif
                @endif
            </table>
            @if($assessment->assessment_notes)
                <hr style="border-color:var(--border-soft);">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:8px;">Completion notes</h6>
                <p style="font-size:14px;color:var(--text-muted);white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;max-width:100%;">{{ $assessment->assessment_notes }}</p>
            @endif
        </div>
       
    </div>
    <div class="col-md-8 min-w-0">
        <div class="card-frc mb-3">
            <div class="card-header-frc">
                <h6 class="card-title-frc"><i class="fa-solid fa-children me-2" style="color:var(--teal);"></i>Assigned Children ({{ $assessment->children->count() }})</h6>
            </div>
            @if($assessment->children->isEmpty())
                <div class="empty-state" style="padding:24px;"><p>No children assigned to this assessment.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table-frc">
                        <thead><tr><th>Name</th><th>Age</th><th>Disabilities</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($assessment->children as $child)
                                <tr>
                                    <td style="font-weight:500;"><a href="{{ route('children.show', $child->id) }}" style="color:var(--navy);">{{ $child->full_name }}</a></td>
                                    <td>{{ $child->age ? $child->age . 'y' : '—' }}</td>
                                    <td style="font-size:12px;color:var(--text-muted);">{{ $child->disabilities->pluck('name')->join(', ') ?: '—' }}</td>
                                    <td><span class="badge-status badge-{{ $child->status }}">{{ ucfirst($child->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card-frc mb-3 assessment-structured-notes-card">
            <div class="card-header-frc assessment-structured-notes-card__header">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-notes-medical me-2" style="color:var(--teal);"></i>Structured assessment notes</h6>
                @if(auth()->user()->isSuperAdmin())
                    <p class="assessment-structured-notes-card__subtitle small text-muted mb-0">All therapist-authored structured notes (every status).</p>
                @elseif(auth()->user()->isAdmin())
                    <p class="assessment-structured-notes-card__subtitle small text-muted mb-0">Completed structured notes only.</p>
                @endif
            </div>
            @if($structuredNotesVisible->isEmpty())
                <div class="empty-state assessment-structured-notes-card__empty"><p class="mb-0 small text-muted">No structured notes visible for your role.</p></div>
            @else
                <div class="structured-notes-list">
                    @foreach($structuredNotesVisible as $note)
                        <div class="structured-note-card">
                            <div class="structured-note-card__meta">
                                <div class="structured-note-card__meta-main min-w-0">
                                    <span class="badge-status badge-{{ $note->status === 'completed' ? 'approved' : 'draft' }}">{{ str_replace('_',' ', $note->status) }}</span>
                                    @if($note->therapist)
                                        <span class="structured-note-card__therapist small text-muted">Therapist: {{ $note->therapist->full_name }}</span>
                                    @endif
                                </div>
                                <span class="structured-note-card__date">{{ $note->created_at?->format('d M Y H:i') }}</span>
                            </div>
                            <div class="structured-note-body">
                                @if($note->observation)<p><strong>Observation:</strong> {{ $note->observation }}</p>@endif
                                @if($note->child_response)<p><strong>Child response:</strong> {{ $note->child_response }}</p>@endif
                                @if($note->initial_recommendation)<p><strong>Recommendation:</strong> {{ $note->initial_recommendation }}</p>@endif
                                @if($note->additional_notes)<p style="margin-bottom:0;"><strong>Additional:</strong> {{ $note->additional_notes }}</p>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

@if(! in_array($assessment->status, ['completed','cancelled'], true))
<div class="modal fade" id="cancelAssessmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h6 class="modal-title">Cancel assessment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assessments.cancel', $assessment) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Why is this assessment being cancelled?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn-teal">Confirm cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
