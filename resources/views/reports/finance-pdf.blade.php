<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
    h1 { font-size: 14px; margin: 0 0 8px 0; color: #0f766e; }
    .sum { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
    .sum td { padding: 2px 5px; border: 1px solid #cbd5e1; line-height: 1.2; }
    .sum td:first-child { font-weight: bold; background: #f1f5f9; width: 28%; }
    table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.data th, table.data td {
        border: 1px solid #cbd5e1;
        padding: 1px 3px;
        text-align: left;
        vertical-align: middle;
        word-wrap: break-word;
        line-height: 1.15;
    }
    table.data th { background: #f1f5f9; font-size: 8px; padding: 2px 3px; }
    table.data tbody td { font-size: 8px; }
    .num { text-align: right; white-space: nowrap; }
    .seq { text-align: center; white-space: nowrap;}
</style>
</head>
<body>
<h1>Finance Report — {{ frc_datetime() }} ({{ count($rows) }} records)</h1>
<table class="sum">
    <tr><td>Total Expected</td><td class="num">PKR {{ frc_money(($summary['total_expected'] ?? 0)) }}</td></tr>
    <tr><td>Total Paid</td><td class="num">PKR {{ frc_money(($summary['total_paid'] ?? 0)) }}</td></tr>
    <tr><td>Pending</td><td class="num">PKR {{ frc_money(($summary['total_pending'] ?? 0)) }}</td></tr>
    <tr><td>Cash Received</td><td class="num">PKR {{ frc_money(($summary['cash_received'] ?? 0)) }}</td></tr>
    <tr><td>Online/Bank</td><td class="num">PKR {{ frc_money(($summary['online_received'] ?? 0)) }}</td></tr>
    <tr><td>Pending Verification</td><td class="num">PKR {{ frc_money(($summary['pending_verification'] ?? 0)) }}</td></tr>
</table>
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>Receipt Number</th>
            <th>GR Number</th>
            <th>Enrollment ID</th>
            <th>Child Name</th>
            <th>Child Status</th>
            <th>Branch</th>
            <th>Therapist</th>
            <th>Service</th>
            <th>Total Fee</th>
            <th>Paid</th>
            <th>Remaining</th>
            <th>Payment Status</th>
            <th>Amount</th>
            <th>Verification Status</th>
            <th>Payment Method</th>
            <th>Payment Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
            <tr>
                <td class="seq">{{ $loop->iteration }}</td>
                <td>{{ $r['receipt'] }}</td>
                <td>{{ $r['child_gr_number'] }}</td>
                <td>{{ $r['enrollment_id'] }}</td>
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
                <td class="num">{{ $r['enrollment_total'] }}</td>
                <td style="font-weight:600;color:var(--success);">{{ $r['enrollment_paid'] !== '—' ? 'PKR '.$r['enrollment_paid'] :
                    '—' }}</td>
                <td style="font-weight:600;color:var(--danger);">{{ $r['enrollment_remaining'] !== '—' ? 'PKR
                    '.$r['enrollment_remaining'] : '—' }}</td>
                <td @if(strtolower($r['enrollment_payment_status'])==='partial paid' ) style="color:orange;font-weight:600;"
                    @elseif(strtolower($r['enrollment_payment_status'])==='fully paid' ) style="color:green;font-weight:600;"
                    @elseif(strtolower($r['enrollment_payment_status'])==='unpaid' ) style="color:red;font-weight:600;"
                    @endif>
                    {{ \App\Models\Payment::labelForEnrollmentPaymentStatus($r['enrollment_payment_status']) }}
                </td>
            
                <td style="font-weight:600;color:var(--teal);">{{ $r['amount'] !== '—' ? 'PKR '.$r['amount'] : '—' }}</td>
                <td @if(strtolower($r['verification_status'])==='pending_verification' ) style="color:orange;font-weight:600;"
                    @elseif(strtolower($r['verification_status'])==='rejected' ) style="color:red;font-weight:600;"
                    @elseif(strtolower($r['verification_status'])==='paid' ) style="color:green;font-weight:600;" @endif>
                    {{ \App\Models\Payment::labelForVerificationStatus($r['verification_status']) }}
                </td>
                <td>{{ $r['payment_method'] }}</td>
                <td>{{ $r['payment_date'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
