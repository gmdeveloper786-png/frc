@extends('layouts.app')
@section('title', 'Enrollment Details')
@section('page-title', 'Enrollment Details')

@section('content')
@php
    $slotsCount = $enrollment->schedules->count();
    $scheduleCardTitle = $enrollment->repeat_weekly
        ? 'Weekly Schedule Slots ('.$slotsCount.')'
        : 'Session Schedule ('.$slotsCount.')';
    $hasAnySessionDate = $enrollment->schedules->contains(fn ($s) => filled($s->session_date));
    $showRecurringDateNote = (bool) $enrollment->repeat_weekly && ! $hasAnySessionDate;
    $remainingPaid = (float) $enrollment->remaining_amount <= 0;
    $highDiscount = $enrollment->isHighDiscount();
    $paymentShowRoute = auth()->user()->isFinance() ? 'finance.payments.show' : 'payments.show';
    $paymentReceiptRoute = auth()->user()->isFinance() ? 'finance.payments.receipt' : 'payments.receipt';
    $paymentVerifyRoute = auth()->user()->isFinance() ? 'finance.payments.verify' : 'payments.verify';
    $manualPaymentCreateRoute = auth()->user()->isFinance() ? 'finance.payments.manual.create' : 'payments.manual.create';
@endphp

<div class="row g-3 enrollment-show-page">
    <div class="col-12 col-md-5">
        <div class="card-frc mb-3">
            <h6 class="enrollment-show-card-title">Enrollment Summary</h6>
            <table class="enrollment-detail-kv enrollment-detail-kv--summary">
                <tr><td style="color:var(--text-muted);padding:6px 0;">Child</td><td style="font-weight:500;"><a href="{{ route('children.show', $enrollment->child->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $enrollment->child?->full_name }}</a></td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Branch</td><td>{{ $enrollment->branch?->name }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Service</td><td>{{ $enrollment->service?->name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Therapist</td><td>{{ $enrollment->therapist?->full_name }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">First session</td><td style="font-weight:500;">{{ $enrollment->schedule_start_date?->format('d M Y') ?? $enrollment->created_at?->format('d M Y') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Price/Session</td><td>PKR {{ number_format($enrollment->price_per_session) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Sessions</td><td>{{ $enrollment->total_sessions }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Fee Before Discount / Subtotal</td><td>PKR {{ number_format($enrollment->subtotal) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Discount Amount</td><td>{{ $enrollment->discount_percentage }}% (PKR {{ number_format($enrollment->discount_amount) }})</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Final Payable Amount</td><td style="font-weight:700;color:var(--navy);">PKR {{ number_format($enrollment->final_total) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Paid</td><td style="color:var(--success);font-weight:600;">PKR {{ number_format($enrollment->paid_amount) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Remaining</td><td style="color:var(--danger);font-weight:600;">PKR {{ number_format($enrollment->remaining_amount) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Status</td><td><span class="badge-status badge-{{ $enrollment->status }}">{{ ucfirst(str_replace('_',' ',$enrollment->status)) }}</span></td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Payment</td><td><span class="badge-status badge-{{ $enrollment->payment_status }}">{{ ucfirst(str_replace('_',' ',$enrollment->payment_status)) }}</span></td></tr>
            </table>
        </div>

        <div class="card-frc mb-3">
            <h6 class="enrollment-show-card-title">Enrollment record</h6>
            <table class="enrollment-detail-kv">
                <tr><td style="color:var(--text-muted);padding:6px 0;">Enrollment ID</td><td style="font-weight:600;">#{{ $enrollment->id }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Created by</td><td>{{ $enrollment->createdBy?->full_name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Created at</td><td>{{ $enrollment->created_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Approved by</td><td>{{ $enrollment->approvedBy?->full_name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Approved at</td><td>{{ $enrollment->approved_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Last updated by</td><td>{{ $enrollment->updatedBy?->full_name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:6px 0;">Last updated at</td><td>{{ $enrollment->updated_at?->format('d M Y H:i') ?? '—' }}</td></tr>
            </table>
        </div>

        @if($highDiscount)
            <div class="card-frc mb-3 enrollment-show-high-discount">
                <h6 class="enrollment-show-card-title"><i class="fa-solid fa-percent me-2" style="color:var(--teal);"></i>High discount (over {{ $frc['high_discount_threshold'] }}%)</h6>
                @php
                    $hdStatus = match (true) {
                        $enrollment->status === 'rejected' => 'Rejected',
                        $enrollment->status === 'pending_super_admin_approval' => 'Pending super admin approval',
                        in_array($enrollment->status, ['approved', 'active', 'completed'], true) => 'Approved',
                        default => ucfirst(str_replace('_', ' ', $enrollment->status)),
                    };
                @endphp
                <table class="enrollment-detail-kv">
                    <tr><td style="color:var(--text-muted);padding:6px 0;vertical-align:top;">Discount reason</td><td style="padding:6px 0;">{{ filled($enrollment->discount_reason) ? $enrollment->discount_reason : '—' }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:6px 0;vertical-align:top;">Supporting file</td><td style="padding:6px 0;">
                        @if($enrollment->discount_file)
                            <a href="{{ asset('storage/' . $enrollment->discount_file) }}" target="_blank" rel="noopener" class="btn-outline-teal" style="font-size:12px;padding:4px 10px;"><i class="fa-solid fa-file"></i> View file</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td></tr>
                    <tr><td style="color:var(--text-muted);padding:6px 0;">Approval status</td><td style="padding:6px 0;"><span class="badge-status badge-{{ $enrollment->status }}">{{ $hdStatus }}</span></td></tr>
                    @if(in_array($enrollment->status, ['approved', 'active', 'completed'], true))
                        <tr><td style="color:var(--text-muted);padding:6px 0;">Approved by</td><td>{{ $enrollment->approvedBy?->full_name ?? '—' }}</td></tr>
                        <tr><td style="color:var(--text-muted);padding:6px 0;">Approval date</td><td>{{ $enrollment->approved_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    @endif
                    @if($enrollment->status === 'rejected')
                        <tr><td style="color:var(--text-muted);padding:6px 0;">Rejected by</td><td>{{ $enrollment->rejectedBy?->full_name ?? '—' }}</td></tr>
                        <tr><td style="color:var(--text-muted);padding:6px 0;">Rejection date</td><td>{{ $enrollment->rejected_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                        <tr><td style="color:var(--text-muted);padding:6px 0;vertical-align:top;">Rejection reason</td><td style="padding:6px 0;color:var(--danger);white-space:pre-wrap;">{{ filled($enrollment->rejection_reason) ? $enrollment->rejection_reason : '—' }}</td></tr>
                    @endif
                </table>
            </div>
        @elseif($enrollment->discount_file)
            <div class="card-frc mb-3">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:8px;">Discount support document</h6>
                <a href="{{ asset('storage/' . $enrollment->discount_file) }}" target="_blank" class="btn-outline-teal">
                    <i class="fa-solid fa-file"></i> View document
                </a>
                @if($enrollment->discount_reason)
                    <p style="font-size:13px;color:var(--text-muted);margin-top:10px;">{{ $enrollment->discount_reason }}</p>
                @endif
            </div>
        @endif

        {{-- Actions --}}
        @if(in_array($enrollment->status, ['draft','pending_super_admin_approval']))
            <div class="card-frc">
                <h6 style="font-family:'Poppins',sans-serif;color:var(--navy);margin-bottom:12px;">Actions</h6>
                <form action="{{ route('enrollments.approve', $enrollment->id) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn-teal" style="width:100%;justify-content:center;" onclick="return confirm('Approve?')">
                        <i class="fa-solid fa-check"></i> Approve Enrollment
                    </button>
                </form>
                <button class="btn-outline-teal" style="width:100%;justify-content:center;color:var(--danger);border-color:var(--danger);" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fa-solid fa-xmark"></i> Reject
                </button>
            </div>
        @endif
    </div>

    <div class="col-12 col-md-7">
        {{-- Schedule --}}
        <div class="card-frc mb-3">
            <div class="card-header-frc card-header-frc--stack-sm flex-column align-items-stretch gap-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 enrollment-show-schedule-header">
                    <h6 class="card-title-frc mb-0"><i class="fa-solid fa-calendar me-2" style="color:var(--teal);"></i>{{ $scheduleCardTitle }}</h6>
                    @can('viewFullSchedule', $enrollment)
                        <a href="{{ route('enrollments.schedule', $enrollment) }}" class="btn-outline-teal btn-view-all enrollment-show-schedule-btn">
                            <i class="fa-solid fa-calendar-days"></i> View Full Schedule
                        </a>
                    @endcan
                </div>
                @if($enrollment->repeat_weekly || $enrollment->total_sessions)
                    <div class="small text-muted" style="font-weight:500;color:var(--navy)!important;">
                        Total programme sessions: {{ $enrollment->total_sessions }}
                        @if($enrollment->schedule_start_date)
                            · Starts {{ $enrollment->schedule_start_date->format('d M Y') }}
                        @endif
                    </div>
                @endif
            </div>
            @if($enrollment->schedules->isEmpty())
                <div class="empty-state" style="padding:24px;"><p>No schedule set.</p></div>
            @else
                <div class="frc-table-wrap frc-table-wrap--wide table-scroll">
                    <table class="table-frc mb-0">
                        <thead><tr><th>Day</th><th>Time slot</th><th>Therapist</th><th>Service</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($enrollment->schedules as $s)
                                @php
                                    $therapist = $s->therapist ?? $enrollment->therapist;
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $s->day }}</td>
                                    <td class="text-nowrap">{{ $s->time_slot }}</td>
                                    <td class="text-nowrap">
                                        @if($therapist)
                                            <a href="{{ route('therapists.show', $therapist->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $therapist->full_name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $enrollment->service?->name ?? '—' }}</td>
                                    <td class="text-nowrap"><span class="badge-status badge-{{ $s->status == 'scheduled' ? 'active' : $s->status }}">{{ ucfirst(str_replace('_',' ', $s->status)) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Payments --}}
        <div class="card-frc">
            <div class="card-header-frc card-header-frc--stack-sm flex-wrap gap-2">
                <h6 class="card-title-frc mb-0"><i class="fa-solid fa-receipt me-2" style="color:var(--teal);"></i>Payments</h6>
                @if(in_array($enrollment->status, ['approved','active']) && auth()->user()->hasPermission('manage_payments'))
                    @if($remainingPaid)
                        <span class="d-inline-block" style="cursor:not-allowed;">
                            <button type="button" class="btn-teal btn-view-all" style="opacity:0.55;pointer-events:none;" disabled aria-disabled="true">
                                <i class="fa-solid fa-plus"></i> Add Payment
                            </button>
                        </span>
                    @else
                        <a href="{{ route($manualPaymentCreateRoute) }}?enrollment_id={{ $enrollment->id }}" class="btn-teal btn-view-all">
                            <i class="fa-solid fa-plus"></i> Add Payment
                        </a>
                    @endif
                @endif
            </div>
            @if($enrollment->payments->isEmpty())
                <div class="empty-state" style="padding:24px;"><p>No payments recorded yet.</p></div>
            @else
                <div class="frc-table-wrap frc-table-wrap--wide table-scroll enrollment-show-payments-table">
                    <table class="table-frc mb-0 child-payments-table">
                        <thead><tr><th>Receipt #</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($enrollment->payments as $p)
                                <tr>
                                    <td class="child-payments-receipt">{{ $p->hasPrintableReceipt() ? $p->receipt_number : '—' }}</td>
                                    <td class="text-amount child-payments-amount">PKR {{ number_format($p->amount, 2) }}</td>
                                    <td class="child-payments-method">{{ \App\Models\Payment::labelForPaymentMethod($p->payment_method) }}</td>
                                    <td class="child-payments-date">{{ $p->payment_date?->format('d M Y') }}</td>
                                    <td class="child-payments-status"><span class="badge-status badge-{{ $p->status }}">{{ ucfirst(str_replace('_',' ',$p->status)) }}</span></td>
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
                <h6 class="modal-title" style="font-family:'Poppins',sans-serif;color:var(--navy);">Reject Enrollment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('enrollments.reject', $enrollment->id) }}" method="POST">
                @csrf
                <div class="modal-body form-frc">
                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
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
