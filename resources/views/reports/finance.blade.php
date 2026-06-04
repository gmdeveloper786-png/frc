@extends('layouts.app')
@section('title', 'Finance Report')
@section('page-title', 'Finance Report')

@push('styles')
<style>
.finance-report-pagination {
    margin-top: 0;
    padding: 1rem 1rem 1.25rem;
    width: 100%;
    overflow-x: auto;
    border-top: 1px solid var(--border-soft, #e5eaf0);
}
.finance-report-pagination > nav {
    width: 100%;
    max-width: 100%;
}
.finance-report-pagination .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    width: 100%;
    gap: 1rem 2rem;
    flex-wrap: wrap;
    align-items: center !important;
}
.finance-report-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child {
    flex: 1 1 auto;
    min-width: 0;
}
.finance-report-pagination .d-none.flex-sm-fill.d-sm-flex > div:first-child p.small {
    margin-bottom: 0;
    line-height: 1.5;
}
.finance-report-pagination .d-none.flex-sm-fill.d-sm-flex > div:last-child {
    flex: 0 0 auto;
    margin-left: auto;
}
.finance-report-pagination div.d-flex.flex-fill.d-sm-none {
    justify-content: center;
    width: 100%;
}
.finance-report-pagination .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0;
}
.finance-report-pagination .page-link {
    border-radius: 8px;
    min-width: 2.25rem;
    text-align: center;
    color: var(--navy, #11517c);
}
.finance-report-pagination .page-item.active .page-link {
    background-color: var(--teal, #008080);
    border-color: var(--teal, #008080);
    color: #fff;
}
.finance-report-table.table-frc thead th {
    padding: 8px 10px;
    font-size: 12px;
}
.finance-report-table.table-frc tbody td {
    padding: 6px 10px;
    font-size: 13px;
    line-height: 1.25;
}
</style>
@endpush

@section('content')
@php
    $exportRoute = auth()->user()->isFinance() ? 'finance.reports.export' : 'reports.finance.export';
    $printRoute = auth()->user()->isFinance() ? 'finance.reports.print' : 'reports.finance.print';
    $receiptRoute = auth()->user()->isFinance() ? 'finance.payments.receipt' : 'payments.receipt';
    $staffClinical = auth()->user()->hasAnyRole(['super_admin','admin']);
    $filterQuery = http_build_query(request()->except('page'));
    $printQuery = http_build_query(array_merge(request()->except('page'), ['autoprint' => 1]));
@endphp

{{-- Filters --}}
<div class="card-frc mb-3">
    <form method="GET" class="row g-2 form-frc align-items-end">
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Branch</label>
            <select name="branch_id" class="form-control">
                <option value="">All Branches</option>
                @foreach($branches ?? [] as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->displayLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Therapist</label>
            <select name="therapist_id" class="form-control">
                <option value="">All Therapists</option>
                @foreach($therapists ?? [] as $th)
                    <option value="{{ $th->id }}" {{ (string) request('therapist_id') === (string) $th->id ? 'selected' : '' }}>{{ $th->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Service</label>
            <select name="service_id" class="form-control">
                <option value="">All Services</option>
                @foreach($services ?? [] as $svc)
                    <option value="{{ $svc->id }}" {{ (string) request('service_id') === (string) $svc->id ? 'selected' : '' }}>{{ $svc->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Payment Method</label>
            <select name="payment_method" class="form-control">
                <option value="">All Methods</option>
                @foreach(['cash','bank_transfer','easypaisa','jazzcash','card','other'] as $m)
                    <option value="{{ $m }}" {{ request('payment_method') == $m ? 'selected' : '' }}>{{ \App\Models\Payment::labelForPaymentMethod($m) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Payment Status</label>
            <select name="enrollment_payment_status" class="form-control">
                <option value="">All</option>
                @foreach(['unpaid','partial_paid','fully_paid','overdue'] as $s)
                    <option value="{{ $s }}" {{ request('enrollment_payment_status') == $s ? 'selected' : '' }}>{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Verification Status</label>
            <select name="verification_status" class="form-control">
                <option value="">All</option>
                @foreach(['pending_verification','paid','rejected','cancelled','refunded'] as $s)
                    <option value="{{ $s }}" {{ request('verification_status') == $s ? 'selected' : '' }}>{{ \App\Models\Payment::labelForVerificationStatus($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Child Name</label>
            <input type="text" name="child_search" value="{{ request('child_search') }}" class="form-control" placeholder="Search child...">
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">GR Number</label>
            <input type="text" name="gr_number" value="{{ request('gr_number') }}" class="form-control" placeholder="FRC-CH-000002">
        </div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-lg-2 col-md-4"><label style="font-size:12px;">Per page</label>
            <select name="per_page" class="form-control">
                @foreach([5, 10, 15, 25, 50] as $n)
                    <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button type="submit" class="btn-teal">Filter</button></div>
        @if(request()->hasAny(['branch_id','therapist_id','service_id','payment_method','date_from','date_to','enrollment_payment_status','verification_status','child_search','gr_number','per_page']))
            <div class="col-auto"><a href="{{ auth()->user()->isFinance() ? route('finance.reports') : route('reports.finance') }}" class="btn-outline-teal">Clear</a></div>
        @endif
    </form>
</div>

{{-- Export --}}
<div class="card-frc mb-3" style="padding:12px 16px;">
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span style="font-size:13px;font-weight:600;color:var(--navy);margin-right:8px;"><i class="fa-solid fa-download me-1" style="color:var(--teal);"></i> Export</span>
        <a href="{{ route($exportRoute, ['format' => 'csv']) }}?{{ $filterQuery }}" class="btn-outline-teal" style="font-size:12px;padding:6px 12px;">CSV</a>
        <a href="{{ route($exportRoute, ['format' => 'pdf']) }}?{{ $filterQuery }}" target="_blank" rel="noopener" class="btn-outline-teal" style="font-size:12px;padding:6px 12px;">PDF</a>
        <a href="{{ route($printRoute) }}?{{ $printQuery }}" target="_blank" rel="noopener" class="btn-outline-teal" style="font-size:12px;padding:6px 12px;"> Print</a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    @php
        $stats = [
            ['label'=>'Total Expected','value'=>$summary['total_expected'] ?? 0,'iconClass'=>'navy','icon'=>'fa-money-bill-trend-up'],
            ['label'=>'Total Paid','value'=>$summary['total_paid'] ?? 0,'iconClass'=>'green','icon'=>'fa-circle-check'],
            ['label'=>'Pending / Overdue','value'=>$summary['total_pending'] ?? 0,'iconClass'=>'red','icon'=>'fa-hourglass-half'],
            ['label'=>'Cash Received','value'=>$summary['cash_received'] ?? 0,'iconClass'=>'teal','icon'=>'fa-money-bills'],
            ['label'=>'Online/Bank','value'=>$summary['online_received'] ?? 0,'iconClass'=>'purple','icon'=>'fa-mobile-screen'],
            ['label'=>'Pending Verification Amount','value'=>$summary['pending_verification'] ?? 0,'iconClass'=>'orange','icon'=>'fa-money-bill-trend-up'],
        ];
        $valueColor = [
            'navy' => 'var(--navy)',
            'green' => 'var(--success)',
            'red' => 'var(--danger)',
            'teal' => 'var(--teal-dark)',
            'purple' => '#7c3aed',
            'orange' => '#e08000',
        ];
    @endphp
    @foreach($stats as $s)
        <div class="col-md-4 col-sm-6">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $s['iconClass'] }}"><i class="fa-solid {{ $s['icon'] }}"></i></div>
                <div class="stat-body">
                    <div class="stat-value" style="font-size:16px;color:{{ $valueColor[$s['iconClass']] }};">{{ frc_pkr($s['value']) }}</div>
                    <div class="stat-label">{{ $s['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>  

{{-- Payment Table --}}
<div class="card-frc">
    <div class="card-header-frc">
        <h6 class="card-title-frc"><i class="fa-solid fa-table me-2" style="color:var(--teal);"></i>Payment Records ({{ $records->total() }})</h6>
    </div>
    @if(!$records || $records->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-receipt empty-icon"></i><h5>No Records Found</h5></div>
    @else
        <div class="table-responsive">
            <table class="table-frc finance-report-table">
                <thead><tr>
                    <th>#</th>
                    <th>Receipt</th><th>GR No</th><th>Enroll#</th><th>Child</th><th>Status</th><th>Branch</th><th>Therapist</th><th>Service</th>
                    <th>Total Fee</th><th>Paid</th><th>Remaining</th><th>Payment</th>
                    <th>Amount</th><th>Verification</th><th>Method</th><th>Date</th><th>Actions</th>
                </tr></thead>
                <tbody>
                    @foreach($records as $payment)
                        @php
                            $enrollment = $payment->enrollment;
                            $child = $payment->child ?? $enrollment?->child;
                        @endphp
                        <tr>
                            <td style="font-weight:700;font-family:'Poppins',sans-serif;font-size:13px;color:var(--navy);">{{ $records->firstItem() + $loop->index }}</td>
                            <td style="font-weight:700;font-family:'Poppins',sans-serif;font-size:13px;color:var(--navy); white-space:nowrap;">{{ $payment->hasPrintableReceipt() ? $payment->receipt_number : '—' }}</td>
                            <td style="font-family:monospace;font-size:12px;color:var(--navy); white-space:nowrap;">{{ $child?->gr_number ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--navy); white-space:nowrap;"> <a href="{{ route('enrollments.show', $enrollment?->id) }}" style="color:var(--navy);text-decoration:underline;">#{{ $enrollment?->id ?? '—' }}</a></td>
                            <td style="font-weight:500; white-space:nowrap;">
                                @if($child)
                                    <a href="{{ route('children.show', $child->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $child->full_name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                       
                            <td style="white-space:nowrap;">
                                @if($child)
                                    <span class="badge-status badge-{{ $child->status }}">{{ \Illuminate\Support\Str::title(str_replace('_',' ', $child->status)) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-size:13px; white-space:nowrap;">{{ $enrollment?->branch?->name ?? '—' }}</td>
                            @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                <td style="font-size:13px; white-space:nowrap;">
                                    <a href="{{ route('therapists.show', $enrollment?->therapist?->id) }}" style="color:var(--navy);text-decoration:underline;">{{ $enrollment?->therapist?->full_name ?? '—' }}</a>
                                </td>
                            @else
                                <td style="font-size:13px; white-space:nowrap;">{{ $enrollment?->therapist?->full_name ?? '—' }}</td>
                            @endif
                            <td style="font-size:13px; white-space:nowrap;">{{ $enrollment?->service?->name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ $enrollment ? frc_pkr($enrollment->final_total) : '—' }}</td>
                            <td style="color:var(--success); white-space:nowrap;">{{ $enrollment ? frc_pkr($enrollment->paid_amount) : '—' }}</td>
                            <td style="color:var(--danger); white-space:nowrap;">{{ $enrollment ? frc_pkr($enrollment->remaining_amount) : '—' }}</td>
                            <td style="white-space:nowrap;">
                                @if($enrollment)
                                    <span class="badge-status badge-{{ $enrollment->payment_status }}">{{ \App\Models\Payment::labelForEnrollmentPaymentStatus($enrollment->payment_status) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="color:var(--teal);font-weight:600; white-space:nowrap;">{{ frc_pkr($payment->amount) }}</td>
                            <td>
                                <span class="badge-status badge-{{ $payment->status }}">{{ \App\Models\Payment::labelForVerificationStatus($payment->status)
                                    ? ucfirst(str_replace('_',' ',$payment->status)) : '—' }}</span>
                                @if($payment->status === 'rejected' && filled($payment->rejection_reason))
                                    <div class="mt-1">
                                        <button
                                            type="button"
                                            class="btn-outline-teal"
                                            style="font-size:11px;padding:3px 8px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#financeRejectReasonModal"
                                            data-reason="{{ e($payment->rejection_reason) }}"
                                        >
                                            Reason
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:13px; white-space:nowrap;">{{ \App\Models\Payment::labelForPaymentMethod($payment->payment_method) ?: '—' }}</td>
                            <td style="font-size:13px;color:var(--text-muted); white-space:nowrap;">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td style="min-width:168px;">
                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    @if($payment->hasPrintableReceipt())
                                        <a href="{{ route($receiptRoute, $payment->id) }}" class="btn-outline-teal" target="_blank" style="font-size:11px;padding:3px 8px;" title="View Fee Receipt">Receipt</a>
                                    @else
                                        <span style="font-size:11px;color:var(--text-muted);">—</span>
                                    @endif
                                    @if($payment->payment_slip)
                                        <a href="{{ $payment->payment_slip_url }}" target="_blank" rel="noopener" class="btn-outline-teal" style="font-size:11px;padding:3px 8px;" title="View Fee Slip">Fee Slip</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="finance-report-pagination" aria-label="Finance report payment pages">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>

<div class="modal fade" id="financeRejectReasonModal" tabindex="-1" aria-labelledby="financeRejectReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border-soft);overflow:hidden;">
            <div class="modal-header" style="background:#fde8e8;border-bottom:1px solid var(--border-soft);">
                <h5 class="modal-title" id="financeRejectReasonModalLabel" style="font-family:'Poppins',sans-serif;font-weight:600;color:var(--danger);font-size:17px;">
                    Rejection Reason
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="financeRejectReasonText" style="white-space:pre-wrap;color:var(--text-dark);"></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-soft);">
                <button type="button" class="btn-outline-teal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('financeRejectReasonModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const text = modal.querySelector('#financeRejectReasonText');
        if (!text) return;
        text.textContent = trigger?.getAttribute('data-reason') || 'No reason provided.';
    });
})();
</script>
@endpush
