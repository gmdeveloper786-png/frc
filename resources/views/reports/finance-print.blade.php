<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Report</title>
<style>
    :root {
        --navy: #11517c;
        --teal: #008080;
        --success: #16a34a;
        --danger: #dc2626;
        --text-muted: #64748b;
        --border-soft: #e2e8f0;
        --bg-light: #f8fafc;
    }
    * { box-sizing: border-box; }
    @page {
        size: A4 landscape;
        margin: 0;
    }
    html, body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', system-ui, sans-serif;
        font-size: 12px;
        color: #1e293b;
        background: #fff;
    }
    body { padding: 16px 20px; }
    @media print {
        html, body {
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            padding: 8mm 10mm !important;
        }
        .no-print { display: none !important; }
        .print-sheet {
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
    }
    .print-actions { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-teal, .btn-outline-teal {
        font-family: inherit;
        font-size: 13px;
        padding: 8px 14px;
        border-radius: 8px;
        cursor: pointer;
        border: 1px solid var(--teal);
    }
    .btn-teal { background: var(--teal); color: #fff; }
    .btn-outline-teal { background: #fff; color: var(--teal); }
    h5 {
        font-size: 15px;
        font-weight: 600;
        color: var(--navy);
        margin: 0 0 10px;
    }
    .mini-sum {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 11px;
        color: var(--navy);
    }
    .mini-sum span {
        background: var(--bg-light);
        padding: 5px 8px;
        border-radius: 6px;
        border: 1px solid var(--border-soft);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    thead th {
        background: var(--bg-light);
        color: var(--navy);
        font-weight: 600;
        padding: 5px 6px;
        text-align: left;
        border-bottom: 2px solid var(--border-soft);
        white-space: nowrap;
    }
    tbody td {
        padding: 4px 6px;
        border-bottom: 1px solid #f0f4f8;
        vertical-align: middle;
        line-height: 1.2;
    }
    tbody tr:last-child td { border-bottom: none; }
</style>
</head>
<body>
<div class="print-sheet">
    <div class="no-print print-actions">
        <button type="button" data-window-print class="btn-teal">Print</button>
        <button type="button" data-window-close class="btn-outline-teal">Close</button>
    </div>
    <h5>Finance Report — {{ frc_datetime() }} ({{ count($rows) }} records)</h5>
    <div class="mini-sum">
        <span><strong>Total Expected</strong> {{ frc_pkr($summary['total_expected'] ?? 0) }}</span>
        <span><strong>Total Paid</strong> {{ frc_pkr($summary['total_paid'] ?? 0) }}</span>
        <span><strong>Pending</strong> {{ frc_pkr($summary['total_pending'] ?? 0) }}</span>
        <span><strong>Cash</strong> {{ frc_pkr($summary['cash_received'] ?? 0) }}</span>
        <span><strong>Online/Bank</strong> {{ frc_pkr($summary['online_received'] ?? 0) }}</span>
        <span><strong>Pending Verif.</strong> {{ frc_pkr($summary['pending_verification'] ?? 0) }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:28px;text-align:center;">#</th>
                <th>Receipt#</th>
                <th>GR No</th>
                <th>Enroll#</th>
                <th>Child</th>
                <th>Status</th>
                <th>Branch</th>
                <th>Therapist</th>
                <th>Service</th>
                <th>Total Fee</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Payment</th>
                <th>Amount</th>
                <th>Verification</th>
                <th>Method</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td style="text-align:center;font-weight:600;color:var(--navy);">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $r['receipt'] }}</td>
                    <td style="font-size:10px;">{{ $r['child_gr_number'] }}</td>
                    <td style="font-size:10px;">{{ $r['enrollment_id'] }}</td>
                    <td>{{ $r['child_name'] }}</td>
                    <td>
                        @if(strtolower($r['child_status']) === 'approved')
                            <span style="color:green;font-weight:600;">Approved</span>
                        @elseif(strtolower($r['child_status']) === 'active')
                            <span style="color:green;font-weight:600;">Active</span>
                        @elseif(strtolower($r['child_status']) === 'inactive')
                            <span style="color:red;font-weight:600;">Inactive</span>
                        @elseif(strtolower($r['child_status']) === 'pending')
                            <span style="color:orange;font-weight:600;">Pending</span>
                        @elseif(strtolower($r['child_status']) === 'rejected')
                            <span style="color:red;font-weight:600;">Rejected</span>
                        @else
                            <span style="color:gray;font-weight:600;">{{ $r['child_status'] }}</span>
                        @endif
                    </td>
                    <td>{{ $r['branch'] }}</td>
                    <td>{{ $r['therapist'] }}</td>
                    <td>{{ $r['service'] }}</td>
                    <td style="font-weight:600;color:var(--teal);">{{ $r['enrollment_total'] !== '—' ? 'PKR '.$r['enrollment_total'] : '—' }}</td>
                    <td style="font-weight:600;color:var(--success);">{{ $r['enrollment_paid'] !== '—' ? 'PKR '.$r['enrollment_paid'] : '—' }}</td>
                    <td style="font-weight:600;color:var(--danger);">{{ $r['enrollment_remaining'] !== '—' ? 'PKR '.$r['enrollment_remaining'] : '—' }}</td>
                    <td
                        @if(strtolower($r['enrollment_payment_status']) === 'partial paid')
                            style="color:orange;font-weight:600;"
                        @elseif(strtolower($r['enrollment_payment_status']) === 'fully paid')
                            style="color:green;font-weight:600;"
                        @elseif(strtolower($r['enrollment_payment_status']) === 'unpaid')
                            style="color:red;font-weight:600;"
                        @elseif(strtolower($r['enrollment_payment_status']) === 'overdue')
                            style="color:red;font-weight:600;"
                        @endif
                    >
                        {{ \App\Models\Payment::labelForEnrollmentPaymentStatus($r['enrollment_payment_status']) }}
                    </td>
               
                    <td style="font-weight:600;color:var(--teal);">{{ $r['amount'] !== '—' ? 'PKR '.$r['amount'] : '—' }}</td>
                    <td
                        @if(strtolower($r['verification_status']) === 'pending_verification')
                            style="color:orange;font-weight:600;"
                        @elseif(strtolower($r['verification_status']) === 'rejected')
                            style="color:red;font-weight:600;"
                        @elseif(strtolower($r['verification_status']) === 'paid')
                            style="color:green;font-weight:600;"
                        @endif
                    >
                        {{ \App\Models\Payment::labelForVerificationStatus($r['verification_status']) }}
                    </td>
                    <td>{{ $r['payment_method'] }}</td>
                    <td>{{ $r['payment_date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script src="{{ asset('js/frc-core.js') }}" nonce="{{ $cspNonce }}"></script>
@if(request('autoprint'))
<script nonce="{{ $cspNonce }}">
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 400);
});
</script>
@endif
</body>
</html>
