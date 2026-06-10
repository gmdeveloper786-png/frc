@extends('layouts.app')
@section('title', 'Enrollments')
@section('page-title', 'Enrollments')

@section('content')
<div class="card-frc card-frc--list-page">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-file-contract me-2" style="color:var(--teal);"></i>Enrollments ({{ $enrollments->total() }})</h6>
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
            <a href="{{ route('enrollments.create') }}" class="btn-teal btn-view-all" style="white-space:nowrap;">
                <i class="fa-solid fa-plus"></i> New Enrollment
            </a>
        @endif
   
    </div>
    <form method="GET" class="p-3 border-bottom list-filters" style="border-color:var(--border-soft)!important;">
        @if($branches->count() === 1)
            <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
        @endif
        <div class="row g-2 align-items-end form-frc">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Child name or GR number">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Enrollment Status</label>
                <select name="status" class="form-control">
                    <option value="">All Enrollment Status</option>
                    @foreach(['draft','pending_super_admin_approval','rejected','active','completed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Payment Status</label>
                <select name="payment_status" class="form-control">
                    <option value="">All Payment Status</option>
                    @foreach(['unpaid','partial_paid','fully_paid','overdue'] as $s)
                        <option value="{{ $s }}" {{ request('payment_status') == $s ? 'selected' : '' }}>{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($s) }}</option>
                    @endforeach
                </select>
            </div>
            @if($branches->count() > 1)
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Branch</label>
                <select name="branch_id" class="form-control">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->displayLabel() }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Service</label>
                <select name="service_id" class="form-control">
                    <option value="">All Services</option>
                    @foreach($services as $svc)
                        <option value="{{ $svc->id }}" {{ (string) request('service_id') === (string) $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Start date from</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small text-muted mb-1">Start date to</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-12 filter-actions">
                <button type="submit" class="btn-teal">Filter</button>
                @if(request()->hasAny(['search', 'status', 'payment_status', 'branch_id', 'service_id', 'date_from', 'date_to']))
                    <a href="{{ route('enrollments.index') }}" class="btn-outline-teal" style="justify-content:center;">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($enrollments->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-file-contract empty-icon"></i><h5>No Enrollments Found</h5></div>
    @else
        <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
            <table class="table-frc mb-0">
                <thead><tr><th>#</th><th>Start date</th><th>Child</th><th>Branch</th><th>Therapist</th><th>Service</th><th>Total Fee</th><th>Paid</th><th>Remaining</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($enrollments as $e)
                        <tr>
                            
                            <td style="color:var(--text-muted);">{{ $enrollments->firstItem() + $loop->index }}</td>
                            <td style="font-weight:500; white-space:nowrap;">{{ ($e->schedule_start_date ?? $e->created_at)?->format('d M Y') ?? '—'
                                }}</td>
                            <td style="font-weight:500; white-space:nowrap;">
                                <a href="{{ route('children.show', $e->child_id) }}" style="color:var(--navy);">{{ $e->child?->full_name }}</a>
                                @if($e->child?->gr_number)
                                    <span style="display:block;font-size:12px;color:var(--text-muted);font-family:monospace;">{{ $e->child->gr_number }}</span>
                                @endif
                                @if($e->isGroupEnrollment())
                                    <span class="badge-status badge-draft" style="font-size:10px;margin-left:4px;" title="Group therapy">Group ({{ $e->groupSize() }})</span>
                                @endif
                            </td>
                            <td style="font-size:13px; white-space:nowrap;">{{ $e->branch?->name }}</td>
                            @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                <td style="font-size:13px; white-space:nowrap;">
                                    <a href="{{ route('therapists.show', $e->therapist->id) }}" style="color:var(--navy);text-decoration:underline;">
                                        {{ $e->therapist?->full_name }}
                                    </a>
                                </td>
                            @else
                                <td style="font-size:13px; white-space:nowrap;">
                                    {{ $e->therapist?->full_name }}
                                </td>
                            @endif
                       
                            <td style="font-size:13px; white-space:nowrap;">{{ $e->service?->name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ frc_pkr($e->final_total ?? 0) }}</td>
                            <td style="color:var(--success); white-space:nowrap;">{{ frc_pkr($e->paid_amount ?? 0) }}</td>
                            <td style="color:var(--danger); white-space:nowrap;">{{ frc_pkr($e->remaining_amount ?? 0) }}</td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ $e->payment_status }}">{{
                                                                \App\Models\Payment::labelForEnrollmentPaymentStatus($e->payment_status) ?? '—' }}</span></td>
                            <td style="white-space:nowrap;"><span class="badge-status badge-{{ $e->status }}">{{ ucfirst(str_replace('_',' ',$e->status)) }}</span></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="{{ route('enrollments.show', $e->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-eye"></i></a>
                                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                        <a href="{{ route('enrollments.edit', $e->id) }}" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-pen"></i></a>
                                    @endif
                                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                        <form action="{{ route('enrollments.destroy', $e->id) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:1.5px solid var(--danger);color:var(--danger);border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;" data-confirm="Delete this enrollment?"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                    @endif
                                    @if($e->status === 'draft' && auth()->user()?->hasPermission('manage_enrollments'))
                                        <form action="{{ route('enrollments.approve', $e->id) }}" method="POST">@csrf
                                            <button type="submit" style="background:var(--success);color:#fff;border:none;border-radius:var(--radius-btn);padding:4px 10px;cursor:pointer;font-size:12px;" data-confirm="Approve?"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($enrollments->hasPages())
            <div class="frc-list-pagination" aria-label="Enrollments list pages">
                {{ $enrollments->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
