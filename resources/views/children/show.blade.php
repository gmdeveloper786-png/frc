@extends('layouts.app')
@section('title', $child->full_name)
@section('page-title', 'Child Profile')

@section('content')
<div class="row g-3 child-show-page">
    {{-- Profile --}}
    <div class="col-12 col-md-4">
        <div class="card-frc child-show-profile-card">
            <div class="child-show-profile-header">
                <div class="child-show-profile-hero">
                    <div class="child-show-avatar">
                        {{ strtoupper(substr($child->full_name ?? '—', 0, 1)) }}
                    </div>
                    <h5 class="child-show-name">{{ $child->full_name ?? '—' }}</h5>
                    <span class="badge-status badge-{{ $child->status ?? 'pending' }}">{{ ucfirst(str_replace('_',' ',$child->status ?? 'pending')) }}</span>
                </div>
                @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <a href="{{ route('children.edit', $child->id) }}" class="btn-outline-teal btn-view-all child-show-edit-btn"><i class="fa-solid fa-pen"></i> Edit</a>
                @endif
            </div>
            <hr class="child-show-divider">
            <table class="enrollment-detail-kv child-show-detail-kv">
                <tr><td>GR Number</td><td class="fw-medium" style="font-family:monospace;letter-spacing:0.02em;">{{ $child->gr_number ?? '—' }}</td></tr>
                <tr><td>Branch</td><td class="fw-medium">{{ $child->branch?->displayLabel() ?? '—' }}</td></tr>
                <tr><td>Father</td><td class="fw-medium">{{ $child->father_name ?? '—' }}</td></tr>
                <tr><td>Email</td><td class="child-show-break-all">{{ $child->email ?? '—' }}</td></tr>
                <tr><td>Phone</td><td>{{ $child->phone_number ?? '—' }}</td></tr>
                <tr><td>WhatsApp</td><td>{{ $child->whatsapp_number ?? '—' }}</td></tr>
                <tr><td>Age</td><td>{{ $child->age ? $child->age . ' years' : '—' }}</td></tr>
                <tr><td>Gender</td><td>{{ ucfirst($child->gender ?? '—') }}</td></tr>
                <tr><td>DOB</td><td>{{ $child->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
                <tr><td>Address</td><td>{{ $child->address ?? '—' }}</td></tr>
            </table>
            @if($child->disabilities->isNotEmpty())
                <hr class="child-show-divider">
                <div class="child-show-section-title">Disabilities</div>
                <div class="child-show-tag-list">
                    @foreach($child->disabilities as $d)
                        <span class="child-show-tag">{{ $child->disabilityLabel($d) }}</span>
                    @endforeach
                </div>
            @endif
            @if($child->parent_notes)
                <hr class="child-show-divider">
                <div class="child-show-section-title">Parent Notes</div>
                <p class="child-show-notes">{{ $child->parent_notes ?? '—' }}</p>
            @endif
        </div>

        {{-- Approval Actions --}}
        @if($child->status === 'pending' && auth()->user()->canApproveChild($child))
            <div class="card-frc mt-3">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:14px;">Approval Actions</h6>
                <form action="{{ route('children.approve', $child->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn-teal" style="width:100%;justify-content:center;" onclick="return confirm('Approve this child?')">
                        <i class="fa-solid fa-check"></i> Approve Registration
                    </button>
                </form>
                <button class="btn-outline-teal" style="width:100%;justify-content:center;color:var(--danger);border-color:var(--danger);"
                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fa-solid fa-xmark"></i> Reject Registration
                </button>
            </div>
        @endif
    </div>

    {{-- Enrollments + Assessments --}}
    <div class="col-12 col-md-8">
        {{-- Enrollments --}}
        <div class="card-frc mb-3">
            <div class="card-header-frc card-header-frc--stack-sm">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-file-contract me-2" style="color:var(--teal);"></i>Enrollments</h6>
            </div>
            @if($child->enrollments->isEmpty())
                <div class="empty-state" style="padding:24px;"><p>No enrollments yet.</p></div>
            @else
                <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
                    <table class="table-frc mb-0">
                        <thead><tr><th>Branch</th><th>Therapist</th><th>Total Fee</th><th>Paid</th><th>Remaining</th><th>Payment</th><th>Enrollment</th><th>Actions</th></tr></thead>
                        <tbody>
                            @foreach($child->enrollments as $en)
                                <tr>
                                    <td style="white-space:nowrap;">{{ $en->branch?->name ?? '—' }}</td>
                                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                        <td style="white-space:nowrap;"><a href="{{ route('therapists.show', $en->therapist->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $en->therapist?->full_name ?? '—' }}</a></td>
                                    @else
                                        <td style="white-space:nowrap;">{{ $en->therapist?->full_name ?? '—' }}</td>
                                    @endif
                                    <td style="white-space:nowrap;">{{ frc_pkr($en->final_total ?? 0) }}</td>
                                    <td style="color:var(--success); white-space:nowrap;">{{ frc_pkr($en->paid_amount ?? 0) }}</td>
                                    <td style="color:var(--danger); white-space:nowrap;">{{ frc_pkr($en->remaining_amount ?? 0) }}</td>
                                    <td style="white-space:nowrap;"><span class="badge-status badge-{{ $en->payment_status ?? 'pending' }}">{{ ucfirst(str_replace('_',' ',$en->payment_status ?? 'pending')) }}</span></td>
                                    <td style="white-space:nowrap;"><span class="badge-status badge-{{ $en->status ?? 'pending' }}">{{ ucfirst(str_replace('_',' ',$en->status ?? 'pending')) }}</span></td>
                                    <td style="white-space:nowrap;"><a href="{{ route('enrollments.show', $en->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Assessments --}}
        <div class="card-frc">
            <div class="card-header-frc card-header-frc--stack-sm">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-clipboard-list me-2" style="color:var(--teal);"></i>Assessments</h6>
            </div>
            @if($child->assessments->isEmpty())
                <div class="empty-state" style="padding:24px;"><p>No assessments yet.</p></div>
            @else
                <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
                    <table class="table-frc mb-0">
                        <thead><tr><th>Date</th><th>Day</th><th>Time</th><th>Branch</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($child->assessments as $as)
                                <tr>
                                    <td style="white-space:nowrap;">{{ $as->date?->format('d M Y') ?? '—' }}</td>
                                    <td style="white-space:nowrap;">{{ $as->day }}</td>
                                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($as->time)?->format('h:i A') ?? '—' }}</td>
                                    <td style="white-space:nowrap;">{{ $as->branch?->name ?? '—' }}</td>
                                    <td style="white-space:nowrap;"><span class="badge-status badge-{{ $as->status ?? 'pending' }}">{{ ucfirst(str_replace('_',' ',$as->status ?? 'pending')) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border-soft);">
                <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Registration</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('children.reject', $child->id) }}" method="POST">
                @csrf
                <div class="modal-body form-frc">
                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Provide a reason..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                    <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-navy">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
